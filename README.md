# ArtisanPack UI Media Library

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)]()
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)]()
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D12.0-red)]()

A comprehensive media management package for Laravel applications with support for image processing, folder organization, tagging, and modern image format conversion (WebP/AVIF).

## Features

- 📁 **Hierarchical Folder Organization** - Organize media into nested folders
- 🏷️ **Tag Management** - Tag media items for easy categorization
- 🖼️ **Image Processing** - Automatic thumbnail generation in multiple sizes
- 🚀 **Modern Image Formats** - Automatic conversion to WebP and AVIF
- 📦 **Storage Abstraction** - Support for multiple storage backends via Laravel's filesystem
- 🎬 **Video Support** - Video thumbnail extraction using FFmpeg (optional)
- 🔍 **Advanced Search & Filtering** - Search by name, filter by type, folder, or tag
- 🎯 **Drag & Drop Upload** - Modern upload interface with progress tracking
- 🖱️ **Media Modal Component** - Single/multi-select modal for choosing media with context support
- 🔐 **Permission-based Access Control** - Granular capability-based permissions
- 🎨 **Publishable Views** - Customize all Blade views to match your design
- 🧪 **Comprehensive Test Coverage** - Over 100 tests with 90%+ coverage

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Intervention Image 3.0 for image processing
- FFmpeg (optional, for video thumbnail extraction)

## Installation

Install via Composer:

```bash
composer require artisanpack-ui/media-library
```

## Quick Start

```php
// Upload media
$media = apUploadMedia($file, [
    'title' => 'My Image',
    'alt_text' => 'Alt text for accessibility',
    'folder_id' => 1,
]);

// Get media URL
$url = apGetMediaUrl($mediaId, 'thumbnail');

// Display image
$media = apGetMedia($mediaId);
echo $media->displayImage('large', ['class' => 'img-fluid']);
```

## Documentation

📚 **[Complete Documentation](docs/home.md)**

- **[Getting Started](docs/getting-started.md)** - Quick start guide
- **[Installation](docs/installation/installation.md)** - Detailed installation instructions
- **[Configuration](docs/installation/configuration.md)** - All configuration options
- **[Helper Functions](docs/usage/helper-functions.md)** - Common usage patterns
- **[Working with Models](docs/usage/models.md)** - Advanced model usage
- **[Livewire Components](docs/usage/livewire-components.md)** - UI component guide
- **[API Reference](docs/api/endpoints.md)** - Complete API documentation
- **[CMS Integration](docs/integration/cms-module.md)** - Digital Shopfront CMS setup
- **[Permissions](docs/integration/permissions.md)** - Access control guide
- **[Customization](docs/integration/customization.md)** - Customization options
- **[Troubleshooting](docs/reference/troubleshooting.md)** - Common issues and solutions
- **[FAQ](docs/reference/faq.md)** - Frequently asked questions

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Contributions are welcome! Please ensure all tests pass and code follows ArtisanPack UI Code Standards.

## Security

If you discover a security vulnerability, please send an email to security@artisanpack.com.

## Credits

- [Jacob Martella](https://github.com/jacobmartella)
- Intervention Image for image processing
- PHP-FFMpeg for video processing

## License

This package is proprietary software developed by ArtisanPack UI.

