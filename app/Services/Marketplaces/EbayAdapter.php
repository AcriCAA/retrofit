<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayAdapter extends AbstractMarketplaceAdapter
{
    public function __construct(private EbayTokenService $tokenService) {}

    public function getName(): string
    {
        return 'ebay';
    }

    public function getDisplayName(): string
    {
        return 'eBay';
    }

    public function isAvailable(): bool
    {
        // Need App ID + Cert ID to auto-fetch tokens; fall back to static token for local dev
        return ! empty(config('marketplaces.ebay.app_id'))
            || ! empty(config('marketplaces.ebay.oauth_token'));
    }

    public function search(SearchRequest $searchRequest): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        try {
            return $this->doSearch($searchRequest);
        } catch (\Exception $e) {
            Log::error('eBay search failed', [
                'error' => $e->getMessage(),
                'search_request_id' => $searchRequest->id,
            ]);

            return collect();
        }
    }

    private function doSearch(SearchRequest $searchRequest, bool $retried = false): Collection
    {
        $token = $this->resolveToken();
        $query = $this->buildSearchQuery($searchRequest);
        $baseUrl = config('marketplaces.ebay.base_url', 'https://api.ebay.com');
        $maxResults = config('marketplaces.defaults.max_results', 20);

        $params = [
            'q' => $query,
            'limit' => $maxResults,
        ];

        $filters = [];
        if ($searchRequest->min_price || $searchRequest->max_price) {
            $filters[] = 'price:[' . ($searchRequest->min_price ?? '0') . '..' . ($searchRequest->max_price ?? '') . '],priceCurrency:USD';
        }
        $filters[] = 'buyingOptions:{FIXED_PRICE|AUCTION}';
        $params['filter'] = implode(',', $filters);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-EBAY-C-MARKETPLACE-ID' => config('marketplaces.ebay.marketplace_id', 'EBAY_US'),
        ])->get("{$baseUrl}/buy/browse/v1/item_summary/search", $params);

        // Token expired mid-cache — flush and retry once with a fresh token
        if ($response->status() === 401 && ! $retried) {
            Log::warning('eBay token rejected (401) — refreshing and retrying');
            $this->tokenService->forgetToken();
            return $this->doSearch($searchRequest, retried: true);
        }

        if (! $response->successful()) {
            Log::warning('eBay API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'search_request_id' => $searchRequest->id,
            ]);

            return collect();
        }

        $items = $response->json('itemSummaries') ?? [];
        $results = collect($items)->map(fn ($item) => $this->normalizeResult($item));
        $this->logSearch($searchRequest, $results->count());

        return $results;
    }

    private function resolveToken(): string
    {
        // Prefer auto-managed token; fall back to static .env value for local dev
        if (config('marketplaces.ebay.app_id')) {
            return $this->tokenService->getToken();
        }

        return config('marketplaces.ebay.oauth_token');
    }

    protected function normalizeResult(array $rawItem): array
    {
        return [
            'external_id' => $rawItem['itemId'] ?? '',
            'title' => $rawItem['title'] ?? '',
            'description' => $rawItem['shortDescription'] ?? null,
            'price' => isset($rawItem['price']['value']) ? (float) $rawItem['price']['value'] : null,
            'currency' => $rawItem['price']['currency'] ?? 'USD',
            'condition' => $rawItem['condition'] ?? null,
            'seller_name' => $rawItem['seller']['username'] ?? null,
            'url' => $rawItem['itemWebUrl'] ?? '',
            'image_url' => $rawItem['image']['imageUrl'] ?? ($rawItem['thumbnailImages'][0]['imageUrl'] ?? null),
        ];
    }
}
