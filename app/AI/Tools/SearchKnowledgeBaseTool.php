<?php

namespace App\AI\Tools;

use App\AI\Contracts\Tool;
use App\Models\User;
use App\Services\RagService;

class SearchKnowledgeBaseTool implements Tool
{
    public function __construct(private readonly RagService $ragService) {}

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function description(): string
    {
        return 'Searches the authorized knowledge base for relevant chunks.';
    }

    public function run(User $user, array $payload): array
    {
        $query = (string) ($payload['query'] ?? '');

        if ($query === '') {
            return ['results' => []];
        }

        return ['results' => $this->ragService->retrieve($user, $query)];
    }
}
