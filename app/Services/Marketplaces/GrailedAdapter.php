<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;

class GrailedAdapter extends AbstractMarketplaceAdapter
{
    public function getName(): string
    {
        return 'grailed';
    }

    public function getDisplayName(): string
    {
        return 'Grailed';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('marketplaces.grailed.api_key'));
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
