<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media Storage Service Tests
 *
 * Tests for the MediaStorageService class that handles
 * file storage operations across different disks.
 *
 * @package Tests\Unit
 *
 * @since   1.0.0
 */
class MediaStorageServiceTest extends TestCase
{
    protected MediaStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MediaStorageService;

        // Setup test disk
        Storage::fake('test-disk');
    }

    /**
     * Test that a file can be stored successfully.
     */
    public function test_can_store_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        $storedPath = $this->service->store($file, $path, 'test-disk');

        Storage::disk('test-disk')->assertExists($storedPath);
        expect($storedPath)->toBe($path);
    }

    /**
     * Test that a file can be deleted successfully.
     */
    public function test_can_delete_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        // Store first
        $this->service->store($file, $path, 'test-disk');
        Storage::disk('test-disk')->assertExists($path);

        // Delete
        $result = $this->service->delete($path, 'test-disk');

        expect($result)->toBeTrue();
        Storage::disk('test-disk')->assertMissing($path);
    }

    /**
     * Test that file existence can be checked.
     */
    public function test_can_check_file_exists(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        // File doesn't exist yet
        expect($this->service->exists($path, 'test-disk'))->toBeFalse();

        // Store the file
        $this->service->store($file, $path, 'test-disk');

        // Now it exists
        expect($this->service->exists($path, 'test-disk'))->toBeTrue();
    }

    /**
     * Test that file URL can be generated.
     */
    public function test_can_get_file_url(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        $this->service->store($file, $path, 'test-disk');
        $url = $this->service->url($path, 'test-disk');

        expect($url)->toBeString();
        expect($url)->toContain('test.jpg');
    }

    /**
     * Test that file contents can be retrieved.
     */
    public function test_can_get_file_contents(): void
    {
        $content = 'Test file content';
        $path = 'uploads/test.txt';

        Storage::disk('test-disk')->put($path, $content);

        $retrieved = $this->service->get($path, 'test-disk');

        expect($retrieved)->toBe($content);
    }

    /**
     * Test that contents can be put to a file.
     */
    public function test_can_put_file_contents(): void
    {
        $content = 'Test file content';
        $path = 'uploads/test.txt';

        $result = $this->service->put($path, $content, 'test-disk');

        expect($result)->toBeTrue();
        Storage::disk('test-disk')->assertExists($path);
        expect(Storage::disk('test-disk')->get($path))->toBe($content);
    }

    /**
     * Test that file size can be retrieved.
     */
    public function test_can_get_file_size(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        $this->service->store($file, $path, 'test-disk');
        $size = $this->service->size($path, 'test-disk');

        expect($size)->toBeInt();
        expect($size)->toBeGreaterThan(0);
    }

    /**
     * Test that file MIME type can be retrieved.
     */
    public function test_can_get_mime_type(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        $this->service->store($file, $path, 'test-disk');
        $mimeType = $this->service->mimeType($path, 'test-disk');

        expect($mimeType)->toBeString();
        expect($mimeType)->toContain('image');
    }

    /**
     * Test that a file can be copied.
     */
    public function test_can_copy_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $fromPath = 'uploads/test.jpg';
        $toPath = 'uploads/test-copy.jpg';

        $this->service->store($file, $fromPath, 'test-disk');
        $result = $this->service->copy($fromPath, $toPath, 'test-disk');

        expect($result)->toBeTrue();
        Storage::disk('test-disk')->assertExists($fromPath);
        Storage::disk('test-disk')->assertExists($toPath);
    }

    /**
     * Test that a file can be moved.
     */
    public function test_can_move_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $fromPath = 'uploads/test.jpg';
        $toPath = 'uploads/moved/test.jpg';

        $this->service->store($file, $fromPath, 'test-disk');
        $result = $this->service->move($fromPath, $toPath, 'test-disk');

        expect($result)->toBeTrue();
        Storage::disk('test-disk')->assertMissing($fromPath);
        Storage::disk('test-disk')->assertExists($toPath);
    }

    /**
     * Test that the correct disk is resolved when none is provided.
     */
    public function test_resolves_default_disk(): void
    {
        config(['artisanpack.media.disk' => 'test-disk']);

        $file = UploadedFile::fake()->image('test.jpg');
        $path = 'uploads/test.jpg';

        $storedPath = $this->service->store($file, $path, null);

        Storage::disk('test-disk')->assertExists($storedPath);
    }
}
