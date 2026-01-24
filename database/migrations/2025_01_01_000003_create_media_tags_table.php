<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Tags Table Migration
 *
 * Creates the media_tags table for categorizing media files with tags.
 * This migration handles both fresh installs and upgrades from v1.0.
 *
 * v1.0 → v1.1 Schema Changes:
 * - Added: description
 *
 * @since 1.0.0
 * @since 1.1.0 Added upgrade support for existing installations.
 */
return new class extends Migration
{
    /**
     * Runs the migrations.
     *
     * @since 1.0.0
     * @since 1.1.0 Added Schema::hasTable check for upgrade support.
     */
    public function up(): void
    {
        if (Schema::hasTable('media_tags')) {
            // Upgrade existing table - add any missing columns
            Schema::table('media_tags', function (Blueprint $table) {
                if (! Schema::hasColumn('media_tags', 'description')) {
                    $table->text('description')->nullable()->after('slug');
                }
            });

            return;
        }

        // Fresh install - create the table
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
     */
    public function down(): void
    {
        Schema::dropIfExists('media_tags');
    }
};
