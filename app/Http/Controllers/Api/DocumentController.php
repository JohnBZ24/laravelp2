<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Http\Requests\Api\UpdateDocumentRequest;
use App\Jobs\IngestDocumentJob;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->user()->can('viewAny', Document::class) || abort(403);

        return response()->json(
            Document::query()->with('knowledgeBase:id,name')->latest('id')->paginate(min(max((int) $request->integer('per_page', 15), 1), 100))
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = Document::query()->create([
            ...$request->validated(),
            'status' => 'pending',
        ]);

        IngestDocumentJob::dispatch($document->id);

        return response()->json($document, 201);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $request->user()->can('view', $document) || abort(403);

        return response()->json($document->load('chunks'));
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $document->update($request->validated());

        return response()->json($document->fresh());
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $request->user()->can('delete', $document) || abort(403);
        $document->delete();

        return response()->json(status: 204);
    }

    public function ingest(Request $request, Document $document): JsonResponse
    {
        $request->user()->can('update', $document) || abort(403);

        IngestDocumentJob::dispatch($document->id);

        return response()->json(['queued' => true]);
    }
}
