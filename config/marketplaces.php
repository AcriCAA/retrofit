<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Marketplaces
    |--------------------------------------------------------------------------
    */

    'enabled' => explode(',', env('MARKETPLACES_ENABLED', 'ebay,grailed')),

    /*
    |--------------------------------------------------------------------------
    | Search Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'max_results' => 20,
        'relevance_threshold' => 0.5,
        'search_frequency_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace Configurations
    |--------------------------------------------------------------------------
    */

    'ebay' => [
        'app_id' => env('EBAY_APP_ID'),
        'cert_id' => env('EBAY_CERT_ID'),
        'dev_id' => env('EBAY_DEV_ID'),
        'oauth_token' => env('EBAY_OAUTH_TOKEN'),
        'marketplace_id' => env('EBAY_MARKETPLACE_ID', 'EBAY_US'),
        'base_url' => 'https://api.ebay.com',
    ],

    'poshmark' => [
        'api_key' => env('POSHMARK_API_KEY'),
    ],

    'mercari' => [
        'api_key' => env('MERCARI_API_KEY'),
    ],

    'thredup' => [
        'api_key' => env('THREDUP_API_KEY'),
    ],

    'grailed' => [
        'algolia_app_id' => env('GRAILED_ALGOLIA_APP_ID'),
        'algolia_api_key' => env('GRAILED_ALGOLIA_API_KEY'),
        'algolia_index' => env('GRAILED_ALGOLIA_INDEX', 'Listing_production'),
        'algolia_host' => env('GRAILED_ALGOLIA_HOST'),
    ],

];
