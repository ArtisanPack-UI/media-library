<?php

use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaCategoryController;
use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaTagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Media Library Web Routes  
|--------------------------------------------------------------------------
|
| These are the web routes for the Media Library package that require
| authentication. These routes provide RESTful endpoints accessible
| via web interface with proper authentication middleware.
|
*/

Route::middleware(['web', 'auth'])->prefix('api/media')->group(function () {
    
    // Media routes - Full CRUD operations for media items
    Route::apiResource('items', MediaController::class)->parameters(['items' => 'media']);
    
    // Media categories routes - Full CRUD operations for media categories
    Route::apiResource('categories', MediaCategoryController::class)->parameters(['categories' => 'mediaCategory']);
    
    // Media tags routes - Full CRUD operations for media tags
    Route::apiResource('tags', MediaTagController::class)->parameters(['tags' => 'mediaTag']);
    
});