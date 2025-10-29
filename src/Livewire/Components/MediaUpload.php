<?php

declare( strict_types = 1 );

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * MediaUpload Livewire component for uploading media files.
 *
 * Provides a drag-and-drop interface for uploading single or multiple files
 * with real-time validation, progress tracking, and file previews.
 *
 * @since 1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class MediaUpload extends Component
{
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
	 * Get total count of all files (both selected and dropped).
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function getTotalFilesCountProperty(): int
	{
		return count( $this->files ) + count( $this->droppedFiles );
	}

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
	 * Handle files being updated (selected via Choose Files button).
	 *
	 * @since 1.0.0
	 */
	public function updatedFiles(): void
	{
		// Filter out any null entries
		$this->files = array_values( array_filter( $this->files, fn( $file ) => null !== $file && $file instanceof TemporaryUploadedFile ) );

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
		\Log::info( 'processUpload called', [
			'files_count'        => count( $this->files ),
			'droppedFiles_count' => count( $this->droppedFiles ),
			'files'              => $this->files,
			'droppedFiles'       => $this->droppedFiles,
		] );

		// Merge files from both sources (Choose Files button and drag-and-drop)
		$processedFiles = [];

		// Add files from wire:model (Choose Files button)
		foreach ( $this->files as $file ) {
			if ( $file instanceof TemporaryUploadedFile ) {
				\Log::info( 'Added file from wire:model', [ 'filename' => $file->getClientOriginalName() ] );
				$processedFiles[] = $file;
			}
		}

		// Add files from drag-and-drop
		foreach ( $this->droppedFiles as $fileReference ) {
			\Log::info( 'Processing dropped file', [ 'reference' => $fileReference, 'type' => gettype( $fileReference ) ] );

			// Livewire automatically hydrates TemporaryUploadedFile objects
			if ( $fileReference instanceof TemporaryUploadedFile ) {
				\Log::info( 'Added file from drag-and-drop (already an object)', [ 'filename' => $fileReference->getClientOriginalName() ] );
				$processedFiles[] = $fileReference;
			} elseif ( is_string( $fileReference ) && str_starts_with( $fileReference, 'livewire-file:' ) ) {
				// Fallback for string references (shouldn't happen with uploadMultiple, but just in case)
				$filename = str_replace( 'livewire-file:', '', $fileReference );
				\Log::info( 'Unserializing file from string', [ 'filename' => $filename ] );
				$tempFile = TemporaryUploadedFile::unserializeFromLivewireRequest( $filename );
				if ( $tempFile ) {
					\Log::info( 'Successfully unserialized file', [ 'filename' => $tempFile->getClientOriginalName() ] );
					$processedFiles[] = $tempFile;
				} else {
					\Log::warning( 'Failed to unserialize file', [ 'filename' => $filename ] );
				}
			} else {
				\Log::warning( 'Unknown file reference type', [ 'type' => gettype( $fileReference ), 'value' => $fileReference ] );
			}
		}

		\Log::info( 'Total processed files', [ 'count' => count( $processedFiles ) ] );

		$allFiles = $processedFiles;

		// Check if we have any files to upload
		if ( empty( $allFiles ) ) {
			$this->addError( 'files', __( 'Please select at least one file to upload.' ) );

			return;
		}

		$this->isUploading = true;
		$this->uploadProgress = 0;
		$this->totalFiles = count( $allFiles );
		$this->uploadedCount = 0;
		$this->uploadedMedia = [];
		$this->uploadErrors = [];

		$uploadService = app( MediaUploadService::class );

		foreach ( $allFiles as $index => $file ) {
			try {
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

				$media = $uploadService->upload( $file, $options );
				$this->uploadedMedia[] = $media;
				$this->uploadedCount++;
			} catch ( \Exception $e ) {
				$this->uploadErrors[] = __( 'Failed to upload :filename: :error', [
					'filename' => $file->getClientOriginalName(),
					'error'    => $e->getMessage(),
				] );
			}

			// Update progress
			$this->uploadProgress = (int) ( ( $this->uploadedCount / $this->totalFiles ) * 100 );
		}

		$this->isUploading = false;

		// Clear files after upload
		$this->files = [];
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
		if ( 0 < $this->uploadedCount ) {
			$this->dispatch( 'toast', [
				'type'    => 'success',
				'message' => __( ':count file(s) uploaded successfully', [ 'count' => $this->uploadedCount ] ),
			] );
		}

		// Show error messages
		if ( 0 < count( $this->uploadErrors ) ) {
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
	 * @param int $index The file index to remove.
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
		$this->files = [];
		$this->droppedFiles = [];
		$this->uploadedMedia = [];
		$this->uploadErrors = [];
		$this->uploadProgress = 0;
		$this->totalFiles = 0;
		$this->uploadedCount = 0;
	}

	/**
	 * Clear uploaded media list.
	 *
	 * @since 1.0.0
	 */
	#[On( 'clear-uploaded' )]
	public function clearUploaded(): void
	{
		$this->uploadedMedia = [];
		$this->uploadErrors = [];
		$this->uploadProgress = 0;
		$this->uploadedCount = 0;
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 *
	 * @return \Illuminate\View\View
	 */
	public function render(): \Illuminate\View\View
	{
		return view( 'media::livewire.pages.media-upload' );
	}
}
