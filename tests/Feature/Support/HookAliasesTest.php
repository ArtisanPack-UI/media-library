<?php

declare( strict_types=1 );

use ArtisanPackUI\MediaLibrary\Support\HookAliases;

beforeEach( function (): void {
    if ( ! function_exists( 'removeAllFilters' ) ) {
        $this->markTestSkipped( 'Hook system not available' );
    }

    $pairs = [
        'ap.media.viewAny'     => 'ap.mediaLibrary.abilities.viewAny',
        'ap.media.view'        => 'ap.mediaLibrary.abilities.view',
        'ap.media.create'      => 'ap.mediaLibrary.abilities.create',
        'ap.media.update'      => 'ap.mediaLibrary.abilities.update',
        'ap.media.delete'      => 'ap.mediaLibrary.abilities.delete',
        'ap.media.restore'     => 'ap.mediaLibrary.abilities.restore',
        'ap.media.forceDelete' => 'ap.mediaLibrary.abilities.forceDelete',
    ];

    foreach ( $pairs as $old => $new ) {
        removeAllFilters( $old );
        removeAllFilters( $new );
    }
} );

describe( 'HookAliases', function (): void {
    it( 'is a no-op when deprecateHook is unavailable', function (): void {
        HookAliases::register();
        expect( true )->toBeTrue();
    } );

    it( 'routes old ap.media.viewAny subscribers through the canonical hook', function (): void {
        if ( ! function_exists( 'deprecateHook' ) ) {
            $this->markTestSkipped( 'hooks < 1.3.0 has no deprecation aliasing' );
        }

        HookAliases::register();

        $called = false;

        addFilter( 'ap.media.viewAny', function ( string $capability ) use ( &$called ): string {
            $called = true;

            return 'custom.viewAny';
        } );

        $result = applyFilters( 'ap.mediaLibrary.abilities.viewAny', 'media.view' );

        expect( $called )->toBeTrue();
        expect( $result )->toBe( 'custom.viewAny' );
    } );

    it( 'routes each renamed ap.media.* alias through its canonical ap.mediaLibrary.abilities.* hook', function ( string $old, string $new ): void {
        if ( ! function_exists( 'deprecateHook' ) ) {
            $this->markTestSkipped( 'hooks < 1.3.0 has no deprecation aliasing' );
        }

        HookAliases::register();

        $called = false;

        addFilter( $old, function ( string $capability ) use ( &$called ): string {
            $called = true;

            return $capability . '.overridden';
        } );

        $result = applyFilters( $new, 'default.cap' );

        expect( $called )->toBeTrue();
        expect( $result )->toBe( 'default.cap.overridden' );
    } )->with( [
        [ 'ap.media.view', 'ap.mediaLibrary.abilities.view' ],
        [ 'ap.media.create', 'ap.mediaLibrary.abilities.create' ],
        [ 'ap.media.update', 'ap.mediaLibrary.abilities.update' ],
        [ 'ap.media.delete', 'ap.mediaLibrary.abilities.delete' ],
        [ 'ap.media.restore', 'ap.mediaLibrary.abilities.restore' ],
        [ 'ap.media.forceDelete', 'ap.mediaLibrary.abilities.forceDelete' ],
    ] );
} );
