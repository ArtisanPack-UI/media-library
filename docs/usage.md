---
title: Usage
---

# Usage

This section covers how to use the Media Library package in your Laravel application, from basic helper functions to advanced model queries and Livewire components.

## Usage Guides

### [Helper Functions](./usage/helper-functions.md)

Learn about convenient helper functions for common operations:
- `apUploadMedia()` - Upload files with metadata
- `apGetMedia()` - Retrieve media by ID
- `apGetMediaUrl()` - Get media URLs for different sizes
- `apDeleteMedia()` - Delete media and files
- `apRegisterImageSize()` - Register custom image sizes
- Usage patterns and examples
- Best practices

### [Working with Models](./usage/models.md)

Advanced usage with Eloquent models:
- **Media Model** - Query scopes, relationships, URL methods, display methods
- **MediaFolder Model** - Hierarchy management, moving folders
- **MediaTag Model** - Tag operations, attaching/detaching
- Eager loading for performance
- Complex queries and filtering
- Type checking methods

### [Livewire Components](./usage/livewire-components.md)

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

- Explore [API Endpoints](./api.md) for programmatic access
- Review [Integration](./integration.md) options
- See [Configuration](./installation/configuration.md) for customization
