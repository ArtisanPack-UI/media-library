<?php

namespace ArtisanPackUI\MediaLibrary\Policies;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Media Policy
 *
 * Handles authorization for media-related operations.
 * Uses hooks to allow customization of capability checks.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Policies
 */
class MediaPolicy
{
    /**
     * Determine whether the user can view any media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @return bool True if the user can view any media, false otherwise.
     */
    public function viewAny(Authenticatable $user): bool
    {
        /**
         * Filters the capability slug used to check whether the user can view any media.
         *
         * This allows applications to customize which capability is required for listing media items.
         *
         * @since 1.0.0
         *
         * @param  string  $default  Default capability slug. Default 'media.view'.
         * @return string Filtered capability slug.
         */
        $capability = applyFilters('ap.media.viewAny', 'media.view');

        return $user->can($capability);
    }

    /**
     * Determine whether the user can view the media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  Media  $media  The media instance.
     * @return bool True if the user can view the media, false otherwise.
     */
    public function view(Authenticatable $user, Media $media): bool
    {
        /**
         * Filters the capability slug used to check viewing a specific media item.
         *
         * Allows applications to change which capability is required when viewing
         * a particular media record.
         *
         * @since 1.0.0
         *
         * @param  string  $default  Default capability slug. Default 'media.view'.
         * @param  Media  $media  The media instance being checked.
         * @return string Filtered capability slug.
         */
        $capability = applyFilters('ap.media.view', 'media.view', $media);

        return $user->can($capability);
    }

    /**
     * Determine whether the user can create media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @return bool True if the user can create media, false otherwise.
     */
    public function create(Authenticatable $user): bool
    {
        /**
         * Filters the capability slug used to check whether the user can create media.
         *
         * @since 1.0.0
         *
         * @param  string  $default  Default capability slug. Default 'media.upload'.
         * @return string Filtered capability slug.
         */
        $capability = applyFilters('ap.media.create', 'media.upload');

        return $user->can($capability);
    }

    /**
     * Determine whether the user can update the media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  Media  $media  The media instance.
     * @return bool True if the user can update the media, false otherwise.
     */
    public function update(Authenticatable $user, Media $media): bool
    {
        /**
         * Filters the capability slug used to check whether the user can update a media item.
         *
         * @since 1.0.0
         *
         * @param  string  $default  Default capability slug. Default 'media.edit'.
         * @param  Media  $media  The media instance being checked.
         * @return string Filtered capability slug.
         */
        $capability = applyFilters('ap.media.update', 'media.edit', $media);

        return $user->can($capability);
    }

    /**
     * Determine whether the user can delete the media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  Media  $media  The media instance.
     * @return bool True if the user can delete the media, false otherwise.
     */
    public function delete(Authenticatable $user, Media $media): bool
    {
        /**
         * Filters the capability slug used to check whether the user can delete a media item.
         *
         * @since 1.0.0
         *
         * @param  string  $default  Default capability slug. Default 'media.delete'.
         * @param  Media  $media  The media instance being checked.
         * @return string Filtered capability slug.
         */
        $capability = applyFilters('ap.media.delete', 'media.delete', $media);

        return $user->can($capability);
    }

    /**
     * Determine whether the user can restore the media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  Media  $media  The media instance.
     * @return bool True if the user can restore the media, false otherwise.
     */
    public function restore(Authenticatable $user, Media $media): bool
    {
        $capability = applyFilters('ap.media.restore', 'media.delete', $media);

        return $user->can($capability);
    }

    /**
     * Determine whether the user can permanently delete the media.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  Media  $media  The media instance.
     * @return bool True if the user can force delete the media, false otherwise.
     */
    public function forceDelete(Authenticatable $user, Media $media): bool
    {
        $capability = applyFilters('ap.media.forceDelete', 'media.delete', $media);

        return $user->can($capability);
    }
}
