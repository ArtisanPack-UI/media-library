---
title: Installation
---

# Installation

This guide provides detailed instructions for installing the ArtisanPack UI Media Library package in your Laravel application.

## Installation Steps

### Step 1: Install via Composer

```bash
composer require artisanpack-ui/media-library
```

The package will automatically register its service provider via Laravel's package auto-discovery.

### Step 2: Publish and Run Migrations

Publish the migration files:

```bash
php artisan vendor:publish --tag=media-migrations
```

This publishes four migration files to your `database/migrations` directory:

- `create_media_table.php` - Main media table
- `create_media_folders_table.php` - Folder hierarchy
- `create_media_tags_table.php` - Tag definitions
- `create_media_taggables_table.php` - Media-tag pivot table

Run the migrations:

```bash
php artisan migrate
```

### Step 3: Create Storage Link

Ensure your public storage is linked:

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

### Step 4: Publish Configuration (Optional)

To customize the package configuration:

```bash
php artisan vendor:publish --tag=media-config
```

This merges configuration into `config/artisanpack.php` under the `media` key.

### Step 5: Publish Views (Optional)

To customize the Blade views:

```bash
php artisan vendor:publish --tag=media-views
```

Views will be published to `resources/views/vendor/media/`.

### Step 6: Install FFmpeg (Optional)

For video thumbnail extraction, install FFmpeg:

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install ffmpeg
```

**macOS (Homebrew):**
```bash
brew install ffmpeg
```

**Verify installation:**
```bash
ffmpeg -version
```

## Verifying Installation

### Run Tests

Verify the installation by running the package tests:

```bash
php artisan test --filter=Media
```

All tests should pass.

### Check Routes

The package registers API routes. Verify they're registered:

```bash
php artisan route:list --path=api/media
```

You should see routes for media, folders, and tags.

### Test Upload

Create a simple test upload:

```php
use Illuminate\Http\UploadedFile;

$file = UploadedFile::fake()->image('test.jpg', 800, 600);

$media = apUploadMedia($file, [
    'title' => 'Test Upload',
]);

// Check if media was created
if ($media) {
    echo "Installation successful! Media ID: {$media->id}";
}
```

## Troubleshooting Installation

### Composer Install Fails

If Composer fails to install the package:

1. Ensure your `composer.json` includes the correct repository
2. Run `composer clear-cache`
3. Try `composer require artisanpack-ui/media-library --no-cache`

### Migration Fails

If migrations fail:

1. Check database connection in `.env`
2. Ensure user has CREATE TABLE permissions
3. Check for existing tables with same names
4. Review error messages for specific issues

### Storage Link Fails

If `storage:link` fails:

1. Check directory permissions
2. Manually create the symlink:
   ```bash
   ln -s ../storage/app/public public/storage
   ```
3. For Windows, run as administrator

### FFmpeg Not Found

If FFmpeg is not detected:

1. Verify installation: `which ffmpeg` (Unix) or `where ffmpeg` (Windows)
2. Add FFmpeg to system PATH
3. Restart terminal/IDE after installation
4. Video thumbnails will still work without FFmpeg (fallback to default icon)

## Uninstalling

To completely remove the package:

1. **Remove data (optional):**
   ```bash
   php artisan migrate:rollback --path=database/migrations/*media*
   ```

2. **Remove package:**
   ```bash
   composer remove artisanpack-ui/media-library
   ```

3. **Remove published files (optional):**
   ```bash
   rm -rf config/artisanpack/media.php
   rm -rf resources/views/vendor/media
   ```

## Next Steps

- Review [Requirements](./requirements.md) for system requirements
- Configure the package in [Configuration](./configuration.md)
- Learn about [Helper Functions](../usage/helper-functions.md)
- Set up [Permissions](../integration/permissions.md)
