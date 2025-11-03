---
title: Integration
---

# Integration

This section covers integrating the Media Library with the Digital Shopfront CMS, setting up permissions, and customizing the package to fit your needs.

## Integration Guides

### [CMS Module Integration](./integration/cms-module.md)

Integration with Digital Shopfront CMS:
- Module overview and features
- Admin pages (Library, Upload, Settings)
- Settings integration and runtime overrides
- Menu integration
- Role and permission setup
- Extending the module
- Module structure
- Testing

### [Permissions & Access Control](./integration/permissions.md)

Capability-based permissions system:
- Permission capabilities (view, upload, edit, delete)
- Policy-based authorization
- Setting up roles (Administrator, Editor, Author, Contributor)
- Checking permissions (controllers, Blade, code)
- Custom permission hooks
- Ownership-based permissions
- Folder permissions
- API authentication
- Testing permissions

### [Customization](./integration/customization.md)

Customizing the package for your needs:
- Publishing assets (config, views, migrations)
- Custom image sizes
- Custom storage disks (S3, GCS, Azure)
- Customizing views
- Extending models
- Custom upload processing
- Custom validation rules
- Hooks and filters
- Custom Livewire components

## Quick Integration Examples

### Set Up Admin Role

```php
use ArtisanPackUI\CMSFramework\Modules\Users\Models\Role;

$admin = Role::where('slug', 'administrator')->first();
$admin->capabilities = array_merge($admin->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit',
    'media.delete',
]);
$admin->save();
```

### Register Custom Image Size

```php
// In AppServiceProvider::boot()
apRegisterImageSize('product-thumbnail', 400, 400, true);
apRegisterImageSize('hero-banner', 1920, 600, true);
```

### Configure S3 Storage

```php
// config/filesystems.php
's3-media' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_MEDIA_BUCKET'),
],
```

```env
# .env
MEDIA_DISK=s3-media
```

### Custom Upload Hook

```php
addAction('media.after_upload', function ($media) {
    // Send notification
    Notification::send($admins, new MediaUploadedNotification($media));
});
```

## Next Steps

- Review [CMS Module](./integration/cms-module.md) for full CMS integration
- Set up [Permissions](./integration/permissions.md) for your roles
- Explore [Customization](./integration/customization.md) options
- See [Usage](./usage.md) for day-to-day operations
