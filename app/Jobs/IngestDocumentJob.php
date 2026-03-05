<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IngestDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $documentId) {}

    public function handle(): void
    {
        $document = Document::query()->find($this->documentId);

        if (! $document) {
            return;
        }

        if ($document->status === 'embedded') {
            return;
        }

        $text = trim((string) ($document->raw_text ?? ''));
        $cleanText = preg_replace('/\s+/', ' ', $text) ?: '';

        $document->forceFill([
            'raw_text' => $cleanText,
            'status' => $cleanText !== '' ? 'ingested' : 'failed',
        ])->save();

        if ($cleanText !== '') {
            ChunkAndEmbedDocumentJob::dispatch($document->id);
        } else {
            Log::warning('Document ingestion failed due to empty content.', ['document_id' => $document->id]);
        }
    }
}
