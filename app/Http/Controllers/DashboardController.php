<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = $user->analyses()->selectRaw('
            COUNT(*) as total_analyses,
            COALESCE(ROUND(AVG(score)), 0) as avg_score,
            COALESCE(MAX(score), 0) as best_score
        ')->first();

        $lastAnalysis = $user->analyses()
            ->select('id', 'file_name', 'score', 'created_at')
            ->orderByDesc('created_at')
            ->first();

        $history = $user->analyses()
            ->select('id', 'file_name', 'score', 'created_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'stats' => [
                    'total_analyses' => (int) $stats->total_analyses,
                    'avg_score' => (int) $stats->avg_score,
                    'best_score' => (int) $stats->best_score,
                    'last_analysis' => $lastAnalysis?->created_at?->diffForHumans(),
                ],
                'history' => $history,
            ],
        ]);
    }
}
