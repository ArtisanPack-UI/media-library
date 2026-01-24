<?php

/**
 * Media Grid Livewire Component
 *
 * Displays media items in a grid or list layout with support
 * for bulk selection and view mode switching.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Media Grid Component
 *
 * Displays media items in a grid or list layout.
 *
 * @since   1.0.0
 */
class MediaGrid extends Component
{
    /**
     * Media items to display (stored as Collection for serialization).
     *
     * @since 1.0.0
     *
     * @var Collection
     */
    #[Locked]
    public Collection $mediaItems;

    /**
     * View mode (grid or list).
     *
     * @since 1.0.0
     *
     * @var string
     */
    public string $viewMode = 'grid';

    /**
     * Whether bulk select mode is active.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $bulkSelectMode = false;

    /**
     * Selected media IDs.
     *
     * @since 1.0.0
     *
     * @var array<int>
     */
    public array $selectedMedia = [];

    /**
     * Mount the component.
     *
     * @since 1.0.0
     *
     * @param  LengthAwarePaginator|Collection  $media  The media items to display.
     * @param  string  $viewMode  The view mode (grid or list).
     * @param  bool  $bulkSelectMode  Whether bulk select mode is active.
     * @param  array<int>  $selectedMedia  Selected media IDs.
     */
    public function mount($media, string $viewMode = 'grid', bool $bulkSelectMode = false, array $selectedMedia = []): void
    {
        // Convert paginator to collection for storage, preserving items
        $this->mediaItems = $media instanceof LengthAwarePaginator ? collect($media->items()) : collect($media);
        $this->viewMode = $viewMode;
        $this->bulkSelectMode = $bulkSelectMode;
        $this->selectedMedia = $selectedMedia;
    }

    /**
     * Toggle media selection.
     *
     * @since 1.0.0
     *
     * @param  int  $mediaId  The media ID to toggle.
     */
    public function toggleSelection(int $mediaId): void
    {
        if (in_array($mediaId, $this->selectedMedia, true)) {
            $this->selectedMedia = array_values(array_diff($this->selectedMedia, [$mediaId]));
        } else {
            $this->selectedMedia[] = $mediaId;
        }

        // Dispatch event to parent component
        $this->dispatch('selection-changed', selectedMedia: $this->selectedMedia);
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
        return view('media::livewire.components.media-grid', [
            'media' => $this->mediaItems,
        ]);
    }
}
