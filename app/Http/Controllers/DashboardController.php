<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $activeSearches = $user->searchRequests()->where('status', 'active')->count();
        $newResults = $user->searchRequests()
            ->withCount(['results as new_results_count' => function ($q) {
                $q->where('user_status', 'new');
            }])
            ->get()
            ->sum('new_results_count');

        $recentResults = \App\Models\SearchResult::whereIn(
            'search_request_id',
            $user->searchRequests()->pluck('id')
        )
            ->with('searchRequest')
            ->latest()
            ->take(12)
            ->get();

        return view('dashboard', compact('activeSearches', 'newResults', 'recentResults'));
    }
}
