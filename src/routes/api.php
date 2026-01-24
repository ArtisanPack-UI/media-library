<?php

use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaFolderController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaTagController;
use Illuminate\Support\Facades\Route;

/**
 * Media Library API Routes
 *
 * These routes provide API endpoints for media management operations.
 * All routes require authentication and are prefixed with 'api/'.
 *
 * @since 1.0.0
 */
Route::middleware(['auth:sanctum'])->group(function () {
    // Media Folder resource routes (before media routes to avoid conflicts)
    Route::post('media/folders/{id}/move', [MediaFolderController::class, 'move'])->name('media.folders.move');
    Route::apiResource('media/folders', MediaFolderController::class);

    // Media Tag resource routes
    Route::post('media/tags/{id}/attach', [MediaTagController::class, 'attach'])->name('media.tags.attach');
    Route::post('media/tags/{id}/detach', [MediaTagController::class, 'detach'])->name('media.tags.detach');
    Route::apiResource('media/tags', MediaTagController::class);

    // Media download route (API)
    Route::get('media/{id}/download', [MediaController::class, 'download'])->name('api.media.download');

    // Media resource routes (last to avoid catching folder/tag routes)
    Route::apiResource('media', MediaController::class);
});
