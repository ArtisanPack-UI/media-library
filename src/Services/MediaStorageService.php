<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Services;

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
 * @package ArtisanPackUI\MediaLibrary\Services
 *
 * @since   1.0.0
 */
class MediaStorageService
{
    /**
     * Store an uploaded file to the specified path and disk.
     *
     * @param  UploadedFile  $file  The uploaded file to store.
     * @param  string  $path  The destination path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return string The stored file path.
     */
    public function store(UploadedFile $file, string $path, ?string $disk = null): string
    {
        $disk = $this->resolveDisk($disk);

        return $file->storeAs(
            dirname($path),
            basename($path),
            ['disk' => $disk]
        );
    }

    /**
     * Delete a file from storage.
     *
     * @param  string  $path  The file path to delete.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return bool True if deleted successfully, false otherwise.
     */
    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->delete($path);
    }

    /**
     * Check if a file exists in storage.
     *
     * @param  string  $path  The file path to check.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->exists($path);
    }

    /**
     * Get the URL for a file.
     *
     * @param  string  $path  The file path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return string The file URL.
     */
    public function url(string $path, ?string $disk = null): string
    {
        return $this->getDisk($disk)->url($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path  The file path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return string The file contents.
     */
    public function get(string $path, ?string $disk = null): string
    {
        return $this->getDisk($disk)->get($path);
    }

    /**
     * Get a filesystem disk instance.
     *
     * @param  string|null  $disk  The storage disk name (defaults to config).
     * @return Filesystem The filesystem disk instance.
     */
    public function getDisk(?string $disk = null): Filesystem
    {
        return Storage::disk($this->resolveDisk($disk));
    }

    /**
     * Put contents to a file path.
     *
     * @param  string  $path  The destination path.
     * @param  string  $contents  The contents to write.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return bool True if successful, false otherwise.
     */
    public function put(string $path, string $contents, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->put($path, $contents);
    }

    /**
     * Get the file size in bytes.
     *
     * @param  string  $path  The file path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return int The file size in bytes.
     */
    public function size(string $path, ?string $disk = null): int
    {
        return $this->getDisk($disk)->size($path);
    }

    /**
     * Get the MIME type of a file.
     *
     * @param  string  $path  The file path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return string|false The MIME type or false if unable to determine.
     */
    public function mimeType(string $path, ?string $disk = null): string|false
    {
        return $this->getDisk($disk)->mimeType($path);
    }

    /**
     * Copy a file to a new location.
     *
     * @param  string  $from  The source path.
     * @param  string  $to  The destination path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return bool True if successful, false otherwise.
     */
    public function copy(string $from, string $to, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->copy($from, $to);
    }

    /**
     * Move a file to a new location.
     *
     * @param  string  $from  The source path.
     * @param  string  $to  The destination path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return bool True if successful, false otherwise.
     */
    public function move(string $from, string $to, ?string $disk = null): bool
    {
        return $this->getDisk($disk)->move($from, $to);
    }

    /**
     * Get the absolute path to a file.
     *
     * @param  string  $path  The relative file path.
     * @param  string|null  $disk  The storage disk to use (defaults to config).
     * @return string The absolute path to the file.
     */
    public function path(string $path, ?string $disk = null): string
    {
        return $this->getDisk($disk)->path($path);
    }

    /**
     * Resolve the disk name to use, defaulting to the configured disk.
     *
     * @param  string|null  $disk  The disk name or null to use default.
     * @return string The resolved disk name.
     */
    protected function resolveDisk(?string $disk = null): string
    {
        return $disk ?? config('artisanpack.media.disk', 'public');
    }
}
