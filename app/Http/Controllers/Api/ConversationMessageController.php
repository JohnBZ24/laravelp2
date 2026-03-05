<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConversationMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ConversationMessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        $request->user()->can('view', $conversation) || abort(403);

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $messages = $conversation->messages()->oldest('id')->paginate($perPage);

        return MessageResource::collection($messages);
    }

    public function store(StoreConversationMessageRequest $request, Conversation $conversation, ChatService $chatService): JsonResponse
    {
        $request->user()->can('view', $conversation) || abort(403);

        $content = (string) $request->validated('content');
        $assistantMessage = $chatService->sendUserMessage($request->user(), $conversation, $content);
        $userMessage = $conversation->messages()->where('role', 'user')->latest('id')->first();

        Log::info('AI response generated.', [
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'assistant_message_id' => $assistantMessage->id,
        ]);

        return response()->json([
            'user_message' => $userMessage ? (new MessageResource($userMessage))->resolve() : null,
            'assistant_message' => (new MessageResource($assistantMessage))->resolve(),
            'streaming_supported' => false,
        ], 201);
    }
}
