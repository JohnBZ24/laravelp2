<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <div class="grid grid-cols-12 gap-4 h-[calc(100vh-10rem)] w-full">
        <aside class="col-span-12 lg:col-span-3 h-full">
            <div class="rounded-2xl border bg-white dark:bg-gray-900 shadow-sm h-full flex flex-col overflow-hidden">
                <div class="p-4 border-b">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Conversations</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Recent chats and drafts</p>
                        </div>
                        <x-filament::button size="xs" wire:click="createConversation" :disabled="$loading">New Chat</x-filament::button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2 space-y-2">
                    @forelse ($this->conversations as $conversation)
                        @php($preview = $conversation->messages->first()?->content)
                        <button
                            type="button"
                            wire:click="selectConversation({{ $conversation->id }})"
                            class="w-full text-left rounded-xl px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition {{ $conversation->id === $selectedConversation ? 'bg-gray-50 dark:bg-gray-800 ring-1 ring-primary-500' : '' }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $conversation->title ?: 'Untitled conversation' }}</p>
                                <p class="shrink-0 text-[10px] text-gray-400">{{ optional($conversation->last_message_at)->diffForHumans() ?: 'just now' }}</p>
                            </div>
                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $preview ?: 'No messages yet' }}</p>
                        </button>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-3 text-xs text-gray-500 dark:text-gray-400">
                            No conversations yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>

        <section class="col-span-12 lg:col-span-9 h-full">
            <div class="rounded-2xl border bg-white dark:bg-gray-900 shadow-sm h-full flex flex-col overflow-hidden">
                <header class="p-4 border-b">
                    <h3 class="text-base font-semibold truncate text-gray-900 dark:text-gray-100">{{ $this->conversationTitle }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">AI assistant ready</p>
                </header>

                <div id="chatScroll" class="flex-1 overflow-y-auto space-y-3 p-4">
                    @forelse ($messages as $message)
                        @if ($message['role'] === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[70%]">
                                    <div class="bg-primary-600 text-white rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap break-words">{{ $message['content'] }}</div>
                                    <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $message['created_at'] }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-start">
                                <div class="max-w-[70%]">
                                    <div class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap break-words">{{ $message['content'] }}</div>
                                    <p class="text-[10px] text-gray-400 mt-1 text-left">{{ $message['created_at'] }}</p>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">Select or create a conversation to start chatting.</div>
                    @endforelse

                    @if ($loading)
                        <div class="flex justify-start">
                            <div class="max-w-[70%]">
                                <div class="bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl px-3 py-2 text-sm animate-pulse">AI is typing...</div>
                                <p class="text-[10px] text-gray-400 mt-1 text-left">now</p>
                            </div>
                        </div>
                    @endif
                </div>

                <footer class="border-t p-3">
                    <div class="flex items-end gap-2">
                        <textarea
                            wire:model.defer="inputMessage"
                            wire:keydown.enter.exact.prevent="sendMessage"
                            rows="2"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-primary-500"
                            placeholder="Type your message..."
                            @disabled($loading || ! $selectedConversation)
                        ></textarea>

                        <x-filament::button wire:click="sendMessage" :disabled="$loading || ! $selectedConversation" wire:loading.attr="disabled">
                            {{ $loading ? 'Sending...' : 'Send' }}
                        </x-filament::button>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Enter sends message, Shift+Enter adds a new line.</p>
                </footer>
            </div>
        </section>
    </div>

    <script>
        (function () {
            const scrollToBottom = () => {
                const el = document.getElementById('chatScroll');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            };

            document.addEventListener('livewire:navigated', scrollToBottom);
            document.addEventListener('message-added', scrollToBottom);
            setTimeout(scrollToBottom, 80);
        })();
    </script>
</x-filament-panels::page>
