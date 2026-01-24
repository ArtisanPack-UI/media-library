---
title: Integration Examples
---

# Integration Examples

Complete examples for integrating the Media Library with visual editors and CMS platforms.

## Blog Post Editor

A full-featured blog post editor with featured image and gallery support.

### Livewire Component

```php
<?php

namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Livewire\Attributes\On;
use Livewire\Component;

class BlogPostEditor extends Component
{
    public $post;
    public $title = '';
    public $content = '';
    public $featuredImageId = null;
    public $galleryImageIds = [];

    public function mount($post = null)
    {
        if ($post) {
            $this->post = $post;
            $this->title = $post->title;
            $this->content = $post->content;
            $this->featuredImageId = $post->featured_image_id;
            $this->galleryImageIds = $post->gallery_images ?? [];
        }
    }

    #[On('media-picked')]
    public function handleMediaPicked(array $media, string $context): void
    {
        match ($context) {
            'featured-image' => $this->setFeaturedImage($media[0]['id']),
            'gallery' => $this->addGalleryImages(collect($media)->pluck('id')->toArray()),
            default => null,
        };
    }

    public function setFeaturedImage(int $mediaId): void
    {
        $this->featuredImageId = $mediaId;
    }

    public function removeFeaturedImage(): void
    {
        $this->featuredImageId = null;
    }

    public function addGalleryImages(array $mediaIds): void
    {
        $this->galleryImageIds = array_unique(
            array_merge($this->galleryImageIds, $mediaIds)
        );
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $this->galleryImageIds = array_values(
            array_diff($this->galleryImageIds, [$mediaId])
        );
    }

    public function reorderGallery(array $order): void
    {
        $this->galleryImageIds = $order;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data = [
            ...$validated,
            'featured_image_id' => $this->featuredImageId,
            'gallery_images' => $this->galleryImageIds,
        ];

        if ($this->post) {
            $this->post->update($data);
        } else {
            $this->post = auth()->user()->posts()->create($data);
        }

        $this->dispatch('post-saved');
    }

    public function render()
    {
        return view('livewire.blog-post-editor', [
            'featuredImage' => $this->featuredImageId
                ? Media::find($this->featuredImageId)
                : null,
            'galleryImages' => Media::whereIn('id', $this->galleryImageIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $this->galleryImageIds ?: [0]) . ')')
                ->get(),
        ]);
    }
}
```

### Blade Template

```blade
{{-- resources/views/livewire/blog-post-editor.blade.php --}}
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">
        {{ $post ? 'Edit Post' : 'Create Post' }}
    </h1>

    <form wire:submit="save" class="space-y-6">
        <!-- Title -->
        <x-artisanpack-input
            label="Title"
            wire:model="title"
            placeholder="Enter post title..."
            :error="$errors->first('title')"
        />

        <!-- Featured Image -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Featured Image</span>
            </label>

            @if($featuredImage)
                <div class="relative inline-block">
                    <img
                        src="{{ $featuredImage->imageUrl('medium') }}"
                        alt="{{ $featuredImage->alt_text }}"
                        class="rounded-lg max-w-md"
                    />
                    <button
                        type="button"
                        wire:click="removeFeaturedImage"
                        class="btn btn-circle btn-sm btn-error absolute top-2 right-2"
                    >
                        <x-artisanpack-icon name="o-x-mark" class="w-4 h-4" />
                    </button>
                </div>
            @else
                <button
                    type="button"
                    @click="$dispatch('open-media-picker', { context: 'featured-image' })"
                    class="btn btn-outline"
                >
                    <x-artisanpack-icon name="o-photo" class="w-5 h-5 mr-2" />
                    Select Featured Image
                </button>
            @endif
        </div>

        <!-- Content -->
        <x-artisanpack-rich-text-editor
            label="Content"
            wire:model="content"
            :error="$errors->first('content')"
        />

        <!-- Gallery -->
        <div class="form-control">
            <label class="label">
                <span class="label-text font-medium">Gallery Images</span>
                <span class="label-text-alt">{{ count($galleryImageIds) }}/20 images</span>
            </label>

            <div
                class="grid grid-cols-4 gap-4"
                x-data="{ dragging: null }"
                x-on:dragover.prevent
            >
                @foreach($galleryImages as $index => $image)
                    <div
                        class="relative group cursor-move"
                        draggable="true"
                        x-on:dragstart="dragging = {{ $image->id }}"
                        x-on:dragend="dragging = null"
                        x-on:drop="$wire.reorderGallery([...document.querySelectorAll('[data-gallery-id]')].map(el => parseInt(el.dataset.galleryId)))"
                        data-gallery-id="{{ $image->id }}"
                    >
                        <img
                            src="{{ $image->imageUrl('thumbnail') }}"
                            alt="{{ $image->alt_text }}"
                            class="rounded aspect-square object-cover w-full"
                        />
                        <button
                            type="button"
                            wire:click="removeGalleryImage({{ $image->id }})"
                            class="btn btn-circle btn-xs btn-error absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <x-artisanpack-icon name="o-x-mark" class="w-3 h-3" />
                        </button>
                        <div class="absolute bottom-1 left-1 badge badge-sm badge-neutral">
                            {{ $index + 1 }}
                        </div>
                    </div>
                @endforeach

                @if(count($galleryImageIds) < 20)
                    <button
                        type="button"
                        @click="$dispatch('open-media-picker', { context: 'gallery' })"
                        class="aspect-square border-2 border-dashed border-base-300 rounded flex flex-col items-center justify-center hover:border-primary transition-colors"
                    >
                        <x-artisanpack-icon name="o-plus" class="w-8 h-8 text-base-content/50" />
                        <span class="text-sm text-base-content/50 mt-1">Add Images</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2">
            <x-artisanpack-button type="button" color="ghost">
                Cancel
            </x-artisanpack-button>
            <x-artisanpack-button type="submit" color="primary">
                {{ $post ? 'Update Post' : 'Create Post' }}
            </x-artisanpack-button>
        </div>
    </form>

    <!-- Media Pickers -->
    <livewire:media-picker
        context="featured-image"
        :allowed-types="['image']"
    />

    <livewire:media-picker
        context="gallery"
        :allowed-types="['image']"
        :multi-select="true"
        :max-selections="20 - count($galleryImageIds)"
    />
</div>
```

## Page Builder with Blocks

A visual page builder with multiple block types.

### Block Configuration

```php
// config/artisanpack.php
'media' => [
    'block_requirements' => [
        'hero' => [
            'allowed_types' => ['image'],
            'max_file_size' => 10240,
            'max_selections' => 1,
            'min_width' => 1920,
            'min_height' => 600,
        ],
        'text-with-image' => [
            'allowed_types' => ['image'],
            'max_file_size' => 5120,
            'max_selections' => 1,
        ],
        'gallery' => [
            'allowed_types' => ['image'],
            'max_file_size' => 5120,
            'max_selections' => 12,
        ],
        'video' => [
            'allowed_types' => ['video'],
            'max_file_size' => 102400,
            'max_selections' => 1,
        ],
        'testimonial' => [
            'allowed_types' => ['image'],
            'max_file_size' => 1024,
            'max_selections' => 1,
        ],
    ],
],
```

### Page Builder Component

```php
<?php

namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Livewire\Attributes\On;
use Livewire\Component;

class PageBuilder extends Component
{
    public $page;
    public $blocks = [];
    public $activeBlockId = null;
    public $activeFieldName = null;

    public function mount($page)
    {
        $this->page = $page;
        $this->blocks = $page->blocks ?? [];
    }

    public function openMediaPicker(string $blockId, string $fieldName): void
    {
        $this->activeBlockId = $blockId;
        $this->activeFieldName = $fieldName;

        $block = collect($this->blocks)->firstWhere('id', $blockId);
        $requirements = apGetBlockRequirements($block['type']);

        $this->dispatch('open-media-picker', [
            'context' => "block-{$blockId}-{$fieldName}",
            'allowedTypes' => $requirements['allowed_types'] ?? ['image'],
            'maxSelections' => $requirements['max_selections'] ?? 1,
        ]);
    }

    #[On('media-picked')]
    public function handleMediaPicked(array $media, string $context): void
    {
        if (!str_starts_with($context, 'block-')) {
            return;
        }

        $parts = explode('-', $context);
        $blockId = $parts[1];
        $fieldName = $parts[2];

        $this->blocks = collect($this->blocks)->map(function ($block) use ($blockId, $fieldName, $media) {
            if ($block['id'] === $blockId) {
                $block['content'][$fieldName] = count($media) === 1
                    ? $media[0]['id']
                    : collect($media)->pluck('id')->toArray();
            }
            return $block;
        })->toArray();
    }

    public function addBlock(string $type): void
    {
        $this->blocks[] = [
            'id' => uniqid('block_'),
            'type' => $type,
            'content' => [],
        ];
    }

    public function removeBlock(string $blockId): void
    {
        $this->blocks = collect($this->blocks)
            ->reject(fn ($block) => $block['id'] === $blockId)
            ->values()
            ->toArray();
    }

    public function save(): void
    {
        $this->page->update(['blocks' => $this->blocks]);
        $this->dispatch('page-saved');
    }

    public function render()
    {
        return view('livewire.page-builder');
    }
}
```

### Page Builder Template

```blade
{{-- resources/views/livewire/page-builder.blade.php --}}
<div class="flex h-screen">
    <!-- Block Library Sidebar -->
    <aside class="w-64 bg-base-200 p-4 overflow-y-auto">
        <h2 class="font-bold mb-4">Add Block</h2>

        <div class="space-y-2">
            @foreach(['hero', 'text-with-image', 'gallery', 'video', 'testimonial'] as $type)
                <button
                    wire:click="addBlock('{{ $type }}')"
                    class="btn btn-sm btn-block justify-start"
                >
                    <x-artisanpack-icon name="{{ $blockIcons[$type] ?? 'o-square-3-stack-3d' }}" class="w-4 h-4" />
                    {{ Str::title(str_replace('-', ' ', $type)) }}
                </button>
            @endforeach
        </div>
    </aside>

    <!-- Canvas -->
    <main class="flex-1 overflow-y-auto p-8 bg-base-100">
        <div class="max-w-4xl mx-auto space-y-6">
            @forelse($blocks as $index => $block)
                <div
                    class="border rounded-lg p-4 relative group"
                    wire:key="block-{{ $block['id'] }}"
                >
                    <!-- Block Header -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="badge">{{ Str::title(str_replace('-', ' ', $block['type'])) }}</span>
                        <button
                            wire:click="removeBlock('{{ $block['id'] }}')"
                            class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100"
                        >
                            Remove
                        </button>
                    </div>

                    <!-- Block Content Editor -->
                    @include("blocks.editors.{$block['type']}", ['block' => $block])
                </div>
            @empty
                <div class="text-center py-12 text-base-content/50">
                    <p>No blocks yet. Add a block from the sidebar.</p>
                </div>
            @endforelse
        </div>
    </main>

    <!-- Dynamic Media Picker -->
    <livewire:media-picker context="block-media" />
</div>
```

### Block Editor Partials

```blade
{{-- resources/views/blocks/editors/hero.blade.php --}}
<div class="space-y-4">
    <!-- Background Image -->
    <div>
        <label class="label">Background Image</label>
        @php $media = isset($block['content']['image']) ? \ArtisanPackUI\MediaLibrary\Models\Media::find($block['content']['image']) : null; @endphp

        @if($media)
            <div class="relative">
                <img src="{{ $media->imageUrl('medium') }}" class="rounded w-full h-48 object-cover" />
                <button
                    type="button"
                    wire:click="$set('blocks.{{ $loop->index }}.content.image', null)"
                    class="btn btn-circle btn-sm btn-error absolute top-2 right-2"
                >
                    <x-artisanpack-icon name="o-x-mark" class="w-4 h-4" />
                </button>
            </div>
        @else
            <button
                type="button"
                wire:click="openMediaPicker('{{ $block['id'] }}', 'image')"
                class="btn btn-outline w-full h-32"
            >
                Select Hero Image (1920x600 min)
            </button>
        @endif
    </div>

    <!-- Title -->
    <x-artisanpack-input
        label="Title"
        wire:model.live="blocks.{{ $loop->index }}.content.title"
    />

    <!-- Subtitle -->
    <x-artisanpack-textarea
        label="Subtitle"
        wire:model.live="blocks.{{ $loop->index }}.content.subtitle"
        rows="2"
    />
</div>
```

```blade
{{-- resources/views/blocks/editors/gallery.blade.php --}}
<div>
    <label class="label">Gallery Images (up to 12)</label>

    @php
        $imageIds = $block['content']['images'] ?? [];
        $images = \ArtisanPackUI\MediaLibrary\Models\Media::whereIn('id', $imageIds)->get();
    @endphp

    <div class="grid grid-cols-4 gap-2">
        @foreach($images as $image)
            <div class="relative aspect-square">
                <img src="{{ $image->imageUrl('thumbnail') }}" class="rounded object-cover w-full h-full" />
                <button
                    type="button"
                    wire:click="$set('blocks.{{ $loop->parent->index }}.content.images', {{ json_encode(array_diff($imageIds, [$image->id])) }})"
                    class="btn btn-circle btn-xs btn-error absolute top-1 right-1"
                >
                    <x-artisanpack-icon name="o-x-mark" class="w-3 h-3" />
                </button>
            </div>
        @endforeach

        @if(count($images) < 12)
            <button
                type="button"
                wire:click="openMediaPicker('{{ $block['id'] }}', 'images')"
                class="aspect-square border-2 border-dashed rounded flex items-center justify-center"
            >
                <x-artisanpack-icon name="o-plus" class="w-6 h-6" />
            </button>
        @endif
    </div>
</div>
```

## Product Editor

E-commerce product editor with image management.

```php
<?php

namespace App\Livewire;

use App\Models\Product;
use ArtisanPackUI\MediaLibrary\Models\Media;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductEditor extends Component
{
    public Product $product;
    public array $imageIds = [];

    protected $rules = [
        'product.name' => 'required|string|max:255',
        'product.description' => 'required|string',
        'product.price' => 'required|numeric|min:0',
        'imageIds' => 'array|min:1',
        'imageIds.*' => 'exists:media,id',
    ];

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->imageIds = $product->images()->pluck('media.id')->toArray();
    }

    #[On('media-picked')]
    public function handleMediaPicked(array $media, string $context): void
    {
        if ($context !== 'product-images') {
            return;
        }

        $this->imageIds = array_unique(array_merge(
            $this->imageIds,
            collect($media)->pluck('id')->toArray()
        ));
    }

    public function removeImage(int $mediaId): void
    {
        $this->imageIds = array_values(array_diff($this->imageIds, [$mediaId]));
    }

    public function setPrimaryImage(int $mediaId): void
    {
        // Move to first position
        $this->imageIds = array_values(array_diff($this->imageIds, [$mediaId]));
        array_unshift($this->imageIds, $mediaId);
    }

    public function save(): void
    {
        $this->validate();

        $this->product->save();
        $this->product->syncImages($this->imageIds);

        $this->dispatch('product-saved');
    }

    public function render()
    {
        return view('livewire.product-editor', [
            'images' => Media::whereIn('id', $this->imageIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $this->imageIds ?: [0]) . ')')
                ->get(),
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/product-editor.blade.php --}}
<div class="grid grid-cols-2 gap-8">
    <!-- Images -->
    <div>
        <h2 class="text-lg font-bold mb-4">Product Images</h2>

        @if($images->isNotEmpty())
            <!-- Primary Image -->
            <div class="mb-4">
                <img
                    src="{{ $images->first()->imageUrl('large') }}"
                    alt="{{ $images->first()->alt_text }}"
                    class="rounded-lg w-full aspect-square object-cover"
                />
            </div>

            <!-- Thumbnail Grid -->
            <div class="grid grid-cols-4 gap-2">
                @foreach($images as $index => $image)
                    <div
                        class="relative aspect-square cursor-pointer {{ $index === 0 ? 'ring-2 ring-primary ring-offset-2' : '' }}"
                        wire:click="setPrimaryImage({{ $image->id }})"
                    >
                        <img
                            src="{{ $image->imageUrl('thumbnail') }}"
                            class="rounded object-cover w-full h-full"
                        />
                        <button
                            wire:click.stop="removeImage({{ $image->id }})"
                            class="btn btn-circle btn-xs btn-error absolute -top-1 -right-1"
                        >
                            <x-artisanpack-icon name="o-x-mark" class="w-3 h-3" />
                        </button>
                        @if($index === 0)
                            <span class="absolute bottom-1 left-1 badge badge-primary badge-xs">Primary</span>
                        @endif
                    </div>
                @endforeach

                <button
                    type="button"
                    @click="$dispatch('open-media-picker', { context: 'product-images' })"
                    class="aspect-square border-2 border-dashed rounded flex items-center justify-center hover:border-primary"
                >
                    <x-artisanpack-icon name="o-plus" class="w-6 h-6" />
                </button>
            </div>
        @else
            <button
                type="button"
                @click="$dispatch('open-media-picker', { context: 'product-images' })"
                class="w-full aspect-square border-2 border-dashed rounded-lg flex flex-col items-center justify-center hover:border-primary"
            >
                <x-artisanpack-icon name="o-photo" class="w-12 h-12 mb-2" />
                <span>Add Product Images</span>
            </button>
        @endif

        @error('imageIds')
            <p class="text-error text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

    <!-- Details -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold">Product Details</h2>

        <x-artisanpack-input
            label="Product Name"
            wire:model="product.name"
            :error="$errors->first('product.name')"
        />

        <x-artisanpack-textarea
            label="Description"
            wire:model="product.description"
            :error="$errors->first('product.description')"
            rows="4"
        />

        <x-artisanpack-input
            label="Price"
            wire:model="product.price"
            type="number"
            step="0.01"
            prefix="$"
            :error="$errors->first('product.price')"
        />

        <x-artisanpack-button wire:click="save" color="primary" class="w-full">
            Save Product
        </x-artisanpack-button>
    </div>

    <!-- Media Picker -->
    <livewire:media-picker
        context="product-images"
        :allowed-types="['image']"
        :multi-select="true"
        :max-selections="10"
    />
</div>
```

## Next Steps

- [MediaPicker Component](./media-picker.md) - Detailed component documentation
- [Block Content Helpers](./block-helpers.md) - Working with block content
- [Configuration](../installation/configuration.md#visual-editor-integration-v11) - All configuration options
