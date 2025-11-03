# Bulk Import - Service Implementation

## Description

Create a service to handle bulk importing of media files from various sources including ZIP archives, URLs, local directories, and external services (Google Drive, Dropbox, etc.).

## Acceptance Criteria

- [ ] Create MediaBulkImportService
- [ ] Support ZIP archive import
- [ ] Support URL list import
- [ ] Support local directory import
- [ ] Support external service import (Google Drive, Dropbox)
- [ ] Handle errors gracefully with detailed reporting
- [ ] Create import jobs for queue processing
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create comprehensive tests

## Technical Details

### MediaBulkImportService

```php
class MediaBulkImportService
{
    public function __construct(
        protected MediaUploadService $uploadService,
        protected MediaStorageService $storage
    ) {}

    /**
     * Import media from ZIP archive.
     *
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function importFromZip(
        UploadedFile $zipFile,
        ?int $folderId = null,
        ?int $userId = null
    ): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $extractPath = storage_path('app/temp/' . Str::random(16));

        try {
            // Extract ZIP
            $zip = new ZipArchive();
            if (true !== $zip->open($zipFile->getRealPath())) {
                throw new Exception('Failed to open ZIP archive');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Import extracted files
            $files = File::allFiles($extractPath);

            foreach ($files as $file) {
                try {
                    $uploadedFile = new UploadedFile(
                        $file->getPathname(),
                        $file->getFilename(),
                        mime_content_type($file->getPathname()),
                        null,
                        true
                    );

                    $this->uploadService->upload($uploadedFile, [
                        'folder_id' => $folderId,
                        'uploaded_by' => $userId ?? auth()->id(),
                    ]);

                    $results['success']++;
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'file' => $file->getFilename(),
                        'error' => $e->getMessage(),
                    ];
                }
            }
        } finally {
            // Cleanup temp directory
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
        }

        return $results;
    }

    /**
     * Import media from list of URLs.
     */
    public function importFromUrls(
        array $urls,
        ?int $folderId = null,
        ?int $userId = null
    ): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($urls as $url) {
            try {
                $tempPath = $this->downloadFile($url);

                $uploadedFile = new UploadedFile(
                    $tempPath,
                    basename(parse_url($url, PHP_URL_PATH)),
                    mime_content_type($tempPath),
                    null,
                    true
                );

                $this->uploadService->upload($uploadedFile, [
                    'folder_id' => $folderId,
                    'uploaded_by' => $userId ?? auth()->id(),
                ]);

                $results['success']++;

                @unlink($tempPath);
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Import media from local directory.
     */
    public function importFromDirectory(
        string $path,
        ?int $folderId = null,
        ?int $userId = null,
        bool $recursive = true
    ): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (!File::isDirectory($path)) {
            throw new InvalidArgumentException('Path is not a directory');
        }

        $files = $recursive ? File::allFiles($path) : File::files($path);

        foreach ($files as $file) {
            try {
                $uploadedFile = new UploadedFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    mime_content_type($file->getPathname()),
                    null,
                    true
                );

                $this->uploadService->upload($uploadedFile, [
                    'folder_id' => $folderId,
                    'uploaded_by' => $userId ?? auth()->id(),
                ]);

                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'file' => $file->getFilename(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Import media from Google Drive.
     */
    public function importFromGoogleDrive(
        string $folderId,
        ?int $mediaFolderId = null,
        ?int $userId = null
    ): array
    {
        // Implementation using Google Drive API
        // This requires google/apiclient package

        throw new BadMethodCallException('Google Drive import not yet implemented');
    }

    /**
     * Import media from Dropbox.
     */
    public function importFromDropbox(
        string $path,
        ?int $folderId = null,
        ?int $userId = null
    ): array
    {
        // Implementation using Dropbox API
        // This requires kunalvarma05/dropbox-php-sdk package

        throw new BadMethodCallException('Dropbox import not yet implemented');
    }

    /**
     * Download file from URL to temp location.
     */
    protected function downloadFile(string $url): string
    {
        $tempPath = storage_path('app/temp/' . Str::random(16) . '_' . basename(parse_url($url, PHP_URL_PATH)));

        $response = Http::timeout(120)->get($url);

        if (!$response->successful()) {
            throw new Exception("Failed to download file from URL: {$url}");
        }

        File::put($tempPath, $response->body());

        return $tempPath;
    }

    /**
     * Validate import source.
     */
    public function validateImportSource(string $source, $data): array
    {
        return match($source) {
            'zip' => $this->validateZip($data),
            'urls' => $this->validateUrls($data),
            'directory' => $this->validateDirectory($data),
            default => ['valid' => false, 'message' => 'Invalid import source'],
        };
    }

    protected function validateZip(UploadedFile $file): array
    {
        if ('application/zip' !== $file->getMimeType()) {
            return ['valid' => false, 'message' => 'File must be a ZIP archive'];
        }

        if ($file->getSize() > 104857600) { // 100MB
            return ['valid' => false, 'message' => 'ZIP file too large (max 100MB)'];
        }

        return ['valid' => true];
    }

    protected function validateUrls(array $urls): array
    {
        if (count($urls) > 100) {
            return ['valid' => false, 'message' => 'Too many URLs (max 100)'];
        }

        foreach ($urls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return ['valid' => false, 'message' => "Invalid URL: {$url}"];
            }
        }

        return ['valid' => true];
    }

    protected function validateDirectory(string $path): array
    {
        if (!File::isDirectory($path)) {
            return ['valid' => false, 'message' => 'Path is not a directory'];
        }

        if (!File::isReadable($path)) {
            return ['valid' => false, 'message' => 'Directory is not readable'];
        }

        return ['valid' => true];
    }
}
```

### Import Job

Create a queued job for processing large imports:

```php
class ImportMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $source,
        public mixed $data,
        public ?int $folderId = null,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        $service = app(MediaBulkImportService::class);

        $results = match($this->source) {
            'zip' => $service->importFromZip($this->data, $this->folderId, $this->userId),
            'urls' => $service->importFromUrls($this->data, $this->folderId, $this->userId),
            'directory' => $service->importFromDirectory($this->data, $this->folderId, $this->userId),
            default => throw new InvalidArgumentException('Invalid import source'),
        };

        // Notify user of completion
        event(new MediaImportCompleted($this->userId, $results));
    }
}
```

## Dependencies

- MediaUploadService (existing)
- MediaStorageService (existing)

## Testing Requirements

- [ ] Test ZIP import with valid archive
- [ ] Test ZIP import with invalid archive
- [ ] Test ZIP import with large files
- [ ] Test URL import with valid URLs
- [ ] Test URL import with invalid URLs
- [ ] Test URL import with unreachable URLs
- [ ] Test directory import
- [ ] Test recursive directory import
- [ ] Test validation methods
- [ ] Test job dispatching
- [ ] Test error handling and reporting
- [ ] Create MediaBulkImportServiceTest with 20+ tests

## Notes

- Large imports should be queued to avoid timeouts
- Provide detailed progress reporting for UI feedback
- Consider adding these features:
  - Duplicate detection (skip if file already exists)
  - Metadata extraction from filename patterns
  - Automatic tagging based on folder structure
  - Image optimization during import
  - CSV import with metadata mapping
- Add these helper functions:
  ```php
  if (!function_exists('apImportMediaFromZip')) {
      function apImportMediaFromZip(UploadedFile $zipFile, ?int $folderId = null): array
      {
          return app(MediaBulkImportService::class)->importFromZip($zipFile, $folderId);
      }
  }

  if (!function_exists('apImportMediaFromUrls')) {
      function apImportMediaFromUrls(array $urls, ?int $folderId = null): array
      {
          return app(MediaBulkImportService::class)->importFromUrls($urls, $folderId);
      }
  }
  ```

## File Locations

- Service: `src/Services/MediaBulkImportService.php`
- Job: `src/Jobs/ImportMediaJob.php`
- Event: `src/Events/MediaImportCompleted.php`
- Tests: `tests/Unit/MediaBulkImportServiceTest.php`
- Helpers: `src/helpers.php` (add to existing)
