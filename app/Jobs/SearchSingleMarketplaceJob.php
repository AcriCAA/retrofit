<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use App\Models\SearchResult;
use App\Services\Marketplaces\MarketplaceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchSingleMarketplaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public SearchRequest $searchRequest,
        public string $marketplaceName
    ) {
        $this->onQueue('marketplace-search');
    }

    public function handle(MarketplaceManager $manager): void
    {
        $adapter = $manager->adapter($this->marketplaceName);

        if (! $adapter || ! $adapter->isAvailable()) {
            return;
        }

        $results = $adapter->search($this->searchRequest);
        $newResults = [];

        foreach ($results as $item) {
            $result = SearchResult::updateOrCreate(
                [
                    'search_request_id' => $this->searchRequest->id,
                    'marketplace' => $this->marketplaceName,
                    'external_id' => $item['external_id'],
                ],
                [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'price' => $item['price'] ?? null,
                    'currency' => $item['currency'] ?? 'USD',
                    'condition' => $item['condition'] ?? null,
                    'seller_name' => $item['seller_name'] ?? null,
                    'url' => $item['url'],
                    'image_url' => $item['image_url'] ?? null,
                ]
            );

            if ($result->wasRecentlyCreated) {
                $newResults[] = $result;
            }
        }

        if (! empty($newResults)) {
            NotifyUserOfResultsJob::dispatch($this->searchRequest, $newResults);
        }
    }
}
