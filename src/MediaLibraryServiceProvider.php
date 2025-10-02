<?php

namespace ArtisanPackUI\MediaLibrary;

use ArtisanPackUI\MediaLibrary\Features\Media\MediaServiceProvider;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Policies\MediaPolicy;
use ArtisanPackUI\MediaLibrary\Policies\MediaCategoryPolicy;
use ArtisanPackUI\MediaLibrary\Policies\MediaTagPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MediaLibraryServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		// Register the MediaServiceProvider
		$this->app->register( MediaServiceProvider::class );

		// Register the main media library singleton
		$this->app->singleton( 'media-library', function ( $app ) {
			return new MediaLibrary();
		} );

		// Merge configuration
		$this->mergeConfigFrom(
			__DIR__ . '/../config/media-library.php', 'artisanpack-media-library-temp'
		);
	}

	public function boot(): void
	{
		// Register model policies
		Gate::policy( Media::class, MediaPolicy::class );
		Gate::policy( MediaCategory::class, MediaCategoryPolicy::class );
		Gate::policy( MediaTag::class, MediaTagPolicy::class );

		// Load API routes (public routes)
		$this->loadRoutesFrom( __DIR__ . '/../routes/api.php' );

		// Load Web routes (authenticated routes)
		$this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );

		// Publish configuration files
		$this->mergeConfiguration();

		if ( $this->app->runningInConsole() ) {
			$this->publishes( [
								  __DIR__ . '/../config/media-library.php' => config_path( 'artisanpack/media-library.php' ),
							  ], 'artisanpack-package-config' );
		}


		// Publish migration files
		$this->publishes( [
							  __DIR__ . '/../database/migrations' => database_path( 'migrations' ),
						  ], 'media-library-migrations' );

		// Load migrations when running in console
		if ( $this->app->runningInConsole() ) {
			$this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );
		}
	}

	/**
	 * Merges the package's default configuration with the user's customizations.
	 *
	 * This method ensures that the user's settings in `config/artisanpack.php`
	 * take precedence over the package's default values.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function mergeConfiguration(): void
	{
		// Get the package's default configuration.
		$packageDefaults = config( 'artisanpack-media-library-temp', [] );

		// Get the user's custom configuration from config/artisanpack.php.
		$userConfig = config( 'artisanpack.media-library', [] );

		// Merge them, with the user's config overwriting the defaults.
		$mergedConfig = array_replace_recursive( $packageDefaults, $userConfig );

		// Set the final, correctly merged configuration.
		config( [ 'artisanpack.media-library' => $mergedConfig ] );
	}

}
