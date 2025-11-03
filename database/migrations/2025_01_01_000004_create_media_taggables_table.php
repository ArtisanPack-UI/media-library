<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Taggables Table Migration
 *
 * Creates the media_taggables pivot table for many-to-many relationships
 * between media and tags.
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
        Schema::create('media_taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->timestamps();

            // Composite unique constraint
            $table->unique(['media_id', 'media_tag_id']);
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
        Schema::dropIfExists('media_taggables');
    }
};
