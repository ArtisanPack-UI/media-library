---
title: Requirements
---

# Requirements

This page lists all the requirements for running the ArtisanPack UI Media Library package.

## Server Requirements

### PHP

- **PHP 8.2 or higher** is required
- The following PHP extensions must be enabled:
  - `fileinfo` - For MIME type detection
  - `gd` or `imagick` - For image processing
  - `mbstring` - For string manipulation
  - `zip` - For certain file operations

**Check PHP version:**
```bash
php -v
```

**Check for extensions:**
```bash
php -m | grep -i gd
php -m | grep -i imagick
php -m | grep -i fileinfo
php -m | grep -i mbstring
```

### Laravel

- **Laravel 12.0 or higher** is required
- Livewire 3.x is included as a dependency

### Database

The package supports all Laravel-supported databases:

- MySQL 5.7+
- PostgreSQL 10+
- SQLite 3.8.8+
- SQL Server 2017+

### Web Server

Any web server that supports Laravel:

- Apache 2.4+
- Nginx 1.10+
- Laravel Herd (recommended for development)

## Image Processing Requirements

### GD Library

The **GD library** is included with most PHP installations.

**Install on Ubuntu/Debian:**
```bash
sudo apt-get install php-gd
```

**Install on macOS:**
```bash
brew install php
# GD is typically included
```

### Imagick (Alternative)

**Imagick** provides better performance and more features than GD.

**Install on Ubuntu/Debian:**
```bash
sudo apt-get install php-imagick
```

**Install on macOS:**
```bash
brew install imagemagick
pecl install imagick
```

**Check WebP support:**
```bash
php -r "echo (imagetypes() & IMG_WEBP) ? 'WebP supported' : 'WebP not supported';"
```

**Check AVIF support (Imagick only):**
```bash
php -r "if (extension_loaded('imagick')) { echo in_array('AVIF', Imagick::queryFormats()) ? 'AVIF supported' : 'AVIF not supported'; } else { echo 'Imagick not installed'; }"
```

## Video Processing (Optional)

### FFmpeg

FFmpeg is optional but recommended for video thumbnail extraction.

**Install on Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install ffmpeg
```

**Install on macOS (Homebrew):**
```bash
brew install ffmpeg
```

**Install on Windows:**
1. Download from [https://ffmpeg.org/download.html](https://ffmpeg.org/download.html)
2. Extract to a directory (e.g., `C:\ffmpeg`)
3. Add to system PATH

**Verify installation:**
```bash
ffmpeg -version
```

### What if I don't install FFmpeg?

- Image uploads work normally
- Video uploads work normally
- Video thumbnails use a default video icon instead of extracted frames
- All other features remain functional

## Storage Requirements

### Disk Space

Storage requirements depend on your usage:

- **Small site (< 100 media):** 500 MB minimum
- **Medium site (100-1000 media):** 2 GB minimum
- **Large site (1000+ media):** 10 GB+ recommended

### File System

The package requires:

- Read/write permissions on `storage/app/public`
- Read/write permissions on `storage/app/media` (if using local disk)
- Symlink support for `public/storage`

**Set correct permissions:**
```bash
chmod -R 775 storage/
chmod -R 775 public/storage/
```

## Cloud Storage (Optional)

For cloud storage, install appropriate drivers:

### Amazon S3

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### Google Cloud Storage

```bash
composer require google/cloud-storage
```

### Azure Blob Storage

```bash
composer require league/flysystem-azure-blob-storage "^3.0"
```

## Development Tools

### Composer

- **Composer 2.0 or higher** is required

**Check version:**
```bash
composer --version
```

### Node.js & NPM (For Asset Building)

If you're customizing views that use frontend assets:

- **Node.js 18 or higher**
- **NPM 9 or higher**

**Check versions:**
```bash
node --version
npm --version
```

## Production Requirements

### PHP Configuration

For production, adjust `php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M
max_input_time = 300
```

### Web Server Configuration

**Nginx example for large uploads:**
```nginx
client_max_body_size 20M;
client_body_timeout 300s;
```

**Apache example for large uploads:**
```apache
LimitRequestBody 20971520
Timeout 300
```

### Queue Worker (Recommended)

For production, use queue workers for image processing:

```bash
php artisan queue:work --queue=media
```

Or use Supervisor to keep it running:

```ini
[program:media-worker]
command=php /path/to/artisan queue:work --queue=media --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/media-worker.log
```

## Checking Your Environment

Run this script to check your environment:

```php
<?php

echo "PHP Version: " . phpversion() . "\n";
echo "Laravel Version: " . app()->version() . "\n";

echo "\nPHP Extensions:\n";
echo "- GD: " . (extension_loaded('gd') ? '✓' : '✗') . "\n";
echo "- Imagick: " . (extension_loaded('imagick') ? '✓' : '✗') . "\n";
echo "- Fileinfo: " . (extension_loaded('fileinfo') ? '✓' : '✗') . "\n";
echo "- Mbstring: " . (extension_loaded('mbstring') ? '✓' : '✗') . "\n";

echo "\nImage Format Support:\n";
echo "- JPEG: " . ((imagetypes() & IMG_JPG) ? '✓' : '✗') . "\n";
echo "- PNG: " . ((imagetypes() & IMG_PNG) ? '✓' : '✗') . "\n";
echo "- GIF: " . ((imagetypes() & IMG_GIF) ? '✓' : '✗') . "\n";
echo "- WebP: " . ((imagetypes() & IMG_WEBP) ? '✓' : '✗') . "\n";

echo "\nFFmpeg: ";
exec('ffmpeg -version 2>&1', $output, $return);
echo ($return === 0 ? '✓' : '✗') . "\n";

echo "\nStorage Writable:\n";
echo "- storage/app: " . (is_writable(storage_path('app')) ? '✓' : '✗') . "\n";
echo "- storage/app/public: " . (is_writable(storage_path('app/public')) ? '✓' : '✗') . "\n";
echo "- public/storage exists: " . (file_exists(public_path('storage')) ? '✓' : '✗') . "\n";
```

Save as `check-media-requirements.php` and run:

```bash
php check-media-requirements.php
```

## Next Steps

- Proceed to [Installation](Installation)
- Review [Configuration](Configuration) options
- Check [Troubleshooting](Reference-Troubleshooting) if you encounter issues
