<?php

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Media Storage Service
 *
 * Provides an abstraction layer for media file storage operations.
 * Supports multiple storage disks and handles file operations like
 * store, delete, exists, and URL generation.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Services
 */
class MediaStorageService
{
    /**
     * Stores an uploaded file to the specified path and disk.
     *
     * @since 1.0.0
     *
     * @param UploadedFile $file  The uploaded file to store.
     * @param string       $path  The destination path.
     * @param string|null  $disk  The storage disk to use (defaults to config).
     * @param Media|null   $media Optional media context passed to the storageDisk filter.
     *
     * @return string The stored file path.
     */
    public function store( UploadedFile $file, string $path, ?string $disk = null, ?Media $media = null ): string
    {
        $disk = $this->resolveDiskFor( $disk, $media );

        return $file->storeAs(
            dirname( $path ),
            basename( $path ),
            [ 'disk' => $disk ],
        );
    }

    /**
     * Deletes a file from storage.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path to delete.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return bool True if deleted successfully, false otherwise.
     */
    public function delete( string $path, ?string $disk = null, ?Media $media = null ): bool
    {
        return $this->getDisk( $disk, $media )->delete( $path );
    }

    /**
     * Gets a filesystem disk instance.
     *
     * @since 1.0.0
     *
     * @param string|null $disk  The storage disk name (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return Filesystem The filesystem disk instance.
     */
    public function getDisk( ?string $disk = null, ?Media $media = null ): Filesystem
    {
        return Storage::disk( $this->resolveDiskFor( $disk, $media ) );
    }

    /**
     * Checks if a file exists in storage.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path to check.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return bool True if the file exists, false otherwise.
     */
    public function exists( string $path, ?string $disk = null, ?Media $media = null ): bool
    {
        return $this->getDisk( $disk, $media )->exists( $path );
    }

    /**
     * Gets the URL for a file.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return string The file URL.
     */
    public function url( string $path, ?string $disk = null, ?Media $media = null ): string
    {
        return $this->getDisk( $disk, $media )->url( $path );
    }

    /**
     * Gets the contents of a file.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return string The file contents.
     */
    public function get( string $path, ?string $disk = null, ?Media $media = null ): string
    {
        return $this->getDisk( $disk, $media )->get( $path );
    }

    /**
     * Puts contents to a file path.
     *
     * @since 1.0.0
     *
     * @param string      $path     The destination path.
     * @param string      $contents The contents to write.
     * @param string|null $disk     The storage disk to use (defaults to config).
     * @param Media|null  $media    Optional media context passed to the storageDisk filter.
     *
     * @return bool True if successful, false otherwise.
     */
    public function put( string $path, string $contents, ?string $disk = null, ?Media $media = null ): bool
    {
        return $this->getDisk( $disk, $media )->put( $path, $contents );
    }

    /**
     * Gets the file size in bytes.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return int The file size in bytes.
     */
    public function size( string $path, ?string $disk = null, ?Media $media = null ): int
    {
        return $this->getDisk( $disk, $media )->size( $path );
    }

    /**
     * Gets the MIME type of a file.
     *
     * @since 1.0.0
     *
     * @param string      $path  The file path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return false|string The MIME type or false if unable to determine.
     */
    public function mimeType( string $path, ?string $disk = null, ?Media $media = null ): string | false
    {
        return $this->getDisk( $disk, $media )->mimeType( $path );
    }

    /**
     * Copies a file to a new location.
     *
     * @since 1.0.0
     *
     * @param string      $from  The source path.
     * @param string      $to    The destination path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return bool True if successful, false otherwise.
     */
    public function copy( string $from, string $to, ?string $disk = null, ?Media $media = null ): bool
    {
        return $this->getDisk( $disk, $media )->copy( $from, $to );
    }

    /**
     * Moves a file to a new location.
     *
     * @since 1.0.0
     *
     * @param string      $from  The source path.
     * @param string      $to    The destination path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return bool True if successful, false otherwise.
     */
    public function move( string $from, string $to, ?string $disk = null, ?Media $media = null ): bool
    {
        return $this->getDisk( $disk, $media )->move( $from, $to );
    }

    /**
     * Gets the absolute path to a file.
     *
     * @since 1.0.0
     *
     * @param string      $path  The relative file path.
     * @param string|null $disk  The storage disk to use (defaults to config).
     * @param Media|null  $media Optional media context passed to the storageDisk filter.
     *
     * @return string The absolute path to the file.
     */
    public function path( string $path, ?string $disk = null, ?Media $media = null ): string
    {
        return $this->getDisk( $disk, $media )->path( $path );
    }

    /**
     * Resolves the disk name to use, applying the `ap.mediaLibrary.storageDisk`
     * filter with an optional media context so subscribers can route
     * per-tenant, per-media, or per-request without touching the storage
     * service internals.
     *
     * @since 1.4.0
     *
     * @param string|null $disk  The disk name or null to use the configured default.
     * @param Media|null  $media Optional media context passed to the filter.
     *
     * @return string The resolved disk name.
     */
    public function resolveDiskFor( ?string $disk = null, ?Media $media = null ): string
    {
        $resolved = $disk ?? config( 'artisanpack.media.disk', 'public' );

        /**
         * Filters the storage disk used for a media operation.
         *
         * Runs on every disk resolution inside the storage service, giving
         * applications a chance to route uploads or reads to a different
         * disk (per-tenant buckets, cold storage moves, etc.).
         *
         * @since 1.4.0
         *
         * @param string     $resolved The resolved disk name.
         * @param Media|null $media    The media instance the disk is being resolved for, when known.
         *
         * @return string The (possibly modified) disk name.
         */
        return (string) applyFilters( 'ap.mediaLibrary.storageDisk', $resolved, $media );
    }

    /**
     * Preserved for callers that reached into the pre-1.4.0 protected
     * `resolveDisk()` name directly. This is a naming courtesy only —
     * the public storage helpers now call `resolveDiskFor()` and will
     * NOT dispatch to a subclass override of this method. If your
     * subclass previously overrode `resolveDisk()`, move the override
     * to `resolveDiskFor()` so the public helpers see it.
     *
     * @since 1.4.0
     * @deprecated 1.4.0 Override `resolveDiskFor()` instead so subscribers receive the media context.
     *
     * @param string|null $disk The disk name or null to use the configured default.
     *
     * @return string The resolved disk name.
     */
    protected function resolveDisk( ?string $disk = null ): string
    {
        return $this->resolveDiskFor( $disk );
    }
}
