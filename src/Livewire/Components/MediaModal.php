<?php

/**
 * Media Modal Livewire Component
 *
 * A modal component for selecting media from the library or uploading new files.
 * Supports single and multi-select modes with tabbed interface. Enhanced with
 * visual editor features including inline mode, recently used, and keyboard navigation.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Media Modal Component
 *
 * A modal component for selecting media from the library or uploading new files.
 * Supports both single and multi-select modes with tabbed interface.
 * Enhanced with visual editor features including inline mode, recently used,
 * quick upload select, and keyboard navigation.
 *
 * @since   1.0.0
 */
class MediaModal extends Component
{
    use Toast;
    use WithPagination;

    /**
     * Whether the modal is open.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $isOpen = false;

    /**
     * Whether multi-select mode is enabled.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $multiSelect = false;

    /**
     * Maximum number of selections allowed (0 = unlimited).
     *
     * @since 1.0.0
     *
     * @var int
     */
    public int $maxSelections = 0;

    /**
     * Currently selected media IDs.
     *
     * @since 1.0.0
     *
     * @var array<int, int>
     */
    public array $selectedMedia = [];

    /**
     * Active tab (library or upload).
     *
     * @since 1.0.0
     *
     * @var string
     */
    #[Url]
    public string $activeTab = 'library';

    /**
     * Search query for filtering media.
     *
     * @since 1.0.0
     *
     * @var string
     */
    #[Url]
    public string $search = '';

    /**
     * Selected folder ID for filtering.
     *
     * @since 1.0.0
     *
     * @var int|null
     */
    #[Url]
    public ?int $folderId = null;

    /**
     * Selected media type filter.
     *
     * @since 1.0.0
     *
     * @var string
     */
    #[Url]
    public string $typeFilter = '';

    /**
     * Items per page.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public int $perPage = 12;

    /**
     * Context identifier for this modal instance.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public string $context = '';

    /**
     * Whether inline/compact mode is enabled for visual editor embedding.
     *
     * @since 1.1.0
     *
     * @var bool
     */
    public bool $inlineMode = false;

    /**
     * Recently used media IDs for quick access.
     *
     * @since 1.1.0
     *
     * @var array<int, int>
     */
    public array $recentlyUsed = [];

    /**
     * Whether to auto-select media immediately after upload.
     *
     * @since 1.1.0
     *
     * @var bool
     */
    public bool $quickUploadSelect = true;

    /**
     * Currently focused media index for keyboard navigation.
     *
     * @since 1.1.0
     *
     * @var int
     */
    public int $focusedIndex = -1;

    /**
     * ID of the last uploaded media for quick upload select.
     *
     * @since 1.1.0
     *
     * @var int|null
     */
    public ?int $lastUploadedMediaId = null;

    /**
     * Mount the component.
     *
     * @since 1.0.0
     *
     * @param  bool  $multiSelect  Whether multi-select mode is enabled.
     * @param  int  $maxSelections  Maximum number of selections (0 = unlimited).
     * @param  array  $selectedMedia  Pre-selected media IDs.
     * @param  string  $context  Context identifier for this modal instance.
     * @param  bool  $inlineMode  Whether to use compact inline mode.
     * @param  bool  $quickUploadSelect  Whether to auto-select after upload.
     */
    public function mount(
        bool $multiSelect = false,
        int $maxSelections = 0,
        array $selectedMedia = [],
        string $context = '',
        bool $inlineMode = false,
        bool $quickUploadSelect = true,
    ): void {
        $this->multiSelect = $multiSelect;
        $this->maxSelections = $maxSelections;
        $this->selectedMedia = $selectedMedia;
        $this->context = $context;
        $this->inlineMode = $inlineMode;
        $this->quickUploadSelect = $quickUploadSelect;

        $this->loadRecentlyUsed();
    }

    /**
     * Load recently used media IDs from session.
     *
     * @since 1.1.0
     */
    protected function loadRecentlyUsed(): void
    {
        $this->recentlyUsed = session('media.recently_used', []);
    }

    /**
     * Track media usage and update recently used list.
     *
     * @since 1.1.0
     *
     * @param  int  $mediaId  The media ID to track.
     */
    public function trackUsage(int $mediaId): void
    {
        $recent = session('media.recently_used', []);

        // Remove if already exists (to move to front)
        $recent = array_values(array_diff($recent, [$mediaId]));

        // Add to front
        array_unshift($recent, $mediaId);

        // Keep only last 10
        $recent = array_slice($recent, 0, 10);

        session(['media.recently_used' => $recent]);
        $this->recentlyUsed = $recent;
    }

    /**
     * Get recently used media items.
     *
     * @since 1.1.0
     *
     * @return Collection<int, Media>
     */
    #[Computed]
    public function recentlyUsedMedia(): Collection
    {
        if (empty($this->recentlyUsed)) {
            return collect();
        }

        return Media::whereIn('id', $this->recentlyUsed)
            ->get()
            ->sortBy(function ($media) {
                return array_search($media->id, $this->recentlyUsed, true);
            })
            ->values();
    }

    /**
     * Get the filtered and paginated media items.
     *
     * @since 1.0.0
     *
     * @return LengthAwarePaginator
     */
    #[Computed]
    public function media()
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

        // Apply type filter
        if (! empty($this->typeFilter)) {
            switch ($this->typeFilter) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'audio':
                    $query->where('mime_type', 'like', 'audio/%');
                    break;
                case 'document':
                    $query->whereIn('mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
                    break;
            }
        }

        return $query->with(['folder', 'uploadedBy'])
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Get all folders for the folder dropdown.
     *
     * @since 1.0.0
     *
     * @return Collection<int, MediaFolder>
     */
    #[Computed]
    public function folders(): Collection
    {
        return MediaFolder::orderBy('name')->get();
    }

    /**
     * Get type filter options for the select component.
     *
     * @since 1.0.0
     *
     * @return array<int, array{key: string, label: string}>
     */
    #[Computed]
    public function typeFilterOptions(): array
    {
        return [
            [
                'key' => '',
                'label' => __('All Types'),
            ],
            [
                'key' => 'image',
                'label' => __('Images'),
            ],
            [
                'key' => 'video',
                'label' => __('Videos'),
            ],
            [
                'key' => 'audio',
                'label' => __('Audio'),
            ],
            [
                'key' => 'document',
                'label' => __('Documents'),
            ],
        ];
    }

    /**
     * Get folder options for the select component.
     *
     * @since 1.0.0
     *
     * @return array<int, array{key: string|int, label: string}>
     */
    #[Computed]
    public function folderOptions(): array
    {
        $options = [
            [
                'key' => '',
                'label' => __('All Folders'),
            ],
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
     * Open the modal.
     *
     * @since 1.0.0
     *
     * @param  string  $context  The context to open (optional).
     */
    #[On('open-media-modal')]
    public function open(string $context = ''): void
    {
        // Only open if context matches or if both are empty (backward compatibility)
        if ($context === '' || $this->context === '' || $context === $this->context) {
            $this->isOpen = true;
            $this->resetFilters();
        }
    }

    /**
     * Reset all filters.
     *
     * @since 1.0.0
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->folderId = null;
        $this->typeFilter = '';
        $this->resetPage();
    }

    /**
     * Switch to a different tab.
     *
     * @since 1.0.0
     *
     * @param  string  $tab  The tab to switch to.
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Toggle selection of a media item.
     *
     * @since 1.0.0
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
                // Single select mode - replace selection
                $this->selectedMedia = [$mediaId];
            } else {
                // Multi select mode - add to selection
                if ($this->maxSelections === 0 || count($this->selectedMedia) < $this->maxSelections) {
                    $this->selectedMedia[] = $mediaId;
                } else {
                    $this->error(__('Maximum :count selections allowed', ['count' => $this->maxSelections]));
                }
            }
        }
    }

    /**
     * Clear all selections.
     *
     * @since 1.0.0
     */
    public function clearSelections(): void
    {
        $this->selectedMedia = [];
    }

    /**
     * Confirm and emit the selected media.
     *
     * @since 1.0.0
     */
    public function confirmSelection(): void
    {
        if (empty($this->selectedMedia)) {
            $this->error(__('Please select at least one media item'));

            return;
        }

        // Track usage for recently used feature
        foreach ($this->selectedMedia as $mediaId) {
            $this->trackUsage($mediaId);
        }

        // Get the actual media objects
        $media = Media::whereIn('id', $this->selectedMedia)->get();

        // Store count before close() clears the selection
        $selectedCount = count($this->selectedMedia);

        // Emit event with selected media and context
        $this->dispatch('media-selected', media: $media->toArray(), context: $this->context);

        // Close the modal
        $this->close();

        $this->success(__(':count media item(s) selected', ['count' => $selectedCount]));
    }

    /**
     * Close the modal.
     *
     * @since 1.0.0
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->selectedMedia = [];
        $this->resetFilters();
    }

    /**
     * Handle media uploaded event from upload tab.
     *
     * @since 1.0.0
     *
     * @param  int|null  $mediaId  The ID of the uploaded media (if available).
     */
    #[On('media-uploaded')]
    public function handleMediaUploaded(?int $mediaId = null): void
    {
        // Refresh the media list
        unset($this->media);

        // Switch to library tab to show uploaded media
        $this->activeTab = 'library';

        // Quick upload select - auto-select the uploaded media
        if ($this->quickUploadSelect && $mediaId !== null) {
            $this->lastUploadedMediaId = $mediaId;

            if (! $this->multiSelect) {
                // Single select mode - select and confirm immediately
                $this->selectedMedia = [$mediaId];
                $this->confirmSelection();

                return;
            }

            // Multi-select mode - add to selection
            if (! in_array($mediaId, $this->selectedMedia, true)) {
                if ($this->maxSelections === 0 || count($this->selectedMedia) < $this->maxSelections) {
                    $this->selectedMedia[] = $mediaId;
                }
            }

            $this->success(__('Media uploaded and selected.'));

            return;
        }

        $this->success(__('Media uploaded successfully. You can now select it.'));
    }

    /**
     * Handle keyboard navigation - move focus to next item.
     *
     * @since 1.1.0
     */
    public function focusNext(): void
    {
        $mediaCount = $this->media->count();

        if ($mediaCount === 0) {
            return;
        }

        $this->focusedIndex = ($this->focusedIndex + 1) % $mediaCount;
    }

    /**
     * Handle keyboard navigation - move focus to previous item.
     *
     * @since 1.1.0
     */
    public function focusPrevious(): void
    {
        // Guard: no-op when no item is focused (consistent with focusUp/focusDown)
        if ($this->focusedIndex < 0) {
            return;
        }

        $mediaCount = $this->media->count();

        if ($mediaCount === 0) {
            return;
        }

        $this->focusedIndex = $this->focusedIndex <= 0
            ? $mediaCount - 1
            : $this->focusedIndex - 1;
    }

    /**
     * Handle keyboard navigation - move focus down one row.
     *
     * @since 1.1.0
     *
     * @param  int  $columnsPerRow  Number of columns in the grid.
     */
    public function focusDown(int $columnsPerRow = 5): void
    {
        // Guard: no-op when no item is focused (mirror focusUp behavior)
        if ($this->focusedIndex < 0) {
            return;
        }

        $mediaCount = $this->media->count();

        if ($mediaCount === 0) {
            return;
        }

        $newIndex = $this->focusedIndex + $columnsPerRow;

        $this->focusedIndex = $newIndex < $mediaCount
            ? $newIndex
            : $this->focusedIndex;
    }

    /**
     * Handle keyboard navigation - move focus up one row.
     *
     * @since 1.1.0
     *
     * @param  int  $columnsPerRow  Number of columns in the grid.
     */
    public function focusUp(int $columnsPerRow = 5): void
    {
        if ($this->focusedIndex < 0) {
            return;
        }

        $newIndex = $this->focusedIndex - $columnsPerRow;

        $this->focusedIndex = $newIndex >= 0
            ? $newIndex
            : $this->focusedIndex;
    }

    /**
     * Handle keyboard navigation - move focus to first item.
     *
     * @since 1.1.0
     */
    public function focusFirst(): void
    {
        $mediaCount = $this->media->count();

        if ($mediaCount === 0) {
            return;
        }

        $this->focusedIndex = 0;
    }

    /**
     * Handle keyboard navigation - move focus to last item.
     *
     * @since 1.1.0
     */
    public function focusLast(): void
    {
        $mediaCount = $this->media->count();

        if ($mediaCount === 0) {
            return;
        }

        $this->focusedIndex = $mediaCount - 1;
    }

    /**
     * Select the currently focused media item.
     *
     * @since 1.1.0
     */
    public function selectFocused(): void
    {
        if ($this->focusedIndex < 0) {
            return;
        }

        $mediaItems = $this->media;

        if ($this->focusedIndex >= $mediaItems->count()) {
            return;
        }

        $mediaItem = $mediaItems->values()[$this->focusedIndex];
        $this->toggleSelect($mediaItem->id);
    }

    /**
     * Reset focus index.
     *
     * @since 1.1.0
     */
    public function resetFocus(): void
    {
        $this->focusedIndex = -1;
    }

    /**
     * Update search and reset pagination.
     *
     * @since 1.0.0
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update folder filter and reset pagination.
     *
     * @since 1.0.0
     */
    public function updatedFolderId(): void
    {
        $this->resetPage();
    }

    /**
     * Update type filter and reset pagination.
     *
     * @since 1.0.0
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Return data for JSON serialization.
     *
     * This method is called by Alpine/Livewire integration when serializing
     * component data. Required to prevent "toJSON method not found" errors.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function toJSON(): array
    {
        return [
            'isOpen' => $this->isOpen,
            'multiSelect' => $this->multiSelect,
            'maxSelections' => $this->maxSelections,
            'selectedMedia' => $this->selectedMedia,
            'activeTab' => $this->activeTab,
            'search' => $this->search,
            'folderId' => $this->folderId,
            'typeFilter' => $this->typeFilter,
            'context' => $this->context,
            'inlineMode' => $this->inlineMode,
            'quickUploadSelect' => $this->quickUploadSelect,
            'focusedIndex' => $this->focusedIndex,
            'lastUploadedMediaId' => $this->lastUploadedMediaId,
        ];
    }

    /**
     * Renders the component.
     *
     * @since 1.0.0
     *
     * @return View The component view.
     */
    public function render(): View
    {
        return view('media::livewire.components.media-modal');
    }
}
