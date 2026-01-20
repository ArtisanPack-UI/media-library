<?php

/**
 * Block Media Helper
 *
 * Provides utility functions for visual editor block integration,
 * including media URL retrieval, data formatting, and validation.
 *
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Helpers;

use ArtisanPackUI\MediaLibrary\Models\Media;

/**
 * Block Media Helper Class
 *
 * Provides static methods for visual editor blocks to retrieve and validate
 * media for their content schema. This centralizes media handling logic
 * that would otherwise be duplicated across individual block implementations.
 *
 * @since   1.1.0
 */
class BlockMediaHelper
{
    /**
     * Get media URL for block content with size optimization.
     *
     * Retrieves the URL for a media item at the specified size.
     * Falls back to the original URL if the sized version doesn't exist
     * or if the media is not an image.
     *
     * @since 1.1.0
     *
     * @param  int|null  $mediaId  The media ID, or null.
     * @param  string  $size  The image size (thumbnail, medium, large, full).
     * @return string|null The media URL or null if not found.
     */
    public static function getBlockMediaUrl(?int $mediaId, string $size = 'medium'): ?string
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::find($mediaId);

        if ($media === null) {
            return null;
        }

        if ($media->isImage()) {
            return $media->imageUrl($size);
        }

        return $media->url();
    }

    /**
     * Get media object formatted for block content schema.
     *
     * Returns a standardized array of media data suitable for use
     * in visual editor block content schemas.
     *
     * @since 1.1.0
     *
     * @param  int|null  $mediaId  The media ID, or null.
     * @return array<string, mixed>|null The formatted media data or null if not found.
     */
    public static function getBlockMediaData(?int $mediaId): ?array
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::find($mediaId);

        if ($media === null) {
            return null;
        }

        $data = [
            'id' => $media->id,
            'url' => $media->url(),
            'alt' => $media->alt_text ?? '',
            'title' => $media->title ?? $media->file_name,
            'mime_type' => $media->mime_type,
            'file_name' => $media->file_name,
            'file_size' => $media->file_size,
        ];

        // Add image-specific data if applicable
        if ($media->isImage()) {
            $data['thumbnail'] = $media->imageUrl('thumbnail');
            $data['medium'] = $media->imageUrl('medium');
            $data['large'] = $media->imageUrl('large');
            $data['width'] = $media->width;
            $data['height'] = $media->height;
            $data['sizes'] = $media->getImageSizes();
        }

        // Add video/audio-specific data if applicable
        if ($media->isVideo() || $media->isAudio()) {
            $data['duration'] = $media->duration;
        }

        return $data;
    }

    /**
     * Validate media for block type requirements.
     *
     * Checks if a media item meets the requirements defined for a specific
     * block type in the block_requirements configuration.
     *
     * @since 1.1.0
     *
     * @param  int  $mediaId  The media ID to validate.
     * @param  string  $blockType  The block type to validate against.
     * @return bool True if the media meets the block requirements.
     */
    public static function validateForBlock(int $mediaId, string $blockType): bool
    {
        $media = Media::find($mediaId);

        if ($media === null) {
            return false;
        }

        $requirements = static::getBlockRequirements($blockType);

        // Validate media type
        if (! static::validateMediaType($media, $requirements)) {
            return false;
        }

        // Validate file extension if specified
        if (! static::validateExtension($media, $requirements)) {
            return false;
        }

        // Validate dimensions if specified (for images)
        if (! static::validateDimensions($media, $requirements)) {
            return false;
        }

        return true;
    }

    /**
     * Get block requirements from configuration.
     *
     * @since 1.1.0
     *
     * @param  string  $blockType  The block type.
     * @return array<string, mixed> The block requirements.
     */
    public static function getBlockRequirements(string $blockType): array
    {
        $requirements = config('artisanpack.media.block_requirements.'.$blockType);

        if ($requirements === null) {
            return config('artisanpack.media.block_requirements.default', [
                'types' => ['image', 'video', 'audio', 'document'],
                'max_files' => 1,
                'min_files' => 0,
            ]);
        }

        return $requirements;
    }

    /**
     * Get the media type category for a media item.
     *
     * @since 1.1.0
     *
     * @param  Media  $media  The media instance.
     * @return string The media type category (image, video, audio, document).
     */
    public static function getMediaTypeCategory(Media $media): string
    {
        if ($media->isImage()) {
            return 'image';
        }

        if ($media->isVideo()) {
            return 'video';
        }

        if ($media->isAudio()) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Get multiple media items formatted for block content.
     *
     * Retrieves and formats an array of media items for blocks that
     * support multiple media (e.g., galleries).
     *
     * @since 1.1.0
     *
     * @param  array<int>  $mediaIds  Array of media IDs.
     * @return array<int, array<string, mixed>> Array of formatted media data.
     */
    public static function getMultipleBlockMediaData(array $mediaIds): array
    {
        $result = [];

        foreach ($mediaIds as $mediaId) {
            $data = static::getBlockMediaData($mediaId);

            if ($data !== null) {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * Validate multiple media items for a block type.
     *
     * Validates an array of media IDs against block requirements,
     * including min/max file count validation.
     *
     * @since 1.1.0
     *
     * @param  array<int>  $mediaIds  Array of media IDs.
     * @param  string  $blockType  The block type to validate against.
     * @return array{valid: bool, errors: array<string>} Validation result with errors.
     */
    public static function validateMultipleForBlock(array $mediaIds, string $blockType): array
    {
        $requirements = static::getBlockRequirements($blockType);
        $errors = [];
        $validCount = 0;

        // Check min/max file counts
        $minFiles = $requirements['min_files'] ?? 0;
        $maxFiles = $requirements['max_files'] ?? PHP_INT_MAX;
        $count = count($mediaIds);

        if ($count < $minFiles) {
            $errors[] = __('At least :min media items required for this block.', ['min' => $minFiles]);
        }

        if ($count > $maxFiles) {
            $errors[] = __('Maximum :max media items allowed for this block.', ['max' => $maxFiles]);
        }

        // Validate each media item
        foreach ($mediaIds as $index => $mediaId) {
            if (static::validateForBlock($mediaId, $blockType)) {
                $validCount++;
            } else {
                $errors[] = __('Media item #:index does not meet block requirements.', ['index' => $index + 1]);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get optimized URL for a block based on its requirements.
     *
     * Automatically selects the best image size based on the block's
     * recommended dimensions configuration.
     *
     * @since 1.1.0
     *
     * @param  int|null  $mediaId  The media ID.
     * @param  string  $blockType  The block type.
     * @return string|null The optimized media URL or null if not found.
     */
    public static function getOptimizedBlockMediaUrl(?int $mediaId, string $blockType): ?string
    {
        if ($mediaId === null) {
            return null;
        }

        $media = Media::find($mediaId);

        if ($media === null) {
            return null;
        }

        if (! $media->isImage()) {
            return $media->url();
        }

        $requirements = static::getBlockRequirements($blockType);

        // If recommended dimensions are specified, find the best size
        if (isset($requirements['recommended_dimensions'])) {
            $targetWidth = $requirements['recommended_dimensions']['width'] ?? 0;

            return static::getBestSizeForWidth($media, $targetWidth);
        }

        // Default to medium size for blocks
        return $media->imageUrl('medium');
    }

    /**
     * Get the best image size URL for a target width.
     *
     * @since 1.1.0
     *
     * @param  Media  $media  The media instance.
     * @param  int  $targetWidth  The target width.
     * @return string The best matching image URL.
     */
    protected static function getBestSizeForWidth(Media $media, int $targetWidth): string
    {
        $imageSizes = config('artisanpack.media.image_sizes', []);

        // Sort sizes by width
        $sortedSizes = [];
        foreach ($imageSizes as $name => $config) {
            $sortedSizes[$name] = $config['width'] ?? 0;
        }
        asort($sortedSizes);

        // Find the smallest size that is >= target width
        foreach ($sortedSizes as $sizeName => $width) {
            if ($width >= $targetWidth) {
                return $media->imageUrl($sizeName);
            }
        }

        // If no size is large enough, return full size
        return $media->url();
    }

    /**
     * Validate media type against block requirements.
     *
     * @since 1.1.0
     *
     * @param  Media  $media  The media instance.
     * @param  array<string, mixed>  $requirements  The block requirements.
     * @return bool True if the media type is allowed.
     */
    protected static function validateMediaType(Media $media, array $requirements): bool
    {
        $allowedTypes = $requirements['types'] ?? ['image', 'video', 'audio', 'document'];
        $mediaType = static::getMediaTypeCategory($media);

        return in_array($mediaType, $allowedTypes, true);
    }

    /**
     * Validate file extension against block requirements.
     *
     * @since 1.1.0
     *
     * @param  Media  $media  The media instance.
     * @param  array<string, mixed>  $requirements  The block requirements.
     * @return bool True if the extension is allowed.
     */
    protected static function validateExtension(Media $media, array $requirements): bool
    {
        if (! isset($requirements['allowed_extensions'])) {
            return true;
        }

        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        // Normalize the allowed extensions by trimming whitespace and converting to lowercase
        $allowedExtensions = array_map(
            fn ($ext) => strtolower(trim($ext)),
            $requirements['allowed_extensions']
        );

        return in_array($extension, $allowedExtensions, true);
    }

    /**
     * Validate image dimensions against block requirements.
     *
     * @since 1.1.0
     *
     * @param  Media  $media  The media instance.
     * @param  array<string, mixed>  $requirements  The block requirements.
     * @return bool True if the dimensions are acceptable.
     */
    protected static function validateDimensions(Media $media, array $requirements): bool
    {
        // Only validate dimensions for images
        if (! $media->isImage()) {
            return true;
        }

        // Check minimum dimensions if specified
        if (isset($requirements['min_dimensions'])) {
            $minWidth = $requirements['min_dimensions']['width'] ?? 0;
            $minHeight = $requirements['min_dimensions']['height'] ?? 0;

            if (($media->width ?? 0) < $minWidth || ($media->height ?? 0) < $minHeight) {
                return false;
            }
        }

        // Check maximum dimensions if specified
        if (isset($requirements['max_dimensions'])) {
            $maxWidth = $requirements['max_dimensions']['width'] ?? PHP_INT_MAX;
            $maxHeight = $requirements['max_dimensions']['height'] ?? PHP_INT_MAX;

            if (($media->width ?? 0) > $maxWidth || ($media->height ?? 0) > $maxHeight) {
                return false;
            }
        }

        return true;
    }
}
