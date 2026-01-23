---
title: Streaming Uploads
---

# Streaming Uploads

The Media Library v1.1 introduces real-time upload progress using Livewire 4's `wire:stream` feature, with automatic fallback to polling for Livewire 3 compatibility.

## Overview

Streaming uploads provide:
- **Real-time progress updates** - Smooth progress bar animations
- **No polling overhead** - Server pushes updates to the client
- **Automatic fallback** - Works seamlessly on Livewire 3
- **Configurable** - Enable/disable via configuration

## How It Works

### Livewire 4 (wire:stream)

On Livewire 4, the upload component uses `wire:stream` to push progress updates directly to the browser without polling:

```blade
<div wire:stream="uploadProgress">
    <div class="progress-bar" style="width: {{ $uploadProgress }}%"></div>
</div>
```

The server streams progress updates as the file uploads:

```php
$this->stream(
    to: 'uploadProgress',
    content: $progress,
    replace: true
);
```

### Livewire 3 (Polling Fallback)

On Livewire 3, the component automatically falls back to polling at a configurable interval:

```blade
<div wire:poll.{{ $fallbackInterval }}ms="refreshProgress">
    <div class="progress-bar" style="width: {{ $uploadProgress }}%"></div>
</div>
```

## Configuration

Configure streaming in `config/artisanpack.php`:

```php
'media' => [
    'features' => [
        'streaming_upload' => true,
        'streaming_fallback_interval' => 500,
    ],
],
```

### Options

#### `streaming_upload`

Enable or disable streaming uploads:

```php
'features' => [
    'streaming_upload' => env('MEDIA_STREAMING_UPLOAD', true),
],
```

When disabled, the component always uses polling regardless of Livewire version.

#### `streaming_fallback_interval`

Polling interval in milliseconds for Livewire 3:

```php
'features' => [
    'streaming_fallback_interval' => 500, // Poll every 500ms
],
```

Lower values = smoother progress but more server requests.

## Using the Upload Component

### Basic Usage

```blade
<livewire:media-upload-zone />
```

The upload zone automatically detects Livewire version and uses the appropriate progress method.

### With Streaming

```blade
<livewire:media-upload-zone
    :stream-progress="true"
/>
```

### Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `maxFileSize` | int | config value | Max file size in KB |
| `allowedTypes` | array | config value | Allowed MIME types |
| `multiple` | bool | `true` | Allow multiple files |
| `folderId` | int | `null` | Target folder |
| `streamProgress` | bool | `true` | Use streaming if available |

## StreamableUpload Trait

Add streaming upload support to your own components using the `StreamableUpload` trait:

```php
<?php

namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomUploader extends Component
{
    use WithFileUploads;
    use StreamableUpload;

    public $file;
    public int $uploadProgress = 0;

    public function upload(): void
    {
        if ($this->isStreamingEnabled()) {
            // Use streaming for progress
            $this->uploadWithStreaming($this->file);
        } else {
            // Use standard upload with polling
            $this->uploadWithPolling($this->file);
        }
    }

    protected function onUploadProgress(int $progress): void
    {
        if ($this->isStreamingEnabled()) {
            $this->stream(
                to: 'uploadProgress',
                content: $progress,
                replace: true
            );
        } else {
            $this->uploadProgress = $progress;
        }
    }
}
```

### Trait Methods

#### `isStreamingEnabled()`

Check if streaming is enabled:

```php
if ($this->isStreamingEnabled()) {
    // Use wire:stream
}
```

#### `getStreamingFallbackInterval()`

Get the fallback polling interval:

```php
$interval = $this->getStreamingFallbackInterval(); // 500
```

#### `isLivewire4OrHigher()`

Check Livewire version:

```php
if ($this->isLivewire4OrHigher()) {
    // Livewire 4+ specific code
}
```

## Custom Progress UI

### Blade Template

```blade
<div class="upload-container">
    @if($this->isStreamingEnabled())
        {{-- Streaming progress --}}
        <div wire:stream="uploadProgress" class="relative h-4 bg-base-200 rounded-full overflow-hidden">
            <div
                class="absolute inset-y-0 left-0 bg-primary transition-all duration-100"
                style="width: {{ $uploadProgress }}%"
            ></div>
        </div>
        <span wire:stream="uploadProgressText" class="text-sm">
            {{ $uploadProgress }}%
        </span>
    @else
        {{-- Polling progress --}}
        <div wire:poll.{{ $fallbackInterval }}ms="refreshProgress" class="relative h-4 bg-base-200 rounded-full overflow-hidden">
            <div
                class="absolute inset-y-0 left-0 bg-primary transition-all duration-300"
                style="width: {{ $uploadProgress }}%"
            ></div>
        </div>
        <span class="text-sm">{{ $uploadProgress }}%</span>
    @endif
</div>
```

### With Alpine.js Animation

```blade
<div
    x-data="{ progress: 0 }"
    x-init="
        Livewire.on('upload-progress', (data) => {
            progress = data.progress;
        })
    "
    class="relative h-4 bg-base-200 rounded-full overflow-hidden"
>
    <div
        class="absolute inset-y-0 left-0 bg-primary"
        :style="`width: ${progress}%; transition: width 0.1s ease-out`"
    ></div>
</div>
```

## Events

### `upload-started`

Dispatched when upload begins:

```javascript
Livewire.on('upload-started', (event) => {
    console.log('Upload started:', event.filename);
});
```

### `upload-progress`

Dispatched during upload (polling mode):

```javascript
Livewire.on('upload-progress', (event) => {
    console.log('Progress:', event.progress, '%');
});
```

### `upload-completed`

Dispatched when upload finishes:

```javascript
Livewire.on('upload-completed', (event) => {
    console.log('Upload completed:', event.mediaId);
});
```

### `upload-failed`

Dispatched on upload error:

```javascript
Livewire.on('upload-failed', (event) => {
    console.error('Upload failed:', event.message);
});
```

## Multiple File Uploads

Handle multiple files with individual progress:

```php
public array $files = [];
public array $uploadProgress = [];

public function uploadFiles(): void
{
    foreach ($this->files as $index => $file) {
        $this->uploadProgress[$index] = 0;

        // Upload with progress callback
        $this->uploadFile($file, function ($progress) use ($index) {
            $this->updateFileProgress($index, $progress);
        });
    }
}

protected function updateFileProgress(int $index, int $progress): void
{
    $this->uploadProgress[$index] = $progress;

    if ($this->isStreamingEnabled()) {
        $this->stream(
            to: "uploadProgress.{$index}",
            content: $progress,
            replace: true
        );
    }
}
```

```blade
@foreach($files as $index => $file)
    <div class="flex items-center gap-4">
        <span class="truncate flex-1">{{ $file->getClientOriginalName() }}</span>
        <div
            @if($this->isStreamingEnabled())
                wire:stream="uploadProgress.{{ $index }}"
            @else
                wire:poll.500ms
            @endif
            class="w-32 h-2 bg-base-200 rounded-full overflow-hidden"
        >
            <div
                class="h-full bg-primary"
                style="width: {{ $uploadProgress[$index] ?? 0 }}%"
            ></div>
        </div>
    </div>
@endforeach
```

## Performance Considerations

### Streaming Benefits

- No polling overhead
- Immediate updates
- Reduced server load
- Better user experience

### Polling Fallback

- Compatible with Livewire 3
- Configurable interval
- Graceful degradation

### Recommended Settings

| Scenario | Streaming | Fallback Interval |
|----------|-----------|-------------------|
| Small files (<5MB) | Enabled | 500ms |
| Large files (>50MB) | Enabled | 250ms |
| High traffic | Enabled | 1000ms |
| Livewire 3 only | Disabled | 500ms |

## Troubleshooting

### Progress Not Updating

1. Check Livewire version: `composer show livewire/livewire`
2. Verify streaming is enabled in config
3. Check browser console for errors
4. Ensure `wire:stream` target exists in template

### Choppy Progress

1. Decrease fallback interval
2. Ensure smooth CSS transitions
3. Use requestAnimationFrame for animations

### High Server Load

1. Increase fallback interval
2. Enable streaming on Livewire 4+
3. Consider chunked uploads for large files

## Next Steps

- [Table Export](./table-export.md) - Export media data
- [Livewire Components](./livewire-components.md) - All components
- [Configuration](../installation/configuration.md#feature-flags-v11) - Feature flags
