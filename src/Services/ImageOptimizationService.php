<?php

namespace ArtisanPackUI\MediaLibrary\Services;

use Exception;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Image Optimization Service
 *
 * Handles image optimization operations such as compression,
 * format conversion, and resizing.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Services
 */
class ImageOptimizationService
{
    /**
     * Intervention Image manager instance.
     *
     * @since 1.0.0
     *
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * Creates a new image optimization service instance.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
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
     * Resizes an image file.
     *
     * @since 1.0.0
     *
     * @param string   $path   The image file path.
     * @param int|null $width  The target width (null for auto).
     * @param int|null $height The target height (null for auto).
     * @param bool     $crop   Whether to crop to exact dimensions.
     *
     * @return bool True if resize successful, false otherwise.
     */
    public function resize( string $path, ?int $width = null, ?int $height = null, bool $crop = false ): bool
    {
        try {
            // Load the image
            $image = $this->imageManager->read( $path );

            // Resize based on options
            if ( $crop && $width !== null && $height !== null ) {
                // Crop to exact dimensions
                $image->cover( $width, $height );
            } elseif ( $width !== null || $height !== null ) {
                // Scale maintaining aspect ratio
                $image->scale( $width, $height );
            }

            // Determine format and save
            $extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
            $quality   = config( 'artisanpack.media.image_quality', 85 );

            $encoded = match ( $extension ) {
                'jpg', 'jpeg' => $image->toJpeg( $quality ),
                'png' => $image->toPng(),
                'webp' => $image->toWebp( $quality ),
                'avif' => $image->toAvif( $quality ),
                'gif' => $image->toGif(),
                default => null,
            };

            if ( $encoded === null ) {
                return false;
            }

            // Write the resized image back to the file
            file_put_contents( $path, (string)$encoded );

            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Converts an image to a different format.
     *
     * @since 1.0.0
     *
     * @param string $path   The source image file path.
     * @param string $format The target format (jpg, png, webp, avif, gif).
     *
     * @return string|null The path to the converted image or null on failure.
     */
    public function convert( string $path, string $format ): ?string
    {
        try {
            // Load the image
            $image = $this->imageManager->read( $path );

            // Generate new filename with target format
            $pathInfo    = pathinfo( $path );
            $newFilename = $pathInfo['filename'] . '.' . $format;
            $newPath     = $pathInfo['dirname'] . '/' . $newFilename;

            // Get quality setting
            $quality = config( 'artisanpack.media.image_quality', 85 );

            // Encode to the target format
            $encoded = match ( $format ) {
                'jpg', 'jpeg' => $image->toJpeg( $quality ),
                'png' => $image->toPng(),
                'webp' => $image->toWebp( $quality ),
                'avif' => $image->toAvif( $quality ),
                'gif' => $image->toGif(),
                default => null,
            };

            if ( $encoded === null ) {
                return null;
            }

            // Write the converted image
            file_put_contents( $newPath, (string)$encoded );

            return $newPath;
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Compresses an image file.
     *
     * @since 1.0.0
     *
     * @param string $path    The image file path.
     * @param int    $quality The quality setting (1-100).
     *
     * @return bool True if compression successful, false otherwise.
     */
    public function compress( string $path, int $quality = 85 ): bool
    {
        return $this->optimize( $path, [ 'quality' => $quality ] );
    }

    /**
     * Optimizes an image file.
     *
     * @since 1.0.0
     *
     * @param string               $path    The image file path.
     * @param array<string, mixed> $options Options for optimization (quality, strip_metadata).
     *
     * @return bool True if optimization successful, false otherwise.
     */
    public function optimize( string $path, array $options = [] ): bool
    {
        try {
            $quality       = $options['quality'] ?? 85;
            $stripMetadata = $options['strip_metadata'] ?? true;

            // Load the image
            $image = $this->imageManager->read( $path );

            // Determine format based on file extension
            $extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

            // Encode with quality and save back to the same path
            $encoded = match ( $extension ) {
                'jpg', 'jpeg' => $image->toJpeg( $quality ),
                'png' => $image->toPng(),
                'webp' => $image->toWebp( $quality ),
                'avif' => $image->toAvif( $quality ),
                'gif' => $image->toGif(),
                default => null,
            };

            if ( $encoded === null ) {
                return false;
            }

            // Write the optimized image back to the file
            file_put_contents( $path, (string)$encoded );

            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }
}
