<?php

/**
 * Livewire Version Helper
 *
 * Provides utilities for detecting the installed Livewire version
 * and available features for graceful degradation between Livewire 3 and 4.
 *
 * @since   1.1.0
 *
 * @package ArtisanPackUI\MediaLibrary\Helpers
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Helpers;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * Livewire Helper Class
 *
 * Provides static methods for detecting Livewire version and capabilities.
 * This allows the package to support both Livewire 3 and Livewire 4 with
 * feature detection for graceful degradation.
 *
 * @since   1.1.0
 *
 * @package ArtisanPackUI\MediaLibrary\Helpers
 */
class LivewireHelper
{
    /**
     * Cached version string to avoid repeated lookups.
     *
     * @since 1.1.0
     *
     * @var string|null
     */
    protected static ?string $cachedVersion = null;

    /**
     * Get the installed Livewire version string.
     *
     * Returns the full version string of the installed Livewire package.
     * The result is cached to avoid repeated Composer lookups.
     *
     * @since 1.1.0
     *
     * @return string The Livewire version string (e.g., "3.7.4", "4.0.0").
     */
    public static function version(): string
    {
        if ( null === static::$cachedVersion ) {
            try {
                $version = InstalledVersions::getPrettyVersion( 'livewire/livewire' ) ?? '0.0.0';
            } catch ( OutOfBoundsException $e ) {
                $version = '0.0.0';
            }

            // Remove 'v' prefix if present (e.g., 'v3.7.4' becomes '3.7.4')
            static::$cachedVersion = ltrim( $version, 'v' );
        }

        return static::$cachedVersion;
    }

    /**
     * Get the major version number of the installed Livewire.
     *
     * Extracts and returns only the major version number from the
     * full version string.
     *
     * @since 1.1.0
     *
     * @return int The major version number (e.g., 3, 4).
     */
    public static function majorVersion(): int
    {
        $version = static::version();
        $parts   = explode( '.', $version );

        return (int) ( $parts[0] ?? 0 );
    }

    /**
     * Check if Livewire 4.x is installed.
     *
     * Returns true if the installed Livewire version is 4.x or higher.
     *
     * @since 1.1.0
     *
     * @return bool True if Livewire 4.x or higher is installed.
     */
    public static function isLivewire4(): bool
    {
        return static::majorVersion() >= 4;
    }

    /**
     * Check if Livewire 3.x is installed.
     *
     * Returns true if the installed Livewire version is 3.x.
     * This method returns false for Livewire 4.x.
     *
     * @since 1.1.0
     *
     * @return bool True if Livewire 3.x is installed.
     */
    public static function isLivewire3(): bool
    {
        return 3 === static::majorVersion();
    }

    /**
     * Check if the installed Livewire version supports streaming.
     *
     * The `wire:stream` directive is a Livewire 4.x feature that allows
     * streaming content updates to the browser. This method returns true
     * if the feature is available.
     *
     * @since 1.1.0
     *
     * @return bool True if streaming is supported (Livewire 4.x+).
     */
    public static function supportsStreaming(): bool
    {
        return static::isLivewire4();
    }

    /**
     * Check if the installed Livewire version meets a minimum requirement.
     *
     * Compares the installed version against a minimum version string
     * using semantic versioning comparison.
     *
     * @since 1.1.0
     *
     * @param string $minimumVersion The minimum version required (e.g., "3.6.0").
     *
     * @return bool True if the installed version meets or exceeds the minimum.
     */
    public static function isAtLeast( string $minimumVersion ): bool
    {
        return version_compare( static::version(), $minimumVersion, '>=' );
    }

    /**
     * Clear the cached version.
     *
     * This method is primarily useful for testing purposes to reset
     * the cached version between tests.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function clearCache(): void
    {
        static::$cachedVersion = null;
    }

    /**
     * Set a mock version for testing purposes.
     *
     * This method allows tests to simulate different Livewire versions
     * without actually changing the installed package.
     *
     * @since 1.1.0
     *
     * @param string $version The version string to use.
     *
     * @return void
     */
    public static function setMockVersion( string $version ): void
    {
        static::$cachedVersion = $version;
    }
}
