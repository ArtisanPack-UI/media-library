<?php

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Media Upload Service
 *
 * Handles file uploads, validation, unique filename generation,
 * metadata extraction, and Media model creation.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Services
 *
 */
class MediaUploadService
{
	/**
	 * Media storage service instance.
	 */
	protected MediaStorageService $storageService;

	/**
	 * Video processing service instance.
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
	 * @param UploadedFile         $file    The file to upload.
	 * @param array<string, mixed> $options Optional parameters (title, alt_text, caption, description, folder_id,
	 *                                      tags).
	 * @return Media The created media instance.
	 *
	 * @throws ValidationException If file validation fails.
	 */
	public function upload( UploadedFile $file, array $options = [] ): Media
	{
		// Validate the file
		$this->validateFile( $file );

		// Generate a unique file name
		$fileName = $this->generateFileName( $file );

		// Get the upload path
		$uploadPath = $this->getUploadPath( $options );

		// Full file path
		$filePath = $uploadPath . '/' . $fileName;

		// Get disk
		$disk = $options['disk'] ?? config( 'artisanpack.media.disk', 'public' );

		// Store the file
		$storedPath = $this->storageService->store( $file, $filePath, $disk );

		// Extract metadata
		$metadata = $this->extractMetadata( $file, $storedPath, $disk );

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
									'uploaded_by' => $options['uploaded_by'] ?? Auth::id(),
									'metadata'    => $metadata['additional'] ?? null,
								] );

		// Attach tags if provided
		if ( isset( $options['tags'] ) && is_array( $options['tags'] ) ) {
			$media->tags()->attach( $options['tags'] );
		}

		return $media;
	}

	/**
	 * Validate the uploaded file.
	 *
	 * @param UploadedFile $file The file to validate.
	 * @return bool True if validation passes.
	 *
	 * @throws ValidationException If validation fails.
	 */
	public function validateFile( UploadedFile $file ): bool
	{
		$allowedMimeTypes = config( 'artisanpack.media.allowed_mime_types', [] );
		$maxFileSize      = config( 'artisanpack.media.max_file_size', 10240 );

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

		return $baseName . '-' . $uniqueId . '.' . $extension;
	}

	/**
	 * Get the upload path based on the configured format.
	 *
	 * @param array<string, mixed> $options Upload options.
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
}
