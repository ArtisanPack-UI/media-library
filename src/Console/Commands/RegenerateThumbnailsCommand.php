<?php

/**
 * Regenerate Thumbnails Command
 *
 * Rebuilds thumbnails for existing images whose file isn't a JPEG or SVG.
 * Before 1.7.0 those thumbnails were JPEG data saved under the source
 * file's extension. Idempotent — thumbnails are overwritten in place, and
 * the optimization tracking columns are left untouched.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Console\Commands
 *
 * @since      1.7.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Console\Commands;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan command to regenerate thumbnails for existing non-JPEG images.
 *
 * @since 1.7.0
 */
class RegenerateThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.7.0
     *
     * @var string
     */
    protected $signature = 'media:regenerate-thumbnails
        {--chunk=200 : Number of rows to process per batch}';

    /**
     * The console command description.
     *
     * @since 1.7.0
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnails for existing non-JPEG images so they match their source format';

    /**
     * Execute the console command.
     *
     * @since 1.7.0
     *
     * @param MediaProcessingService $processingService The processing service instance.
     */
    public function handle( MediaProcessingService $processingService ): int
    {
        $chunkSize   = max( 1, (int) $this->option( 'chunk' ) );
        $regenerated = 0;
        $failed      = 0;

        // Thumbnails take their extension from file_path, which can disagree
        // with mime_type because uploads keep the client's extension
        Media::query()
            ->where( 'mime_type', 'like', 'image/%' )
            ->where( 'mime_type', '!=', 'image/svg+xml' )
            ->where( 'file_path', 'not like', '%.jpg' )
            ->where( 'file_path', 'not like', '%.jpeg' )
            ->where( 'file_path', 'not like', '%.svg' )
            ->chunkById( $chunkSize, function ( $rows ) use ( $processingService, &$regenerated, &$failed ): void {
                foreach ( $rows as $media ) {
                    try {
                        $thumbnails = $processingService->generateThumbnails( $media );
                        $missing    = array_diff_key( $processingService->getImageSizes( $media ), $thumbnails );
                    } catch ( Throwable $e ) {
                        $failed++;
                        $this->warn( "Failed to regenerate media {$media->getKey()} ({$media->file_path}): {$e->getMessage()}" );

                        continue;
                    }

                    // A size that failed still has its old file on disk
                    if ( [] !== $missing ) {
                        $failed++;
                        $sizes = implode( ', ', array_keys( $missing ) );
                        $this->warn( "Missing sizes for media {$media->getKey()} ({$media->file_path}): {$sizes}." );

                        continue;
                    }

                    $regenerated++;
                }
            } );

        $this->info( "Regenerated thumbnails for {$regenerated} image(s); {$failed} failed." );

        return 0 === $failed ? self::SUCCESS : self::FAILURE;
    }
}
