# Usage Tracking - Livewire Component

## Description

Create a Livewire component to display where media is being used throughout the application. This should be integrated into the Media Library UI and media detail views.

## Acceptance Criteria

- [ ] Create MediaUsageViewer component to display usage information
- [ ] Show all locations where media is used
- [ ] Display usage context and metadata
- [ ] Show last used timestamps
- [ ] Add "View" links to usage locations
- [ ] Add warning when attempting to delete used media
- [ ] Integrate into MediaLibrary and MediaItem components
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create Blade view with inline CSS

## Technical Details

### MediaUsageViewer Component

```php
class MediaUsageViewer extends Component
{
    public int $mediaId;
    public bool $showModal = false;

    #[Computed]
    public function usage()
    {
        return app(MediaUsageTracker::class)->getUsageFor($this->mediaId);
    }

    #[Computed]
    public function usageCount(): int
    {
        return $this->usage()->count();
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Format usable model for display.
     */
    protected function formatUsable(MediaUsage $usage): array
    {
        return [
            'type' => class_basename($usage->usable_type),
            'title' => $usage->usable->title ?? $usage->usable->name ?? 'Unknown',
            'url' => $this->getUsableUrl($usage->usable),
            'context' => $usage->context,
            'lastUsed' => $usage->last_used_at->diffForHumans(),
        ];
    }

    protected function getUsableUrl(Model $usable): ?string
    {
        // Generate appropriate URL based on model type
        // Can be customized via hooks
        return applyFilters('ap.media.usageUrl', null, $usable);
    }
}
```

### Blade View

The view should include:
- Modal/panel display of usage information
- Table or list of usage locations
- Icon/badge for usage type (Page, Post, Product, etc.)
- Context labels (Featured Image, Gallery, Content, etc.)
- "View" button linking to the content using the media
- Last used timestamp
- Empty state when media is not used
- Loading states

### Integration into MediaItem Component

```php
// Add to MediaItem component
public function confirmDelete(): void
{
    // Check if media is used
    if (apIsMediaUsed($this->media->id)) {
        $usageCount = app(MediaUsageTracker::class)->getUsageCount($this->media->id);

        $this->addToast(
            "This media is used in {$usageCount} location(s). Are you sure you want to delete it?",
            'warning'
        );

        $this->dispatch('confirm-delete-used-media', mediaId: $this->media->id);
        return;
    }

    $this->deleteMedia();
}
```

### Usage Badge

Add a small badge to MediaItem showing usage count:

```blade
@if($usageCount = apGetMediaUsage($media->id)->count())
    <span class="usage-badge">
        Used in {{ $usageCount }} {{ Str::plural('place', $usageCount) }}
    </span>
@endif
```

## Dependencies

- Task 05: Usage tracking database schema
- Task 06: Usage tracking service

## Testing Requirements

- [ ] Test component renders usage information
- [ ] Test modal opens and closes
- [ ] Test usage list displays correctly
- [ ] Test empty state when no usage
- [ ] Test "View" links are generated
- [ ] Test usage count badge displays
- [ ] Test delete warning for used media
- [ ] Test integration with MediaItem component
- [ ] Create MediaUsageViewerTest with 10+ tests

## Notes

- The component should be lightweight and only load usage data when opened
- Consider lazy loading usage information in the modal
- Add a hook for customizing how different model types are displayed:
  ```php
  add_filter('ap.media.usageDisplay', function($display, $usage) {
      if ($usage->usable_type === 'App\\Models\\Product') {
          $display['icon'] = 'fas.shopping-cart';
          $display['color'] = 'blue';
      }
      return $display;
  }, 10, 2);
  ```
- Usage information can be valuable for content audits
- Consider adding a "Find Unused Media" feature using this data

## File Locations

- Component: `src/Livewire/Components/MediaUsageViewer.php`
- View: `resources/views/livewire/components/media-usage-viewer.blade.php`
- Tests: `tests/Feature/MediaUsageViewerTest.php`
