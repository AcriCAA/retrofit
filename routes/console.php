<?php

use App\Jobs\DispatchMarketplaceSearchesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new DispatchMarketplaceSearchesJob)->everyFifteenMinutes();
