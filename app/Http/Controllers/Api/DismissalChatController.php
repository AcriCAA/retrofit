<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SearchResult;
use App\Services\AI\DismissalChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DismissalChatController extends Controller
{
    public function start(Request $request, SearchResult $result, DismissalChatService $chatService): JsonResponse
    {
        if ($result->searchRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        $result->update(['user_status' => 'dismissed']);

        $response = $chatService->startChat($request->user(), $result);

        return response()->json($response, $response['success'] ? 200 : 422);
    }

    public function send(Request $request, string $uuid, DismissalChatService $chatService): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->where('type', 'dismissal_feedback')
            ->firstOrFail();

        if ($conversation->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'This conversation has been completed.',
            ], 422);
        }

        $result = $chatService->sendMessage(
            $conversation,
            $request->user(),
            $request->input('message')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
