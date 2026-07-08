<?php

/**
 * Livewire-side helpers for running media-library AI agents.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Ai\Concerns;

use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\Ai\Exceptions\MissingCredentialsException;
use Exception;

/**
 * Shared feature-toggle checks + agent-invocation error handling used by
 * every Livewire surface that offers media-library AI suggestions.
 *
 * Kept as a trait so both `MediaEdit` (post-upload) and `MediaUpload`
 * (upload-time) can offer the same buttons without diverging on the
 * "is this feature installed and enabled?" plumbing.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */
trait InteractsWithMediaAi
{
    /**
     * Whether an AI action is running (used to disable AI buttons in the view).
     *
     * @since 1.3.0
     *
     * @var bool
     */
    public bool $isRunningAi = false;

    /**
     * Whether AI features are usable in this environment.
     *
     * Returns `false` when `artisanpack-ui/ai` is not installed so views
     * can hide the AI buttons without erroring on missing classes.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public function isAiAvailable(): bool
    {
        return interface_exists( FeatureRegistry::class );
    }

    /**
     * Whether a particular AI feature is registered and enabled.
     *
     * @since 1.3.0
     *
     * @param  string  $featureKey  Fully-qualified feature key.
     *
     * @return bool
     */
    public function isAiFeatureEnabled( string $featureKey ): bool
    {
        if ( ! $this->isAiAvailable() ) {
            return false;
        }

        /** @var FeatureRegistry $registry */
        $registry = app( FeatureRegistry::class );

        if ( null === $registry->get( $featureKey ) ) {
            return false;
        }

        return $registry->isToggleOn( $featureKey );
    }

    /**
     * Run an agent-invoking closure with shared loading state and error
     * handling for the ai package's exception surface.
     *
     * @since 1.3.0
     *
     * @param  callable  $callback  Closure that runs the agent.
     */
    protected function runAi( callable $callback ): void
    {
        $this->isRunningAi = true;

        try {
            $callback();
        } catch ( FeatureDisabledException $e ) {
            $this->reportAiError( __( 'This AI feature is disabled.' ) );
        } catch ( MissingCredentialsException $e ) {
            $this->reportAiError( __( 'AI credentials are not configured.' ) );
        } catch ( FeatureError $e ) {
            $this->reportAiError( __( 'AI request failed: :error', [ 'error' => $e->getMessage() ] ) );
        } catch ( Exception $e ) {
            $this->reportAiError( __( 'AI request failed: :error', [ 'error' => $e->getMessage() ] ) );
        } finally {
            $this->isRunningAi = false;
        }
    }

    /**
     * Surface an AI-related error to the user.
     *
     * Prefers the Toast trait's `error()` method when the host component
     * uses it; otherwise falls back to a Livewire `toast` event and
     * finally `session()->flash()` so the message is never silently
     * dropped.
     *
     * @since 1.3.0
     *
     * @param  string  $message  Localised error message.
     */
    protected function reportAiError( string $message ): void
    {
        if ( method_exists( $this, 'error' ) ) {
            $this->error( $message );

            return;
        }

        if ( method_exists( $this, 'dispatch' ) ) {
            $this->dispatch( 'toast', [
                'type'    => 'error',
                'message' => $message,
            ] );

            return;
        }

        session()->flash( 'ai_error', $message );
    }

    /**
     * Surface an AI-related informational note to the user.
     *
     * @since 1.3.0
     *
     * @param  string  $message  Localised message.
     */
    protected function reportAiInfo( string $message ): void
    {
        if ( method_exists( $this, 'info' ) ) {
            $this->info( $message );

            return;
        }

        if ( method_exists( $this, 'dispatch' ) ) {
            $this->dispatch( 'toast', [
                'type'    => 'info',
                'message' => $message,
            ] );

            return;
        }

        session()->flash( 'ai_info', $message );
    }

    /**
     * Build a filename-based fallback alt-text stub used when the model
     * returns an empty suggestion.
     *
     * `$filename` is treated as a real file name — the last `.ext`
     * fragment is stripped before separator normalisation. `$title` is
     * treated as free-form text and is used as-is so titles like
     * `Roadmap v1.0` are not truncated to `Roadmap v1` by an
     * accidental extension strip.
     *
     * Returns `__('Image')` when both inputs are empty.
     *
     * @since 1.3.0
     *
     * @param  string|null  $filename  Original filename (e.g. `company-banner.jpg`).
     * @param  string|null  $title     Optional user-authored title fallback.
     *
     * @return string
     */
    protected function filenameFallbackAltText( ?string $filename = null, ?string $title = null ): string
    {
        $filename = null === $filename ? '' : trim( $filename );

        if ( '' !== $filename ) {
            $base = pathinfo( $filename, PATHINFO_FILENAME );

            return $this->humaniseFallbackBase( (string) $base );
        }

        $title = null === $title ? '' : trim( $title );

        if ( '' !== $title ) {
            return $this->humaniseFallbackBase( $title );
        }

        return __( 'Image' );
    }

    /**
     * Kebab/underscore → spaces, trim, and ucfirst.
     *
     * @since 1.3.0
     *
     * @param  string  $base  Raw base string.
     *
     * @return string
     */
    protected function humaniseFallbackBase( string $base ): string
    {
        $base = trim( (string) preg_replace( '/[-_]+/', ' ', $base ) );

        if ( '' === $base ) {
            return __( 'Image' );
        }

        return ucfirst( $base );
    }

    /**
     * Surface an AI-related success note to the user.
     *
     * @since 1.3.0
     *
     * @param  string  $message  Localised message.
     */
    protected function reportAiSuccess( string $message ): void
    {
        if ( method_exists( $this, 'success' ) ) {
            $this->success( $message );

            return;
        }

        if ( method_exists( $this, 'dispatch' ) ) {
            $this->dispatch( 'toast', [
                'type'    => 'success',
                'message' => $message,
            ] );

            return;
        }

        session()->flash( 'ai_success', $message );
    }
}
