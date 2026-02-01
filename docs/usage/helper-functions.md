---
title: Helper Functions
---

# Helper Functions

The Media Library package provides convenient helper functions for common operations. These functions offer a simpler API compared to working directly with models and services.

## Upload Functions

### apUploadMedia()

Upload a media file with metadata.

**Signature:**
```php
apUploadMedia(UploadedFile $file, array $options = []): Media
```

**Parameters:**
- `$file` - The uploaded file instance
- `$options` - Optional metadata array

**Options:**
- `title` - Media title
- `alt_text` - Alternative text for accessibility
- `caption` - Caption text
- `description` - Detailed description
- `folder_id` - Folder ID to organize into
- `tags` - Array of tag slugs or IDs

**Example:**
```php
use Illuminate\Http\UploadedFile;

$file = $request->file('upload');

$media = apUploadMedia($file, [
    'title' => 'Product Photo',
    'alt_text' => 'Red sneakers on white background',
    'caption' => 'Spring 2025 Collection',
    'folder_id' => 5,
    'tags' => ['featured', 'products'],
]);

echo "Uploaded media ID: {$media->id}";
```

**Returns:** `Media` model instance

**Throws:** `ValidationException` if file is invalid

## Retrieval Functions

### apGetMedia()

Get a media item by ID.

**Signature:**
```php
apGetMedia(int $id): ?Media
```

**Example:**
```php
$media = apGetMedia(123);

if ($media) {
    echo $media->title;
    echo $media->url();
}
```

**Returns:** `Media` instance or `null` if not found

### apGetMediaUrl()

Get the URL for a media item.

**Signature:**
```php
apGetMediaUrl(int $id, ?string $size = null): ?string
```

**Parameters:**
- `$id` - Media ID
- `$size` - Image size (optional): 'thumbnail', 'medium', 'large', or custom size

**Examples:**
```php
// Original file
$url = apGetMediaUrl(123);

// Thumbnail
$thumbnail = apGetMediaUrl(123, 'thumbnail');

// Custom size
$hero = apGetMediaUrl(123, 'hero-banner');
```

**Returns:** URL string or `null` if media not found

## Deletion Functions

### apDeleteMedia()

Delete a media item and all its files.

**Signature:**
```php
apDeleteMedia(int $id): bool
```

**Example:**
```php
if (apDeleteMedia(123)) {
    echo "Media deleted successfully";
}
```

**Returns:** `true` on success, `false` on failure

**Note:** This performs a soft delete. Use `forceDelete()` on the model for permanent deletion.

## Image Size Functions

### apRegisterImageSize()

Register a custom image size.

**Signature:**
```php
apRegisterImageSize(string $name, int $width, int $height, bool $crop = false): void
```

**Parameters:**
- `$name` - Size identifier (e.g., 'product-thumbnail')
- `$width` - Maximum width in pixels
- `$height` - Maximum height in pixels
- `$crop` - Whether to crop (`true`) or resize proportionally (`false`)

**Example:**
```php
// In a service provider boot() method
apRegisterImageSize('product-grid', 400, 400, true);
apRegisterImageSize('hero-banner', 1920, 600, true);
apRegisterImageSize('blog-thumbnail', 600, 400, false);

// Use the custom size
$url = apGetMediaUrl($mediaId, 'product-grid');
```

## Usage Patterns

### Basic Upload Flow

```php
public function store(Request $request)
{
    $request->validate([
        'file' => 'required|file|max:10240',
        'title' => 'nullable|string|max:255',
    ]);

    try {
        $media = apUploadMedia($request->file('file'), [
            'title' => $request->input('title'),
            'alt_text' => $request->input('alt_text'),
        ]);

        return response()->json([
            'success' => true,
            'media' => [
                'id' => $media->id,
                'url' => apGetMediaUrl($media->id),
                'thumbnail' => apGetMediaUrl($media->id, 'thumbnail'),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
```

### Display with Fallback

```php
$mediaId = $post->featured_image_id;

if ($mediaId && $media = apGetMedia($mediaId)) {
    $imageUrl = apGetMediaUrl($mediaId, 'large');
} else {
    $imageUrl = asset('images/placeholder.jpg');
}

echo "<img src=\"{$imageUrl}\" alt=\"Post image\">";
```

### Multiple Uploads

```php
$uploadedMedia = [];

foreach ($request->file('files') as $file) {
    $media = apUploadMedia($file, [
        'folder_id' => $galleryFolderId,
        'tags' => ['gallery', $eventSlug],
    ]);

    $uploadedMedia[] = $media;
}

return response()->json([
    'count' => count($uploadedMedia),
    'media' => $uploadedMedia,
]);
```

### Conditional Image Sizes

```php
// Register sizes based on theme
if (config('theme.enable_retina')) {
    apRegisterImageSize('thumbnail-2x', 300, 300, true);
    apRegisterImageSize('medium-2x', 600, 600, false);
}

// Use responsive images
$media = apGetMedia($imageId);
?>
<img
    src="<?= apGetMediaUrl($imageId, 'thumbnail') ?>"
    srcset="
        <?= apGetMediaUrl($imageId, 'thumbnail') ?> 1x,
        <?= apGetMediaUrl($imageId, 'thumbnail-2x') ?> 2x
    "
    alt="<?= $media->alt_text ?>"
>
```

## Best Practices

### Always Validate Uploads

```php
$request->validate([
    'file' => [
        'required',
        'file',
        'max:10240', // 10 MB
        'mimes:jpg,jpeg,png,gif,webp',
    ],
]);
```

### Use Try-Catch for Error Handling

```php
try {
    $media = apUploadMedia($file, $options);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Handle validation errors
    return back()->withErrors($e->errors());
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Media upload failed', ['error' => $e->getMessage()]);
    return back()->with('error', 'Upload failed');
}
```

### Check Media Exists Before Using

```php
$media = apGetMedia($id);

if (!$media) {
    abort(404, 'Media not found');
}
```

### Clean Up After Deletion

```php
// Delete media and update reference
if (apDeleteMedia($post->featured_image_id)) {
    $post->update(['featured_image_id' => null]);
}
```

## Next Steps

- Learn about [Working with Models](Models) for advanced queries
- Explore [Livewire Components](Livewire-Components) for UI integration
- Review [API Endpoints](Api-Endpoints) for programmatic access
