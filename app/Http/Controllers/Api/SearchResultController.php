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
}
