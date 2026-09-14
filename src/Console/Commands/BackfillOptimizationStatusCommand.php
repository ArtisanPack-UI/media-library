<?php

/**
 * Backfill Optimization Status Command
 *
 * Marks legacy image rows as `optimized` so downstream UIs don't render
 * them as pending forever. Idempotent — rows that already carry a status
 * are skipped.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Console\Commands
 *
 * @since      1.5.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Console\Commands;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Console\Command;

/**
 * Artisan command to backfill the optimization tracking columns for
 * existing image rows.
 *
 * @since 1.5.0
 */
class BackfillOptimizationStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.5.0
     *
     * @var string
     */
    protected $signature = 'media:backfill-optimization-status
        {--chunk=200 : Number of rows to process per batch}';

    /**
     * The console command description.
     *
     * @since 1.5.0
     *
     * @var string
     */
    protected $description = 'Backfill optimization tracking columns for existing image rows';

    /**
     * Execute the console command.
     *
     * @since 1.5.0
     */
    public function handle(): int
    {
        $chunkSize = max( 1, (int) $this->option( 'chunk' ) );
        $updated   = 0;

        Media::query()
            ->where( 'mime_type', 'like', 'image/%' )
            ->whereNull( 'optimization_status' )
            ->chunkById( $chunkSize, function ( $rows ) use ( &$updated ): void {
                foreach ( $rows as $media ) {
                    $media->forceFill( [
                        'optimization_status'      => Media::OPTIMIZATION_STATUS_OPTIMIZED,
                        'optimized_at'             => $media->updated_at ?? $media->created_at ?? now(),
                        'optimization_bytes_saved' => null,
                        'optimization_error'       => null,
                    ] )->save();

                    $updated++;
                }
            } );

        $this->info( "Backfilled optimization status for {$updated} image row(s)." );

        return self::SUCCESS;
    }
}
