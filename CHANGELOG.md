# ArtisanPack UI Media Library Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.0] - Unreleased

### Changed

- **BREAKING**: Renamed the seven `MediaPolicy` ability filters from `ap.media.*` to `ap.mediaLibrary.abilities.*` for cross-package consistency with the `abilities.` sub-namespace pattern used elsewhere in the ArtisanPack UI ecosystem. Old hook names remain registered as deprecation aliases via `artisanpack-ui/hooks` `deprecateHook()`, so existing subscribers keep firing (an info-level deprecation notice is logged on first use). Aliases will be removed in the next major version. Requires `artisanpack-ui/hooks: ^1.3`. (#85)
  - `ap.media.viewAny` → `ap.mediaLibrary.abilities.viewAny`
  - `ap.media.view` → `ap.mediaLibrary.abilities.view`
  - `ap.media.create` → `ap.mediaLibrary.abilities.create`
  - `ap.media.update` → `ap.mediaLibrary.abilities.update`
  - `ap.media.delete` → `ap.mediaLibrary.abilities.delete`
  - `ap.media.restore` → `ap.mediaLibrary.abilities.restore`
  - `ap.media.forceDelete` → `ap.mediaLibrary.abilities.forceDelete`

## [1.3.0] - 2026-07-08

### Added

- **AI integrations** — three vision-powered features consumed by the Livewire, React, and Vue frontends alike through a single set of JSON endpoints (`POST /api/media/{id}/ai/alt-text|tags|description`).
  - `ai.alt_text` — cross-cutting alt-text agent (ships in `artisanpack-ui/ai`), wired into `MediaEdit`, `MediaUpload`, and the JSON API (#80). Media library injects a stricter default prompt so the model never returns empty for images it classifies as decorative (deferring to any operator override); both surfaces also fall back to a filename-derived stub when the model still declines.
  - `media.suggest_tags` — new `ImageTagSuggestionAgent` that picks matching tags from the existing `media_tags` taxonomy, with an opt-in `allow_new` mode for out-of-taxonomy suggestions (#81).
  - `media.image_description` — new `ImageDescriptionAgent` producing paragraph-length descriptions in short / medium / long tiers, distinct from alt text (#82).
- Shipped `MediaEdit.tsx` (React) and `MediaEdit.vue` (Vue) components gain the same "Suggest with AI" / "Describe with AI" / "Suggest tags" buttons via typed helpers exported from `resources/js/{react,vue}/utils/api.ts`. No changes to `@artisanpack-ui/react` or `@artisanpack-ui/vue` are required to adopt the feature.
- Livewire `MediaUpload` gains upload-time AI suggestions for alt text and description, operating on the pending `TemporaryUploadedFile` before the record is persisted.

### Changed

- **BREAKING**: `artisanpack-ui/ai` `^1.0` is now a required dependency. Configure credentials via `config/artisanpack.php` or the AI settings admin surface before adopting v1.3.

### Documentation

- Added the "AI features" section to the README with the endpoint contract and per-surface wiring notes.
- Added `docs/usage/ai-features.md` covering configuration, endpoints, agent inputs/outputs, and Livewire component wiring.

## [1.2.2] - 2026-06-09

### Added

- Laravel 13 support. The package now allows `laravel/framework` `^12.17|^13.0` and `illuminate/support` `^12.17.0|^13.0`, and `orchestra/testbench` `^10.0|^11.0`. PHP 8.3+ is required when using Laravel 13 (enforced by Laravel 13's own constraint); existing users on Laravel 12 remain on PHP 8.2+. The `laravel/framework` floor is aligned with `illuminate/support` (`12.17.0`); this matches the floor the package's transitive constraints have effectively required since `1.2.0`.

## [1.2.1] - 2026-05-26

### Changed

- Relaxed `artisanpack-ui/security` dependency constraint to allow `^1.0|^2.0`

### Removed

- Removed Claude-related GitHub Actions workflows (`claude.yml`, `claude-code-review.yml`)

## [1.2.0] - 2026-04-09

### Added

#### React & Vue Frontend Components

- Complete React component set (11 components) with TypeScript support for Inertia.js applications
- React hooks: `useMediaLibrary`, `useMediaPicker`, `useMediaUpload`
- Complete Vue 3 component set (12 components) with TypeScript support for Inertia.js applications
- Vue composables: `useMediaLibrary`, `useMediaPicker`, `useMediaUpload`
- Shared API utility (`api.ts`) with Sanctum-authenticated requests for both stacks
- React `Portal` utility for rendering modals outside the DOM hierarchy
- Vue `FolderNode` recursive component for folder tree rendering

#### Frontend Installation Command

- `php artisan media:install-frontend` Artisan command for one-step frontend setup
- Interactive stack selection (React or Vue) with `--stack` option
- `--force` flag to overwrite previously published files
- Automatic display of required npm peer dependencies after publishing

#### Config API Endpoint

- `GET /api/media/config` public endpoint for client-side upload validation
- Returns max file size, allowed MIME types grouped by category, allowed extensions, image sizes, and feature flags
- HTTP caching with `Cache-Control` and `ETag` headers for efficient repeated requests
- `MediaConfigController` with MIME type grouping and extension extraction

#### TypeScript Type Definitions

- Shared `media.d.ts` type definitions publishable via `media-types` tag
- Full type coverage for media items, folders, tags, and API responses

#### Publish Groups

- `media-react` tag for publishing React components and types
- `media-vue` tag for publishing Vue components and types
- `media-types` tag for publishing shared TypeScript type definitions

### Changed

- Updated license in `composer.json` from `GPL-3.0-or-later` to `MIT` to match the LICENSE file
- Added consistent file-level PHPDoc blocks with `@package`/`@subpackage` tags across all source files
- Restructured CI workflow: separated lint and test jobs, added dedicated release workflow with Packagist update
- Enabled Claude Code review and Claude Code action GitHub workflows
- Updated documentation with v1.2 frontend components section and upgrading guide

## [1.1.0] - 2026-01-23

### Added

#### Livewire 4 Streaming Uploads

- Real-time upload progress using `wire:stream` directive
- `StreamableUpload` trait for custom upload components
- Automatic fallback to polling for Livewire 3 compatibility
- Configurable streaming options via `features.streaming_upload` config

#### Visual Editor Integration

- `MediaPicker` component for embedding in visual editors and CMS platforms
- Full keyboard navigation support (arrow keys, Enter, Escape, Home, End)
- Context-based event dispatching for multiple pickers on same page
- Recently used media tracking with configurable limit
- Quick upload select mode for automatic selection after upload
- Block requirements configuration for per-block-type media constraints
- Block content helpers (`apGetBlockRequirements`, `apValidateMediaForBlock`)
- `HasBlockMedia` trait for models with block-based content

#### Media Statistics Dashboard

- `MediaStatistics` Livewire component with KPI cards
- Sparkline charts showing upload trends over configurable periods
- Storage usage, media counts, and type distribution metrics
- Optional auto-refresh with configurable interval

#### UI Enhancements

- Glass effects integration with livewire-ui-components v2.0
- Configurable blur and opacity for card overlays and modal backdrops
- Table export functionality (CSV, XLSX, PDF) via `WithTableExport` trait
- Enhanced media grid with focus indicators for keyboard navigation

#### Configuration

- `features.streaming_upload` - Enable/disable Livewire 4 streaming
- `features.streaming_fallback_interval` - Polling interval for Livewire 3
- `ui.glass_effects` - Glass effect settings (enabled, blur, opacity)
- `ui.stats_dashboard` - Statistics component settings
- `ui.table_export` - Export formats and limits
- `visual_editor` - MediaPicker settings (recently used, quick upload, picker defaults)
- `block_requirements` - Media constraints per block type

#### Documentation

- Comprehensive documentation for all v1.1 features
- Visual editor integration guide with examples
- Dashboard and statistics documentation
- Streaming uploads documentation
- Table export documentation
- Updated configuration documentation with all new options

### Changed

- Upgraded livewire-ui-components dependency to v2.0
- Enhanced `MediaModal` with keyboard navigation and focus management
- Improved test coverage to 580+ tests with 1293 assertions
- Updated README with new features overview and documentation links

### Fixed

- Improved screen reader announcements for bulk selection operations
- Fixed focus management in media selection components

## [1.0.0] - 2026-01-02

### Added

- Comprehensive Livewire component test suite with 150+ test cases covering all 8 components:
    - MediaUpload tests for file upload, validation, and progress tracking
    - MediaModal tests for selection modes, filtering, and context handling
    - MediaLibrary tests for filtering, sorting, and bulk operations
    - FolderManager tests for folder CRUD and hierarchy management
    - TagManager tests for tag CRUD operations
    - MediaEdit tests for metadata editing and tag syncing
    - MediaGrid tests for display modes and selection handling
    - MediaItem tests for individual media actions
- API endpoint tests for folder move and tag attach/detach operations
- Full test coverage for circular reference prevention in folder moves

### Changed

- Promoted from beta to stable release
- All core features now have comprehensive test coverage

### Fixed

- Improved test isolation with proper Storage and Gate mocking

## [1.0.0-beta2] - 2025-11-23

### Added

- Laravel Boost AI guidelines support for better AI-assisted development
- Laravel Pint code style checker integration
- ArtisanPack UI Pint code style configuration

### Changed

- Updated GitLab CI pipeline to use PHP 8.4 for all stages
- Updated Symfony HTTP Foundation to v7.3.7 to address security advisories

### Fixed

- Fixed GitLab CI build failures due to PHP 8.5 incompatibility
- Resolved Composer dependency conflicts with security-patched packages

## [1.0.0-beta1] - 2025-11-03

### Added

- Initial beta release of ArtisanPack UI Media Library package
- Comprehensive media library documentation
- Media manager class for media management operations
- Support for multiple modals in media library interface
- Component-based architecture for media library UI
- Media library service provider integration
- Media selection and management functionality

