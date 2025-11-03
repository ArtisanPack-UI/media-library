# Image Editing - Service Implementation

## Description

Create the ImageEditingService to perform basic image editing operations (crop, rotate, flip, resize) on media files. This service will generate edited versions while preserving originals.

## Acceptance Criteria

- [ ] Create ImageEditingService with editing methods
- [ ] Implement crop functionality
- [ ] Implement rotate functionality
- [ ] Implement flip (horizontal/vertical) functionality
- [ ] Implement resize functionality
- [ ] Save edited images as new versions or replace with backup
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Add comprehensive PHPDoc blocks
- [ ] Create comprehensive tests

## Technical Details

### ImageEditingService

```php
class ImageEditingService
{
    public function __construct(
        protected MediaStorageService $storage,
        protected MediaProcessingService $processing
    ) {}

    /**
     * Crop an image to specified dimensions.
     *
     * @param Media $media The media to crop
     * @param int $x X coordinate of crop area
     * @param int $y Y coordinate of crop area
     * @param int $width Width of crop area
     * @param int $height Height of crop area
     * @param bool $saveAsNew Whether to save as new media or update existing
     * @return Media The cropped media (new or updated)
     */
    public function crop(
        Media $media,
        int $x,
        int $y,
        int $width,
        int $height,
        bool $saveAsNew = false
    ): Media

    /**
     * Rotate an image by specified degrees.
     *
     * @param Media $media The media to rotate
     * @param int $degrees Rotation angle (90, 180, 270, or custom)
     * @param bool $saveAsNew Whether to save as new media or update existing
     * @return Media The rotated media
     */
    public function rotate(Media $media, int $degrees, bool $saveAsNew = false): Media

    /**
     * Flip an image horizontally or vertically.
     *
     * @param Media $media The media to flip
     * @param string $direction 'horizontal' or 'vertical'
     * @param bool $saveAsNew Whether to save as new media or update existing
     * @return Media The flipped media
     */
    public function flip(Media $media, string $direction, bool $saveAsNew = false): Media

    /**
     * Resize an image to specified dimensions.
     *
     * @param Media $media The media to resize
     * @param int|null $width Target width (null to maintain aspect ratio)
     * @param int|null $height Target height (null to maintain aspect ratio)
     * @param bool $crop Whether to crop to exact dimensions
     * @param bool $saveAsNew Whether to save as new media or update existing
     * @return Media The resized media
     */
    public function resize(
        Media $media,
        ?int $width = null,
        ?int $height = null,
        bool $crop = false,
        bool $saveAsNew = false
    ): Media

    /**
     * Apply multiple edits in sequence.
     *
     * @param Media $media The media to edit
     * @param array $operations Array of operations to perform
     * @param bool $saveAsNew Whether to save as new media or update existing
     * @return Media The edited media
     */
    public function applyEdits(Media $media, array $operations, bool $saveAsNew = false): Media

    /**
     * Create a backup of original image before editing.
     */
    protected function backupOriginal(Media $media): string

    /**
     * Restore from backup.
     */
    public function restoreOriginal(Media $media): bool

    /**
     * Update media dimensions after editing.
     */
    protected function updateMediaDimensions(Media $media, string $path): void

    /**
     * Regenerate thumbnails after editing.
     */
    protected function regenerateThumbnails(Media $media): void
}
```

### Edit Operation Format

```php
$operations = [
    ['type' => 'crop', 'x' => 10, 'y' => 10, 'width' => 500, 'height' => 500],
    ['type' => 'rotate', 'degrees' => 90],
    ['type' => 'flip', 'direction' => 'horizontal'],
    ['type' => 'resize', 'width' => 1024, 'height' => 768, 'crop' => false],
];
```

### Configuration

Add to config file:

```php
// config/media.php
return [
    // ... existing config ...

    /*
    |--------------------------------------------------------------------------
    | Image Editing
    |--------------------------------------------------------------------------
    */
    'editing' => [
        'create_backups' => env('MEDIA_EDITING_CREATE_BACKUPS', true),
        'backup_directory' => 'backups',
        'allowed_operations' => ['crop', 'rotate', 'flip', 'resize'],
    ],
];
```

## Dependencies

- ImageOptimizationService must exist (Phase 3)
- MediaProcessingService must exist (Phase 3)

## Testing Requirements

- [ ] Test crop() with various crop areas
- [ ] Test crop() maintains image quality
- [ ] Test rotate() with 90, 180, 270 degrees
- [ ] Test flip() horizontal and vertical
- [ ] Test resize() with aspect ratio preservation
- [ ] Test resize() with crop mode
- [ ] Test applyEdits() with multiple operations
- [ ] Test saveAsNew creates new media record
- [ ] Test updating existing media
- [ ] Test backup creation and restoration
- [ ] Test thumbnail regeneration after edits
- [ ] Test dimension updates after edits
- [ ] Create ImageEditingServiceTest with 20+ tests

## Notes

- Use Intervention Image for all editing operations
- When updating existing media:
  1. Create backup of original
  2. Apply edits
  3. Save edited version
  4. Regenerate all thumbnails
  5. Update media dimensions
- When creating new media:
  1. Apply edits to copy
  2. Create new Media record
  3. Link to original via metadata
  4. Generate thumbnails for new media
- Consider adding these helper functions:
  ```php
  if (!function_exists('apCropMedia')) {
      function apCropMedia(int $mediaId, int $x, int $y, int $width, int $height, bool $saveAsNew = false): ?Media
      {
          $media = Media::find($mediaId);
          if (null === $media || !$media->isImage()) {
              return null;
          }

          return app(ImageEditingService::class)->crop($media, $x, $y, $width, $height, $saveAsNew);
      }
  }
  ```

## File Locations

- Service: `src/Services/ImageEditingService.php`
- Tests: `tests/Unit/ImageEditingServiceTest.php`
- Helpers: `src/helpers.php` (add to existing file)
