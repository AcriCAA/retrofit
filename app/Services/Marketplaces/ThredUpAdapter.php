<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;

class ThredUpAdapter extends AbstractMarketplaceAdapter
{
    public function getName(): string
    {
        return 'thredup';
    }

    public function getDisplayName(): string
    {
        return 'ThredUp';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('marketplaces.thredup.api_key'));
    }

    public function search(SearchRequest $searchRequest): Collection
    {
        // Stub — no official public API
        return collect();
    }

    protected function normalizeResult(array $rawItem): array
    {
        return [];
    }
}
