---
title: Table Export
---

# Table Export

The Media Library v1.1 includes table export functionality powered by the `WithTableExport` trait from livewire-ui-components. Export your media library data to CSV, XLSX, or PDF formats.

## Overview

Table export allows users to:
- Export filtered media data to various formats
- Download reports for external processing
- Generate backups of media metadata
- Create reports for stakeholders

## Basic Usage

The MediaLibrary component includes export functionality by default:

```blade
<livewire:media-library />
```

The export buttons appear in the table toolbar when `table_export.enabled` is `true` in configuration.

## Configuration

Configure export settings in `config/artisanpack.php`:

```php
'media' => [
    'ui' => [
        'table_export' => [
            'enabled' => true,
            'formats' => ['csv', 'xlsx', 'pdf'],
            'max_rows' => 10000,
        ],
    ],
],
```

### Options

#### `enabled`

Enable or disable table export:

```php
'table_export' => [
    'enabled' => env('MEDIA_TABLE_EXPORT', true),
],
```

#### `formats`

Available export formats:

```php
'table_export' => [
    'formats' => ['csv', 'xlsx', 'pdf'],
],
```

| Format | Description | Requirements |
|--------|-------------|--------------|
| `csv` | Comma-separated values | None |
| `xlsx` | Microsoft Excel | PhpSpreadsheet |
| `pdf` | PDF document | DOMPDF or similar |

#### `max_rows`

Maximum rows to export (0 for unlimited):

```php
'table_export' => [
    'max_rows' => 10000,
],
```

## Export Buttons

### Using the Table Export Component

```blade
<x-artisanpack-table-export
    :formats="['csv', 'xlsx', 'pdf']"
    :filename="'media-export'"
/>
```

### Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `formats` | array | `['csv']` | Available export formats |
| `filename` | string | `'export'` | Base filename (without extension) |
| `tableId` | string | `'default'` | Table identifier for data source |

## Exported Data

### Default Columns

The export includes these columns by default:

| Column | Description |
|--------|-------------|
| ID | Media ID |
| Title | Media title |
| File Name | Original filename |
| Type | MIME type |
| Size | Human-readable file size |
| Folder | Folder name (or "No Folder") |
| Uploaded | Upload date/time |

### Custom Export Data

Override the `getTableExportData` method in your component:

```php
<?php

namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaLibrary;

class CustomMediaLibrary extends MediaLibrary
{
    public function getTableExportData(string $tableId = 'default'): array
    {
        $media = $this->getFilteredMedia(); // Get all filtered media

        return [
            'headers' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'title', 'label' => 'Title'],
                ['key' => 'file_name', 'label' => 'File Name'],
                ['key' => 'mime_type', 'label' => 'Type'],
                ['key' => 'file_size', 'label' => 'Size'],
                ['key' => 'folder', 'label' => 'Folder'],
                ['key' => 'tags', 'label' => 'Tags'],
                ['key' => 'uploaded_by', 'label' => 'Uploaded By'],
                ['key' => 'created_at', 'label' => 'Uploaded'],
            ],
            'rows' => $media->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title ?? '',
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'file_size' => $m->humanFileSize(),
                'folder' => $m->folder?->name ?? 'No Folder',
                'tags' => $m->tags->pluck('name')->implode(', '),
                'uploaded_by' => $m->uploadedBy?->name ?? 'Unknown',
                'created_at' => $m->created_at->format('Y-m-d H:i'),
            ])->toArray(),
            'filename' => 'media-export-' . date('Y-m-d'),
        ];
    }
}
```

## Export Methods

The `WithTableExport` trait provides these methods:

### `exportTableToCsv()`

Export to CSV format:

```php
public function exportToCsv(): StreamedResponse
{
    return $this->exportTableToCsv();
}
```

### `exportTableToXlsx()`

Export to Excel format (requires PhpSpreadsheet):

```php
public function exportToExcel(): BinaryFileResponse
{
    return $this->exportTableToXlsx();
}
```

### `exportTableToPdf()`

Export to PDF format (requires DOMPDF):

```php
public function exportToPdf(): Response
{
    return $this->exportTableToPdf();
}
```

### `canExportCsv()`, `canExportXlsx()`, `canExportPdf()`

Check if export format is available:

```php
@if($this->canExportXlsx())
    <button wire:click="exportTableToXlsx">Export to Excel</button>
@endif
```

## Filtering Before Export

Exports respect current filters:

```blade
<div>
    <!-- Filters -->
    <x-artisanpack-input
        wire:model.live="search"
        placeholder="Search..."
    />

    <x-artisanpack-select
        wire:model.live="type"
        :options="$types"
        placeholder="Filter by type"
    />

    <!-- Export includes only filtered results -->
    <x-artisanpack-table-export :formats="['csv', 'xlsx']" />

    <!-- Table -->
    <livewire:media-library />
</div>
```

## Custom Export Buttons

Create custom export UI:

```blade
<div class="flex gap-2">
    <x-artisanpack-button
        wire:click="exportTableToCsv"
        size="sm"
        icon="o-document-arrow-down"
    >
        CSV
    </x-artisanpack-button>

    @if($this->canExportXlsx())
        <x-artisanpack-button
            wire:click="exportTableToXlsx"
            size="sm"
            icon="o-table-cells"
        >
            Excel
        </x-artisanpack-button>
    @endif

    @if($this->canExportPdf())
        <x-artisanpack-button
            wire:click="exportTableToPdf"
            size="sm"
            icon="o-document"
        >
            PDF
        </x-artisanpack-button>
    @endif
</div>
```

## Dependencies

### CSV Export

CSV export works out of the box with no additional dependencies.

### XLSX Export

Install PhpSpreadsheet for Excel export:

```bash
composer require phpoffice/phpspreadsheet
```

### PDF Export

Install DOMPDF for PDF export:

```bash
composer require barryvdh/laravel-dompdf
```

## Large Exports

### Chunked Processing

For large datasets, exports are automatically chunked:

```php
// Exports process 1000 rows at a time
protected int $exportChunkSize = 1000;
```

### Queue Processing

For very large exports, consider queueing:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportMediaLibrary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public array $filters,
        public string $format
    ) {}

    public function handle(): void
    {
        // Generate export
        $exporter = new MediaExporter($this->filters);
        $path = $exporter->export($this->format);

        // Notify user
        User::find($this->userId)->notify(
            new ExportReadyNotification($path)
        );
    }
}
```

## Events

### `table-exported`

Dispatched after successful export:

```javascript
Livewire.on('table-exported', (event) => {
    console.log('Exported:', event.format, event.rows);
});
```

### Tracking Exports

Log export activity:

```php
protected function afterExport(string $format, int $rowCount): void
{
    activity()
        ->causedBy(auth()->user())
        ->performedOn($this)
        ->withProperties([
            'format' => $format,
            'rows' => $rowCount,
            'filters' => $this->getActiveFilters(),
        ])
        ->log('exported media library');
}
```

## Security

### Authorization

Exports respect the same authorization as viewing:

```php
// In MediaPolicy
public function export(User $user): bool
{
    return $user->can('view media');
}
```

### Rate Limiting

Prevent export abuse:

```php
// In your component
public function exportTableToCsv()
{
    $this->authorize('export', Media::class);

    RateLimiter::attempt(
        key: 'export:' . auth()->id(),
        maxAttempts: 5,
        callback: fn () => parent::exportTableToCsv(),
        decaySeconds: 60
    );
}
```

## Next Steps

- [Streaming Uploads](Streaming-Uploads) - Real-time upload progress
- [Livewire Components](Livewire-Components) - All component documentation
- [Configuration](Installation-Configuration#ui-settings-v11) - Export configuration
