<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AI\Providers\ChatProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::factory()->for($owner)->create();

        Sanctum::actingAs($other);

        $this->getJson("/api/conversations/{$conversation->id}")
            ->assertForbidden();
    }

    public function test_user_can_create_conversation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/conversations', ['title' => 'Support thread'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Support thread');

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'Support thread',
        ]);
    }

    public function test_user_can_send_message_with_mocked_provider(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->for($user)->create();

        $provider = Mockery::mock(ChatProvider::class);
        $provider->shouldReceive('send')->once()->andReturn([
            'content' => 'Hello from assistant',
            'input_tokens' => 12,
            'output_tokens' => 7,
            'raw' => [],
        ]);
        $this->app->instance(ChatProvider::class, $provider);

        Sanctum::actingAs($user);

        $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Hi there',
        ])->assertCreated()
            ->assertJsonPath('assistant_message.role', 'assistant')
            ->assertJsonPath('assistant_message.content', 'Hello from assistant');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hi there',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Hello from assistant',
        ]);
    }

    public function test_conversation_and_message_pagination_works(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $conversations = Conversation::factory()->count(3)->for($user)->create();

        $this->getJson('/api/conversations?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $conversation = $conversations->first();

        Message::factory()->count(4)->for($conversation)->create();

        $this->getJson("/api/conversations/{$conversation->id}/messages?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
