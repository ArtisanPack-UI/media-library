<?php

/**
 * Media Library Helper Functions
 *
 * Global helper functions for media management.
 *
 * @since   1.0.0
 */

use ArtisanPackUI\MediaLibrary\Helpers\BlockMediaHelper;
use ArtisanPackUI\MediaLibrary\Helpers\LivewireHelper;
use ArtisanPackUI\MediaLibrary\Managers\MediaManager;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;

if (! function_exists('apRegisterImageSize')) {
    /**
     * Register a custom image size.
     *
     * @since 1.0.0
     *
     * @param  string  $name  The name of the image size.
     * @param  int  $width  The maximum width in pixels.
     * @param  int  $height  The maximum height in pixels.
     * @param  bool  $crop  Whether to crop to exact dimensions.
     */
    function apRegisterImageSize(string $name, int $width, int $height, bool $crop = false): void
    {
        app(MediaManager::class)->registerImageSize($name, $width, $height, $crop);
    }
}

if (! function_exists('apGetMedia')) {
    /**
     * Get a media item by ID.
     *
     * @since 1.0.0
     *
     * @param  int  $id  The media ID.
     * @return Media|null The media instance or null if not found.
     */
    function apGetMedia(int $id): ?Media
    {
        return Media::find($id);
    }
}

if (! function_exists('apGetMediaUrl')) {
    /**
     * Get the URL for a media item.
     *
     * @since 1.0.0
     *
     * @param  int  $id  The media ID.
     * @param  string  $size  The image size (e.g., 'thumbnail', 'medium', 'large', 'full').
     * @return string|null The media URL or null if not found.
     */
    function apGetMediaUrl(int $id, string $size = 'full'): ?string
    {
        $media = apGetMedia($id);

        if (! $media) {
            return null;
        }

        return $media->isImage() ? $media->imageUrl($size) : $media->url();
    }
}

if (! function_exists('apUploadMedia')) {
    /**
     * Upload a media file.
     *
     * This function will be fully implemented in Phase 2 when MediaUploadService is created.
     *
     * @since 1.0.0
     *
     * @param  UploadedFile  $file  The uploaded file.
     * @param  array<string, mixed>  $options  Additional options for the upload.
     * @return Media The created media instance.
     */
    function apUploadMedia(UploadedFile $file, array $options = []): Media
    {
        return app(MediaUploadService::class)->upload($file, $options);
    }
}

if (! function_exists('apDeleteMedia')) {
    /**
     * Delete a media item and its files.
     *
     * @since 1.0.0
     *
     * @param  int  $id  The media ID.
     * @return bool True if deleted successfully, false otherwise.
     */
    function apDeleteMedia(int $id): bool
    {
        $media = apGetMedia($id);

        if (! $media) {
            return false;
        }

        $media->deleteFiles();

        return $media->delete();
    }
}

if (! function_exists('apLivewireVersion')) {
    /**
     * Get the installed Livewire version string.
     *
     * @since 1.1.0
     *
     * @return string The Livewire version string.
     */
    function apLivewireVersion(): string
    {
        return LivewireHelper::version();
    }
}

if (! function_exists('apIsLivewire4')) {
    /**
     * Check if Livewire 4.x is installed.
     *
     * @since 1.1.0
     *
     * @return bool True if Livewire 4.x or higher is installed.
     */
    function apIsLivewire4(): bool
    {
        return LivewireHelper::isLivewire4();
    }
}

if (! function_exists('apIsLivewire3')) {
    /**
     * Check if Livewire 3.x is installed.
     *
     * @since 1.1.0
     *
     * @return bool True if Livewire 3.x is installed.
     */
    function apIsLivewire3(): bool
    {
        return LivewireHelper::isLivewire3();
    }
}

if (! function_exists('apSupportsLivewireStreaming')) {
    /**
     * Check if the installed Livewire version supports streaming.
     *
     * @since 1.1.0
     *
     * @return bool True if streaming is supported (Livewire 4.x+).
     */
    function apSupportsLivewireStreaming(): bool
    {
        return LivewireHelper::supportsStreaming();
    }
}

if (! function_exists('apBlockMedia')) {
    /**
     * Get media data formatted for visual editor block content.
     *
     * Returns a standardized array of media data suitable for use
     * in visual editor block content schemas.
     *
     * @since 1.1.0
     *
     * @param  int|null  $mediaId  The media ID, or null.
     * @return array<string, mixed>|null The formatted media data or null if not found.
     */
    function apBlockMedia(?int $mediaId): ?array
    {
        return BlockMediaHelper::getBlockMediaData($mediaId);
    }
}

if (! function_exists('apBlockMediaUrl')) {
    /**
     * Get media URL for block content with size optimization.
     *
     * @since 1.1.0
     *
     * @param  int|null  $mediaId  The media ID, or null.
     * @param  string  $size  The image size (thumbnail, medium, large, full).
     * @return string|null The media URL or null if not found.
     */
    function apBlockMediaUrl(?int $mediaId, string $size = 'medium'): ?string
    {
        return BlockMediaHelper::getBlockMediaUrl($mediaId, $size);
    }
}

if (! function_exists('apValidateBlockMedia')) {
    /**
     * Validate media for block type requirements.
     *
     * @since 1.1.0
     *
     * @param  int  $mediaId  The media ID to validate.
     * @param  string  $blockType  The block type to validate against.
     * @return bool True if the media meets the block requirements.
     */
    function apValidateBlockMedia(int $mediaId, string $blockType): bool
    {
        return BlockMediaHelper::validateForBlock($mediaId, $blockType);
    }
}

if (! function_exists('apBlockMediaMultiple')) {
    /**
     * Get multiple media items formatted for block content.
     *
     * @since 1.1.0
     *
     * @param  array<int>  $mediaIds  Array of media IDs.
     * @return array<int, array<string, mixed>> Array of formatted media data.
     */
    function apBlockMediaMultiple(array $mediaIds): array
    {
        return BlockMediaHelper::getMultipleBlockMediaData($mediaIds);
    }
}
