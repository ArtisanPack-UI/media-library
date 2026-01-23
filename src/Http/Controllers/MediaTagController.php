<?php

/**
 * Media Tag Controller
 *
 * Handles API endpoints for media tag management including listing,
 * creating, updating, deleting, and attaching tags to media.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Http\Controllers
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Http\Controllers;

use ArtisanPackUI\MediaLibrary\Http\Requests\MediaTagStoreRequest;
use ArtisanPackUI\MediaLibrary\Http\Requests\MediaTagUpdateRequest;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Media Tag Controller
 *
 * Handles API endpoints for media tag management including listing,
 * creating, updating, deleting, and attaching tags to media.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Http\Controllers
 */
class MediaTagController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of media tags.
     *
     * Returns all tags with media count.
     *
     * @since 1.0.0
     *
     * @param Request $request The HTTP request instance.
     * @return JsonResponse The tags collection.
     */
    public function index( Request $request ): JsonResponse
    {
        $tags = MediaTag::query()
                        ->withCount( 'media' )
                        ->orderBy( 'name', 'asc' )
                        ->get();

        return response()->json( [
                                     'data' => $tags,
                                 ] );
    }

    /**
     * Store a newly created tag.
     *
     * @since 1.0.0
     *
     * @param MediaTagStoreRequest $request The validated request.
     * @return JsonResponse The created tag.
     */
    public function store( MediaTagStoreRequest $request ): JsonResponse
    {
        $data = $request->validated();

        // Generate slug from name
        $data['slug'] = Str::slug( $data['name'] );

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter      = 1;
        while ( MediaTag::where( 'slug', $data['slug'] )->exists() ) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $tag = MediaTag::create( $data );

        return response()->json( [
                                     'data' => $tag->loadCount( 'media' ),
                                                                                                                                                                                                                                                                                              'message' => 'Tag created successfully',
                                 ], 201 );
    }

    /**
     * Display the specified tag.
     *
     * @since 1.0.0
     *
     * @param int $id The tag ID.
     * @return JsonResponse The tag data.
     */
    public function show( int $id ): JsonResponse
    {
        $tag = MediaTag::withCount( 'media' )->findOrFail( $id );

        return response()->json( [
                                     'data' => $tag,
                                 ] );
    }

    /**
     * Update the specified tag.
     *
     * @since 1.0.0
     *
     * @param MediaTagUpdateRequest $request The validated request.
     * @param int                   $id      The tag ID.
     * @return JsonResponse The updated tag.
     */
    public function update( MediaTagUpdateRequest $request, int $id ): JsonResponse
    {
        $tag  = MediaTag::findOrFail( $id );
        $data = $request->validated();

        // Update slug if name changed
        if ( isset( $data['name'] ) && $data['name'] !== $tag->name ) {
            $data['slug'] = Str::slug( $data['name'] );

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter      = 1;
            while ( MediaTag::where( 'slug', $data['slug'] )->where( 'id', '!=', $id )->exists() ) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $tag->update( $data );

        return response()->json( [
                                     'data'    => $tag->loadCount( 'media' ),
                                     'message' => 'Tag updated successfully',
                                 ] );
    }

    /**
     * Remove the specified tag.
     *
     * @since 1.0.0
     *
     * @param int $id The tag ID.
     * @return Response The response with no content.
     */
    public function destroy( int $id ): Response
    {
        $tag = MediaTag::findOrFail( $id );

        // Detach all media before deleting
        $tag->media()->detach();

        $tag->delete();

        return response()->noContent();
    }

    /**
     * Detach a tag from multiple media items.
     *
     * @since 1.0.0
     *
     * @param Request $request The HTTP request instance.
     * @param int     $id      The tag ID.
     * @return JsonResponse The result.
     */
    public function detach( Request $request, int $id ): JsonResponse
    {
        $request->validate( [
                                'media_ids'   => [ 'required', 'array' ],
                                'media_ids.*' => [ 'exists:media,id' ],
                            ] );

        $tag      = MediaTag::findOrFail( $id );
        $mediaIds = $request->input( 'media_ids' );

        // Detach specified media
        $tag->media()->detach( $mediaIds );

        return response()->json( [
                                     'message' => 'Tag detached from media successfully',
                                 ] );
    }

    /**
     * Attach a tag to multiple media items.
     *
     * @since 1.0.0
     *
     * @param Request $request The HTTP request instance.
     * @param int     $id      The tag ID.
     * @return JsonResponse The result.
     */
    public function attach( Request $request, int $id ): JsonResponse
    {
        $request->validate( [
                                'media_ids'   => [ 'required', 'array' ],
                                'media_ids.*' => [ 'exists:media,id' ],
                            ] );

        $tag      = MediaTag::findOrFail( $id );
        $mediaIds = $request->input( 'media_ids' );

        // Attach without duplicates
        $tag->media()->syncWithoutDetaching( $mediaIds );

        return response()->json( [
                                     'message' => 'Tag attached to media successfully',
                                 ] );
    }
}
