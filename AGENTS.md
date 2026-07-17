# AI Resume Analyzer API

Backend Laravel 10 REST API for AI Resume Analyzer. Pairs with Next.js frontend at `../ai-resumer-analyzer/`.

## Quick Reference

```bash
# Dev server (Laragon: http://ai-resume-analyzer-api.test)
php artisan serve

# Testing
php artisan test
php artisan test --filter=TestClassName

# Code style
./vendor/bin/pint

# Clear cache
php artisan cache:clear && php artisan config:clear && php artisan route:clear
```

## Environment

- **PHP**: ^8.1 (Laragon ships 8.1+)
- **Database**: MySQL 8 (default: `laravel` database on `127.0.0.1:3306`, user `root` no password)
- **AI Service**: Google Gemini API (set `GEMINI_API_KEY` in `.env`)
- **Env file**: `.env`

## API Endpoints

### `POST /api/analyze`
- **Input**: `resume` (PDF/DOCX, max 5MB) + `jobDescription` (string)
- **Output**: `{ success, data: { score, keywordsMatched, keywordsTotal, summary, findings, missingKeywords, suggestions } }`
- **Auth**: None required (public endpoint)
- **Cache**: Results cached for 1 hour (key: md5 of resume text + job description)

## Architecture

- `app/Services/AiService.php` — AI provider integration (Groq/Gemini via Guzzle HTTP)
- `app/Http/Controllers/AnalyzeController.php` — API controller
- `app/Http/Middleware/` — CORS, auth middleware
- `routes/api.php` — API routes (prefix: `/api`)
- `config/services.php` — AI config (`AI_API_KEY`, `AI_MODEL`, `AI_PROVIDER`)
- `config/cors.php` — CORS config (allows `localhost:3000`)

## Dependencies

- `guzzlehttp/guzzle` — HTTP client for Gemini API
- `smalot/pdfparser` — PDF text extraction
- Native `ZipArchive` — DOCX text extraction

## Key Notes

- DOCX extraction uses `ZipArchive` (no extra package needed)
- Gemini response validated and normalized before returning
- All AI responses in Bahasa Indonesia
- Error responses include user-friendly Indonesian messages
