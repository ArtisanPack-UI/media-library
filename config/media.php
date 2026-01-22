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
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model to use for relationships (uploaded_by, created_by, etc.).
    | Defaults to the cms-framework user model if available, otherwise App\Models\User.
    |
    */
    'user_model' => config('artisanpack.cms-framework.user_model', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Guest Uploads
    |--------------------------------------------------------------------------
    |
    | Allow uploads by non-authenticated users (guests). When enabled,
    | the uploaded_by field will use the guest_user_id if set, or null otherwise.
    |
    | WARNING: Enabling guest uploads may have security implications.
    | Consider implementing rate limiting and file validation.
    |
    */
    'allow_guest_uploads' => env('MEDIA_ALLOW_GUEST_UPLOADS', false),

    /*
    |--------------------------------------------------------------------------
    | Guest User ID
    |--------------------------------------------------------------------------
    |
    | When guest uploads are enabled, this user ID will be used as the
    | uploaded_by value. Set to null to allow null uploaded_by values.
    | You might want to create a "System" or "Guest" user for this purpose.
    |
    */
    'guest_user_id' => env('MEDIA_GUEST_USER_ID', null),

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

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Core feature toggles for v1.1 functionality. These allow you to opt-in
    | or opt-out of specific features based on your requirements.
    |
    */
    'features' => [
        /*
        |----------------------------------------------------------------------
        | Streaming Upload (Livewire 4)
        |----------------------------------------------------------------------
        |
        | Enable real-time upload progress streaming using Livewire 4's
        | wire:stream directive. When disabled or on Livewire 3, falls back
        | to polling-based progress updates.
        |
        */
        'streaming_upload' => env( 'MEDIA_STREAMING_UPLOAD', true ),

        /*
        |----------------------------------------------------------------------
        | Streaming Fallback Interval
        |----------------------------------------------------------------------
        |
        | When streaming is not available (Livewire 3), this sets the polling
        | interval in milliseconds for progress updates.
        |
        */
        'streaming_fallback_interval' => env( 'MEDIA_STREAMING_FALLBACK_INTERVAL', 500 ),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Enhancements
    |--------------------------------------------------------------------------
    |
    | Configuration for UI enhancement features from livewire-ui-components v2.0.
    |
    */
    'ui' => [
        /*
        |----------------------------------------------------------------------
        | Glass Effects
        |----------------------------------------------------------------------
        |
        | Enable glass morphism effects from livewire-ui-components for a
        | modern, translucent UI appearance.
        |
        */
        'glass_effects' => [
            'enabled' => env( 'MEDIA_GLASS_EFFECTS_ENABLED', true ),

            // Preset for card overlays (e.g., media item info overlays)
            'card_overlay' => env( 'MEDIA_GLASS_CARD_OVERLAY', 'frost' ),

            // Preset for modal backdrops
            'modal_backdrop' => env( 'MEDIA_GLASS_MODAL_BACKDROP', 'blur' ),
        ],

        /*
        |----------------------------------------------------------------------
        | Statistics Dashboard
        |----------------------------------------------------------------------
        |
        | Enable the media statistics dashboard with KPI cards and sparklines.
        |
        */
        'stats_dashboard' => [
            'enabled' => env( 'MEDIA_STATS_DASHBOARD_ENABLED', true ),

            // Number of days to display in sparkline charts
            'sparkline_days' => env( 'MEDIA_STATS_SPARKLINE_DAYS', 7 ),

            // Refresh interval in seconds (0 to disable auto-refresh)
            'refresh_interval' => env( 'MEDIA_STATS_REFRESH_INTERVAL', 60 ),
        ],

        /*
        |----------------------------------------------------------------------
        | Table Export
        |----------------------------------------------------------------------
        |
        | Enable table export functionality for media listings.
        | Requires optional packages: phpoffice/phpspreadsheet (xlsx),
        | barryvdh/laravel-dompdf (pdf).
        |
        */
        'table_export' => [
            'enabled' => env( 'MEDIA_TABLE_EXPORT_ENABLED', true ),

            // Available export formats
            'formats' => [
                'csv',
                'xlsx',
                'pdf',
            ],

            // Maximum rows to export (0 for unlimited)
            'max_rows' => env( 'MEDIA_TABLE_EXPORT_MAX_ROWS', 10000 ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Editor / CMS Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Keystone CMS visual editor integration and the
    | MediaPicker component.
    |
    */
    'visual_editor' => [
        /*
        |----------------------------------------------------------------------
        | Track Recently Used Media
        |----------------------------------------------------------------------
        |
        | Keep track of recently selected media items per user for quick
        | access in the media picker.
        |
        */
        'track_recently_used' => env( 'MEDIA_TRACK_RECENTLY_USED', true ),

        // Maximum number of recently used items to track per user
        'recently_used_limit' => env( 'MEDIA_RECENTLY_USED_LIMIT', 20 ),

        /*
        |----------------------------------------------------------------------
        | Quick Upload Select
        |----------------------------------------------------------------------
        |
        | Automatically select media items immediately after upload,
        | streamlining the upload-and-select workflow.
        |
        */
        'quick_upload_select' => env( 'MEDIA_QUICK_UPLOAD_SELECT', true ),

        /*
        |----------------------------------------------------------------------
        | Media Picker Settings
        |----------------------------------------------------------------------
        |
        | Configuration for the MediaPicker component used in the visual
        | editor and content blocks.
        |
        */
        'picker' => [
            // Default view mode: 'grid' or 'list'
            'default_view' => env( 'MEDIA_PICKER_DEFAULT_VIEW', 'grid' ),

            // Items per page in the picker modal
            'per_page' => env( 'MEDIA_PICKER_PER_PAGE', 24 ),

            // Show upload tab in picker modal
            'show_upload_tab' => env( 'MEDIA_PICKER_SHOW_UPLOAD_TAB', true ),

            // Enable drag-and-drop reordering for multi-select
            'enable_reorder' => env( 'MEDIA_PICKER_ENABLE_REORDER', true ),

            // Show media details panel in picker
            'show_details_panel' => env( 'MEDIA_PICKER_SHOW_DETAILS_PANEL', true ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Requirements
    |--------------------------------------------------------------------------
    |
    | Define media type requirements for specific content block types.
    | Used by the visual editor to validate media selection for blocks.
    |
    */
    'block_requirements' => [
        /*
        |----------------------------------------------------------------------
        | Default Requirements
        |----------------------------------------------------------------------
        |
        | Default media requirements applied when a block doesn't specify
        | its own requirements.
        |
        */
        'default' => [
            'types' => [ 'image', 'video', 'audio', 'document' ],
            'max_files' => 1,
            'min_files' => 0,
        ],

        /*
        |----------------------------------------------------------------------
        | Image Block
        |----------------------------------------------------------------------
        */
        'image' => [
            'types' => [ 'image' ],
            'max_files' => 1,
            'min_files' => 1,
            'allowed_extensions' => [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg' ],
        ],

        /*
        |----------------------------------------------------------------------
        | Gallery Block
        |----------------------------------------------------------------------
        */
        'gallery' => [
            'types' => [ 'image' ],
            'max_files' => 50,
            'min_files' => 1,
            'allowed_extensions' => [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' ],
        ],

        /*
        |----------------------------------------------------------------------
        | Video Block
        |----------------------------------------------------------------------
        */
        'video' => [
            'types' => [ 'video' ],
            'max_files' => 1,
            'min_files' => 1,
            'allowed_extensions' => [ 'mp4', 'webm', 'mov' ],
        ],

        /*
        |----------------------------------------------------------------------
        | Audio Block
        |----------------------------------------------------------------------
        */
        'audio' => [
            'types' => [ 'audio' ],
            'max_files' => 1,
            'min_files' => 1,
            'allowed_extensions' => [ 'mp3', 'wav', 'ogg' ],
        ],

        /*
        |----------------------------------------------------------------------
        | Document Block
        |----------------------------------------------------------------------
        */
        'document' => [
            'types' => [ 'document' ],
            'max_files' => 10,
            'min_files' => 1,
            'allowed_extensions' => [ 'pdf', 'doc', 'docx', 'xls', 'xlsx' ],
        ],

        /*
        |----------------------------------------------------------------------
        | Hero Block
        |----------------------------------------------------------------------
        */
        'hero' => [
            'types' => [ 'image', 'video' ],
            'max_files' => 1,
            'min_files' => 0,
            'recommended_dimensions' => [
                'width' => 1920,
                'height' => 1080,
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Background Block
        |----------------------------------------------------------------------
        */
        'background' => [
            'types' => [ 'image', 'video' ],
            'max_files' => 1,
            'min_files' => 0,
            'allowed_extensions' => [ 'jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm' ],
        ],
    ],
];
