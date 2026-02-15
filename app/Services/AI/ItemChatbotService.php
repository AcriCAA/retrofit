<?php

namespace App\Services\AI;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\ProductCategory;
use App\Models\SearchRequest;
use App\Models\SearchRequestAttribute;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ItemChatbotService
{
    protected AnthropicService $anthropic;

    public function __construct(AnthropicService $anthropic)
    {
        $this->anthropic = $anthropic;
    }

    public function startConversation(User $user, string $imagePath, ?string $message = null, ?int $categoryId = null): array
    {
        $category = $categoryId
            ? ProductCategory::find($categoryId)
            : ProductCategory::where('slug', 'clothing')->first();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'product_category_id' => $category?->id,
            'image_path' => $imagePath,
            'status' => 'active',
        ]);

        $userMessage = $message ?: 'I uploaded a photo of an item I\'m looking for. Can you help me identify it and set up a search?';

        return $this->sendMessage($conversation, $user, $userMessage, true);
    }

    public function sendMessage(Conversation $conversation, User $user, string $userMessage, bool $includeImage = false): array
    {
        if (! $this->anthropic->checkRateLimit($user)) {
            $status = $this->anthropic->getRateLimitStatus($user);

            return [
                'success' => false,
                'error' => $status['reason'],
                'error_type' => 'rate_limit',
            ];
        }

        // Save user message
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        $systemPrompt = $this->buildSystemPrompt($conversation);
        $messages = $this->buildMessageHistory($conversation, $includeImage);
        $tools = $this->getTools();

        $searchCreated = null;

        $toolHandler = function (string $toolName, array $input) use ($conversation, $user, &$searchCreated) {
            if ($toolName === 'create_search_request') {
                return $this->handleCreateSearchRequest($conversation, $user, $input, $searchCreated);
            }

            return ['error' => 'Unknown tool: ' . $toolName];
        };

        $result = $this->anthropic->sendMessage(
            $systemPrompt,
            $messages,
            $user,
            $conversation->id,
            'item_chat',
            $tools,
            $toolHandler
        );

        if (! $result['success']) {
            return $result;
        }

        // Save assistant response
        $assistantMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $result['content'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
        ]);

        $response = [
            'success' => true,
            'conversation_id' => $conversation->uuid,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $result['content'],
                'created_at' => $assistantMessage->created_at->toISOString(),
            ],
        ];

        if ($searchCreated) {
            $conversation->update(['status' => 'completed']);
            $response['search_request'] = [
                'id' => $searchCreated->id,
                'title' => $searchCreated->title,
            ];
        }

        return $response;
    }

    protected function buildSystemPrompt(Conversation $conversation): string
    {
        $category = $conversation->productCategory;
        $categoryPrompt = $category?->chatbot_prompt_config['system_prompt_suffix'] ?? '';

        return <<<PROMPT
You are RetroFit's AI shopping assistant, helping users find discontinued or hard-to-find items on secondhand marketplaces (eBay, Poshmark, Mercari, ThredUp, Grailed).

Your workflow:
1. Analyze the uploaded photo carefully
2. Identify key details visible in the image (brand, style, color, material, era)
3. Ask follow-up questions 1-2 at a time to fill in missing details
4. Once you have enough information, confirm the details with the user
5. When the user confirms, use the create_search_request tool to create the search

Key information to gather:
- Brand name
- Specific model/style name if identifiable
- Size (be specific — e.g., "32x30" for pants, "M/38R" for jackets)
- Color/pattern/wash
- Material/fabric
- Fit/cut style
- Condition preference (any, like new, good, etc.)
- Price range the user is willing to pay

Be conversational and enthusiastic. Show expertise in identifying items from photos. If you're uncertain about something, say so and ask.

{$categoryPrompt}
PROMPT;
    }

    protected function buildMessageHistory(Conversation $conversation, bool $includeImage = false): array
    {
        $messages = [];
        $chatMessages = $conversation->messages()->orderBy('id')->get();

        foreach ($chatMessages as $index => $msg) {
            if ($msg->role === 'user' && $index === 0 && $includeImage && $conversation->image_path) {
                // First user message: include the image
                $messages[] = [
                    'role' => 'user',
                    'content' => [
                        $this->buildImageContent($conversation->image_path),
                        [
                            'type' => 'text',
                            'text' => $msg->content,
                        ],
                    ],
                ];
            } else {
                $messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            }
        }

        return $messages;
    }

    protected function buildImageContent(string $imagePath): array
    {
        $fullPath = Storage::disk('public')->path($imagePath);
        $imageData = base64_encode(file_get_contents($fullPath));
        $mimeType = mime_content_type($fullPath);

        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $mimeType,
                'data' => $imageData,
            ],
        ];
    }

    protected function getTools(): array
    {
        return [
            [
                'name' => 'create_search_request',
                'description' => 'Create a search request to find a specific item across secondhand marketplaces. Call this when you have gathered enough details from the user and they have confirmed the search parameters.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'A concise, searchable title for the item (e.g., "Diesel Zatiny 008J1 Dark Wash Bootcut Jeans")',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'A detailed description of the item being searched for',
                        ],
                        'attributes' => [
                            'type' => 'object',
                            'description' => 'Key-value pairs of item attributes',
                            'properties' => [
                                'brand' => ['type' => 'string'],
                                'model' => ['type' => 'string'],
                                'size' => ['type' => 'string'],
                                'color' => ['type' => 'string'],
                                'material' => ['type' => 'string'],
                                'fit' => ['type' => 'string'],
                                'style' => ['type' => 'string'],
                                'gender' => ['type' => 'string'],
                                'condition' => ['type' => 'string'],
                            ],
                        ],
                        'min_price' => [
                            'type' => 'number',
                            'description' => 'Minimum price in USD (optional)',
                        ],
                        'max_price' => [
                            'type' => 'number',
                            'description' => 'Maximum price in USD (optional)',
                        ],
                    ],
                    'required' => ['title', 'description', 'attributes'],
                ],
            ],
        ];
    }

    protected function handleCreateSearchRequest(
        Conversation $conversation,
        User $user,
        array $input,
        ?SearchRequest &$searchCreated
    ): array {
        try {
            $searchRequest = SearchRequest::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'product_category_id' => $conversation->product_category_id,
                'title' => $input['title'],
                'description' => $input['description'] ?? null,
                'image_path' => $conversation->image_path,
                'status' => 'active',
                'search_frequency_minutes' => config('marketplaces.defaults.search_frequency_minutes', 60),
                'min_price' => $input['min_price'] ?? null,
                'max_price' => $input['max_price'] ?? null,
            ]);

            if (! empty($input['attributes'])) {
                foreach ($input['attributes'] as $key => $value) {
                    if ($value) {
                        SearchRequestAttribute::create([
                            'search_request_id' => $searchRequest->id,
                            'key' => $key,
                            'value' => $value,
                        ]);
                    }
                }
            }

            $searchCreated = $searchRequest;

            Log::info('Search request created via chatbot', [
                'search_request_id' => $searchRequest->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'search_request_id' => $searchRequest->id,
                'message' => 'Search request created successfully. The user will be notified when matches are found.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create search request', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create search request: ' . $e->getMessage(),
            ];
        }
    }
}
