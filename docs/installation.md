# Installation Guide

This guide covers the complete installation and setup process for the ArtisanPack UI Media Library package.

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Laravel Sanctum 4.1 or higher

## Installation Steps

### 1. Install via Composer

```bash
composer require artisanpack-ui/media-library
```

### 2. Publish and Run Migrations

Publish the migration files and run them to set up the database tables:

```bash
php artisan vendor:publish --tag="media-library-migrations"
php artisan migrate
```

### 3. Publish Configuration (Optional)

Optionally publish the configuration file to customize package settings:

```bash
php artisan vendor:publish --tag="media-library-config"
```

### 4. Service Provider Registration

The service provider will be automatically registered via Laravel's package auto-discovery. If you need to register it manually, add it to your `config/app.php`:

```php
'providers' => [
    // Other providers...
    ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider::class,
],
```

### 5. Laravel Sanctum Setup

Ensure Laravel Sanctum is properly configured for API authentication:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Next Steps

After installation, you'll want to:

1. [Configure the package](configuration.md) according to your needs
2. Review the [usage examples](usage.md) to get started
3. Explore the [API documentation](api.md) for endpoint details

## Troubleshooting Installation

### Common Installation Issues

#### Permission Issues
```bash
chmod -R 755 storage/app/public
php artisan storage:link
```

#### Missing Dependencies
Make sure all required PHP extensions are installed:
- `fileinfo`
- `gd` or `imagick` (for image processing)
- `json`

#### Database Issues
Verify your database configuration in `.env` and ensure the database exists before running migrations.