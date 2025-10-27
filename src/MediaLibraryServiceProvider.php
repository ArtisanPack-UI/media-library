<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Media Library package.
 *
 * Bootstraps the Media Library by registering configuration, views,
 * database migrations, and API routes. Configuration is merged into
 * the main artisanpack.php config file following the ArtisanPack UI
 * package conventions.
 *
 * @package ArtisanPackUI\MediaLibrary
 *
 * @since 1.0.0
 */
class MediaLibraryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method merges the package's local media configuration into a temporary key.
     * The `boot` method will then handle merging this into the main `artisanpack` config.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/media.php',
            'artisanpack-media-temp'
        );

        // Service registrations will be added in Phase 2+
        // $this->app->singleton(MediaManager::class);
        // $this->app->singleton(MediaUploadService::class);
        // etc.
    }

    /**
     * Bootstrap any application services.
     *
     * This method publishes the configuration, merges it into the main `artisanpack`
     * config array, registers views, and loads database migrations.
     *
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->mergeConfiguration();
        $this->publishConfiguration();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerRoutes();
    }

    /**
     * Merges the package's default configuration with the user's customizations.
     *
     * This method ensures that the user's settings under the 'media' key
     * in `config/artisanpack.php` take precedence over the package's default values.
     *
     * @since 1.0.0
     */
    protected function mergeConfiguration(): void
    {
        $packageDefaults = config('artisanpack-media-temp', []);
        $userConfig = config('artisanpack.media', []);
        $mergedConfig = array_replace_recursive($packageDefaults, $userConfig);
        config(['artisanpack.media' => $mergedConfig]);
    }

    /**
     * Publish the configuration file to the application's config directory.
     *
     * Configuration will be published to config/artisanpack/media.php to maintain
     * the unified ArtisanPack UI configuration structure.
     *
     * @since 1.0.0
     */
    protected function publishConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/media.php' => config_path('artisanpack/media.php'),
            ], 'artisanpack-package-config');
        }
    }

    /**
     * Register the Media Library views.
     *
     * Publishes views to the application's resources path and loads views
     * from both the published and package source paths.
     *
     * @since 1.0.0
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'media');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/media'),
        ], 'media-views');
    }

    /**
     * Register the Media Library API routes.
     *
     * Routes will be registered in Phase 4 when the API controller is implemented.
     *
     * @since 1.0.0
     */
    protected function registerRoutes(): void
    {
        // Routes will be added in Phase 4
        // Route::middleware('api')
        //     ->prefix('api')
        //     ->group(__DIR__ . '/../routes/api.php');
    }
}
