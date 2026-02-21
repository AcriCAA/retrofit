<?php

namespace App\Services\AI;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SearchRequest;
use App\Models\SearchRequestAttribute;
use App\Models\SearchResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DismissalChatService
{
    protected AnthropicService $anthropic;

    public function __construct(AnthropicService $anthropic)
    {
        $this->anthropic = $anthropic;
    }

    public function startChat(User $user, SearchResult $result): array
    {
        $searchRequest = $result->searchRequest;

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'product_category_id' => $searchRequest->product_category_id,
            'type' => 'dismissal_feedback',
            'search_request_id' => $searchRequest->id,
            'search_result_id' => $result->id,
            'status' => 'active',
        ]);

        $initialContent = "What was wrong with this listing, or did it not fit your search criteria?";

        $assistantMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $initialContent,
        ]);

        return [
            'success' => true,
            'conversation_id' => $conversation->uuid,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $initialContent,
                'created_at' => $assistantMessage->created_at->toISOString(),
            ],
        ];
    }

    public function sendMessage(Conversation $conversation, User $user, string $userMessage): array
    {
        if (! $this->anthropic->checkRateLimit($user)) {
            $status = $this->anthropic->getRateLimitStatus($user);

            return [
                'success' => false,
                'error' => $status['reason'],
                'error_type' => 'rate_limit',
            ];
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        $searchRequest = $conversation->searchRequest;
        $dismissedResult = $conversation->searchResult;

        // Load other active results from the same search to offer bulk dismissal
        $otherResults = $searchRequest
            ? SearchResult::where('search_request_id', $searchRequest->id)
                ->whereIn('user_status', ['new', 'viewed'])
                ->where('id', '!=', $dismissedResult?->id)
                ->latest()
                ->limit(25)
                ->get()
            : collect();

        $systemPrompt = $this->buildSystemPrompt($searchRequest, $dismissedResult, $otherResults);
        $messages = $this->buildMessageHistory($conversation);
        $tools = $this->getTools();

        $criteriaRefined = null;
        $similarResults = null;

        $toolHandler = function (string $toolName, array $input) use ($searchRequest, $conversation, &$criteriaRefined, &$similarResults) {
            if ($toolName === 'refine_search_criteria') {
                return $this->handleRefineCriteria($searchRequest, $conversation, $input, $criteriaRefined, $similarResults);
            }

            return ['error' => 'Unknown tool: ' . $toolName];
        };

        $aiResult = $this->anthropic->sendMessage(
            $systemPrompt,
            $messages,
            $user,
            $conversation->id,
            'dismissal_chat',
            $tools,
            $toolHandler
        );

        if (! $aiResult['success']) {
            return $aiResult;
        }

        $assistantMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'input_tokens' => $aiResult['input_tokens'],
            'output_tokens' => $aiResult['output_tokens'],
        ]);

        $response = [
            'success' => true,
            'conversation_id' => $conversation->uuid,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $aiResult['content'],
                'created_at' => $assistantMessage->created_at->toISOString(),
            ],
        ];

        if ($criteriaRefined) {
            $conversation->update(['status' => 'completed']);
            $response['criteria_refined'] = true;
            $response['refinement_summary'] = $criteriaRefined;

            if (! empty($similarResults)) {
                $response['similar_results'] = $similarResults;
            }
        }

        return $response;
    }

    protected function buildSystemPrompt(SearchRequest $searchRequest, ?SearchResult $dismissedResult, Collection $otherResults): string
    {
        $attributes = $searchRequest->attributes->mapWithKeys(fn ($a) => [$a->key => $a->value])->toArray();
        $attributeLines = collect($attributes)->map(fn ($v, $k) => "- " . ucfirst(str_replace('_', ' ', $k)) . ": $v")->implode("\n");

        $priceRange = '';
        if ($searchRequest->min_price && $searchRequest->max_price) {
            $priceRange = "\n- Price range: \${$searchRequest->min_price} – \${$searchRequest->max_price}";
        } elseif ($searchRequest->max_price) {
            $priceRange = "\n- Max price: \${$searchRequest->max_price}";
        } elseif ($searchRequest->min_price) {
            $priceRange = "\n- Min price: \${$searchRequest->min_price}";
        }

        $resultContext = '';
        if ($dismissedResult) {
            $resultContext = <<<RESULT

The dismissed listing details:
- Title: {$dismissedResult->title}
- Price: \${$dismissedResult->price}
- Condition: {$dismissedResult->condition}
- Marketplace: {$dismissedResult->marketplace}
- Seller: {$dismissedResult->seller_name}
RESULT;
        }

        $otherResultsSection = '';
        if ($otherResults->isNotEmpty()) {
            $lines = $otherResults->map(fn ($r) => sprintf(
                '  - ID %d: "%s"%s (%s)',
                $r->id,
                $r->title,
                $r->price ? ' — $' . number_format((float) $r->price, 0) : '',
                ucfirst($r->marketplace)
            ))->implode("\n");

            $otherResultsSection = <<<OTHERS

Other active results currently showing in this search (for you to evaluate):
{$lines}

After refining the search criteria, review this list. If any of these results share the SAME underlying issue as the dismissed item (e.g., also women's clothing when the user wants men's), include their IDs in the similar_result_ids field of refine_search_criteria. The user will be shown those as candidates to dismiss in one click — they won't be dismissed automatically.
OTHERS;
        }

        return <<<PROMPT
You are RetroFit's AI assistant helping to improve a user's saved search criteria.

The user is searching for: {$searchRequest->title}
Search description: {$searchRequest->description}
Current search attributes:
{$attributeLines}{$priceRange}
{$resultContext}

The user just dismissed this listing. Your job:
1. Understand specifically WHY this listing didn't fit (wrong size, wrong color, wrong condition, too expensive, wrong style, wrong gender, not the right item, etc.)
2. Ask at most 1-2 short, focused follow-up questions if needed
3. Once you understand the issue, use the refine_search_criteria tool to update the search criteria. If you can identify other results in the list below that share the same root problem, include their IDs in similar_result_ids.
4. Keep the conversation very brief — 2-3 exchanges maximum
5. Be friendly and concise

Do NOT suggest completely different items. Focus only on understanding what specifically was wrong and refining accordingly.
{$otherResultsSection}
PROMPT;
    }

    protected function buildMessageHistory(Conversation $conversation): array
    {
        $messages = [];
        $chatMessages = $conversation->messages()->orderBy('id')->get();

        foreach ($chatMessages as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        return $messages;
    }

    protected function getTools(): array
    {
        return [
            [
                'name' => 'refine_search_criteria',
                'description' => 'Update the search criteria based on user feedback about the dismissed listing. Call this once you understand what was wrong and have enough information to refine the search.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'description' => [
                            'type' => 'string',
                            'description' => 'Updated description for the search (only if it needs changing)',
                        ],
                        'attributes_to_update' => [
                            'type' => 'object',
                            'description' => 'Key-value pairs of attributes to add or update (e.g., {"condition": "like new", "size": "32x30"})',
                        ],
                        'attributes_to_remove' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Attribute keys to remove from the search criteria',
                        ],
                        'min_price' => [
                            'type' => 'number',
                            'description' => 'Updated minimum price in USD (only if changing)',
                        ],
                        'max_price' => [
                            'type' => 'number',
                            'description' => 'Updated maximum price in USD (only if changing)',
                        ],
                        'refinement_summary' => [
                            'type' => 'string',
                            'description' => 'A brief, user-friendly summary of what was refined (e.g., "Got it — I\'ll filter out listings over $60 and prioritize like-new condition.")',
                        ],
                        'similar_result_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => 'IDs of other results from the provided list that share the same root problem as the dismissed item (e.g., all women\'s items when user wants men\'s). Only include IDs from the list you were given. These will be offered to the user for one-click bulk dismissal — not dismissed automatically.',
                        ],
                    ],
                    'required' => ['refinement_summary'],
                ],
            ],
        ];
    }

    protected function handleRefineCriteria(
        SearchRequest $searchRequest,
        Conversation $conversation,
        array $input,
        ?string &$criteriaRefined,
        ?array &$similarResults
    ): array {
        try {
            $updates = [];

            if (! empty($input['description'])) {
                $updates['description'] = $input['description'];
            }

            if (isset($input['min_price'])) {
                $updates['min_price'] = $input['min_price'];
            }

            if (isset($input['max_price'])) {
                $updates['max_price'] = $input['max_price'];
            }

            if (! empty($updates)) {
                $searchRequest->update($updates);
            }

            if (! empty($input['attributes_to_update'])) {
                foreach ($input['attributes_to_update'] as $key => $value) {
                    if ($value) {
                        SearchRequestAttribute::updateOrCreate(
                            ['search_request_id' => $searchRequest->id, 'key' => $key],
                            ['value' => $value]
                        );
                    }
                }
            }

            if (! empty($input['attributes_to_remove'])) {
                $searchRequest->attributes()
                    ->whereIn('key', $input['attributes_to_remove'])
                    ->delete();
            }

            $criteriaRefined = $input['refinement_summary'];

            // Resolve similar results — validate IDs belong to this search request
            if (! empty($input['similar_result_ids'])) {
                $ids = collect($input['similar_result_ids'])->filter()->unique()->values();

                $similar = SearchResult::whereIn('id', $ids)
                    ->where('search_request_id', $searchRequest->id)
                    ->whereIn('user_status', ['new', 'viewed'])
                    ->get(['id', 'title', 'price', 'marketplace', 'image_url', 'condition']);

                if ($similar->isNotEmpty()) {
                    $similarResults = $similar->map(fn ($r) => [
                        'id' => $r->id,
                        'title' => $r->title,
                        'price' => $r->price,
                        'marketplace' => $r->marketplace,
                        'image_url' => $r->image_url,
                        'condition' => $r->condition,
                    ])->values()->toArray();
                }
            }

            Log::info('Search criteria refined via dismissal feedback', [
                'search_request_id' => $searchRequest->id,
                'conversation_id' => $conversation->id,
                'refinement_summary' => $criteriaRefined,
                'similar_result_ids' => collect($similarResults ?? [])->pluck('id'),
            ]);

            return [
                'success' => true,
                'message' => 'Search criteria updated successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refine search criteria', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update criteria: ' . $e->getMessage(),
            ];
        }
    }
}
