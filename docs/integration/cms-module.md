---
title: CMS Module Integration
---

# CMS Module Integration

The Media Library includes a dedicated module for integration with the Digital Shopfront CMS. This module provides admin pages, settings management, and runtime configuration overrides.

## Module Overview

The Media module is located in `Modules/Media/` and provides:

- Media Library admin page
- Media Upload admin page
- Media Settings admin page
- Runtime configuration from database settings
- Permission integration with CMS roles

## Installation

The Media module is automatically discovered by the CMS if present in the `Modules/` directory.

### Manual Registration

If needed, register the module in `config/modules.php`:

```php
'modules' => [
    'Media' => [
        'enabled' => true,
        'provider' => 'Modules\Media\Providers\MediaServiceProvider',
    ],
],
```

## Admin Pages

The module registers three admin pages:

### Media Library Page

**Route:** `/admin/media`

Full-featured media browsing interface with:
- Grid/list view
- Folder navigation
- Tag filtering
- Search functionality
- Bulk actions

**Permissions Required:** `media.view`

### Add Media Page

**Route:** `/admin/media/add`

Drag-and-drop upload interface with:
- Multi-file upload
- Progress tracking
- Folder selection
- Tag assignment
- Metadata input

**Permissions Required:** `media.upload`

### Media Settings Page

**Route:** `/admin/settings/media`

Configure media library settings:
- Allowed file types
- Maximum file size
- Upload path format
- Modern format conversion
- Image quality
- Thumbnail generation

**Permissions Required:** `settings.manage`

## Settings Integration

The module registers these settings:

- `media.allowedFileTypes` - Allowed MIME types (stored as string)
- `media.maxFileSize` - Maximum file size in KB
- `media.uploadPathFormat` - Upload directory structure
- `media.enableModernFormats` - Enable WebP/AVIF conversion
- `media.modernFormat` - Format to use (`webp` or `avif`)
- `media.enableThumbnails` - Enable thumbnail generation
- `media.imageQuality` - Image quality (1-100)

### Runtime Configuration Override

Settings from the database automatically override package configuration at runtime:

```php
// In MediaServiceProvider::boot()
$this->overrideConfigFromSettings();
```

This allows administrators to change media library behavior through the admin interface without modifying configuration files.

### Accessing Settings

In your code:

```php
// Get setting with fallback to config
$maxSize = apGetSetting('media.maxFileSize', config('artisanpack.media.max_file_size'));

// Get all media settings
$mediaSettings = apGetSettings('media');
```

## Menu Integration

The module adds menu items to the admin sidebar:

```php
addFilter('admin.menu.items', function ($items) {
    $items['media'] = [
        'label' => __('Media'),
        'icon' => 'photo',
        'order' => 40,
        'capability' => 'media.view',
        'children' => [
            [
                'label' => __('Library'),
                'url' => dsGetAdminUrl('media'),
                'capability' => 'media.view',
            ],
            [
                'label' => __('Add New'),
                'url' => dsGetAdminUrl('media/add'),
                'capability' => 'media.upload',
            ],
        ],
    ];
    
    return $items;
});
```

## Role & Permission Setup

Set up permissions for different roles:

```php
use ArtisanPackUI\CMSFramework\Modules\Users\Models\Role;

// Administrator - Full access
$admin = Role::where('slug', 'administrator')->first();
$admin->capabilities = array_merge($admin->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit',
    'media.delete',
]);
$admin->save();

// Editor - All except delete
$editor = Role::where('slug', 'editor')->first();
$editor->capabilities = array_merge($editor->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit',
]);
$editor->save();

// Author - View and upload only
$author = Role::where('slug', 'author')->first();
$author->capabilities = array_merge($author->capabilities ?? [], [
    'media.view',
    'media.upload',
]);
$author->save();
```

## Extending the Module

### Adding Custom Settings

Register additional settings in your module's service provider:

```php
public function boot(): void
{
    apRegisterSetting('media.customSetting', 'default-value');
}
```

### Custom Admin Pages

Add custom admin pages related to media:

```php
use Livewire\Volt\Volt;

Volt::route('/admin/media/analytics', AnalyticsPage::class)
    ->middleware(['auth', 'verified']);
```

### Custom Hooks

The module fires hooks you can use:

```php
// Before saving settings
addAction('media.settings.before_save', function ($settings) {
    // Custom logic
});

// After saving settings
addAction('media.settings.after_save', function ($settings) {
    // Clear caches, etc.
});
```

## Module Structure

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

## Testing

The module includes comprehensive tests:

```bash
# Run all Media module tests
php artisan test Modules/Media

# Run specific test
php artisan test Modules/Media/tests/Feature/MediaSettingsPageTest.php
```

## Next Steps

- Review [Permissions & Access Control](./permissions.md)
- Explore [Customization](./customization.md) options
- See [Helper Functions](../usage/helper-functions.md) for usage patterns
