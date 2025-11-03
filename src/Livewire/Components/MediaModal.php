<?php

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
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class MediaModal extends Component
{
    use Toast;
    use WithPagination;

    /**
     * Whether the modal is open.
     *
     * @since 1.0.0
     */
    public bool $isOpen = false;

    /**
     * Whether multi-select mode is enabled.
     *
     * @since 1.0.0
     */
    public bool $multiSelect = false;

    /**
     * Maximum number of selections allowed (0 = unlimited).
     *
     * @since 1.0.0
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
     */
    #[Url]
    public string $activeTab = 'library';

    /**
     * Search query for filtering media.
     *
     * @since 1.0.0
     */
    #[Url]
    public string $search = '';

    /**
     * Selected folder ID for filtering.
     *
     * @since 1.0.0
     */
    #[Url]
    public ?int $folderId = null;

    /**
     * Selected media type filter.
     *
     * @since 1.0.0
     */
    #[Url]
    public string $typeFilter = '';

    /**
     * Items per page.
     *
     * @since 1.0.0
     */
    public int $perPage = 12;

    /**
     * Context identifier for this modal instance.
     *
     * @since 1.0.0
     */
    public string $context = '';

    /**
     * Mount the component.
     *
     * @since 1.0.0
     *
     * @param bool   $multiSelect   Whether multi-select mode is enabled.
     * @param int    $maxSelections Maximum number of selections (0 = unlimited).
     * @param array  $selectedMedia Pre-selected media IDs.
     * @param string $context       Context identifier for this modal instance.
     */
    public function mount( bool $multiSelect = false, int $maxSelections = 0, array $selectedMedia = [], string $context = '' ): void
    {
        $this->multiSelect   = $multiSelect;
        $this->maxSelections = $maxSelections;
        $this->selectedMedia = $selectedMedia;
        $this->context       = $context;
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
        if ( ! empty( $this->search ) ) {
            $query->where( function ( $q ) {
                $q->where( 'title', 'like', '%' . $this->search . '%' )
                  ->orWhere( 'file_name', 'like', '%' . $this->search . '%' )
                  ->orWhere( 'alt_text', 'like', '%' . $this->search . '%' );
            } );
        }

        // Apply folder filter
        if ( $this->folderId !== null ) {
            $query->where( 'folder_id', $this->folderId );
        }

        // Apply type filter
        if ( ! empty( $this->typeFilter ) ) {
            switch ( $this->typeFilter ) {
                case 'image':
                    $query->where( 'mime_type', 'like', 'image/%' );
                    break;
                case 'video':
                    $query->where( 'mime_type', 'like', 'video/%' );
                    break;
                case 'audio':
                    $query->where( 'mime_type', 'like', 'audio/%' );
                    break;
                case 'document':
                    $query->whereIn( 'mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ] );
                    break;
            }
        }

        return $query->with( [ 'folder', 'uploadedBy' ] )
                     ->latest()
                     ->paginate( $this->perPage );
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
        return MediaFolder::orderBy( 'name' )->get();
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
            [ 'key' => '', 'label' => __( 'All Types' ) ],
            [ 'key' => 'image', 'label' => __( 'Images' ) ],
            [ 'key' => 'video', 'label' => __( 'Videos' ) ],
            [ 'key' => 'audio', 'label' => __( 'Audio' ) ],
            [ 'key' => 'document', 'label' => __( 'Documents' ) ],
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
            [ 'key' => '', 'label' => __( 'All Folders' ) ],
        ];

        foreach ( $this->folders as $folder ) {
            $options[] = [
                'key'   => $folder->id,
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
     * @param string $context The context to open (optional).
     */
    #[On( 'open-media-modal' )]
    public function open( string $context = '' ): void
    {
        // Only open if context matches or if both are empty (backward compatibility)
        if ( $context === '' || $this->context === '' || $context === $this->context ) {
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
        $this->search     = '';
        $this->folderId   = null;
        $this->typeFilter = '';
        $this->resetPage();
    }

    /**
     * Switch to a different tab.
     *
     * @since 1.0.0
     *
     * @param string $tab The tab to switch to.
     */
    public function switchTab( string $tab ): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Toggle selection of a media item.
     *
     * @since 1.0.0
     *
     * @param int $mediaId The media ID to toggle.
     */
    public function toggleSelect( int $mediaId ): void
    {
        if ( in_array( $mediaId, $this->selectedMedia, true ) ) {
            // Deselect
            $this->selectedMedia = array_values( array_diff( $this->selectedMedia, [ $mediaId ] ) );
        } else {
            // Select
            if ( ! $this->multiSelect ) {
                // Single select mode - replace selection
                $this->selectedMedia = [ $mediaId ];
            } else {
                // Multi select mode - add to selection
                if ( $this->maxSelections === 0 || count( $this->selectedMedia ) < $this->maxSelections ) {
                    $this->selectedMedia[] = $mediaId;
                } else {
                    $this->error( __( 'Maximum :count selections allowed', [ 'count' => $this->maxSelections ] ) );
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
        if ( empty( $this->selectedMedia ) ) {
            $this->error( __( 'Please select at least one media item' ) );

            return;
        }

        // Get the actual media objects
        $media = Media::whereIn( 'id', $this->selectedMedia )->get();

        // Emit event with selected media and context
        $this->dispatch( 'media-selected', media: $media->toArray(), context: $this->context );

        // Close the modal
        $this->close();

        $this->success( __( ':count media item(s) selected', [ 'count' => count( $this->selectedMedia ) ] ) );
    }

    /**
     * Close the modal.
     *
     * @since 1.0.0
     */
    public function close(): void
    {
        $this->isOpen        = false;
        $this->selectedMedia = [];
        $this->resetFilters();
    }

    /**
     * Handle media uploaded event from upload tab.
     *
     * @since 1.0.0
     */
    #[On( 'media-uploaded' )]
    public function handleMediaUploaded(): void
    {
        // Refresh the media list
        unset( $this->media );

        // Switch to library tab to show uploaded media
        $this->activeTab = 'library';

        $this->success( __( 'Media uploaded successfully. You can now select it.' ) );
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
     * Renders the component.
     *
     * @since 1.0.0
     *
     * @return View The component view.
     */
    public function render(): View
    {
        return view( 'media::livewire.components.media-modal' );
    }
}
