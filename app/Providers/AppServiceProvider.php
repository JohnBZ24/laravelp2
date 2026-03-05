<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\KnowledgeBase;
use App\Models\Message;
use App\Policies\ConversationPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\KnowledgeBasePolicy;
use App\Policies\MessagePolicy;
use App\Services\AI\Providers\ChatProvider;
use App\Services\AI\Providers\OpenAIChatProvider;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChatProvider::class, OpenAIChatProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(KnowledgeBase::class, KnowledgeBasePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::define('knowledge_bases.manage', fn (User $user): bool => $user->hasPermission('knowledge_bases.manage'));

        RateLimiter::for('chat', fn (Request $request): Limit => Limit::perMinute((int) config('ai.chat.max_messages_per_minute', 20))
            ->by((string) optional($request->user())->id ?: $request->ip()));
    }
}
