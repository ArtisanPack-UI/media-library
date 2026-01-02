<?php

namespace Tests;

use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;
use BladeUI\Icons\Factory as IconFactory;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base Test Case
 *
 * Provides base functionality for all package tests.
 *
 * @since   1.0.0
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * @since 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register test icon sets with blade-icons Factory
        $iconsPath = realpath(__DIR__.'/resources/icons');
        $factory = $this->app->make(IconFactory::class);

        // Add test icon sets for fas and far prefixes
        $factory->add('test-fas', [
            'path' => $iconsPath.'/fas',
            'prefix' => 'fas',
        ]);
        $factory->add('test-far', [
            'path' => $iconsPath.'/far',
            'prefix' => 'far',
        ]);
        $factory->add('test-default', [
            'path' => $iconsPath,
            'prefix' => '',
        ]);

        // Define routes used by media library views
        $this->defineMediaRoutes();
    }

    /**
     * Define routes used by media library views.
     *
     * @since 1.0.0
     */
    protected function defineMediaRoutes(): void
    {
        \Illuminate\Support\Facades\Route::get('/admin/media', function () {
            return 'Media Library';
        })->name('admin.media');

        \Illuminate\Support\Facades\Route::get('/admin/media/{media}/edit', function ($media) {
            return 'Edit Media';
        })->name('admin.media.edit');

        \Illuminate\Support\Facades\Route::get('/admin/media/upload', function () {
            return 'Upload Media';
        })->name('admin.media.upload');

        \Illuminate\Support\Facades\Route::get('/admin/media/add', function () {
            return 'Add Media';
        })->name('admin.media.add');
    }

    /**
     * Gets package providers.
     *
     * @since 1.0.0
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     * @return array<int, class-string> Array of service provider class names.
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \ArtisanPack\LivewireUiComponents\LivewireUiComponentsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
        ];
    }

    /**
     * Defines environment setup.
     *
     * @since 1.0.0
     *
     * @param  \Illuminate\Foundation\Application  $app  The application instance.
     */
    protected function defineEnvironment($app): void
    {
        // Setup app key for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Setup view compiled path for __components namespace
        $viewsPath = sys_get_temp_dir().'/media-library-test-views';
        if (! is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }
        $app['config']->set('view.compiled', $viewsPath);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup filesystem
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => storage_path('app'),
        ]);

        // Setup authentication
        $app['config']->set('auth.providers.users.model', \ArtisanPackUI\MediaLibrary\Models\User::class);

        // Setup Sanctum
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);

        $app['config']->set('sanctum.stateful', ['localhost']);
        $app['config']->set('sanctum.guard', ['web']);
    }

    /**
     * Defines database migrations.
     *
     * @since 1.0.0
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load testing migrations (users table - only for tests)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/testing');

        // Load main package migrations (media tables - will run in consuming apps)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
