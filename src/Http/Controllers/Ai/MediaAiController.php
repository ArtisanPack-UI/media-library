<?php

/**
 * Media AI Controller
 *
 * JSON endpoints that run the media-library AI agents against a stored
 * media item. Consumed by the Livewire admin surface and by React/Vue
 * frontends alike so no framework-specific components are required.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Http\Controllers\Ai
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Http\Controllers\Ai;

use ArtisanPackUI\Ai\Agents\AltTextGenerationAgent;
use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Exceptions\FeatureDisabledException;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\Ai\Exceptions\MissingCredentialsException;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageTagSuggestionAgent;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Media AI Controller.
 *
 * All actions authorize the `update` policy on the media item since
 * the results are intended to be written back to the media metadata.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Http\Controllers\Ai
 *
 * @since      1.3.0
 */
class MediaAiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Run the cross-cutting `ai.alt_text` agent against a stored media
     * item and return the suggested alt text.
     *
     * @since 1.3.0
     *
     * @param  Request  $request  Incoming HTTP request.
     * @param  int      $id       Media ID.
     *
     * @return JsonResponse
     */
    public function altText( Request $request, int $id ): JsonResponse
    {
        $media = $this->authorizeMedia( $id );

        if ( ! $this->isEnabled( 'ai.alt_text' ) ) {
            return $this->disabledResponse( 'ai.alt_text' );
        }

        return $this->runAgent( function () use ( $media ): array {
            return AltTextGenerationAgent::for( $this->imageReferenceFor( $media ) )->run();
        } );
    }

    /**
     * Run the `media.suggest_tags` agent against a stored media item.
     *
     * Accepts an optional `allow_new` boolean in the request body; the
     * existing tag taxonomy is always drawn from the `media_tags` table
     * so callers cannot smuggle a fabricated taxonomy into the prompt.
     *
     * @since 1.3.0
     *
     * @param  Request  $request  Incoming HTTP request.
     * @param  int      $id       Media ID.
     *
     * @return JsonResponse
     */
    public function suggestTags( Request $request, int $id ): JsonResponse
    {
        $media = $this->authorizeMedia( $id );

        if ( ! $this->isEnabled( 'media.suggest_tags' ) ) {
            return $this->disabledResponse( 'media.suggest_tags' );
        }

        $existingTags = MediaTag::query()->orderBy( 'name' )->pluck( 'name' )->all();

        return $this->runAgent( function () use ( $media, $request, $existingTags ): array {
            return ImageTagSuggestionAgent::for( [
                'image'         => $this->imageReferenceFor( $media ),
                'existing_tags' => $existingTags,
                'filename'      => (string) ( $media->file_name ?? '' ),
                'folder'        => (string) ( $media->folder?->name ?? '' ),
                'allow_new'     => (bool) $request->boolean( 'allow_new' ),
            ] )->run();
        } );
    }

    /**
     * Run the `media.image_description` agent against a stored media item.
     *
     * @since 1.3.0
     *
     * @param  Request  $request  Incoming HTTP request.
     * @param  int      $id       Media ID.
     *
     * @return JsonResponse
     */
    public function describe( Request $request, int $id ): JsonResponse
    {
        $media = $this->authorizeMedia( $id );

        if ( ! $this->isEnabled( 'media.image_description' ) ) {
            return $this->disabledResponse( 'media.image_description' );
        }

        $length = $request->input( 'length', 'medium' );

        if ( ! is_string( $length ) ) {
            $length = 'medium';
        }

        return $this->runAgent( function () use ( $media, $length ): array {
            return ImageDescriptionAgent::for( [
                'image'  => $this->imageReferenceFor( $media ),
                'length' => $length,
            ] )->run();
        } );
    }

    /**
     * Load the media item and enforce the `update` policy.
     *
     * @since 1.3.0
     *
     * @param  int  $id  Media ID.
     *
     * @return Media
     */
    protected function authorizeMedia( int $id ): Media
    {
        /** @var Media $media */
        $media = Media::query()->with( [ 'folder' ] )->findOrFail( $id );

        $this->authorize( 'update', $media );

        if ( ! $media->isImage() ) {
            abort( 422, __( 'AI features are only available for image media items.' ) );
        }

        return $media;
    }

    /**
     * Build the laravel/ai-style image reference for a stored media item.
     *
     * Prefers the public URL so providers can fetch the bytes themselves;
     * private-disk media falls back to a local filesystem path.
     *
     * @since 1.3.0
     *
     * @param  Media  $media  Media item.
     *
     * @return array{ source: string, value: string }
     */
    protected function imageReferenceFor( Media $media ): array
    {
        $url = $media->url();

        if ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) ) {
            return [ 'source' => 'url', 'value' => $url ];
        }

        $disk = \Illuminate\Support\Facades\Storage::disk( $media->disk );

        return [ 'source' => 'path', 'value' => $disk->path( $media->file_path ) ];
    }

    /**
     * Whether the feature is enabled in the ai package's registry.
     *
     * Matches `InteractsWithMediaAi::isAiFeatureEnabled()` — an unknown
     * feature key resolves to `false` so the Livewire admin and the
     * JSON API refuse to run agents an operator has not opted into.
     *
     * @since 1.3.0
     *
     * @param  string  $featureKey  Fully-qualified feature key.
     *
     * @return bool
     */
    protected function isEnabled( string $featureKey ): bool
    {
        /** @var FeatureRegistry $registry */
        $registry = app( FeatureRegistry::class );

        if ( null === $registry->get( $featureKey ) ) {
            return false;
        }

        return $registry->isToggleOn( $featureKey );
    }

    /**
     * Standard 403 payload for a disabled feature.
     *
     * @since 1.3.0
     *
     * @param  string  $featureKey  Feature key that was requested.
     *
     * @return JsonResponse
     */
    protected function disabledResponse( string $featureKey ): JsonResponse
    {
        return response()->json( [
            'message'     => __( 'This AI feature is disabled.' ),
            'feature_key' => $featureKey,
        ], 403 );
    }

    /**
     * Run an agent closure and translate the ai package's exceptions into
     * consistent JSON error responses.
     *
     * @since 1.3.0
     *
     * @param  callable  $callback  Closure that runs the agent and returns its output.
     *
     * @return JsonResponse
     */
    protected function runAgent( callable $callback ): JsonResponse
    {
        try {
            return response()->json( [ 'data' => $callback() ] );
        } catch ( FeatureDisabledException $e ) {
            return response()->json( [ 'message' => $e->getMessage() ], 403 );
        } catch ( MissingCredentialsException $e ) {
            return response()->json( [ 'message' => $e->getMessage() ], 503 );
        } catch ( FeatureError $e ) {
            return response()->json( [ 'message' => $e->getMessage() ], 422 );
        }
    }
}
