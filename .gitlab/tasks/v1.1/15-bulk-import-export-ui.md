# Bulk Import/Export - UI Components

## Description

Create Livewire components for bulk importing and exporting media through an intuitive admin interface with progress tracking and error reporting.

## Acceptance Criteria

- [ ] Create BulkImportModal component
- [ ] Create BulkExportModal component
- [ ] Support multiple import sources (ZIP, URLs, directory)
- [ ] Support multiple export formats (ZIP, JSON, CSV)
- [ ] Show progress bars for long operations
- [ ] Display detailed error reports
- [ ] Integrate with MediaLibrary page
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create Blade views with inline CSS

## Technical Details

### BulkImportModal Component

```php
class BulkImportModal extends Component
{
    use Toast, WithFileUploads;

    public bool $isOpen = false;
    public string $importSource = 'zip'; // zip, urls, directory
    public ?UploadedFile $zipFile = null;
    public string $urlList = '';
    public string $directoryPath = '';
    public ?int $folderId = null;
    public bool $isImporting = false;
    public array $importResults = [];

    #[On('open-bulk-import')]
    public function openModal(): void
    {
        $this->isOpen = true;
        $this->reset(['zipFile', 'urlList', 'directoryPath', 'importResults']);
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function import(): void
    {
        $this->validate($this->getRules());

        $this->isImporting = true;

        try {
            $service = app(MediaBulkImportService::class);

            $this->importResults = match($this->importSource) {
                'zip' => $service->importFromZip($this->zipFile, $this->folderId),
                'urls' => $service->importFromUrls($this->parseUrls(), $this->folderId),
                'directory' => $service->importFromDirectory($this->directoryPath, $this->folderId),
                default => throw new InvalidArgumentException('Invalid import source'),
            };

            if ($this->importResults['success'] > 0) {
                $this->addToast("{$this->importResults['success']} media items imported successfully.", 'success');
                $this->dispatch('media-imported');
            }

            if ($this->importResults['failed'] > 0) {
                $this->addToast("{$this->importResults['failed']} items failed to import.", 'error');
            }
        } catch (Exception $e) {
            $this->addToast('Import failed: ' . $e->getMessage(), 'error');
        } finally {
            $this->isImporting = false;
        }
    }

    protected function getRules(): array
    {
        return match($this->importSource) {
            'zip' => [
                'zipFile' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100MB
                'folderId' => ['nullable', 'exists:media_folders,id'],
            ],
            'urls' => [
                'urlList' => ['required', 'string'],
                'folderId' => ['nullable', 'exists:media_folders,id'],
            ],
            'directory' => [
                'directoryPath' => ['required', 'string'],
                'folderId' => ['nullable', 'exists:media_folders,id'],
            ],
            default => [],
        };
    }

    protected function parseUrls(): array
    {
        return array_filter(
            array_map('trim', explode("\n", $this->urlList)),
            fn($url) => !empty($url)
        );
    }

    #[Computed]
    public function folders()
    {
        return MediaFolder::orderBy('name')->get();
    }
}
```

### BulkExportModal Component

```php
class BulkExportModal extends Component
{
    use Toast;

    public bool $isOpen = false;
    public string $exportFormat = 'zip'; // zip, json, csv
    public bool $includeMetadata = true;
    public array $selectedMediaIds = [];
    public ?string $downloadUrl = null;
    public bool $isExporting = false;

    // Filtered export options
    public bool $useFilters = false;
    public array $exportFilters = [];

    #[On('open-bulk-export')]
    public function openModal(array $selectedMediaIds = []): void
    {
        $this->isOpen = true;
        $this->selectedMediaIds = $selectedMediaIds;
        $this->reset(['downloadUrl', 'exportFilters']);
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function export(): void
    {
        if (!$this->useFilters && 0 === count($this->selectedMediaIds)) {
            $this->addToast('No media selected for export.', 'error');
            return;
        }

        $this->isExporting = true;

        try {
            $service = app(MediaBulkExportService::class);

            if ($this->useFilters) {
                $exportPath = $service->exportFiltered($this->exportFilters, $this->exportFormat);
            } else {
                $exportPath = match($this->exportFormat) {
                    'zip' => $service->exportToZip($this->selectedMediaIds, $this->includeMetadata),
                    'json' => $service->exportToJson($this->selectedMediaIds),
                    'csv' => $service->exportToCsv($this->selectedMediaIds),
                    default => throw new InvalidArgumentException('Invalid export format'),
                };
            }

            // Generate download URL
            $this->downloadUrl = $this->generateDownloadUrl($exportPath);

            $this->addToast('Export completed successfully.', 'success');
        } catch (Exception $e) {
            $this->addToast('Export failed: ' . $e->getMessage(), 'error');
        } finally {
            $this->isExporting = false;
        }
    }

    protected function generateDownloadUrl(string $path): string
    {
        return URL::temporarySignedRoute(
            'media.download-export',
            now()->addHours(24),
            ['file' => basename($path)]
        );
    }

    public function download(): void
    {
        if (null === $this->downloadUrl) {
            $this->addToast('No export file available.', 'error');
            return;
        }

        // Redirect to download URL
        $this->redirect($this->downloadUrl);
    }
}
```

### BulkImportModal Blade View

The view should include:

1. **Modal Structure**: Full-screen or large modal
2. **Import Source Selector**: Tabs or radio buttons
3. **ZIP Upload Tab**:
   - File upload input with drag-drop
   - File size limit display
   - Folder selection dropdown
4. **URL List Tab**:
   - Textarea for URL list (one per line)
   - URL count display
   - Folder selection dropdown
5. **Directory Tab** (admin only):
   - Text input for directory path
   - Browse button (if possible)
   - Folder selection dropdown
6. **Import Button**: "Import X Files"
7. **Progress Display**: Progress bar during import
8. **Results Display**:
   - Success count
   - Failed count
   - Error list (expandable)
9. **Action Buttons**: Import, Cancel, Close

### BulkExportModal Blade View

The view should include:

1. **Modal Structure**: Full-screen or large modal
2. **Export Format Selector**: Radio buttons (ZIP, JSON, CSV)
3. **Export Options**:
   - Include metadata (for ZIP)
   - Selected items count
   - Use filters toggle
4. **Filter Options** (when use filters enabled):
   - Date range
   - File type
   - Folder
   - Tags
5. **Export Button**: "Export X Items"
6. **Progress Display**: Progress bar during export
7. **Download Section**:
   - Download URL (when ready)
   - Download button
   - Expiration notice (24 hours)
8. **Action Buttons**: Export, Download, Close

### MediaLibrary Integration

Add buttons to MediaLibrary page:

```blade
{{-- In media-library.blade.php header --}}
<div class="bulk-actions">
    <button
        wire:click="$dispatch('open-bulk-import')"
        class="btn btn-secondary"
    >
        <i class="fas fa-file-import"></i>
        Bulk Import
    </button>

    <button
        wire:click="$dispatch('open-bulk-export', { selectedMediaIds: @js($selectedMedia) })"
        class="btn btn-secondary"
        @if(empty($selectedMedia)) disabled @endif
    >
        <i class="fas fa-file-export"></i>
        Export Selected ({{ count($selectedMedia) }})
    </button>
</div>

{{-- Include modals --}}
<livewire:media::bulk-import-modal />
<livewire:media::bulk-export-modal />
```

## Dependencies

- Task 13: Bulk import service
- Task 14: Bulk export service

## Testing Requirements

- [ ] Test BulkImportModal opens and closes
- [ ] Test ZIP file upload
- [ ] Test URL list import
- [ ] Test directory import (admin only)
- [ ] Test import validation
- [ ] Test import progress tracking
- [ ] Test import error display
- [ ] Test BulkExportModal opens and closes
- [ ] Test export format selection
- [ ] Test export with selected media
- [ ] Test export with filters
- [ ] Test download URL generation
- [ ] Create BulkImportModalTest and BulkExportModalTest

## Notes

- Large imports/exports should show progress
- Consider using Livewire polling for progress updates
- Add file preview in import modal
- Show estimated file size for exports
- Add "Export All" option for filtered media
- Consider adding:
  - Import templates (CSV with metadata)
  - Scheduled exports
  - Email notification when export ready
  - Direct upload to cloud storage (S3, Google Drive, Dropbox)
- Add keyboard shortcuts:
  - Ctrl+I: Open import modal
  - Ctrl+E: Open export modal (with selection)
  - Escape: Close modals

## File Locations

- Components:
  - `src/Livewire/Components/BulkImportModal.php`
  - `src/Livewire/Components/BulkExportModal.php`
- Views:
  - `resources/views/livewire/components/bulk-import-modal.blade.php`
  - `resources/views/livewire/components/bulk-export-modal.blade.php`
- Tests:
  - `tests/Feature/BulkImportModalTest.php`
  - `tests/Feature/BulkExportModalTest.php`
