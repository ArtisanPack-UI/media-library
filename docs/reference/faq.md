---
title: Frequently Asked Questions
---

# Frequently Asked Questions

Common questions about the Media Library package.

## General Questions

### What image formats are supported?

The package supports:

- **Images:** JPEG, PNG, GIF, WebP, AVIF, SVG
- **Videos:** MP4, MPEG, QuickTime, WebM
- **Audio:** MP3, WAV, OGG
- **Documents:** PDF, Word, Excel

See [Configuration](../installation/configuration.md) to customize allowed types.

### Can I use cloud storage?

Yes! The package supports any Laravel filesystem disk:

- Amazon S3
- Google Cloud Storage
- Azure Blob Storage
- DigitalOcean Spaces
- Any S3-compatible storage

See [Configuration - Cloud Storage](../installation/configuration.md#cloud-storage-configuration).

### Does it work with Laravel 11?

The package requires Laravel 12.0 or higher. For Laravel 11, you would need to use an earlier version of the package or upgrade to Laravel 12.

### Is FFmpeg required?

No, FFmpeg is optional. It's only needed for:

- Video thumbnail extraction
- Audio waveform generation

Without FFmpeg, videos use a default thumbnail icon.

## Installation Questions

### Do I need to publish migrations?

Yes, run:

```bash
php artisan vendor:publish --tag=media-migrations
php artisan migrate
```

### Can I customize the database tables?

Yes, publish and modify the migrations before running them. Note that changing table/column names may require updating model configurations.

### How do I update the package?

```bash
composer update artisanpack-ui/media-library
php artisan migrate
php artisan cache:clear
```

## Usage Questions

### How do I upload media programmatically?

Use the helper function:

```php
$media = apUploadMedia($file, [
    'title' => 'My Image',
    'folder_id' => 1,
]);
```

See [Helper Functions](../usage/helper-functions.md) for more examples.

### Can I have multiple media modals on one page?

Yes! Use the `context` parameter to distinguish them:

```blade
<livewire:media::media-modal context="logo" wire:key="logo-modal" />
<livewire:media::media-modal context="banner" wire:key="banner-modal" />
```

See [Livewire Components](../usage/livewire-components.md#multiple-modals-on-same-page).

### How do I restrict media to certain folders?

You can implement folder-based permissions. See [Permissions - Folder Permissions](../integration/permissions.md#folder-permissions).

### Can users only see their own media?

Yes, implement ownership-based permissions. See [Permissions - Ownership-Based Permissions](../integration/permissions.md#ownership-based-permissions).

## Configuration Questions

### How do I change the maximum file size?

In `.env`:

```env
MEDIA_MAX_FILE_SIZE=20480  # 20 MB
```

Also update `php.ini` and web server config to match.

### How do I add custom image sizes?

In a service provider:

```php
apRegisterImageSize('custom-size', 400, 300, true);
```

See [Customization - Custom Image Sizes](../integration/customization.md#custom-image-sizes).

### Can I disable thumbnail generation?

Yes, in `.env`:

```env
MEDIA_ENABLE_THUMBNAILS=false
```

### How do I change the upload directory structure?

In `.env`:

```env
MEDIA_UPLOAD_PATH_FORMAT={year}/{month}/{day}
# or
MEDIA_UPLOAD_PATH_FORMAT=users/{user_id}
```

## Performance Questions

### Should I use queues?

Yes! For production, use queues to process images in the background:

```bash
php artisan queue:work --queue=media
```

### How do I optimize for large uploads?

1. Increase PHP limits
2. Use queue processing
3. Enable OpCache
4. Use Redis for cache
5. Consider CDN for delivery

See [Configuration - Performance Optimization](../installation/configuration.md#performance-optimization).

### Can I lazy-load images?

Yes! The `displayImage()` method supports lazy loading:

```php
echo $media->displayImage('large', ['loading' => 'lazy']);
```

## Integration Questions

### How do I integrate with the CMS?

The package includes a CMS module. See [CMS Module Integration](../integration/cms-module.md).

### Can I use with Livewire components?

Yes! The package provides Livewire components. See [Livewire Components](../usage/livewire-components.md).

### Does it work with InertiaJS?

Yes, through the API endpoints. See [API Endpoints](../api/endpoints.md).

### Can I use with Filament?

Yes, you can create custom Filament resources that use the Media models. You may need to create custom form fields for the media picker.

## API Questions

### How do I authenticate API requests?

Use Laravel Sanctum tokens in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN
```

See [API Authentication](../api/authentication.md).

### Are there rate limits?

Yes, 60 requests per minute for authenticated users. See [API Overview - Rate Limiting](../api/api.md#rate-limiting).

### Can I upload via API?

Yes! See [API Endpoints - Upload Media](../api/endpoints.md#upload-media).

## Security Questions

### How are files validated?

Files are validated by:

- MIME type checking
- File extension validation
- Size limits
- File integrity checks

### Are uploads sanitized?

Yes, file names are sanitized and stored files use hashed names to prevent directory traversal attacks.

### How do permissions work?

The package uses capability-based permissions. See [Permissions & Access Control](../integration/permissions.md).

### Can I scan for malware?

Yes, integrate a malware scanning service in the upload process. See [Customization - Custom Upload Processing](../integration/customization.md#custom-upload-processing).

## Troubleshooting Questions

### Uploads fail silently, what do I check?

1. Check PHP error log
2. Check Laravel log: `storage/logs/laravel.log`
3. Verify upload limits in `php.ini`
4. Check storage directory permissions

See [Troubleshooting](./troubleshooting.md) for detailed solutions.

### Images don't display, what's wrong?

1. Check storage link: `ls -la public/storage`
2. Verify file exists
3. Check file permissions (should be 644)
4. Clear cache: `php artisan cache:clear`

See [Troubleshooting - URL & Display Issues](./troubleshooting.md#url--display-issues).

### How do I enable debug mode?

In `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Remember to disable in production!

## Migration Questions

### Can I migrate from another media library?

Yes, but you'll need to write custom migration scripts to map your existing data to the Media Library models.

### How do I export my media data?

Query the database directly or use Laravel's export features:

```php
Media::with(['folder', 'tags'])->get()->toJson();
```

### Can I backup media files?

Yes, backup the storage disk and database. For S3, use S3 versioning and lifecycle policies.

## Support Questions

### Where can I get help?

1. Check this documentation
2. Review [Troubleshooting Guide](./troubleshooting.md)
3. Search GitLab issues
4. Contact support: [support@artisanpackui.dev](mailto:support@artisanpackui.dev)

### How do I report a bug?

Create an issue on GitLab with:

- Laravel version
- PHP version
- Package version
- Steps to reproduce
- Error messages/logs

### Can I request features?

Yes! Submit feature requests on GitLab or contact us directly.

## Next Steps

- Review [Troubleshooting](./troubleshooting.md) for solutions
- See [Getting Started](../getting-started.md) for quick setup
- Explore [Helper Functions](../usage/helper-functions.md) for common patterns
