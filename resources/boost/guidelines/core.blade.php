## ArtisanPack UI Media Library

This package provides comprehensive media management functionality for Laravel applications, including image processing, folder organization, tagging, and modern image format conversion (WebP/AVIF).

### Installation

Install via Composer and run migrations:

@verbatim
<code-snippet name="Install the package" lang="bash">
composer require artisanpack-ui/media-library
php artisan migrate
</code-snippet>
@endverbatim

### Core Features

- **Media Upload & Management**: Upload images, videos, audio files, and documents with automatic processing
- **Folder Organization**: Organize media into hierarchical folders
- **Tag System**: Tag media items for categorization and filtering
- **Image Processing**: Automatic thumbnail generation in multiple sizes with WebP/AVIF conversion
- **Livewire Components**: Pre-built UI components for media management
- **Permission-Based Access**: Granular capability-based permissions

### Helper Functions

The package provides global helper functions prefixed with `ap`:

@verbatim
<code-snippet name="Upload media" lang="php">
use Illuminate\Http\UploadedFile;

// Upload a file with options
$media = apUploadMedia($request->file('image'), [
    'title' => 'Product Image',
    'alt_text' => 'Product photo for accessibility',
    'folder_id' => 1,
]);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Get media and URLs" lang="php">
// Get media by ID
$media = apGetMedia($mediaId);

// Get media URL for specific size
$url = apGetMediaUrl($mediaId, 'thumbnail'); // sizes: thumbnail, medium, large, full

// Display image with attributes
echo $media->displayImage('large', ['class' => 'img-fluid rounded']);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Register custom image sizes" lang="php">
// Register in AppServiceProvider boot method
apRegisterImageSize('product-thumb', 300, 300, true);
apRegisterImageSize('hero', 1920, 1080, false);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Delete media" lang="php">
// Deletes both the database record and all files
apDeleteMedia($mediaId);
</code-snippet>
@endverbatim

### Working with Models

@verbatim
<code-snippet name="Query media using scopes" lang="php">
use ArtisanPackUI\MediaLibrary\Models\Media;

// Get images only
$images = Media::images()->get();

// Get videos in a specific folder
$videos = Media::videos()->inFolder($folderId)->get();

// Get media with specific tag
$tagged = Media::withTag('featured')->get();

// Filter by type
$pdfs = Media::byType('application/pdf')->get();
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Access media properties and relationships" lang="php">
// Check media type
if ($media->isImage()) { /* ... */ }
if ($media->isVideo()) { /* ... */ }
if ($media->isAudio()) { /* ... */ }
if ($media->isDocument()) { /* ... */ }

// Get URLs
$fullUrl = $media->url();
$thumbUrl = $media->imageUrl('thumbnail');

// Get all generated sizes
$allSizes = $media->getImageSizes(); // ['thumbnail' => 'url', 'medium' => 'url', ...]

// Get human-readable file size
$size = $media->humanFileSize(); // "1.5 MB"

// Access relationships
$folder = $media->folder;
$tags = $media->tags;
$uploader = $media->uploadedBy;
</code-snippet>
@endverbatim

### Livewire Components

@verbatim
<code-snippet name="MediaModal - Select media with modal" lang="blade">
{{-- Single select mode --}}
<livewire:media-modal
    :multi-select="false"
    context="featured-image" />

{{-- Multi-select with limit --}}
<livewire:media-modal
    :multi-select="true"
    :max-selections="5"
    context="gallery-images" />

{{-- Listen for selected media --}}
<script>
Livewire.on('media-selected', (event) => {
    const media = event.media;
    const context = event.context;

    if (context === 'featured-image') {
        // Handle single selection
        console.log('Selected:', media[0]);
    }
});
</script>

{{-- Open modal via JavaScript --}}
<button onclick="Livewire.dispatch('open-media-modal', { context: 'featured-image' })">
    Select Image
</button>
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Full media library page" lang="blade">
{{-- Complete media management interface --}}
<livewire:media-library />
</code-snippet>
@endverbatim

### Folders and Tags

@verbatim
<code-snippet name="Work with folders" lang="php">
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;

// Create folder
$folder = MediaFolder::create([
    'name' => 'Product Images',
    'slug' => 'product-images',
    'parent_id' => null, // or parent folder ID for nested folders
]);

// Get folder media
$media = $folder->media()->get();
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Work with tags" lang="php">
use ArtisanPackUI\MediaLibrary\Models\MediaTag;

// Create tag
$tag = MediaTag::create([
    'name' => 'Featured',
    'slug' => 'featured',
]);

// Attach tag to media
$media->tags()->attach($tag->id);

// Get media by tag
$featuredMedia = Media::withTag('featured')->get();
</code-snippet>
@endverbatim

### Configuration

Publish and customize configuration:

@verbatim
<code-snippet name="Publish configuration" lang="bash">
php artisan vendor:publish --tag=media-config
</code-snippet>
@endverbatim

Key configuration options in `config/artisanpack/media.php`:
- `image_sizes`: Define custom image sizes
- `allowed_mime_types`: Restrict allowed file types
- `max_file_size`: Maximum upload size in KB
- `disk`: Storage disk to use (default: 'public')
- `enable_webp`: Enable WebP conversion
- `enable_avif`: Enable AVIF conversion

### API Endpoints

The package provides RESTful API endpoints:

@verbatim
<code-snippet name="Use media API endpoints" lang="javascript">
// List media
GET /api/media?folder_id=1&type=image&search=logo

// Upload media
POST /api/media
Content-Type: multipart/form-data
{
    file: [File],
    title: "Image Title",
    alt_text: "Alt text",
    folder_id: 1
}

// Get single media
GET /api/media/{id}

// Update media
PUT /api/media/{id}
{
    title: "Updated Title",
    alt_text: "Updated alt text"
}

// Delete media
DELETE /api/media/{id}
</code-snippet>
@endverbatim

### Best Practices

1. **Always set alt_text** for images to ensure accessibility
2. **Use appropriate image sizes** instead of full-size images for better performance
3. **Register custom image sizes** in AppServiceProvider for consistent sizing across your app
4. **Use folder organization** to keep media organized by feature/section
5. **Check permissions** using Laravel's authorization features with MediaPolicy
6. **Use MediaModal context** when working with multiple media selectors on the same page
7. **Enable WebP/AVIF** in config for better performance (requires GD/Imagick support)
