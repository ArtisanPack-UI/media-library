<?php

declare(strict_types = 1);

use ArtisanPackUI\MediaLibrary\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

/**
 * Media Library API Routes
 *
 * These routes provide API endpoints for media management operations.
 * All routes require authentication and are prefixed with 'api/'.
 *
 * @since 1.0.0
 */

Route::middleware( [ 'auth:sanctum' ] )->group( function () {
	// Media resource routes
	Route::apiResource( 'media', MediaController::class );
} );
