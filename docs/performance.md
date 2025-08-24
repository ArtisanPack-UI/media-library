# Performance & Troubleshooting Guide

This guide covers performance optimization strategies and troubleshooting common issues with the ArtisanPack UI Media Library package.

## Performance Considerations

### Database Optimization

#### Indexing Strategy

Add appropriate indexes to improve query performance:

```sql
-- Core media queries
CREATE INDEX idx_media_user_id ON media(user_id);
CREATE INDEX idx_media_created_at ON media(created_at);
CREATE INDEX idx_media_mime_type ON media(mime_type);
CREATE INDEX idx_media_file_size ON media(file_size);

-- Category and tag relationships
CREATE INDEX idx_media_categories_media_id ON media_categories(media_id);
CREATE INDEX idx_media_categories_category_id ON media_categories(media_category_id);
CREATE INDEX idx_media_tags_media_id ON media_tags(media_id);
CREATE INDEX idx_media_tags_tag_id ON media_tags(media_tag_id);

-- Search optimization
CREATE INDEX idx_media_alt_text ON media(alt_text);
CREATE INDEX idx_media_caption ON media(caption);
CREATE FULLTEXT INDEX idx_media_search ON media(alt_text, caption);
```

#### Query Optimization

Use eager loading to prevent N+1 queries:

```php
// Good: Eager load relationships
$media = Media::with(['mediaCategories', 'mediaTags', 'user'])
    ->where('user_id', auth()->id())
    ->paginate(15);

// Bad: N+1 queries
$media = Media::where('user_id', auth()->id())->paginate(15);
foreach ($media as $item) {
    echo $item->mediaCategories->count(); // N+1 query
}
```

#### Pagination Best Practices

```php
// Use cursor-based pagination for large datasets
$media = Media::orderBy('id')
    ->cursorPaginate(20);

// Use specific columns to reduce memory usage
$media = Media::select(['id', 'filename', 'file_path', 'mime_type'])
    ->paginate(15);

// Cache paginated results for frequently accessed data
$cacheKey = "media_page_{$page}_user_{$userId}";
$media = Cache::remember($cacheKey, 300, function () use ($userId) {
    return Media::where('user_id', $userId)->paginate(15);
});
```

#### Query Scopes for Performance

```php
// In Media model
public function scopeImages($query)
{
    return $query->where('mime_type', 'LIKE', 'image/%');
}

public function scopeRecentFirst($query)
{
    return $query->orderBy('created_at', 'desc');
}

public function scopeWithBasicRelations($query)
{
    return $query->with(['user:id,name', 'mediaCategories:id,name,slug']);
}

// Usage
$recentImages = Media::images()
    ->recentFirst()
    ->withBasicRelations()
    ->limit(10)
    ->get();
```

### File Storage Optimization

#### Storage Configuration

Configure appropriate storage drivers for your needs:

```php
// config/filesystems.php

// Local development
'local' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],

// Production with CDN
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'), // CDN URL
    'visibility' => 'public',
],
```

#### File Organization

Organize files efficiently to improve performance:

```php
// config/media-library.php
return [
    // Organize by date to distribute files evenly
    'organize_by_date' => true,
    
    // Use shorter directory structure for better performance
    'date_format' => 'Y/m', // Instead of Y/m/d
    
    // Limit directory size (move to new directory after threshold)
    'max_files_per_directory' => 1000,
];
```

#### CDN Integration

Implement CDN for static file serving:

```php
// In your media model or service
public function getCdnUrlAttribute()
{
    if (config('media-library.use_cdn')) {
        return config('media-library.cdn_url') . '/' . $this->file_path;
    }
    
    return Storage::url($this->file_path);
}
```

### Memory Management

#### Chunked Processing

Process large datasets in chunks to prevent memory exhaustion:

```php
// Bulk operations
Media::chunk(1000, function ($mediaItems) {
    foreach ($mediaItems as $media) {
        // Process each media item
        $this->processMedia($media);
    }
});

// Large file uploads with streaming
public function handleLargeUpload(Request $request)
{
    $stream = $request->getContent(true); // Get as stream
    
    $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
    $handle = fopen($tempPath, 'w');
    
    while (!feof($stream)) {
        fwrite($handle, fread($stream, 8192)); // 8KB chunks
    }
    
    fclose($handle);
    fclose($stream);
    
    // Process the temporary file
    $this->processFile($tempPath);
}
```

#### Memory Monitoring

Monitor memory usage in development:

```php
// Add to your upload process
public function upload($file, $altText, $caption = null)
{
    $memoryBefore = memory_get_usage(true);
    
    // Upload process
    $media = $this->processUpload($file, $altText, $caption);
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    if (app()->environment('local')) {
        Log::debug("Upload memory usage: " . number_format($memoryUsed / 1024 / 1024, 2) . " MB");
    }
    
    return $media;
}
```

#### Garbage Collection

Optimize garbage collection for file operations:

```php
// In long-running processes
public function processBatch($files)
{
    foreach ($files as $index => $file) {
        $this->processFile($file);
        
        // Force garbage collection every 100 files
        if ($index % 100 === 0) {
            gc_collect_cycles();
        }
    }
}
```

### Caching Strategies

#### Query Result Caching

Cache frequently accessed data:

```php
// Cache media counts
public function getUserMediaCount($userId)
{
    return Cache::tags(['media', "user_{$userId}"])
        ->remember("media_count_{$userId}", 3600, function () use ($userId) {
            return Media::where('user_id', $userId)->count();
        });
}

// Cache category relationships
public function getMediaCategories($mediaId)
{
    return Cache::tags(['media', 'categories'])
        ->remember("media_categories_{$mediaId}", 1800, function () use ($mediaId) {
            return Media::find($mediaId)->mediaCategories;
        });
}

// Invalidate cache when media is updated
class Media extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($media) {
            Cache::tags(['media', "user_{$media->user_id}"])->flush();
        });
        
        static::deleted(function ($media) {
            Cache::tags(['media', "user_{$media->user_id}"])->flush();
        });
    }
}
```

#### File Metadata Caching

Cache file information to avoid repeated filesystem calls:

```php
public function getFileInfo($filePath)
{
    $cacheKey = 'file_info_' . md5($filePath);
    
    return Cache::remember($cacheKey, 3600, function () use ($filePath) {
        if (!Storage::exists($filePath)) {
            return null;
        }
        
        return [
            'size' => Storage::size($filePath),
            'last_modified' => Storage::lastModified($filePath),
            'exists' => true,
        ];
    });
}
```

## Troubleshooting

### Common Issues

#### "Media upload failed" Error

**Cause**: Usually filesystem permissions or configuration issues.

**Solutions**:

1. **Check storage disk permissions**:
   ```bash
   chmod -R 755 storage/app/public
   chown -R www-data:www-data storage/app/public
   ```

2. **Create storage link**:
   ```bash
   php artisan storage:link
   ```

3. **Verify storage configuration**:
   ```php
   // Check in tinker
   php artisan tinker
   >>> Storage::disk('public')->put('test.txt', 'test');
   >>> Storage::disk('public')->exists('test.txt');
   ```

4. **Check disk space**:
   ```bash
   df -h
   ```

#### "Unauthorized" Responses

**Cause**: Authentication or policy issues.

**Solutions**:

1. **Ensure Laravel Sanctum is configured**:
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

2. **Include bearer token in API requests**:
   ```javascript
   fetch('/api/media/items', {
       headers: {
           'Authorization': `Bearer ${token}`,
           'Accept': 'application/json'
       }
   });
   ```

3. **Check user permissions and policies**:
   ```php
   // In MediaPolicy
   public function create(User $user)
   {
       return true; // Adjust based on your requirements
   }
   ```

#### File Size Limit Exceeded

**Cause**: PHP or package file size limits.

**Solutions**:

1. **Update PHP settings**:
   ```ini
   # In php.ini
   upload_max_filesize = 20M
   post_max_size = 20M
   memory_limit = 256M
   max_execution_time = 300
   ```

2. **Adjust package configuration**:
   ```php
   // config/media-library.php
   'max_file_size' => 20971520, // 20MB
   ```

3. **Check web server limits**:
   ```nginx
   # Nginx
   client_max_body_size 20M;
   ```
   
   ```apache
   # Apache .htaccess
   php_value upload_max_filesize 20M
   php_value post_max_size 20M
   ```

#### Database Connection Issues

**Cause**: Missing migrations or database configuration.

**Solutions**:

1. **Run migrations**:
   ```bash
   php artisan migrate:status
   php artisan migrate
   ```

2. **Check database configuration**:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **Verify database exists**:
   ```sql
   SHOW DATABASES;
   USE your_database_name;
   SHOW TABLES;
   ```

### Performance Issues

#### Slow Query Performance

**Symptoms**: Long response times, high database CPU usage.

**Solutions**:

1. **Add database indexes**:
   ```sql
   -- Check slow queries
   SHOW PROCESSLIST;
   
   -- Add missing indexes
   CREATE INDEX idx_media_user_created ON media(user_id, created_at);
   CREATE INDEX idx_media_search ON media(alt_text, caption);
   ```

2. **Use eager loading**:
   ```php
   // Instead of lazy loading
   $media = Media::with(['mediaCategories', 'mediaTags', 'user'])->get();
   ```

3. **Implement query caching**:
   ```php
   $popularMedia = Cache::remember('popular_media', 3600, function () {
       return Media::withCount(['views', 'downloads'])
           ->orderByDesc('views_count')
           ->limit(10)
           ->get();
   });
   ```

4. **Optimize database configuration**:
   ```sql
   -- MySQL optimization
   SET innodb_buffer_pool_size = 1G;
   SET query_cache_size = 256M;
   SET query_cache_type = 1;
   ```

#### Memory Usage Issues

**Symptoms**: "Fatal error: Allowed memory size exhausted"

**Solutions**:

1. **Increase PHP memory limit**:
   ```ini
   memory_limit = 512M
   ```

2. **Use chunked processing**:
   ```php
   // Process in batches
   Media::chunk(100, function ($media) {
       foreach ($media as $item) {
           $this->processItem($item);
       }
   });
   ```

3. **Implement pagination**:
   ```php
   // Don't load all records at once
   $media = Media::paginate(50);
   ```

4. **Clean up unused variables**:
   ```php
   foreach ($largeDataset as $item) {
       $result = $this->process($item);
       // Process result
       unset($result); // Free memory
   }
   ```

#### File Upload Timeouts

**Symptoms**: Upload requests timing out for large files.

**Solutions**:

1. **Increase PHP execution time**:
   ```ini
   max_execution_time = 300
   max_input_time = 300
   ```

2. **Implement chunked uploads**:
   ```javascript
   // Frontend: Upload in chunks
   const chunkSize = 1024 * 1024; // 1MB chunks
   for (let i = 0; i < file.size; i += chunkSize) {
       const chunk = file.slice(i, i + chunkSize);
       await uploadChunk(chunk, i / chunkSize);
   }
   ```

3. **Use async processing**:
   ```php
   // Queue large file processing
   ProcessLargeFile::dispatch($filePath);
   ```

### Debug Mode

#### Enable Detailed Logging

```php
// config/logging.php
'channels' => [
    'media' => [
        'driver' => 'daily',
        'path' => storage_path('logs/media.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

```php
// In your MediaManager
use Illuminate\Support\Facades\Log;

public function upload($file, $altText, $caption = null)
{
    Log::channel('media')->info('Upload started', [
        'filename' => $file->getClientOriginalName(),
        'size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
    ]);
    
    try {
        $media = $this->processUpload($file, $altText, $caption);
        
        Log::channel('media')->info('Upload completed', [
            'media_id' => $media->id,
            'file_path' => $media->file_path,
        ]);
        
        return $media;
    } catch (\Exception $e) {
        Log::channel('media')->error('Upload failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        throw $e;
    }
}
```

#### Monitor Application Performance

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Monitor system resources
top -p $(pgrep php)

# Check database performance
mysql -e "SHOW PROCESSLIST;"

# Monitor storage usage
du -sh storage/app/public/media/
```

### Database Maintenance

#### Regular Optimization

```sql
-- Analyze tables for better query plans
ANALYZE TABLE media, media_categories, media_tags;

-- Optimize tables to reclaim space
OPTIMIZE TABLE media;

-- Check for table corruption
CHECK TABLE media;
```

#### Cleanup Old Data

```php
// Cleanup soft-deleted records older than 30 days
Media::onlyTrashed()
    ->where('deleted_at', '<', now()->subDays(30))
    ->forceDelete();

// Archive old media files
$oldMedia = Media::where('created_at', '<', now()->subYear())
    ->whereNull('archived_at')
    ->get();

foreach ($oldMedia as $media) {
    $this->archiveMedia($media);
}
```

## Monitoring and Alerting

### Application Metrics

```php
// Custom metrics for monitoring
class MediaMetrics
{
    public static function recordUpload($fileSize, $processingTime)
    {
        // Log to monitoring service
        Log::info('media_upload', [
            'file_size' => $fileSize,
            'processing_time' => $processingTime,
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    public static function recordError($error, $context = [])
    {
        Log::error('media_error', array_merge($context, [
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ]));
    }
}
```

### Health Checks

```php
// Health check endpoint
Route::get('/health/media', function () {
    $checks = [
        'storage_writable' => is_writable(storage_path('app/public')),
        'database_connected' => DB::connection()->getPdo() !== null,
        'disk_space' => disk_free_space(storage_path()) > 1024 * 1024 * 100, // 100MB
    ];
    
    $healthy = array_reduce($checks, fn($carry, $check) => $carry && $check, true);
    
    return response()->json([
        'healthy' => $healthy,
        'checks' => $checks,
    ], $healthy ? 200 : 500);
});
```

## Next Steps

- Review [configuration options](configuration.md) for optimization settings
- Check [API documentation](api.md) for efficient endpoint usage  
- See [usage examples](usage.md) for performance best practices
- Explore [testing strategies](testing.md) for performance testing