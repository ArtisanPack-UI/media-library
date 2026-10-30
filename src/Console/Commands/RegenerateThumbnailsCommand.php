<?php

/**
 * Regenerate Thumbnails Command
 *
 * Rebuilds thumbnails for existing images whose source format isn't JPEG.
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

        Media::query()
            ->where( 'mime_type', 'like', 'image/%' )
            ->whereNotIn( 'mime_type', [ 'image/jpeg', 'image/jpg', 'image/svg+xml' ] )
            ->chunkById( $chunkSize, function ( $rows ) use ( $processingService, &$regenerated, &$failed ): void {
                foreach ( $rows as $media ) {
                    if ( [] === $processingService->generateThumbnails( $media ) ) {
                        $failed++;
                        $this->warn( "No thumbnails generated for media {$media->getKey()} ({$media->file_path})." );

                        continue;
                    }

                    $regenerated++;
                }
            } );

        $this->info( "Regenerated thumbnails for {$regenerated} image(s); {$failed} failed." );

        return 0 === $failed ? self::SUCCESS : self::FAILURE;
    }
}
