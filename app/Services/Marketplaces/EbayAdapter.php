<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayAdapter extends AbstractMarketplaceAdapter
{
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
        return ! empty(config('marketplaces.ebay.oauth_token'));
    }

    public function search(SearchRequest $searchRequest): Collection
    {
        if (! $this->isAvailable()) {
            return collect();
        }

        try {
            $query = $this->buildSearchQuery($searchRequest);
            $baseUrl = config('marketplaces.ebay.base_url', 'https://api.ebay.com');
            $maxResults = config('marketplaces.defaults.max_results', 20);

            $params = [
                'q' => $query,
                'limit' => $maxResults,
            ];

            // Build filter string
            $filters = [];
            if ($searchRequest->min_price || $searchRequest->max_price) {
                $priceFilter = 'price:[';
                $priceFilter .= $searchRequest->min_price ?? '0';
                $priceFilter .= '..';
                $priceFilter .= $searchRequest->max_price ?? '';
                $priceFilter .= '],priceCurrency:USD';
                $filters[] = $priceFilter;
            }
            $filters[] = 'buyingOptions:{FIXED_PRICE|AUCTION}';

            if (! empty($filters)) {
                $params['filter'] = implode(',', $filters);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('marketplaces.ebay.oauth_token'),
                'X-EBAY-C-MARKETPLACE-ID' => config('marketplaces.ebay.marketplace_id', 'EBAY_US'),
            ])->get("{$baseUrl}/buy/browse/v1/item_summary/search", $params);

            if (! $response->successful()) {
                Log::warning('eBay API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();
            $items = $data['itemSummaries'] ?? [];

            $results = collect($items)->map(fn ($item) => $this->normalizeResult($item));
            $this->logSearch($searchRequest, $results->count());

            return $results;
        } catch (\Exception $e) {
            Log::error('eBay search failed', [
                'error' => $e->getMessage(),
                'search_request_id' => $searchRequest->id,
            ]);

            return collect();
        }
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
