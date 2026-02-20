<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Messages\Message;
use Anthropic\Messages\TextBlock;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    protected Client $client;
    protected string $model;
    protected int $maxTokens;

    public function __construct()
    {
        $apiKey = config('ai.anthropic.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured');
        }

        $this->client = new Client(apiKey: $apiKey);
        $this->model = config('ai.anthropic.model', 'claude-sonnet-4-20250514');
        $this->maxTokens = config('ai.anthropic.max_tokens', 4096);
    }

    public function sendMessage(
        string $systemPrompt,
        array $messages,
        ?User $user = null,
        ?int $conversationId = null,
        string $feature = 'item_chat',
        ?array $tools = null,
        ?callable $toolHandler = null
    ): array {
        try {
            $response = $this->callApiWithRetry($messages, $systemPrompt, $tools);

            $inputTokens = $response->usage->inputTokens ?? 0;
            $outputTokens = $response->usage->outputTokens ?? 0;

            if ($response->stopReason === 'tool_use' && $toolHandler) {
                return $this->handleToolUse(
                    $response,
                    $systemPrompt,
                    $messages,
                    $user,
                    $conversationId,
                    $feature,
                    $tools,
                    $toolHandler,
                    $inputTokens,
                    $outputTokens
                );
            }

            $content = $this->extractTextContent($response);

            if ($user) {
                $this->logUsage($user, $conversationId, $feature, $inputTokens, $outputTokens);
            }

            return [
                'success' => true,
                'content' => $content,
                'model' => $response->model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'stop_reason' => $response->stopReason,
                'tool_results' => [],
            ];
        } catch (\Anthropic\RateLimitError $e) {
            Log::warning('Anthropic rate limit exceeded', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please wait a moment before trying again.',
                'error_type' => 'rate_limit',
            ];
        } catch (\Anthropic\AuthenticationError $e) {
            Log::error('Anthropic authentication error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Authentication failed. Please check API configuration.',
                'error_type' => 'auth',
            ];
        } catch (\Exception $e) {
            Log::error('Anthropic API error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'error' => 'An error occurred while processing your request. Please try again.',
                'error_type' => 'general',
            ];
        }
    }

    protected function handleToolUse(
        Message $response,
        string $systemPrompt,
        array $messages,
        ?User $user,
        ?int $conversationId,
        string $feature,
        array $tools,
        callable $toolHandler,
        int $accumulatedInputTokens,
        int $accumulatedOutputTokens
    ): array {
        $allToolResults = [];
        $currentResponse = $response;
        $currentMessages = $messages;
        $totalInputTokens = $accumulatedInputTokens;
        $totalOutputTokens = $accumulatedOutputTokens;
        $maxRounds = 5;

        for ($round = 0; $round < $maxRounds; $round++) {
            $assistantContent = [];
            $toolResultContent = [];

            foreach ($currentResponse->content as $block) {
                if ($block->type === 'tool_use') {
                    $assistantContent[] = [
                        'type' => 'tool_use',
                        'id' => $block->id,
                        'name' => $block->name,
                        'input' => $block->input,
                    ];

                    $result = $toolHandler($block->name, $block->input);
                    $allToolResults[] = [
                        'tool_name' => $block->name,
                        'result' => $result,
                    ];

                    $toolResultContent[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block->id,
                        'content' => json_encode($result),
                    ];
                } elseif ($block->type === 'text') {
                    $assistantContent[] = [
                        'type' => 'text',
                        'text' => $block->text,
                    ];
                }
            }

            $currentMessages = array_merge($currentMessages, [
                ['role' => 'assistant', 'content' => $assistantContent],
                ['role' => 'user', 'content' => $toolResultContent],
            ]);

            $currentResponse = $this->callApiWithRetry($currentMessages, $systemPrompt, $tools);

            $totalInputTokens += $currentResponse->usage->inputTokens ?? 0;
            $totalOutputTokens += $currentResponse->usage->outputTokens ?? 0;

            if ($currentResponse->stopReason !== 'tool_use') {
                break;
            }
        }

        if ($user) {
            $this->logUsage($user, $conversationId, $feature, $totalInputTokens, $totalOutputTokens);
        }

        $content = $this->extractTextContent($currentResponse);

        return [
            'success' => true,
            'content' => $content,
            'model' => $currentResponse->model,
            'input_tokens' => $totalInputTokens,
            'output_tokens' => $totalOutputTokens,
            'stop_reason' => $currentResponse->stopReason,
            'tool_results' => $allToolResults,
        ];
    }

    protected function callApiWithRetry(array $messages, string $systemPrompt, ?array $tools): Message
    {
        $maxRetries = 3;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->client->messages->create(
                    maxTokens: $this->maxTokens,
                    messages: $messages,
                    model: $this->model,
                    system: $systemPrompt,
                    tools: $tools,
                );
            } catch (\Exception $e) {
                $isOverloaded = str_contains($e->getMessage(), 'overloaded_error')
                    || str_contains($e->getMessage(), '529');

                if (!$isOverloaded || $attempt === $maxRetries) {
                    throw $e;
                }

                $delaySeconds = 2 ** $attempt; // 2s, 4s, 8s
                Log::warning('Anthropic API overloaded, retrying', [
                    'attempt' => $attempt,
                    'delay_seconds' => $delaySeconds,
                ]);
                sleep($delaySeconds);
            }
        }
    }

    protected function extractTextContent(Message $response): string
    {
        $textParts = [];

        foreach ($response->content as $block) {
            if ($block instanceof TextBlock) {
                $textParts[] = $block->text;
            }
        }

        return implode("\n", $textParts);
    }

    protected function logUsage(
        User $user,
        ?int $conversationId,
        string $feature,
        int $inputTokens,
        int $outputTokens
    ): void {
        $cost = AiUsageLog::calculateCost($this->model, $inputTokens, $outputTokens);

        AiUsageLog::create([
            'user_id' => $user->id,
            'conversation_id' => $conversationId,
            'feature' => $feature,
            'model' => $this->model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd' => $cost,
        ]);
    }

    public function checkRateLimit(User $user): bool
    {
        return $this->getRateLimitStatus($user)['allowed'];
    }

    public function getRateLimitStatus(User $user): array
    {
        $requestsPerMinute = config('ai.rate_limits.requests_per_minute', 10);
        $tokensPerDay = config('ai.rate_limits.tokens_per_day', 500000);

        $recentRequests = AiUsageLog::forUser($user->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        $todayTokens = AiUsageLog::getTodayTokensForUser($user->id);

        $allowed = true;
        $reason = null;

        if ($recentRequests >= $requestsPerMinute) {
            $allowed = false;
            $reason = 'Too many requests. Please wait a moment before trying again.';
        } elseif ($todayTokens >= $tokensPerDay) {
            $allowed = false;
            $reason = 'Daily token limit reached. Usage resets at midnight.';
        }

        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'tokens_used_today' => $todayTokens,
            'tokens_limit' => $tokensPerDay,
            'tokens_remaining' => max(0, $tokensPerDay - $todayTokens),
            'requests_this_minute' => $recentRequests,
            'requests_limit' => $requestsPerMinute,
        ];
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
