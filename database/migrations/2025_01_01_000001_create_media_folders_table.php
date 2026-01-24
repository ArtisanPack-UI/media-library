<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Media Folders Table Migration
 *
 * Creates the media_folders table for organizing media files into folders.
 * This migration handles both fresh installs and upgrades from v1.0.
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
        if (Schema::hasTable('media_folders')) {
            // Upgrade existing table - add any missing columns
            Schema::table('media_folders', function (Blueprint $table) {
                if (! Schema::hasColumn('media_folders', 'description')) {
                    $table->text('description')->nullable()->after('slug');
                }

                if (! Schema::hasColumn('media_folders', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('parent_id')->constrained('users')->cascadeOnDelete();
                }
            });

            return;
        }

        // Fresh install - create the table
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('parent_id');
        });
    }

    /**
     * Reverses the migrations.
     *
     * @since 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};
