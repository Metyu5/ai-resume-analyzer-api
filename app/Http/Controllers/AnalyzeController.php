<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            return response()->json([
                'success' => true,
                'data' => [
                    'score' => $analysis['score'],
                    'keywordsMatched' => $analysis['keywordsMatched'],
                    'keywordsTotal' => $analysis['keywordsTotal'],
                    'summary' => $analysis['summary'] ?? '',
                    'findings' => $analysis['findings'],
                    'missingKeywords' => $analysis['missingKeywords'] ?? [],
                    'suggestions' => $analysis['suggestions'] ?? [],
                ],
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
