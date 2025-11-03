<?php

namespace ArtisanPackUI\MediaLibrary\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * MediaLibrary Facade
 *
 * Provides a static interface to the MediaLibrary service.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Facades
 *
 * @see \ArtisanPackUI\MediaLibrary\MediaLibrary
 */
class MediaLibrary extends Facade
{
    /**
     * Gets the registered name of the component.
     *
     * @since 1.0.0
     *
     * @return string The facade accessor name.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'media-library';
    }
}
