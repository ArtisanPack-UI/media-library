---
title: Getting Started with Media Library
---

# Getting Started

This guide helps you quickly set up the ArtisanPack UI Media Library package in your Laravel application. You'll learn how to install the package, run migrations, and perform basic operations.

## Prerequisites

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Composer 2+
- Intervention Image 3.0 (installed automatically)
- FFmpeg (optional, for video thumbnail extraction)

## Quick Installation

1. **Install the package via Composer:**

```bash
composer require artisanpack-ui/media-library
```

2. **Run migrations:**

```bash
php artisan vendor:publish --tag=media-migrations
php artisan migrate
```

This creates four tables:
- `media` - Media file information
- `media_folders` - Hierarchical folder structure
- `media_tags` - Tag definitions
- `media_taggables` - Media-tag relationships

3. **Create storage link:**

```bash
php artisan storage:link
```

4. **Configure environment (optional):**

Add to your `.env` file:

```env
MEDIA_DISK=public
MEDIA_MAX_FILE_SIZE=10240
MEDIA_ENABLE_MODERN_FORMATS=true
MEDIA_MODERN_FORMAT=webp
MEDIA_IMAGE_QUALITY=85
```

## Basic Usage

### Upload Media

```php
use Illuminate\Http\UploadedFile;

$file = $request->file('upload');

$media = apUploadMedia($file, [
    'title' => 'My Image',
    'alt_text' => 'Alt text for accessibility',
    'folder_id' => null, // Optional folder
]);
```

### Get Media URL

```php
// Get original file URL
$url = apGetMediaUrl($mediaId);

// Get specific image size
$thumbnailUrl = apGetMediaUrl($mediaId, 'thumbnail');
$mediumUrl = apGetMediaUrl($mediaId, 'medium');
```

### Display Media

```php
$media = apGetMedia($mediaId);

// Display with automatic escaping
echo $media->displayImage('large', ['class' => 'img-fluid']);
```

### Use Media Modal in Livewire

```blade
{{-- Include modal in your view --}}
<livewire:media::media-modal
    :multi-select="false"
    context="profile-photo"
    wire:key="profile-photo-modal"
/>

{{-- Button to trigger modal --}}
<button wire:click="$dispatch('open-media-modal', { context: 'profile-photo' })">
    Select Photo
</button>

{{-- Listen for selection in your component --}}
@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'profile-photo') {
            $wire.set('photoId', event.media[0].id);
        }
    });
});
</script>
@endscript
```

## Testing

Run the test suite to verify installation:

```bash
php artisan test --filter=Media
```

All tests should pass if the installation is successful.

## Next Steps

- Read [Installation](Installation-Installation) for detailed setup options
- Review [Configuration](Installation-Configuration) for all available settings
- Explore [Helper Functions](Usage-Helper-Functions) for common patterns
- Check [Livewire Components](Usage-Livewire-Components) for UI integration
- See [CMS Module Integration](Integration-Cms-Module) for Digital Shopfront CMS setup

## Common First Tasks

### Create Folders

```php
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;

$folder = MediaFolder::create([
    'name' => 'Products',
    'description' => 'Product images',
    'created_by' => auth()->id(),
]);
```

### Create Tags

```php
use ArtisanPackUI\MediaLibrary\Models\MediaTag;

$tag = MediaTag::create([
    'name' => 'Featured',
    'slug' => 'featured',
]);
```

### Set Up Permissions

```php
use ArtisanPackUI\CMSFramework\Modules\Users\Models\Role;

$role = Role::where('slug', 'editor')->first();
$role->capabilities = array_merge($role->capabilities, [
    'media.view',
    'media.upload',
    'media.edit',
    'media.delete',
]);
$role->save();
```

## Getting Help

If you encounter issues:

1. Check the [Troubleshooting](Reference-Troubleshooting) guide
2. Review the [FAQ](Reference-Faq)
3. Ensure all prerequisites are installed
4. Verify database migrations ran successfully

For additional support, contact support@artisanpack.com
