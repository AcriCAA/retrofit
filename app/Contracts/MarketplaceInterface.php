<?php

namespace App\Contracts;

use App\Models\SearchRequest;
use Illuminate\Support\Collection;

interface MarketplaceInterface
{
    public function getName(): string;

    public function getDisplayName(): string;

    public function search(SearchRequest $searchRequest): Collection;

    public function isAvailable(): bool;
}
