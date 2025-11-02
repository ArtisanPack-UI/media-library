# Media Library Integration Guide

This guide provides comprehensive instructions for integrating the ArtisanPack UI Media Library package into your Laravel application or Digital Shopfront CMS.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [CMS Module Integration](#cms-module-integration)
4. [Helper Functions](#helper-functions)
5. [Using Models](#using-models)
6. [Livewire Components](#livewire-components)
7. [API Endpoints](#api-endpoints)
8. [Permissions](#permissions)
9. [Customization](#customization)
10. [Troubleshooting](#troubleshooting)

## Installation

### Step 1: Install the Package

Install the package via Composer:

```bash
composer require artisanpack-ui/media-library
```

### Step 2: Run Migrations

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=media-migrations
php artisan migrate
```

This creates four tables:
- `media` - Stores media file information
- `media_folders` - Hierarchical folder structure
- `media_tags` - Tag definitions
- `media_taggables` - Pivot table for media-tag relationships

### Step 3: Create Storage Link

Ensure your public storage is linked:

```bash
php artisan storage:link
```

### Step 4: Install FFmpeg (Optional)

For video thumbnail extraction, install FFmpeg:

```bash
# Ubuntu/Debian
sudo apt-get install ffmpeg

# macOS (Homebrew)
brew install ffmpeg

# Verify installation
ffmpeg -version
```

## Configuration

### Publish Configuration

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=media-config
```

This merges settings into `config/artisanpack.php` under the `media` key.

### Configuration Options

```php
return [
    'media' => [
        // Storage disk for uploads
        'disk' => env('MEDIA_DISK', 'public'),

        // Maximum file size in kilobytes
        'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10240),

        // Upload path format: {year}, {month}, {day}, {user_id}
        'upload_path_format' => env('MEDIA_UPLOAD_PATH_FORMAT', '{year}/{month}'),

        // Enable modern format conversion (WebP/AVIF)
        'enable_modern_formats' => env('MEDIA_ENABLE_MODERN_FORMATS', true),

        // Modern format: 'webp' or 'avif'
        'modern_format' => env('MEDIA_MODERN_FORMAT', 'webp'),

        // Image quality (1-100)
        'image_quality' => env('MEDIA_IMAGE_QUALITY', 85),

        // Enable thumbnail generation
        'enable_thumbnails' => env('MEDIA_ENABLE_THUMBNAILS', true),

        // Allowed MIME types
        'allowed_mime_types' => [
            // Images
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'image/webp', 'image/avif', 'image/svg+xml',
            // Videos
            'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm',
            // Audio
            'audio/mpeg', 'audio/wav', 'audio/ogg',
            // Documents
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],

        // Image sizes
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

        // Custom image sizes (registered at runtime)
        'custom_image_sizes' => [],

        // User model for relationships
        'user_model' => null, // Auto-detected
    ],
];
```

### Environment Variables

Add to your `.env` file:

```env
MEDIA_DISK=public
MEDIA_MAX_FILE_SIZE=10240
MEDIA_UPLOAD_PATH_FORMAT={year}/{month}
MEDIA_ENABLE_MODERN_FORMATS=true
MEDIA_MODERN_FORMAT=webp
MEDIA_IMAGE_QUALITY=85
MEDIA_ENABLE_THUMBNAILS=true
```

## CMS Module Integration

For Digital Shopfront CMS, a dedicated Media module is included that integrates the package with the CMS admin interface.

### Module Structure

```
Modules/Media/
├── Providers/
│   ├── MediaServiceProvider.php
│   ├── EventServiceProvider.php
│   └── RouteServiceProvider.php
├── app/Livewire/Pages/
│   └── MediaSettingsPage.php
├── resources/views/livewire/pages/
│   └── media-settings.blade.php
├── tests/Feature/
│   ├── MediaSettingsPageTest.php
│   └── RuntimeOverridesTest.php
├── config/config.php
├── composer.json
└── module.json
```

### Settings Integration

The Media module registers settings that override package defaults at runtime:

```php
// Registered settings
apRegisterSetting('media.allowedFileTypes', config('artisanpack.media.allowed_mime_types'));
apRegisterSetting('media.maxFileSize', config('artisanpack.media.max_file_size'));
apRegisterSetting('media.uploadPathFormat', config('artisanpack.media.upload_path_format'));
apRegisterSetting('media.enableModernFormats', config('artisanpack.media.enable_modern_formats'));
apRegisterSetting('media.modernFormat', config('artisanpack.media.modern_format'));
apRegisterSetting('media.enableThumbnails', config('artisanpack.media.enable_thumbnails'));
apRegisterSetting('media.imageQuality', config('artisanpack.media.image_quality'));
```

### Admin Pages

The module registers these admin pages:

- **Media Library** (`/admin/media`) - Browse and manage media
- **Add Media** (`/admin/media/add`) - Upload new media
- **Media Settings** (`/admin/settings/media`) - Configure media settings

## Helper Functions

### apUploadMedia()

Upload a media file:

```php
use Illuminate\Http\UploadedFile;

$file = $request->file('upload');

$media = apUploadMedia($file, [
    'title' => 'My Image',
    'alt_text' => 'Alt text for accessibility',
    'caption' => 'Optional caption',
    'description' => 'Optional description',
    'folder_id' => 1, // Optional folder
    'tags' => ['tag-slug-1', 'tag-slug-2'], // Optional tags
]);
```

### apGetMedia()

Get a media item by ID:

```php
$media = apGetMedia($mediaId);
```

### apGetMediaUrl()

Get the URL for a media item:

```php
// Original file
$url = apGetMediaUrl($mediaId);

// Specific size
$thumbnailUrl = apGetMediaUrl($mediaId, 'thumbnail');
$mediumUrl = apGetMediaUrl($mediaId, 'medium');
$largeUrl = apGetMediaUrl($mediaId, 'large');
```

### apDeleteMedia()

Delete a media item and its files:

```php
apDeleteMedia($mediaId);
```

### apRegisterImageSize()

Register a custom image size:

```php
// In a service provider
apRegisterImageSize('product-thumbnail', 250, 250, true);

// Use it
$url = apGetMediaUrl($mediaId, 'product-thumbnail');
```

## Using Models

### Media Model

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

// Get all images
$images = Media::images()->get();

// Get videos
$videos = Media::videos()->get();

// Get media in a folder
$media = Media::inFolder($folderId)->get();

// Get media with a tag
$media = Media::withTag('featured')->get();

// Get media instance
$media = Media::find($id);

// Get URLs
$originalUrl = $media->url();
$thumbnailUrl = $media->imageUrl('thumbnail');

// Display image with escaping
echo $media->displayImage('large', ['class' => 'img-fluid']);

// Type checking
if ($media->isImage()) {
    // Handle image
}

// Human-readable file size
echo $media->humanFileSize(); // "2.5 MB"
```

### MediaFolder Model

```php
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;

// Create folder
$folder = MediaFolder::create([
    'name' => 'Products',
    'description' => 'Product images',
    'parent_id' => null,
    'created_by' => auth()->id(),
]);

// Get folder hierarchy
$path = $folder->fullPath(); // "Parent/Child/Grandchild"

// Get media in folder
$media = $folder->media;

// Get child folders
$children = $folder->children;

// Move folder
$folder->moveTo($newParentId);
```

### MediaTag Model

```php
use ArtisanPackUI\MediaLibrary\Models\MediaTag;

// Create tag
$tag = MediaTag::create([
    'name' => 'Featured',
    'slug' => 'featured',
]);

// Get media with tag
$media = $tag->media;

// Get media count
$count = $tag->mediaCount();
```

## Livewire Components

### Media Library Page

Full-featured media browsing:

```blade
<livewire:media::media-library />
```

### Media Upload Page

Drag-and-drop upload:

```blade
<livewire:media::media-upload />
```

### Media Modal Component

Single/multi-select modal:

```blade
{{-- Include modal --}}
<livewire:media::media-modal
    :multi-select="false"
    :max-selections="0"
    context="site-logo"
    wire:key="site-logo-modal"
/>

{{-- Trigger button --}}
<button wire:click="$dispatch('open-media-modal', { context: 'site-logo' })">
    Select Logo
</button>

{{-- Listen for selection --}}
@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'site-logo') {
            console.log('Selected media:', event.media);
        }
    });
});
</script>
@endscript
```

#### Modal Properties

- `multi-select` (boolean) - Enable multi-select mode
- `max-selections` (int) - Maximum selections (0 = unlimited)
- `context` (string) - Context identifier for this modal instance

#### Using Multiple Modals on Same Page

```blade
{{-- Site Logo --}}
<livewire:media::media-modal
    :multi-select="false"
    context="site-logo"
    wire:key="site-logo-modal"
/>

{{-- Background Image --}}
<livewire:media::media-modal
    :multi-select="false"
    context="background-image"
    wire:key="background-modal"
/>

{{-- Different contexts ensure correct modal opens --}}
<button wire:click="$dispatch('open-media-modal', { context: 'site-logo' })">
    Select Logo
</button>

<button wire:click="$dispatch('open-media-modal', { context: 'background-image' })">
    Select Background
</button>

{{-- Handle selections --}}
@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'site-logo') {
            $wire.set('form.siteLogo', event.media[0].id);
        } else if (event.context === 'background-image') {
            $wire.set('form.backgroundImage', event.media[0].id);
        }
    });
});
</script>
@endscript
```

## API Endpoints

All endpoints require `auth:sanctum` middleware.

### Media Endpoints

```
GET    /api/media              List all media (paginated)
POST   /api/media              Upload new media
GET    /api/media/{id}         Get specific media
PUT    /api/media/{id}         Update media metadata
DELETE /api/media/{id}         Delete media
```

**Query Parameters:**

- `per_page` - Items per page (default: 15)
- `folder_id` - Filter by folder
- `tag` - Filter by tag slug
- `type` - Filter by type: 'image', 'video', 'audio', 'document'
- `search` - Search title and filename
- `sort_by` - Sort column (default: 'created_at')
- `sort_order` - Sort direction: 'asc' or 'desc'

### Folder Endpoints

```
GET    /api/media/folders           List all folders
POST   /api/media/folders           Create folder
GET    /api/media/folders/{id}      Get specific folder
PUT    /api/media/folders/{id}      Update folder
DELETE /api/media/folders/{id}      Delete folder
POST   /api/media/folders/{id}/move Move folder
```

### Tag Endpoints

```
GET    /api/media/tags              List all tags
POST   /api/media/tags              Create tag
GET    /api/media/tags/{id}         Get specific tag
PUT    /api/media/tags/{id}         Update tag
DELETE /api/media/tags/{id}         Delete tag
POST   /api/media/tags/{id}/attach  Attach tag to media
POST   /api/media/tags/{id}/detach  Detach tag from media
```

## Permissions

The package uses capability-based permissions:

- `media.view` - View media library
- `media.upload` - Upload new media
- `media.edit` - Edit media metadata
- `media.delete` - Delete media

### Setting Up Roles

```php
use ArtisanPackUI\CMSFramework\Modules\Users\Models\Role;

// Administrator
$admin = Role::where('slug', 'administrator')->first();
$admin->capabilities = array_merge($admin->capabilities, [
    'media.view',
    'media.upload',
    'media.edit',
    'media.delete',
]);
$admin->save();

// Editor
$editor = Role::where('slug', 'editor')->first();
$editor->capabilities = array_merge($editor->capabilities, [
    'media.view',
    'media.upload',
    'media.edit',
    'media.delete',
]);
$editor->save();

// Author (limited)
$author = Role::where('slug', 'author')->first();
$author->capabilities = array_merge($author->capabilities, [
    'media.view',
    'media.upload',
    'media.edit',
    // NO delete capability
]);
$author->save();
```

### Custom Permission Hooks

```php
// Customize capability checks
addFilter('ap.media.viewAny', function($capability) {
    return 'custom.media.view';
});

addFilter('ap.media.create', function($capability) {
    return 'custom.media.upload';
});
```

## Customization

### Publish Views

Customize all Blade views:

```bash
php artisan vendor:publish --tag=media-views
```

Views will be published to `resources/views/vendor/media/`.

### Custom Storage Disk

Configure in `config/filesystems.php`:

```php
'disks' => [
    's3-media' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_MEDIA_BUCKET'),
    ],
],
```

Update `.env`:

```env
MEDIA_DISK=s3-media
```

### Custom Image Sizes

In a service provider:

```php
public function boot(): void
{
    apRegisterImageSize('hero-banner', 1920, 600, true);
    apRegisterImageSize('product-grid', 400, 400, true);
    apRegisterImageSize('blog-thumbnail', 600, 400, false);
}
```

## Troubleshooting

### Video Thumbnails Not Generating

Ensure FFmpeg is installed:

```bash
ffmpeg -version
```

If not installed, see installation instructions for your OS.

### Image Processing Errors

Check available drivers:

```bash
php -m | grep -i gd
php -m | grep -i imagick
```

Install missing drivers:

```bash
# GD (Ubuntu/Debian)
sudo apt-get install php-gd

# Imagick
sudo apt-get install php-imagick
```

### Permission Denied Errors

Ensure writable directories:

```bash
chmod -R 775 storage/
chmod -R 775 public/storage/
```

### Large File Uploads

Update `php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M
```

Restart PHP-FPM or web server after changes.

### Modern Format Conversion Fails

Ensure Intervention Image supports WebP/AVIF:

```bash
# Check WebP support
php -r "echo (imagetypes() & IMG_WEBP) ? 'WebP supported' : 'WebP not supported';"

# Check AVIF support (requires Imagick)
php -r "if (extension_loaded('imagick')) { echo in_array('AVIF', Imagick::queryFormats()) ? 'AVIF supported' : 'AVIF not supported'; } else { echo 'Imagick not installed'; }"
```

## Support

For issues or questions:

- Create an issue in the repository
- Email support@artisanpack.com

## Additional Resources

- [Laravel Filesystem Documentation](https://laravel.com/docs/12.x/filesystem)
- [Intervention Image Documentation](https://image.intervention.io/v3)
- [Livewire Documentation](https://livewire.laravel.com)
