<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreKnowledgeBaseRequest;
use App\Http\Requests\Api\UpdateKnowledgeBaseRequest;
use App\Jobs\RebuildKnowledgeBaseIndexJob;
use App\Models\KnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->user()->can('viewAny', KnowledgeBase::class) || abort(403);

        return response()->json(
            KnowledgeBase::query()->latest('id')->paginate(min(max((int) $request->integer('per_page', 15), 1), 100))
        );
    }

    public function store(StoreKnowledgeBaseRequest $request): JsonResponse
    {
        $knowledgeBase = KnowledgeBase::query()->create($request->validated());

        return response()->json($knowledgeBase, 201);
    }

    public function show(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $request->user()->can('view', $knowledgeBase) || abort(403);

        return response()->json($knowledgeBase);
    }

    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $knowledgeBase->update($request->validated());

        return response()->json($knowledgeBase->fresh());
    }

    public function destroy(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $request->user()->can('delete', $knowledgeBase) || abort(403);
        $knowledgeBase->delete();

        return response()->json(status: 204);
    }

    public function rebuild(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $request->user()->can('update', $knowledgeBase) || abort(403);

        RebuildKnowledgeBaseIndexJob::dispatch($knowledgeBase->id);

        return response()->json(['queued' => true]);
    }
}
