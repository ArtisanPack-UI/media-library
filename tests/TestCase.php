<?php

namespace Tests;

use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Base Test Case
 *
 * Provides base functionality for all package tests.
 *
 * @since   1.0.0
 *
 * @package Tests
 */
abstract class TestCase extends BaseTestCase
{
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
     * @return void
     */
    protected function defineEnvironment($app): void
    {
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
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load testing migrations (users table - only for tests)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/testing');

        // Load main package migrations (media tables - will run in consuming apps)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
