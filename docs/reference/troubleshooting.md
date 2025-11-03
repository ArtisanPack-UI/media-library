---
title: Troubleshooting
---

# Troubleshooting

Common issues and solutions for the Media Library package.

## Installation Issues

### Composer Install Fails

**Problem:** `composer require artisanpack-ui/media-library` fails

**Solutions:**
1. Clear Composer cache:
   ```bash
   composer clear-cache
   composer require artisanpack-ui/media-library --no-cache
   ```

2. Check repository configuration in `composer.json`

3. Verify PHP version:
   ```bash
   php -v  # Should be 8.2 or higher
   ```

### Migration Fails

**Problem:** `php artisan migrate` fails with database errors

**Solutions:**
1. Check database connection in `.env`
2. Ensure database user has CREATE TABLE permissions
3. Check for existing tables with same names:
   ```bash
   php artisan db:show
   ```
4. Review error messages for specific issues

### Storage Link Fails

**Problem:** `php artisan storage:link` fails or doesn't work

**Solutions:**
1. Check directory permissions:
   ```bash
   chmod -R 775 storage/
   chmod -R 775 public/
   ```

2. Manually create symlink:
   ```bash
   ln -s ../storage/app/public public/storage
   ```

3. On Windows, run as administrator:
   ```cmd
   mklink /D public\storage ..\storage\app\public
   ```

## Upload Issues

### File Upload Fails

**Problem:** Files fail to upload with no error message

**Solutions:**
1. Check PHP upload limits in `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 25M
   max_execution_time = 300
   memory_limit = 256M
   ```

2. Check web server limits:
   
   **Nginx:**
   ```nginx
   client_max_body_size 20M;
   ```
   
   **Apache:**
   ```apache
   LimitRequestBody 20971520
   ```

3. Restart PHP-FPM/web server after changes

### "Permission Denied" Error

**Problem:** Upload fails with permission error

**Solutions:**
1. Check storage directory permissions:
   ```bash
   chmod -R 775 storage/app/public
   chown -R www-data:www-data storage/
   ```

2. Check parent directory ownership

3. Verify storage disk configuration in `config/filesystems.php`

### Invalid File Type Error

**Problem:** File rejected as invalid type

**Solutions:**
1. Check allowed MIME types in configuration:
   ```php
   config('artisanpack.media.allowed_mime_types')
   ```

2. Verify file extension matches MIME type

3. Check for corrupted files

## Image Processing Issues

### Thumbnails Not Generating

**Problem:** Images upload but thumbnails don't generate

**Solutions:**
1. Check if GD or Imagick is installed:
   ```bash
   php -m | grep -i gd
   php -m | grep -i imagick
   ```

2. Install missing extension:
   ```bash
   # GD
   sudo apt-get install php-gd
   
   # Imagick
   sudo apt-get install php-imagick
   ```

3. Restart PHP-FPM:
   ```bash
   sudo service php8.2-fpm restart
   ```

4. Check error logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### WebP/AVIF Conversion Fails

**Problem:** Modern format conversion doesn't work

**Solutions:**
1. Check WebP support:
   ```bash
   php -r "echo (imagetypes() & IMG_WEBP) ? 'Supported' : 'Not supported';"
   ```

2. For AVIF, ensure Imagick is installed and updated:
   ```bash
   php -r "if (extension_loaded('imagick')) { echo in_array('AVIF', Imagick::queryFormats()) ? 'Supported' : 'Not supported'; }"
   ```

3. Update Imagick:
   ```bash
   sudo pecl upgrade imagick
   ```

4. Disable modern formats if not supported:
   ```env
   MEDIA_ENABLE_MODERN_FORMATS=false
   ```

### Low Quality Images

**Problem:** Processed images are low quality

**Solutions:**
1. Increase quality setting in `.env`:
   ```env
   MEDIA_IMAGE_QUALITY=95
   ```

2. Check intervention/image driver:
   ```php
   config('image.driver') // should be 'imagick' for better quality
   ```

## Video Issues

### Video Thumbnails Not Generating

**Problem:** Videos upload but no thumbnails

**Solutions:**
1. Check if FFmpeg is installed:
   ```bash
   ffmpeg -version
   ```

2. Install FFmpeg:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install ffmpeg
   
   # macOS
   brew install ffmpeg
   ```

3. Ensure FFmpeg is in system PATH:
   ```bash
   which ffmpeg
   ```

4. Check logs for FFmpeg errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## URL & Display Issues

### Media URLs Return 404

**Problem:** Media files return 404 errors

**Solutions:**
1. Verify storage link exists:
   ```bash
   ls -la public/storage
   ```

2. Check file actually exists:
   ```bash
   ls storage/app/public/media/
   ```

3. Verify disk configuration:
   ```php
   config('artisanpack.media.disk')
   config('filesystems.disks.public')
   ```

4. Clear Laravel cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Images Don't Display

**Problem:** `<img>` tags show broken image icon

**Solutions:**
1. Check browser console for errors
2. Verify URL is correct:
   ```php
   dd($media->url());
   ```
3. Check file permissions (should be 644)
4. Verify Content-Type header is correct

## Performance Issues

### Slow Upload Processing

**Problem:** Uploads take too long to process

**Solutions:**
1. Use queue for image processing:
   ```bash
   php artisan queue:work --queue=media
   ```

2. Increase PHP limits:
   ```ini
   max_execution_time = 600
   memory_limit = 512M
   ```

3. Disable unnecessary image sizes

4. Reduce image quality setting

### High Memory Usage

**Problem:** PHP runs out of memory during processing

**Solutions:**
1. Increase memory limit:
   ```ini
   memory_limit = 512M
   ```

2. Process images in background queue

3. Reduce maximum image dimensions in configuration

## Database Issues

### Foreign Key Constraint Fails

**Problem:** Cannot delete media due to foreign key constraint

**Solutions:**
1. Check for references in other tables:
   ```sql
   SELECT * FROM posts WHERE featured_image_id = 123;
   ```

2. Remove references before deleting

3. Use soft deletes (enabled by default)

### Duplicate Slug Error

**Problem:** Cannot create folder/tag due to duplicate slug

**Solutions:**
1. The system auto-generates unique slugs
2. If error persists, manually specify a unique slug:
   ```php
   MediaFolder::create([
       'name' => 'Products',
       'slug' => 'products-2025',
   ]);
   ```

## API Issues

### 401 Unauthorized

**Problem:** API requests return 401 error

**Solutions:**
1. Verify token is included in header:
   ```bash
   Authorization: Bearer YOUR_TOKEN
   ```

2. Check token hasn't expired

3. Verify user has required permissions

### 422 Validation Error

**Problem:** API upload returns validation error

**Solutions:**
1. Check Content-Type header:
   ```
   Content-Type: multipart/form-data
   ```

2. Verify all required fields are included

3. Check file size limits

## Testing Issues

### Tests Fail with Database Errors

**Problem:** Tests fail with "table not found" errors

**Solutions:**
1. Run migrations in test environment:
   ```bash
   php artisan migrate --env=testing
   ```

2. Use RefreshDatabase trait in tests:
   ```php
   use Illuminate\Foundation\Testing\RefreshDatabase;
   
   class MediaTest extends TestCase
   {
       use RefreshDatabase;
   }
   ```

## Getting Help

If these solutions don't resolve your issue:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode temporarily: `APP_DEBUG=true`
3. Search existing issues on GitHub
4. Contact support: support@artisanpack.com

## Next Steps

- Review [FAQ](./faq.md) for common questions
- Check [Installation Guide](../installation/installation.md)
- See [Configuration](../installation/configuration.md) options
