<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class RagService
{
    public function __construct(private readonly EmbeddingService $embeddingService) {}

    /**
     * @return array<int, array{document_id: int, title: string, source: string|null, content: string, score: float}>
     */
    public function retrieve(User $user, string $query, ?int $topK = null): array
    {
        if (! $user->hasPermission('knowledge_bases.view')) {
            return [];
        }

        $cacheKey = 'rag:'.$user->id.':'.sha1($query);
        $ttl = (int) config('ai.chat.cache_ttl_seconds', 60);

        return Cache::remember($cacheKey, $ttl, function () use ($query, $topK): array {
            $queryEmbedding = $this->embeddingService->embed($query);
            $limit = $topK ?? (int) config('ai.chat.top_k', 5);

            $chunks = DocumentChunk::query()
                ->with('document:id,knowledge_base_id,title,source,status')
                ->whereHas('document.knowledgeBase', fn ($query) => $query->where('is_active', true))
                ->whereHas('document', fn ($query) => $query->where('status', 'embedded'))
                ->limit(250)
                ->get();

            $ranked = $chunks
                ->map(function (DocumentChunk $chunk) use ($queryEmbedding): array {
                    $embedding = $chunk->embedding ? (json_decode($chunk->embedding, true) ?: []) : [];
                    $score = $this->embeddingService->cosineSimilarity($queryEmbedding, $embedding);

                    return [
                        'document_id' => $chunk->document_id,
                        'title' => (string) optional($chunk->document)->title,
                        'source' => optional($chunk->document)->source,
                        'content' => $chunk->content,
                        'score' => $score,
                    ];
                })
                ->sortByDesc('score')
                ->take(max($limit, 1))
                ->values()
                ->all();

            return $ranked;
        });
    }
}
