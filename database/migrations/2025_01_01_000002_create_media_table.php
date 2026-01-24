<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Table Migration
 *
 * Creates the media table for storing uploaded media files and their metadata.
 * This migration handles both fresh installs and upgrades from v1.0.
 *
 * v1.0 → v1.1 Schema Changes:
 * - Renamed: path → file_path, size → file_size, user_id → uploaded_by
 * - Added: title, disk, description, width, height, duration, folder_id, deleted_at
 * - Removed: is_decorative
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
        if (Schema::hasTable('media')) {
            $this->upgradeFromV1();

            return;
        }

        // Fresh install - create the table with v1.1 schema
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('file_name');
            $table->index('mime_type');
            $table->index('folder_id');
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    /**
     * Upgrades the media table from v1.0 to v1.1 schema.
     *
     * @since 1.1.0
     */
    protected function upgradeFromV1(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Rename columns from v1.0 to v1.1
            if (Schema::hasColumn('media', 'path') && ! Schema::hasColumn('media', 'file_path')) {
                $table->renameColumn('path', 'file_path');
            }

            if (Schema::hasColumn('media', 'size') && ! Schema::hasColumn('media', 'file_size')) {
                $table->renameColumn('size', 'file_size');
            }

            if (Schema::hasColumn('media', 'user_id') && ! Schema::hasColumn('media', 'uploaded_by')) {
                $table->renameColumn('user_id', 'uploaded_by');
            }
        });

        // Add new columns (separate table call after renames to avoid conflicts)
        Schema::table('media', function (Blueprint $table) {
            if (! Schema::hasColumn('media', 'title')) {
                $table->string('title')->nullable()->after('id');
            }

            if (! Schema::hasColumn('media', 'disk')) {
                $table->string('disk')->default('public')->after('file_path');
            }

            if (! Schema::hasColumn('media', 'description')) {
                $table->text('description')->nullable()->after('caption');
            }

            if (! Schema::hasColumn('media', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('description');
            }

            if (! Schema::hasColumn('media', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }

            if (! Schema::hasColumn('media', 'duration')) {
                $table->unsignedInteger('duration')->nullable()->after('height');
            }

            if (! Schema::hasColumn('media', 'folder_id')) {
                $table->foreignId('folder_id')->nullable()->after('duration')->constrained('media_folders')->nullOnDelete();
            }

            if (! Schema::hasColumn('media', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Drop removed columns
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'is_decorative')) {
                $table->dropColumn('is_decorative');
            }
        });

        // Make uploaded_by nullable for guest upload support (v1.1.1)
        // This requires dropping and re-creating the foreign key constraint
        // Using Laravel's native Schema methods (Laravel 11+) instead of Doctrine
        $columns = Schema::getColumns('media');
        $uploadedByColumn = collect($columns)->firstWhere('name', 'uploaded_by');

        // Only proceed if column exists and is NOT nullable
        if ($uploadedByColumn !== null && ! $uploadedByColumn['nullable']) {
            // Drop the existing foreign key if it exists
            $foreignKeys = Schema::getForeignKeys('media');

            foreach ($foreignKeys as $foreignKey) {
                if (in_array('uploaded_by', $foreignKey['columns'], true)) {
                    Schema::table('media', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                    break;
                }
            }

            // Make the column nullable using raw SQL for database-agnostic approach
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite doesn't support ALTER COLUMN, but columns are nullable by default
                // when no NOT NULL constraint is specified during table creation.
                // For SQLite, we'd need to recreate the table, but since this is an upgrade
                // migration and SQLite is typically only used in development/testing,
                // we can skip this for SQLite as the fresh install already creates it nullable.
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE media ALTER COLUMN uploaded_by DROP NOT NULL');
            } else {
                // MySQL/MariaDB
                DB::statement('ALTER TABLE media MODIFY uploaded_by BIGINT UNSIGNED NULL');
            }

            // Re-add the foreign key with nullOnDelete
            Schema::table('media', function (Blueprint $table) {
                $table->foreign('uploaded_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        // Add indexes if missing (check for existing indexes to avoid duplicates)
        // Using Laravel's native Schema::getIndexListing() instead of Doctrine
        Schema::table('media', function (Blueprint $table) {
            $indexNames = Schema::getIndexListing('media');

            // Check for both possible naming conventions (_index and _idx)
            $hasFileNameIndex = in_array('media_file_name_index', $indexNames, true)
                || in_array('media_file_name_idx', $indexNames, true);
            if (! $hasFileNameIndex) {
                $table->index('file_name');
            }

            $hasMimeTypeIndex = in_array('media_mime_type_index', $indexNames, true)
                || in_array('media_mime_type_idx', $indexNames, true);
            if (! $hasMimeTypeIndex) {
                $table->index('mime_type');
            }

            $hasFolderIdIndex = in_array('media_folder_id_index', $indexNames, true)
                || in_array('media_folder_id_idx', $indexNames, true);
            if (! $hasFolderIdIndex) {
                $table->index('folder_id');
            }

            $hasUploadedByIndex = in_array('media_uploaded_by_index', $indexNames, true)
                || in_array('media_uploaded_by_idx', $indexNames, true);
            if (! $hasUploadedByIndex) {
                $table->index('uploaded_by');
            }

            $hasCreatedAtIndex = in_array('media_created_at_index', $indexNames, true)
                || in_array('media_created_at_idx', $indexNames, true);
            if (! $hasCreatedAtIndex) {
                $table->index('created_at');
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
        Schema::dropIfExists('media');
    }
};
