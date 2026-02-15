<?php

namespace App\Services\Marketplaces;

use App\Contracts\MarketplaceInterface;
use App\Models\SearchRequest;
use Illuminate\Support\Facades\Log;

abstract class AbstractMarketplaceAdapter implements MarketplaceInterface
{
    protected function buildSearchQuery(SearchRequest $searchRequest): string
    {
        $parts = [$searchRequest->title];

        $keyAttributes = ['brand', 'model', 'color', 'size'];
        foreach ($searchRequest->attributes as $attr) {
            if (in_array($attr->key, $keyAttributes) && ! str_contains(strtolower($searchRequest->title), strtolower($attr->value))) {
                $parts[] = $attr->value;
            }
        }

        return implode(' ', $parts);
    }

    abstract protected function normalizeResult(array $rawItem): array;

    protected function logSearch(SearchRequest $searchRequest, int $resultCount): void
    {
        Log::info("Marketplace search completed", [
            'marketplace' => $this->getName(),
            'search_request_id' => $searchRequest->id,
            'query' => $this->buildSearchQuery($searchRequest),
            'results_count' => $resultCount,
        ]);
    }
}
