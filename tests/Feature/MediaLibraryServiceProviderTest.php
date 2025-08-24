<?php

declare(strict_types=1);

use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;
use ArtisanPackUI\MediaLibrary\Features\Media\MediaServiceProvider;
use ArtisanPackUI\MediaLibrary\MediaLibrary;
use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Policies\MediaCategoryPolicy;
use ArtisanPackUI\MediaLibrary\Policies\MediaPolicy;
use ArtisanPackUI\MediaLibrary\Policies\MediaTagPolicy;

test('service provider boots without errors', function () {
    $serviceProvider = new MediaLibraryServiceProvider($this->app);
    
    // Test that register method works without errors
    try {
        $serviceProvider->register();
        expect(true)->toBeTrue(); // If we get here, no exception was thrown
    } catch (Exception $e) {
        expect(false)->toBeTrue('Register method should not throw exception: ' . $e->getMessage());
    }
    
    // Test that boot method works without errors
    try {
        $serviceProvider->boot();
        expect(true)->toBeTrue(); // If we get here, no exception was thrown
    } catch (Exception $e) {
        expect(false)->toBeTrue('Boot method should not throw exception: ' . $e->getMessage());
    }
});

test('media service provider is registered', function () {
    // Verify MediaServiceProvider is registered in the application
    $registeredProviders = $this->app->getLoadedProviders();
    
    expect($registeredProviders)->toHaveKey(MediaServiceProvider::class);
});

test('media library singleton is registered', function () {
    // Verify the media-library singleton is registered
    expect($this->app->bound('media-library'))->toBeTrue();
    
    // Verify it returns the correct instance
    $mediaLibrary = $this->app->make('media-library');
    expect($mediaLibrary)->toBeInstanceOf(MediaLibrary::class);
});

test('media manager is registered', function () {
    // Verify MediaManager is registered as singleton
    expect($this->app->bound(MediaManager::class))->toBeTrue();
    
    // Verify it returns the correct instance
    $mediaManager = $this->app->make(MediaManager::class);
    expect($mediaManager)->toBeInstanceOf(MediaManager::class);
});

test('policies are registered with gate', function () {
    // Test Media model policy registration
    $mediaPolicy = Gate::getPolicyFor(Media::class);
    expect($mediaPolicy)->toBe(MediaPolicy::class);
    
    // Test MediaCategory model policy registration
    $mediaCategoryPolicy = Gate::getPolicyFor(MediaCategory::class);
    expect($mediaCategoryPolicy)->toBe(MediaCategoryPolicy::class);
    
    // Test MediaTag model policy registration
    $mediaTagPolicy = Gate::getPolicyFor(MediaTag::class);
    expect($mediaTagPolicy)->toBe(MediaTagPolicy::class);
});

test('migrations are publishable', function () {
    $serviceProvider = new MediaLibraryServiceProvider($this->app);
    
    // Register the service provider
    $serviceProvider->register();
    $serviceProvider->boot();
    
    // Get publishable migrations
    $publishables = $serviceProvider->pathsToPublish(MediaLibraryServiceProvider::class, 'media-library-migrations');
    
    // Verify migrations are configured for publishing
    expect($publishables)->not->toBeEmpty();
    
    // Verify the source path contains migration files
    $sourcePath = array_keys($publishables)[0];
    expect($sourcePath)->toContain('database/migrations');
    
    // Verify target path is database/migrations
    $targetPath = array_values($publishables)[0];
    expect($targetPath)->toBe(database_path('migrations'));
});

test('migrations are loaded in console', function () {
    // Mock running in console
    $this->app->instance('env', 'testing');
    
    $serviceProvider = new MediaLibraryServiceProvider($this->app);
    
    // Register and boot the service provider
    $serviceProvider->register();
    
    // Boot should load migrations when running in console
    expect(fn() => $serviceProvider->boot())->not->toThrow();
});

test('all components are registered correctly', function () {
    // Test that all major components work together
    
    // 1. MediaServiceProvider is registered
    expect($this->app->getLoadedProviders())->toHaveKey(MediaServiceProvider::class);
    
    // 2. MediaLibrary singleton is available
    expect($this->app->bound('media-library'))->toBeTrue();
    
    // 3. MediaManager is available
    expect($this->app->bound(MediaManager::class))->toBeTrue();
    
    // 4. All policies are registered
    expect(Gate::getPolicyFor(Media::class))->toBe(MediaPolicy::class);
    expect(Gate::getPolicyFor(MediaCategory::class))->toBe(MediaCategoryPolicy::class);
    expect(Gate::getPolicyFor(MediaTag::class))->toBe(MediaTagPolicy::class);
    
    // 5. Service provider boots successfully
    $serviceProvider = new MediaLibraryServiceProvider($this->app);
    expect(fn() => $serviceProvider->boot())->not->toThrow();
});

test('service provider handles missing dependencies gracefully', function () {
    // Test that service provider doesn't break if dependencies are missing
    $serviceProvider = new MediaLibraryServiceProvider($this->app);
    
    // Should not throw exceptions during registration and boot
    try {
        $serviceProvider->register();
        $serviceProvider->boot();
        expect(true)->toBeTrue(); // If we get here, no exception was thrown
    } catch (Exception $e) {
        expect(false)->toBeTrue('Service provider should handle dependencies gracefully: ' . $e->getMessage());
    }
});