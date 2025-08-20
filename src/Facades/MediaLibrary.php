<?php

namespace ArtisanPackUI\MediaLibrary\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArtisanPackUI\MediaLibrary\MediaLibrary
 */
class MediaLibrary extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'media-library';
	}
}
