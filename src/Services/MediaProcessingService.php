<?php

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Exception;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

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

        // Generate thumbnails if enabled
        if ( config( 'artisanpack.media.enable_thumbnails', true ) ) {
            $this->generateThumbnails( $media );
        }

        // Convert to modern format if enabled
        if ( config( 'artisanpack.media.enable_modern_formats', true ) ) {
            $format = config( 'artisanpack.media.modern_format', 'webp' );
            $this->convertToModernFormat( $media, $format );
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
        $imageSizes = $this->getImageSizes();
        $sourcePath = $this->storageService->path( $media->file_path, $media->disk );

        foreach ( $imageSizes as $sizeName => $sizeConfig ) {
            try {
                $thumbnailPath = $this->generateSingleThumbnail(
                    $sourcePath,
                    $media->file_path,
                    $media->disk,
                    $sizeName,
                    $sizeConfig
                );

                if ( $thumbnailPath !== null ) {
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

        return $thumbnails;
    }

    /**
     * Gets all configured image sizes (built-in + custom).
     *
     * @since 1.0.0
     *
     * @return array<string, array<string, mixed>> The image sizes configuration.
     */
    protected function getImageSizes(): array
    {
        $builtInSizes = config( 'artisanpack.media.image_sizes', [] );
        $customSizes  = config( 'artisanpack.media.custom_image_sizes', [] );

        return array_merge( $builtInSizes, $customSizes );
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
     *
     * @return string|null The generated thumbnail path or null on failure.
     */
    protected function generateSingleThumbnail(
        string $sourcePath,
        string $relativeSource,
        string $disk,
        string $sizeName,
        array $sizeConfig
    ): ?string
    {
        try {
            // Load the image
            $image = $this->imageManager->read( $sourcePath );

            $width  = $sizeConfig['width'] ?? null;
            $height = $sizeConfig['height'] ?? null;
            $crop   = $sizeConfig['crop'] ?? false;

            // Resize based on configuration
            if ( $crop && $width !== null && $height !== null ) {
                // Crop to exact dimensions
                $image->cover( $width, $height );
            } elseif ( $width !== null || $height !== null ) {
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
            $this->storageService->put( $thumbnailPath, (string)$encoded, $disk );

            return $thumbnailPath;
        } catch ( Exception $e ) {
            return null;
        }
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
        if ( $media->mime_type === 'image/svg+xml' ) {
            return null;
        }

        // Don't convert if already in modern format
        if ( in_array( $media->mime_type, [ 'image/webp', 'image/avif' ], true ) ) {
            return null;
        }

        try {
            $sourcePath = $this->storageService->path( $media->file_path, $media->disk );
            $image      = $this->imageManager->read( $sourcePath );

            // Generate modern format filename
            $pathInfo   = pathinfo( $media->file_path );
            $modernName = $pathInfo['filename'] . '.' . $format;
            $modernPath = $pathInfo['dirname'] . '/' . $modernName;

            // Get quality setting
            $quality = config( 'artisanpack.media.image_quality', 85 );

            // Encode to the target format
            $encoded = match ( $format ) {
                'webp' => $image->toWebp( $quality ),
                'avif' => $image->toAvif( $quality ),
                default => null,
            };

            if ( $encoded === null ) {
                return null;
            }

            // Store the converted image
            $this->storageService->put( $modernPath, (string)$encoded, $media->disk );

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
            if ( $imageSize === false ) {
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
}
