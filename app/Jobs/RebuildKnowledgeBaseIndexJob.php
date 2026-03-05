<?php

namespace App\Jobs;

use App\Models\KnowledgeBase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RebuildKnowledgeBaseIndexJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $knowledgeBaseId) {}

    public function handle(): void
    {
        $knowledgeBase = KnowledgeBase::query()->with('documents')->find($this->knowledgeBaseId);

        if (! $knowledgeBase) {
            return;
        }

        $knowledgeBase->documents->each(function ($document): void {
            IngestDocumentJob::dispatch($document->id);
        });
    }
}
