<?php

/**
 * Media Upload Livewire Component
 *
 * Provides a drag-and-drop interface for uploading single or multiple files
 * with real-time validation, progress tracking, and file previews. Supports
 * Livewire 4 streaming for real-time progress with Livewire 3 polling fallback.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPackUI\Ai\Agents\AltTextGenerationAgent;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent;
use ArtisanPackUI\MediaLibrary\Ai\Concerns\InteractsWithMediaAi;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Log;

/**
 * MediaUpload Livewire component for uploading media files.
 *
 * Provides a drag-and-drop interface for uploading single or multiple files
 * with real-time validation, progress tracking, and file previews.
 *
 * @since   1.0.0
 */
class MediaUpload extends Component
{
    use InteractsWithMediaAi;
    use StreamableUpload;
    use WithFileUploads;

    /**
     * Files selected via wire:model (Choose Files button).
     *
     * @since 1.0.0
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $files = [];

    /**
     * Files uploaded via drag-and-drop.
     *
     * Livewire automatically hydrates these to TemporaryUploadedFile objects.
     *
     * @since 1.0.0
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $droppedFiles = [];

    /**
     * Uploaded media items.
     *
     * @since 1.0.0
     *
     * @var array<int, Media>
     */
    public array $uploadedMedia = [];

    /**
     * Upload errors.
     *
     * @since 1.0.0
     *
     * @var array<int, string>
     */
    public array $uploadErrors = [];

    /**
     * Whether files are currently being uploaded.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $isUploading = false;

    /**
     * Current upload progress (0-100).
     *
     * @since 1.0.0
     *
     * @var int
     */
    public int $uploadProgress = 0;

    /**
     * Total number of files to upload.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public int $totalFiles = 0;

    /**
     * Number of files uploaded successfully.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public int $uploadedCount = 0;

    /**
     * Selected folder ID for uploaded files.
     *
     * @since 1.0.0
     *
     * @var int|null
     */
    public ?int $folderId = null;

    /**
     * File metadata.
     *
     * @since 1.0.0
     *
     * @var array<string, mixed>
     */
    public array $metadata = [
        'title'       => '',
        'alt_text'    => '',
        'caption'     => '',
        'description' => '',
    ];

    /**
     * Requested description length tier for the description agent.
     *
     * @since 1.3.0
     *
     * @var string
     */
    public string $aiDescriptionLength = 'medium';

    /**
     * Whether the current `metadata.alt_text` came from the alt-text
     * agent this session.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $altTextIsAiSuggested = false;

    /**
     * Whether the current `metadata.description` came from the
     * description agent this session.
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $descriptionIsAiSuggested = false;

    /**
     * Note surfaced under the alt-text input when the agent returned
     * an empty suggestion (e.g. the model classified the image as
     * decorative). Cleared on the next successful suggestion or edit.
     *
     * @since 1.3.0
     *
     * @var string
     */
    public string $altTextAiNote = '';

    /**
     * Consumed once by the property-updated hooks to distinguish a
     * user edit from the `$wire.set(...)` echo dispatched right after
     * an AI suggestion; keeps the "AI suggested" badge from being
     * wiped out by the follow-up round-trip.
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
     * Get total count of all files (both selected and dropped).
     *
     * @since 1.0.0
     */
    public function getTotalFilesCountProperty(): int
    {
        return count( $this->files ) + count( $this->droppedFiles );
    }

    /**
     * Check if polling-based progress updates should be used.
     *
     * Polling is used as a fallback when:
     * - Streaming is not enabled (Livewire 3 or config disabled)
     * - An upload is currently in progress
     *
     * @since 1.1.0
     */
    #[Computed]
    public function shouldUsePoll(): bool
    {
        return $this->isUploading && ! $this->isStreamingEnabled();
    }

    /**
     * Get the polling interval in milliseconds for progress updates.
     *
     * Returns the configured fallback interval for Livewire 3 polling-based
     * progress updates.
     *
     * @since 1.1.0
     */
    #[Computed]
    public function pollingInterval(): int
    {
        return $this->getStreamingFallbackInterval();
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
     * Get folder options for the select component.
     *
     * @since 1.0.0
     *
     * @return array<int, array{key: int|string, label: string}>
     */
    #[Computed]
    public function folderOptions(): array
    {
        $options = [
            ['key' => '', 'label' => __( 'No Folder' )],
        ];

        foreach ( $this->folders as $folder ) {
            $options[] = [
                'key'   => $folder->id,
                'label' => $folder->name,
            ];

            // Add children with indentation
            if ( $folder->children->isNotEmpty() ) {
                foreach ( $folder->children as $child ) {
                    $options[] = [
                        'key'   => $child->id,
                        'label' => '-- ' . $child->name,
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * Handle files being updated (selected via Choose Files button).
     *
     * @since 1.0.0
     */
    public function updatedFiles(): void
    {
        // Filter out any null entries
        $this->files = array_values( array_filter( $this->files, fn ( $file ) => null !== $file && $file instanceof TemporaryUploadedFile ) );

        $this->validate( [
            'files.*' => [
                'file',
                'max:' . config( 'artisanpack.media.max_file_size' ),
            ],
        ] );
    }

    /**
     * Process and upload all selected files to the media library.
     *
     * @since 1.0.0
     */
    public function processUpload(): void
    {
        Log::debug( 'processUpload called', [
            'files_count'        => count( $this->files ),
            'droppedFiles_count' => count( $this->droppedFiles ),
        ] );

        // Merge files from both sources (Choose Files button and drag-and-drop)
        $processedFiles = $this->collectFilesForUpload();

        Log::info( 'Processing upload', ['total_files' => count( $processedFiles )] );

        // Check if we have any files to upload
        if ( empty( $processedFiles ) ) {
            $this->addError( 'files', __( 'Please select at least one file to upload.' ) );

            return;
        }

        $this->isUploading    = true;
        $this->uploadProgress = 0;
        $this->totalFiles     = count( $processedFiles );
        $this->uploadedCount  = 0;
        $this->uploadedMedia  = [];
        $this->uploadErrors   = [];

        // Build upload options
        $uploadOptions = $this->buildUploadOptions();

        // Use streaming if enabled, otherwise use standard upload
        if ( $this->isStreamingEnabled() ) {
            $result              = $this->processFilesWithStreaming( $processedFiles, $uploadOptions );
            $this->uploadedMedia = $result['uploaded'];
            $this->uploadErrors  = $result['errors'];
            $this->uploadedCount = count( $result['uploaded'] );
        } else {
            $this->processFilesStandard( $processedFiles, $uploadOptions );
        }

        $this->isUploading = false;

        // Clear files after upload
        $this->files        = [];
        $this->droppedFiles = [];

        // Reset metadata
        $this->metadata = [
            'title'       => '',
            'alt_text'    => '',
            'caption'     => '',
            'description' => '',
        ];

        // Dispatch event to notify media library of new uploads
        $this->dispatch( 'media-uploaded' );

        // Show success message
        if ( $this->uploadedCount > 0 ) {
            $this->dispatch( 'toast', [
                'type'    => 'success',
                'message' => __( ':count file(s) uploaded successfully', ['count' => $this->uploadedCount] ),
            ] );
        }

        // Show error messages
        if ( count( $this->uploadErrors ) > 0 ) {
            foreach ( $this->uploadErrors as $error ) {
                $this->dispatch( 'toast', [
                    'type'    => 'error',
                    'message' => $error,
                ] );
            }
        }
    }

    /**
     * Remove a file from the upload queue.
     *
     * @since 1.0.0
     *
     * @param  int  $index  The file index to remove.
     */
    public function removeFile( int $index ): void
    {
        if ( isset( $this->files[ $index ] ) ) {
            unset( $this->files[ $index ] );
            $this->files = array_values( $this->files ); // Re-index array
        }
    }

    /**
     * Clear all files from the upload queue.
     *
     * @since 1.0.0
     */
    public function clearFiles(): void
    {
        $this->files          = [];
        $this->droppedFiles   = [];
        $this->uploadedMedia  = [];
        $this->uploadErrors   = [];
        $this->uploadProgress = 0;
        $this->totalFiles     = 0;
        $this->uploadedCount  = 0;
    }

    /**
     * Clear uploaded media list.
     *
     * @since 1.0.0
     */
    #[On( 'clear-uploaded' )]
    public function clearUploaded(): void
    {
        $this->uploadedMedia  = [];
        $this->uploadErrors   = [];
        $this->uploadProgress = 0;
        $this->uploadedCount  = 0;
    }

    /**
     * Clear the "AI suggested" badge as soon as the user edits alt text.
     *
     * @since 1.3.0
     */
    public function updatedMetadataAltText(): void
    {
        if ( $this->suppressNextAltTextHook ) {
            $this->suppressNextAltTextHook = false;

            return;
        }

        $this->altTextIsAiSuggested = false;
        $this->altTextAiNote        = '';
    }

    /**
     * Clear the "AI suggested" badge as soon as the user edits the
     * description.
     *
     * @since 1.3.0
     */
    public function updatedMetadataDescription(): void
    {
        if ( $this->suppressNextDescriptionHook ) {
            $this->suppressNextDescriptionHook = false;

            return;
        }

        $this->descriptionIsAiSuggested = false;
    }

    /**
     * Suggest alt text for the first pending image upload using the
     * `ai.alt_text` agent.
     *
     * @since 1.3.0
     */
    public function suggestAltText(): void
    {
        $file = $this->firstImageUpload();

        if ( ! $this->guardUploadAi( 'ai.alt_text', $file ) ) {
            return;
        }

        $this->runAi( function () use ( $file ): void {
            $result = AltTextGenerationAgent::for( [
                'source' => 'path',
                'value'  => $file->getRealPath(),
            ] )->run();

            $altText  = trim( (string) ( $result['alt_text'] ?? '' ) );
            $warnings = (array) ( $result['warnings'] ?? [] );

            Log::info( 'MediaUpload::suggestAltText result', [
                'alt_text_length' => strlen( $altText ),
                'warnings'        => $warnings,
            ] );

            if ( '' === $altText ) {
                // Some models still return an empty string despite the
                // "always describe" prompt. Fall back to a filename-based
                // stub so the user is never left with a silently empty
                // field — they can still edit it before uploading.
                $altText = $this->filenameFallbackAltText( $file->getClientOriginalName() );

                $this->altTextAiNote = __(
                    'AI could not confidently describe this image; a filename-based placeholder was inserted.',
                );
            } else {
                $this->altTextAiNote = '';
            }

            $this->metadata['alt_text']    = $altText;
            $this->altTextIsAiSuggested    = true;
            $this->suppressNextAltTextHook = true;
            $this->js( sprintf(
                "\$wire.set('metadata.alt_text', %s)",
                json_encode( $altText, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ) );
        } );
    }

    /**
     * Suggest a paragraph-length description for the first pending
     * image upload using the `media.image_description` agent.
     *
     * @since 1.3.0
     */
    public function suggestDescription(): void
    {
        $file = $this->firstImageUpload();

        if ( ! $this->guardUploadAi( 'media.image_description', $file ) ) {
            return;
        }

        $this->runAi( function () use ( $file ): void {
            $result = ImageDescriptionAgent::for( [
                'image'  => [ 'source' => 'path', 'value' => $file->getRealPath() ],
                'length' => $this->aiDescriptionLength,
            ] )->run();

            $description = (string) ( $result['description'] ?? '' );

            if ( '' === $description ) {
                $this->reportAiInfo( __( 'The AI did not produce a description for this image.' ) );

                return;
            }

            $this->metadata['description']     = $description;
            $this->descriptionIsAiSuggested    = true;
            $this->suppressNextDescriptionHook = true;
            $this->js( sprintf(
                "\$wire.set('metadata.description', %s)",
                json_encode( $description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ) );

            $this->reportAiSuccess( __( 'AI-suggested description populated.' ) );
        } );
    }

    /**
     * Whether the pending upload set contains at least one image the AI
     * features can act on. Used by the Blade view to hide the AI buttons
     * when nothing selectable is queued.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public function hasImageUpload(): bool
    {
        return null !== $this->firstImageUpload();
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
        return view( 'media::livewire.pages.media-upload' );
    }

    /**
     * Feature-toggle + image-payload guard used before dispatching an
     * upload-time agent run.
     *
     * @since 1.3.0
     *
     * @param  string                       $featureKey  Feature key to check.
     * @param  TemporaryUploadedFile|null   $file        Selected image upload, if any.
     *
     * @return bool True if the caller may proceed.
     */
    protected function guardUploadAi( string $featureKey, ?TemporaryUploadedFile $file ): bool
    {
        if ( ! $this->isAiAvailable() ) {
            $this->reportAiError( __( 'AI features are not installed.' ) );

            return false;
        }

        if ( ! $this->isAiFeatureEnabled( $featureKey ) ) {
            $this->reportAiError( __( 'This AI feature is disabled.' ) );

            return false;
        }

        if ( null === $file ) {
            $this->reportAiError( __( 'Add an image to the upload queue before requesting an AI suggestion.' ) );

            return false;
        }

        return true;
    }

    /**
     * Locate the first image-typed pending upload across `$files` and
     * `$droppedFiles`. Returns null if none of the queued uploads is an
     * image the vision agents can process.
     *
     * @since 1.3.0
     *
     * @return TemporaryUploadedFile|null
     */
    protected function firstImageUpload(): ?TemporaryUploadedFile
    {
        foreach ( array_merge( $this->files, $this->droppedFiles ) as $file ) {
            if ( $file instanceof TemporaryUploadedFile
                && str_starts_with( (string) $file->getMimeType(), 'image/' )
            ) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Collect all files from both wire:model and drag-and-drop sources.
     *
     * @since 1.1.0
     *
     * @return array<int, TemporaryUploadedFile>
     */
    protected function collectFilesForUpload(): array
    {
        $processedFiles = [];
        $wireModelCount = 0;
        $droppedCount   = 0;

        // Add files from wire:model (Choose Files button)
        foreach ( $this->files as $file ) {
            if ( $file instanceof TemporaryUploadedFile ) {
                $processedFiles[] = $file;
                $wireModelCount++;
            }
        }

        // Add files from drag-and-drop
        foreach ( $this->droppedFiles as $fileReference ) {
            // Livewire automatically hydrates TemporaryUploadedFile objects
            if ( $fileReference instanceof TemporaryUploadedFile ) {
                $processedFiles[] = $fileReference;
                $droppedCount++;
            } elseif ( is_string( $fileReference ) && str_starts_with( $fileReference, 'livewire-file:' ) ) {
                // Fallback for string references (shouldn't happen with uploadMultiple, but just in case)
                $filename = str_replace( 'livewire-file:', '', $fileReference );
                $tempFile = TemporaryUploadedFile::unserializeFromLivewireRequest( $filename );
                if ( $tempFile ) {
                    $processedFiles[] = $tempFile;
                    $droppedCount++;
                } else {
                    Log::warning( 'Failed to unserialize file from Livewire request' );
                }
            } else {
                Log::warning( 'Unknown file reference type encountered', ['type' => gettype( $fileReference )] );
            }
        }

        Log::debug( 'Files collected for upload', [
            'wire_model_count' => $wireModelCount,
            'dropped_count'    => $droppedCount,
            'total'            => count( $processedFiles ),
        ] );

        return $processedFiles;
    }

    /**
     * Build upload options from metadata.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    protected function buildUploadOptions(): array
    {
        $options = [
            'folder_id' => $this->folderId,
        ];

        // Add metadata if provided
        if ( ! empty( $this->metadata['title'] ) ) {
            $options['title'] = $this->metadata['title'];
        }
        if ( ! empty( $this->metadata['alt_text'] ) ) {
            $options['alt_text'] = $this->metadata['alt_text'];
        }
        if ( ! empty( $this->metadata['caption'] ) ) {
            $options['caption'] = $this->metadata['caption'];
        }
        if ( ! empty( $this->metadata['description'] ) ) {
            $options['description'] = $this->metadata['description'];
        }

        return $options;
    }

    /**
     * Process files using standard (non-streaming) upload.
     *
     * @since 1.1.0
     *
     * @param  array<int, TemporaryUploadedFile>  $files  Files to upload.
     * @param  array<string, mixed>  $options  Upload options.
     */
    protected function processFilesStandard( array $files, array $options ): void
    {
        $uploadService  = app( MediaUploadService::class );
        $processedCount = 0;

        foreach ( $files as $file ) {
            try {
                $media                 = $uploadService->upload( $file, $options );
                $this->uploadedMedia[] = $media;
                $this->uploadedCount++;
            } catch ( Exception $e ) {
                $this->uploadErrors[] = __( 'Failed to upload :filename: :error', [
                    'filename' => $file->getClientOriginalName(),
                    'error'    => $e->getMessage(),
                ] );
            }

            // Increment processed count regardless of success/failure
            $processedCount++;

            // Update progress based on processed files (not just successful uploads)
            $this->uploadProgress = (int) ( ( $processedCount / $this->totalFiles ) * 100 );
        }
    }
}
