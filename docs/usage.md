# Usage Guide

This guide provides comprehensive examples of how to use the ArtisanPack UI Media Library package in your Laravel application.

## Basic File Upload

### Simple Upload Example

```php
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;

// Inject MediaManager into your controller
public function upload(Request $request, MediaManager $mediaManager)
{
    $file = $request->file('upload');
    
    $media = $mediaManager->upload(
        file: $file,
        altText: 'Description of the image',
        caption: 'Optional caption',
        isDecorative: false,
        metadata: ['width' => 1920, 'height' => 1080]
    );
    
    return response()->json(['media' => $media]);
}
```

### Upload with Validation

```php
public function uploadWithValidation(Request $request, MediaManager $mediaManager)
{
    $request->validate([
        'upload' => 'required|file|mimes:jpeg,png,gif,webp|max:10240', // 10MB max
        'alt_text' => 'required|string|max:255',
        'caption' => 'nullable|string|max:500',
        'is_decorative' => 'boolean',
    ]);
    
    $media = $mediaManager->upload(
        file: $request->file('upload'),
        altText: $request->input('alt_text'),
        caption: $request->input('caption'),
        isDecorative: $request->boolean('is_decorative'),
        metadata: $request->input('metadata', [])
    );
    
    return response()->json([
        'success' => true,
        'media' => $media,
        'url' => Storage::url($media->file_path)
    ]);
}
```

## Retrieving Media

### Basic Media Retrieval

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

// Get all media for the current user
$userMedia = Media::where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->paginate(15);

// Get a specific media item
$media = Media::findOrFail($id);
```

### Retrieving Media with Relationships

```php
// Get media with categories and tags
$mediaItems = Media::with(['mediaCategories', 'mediaTags'])
    ->where('user_id', auth()->id())
    ->paginate(15);

// Get media by category
$categoryMedia = Media::whereHas('mediaCategories', function ($query) {
    $query->where('slug', 'photos');
})->get();

// Get media by tag
$taggedMedia = Media::whereHas('mediaTags', function ($query) {
    $query->where('slug', 'featured');
})->get();

// Get media by multiple filters
$filteredMedia = Media::with(['mediaCategories', 'mediaTags'])
    ->where('user_id', auth()->id())
    ->where('mime_type', 'LIKE', 'image/%')
    ->whereHas('mediaCategories', function ($query) {
        $query->whereIn('slug', ['photos', 'graphics']);
    })
    ->orderBy('created_at', 'desc')
    ->get();
```

## Working with Categories and Tags

### Creating Categories

```php
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;

// Create a category
$category = MediaCategory::create([
    'name' => 'Product Images',
    'slug' => 'product-images',
    'description' => 'Images for product listings'
]);

// Create multiple categories
$categories = [
    ['name' => 'Blog Posts', 'slug' => 'blog-posts', 'description' => 'Blog post images'],
    ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Marketing materials'],
    ['name' => 'User Avatars', 'slug' => 'user-avatars', 'description' => 'User profile pictures'],
];

foreach ($categories as $categoryData) {
    MediaCategory::create($categoryData);
}
```

### Creating Tags

```php
use ArtisanPackUI\MediaLibrary\Models\MediaTag;

// Create a tag
$tag = MediaTag::create([
    'name' => 'Featured',
    'slug' => 'featured',
    'description' => 'Featured content'
]);

// Create multiple tags
$tags = [
    ['name' => 'Summer 2024', 'slug' => 'summer-2024', 'description' => 'Summer campaign'],
    ['name' => 'High Priority', 'slug' => 'high-priority', 'description' => 'Important content'],
    ['name' => 'Draft', 'slug' => 'draft', 'description' => 'Work in progress'],
];

foreach ($tags as $tagData) {
    MediaTag::create($tagData);
}
```

### Attaching Categories and Tags to Media

```php
// Attach to media during upload
$media = $mediaManager->upload(
    file: $file,
    altText: 'Product photo',
    caption: 'New product showcase',
    isDecorative: false,
    metadata: []
);

// Attach categories
$media->mediaCategories()->attach([1, 2]); // Category IDs

// Attach tags
$media->mediaTags()->attach([3, 4]); // Tag IDs

// Or use sync to replace existing relationships
$media->mediaCategories()->sync([1, 3]);
$media->mediaTags()->sync([2, 5, 6]);

// Detach specific items
$media->mediaCategories()->detach(2);
$media->mediaTags()->detach([3, 4]);
```

## Using the MediaManager

The `MediaManager` class provides a high-level interface for media operations.

### Basic MediaManager Usage

```php
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;

class MediaController extends Controller
{
    public function __construct(private MediaManager $mediaManager)
    {
    }
    
    public function index()
    {
        return $this->mediaManager->all(perPage: 20);
    }
    
    public function show(int $id)
    {
        return $this->mediaManager->get($id);
    }
    
    public function update(int $id, array $data)
    {
        return $this->mediaManager->update($id, $data);
    }
    
    public function delete(int $id)
    {
        return $this->mediaManager->delete($id);
    }
}
```

### Advanced MediaManager Operations

```php
// Get media with filters
$media = $this->mediaManager->all(
    perPage: 15,
    filters: [
        'mime_type' => 'image/jpeg',
        'category' => 'photos',
        'tag' => 'featured'
    ]
);

// Update media with relationships
$updatedMedia = $this->mediaManager->update($id, [
    'alt_text' => 'Updated description',
    'caption' => 'Updated caption',
    'is_decorative' => false,
    'media_categories' => [1, 2, 3],
    'media_tags' => [4, 5]
]);

// Bulk operations
$this->mediaManager->bulkDelete([1, 2, 3, 4]);
$this->mediaManager->bulkUpdateCategories([1, 2, 3], [5, 6]);
```

## Working with Files and Storage

### Getting File URLs

```php
use Illuminate\Support\Facades\Storage;

$media = Media::find(1);

// Get the file URL
$url = Storage::url($media->file_path);

// For S3 or other cloud storage, get temporary URLs
$temporaryUrl = Storage::temporaryUrl($media->file_path, now()->addMinutes(5));

// Check if file exists
if (Storage::exists($media->file_path)) {
    // File exists, safe to display
    $url = Storage::url($media->file_path);
}
```

### File Information

```php
$media = Media::find(1);

// Access file information
echo $media->original_filename; // Original name when uploaded
echo $media->filename; // Stored filename
echo $media->file_size; // Size in bytes
echo $media->mime_type; // MIME type
echo $media->file_path; // Full storage path

// Format file size for display
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

echo formatFileSize($media->file_size); // "2.5 MB"
```

## Blade Template Examples

### Displaying Media in Views

```blade
{{-- Single media item --}}
<div class="media-item">
    @if($media->mime_type && str_starts_with($media->mime_type, 'image/'))
        <img src="{{ Storage::url($media->file_path) }}" 
             alt="{{ $media->alt_text }}" 
             class="img-fluid"
             @if($media->is_decorative) aria-hidden="true" @endif>
    @else
        <a href="{{ Storage::url($media->file_path) }}" 
           target="_blank" 
           class="file-link">
            {{ $media->original_filename }}
        </a>
    @endif
    
    @if($media->caption)
        <p class="caption">{{ $media->caption }}</p>
    @endif
</div>

{{-- Media gallery --}}
<div class="media-gallery row">
    @foreach($mediaItems as $media)
        <div class="col-md-4 mb-3">
            <div class="card">
                @if(str_starts_with($media->mime_type, 'image/'))
                    <img src="{{ Storage::url($media->file_path) }}" 
                         alt="{{ $media->alt_text }}" 
                         class="card-img-top">
                @endif
                
                <div class="card-body">
                    <h6 class="card-title">{{ $media->original_filename }}</h6>
                    @if($media->caption)
                        <p class="card-text">{{ $media->caption }}</p>
                    @endif
                    
                    {{-- Categories --}}
                    @if($media->mediaCategories->count() > 0)
                        <div class="categories">
                            @foreach($media->mediaCategories as $category)
                                <span class="badge bg-primary">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- Tags --}}
                    @if($media->mediaTags->count() > 0)
                        <div class="tags mt-2">
                            @foreach($media->mediaTags as $tag)
                                <span class="badge bg-secondary">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
```

### Upload Form

```blade
<form action="{{ route('media.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="mb-3">
        <label for="upload" class="form-label">Choose File</label>
        <input type="file" class="form-control @error('upload') is-invalid @enderror" 
               id="upload" name="upload" accept="image/*,.pdf,.txt" required>
        @error('upload')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3">
        <label for="alt_text" class="form-label">Alt Text</label>
        <input type="text" class="form-control @error('alt_text') is-invalid @enderror" 
               id="alt_text" name="alt_text" value="{{ old('alt_text') }}" required>
        @error('alt_text')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3">
        <label for="caption" class="form-label">Caption (Optional)</label>
        <textarea class="form-control @error('caption') is-invalid @enderror" 
                  id="caption" name="caption">{{ old('caption') }}</textarea>
        @error('caption')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="is_decorative" name="is_decorative" value="1">
        <label class="form-check-label" for="is_decorative">
            This image is decorative (no alt text needed)
        </label>
    </div>
    
    <div class="mb-3">
        <label for="categories" class="form-label">Categories</label>
        <select multiple class="form-select" id="categories" name="media_categories[]">
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="mb-3">
        <label for="tags" class="form-label">Tags</label>
        <select multiple class="form-select" id="tags" name="media_tags[]">
            @foreach($tags as $tag)
                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
            @endforeach
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Upload Media</button>
</form>
```

## Next Steps

- Explore the [API documentation](api.md) for endpoint details
- Review [performance optimization](performance.md) guidelines
- Check the [migration guide](migration.md) if upgrading from another system