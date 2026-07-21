<?php

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Support;

/**
 * Registers backwards-compat aliases for renamed hooks.
 *
 * Subscribers on the old `ap.media.*` policy ability filter names continue
 * firing (with an info-level deprecation log) after the Wave 2 hook rename
 * to `ap.mediaLibrary.abilities.*` for cross-package consistency with the
 * `abilities.` sub-namespace pattern used elsewhere in the ecosystem.
 * Aliases will be removed in the next major version.
 *
 * @since 1.4.0
 */
class HookAliases
{
    /**
     * Register deprecation aliases for renamed media-library hooks.
     *
     * @since 1.4.0
     */
    public static function register(): void
    {
        if ( ! function_exists( 'deprecateHook' ) ) {
            return;
        }

        $aliases = [
            'ap.media.viewAny'     => 'ap.mediaLibrary.abilities.viewAny',
            'ap.media.view'        => 'ap.mediaLibrary.abilities.view',
            'ap.media.create'      => 'ap.mediaLibrary.abilities.create',
            'ap.media.update'      => 'ap.mediaLibrary.abilities.update',
            'ap.media.delete'      => 'ap.mediaLibrary.abilities.delete',
            'ap.media.restore'     => 'ap.mediaLibrary.abilities.restore',
            'ap.media.forceDelete' => 'ap.mediaLibrary.abilities.forceDelete',
        ];

        foreach ( $aliases as $old => $new ) {
            deprecateHook( $old, $new );
        }
    }
}
