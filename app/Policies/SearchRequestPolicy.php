<?php

namespace App\Policies;

use App\Models\SearchRequest;
use App\Models\User;

class SearchRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SearchRequest $searchRequest): bool
    {
        return $user->id === $searchRequest->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SearchRequest $searchRequest): bool
    {
        return $user->id === $searchRequest->user_id;
    }

    public function delete(User $user, SearchRequest $searchRequest): bool
    {
        return $user->id === $searchRequest->user_id;
    }
}
