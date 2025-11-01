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
     */
    protected static function getFacadeAccessor(): string
    {
        return 'media-library';
    }
}
