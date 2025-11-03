# Image Editing - Livewire Component

## Description

Create an interactive Livewire component with JavaScript-based image editing UI. Users should be able to crop, rotate, flip, and resize images with real-time preview before saving changes.

## Acceptance Criteria

- [ ] Create ImageEditor Livewire component
- [ ] Create interactive UI with crop tool
- [ ] Add rotate and flip buttons
- [ ] Add resize options
- [ ] Show real-time preview of edits
- [ ] Allow saving as new image or overwriting existing
- [ ] Integrate with existing Media Library UI
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create Blade view with inline CSS and JavaScript

## Technical Details

### ImageEditor Component

```php
class ImageEditor extends Component
{
    use Toast;

    public int $mediaId;
    public ?Media $media = null;
    public bool $isOpen = false;
    public string $mode = 'crop'; // crop, rotate, flip, resize

    // Crop data
    public int $cropX = 0;
    public int $cropY = 0;
    public int $cropWidth = 0;
    public int $cropHeight = 0;

    // Rotate data
    public int $rotation = 0;

    // Flip data
    public bool $flipHorizontal = false;
    public bool $flipVertical = false;

    // Resize data
    public ?int $resizeWidth = null;
    public ?int $resizeHeight = null;
    public bool $maintainAspectRatio = true;
    public bool $cropToFit = false;

    // Options
    public bool $saveAsNew = false;

    #[On('open-image-editor')]
    public function openEditor(int $mediaId): void
    {
        $this->mediaId = $mediaId;
        $this->media = Media::find($mediaId);

        if (null === $this->media || !$this->media->isImage()) {
            $this->addToast('Selected media is not an image.', 'error');
            return;
        }

        $this->resetEdits();
        $this->isOpen = true;
    }

    public function closeEditor(): void
    {
        $this->isOpen = false;
        $this->resetEdits();
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function updateCropData(int $x, int $y, int $width, int $height): void
    {
        $this->cropX = $x;
        $this->cropY = $y;
        $this->cropWidth = $width;
        $this->cropHeight = $height;
    }

    public function rotateImage(int $degrees): void
    {
        $this->rotation = ($this->rotation + $degrees) % 360;
    }

    public function flipImage(string $direction): void
    {
        if ('horizontal' === $direction) {
            $this->flipHorizontal = !$this->flipHorizontal;
        } else {
            $this->flipVertical = !$this->flipVertical;
        }
    }

    public function applyEdits(): void
    {
        try {
            $operations = $this->buildOperations();

            $editedMedia = app(ImageEditingService::class)->applyEdits(
                $this->media,
                $operations,
                $this->saveAsNew
            );

            $this->addToast('Image edited successfully.', 'success');
            $this->dispatch('media-updated', mediaId: $editedMedia->id);
            $this->closeEditor();
        } catch (Exception $e) {
            $this->addToast('Failed to edit image: ' . $e->getMessage(), 'error');
        }
    }

    public function restoreOriginal(): void
    {
        try {
            $restored = app(ImageEditingService::class)->restoreOriginal($this->media);

            if ($restored) {
                $this->addToast('Original image restored.', 'success');
                $this->dispatch('media-updated', mediaId: $this->mediaId);
                $this->closeEditor();
            } else {
                $this->addToast('No backup found to restore.', 'error');
            }
        } catch (Exception $e) {
            $this->addToast('Failed to restore: ' . $e->getMessage(), 'error');
        }
    }

    protected function buildOperations(): array
    {
        $operations = [];

        // Add crop operation
        if ('crop' === $this->mode && $this->cropWidth > 0 && $this->cropHeight > 0) {
            $operations[] = [
                'type' => 'crop',
                'x' => $this->cropX,
                'y' => $this->cropY,
                'width' => $this->cropWidth,
                'height' => $this->cropHeight,
            ];
        }

        // Add rotate operation
        if (0 !== $this->rotation) {
            $operations[] = [
                'type' => 'rotate',
                'degrees' => $this->rotation,
            ];
        }

        // Add flip operations
        if ($this->flipHorizontal) {
            $operations[] = ['type' => 'flip', 'direction' => 'horizontal'];
        }
        if ($this->flipVertical) {
            $operations[] = ['type' => 'flip', 'direction' => 'vertical'];
        }

        // Add resize operation
        if ('resize' === $this->mode && (null !== $this->resizeWidth || null !== $this->resizeHeight)) {
            $operations[] = [
                'type' => 'resize',
                'width' => $this->resizeWidth,
                'height' => $this->resizeHeight,
                'crop' => $this->cropToFit,
            ];
        }

        return $operations;
    }

    protected function resetEdits(): void
    {
        $this->mode = 'crop';
        $this->cropX = 0;
        $this->cropY = 0;
        $this->cropWidth = 0;
        $this->cropHeight = 0;
        $this->rotation = 0;
        $this->flipHorizontal = false;
        $this->flipVertical = false;
        $this->resizeWidth = null;
        $this->resizeHeight = null;
        $this->maintainAspectRatio = true;
        $this->cropToFit = false;
        $this->saveAsNew = false;
    }
}
```

### Blade View Features

The view should include:

1. **Modal Layout**: Full-screen or large modal for editing
2. **Image Canvas**: Display image with editing overlays
3. **Tool Tabs**: Crop, Rotate, Flip, Resize
4. **Crop Tool**:
   - Draggable/resizable crop area
   - Show dimensions
   - Preset aspect ratios (1:1, 4:3, 16:9, free)
5. **Rotate Tool**:
   - Buttons for 90°, 180°, 270° rotation
   - Visual rotation preview
6. **Flip Tool**:
   - Horizontal flip button
   - Vertical flip button
   - Visual flip preview
7. **Resize Tool**:
   - Width/height inputs
   - Lock aspect ratio toggle
   - Crop to fit toggle
   - Preset sizes (thumbnail, medium, large)
8. **Action Buttons**:
   - Apply Edits
   - Save as New (checkbox)
   - Restore Original
   - Cancel

### JavaScript Integration

Use Cropper.js or similar library for interactive cropping:

```blade
<script>
document.addEventListener('livewire:initialized', () => {
    let cropper = null;

    Livewire.on('open-image-editor', () => {
        // Initialize cropper
        const image = document.getElementById('editor-image');
        cropper = new Cropper(image, {
            viewMode: 1,
            crop(event) {
                @this.call('updateCropData',
                    Math.round(event.detail.x),
                    Math.round(event.detail.y),
                    Math.round(event.detail.width),
                    Math.round(event.detail.height)
                );
            },
        });
    });

    Livewire.on('close-image-editor', () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });
});
</script>
```

## Dependencies

- Task 08: Image editing service must be completed
- Cropper.js library (or similar) for interactive cropping

## Testing Requirements

- [ ] Test component opens with valid image
- [ ] Test component rejects non-image media
- [ ] Test crop mode updates crop data
- [ ] Test rotate buttons work
- [ ] Test flip buttons work
- [ ] Test resize inputs work
- [ ] Test applyEdits() calls service correctly
- [ ] Test saveAsNew option works
- [ ] Test restoreOriginal() works
- [ ] Test error handling
- [ ] Create ImageEditorComponentTest with 15+ tests

## Notes

- Consider using these JavaScript libraries:
  - **Cropper.js**: For interactive cropping (MIT license)
  - **Fabric.js**: For more advanced editing features
  - **Tui.ImageEditor**: Full-featured image editor (MIT license)
- The component should work entirely client-side for preview
- Only send final edit parameters to server when applying
- Add undo/redo functionality in future enhancement
- Consider adding filters (brightness, contrast, saturation) in future
- Make sure to include library assets via CDN or npm:
  ```blade
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
  ```

## File Locations

- Component: `src/Livewire/Components/ImageEditor.php`
- View: `resources/views/livewire/components/image-editor.blade.php`
- Tests: `tests/Feature/ImageEditorComponentTest.php`
