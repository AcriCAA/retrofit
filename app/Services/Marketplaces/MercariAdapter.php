<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;

class MercariAdapter extends AbstractMarketplaceAdapter
{
    public function getName(): string
    {
        return 'mercari';
    }

    public function getDisplayName(): string
    {
        return 'Mercari';
    }

    public function isAvailable(): bool
    {
        return ! empty(config('marketplaces.mercari.api_key'));
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
