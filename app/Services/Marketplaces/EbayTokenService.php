<?php

namespace App\Services\Marketplaces;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EbayTokenService
{
    private const CACHE_KEY = 'ebay_oauth_token';
    private const TOKEN_BUFFER_SECONDS = 300; // refresh 5 min before real expiry

    public function getToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        return $this->fetchAndCache();
    }

    public function forgetToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function fetchAndCache(): string
    {
        $appId = config('marketplaces.ebay.app_id');
        $certId = config('marketplaces.ebay.cert_id');

        if (! $appId || ! $certId) {
            throw new RuntimeException('EBAY_APP_ID and EBAY_CERT_ID must be set to fetch an OAuth token.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$appId}:{$certId}"),
        ])->asForm()->post('https://api.ebay.com/identity/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => 'https://api.ebay.com/oauth/api_scope',
        ]);

        if (! $response->successful()) {
            Log::error('Failed to fetch eBay OAuth token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('eBay token fetch failed (HTTP ' . $response->status() . '): ' . $response->body());
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 7200;

        if (! $token) {
            throw new RuntimeException('eBay token response missing access_token field.');
        }

        $ttl = max($expiresIn - self::TOKEN_BUFFER_SECONDS, 60);
        Cache::put(self::CACHE_KEY, $token, $ttl);

        Log::info('eBay OAuth token refreshed', ['expires_in' => $expiresIn, 'cached_for' => $ttl]);

        return $token;
    }
}
