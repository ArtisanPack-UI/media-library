<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Tags Table Migration
 *
 * Creates the media_tags table for categorizing media files with tags.
 *
 * @since 1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Database\Migrations
 */
return new class extends Migration
{
    /**
     * Runs the migrations.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('slug');
        });
    }

    /**
     * Reverses the migrations.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('media_tags');
    }
};
