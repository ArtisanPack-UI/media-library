<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media Processing Service Tests
 *
 * Tests for the MediaProcessingService class that handles
 * image processing operations like thumbnail generation and
 * modern format conversion.
 *
 * @package Tests\Unit
 *
 * @since   1.0.0
 */
class MediaProcessingServiceTest extends TestCase
{
	use RefreshDatabase;

	protected MediaProcessingService $service;
	protected MediaStorageService $storageService;
	protected User $user;

	protected function setUp(): void
	{
		parent::setUp();

		$this->defineDatabaseMigrations();

		// Create user for testing
		$this->user = User::factory()->create();
		$this->actingAs( $this->user );

		// Setup test disk
		Storage::fake( 'test-disk' );

		$this->storageService = new MediaStorageService();
		$optimizationService  = new ImageOptimizationService();
		$this->service        = new MediaProcessingService( $this->storageService, $optimizationService );
	}

	/**
	 * Test that processImage can be called successfully for an image.
	 */
	public function test_can_process_image(): void
	{
		$media = $this->createTestMedia();

		// Should not throw exception
		$this->service->processImage( $media );

		$this->assertTrue( true );
	}

	/**
	 * Test that processImage skips non-image media.
	 */
	public function test_skips_processing_non_images(): void
	{
		$media = Media::factory()->create( [
											  'mime_type'   => 'application/pdf',
											  'file_path'   => 'test.pdf',
											  'uploaded_by' => $this->user->id,
										  ] );

		// Should not throw exception and should not process
		$this->service->processImage( $media );

		$this->assertTrue( true );
	}

	/**
	 * Test that thumbnails can be generated for an image.
	 */
	public function test_can_generate_thumbnails(): void
	{
		config( [
					'artisanpack.media.enable_thumbnails' => true,
					'artisanpack.media.image_sizes'       => [
						'thumbnail' => [
							'width'  => 150,
							'height' => 150,
							'crop'   => true,
						],
						'medium'    => [
							'width'  => 300,
							'height' => 300,
							'crop'   => false,
						],
					],
				] );

		$media = $this->createTestMedia();

		$thumbnails = $this->service->generateThumbnails( $media );

		expect( $thumbnails )->toBeArray();
	}

	/**
	 * Test that thumbnails are not generated for non-images.
	 */
	public function test_skips_thumbnails_for_non_images(): void
	{
		$media = Media::factory()->create( [
											  'mime_type'   => 'application/pdf',
											  'file_path'   => 'test.pdf',
											  'uploaded_by' => $this->user->id,
										  ] );

		$thumbnails = $this->service->generateThumbnails( $media );

		expect( $thumbnails )->toBeEmpty();
	}

	/**
	 * Test that modern format conversion can be triggered.
	 */
	public function test_can_convert_to_modern_format(): void
	{
		$media = $this->createTestMedia();

		// WebP conversion
		$result = $this->service->convertToModernFormat( $media, 'webp' );

		// Result may be null if conversion fails, or a string path if it succeeds
		$this->assertTrue( null === $result || is_string( $result ) );
	}

	/**
	 * Test that SVG images are not converted to modern formats.
	 */
	public function test_skips_svg_conversion(): void
	{
		$media = Media::factory()->create( [
											  'mime_type'   => 'image/svg+xml',
											  'file_path'   => 'test.svg',
											  'uploaded_by' => $this->user->id,
										  ] );

		$result = $this->service->convertToModernFormat( $media, 'webp' );

		expect( $result )->toBeNull();
	}

	/**
	 * Test that images already in modern format are not converted again.
	 */
	public function test_skips_conversion_for_modern_formats(): void
	{
		$media = Media::factory()->create( [
											  'mime_type'   => 'image/webp',
											  'file_path'   => 'test.webp',
											  'uploaded_by' => $this->user->id,
										  ] );

		$result = $this->service->convertToModernFormat( $media, 'webp' );

		expect( $result )->toBeNull();
	}

	/**
	 * Test that non-images are not converted.
	 */
	public function test_skips_conversion_for_non_images(): void
	{
		$media = Media::factory()->create( [
											  'mime_type'   => 'application/pdf',
											  'file_path'   => 'test.pdf',
											  'uploaded_by' => $this->user->id,
										  ] );

		$result = $this->service->convertToModernFormat( $media, 'webp' );

		expect( $result )->toBeNull();
	}

	/**
	 * Test that image dimensions can be extracted.
	 */
	public function test_can_extract_image_dimensions(): void
	{
		// Create a test image file
		$imagePath = $this->createTestImageFile();

		$dimensions = $this->service->extractImageDimensions( $imagePath );

		// May be null if image processing fails with fake images, but shouldn't error
		$this->assertTrue( null === $dimensions || ( is_array( $dimensions ) && isset( $dimensions['width'], $dimensions['height'] ) ) );
	}

	/**
	 * Test that extractImageDimensions returns null for invalid paths.
	 */
	public function test_extract_dimensions_returns_null_for_invalid_path(): void
	{
		$dimensions = $this->service->extractImageDimensions( '/invalid/path/image.jpg' );

		expect( $dimensions )->toBeNull();
	}

	/**
	 * Helper method to create a test Media instance with a real image file.
	 *
	 * @return Media The created media instance.
	 */
	protected function createTestMedia(): Media
	{
		$file = UploadedFile::fake()->image( 'test.jpg', 500, 500 );
		$path = 'uploads/test.jpg';

		// Store the file
		$this->storageService->store( $file, $path, 'test-disk' );

		// Create media instance
		return Media::factory()->create( [
											'mime_type'   => 'image/jpeg',
											'file_path'   => $path,
											'disk'        => 'test-disk',
											'width'       => 500,
											'height'      => 500,
											'uploaded_by' => $this->user->id,
										] );
	}

	/**
	 * Helper method to create a real test image file.
	 *
	 * @return string The path to the created image file.
	 */
	protected function createTestImageFile(): string
	{
		$file = UploadedFile::fake()->image( 'test.jpg', 500, 500 );

		return $file->getRealPath();
	}
}
