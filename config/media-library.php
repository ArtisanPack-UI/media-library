<?php
/**
 * Media Library Configuration
 *
 * Configuration settings for the ArtisanPack UI Media Library package.
 * These settings control file storage, validation, security, and behavior
 * of media operations within the application.
 *
 * @link       https://gitlab.com/jacob-martella-web-design/artisanpack-ui/artisanpack-ui-media-library
 *
 * @package    ArtisanPackUI\MediaLibrary
 * @subpackage Config
 * @since      1.0.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control where and how media files are stored.
    |
    */

    /**
     * The default storage disk to use for media files.
     * 
     * This should correspond to a disk configured in config/filesystems.php
     * Common values: 'public', 'local', 's3'
     *
     * @since 1.0.0
     */
    'disk' => env('MEDIA_DISK', 'public'),

    /**
     * The base directory for media file storage.
     * 
     * Files will be stored in a year/month structure under this directory.
     * Example: media/2024/08/filename.jpg
     *
     * @since 1.0.0
     */
    'directory' => env('MEDIA_DIRECTORY', 'media'),

    /**
     * Whether to organize files by date (year/month structure).
     *
     * @since 1.0.0
     */
    'organize_by_date' => env('MEDIA_ORGANIZE_BY_DATE', true),

    /*
    |--------------------------------------------------------------------------
    | File Validation
    |--------------------------------------------------------------------------
    |
    | These settings control what types of files can be uploaded and their
    | size limits.
    |
    */

    /**
     * Maximum file size allowed for uploads (in bytes).
     * 
     * Default: 10MB (10 * 1024 * 1024)
     *
     * @since 1.0.0
     */
    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10485760),

    /**
     * Allowed MIME types for file uploads.
     * 
     * Common image types are included by default.
     * Add or remove types as needed for your application.
     *
     * @since 1.0.0
     */
    'allowed_mime_types' => [
        // Images
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        
        // Documents  
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        
        // Video
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
    ],

    /**
     * Allowed file extensions for uploads.
     * 
     * This provides an additional layer of validation beyond MIME types.
     *
     * @since 1.0.0
     */
    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv',
        'zip', 'rar',
        'mp3', 'wav', 'ogg',
        'mp4', 'mov', 'avi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Settings for automatic image processing and optimization.
    |
    */

    /**
     * Whether to automatically generate thumbnails for images.
     *
     * @since 1.0.0
     */
    'generate_thumbnails' => env('MEDIA_GENERATE_THUMBNAILS', true),

    /**
     * Thumbnail sizes to generate.
     * 
     * Each key represents a size name, and the value is [width, height].
     * Set height to null to maintain aspect ratio.
     *
     * @since 1.0.0
     */
    'thumbnail_sizes' => [
        'thumb' => [150, 150],
        'medium' => [300, null],
        'large' => [800, null],
    ],

    /**
     * Default image quality for processed images (1-100).
     *
     * @since 1.0.0
     */
    'image_quality' => env('MEDIA_IMAGE_QUALITY', 85),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for media handling.
    |
    */

    /**
     * Whether to sanitize uploaded filenames.
     * 
     * Recommended to keep enabled for security.
     *
     * @since 1.0.0
     */
    'sanitize_filenames' => env('MEDIA_SANITIZE_FILENAMES', true),

    /**
     * Whether to scan uploaded files for malware (requires clamav or similar).
     *
     * @since 1.0.0
     */
    'scan_for_malware' => env('MEDIA_SCAN_MALWARE', false),

    /**
     * Characters to remove or replace in filenames during sanitization.
     *
     * @since 1.0.0
     */
    'filename_sanitization' => [
        'remove' => [' ', '(', ')', '[', ']', '{', '}', '&', '@', '#', '%', '^'],
        'replace' => ['_'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Settings  
    |--------------------------------------------------------------------------
    |
    | Configuration for accessibility features.
    |
    */

    /**
     * Whether alt text is required for image uploads.
     *
     * @since 1.0.0
     */
    'require_alt_text' => env('MEDIA_REQUIRE_ALT_TEXT', true),

    /**
     * Whether to allow decorative images (images with empty alt text).
     *
     * @since 1.0.0
     */
    'allow_decorative' => env('MEDIA_ALLOW_DECORATIVE', true),

    /**
     * Maximum length for alt text.
     *
     * @since 1.0.0
     */
    'max_alt_text_length' => env('MEDIA_MAX_ALT_TEXT_LENGTH', 200),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for API responses and pagination.
    |
    */

    /**
     * Default number of items per page for API responses.
     *
     * @since 1.0.0
     */
    'per_page_default' => env('MEDIA_PER_PAGE_DEFAULT', 15),

    /**
     * Maximum number of items per page allowed in API requests.
     *
     * @since 1.0.0
     */
    'per_page_max' => env('MEDIA_PER_PAGE_MAX', 100),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for media-related logging.
    |
    */

    /**
     * Whether to log media operations (uploads, updates, deletions).
     *
     * @since 1.0.0
     */
    'log_operations' => env('MEDIA_LOG_OPERATIONS', true),

    /**
     * Log level for media operations.
     * 
     * Available: 'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
     *
     * @since 1.0.0
     */
    'log_level' => env('MEDIA_LOG_LEVEL', 'info'),
];