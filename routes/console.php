<?php

use App\Jobs\DispatchMarketplaceSearchesJob;
use Illuminate\Support\Facades\Schedule;

$schedule = Schedule::job(new DispatchMarketplaceSearchesJob)
    ->everyFifteenMinutes();

if ($heartbeatUrl = env('HEARTBEAT_DISPATCH_SEARCHES_URL')) {
    $schedule->pingOnSuccess($heartbeatUrl);
}
