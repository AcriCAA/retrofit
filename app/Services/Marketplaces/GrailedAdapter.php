<?php

namespace App\Services\Marketplaces;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        return ! empty(config('marketplaces.grailed.algolia_app_id'))
            && ! empty(config('marketplaces.grailed.algolia_api_key'));
    }

    public function search(SearchRequest $searchRequest): Collection
    {
        try {
            $query = $this->buildGrailedQuery($searchRequest);
            $maxResults = config('marketplaces.defaults.max_results', 20);

            $params = 'hitsPerPage=' . $maxResults;

            // Price filter — Grailed stores prices in whole dollars
            $numericFilters = [];
            if ($searchRequest->min_price) {
                $numericFilters[] = 'price_i>=' . (int) $searchRequest->min_price;
            }
            if ($searchRequest->max_price) {
                $numericFilters[] = 'price_i<=' . (int) $searchRequest->max_price;
            }
            if ($numericFilters) {
                $params .= '&numericFilters=' . urlencode(implode(',', $numericFilters));
            }

            $host = config('marketplaces.grailed.algolia_host');

            $response = Http::withHeaders([
                'x-algolia-application-id' => config('marketplaces.grailed.algolia_app_id'),
                'x-algolia-api-key'        => config('marketplaces.grailed.algolia_api_key'),
                'Content-Type'             => 'application/json',
            ])->post($host . '/1/indexes/*/queries', [
                'requests' => [
                    [
                        'indexName' => config('marketplaces.grailed.algolia_index'),
                        'query'     => $query,
                        'params'    => $params,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Grailed Algolia search error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'search_request_id' => $searchRequest->id,
                ]);

                return collect();
            }

            $hits = $response->json('results.0.hits') ?? [];
            $results = collect($hits)->map(fn ($hit) => $this->normalizeResult($hit));
            $this->logSearch($searchRequest, $results->count());

            return $results;
        } catch (\Exception $e) {
            Log::error('Grailed search failed', [
                'error' => $e->getMessage(),
                'search_request_id' => $searchRequest->id,
            ]);

            return collect();
        }
    }

    private function buildGrailedQuery(SearchRequest $searchRequest): string
    {
        $attrs = $searchRequest->attributes->keyBy('key');

        $brand = $attrs->get('brand')?->value;
        $model = $attrs->get('model')?->value;

        if ($brand && $model) {
            return "{$brand} {$model}";
        }

        return $brand ?? $searchRequest->title;
    }

    protected function normalizeResult(array $hit): array
    {
        $id  = $hit['id'] ?? $hit['objectID'] ?? '';
        $url = "https://www.grailed.com/listings/{$id}";

        // Price is stored in whole dollars
        $price = isset($hit['price_i']) ? (float) $hit['price_i'] : null;

        // Cover photo
        $photo = $hit['cover_photo'] ?? null;
        $imageUrl = is_array($photo)
            ? ($photo['image_url'] ?? $photo['url'] ?? null)
            : null;

        // Designer/brand — can be array or string
        $designers = $hit['designers'] ?? [];
        $brand = is_array($designers) && ! empty($designers)
            ? ($designers[0]['name'] ?? null)
            : null;

        return [
            'external_id' => (string) $id,
            'title'       => $hit['title'] ?? '',
            'description' => $brand,
            'price'       => $price,
            'currency'    => 'USD',
            'condition'   => $hit['condition'] ?? null,
            'seller_name' => $hit['user']['username'] ?? null,
            'url'         => $url,
            'image_url'   => $imageUrl,
        ];
    }
}
