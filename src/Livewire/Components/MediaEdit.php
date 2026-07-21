<?php

/**
 * Media Edit Livewire Component
 *
 * Provides an interface for editing media metadata including title, alt text,
 * caption, description, folder assignment, and tags.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\Ai\Agents\AltTextGenerationAgent;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageTagSuggestionAgent;
use ArtisanPackUI\MediaLibrary\Ai\Concerns\InteractsWithMediaAi;
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
    use InteractsWithMediaAi;
    use Toast;

    /**
     * The media item being edited.
     *
     * @since 1.0.0
     *
     * @var Media
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
     *
     * @var bool
     */
    public bool $isSaving = false;

    /**
     * Whether the current alt_text value came from the alt-text agent
     * this session. The Blade template uses this to render an "AI
     * suggested" badge until the user edits or confirms.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $altTextIsAiSuggested = false;

    /**
     * Whether the current description value came from the description
     * agent this session.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $descriptionIsAiSuggested = false;

    /**
     * Tag IDs the tag-suggestion agent proposed this session — used to
     * highlight suggested tags in the taxonomy picker.
     *
     * @since 1.3.0
     *
     * @var array<int, int>
     */
    public array $aiSuggestedTagIds = [];

    /**
     * Requested description length tier for the description agent.
     *
     * @since 1.3.0
     *
     * @var string
     */
    public string $aiDescriptionLength = 'medium';

    /**
     * Consumed once by `updatedFormAltText()` to distinguish a user
     * edit from the client-side `$wire.set('form.alt_text', ...)`
     * echo dispatched right after an AI suggestion. Without this the
     * echo request would immediately reset `$altTextIsAiSuggested` to
     * false and the badge would never appear.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $suppressNextAltTextHook = false;

    /**
     * Companion suppression flag for the description hook.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $suppressNextDescriptionHook = false;

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
     * Clear the "AI suggested" badge as soon as the user edits alt text.
     *
     * @since 1.3.0
     */
    public function updatedFormAltText(): void
    {
        if ( $this->suppressNextAltTextHook ) {
            $this->suppressNextAltTextHook = false;

            return;
        }

        $this->altTextIsAiSuggested = false;
    }

    /**
     * Clear the "AI suggested" badge as soon as the user edits the
     * description.
     *
     * @since 1.3.0
     */
    public function updatedFormDescription(): void
    {
        if ( $this->suppressNextDescriptionHook ) {
            $this->suppressNextDescriptionHook = false;

            return;
        }

        $this->descriptionIsAiSuggested = false;
    }

    /**
     * Suggest alt text for the current media item using the
     * `ai.alt_text` agent.
     *
     * @since 1.3.0
     */
    public function suggestAltText(): void
    {
        if ( ! $this->guardAi( 'ai.alt_text' ) ) {
            return;
        }

        $this->runAi( function (): void {
            $result = AltTextGenerationAgent::for( $this->imageReference() )->run();

            $altText      = trim( (string) ( $result['alt_text'] ?? '' ) );
            $usedFallback = false;

            if ( '' === $altText ) {
                // Fall back to a filename-based stub rather than leaving
                // the field silently empty when the model refuses to
                // describe an image.
                $altText     = $this->filenameFallbackAltText(
                    (string) ( $this->media->file_name ?? '' ),
                    (string) ( $this->media->title ?? '' ),
                );
                $usedFallback = true;
            }

            /**
             * Filters an AI-generated alt-text suggestion before it is
             * shown to the user.
             *
             * Runs after the fallback stub is applied, so the suggestion
             * is guaranteed non-empty. Applications can rewrite the
             * suggestion, translate it, or clear it (by returning null)
             * without touching the AI agent itself.
             *
             * Null-return semantics vary by fire site: this Livewire
             * site leaves the field untouched and does not flip the
             * AI-suggested flag or emit a toast. The JSON endpoint
             * (`MediaAiController::altText`) instead nulls out the
             * `alt_text` field in the response so React/Vue clients
             * can distinguish "AI declined" from an empty string.
             *
             * @since 1.4.0
             *
             * @param string|null $altText The AI-produced (or fallback) alt-text suggestion.
             * @param Media       $media   The media instance the suggestion is for.
             *
             * @return string|null The (possibly modified) alt-text suggestion.
             */
            $altText = applyFilters( 'ap.mediaLibrary.altTextSuggestion', $altText, $this->media );

            // Subscribers can opt out entirely by returning null — leave
            // the field untouched, don't flag it as AI-suggested, and
            // don't flash any toast (they made an explicit no-op choice).
            if ( null === $altText ) {
                return;
            }

            $altText = (string) $altText;

            $usedFallback
                ? $this->info( __(
                    'AI could not confidently describe this image; a filename-based placeholder was inserted.',
                ) )
                : $this->success( __( 'AI-suggested alt text populated.' ) );

            $this->form['alt_text']        = $altText;
            $this->altTextIsAiSuggested    = true;
            $this->suppressNextAltTextHook = true;
            $this->js( sprintf(
                "\$wire.set('form.alt_text', %s)",
                json_encode( $altText, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ) );
        } );
    }

    /**
     * Suggest tags for the current media item using the
     * `media.suggest_tags` agent.
     *
     * @since 1.3.0
     *
     * @param  bool  $allowNew  Whether to accept net-new tags outside the existing taxonomy.
     */
    public function suggestTags( bool $allowNew = false ): void
    {
        if ( ! $this->guardAi( 'media.suggest_tags' ) ) {
            return;
        }

        $this->runAi( function () use ( $allowNew ): void {
            $existingTags = MediaTag::query()->orderBy( 'name' )->pluck( 'name', 'id' )->all();

            $result = ImageTagSuggestionAgent::for( [
                'image'         => $this->imageReference(),
                'existing_tags' => array_values( $existingTags ),
                'filename'      => (string) ( $this->media->file_name ?? '' ),
                'folder'        => (string) ( $this->media->folder?->name ?? '' ),
                'allow_new'     => $allowNew,
            ] )->run();

            $selected  = $this->selectedTags;
            $suggested = [];

            $lookup = [];

            foreach ( $existingTags as $id => $name ) {
                $lookup[ strtolower( $name ) ] = (int) $id;
            }

            foreach ( (array) ( $result['tags'] ?? [] ) as $tagName ) {
                $tagId = $lookup[ strtolower( (string) $tagName ) ] ?? null;

                if ( null === $tagId ) {
                    continue;
                }

                $suggested[] = $tagId;

                if ( ! in_array( $tagId, $selected, true ) ) {
                    $selected[] = $tagId;
                }
            }

            $this->selectedTags      = array_values( array_unique( $selected ) );
            $this->aiSuggestedTagIds = $suggested;

            if ( $allowNew ) {
                foreach ( (array) ( $result['new_tags'] ?? [] ) as $tagName ) {
                    $tagName = trim( (string) $tagName );

                    if ( '' === $tagName ) {
                        continue;
                    }

                    $tag = MediaTag::firstOrCreate(
                        [ 'name' => $tagName ],
                        [ 'slug' => \Illuminate\Support\Str::slug( $tagName ) ],
                    );

                    $this->selectedTags[]      = $tag->id;
                    $this->aiSuggestedTagIds[] = $tag->id;
                }

                $this->selectedTags      = array_values( array_unique( $this->selectedTags ) );
                $this->aiSuggestedTagIds = array_values( array_unique( $this->aiSuggestedTagIds ) );

                unset( $this->tags );
            }

            $count = count( $suggested ) + count( (array) ( $result['new_tags'] ?? [] ) );

            if ( 0 === $count ) {
                $this->info( __( 'The AI did not suggest any tags for this image.' ) );

                return;
            }

            $this->success( trans_choice(
                ':count tag suggested by AI.|:count tags suggested by AI.',
                $count,
                [ 'count' => $count ],
            ) );
        } );
    }

    /**
     * Suggest a paragraph-length description for the current media item
     * using the `media.image_description` agent.
     *
     * @since 1.3.0
     */
    public function suggestDescription(): void
    {
        if ( ! $this->guardAi( 'media.image_description' ) ) {
            return;
        }

        $this->runAi( function (): void {
            $result = ImageDescriptionAgent::for( [
                'image'  => $this->imageReference(),
                'length' => $this->aiDescriptionLength,
            ] )->run();

            $description = (string) ( $result['description'] ?? '' );

            if ( '' === $description ) {
                $this->info( __( 'The AI did not produce a description for this image.' ) );

                return;
            }

            $this->form['description']         = $description;
            $this->descriptionIsAiSuggested    = true;
            $this->suppressNextDescriptionHook = true;
            $this->js( sprintf(
                "\$wire.set('form.description', %s)",
                json_encode( $description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ) );

            $this->success( __( 'AI-suggested description populated.' ) );
        } );
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

    /**
     * Verify the current media is image-typed and the requested feature
     * is enabled before dispatching an agent run.
     *
     * @since 1.3.0
     *
     * @param  string  $featureKey  Feature key to check.
     *
     * @return bool True if the caller may proceed.
     */
    protected function guardAi( string $featureKey ): bool
    {
        if ( ! $this->isAiAvailable() ) {
            $this->error( __( 'AI features are not installed.' ) );

            return false;
        }

        if ( ! $this->media->isImage() ) {
            $this->error( __( 'AI features are only available for image media items.' ) );

            return false;
        }

        if ( ! $this->isAiFeatureEnabled( $featureKey ) ) {
            $this->error( __( 'This AI feature is disabled.' ) );

            return false;
        }

        return true;
    }

    /**
     * Build the laravel/ai image reference for the current media item.
     *
     * @since 1.3.0
     *
     * @return array{ source: string, value: string }
     */
    protected function imageReference(): array
    {
        $url = $this->media->url();

        if ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) ) {
            return [ 'source' => 'url', 'value' => $url ];
        }

        return [
            'source' => 'path',
            'value'  => $this->media->path(),
        ];
    }
}
