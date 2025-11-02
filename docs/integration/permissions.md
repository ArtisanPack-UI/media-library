---
title: Permissions & Access Control
---

# Permissions & Access Control

The Media Library uses capability-based permissions for fine-grained access control. This guide covers setting up and managing permissions.

## Permission Capabilities

The package defines four main capabilities:

- `media.view` - View media library
- `media.upload` - Upload new media
- `media.edit` - Edit media metadata
- `media.delete` - Delete media

## Policy-Based Authorization

The package uses Laravel Policies for authorization checks:

```php
// In MediaPolicy.php
public function view(User $user, Media $media): bool
{
    return $user->hasCapability('media.view');
}

public function create(User $user): bool
{
    return $user->hasCapability('media.upload');
}

public function update(User $user, Media $media): bool
{
    return $user->hasCapability('media.edit');
}

public function delete(User $user, Media $media): bool
{
    return $user->hasCapability('media.delete');
}
```

## Setting Up Roles

### Administrator Role

Full access to all media features:

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

### Editor Role

Can view, upload, and edit but not delete:

```php
$editor = Role::where('slug', 'editor')->first();
$editor->capabilities = array_merge($editor->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit',
]);
$editor->save();
```

### Author Role

Can view and upload only:

```php
$author = Role::where('slug', 'author')->first();
$author->capabilities = array_merge($author->capabilities ?? [], [
    'media.view',
    'media.upload',
]);
$author->save();
```

### Contributor Role

Can view only (read-only access):

```php
$contributor = Role::where('slug', 'contributor')->first();
$contributor->capabilities = array_merge($contributor->capabilities ?? [], [
    'media.view',
]);
$contributor->save();
```

## Checking Permissions

### In Controllers

```php
// Check before action
$this->authorize('view', Media::class);
$this->authorize('create', Media::class);
$this->authorize('update', $media);
$this->authorize('delete', $media);
```

### In Blade Views

```blade
@can('view', App\Models\Media::class)
    <a href="/admin/media">Media Library</a>
@endcan

@can('create', App\Models\Media::class)
    <button>Upload Media</button>
@endcan

@can('update', $media)
    <button>Edit</button>
@endcan

@can('delete', $media)
    <button>Delete</button>
@endcan
```

### In Code

```php
// Check capability
if (auth()->user()->hasCapability('media.upload')) {
    // Allow upload
}

// Check using Gate
if (Gate::allows('create', Media::class)) {
    // Allow creation
}
```

## Custom Permission Hooks

Override permission checks using hooks:

```php
// Customize viewAny capability
addFilter('ap.media.viewAny', function($capability) {
    return 'custom.media.view';
});

// Customize create capability
addFilter('ap.media.create', function($capability) {
    return 'custom.media.upload';
});

// Other available hooks:
// ap.media.view
// ap.media.update
// ap.media.delete
// ap.media.restore
// ap.media.forceDelete
```

## Ownership-Based Permissions

Restrict users to only their own media:

```php
// In MediaPolicy.php
public function update(User $user, Media $media): bool
{
    // Check capability
    if (!$user->hasCapability('media.edit')) {
        return false;
    }
    
    // Check ownership
    return $media->uploaded_by === $user->id || $user->hasCapability('media.edit.any');
}
```

Then assign the capability:

```php
// Author can edit only their own media
$author->capabilities = array_merge($author->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit', // Own media only
]);

// Editor can edit any media
$editor->capabilities = array_merge($editor->capabilities ?? [], [
    'media.view',
    'media.upload',
    'media.edit',
    'media.edit.any', // Any media
]);
```

## Folder Permissions

Restrict access to specific folders:

```php
// In a custom policy or middleware
public function viewFolder(User $user, MediaFolder $folder): bool
{
    // Allow if user has general view capability
    if (!$user->hasCapability('media.view')) {
        return false;
    }
    
    // Check folder-specific permissions
    $allowedFolders = $user->settings['allowed_media_folders'] ?? [];
    
    // Empty array means all folders
    if (empty($allowedFolders)) {
        return true;
    }
    
    // Check if folder is in allowed list
    return in_array($folder->id, $allowedFolders);
}
```

## API Authentication

API endpoints use the same permission system via Sanctum:

```php
// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    // ... other routes
});
```

Permissions are checked in controllers:

```php
public function index(Request $request)
{
    $this->authorize('viewAny', Media::class);
    // ...
}
```

## Testing Permissions

Write tests for permission checks:

```php
test('user without view capability cannot access media', function () {
    $user = User::factory()->create();
    
    $response = actingAs($user)->get('/admin/media');
    
    $response->assertForbidden();
});

test('user with view capability can access media', function () {
    $role = Role::factory()->create([
        'capabilities' => ['media.view'],
    ]);
    
    $user = User::factory()->create();
    $user->roles()->attach($role);
    
    $response = actingAs($user)->get('/admin/media');
    
    $response->assertOk();
});
```

## Next Steps

- Learn about [Customization](./customization.md)
- Review [CMS Module Integration](./cms-module.md)
- See [Troubleshooting](../reference/troubleshooting.md)
