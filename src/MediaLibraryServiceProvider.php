<?php

namespace ArtisanPackUI\MediaLibrary;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaGrid;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaItem;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaLibrary;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaUpload;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Policies\MediaPolicy;
use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use ArtisanPackUI\MediaLibrary\Services\VideoProcessingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Service provider for the Media Library package.
 *
 * Bootstraps the Media Library by registering configuration, views,
 * database migrations, and API routes. Configuration is merged into
 * the main artisanpack.php config file following the ArtisanPack UI
 * package conventions.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary
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
			__DIR__ . '/../config/media.php',
			'artisanpack-media-temp'
		);

		// Register services as singletons
		$this->app->singleton( MediaStorageService::class );
		$this->app->singleton( VideoProcessingService::class );
		$this->app->singleton( ImageOptimizationService::class );
		$this->app->singleton( MediaProcessingService::class );
		$this->app->singleton( MediaUploadService::class );
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
		$this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );
		$this->registerPolicies();
		$this->registerRoutes();
		$this->registerLivewireComponents();
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
		$packageDefaults = config( 'artisanpack-media-temp', [] );
		$userConfig      = config( 'artisanpack.media', [] );
		$mergedConfig    = array_replace_recursive( $packageDefaults, $userConfig );
		config( [ 'artisanpack.media' => $mergedConfig ] );
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
		if ( $this->app->runningInConsole() ) {
			$this->publishes( [
								  __DIR__ . '/../config/media.php' => config_path( 'artisanpack/media.php' ),
							  ], 'artisanpack-package-config' );
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
		$this->loadViewsFrom( __DIR__ . '/../resources/views', 'media' );

		$this->publishes( [
							  __DIR__ . '/../resources/views' => resource_path( 'views/vendor/media' ),
						  ], 'media-views' );
	}

	/**
	 * Register the Media Library policies.
	 *
	 * @since 1.0.0
	 */
	protected function registerPolicies(): void
	{
		Gate::policy( Media::class, MediaPolicy::class );
	}

	/**
	 * Register the Media Library API routes.
	 *
	 * @since 1.0.0
	 */
	protected function registerRoutes(): void
	{
		Route::middleware( 'api' )
			->prefix( 'api' )
			->group( __DIR__ . '/routes/api.php' );
	}

	/**
	 * Register Livewire components.
	 *
	 * Only registers if Livewire is available (i.e., in a full Laravel application context).
	 *
	 * @since 1.0.0
	 */
	protected function registerLivewireComponents(): void
	{
		// Only register Livewire components if Livewire is bound in the container
		if ( ! $this->app->bound( 'livewire' ) ) {
			return;
		}

		// Register components
		Livewire::component( 'media::media-library', MediaLibrary::class );
		Livewire::component( 'media::media-upload', MediaUpload::class );
		Livewire::component( 'media::media-grid', MediaGrid::class );
		Livewire::component( 'media::media-item', MediaItem::class );
	}
}
