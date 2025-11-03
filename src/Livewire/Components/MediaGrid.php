<?php

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Media Grid Component
 *
 * Displays media items in a grid or list layout.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class MediaGrid extends Component
{
    /**
     * Media items to display.
     *
     * @since 1.0.0
     *
     * @var LengthAwarePaginator
     */
    public $media;

    /**
     * View mode (grid or list).
     *
     * @since 1.0.0
     */
    public string $viewMode = 'grid';

    /**
     * Whether bulk select mode is active.
     *
     * @since 1.0.0
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
     * @param LengthAwarePaginator $media          The media items to display.
     * @param string               $viewMode       The view mode (grid or list).
     * @param bool                 $bulkSelectMode Whether bulk select mode is active.
     * @param array<int>           $selectedMedia  Selected media IDs.
     */
    public function mount( $media, string $viewMode = 'grid', bool $bulkSelectMode = false, array $selectedMedia = [] ): void
    {
        $this->media          = $media;
        $this->viewMode       = $viewMode;
        $this->bulkSelectMode = $bulkSelectMode;
        $this->selectedMedia  = $selectedMedia;
    }

    /**
     * Toggle media selection.
     *
     * @since 1.0.0
     *
     * @param int $mediaId The media ID to toggle.
     */
    public function toggleSelection( int $mediaId ): void
    {
        if ( in_array( $mediaId, $this->selectedMedia, true ) ) {
            $this->selectedMedia = array_values( array_diff( $this->selectedMedia, [ $mediaId ] ) );
        } else {
            $this->selectedMedia[] = $mediaId;
        }

        // Dispatch event to parent component
        $this->dispatch( 'selection-changed', selectedMedia: $this->selectedMedia );
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
        return view( 'media::livewire.components.media-grid' );
    }
}
