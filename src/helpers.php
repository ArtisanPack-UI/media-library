<?php

declare(strict_types=1);

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Http\UploadedFile;

if (! function_exists('apRegisterImageSize')) {
    /**
     * Register a custom image size.
     *
     * This function will be fully implemented in Phase 3 when MediaManager is created.
     *
     * @param  string  $name  The name of the image size.
     * @param  int  $width  The maximum width in pixels.
     * @param  int  $height  The maximum height in pixels.
     * @param  bool  $crop  Whether to crop to exact dimensions.
     *
     * @since 1.0.0
     */
    function apRegisterImageSize(string $name, int $width, int $height, bool $crop = false): void
    {
        // TODO: Implement in Phase 3 with MediaManager
        // app(MediaManager::class)->registerImageSize($name, $width, $height, $crop);
    }
}

if (! function_exists('apGetMedia')) {
    /**
     * Get a media item by ID.
     *
     * @param  int  $id  The media ID.
     * @return Media|null The media instance or null if not found.
     *
     * @since 1.0.0
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
     * @param  int  $id  The media ID.
     * @param  string  $size  The image size (e.g., 'thumbnail', 'medium', 'large', 'full').
     * @return string|null The media URL or null if not found.
     *
     * @since 1.0.0
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
     * @param  UploadedFile  $file  The uploaded file.
     * @param  array<string, mixed>  $options  Additional options for the upload.
     * @return Media The created media instance.
     *
     * @since 1.0.0
     */
    function apUploadMedia(UploadedFile $file, array $options = []): Media
    {
        // TODO: Implement in Phase 2 with MediaUploadService
        // return app(MediaUploadService::class)->upload($file, $options);
        throw new \RuntimeException('apUploadMedia will be implemented in Phase 2');
    }
}

if (! function_exists('apDeleteMedia')) {
    /**
     * Delete a media item and its files.
     *
     * @param  int  $id  The media ID.
     * @return bool True if deleted successfully, false otherwise.
     *
     * @since 1.0.0
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
