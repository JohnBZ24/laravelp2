<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->user()->can('viewAny', Conversation::class) || abort(403);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $conversations = Conversation::query()
            ->where('user_id', $request->user()->id)
            ->latest('last_message_at')
            ->paginate($perPage);

        return ConversationResource::collection($conversations);
    }

    public function store(StoreConversationRequest $request, ChatService $chatService): JsonResponse
    {
        $validated = $request->validated();
        $conversation = $chatService->createConversation($request->user(), $validated['title'] ?? null);

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $request->user()->can('view', $conversation) || abort(403);

        return new ConversationResource($conversation);
    }
}
