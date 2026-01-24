---
title: Block Content Helpers
---

# Block Content Helpers

The Media Library provides helper functions and traits for working with media in block-based content structures commonly used in visual editors and CMS platforms.

## Overview

Block content helpers simplify:
- Retrieving block requirements from configuration
- Validating media against block type constraints
- Rendering media in block templates
- Managing media relationships for blocks

## Block Requirements

Block requirements define constraints for each block type in your visual editor.

### Configuration

Define requirements in `config/artisanpack.php`:

```php
'media' => [
    'block_requirements' => [
        'default' => [
            'allowed_types' => ['image', 'video', 'audio', 'document'],
            'max_file_size' => null,
            'max_selections' => null,
        ],
        'image' => [
            'allowed_types' => ['image'],
            'max_file_size' => 5120, // 5 MB
            'max_selections' => 1,
        ],
        'gallery' => [
            'allowed_types' => ['image'],
            'max_file_size' => 5120,
            'max_selections' => 20,
        ],
        'hero' => [
            'allowed_types' => ['image'],
            'max_file_size' => 10240,
            'max_selections' => 1,
            'min_width' => 1920,
            'min_height' => 600,
        ],
    ],
],
```

### Helper Functions

#### `apGetBlockRequirements()`

Retrieve requirements for a block type:

```php
// Get requirements for 'hero' block type
$requirements = apGetBlockRequirements('hero');
// Returns: ['allowed_types' => ['image'], 'max_file_size' => 10240, ...]

// Falls back to 'default' if type not found
$requirements = apGetBlockRequirements('unknown-type');
// Returns: config('artisanpack.media.block_requirements.default')
```

#### `apValidateMediaForBlock()`

Validate a media item against block requirements:

```php
$media = apGetMedia($mediaId);
$result = apValidateMediaForBlock($media, 'hero');

if ($result['valid']) {
    // Media meets all requirements
} else {
    // Get validation errors
    $errors = $result['errors'];
    // ['Image must be at least 1920x600 pixels']
}
```

#### `apGetAllowedTypesForBlock()`

Get allowed MIME types for a block:

```php
$types = apGetAllowedTypesForBlock('image');
// Returns: ['image/jpeg', 'image/png', 'image/gif', 'image/webp', ...]
```

## HasBlockMedia Trait

Add the `HasBlockMedia` trait to your block models for convenient media management.

### Setup

```php
use ArtisanPackUI\MediaLibrary\Traits\HasBlockMedia;

class ContentBlock extends Model
{
    use HasBlockMedia;

    protected $casts = [
        'content' => 'array',
    ];
}
```

### Methods

#### `getBlockMedia()`

Retrieve media for the block:

```php
$block = ContentBlock::find(1);

// Get single media (for image blocks)
$media = $block->getBlockMedia();

// Get media from specific content key
$media = $block->getBlockMedia('background_image');
```

#### `setBlockMedia()`

Set media for the block:

```php
$block = ContentBlock::find(1);

// Set single media
$block->setBlockMedia($mediaId);

// Set media with specific key
$block->setBlockMedia($mediaId, 'featured_image');

// Set multiple media (galleries)
$block->setBlockMedia([$id1, $id2, $id3], 'gallery');
```

#### `validateBlockMedia()`

Validate media against block requirements:

```php
$block = ContentBlock::find(1);
$block->block_type = 'hero';

$validation = $block->validateBlockMedia($mediaId);

if (!$validation['valid']) {
    throw new ValidationException($validation['errors']);
}
```

## Rendering Media in Blocks

### Single Image Block

```blade
@php
    $block = $page->blocks->where('type', 'hero')->first();
    $media = $block->getBlockMedia();
@endphp

@if($media)
    <div class="hero-image">
        <img
            src="{{ $media->url() }}"
            alt="{{ $media->alt_text }}"
            width="{{ $media->width }}"
            height="{{ $media->height }}"
        />
    </div>
@endif
```

### Gallery Block

```blade
@php
    $block = $page->blocks->where('type', 'gallery')->first();
    $images = $block->getBlockMedia('images');
@endphp

@if($images->isNotEmpty())
    <div class="gallery grid grid-cols-3 gap-4">
        @foreach($images as $image)
            <figure>
                <img
                    src="{{ $image->imageUrl('medium') }}"
                    alt="{{ $image->alt_text }}"
                />
                @if($image->caption)
                    <figcaption>{{ $image->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
```

### Responsive Images

```blade
@php
    $media = $block->getBlockMedia();
@endphp

@if($media && $media->isImage())
    <picture>
        @if($media->hasSize('large'))
            <source
                media="(min-width: 1024px)"
                srcset="{{ $media->imageUrl('large') }}"
            />
        @endif
        @if($media->hasSize('medium'))
            <source
                media="(min-width: 640px)"
                srcset="{{ $media->imageUrl('medium') }}"
            />
        @endif
        <img
            src="{{ $media->imageUrl('thumbnail') }}"
            alt="{{ $media->alt_text }}"
        />
    </picture>
@endif
```

## Block Type Registry

Register custom block types at runtime:

```php
// In AppServiceProvider boot()
use ArtisanPackUI\MediaLibrary\Facades\MediaLibrary;

MediaLibrary::registerBlockType('testimonial', [
    'allowed_types' => ['image'],
    'max_file_size' => 1024,
    'max_selections' => 1,
    'min_width' => 200,
    'min_height' => 200,
    'aspect_ratio' => '1:1',
]);
```

### Custom Validators

Add custom validation rules for block types:

```php
MediaLibrary::addBlockValidator('testimonial', function ($media, $requirements) {
    // Custom validation logic
    if ($media->width !== $media->height) {
        return [
            'valid' => false,
            'errors' => ['Testimonial images must be square'],
        ];
    }

    return ['valid' => true, 'errors' => []];
});
```

## Integration with Visual Editors

### Block Handler Example

```php
// In your visual editor block handler
class HeroBlock extends BaseBlock
{
    public function getMediaRequirements(): array
    {
        return apGetBlockRequirements('hero');
    }

    public function validateContent(array $content): array
    {
        $errors = parent::validateContent($content);

        if (isset($content['media_id'])) {
            $validation = apValidateMediaForBlock(
                apGetMedia($content['media_id']),
                'hero'
            );

            if (!$validation['valid']) {
                $errors['media_id'] = $validation['errors'];
            }
        }

        return $errors;
    }
}
```

### Generic Visual Editor Integration

```javascript
// JavaScript: When a block requests media
function openMediaPickerForBlock(blockType, blockId, fieldName) {
    const requirements = window.blockRequirements[blockType];

    Livewire.dispatch('open-media-picker', {
        context: `${blockType}-${blockId}-${fieldName}`,
        allowedTypes: requirements.allowed_types,
        maxSelections: requirements.max_selections,
    });
}

// Handle selection
Livewire.on('media-picked', (event) => {
    const [blockType, blockId, fieldName] = event.context.split('-');

    updateBlockMedia(blockType, blockId, fieldName, event.media);
});
```

## Next Steps

- [MediaPicker Component](./media-picker.md) - UI for media selection
- [Integration Examples](./examples.md) - Complete integration guides
- [Configuration](../installation/configuration.md#block-requirements-v11) - Block requirements configuration
