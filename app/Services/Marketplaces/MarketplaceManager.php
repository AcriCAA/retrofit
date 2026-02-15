<?php

namespace App\Services\Marketplaces;

use App\Contracts\MarketplaceInterface;
use App\Models\SearchRequest;

class MarketplaceManager
{
    protected array $adapters = [];

    public function __construct()
    {
        $this->registerAdapter(new EbayAdapter);
        $this->registerAdapter(new PoshmarkAdapter);
        $this->registerAdapter(new MercariAdapter);
        $this->registerAdapter(new ThredUpAdapter);
        $this->registerAdapter(new GrailedAdapter);
    }

    protected function registerAdapter(MarketplaceInterface $adapter): void
    {
        $this->adapters[$adapter->getName()] = $adapter;
    }

    public function adapter(string $name): ?MarketplaceInterface
    {
        return $this->adapters[$name] ?? null;
    }

    public function available(): array
    {
        $enabled = config('marketplaces.enabled', []);

        return array_filter(
            $this->adapters,
            fn (MarketplaceInterface $adapter) => $adapter->isAvailable() && in_array($adapter->getName(), $enabled)
        );
    }

    public function forSearchRequest(SearchRequest $searchRequest): array
    {
        return $this->available();
    }
}
