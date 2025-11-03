# Bulk Export - Service Implementation

## Description

Create a service to handle bulk exporting of media files in various formats including ZIP archives, JSON/CSV metadata export, and external service export (Google Drive, Dropbox, etc.).

## Acceptance Criteria

- [ ] Create MediaBulkExportService
- [ ] Support ZIP archive export
- [ ] Support JSON metadata export
- [ ] Support CSV metadata export
- [ ] Support external service export (Google Drive, Dropbox)
- [ ] Support filtered exports (by folder, tag, collection, date range)
- [ ] Create export jobs for queue processing
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create comprehensive tests

## Technical Details

### MediaBulkExportService

```php
class MediaBulkExportService
{
    public function __construct(
        protected MediaStorageService $storage
    ) {}

    /**
     * Export media to ZIP archive.
     *
     * @param array $mediaIds Array of media IDs to export
     * @param bool $includeMetadata Include JSON metadata file
     * @return string Path to generated ZIP file
     */
    public function exportToZip(array $mediaIds, bool $includeMetadata = true): string
    {
        $zipPath = storage_path('app/exports/' . Str::random(16) . '.zip');

        // Ensure export directory exists
        if (!File::exists(dirname($zipPath))) {
            File::makeDirectory(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception('Failed to create ZIP archive');
        }

        $media = Media::whereIn('id', $mediaIds)->get();

        foreach ($media as $item) {
            $filePath = $this->storage->path($item->file_path, $item->disk);

            if (File::exists($filePath)) {
                // Add file to ZIP with structured path
                $zipFilePath = $this->getExportPath($item);
                $zip->addFile($filePath, $zipFilePath);
            }
        }

        // Add metadata JSON file
        if ($includeMetadata) {
            $metadata = $this->generateMetadataJson($media);
            $zip->addFromString('metadata.json', $metadata);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Export media metadata to JSON.
     */
    public function exportToJson(array $mediaIds, ?array $fields = null): string
    {
        $media = Media::with(['folder', 'tags', 'uploadedBy'])
            ->whereIn('id', $mediaIds)
            ->get();

        $data = $media->map(function($item) use ($fields) {
            $export = [
                'id' => $item->id,
                'title' => $item->title,
                'file_name' => $item->file_name,
                'file_path' => $item->file_path,
                'disk' => $item->disk,
                'mime_type' => $item->mime_type,
                'file_size' => $item->file_size,
                'url' => $item->url(),
                'alt_text' => $item->alt_text,
                'caption' => $item->caption,
                'description' => $item->description,
                'width' => $item->width,
                'height' => $item->height,
                'duration' => $item->duration,
                'folder' => $item->folder?->name,
                'tags' => $item->tags->pluck('name')->toArray(),
                'uploaded_by' => $item->uploadedBy->name ?? null,
                'created_at' => $item->created_at->toIso8601String(),
                'updated_at' => $item->updated_at->toIso8601String(),
            ];

            // Filter fields if specified
            if (null !== $fields) {
                $export = array_intersect_key($export, array_flip($fields));
            }

            return $export;
        });

        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'count' => $data->count(),
            'media' => $data,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Export media metadata to CSV.
     */
    public function exportToCsv(array $mediaIds, ?array $fields = null): string
    {
        $media = Media::with(['folder', 'tags', 'uploadedBy'])
            ->whereIn('id', $mediaIds)
            ->get();

        $csvPath = storage_path('app/exports/' . Str::random(16) . '.csv');

        // Ensure export directory exists
        if (!File::exists(dirname($csvPath))) {
            File::makeDirectory(dirname($csvPath), 0755, true);
        }

        $handle = fopen($csvPath, 'w');

        // Default fields if not specified
        $fields = $fields ?? [
            'id', 'title', 'file_name', 'mime_type', 'file_size',
            'width', 'height', 'folder', 'tags', 'created_at'
        ];

        // Write header
        fputcsv($handle, $fields);

        // Write data
        foreach ($media as $item) {
            $row = [];

            foreach ($fields as $field) {
                $row[] = match($field) {
                    'folder' => $item->folder?->name,
                    'tags' => $item->tags->pluck('name')->implode('; '),
                    'uploaded_by' => $item->uploadedBy->name ?? null,
                    'created_at', 'updated_at' => $item->{$field}?->toDateTimeString(),
                    default => $item->{$field},
                };
            }

            fputcsv($handle, $row);
        }

        fclose($handle);

        return $csvPath;
    }

    /**
     * Export filtered media.
     */
    public function exportFiltered(array $filters, string $format = 'zip'): string
    {
        $query = app(MediaSearchService::class)->search($filters);
        $mediaIds = $query->pluck('id')->toArray();

        return match($format) {
            'zip' => $this->exportToZip($mediaIds),
            'json' => $this->exportToJson($mediaIds),
            'csv' => $this->exportToCsv($mediaIds),
            default => throw new InvalidArgumentException('Invalid export format'),
        };
    }

    /**
     * Export to Google Drive.
     */
    public function exportToGoogleDrive(array $mediaIds, string $folderId): array
    {
        // Implementation using Google Drive API
        // This requires google/apiclient package

        throw new BadMethodCallException('Google Drive export not yet implemented');
    }

    /**
     * Export to Dropbox.
     */
    public function exportToDropbox(array $mediaIds, string $path): array
    {
        // Implementation using Dropbox API
        // This requires kunalvarma05/dropbox-php-sdk package

        throw new BadMethodCallException('Dropbox export not yet implemented');
    }

    /**
     * Generate metadata JSON for media collection.
     */
    protected function generateMetadataJson(Collection $media): string
    {
        return $this->exportToJson($media->pluck('id')->toArray());
    }

    /**
     * Get export path for media item (preserves folder structure).
     */
    protected function getExportPath(Media $media): string
    {
        $folder = $media->folder;
        $path = [];

        // Build folder path
        while (null !== $folder) {
            array_unshift($path, $folder->slug);
            $folder = $folder->parent;
        }

        $path[] = $media->file_name;

        return implode('/', $path);
    }

    /**
     * Clean up old export files.
     */
    public function cleanupOldExports(int $hoursOld = 24): int
    {
        $exportPath = storage_path('app/exports');
        $count = 0;

        if (!File::exists($exportPath)) {
            return 0;
        }

        $files = File::files($exportPath);
        $threshold = now()->subHours($hoursOld)->timestamp;

        foreach ($files as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getPathname());
                $count++;
            }
        }

        return $count;
    }
}
```

### Export Job

Create a queued job for processing large exports:

```php
class ExportMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $mediaIds,
        public string $format,
        public bool $includeMetadata,
        public int $userId
    ) {}

    public function handle(): void
    {
        $service = app(MediaBulkExportService::class);

        $exportPath = match($this->format) {
            'zip' => $service->exportToZip($this->mediaIds, $this->includeMetadata),
            'json' => $service->exportToJson($this->mediaIds),
            'csv' => $service->exportToCsv($this->mediaIds),
            default => throw new InvalidArgumentException('Invalid export format'),
        };

        // Generate download URL
        $downloadUrl = $this->generateDownloadUrl($exportPath);

        // Notify user of completion
        event(new MediaExportCompleted($this->userId, $downloadUrl));
    }

    protected function generateDownloadUrl(string $path): string
    {
        // Generate signed URL for download
        return URL::temporarySignedRoute(
            'media.download-export',
            now()->addHours(24),
            ['file' => basename($path)]
        );
    }
}
```

## Dependencies

- MediaStorageService (existing)
- MediaSearchService (Task 10)

## Testing Requirements

- [ ] Test ZIP export with single file
- [ ] Test ZIP export with multiple files
- [ ] Test ZIP export with folder structure
- [ ] Test ZIP export with metadata
- [ ] Test JSON export
- [ ] Test CSV export
- [ ] Test filtered export
- [ ] Test export path generation
- [ ] Test cleanup old exports
- [ ] Test job dispatching
- [ ] Create MediaBulkExportServiceTest with 20+ tests

## Notes

- Large exports should be queued to avoid timeouts
- Export files should be auto-deleted after 24 hours
- Consider adding these features:
  - Selective field export for CSV/JSON
  - Include thumbnails in ZIP export
  - Export with original folder structure
  - Export with collections information
  - Export with usage tracking data
  - Scheduled exports (daily/weekly/monthly)
- Add these helper functions:
  ```php
  if (!function_exists('apExportMediaToZip')) {
      function apExportMediaToZip(array $mediaIds, bool $includeMetadata = true): string
      {
          return app(MediaBulkExportService::class)->exportToZip($mediaIds, $includeMetadata);
      }
  }

  if (!function_exists('apExportMediaToJson')) {
      function apExportMediaToJson(array $mediaIds): string
      {
          return app(MediaBulkExportService::class)->exportToJson($mediaIds);
      }
  }

  if (!function_exists('apExportMediaToCsv')) {
      function apExportMediaToCsv(array $mediaIds): string
      {
          return app(MediaBulkExportService::class)->exportToCsv($mediaIds);
      }
  }
  ```

## File Locations

- Service: `src/Services/MediaBulkExportService.php`
- Job: `src/Jobs/ExportMediaJob.php`
- Event: `src/Events/MediaExportCompleted.php`
- Tests: `tests/Unit/MediaBulkExportServiceTest.php`
- Helpers: `src/helpers.php` (add to existing)
