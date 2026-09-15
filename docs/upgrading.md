---
title: Upgrading
---

# Upgrading

This guide covers upgrading between versions of the ArtisanPack UI Media Library.

- [Upgrading from v1.4 to v1.5](#upgrading-from-v14-to-v15)
- [Upgrading from v1.3 to v1.4](#upgrading-from-v13-to-v14)
- [Upgrading from v1.1 to v1.2](#upgrading-from-v11-to-v12)
- [Upgrading from v1.0 to v1.1](#upgrading-from-v10-to-v11)

---

## Upgrading from v1.4 to v1.5

The v1.5 release is fully backward compatible with no breaking changes. It adds a queueable `MediaUploaded` event, persistent optimization status tracking on the `Media` model, and a small button focus/hover polish.

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher (8.3+ on Laravel 13) |
| Laravel | 12.17+ or 13.0+ |
| artisanpack-ui/hooks | ^1.3 |

### Upgrade Steps

#### 1. Update Dependencies

```bash
composer update artisanpack-ui/media-library
```

#### 2. Run Migrations

v1.5 ships one new migration (`2026_09_14_000001_add_optimization_columns_to_media_table.php`) that adds six nullable columns and one index to the `media` table:

- `optimization_status` (string, 20)
- `optimized_at` (timestamp)
- `optimization_bytes_saved` (unsigned big integer)
- `optimization_original_size` (unsigned big integer)
- `optimization_formats` (json)
- `optimization_error` (string, 500)

```bash
php artisan migrate
```

Each column is added with `hasColumn()` guards, so re-running the migration is safe.

#### 3. Backfill Legacy Image Rows (Optional)

New uploads flow through the optimization pipeline and will land as `optimized` or `failed` on their own. If you have pre-1.5 image rows that should not render as pending in your UI, backfill them:

```bash
php artisan media:backfill-optimization-status
```

The command is idempotent — rows that already carry a status are skipped, and each write is guarded by an atomic `whereNull('optimization_status')` check so a concurrent pipeline run can't be clobbered.

#### 4. Clear Caches

```bash
php artisan config:clear
php artisan view:clear
```

### Breaking Changes

**None.** Version 1.5 is fully backward compatible with v1.4.x.

### New Features Available

#### Queueable `MediaUploaded` event

`ArtisanPackUI\MediaLibrary\Events\MediaUploaded` is dispatched from `MediaUploadService::upload()` immediately after the synchronous `ap.mediaLibrary.uploaded` hook. It carries the freshly persisted `Media` record and uses `SerializesModels`, so listeners can implement `ShouldQueue` to run alt-text generation, indexing, or notifications on a queue worker without holding the upload request open.

```php
namespace App\Listeners;

use ArtisanPackUI\MediaLibrary\Events\MediaUploaded;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateAiAltText implements ShouldQueue
{
    public function handle( MediaUploaded $event ): void
    {
        // $event->media is the fresh Media record.
    }
}
```

The event fires from every upload entry point (helper, trait, Livewire, HTTP controller) since they all funnel through the service. The synchronous `ap.mediaLibrary.uploaded` hook still fires first — nothing needs to change for existing subscribers.

#### Optimization status tracking on `Media`

`MediaProcessingService::processImage()` now flips `optimization_status` from `pending → optimized` on success, and `pending → failed` on any pipeline exception (the exception is still surfaced). Consumers can render a per-item badge from `Media::$optimization_status` (three constants live on the model: `OPTIMIZATION_STATUS_PENDING`, `OPTIMIZATION_STATUS_OPTIMIZED`, `OPTIMIZATION_STATUS_FAILED`).

`MediaResource` gains an `optimization` block with a stable shape across every media type — non-image rows return the block with all fields set to `null` so consumers can encode it as one interface rather than a discriminated union:

```json
"optimization": {
    "status": "optimized",
    "optimized_at": "2026-09-14T12:00:00Z",
    "bytes_saved": 123456,
    "original_size": 789012,
    "formats": { "webp": true, "avif": true },
    "error": null
}
```

TypeScript consumers get matching `OptimizationStatus` and `MediaOptimization` exports from `resources/types/media.d.ts`.

#### Button focus/hover polish

Package buttons now render with `cursor-pointer`, hover, and `focus-visible` affordances so keyboard and pointer users get consistent feedback across the media library UI. Purely visual — no API changes required.

---

## Upgrading from v1.3 to v1.4

The v1.4 release introduces the media pipeline hooks and renames the seven `MediaPolicy` ability filters. It contains one BREAKING change that ships with backward-compatible deprecation aliases, so existing subscribers keep firing during the upgrade window.

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher (8.3+ on Laravel 13) |
| Laravel | 12.17+ or 13.0+ |
| artisanpack-ui/hooks | ^1.3 |

### Upgrade Steps

#### 1. Update Dependencies

```bash
composer update artisanpack-ui/media-library artisanpack-ui/hooks
```

The `artisanpack-ui/hooks: ^1.3` bump is required so the renamed policy hooks can register their legacy aliases via `deprecateHook()`.

#### 2. Rename Policy Ability Hook Subscribers

If your application subscribes to any of the seven `MediaPolicy` ability filters, rename them to the new `ap.mediaLibrary.abilities.*` namespace. The old names still fire (an info-level deprecation notice is logged on first use) but will be removed in the next major version.

| Old name (deprecated) | New name |
|-----------------------|----------|
| `ap.media.viewAny` | `ap.mediaLibrary.abilities.viewAny` |
| `ap.media.view` | `ap.mediaLibrary.abilities.view` |
| `ap.media.create` | `ap.mediaLibrary.abilities.create` |
| `ap.media.update` | `ap.mediaLibrary.abilities.update` |
| `ap.media.delete` | `ap.mediaLibrary.abilities.delete` |
| `ap.media.restore` | `ap.mediaLibrary.abilities.restore` |
| `ap.media.forceDelete` | `ap.mediaLibrary.abilities.forceDelete` |

See [`docs/integration/permissions.md`](integration/permissions.md) for the current subscriber signature.

#### 3. Clear Caches

```bash
php artisan config:clear
php artisan view:clear
```

### Breaking Changes

- The seven `MediaPolicy` ability filters were renamed from `ap.media.*` to `ap.mediaLibrary.abilities.*` for cross-package consistency with the `abilities.` sub-namespace pattern used elsewhere in the ArtisanPack UI ecosystem. Legacy names remain registered as deprecation aliases; migrate before the next major release.

### New Features Available

#### Media Pipeline Hooks

Thirteen new hooks cover the upload → process → thumbnail → delete lifecycle, so applications can intercept every stage without subclassing the shipped services.

**Actions (6):**

| Hook | Fires |
|------|-------|
| `ap.mediaLibrary.uploading` | Before an upload begins |
| `ap.mediaLibrary.uploaded` | After a `Media` record is persisted |
| `ap.mediaLibrary.beforeProcess` | Before image processing runs |
| `ap.mediaLibrary.thumbnailsGenerated` | After thumbnails are written |
| `ap.mediaLibrary.beforeDelete` | Before a `Media` record is deleted |
| `ap.mediaLibrary.deleted` | After deletion completes |

**Filters (7):**

| Hook | Filters |
|------|---------|
| `ap.mediaLibrary.uploadOptions` | The options array passed to the upload service |
| `ap.mediaLibrary.filenameGenerated` | The stored filename before persistence |
| `ap.mediaLibrary.allowedMimeTypes` | The MIME allow-list |
| `ap.mediaLibrary.maxFileSize` | The maximum upload size |
| `ap.mediaLibrary.storageDisk` | The disk used for storage |
| `ap.mediaLibrary.imageSizes` | The registered image size definitions |
| `ap.mediaLibrary.altTextSuggestion` | Suggested alt text before it's applied |

See the "Pipeline hooks" section of the README and [`docs/integration/customization.md`](integration/customization.md) for payload signatures and examples.

---

## Upgrading from v1.1 to v1.2

The v1.2 release adds React and Vue component support for Inertia.js applications. It is fully backward compatible with no breaking changes.

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 12.0 or higher |
| Livewire | 3.6+ or 4.0+ |
| livewire-ui-components | ^2.0 |

### Upgrade Steps

#### 1. Update Dependencies

```bash
composer update artisanpack-ui/media-library
```

#### 2. Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

#### 3. Install Frontend Components (Optional)

If you want to use the new React or Vue components in an Inertia.js application:

```bash
# React
php artisan media:install-frontend --stack=react
npm install react@"^18.0 || ^19.0" react-dom@"^18.0 || ^19.0"
npm install -D typescript@"^5.0"

# Vue
php artisan media:install-frontend --stack=vue
npm install vue@"^3.4"
npm install -D typescript@"^5.0"
```

### Breaking Changes

**None.** Version 1.2 is fully backward compatible with v1.1.x. All existing Livewire components and API endpoints continue to work without modifications.

### New Features Available

| Feature | Description |
|---------|-------------|
| React Components | 11 components + 3 hooks for React/Inertia.js apps |
| Vue Components | 12 components + 3 composables for Vue/Inertia.js apps |
| `media:install-frontend` | Artisan command to publish frontend components |
| Config API Endpoint | `GET /api/media/config` for client-side upload validation |
| TypeScript Definitions | Shared type definitions for React/Vue consumers |

### New API Endpoint

A new public endpoint is available at `GET /api/media/config` that returns the server-side upload configuration (max file size, allowed MIME types, allowed extensions, image sizes, and feature flags). This enables client-side validation in React/Vue components. No authentication is required.

### New Artisan Command

```bash
# Interactive mode
php artisan media:install-frontend

# Specify stack
php artisan media:install-frontend --stack=react
php artisan media:install-frontend --stack=vue

# Overwrite existing files
php artisan media:install-frontend --stack=react --force
```

### New Publish Tags

| Tag | Description |
|-----|-------------|
| `media-react` | React components and type definitions |
| `media-vue` | Vue components and type definitions |
| `media-types` | Shared TypeScript type definitions |

---

## Upgrading from v1.0 to v1.1

The v1.1 release is fully backward compatible with no breaking changes.

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2 or higher |
| Laravel | 12.0 or higher |
| Livewire | 3.6+ or 4.0+ |
| livewire-ui-components | ^2.0 |

### Upgrade Steps

#### 1. Update Dependencies

```bash
composer update artisanpack-ui/media-library artisanpack-ui/livewire-ui-components
```

This will update:
- `artisanpack-ui/media-library` to v1.1
- `artisanpack-ui/livewire-ui-components` to v2.0 (required for glass effects and stats dashboard)

#### 2. Clear Caches

```bash
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

#### 3. Publish Updated Configuration (Optional)

If you want to customize the new v1.1 features, publish the updated configuration:

```bash
php artisan vendor:publish --tag=media-config --force
```

**Note:** Using `--force` will overwrite your existing configuration. If you have customizations, manually merge the new options instead (see [New Configuration Options](#new-configuration-options) below).

#### 4. Run Migrations (If Any)

```bash
php artisan migrate
```

**Note:** v1.1 does not include new migrations. Your existing database schema is compatible.

#### 5. Rebuild Assets

```bash
npm run build
```

### Breaking Changes

**None.** Version 1.1 is fully backward compatible with v1.0.x.

### New Features Available

#### For All Users

| Feature | Default | Description |
|---------|---------|-------------|
| Glass Effects | Enabled | Modern glassmorphism UI styling |
| Media Statistics | Enabled | KPI dashboard with sparklines |
| Table Export | Enabled | Export to CSV/XLSX/PDF |
| MediaPicker | Available | Visual editor integration component |
| Keyboard Navigation | Enabled | Arrow keys, Enter, Escape support |
| Recently Used Media | Enabled | Quick access to recent selections |

#### For Livewire 4 Users

| Feature | Default | Description |
|---------|---------|-------------|
| Streaming Uploads | Enabled | Real-time upload progress via `wire:stream` |

Livewire 3 users automatically get a polling-based fallback for upload progress.

### New Configuration Options

The following configuration options are new in v1.1. Add them to your `config/artisanpack.php` file under the `media` key if you published configuration previously:

#### Feature Flags

```php
'features' => [
    'streaming_upload' => env('MEDIA_STREAMING_UPLOAD', true),
    'streaming_fallback_interval' => env('MEDIA_STREAMING_FALLBACK_INTERVAL', 500),
],
```

#### UI Settings

```php
'ui' => [
    'glass_effects' => [
        'enabled' => env('MEDIA_GLASS_EFFECTS', true),
        'card_overlay' => [
            'blur' => 'md',
            'opacity' => 80,
        ],
        'modal_backdrop' => [
            'blur' => 'sm',
            'opacity' => 50,
        ],
    ],
    'stats_dashboard' => [
        'enabled' => env('MEDIA_STATS_ENABLED', true),
        'sparkline_days' => 30,
        'refresh_interval' => 0,
    ],
    'table_export' => [
        'enabled' => env('MEDIA_TABLE_EXPORT', true),
        'formats' => ['csv', 'xlsx', 'pdf'],
        'max_rows' => 10000,
    ],
],
```

#### Visual Editor Integration

```php
'visual_editor' => [
    'track_recently_used' => true,
    'recently_used_limit' => 20,
    'quick_upload_select' => true,
    'picker' => [
        'default_view' => 'grid',
        'items_per_page' => 24,
        'show_folders' => true,
        'allow_upload' => true,
    ],
],
```

#### Block Requirements

```php
'block_requirements' => [
    'default' => [
        'allowed_types' => ['image', 'video', 'audio', 'document'],
        'max_file_size' => null,
        'max_selections' => null,
    ],
    'image' => [
        'allowed_types' => ['image'],
        'max_file_size' => 5120,
        'max_selections' => 1,
    ],
    'gallery' => [
        'allowed_types' => ['image'],
        'max_file_size' => 5120,
        'max_selections' => 20,
    ],
],
```

### Opting Out of New Features

All new features can be disabled via configuration or environment variables:

```env
MEDIA_GLASS_EFFECTS=false
MEDIA_STATS_ENABLED=false
MEDIA_TABLE_EXPORT=false
MEDIA_STREAMING_UPLOAD=false
```

Or in `config/artisanpack.php`:

```php
'media' => [
    'visual_editor' => [
        'track_recently_used' => false,
    ],
],
```

### Livewire 3 vs Livewire 4

| Feature | Livewire 4 | Livewire 3 |
|---------|-----------|------------|
| Progress Updates | Real-time via `wire:stream` | Polling-based |
| Default Behavior | Automatic streaming | Automatic polling fallback |
| Configuration | `streaming_upload` | `streaming_fallback_interval` |

The package automatically detects your Livewire version. No code changes are required.

## Getting Help

If you encounter issues not covered here:

1. Check the [FAQ](Reference-Faq)
2. Review the [Troubleshooting Guide](Reference-Troubleshooting)
3. Search existing issues in the repository
4. Open a new issue with:
   - Package versions (`composer show artisanpack-ui/*`)
   - PHP and Laravel versions
   - Steps to reproduce
   - Error messages or screenshots
