<?php

namespace ArtisanPackUI\MediaLibrary;

use Illuminate\Support\ServiceProvider;

class MediaLibraryServiceProvider extends ServiceProvider
{

	public function register(): void
	{
		$this->app->singleton( 'media-library', function ( $app ) {
			return new MediaLibrary();
		} );
	}
}
