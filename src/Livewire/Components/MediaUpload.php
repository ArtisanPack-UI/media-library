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
        'title' => '',
        'alt_text' => '',
        'caption' => '',
        'description' => '',
    ];

    /**
     * Get total count of all files (both selected and dropped).
     *
     * @since 1.0.0
     */
    public function getTotalFilesCountProperty(): int
    {
        return count($this->files) + count($this->droppedFiles);
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
        return MediaFolder::orderBy('name')->get();
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
            ['key' => '', 'label' => __('No Folder')],
        ];

        foreach ($this->folders as $folder) {
            $options[] = [
                'key' => $folder->id,
                'label' => $folder->name,
            ];

            // Add children with indentation
            if ($folder->children->isNotEmpty()) {
                foreach ($folder->children as $child) {
                    $options[] = [
                        'key' => $child->id,
                        'label' => '-- '.$child->name,
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
        $this->files = array_values(array_filter($this->files, fn ($file) => $file !== null && $file instanceof TemporaryUploadedFile));

        $this->validate([
            'files.*' => [
                'file',
                'max:'.config('artisanpack.media.max_file_size'),
            ],
        ]);
    }

    /**
     * Process and upload all selected files to the media library.
     *
     * @since 1.0.0
     */
    public function processUpload(): void
    {
        Log::debug('processUpload called', [
            'files_count' => count($this->files),
            'droppedFiles_count' => count($this->droppedFiles),
        ]);

        // Merge files from both sources (Choose Files button and drag-and-drop)
        $processedFiles = $this->collectFilesForUpload();

        Log::info('Processing upload', ['total_files' => count($processedFiles)]);

        // Check if we have any files to upload
        if (empty($processedFiles)) {
            $this->addError('files', __('Please select at least one file to upload.'));

            return;
        }

        $this->isUploading = true;
        $this->uploadProgress = 0;
        $this->totalFiles = count($processedFiles);
        $this->uploadedCount = 0;
        $this->uploadedMedia = [];
        $this->uploadErrors = [];

        // Build upload options
        $uploadOptions = $this->buildUploadOptions();

        // Use streaming if enabled, otherwise use standard upload
        if ($this->isStreamingEnabled()) {
            $result = $this->processFilesWithStreaming($processedFiles, $uploadOptions);
            $this->uploadedMedia = $result['uploaded'];
            $this->uploadErrors = $result['errors'];
            $this->uploadedCount = count($result['uploaded']);
        } else {
            $this->processFilesStandard($processedFiles, $uploadOptions);
        }

        $this->isUploading = false;

        // Clear files after upload
        $this->files = [];
        $this->droppedFiles = [];

        // Reset metadata
        $this->metadata = [
            'title' => '',
            'alt_text' => '',
            'caption' => '',
            'description' => '',
        ];

        // Dispatch event to notify media library of new uploads
        $this->dispatch('media-uploaded');

        // Show success message
        if ($this->uploadedCount > 0) {
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => __(':count file(s) uploaded successfully', ['count' => $this->uploadedCount]),
            ]);
        }

        // Show error messages
        if (count($this->uploadErrors) > 0) {
            foreach ($this->uploadErrors as $error) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => $error,
                ]);
            }
        }
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
        $droppedCount = 0;

        // Add files from wire:model (Choose Files button)
        foreach ($this->files as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $processedFiles[] = $file;
                $wireModelCount++;
            }
        }

        // Add files from drag-and-drop
        foreach ($this->droppedFiles as $fileReference) {
            // Livewire automatically hydrates TemporaryUploadedFile objects
            if ($fileReference instanceof TemporaryUploadedFile) {
                $processedFiles[] = $fileReference;
                $droppedCount++;
            } elseif (is_string($fileReference) && str_starts_with($fileReference, 'livewire-file:')) {
                // Fallback for string references (shouldn't happen with uploadMultiple, but just in case)
                $filename = str_replace('livewire-file:', '', $fileReference);
                $tempFile = TemporaryUploadedFile::unserializeFromLivewireRequest($filename);
                if ($tempFile) {
                    $processedFiles[] = $tempFile;
                    $droppedCount++;
                } else {
                    Log::warning('Failed to unserialize file from Livewire request');
                }
            } else {
                Log::warning('Unknown file reference type encountered', ['type' => gettype($fileReference)]);
            }
        }

        Log::debug('Files collected for upload', [
            'wire_model_count' => $wireModelCount,
            'dropped_count' => $droppedCount,
            'total' => count($processedFiles),
        ]);

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
        if (! empty($this->metadata['title'])) {
            $options['title'] = $this->metadata['title'];
        }
        if (! empty($this->metadata['alt_text'])) {
            $options['alt_text'] = $this->metadata['alt_text'];
        }
        if (! empty($this->metadata['caption'])) {
            $options['caption'] = $this->metadata['caption'];
        }
        if (! empty($this->metadata['description'])) {
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
    protected function processFilesStandard(array $files, array $options): void
    {
        $uploadService = app(MediaUploadService::class);
        $processedCount = 0;

        foreach ($files as $file) {
            try {
                $media = $uploadService->upload($file, $options);
                $this->uploadedMedia[] = $media;
                $this->uploadedCount++;
            } catch (Exception $e) {
                $this->uploadErrors[] = __('Failed to upload :filename: :error', [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }

            // Increment processed count regardless of success/failure
            $processedCount++;

            // Update progress based on processed files (not just successful uploads)
            $this->uploadProgress = (int) (($processedCount / $this->totalFiles) * 100);
        }
    }

    /**
     * Remove a file from the upload queue.
     *
     * @since 1.0.0
     *
     * @param  int  $index  The file index to remove.
     */
    public function removeFile(int $index): void
    {
        if (isset($this->files[$index])) {
            unset($this->files[$index]);
            $this->files = array_values($this->files); // Re-index array
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
    #[On('clear-uploaded')]
    public function clearUploaded(): void
    {
        $this->uploadedMedia = [];
        $this->uploadErrors = [];
        $this->uploadProgress = 0;
        $this->uploadedCount = 0;
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
        return view('media::livewire.pages.media-upload');
    }
}
