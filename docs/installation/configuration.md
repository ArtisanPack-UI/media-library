---
title: Configuration
---

# Configuration

This guide covers all configuration options for the ArtisanPack UI Media Library package.

## Configuration File

The package configuration is merged into `config/artisanpack.php` under the `media` key.

To publish the configuration:

```bash
php artisan vendor:publish --tag=media-config
```

## Configuration Options

### Storage Settings

#### `disk`

The storage disk to use for media uploads.

```php
'disk' => env('MEDIA_DISK', 'public'),
```

**Environment variable:**
```env
MEDIA_DISK=public
```

**Common values:**
- `public` - Local public storage (default)
- `s3` - Amazon S3
- `gcs` - Google Cloud Storage
- `azure` - Azure Blob Storage

#### `max_file_size`

Maximum file size in kilobytes.

```php
'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10240),
```

**Environment variable:**
```env
MEDIA_MAX_FILE_SIZE=10240  # 10 MB
```

**Note:** Also configure your web server and `php.ini` accordingly.

#### `upload_path_format`

Format for organizing uploaded files in directories.

```php
'upload_path_format' => env('MEDIA_UPLOAD_PATH_FORMAT', '{year}/{month}'),
```

**Environment variable:**
```env
MEDIA_UPLOAD_PATH_FORMAT={year}/{month}
```

**Available placeholders:**
- `{year}` - Four-digit year (e.g., 2025)
- `{month}` - Two-digit month (e.g., 01)
- `{day}` - Two-digit day (e.g., 15)
- `{user_id}` - User ID of uploader

**Examples:**
- `{year}/{month}` → `2025/01/`
- `{year}/{month}/{day}` → `2025/01/15/`
- `users/{user_id}` → `users/123/`

### Image Processing

#### `enable_modern_formats`

Enable automatic conversion to modern image formats (WebP/AVIF).

```php
'enable_modern_formats' => env('MEDIA_ENABLE_MODERN_FORMATS', true),
```

**Environment variable:**
```env
MEDIA_ENABLE_MODERN_FORMATS=true
```

When enabled, the package creates modern format versions alongside originals.

#### `modern_format`

Which modern format to use: `webp` or `avif`.

```php
'modern_format' => env('MEDIA_MODERN_FORMAT', 'webp'),
```

**Environment variable:**
```env
MEDIA_MODERN_FORMAT=webp
```

**Options:**
- `webp` - WebP format (widely supported)
- `avif` - AVIF format (better compression, requires Imagick)

#### `image_quality`

Image quality for processed images (1-100).

```php
'image_quality' => env('MEDIA_IMAGE_QUALITY', 85),
```

**Environment variable:**
```env
MEDIA_IMAGE_QUALITY=85
```

**Guidelines:**
- `100` - Highest quality, largest file size
- `85` - Recommended balance (default)
- `60-70` - Lower quality, smaller files

#### `enable_thumbnails`

Enable automatic thumbnail generation.

```php
'enable_thumbnails' => env('MEDIA_ENABLE_THUMBNAILS', true),
```

**Environment variable:**
```env
MEDIA_ENABLE_THUMBNAILS=true
```

### Image Sizes

Define default image sizes to generate.

```php
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
```

**Options:**
- `width` - Maximum width in pixels
- `height` - Maximum height in pixels
- `crop` - Whether to crop or resize proportionally

#### Registering Custom Image Sizes

Register additional sizes at runtime:

```php
// In a service provider or plugin
apRegisterImageSize('hero-banner', 1920, 600, true);
apRegisterImageSize('product-grid', 400, 400, true);
```

Access in configuration:

```php
'custom_image_sizes' => [
    // Runtime-registered sizes appear here
],
```

### Allowed MIME Types

Define which file types are allowed for upload.

```php
'allowed_mime_types' => [
    // Images
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/avif',
    'image/svg+xml',

    // Videos
    'video/mp4',
    'video/mpeg',
    'video/quicktime',
    'video/webm',

    // Audio
    'audio/mpeg',
    'audio/wav',
    'audio/ogg',

    // Documents
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
],
```

**To add custom types:**

```php
'allowed_mime_types' => array_merge(config('artisanpack.media.allowed_mime_types'), [
    'application/zip',
    'text/plain',
]),
```

### User Model

The User model to use for relationships.

```php
'user_model' => null, // Auto-detected
```

The package automatically detects the User model from:
1. `config('artisanpack.cms-framework.user_model')`
2. `config('auth.providers.users.model')`
3. Falls back to `App\Models\User`

**To override:**

```php
'user_model' => App\Models\CustomUser::class,
```

## Environment Variables Reference

Complete list of environment variables:

```env
# Storage
MEDIA_DISK=public
MEDIA_MAX_FILE_SIZE=10240
MEDIA_UPLOAD_PATH_FORMAT={year}/{month}

# Image Processing
MEDIA_ENABLE_MODERN_FORMATS=true
MEDIA_MODERN_FORMAT=webp
MEDIA_IMAGE_QUALITY=85
MEDIA_ENABLE_THUMBNAILS=true
```

## Cloud Storage Configuration

### Amazon S3

In `config/filesystems.php`:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
],
```

In `.env`:

```env
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

### Google Cloud Storage

In `config/filesystems.php`:

```php
'gcs' => [
    'driver' => 'gcs',
    'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE'),
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
    'bucket' => env('GOOGLE_CLOUD_BUCKET'),
],
```

### Azure Blob Storage

In `config/filesystems.php`:

```php
'azure' => [
    'driver' => 'azure',
    'name' => env('AZURE_STORAGE_NAME'),
    'key' => env('AZURE_STORAGE_KEY'),
    'container' => env('AZURE_STORAGE_CONTAINER'),
],
```

## Runtime Configuration

### Override Configuration Values

You can override configuration at runtime:

```php
config([
    'artisanpack.media.max_file_size' => 20480, // 20 MB
    'artisanpack.media.image_quality' => 90,
]);
```

### CMS Module Integration

When using the Media module with Digital Shopfront CMS, settings can be configured through the admin interface at `/admin/settings/media`.

These settings override the default configuration at runtime.

## Performance Optimization

### Queue Configuration

For better performance, process images via queues:

In `config/queue.php`:

```php
'connections' => [
    'media' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'media',
        'retry_after' => 600,
    ],
],
```

Run queue worker:

```bash
php artisan queue:work --queue=media
```

### Caching

The package uses Laravel's cache for media metadata:

```php
config([
    'cache.stores.media' => [
        'driver' => 'redis',
        'connection' => 'media',
    ],
]);
```

## Next Steps

- Learn about [Helper Functions](../usage/helper-functions.md)
- Explore [Model Usage](../usage/models.md)
- Set up [Permissions](../integration/permissions.md)
- Review [Customization](../integration/customization.md) options
