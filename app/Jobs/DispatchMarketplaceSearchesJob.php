<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchMarketplaceSearchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('marketplace-search');
    }

    public function handle(): void
    {
        SearchRequest::dueForSearch()
            ->with('attributes')
            ->chunk(50, function ($requests) {
                foreach ($requests as $request) {
                    SearchMarketplacesJob::dispatch($request);
                }
            });
    }
}
