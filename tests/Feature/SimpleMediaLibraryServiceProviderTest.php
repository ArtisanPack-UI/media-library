<?php

declare(strict_types=1);

use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;

test('service provider class exists and is instantiable', function () {
    expect(class_exists(MediaLibraryServiceProvider::class))->toBeTrue();
    
    $app = app();
    $serviceProvider = new MediaLibraryServiceProvider($app);
    expect($serviceProvider)->toBeInstanceOf(MediaLibraryServiceProvider::class);
});

test('service provider has required methods', function () {
    expect(method_exists(MediaLibraryServiceProvider::class, 'register'))->toBeTrue();
    expect(method_exists(MediaLibraryServiceProvider::class, 'boot'))->toBeTrue();
});

test('service provider register method works', function () {
    $app = app();
    $serviceProvider = new MediaLibraryServiceProvider($app);
    
    // Should not throw exception
    $serviceProvider->register();
    
    expect(true)->toBeTrue(); // Test passes if no exception is thrown
});

test('service provider boot method works', function () {
    $app = app();
    $serviceProvider = new MediaLibraryServiceProvider($app);
    
    // Register first, then boot
    $serviceProvider->register();
    $serviceProvider->boot();
    
    expect(true)->toBeTrue(); // Test passes if no exception is thrown
});

test('media library singleton is registered in app', function () {
    expect(app()->bound('media-library'))->toBeTrue();
    
    $mediaLibrary = app('media-library');
    expect($mediaLibrary)->not->toBeNull();
});

test('media service provider is registered', function () {
    $providers = app()->getLoadedProviders();
    expect($providers)->toHaveKey('ArtisanPackUI\MediaLibrary\Features\Media\MediaServiceProvider');
});

test('migration files exist for publishing', function () {
    $migrationPath = __DIR__ . '/../../database/migrations';
    expect(is_dir($migrationPath))->toBeTrue();
    
    $migrations = glob($migrationPath . '/*.php');
    expect(count($migrations))->toBeGreaterThan(0);
    
    // Check specific migration files exist
    $expectedMigrations = [
        'create_media_table.php',
        'create_media_categories_table.php', 
        'create_media_tags_table.php',
        'create_media_media_category.php',
        'create_media_media_tag.php'
    ];
    
    foreach ($expectedMigrations as $expectedMigration) {
        $found = false;
        foreach ($migrations as $migration) {
            if (str_contains($migration, $expectedMigration)) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue("Migration {$expectedMigration} should exist");
    }
});

test('all model classes exist', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\Media'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\MediaCategory'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\MediaTag'))->toBeTrue();
});

test('all policy classes exist', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaPolicy'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaCategoryPolicy'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaTagPolicy'))->toBeTrue();
});

test('service provider integration test', function () {
    // This test verifies that all main components work together
    $app = app();
    
    // 1. Service provider can be instantiated
    $serviceProvider = new MediaLibraryServiceProvider($app);
    expect($serviceProvider)->toBeInstanceOf(MediaLibraryServiceProvider::class);
    
    // 2. Register and boot work without errors
    $serviceProvider->register();
    $serviceProvider->boot();
    
    // 3. Main singleton is available
    expect($app->bound('media-library'))->toBeTrue();
    
    // 4. MediaServiceProvider is registered
    $providers = $app->getLoadedProviders();
    expect($providers)->toHaveKey('ArtisanPackUI\MediaLibrary\Features\Media\MediaServiceProvider');
    
    expect(true)->toBeTrue(); // All checks passed
});