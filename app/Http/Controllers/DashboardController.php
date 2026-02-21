<?php

namespace App\Http\Controllers;

use App\Models\SearchRequest;
use App\Models\SearchResult;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $userSearchRequestIds = SearchRequest::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $activeSearches = SearchRequest::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->count();
        $newResults = SearchResult::whereIn('search_request_id', $userSearchRequestIds)
            ->where('user_status', 'new')
            ->count();

        $recentResults = SearchResult::whereIn('search_request_id', $userSearchRequestIds)
            ->with('searchRequest')
            ->latest()
            ->take(12)
            ->get();

        return view('dashboard', compact('activeSearches', 'newResults', 'recentResults'));
    }
}
