<?php

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Media Controller Feature Tests
 *
 * Tests for the MediaController API endpoints including
 * listing, uploading, updating, and deleting media.
 *
 * @package Tests\Feature
 *
 * @since   1.0.0
 */
class MediaControllerTest extends TestCase
{
	use RefreshDatabase;

	protected User $user;

	protected function setUp(): void
	{
		parent::setUp();

		$this->defineDatabaseMigrations();

		// Create test user
		$this->user = User::factory()->create();

		// Setup test disk
		Storage::fake( 'test-disk' );

		// Configure media settings
		config( [
			'artisanpack.media.disk'               => 'test-disk',
			'artisanpack.media.user_model'         => User::class,
			'artisanpack.media.allowed_mime_types' => [
				'image/jpeg',
				'image/png',
				'image/gif',
			],
			'artisanpack.media.max_file_size'      => 10240,
		] );

		// Allow all authorization checks to pass for authenticated users in tests
		Gate::before( fn ( $user, $ability ) => true );
	}

	/**
	 * Test that index endpoint returns paginated media list.
	 */
	public function test_index_returns_paginated_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		// Create some media
		Media::factory()->count( 5 )->create( [ 'uploaded_by' => $this->user->id ] );

		$response = $this->getJson( '/api/media' );

		$response->assertOk()
			->assertJsonStructure( [
				'data' => [
					'*' => [
						'id',
						'title',
						'file_name',
						'url',
						'mime_type',
						'file_size',
						'created_at',
					],
				],
				'links',
				'meta',
			] );
	}

	/**
	 * Test that index endpoint filters by folder_id.
	 */
	public function test_index_filters_by_folder(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$folder = MediaFolder::factory()->create( [ 'created_by' => $this->user->id ] );

		Media::factory()->count( 3 )->create( [
			'uploaded_by' => $this->user->id,
			'folder_id'   => $folder->id,
		] );

		Media::factory()->count( 2 )->create( [
			'uploaded_by' => $this->user->id,
			'folder_id'   => null,
		] );

		$response = $this->getJson( '/api/media?folder_id=' . $folder->id );

		$response->assertOk()
			->assertJsonCount( 3, 'data' );
	}

	/**
	 * Test that index endpoint filters by type.
	 */
	public function test_index_filters_by_type(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		Media::factory()->count( 3 )->image()->create( [ 'uploaded_by' => $this->user->id ] );
		Media::factory()->count( 2 )->video()->create( [ 'uploaded_by' => $this->user->id ] );

		$response = $this->getJson( '/api/media?type=image' );

		$response->assertOk()
			->assertJsonCount( 3, 'data' );
	}

	/**
	 * Test that index endpoint searches by title and file_name.
	 */
	public function test_index_searches_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		Media::factory()->create( [
			'uploaded_by' => $this->user->id,
			'title'       => 'Searchable Media',
		] );

		Media::factory()->create( [
			'uploaded_by' => $this->user->id,
			'title'       => 'Other Media',
		] );

		$response = $this->getJson( '/api/media?search=Searchable' );

		$response->assertOk()
			->assertJsonCount( 1, 'data' );
	}

	/**
	 * Test that store endpoint uploads a file successfully.
	 */
	public function test_store_uploads_file(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$file = UploadedFile::fake()->image( 'test.jpg', 100, 100 );

		$response = $this->postJson( '/api/media', [
			'file'  => $file,
			'title' => 'Test Upload',
		] );

		$response->assertCreated()
			->assertJsonStructure( [
				'data' => [
					'id',
					'title',
					'file_name',
					'url',
				],
			] );

		$this->assertDatabaseHas( 'media', [
			'title'       => 'Test Upload',
			'uploaded_by' => $this->user->id,
		] );
	}

	/**
	 * Test that store endpoint validates required file.
	 */
	public function test_store_validates_required_file(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$response = $this->postJson( '/api/media', [
			'title' => 'Test Upload',
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'file' ] );
	}

	/**
	 * Test that store endpoint validates file size.
	 */
	public function test_store_validates_file_size(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		// Set max file size to 1KB
		config( [ 'artisanpack.media.max_file_size' => 1 ] );

		// Create a file that's 2KB (larger than the 1KB limit)
		$file = UploadedFile::fake()->create( 'large.jpg', 2, 'image/jpeg' );

		$response = $this->postJson( '/api/media', [
			'file' => $file,
		] );

		$response->assertUnprocessable()
			->assertJsonValidationErrors( [ 'file' ] );
	}

	/**
	 * Test that show endpoint returns single media item.
	 */
	public function test_show_returns_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

		$response = $this->getJson( '/api/media/' . $media->id );

		$response->assertOk()
			->assertJson( [
				'data' => [
					'id'        => $media->id,
					'title'     => $media->title,
					'file_name' => $media->file_name,
				],
			] );
	}

	/**
	 * Test that show endpoint returns 404 for non-existent media.
	 */
	public function test_show_returns_404_for_non_existent_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$response = $this->getJson( '/api/media/999999' );

		$response->assertNotFound();
	}

	/**
	 * Test that update endpoint updates media metadata.
	 */
	public function test_update_modifies_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$media = Media::factory()->create( [
			'uploaded_by' => $this->user->id,
			'title'       => 'Original Title',
		] );

		$response = $this->putJson( '/api/media/' . $media->id, [
			'title'       => 'Updated Title',
			'alt_text'    => 'Updated Alt Text',
			'caption'     => 'Updated Caption',
			'description' => 'Updated Description',
		] );

		$response->assertOk()
			->assertJson( [
				'data' => [
					'id'          => $media->id,
					'title'       => 'Updated Title',
					'alt_text'    => 'Updated Alt Text',
					'caption'     => 'Updated Caption',
					'description' => 'Updated Description',
				],
			] );

		$this->assertDatabaseHas( 'media', [
			'id'    => $media->id,
			'title' => 'Updated Title',
		] );
	}

	/**
	 * Test that destroy endpoint deletes media.
	 */
	public function test_destroy_deletes_media(): void
	{
		Sanctum::actingAs( $this->user, [ '*' ] );

		$media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

		$response = $this->deleteJson( '/api/media/' . $media->id );

		$response->assertNoContent();

		$this->assertSoftDeleted( 'media', [
			'id' => $media->id,
		] );
	}

	/**
	 * Test that unauthorized requests are rejected.
	 */
	public function test_unauthorized_requests_are_rejected(): void
	{
		// Don't authenticate

		$response = $this->getJson( '/api/media' );

		$response->assertUnauthorized();
	}
}
