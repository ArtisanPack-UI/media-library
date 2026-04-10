---
title: Upgrading
---

# Upgrading

This guide covers upgrading between versions of the ArtisanPack UI Media Library.

- [Upgrading from v1.1 to v1.2](#upgrading-from-v11-to-v12)
- [Upgrading from v1.0 to v1.1](#upgrading-from-v10-to-v11)

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
