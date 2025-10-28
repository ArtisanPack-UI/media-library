<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Policies\MediaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Media Policy Tests
 *
 * Tests for the MediaPolicy class that handles authorization
 * for media-related operations.
 *
 * @package Tests\Unit
 *
 * @since   1.0.0
 */
class MediaPolicyTest extends TestCase
{
	use RefreshDatabase;

	protected MediaPolicy $policy;
	protected User $user;
	protected Media $media;

	protected function setUp(): void
	{
		parent::setUp();

		$this->defineDatabaseMigrations();

		// Create test user and media
		$this->user = User::factory()->create();
		$this->media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

		$this->policy = new MediaPolicy();
	}

	/**
	 * Test that viewAny calls the correct capability check.
	 */
	public function test_view_any_checks_capability(): void
	{
		// Mock the user's can method
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.view' ) )
			->willReturn( true );

		$result = $this->policy->viewAny( $userMock );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that view calls the correct capability check.
	 */
	public function test_view_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.view' ) )
			->willReturn( true );

		$result = $this->policy->view( $userMock, $this->media );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that create calls the correct capability check.
	 */
	public function test_create_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.upload' ) )
			->willReturn( true );

		$result = $this->policy->create( $userMock );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that update calls the correct capability check.
	 */
	public function test_update_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.edit' ) )
			->willReturn( true );

		$result = $this->policy->update( $userMock, $this->media );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that delete calls the correct capability check.
	 */
	public function test_delete_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.delete' ) )
			->willReturn( true );

		$result = $this->policy->delete( $userMock, $this->media );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that restore calls the correct capability check.
	 */
	public function test_restore_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.delete' ) )
			->willReturn( true );

		$result = $this->policy->restore( $userMock, $this->media );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that forceDelete calls the correct capability check.
	 */
	public function test_force_delete_checks_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->once() )
			->method( 'can' )
			->with( $this->equalTo( 'media.delete' ) )
			->willReturn( true );

		$result = $this->policy->forceDelete( $userMock, $this->media );

		expect( $result )->toBeTrue();
	}

	/**
	 * Test that policies return false when user doesn't have capability.
	 */
	public function test_returns_false_when_no_capability(): void
	{
		$userMock = $this->createMock( User::class );
		$userMock->expects( $this->exactly( 7 ) )
			->method( 'can' )
			->willReturn( false );

		expect( $this->policy->viewAny( $userMock ) )->toBeFalse();
		expect( $this->policy->view( $userMock, $this->media ) )->toBeFalse();
		expect( $this->policy->create( $userMock ) )->toBeFalse();
		expect( $this->policy->update( $userMock, $this->media ) )->toBeFalse();
		expect( $this->policy->delete( $userMock, $this->media ) )->toBeFalse();
		expect( $this->policy->restore( $userMock, $this->media ) )->toBeFalse();
		expect( $this->policy->forceDelete( $userMock, $this->media ) )->toBeFalse();
	}
}
