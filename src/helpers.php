<?php

use ArtisanPackUI\MediaLibrary\MediaLibrary;

if ( !function_exists( 'mediaLibrary' ) ) {
	/**
	 * Get the MediaLibrary instance.
	 *
	 * @return MediaLibrary
	 */
	function mediaLibrary()
	{
		return app( 'media-library' );
	}
}

if ( !function_exists( 'a11yCSSVarBlackOrWhite' ) ) {
	/**
	 * Returns whether a text color should be black or white based on the background color.
	 *
	 * @param string $hexColor The hex code for the background color.
	 * @return string
	 * @since 1.0.0
	 */
	function a11yCSSVarBlackOrWhite( string $hexColor ): string
	{
		return mediaLibrary()->a11yCSSVarBlackOrWhite( $hexColor );
	}
}

if ( !function_exists( 'a11yGetContrastColor' ) ) {
	/**
	 * Returns whether a text color should be black or white based on the background color.
	 *
	 * @param string $hexColor The hex code for the background color.
	 * @return string
	 * @since 1.0.0
	 */
	function a11yGetContrastColor( string $hexColor ): string
	{
		return mediaLibrary()->a11yGetContrastColor( $hexColor );
	}
}

if ( !function_exists( 'getToastDuration' ) ) {
	/**
	 * Gets the user's setting for how long the toast element should stay on the screen.
	 *
	 * @return float|int
	 * @since 1.0.0
	 */
	function getToastDuration(): float|int
	{
		return mediaLibrary()->getToastDuration();
	}
}

if ( !function_exists( 'a11yCheckContrastColor' ) ) {
	/**
	 * Returns whether two given colors have the correct amount of contrast between them.
	 *
	 * @param string $firstHexColor  The first color to check.
	 * @param string $secondHexColor The second color to check.
	 * @return bool
	 * @since 1.0.0
	 */
	function a11yCheckContrastColor( string $firstHexColor, string $secondHexColor ): bool
	{
		return mediaLibrary()->a11yCheckContrastColor( $firstHexColor, $secondHexColor );
	}
}
