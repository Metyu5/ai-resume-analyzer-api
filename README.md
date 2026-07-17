# AI Resume Analyzer — API

Backend API for **AI Resume Analyzer**, built with Laravel. Handles resume file uploads (PDF/DOCX), extracts their content, and analyzes them using Gemini AI to produce an ATS compatibility score along with actionable feedback.

The frontend (Next.js) for this project lives in a separate repo: [ai-resume-analyzer](https://github.com/Metyu5/ai-resume-analyzer).

## Features

- Text extraction from PDF and DOCX resume files
- AI-powered resume analysis (Gemini) against a job description
- ATS compatibility score with matched/missing keywords
- Actionable improvement suggestions
- File validation (type, max size) and structured error handling

## Tech Stack

- **Framework:** Laravel
- **AI Provider:** Google Gemini
- **Supported file formats:** PDF, DOCX (max. 5MB)

## Installation

```bash
git clone https://github.com/Metyu5/ai-resume-analyzer-api.git
cd ai-resume-analyzer-api
composer install
cp .env.example .env
php artisan key:generate
```

## Configuration

Fill in the following variables in your `.env` file:

```env
AI_PROVIDER=groq
AI_API_KEY=your_api_key_here
AI_MODEL=llama3-8b-8192

FRONTEND_URL=http://localhost:3000
```

## Running the Server

```bash
php artisan serve
```

The server will run at `http://localhost:8000`.

## API Endpoint

### `POST /api/analyze`

Analyzes a resume against a given job description.

**Request** (`multipart/form-data`)

| Field | Type | Required | Description |
|---|---|---|---|
| `resume` | file | Yes | PDF or DOCX file, max 5MB |
| `jobDescription` | string | Yes | Job description, minimum 10 characters |

**Response** (200)

```json
{
  "success": true,
  "data": {
    "score": 78,
    "keywordsMatched": 14,
    "keywordsTotal": 20,
    "summary": "...",
    "findings": [
      { "title": "...", "description": "...", "status": "good" }
    ],
    "missingKeywords": ["..."],
    "suggestions": ["..."]
  }
}
```

**Response** (422 — validation failed)

```json
{
  "message": "The resume field is required.",
  "errors": {
    "resume": ["The resume field is required."]
  }
}
```

## License

This project was built for learning and portfolio purposes.
