# Migration Guide

This guide helps you migrate from existing media management systems to the ArtisanPack UI Media Library package.

## Migration from CMS Framework

If you're migrating from the cms-framework's internal media functionality, follow these steps:

### 1. Update Dependencies

Remove cms-framework media dependencies and add media-library:

```bash
# Remove old dependency (if applicable)
composer remove artisanpack-ui/cms-framework

# Add new dependency
composer require artisanpack-ui/media-library
```

### 2. Update Imports

Replace cms-framework media imports throughout your codebase:

```php
// Before
use ArtisanPackUI\CMSFramework\Features\Media\MediaManager;
use ArtisanPackUI\CMSFramework\Models\Media;
use ArtisanPackUI\CMSFramework\Models\MediaCategory;
use ArtisanPackUI\CMSFramework\Models\MediaTag;

// After  
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
```

### 3. Update Service Provider Registration

```php
// Before
$this->app->register(CMSFramework\Features\Media\MediaServiceProvider::class);

// After
$this->app->register(ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider::class);
```

### 4. Database Migration

The database structure remains compatible, but you may need to run new migrations:

```bash
php artisan vendor:publish --tag="media-library-migrations"
php artisan migrate
```

### 5. Configuration Updates

Update your media configuration to use the new config file:

```bash
php artisan vendor:publish --tag="media-library-config"
```

Compare your existing configuration with the new format and update accordingly.

### 6. Route Updates

If you were using CMS Framework routes, update your route references:

```php
// Before
Route::group(['prefix' => 'cms/media'], function () {
    // CMS Framework routes
});

// After
// Media Library routes are auto-registered
// Or customize in your RouteServiceProvider
```

### 7. API Endpoint Changes

Update any frontend code that calls the media API:

```javascript
// Before
fetch('/cms/api/media')

// After  
fetch('/api/media/items')
```

### 8. View and Blade Template Updates

Update any Blade templates that reference media:

```blade
{{-- Before --}}
@if($media->file_type === 'image')
    <img src="{{ $media->url }}" alt="{{ $media->description }}">
@endif

{{-- After --}}
@if(str_starts_with($media->mime_type, 'image/'))
    <img src="{{ Storage::url($media->file_path) }}" alt="{{ $media->alt_text }}">
@endif
```

## Migration from Laravel Spatie Media Library

If you're migrating from Spatie's Media Library package:

### 1. Data Migration Script

Create a migration script to transfer existing data:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use ArtisanPackUI\MediaLibrary\Models\Media as NewMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class MigrateFromSpatieMediaLibrary extends Migration
{
    public function up()
    {
        $spatieMedia = SpatieMedia::all();
        
        foreach ($spatieMedia as $media) {
            NewMedia::create([
                'filename' => $media->file_name,
                'original_filename' => $media->name,
                'file_path' => $media->getPath(),
                'mime_type' => $media->mime_type,
                'file_size' => $media->size,
                'alt_text' => $media->getCustomProperty('alt') ?? '',
                'caption' => $media->getCustomProperty('caption') ?? '',
                'is_decorative' => $media->getCustomProperty('decorative') ?? false,
                'metadata' => $media->custom_properties,
                'user_id' => $media->model_id, // Adjust as needed
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
            ]);
        }
    }
    
    public function down()
    {
        // Rollback logic if needed
    }
}
```

### 2. Update Model Relationships

```php
// Before (Spatie)
class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public function getFirstMediaUrl($collection = 'default'): string
    {
        return $this->getFirstMediaUrl($collection);
    }
}

// After (Media Library)
class Post extends Model
{
    public function media()
    {
        return $this->belongsToMany(Media::class, 'post_media');
    }
    
    public function getFirstMediaUrl(): string
    {
        $media = $this->media()->first();
        return $media ? Storage::url($media->file_path) : '';
    }
}
```

## Migration from Custom Media Solutions

### 1. Analyze Current Structure

Document your current media table structure:

```sql
-- Example current structure
CREATE TABLE media (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    path VARCHAR(255),
    size INT,
    type VARCHAR(100),
    created_at TIMESTAMP
);
```

### 2. Create Data Migration

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

// Migration example
$oldMedia = DB::table('old_media_table')->get();

foreach ($oldMedia as $media) {
    Media::create([
        'filename' => pathinfo($media->name, PATHINFO_FILENAME),
        'original_filename' => $media->name,
        'file_path' => $media->path,
        'mime_type' => $media->type,
        'file_size' => $media->size,
        'alt_text' => $media->alt ?? '',
        'caption' => $media->caption ?? '',
        'is_decorative' => false,
        'metadata' => [],
        'user_id' => $media->user_id ?? 1,
        'created_at' => $media->created_at,
    ]);
}
```

### 3. File System Migration

If files need to be moved to match the new structure:

```php
use Illuminate\Support\Facades\Storage;

$mediaItems = Media::all();

foreach ($mediaItems as $media) {
    $oldPath = $media->file_path;
    $newPath = 'media/' . date('Y/m', strtotime($media->created_at)) . '/' . $media->filename;
    
    if (Storage::exists($oldPath)) {
        Storage::move($oldPath, $newPath);
        $media->update(['file_path' => $newPath]);
    }
}
```

## Post-Migration Testing

### 1. Verify Data Integrity

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

// Check media counts match
$newCount = Media::count();
echo "Migrated {$newCount} media items";

// Verify file accessibility
$media = Media::first();
if (Storage::exists($media->file_path)) {
    echo "Files accessible";
}
```

### 2. Test Upload Functionality

```php
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;

$mediaManager = app(MediaManager::class);
$testFile = UploadedFile::fake()->image('test.jpg');

$media = $mediaManager->upload(
    file: $testFile,
    altText: 'Test migration upload',
    caption: 'Testing after migration'
);

echo "Upload test successful: {$media->id}";
```

### 3. API Testing

Test all API endpoints to ensure they work correctly:

```bash
# Test media listing
curl -H "Authorization: Bearer {token}" http://yourapp.com/api/media/items

# Test upload
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "file=@test.jpg" \
  -F "alt_text=Test image" \
  http://yourapp.com/api/media/items
```

## Common Migration Issues

### File Path Issues

**Problem**: Files not found after migration
**Solution**: Update file paths in the database or move files to match expected locations

```php
// Update file paths
Media::chunk(100, function ($mediaItems) {
    foreach ($mediaItems as $media) {
        $correctPath = 'media/' . date('Y/m', strtotime($media->created_at)) . '/' . $media->filename;
        if (Storage::exists($correctPath)) {
            $media->update(['file_path' => $correctPath]);
        }
    }
});
```

### Permission Issues

**Problem**: Uploaded files not accessible
**Solution**: Fix storage permissions

```bash
chmod -R 755 storage/app/public
php artisan storage:link
```

### Database Constraint Issues

**Problem**: Foreign key constraints during migration
**Solution**: Temporarily disable constraints

```php
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
// Run migration
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
```

### Memory Issues with Large Migrations

**Problem**: PHP memory exhaustion during large data migrations
**Solution**: Process in chunks

```php
Media::chunk(1000, function ($mediaItems) {
    foreach ($mediaItems as $media) {
        // Process each media item
    }
});
```

## Rollback Plan

Always have a rollback plan:

1. **Database Backup**: Before migration, backup all relevant tables
2. **File Backup**: Backup media files
3. **Rollback Script**: Prepare scripts to restore previous state

```bash
# Database backup
mysqldump -u user -p database_name > backup_before_migration.sql

# File backup
tar -czf media_backup.tar.gz storage/app/public/media/
```

## Getting Help

If you encounter issues during migration:

1. Check the [troubleshooting guide](performance.md#troubleshooting)
2. Review the [usage examples](usage.md) for correct implementation
3. Consult the [API documentation](api.md) for endpoint changes
4. Create an issue on GitHub with migration details

## Next Steps

After successful migration:

1. Review [performance optimization](performance.md) guidelines
2. Set up proper [configuration](configuration.md)
3. Test all functionality with the [usage guide](usage.md)
4. Update your documentation to reflect the new implementation