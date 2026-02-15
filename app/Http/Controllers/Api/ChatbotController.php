<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\AI\ItemChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatbotController extends Controller
{
    public function start(Request $request, ItemChatbotService $chatbot): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'message' => 'nullable|string|max:2000',
            'category_id' => 'nullable|exists:product_categories,id',
        ]);

        $imagePath = $request->file('image')->store('chat-images', 'public');

        $result = $chatbot->startConversation(
            $request->user(),
            $imagePath,
            $request->input('message'),
            $request->input('category_id')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function send(Request $request, string $uuid, ItemChatbotService $chatbot): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($conversation->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'This conversation has been completed.',
            ], 422);
        }

        $result = $chatbot->sendMessage(
            $conversation,
            $request->user(),
            $request->input('message')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function history(Request $request, string $uuid): JsonResponse
    {
        $conversation = Conversation::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->uuid,
            'status' => $conversation->status,
            'messages' => $messages,
        ]);
    }
}
