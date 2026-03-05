<?php

namespace App\AI\Agents;

use App\AI\Contracts\ChatAgent;
use App\Models\User;

class ChatSupportAgent implements ChatAgent
{
    public function respond(User $user, array $messages, array $citations = [], array $metadata = []): array
    {
        $latestUserMessage = collect($messages)->reverse()->firstWhere('role', 'user')['content'] ?? '';

        $response = "I can help with that. Here's what I found based on your context: \n\n";
        $response .= trim((string) $latestUserMessage) !== ''
            ? 'You asked: '.trim((string) $latestUserMessage)."\n"
            : '';

        if ($citations !== []) {
            $response .= "\nRelevant references:\n";

            foreach ($citations as $index => $citation) {
                $title = $citation['title'] ?: 'Untitled document';
                $source = $citation['source'] ? " ({$citation['source']})" : '';
                $response .= ($index + 1).". {$title}{$source}\n";
            }
        } else {
            $response .= "\nI did not find a matching knowledge-base citation for this request.";
        }

        $inputTokens = max((int) ceil(strlen(json_encode($messages) ?: '') / 4), 1);
        $outputTokens = max((int) ceil(strlen($response) / 4), 1);

        return [
            'content' => trim($response),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'provider' => (string) config('ai.default_provider', 'openai'),
            'model' => (string) config('ai.default_model', 'gpt-4.1-mini'),
        ];
    }
}
