<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AnalyzeController extends Controller
{
    public function __construct(
        private AiService $aiService
    ) {}

    /**
     * Analyze resume against job description.
     */
    public function analyze(Request $request): JsonResponse
    {
        // Optional auth: resolve user from Bearer token if present
        $user = $request->user();
        if (! $user) {
            $token = $request->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }

        $request->validate([
            'resume' => 'required|file|max:5120|mimes:pdf,docx',
            'jobDescription' => 'nullable|string|max:5000',
        ]);

        try {
            $file = $request->file('resume');
            $jobDescription = (string) ($request->input('jobDescription') ?? '');
            $extension = strtolower($file->getClientOriginalExtension());

            $resumeText = match ($extension) {
                'pdf' => $this->aiService->extractPdfText($file->getRealPath()),
                'docx' => $this->aiService->extractDocxText($file->getRealPath()),
                default => throw new \InvalidArgumentException('Unsupported file type'),
            };

            if (empty(trim($resumeText))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengekstrak teks dari resume. Pastikan file tidak kosong atau terenkripsi.',
                ], 422);
            }

            $analysis = $this->aiService->analyzeResume($resumeText, $jobDescription);

            $resultData = [
                'score' => $analysis['score'],
                'keywordsMatched' => $analysis['keywordsMatched'],
                'keywordsTotal' => $analysis['keywordsTotal'],
                'summary' => $analysis['summary'] ?? '',
                'findings' => $analysis['findings'],
                'missingKeywords' => $analysis['missingKeywords'] ?? [],
                'suggestions' => $analysis['suggestions'] ?? [],
            ];

            // Save to history if user is authenticated
            if ($user) {
                $user->analyses()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'job_description' => $jobDescription ?: null,
                    'score' => $analysis['score'],
                    'keywords_matched' => $analysis['keywordsMatched'],
                    'keywords_total' => $analysis['keywordsTotal'],
                    'summary' => $analysis['summary'] ?? null,
                    'findings' => $analysis['findings'],
                    'missing_keywords' => $analysis['missingKeywords'] ?? [],
                    'suggestions' => $analysis['suggestions'] ?? [],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $resultData,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $apiMessage = $body['error']['message'] ?? 'Unknown API error';

            Log::error('AI API client error', [
                'status' => $status,
                'message' => $apiMessage,
            ]);

            if ($status === 401) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key tidak valid. Silakan hubungi admin.',
                ], 502);
            }

            if ($status === 400 && str_contains($apiMessage, 'decommissioned')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model AI tidak tersedia. Silakan hubungi admin.',
                ], 502);
            }

            if ($status === 429) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kuota API habis. Silakan coba lagi nanti.',
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Layanan AI mengalami gangguan. Silakan coba lagi.',
            ], 502);
        } catch (\RuntimeException $e) {
            Log::error('Resume analysis failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menganalisis resume. Silakan coba lagi.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan tak terduga. Silakan coba lagi.',
            ], 500);
        }
    }
}
