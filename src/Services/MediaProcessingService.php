<?php

/**
 * Media Processing Service
 *
 * Handles image-related operations such as thumbnail generation,
 * modern format conversion (WebP/AVIF), and image optimization.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Services
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Media Processing Service
 *
 * Handles image-related operations such as thumbnail generation,
 * modern format conversion (WebP/AVIF), and image optimization.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Services
 */
class MediaProcessingService
{
    /**
     * Media storage service instance.
     *
     * @since 1.0.0
     *
     * @var MediaStorageService
     */
    protected MediaStorageService $storageService;

    /**
     * Image optimization service instance.
     *
     * @since 1.0.0
     *
     * @var ImageOptimizationService
     */
    protected ImageOptimizationService $optimizationService;

    /**
     * Intervention Image manager instance.
     *
     * @since 1.0.0
     *
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * Creates a new media processing service instance.
     *
     * @since 1.0.0
     *
     * @param MediaStorageService      $storageService      The storage service instance.
     * @param ImageOptimizationService $optimizationService The optimization service instance.
     */
    public function __construct( MediaStorageService $storageService, ImageOptimizationService $optimizationService )
    {
        $this->storageService      = $storageService;
        $this->optimizationService = $optimizationService;

        // Initialize Intervention Image with the best available driver
        $this->imageManager = $this->createImageManager();
    }

    /**
     * Processes an image: generates thumbnails and converts to modern formats.
     *
     * @since 1.0.0
     *
     * @param Media $media The media instance to process.
     *
     * @return void
     */
    public function processImage( Media $media ): void
    {
        if ( ! $media->isImage() ) {
            return;
        }

        /**
         * Fires before an image is processed (thumbnails + modern formats).
         *
         * Runs after the media has been confirmed as an image and before
         * any transformation begins. Use this to seed derived data or
         * short-circuit processing by mutating configuration.
         *
         * @since 1.4.0
         *
         * @param Media $media The media instance about to be processed.
         */
        doAction( 'ap.mediaLibrary.beforeProcess', $media );

        $originalSize = $this->currentFileSize( $media );

        $media->forceFill( [
            'optimization_status'        => Media::OPTIMIZATION_STATUS_PENDING,
            'optimization_original_size' => $originalSize,
            'optimized_at'               => null,
            'optimization_bytes_saved'   => null,
            'optimization_formats'       => null,
            'optimization_error'         => null,
        ] )->save();

        try {
            // Generate thumbnails if enabled
            if ( config( 'artisanpack.media.enable_thumbnails', true ) ) {
                $this->generateThumbnails( $media );
            }

            $generatedFormats = [];

            // Convert to modern format if enabled
            if ( config( 'artisanpack.media.enable_modern_formats', true ) ) {
                $format = config( 'artisanpack.media.modern_format', 'webp' );
                $result = $this->convertToModernFormat( $media, $format );

                if ( null !== $result ) {
                    $generatedFormats[ $format ] = true;
                }
            }

            $finalSize   = $this->currentFileSize( $media );
            $bytesSaved  = null;

            if ( null !== $originalSize && null !== $finalSize ) {
                $bytesSaved = max( 0, $originalSize - $finalSize );
            }

            $media->forceFill( [
                'optimization_status'      => Media::OPTIMIZATION_STATUS_OPTIMIZED,
                'optimized_at'             => now(),
                'optimization_bytes_saved' => $bytesSaved,
                'optimization_formats'     => $generatedFormats,
                'optimization_error'       => null,
            ] )->save();
        } catch ( Throwable $e ) {
            $media->forceFill( [
                'optimization_status' => Media::OPTIMIZATION_STATUS_FAILED,
                'optimization_error'  => Str::limit( $e->getMessage(), 500 ),
            ] )->save();

            throw $e;
        }
    }

    /**
     * Generates thumbnails for all configured image sizes.
     *
     * @since 1.0.0
     *
     * @param Media $media The media instance.
     *
     * @return array<string, string> Array of generated thumbnail paths keyed by size name.
     */
    public function generateThumbnails( Media $media ): array
    {
        if ( ! $media->isImage() ) {
            return [];
        }

        $thumbnails = [];
        $imageSizes = $this->getImageSizes( $media );
        $sourcePath = $this->storageService->path( $media->file_path, $media->disk, $media );

        foreach ( $imageSizes as $sizeName => $sizeConfig ) {
            try {
                $thumbnailPath = $this->generateSingleThumbnail(
                    $sourcePath,
                    $media->file_path,
                    $media->disk,
                    $sizeName,
                    $sizeConfig,
                    $media,
                );

                if ( null !== $thumbnailPath ) {
                    $thumbnails[ $sizeName ] = $thumbnailPath;
                }
            } catch ( Exception $e ) {
                // Continue generating other sizes on failure
                continue;
            }
        }

        // Store thumbnail paths in metadata
        if ( ! empty( $thumbnails ) ) {
            $metadata               = $media->metadata ?? [];
            $metadata['thumbnails'] = $thumbnails;
            $media->update( [ 'metadata' => $metadata ] );
        }

        // Only fire the "thumbnails generated" hook when at least one
        // thumbnail actually made it to disk — otherwise the empty payload
        // gives subscribers (CDN pushers, indexers) no signal that
        // generation failed and would push empty maps as success.
        if ( ! empty( $thumbnails ) ) {
            // Build a URL map for hook subscribers so they don't have to
            // look paths up against the disk themselves.
            $urls = [];
            foreach ( $thumbnails as $sizeName => $thumbnailPath ) {
                $urls[ $sizeName ] = $this->storageService->url( $thumbnailPath, $media->disk, $media );
            }

            /**
             * Fires after all thumbnail sizes have been generated for a media
             * item.
             *
             * The media's metadata has already been updated with the thumbnail
             * paths. Subscribers receive both the media instance and a map of
             * size name to public URL for downstream CDN pushes, indexing, etc.
             *
             * @since 1.4.0
             *
             * @param Media                 $media The media instance whose thumbnails were generated.
             * @param array<string, string> $urls  Map of size name to public thumbnail URL.
             */
            doAction( 'ap.mediaLibrary.thumbnailsGenerated', $media, $urls );
        }

        return $thumbnails;
    }

    /**
     * Converts an image to a modern format (WebP or AVIF).
     *
     * @since 1.0.0
     *
     * @param Media  $media  The media instance.
     * @param string $format The target format ('webp' or 'avif').
     *
     * @return string|null The converted image path or null on failure.
     */
    public function convertToModernFormat( Media $media, string $format = 'webp' ): ?string
    {
        if ( ! $media->isImage() ) {
            return null;
        }

        // Don't convert SVGs
        if ( 'image/svg+xml' === $media->mime_type ) {
            return null;
        }

        // Don't convert if already in modern format
        if ( in_array( $media->mime_type, [ 'image/webp', 'image/avif' ], true ) ) {
            return null;
        }

        try {
            $sourcePath = $this->storageService->path( $media->file_path, $media->disk, $media );
            $image      = $this->imageManager->read( $sourcePath );

            // Generate modern format filename
            $pathInfo   = pathinfo( $media->file_path );
            $modernName = $pathInfo['filename'] . '.' . $format;
            $modernPath = $pathInfo['dirname'] . '/' . $modernName;

            // Get quality setting
            $quality = config( 'artisanpack.media.image_quality', 85 );

            // Encode to the target format
            $encoded = match ( $format ) {
                'webp'  => $image->toWebp( $quality ),
                'avif'  => $image->toAvif( $quality ),
                default => null,
            };

            if ( null === $encoded ) {
                return null;
            }

            // Store the converted image
            $this->storageService->put( $modernPath, (string)$encoded, $media->disk, $media );

            // Update metadata
            $metadata                              = $media->metadata ?? [];
            $metadata['modern_formats']            = $metadata['modern_formats'] ?? [];
            $metadata['modern_formats'][ $format ] = $modernPath;
            $media->update( [ 'metadata' => $metadata ] );

            return $modernPath;
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Extracts dimensions from an image file path.
     *
     * @since 1.0.0
     *
     * @param string $path The image file path.
     *
     * @return array<string, int>|null Array with width and height, or null if unable to extract.
     */
    public function extractImageDimensions( string $path ): ?array
    {
        try {
            $imageSize = getimagesize( $path );
            if ( false === $imageSize ) {
                return null;
            }

            return [
                'width'  => $imageSize[0],
                'height' => $imageSize[1],
            ];
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Optimizes an image at the given path.
     *
     * @since 1.0.0
     *
     * @param string $path    The image file path.
     * @param int    $quality The quality setting (1-100).
     *
     * @return void
     */
    public function optimizeImage( string $path, int $quality = 85 ): void
    {
        $this->optimizationService->optimize( $path, [ 'quality' => $quality ] );
    }

    /**
     * Read the current on-disk size for a media item, tolerating missing
     * files by returning null so the optimization delta stays honest.
     *
     * @since 1.5.0
     *
     * @param Media $media The media instance.
     *
     * @return int|null The current file size in bytes, or null if unavailable.
     */
    protected function currentFileSize( Media $media ): ?int
    {
        try {
            $disk = Storage::disk( $media->disk );

            if ( ! $disk->exists( $media->file_path ) ) {
                return $media->file_size ?: null;
            }

            $size = $disk->size( $media->file_path );

            return is_int( $size ) ? $size : (int) $size;
        } catch ( Throwable $e ) {
            return $media->file_size ?: null;
        }
    }

    /**
     * Creates an Intervention Image manager with the best available driver.
     *
     * @since 1.0.0
     *
     * @return ImageManager The image manager instance.
     */
    protected function createImageManager(): ImageManager
    {
        // Prefer Imagick over GD if available
        if ( extension_loaded( 'imagick' ) ) {
            return new ImageManager( new ImagickDriver );
        }

        return new ImageManager( new GdDriver );
    }

    /**
     * Gets all configured image sizes (built-in + custom).
     *
     * @since 1.0.0
     *
     * @param  Media|null  $media  Optional media context for the filter.
     *
     * @return array<string, array<string, mixed>> The image sizes configuration.
     */
    protected function getImageSizes( ?Media $media = null ): array
    {
        $builtInSizes = config( 'artisanpack.media.image_sizes', [] );
        $customSizes  = config( 'artisanpack.media.custom_image_sizes', [] );
        $sizes        = array_merge( $builtInSizes, $customSizes );

        /**
         * Filters the set of image sizes that will be generated for a media
         * item.
         *
         * Runs immediately before the optimization/thumbnail loop, allowing
         * applications to add per-image sizes, drop unnecessary ones, or
         * swap crop settings on a per-media basis.
         *
         * @since 1.4.0
         *
         * @param array<string, array<string, mixed>> $sizes The resolved image sizes.
         * @param Media|null                          $media The media instance being processed, when known.
         *
         * @return array<string, array<string, mixed>> The (possibly modified) size definitions.
         */
        return (array) applyFilters( 'ap.mediaLibrary.imageSizes', $sizes, $media );
    }

    /**
     * Generates a single thumbnail for a specific size.
     *
     * @since 1.0.0
     *
     * @param string               $sourcePath     The source image path.
     * @param string               $relativeSource The relative source path for generating output name.
     * @param string               $disk           The storage disk.
     * @param string               $sizeName       The size name (e.g., 'thumbnail', 'medium').
     * @param array<string, mixed> $sizeConfig     The size configuration.
     * @param Media|null           $media          Optional media context passed to the storageDisk filter.
     *
     * @return string|null The generated thumbnail path or null on failure.
     */
    protected function generateSingleThumbnail(
        string $sourcePath,
        string $relativeSource,
        string $disk,
        string $sizeName,
        array $sizeConfig,
        ?Media $media = null,
    ): ?string {
        try {
            // Load the image
            $image = $this->imageManager->read( $sourcePath );

            $width  = $sizeConfig['width'] ?? null;
            $height = $sizeConfig['height'] ?? null;
            $crop   = $sizeConfig['crop'] ?? false;

            // Resize based on configuration
            if ( $crop && null !== $width && null !== $height ) {
                // Crop to exact dimensions
                $image->cover( $width, $height );
            } elseif ( null !== $width || null !== $height ) {
                // Scale maintaining aspect ratio
                $image->scale( $width, $height );
            }

            // Generate thumbnail filename
            $pathInfo      = pathinfo( $relativeSource );
            $thumbnailName = $pathInfo['filename'] . '-' . $sizeName . '.' . $pathInfo['extension'];
            $thumbnailPath = $pathInfo['dirname'] . '/' . $thumbnailName;

            // Encode with quality setting
            $quality = config( 'artisanpack.media.image_quality', 85 );
            $encoded = $image->toJpeg( $quality );

            // Store the thumbnail
            $this->storageService->put( $thumbnailPath, (string)$encoded, $disk, $media );

            return $thumbnailPath;
        } catch ( Exception $e ) {
            return null;
        }
    }
}
