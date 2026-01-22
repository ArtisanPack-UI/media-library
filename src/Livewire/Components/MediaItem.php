<?php

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Media Item Component
 *
 * Displays a single media item with preview, actions, and metadata.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class MediaItem extends Component
{
    use Toast;

    /**
     * The media item to display.
     *
     * @since 1.0.0
     */
    public Media $media;

    /**
     * Whether the item is selected.
     *
     * @since 1.0.0
     */
    public bool $selected = false;

    /**
     * Whether bulk select mode is active.
     *
     * @since 1.0.0
     */
    public bool $bulkSelectMode = false;

    /**
     * Mount the component.
     *
     * @since 1.0.0
     *
     * @param Media $media          The media item to display.
     * @param bool  $selected       Whether the item is selected.
     * @param bool  $bulkSelectMode Whether bulk select mode is active.
     */
    public function mount( Media $media, bool $selected = false, bool $bulkSelectMode = false ): void
    {
        $this->media          = $media;
        $this->selected       = $selected;
        $this->bulkSelectMode = $bulkSelectMode;
    }

    /**
     * Toggle selection of this media item.
     *
     * @since 1.0.0
     */
    public function toggleSelect(): void
    {
        $this->selected = ! $this->selected;
        $this->dispatch( 'media-selected', mediaId: $this->media->id, selected: $this->selected );
    }

    /**
     * Delete this media item.
     *
     * @since 1.0.0
     */
    public function delete(): void
    {
        if ( ! auth()->user()->can( 'delete', $this->media ) ) {
            $this->error( __( 'You do not have permission to delete this media' ) );

            return;
        }

        $this->media->deleteFiles();
        $this->media->delete();

        $this->success( __( 'Media deleted successfully' ) );
        $this->dispatch( 'media-updated' );
    }

    /**
     * Copy the media URL to clipboard.
     *
     * @since 1.0.0
     */
    public function copyUrl(): void
    {
        $this->dispatch( 'copy-to-clipboard', url: $this->media->url() );
        $this->success( __( 'URL copied to clipboard' ) );
    }

    /**
     * Download the media file.
     *
     * Dispatches a download-file event with the media URL and filename
     * for the frontend to handle the download.
     *
     * @since 1.0.0
     */
    public function download(): void
    {
        $this->dispatch( 'download-file', url: $this->media->url(), filename: $this->media->file_name );
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
        return view( 'media::livewire.components.media-item' );
    }
}
