<?php

/**
 * Image reference normalization for vision agents.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Ai\Concerns;

use ArtisanPackUI\Ai\Exceptions\FeatureError;

/**
 * Shared behaviour for media-library vision agents that accept an image
 * reference in one of the laravel/ai-compatible shapes (path, URL, base64
 * blob, or explicit `[source, value]` array).
 *
 * NOTE: This is a deliberate clone of the private
 * `normalizeImageReference()` / `detectSource()` methods on
 * {@see \ArtisanPackUI\Ai\Agents\AltTextGenerationAgent}. The proper fix
 * is to promote those helpers to a public trait in `artisanpack-ui/ai`
 * (`ArtisanPackUI\Ai\Support\NormalizesImageReference`) that both
 * packages can consume. That change is scoped to a separate PR against
 * the ai package — until it ships, keep this file in lock-step with the
 * upstream heuristic (especially the base64 length/padding rule) or the
 * two agents will disagree on ambiguous inputs like short bare
 * filenames.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */
trait NormalizesImageReference
{
    /**
     * Coerce raw agent input into a laravel/ai-style image attachment part.
     *
     * @since 1.3.0
     *
     * @param  mixed   $input       Agent input payload.
     * @param  string  $featureKey  Feature key used in the raised FeatureError.
     *
     * @return array{ type: string, source: string, value: string }
     */
    protected function normalizeImageReference( mixed $input, string $featureKey ): array
    {
        if (
            is_array( $input )
            && isset( $input['source'], $input['value'] )
            && is_string( $input['source'] )
            && is_string( $input['value'] )
            && '' !== $input['source']
            && '' !== $input['value']
        ) {
            $source = $input['source'];
            $value  = $input['value'];
        } elseif ( is_string( $input ) && '' !== $input ) {
            $source = $this->detectImageSource( $input );
            $value  = $input;
        } else {
            throw FeatureError::forFeature(
                $featureKey,
                'input must be an image path, URL, base64 string, or [source, value] pair.',
            );
        }

        if ( ! in_array( $source, [ 'path', 'url', 'base64' ], true ) ) {
            throw FeatureError::forFeature(
                $featureKey,
                sprintf( 'unsupported image source "%s"', $source ),
            );
        }

        if ( 'path' === $source && ! is_readable( $value ) ) {
            throw FeatureError::forFeature(
                $featureKey,
                sprintf( 'image path "%s" is not readable', $value ),
            );
        }

        return [
            'type'   => 'image',
            'source' => $source,
            'value'  => $value,
        ];
    }

    /**
     * Guess whether a bare string is a URL, filesystem path, or base64 blob.
     *
     * Matches the detection heuristic on
     * {@see \ArtisanPackUI\Ai\Agents\AltTextGenerationAgent} so the two
     * agents agree on ambiguous inputs (e.g. short bare filenames).
     *
     * @since 1.3.0
     *
     * @param  string  $input  Raw input string.
     *
     * @return string
     */
    protected function detectImageSource( string $input ): string
    {
        if ( str_starts_with( $input, 'http://' ) || str_starts_with( $input, 'https://' ) ) {
            return 'url';
        }

        if ( str_starts_with( $input, 'data:image/' ) ) {
            return 'base64';
        }

        if (
            strlen( $input ) >= 40
            && 0 === strlen( $input ) % 4
            && ! str_contains( $input, '/' )
            && ! str_contains( $input, '\\' )
            && 1 === preg_match( '#^[A-Za-z0-9+/=]+$#', $input )
        ) {
            return 'base64';
        }

        return 'path';
    }
}
