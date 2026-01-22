<?php

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPack\LivewireUiComponents\Traits\WithTableExport;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Media Library Component
 *
 * Component for browsing, searching, and managing media files.
 * Supports search, filtering, sorting, and bulk actions.
 *
 * @since   1.0.0
 */
class MediaLibrary extends Component
{
    use Toast;
    use WithPagination;
    use WithTableExport;

    /**
     * Search query.
     *
     * @since 1.0.0
     */
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Current folder ID for filtering.
     *
     * @since 1.0.0
     */
    #[Url(as: 'folder')]
    public ?int $folderId = null;

    /**
     * Media type filter (image, video, audio, document).
     *
     * @since 1.0.0
     */
    #[Url]
    public string $type = '';

    /**
     * Tag slug for filtering.
     *
     * @since 1.0.0
     */
    #[Url]
    public string $tag = '';

    /**
     * Sort column.
     *
     * @since 1.0.0
     */
    #[Url]
    public string $sortBy = 'created_at';

    /**
     * Sort direction (asc, desc).
     *
     * @since 1.0.0
     */
    #[Url]
    public string $sortOrder = 'desc';

    /**
     * View mode (grid, list).
     *
     * @since 1.0.0
     */
    public string $viewMode = 'grid';

    /**
     * Selected media IDs for bulk actions.
     *
     * @since 1.0.0
     *
     * @var array<int>
     */
    public array $selectedMedia = [];

    /**
     * Whether bulk select mode is active.
     *
     * @since 1.0.0
     */
    public bool $bulkSelectMode = false;

    /**
     * Number of items per page.
     *
     * @since 1.0.0
     */
    public int $perPage = 24;

    /**
     * Announcement message for screen readers.
     *
     * @since 1.1.0
     */
    public string $announcement = '';

    /**
     * Available media type filter options.
     *
     * @since 1.0.0
     *
     * @var array<int, array{value: string, label: string}>
     */
    public array $types = [];

    /**
     * Available sort by options.
     *
     * @since 1.0.0
     *
     * @var array<int, array{value: string, label: string}>
     */
    public array $sortByOptions = [];

    /**
     * Available sort order options.
     *
     * @since 1.0.0
     *
     * @var array<int, array{value: string, label: string}>
     */
    public array $sortOrderOptions = [];

    /**
     * Mount the component.
     *
     * @since 1.0.0
     */
    public function mount(): void
    {
        // Load view mode from session
        $this->viewMode = session('media.viewMode', 'grid');
        $this->types = [
            [
                'value' => '',
                'label' => __('All Types'),
            ],
            [
                'value' => 'image',
                'label' => __('Image'),
            ],
            [
                'value' => 'video',
                'label' => __('Video'),
            ],
            [
                'value' => 'audio',
                'label' => __('Audio'),
            ],
            [
                'value' => 'document',
                'label' => __('Documents'),
            ],
        ];

        $this->sortByOptions = [
            [
                'value' => 'created_at',
                'label' => __('Date Added'),
            ],
            [
                'value' => 'title',
                'label' => __('Title'),
            ],
            [
                'value' => 'file_name',
                'label' => __('File Name'),
            ],
            [
                'value' => 'file_size',
                'label' => __('File Size'),
            ],
        ];

        $this->sortOrderOptions = [
            [
                'value' => 'asc',
                'label' => __('Ascending'),
            ],
            [
                'value' => 'desc',
                'label' => __('Descending'),
            ],
        ];
    }

    /**
     * Gets media items with filters applied.
     *
     * @since 1.0.0
     *
     * @return LengthAwarePaginator The paginated media items.
     */
    #[Computed]
    public function media(): LengthAwarePaginator
    {
        $query = Media::query()->with(['folder', 'uploadedBy', 'tags']);

        // Apply folder filter
        if ($this->folderId !== null) {
            $query->where('folder_id', $this->folderId);
        }

        // Apply type filter
        if ($this->type !== '') {
            if ($this->type === 'image') {
                $query->images();
            } elseif ($this->type === 'video') {
                $query->videos();
            } elseif ($this->type === 'audio') {
                $query->audios();
            } elseif ($this->type === 'document') {
                $query->documents();
            } else {
                $query->byType($this->type);
            }
        }

        // Apply tag filter
        if ($this->tag !== '') {
            $query->withTag($this->tag);
        }

        // Apply search
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('file_name', 'like', '%'.$this->search.'%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortOrder);

        return $query->paginate($this->perPage);
    }

    /**
     * Gets all folders for navigation.
     *
     * @since 1.0.0
     *
     * @return Collection<MediaFolder> The collection of folders.
     */
    #[Computed]
    public function folders(): Collection
    {
        return MediaFolder::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    /**
     * Gets all tags for filtering.
     *
     * @since 1.0.0
     *
     * @return Collection<MediaTag> The collection of tags.
     */
    #[Computed]
    public function tags(): Collection
    {
        return MediaTag::query()->orderBy('name')->get();
    }

    /**
     * Gets current folder.
     *
     * @since 1.0.0
     *
     * @return MediaFolder|null The current folder or null if none selected.
     */
    #[Computed]
    public function currentFolder(): ?MediaFolder
    {
        if ($this->folderId === null) {
            return null;
        }

        return MediaFolder::find($this->folderId);
    }

    /**
     * Clear all filters.
     *
     * @since 1.0.0
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->folderId = null;
        $this->type = '';
        $this->tag = '';
        $this->resetPage();
    }

    /**
     * Set the folder filter.
     *
     * @since 1.0.0
     *
     * @param  int|null  $folderId  The folder ID to filter by.
     */
    public function setFolder(?int $folderId): void
    {
        $this->folderId = $folderId;
        $this->resetPage();
    }

    /**
     * Set the type filter.
     *
     * @since 1.0.0
     *
     * @param  string  $type  The media type to filter by.
     */
    public function setType(string $type): void
    {
        $this->type = $type;
        $this->resetPage();
    }

    /**
     * Set the tag filter.
     *
     * @since 1.0.0
     *
     * @param  string  $tag  The tag slug to filter by.
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
        $this->resetPage();
    }

    /**
     * Set the sort column and direction.
     *
     * @since 1.0.0
     *
     * @param  string  $column  The column to sort by.
     */
    public function setSortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            // Toggle sort direction if already sorting by this column
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortOrder = 'desc';
        }

        $this->resetPage();
    }

    /**
     * Announce a message for screen readers.
     *
     * @since 1.1.0
     *
     * @param  string  $message  The message to announce.
     */
    protected function announce(string $message): void
    {
        $this->announcement = $message;
    }

    /**
     * Toggle view mode between grid and list.
     *
     * @since 1.0.0
     */
    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
        session(['media.viewMode' => $this->viewMode]);
        $this->announce($this->viewMode === 'grid' ? __('Switched to grid view') : __('Switched to list view'));
    }

    /**
     * Toggle bulk select mode.
     *
     * @since 1.0.0
     */
    public function toggleBulkSelect(): void
    {
        $this->bulkSelectMode = ! $this->bulkSelectMode;
        if (! $this->bulkSelectMode) {
            $this->selectedMedia = [];
        }
        $this->announce($this->bulkSelectMode ? __('Bulk selection mode enabled') : __('Bulk selection mode disabled'));
    }

    /**
     * Select all media on current page.
     *
     * @since 1.0.0
     */
    public function selectAll(): void
    {
        $this->selectedMedia = $this->media->pluck('id')->toArray();
        $this->announce(__(':count items selected', ['count' => count($this->selectedMedia)]));
    }

    /**
     * Deselect all media.
     *
     * @since 1.0.0
     */
    public function deselectAll(): void
    {
        $this->selectedMedia = [];
        $this->announce(__('All items deselected'));
    }

    /**
     * Delete selected media items.
     *
     * @since 1.0.0
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedMedia)) {
            $this->warning(__('No media selected'));

            return;
        }

        $count = 0;
        foreach ($this->selectedMedia as $mediaId) {
            $media = Media::find($mediaId);
            if ($media !== null && auth()->user()->can('delete', $media)) {
                $media->delete();
                $count++;
            }
        }

        $this->selectedMedia = [];
        $this->bulkSelectMode = false;

        $this->success(__(':count media items deleted', ['count' => $count]));
        $this->dispatch('media-updated');
    }

    /**
     * Move selected media to a folder.
     *
     * @since 1.0.0
     *
     * @param  int|null  $folderId  The folder ID to move to.
     */
    public function bulkMove(?int $folderId): void
    {
        if (empty($this->selectedMedia)) {
            $this->warning(__('No media selected'));

            return;
        }

        $count = 0;
        foreach ($this->selectedMedia as $mediaId) {
            $media = Media::find($mediaId);
            if ($media !== null && auth()->user()->can('update', $media)) {
                $media->update(['folder_id' => $folderId]);
                $count++;
            }
        }

        $this->selectedMedia = [];
        $this->bulkSelectMode = false;

        $this->success(__(':count media items moved', ['count' => $count]));
        $this->dispatch('media-updated');
    }

    /**
     * Listen for media updates.
     *
     * @since 1.0.0
     */
    #[On('media-updated')]
    public function refreshMedia(): void
    {
        // Refresh computed properties
        unset($this->media);
    }

    /**
     * Handle media selection from child components.
     *
     * @since 1.0.0
     *
     * @param  int  $mediaId  The media ID that was toggled.
     * @param  bool  $selected  Whether the media is now selected.
     */
    #[On('media-selected')]
    public function handleMediaSelected(int $mediaId, bool $selected): void
    {
        if ($selected) {
            if (! in_array($mediaId, $this->selectedMedia, true)) {
                $this->selectedMedia[] = $mediaId;
            }
        } else {
            $this->selectedMedia = array_values(array_diff($this->selectedMedia, [$mediaId]));
        }
        $this->announce(__(':count items selected', ['count' => count($this->selectedMedia)]));
    }

    /**
     * Reset pagination when search changes.
     *
     * @since 1.0.0
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Get export data for the media table.
     *
     * Returns filtered media data (respects current search/filters) for export.
     *
     * @since 1.1.0
     *
     * @param  string  $tableId  The table identifier (unused, single table).
     * @return array{headers: array, rows: array, filename: string} The export data.
     */
    public function getTableExportData(string $tableId = 'default'): array
    {
        // Get all filtered media (not paginated) for export
        $query = Media::query()->with(['folder', 'uploadedBy']);

        // Apply folder filter
        if ($this->folderId !== null) {
            $query->where('folder_id', $this->folderId);
        }

        // Apply type filter
        if ($this->type !== '') {
            if ($this->type === 'image') {
                $query->images();
            } elseif ($this->type === 'video') {
                $query->videos();
            } elseif ($this->type === 'audio') {
                $query->audios();
            } elseif ($this->type === 'document') {
                $query->documents();
            } else {
                $query->byType($this->type);
            }
        }

        // Apply tag filter
        if ($this->tag !== '') {
            $query->withTag($this->tag);
        }

        // Apply search
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('file_name', 'like', '%'.$this->search.'%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortOrder);

        $media = $query->get();

        return [
            'headers' => [
                [
                    'key' => 'id',
                    'label' => __('ID'),
                ],
                [
                    'key' => 'title',
                    'label' => __('Title'),
                ],
                [
                    'key' => 'file_name',
                    'label' => __('File Name'),
                ],
                [
                    'key' => 'mime_type',
                    'label' => __('Type'),
                ],
                [
                    'key' => 'file_size',
                    'label' => __('Size'),
                ],
                [
                    'key' => 'folder',
                    'label' => __('Folder'),
                ],
                [
                    'key' => 'created_at',
                    'label' => __('Uploaded'),
                ],
            ],
            'rows' => $media->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title ?? '',
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'file_size' => $m->humanFileSize(),
                'folder' => $m->folder?->name ?? __('No Folder'),
                'created_at' => $m->created_at->format('Y-m-d H:i'),
            ])->toArray(),
            'filename' => 'media-export-'.date('Y-m-d'),
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
        return view('media::livewire.pages.media-library');
    }
}
