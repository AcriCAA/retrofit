<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\SearchRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SearchRequestController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $searches = $request->user()
            ->searchRequests()
            ->withCount(['results', 'results as new_results_count' => function ($q) {
                $q->where('user_status', 'new');
            }])
            ->latest()
            ->paginate(12);

        return view('searches.index', compact('searches'));
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)->get();

        return view('searches.create', compact('categories'));
    }

    public function show(SearchRequest $searchRequest)
    {
        $this->authorize('view', $searchRequest);

        $searchRequest->load(['attributes', 'results' => function ($q) {
            $q->latest();
        }]);

        return view('searches.show', compact('searchRequest'));
    }

    public function edit(SearchRequest $searchRequest)
    {
        $this->authorize('update', $searchRequest);

        $searchRequest->load('attributes');

        return view('searches.edit', compact('searchRequest'));
    }

    public function update(Request $request, SearchRequest $searchRequest)
    {
        $this->authorize('update', $searchRequest);

        $validated = $request->validate([
            'status' => 'sometimes|in:active,paused,completed',
            'search_frequency_minutes' => 'sometimes|integer|min:15|max:10080',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
        ]);

        $searchRequest->update($validated);

        return redirect()->route('searches.show', $searchRequest)
            ->with('success', 'Search request updated.');
    }

    public function destroy(SearchRequest $searchRequest)
    {
        $this->authorize('delete', $searchRequest);

        $searchRequest->delete();

        return redirect()->route('searches.index')
            ->with('success', 'Search request deleted.');
    }
}
