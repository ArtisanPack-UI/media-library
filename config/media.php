<?php

declare(strict_types=1);

/**
 * ArtisanPack UI - Media Library Configuration
 *
 * This configuration file defines settings for the Media Library package.
 * Settings are merged into the main artisanpack.php config file under the
 * 'media' key, following ArtisanPack UI package conventions.
 *
 * After publishing, this file can be found at: config/artisanpack/media.php
 *
 * @package    ArtisanPackUI\MediaLibrary
 *
 * @since      1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to use for media uploads.
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | Define which file types can be uploaded. Use MIME types.
    |
    */
    'allowed_mime_types' => [
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/svg+xml',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // Videos
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/webm',

        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | Maximum file size in kilobytes. Default: 10MB
    |
    */
    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10240),

    /*
    |--------------------------------------------------------------------------
    | Upload Path Format
    |--------------------------------------------------------------------------
    |
    | Path format for uploaded files. Available variables:
    | {year} - 4 digit year
    | {month} - 2 digit month
    | {day} - 2 digit day
    | {user_id} - Uploading user's ID
    |
    */
    'upload_path_format' => env('MEDIA_UPLOAD_PATH_FORMAT', '{year}/{month}'),

    /*
    |--------------------------------------------------------------------------
    | Enable Modern Image Formats
    |--------------------------------------------------------------------------
    |
    | Automatically convert uploaded images to modern formats (WebP/AVIF)
    | while keeping the original file.
    |
    */
    'enable_modern_formats' => env('MEDIA_ENABLE_MODERN_FORMATS', true),

    /*
    |--------------------------------------------------------------------------
    | Modern Image Format
    |--------------------------------------------------------------------------
    |
    | Which modern format to use: 'webp' or 'avif'
    |
    */
    'modern_format' => env('MEDIA_MODERN_FORMAT', 'webp'),

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Quality for image compression (1-100). Higher is better quality.
    |
    */
    'image_quality' => env('MEDIA_IMAGE_QUALITY', 85),

    /*
    |--------------------------------------------------------------------------
    | Enable Thumbnails
    |--------------------------------------------------------------------------
    |
    | Generate thumbnails for uploaded images.
    |
    */
    'enable_thumbnails' => env('MEDIA_ENABLE_THUMBNAILS', true),

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | Define image sizes to generate. Each size should have:
    | - width: max width in pixels (null for no constraint)
    | - height: max height in pixels (null for no constraint)
    | - crop: whether to crop to exact dimensions
    |
    */
    'image_sizes' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'crop' => true,
        ],
        'medium' => [
            'width' => 300,
            'height' => 300,
            'crop' => false,
        ],
        'large' => [
            'width' => 1024,
            'height' => 1024,
            'crop' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Image Sizes
    |--------------------------------------------------------------------------
    |
    | Additional custom image sizes registered at runtime.
    | This is populated via the registerImageSize helper.
    |
    */
    'custom_image_sizes' => [],
];
