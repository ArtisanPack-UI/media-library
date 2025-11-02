---
title: Reference
---

# Reference

This section provides troubleshooting guides and answers to frequently asked questions about the Media Library package.

## Reference Guides

### [Troubleshooting](./reference/troubleshooting.md)

Common issues and their solutions:

**Installation Issues:**
- Composer install fails
- Migration fails
- Storage link fails

**Upload Issues:**
- File upload fails
- Permission denied errors
- Invalid file type errors

**Image Processing Issues:**
- Thumbnails not generating
- WebP/AVIF conversion fails
- Low quality images

**Video Issues:**
- Video thumbnails not generating
- FFmpeg not found

**URL & Display Issues:**
- Media URLs return 404
- Images don't display

**Performance Issues:**
- Slow upload processing
- High memory usage

**Database Issues:**
- Foreign key constraint fails
- Duplicate slug errors

**API Issues:**
- 401 Unauthorized
- 422 Validation errors

**Testing Issues:**
- Tests fail with database errors

### [FAQ](./reference/faq.md)

Frequently asked questions organized by category:

**General Questions:**
- Supported formats
- Cloud storage compatibility
- Laravel version requirements
- FFmpeg necessity

**Installation Questions:**
- Publishing migrations
- Customizing database tables
- Updating the package

**Usage Questions:**
- Programmatic uploads
- Multiple media modals
- Folder restrictions
- User-specific media

**Configuration Questions:**
- Changing file size limits
- Adding custom image sizes
- Disabling thumbnails
- Upload directory structure

**Performance Questions:**
- Queue usage
- Optimizing large uploads
- Lazy loading

**Integration Questions:**
- CMS integration
- Livewire compatibility
- InertiaJS usage
- Filament integration

**API Questions:**
- Authentication
- Rate limits
- API uploads

**Security Questions:**
- File validation
- Upload sanitization
- Permissions
- Malware scanning

**Troubleshooting Questions:**
- Silent upload failures
- Display issues
- Debug mode

**Migration Questions:**
- Migrating from other libraries
- Exporting data
- Backups

**Support Questions:**
- Getting help
- Reporting bugs
- Feature requests

## Quick Troubleshooting

### Upload Fails

1. Check PHP limits in `php.ini`
2. Verify storage permissions: `chmod -R 775 storage/`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Enable debug mode temporarily

### Images Don't Display

1. Verify storage link: `ls -la public/storage`
2. Check file exists in `storage/app/public/media/`
3. Clear cache: `php artisan cache:clear`
4. Check file permissions (should be 644)

### FFmpeg Not Found

1. Install FFmpeg: `brew install ffmpeg` (macOS) or `apt-get install ffmpeg` (Ubuntu)
2. Verify: `ffmpeg -version`
3. Restart web server

## Getting Help

If you can't find a solution:

1. Check [Troubleshooting](./reference/troubleshooting.md)
2. Review [FAQ](./reference/faq.md)
3. Search GitHub issues
4. Contact support: support@artisanpack.com

## Next Steps

- Review detailed [Troubleshooting Guide](./reference/troubleshooting.md)
- Browse [FAQ](./reference/faq.md) for common questions
- Check [Installation](./installation.md) if having setup issues
- See [Configuration](./installation/configuration.md) for optimization
