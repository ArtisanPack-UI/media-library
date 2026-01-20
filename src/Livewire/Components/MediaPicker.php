<?php

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Media Picker Component
 *
 * A lightweight, embeddable component for selecting media items.
 * Supports infinite scroll, type filtering, and single/multi-select modes.
 * Designed for integration with visual editors like Keystone CMS.
 *
 * @since   1.1.0
 */
class MediaPicker extends Component
{
    /**
     * Whether the picker is visible/open.
     *
     * @since 1.1.0
     */
    public bool $isOpen = false;

    /**
     * Whether multi-select mode is enabled.
     *
     * @since 1.1.0
     */
    public bool $multiSelect = false;

    /**
     * Maximum number of selections allowed (0 = unlimited).
     *
     * @since 1.1.0
     */
    public int $maxSelections = 0;

    /**
     * Currently selected media IDs.
     *
     * @since 1.1.0
     *
     * @var array<int, int>
     */
    public array $selectedMedia = [];

    /**
     * Accepted MIME types pattern (e.g., 'image/*', 'video/*', 'application/pdf').
     *
     * @since 1.1.0
     */
    public string $acceptTypes = '';

    /**
     * Number of items to load per batch for infinite scroll.
     *
     * @since 1.1.0
     */
    public int $loadCount = 20;

    /**
     * Current number of items loaded.
     *
     * @since 1.1.0
     */
    public int $loadedCount = 20;

    /**
     * Search query for filtering media.
     *
     * @since 1.1.0
     */
    public string $search = '';

    /**
     * Selected folder ID for filtering.
     *
     * @since 1.1.0
     */
    public ?int $folderId = null;

    /**
     * Context identifier for this picker instance.
     *
     * @since 1.1.0
     */
    public string $context = '';

    /**
     * Mount the component.
     *
     * @since 1.1.0
     *
     * @param  bool  $multiSelect  Whether multi-select mode is enabled.
     * @param  int  $maxSelections  Maximum number of selections (0 = unlimited).
     * @param  array  $selectedMedia  Pre-selected media IDs.
     * @param  string  $acceptTypes  Accepted MIME types pattern.
     * @param  int  $loadCount  Number of items to load per batch.
     * @param  string  $context  Context identifier for this picker instance.
     * @param  bool  $isOpen  Whether the picker starts open.
     */
    public function mount(
        bool $multiSelect = false,
        int $maxSelections = 0,
        array $selectedMedia = [],
        string $acceptTypes = '',
        int $loadCount = 20,
        string $context = '',
        bool $isOpen = false,
    ): void {
        $this->multiSelect = $multiSelect;
        $this->maxSelections = $this->clampMaxSelections($maxSelections);

        // Normalize selectedMedia: remove duplicates, reindex, and trim to maxSelections
        $normalizedMedia = array_values(array_unique($selectedMedia));
        if ($this->maxSelections > 0 && count($normalizedMedia) > $this->maxSelections) {
            $normalizedMedia = array_slice($normalizedMedia, 0, $this->maxSelections);
        }
        $this->selectedMedia = $normalizedMedia;

        $this->acceptTypes = $acceptTypes;
        $this->loadCount = $this->clampLoadCount($loadCount);
        $this->loadedCount = $this->loadCount;
        $this->context = $context;
        $this->isOpen = $isOpen;
    }

    /**
     * Clamp maxSelections to a safe range (0-1000).
     *
     * @since 1.1.0
     *
     * @param  int  $value  The value to clamp.
     * @return int The clamped value.
     */
    protected function clampMaxSelections(int $value): int
    {
        return max(0, min(1000, $value));
    }

    /**
     * Clamp loadCount to a safe range (1-100).
     *
     * @since 1.1.0
     *
     * @param  int  $value  The value to clamp.
     * @return int The clamped value.
     */
    protected function clampLoadCount(int $value): int
    {
        return max(1, min(100, $value));
    }

    /**
     * Get the filtered media items with infinite scroll support.
     *
     * @since 1.1.0
     *
     * @return Collection<int, Media>
     */
    #[Computed]
    public function media(): Collection
    {
        $query = Media::query();

        // Apply search filter
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('file_name', 'like', '%'.$this->search.'%')
                    ->orWhere('alt_text', 'like', '%'.$this->search.'%');
            });
        }

        // Apply folder filter
        if ($this->folderId !== null) {
            $query->where('folder_id', $this->folderId);
        }

        // Apply MIME type filter based on acceptTypes
        if (! empty($this->acceptTypes)) {
            $this->applyTypeFilter($query);
        }

        return $query->with(['folder', 'uploadedBy'])
            ->latest()
            ->take($this->loadedCount)
            ->get();
    }

    /**
     * Apply the MIME type filter based on acceptTypes pattern.
     *
     * @since 1.1.0
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     */
    protected function applyTypeFilter($query): void
    {
        $types = array_map('trim', explode(',', $this->acceptTypes));

        $query->where(function ($q) use ($types) {
            foreach ($types as $type) {
                if (str_ends_with($type, '/*')) {
                    // Wildcard pattern like 'image/*'
                    $prefix = str_replace('/*', '/', $type);
                    $q->orWhere('mime_type', 'like', $prefix.'%');
                } else {
                    // Exact match like 'application/pdf'
                    $q->orWhere('mime_type', $type);
                }
            }
        });
    }

    /**
     * Get total count of available media items.
     *
     * @since 1.1.0
     */
    #[Computed]
    public function totalCount(): int
    {
        $query = Media::query();

        // Apply search filter
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('file_name', 'like', '%'.$this->search.'%')
                    ->orWhere('alt_text', 'like', '%'.$this->search.'%');
            });
        }

        // Apply folder filter
        if ($this->folderId !== null) {
            $query->where('folder_id', $this->folderId);
        }

        // Apply MIME type filter based on acceptTypes
        if (! empty($this->acceptTypes)) {
            $this->applyTypeFilter($query);
        }

        return $query->count();
    }

    /**
     * Check if there are more items to load.
     *
     * @since 1.1.0
     */
    #[Computed]
    public function hasMore(): bool
    {
        return $this->loadedCount < $this->totalCount;
    }

    /**
     * Get all folders for the folder dropdown.
     *
     * @since 1.1.0
     *
     * @return Collection<int, MediaFolder>
     */
    #[Computed]
    public function folders(): Collection
    {
        return MediaFolder::orderBy('name')->get();
    }

    /**
     * Get folder options for the select component.
     *
     * @since 1.1.0
     *
     * @return array<int, array{key: string|int|null, label: string}>
     */
    #[Computed]
    public function folderOptions(): array
    {
        $options = [
            ['key' => null, 'label' => __('All Folders')],
        ];

        foreach ($this->folders as $folder) {
            $options[] = [
                'key' => $folder->id,
                'label' => $folder->name,
            ];
        }

        return $options;
    }

    /**
     * Open the picker.
     *
     * @since 1.1.0
     *
     * @param  string  $context  The context to open (optional).
     */
    #[On('open-media-picker')]
    public function open(string $context = ''): void
    {
        // Only open if context matches or if both are empty (backward compatibility)
        if ($context === '' || $this->context === '' || $context === $this->context) {
            $this->isOpen = true;
            $this->loadedCount = $this->loadCount;
            $this->resetFilters();
            $this->dispatch('media-picker-opened', context: $this->context);
        }
    }

    /**
     * Close the picker.
     *
     * @since 1.1.0
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->selectedMedia = [];
        $this->loadedCount = $this->loadCount;
        $this->resetFilters();
        $this->dispatch('media-picker-closed', context: $this->context);
    }

    /**
     * Reset all filters.
     *
     * @since 1.1.0
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->folderId = null;
    }

    /**
     * Load more items for infinite scroll.
     *
     * @since 1.1.0
     */
    public function loadMore(): void
    {
        $this->loadedCount += $this->loadCount;
    }

    /**
     * Toggle selection of a media item.
     *
     * @since 1.1.0
     *
     * @param  int  $mediaId  The media ID to toggle.
     */
    public function toggleSelect(int $mediaId): void
    {
        if (in_array($mediaId, $this->selectedMedia, true)) {
            // Deselect
            $this->selectedMedia = array_values(array_diff($this->selectedMedia, [$mediaId]));
        } else {
            // Select
            if (! $this->multiSelect) {
                // Single select mode - pick immediately
                $this->selectedMedia = [$mediaId];
                $this->confirmSelection();
            } else {
                // Multi select mode - add to selection
                if ($this->maxSelections === 0 || count($this->selectedMedia) < $this->maxSelections) {
                    $this->selectedMedia[] = $mediaId;
                }
            }
        }
    }

    /**
     * Clear all selections.
     *
     * @since 1.1.0
     */
    public function clearSelections(): void
    {
        $this->selectedMedia = [];
    }

    /**
     * Confirm and emit the selected media.
     *
     * @since 1.1.0
     */
    public function confirmSelection(): void
    {
        if (empty($this->selectedMedia)) {
            return;
        }

        // Get the actual media objects
        $media = Media::whereIn('id', $this->selectedMedia)->get();

        // Emit event with selected media and context
        $this->dispatch('media-picked', media: $media->toArray(), context: $this->context);

        // Close the picker
        $this->close();
    }

    /**
     * Handle media uploaded event.
     *
     * @since 1.1.0
     */
    #[On('media-uploaded')]
    public function handleMediaUploaded(): void
    {
        // Refresh the media list
        unset($this->media);
        unset($this->totalCount);
    }

    /**
     * Update search and reset loaded count.
     *
     * @since 1.1.0
     */
    public function updatedSearch(): void
    {
        $this->loadedCount = $this->loadCount;
    }

    /**
     * Update folder filter and reset loaded count.
     *
     * @since 1.1.0
     */
    public function updatedFolderId(): void
    {
        $this->loadedCount = $this->loadCount;
    }

    /**
     * Renders the component.
     *
     * @since 1.1.0
     *
     * @return View The component view.
     */
    public function render(): View
    {
        return view('media::livewire.components.media-picker');
    }
}
