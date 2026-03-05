<?php

namespace App\Services\AI\Providers;

interface ChatProvider
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, input_tokens: int|null, output_tokens: int|null, raw: array<string, mixed>}
     */
    public function send(array $messages): array;
}
