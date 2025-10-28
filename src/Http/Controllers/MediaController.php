<?php

declare(strict_types = 1);

namespace ArtisanPackUI\MediaLibrary\Http\Controllers;

use ArtisanPackUI\MediaLibrary\Http\Requests\MediaStoreRequest;
use ArtisanPackUI\MediaLibrary\Http\Requests\MediaUpdateRequest;
use ArtisanPackUI\MediaLibrary\Http\Resources\MediaResource;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Media Controller
 *
 * Handles API endpoints for media management including listing,
 * uploading, updating, and deleting media items.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Http\Controllers
 *
 */
class MediaController extends Controller
{
	use AuthorizesRequests;

	/**
	 * Media upload service instance.
	 */
	protected MediaUploadService $uploadService;

	/**
	 * Create a new controller instance.
	 *
	 * @param MediaUploadService $uploadService The media upload service.
	 */
	public function __construct( MediaUploadService $uploadService )
	{
		$this->uploadService = $uploadService;
	}

	/**
	 * Display a listing of media items.
	 *
	 * Supports filtering by folder_id, tag, type (mime_type), and search query.
	 *
	 * @param Request $request The HTTP request instance.
	 * @return AnonymousResourceCollection The paginated media collection.
	 */
	public function index( Request $request ): AnonymousResourceCollection
	{
		$this->authorize( 'viewAny', Media::class );

		$perPage = $request->input( 'per_page', 15 );
		$query   = Media::query()->with( [ 'folder', 'uploadedBy', 'tags' ] );

		// Filter by folder
		if ( $request->filled( 'folder_id' ) ) {
			$query->where( 'folder_id', $request->input( 'folder_id' ) );
		}

		// Filter by tag
		if ( $request->filled( 'tag' ) ) {
			$query->withTag( $request->input( 'tag' ) );
		}

		// Filter by type (mime_type)
		if ( $request->filled( 'type' ) ) {
			$type = $request->input( 'type' );
			if ( 'image' === $type ) {
				$query->images();
			} elseif ( 'video' === $type ) {
				$query->videos();
			} elseif ( 'audio' === $type ) {
				$query->audios();
			} elseif ( 'document' === $type ) {
				$query->documents();
			} else {
				$query->byType( $type );
			}
		}

		// Search by title or file_name
		if ( $request->filled( 'search' ) ) {
			$search = $request->input( 'search' );
			$query->where( function ( $q ) use ( $search ) {
				$q->where( 'title', 'like', '%' . $search . '%' )
					->orWhere( 'file_name', 'like', '%' . $search . '%' );
			} );
		}

		// Sort by column and direction
		$sortBy    = $request->input( 'sort_by', 'created_at' );
		$sortOrder = $request->input( 'sort_order', 'desc' );
		$query->orderBy( $sortBy, $sortOrder );

		$media = $query->paginate( $perPage );

		return MediaResource::collection( $media );
	}

	/**
	 * Store a newly uploaded media item.
	 *
	 * @param MediaStoreRequest $request The validated store request.
	 * @return JsonResponse The created media resource.
	 */
	public function store( MediaStoreRequest $request ): JsonResponse
	{
		$this->authorize( 'create', Media::class );

		$file = $request->file( 'file' );

		$options = [
			'title'       => $request->input( 'title' ),
			'alt_text'    => $request->input( 'alt_text' ),
			'caption'     => $request->input( 'caption' ),
			'description' => $request->input( 'description' ),
			'folder_id'   => $request->input( 'folder_id' ),
			'tags'        => $request->input( 'tags' ),
		];

		$media = $this->uploadService->upload( $file, $options );

		return ( new MediaResource( $media ) )
			->response()
			->setStatusCode( 201 );
	}

	/**
	 * Display the specified media item.
	 *
	 * @param int $id The media ID.
	 * @return MediaResource The media resource.
	 */
	public function show( int $id ): MediaResource
	{
		$media = Media::with( [ 'folder', 'uploadedBy', 'tags' ] )->findOrFail( $id );

		$this->authorize( 'view', $media );

		return new MediaResource( $media );
	}

	/**
	 * Update the specified media item's metadata.
	 *
	 * @param MediaUpdateRequest $request The validated update request.
	 * @param int                $id      The media ID.
	 * @return MediaResource The updated media resource.
	 */
	public function update( MediaUpdateRequest $request, int $id ): MediaResource
	{
		$media = Media::findOrFail( $id );

		$this->authorize( 'update', $media );

		// Update basic fields
		$media->update( $request->only( [
			'title',
			'alt_text',
			'caption',
			'description',
			'folder_id',
		] ) );

		// Sync tags if provided
		if ( $request->has( 'tags' ) ) {
			$tags = $request->input( 'tags', [] );
			$media->tags()->sync( $tags );
		}

		$media->load( [ 'folder', 'uploadedBy', 'tags' ] );

		return new MediaResource( $media );
	}

	/**
	 * Remove the specified media item (soft delete).
	 *
	 * @param int $id The media ID.
	 * @return Response No content response.
	 */
	public function destroy( int $id ): Response
	{
		$media = Media::findOrFail( $id );

		$this->authorize( 'delete', $media );

		$media->delete();

		return response()->noContent();
	}
}
