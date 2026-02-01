---
title: Working with Models
---

# Working with Models

The Media Library provides three main models for working with media, folders, and tags. This guide covers advanced usage patterns and query optimization.

## Media Model

The `Media` model represents uploaded files.

### Basic Usage

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

// Find by ID
$media = Media::find(1);

// Get all media
$allMedia = Media::all();

// Paginate results
$media = Media::paginate(15);
```

### Query Scopes

#### images()

Get only image files.

```php
$images = Media::images()->get();
```

#### videos()

Get only video files.

```php
$videos = Media::videos()->get();
```

#### audios()

Get only audio files.

```php
$audioFiles = Media::audios()->get();
```

#### documents()

Get only document files.

```php
$documents = Media::documents()->get();
```

#### byType()

Filter by specific MIME type.

```php
$pdfFiles = Media::byType('application/pdf')->get();
$jpegImages = Media::byType('image/jpeg')->get();
```

#### inFolder()

Get media in a specific folder.

```php
$folderMedia = Media::inFolder($folderId)->get();

// Include subfolders
$allMedia = Media::inFolderRecursive($folderId)->get();
```

#### withTag()

Get media with a specific tag.

```php
$featured = Media::withTag('featured')->get();
$products = Media::withTag('products')->get();
```

### Relationships

#### folder

The folder this media belongs to.

```php
$media = Media::with('folder')->find(1);
echo $media->folder->name;
```

#### uploadedBy

The user who uploaded this media.

```php
$media = Media::with('uploadedBy')->find(1);
echo $media->uploadedBy->name;
```

#### tags

Tags associated with this media.

```php
$media = Media::with('tags')->find(1);

foreach ($media->tags as $tag) {
    echo $tag->name;
}
```

### URL Methods

#### url()

Get the original file URL.

```php
$originalUrl = $media->url();
```

#### imageUrl()

Get a specific image size URL.

```php
$thumbnail = $media->imageUrl('thumbnail');
$medium = $media->imageUrl('medium');
$large = $media->imageUrl('large');
$custom = $media->imageUrl('hero-banner');
```

### Display Methods

#### displayImage()

Generate an HTML img tag with proper escaping.

```php
// Basic usage
echo $media->displayImage();

// With size
echo $media->displayImage('large');

// With attributes
echo $media->displayImage('thumbnail', [
    'class' => 'img-fluid rounded',
    'loading' => 'lazy',
    'data-id' => $media->id,
]);
```

Outputs:
```html
<img src="..." alt="..." class="img-fluid rounded" loading="lazy" data-id="123">
```

### Type Checking

#### isImage()

Check if media is an image.

```php
if ($media->isImage()) {
    echo $media->imageUrl('large');
}
```

#### isVideo()

Check if media is a video.

```php
if ($media->isVideo()) {
    echo '<video src="' . $media->url() . '" controls></video>';
}
```

#### isAudio()

Check if media is audio.

```php
if ($media->isAudio()) {
    echo '<audio src="' . $media->url() . '" controls></audio>';
}
```

### Utility Methods

#### humanFileSize()

Get human-readable file size.

```php
echo $media->humanFileSize(); // "2.5 MB"
```

#### getTypeCategory()

Get media category (image, video, audio, document, other).

```php
$category = $media->getTypeCategory(); // "image"
```

## MediaFolder Model

The `MediaFolder` model represents hierarchical folders.

### Creating Folders

```php
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;

$folder = MediaFolder::create([
    'name' => 'Products',
    'slug' => 'products', // Auto-generated if not provided
    'description' => 'Product images and assets',
    'parent_id' => null, // Root folder
    'created_by' => auth()->id(),
]);
```

### Relationships

#### parent

The parent folder.

```php
$folder = MediaFolder::with('parent')->find(1);

if ($folder->parent) {
    echo "Parent: " . $folder->parent->name;
}
```

#### children

Child folders.

```php
$folder = MediaFolder::with('children')->find(1);

foreach ($folder->children as $child) {
    echo $child->name;
}
```

#### media

Media in this folder.

```php
$folder = MediaFolder::with('media')->find(1);
echo "Media count: " . $folder->media->count();
```

#### creator

The user who created this folder.

```php
$folder = MediaFolder::with('creator')->find(1);
echo "Created by: " . $folder->creator->name;
```

### Hierarchy Methods

#### fullPath()

Get the full path including ancestors.

```php
$path = $folder->fullPath();
// "Products/Electronics/Laptops"
```

#### moveTo()

Move folder to a new parent.

```php
$folder->moveTo($newParentId);

// Move to root
$folder->moveTo(null);
```

#### descendants()

Get all descendant folders.

```php
$descendants = $folder->descendants();

foreach ($descendants as $descendant) {
    echo $descendant->fullPath();
}
```

## MediaTag Model

The `MediaTag` model represents tags for categorizing media.

### Creating Tags

```php
use ArtisanPackUI\MediaLibrary\Models\MediaTag;

$tag = MediaTag::create([
    'name' => 'Featured',
    'slug' => 'featured', // Auto-generated if not provided
    'description' => 'Featured content for homepage',
]);
```

### Relationships

#### media

Get all media with this tag.

```php
$tag = MediaTag::with('media')->find(1);

foreach ($tag->media as $media) {
    echo $media->title;
}
```

### Utility Methods

#### mediaCount()

Get the number of media items with this tag.

```php
$count = $tag->mediaCount();
echo "Media with this tag: {$count}";
```

### Attaching/Detaching

```php
$tag = MediaTag::find(1);

// Attach to media
$tag->media()->attach([1, 2, 3]);

// Detach from media
$tag->media()->detach([2]);

// Sync (replaces existing)
$tag->media()->sync([1, 3, 4]);

// Toggle
$tag->media()->toggle([3, 5]);
```

## Advanced Queries

### Eager Loading

Prevent N+1 queries by eager loading relationships:

```php
$media = Media::with(['folder', 'uploadedBy', 'tags'])
    ->paginate(15);
```

### Filtering and Searching

```php
$results = Media::query()
    ->where('mime_type', 'like', 'image/%')
    ->where('file_size', '<', 1024000) // < 1 MB
    ->where(function ($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('file_name', 'like', "%{$search}%");
    })
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

### Complex Folder Queries

```php
// Get all media in a folder and subfolders
$allMedia = Media::whereHas('folder', function ($q) use ($folderId) {
    $folder = MediaFolder::find($folderId);
    $descendants = $folder->descendants()->pluck('id')->push($folderId);
    $q->whereIn('id', $descendants);
})->get();

// Get folders without media
$emptyFolders = MediaFolder::doesntHave('media')->get();

// Get folders created by specific user
$userFolders = MediaFolder::where('created_by', $userId)->get();
```

### Tag Combinations

```php
// Media with all specified tags
$media = Media::whereHas('tags', function ($q) {
    $q->whereIn('slug', ['featured', 'published']);
}, '=', 2)->get();

// Media with any of specified tags
$media = Media::whereHas('tags', function ($q) {
    $q->whereIn('slug', ['draft', 'pending']);
})->get();
```

## Next Steps

- Learn about [Livewire Components](Livewire-Components)
- Review [API Endpoints](Api-Endpoints)
- Explore [Customization](Integration-Customization) options
