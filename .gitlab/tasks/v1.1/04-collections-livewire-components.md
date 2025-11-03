# Collections/Albums - Livewire Components

## Description

Create Livewire components for managing collections in the admin interface, including collection browser, collection builder/editor, and collection selector modal.

## Acceptance Criteria

- [ ] Create CollectionManager component (modal for CRUD operations)
- [ ] Create CollectionBuilder component (add/remove media, reorder, set cover)
- [ ] Create CollectionSelector component (modal for selecting collections)
- [ ] Create all corresponding Blade views with inline CSS
- [ ] Integrate with MediaLibrary page
- [ ] Add event-based communication between components
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Register components in MediaLibraryServiceProvider

## Technical Details

### CollectionManager Component

```php
class CollectionManager extends Component
{
    use Toast;

    public bool $isOpen = false;
    public ?int $editingCollectionId = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public ?int $coverImageId = null;

    #[On('open-collection-manager')]
    public function openModal(?int $collectionId = null): void

    public function createCollection(): void
    public function updateCollection(): void
    public function deleteCollection(int $collectionId): void
    #[Computed]
    public function collections()
    public function updatedName(): void // Auto-generate slug
}
```

### CollectionBuilder Component

```php
class CollectionBuilder extends Component
{
    use Toast, WithPagination;

    public int $collectionId;
    public array $mediaIds = [];
    public bool $showMediaSelector = false;
    public int $draggedIndex = -1;

    #[Computed]
    public function collection()
    #[Computed]
    public function collectionMedia()
    #[Computed]
    public function availableMedia() // Media not in collection

    public function addMedia(array $mediaIds): void
    public function removeMedia(int $mediaId): void
    public function reorder(array $orderedIds): void
    public function setCoverImage(int $mediaId): void
    public function saveCollection(): void
}
```

### CollectionSelector Component

```php
class CollectionSelector extends Component
{
    use WithPagination;

    public bool $isOpen = false;
    public bool $multiSelect = false;
    public array $selectedCollections = [];

    #[On('open-collection-selector')]
    public function openModal(array $options = []): void

    public function toggleSelection(int $collectionId): void
    public function confirmSelection(): void
}
```

### Blade Views

Each component needs:
- Professional UI with inline CSS
- Responsive design
- Loading states
- Empty states
- Validation error display
- Toast notifications
- Alpine.js for client-side interactions
- Accessibility features (ARIA labels, keyboard navigation)

## Dependencies

- Task 01: Collections database schema
- Task 02: Collections models
- Task 03: Collections API endpoints

## Testing Requirements

- [ ] Test CollectionManager opens and closes
- [ ] Test creating collections with auto-slug generation
- [ ] Test editing collections
- [ ] Test deleting collections
- [ ] Test CollectionBuilder displays media correctly
- [ ] Test adding media to collections
- [ ] Test removing media from collections
- [ ] Test reordering media
- [ ] Test setting cover images
- [ ] Test CollectionSelector in single/multi modes
- [ ] Create CollectionManagerTest, CollectionBuilderTest, CollectionSelectorTest

## Notes

- Use Alpine.js for drag-and-drop reordering in CollectionBuilder
- Collections should appear as a filter option in MediaLibrary component
- Add "Manage Collections" button to MediaLibrary page header
- Consider adding collection badges to media items in grid view
- Use wire:sortable for drag-and-drop reordering if available
- Follow same patterns as FolderManager and TagManager components

## File Locations

- Components:
  - `src/Livewire/Components/CollectionManager.php`
  - `src/Livewire/Components/CollectionBuilder.php`
  - `src/Livewire/Components/CollectionSelector.php`
- Views:
  - `resources/views/livewire/components/collection-manager.blade.php`
  - `resources/views/livewire/components/collection-builder.blade.php`
  - `resources/views/livewire/components/collection-selector.blade.php`
- Tests:
  - `tests/Feature/CollectionManagerTest.php`
  - `tests/Feature/CollectionBuilderTest.php`
  - `tests/Feature/CollectionSelectorTest.php`
