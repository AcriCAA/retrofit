<?php

use App\Jobs\DispatchMarketplaceSearchesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new DispatchMarketplaceSearchesJob)
    ->everyFifteenMinutes()
    ->pingOnSuccess(env('HEARTBEAT_DISPATCH_SEARCHES_URL'));
