<?php

namespace ArtisanPackUI\MediaLibrary\Managers;

/**
 * Media Manager
 *
 * Coordinates media library operations and manages configuration.
 * Responsible for registering custom image sizes, managing allowed
 * mime types, and providing centralized access to media library settings.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Managers
 */
class MediaManager
{
    /**
     * Custom image sizes registered at runtime.
     *
     * @since 1.0.0
     *
     * @var array<string, array{width: int, height: int, crop: bool}>
     */
    protected array $customImageSizes = [];

    /**
     * Register a custom image size.
     *
     * Registers a new image size that will be generated when images
     * are uploaded or processed. Custom sizes are stored in memory
     * and can be retrieved via getImageSizes().
     *
     * @since 1.0.0
     *
     * @param  string  $name  The unique name of the image size.
     * @param  int  $width  The maximum width in pixels.
     * @param  int  $height  The maximum height in pixels.
     * @param  bool  $crop  Whether to crop to exact dimensions.
     */
    public function registerImageSize(string $name, int $width, int $height, bool $crop = false): void
    {
        $this->customImageSizes[$name] = [
            'width' => $width,
            'height' => $height,
            'crop' => $crop,
        ];
    }

    /**
     * Get all registered image sizes.
     *
     * Returns both default image sizes from configuration and any
     * custom sizes registered at runtime via registerImageSize().
     *
     * @since 1.0.0
     *
     * @return array<string, array{width: int|null, height: int|null, crop: bool}>
     */
    public function getImageSizes(): array
    {
        $defaultSizes = config('artisanpack.media.image_sizes', []);

        return array_merge($defaultSizes, $this->customImageSizes);
    }

    /**
     * Get allowed MIME types for uploads.
     *
     * Returns the list of allowed MIME types from configuration.
     * These MIME types determine which file types can be uploaded
     * to the media library.
     *
     * @since 1.0.0
     *
     * @return array<int, string>
     */
    public function getAllowedMimeTypes(): array
    {
        return config('artisanpack.media.allowed_mime_types', []);
    }

    /**
     * Check if a MIME type is allowed.
     *
     * Validates whether the given MIME type is in the list of
     * allowed types from configuration.
     *
     * @since 1.0.0
     *
     * @param  string  $mimeType  The MIME type to check.
     * @return bool True if the MIME type is allowed, false otherwise.
     */
    public function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, $this->getAllowedMimeTypes(), true);
    }

    /**
     * Get the maximum allowed file size in kilobytes.
     *
     * Returns the maximum file size for uploads from configuration.
     *
     * @since 1.0.0
     *
     * @return int The maximum file size in kilobytes.
     */
    public function getMaxFileSize(): int
    {
        return (int) config('artisanpack.media.max_file_size', 10240);
    }

    /**
     * Get the storage disk name.
     *
     * Returns the configured storage disk for media files.
     *
     * @since 1.0.0
     *
     * @return string The storage disk name.
     */
    public function getDisk(): string
    {
        return config('artisanpack.media.disk', 'public');
    }

    /**
     * Get the upload path format.
     *
     * Returns the configured path format for uploaded files.
     *
     * @since 1.0.0
     *
     * @return string The upload path format.
     */
    public function getUploadPathFormat(): string
    {
        return config('artisanpack.media.upload_path_format', '{year}/{month}');
    }

    /**
     * Check if modern image formats are enabled.
     *
     * Returns whether automatic conversion to modern formats (WebP/AVIF)
     * is enabled in the configuration.
     *
     * @since 1.0.0
     *
     * @return bool True if modern formats are enabled, false otherwise.
     */
    public function isModernFormatsEnabled(): bool
    {
        return (bool) config('artisanpack.media.enable_modern_formats', true);
    }

    /**
     * Get the modern image format to use.
     *
     * Returns the configured modern format (webp or avif) for conversions.
     *
     * @since 1.0.0
     *
     * @return string The modern format to use ('webp' or 'avif').
     */
    public function getModernFormat(): string
    {
        return config('artisanpack.media.modern_format', 'webp');
    }

    /**
     * Get the image quality setting.
     *
     * Returns the configured quality for image compression (1-100).
     *
     * @since 1.0.0
     *
     * @return int The image quality setting.
     */
    public function getImageQuality(): int
    {
        return (int) config('artisanpack.media.image_quality', 85);
    }

    /**
     * Check if thumbnails are enabled.
     *
     * Returns whether automatic thumbnail generation is enabled.
     *
     * @since 1.0.0
     *
     * @return bool True if thumbnails are enabled, false otherwise.
     */
    public function isThumbnailsEnabled(): bool
    {
        return (bool) config('artisanpack.media.enable_thumbnails', true);
    }

    /**
     * Get the user model class name.
     *
     * Returns the configured user model for media relationships.
     *
     * @since 1.0.0
     *
     * @return string The fully qualified user model class name.
     */
    public function getUserModel(): string
    {
        return config('artisanpack.media.user_model', 'App\Models\User');
    }
}
