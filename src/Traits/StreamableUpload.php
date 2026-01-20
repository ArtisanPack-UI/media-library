<?php

/**
 * Streamable Upload Trait
 *
 * Provides real-time upload progress streaming capabilities using Livewire's
 * wire:stream directive for enhanced user feedback during file uploads.
 *
 * @since   1.1.0
 */

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Traits;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Exception;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Streamable Upload Trait
 *
 * Adds real-time streaming upload progress capabilities to Livewire components.
 * When streaming is enabled and supported, provides real-time progress updates
 * via Livewire's wire:stream directive. Falls back to standard property updates
 * when streaming is not available.
 *
 * @since   1.1.0
 */
trait StreamableUpload
{
    /**
     * Current file being uploaded (for streaming display).
     *
     * @since 1.1.0
     */
    public string $currentFileName = '';

    /**
     * Individual file progress (0-100).
     *
     * @since 1.1.0
     */
    public int $currentFileProgress = 0;

    /**
     * Check if streaming uploads are enabled and supported.
     *
     * Streaming is enabled when:
     * - The config option `features.streaming_upload` is true
     * - Livewire supports the stream() method
     *
     * @since 1.1.0
     *
     * @return bool True if streaming is enabled and supported.
     */
    public function isStreamingEnabled(): bool
    {
        return config('artisanpack.media.features.streaming_upload', true)
            && method_exists($this, 'stream');
    }

    /**
     * Get the fallback polling interval for non-streaming uploads.
     *
     * @since 1.1.0
     *
     * @return int Polling interval in milliseconds.
     */
    public function getStreamingFallbackInterval(): int
    {
        return (int) config('artisanpack.media.features.streaming_fallback_interval', 500);
    }

    /**
     * Stream upload progress to the browser.
     *
     * Sends real-time progress updates to elements with wire:stream="upload-progress".
     *
     * @since 1.1.0
     *
     * @param  int  $progress  Overall progress percentage (0-100).
     * @param  string  $fileName  Current file being uploaded.
     * @param  int  $fileProgress  Individual file progress (0-100).
     * @param  int  $current  Current file number.
     * @param  int  $total  Total number of files.
     * @param  string|null  $status  Optional status message.
     */
    protected function streamProgress(
        int $progress,
        string $fileName,
        int $fileProgress,
        int $current,
        int $total,
        ?string $status = null
    ): void {
        if (! $this->isStreamingEnabled()) {
            // Update properties directly for non-streaming mode
            $this->uploadProgress = $progress;
            $this->currentFileName = $fileName;
            $this->currentFileProgress = $fileProgress;

            return;
        }

        $content = json_encode([
            'progress' => $progress,
            'fileName' => $fileName,
            'fileProgress' => $fileProgress,
            'current' => $current,
            'total' => $total,
            'status' => $status ?? __('Uploading :name...', ['name' => $fileName]),
        ]);

        $this->stream(
            to: 'upload-progress',
            content: $content,
            replace: true,
        );
    }

    /**
     * Stream an upload completion message.
     *
     * @since 1.1.0
     *
     * @param  int  $successCount  Number of files uploaded successfully.
     * @param  int  $errorCount  Number of files that failed.
     * @param  int  $total  Total number of files.
     */
    protected function streamComplete(int $successCount, int $errorCount, int $total): void
    {
        if (! $this->isStreamingEnabled()) {
            return;
        }

        $status = $errorCount > 0
            ? __('Uploaded :success of :total files (:errors failed)', [
                'success' => $successCount,
                'total' => $total,
                'errors' => $errorCount,
            ])
            : __('Successfully uploaded :count files', ['count' => $successCount]);

        $content = json_encode([
            'progress' => 100,
            'fileName' => '',
            'fileProgress' => 100,
            'current' => $total,
            'total' => $total,
            'status' => $status,
            'complete' => true,
            'successCount' => $successCount,
            'errorCount' => $errorCount,
        ]);

        $this->stream(
            to: 'upload-progress',
            content: $content,
            replace: true,
        );
    }

    /**
     * Stream an error message.
     *
     * @since 1.1.0
     *
     * @param  string  $fileName  The file that failed.
     * @param  string  $error  The error message.
     */
    protected function streamError(string $fileName, string $error): void
    {
        if (! $this->isStreamingEnabled()) {
            return;
        }

        $content = json_encode([
            'error' => true,
            'fileName' => $fileName,
            'message' => $error,
        ]);

        $this->stream(
            to: 'upload-errors',
            content: $content,
            replace: false, // Append errors
        );
    }

    /**
     * Process files with streaming progress.
     *
     * This method processes the upload with real-time streaming progress updates.
     * It replaces the standard processUpload method when streaming is enabled.
     *
     * @since 1.1.0
     *
     * @param  array<int, TemporaryUploadedFile>  $files  Files to upload.
     * @param  array<string, mixed>  $uploadOptions  Upload options.
     * @return array{uploaded: array<int, Media>, errors: array<int, string>}
     */
    protected function processFilesWithStreaming(array $files, array $uploadOptions = []): array
    {
        $uploadService = app(MediaUploadService::class);
        $uploadedMedia = [];
        $uploadErrors = [];
        $totalFiles = count($files);
        $successCount = 0;

        foreach ($files as $index => $file) {
            $fileName = $file->getClientOriginalName();
            $fileNumber = $index + 1;
            $baseProgress = (int) (($index / $totalFiles) * 100);

            // Stream start of file upload
            $this->streamProgress(
                progress: $baseProgress,
                fileName: $fileName,
                fileProgress: 0,
                current: $fileNumber,
                total: $totalFiles,
                status: __('Starting upload: :name', ['name' => $fileName])
            );

            try {
                // Stream processing state
                $this->streamProgress(
                    progress: $baseProgress + (int) ((1 / $totalFiles) * 50),
                    fileName: $fileName,
                    fileProgress: 50,
                    current: $fileNumber,
                    total: $totalFiles,
                    status: __('Processing: :name', ['name' => $fileName])
                );

                $media = $uploadService->upload($file, $uploadOptions);
                $uploadedMedia[] = $media;
                $successCount++;

                // Stream completion of this file
                $completedProgress = (int) (($fileNumber / $totalFiles) * 100);
                $this->streamProgress(
                    progress: $completedProgress,
                    fileName: $fileName,
                    fileProgress: 100,
                    current: $fileNumber,
                    total: $totalFiles,
                    status: __('Completed: :name', ['name' => $fileName])
                );
            } catch (Exception $e) {
                $errorMessage = __('Failed to upload :filename: :error', [
                    'filename' => $fileName,
                    'error' => $e->getMessage(),
                ]);
                $uploadErrors[] = $errorMessage;

                // Stream error
                $this->streamError($fileName, $errorMessage);

                // Continue with next file
                $this->streamProgress(
                    progress: (int) (($fileNumber / $totalFiles) * 100),
                    fileName: $fileName,
                    fileProgress: 0,
                    current: $fileNumber,
                    total: $totalFiles,
                    status: __('Failed: :name', ['name' => $fileName])
                );
            }
        }

        // Stream final completion
        $this->streamComplete($successCount, count($uploadErrors), $totalFiles);

        return [
            'uploaded' => $uploadedMedia,
            'errors' => $uploadErrors,
        ];
    }
}
