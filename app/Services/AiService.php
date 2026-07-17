<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class AiService
{
    private Client $httpClient;
    private string $apiKey;
    private string $model;
    private string $provider;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 60,
        ]);
        $this->apiKey = config('services.ai.api_key');
        $this->model = config('services.ai.model', 'llama-3.1-8b-instant');
        $this->provider = config('services.ai.provider', 'groq');
    }

    public function analyzeResume(string $resumeText, string $jobDescription): array
    {
        $cacheKey = 'analysis_' . md5($resumeText . $jobDescription);

        return Cache::remember($cacheKey, 3600, function () use ($resumeText, $jobDescription) {
            return $this->callAI($resumeText, $jobDescription);
        });
    }

    public function extractPdfText(string $filePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    public function extractDocxText(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Cannot open DOCX file');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            throw new \RuntimeException('Cannot read document.xml from DOCX');
        }

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $text = '';
        $nodes = $dom->getElementsByTagName('w:t');
        foreach ($nodes as $node) {
            $text .= $node->textContent . ' ';
        }

        return trim($text);
    }

    private function callAI(string $resumeText, string $jobDescription): array
    {
        $prompt = $this->buildPrompt($resumeText, $jobDescription);

        if ($this->provider === 'groq') {
            return $this->callGroq($prompt);
        }

        return $this->callGoogleGemini($prompt);
    }

    private function callGroq(string $prompt): array
    {
        $response = $this->httpClient->post(
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert ATS analyst. Always respond in valid JSON only, no markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 4096,
                ],
            ]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        if (!isset($body['choices'][0]['message']['content'])) {
            Log::error('Groq API unexpected response', ['response' => $body]);
            throw new \RuntimeException('Invalid response from AI service');
        }

        $aiResponse = $body['choices'][0]['message']['content'];
        $result = json_decode($aiResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse AI response', ['raw' => $aiResponse]);
            throw new \RuntimeException('Failed to parse AI response');
        }

        return $this->validateAndNormalize($result);
    }

    private function callGoogleGemini(string $prompt): array
    {
        $response = $this->httpClient->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 4096,
                        'responseMimeType' => 'application/json',
                    ],
                ],
            ]
        );

        $body = json_decode($response->getBody()->getContents(), true);

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('Gemini API unexpected response', ['response' => $body]);
            throw new \RuntimeException('Invalid response from AI service');
        }

        $aiResponse = $body['candidates'][0]['content']['parts'][0]['text'];
        $result = json_decode($aiResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse AI response', ['raw' => $aiResponse]);
            throw new \RuntimeException('Failed to parse AI response');
        }

        return $this->validateAndNormalize($result);
    }

    private function buildPrompt(string $resumeText, string $jobDescription): string
    {
        $hasJob = !empty(trim($jobDescription));

        if ($hasJob) {
            $prompt = 'You are an expert ATS (Applicant Tracking System) analyst and career coach.
Analyze the following resume against the job description and provide a comprehensive analysis.

RESUME:
{resume}

JOB DESCRIPTION:
{job}

Analyze the resume and return a JSON object with EXACTLY this structure:
{
  "score": <number 0-100>,
  "keywordsMatched": <number of keywords from job description found in resume>,
  "keywordsTotal": <total important keywords from job description>,
  "summary": "<1-2 sentence overall assessment in Indonesian>",
  "findings": [
    {
      "title": "<finding title in Indonesian>",
      "description": "<detailed description in Indonesian>",
      "status": "good" or "warning" or "bad"
    }
  ],
  "missingKeywords": ["<list of important keywords missing from resume>"],
  "suggestions": ["<actionable improvement suggestions in Indonesian>"]
}

SCORING RULES:
- ATS Format (20 points): Clean text, no tables/columns/images, proper section headers
- Keywords Match (25 points): How well resume keywords match job requirements
- Experience Quality (20 points): Quantified achievements, action verbs, valid experience
- Skills Alignment (15 points): Technical and soft skills match
- Education & Certs (10 points): Relevant education and certifications
- Contact & Professional Info (10 points): Complete contact details, LinkedIn, etc.

FINDINGS RULES:
- Return 4-8 findings minimum
- Each finding must be specific and actionable
- Mix of good (2-3), warning (2-3), and bad (1-2) findings
- Status "good" = strength to keep
- Status "warning" = area for improvement
- Status "bad" = critical issue to fix

All text responses MUST be in Bahasa Indonesia.';

            return str_replace(
                ['{resume}', '{job}'],
                [$resumeText, $jobDescription],
                $prompt
            );
        }

        $prompt = 'You are an expert career coach and resume reviewer.
Analyze the following resume and provide a comprehensive general review (no specific job description).

RESUME:
{resume}

Analyze the resume and return a JSON object with EXACTLY this structure:
{
  "score": <number 0-100>,
  "keywordsMatched": 0,
  "keywordsTotal": 0,
  "summary": "<1-2 sentence overall assessment in Indonesian>",
  "findings": [
    {
      "title": "<finding title in Indonesian>",
      "description": "<detailed description in Indonesian>",
      "status": "good" or "warning" or "bad"
    }
  ],
  "missingKeywords": [],
  "suggestions": ["<actionable improvement suggestions in Indonesian>"]
}

SCORING RULES:
- Content & Structure (30 points): Clear sections, logical flow, complete information
- Experience Quality (25 points): Quantified achievements, action verbs, relevant experience
- Skills Presentation (20 points): Well-organized skills, relevant to career field
- Education & Certs (10 points): Relevant education and certifications
- Contact & Professional Info (10 points): Complete contact details, LinkedIn, etc.
- Formatting & ATS-readiness (5 points): Clean text, parsable by ATS

FINDINGS RULES:
- Return 4-8 findings minimum
- Each finding must be specific and actionable
- Mix of good (2-3), warning (2-3), and bad (1-2) findings
- Status "good" = strength to keep
- Status "warning" = area for improvement
- Status "bad" = critical issue to fix

Since no job description is provided, set keywordsMatched to 0, keywordsTotal to 0, and missingKeywords to empty array.

All text responses MUST be in Bahasa Indonesia.';

        return str_replace('{resume}', $resumeText, $prompt);
    }

    private function validateAndNormalize(array $result): array
    {
        $result['score'] = max(0, min(100, (int) ($result['score'] ?? 0)));
        $result['keywordsMatched'] = max(0, (int) ($result['keywordsMatched'] ?? 0));
        $result['keywordsTotal'] = max(0, (int) ($result['keywordsTotal'] ?? 0));
        $result['summary'] = $result['summary'] ?? '';
        $result['missingKeywords'] = $result['missingKeywords'] ?? [];
        $result['suggestions'] = $result['suggestions'] ?? [];

        if (!isset($result['findings']) || !is_array($result['findings'])) {
            $result['findings'] = [];
        }

        $validStatuses = ['good', 'warning', 'bad'];
        $result['findings'] = array_map(function ($finding) use ($validStatuses) {
            return [
                'title' => $finding['title'] ?? 'Untitled',
                'description' => $finding['description'] ?? '',
                'status' => in_array($finding['status'] ?? '', $validStatuses)
                    ? $finding['status']
                    : 'warning',
            ];
        }, $result['findings']);

        return $result;
    }
}
