---
title: Customization
---

# Customization

This guide covers various ways to customize the Media Library package to fit your application's needs.

## Publishing Assets

### Publish Configuration

```bash
php artisan vendor:publish --tag=media-config
```

Configuration will be in `config/artisanpack.php` under the `media` key.

### Publish Views

```bash
php artisan vendor:publish --tag=media-views
```

Views will be in `resources/views/vendor/media/`.

### Publish Migrations

```bash
php artisan vendor:publish --tag=media-migrations
```

Migrations will be in `database/migrations/`.

## Custom Image Sizes

Register custom image sizes in a service provider:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Product thumbnails
        apRegisterImageSize('product-small', 200, 200, true);
        apRegisterImageSize('product-medium', 400, 400, true);
        apRegisterImageSize('product-large', 800, 800, false);
        
        // Hero banners
        apRegisterImageSize('hero-desktop', 1920, 600, true);
        apRegisterImageSize('hero-mobile', 768, 400, true);
        
        // Social media
        apRegisterImageSize('og-image', 1200, 630, true);
        apRegisterImageSize('twitter-card', 1200, 600, true);
    }
}
```

Use custom sizes:

```php
$url = apGetMediaUrl($mediaId, 'product-medium');
$media->imageUrl('hero-desktop');
```

## Custom Storage Disk

### Configure Cloud Storage

**Amazon S3:**

In `config/filesystems.php`:

```php
's3-media' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_MEDIA_BUCKET'),
    'url' => env('AWS_URL'),
    'visibility' => 'public',
],
```

In `.env`:

```env
MEDIA_DISK=s3-media
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_MEDIA_BUCKET=my-media-bucket
```

**Google Cloud Storage:**

```php
'gcs-media' => [
    'driver' => 'gcs',
    'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE'),
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
    'bucket' => env('GOOGLE_CLOUD_MEDIA_BUCKET'),
    'visibility' => 'public',
],
```

## Customizing Views

After publishing views, customize them:

```blade
{{-- resources/views/vendor/media/livewire/components/media-modal.blade.php --}}

<div>
    {{-- Add custom header --}}
    <div class="custom-header">
        <h2>{{ __('Select Media') }}</h2>
        <p class="text-muted">{{ __('Choose from your media library') }}</p>
    </div>

    {{-- Original content --}}
    @include('media::partials.media-grid')
</div>
```

## Extending Models

Extend the base models to add custom functionality:

```php
namespace App\Models;

use ArtisanPackUI\MediaLibrary\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * Get media usage count across the site.
     */
    public function usageCount(): int
    {
        $count = 0;
        
        // Count in posts
        $count += \App\Models\Post::where('featured_image_id', $this->id)->count();
        
        // Count in galleries
        $count += \DB::table('gallery_media')->where('media_id', $this->id)->count();
        
        return $count;
    }
    
    /**
     * Check if media is in use.
     */
    public function isInUse(): bool
    {
        return $this->usageCount() > 0;
    }
}
```

Then update the configuration:

```php
// config/artisanpack.php
'media' => [
    'user_model' => App\Models\Media::class,
],
```

## Custom Upload Processing

Create a custom upload service:

```php
namespace App\Services;

use ArtisanPackUI\MediaLibrary\Services\MediaUploadService as BaseUploadService;

class CustomMediaUploadService extends BaseUploadService
{
    protected function afterUpload($media, $file): void
    {
        parent::afterUpload($media, $file);
        
        // Add custom watermark
        if ($media->isImage()) {
            $this->addWatermark($media);
        }
        
        // Generate AI descriptions
        $this->generateAIDescription($media);
    }
    
    protected function addWatermark($media): void
    {
        // Custom watermark logic
    }
    
    protected function generateAIDescription($media): void
    {
        // AI description generation
    }
}
```

Bind in service provider:

```php
$this->app->bind(
    \ArtisanPackUI\MediaLibrary\Services\MediaUploadService::class,
    \App\Services\CustomMediaUploadService::class
);
```

## Custom Validation Rules

Add custom validation in a Form Request:

```php
namespace App\Http\Requests;

use ArtisanPackUI\MediaLibrary\Http\Requests\MediaStoreRequest as BaseRequest;

class CustomMediaStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'file' => [
                'required',
                'file',
                'max:' . config('artisanpack.media.max_file_size'),
                'mimes:jpg,png,gif,webp',
                function ($attribute, $value, $fail) {
                    // Custom validation: check image dimensions
                    if ($value->isValid()) {
                        [$width, $height] = getimagesize($value->path());
                        
                        if ($width < 800 || $height < 600) {
                            $fail('Image must be at least 800x600 pixels.');
                        }
                    }
                },
            ],
        ]);
    }
}
```

## Pipeline hooks

The media library dispatches 13 hooks across the upload → process → thumbnail → delete lifecycle, so applications can intercept every stage without subclassing the shipped services. All hook names use the `ap.mediaLibrary.*` namespace introduced in v1.4. See the "Pipeline hooks" section of the README for the full payload signatures.

**Actions**

| Hook | Fires |
|------|-------|
| `ap.mediaLibrary.uploading` | Before an upload begins |
| `ap.mediaLibrary.uploaded` | After a `Media` record is persisted |
| `ap.mediaLibrary.beforeProcess` | Before image processing runs |
| `ap.mediaLibrary.thumbnailsGenerated` | After thumbnails are written |
| `ap.mediaLibrary.beforeDelete` | Before a `Media` record is deleted |
| `ap.mediaLibrary.deleted` | After deletion completes |

**Filters**

| Hook | Filters |
|------|---------|
| `ap.mediaLibrary.uploadOptions` | The options array passed to the upload service |
| `ap.mediaLibrary.filenameGenerated` | The stored filename before persistence |
| `ap.mediaLibrary.allowedMimeTypes` | The MIME allow-list |
| `ap.mediaLibrary.maxFileSize` | The maximum upload size |
| `ap.mediaLibrary.storageDisk` | The disk used for storage |
| `ap.mediaLibrary.imageSizes` | The registered image size definitions |
| `ap.mediaLibrary.altTextSuggestion` | Suggested alt text before it's applied |

### Examples

```php
// Extend the MIME allow-list
addFilter('ap.mediaLibrary.allowedMimeTypes', function (array $types) {
    return array_merge($types, [
        'application/zip',
        'text/plain',
    ]);
});

// Rewrite the generated filename (e.g., organize by user)
addFilter('ap.mediaLibrary.filenameGenerated', function (string $filename, $file, $context) {
    $user = $context['user'] ?? null;

    return $user
        ? "users/{$user->id}/" . date('Y/m') . "/{$filename}"
        : $filename;
}, 10, 3);

// Register additional image sizes dynamically
addFilter('ap.mediaLibrary.imageSizes', function (array $sizes) {
    if (config('theme.enable_retina')) {
        $sizes['thumbnail-2x'] = ['width' => 300, 'height' => 300, 'crop' => true];
        $sizes['medium-2x']    = ['width' => 600, 'height' => 600, 'crop' => false];
    }

    return $sizes;
});

// Guard deletion of media that's still in use
addAction('ap.mediaLibrary.beforeDelete', function ($media) {
    if ($media->isInUse()) {
        throw new \RuntimeException('Cannot delete media that is in use');
    }
});

// Notify administrators after a successful upload
addAction('ap.mediaLibrary.uploaded', function ($media) {
    Notification::send($admins, new MediaUploadedNotification($media));
});
```

### Policy ability filters

The seven `MediaPolicy` ability filters were renamed from `ap.media.*` to `ap.mediaLibrary.abilities.*` in v1.4 for cross-package consistency. The legacy names remain registered as deprecation aliases (an info-level notice is logged on first use) and will be removed in the next major version. See [`docs/integration/permissions.md`](permissions.md) for the current subscriber signatures.

## `MediaUploaded` queueable event

*Added in v1.5.0.* The synchronous `ap.mediaLibrary.uploaded` hook is the right entry point when you want to observe uploads in-band — inside the upload request lifecycle. For work that should not block the upload request (queued AI alt-text generation, indexing, notifications, thumbnails on a worker), subscribe to the Laravel event `ArtisanPackUI\MediaLibrary\Events\MediaUploaded` instead.

`ShouldQueue` listeners are dispatched onto the configured queue connection: an asynchronous connection (e.g. `redis`, `database`, `sqs`) with a running worker runs the listener outside the upload request, while Laravel's `sync` connection still executes it immediately in the current request. Pick the connection that matches how the listener should run.

The event carries the freshly persisted `Media` record and uses `SerializesModels`, so listeners can implement `ShouldQueue` safely:

```php
namespace App\Listeners;

use ArtisanPackUI\MediaLibrary\Events\MediaUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateAiAltText implements ShouldQueue
{
    public function handle( MediaUploaded $event ): void
    {
        // $event->media is the fresh Media record.
    }
}
```

Register the listener the way you would any other Laravel event — via an `EventServiceProvider` mapping or a `#[AsEventListener]` attribute on the listener class.

Both surfaces fire on every successful upload: `ap.mediaLibrary.uploaded` runs first (synchronously, in-request), then `MediaUploaded::dispatch()` runs immediately after. Use whichever matches the work you need to do — you can subscribe to both.

## Custom Livewire Components

Extend base components:

```php
namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal as BaseMediaModal;

class CustomMediaModal extends BaseMediaModal
{
    public array $customFilters = [];
    
    public function mount(bool $multiSelect = false, int $maxSelections = 0, array $selectedMedia = [], string $context = ''): void
    {
        parent::mount($multiSelect, $maxSelections, $selectedMedia, $context);
        
        // Add custom initialization
        $this->customFilters = $this->getCustomFilters();
    }
    
    protected function getCustomFilters(): array
    {
        return [
            'recent' => __('Recently Uploaded'),
            'popular' => __('Most Used'),
            'unused' => __('Unused'),
        ];
    }
    
    public function applyCustomFilter($filter): void
    {
        // Apply custom filtering logic
    }
}
```

Use in views:

```blade
<livewire:custom-media-modal context="custom" />
```

## Next Steps

- Review [CMS Module Integration](Cms-Module)
- Learn about [Permissions](Permissions)
- See [Troubleshooting](Reference-Troubleshooting)
