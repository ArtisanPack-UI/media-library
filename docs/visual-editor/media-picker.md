---
title: MediaPicker Component
---

# MediaPicker Component

The MediaPicker is a Livewire component designed for embedding in visual editors and CMS platforms. It provides a modal-based media selection interface with support for filtering, keyboard navigation, and contextual events.

## Basic Usage

```blade
<livewire:media::media-picker
    context="featured-image"
    :allowed-types="['image']"
    :multi-select="false"
/>
```

## Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `context` | string | `''` | Unique identifier for this picker instance |
| `allowedTypes` | array | `['image', 'video', 'audio', 'document']` | Allowed media types |
| `multiSelect` | bool | `false` | Enable multi-selection mode |
| `maxSelections` | int | `null` | Maximum items when multi-select enabled |
| `folderId` | int | `null` | Initial folder to display |
| `showUpload` | bool | `true` | Show upload tab |
| `showFolders` | bool | `true` | Show folder sidebar |
| `quickUploadSelect` | bool | `true` | Auto-select newly uploaded media |

## Context Parameter

The `context` parameter is crucial for identifying which picker instance dispatched an event. This allows multiple pickers on the same page to operate independently.

```blade
<!-- Featured image picker -->
<livewire:media::media-picker context="featured-image" />

<!-- Gallery picker -->
<livewire:media::media-picker context="gallery" :multi-select="true" />

<!-- Background image picker -->
<livewire:media::media-picker context="background" />
```

## Opening the Picker

### Via Livewire Event

```javascript
// Open a specific picker by context
Livewire.dispatch('open-media-picker', { context: 'featured-image' });
```

### Via Blade Directive

```blade
<button @click="$dispatch('open-media-picker', { context: 'featured-image' })">
    Select Image
</button>
```

### Via Component Method

```blade
<livewire:media::media-picker context="featured-image" wire:ref="featuredPicker" />

<button wire:click="$refs.featuredPicker.open()">
    Select Image
</button>
```

## Events

### `media-picked`

Dispatched when the user confirms their selection.

**Payload:**
```javascript
{
    context: 'featured-image',      // The picker's context
    media: [                         // Array of selected media
        {
            id: 123,
            title: 'My Image',
            file_name: 'my-image.jpg',
            mime_type: 'image/jpeg',
            url: 'https://example.com/storage/media/my-image.jpg',
            thumbnail_url: 'https://example.com/storage/media/thumbnails/my-image.jpg',
            alt_text: 'Description',
            file_size: 102400,
            width: 1920,
            height: 1080
        }
    ]
}
```

**Listening for events:**

```javascript
// JavaScript
Livewire.on('media-picked', (event) => {
    if (event.context === 'featured-image') {
        const selectedMedia = event.media;
        // Process the selection
    }
});
```

```php
// Livewire Component
#[On('media-picked')]
public function handleMediaPicked(array $media, string $context): void
{
    if ($context === 'featured-image') {
        $this->featuredImageId = $media[0]['id'];
    }
}
```

### `media-picker-opened`

Dispatched when the picker modal opens.

```javascript
Livewire.on('media-picker-opened', (event) => {
    console.log('Picker opened:', event.context);
});
```

### `media-picker-closed`

Dispatched when the picker modal closes (without selection).

```javascript
Livewire.on('media-picker-closed', (event) => {
    console.log('Picker closed:', event.context);
});
```

## Keyboard Navigation

The MediaPicker supports full keyboard navigation:

| Key | Action |
|-----|--------|
| `Arrow Left` | Move to previous item |
| `Arrow Right` | Move to next item |
| `Arrow Up` | Move to item above (in grid) |
| `Arrow Down` | Move to item below (in grid) |
| `Enter` | Toggle selection of focused item |
| `Space` | Toggle selection of focused item |
| `Escape` | Close the picker |
| `Home` | Focus first item |
| `End` | Focus last item |

### Enabling Keyboard Navigation

Keyboard navigation is enabled by default when the picker is open. The focus indicator shows which item is currently focused.

## Filtering by Type

Restrict the picker to specific media types:

```blade
<!-- Images only -->
<livewire:media::media-picker
    context="image-picker"
    :allowed-types="['image']"
/>

<!-- Videos only -->
<livewire:media::media-picker
    context="video-picker"
    :allowed-types="['video']"
/>

<!-- Documents only -->
<livewire:media::media-picker
    context="document-picker"
    :allowed-types="['document']"
/>

<!-- Multiple types -->
<livewire:media::media-picker
    context="media-picker"
    :allowed-types="['image', 'video']"
/>
```

## Multi-Selection

Enable multi-select mode for galleries or collections:

```blade
<livewire:media::media-picker
    context="gallery"
    :multi-select="true"
    :max-selections="20"
/>
```

### Selection Controls

In multi-select mode, the picker shows:
- "Select All" button to select all visible items
- "Deselect All" button to clear selections
- Selection counter showing current/max selections

## Recently Used Media

When `track_recently_used` is enabled in configuration, the picker shows recently selected media for quick access.

```php
// config/artisanpack.php
'media' => [
    'visual_editor' => [
        'track_recently_used' => true,
        'recently_used_limit' => 20,
    ],
],
```

Recently used items appear at the top of the media grid when no search/filter is active.

## Quick Upload Select

When enabled, newly uploaded media is automatically selected:

```blade
<livewire:media::media-picker
    context="quick-picker"
    :quick-upload-select="true"
/>
```

**Behavior:**
- **Single-select mode:** Upload completes, media selected, picker closes
- **Multi-select mode:** Upload completes, media added to selection, picker stays open

## Block Type Integration

Use block requirements from configuration:

```php
// In your visual editor component
$requirements = config('artisanpack.media.block_requirements.hero', []);
```

```blade
<livewire:media::media-picker
    context="hero-block"
    :allowed-types="$requirements['allowed_types'] ?? ['image']"
    :max-selections="$requirements['max_selections'] ?? 1"
/>
```

## Styling

The MediaPicker uses daisyUI and Tailwind CSS classes. Customize via:

### Publishing Views

```bash
php artisan vendor:publish --tag=media-views
```

Views are published to `resources/views/vendor/media/`.

### CSS Variables

```css
:root {
    --media-picker-grid-cols: 4;
    --media-picker-item-aspect: 1;
    --media-picker-selected-ring: theme('colors.primary');
}
```

## Integration Example

Complete example for a blog post editor:

```blade
<!-- Blog Post Editor -->
<div>
    <!-- Featured Image Field -->
    <div class="form-control">
        <label class="label">Featured Image</label>

        @if($featuredImage)
            <div class="relative w-48 h-32">
                <img src="{{ $featuredImage->thumbnail_url }}" class="rounded" />
                <button wire:click="removeFeaturedImage" class="btn btn-circle btn-xs absolute top-1 right-1">
                    ✕
                </button>
            </div>
        @else
            <button
                type="button"
                @click="$dispatch('open-media-picker', { context: 'featured-image' })"
                class="btn btn-outline"
            >
                Select Featured Image
            </button>
        @endif
    </div>

    <!-- Gallery Field -->
    <div class="form-control mt-4">
        <label class="label">Gallery Images</label>

        <div class="flex flex-wrap gap-2">
            @foreach($galleryImages as $image)
                <div class="relative w-24 h-24">
                    <img src="{{ $image->thumbnail_url }}" class="rounded" />
                    <button wire:click="removeGalleryImage({{ $image->id }})" class="btn btn-circle btn-xs absolute top-1 right-1">
                        ✕
                    </button>
                </div>
            @endforeach

            <button
                type="button"
                @click="$dispatch('open-media-picker', { context: 'gallery' })"
                class="btn btn-outline w-24 h-24"
            >
                + Add
            </button>
        </div>
    </div>

    <!-- Media Pickers -->
    <livewire:media::media-picker
        context="featured-image"
        :allowed-types="['image']"
    />

    <livewire:media::media-picker
        context="gallery"
        :allowed-types="['image']"
        :multi-select="true"
        :max-selections="20"
    />
</div>

@script
<script>
    Livewire.on('media-picked', (event) => {
        if (event.context === 'featured-image') {
            $wire.setFeaturedImage(event.media[0].id);
        } else if (event.context === 'gallery') {
            $wire.addGalleryImages(event.media.map(m => m.id));
        }
    });
</script>
@endscript
```

## Next Steps

- [Block Content Helpers](Block-Helpers) - Working with block content
- [Integration Examples](Examples) - More complete examples
- [Configuration](Installation-Configuration#visual-editor-integration-v11) - All configuration options
