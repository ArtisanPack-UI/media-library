# Advanced Search - UI Component

## Description

Create an advanced search UI component with collapsible filter panel, saved searches, and search presets for the Media Library interface.

## Acceptance Criteria

- [ ] Create AdvancedSearchPanel Livewire component
- [ ] Add collapsible advanced filters panel to MediaLibrary
- [ ] Implement all filter inputs (date ranges, sizes, dimensions, etc.)
- [ ] Add saved search management UI
- [ ] Add search preset buttons
- [ ] Show active filters with clear buttons
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create Blade view with inline CSS

## Technical Details

### AdvancedSearchPanel Component

```php
class AdvancedSearchPanel extends Component
{
    use Toast;

    // Basic search
    public string $search = '';

    // Date range
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // File size range (in KB)
    public ?int $sizeMin = null;
    public ?int $sizeMax = null;

    // Dimension range
    public ?int $widthMin = null;
    public ?int $widthMax = null;
    public ?int $heightMin = null;
    public ?int $heightMax = null;

    // Orientation
    public ?string $orientation = null;

    // Usage status
    public ?bool $isUsed = null;

    // Uploader
    public ?int $uploadedBy = null;

    // MIME group
    public ?string $mimeGroup = null;

    // Folder
    public ?int $folderId = null;

    // Tags
    public array $tags = [];

    // Collection
    public ?int $collectionId = null;

    // Sorting
    public string $sortBy = 'created_at';
    public string $sortOrder = 'desc';

    // UI state
    public bool $showAdvancedFilters = false;
    public bool $showSavedSearches = false;

    /**
     * Toggle advanced filters panel.
     */
    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    /**
     * Apply filters and search.
     */
    public function applyFilters(): void
    {
        $filters = $this->getActiveFilters();
        $this->dispatch('filters-updated', filters: $filters);
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->reset([
            'search', 'dateFrom', 'dateTo', 'sizeMin', 'sizeMax',
            'widthMin', 'widthMax', 'heightMin', 'heightMax',
            'orientation', 'isUsed', 'uploadedBy', 'mimeGroup',
            'folderId', 'tags', 'collectionId'
        ]);

        $this->applyFilters();
    }

    /**
     * Load a saved search.
     */
    public function loadSavedSearch(int $savedSearchId): void
    {
        $filters = app(MediaSearchService::class)->loadSearch($savedSearchId);

        if (null !== $filters) {
            foreach ($filters as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }

            $this->applyFilters();
            $this->addToast('Saved search loaded.', 'success');
        }
    }

    /**
     * Save current search.
     */
    public function saveCurrentSearch(string $name): void
    {
        if (empty(trim($name))) {
            $this->addToast('Please enter a name for the saved search.', 'error');
            return;
        }

        $filters = $this->getActiveFilters();

        app(MediaSearchService::class)->saveSearch(
            $name,
            $filters,
            auth()->id()
        );

        $this->addToast('Search saved successfully.', 'success');
        $this->dispatch('saved-search-created');
    }

    /**
     * Apply search preset.
     */
    public function applyPreset(string $preset): void
    {
        $this->clearFilters();

        switch ($preset) {
            case 'large_images':
                $this->mimeGroup = 'image';
                $this->sizeMin = 2048; // 2MB
                $this->widthMin = 1920;
                $this->heightMin = 1080;
                break;

            case 'recent_uploads':
                $this->dateFrom = now()->subDays(7)->toDateString();
                break;

            case 'unused_media':
                $this->isUsed = false;
                break;

            case 'portrait_photos':
                $this->mimeGroup = 'image';
                $this->orientation = 'portrait';
                break;

            case 'landscape_photos':
                $this->mimeGroup = 'image';
                $this->orientation = 'landscape';
                break;
        }

        $this->applyFilters();
    }

    /**
     * Get currently active filters.
     */
    protected function getActiveFilters(): array
    {
        return array_filter([
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'size_min' => $this->sizeMin,
            'size_max' => $this->sizeMax,
            'width_min' => $this->widthMin,
            'width_max' => $this->widthMax,
            'height_min' => $this->heightMin,
            'height_max' => $this->heightMax,
            'orientation' => $this->orientation,
            'is_used' => $this->isUsed,
            'uploaded_by' => $this->uploadedBy,
            'mime_group' => $this->mimeGroup,
            'folder_id' => $this->folderId,
            'tags' => $this->tags,
            'collection_id' => $this->collectionId,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ], fn($value) => null !== $value && '' !== $value && [] !== $value);
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return count($this->getActiveFilters());
    }

    #[Computed]
    public function savedSearches()
    {
        return app(MediaSearchService::class)->getUserSavedSearches(auth()->id());
    }
}
```

### Blade View Structure

The view should include:

1. **Basic Search Bar**: Text input with search button
2. **Advanced Filters Toggle**: Button to show/hide advanced panel
3. **Active Filters Display**: Chips showing active filters with X to remove
4. **Advanced Filters Panel** (collapsible):
   - **Date Range**: From/To date pickers
   - **File Size**: Min/Max inputs (with KB/MB selector)
   - **Dimensions**: Min/Max width and height inputs
   - **Orientation**: Radio buttons (Any, Portrait, Landscape, Square)
   - **Usage Status**: Radio buttons (Any, Used, Unused)
   - **Uploader**: User dropdown
   - **Type**: MIME group selector
   - **Folder**: Folder dropdown
   - **Tags**: Multi-select tag picker
   - **Collection**: Collection dropdown
5. **Search Presets**: Quick filter buttons
   - Large Images
   - Recent Uploads
   - Unused Media
   - Portrait Photos
   - Landscape Photos
6. **Saved Searches Dropdown**: Load/manage saved searches
7. **Save Current Search**: Button with name input
8. **Action Buttons**:
   - Apply Filters
   - Clear All Filters

## Dependencies

- Task 10: Advanced search implementation must be completed
- MediaSearchService must be registered in service provider

## Testing Requirements

- [ ] Test advanced filters panel opens/closes
- [ ] Test all filter inputs work
- [ ] Test applyFilters() dispatches event
- [ ] Test clearFilters() resets all filters
- [ ] Test saved search creation
- [ ] Test saved search loading
- [ ] Test search presets
- [ ] Test active filter display
- [ ] Test individual filter removal
- [ ] Test MediaLibrary integration
- [ ] Create AdvancedSearchPanelTest with 20+ tests

## Notes

- The component should integrate seamlessly with existing MediaLibrary component
- Use Alpine.js for smooth panel transitions
- Add keyboard shortcuts:
  - Ctrl+F or Cmd+F: Focus search input
  - Ctrl+Shift+F: Toggle advanced filters
  - Escape: Close advanced panel
- Consider adding:
  - Filter presets for common use cases
  - Recently used filters
  - Filter templates
- Active filters should be displayed as removable chips
- Save search UI could be a modal or inline form
- Date pickers should use a good library (Flatpickr, etc.)
- File size inputs should allow KB/MB/GB selection
- Add tooltips for each filter explaining what it does

## File Locations

- Component: `src/Livewire/Components/AdvancedSearchPanel.php`
- View: `resources/views/livewire/components/advanced-search-panel.blade.php`
- Tests: `tests/Feature/AdvancedSearchPanelTest.php`
