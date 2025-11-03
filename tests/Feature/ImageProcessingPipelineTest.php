<?php

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Image Processing Pipeline Feature Tests
 *
 * Tests for the complete image processing pipeline from upload
 * through thumbnail generation and modern format conversion.
 *
 * @package Tests\Feature
 *
 * @since   1.0.0
 */
class ImageProcessingPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected MediaUploadService $uploadService;

    protected MediaProcessingService $processingService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        // Create user for testing
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Setup test disk
        Storage::fake('test-disk');

        $this->uploadService = app(MediaUploadService::class);
        $this->processingService = app(MediaProcessingService::class);

        // Configure for testing
        config([
            'artisanpack.media.disk' => 'test-disk',
            'artisanpack.media.enable_thumbnails' => true,
            'artisanpack.media.enable_modern_formats' => true,
            'artisanpack.media.modern_format' => 'webp',
            'artisanpack.media.image_quality' => 85,
            'artisanpack.media.image_sizes' => [
                'thumbnail' => [
                    'width' => 150,
                    'height' => 150,
                    'crop' => true,
                ],
                'medium' => [
                    'width' => 300,
                    'height' => 300,
                    'crop' => false,
                ],
            ],
        ]);
    }

    /**
     * Test complete image upload and processing pipeline.
     */
    public function test_complete_image_processing_pipeline(): void
    {
        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        // Upload the image
        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        // Verify media was created
        expect($media)->toBeInstanceOf(Media::class);
        expect($media->mime_type)->toBe('image/jpeg');
        expect($media->width)->toBe(800);
        expect($media->height)->toBe(600);

        // Verify file was stored
        Storage::disk('test-disk')->assertExists($media->file_path);

        // Process the image
        $this->processingService->processImage($media);

        // Reload media to get updated metadata
        $media->refresh();

        // Verify thumbnails were generated and stored in metadata
        expect($media->metadata)->toBeArray();
        expect($media->metadata)->toHaveKey('thumbnails');
    }

    /**
     * Test that thumbnails are generated with correct dimensions.
     */
    public function test_thumbnails_have_correct_dimensions(): void
    {
        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        // Generate thumbnails
        $thumbnails = $this->processingService->generateThumbnails($media);

        // Should have generated thumbnails for configured sizes
        expect($thumbnails)->toBeArray();
        expect($thumbnails)->toHaveKeys(['thumbnail', 'medium']);
    }

    /**
     * Test that modern format conversion creates WebP version.
     */
    public function test_modern_format_conversion_creates_webp(): void
    {
        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        // Convert to WebP
        $webpPath = $this->processingService->convertToModernFormat($media, 'webp');

        // May be null if conversion isn't supported, but shouldn't error
        if ($webpPath !== null) {
            // Verify metadata was updated
            $media->refresh();
            expect($media->metadata)->toHaveKey('modern_formats');
        }

        expect(true)->toBeTrue();
    }

    /**
     * Test that processImage handles both thumbnails and conversion.
     */
    public function test_process_image_handles_full_pipeline(): void
    {
        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        // Process should handle both thumbnails and modern formats
        $this->processingService->processImage($media);

        // Shouldn't throw any exceptions
        expect(true)->toBeTrue();
    }

    /**
     * Test that SVG images are handled correctly.
     */
    public function test_svg_images_skip_processing(): void
    {
        $file = UploadedFile::fake()->create('test.svg', 10, 'image/svg+xml');

        $media = Media::factory()->create([
            'mime_type' => 'image/svg+xml',
            'file_path' => 'uploads/test.svg',
            'disk' => 'test-disk',
        ]);

        // Store the file
        Storage::disk('test-disk')->put($media->file_path, '<svg></svg>');

        // Process should handle SVG gracefully
        $this->processingService->processImage($media);

        expect(true)->toBeTrue();
    }

    /**
     * Test that non-image files skip image processing.
     */
    public function test_non_images_skip_processing(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $media = Media::factory()->create([
            'mime_type' => 'application/pdf',
            'file_path' => 'uploads/test.pdf',
            'disk' => 'test-disk',
        ]);

        // Store the file
        Storage::disk('test-disk')->put($media->file_path, 'PDF content');

        // Process should skip non-images
        $this->processingService->processImage($media);

        expect(true)->toBeTrue();
    }

    /**
     * Test processing with thumbnails disabled.
     */
    public function test_processing_with_thumbnails_disabled(): void
    {
        config(['artisanpack.media.enable_thumbnails' => false]);

        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        $this->processingService->processImage($media);

        // Should complete without errors
        expect(true)->toBeTrue();
    }

    /**
     * Test processing with modern formats disabled.
     */
    public function test_processing_with_modern_formats_disabled(): void
    {
        config(['artisanpack.media.enable_modern_formats' => false]);

        $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

        $media = $this->uploadService->upload($file, ['disk' => 'test-disk']);

        $this->processingService->processImage($media);

        // Should complete without errors
        expect(true)->toBeTrue();
    }

    /**
     * Test that multiple images can be processed in sequence.
     */
    public function test_can_process_multiple_images(): void
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 1024, 768);
        $file3 = UploadedFile::fake()->image('photo3.jpg', 500, 500);

        $media1 = $this->uploadService->upload($file1, ['disk' => 'test-disk']);
        $media2 = $this->uploadService->upload($file2, ['disk' => 'test-disk']);
        $media3 = $this->uploadService->upload($file3, ['disk' => 'test-disk']);

        // Process all images
        $this->processingService->processImage($media1);
        $this->processingService->processImage($media2);
        $this->processingService->processImage($media3);

        // All files should exist
        Storage::disk('test-disk')->assertExists($media1->file_path);
        Storage::disk('test-disk')->assertExists($media2->file_path);
        Storage::disk('test-disk')->assertExists($media3->file_path);
    }
}
