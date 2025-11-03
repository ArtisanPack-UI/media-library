---
title: Livewire Components
---

# Livewire Components

The Media Library includes three powerful Livewire components for managing and selecting media in your application UI.

## Media Modal Component

A reusable modal component for selecting media with support for single/multi-select and contextawareness for multiple instances on the same page.

### Basic Usage

```blade
{{-- Include the modal component --}}
<livewire:media::media-modal
    :multi-select="false"
    context="profile-photo"
    wire:key="profile-photo-modal"
/>

{{-- Button to trigger modal --}}
<button wire:click="$dispatch('open-media-modal', { context: 'profile-photo' })">
    Select Photo
</button>
```

### Component Properties

- `multi-select` (boolean) - Enable multiple selection
- `max-selections` (int) - Maximum selections (0 = unlimited)
- `context` (string) - Unique identifier for this modal instance
- `selected-media` (array) - Pre-selected media IDs

### Events

#### Opening the Modal

Dispatch the `open-media-modal` event with context:

```blade
<button wire:click="$dispatch('open-media-modal', { context: 'site-logo' })">
    Select Logo
</button>
```

#### Listening for Selection

Listen for the `media-selected` event:

```blade
@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'profile-photo') {
            console.log('Selected media:', event.media);
            $wire.set('photoId', event.media[0].id);
        }
    });
});
</script>
@endscript
```

### Single Select Example

```blade
<div>
    {{-- Display current image --}}
    @if ($form['siteLogo'])
        <img src="{{ apGetMediaUrl($form['siteLogo'], 'thumbnail') }}" alt="Site Logo">
        <button wire:click="removeLogo">Remove</button>
    @endif

    {{-- Select button --}}
    <button wire:click="$dispatch('open-media-modal', { context: 'site-logo' })">
        Select Logo
    </button>

    {{-- Modal --}}
    <livewire:media::media-modal
        :multi-select="false"
        context="site-logo"
        wire:key="site-logo-modal"
    />
</div>

@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'site-logo') {
            $wire.set('form.siteLogo', event.media[0].id);
        }
    });
});
</script>
@endscript
```

### Multi-Select Example

```blade
<div>
    {{-- Display selected images --}}
    @if (count($selectedImages) > 0)
        <div class="grid grid-cols-4 gap-4">
            @foreach($selectedImages as $imageId)
                <img src="{{ apGetMediaUrl($imageId, 'thumbnail') }}" alt="">
            @endforeach
        </div>
    @endif

    {{-- Select button --}}
    <button wire:click="$dispatch('open-media-modal', { context: 'gallery' })">
        Select Images (Max 10)
    </button>

    {{-- Modal --}}
    <livewire:media::media-modal
        :multi-select="true"
        :max-selections="10"
        context="gallery"
        wire:key="gallery-modal"
    />
</div>

@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'gallery') {
            $wire.set('selectedImages', event.media.map(m => m.id));
        }
    });
});
</script>
@endscript
```

### Multiple Modals on Same Page

Use different contexts to distinguish between modals:

```blade
{{-- Logo Modal --}}
<livewire:media::media-modal
    :multi-select="false"
    context="site-logo"
    wire:key="logo-modal"
/>

{{-- Background Modal --}}
<livewire:media::media-modal
    :multi-select="false"
    context="background-image"
    wire:key="background-modal"
/>

{{-- Triggers --}}
<button wire:click="$dispatch('open-media-modal', { context: 'site-logo' })">
    Select Logo
</button>

<button wire:click="$dispatch('open-media-modal', { context: 'background-image' })">
    Select Background
</button>

@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-selected', (event) => {
        if (event.context === 'site-logo') {
            $wire.set('form.siteLogo', event.media[0].id);
        } else if (event.context === 'background-image') {
            $wire.set('form.backgroundImage', event.media[0].id);
        }
    });
});
</script>
@endscript
```

### Pre-selecting Media

Pass pre-selected media IDs to the modal:

```blade
<livewire:media::media-modal
    :multi-select="true"
    :selected-media="$existingMediaIds"
    context="existing-gallery"
    wire:key="existing-gallery-modal"
/>
```

## Media Library Component

A full-featured media browsing interface with folders, tags, search, and filtering.

### Basic Usage

```blade
<livewire:media::media-library />
```

### Features

- Browse media by folder
- Search by title or filename
- Filter by type (image, video, audio, document)
- Filter by tag
- Sort by date, name, or size
- Pagination
- Grid/list view toggle
- Bulk actions

### Embedding in Admin Pages

```php
use Livewire\Volt\Volt;

// Register as admin page
Volt::route('/admin/media', MediaLibrary::class)
    ->middleware(['auth', 'verified']);
```

## Media Upload Component

A drag-and-drop upload interface with progress tracking.

### Basic Usage

```blade
<livewire:media::media-upload />
```

### Features

- Drag and drop file upload
- Multi-file upload
- Upload progress bars
- File validation
- Automatic folder selection
- Tag assignment
- Metadata input

### Custom Upload Form

```blade
<form wire:submit.prevent="upload">
    <input type="file" wire:model="files" multiple>

    @error('files.*')
        <span class="error">{{ $message }}</span>
    @enderror

    <select wire:model="folderId">
        <option value="">Root</option>
        @foreach ($folders as $folder)
            <option value="{{ $folder->id }}">{{ $folder->fullPath() }}</option>
        @endforeach
    </select>

    <button type="submit">Upload</button>
</form>

<div wire:loading wire:target="files">
    Uploading...
</div>
```

## Component Events

### Global Events

All components emit and listen for these events:

- `media-uploaded` - Fired when new media is uploaded
- `media-updated` - Fired when media metadata is updated
- `media-deleted` - Fired when media is deleted
- `folder-created` - Fired when a new folder is created
- `folder-updated` - Fired when a folder is updated

### Listening to Events

```blade
@script
<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('media-uploaded', (event) => {
        console.log('New media uploaded:', event.media);
        // Refresh your component
        $wire.$refresh();
    });

    Livewire.on('media-deleted', (event) => {
        console.log('Media deleted:', event.mediaId);
        // Update UI
    });
});
</script>
@endscript
```

## Customizing Components

### Publishing Views

Publish the component views to customize:

```bash
php artisan vendor:publish --tag=media-views
```

Views will be in `resources/views/vendor/media/livewire/`.

### Extending Components

Create your own component that extends the base:

```php
namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal as BaseMediaModal;

class CustomMediaModal extends BaseMediaModal
{
    public function mount(bool $multiSelect = false, int $maxSelections = 0, array $selectedMedia = [], string $context = ''): void
    {
        parent::mount($multiSelect, $maxSelections, $selectedMedia, $context);

        // Add custom logic
        $this->customProperty = 'custom value';
    }

    public function customMethod()
    {
        // Custom functionality
    }
}
```

## Best Practices

### Always Use Context

When using multiple modals, always provide unique contexts:

```blade
{{-- Good --}}
<livewire:media::media-modal context="logo" wire:key="logo-modal" />
<livewire:media::media-modal context="banner" wire:key="banner-modal" />

{{-- Bad (modals will conflict) --}}
<livewire:media::media-modal />
<livewire:media::media-modal />
```

### Use wire:key

Always provide a unique `wire:key` to prevent Livewire conflicts:

```blade
<livewire:media::media-modal context="photo" wire:key="photo-modal-{{ $userId }}" />
```

### Handle Errors

Always handle potential errors in event listeners:

```javascript
Livewire.on('media-selected', (event) => {
    try {
        if (event.context === 'profile-photo' && event.media.length > 0) {
            $wire.set('photoId', event.media[0].id);
        }
    } catch (error) {
        console.error('Error handling media selection:', error);
    }
});
```

## Next Steps

- Review [API Endpoints](../api/endpoints.md) for programmatic access
- Explore [Customization](../integration/customization.md) options
- Learn about [Permissions](../integration/permissions.md)
