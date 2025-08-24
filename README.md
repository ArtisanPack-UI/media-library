# ArtisanPack UI Media Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/artisanpack-ui/media-library.svg?style=flat-square)](https://packagist.org/packages/artisanpack-ui/media-library)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/artisanpack-ui/media-library/run-tests?label=tests)](https://github.com/artisanpack-ui/media-library/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/artisanpack-ui/media-library/Check%20&%20fix%20styling?label=code%20style)](https://github.com/artisanpack-ui/media-library/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/artisanpack-ui/media-library.svg?style=flat-square)](https://packagist.org/packages/artisanpack-ui/media-library)

A comprehensive Laravel package for managing media files with support for categories, tags, accessibility features, and RESTful API endpoints. Built with Laravel 12+ and designed for modern web applications.

## Features

- **File Upload Management**: Secure file uploads with validation and storage optimization
- **Media Organization**: Categorize and tag media items for better organization
- **Accessibility Support**: Built-in alt text, captions, and decorative image handling
- **RESTful API**: Complete CRUD operations via API endpoints
- **Authentication Integration**: Laravel Sanctum authentication support
- **Policy-Based Authorization**: Granular permissions for media operations
- **Performance Optimized**: Efficient database queries and memory usage
- **Comprehensive Testing**: Full test coverage with feature and performance tests

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Laravel Sanctum 4.1 or higher

## Quick Installation

```bash
# Install the package
composer require artisanpack-ui/media-library

# Publish and run migrations
php artisan vendor:publish --tag="media-library-migrations"
php artisan migrate

# Publish configuration (optional)
php artisan vendor:publish --tag="media-library-config"
```

## Documentation

📚 **[Complete Documentation](docs/)**

- **[Installation Guide](docs/installation.md)** - Detailed installation and setup instructions
- **[Configuration](docs/configuration.md)** - Configuration options and environment setup
- **[Usage Guide](docs/usage.md)** - Comprehensive usage examples and tutorials
- **[API Documentation](docs/api.md)** - Complete REST API reference
- **[Migration Guide](docs/migration.md)** - Migrating from other media libraries
- **[Testing](docs/testing.md)** - Testing strategies and examples
- **[Performance & Troubleshooting](docs/performance.md)** - Optimization and common issues
- **[Contributing](docs/contributing.md)** - Development and contribution guidelines

## Quick Start

### Basic Usage

```php
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;

// Upload a file
$media = app(MediaManager::class)->upload(
    file: $request->file('upload'),
    altText: 'Description of the image',
    caption: 'Optional caption'
);

// Retrieve media
$mediaItems = Media::with(['mediaCategories', 'mediaTags'])
    ->where('user_id', auth()->id())
    ->paginate(15);
```

### API Endpoints

```http
# Upload media
POST /api/media/items

# List media
GET /api/media/items

# Get single media
GET /api/media/items/{id}

# Update media
PUT /api/media/items/{id}

# Delete media  
DELETE /api/media/items/{id}
```

For detailed API documentation, see **[API Reference](docs/api.md)**.

## Contributing

We welcome contributions! Please see **[Contributing Guide](docs/contributing.md)** for details on:

- Development setup
- Code style guidelines
- Testing requirements
- Submission process

## Security

If you discover a security vulnerability, please send an email to security@artisanpack.com. All security vulnerabilities will be promptly addressed.

## Credits

- [Jacob Martella](https://github.com/jacobmartella)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

Please see **[CHANGELOG.md](CHANGELOG.md)** for more information on what has changed recently.

