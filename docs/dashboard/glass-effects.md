---
title: Glass Effects
---

# Glass Effects

The Media Library v1.1 integrates with livewire-ui-components v2.0 to provide modern glassmorphism (glass) effects for cards, modals, and overlays.

## Overview

Glass effects create a frosted glass appearance with:
- Semi-transparent backgrounds
- Backdrop blur
- Subtle borders
- Modern aesthetic

## Basic Usage

### Cards with Glass Effect

```blade
<x-artisanpack-card :glass="true">
    Content with glass styling
</x-artisanpack-card>
```

### Stats with Glass Effect

```blade
<x-artisanpack-stat
    title="Total Media"
    :value="$count"
    :glass="true"
/>
```

### Media Statistics Dashboard

```blade
<livewire:media-statistics :glass="true" />
```

## Configuration

Configure glass effects in `config/artisanpack.php`:

```php
'media' => [
    'ui' => [
        'glass_effects' => [
            'enabled' => true,
            'card_overlay' => [
                'blur' => 'md',
                'opacity' => 80,
            ],
            'modal_backdrop' => [
                'blur' => 'sm',
                'opacity' => 50,
            ],
        ],
    ],
],
```

### Options

#### `enabled`

Enable or disable glass effects globally:

```php
'glass_effects' => [
    'enabled' => env('MEDIA_GLASS_EFFECTS', true),
],
```

#### `card_overlay.blur`

Blur intensity for card overlays:

| Value | Effect |
|-------|--------|
| `sm` | Subtle blur (4px) |
| `md` | Medium blur (12px) - default |
| `lg` | Strong blur (16px) |
| `xl` | Very strong blur (24px) |
| `2xl` | Maximum blur (40px) |

```php
'card_overlay' => [
    'blur' => 'lg',
],
```

#### `card_overlay.opacity`

Background opacity (0-100):

```php
'card_overlay' => [
    'opacity' => 80, // 80% opaque
],
```

Lower values = more transparent, higher values = more opaque.

#### `modal_backdrop.blur`

Blur intensity for modal backdrops:

```php
'modal_backdrop' => [
    'blur' => 'sm',
],
```

#### `modal_backdrop.opacity`

Modal backdrop opacity:

```php
'modal_backdrop' => [
    'opacity' => 50, // 50% opaque
],
```

## Programmatic Control

### Runtime Configuration

Override settings at runtime:

```php
config([
    'artisanpack.media.ui.glass_effects.enabled' => false,
]);
```

### Component-Level Control

Override per-component:

```blade
{{-- Enable glass on a specific card --}}
<x-artisanpack-card :glass="true">
    Glass enabled
</x-artisanpack-card>

{{-- Disable glass on a specific card --}}
<x-artisanpack-card :glass="false">
    Glass disabled
</x-artisanpack-card>
```

### Custom Glass Classes

Apply custom glass styling:

```blade
<div class="glass-custom">
    Custom glass container
</div>
```

```css
.glass-custom {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
}
```

## Best Practices

### Background Requirements

Glass effects work best with colorful backgrounds:

```blade
{{-- Good: Gradient background --}}
<div class="bg-gradient-to-br from-primary/30 to-secondary/30 p-6">
    <livewire:media-statistics :glass="true" />
</div>

{{-- Good: Image background --}}
<div class="relative">
    <img src="/hero.jpg" class="absolute inset-0 w-full h-full object-cover" />
    <div class="relative z-10 p-6">
        <livewire:media-statistics :glass="true" />
    </div>
</div>

{{-- Less effective: Solid white background --}}
<div class="bg-white p-6">
    <livewire:media-statistics :glass="true" />
</div>
```

### Contrast for Readability

Ensure text remains readable:

```blade
{{-- Add text shadow for better readability --}}
<x-artisanpack-card :glass="true" class="text-shadow">
    <h2 class="text-white drop-shadow-lg">Title</h2>
    <p class="text-white/90 drop-shadow">Content</p>
</x-artisanpack-card>
```

### Performance

Glass effects use `backdrop-filter` which can impact performance on older devices:

```blade
{{-- Conditionally disable on low-powered devices --}}
@if(!$isLowPowerDevice)
    <livewire:media-statistics :glass="true" />
@else
    <livewire:media-statistics :glass="false" />
@endif
```

### Browser Support

`backdrop-filter` is supported in:
- Chrome 76+
- Firefox 103+
- Safari 9+
- Edge 79+

For unsupported browsers, the component falls back to a semi-transparent solid background.

## Styling Examples

### Dashboard with Glass Cards

```blade
<div class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-8">
    <h1 class="text-3xl font-bold text-white mb-8">Media Dashboard</h1>

    <livewire:media-statistics
        :glass="true"
        :show-sparklines="true"
    />
</div>
```

### Modal with Glass Backdrop

```blade
<x-artisanpack-modal
    wire:model="showModal"
    :glass-backdrop="true"
    :backdrop-blur="'md'"
    :backdrop-opacity="60"
>
    <x-slot:title>Select Media</x-slot:title>
    ...
</x-artisanpack-modal>
```

### Card Overlay on Media Grid

```blade
<div class="relative group">
    <img src="{{ $media->thumbnail_url }}" class="w-full aspect-square object-cover" />

    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity">
        <x-artisanpack-card :glass="true" class="absolute bottom-0 left-0 right-0 m-2">
            <p class="text-white text-sm truncate">{{ $media->title }}</p>
        </x-artisanpack-card>
    </div>
</div>
```

### Dark Mode Support

```blade
<x-artisanpack-card
    :glass="true"
    class="bg-white/10 dark:bg-black/20"
>
    Content adapts to dark mode
</x-artisanpack-card>
```

## Customizing via CSS

Override default glass styles:

```css
/* Custom glass effect */
[data-glass="true"] {
    --glass-blur: 20px;
    --glass-opacity: 0.15;
    --glass-border-opacity: 0.25;

    background: rgba(255, 255, 255, var(--glass-opacity));
    backdrop-filter: blur(var(--glass-blur));
    border: 1px solid rgba(255, 255, 255, var(--glass-border-opacity));
}

/* Stronger glass for headers */
.glass-strong {
    --glass-opacity: 0.25;
    --glass-blur: 24px;
}

/* Subtle glass for overlays */
.glass-subtle {
    --glass-opacity: 0.08;
    --glass-blur: 8px;
}
```

## Tailwind CSS Integration

Use Tailwind's backdrop utilities directly:

```blade
<div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
    Manual glass effect with Tailwind
</div>
```

## Next Steps

- [Media Statistics](Statistics) - KPI dashboard component
- [Configuration](Installation-Configuration#ui-settings-v11) - All UI options
- [Customization](Integration-Customization) - General customization guide
