<?php

/**
 * Media Upload Service
 *
 * Handles file uploads, validation, unique filename generation,
 * metadata extraction, and Media model creation.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Services
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Events\MediaUploaded;
use ArtisanPackUI\MediaLibrary\Models\Media;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Media Upload Service
 *
 * Handles file uploads, validation, unique filename generation,
 * metadata extraction, and Media model creation.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Services
 */
class MediaUploadService
{
    /**
     * Media storage service instance.
     *
     * @since 1.0.0
     *
     * @var MediaStorageService
     */
    protected MediaStorageService $storageService;

    /**
     * Video processing service instance.
     *
     * @since 1.0.0
     *
     * @var VideoProcessingService
     */
    protected VideoProcessingService $videoService;

    /**
     * Create a new media upload service instance.
     *
     * @param MediaStorageService    $storageService The storage service instance.
     * @param VideoProcessingService $videoService   The video processing service instance.
     */
    public function __construct( MediaStorageService $storageService, VideoProcessingService $videoService )
    {
        $this->storageService = $storageService;
        $this->videoService   = $videoService;
    }

    /**
     * Upload a file and create a media record.
     *
     * @param UploadedFile         $file       The file to upload.
     * @param array<string, mixed> $options    Optional parameters (title, alt_text, caption, description, folder_id,
     *                                         tags).
     *
     * @throws ValidationException If file validation fails.
     *
     * @return Media The created media instance.
     */
    public function upload( UploadedFile $file, array $options = [] ): Media
    {
        /**
         * Fires when a media upload begins, before validation.
         *
         * Allows applications to observe uploads or short-circuit them by
         * throwing an exception. Runs on every upload attempt regardless of
         * validity.
         *
         * @since 1.4.0
         *
         * @param UploadedFile         $file    The incoming uploaded file.
         * @param array<string, mixed> $options Caller-supplied upload options.
         */
        doAction( 'ap.mediaLibrary.uploading', $file, $options );

        // Validate the file
        $this->validateFile( $file );

        // Generate a unique file name
        $fileName = $this->generateFileName( $file );

        // Get the upload path
        $uploadPath = $this->getUploadPath( $options );

        // Full file path
        $filePath = $uploadPath . '/' . $fileName;

        // Persist the caller-supplied (or configured default) disk on the
        // Media record — the storageDisk filter is applied lazily by
        // MediaStorageService and by Media::resolvedDisk() on every read,
        // so a subscriber that reroutes uploads to a per-tenant bucket
        // will still find the file when Media::url() runs later.
        $disk = $options['disk'] ?? config( 'artisanpack.media.disk', 'public' );

        // store() applies the storageDisk filter internally, so the file
        // lands on the filter-resolved disk even when we persist the
        // pre-filter value above.
        $storedPath = $this->storageService->store( $file, $filePath, $disk );

        // Extract metadata
        $metadata = $this->extractMetadata( $file, $storedPath, $disk );

        /**
         * Filters the upload options immediately before the Media record is
         * persisted.
         *
         * Applications can inject or override title, alt text, caption,
         * description, folder assignment, tags, or any other option before
         * the record is created. The file has already been stored and its
         * metadata extracted at this point.
         *
         * @since 1.4.0
         *
         * @param array<string, mixed> $options The resolved upload options.
         * @param UploadedFile         $file    The uploaded file.
         *
         * @return array<string, mixed> The (possibly modified) options.
         */
        $options = (array) applyFilters( 'ap.mediaLibrary.uploadOptions', $options, $file );

        // Create the media record
        $media = Media::create( [
                                    'title'       => $options['title'] ?? null,
                                    'file_name'   => $fileName,
                                    'file_path'   => $storedPath,
                                    'disk'        => $disk,
                                    'mime_type'   => $file->getMimeType() ?? 'application/octet-stream',
                                    'file_size'   => $file->getSize(),
                                    'alt_text'    => $options['alt_text'] ?? null,
                                    'caption'     => $options['caption'] ?? null,
                                    'description' => $options['description'] ?? null,
                                    'width'       => $metadata['width'] ?? null,
                                    'height'      => $metadata['height'] ?? null,
                                    'duration'    => $metadata['duration'] ?? null,
                                    'folder_id'   => $options['folder_id'] ?? null,
                                    'uploaded_by' => $this->resolveUploadedBy( $options ),
                                    'metadata'    => $metadata['additional'] ?? null,
                                ] );

        // Attach tags if provided
        if ( isset( $options['tags'] ) && is_array( $options['tags'] ) ) {
            $media->tags()->attach( $options['tags'] );
        }

        /**
         * Fires after a Media record has been created from an upload.
         *
         * Runs after tag attachment and before the media is returned to the
         * caller. Use this to trigger downstream processing (thumbnail
         * generation, indexing, notifications).
         *
         * @since 1.4.0
         *
         * @param Media $media The freshly created media record.
         */
        doAction( 'ap.mediaLibrary.uploaded', $media );

        /**
         * Dispatches the queueable {@see MediaUploaded} event so downstream
         * packages can subscribe with `ShouldQueue` listeners (e.g. queued
         * AI alt-text generation) without running work in the upload
         * request lifecycle. Fires immediately after the synchronous
         * `ap.mediaLibrary.uploaded` hook.
         *
         * @since 1.5.0
         */
        MediaUploaded::dispatch( $media );

        return $media;
    }

    /**
     * Validate the uploaded file.
     *
     * @param UploadedFile $file The file to validate.
     *
     * @throws ValidationException If validation fails.
     *
     * @return bool True if validation passes.
     */
    public function validateFile( UploadedFile $file ): bool
    {
        $allowedMimeTypes = $this->resolveAllowedMimeTypes();
        $maxFileSize      = $this->resolveMaxFileSize();

        // Check file size (convert to bytes)
        $maxFileSizeBytes = $maxFileSize * 1024;
        if ( $maxFileSizeBytes < $file->getSize() ) {
            throw ValidationException::withMessages( [
                                                         'file' => 'The file size exceeds the maximum allowed size of ' . $maxFileSize . ' KB.',
                                                     ] );
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if ( ! in_array( $mimeType, $allowedMimeTypes, true ) ) {
            throw ValidationException::withMessages( [
                                                         'file' => 'The file type ' . $mimeType . ' is not allowed.',
                                                     ] );
        }

        return true;
    }

    /**
     * Generate a unique file name for the uploaded file.
     *
     * @param UploadedFile $file The uploaded file.
     *
     * @return string The generated unique file name.
     */
    public function generateFileName( UploadedFile $file ): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName  = pathinfo( $file->getClientOriginalName(), PATHINFO_FILENAME );

        // Sanitize the base name
        $baseName = Str::slug( $baseName );

        // Generate unique suffix
        $uniqueId = Str::random( 8 );

        $fileName = $baseName . '-' . $uniqueId . '.' . $extension;

        /**
         * Filters the generated filename before it is used for storage.
         *
         * Applications can substitute a custom naming scheme (dates, hashes,
         * user-scoped prefixes) while preserving the file extension.
         *
         * @since 1.4.0
         *
         * @param string       $fileName The generated filename.
         * @param UploadedFile $file     The uploaded file.
         *
         * @return string The (possibly modified) filename.
         */
        return (string) applyFilters( 'ap.mediaLibrary.filenameGenerated', $fileName, $file );
    }

    /**
     * Get the upload path based on the configured format.
     *
     * @param array<string, mixed> $options Upload options.
     *
     * @return string The generated upload path.
     */
    public function getUploadPath( array $options = [] ): string
    {
        $format = config( 'artisanpack.media.upload_path_format', '{year}/{month}' );

        $replacements = [
            '{year}'    => date( 'Y' ),
            '{month}'   => date( 'm' ),
            '{day}'     => date( 'd' ),
            '{user_id}' => $options['uploaded_by'] ?? Auth::id() ?? 'guest',
        ];

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $format );
    }

    /**
     * Extract metadata from the uploaded file.
     *
     * @param UploadedFile $file       The uploaded file.
     * @param string       $storedPath The path where the file was stored.
     * @param string       $disk       The storage disk used.
     *
     * @return array<string, mixed> The extracted metadata.
     */
    public function extractMetadata( UploadedFile $file, string $storedPath, string $disk ): array
    {
        $metadata = [
            'width'      => null,
            'height'     => null,
            'duration'   => null,
            'additional' => [],
        ];

        $mimeType = $file->getMimeType();

        // Extract image dimensions
        if ( null !== $mimeType && str_starts_with( $mimeType, 'image/' ) ) {
            $imageData = $this->extractImageDimensions( $file );
            if ( null !== $imageData ) {
                $metadata['width']  = $imageData['width'];
                $metadata['height'] = $imageData['height'];
            }
        }

        // Extract video dimensions and duration
        if ( null !== $mimeType && str_starts_with( $mimeType, 'video/' ) ) {
            $videoData = $this->extractVideoMetadata( $storedPath, $disk );
            if ( null !== $videoData ) {
                $metadata['width']    = $videoData['width'] ?? null;
                $metadata['height']   = $videoData['height'] ?? null;
                $metadata['duration'] = $videoData['duration'] ?? null;
            }
        }

        return $metadata;
    }

    /**
     * Extract dimensions from an image file.
     *
     * @param UploadedFile $file The uploaded image file.
     *
     * @return array<string, int>|null Array with width and height, or null if unable to extract.
     */
    public function extractImageDimensions( UploadedFile $file ): ?array
    {
        try {
            $imagePath = $file->getRealPath();
            if ( false === $imagePath ) {
                return null;
            }

            $imageSize = getimagesize( $imagePath );
            if ( false === $imageSize ) {
                return null;
            }

            return [
                'width'  => $imageSize[0],
                'height' => $imageSize[1],
            ];
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Extract metadata from a video file using FFmpeg.
     *
     * @param string $storedPath The stored file path.
     * @param string $disk       The storage disk.
     *
     * @return array<string, mixed>|null Video metadata or null if unable to extract.
     */
    public function extractVideoMetadata( string $storedPath, string $disk ): ?array
    {
        if ( ! $this->videoService->isAvailable() ) {
            return null;
        }

        $metadata = $this->videoService->extractMetadata( $storedPath, $disk );

        return empty( $metadata ) ? null : $metadata;
    }

    /**
     * Resolve the allowed MIME types, applying the configuration filter.
     *
     * @since 1.4.0
     *
     * @return array<int, string> The allowed MIME types.
     */
    protected function resolveAllowedMimeTypes(): array
    {
        $mimes = (array) config( 'artisanpack.media.allowed_mime_types', [] );

        /**
         * Filters the list of MIME types accepted by the media library.
         *
         * Runs at every read of the configured allow-list so runtime
         * subscribers (feature flags, per-tenant policies) can adjust it
         * without touching the underlying config value.
         *
         * @since 1.4.0
         *
         * @param array<int, string> $mimes The configured allowed MIME types.
         *
         * @return array<int, string> The (possibly modified) MIME type list.
         */
        return (array) applyFilters( 'ap.mediaLibrary.allowedMimeTypes', $mimes );
    }

    /**
     * Resolve the maximum upload file size in kilobytes, applying the filter.
     *
     * @since 1.4.0
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  Optional user context. Defaults to the current auth user.
     *
     * @return int The maximum file size in kilobytes.
     */
    protected function resolveMaxFileSize( $user = null ): int
    {
        $size = (int) config( 'artisanpack.media.max_file_size', 10240 );
        $user ??= Auth::user();

        /**
         * Filters the maximum upload size (in kilobytes) allowed by the
         * media library.
         *
         * Runs at every read of the configured limit so runtime subscribers
         * (per-plan quotas, admin overrides) can adjust it. The user context
         * is provided so subscribers can vary the limit per account.
         *
         * @since 1.4.0
         *
         * @param int                                              $size The configured max file size (KB).
         * @param \Illuminate\Contracts\Auth\Authenticatable|null  $user The authenticated user, or null for guests.
         *
         * @return int The (possibly modified) max file size in KB.
         */
        return (int) applyFilters( 'ap.mediaLibrary.maxFileSize', $size, $user );
    }

    /**
     * Resolve the uploaded_by user ID based on authentication and config.
     *
     * This method determines the appropriate user ID for the uploaded_by field:
     * 1. If explicitly provided in options, use that value
     * 2. If user is authenticated, use their ID
     * 3. If guest uploads are allowed, use the configured guest_user_id (or null)
     * 4. If guest uploads are not allowed and user is not authenticated, throw an exception
     *
     * @param  array<string, mixed>  $options  Upload options that may contain 'uploaded_by'.
     *
     * @throws RuntimeException If guest uploads are not allowed and user is not authenticated.
     *
     * @return int|null The user ID or null for guest uploads.
     *
     * @since 1.1.0
     */
    protected function resolveUploadedBy( array $options ): ?int
    {
        // If explicitly provided, use that value
        if ( isset( $options['uploaded_by'] ) ) {
            return $options['uploaded_by'];
        }

        // If user is authenticated, use their ID
        $userId = Auth::id();
        if ( null !== $userId ) {
            return $userId;
        }

        // User is not authenticated - check if guest uploads are allowed
        $allowGuestUploads = config( 'artisanpack.media.allow_guest_uploads', false );

        if ( ! $allowGuestUploads ) {
            throw new RuntimeException(
                __( 'Authentication required. Guest uploads are not enabled. Set MEDIA_ALLOW_GUEST_UPLOADS=true in your .env file to allow guest uploads.' ),
            );
        }

        // Guest uploads are allowed - return the configured guest user ID (may be null)
        return config( 'artisanpack.media.guest_user_id' );
    }
}
