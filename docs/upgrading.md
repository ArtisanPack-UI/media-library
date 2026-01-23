---
title: Upgrading to v1.1
---

# Upgrading to v1.1

This guide covers upgrading from ArtisanPack UI Media Library v1.0.x to v1.1. The v1.1 release is fully backward compatible with no breaking changes.

## Requirements

Before upgrading, ensure your application meets these requirements:

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 11.0 or higher |
| Livewire | 3.6+ or 4.0+ |
| livewire-ui-components | ^2.0 |

## Upgrade Steps

### 1. Update Dependencies

Update the media-library package and its dependencies:

```bash
composer update artisanpack-ui/media-library artisanpack-ui/livewire-ui-components
```

This will update:
- `artisanpack-ui/media-library` to v1.1
- `artisanpack-ui/livewire-ui-components` to v2.0 (required for glass effects and stats dashboard)

### 2. Clear Caches

Clear your application caches to ensure the new configuration is loaded:

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

### 3. Publish Updated Configuration (Optional)

If you want to customize the new v1.1 features, publish the updated configuration:

```bash
php artisan vendor:publish --tag=media-config --force
```

**Note:** Using `--force` will overwrite your existing configuration. If you have customizations, manually merge the new options instead (see [New Configuration Options](#new-configuration-options) below).

### 4. Run Migrations (If Any)

Check for and run any new migrations:

```bash
php artisan migrate
```

**Note:** v1.1 does not include new migrations. Your existing database schema is compatible.

### 5. Rebuild Assets

If you're using the package's JavaScript or CSS:

```bash
npm run build
```

## Breaking Changes

**None.** Version 1.1 is fully backward compatible with v1.0.x. All existing code will continue to work without modifications.

## New Features Available

After upgrading, these features are available:

### For All Users

| Feature | Default | Description |
|---------|---------|-------------|
| Glass Effects | Enabled | Modern glassmorphism UI styling |
| Media Statistics | Enabled | KPI dashboard with sparklines |
| Table Export | Enabled | Export to CSV/XLSX/PDF |
| MediaPicker | Available | Visual editor integration component |
| Keyboard Navigation | Enabled | Arrow keys, Enter, Escape support |
| Recently Used Media | Enabled | Quick access to recent selections |

### For Livewire 4 Users

| Feature | Default | Description |
|---------|---------|-------------|
| Streaming Uploads | Enabled | Real-time upload progress via `wire:stream` |

Livewire 3 users automatically get a polling-based fallback for upload progress.

## New Configuration Options

The following configuration options are new in v1.1. Add them to your `config/artisanpack.php` file under the `media` key if you published configuration previously:

### Feature Flags

```php
'features' => [
    // Enable Livewire 4 streaming uploads (auto-fallback for Livewire 3)
    'streaming_upload' => env('MEDIA_STREAMING_UPLOAD', true),

    // Polling interval (ms) for Livewire 3 fallback
    'streaming_fallback_interval' => env('MEDIA_STREAMING_FALLBACK_INTERVAL', 500),
],
```

### UI Settings

```php
'ui' => [
    // Glass effect settings
    'glass_effects' => [
        'enabled' => env('MEDIA_GLASS_EFFECTS', true),
        'card_overlay' => [
            'blur' => 'md',    // sm, md, lg, xl, 2xl
            'opacity' => 80,   // 0-100
        ],
        'modal_backdrop' => [
            'blur' => 'sm',
            'opacity' => 50,
        ],
    ],

    // Statistics dashboard settings
    'stats_dashboard' => [
        'enabled' => env('MEDIA_STATS_ENABLED', true),
        'sparkline_days' => 30,
        'refresh_interval' => 0, // seconds, 0 = disabled
    ],

    // Table export settings
    'table_export' => [
        'enabled' => env('MEDIA_TABLE_EXPORT', true),
        'formats' => ['csv', 'xlsx', 'pdf'],
        'max_rows' => 10000,
    ],
],
```

### Visual Editor Integration

```php
'visual_editor' => [
    'track_recently_used' => true,
    'recently_used_limit' => 20,
    'quick_upload_select' => true,

    'picker' => [
        'default_view' => 'grid',
        'items_per_page' => 24,
        'show_folders' => true,
        'allow_upload' => true,
    ],
],
```

### Block Requirements

```php
'block_requirements' => [
    'default' => [
        'allowed_types' => ['image', 'video', 'audio', 'document'],
        'max_file_size' => null,
        'max_selections' => null,
    ],
    'image' => [
        'allowed_types' => ['image'],
        'max_file_size' => 5120,
        'max_selections' => 1,
    ],
    'gallery' => [
        'allowed_types' => ['image'],
        'max_file_size' => 5120,
        'max_selections' => 20,
    ],
    // Add custom block types as needed
],
```

## Opting Out of New Features

All new features can be disabled via configuration or environment variables:

### Disable Glass Effects

```php
// config/artisanpack.php
'media' => [
    'ui' => [
        'glass_effects' => [
            'enabled' => false,
        ],
    ],
],
```

Or via environment variable:

```env
MEDIA_GLASS_EFFECTS=false
```

### Disable Statistics Dashboard

```php
'media' => [
    'ui' => [
        'stats_dashboard' => [
            'enabled' => false,
        ],
    ],
],
```

Or via environment variable:

```env
MEDIA_STATS_ENABLED=false
```

### Disable Table Export

```php
'media' => [
    'ui' => [
        'table_export' => [
            'enabled' => false,
        ],
    ],
],
```

Or via environment variable:

```env
MEDIA_TABLE_EXPORT=false
```

### Disable Streaming Uploads

```php
'media' => [
    'features' => [
        'streaming_upload' => false,
    ],
],
```

Or via environment variable:

```env
MEDIA_STREAMING_UPLOAD=false
```

### Disable Recently Used Media

```php
'media' => [
    'visual_editor' => [
        'track_recently_used' => false,
    ],
],
```

## Livewire 3 vs Livewire 4 Considerations

### Upload Progress

| Feature | Livewire 4 | Livewire 3 |
|---------|-----------|------------|
| Progress Updates | Real-time via `wire:stream` | Polling-based |
| Default Behavior | Automatic streaming | Automatic polling fallback |
| Configuration | `streaming_upload` | `streaming_fallback_interval` |

The package automatically detects your Livewire version and uses the appropriate method. No code changes are required.

### Streaming Upload Fallback

On Livewire 3, upload progress uses polling instead of streaming. Adjust the polling interval if needed:

```php
'features' => [
    'streaming_fallback_interval' => 500, // ms between polls
],
```

Lower values = smoother progress, higher server load.

### Checking Livewire Version

In your components, you can check the Livewire version:

```php
use ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;

class MyComponent extends Component
{
    use StreamableUpload;

    public function someMethod(): void
    {
        if ($this->isLivewire4OrHigher()) {
            // Livewire 4+ specific code
        }
    }
}
```

## Visual Editor Integration Quick Start

The new MediaPicker component enables media selection in visual editors:

### 1. Add the MediaPicker Component

```blade
<livewire:media-picker
    context="featured-image"
    :allowed-types="['image']"
    :multi-select="false"
/>
```

### 2. Add a Trigger Button

```blade
<button @click="$dispatch('open-media-picker', { context: 'featured-image' })">
    Select Image
</button>
```

### 3. Listen for Selection Events

```javascript
Livewire.on('media-picked', (event) => {
    if (event.context === 'featured-image') {
        console.log('Selected:', event.media);
    }
});
```

Or in a Livewire component:

```php
use Livewire\Attributes\On;

#[On('media-picked')]
public function handleMediaPicked(array $media, string $context): void
{
    if ($context === 'featured-image') {
        $this->featuredImageId = $media[0]['id'];
    }
}
```

For complete documentation, see [MediaPicker Component](./visual-editor/media-picker.md).

## Troubleshooting

### Glass Effects Not Showing

**Symptoms:** Cards and modals don't have the glassmorphism effect.

**Solutions:**

1. Ensure `livewire-ui-components` v2.0+ is installed:
   ```bash
   composer show artisanpack-ui/livewire-ui-components
   ```

2. Check configuration:
   ```php
   config('artisanpack.media.ui.glass_effects.enabled') // Should be true
   ```

3. Glass effects require a background to show through. Add a gradient or image background:
   ```blade
   <div class="bg-gradient-to-br from-primary/20 to-secondary/20">
       <livewire:media-statistics :glass="true" />
   </div>
   ```

### Streaming Progress Not Working on Livewire 4

**Symptoms:** Upload progress doesn't update in real-time.

**Solutions:**

1. Verify Livewire version:
   ```bash
   composer show livewire/livewire
   ```

2. Check streaming is enabled:
   ```php
   config('artisanpack.media.features.streaming_upload') // Should be true
   ```

3. Ensure the `wire:stream` target exists in your template:
   ```blade
   <div wire:stream="uploadProgress">...</div>
   ```

### Export Buttons Missing

**Symptoms:** CSV/XLSX/PDF export buttons don't appear.

**Solutions:**

1. Check export is enabled:
   ```php
   config('artisanpack.media.ui.table_export.enabled') // Should be true
   ```

2. For XLSX export, install PhpSpreadsheet:
   ```bash
   composer require phpoffice/phpspreadsheet
   ```

3. For PDF export, install DOMPDF:
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

### Statistics Not Loading

**Symptoms:** Media statistics component shows no data or errors.

**Solutions:**

1. Check stats are enabled:
   ```php
   config('artisanpack.media.ui.stats_dashboard.enabled') // Should be true
   ```

2. Clear the statistics cache:
   ```bash
   php artisan cache:forget media_statistics_*
   ```

3. Ensure you have media items in the database.

### MediaPicker Not Opening

**Symptoms:** Clicking the trigger doesn't open the picker.

**Solutions:**

1. Verify the component is on the page:
   ```blade
   <livewire:media-picker context="my-context" />
   ```

2. Check the context matches:
   ```javascript
   // Context in event must match component
   $dispatch('open-media-picker', { context: 'my-context' })
   ```

3. Check browser console for JavaScript errors.

### Configuration Not Updating

**Symptoms:** Changed config values don't take effect.

**Solutions:**

1. Clear configuration cache:
   ```bash
   php artisan config:clear
   ```

2. If using `.env` variables, restart your server/queue workers.

3. Verify the config path is correct:
   ```php
   // Should be under 'artisanpack.media'
   config('artisanpack.media.ui.glass_effects.enabled')
   ```

## Getting Help

If you encounter issues not covered here:

1. Check the [FAQ](./reference/faq.md)
2. Review the [Troubleshooting Guide](./reference/troubleshooting.md)
3. Search existing [GitLab issues](https://gitlab.com/jacob-martella-web-design/artisanpack-ui/artisanpack-ui-media-library/-/issues)
4. Open a new issue with:
   - Package versions (`composer show artisanpack-ui/*`)
   - PHP and Laravel versions
   - Steps to reproduce
   - Error messages or screenshots

## Next Steps

- [What's New in v1.1](./home.md#new-in-v11) - Full feature overview
- [Streaming Uploads](./usage/streaming-uploads.md) - Real-time upload progress
- [Media Statistics](./dashboard/statistics.md) - KPI dashboard
- [MediaPicker Component](./visual-editor/media-picker.md) - Visual editor integration
- [Configuration Reference](./installation/configuration.md) - All configuration options
