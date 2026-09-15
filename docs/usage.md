---
title: Usage
---

# Usage

This section covers how to use the Media Library package in your Laravel application, from basic helper functions to advanced model queries and Livewire components.

## Usage Guides

### [Helper Functions](Usage-Helper-Functions)

Learn about convenient helper functions for common operations:
- `apUploadMedia()` - Upload files with metadata
- `apGetMedia()` - Retrieve media by ID
- `apGetMediaUrl()` - Get media URLs for different sizes
- `apDeleteMedia()` - Delete media and files
- `apRegisterImageSize()` - Register custom image sizes
- Usage patterns and examples
- Best practices

### [Working with Models](Usage-Models)

Advanced usage with Eloquent models:
- **Media Model** - Query scopes, relationships, URL methods, display methods
- **MediaFolder Model** - Hierarchy management, moving folders
- **MediaTag Model** - Tag operations, attaching/detaching
- Eager loading for performance
- Complex queries and filtering
- Type checking methods

### [AI Features](Usage-AI-Features)

*Added in v1.3.0.* Vision-powered helpers for alt text, tag suggestions, and paragraph-length image descriptions:
- Three JSON endpoints — `POST /api/media/{id}/ai/{alt-text|tags|description}`
- Wired into the Livewire `MediaEdit` and `MediaUpload` components and the shipped React/Vue components
- Filename-based fallback so the alt-text field is never left silently empty
- Requires `artisanpack-ui/ai` `^1.0`

### [Optimization Status](Usage-Optimization-Status)

*Added in v1.5.0.* Persistent per-item state for the image optimization pipeline:
- Six new columns on the `media` table — `optimization_status`, `optimized_at`, `optimization_bytes_saved`, `optimization_original_size`, `optimization_formats`, `optimization_error`
- Three model constants (`OPTIMIZATION_STATUS_PENDING`, `OPTIMIZATION_STATUS_OPTIMIZED`, `OPTIMIZATION_STATUS_FAILED`) for status comparisons
- `MediaResource` returns a stable `optimization` block on every media item (non-image rows carry `null` values)
- `media:backfill-optimization-status` artisan command for legacy image rows

### [Livewire Components](Usage-Livewire-Components)

UI components for media management:
- **Media Modal** - Single/multi-select modal with context support
- **Media Library** - Full browsing interface
- **Media Upload** - Drag-and-drop upload component
- Component events and listeners
- Multiple modals on same page
- Customizing components
- Best practices

## Quick Examples

### Upload Media

```php
$media = apUploadMedia($file, [
    'title' => 'Product Photo',
    'alt_text' => 'Red sneakers',
    'folder_id' => 1,
    'tags' => ['featured', 'products'],
]);
```

### Display Image

```php
$media = apGetMedia($mediaId);
echo $media->displayImage('large', ['class' => 'img-fluid', 'loading' => 'lazy']);
```

### Use Media Modal

```blade
<livewire:media::media-modal
    :multi-select="false"
    context="profile-photo"
    wire:key="profile-photo-modal"
/>

<button wire:click="$dispatch('open-media-modal', { context: 'profile-photo' })">
    Select Photo
</button>
```

### Query Media

```php
$images = Media::images()
    ->inFolder($folderId)
    ->withTag('featured')
    ->latest()
    ->paginate(15);
```

## Next Steps

- Explore [API Endpoints](Api) for programmatic access
- Review [Integration](Integration) options
- See [Configuration](Installation-Configuration) for customization
