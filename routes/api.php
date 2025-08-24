<?php

use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaCategoryController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaTagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Media Library API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Media Library package. These routes
| provide RESTful endpoints for managing media items, categories, and tags.
| All routes are prefixed with 'api/media' and use API middleware.
|
*/

Route::middleware(['auth:sanctum'])->prefix('media')->group(function () {
    
    // Media routes - Full CRUD operations for media items
    Route::apiResource('items', MediaController::class)->parameters(['items' => 'media']);
    
    // Media categories routes - Full CRUD operations for media categories
    Route::apiResource('categories', MediaCategoryController::class)->parameters(['categories' => 'mediaCategory']);
    
    // Media tags routes - Full CRUD operations for media tags
    Route::apiResource('tags', MediaTagController::class)->parameters(['tags' => 'mediaTag']);
    
});

// Public media routes (no authentication required)
Route::prefix('media')->group(function () {
    
    // Public read-only access to media items
    Route::get('items', [MediaController::class, 'index'])->name('media.items.public.index');
    Route::get('items/{media}', [MediaController::class, 'show'])->name('media.items.public.show');
    
    // Public read-only access to categories
    Route::get('categories', [MediaCategoryController::class, 'index'])->name('media.categories.public.index');
    Route::get('categories/{mediaCategory}', [MediaCategoryController::class, 'show'])->name('media.categories.public.show');
    
    // Public read-only access to tags
    Route::get('tags', [MediaTagController::class, 'index'])->name('media.tags.public.index');
    Route::get('tags/{mediaTag}', [MediaTagController::class, 'show'])->name('media.tags.public.show');
    
});