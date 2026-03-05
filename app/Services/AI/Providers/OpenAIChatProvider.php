<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIChatProvider implements ChatProvider
{
    public function send(array $messages): array
    {
        $apiKey = (string) config('ai.providers.openai.api_key');
        $baseUrl = rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
        $chatEndpoint = '/'.ltrim((string) config('ai.providers.openai.chat_endpoint', '/chat/completions'), '/');
        $model = (string) config('ai.default_model', 'gpt-4.1-mini');
        $timeout = (int) config('ai.request_timeout_seconds', 30);
        $maxTokens = (int) config('ai.chat.max_tokens', 512);
        $temperature = (float) config('ai.chat.temperature', 0.7);

        if ($apiKey === '') {
            throw new RuntimeException('AI provider is not configured.');
        }

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name', 'Laravel Chatbot'),
            ])
            ->post("{$baseUrl}{$chatEndpoint}", [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => max($maxTokens, 64),
                'temperature' => $temperature,
            ]);

        if (! $response->successful()) {
            $errorMessage = (string) data_get($response->json(), 'error.message', $response->body());

            if ($response->status() === 402) {
                throw new RuntimeException('AI credits are insufficient on provider account. Reduce token limits or add credits.');
            }

            throw new RuntimeException('AI request failed: '.trim($errorMessage));
        }

        $response = $response->json();

        $content = (string) data_get($response, 'choices.0.message.content', '');

        return [
            'content' => trim($content),
            'input_tokens' => data_get($response, 'usage.prompt_tokens'),
            'output_tokens' => data_get($response, 'usage.completion_tokens'),
            'raw' => is_array($response) ? $response : [],
        ];
    }
}
