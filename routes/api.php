<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConversationMessageController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [ConversationMessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [ConversationMessageController::class, 'store'])
        ->middleware('throttle:chat');

    Route::middleware('can:knowledge_bases.manage')->group(function (): void {
        Route::get('/knowledge-bases', [KnowledgeBaseController::class, 'index']);
        Route::post('/knowledge-bases', [KnowledgeBaseController::class, 'store']);
        Route::get('/knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'show']);
        Route::match(['put', 'patch'], '/knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'update']);
        Route::delete('/knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy']);
        Route::post('/knowledge-bases/{knowledgeBase}/rebuild', [KnowledgeBaseController::class, 'rebuild']);

        Route::get('/documents', [DocumentController::class, 'index']);
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::get('/documents/{document}', [DocumentController::class, 'show']);
        Route::match(['put', 'patch'], '/documents/{document}', [DocumentController::class, 'update']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
        Route::post('/documents/{document}/ingest', [DocumentController::class, 'ingest']);
    });

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
