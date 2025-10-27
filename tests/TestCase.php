<?php

namespace Tests;

use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
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
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load testing migrations (users table - only for tests)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/testing');

        // Load main package migrations (media tables - will run in consuming apps)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
