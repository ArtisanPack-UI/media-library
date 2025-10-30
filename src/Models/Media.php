<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
	use HasFactory;
	use SoftDeletes;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'media';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<string>
	 */
	protected $fillable = [
		'title',
		'file_name',
		'file_path',
		'disk',
		'mime_type',
		'file_size',
		'alt_text',
		'caption',
		'description',
		'width',
		'height',
		'duration',
		'folder_id',
		'uploaded_by',
		'metadata',
	];

	/**
	 * Create a new factory instance for the model.
	 */
	protected static function newFactory(): MediaFactory
	{
		return MediaFactory::new();
	}

	/**
	 * Get the user who uploaded this media.
	 */
	public function uploadedBy(): BelongsTo
	{
		return $this->belongsTo( config( 'artisanpack.media.user_model' ), 'uploaded_by' );
	}

	/**
	 * Get the folder this media belongs to.
	 */
	public function folder(): BelongsTo
	{
		return $this->belongsTo( MediaFolder::class, 'folder_id' );
	}

	/**
	 * Get the tags associated with this media.
	 */
	public function tags(): BelongsToMany
	{
		return $this->belongsToMany( MediaTag::class, 'media_taggables' );
	}

	/**
	 * Display the image with proper escaping.
	 */
	public function displayImage( string $size = 'full', array $attributes = [] ): string
	{
		if ( ! $this->isImage() ) {
			return '';
		}

		$url = escAttr( $this->imageUrl( $size ) );
		$alt = escAttr( $this->alt_text ?? $this->title ?? '' );

		$attrString = '';
		foreach ( $attributes as $key => $value ) {
			$attrString .= ' ' . escAttr( $key ) . '="' . escAttr( $value ) . '"';
		}

		return '<img src="' . $url . '" alt="' . $alt . '"' . $attrString . ' />';
	}

	/**
	 * Check if media is an image.
	 */
	public function isImage(): bool
	{
		return str_starts_with( $this->mime_type, 'image/' );
	}

	/**
	 * Get the URL for a specific image size.
	 */
	public function imageUrl( string $size = 'thumbnail' ): ?string
	{
		if ( ! $this->isImage() ) {
			return null;
		}

		if ( 'full' === $size ) {
			return $this->url();
		}

		// Generate path for the sized image
		$pathInfo  = pathinfo( $this->file_path );
		$sizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $size . '.' . $pathInfo['extension'];

		if ( Storage::disk( $this->disk )->exists( $sizedPath ) ) {
			return Storage::disk( $this->disk )->url( $sizedPath );
		}

		// Fallback to original if sized version doesn't exist
		return $this->url();
	}

	/**
	 * Get the full URL to the media file.
	 */
	public function url(): string
	{
		return Storage::disk( $this->disk )->url( $this->file_path );
	}

	/**
	 * Check if media is a video.
	 */
	public function isVideo(): bool
	{
		return str_starts_with( $this->mime_type, 'video/' );
	}

	/**
	 * Check if media is audio.
	 */
	public function isAudio(): bool
	{
		return str_starts_with( $this->mime_type, 'audio/' );
	}

	/**
	 * Check if media is a document.
	 */
	public function isDocument(): bool
	{
		return str_starts_with( $this->mime_type, 'application/' );
	}

	/**
	 * Get human-readable file size.
	 */
	public function humanFileSize(): string
	{
		$bytes = $this->file_size;
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

		for ( $i = 0; 1024 < $bytes && ( count( $units ) - 1 ) > $i; $i++ ) {
			$bytes /= 1024;
		}

		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Get all generated image sizes for this media.
	 */
	public function getImageSizes(): array
	{
		if ( ! $this->isImage() ) {
			return [];
		}

		$sizes      = [];
		$pathInfo   = pathinfo( $this->file_path );
		$imageSizes = config( 'artisanpack.media.image_sizes', [] );

		foreach ( $imageSizes as $sizeName => $config ) {
			$sizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $sizeName . '.' . $pathInfo['extension'];

			if ( Storage::disk( $this->disk )->exists( $sizedPath ) ) {
				$sizes[ $sizeName ] = Storage::disk( $this->disk )->url( $sizedPath );
			}
		}

		return $sizes;
	}

	/**
	 * Delete all associated files and thumbnails.
	 */
	public function deleteFiles(): bool
	{
		$storage = Storage::disk( $this->disk );
		$deleted = true;

		// Delete original file
		if ( $storage->exists( $this->file_path ) ) {
			$deleted = $storage->delete( $this->file_path ) && $deleted;
		}

		// Delete all generated sizes
		if ( $this->isImage() ) {
			$pathInfo   = pathinfo( $this->file_path );
			$imageSizes = config( 'artisanpack.media.image_sizes', [] );

			foreach ( $imageSizes as $sizeName => $config ) {
				$sizedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $sizeName . '.' . $pathInfo['extension'];

				if ( $storage->exists( $sizedPath ) ) {
					$deleted = $storage->delete( $sizedPath ) && $deleted;
				}
			}

			// Delete modern format versions (WebP/AVIF)
			$modernFormats = [ 'webp', 'avif' ];
			foreach ( $modernFormats as $format ) {
				$modernPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . $format;

				if ( $storage->exists( $modernPath ) ) {
					$deleted = $storage->delete( $modernPath ) && $deleted;
				}

				// Delete sized modern formats
				foreach ( $imageSizes as $sizeName => $config ) {
					$sizedModernPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $sizeName . '.' . $format;

					if ( $storage->exists( $sizedModernPath ) ) {
						$deleted = $storage->delete( $sizedModernPath ) && $deleted;
					}
				}
			}
		}

		return $deleted;
	}

	/**
	 * Scope a query to only include images.
	 *
	 * @param Builder $query The query builder instance.
	 */
	public function scopeImages( Builder $query ): Builder
	{
		return $query->where( 'mime_type', 'like', 'image/%' );
	}

	/**
	 * Scope a query to only include videos.
	 *
	 * @param Builder $query The query builder instance.
	 */
	public function scopeVideos( Builder $query ): Builder
	{
		return $query->where( 'mime_type', 'like', 'video/%' );
	}

	/**
	 * Scope a query to only include audio files.
	 *
	 * @param Builder $query The query builder instance.
	 */
	public function scopeAudios( Builder $query ): Builder
	{
		return $query->where( 'mime_type', 'like', 'audio/%' );
	}

	/**
	 * Scope a query to only include documents.
	 *
	 * @param Builder $query The query builder instance.
	 */
	public function scopeDocuments( Builder $query ): Builder
	{
		return $query->where( 'mime_type', 'like', 'application/%' );
	}

	/**
	 * Scope a query to only include media in a specific folder.
	 *
	 * @param Builder $query    The query builder instance.
	 * @param int     $folderId The folder ID to filter by.
	 */
	public function scopeInFolder( Builder $query, int $folderId ): Builder
	{
		return $query->where( 'folder_id', $folderId );
	}

	/**
	 * Scope a query to only include media with a specific tag.
	 *
	 * @param Builder $query   The query builder instance.
	 * @param string  $tagSlug The tag slug to filter by.
	 */
	public function scopeWithTag( Builder $query, string $tagSlug ): Builder
	{
		return $query->whereHas( 'tags', function ( $q ) use ( $tagSlug ) {
			$q->where( 'slug', $tagSlug );
		} );
	}

	/**
	 * Scope a query to only include media of a specific MIME type.
	 *
	 * @param Builder $query    The query builder instance.
	 * @param string  $mimeType The MIME type to filter by.
	 */
	public function scopeByType( Builder $query, string $mimeType ): Builder
	{
		return $query->where( 'mime_type', $mimeType );
	}

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'metadata'  => 'array',
			'file_size' => 'integer',
			'width'     => 'integer',
			'height'    => 'integer',
			'duration'  => 'integer',
		];
	}
}
