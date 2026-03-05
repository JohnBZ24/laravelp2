<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AI\Providers\ChatProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(private readonly ChatProvider $chatProvider) {}

    public function createConversation(User $user, ?string $title = null): Conversation
    {
        return Conversation::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'last_message_at' => now(),
        ]);
    }

    public function sendUserMessage(User $user, Conversation $conversation, string $content): Message
    {
        return DB::transaction(function () use ($user, $conversation, $content): Message {
            $conversation->messages()->create([
                'role' => 'user',
                'content' => $content,
            ]);

            $payload = $this->chatProvider->send($this->buildMessageHistory($conversation));

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => (string) ($payload['content'] ?? ''),
                'token_usage' => [
                    'input_tokens' => $payload['input_tokens'] ?? null,
                    'output_tokens' => $payload['output_tokens'] ?? null,
                ],
            ]);

            $conversation->forceFill([
                'last_message_at' => now(),
                'title' => $conversation->title ?: Str::limit($content, 80),
            ])->save();

            return $assistantMessage;
        });
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessageHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit(20)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (Message $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }
}
