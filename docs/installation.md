---
title: Installation
---

# Installation

This section covers everything you need to install and set up the ArtisanPack UI Media Library package in your Laravel application.

## Installation Guides

### [Installation Guide](Installation-Installation)

Complete step-by-step installation instructions including:
- Installing via Composer
- Publishing and running migrations
- Creating storage links
- Publishing configuration and views
- Installing optional dependencies (FFmpeg)
- Verifying the installation
- Troubleshooting installation issues

### [Requirements](Installation-Requirements)

Detailed system requirements including:
- PHP version and extensions
- Laravel version
- Database requirements
- Image processing libraries (GD/Imagick)
- Video processing (FFmpeg)
- Storage requirements
- Cloud storage drivers
- Production requirements
- Environment check script

### [Configuration](Installation-Configuration)

Complete configuration reference covering:
- Storage settings (disk, file size, upload paths)
- Image processing options
- Modern format conversion (WebP/AVIF)
- Image sizes and thumbnails
- Allowed MIME types
- User model configuration
- Environment variables
- Cloud storage setup (S3, GCS, Azure)
- Performance optimization
- Queue configuration

## Quick Installation

For a quick start:

```bash
# Install package
composer require artisanpack-ui/media-library

# Run migrations
php artisan vendor:publish --tag=media-migrations
php artisan migrate

# Create storage link
php artisan storage:link
```

## Next Steps

After installation:
- Review [Configuration](Installation-Configuration) to customize settings
- Learn about [Helper Functions](Usage-Helper-Functions) for basic usage
- Set up [Permissions](Integration-Permissions) for your roles
- Check [Troubleshooting](Reference-Troubleshooting) if you encounter issues
