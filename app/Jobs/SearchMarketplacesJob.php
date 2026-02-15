<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use App\Services\Marketplaces\MarketplaceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchMarketplacesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public SearchRequest $searchRequest)
    {
        $this->onQueue('marketplace-search');
    }

    public function handle(MarketplaceManager $manager): void
    {
        $adapters = $manager->forSearchRequest($this->searchRequest);

        foreach ($adapters as $adapter) {
            SearchSingleMarketplaceJob::dispatch($this->searchRequest, $adapter->getName());
        }

        $this->searchRequest->update(['last_searched_at' => now()]);
    }
}
