<?php

namespace App\Console\Commands;

use App\Models\SearchRequest;
use App\Services\Marketplaces\EbayAdapter;
use App\Services\Marketplaces\EbayTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnoseSearchCommand extends Command
{
    protected $signature = 'search:diagnose
                            {id? : SearchRequest ID to diagnose (defaults to first active)}
                            {--force-search : Reset last_searched_at so it is immediately due}
                            {--run : Actually execute the eBay search and store results}';

    protected $description = 'Diagnose why a search is not returning results';

    public function __construct(private EbayTokenService $tokenService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>RetroFit Search Diagnostics</>');
        $this->line(str_repeat('─', 60));

        $this->checkEnvironment();
        $this->checkProcesses();
        $searchRequest = $this->resolveSearchRequest();

        if (! $searchRequest) {
            return self::FAILURE;
        }

        $this->checkSearchRequest($searchRequest);
        $this->probeEbayApi($searchRequest);

        if ($this->option('force-search')) {
            $this->forceResetDue($searchRequest);
        }

        if ($this->option('run')) {
            $this->runSearchNow($searchRequest);
        }

        $this->newLine();

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    // Section 1: Environment / Config
    // ─────────────────────────────────────────────────────────────

    private function checkEnvironment(): void
    {
        $this->newLine();
        $this->line('<options=bold>1. Configuration</>');

        $appId = config('marketplaces.ebay.app_id');
        $certId = config('marketplaces.ebay.cert_id');
        $staticToken = config('marketplaces.ebay.oauth_token');
        $enabled = config('marketplaces.enabled', []);

        $this->statusRow(
            'EBAY_APP_ID',
            $appId ? substr($appId, 0, 8) . '…' : 'not set',
            (bool) $appId
        );

        $this->statusRow(
            'EBAY_CERT_ID',
            $certId ? substr($certId, 0, 4) . '…' : 'not set',
            (bool) $certId
        );

        if ($appId && $certId) {
            $this->statusRow('Token mode', 'Auto-refresh (App ID + Cert ID)', true);
        } elseif ($staticToken) {
            $this->statusRow('Token mode', 'Static .env token (expires every 2 hours)', false,
                'Set EBAY_APP_ID and EBAY_CERT_ID to enable auto-refresh');
        } else {
            $this->statusRow('Token mode', 'No credentials found', false);
        }

        $this->statusRow(
            'MARKETPLACES_ENABLED',
            implode(', ', $enabled) ?: '(empty)',
            in_array('ebay', $enabled)
        );

        $this->statusRow('QUEUE_CONNECTION', config('queue.default'), true);
    }

    // ─────────────────────────────────────────────────────────────
    // Section 2: Processes
    // ─────────────────────────────────────────────────────────────

    private function checkProcesses(): void
    {
        $this->newLine();
        $this->line('<options=bold>2. Running Processes</>');

        $workerRunning = $this->isProcessRunning('artisan queue:work')
            || $this->isProcessRunning('artisan queue:listen')
            || $this->isProcessRunning('horizon');

        $this->statusRow(
            'Queue worker (queue:work / horizon)',
            $workerRunning ? 'running' : 'NOT DETECTED — searches will not execute',
            $workerRunning,
            ! $workerRunning ? 'Run: php artisan queue:work --queue=marketplace-search' : null
        );

        $scheduleRunning = $this->isProcessRunning('artisan schedule:work')
            || $this->isProcessRunning('artisan schedule:run');

        $this->statusRow(
            'Scheduler (schedule:work)',
            $scheduleRunning ? 'running' : 'not detected (may be cron-managed)',
            true
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Section 3: SearchRequest state
    // ─────────────────────────────────────────────────────────────

    private function checkSearchRequest(SearchRequest $searchRequest): void
    {
        $this->newLine();
        $this->line("<options=bold>3. Search Request #{$searchRequest->id} — {$searchRequest->title}</>");

        $this->statusRow('Status', $searchRequest->status, $searchRequest->status === 'active');

        $lastSearched = $searchRequest->last_searched_at;
        $this->statusRow(
            'Last searched at',
            $lastSearched ? $lastSearched->toDayDateTimeString() . ' (' . $lastSearched->diffForHumans() . ')' : 'never',
            true
        );

        $freq = $searchRequest->search_frequency_minutes;
        $this->statusRow('Search frequency', "{$freq} minutes", true);

        $isDue = $this->isSearchDue($searchRequest);
        $this->statusRow(
            'Currently due for search',
            $isDue ? 'YES' : 'NO — not due yet',
            $isDue,
            ! $isDue ? 'Use --force-search to reset last_searched_at and make it due immediately' : null
        );

        $resultCount = $searchRequest->results()->count();
        $this->statusRow('Total results stored', (string) $resultCount, true);

        $this->newLine();
        $this->line('  <fg=gray>Search query that would be sent to eBay:</>');
        $this->line('  <fg=yellow>  ' . $this->buildQuery($searchRequest) . '</>');
    }

    // ─────────────────────────────────────────────────────────────
    // Section 4: Live eBay API probe
    // ─────────────────────────────────────────────────────────────

    private function probeEbayApi(SearchRequest $searchRequest): void
    {
        $this->newLine();
        $this->line('<options=bold>4. Live eBay API Probe</>');

        try {
            $token = $this->resolveToken();
        } catch (\Exception $e) {
            $this->error('  Could not get token: ' . $e->getMessage());
            return;
        }

        $query = $this->buildQuery($searchRequest);
        $baseUrl = config('marketplaces.ebay.base_url', 'https://api.ebay.com');

        $params = ['q' => $query, 'limit' => 3];

        $filters = [];
        if ($searchRequest->min_price || $searchRequest->max_price) {
            $filters[] = 'price:[' . ($searchRequest->min_price ?? '0') . '..' . ($searchRequest->max_price ?? '') . '],priceCurrency:USD';
        }
        $filters[] = 'buyingOptions:{FIXED_PRICE|AUCTION}';
        $params['filter'] = implode(',', $filters);

        $url = "{$baseUrl}/buy/browse/v1/item_summary/search";

        $this->line("  <fg=gray>GET {$url}</>");
        $this->line("  <fg=gray>Query: " . http_build_query($params) . "</>");
        $this->newLine();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-EBAY-C-MARKETPLACE-ID' => config('marketplaces.ebay.marketplace_id', 'EBAY_US'),
            ])->get($url, $params);

            $status = $response->status();
            $ok = $response->successful();

            $this->statusRow('HTTP status', (string) $status, $ok);

            if (! $ok) {
                $this->newLine();
                $this->line('  <fg=red>Response body:</>');
                $this->line('  ' . json_encode($response->json() ?? $response->body(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                if ($status === 401) {
                    $this->tokenService->forgetToken();
                    $this->warn('  Token was invalid — cleared from cache. Re-run to fetch a fresh one.');
                } elseif ($status === 403) {
                    $this->warn('  Forbidden — check eBay app scopes include: https://api.ebay.com/oauth/api_scope');
                }
            } else {
                $data = $response->json();
                $total = $data['total'] ?? 0;
                $returned = count($data['itemSummaries'] ?? []);

                $this->statusRow('Total matches on eBay', number_format($total), $total > 0);
                $this->statusRow('Items in probe response', (string) $returned, $returned > 0);

                if ($returned > 0) {
                    $this->newLine();
                    $this->line('  <fg=gray>Sample results:</>');
                    foreach (array_slice($data['itemSummaries'] ?? [], 0, 3) as $i => $item) {
                        $price = $item['price']['value'] ?? 'N/A';
                        $currency = $item['price']['currency'] ?? '';
                        $this->line('  <fg=green>[' . ($i + 1) . "]</> {$item['title']}");
                        $this->line("      {$currency} {$price} — {$item['itemWebUrl']}");
                    }
                } elseif ($total === 0) {
                    $this->warn('  eBay returned 0 results — try loosening the search title.');
                }
            }
        } catch (\Exception $e) {
            $this->error('  Exception during eBay probe: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function resolveToken(): string
    {
        if (config('marketplaces.ebay.app_id')) {
            return $this->tokenService->getToken();
        }

        $static = config('marketplaces.ebay.oauth_token');

        if ($static) {
            return $static;
        }

        throw new \RuntimeException('No eBay credentials configured. Set EBAY_APP_ID and EBAY_CERT_ID in .env');
    }

    private function resolveSearchRequest(): ?SearchRequest
    {
        $id = $this->argument('id');

        if ($id) {
            $request = SearchRequest::withoutGlobalScope('scoped_to_auth_user')->find($id);
            if (! $request) {
                $this->error("SearchRequest #{$id} not found.");
                return null;
            }
            return $request;
        }

        $request = SearchRequest::withoutGlobalScope('scoped_to_auth_user')
            ->where('status', 'active')
            ->latest()
            ->first();

        if (! $request) {
            $this->error('No active search requests found. Create one first.');
            return null;
        }

        $this->line("  <fg=gray>(No ID given — using most recent active SearchRequest #{$request->id})</>");

        return $request;
    }

    private function isSearchDue(SearchRequest $searchRequest): bool
    {
        if (! $searchRequest->last_searched_at) {
            return true;
        }

        return $searchRequest->last_searched_at->addMinutes($searchRequest->search_frequency_minutes)->isPast();
    }

    private function buildQuery(SearchRequest $searchRequest): string
    {
        $parts = [$searchRequest->title];
        $keyAttributes = ['brand', 'model', 'color', 'size'];

        foreach ($searchRequest->attributes as $attr) {
            if (in_array($attr->key, $keyAttributes) && ! str_contains(strtolower($searchRequest->title), strtolower($attr->value))) {
                $parts[] = $attr->value;
            }
        }

        return implode(' ', $parts);
    }

    private function forceResetDue(SearchRequest $searchRequest): void
    {
        $this->newLine();
        $this->line('<options=bold>→ Force-resetting last_searched_at to null</>');
        $searchRequest->update(['last_searched_at' => null]);
        $this->info('  Done. The search will be picked up on the next scheduler/queue cycle.');
        $this->line('  Or run with --run to execute immediately.');
    }

    private function runSearchNow(SearchRequest $searchRequest): void
    {
        $this->newLine();
        $this->line('<options=bold>→ Running search now (synchronous, bypasses queue)</>');

        $adapter = new EbayAdapter($this->tokenService);

        if (! $adapter->isAvailable()) {
            $this->error('  eBay adapter is not available. Check credentials in .env');
            return;
        }

        $searchRequest->loadMissing('attributes');
        $results = $adapter->search($searchRequest);

        $this->statusRow('Results retrieved', (string) $results->count(), $results->isNotEmpty());

        if ($results->isNotEmpty()) {
            $new = 0;
            foreach ($results as $item) {
                $result = \App\Models\SearchResult::updateOrCreate(
                    [
                        'search_request_id' => $searchRequest->id,
                        'marketplace' => 'ebay',
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
                    $new++;
                }
            }

            $searchRequest->update(['last_searched_at' => now()]);
            $this->info("  Stored {$results->count()} results ({$new} new) in the database.");
        }
    }

    private function isProcessRunning(string $pattern): bool
    {
        $output = shell_exec('pgrep -af ' . escapeshellarg($pattern) . ' 2>/dev/null');
        return ! empty(trim((string) $output));
    }

    private function statusRow(string $label, string $value, bool $ok, ?string $hint = null): void
    {
        $icon = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $valueColor = $ok ? 'green' : 'red';
        $this->line("  {$icon} <fg=white>{$label}:</> <fg={$valueColor}>{$value}</>");
        if ($hint) {
            $this->line("      <fg=yellow>↳ {$hint}</>");
        }
    }
}
