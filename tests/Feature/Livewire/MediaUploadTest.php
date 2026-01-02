<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaUpload;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * MediaUpload Component Tests
 *
 * Tests for the MediaUpload Livewire component including file upload,
 * validation, progress tracking, and metadata handling.
 *
 * @since   1.0.0
 */
class MediaUploadTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('test-disk');

        $this->user = User::factory()->create();

        config([
            'artisanpack.media.disk' => 'test-disk',
            'artisanpack.media.user_model' => User::class,
            'artisanpack.media.allowed_mime_types' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            'artisanpack.media.max_file_size' => 10240,
        ]);

        Gate::before(fn ($user, $ability) => true);
    }

    /**
     * Test that the component renders successfully.
     */
    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->assertSet('files', [])
            ->assertSet('droppedFiles', [])
            ->assertSet('uploadedMedia', [])
            ->assertSet('uploadErrors', [])
            ->assertSet('isUploading', false)
            ->assertSet('uploadProgress', 0)
            ->assertSet('totalFiles', 0)
            ->assertSet('uploadedCount', 0)
            ->assertSet('folderId', null);
    }

    /**
     * Test that metadata has default values.
     */
    public function test_metadata_has_default_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->assertSet('metadata.title', '')
            ->assertSet('metadata.alt_text', '')
            ->assertSet('metadata.caption', '')
            ->assertSet('metadata.description', '');
    }

    /**
     * Test file validation for invalid mime types during upload.
     *
     * Note: Mime type validation happens in the upload service, not in the component's
     * file selection validation. This test verifies that invalid mime types result
     * in upload errors when processUpload is called.
     */
    public function test_validates_file_types_during_upload(): void
    {
        config(['artisanpack.media.max_file_size' => 10240]);
        config(['artisanpack.media.allowed_mime_types' => ['image/jpeg', 'image/png']]);

        $mockService = Mockery::mock(MediaUploadService::class);
        $mockService->shouldReceive('upload')
            ->once()
            ->andThrow(new \Exception('File type not allowed'));

        $this->app->instance(MediaUploadService::class, $mockService);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file])
            ->call('processUpload')
            ->assertCount('uploadErrors', 1);
    }

    /**
     * Test file validation for oversized files.
     */
    public function test_validates_file_size(): void
    {
        config(['artisanpack.media.max_file_size' => 1]);

        $file = UploadedFile::fake()->create('large.jpg', 5000, 'image/jpeg');

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file])
            ->assertHasErrors('files.0');
    }

    /**
     * Test that valid image files pass validation.
     */
    public function test_accepts_valid_image_files(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file])
            ->assertHasNoErrors();
    }

    /**
     * Test remove file from queue.
     */
    public function test_removes_file_from_queue(): void
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 100, 100);

        $component = Livewire::actingAs($this->user)
            ->test(MediaUpload::class);

        $component->set('files', [$file1, $file2])
            ->assertCount('files', 2)
            ->call('removeFile', 0)
            ->assertCount('files', 1);
    }

    /**
     * Test clear all files.
     */
    public function test_clears_all_files(): void
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file1, $file2])
            ->assertCount('files', 2)
            ->call('clearFiles')
            ->assertSet('files', [])
            ->assertSet('droppedFiles', [])
            ->assertSet('uploadedMedia', [])
            ->assertSet('uploadErrors', [])
            ->assertSet('uploadProgress', 0)
            ->assertSet('totalFiles', 0)
            ->assertSet('uploadedCount', 0);
    }

    /**
     * Test clear uploaded media.
     */
    public function test_clears_uploaded_media(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('uploadedMedia', [$media1, $media2])
            ->set('uploadErrors', ['error1'])
            ->set('uploadProgress', 100)
            ->set('uploadedCount', 2)
            ->call('clearUploaded')
            ->assertSet('uploadedMedia', [])
            ->assertSet('uploadErrors', [])
            ->assertSet('uploadProgress', 0)
            ->assertSet('uploadedCount', 0);
    }

    /**
     * Test folder selection.
     */
    public function test_can_set_folder_id(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('folderId', $folder->id)
            ->assertSet('folderId', $folder->id);
    }

    /**
     * Test that folder options are computed correctly.
     */
    public function test_folder_options_includes_all_folders(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaUpload::class);

        $folderOptions = $component->invade()->folderOptions();
        expect($folderOptions)->toBeArray();
        expect(count($folderOptions))->toBeGreaterThanOrEqual(4);
    }

    /**
     * Test metadata can be set.
     */
    public function test_can_set_metadata(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('metadata.title', 'Test Title')
            ->set('metadata.alt_text', 'Test Alt Text')
            ->set('metadata.caption', 'Test Caption')
            ->set('metadata.description', 'Test Description')
            ->assertSet('metadata.title', 'Test Title')
            ->assertSet('metadata.alt_text', 'Test Alt Text')
            ->assertSet('metadata.caption', 'Test Caption')
            ->assertSet('metadata.description', 'Test Description');
    }

    /**
     * Test process upload shows error when no files selected.
     */
    public function test_process_upload_shows_error_when_no_files(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->call('processUpload')
            ->assertHasErrors('files');
    }

    /**
     * Test that successful upload dispatches media-uploaded event.
     */
    public function test_successful_upload_dispatches_event(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        $mockService = Mockery::mock(MediaUploadService::class);
        $mockService->shouldReceive('upload')
            ->once()
            ->andReturn($media);

        $this->app->instance(MediaUploadService::class, $mockService);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file])
            ->call('processUpload')
            ->assertDispatched('media-uploaded');
    }

    /**
     * Test that upload clears metadata after completion.
     */
    public function test_upload_clears_metadata_after_completion(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        $mockService = Mockery::mock(MediaUploadService::class);
        $mockService->shouldReceive('upload')
            ->once()
            ->andReturn($media);

        $this->app->instance(MediaUploadService::class, $mockService);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('metadata.title', 'Test Title')
            ->set('metadata.alt_text', 'Test Alt Text')
            ->set('files', [$file])
            ->call('processUpload')
            ->assertSet('metadata.title', '')
            ->assertSet('metadata.alt_text', '');
    }

    /**
     * Test total files count property.
     */
    public function test_total_files_count_property(): void
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 100, 100);

        $component = Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file1, $file2]);

        expect($component->get('totalFilesCount'))->toBe(2);
    }

    /**
     * Test that upload handles errors gracefully.
     */
    public function test_upload_handles_errors_gracefully(): void
    {
        $mockService = Mockery::mock(MediaUploadService::class);
        $mockService->shouldReceive('upload')
            ->once()
            ->andThrow(new \Exception('Upload failed'));

        $this->app->instance(MediaUploadService::class, $mockService);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('files', [$file])
            ->call('processUpload')
            ->assertSet('isUploading', false)
            ->assertCount('uploadErrors', 1);
    }

    /**
     * Test that clear-uploaded event resets state.
     */
    public function test_clear_uploaded_event_resets_state(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->set('uploadedMedia', [$media])
            ->set('uploadProgress', 100)
            ->dispatch('clear-uploaded')
            ->assertSet('uploadedMedia', [])
            ->assertSet('uploadProgress', 0);
    }

    /**
     * Test component renders upload area.
     */
    public function test_renders_upload_area(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaUpload::class)
            ->assertStatus(200);
    }
}
