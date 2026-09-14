<?php

declare( strict_types=1 );

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add optimization tracking columns to the media table.
 *
 * Persists the outcome of the image optimization pipeline so consumers can
 * render a per-item status badge (optimized / pending / failed / N/A) and
 * show bytes-saved details without recomputing them.
 *
 * @since 1.5.0
 */
return new class extends Migration {
    /**
     * Runs the migration.
     *
     * @since 1.5.0
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'media' ) ) {
            return;
        }

        Schema::table( 'media', function ( Blueprint $table ): void {
            if ( ! Schema::hasColumn( 'media', 'optimization_status' ) ) {
                $table->string( 'optimization_status', 20 )->nullable()->after( 'metadata' );
            }

            if ( ! Schema::hasColumn( 'media', 'optimized_at' ) ) {
                $table->timestamp( 'optimized_at' )->nullable()->after( 'optimization_status' );
            }

            if ( ! Schema::hasColumn( 'media', 'optimization_bytes_saved' ) ) {
                $table->unsignedBigInteger( 'optimization_bytes_saved' )->nullable()->after( 'optimized_at' );
            }

            if ( ! Schema::hasColumn( 'media', 'optimization_original_size' ) ) {
                $table->unsignedBigInteger( 'optimization_original_size' )->nullable()->after( 'optimization_bytes_saved' );
            }

            if ( ! Schema::hasColumn( 'media', 'optimization_formats' ) ) {
                $table->json( 'optimization_formats' )->nullable()->after( 'optimization_original_size' );
            }

            if ( ! Schema::hasColumn( 'media', 'optimization_error' ) ) {
                $table->string( 'optimization_error', 500 )->nullable()->after( 'optimization_formats' );
            }
        } );

        Schema::table( 'media', function ( Blueprint $table ): void {
            $indexNames = Schema::getIndexListing( 'media' );

            $hasStatusIndex = in_array( 'media_optimization_status_index', $indexNames, true )
                || in_array( 'media_optimization_status_idx', $indexNames, true );

            if ( ! $hasStatusIndex ) {
                $table->index( 'optimization_status' );
            }
        } );
    }

    /**
     * Reverses the migration.
     *
     * @since 1.5.0
     */
    public function down(): void
    {
        if ( ! Schema::hasTable( 'media' ) ) {
            return;
        }

        Schema::table( 'media', function ( Blueprint $table ): void {
            $indexNames = Schema::getIndexListing( 'media' );

            if ( in_array( 'media_optimization_status_index', $indexNames, true ) ) {
                $table->dropIndex( 'media_optimization_status_index' );
            }
        } );

        Schema::table( 'media', function ( Blueprint $table ): void {
            $columns = [
                'optimization_status',
                'optimized_at',
                'optimization_bytes_saved',
                'optimization_original_size',
                'optimization_formats',
                'optimization_error',
            ];

            foreach ( $columns as $column ) {
                if ( Schema::hasColumn( 'media', $column ) ) {
                    $table->dropColumn( $column );
                }
            }
        } );
    }
};
