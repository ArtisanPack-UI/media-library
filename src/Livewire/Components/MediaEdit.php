<?php

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * MediaEdit Livewire component for editing media metadata.
 *
 * Provides an interface for editing media title, alt text, caption,
 * description, folder, and tags.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class MediaEdit extends Component
{
    use Toast;

    /**
     * The media item being edited.
     *
     * @since 1.0.0
     */
    public Media $media;

    /**
     * Form data for the media item.
     *
     * @since 1.0.0
     *
     * @var array<string, mixed>
     */
    public array $form = [
        'title'       => '',
        'alt_text'    => '',
        'caption'     => '',
        'description' => '',
        'folder_id'   => null,
    ];

    /**
     * Selected tag IDs.
     *
     * @since 1.0.0
     *
     * @var array<int>
     */
    public array $selectedTags = [];

    /**
     * Whether the form is being saved.
     *
     * @since 1.0.0
     */
    public bool $isSaving = false;

    /**
     * Mount the component.
     *
     * @since 1.0.0
     *
     * @param int $mediaId The media ID to edit.
     */
    public function mount( int $mediaId ): void
    {
        $this->media = Media::with( [ 'folder', 'tags', 'uploadedBy' ] )->findOrFail( $mediaId );

        // Populate form with existing data
        $this->form = [
            'title'       => $this->media->title ?? '',
            'alt_text'    => $this->media->alt_text ?? '',
            'caption'     => $this->media->caption ?? '',
            'description' => $this->media->description ?? '',
            'folder_id'   => $this->media->folder_id,
        ];

        // Load selected tags
        $this->selectedTags = $this->media->tags->pluck( 'id' )->toArray();
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
     * Save the media metadata changes.
     *
     * @since 1.0.0
     */
    public function save(): void
    {
        $this->isSaving = true;

        // Validate the form data
        $validated = $this->validate( [
                                          'form.title'       => [ 'nullable', 'string', 'max:255' ],
                                          'form.alt_text'    => [ 'nullable', 'string', 'max:255' ],
                                          'form.caption'     => [ 'nullable', 'string', 'max:1000' ],
                                          'form.description' => [ 'nullable', 'string', 'max:2000' ],
                                          'form.folder_id'   => [ 'nullable', 'exists:media_folders,id' ],
                                          'selectedTags'     => [ 'nullable', 'array' ],
                                          'selectedTags.*'   => [ 'exists:media_tags,id' ],
                                      ] );

        try {
            // Update media metadata
            $this->media->update( [
                                      'title'       => $this->form['title'],
                                      'alt_text'    => $this->form['alt_text'],
                                      'caption'     => $this->form['caption'],
                                      'description' => $this->form['description'],
                                      'folder_id'   => $this->form['folder_id'],
                                  ] );

            // Sync tags
            $this->media->tags()->sync( $this->selectedTags );

            $this->success( __( 'Media updated successfully' ) );

            // Dispatch event to refresh media library if open
            $this->dispatch( 'media-updated' );
        } catch ( Exception $e ) {
            $this->error( __( 'Failed to update media: :error', [ 'error' => $e->getMessage() ] ) );
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Get all tags for the tag selector.
     *
     * @since 1.0.0
     *
     * @return Collection<int, MediaTag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return MediaTag::orderBy( 'name' )->get();
    }

    /**
     * Delete the media item.
     *
     * @since 1.0.0
     */
    public function delete(): void
    {
        try {
            $this->media->delete();

            $this->success( __( 'Media deleted successfully' ) );

            // Dispatch event to notify media library
            $this->dispatch( 'media-updated' );

            // Redirect to media library
            $this->redirect( route( 'admin.media' ), navigate: true );
        } catch ( Exception $e ) {
            $this->error( __( 'Failed to delete media: :error', [ 'error' => $e->getMessage() ] ) );
        }
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
        return view( 'media::livewire.pages.media-edit' );
    }
}
