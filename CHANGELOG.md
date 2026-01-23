# ArtisanPack UI Media Library Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

