<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Media model representing a single uploaded media item (image, video, audio, or document).
 *
 * Provides helpers for URLs, image sizes, file deletion, and query scopes by type/tag/folder.
 *
 * @since 1.0.0
 */
class Media extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $table = 'media';

    /**
     * The accessors to append to the model's array form.
     *
     * @since 1.1.0
     *
     * @var array<string>
     */
    protected $appends = [
        'url',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     *
     * @return MediaFactory The model factory instance.
     */
    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    /**
     * Get the user who uploaded this media.
     *
     * @since 1.0.0
     *
     * @return BelongsTo The relationship to the uploader user model.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo( config( 'artisanpack.media.user_model' ), 'uploaded_by' );
    }

    /**
     * Get the folder this media belongs to.
     *
     * @since 1.0.0
     *
     * @return BelongsTo The relationship to the parent folder model.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo( MediaFolder::class, 'folder_id' );
    }

    /**
     * Get the tags associated with this media.
     *
     * @since 1.0.0
     *
     * @return BelongsToMany The many-to-many relationship to tags.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany( MediaTag::class, 'media_taggables' );
    }

    /**
     * Display the image with proper escaping.
     *
     * @since 1.0.0
     *
     * @param string               $size       The image size to display. Default 'full'.
     * @param array<string, mixed> $attributes Additional HTML attributes.
     * @return string The HTML img tag or empty string if not an image.
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
     *
     * @since 1.0.0
     *
     * @return bool True if the media is an image, false otherwise.
     */
    public function isImage(): bool
    {
        return str_starts_with( $this->mime_type, 'image/' );
    }

    /**
     * Get the URL for a specific image size.
     *
     * @since 1.0.0
     *
     * @param string $size The image size name. Default 'thumbnail'.
     * @return string|null The image URL or null if not an image.
     */
    public function imageUrl( string $size = 'thumbnail' ): ?string
    {
        if ( ! $this->isImage() ) {
            return null;
        }

        if ( $size === 'full' ) {
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
     *
     * @since 1.0.0
     *
     * @return string The full URL to the media file.
     */
    public function url(): string
    {
        return Storage::disk( $this->disk )->url( $this->file_path );
    }

    /**
     * Get the URL attribute for array/JSON serialization.
     *
     * @since 1.1.0
     *
     * @return string The full URL to the media file.
     */
    public function getUrlAttribute(): string
    {
        return $this->url();
    }

    /**
     * Check if media is a video.
     *
     * @since 1.0.0
     *
     * @return bool True if the media is a video, false otherwise.
     */
    public function isVideo(): bool
    {
        return str_starts_with( $this->mime_type, 'video/' );
    }

    /**
     * Check if media is audio.
     *
     * @since 1.0.0
     *
     * @return bool True if the media is audio, false otherwise.
     */
    public function isAudio(): bool
    {
        return str_starts_with( $this->mime_type, 'audio/' );
    }

    /**
     * Check if media is a document.
     *
     * @since 1.0.0
     *
     * @return bool True if the media is a document, false otherwise.
     */
    public function isDocument(): bool
    {
        return str_starts_with( $this->mime_type, 'application/' );
    }

    /**
     * Get human-readable file size.
     *
     * @since 1.0.0
     *
     * @return string The formatted file size (e.g., "1.5 MB").
     */
    public function humanFileSize(): string
    {
        $bytes = $this->file_size;
        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

        for ( $i = 0; $bytes > 1024 && ( count( $units ) - 1 ) > $i; $i++ ) {
            $bytes /= 1024;
        }

        return round( $bytes, 2 ) . ' ' . $units[ $i ];
    }

    /**
     * Get all generated image sizes for this media.
     *
     * @since 1.0.0
     *
     * @return array<string, string> Array of size names and their URLs.
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
     *
     * @since 1.0.0
     *
     * @return bool True if all files were deleted successfully, false otherwise.
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
     * @since 1.0.0
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query builder.
     */
    public function scopeImages( Builder $query ): Builder
    {
        return $query->where( 'mime_type', 'like', 'image/%' );
    }

    /**
     * Scope a query to only include videos.
     *
     * @since 1.0.0
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query builder.
     */
    public function scopeVideos( Builder $query ): Builder
    {
        return $query->where( 'mime_type', 'like', 'video/%' );
    }

    /**
     * Scope a query to only include audio files.
     *
     * @since 1.0.0
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query builder.
     */
    public function scopeAudios( Builder $query ): Builder
    {
        return $query->where( 'mime_type', 'like', 'audio/%' );
    }

    /**
     * Scope a query to only include documents.
     *
     * @since 1.0.0
     *
     * @param Builder $query The query builder instance.
     * @return Builder The modified query builder.
     */
    public function scopeDocuments( Builder $query ): Builder
    {
        return $query->where( 'mime_type', 'like', 'application/%' );
    }

    /**
     * Scope a query to only include media in a specific folder.
     *
     * @since 1.0.0
     *
     * @param Builder $query    The query builder instance.
     * @param int     $folderId The folder ID to filter by.
     * @return Builder The modified query builder.
     */
    public function scopeInFolder( Builder $query, int $folderId ): Builder
    {
        return $query->where( 'folder_id', $folderId );
    }

    /**
     * Scope a query to only include media with a specific tag.
     *
     * @since 1.0.0
     *
     * @param Builder $query   The query builder instance.
     * @param string  $tagSlug The tag slug to filter by.
     * @return Builder The modified query builder.
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
     * @since 1.0.0
     *
     * @param Builder $query    The query builder instance.
     * @param string  $mimeType The MIME type to filter by.
     * @return Builder The modified query builder.
     */
    public function scopeByType( Builder $query, string $mimeType ): Builder
    {
        return $query->where( 'mime_type', $mimeType );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @since 1.0.0
     *
     * @return array<string, string> Array of attribute names and their cast types.
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
