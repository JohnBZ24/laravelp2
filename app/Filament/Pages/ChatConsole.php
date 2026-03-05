<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ChatConsole extends Page
{
    protected string $view = 'filament.pages.chat-console';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'Chatbot';

    public ?int $selectedConversation = null;

    /**
     * @var array<int, array{id:int|null, role:string, content:string, created_at:string}>
     */
    public array $messages = [];

    public string $inputMessage = '';

    public bool $loading = false;

    public function mount(): void
    {
        $latestConversation = Conversation::query()
            ->where('user_id', Auth::id())
            ->latest('last_message_at')
            ->first();

        $this->selectedConversation = $latestConversation?->id;

        if ($this->selectedConversation) {
            $this->loadMessages();
        }
    }

    public function getConversationsProperty()
    {
        return Conversation::query()
            ->where('user_id', Auth::id())
            ->with(['messages' => fn ($query) => $query->latest('id')->limit(1)])
            ->latest('last_message_at')
            ->limit(50)
            ->get();
    }

    public function getConversationTitleProperty(): string
    {
        if (! $this->selectedConversation) {
            return 'No conversation selected';
        }

        $conversation = Conversation::query()
            ->where('user_id', Auth::id())
            ->find($this->selectedConversation);

        if (! $conversation) {
            return 'No conversation selected';
        }

        return $conversation->title ?: 'Untitled conversation';
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::query()
            ->where('user_id', Auth::id())
            ->findOrFail($conversationId);

        $this->selectedConversation = $conversation->id;
        $this->loadMessages();
    }

    public function createConversation(ChatService $chatService): void
    {
        /** @var User $user */
        $user = Auth::user();

        $conversation = $chatService->createConversation($user, 'New chat');
        $this->selectedConversation = $conversation->id;
        $this->messages = [];
        $this->dispatch('chat-updated');
        $this->dispatch('message-added');
    }

    public function sendMessage(ChatService $chatService): void
    {
        $content = trim($this->inputMessage);

        if (! $this->selectedConversation || $content === '') {
            return;
        }

        $conversation = Conversation::query()->where('user_id', Auth::id())->findOrFail($this->selectedConversation);

        /** @var User $user */
        $user = Auth::user();

        $this->loading = true;

        $this->messages[] = [
            'id' => null,
            'role' => 'user',
            'content' => $content,
            'created_at' => now()->format('H:i'),
        ];

        $this->inputMessage = '';
        $this->dispatch('chat-updated');
        $this->dispatch('message-added');

        try {
            $assistantMessage = $chatService->sendUserMessage($user, $conversation, $content);

            $this->messages[] = [
                'id' => $assistantMessage->id,
                'role' => $assistantMessage->role,
                'content' => $assistantMessage->content,
                'created_at' => $assistantMessage->created_at?->format('H:i') ?? now()->format('H:i'),
            ];

            Notification::make()->title('Message sent')->success()->send();
        } catch (\Throwable $exception) {
            report($exception);

            $message = 'Failed to send message. Check logs for details.';

            if ($exception instanceof RuntimeException) {
                if (str_contains($exception->getMessage(), 'not configured')) {
                    $message = 'Set OPENAI_API_KEY in your .env to enable AI replies.';
                }

                if (str_contains($exception->getMessage(), 'credits are insufficient')) {
                    $message = 'Your AI provider account has insufficient credits. Please top up or lower max tokens.';
                }

                if (str_starts_with($exception->getMessage(), 'AI request failed:')) {
                    $message = $exception->getMessage();
                }
            }

            Notification::make()->title($message)->danger()->send();
        } finally {
            $this->loading = false;
            $this->dispatch('chat-updated');
            $this->dispatch('message-added');
        }
    }

    private function loadMessages(): void
    {
        if (! $this->selectedConversation) {
            $this->messages = [];

            return;
        }

        $conversation = Conversation::query()
            ->where('user_id', Auth::id())
            ->with(['messages' => fn ($query) => $query->oldest('id')->limit(300)])
            ->findOrFail($this->selectedConversation);

        $this->messages = $conversation->messages->map(fn ($message): array => [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
        ])->all();

        $this->dispatch('chat-updated');
        $this->dispatch('message-added');
    }
}
