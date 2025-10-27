<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use ArtisanPackUI\MediaLibrary\Services\VideoProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Media Upload Service Tests
 *
 * Tests for the MediaUploadService class that handles
 * file uploads, validation, and metadata extraction.
 *
 * @package Tests\Unit
 *
 * @since   1.0.0
 */
class MediaUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MediaUploadService $service;

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

        // Configure media settings
        config([
            'artisanpack.media.disk' => 'test-disk',
            'artisanpack.media.allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'video/mp4',
            ],
            'artisanpack.media.max_file_size' => 10240,
        ]);

        // Create service instances
        $storageService = new MediaStorageService;
        $videoService = new VideoProcessingService($storageService);
        $this->service = new MediaUploadService($storageService, $videoService);
    }

    /**
     * Test that a valid image file can be uploaded successfully.
     */
    public function test_can_upload_valid_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $media = $this->service->upload($file);

        expect($media)->toBeInstanceOf(Media::class);
        expect($media->file_name)->toBeString();
        expect($media->file_path)->toBeString();
        expect($media->mime_type)->toBe('image/jpeg');
        expect($media->width)->toBe(100);
        expect($media->height)->toBe(100);
        expect($media->uploaded_by)->toBe($this->user->id);

        Storage::disk('test-disk')->assertExists($media->file_path);
    }

    /**
     * Test that upload with custom options works correctly.
     */
    public function test_can_upload_with_custom_options(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $options = [
            'title' => 'Test Image',
            'alt_text' => 'Test Alt Text',
            'caption' => 'Test Caption',
            'description' => 'Test Description',
        ];

        $media = $this->service->upload($file, $options);

        expect($media->title)->toBe('Test Image');
        expect($media->alt_text)->toBe('Test Alt Text');
        expect($media->caption)->toBe('Test Caption');
        expect($media->description)->toBe('Test Description');
    }

    /**
     * Test that validation fails for files that are too large.
     */
    public function test_validation_fails_for_large_files(): void
    {
        // Set a very small max file size
        config(['artisanpack.media.max_file_size' => 1]); // 1KB

        $file = UploadedFile::fake()->image('large.jpg', 1000, 1000);

        $this->expectException(ValidationException::class);

        $this->service->upload($file);
    }

    /**
     * Test that validation fails for disallowed MIME types.
     */
    public function test_validation_fails_for_disallowed_mime_types(): void
    {
        // Only allow images
        config(['artisanpack.media.allowed_mime_types' => ['image/jpeg', 'image/png']]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->expectException(ValidationException::class);

        $this->service->upload($file);
    }

    /**
     * Test that unique file names are generated.
     */
    public function test_generates_unique_file_names(): void
    {
        $file1 = UploadedFile::fake()->image('test.jpg');
        $file2 = UploadedFile::fake()->image('test.jpg');

        $fileName1 = $this->service->generateFileName($file1);
        $fileName2 = $this->service->generateFileName($file2);

        expect($fileName1)->not->toBe($fileName2);
        expect($fileName1)->toContain('test');
        expect($fileName2)->toContain('test');
    }

    /**
     * Test that file names are properly sanitized.
     */
    public function test_sanitizes_file_names(): void
    {
        $file = UploadedFile::fake()->image('Test File With Spaces!@#.jpg');

        $fileName = $this->service->generateFileName($file);

        // Should not contain spaces or special characters
        expect($fileName)->not->toContain(' ');
        expect($fileName)->not->toContain('!');
        expect($fileName)->not->toContain('@');
        expect($fileName)->not->toContain('#');
        expect($fileName)->toContain('test-file-with-spaces');
        expect($fileName)->toEndWith('.jpg');
    }

    /**
     * Test that image dimensions are extracted correctly.
     */
    public function test_extracts_image_dimensions(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 200, 150);

        $dimensions = $this->service->extractImageDimensions($file);

        expect($dimensions)->not->toBeNull();
        expect($dimensions['width'])->toBe(200);
        expect($dimensions['height'])->toBe(150);
    }

    /**
     * Test that upload path is generated based on configuration.
     */
    public function test_generates_upload_path_from_config(): void
    {
        config(['artisanpack.media.upload_path_format' => '{year}/{month}']);

        $path = $this->service->getUploadPath();

        $expectedPath = date('Y').'/'.date('m');
        expect($path)->toBe($expectedPath);
    }

    /**
     * Test that upload path supports user ID variable.
     */
    public function test_upload_path_supports_user_id(): void
    {
        config(['artisanpack.media.upload_path_format' => 'users/{user_id}']);

        $path = $this->service->getUploadPath(['uploaded_by' => 123]);

        expect($path)->toBe('users/123');
    }

    /**
     * Test that upload path supports day variable.
     */
    public function test_upload_path_supports_day_variable(): void
    {
        config(['artisanpack.media.upload_path_format' => '{year}/{month}/{day}']);

        $path = $this->service->getUploadPath();

        $expectedPath = date('Y').'/'.date('m').'/'.date('d');
        expect($path)->toBe($expectedPath);
    }

    /**
     * Test that file validation passes for valid files.
     */
    public function test_validates_valid_files(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->validateFile($file);

        expect($result)->toBeTrue();
    }

    /**
     * Test that metadata is extracted during upload.
     */
    public function test_extracts_metadata_during_upload(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 300, 200);

        $media = $this->service->upload($file);

        expect($media->width)->toBe(300);
        expect($media->height)->toBe(200);
        expect($media->file_size)->toBeInt();
        expect($media->file_size)->toBeGreaterThan(0);
    }

    /**
     * Test that the media record is created with correct uploaded_by.
     */
    public function test_sets_uploaded_by_to_authenticated_user(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $media = $this->service->upload($file);

        expect($media->uploaded_by)->toBe($this->user->id);
    }

    /**
     * Test that uploaded_by can be overridden in options.
     */
    public function test_can_override_uploaded_by(): void
    {
        $otherUser = User::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg');

        $media = $this->service->upload($file, ['uploaded_by' => $otherUser->id]);

        expect($media->uploaded_by)->toBe($otherUser->id);
    }
}
