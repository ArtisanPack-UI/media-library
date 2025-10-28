<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Image Optimization Service Tests
 *
 * Tests for the ImageOptimizationService class that handles
 * image optimization operations like compression, resizing,
 * and format conversion.
 *
 * @package Tests\Unit
 *
 * @since   1.0.0
 */
class ImageOptimizationServiceTest extends TestCase
{
	protected ImageOptimizationService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = new ImageOptimizationService();
	}

	/**
	 * Test that an image can be optimized.
	 */
	public function test_can_optimize_image(): void
	{
		$imagePath = $this->createTestImageFile();
		$originalSize = filesize( $imagePath );

		$result = $this->service->optimize( $imagePath, [ 'quality' => 85 ] );

		expect( $result )->toBeTrue();
		expect( file_exists( $imagePath ) )->toBeTrue();

		// File size may change after optimization
		$newSize = filesize( $imagePath );
		expect( $newSize )->toBeGreaterThan( 0 );
	}

	/**
	 * Test that optimize returns false for invalid paths.
	 */
	public function test_optimize_returns_false_for_invalid_path(): void
	{
		$result = $this->service->optimize( '/invalid/path/image.jpg' );

		expect( $result )->toBeFalse();
	}

	/**
	 * Test that an image can be resized.
	 */
	public function test_can_resize_image(): void
	{
		$imagePath = $this->createTestImageFile();

		$result = $this->service->resize( $imagePath, 200, 200 );

		expect( $result )->toBeTrue();
		expect( file_exists( $imagePath ) )->toBeTrue();

		// Verify dimensions
		$imageSize = getimagesize( $imagePath );
		expect( $imageSize )->not()->toBeFalse();

		// Image should be scaled to fit within 200x200
		expect( $imageSize[0] )->toBeLessThanOrEqual( 200 );
		expect( $imageSize[1] )->toBeLessThanOrEqual( 200 );
	}

	/**
	 * Test that an image can be resized with crop.
	 */
	public function test_can_resize_image_with_crop(): void
	{
		$imagePath = $this->createTestImageFile();

		$result = $this->service->resize( $imagePath, 200, 200, true );

		expect( $result )->toBeTrue();
		expect( file_exists( $imagePath ) )->toBeTrue();

		// Verify exact dimensions when cropped
		$imageSize = getimagesize( $imagePath );
		expect( $imageSize )->not()->toBeFalse();
		expect( $imageSize[0] )->toBe( 200 );
		expect( $imageSize[1] )->toBe( 200 );
	}

	/**
	 * Test that resize returns false for invalid paths.
	 */
	public function test_resize_returns_false_for_invalid_path(): void
	{
		$result = $this->service->resize( '/invalid/path/image.jpg', 200, 200 );

		expect( $result )->toBeFalse();
	}

	/**
	 * Test that an image can be converted to a different format.
	 */
	public function test_can_convert_image_format(): void
	{
		$imagePath = $this->createTestImageFile( 'test.jpg' );
		$pathInfo = pathinfo( $imagePath );

		$convertedPath = $this->service->convert( $imagePath, 'png' );

		expect( $convertedPath )->not()->toBeNull();
		expect( file_exists( $convertedPath ) )->toBeTrue();
		expect( pathinfo( $convertedPath, PATHINFO_EXTENSION ) )->toBe( 'png' );

		// Clean up converted file
		if ( null !== $convertedPath && file_exists( $convertedPath ) ) {
			unlink( $convertedPath );
		}
	}

	/**
	 * Test that convert returns null for invalid paths.
	 */
	public function test_convert_returns_null_for_invalid_path(): void
	{
		$result = $this->service->convert( '/invalid/path/image.jpg', 'png' );

		expect( $result )->toBeNull();
	}

	/**
	 * Test that compress is an alias for optimize.
	 */
	public function test_compress_optimizes_image(): void
	{
		$imagePath = $this->createTestImageFile();

		$result = $this->service->compress( $imagePath, 85 );

		expect( $result )->toBeTrue();
		expect( file_exists( $imagePath ) )->toBeTrue();
	}

	/**
	 * Test optimization with different quality settings.
	 */
	public function test_can_optimize_with_different_quality(): void
	{
		$imagePath1 = $this->createTestImageFile( 'test1.jpg' );
		$imagePath2 = $this->createTestImageFile( 'test2.jpg' );

		// Optimize with high quality
		$result1 = $this->service->optimize( $imagePath1, [ 'quality' => 95 ] );
		expect( $result1 )->toBeTrue();

		// Optimize with low quality
		$result2 = $this->service->optimize( $imagePath2, [ 'quality' => 50 ] );
		expect( $result2 )->toBeTrue();

		// Both should succeed
		expect( file_exists( $imagePath1 ) )->toBeTrue();
		expect( file_exists( $imagePath2 ) )->toBeTrue();
	}

	/**
	 * Helper method to create a real test image file.
	 *
	 * @param string $filename The filename for the test image.
	 * @return string The path to the created image file.
	 */
	protected function createTestImageFile( string $filename = 'test.jpg' ): string
	{
		// Create a real image using GD
		$width  = 500;
		$height = 500;
		$image  = imagecreatetruecolor( $width, $height );

		// Fill with a color (blue)
		$blue = imagecolorallocate( $image, 0, 0, 255 );
		imagefill( $image, 0, 0, $blue );

		// Add some white text
		$white = imagecolorallocate( $image, 255, 255, 255 );
		imagestring( $image, 5, 200, 240, 'Test Image', $white );

		// Save to temp file
		$tempPath = sys_get_temp_dir() . '/' . uniqid() . '-' . $filename;

		// Determine format from filename
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( 'png' === $extension ) {
			imagepng( $image, $tempPath );
		} elseif ( in_array( $extension, [ 'jpg', 'jpeg' ], true ) ) {
			imagejpeg( $image, $tempPath, 90 );
		} else {
			// Default to JPEG
			imagejpeg( $image, $tempPath, 90 );
		}

		imagedestroy( $image );

		return $tempPath;
	}
}
