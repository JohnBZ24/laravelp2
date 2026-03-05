<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ChunkAndEmbedDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $documentId) {}

    public function handle(EmbeddingService $embeddingService): void
    {
        $document = Document::query()->with('chunks')->find($this->documentId);

        if (! $document) {
            return;
        }

        if ($document->status === 'embedded') {
            return;
        }

        $rawText = (string) $document->raw_text;

        if ($rawText === '') {
            $document->update(['status' => 'failed']);
            Log::warning('Cannot embed an empty document.', ['document_id' => $document->id]);

            return;
        }

        $chunks = $embeddingService->chunkText($rawText);

        $document->chunks()->delete();

        foreach ($chunks as $index => $chunkText) {
            $document->chunks()->create([
                'content' => $chunkText,
                'embedding' => json_encode($embeddingService->embed($chunkText)),
                'metadata' => [
                    'chunk_index' => $index,
                    'length' => strlen($chunkText),
                ],
            ]);
        }

        $document->update(['status' => 'embedded']);
    }
}
