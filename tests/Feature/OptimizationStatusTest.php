<?php

declare( strict_types=1 );

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Http\Resources\MediaResource;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Optimization Status Persistence Feature Tests.
 *
 * Covers the pipeline flow that persists `optimization_status`,
 * `optimized_at`, `optimization_bytes_saved`, `optimization_formats`,
 * and `optimization_error` on the Media row, plus the MediaResource
 * payload and the backfill artisan command.
 *
 * @since 1.5.0
 */
class OptimizationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected MediaProcessingService $service;

    protected MediaStorageService $storageService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        $this->user = User::factory()->create();
        $this->actingAs( $this->user );

        Storage::fake( 'test-disk' );

        $this->storageService = new MediaStorageService;
        $optimizationService  = new ImageOptimizationService;
        $this->service        = new MediaProcessingService( $this->storageService, $optimizationService );

        config( [
            'artisanpack.media.enable_thumbnails'     => false,
            'artisanpack.media.enable_modern_formats' => false,
            'artisanpack.media.user_model'            => User::class,
        ] );
    }

    public function test_process_image_marks_row_optimized_on_success(): void
    {
        $media = $this->createTestMedia();

        $this->service->processImage( $media );

        $media->refresh();

        expect( $media->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_OPTIMIZED );
        expect( $media->optimized_at )->not->toBeNull();
        expect( $media->optimization_original_size )->toBeGreaterThan( 0 );
        // With modern-format conversion disabled there's no derived file to
        // compare against, so bytes_saved is left null on purpose.
        expect( $media->optimization_bytes_saved )->toBeNull();
        expect( $media->optimization_error )->toBeNull();
        expect( $media->optimization_formats )->toBe( [] );
    }

    public function test_process_image_records_generated_modern_formats(): void
    {
        config( [
            'artisanpack.media.enable_modern_formats' => true,
            'artisanpack.media.modern_format'         => 'webp',
        ] );

        $media = $this->createTestMedia();

        $this->service->processImage( $media );

        $media->refresh();

        expect( $media->optimization_formats )->toBeArray();
        // On environments where WebP conversion actually succeeds we get
        // a "webp" key; when the driver refuses (no gd/imagick webp) we
        // get an empty array. Either outcome must still be a persisted
        // array and never a "failed" status.
        expect( $media->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_OPTIMIZED );
    }

    public function test_process_image_persists_failure_and_rethrows(): void
    {
        $media = $this->createTestMedia();

        config( [
            'artisanpack.media.enable_thumbnails' => true,
            'artisanpack.media.image_sizes'       => [
                'thumbnail' => [ 'width' => 150, 'height' => 150, 'crop' => true ],
            ],
        ] );

        addFilter( 'ap.mediaLibrary.imageSizes', function ( array $sizes ): array {
            throw new RuntimeException( 'boom' );
        }, 5 );

        try {
            $this->service->processImage( $media );
            $this->fail( 'Expected pipeline exception to bubble.' );
        } catch ( RuntimeException $e ) {
            expect( $e->getMessage() )->toBe( 'boom' );
        } finally {
            removeAllFilters( 'ap.mediaLibrary.imageSizes' );
        }

        $media->refresh();

        expect( $media->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_FAILED );
        expect( $media->optimization_error )->toBe( 'boom' );
    }

    public function test_process_image_persists_failure_when_before_process_listener_throws(): void
    {
        $media = $this->createTestMedia();

        addAction( 'ap.mediaLibrary.beforeProcess', function ( Media $m ): void {
            throw new RuntimeException( 'listener boom' );
        }, 5 );

        try {
            $this->service->processImage( $media );
            $this->fail( 'Expected listener exception to bubble.' );
        } catch ( RuntimeException $e ) {
            expect( $e->getMessage() )->toBe( 'listener boom' );
        } finally {
            removeAllActions( 'ap.mediaLibrary.beforeProcess' );
        }

        $media->refresh();

        expect( $media->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_FAILED );
        expect( $media->optimization_error )->toBe( 'listener boom' );
    }

    public function test_process_image_ignores_non_image_rows(): void
    {
        $media = Media::factory()->create( [
            'mime_type'   => 'application/pdf',
            'file_path'   => 'test.pdf',
            'uploaded_by' => $this->user->id,
        ] );

        $this->service->processImage( $media );

        $media->refresh();

        expect( $media->optimization_status )->toBeNull();
        expect( $media->optimized_at )->toBeNull();
    }

    public function test_media_resource_exposes_stable_optimization_shape(): void
    {
        $media = Media::factory()->create( [
            'mime_type'                  => 'image/jpeg',
            'file_path'                  => 'x.jpg',
            'uploaded_by'                => $this->user->id,
            'optimization_status'        => Media::OPTIMIZATION_STATUS_OPTIMIZED,
            'optimized_at'               => now(),
            'optimization_bytes_saved'   => 500,
            'optimization_original_size' => 2000,
            'optimization_formats'       => [ 'webp' => true ],
            'optimization_error'         => null,
        ] );

        $payload = ( new MediaResource( $media ) )->toArray( Request::create( '/' ) );

        expect( $payload )->toHaveKey( 'optimization' );
        expect( $payload['optimization'] )->toMatchArray( [
            'status'        => 'optimized',
            'bytes_saved'   => 500,
            'original_size' => 2000,
            'formats'       => [ 'webp' => true ],
            'error'         => null,
        ] );
        expect( $payload['optimization']['optimized_at'] )->toBeString();
    }

    public function test_media_resource_null_optimization_block_for_non_image(): void
    {
        $media = Media::factory()->document()->create( [
            'uploaded_by' => $this->user->id,
        ] );

        $payload = ( new MediaResource( $media ) )->toArray( Request::create( '/' ) );

        expect( $payload['optimization'] )->toBe( [
            'status'        => null,
            'optimized_at'  => null,
            'bytes_saved'   => null,
            'original_size' => null,
            'formats'       => null,
            'error'         => null,
        ] );
    }

    public function test_backfill_command_marks_legacy_image_rows(): void
    {
        $image    = Media::factory()->image()->create( [
            'uploaded_by'         => $this->user->id,
            'optimization_status' => null,
        ] );
        $document = Media::factory()->document()->create( [
            'uploaded_by'         => $this->user->id,
            'optimization_status' => null,
        ] );
        $alreadyOptimized = Media::factory()->image()->create( [
            'uploaded_by'         => $this->user->id,
            'optimization_status' => Media::OPTIMIZATION_STATUS_FAILED,
            'optimization_error'  => 'previous run',
        ] );

        $this->artisan( 'media:backfill-optimization-status' )
            ->assertSuccessful();

        $image->refresh();
        $document->refresh();
        $alreadyOptimized->refresh();

        expect( $image->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_OPTIMIZED );
        expect( $image->optimized_at )->not->toBeNull();
        expect( $image->optimization_bytes_saved )->toBeNull();

        expect( $document->optimization_status )->toBeNull();

        expect( $alreadyOptimized->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_FAILED );
        expect( $alreadyOptimized->optimization_error )->toBe( 'previous run' );
    }

    protected function createTestMedia(): Media
    {
        $file = UploadedFile::fake()->image( 'test.jpg', 500, 500 );
        $path = 'uploads/test.jpg';

        $this->storageService->store( $file, $path, 'test-disk' );

        return Media::factory()->create( [
            'mime_type'   => 'image/jpeg',
            'file_path'   => $path,
            'disk'        => 'test-disk',
            'file_size'   => Storage::disk( 'test-disk' )->size( $path ),
            'width'       => 500,
            'height'      => 500,
            'uploaded_by' => $this->user->id,
        ] );
    }
}
