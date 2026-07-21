<?php

declare( strict_types=1 );

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Media library pipeline hook tests.
 *
 * Covers every hook added in v1.4.0: upload lifecycle, processing,
 * config, storage disk, delete lifecycle, and AI alt-text suggestion.
 *
 * @since 1.4.0
 */

/**
 * Clear every media pipeline hook between tests so subscribers cannot leak.
 */
$clearMediaHooks = static function (): void {
    if ( ! function_exists( 'removeAllFilters' ) ) {
        return;
    }

    $hooks = [
        'ap.mediaLibrary.uploading',
        'ap.mediaLibrary.uploadOptions',
        'ap.mediaLibrary.uploaded',
        'ap.mediaLibrary.filenameGenerated',
        'ap.mediaLibrary.allowedMimeTypes',
        'ap.mediaLibrary.maxFileSize',
        'ap.mediaLibrary.storageDisk',
        'ap.mediaLibrary.beforeProcess',
        'ap.mediaLibrary.imageSizes',
        'ap.mediaLibrary.thumbnailsGenerated',
        'ap.mediaLibrary.beforeDelete',
        'ap.mediaLibrary.deleted',
        'ap.mediaLibrary.altTextSuggestion',
    ];

    foreach ( $hooks as $hook ) {
        removeAllFilters( $hook );
        removeAllActions( $hook );
    }
};

beforeEach( function () use ( $clearMediaHooks ): void {
    if ( ! function_exists( 'addAction' ) || ! function_exists( 'addFilter' ) ) {
        $this->markTestSkipped( 'Hook system not available' );
    }

    $clearMediaHooks();

    Storage::fake( 'test-disk' );

    config( [
        'artisanpack.media.disk'               => 'test-disk',
        'artisanpack.media.allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
        ],
        'artisanpack.media.max_file_size'      => 10240,
        'artisanpack.media.image_sizes'        => [
            'thumbnail' => [ 'width' => 150, 'height' => 150, 'crop' => true ],
        ],
        'artisanpack.media.enable_thumbnails'     => true,
        'artisanpack.media.enable_modern_formats' => false,
    ] );

    $this->user = User::factory()->create();
    $this->actingAs( $this->user );
} );

afterEach( function () use ( $clearMediaHooks ): void {
    $clearMediaHooks();
} );

describe( 'ap.mediaLibrary.uploading', function (): void {
    it( 'fires with the uploaded file and caller options', function (): void {
        $captured = [];
        addAction( 'ap.mediaLibrary.uploading', function ( UploadedFile $file, array $options ) use ( &$captured ): void {
            $captured = [
                'name'    => $file->getClientOriginalName(),
                'options' => $options,
            ];
        } );

        $service = app( MediaUploadService::class );
        $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ), [ 'title' => 'A' ] );

        expect( $captured['name'] )->toBe( 'photo.jpg' );
        expect( $captured['options'] )->toBe( [ 'title' => 'A' ] );
    } );
} );

describe( 'ap.mediaLibrary.uploadOptions', function (): void {
    it( 'lets subscribers mutate the options before the record is persisted', function (): void {
        addFilter( 'ap.mediaLibrary.uploadOptions', function ( array $options, UploadedFile $file ): array {
            $options['title']    = 'Overridden Title';
            $options['alt_text'] = 'Overridden Alt';

            return $options;
        } );

        $service = app( MediaUploadService::class );
        $media   = $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ), [
            'title' => 'Original',
        ] );

        expect( $media->title )->toBe( 'Overridden Title' );
        expect( $media->alt_text )->toBe( 'Overridden Alt' );
    } );
} );

describe( 'ap.mediaLibrary.uploaded', function (): void {
    it( 'fires with the freshly created Media record after tags are attached', function (): void {
        $captured = null;
        addAction( 'ap.mediaLibrary.uploaded', function ( Media $media ) use ( &$captured ): void {
            $captured = $media;
        } );

        $service = app( MediaUploadService::class );
        $media   = $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ) );

        expect( $captured )->not->toBeNull();
        expect( $captured->id )->toBe( $media->id );
        expect( $captured->exists )->toBeTrue();
    } );
} );

describe( 'ap.mediaLibrary.filenameGenerated', function (): void {
    it( 'lets subscribers rewrite the generated filename', function (): void {
        addFilter( 'ap.mediaLibrary.filenameGenerated', function ( string $fileName, UploadedFile $file ): string {
            return 'custom-' . $fileName;
        } );

        $service  = app( MediaUploadService::class );
        $fileName = $service->generateFileName( UploadedFile::fake()->image( 'photo.jpg' ) );

        expect( $fileName )->toStartWith( 'custom-' );
    } );
} );

describe( 'ap.mediaLibrary.allowedMimeTypes', function (): void {
    it( 'allows subscribers to append additional MIME types to the allow-list', function (): void {
        addFilter( 'ap.mediaLibrary.allowedMimeTypes', function ( array $mimes ): array {
            $mimes[] = 'image/webp';

            return $mimes;
        } );

        $service = app( MediaUploadService::class );

        // image/webp is not in the base config, but the filter added it, so
        // validation should succeed for a webp file.
        $file = UploadedFile::fake()->image( 'photo.webp', 100, 100 )
            ->mimeType( 'image/webp' );

        expect( $service->validateFile( $file ) )->toBeTrue();
    } );
} );

describe( 'ap.mediaLibrary.maxFileSize', function (): void {
    it( 'lets subscribers override the maximum file size in KB', function (): void {
        addFilter( 'ap.mediaLibrary.maxFileSize', function ( int $size, ?Illuminate\Contracts\Auth\Authenticatable $user ): int {
            return 1; // 1 KB
        } );

        $service = app( MediaUploadService::class );

        // Fake image is well over 1 KB, so validation must fail.
        $file = UploadedFile::fake()->image( 'photo.jpg', 200, 200 );

        expect( fn () => $service->validateFile( $file ) )
            ->toThrow( Illuminate\Validation\ValidationException::class );
    } );

    it( 'passes the authenticated user through to subscribers', function (): void {
        $capturedUser = null;
        addFilter( 'ap.mediaLibrary.maxFileSize', function ( int $size, $user ) use ( &$capturedUser ): int {
            $capturedUser = $user;

            return $size;
        } );

        $service = app( MediaUploadService::class );
        $service->validateFile( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ) );

        expect( $capturedUser?->id )->toBe( $this->user->id );
    } );
} );

describe( 'ap.mediaLibrary.storageDisk', function (): void {
    it( 'lets subscribers reroute the storage disk before every operation', function (): void {
        Storage::fake( 'archive-disk' );

        addFilter( 'ap.mediaLibrary.storageDisk', function ( string $disk, ?Media $media ): string {
            return 'archive-disk';
        } );

        $service = app( MediaStorageService::class );

        // Writing through the storage service should land on the rerouted disk.
        $service->put( 'sample.txt', 'hello world' );

        expect( Storage::disk( 'archive-disk' )->exists( 'sample.txt' ) )->toBeTrue();
        expect( Storage::disk( 'test-disk' )->exists( 'sample.txt' ) )->toBeFalse();
    } );

    it( 'passes the Media instance to subscribers when reading through the Model helpers', function (): void {
        $captured = null;
        addFilter( 'ap.mediaLibrary.storageDisk', function ( string $disk, ?Media $media ) use ( &$captured ): string {
            if ( null !== $media ) {
                $captured = $media->id;
            }

            return $disk;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );
        $media->url();

        expect( $captured )->toBe( $media->id );
    } );

    it( 'memoizes the filter result per Media instance so serialization is not O(N)', function (): void {
        $calls = 0;
        addFilter( 'ap.mediaLibrary.storageDisk', function ( string $disk, ?Media $media ) use ( &$calls ): string {
            $calls++;

            return $disk;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );
        $media->url();
        $media->url();
        $media->url();

        // One resolution for the whole instance, not one per read.
        expect( $calls )->toBe( 1 );
    } );

    it( 'invalidates the memoized disk when $media->disk is re-assigned', function (): void {
        Storage::fake( 'other-disk' );

        $seen = [];
        addFilter( 'ap.mediaLibrary.storageDisk', function ( string $disk, ?Media $media ) use ( &$seen ): string {
            $seen[] = $disk;

            return $disk;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id, 'disk' => 'test-disk' ] );
        $media->url();
        $media->disk = 'other-disk';
        $media->url();

        expect( $seen )->toBe( [ 'test-disk', 'other-disk' ] );
    } );
} );

describe( 'ap.mediaLibrary.beforeProcess', function (): void {
    it( 'fires before an image is processed', function (): void {
        $captured = null;
        addAction( 'ap.mediaLibrary.beforeProcess', function ( Media $media ) use ( &$captured ): void {
            $captured = $media;
        } );

        $upload = app( MediaUploadService::class );
        $media  = $upload->upload( UploadedFile::fake()->image( 'photo.jpg', 200, 200 ) );

        app( MediaProcessingService::class )->processImage( $media );

        expect( $captured )->not->toBeNull();
        expect( $captured->id )->toBe( $media->id );
    } );

    it( 'does not fire for non-image media', function (): void {
        $called = false;
        addAction( 'ap.mediaLibrary.beforeProcess', function () use ( &$called ): void {
            $called = true;
        } );

        $media = Media::factory()->create( [
            'mime_type'   => 'application/pdf',
            'uploaded_by' => $this->user->id,
        ] );

        app( MediaProcessingService::class )->processImage( $media );

        expect( $called )->toBeFalse();
    } );
} );

describe( 'ap.mediaLibrary.imageSizes', function (): void {
    it( 'lets subscribers add or replace image sizes per media item', function (): void {
        addFilter( 'ap.mediaLibrary.imageSizes', function ( array $sizes, ?Media $media ): array {
            $sizes['tiny'] = [ 'width' => 32, 'height' => 32, 'crop' => true ];

            return $sizes;
        } );

        $upload = app( MediaUploadService::class );
        $media  = $upload->upload( UploadedFile::fake()->image( 'photo.jpg', 200, 200 ) );

        $thumbnails = app( MediaProcessingService::class )->generateThumbnails( $media );

        expect( $thumbnails )->toHaveKey( 'tiny' );
    } );
} );

describe( 'ap.mediaLibrary.thumbnailsGenerated', function (): void {
    it( 'fires with the media and a map of size name to public URL', function (): void {
        $captured = null;
        addAction( 'ap.mediaLibrary.thumbnailsGenerated', function ( Media $media, array $urls ) use ( &$captured ): void {
            $captured = [ 'media' => $media, 'urls' => $urls ];
        } );

        $upload = app( MediaUploadService::class );
        $media  = $upload->upload( UploadedFile::fake()->image( 'photo.jpg', 200, 200 ) );

        app( MediaProcessingService::class )->generateThumbnails( $media );

        expect( $captured )->not->toBeNull();
        expect( $captured['media']->id )->toBe( $media->id );
        expect( $captured['urls'] )->toHaveKey( 'thumbnail' );
        expect( $captured['urls']['thumbnail'] )->toBeString();
    } );
} );

describe( 'ap.mediaLibrary.beforeDelete', function (): void {
    it( 'fires from the Eloquent deleting event on soft delete', function (): void {
        $captured = null;
        addAction( 'ap.mediaLibrary.beforeDelete', function ( Media $media ) use ( &$captured ): void {
            $captured = [ 'id' => $media->id, 'trashed' => $media->trashed() ];
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );
        $media->delete();

        expect( $captured['id'] )->toBe( $media->id );
        // deleting event fires before soft-delete timestamp is written.
        expect( $captured['trashed'] )->toBeFalse();
    } );

    it( 'fires on forceDelete too', function (): void {
        $called = 0;
        addAction( 'ap.mediaLibrary.beforeDelete', function () use ( &$called ): void {
            $called++;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );
        $media->forceDelete();

        expect( $called )->toBe( 1 );
    } );
} );

describe( 'ap.mediaLibrary.deleted', function (): void {
    it( 'fires from the Eloquent deleted event', function (): void {
        $captured = null;
        addAction( 'ap.mediaLibrary.deleted', function ( Media $media ) use ( &$captured ): void {
            $captured = $media->id;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );
        $media->delete();

        expect( $captured )->toBe( $media->id );
    } );
} );

describe( 'ap.mediaLibrary.altTextSuggestion', function (): void {
    it( 'is registered with the filter API and returns the value unchanged when no subscribers are present', function (): void {
        // No subscribers registered: applying the filter must return the
        // original suggestion untouched. This mirrors the contract the
        // MediaEdit / MediaAiController fire sites depend on.
        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

        $filtered = applyFilters( 'ap.mediaLibrary.altTextSuggestion', 'a cat on a chair', $media );

        expect( $filtered )->toBe( 'a cat on a chair' );
    } );

    it( 'lets subscribers rewrite an AI-generated suggestion', function (): void {
        addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
            return null === $suggestion ? null : strtoupper( $suggestion );
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

        $filtered = applyFilters( 'ap.mediaLibrary.altTextSuggestion', 'a cat on a chair', $media );

        expect( $filtered )->toBe( 'A CAT ON A CHAIR' );
    } );

    it( 'lets subscribers clear a suggestion by returning null', function (): void {
        addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
            return null;
        } );

        $media = Media::factory()->create( [ 'uploaded_by' => $this->user->id ] );

        $filtered = applyFilters( 'ap.mediaLibrary.altTextSuggestion', 'ignored', $media );

        expect( $filtered )->toBeNull();
    } );
} );
