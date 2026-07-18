<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $analyses = $user->analyses()
            ->select('id', 'file_name', 'score', 'keywords_matched', 'keywords_total', 'created_at')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($analyses);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $analysis = $user->analyses()
            ->select(
                'id', 'file_name', 'job_description', 'score',
                'keywords_matched', 'keywords_total', 'summary',
                'findings', 'missing_keywords', 'suggestions', 'created_at'
            )
            ->findOrFail($id);

        return response()->json(['data' => $analysis]);
    }
}
