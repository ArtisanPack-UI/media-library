<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Taggables Table Migration
 *
 * Creates the media_taggables pivot table for many-to-many relationships
 * between media and tags.
 * This migration handles both fresh installs and upgrades from v1.0.
 *
 * v1.0 → v1.1 Schema Changes:
 * - Renamed table: media_media_tag → media_taggables (if old table exists)
 * - Renamed column: tag_id → media_tag_id (if old column exists)
 * - Added: id, timestamps
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
     * @since 1.1.0 Added Schema::hasTable check and upgrade logic.
     */
    public function up(): void
    {
        // Check if old pivot table exists and migrate data
        if (Schema::hasTable('media_media_tag') && ! Schema::hasTable('media_taggables')) {
            $this->migrateFromOldPivotTable();

            return;
        }

        // Check if media_taggables already exists (upgrade scenario)
        if (Schema::hasTable('media_taggables')) {
            $this->upgradeExistingTable();

            return;
        }

        // Fresh install - create the table
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
     * Migrates data from old media_media_tag pivot table to new media_taggables.
     *
     * @since 1.1.0
     */
    protected function migrateFromOldPivotTable(): void
    {
        // Create new table
        Schema::create('media_taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['media_id', 'media_tag_id']);
        });

        // Migrate data from old table
        $oldColumnName = Schema::hasColumn('media_media_tag', 'tag_id') ? 'tag_id' : 'media_tag_id';

        DB::statement("
			INSERT INTO media_taggables (media_id, media_tag_id, created_at, updated_at)
			SELECT media_id, {$oldColumnName}, NOW(), NOW()
			FROM media_media_tag
		");

        // Drop old table
        Schema::dropIfExists('media_media_tag');
    }

    /**
     * Upgrades existing media_taggables table if needed.
     *
     * @since 1.1.0
     */
    protected function upgradeExistingTable(): void
    {
        Schema::table('media_taggables', function (Blueprint $table) {
            // Add id column if missing
            if (! Schema::hasColumn('media_taggables', 'id')) {
                $table->id()->first();
            }

            // Rename tag_id to media_tag_id if needed
            if (Schema::hasColumn('media_taggables', 'tag_id') && ! Schema::hasColumn('media_taggables', 'media_tag_id')) {
                $table->renameColumn('tag_id', 'media_tag_id');
            }

            // Add timestamps if missing
            if (! Schema::hasColumn('media_taggables', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverses the migrations.
     *
     * @since 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('media_taggables');
    }
};
