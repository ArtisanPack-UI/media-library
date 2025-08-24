# Configuration Guide

This guide covers all configuration options available for the ArtisanPack UI Media Library package.

## Storage Configuration

Configure your storage settings in the published `config/media-library.php` file:

```php
return [
    // Storage disk for media files
    'disk' => env('MEDIA_DISK', 'public'),
    
    // Base directory for media storage
    'directory' => env('MEDIA_DIRECTORY', 'media'),
    
    // Organize files by date (year/month structure)
    'organize_by_date' => env('MEDIA_ORGANIZE_BY_DATE', true),
    
    // Maximum file size (10MB default)
    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10485760),
    
    // Allowed MIME types
    'allowed_mime_types' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain',
        // Add more as needed
    ],
];
```

## Environment Variables

Add these variables to your `.env` file:

```env
# Media Library Configuration
MEDIA_DISK=public
MEDIA_DIRECTORY=media
MEDIA_ORGANIZE_BY_DATE=true
MEDIA_MAX_FILE_SIZE=10485760
```

## Configuration Options Explained

### Storage Disk
- `MEDIA_DISK`: Specifies which Laravel filesystem disk to use for storing media files
- Default: `public` (storage/app/public)
- Options: `local`, `public`, `s3`, or any custom disk you've configured

### Directory Structure
- `MEDIA_DIRECTORY`: Base directory within the storage disk
- `MEDIA_ORGANIZE_BY_DATE`: When enabled, creates year/month subdirectories (e.g., `media/2024/08/`)

### File Restrictions
- `MEDIA_MAX_FILE_SIZE`: Maximum allowed file size in bytes
- `allowed_mime_types`: Array of permitted MIME types for uploads

## Storage Disk Configuration

### Local/Public Storage
```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

### Amazon S3 Storage
```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
],
```

For S3 configuration, add these environment variables:
```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
MEDIA_DISK=s3
```

## Security Configuration

### File Upload Security
The package includes several security measures:

1. **MIME Type Validation**: Only allowed file types can be uploaded
2. **File Size Limits**: Configurable maximum file size
3. **File Extension Validation**: Validates file extensions match MIME types
4. **User Authentication**: All API endpoints require authentication

### Recommended Security Settings

```php
// config/media-library.php
'allowed_mime_types' => [
    // Images
    'image/jpeg',
    'image/png', 
    'image/gif',
    'image/webp',
    'image/svg+xml', // Only if you trust the source
    
    // Documents
    'application/pdf',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    
    // Archives (use with caution)
    // 'application/zip',
    // 'application/x-rar-compressed',
],

// Conservative file size limit (5MB)
'max_file_size' => 5242880,
```

## Performance Configuration

### Database Optimization
Add these indexes to improve query performance:

```sql
CREATE INDEX idx_media_user_id ON media(user_id);
CREATE INDEX idx_media_created_at ON media(created_at);
CREATE INDEX idx_media_mime_type ON media(mime_type);
```

### Caching
Consider implementing caching for frequently accessed media metadata:

```php
// In your service provider
use Illuminate\Support\Facades\Cache;

$media = Cache::remember("media.{$id}", 3600, function () use ($id) {
    return Media::with(['mediaCategories', 'mediaTags'])->find($id);
});
```

## Testing Configuration

For testing environments, you may want to use a separate storage disk:

```php
// config/filesystems.php
'testing' => [
    'driver' => 'local',
    'root' => storage_path('app/testing'),
    'visibility' => 'public',
    'throw' => false,
],
```

```env
# .env.testing
MEDIA_DISK=testing
```

## Next Steps

After configuring the package:

1. Review [usage examples](usage.md) to start implementing media functionality
2. Check the [API documentation](api.md) for endpoint details
3. See [performance guidelines](performance.md) for optimization tips