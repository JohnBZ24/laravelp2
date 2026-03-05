<?php

namespace App\AI\Contracts;

use App\Models\User;

interface ChatAgent
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, array{title: string, source: string|null, content: string}>  $citations
     * @param  array<string, mixed>  $metadata
     * @return array{content: string, input_tokens: int, output_tokens: int, provider: string, model: string}
     */
    public function respond(User $user, array $messages, array $citations = [], array $metadata = []): array;
}
