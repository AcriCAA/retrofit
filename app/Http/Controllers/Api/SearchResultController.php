<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchResultController extends Controller
{
    public function updateStatus(Request $request, SearchResult $result): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:viewed,saved,dismissed',
        ]);

        // Ensure the user owns the search request
        if ($result->searchRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        $result->update(['user_status' => $validated['status']]);

        return response()->json(['success' => true, 'status' => $result->user_status]);
    }

    public function bulkDismiss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'result_ids' => 'required|array|max:50',
            'result_ids.*' => 'integer',
        ]);

        $userId = $request->user()->id;

        $results = SearchResult::whereIn('id', $validated['result_ids'])
            ->whereHas('searchRequest', fn ($q) => $q->where('user_id', $userId)->withoutGlobalScopes())
            ->whereIn('user_status', ['new', 'viewed'])
            ->get();

        $results->each(fn ($r) => $r->update(['user_status' => 'dismissed']));

        return response()->json([
            'success' => true,
            'dismissed_ids' => $results->pluck('id')->values(),
        ]);
    }
}
