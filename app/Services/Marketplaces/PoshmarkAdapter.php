<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;

class PoshmarkAdapter extends AbstractMarketplaceAdapter
{
    public function getName(): string
    {
        return 'poshmark';
    }

    public function getDisplayName(): string
    {
        return 'Poshmark';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('marketplaces.poshmark.api_key'));
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
