<?php

declare( strict_types=1 );

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use finfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Intervention\Image\Config;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\DriverInterface;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Thumbnail Format Feature Tests.
 *
 * Covers thumbnails keeping the source file's format and transparency,
 * plus the command that repairs thumbnails generated before that fix.
 *
 * @since 1.7.0
 */
class ThumbnailFormatTest extends TestCase
{
    use RefreshDatabase;

    protected MediaProcessingService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        $this->user = User::factory()->create();
        $this->actingAs( $this->user );

        Storage::fake( 'test-disk' );

        $this->service = $this->serviceUsing( GdDriver::class );

        config( [
            'artisanpack.media.enable_thumbnails'     => true,
            'artisanpack.media.enable_modern_formats' => false,
            'artisanpack.media.user_model'            => User::class,
            'artisanpack.media.image_sizes'           => [
                'thumbnail' => [ 'width' => 40, 'height' => 40, 'crop' => true ],
            ],
            'artisanpack.media.custom_image_sizes'    => [],
        ] );
    }

    /**
     * Provides the Intervention drivers thumbnails can run on.
     *
     * @return array<string, array{0: class-string}>
     */
    public static function driverProvider(): array
    {
        return [
            'gd'      => [ GdDriver::class ],
            'imagick' => [ ImagickDriver::class ],
        ];
    }

    /**
     * Provides source formats and the encoded format expected for each.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: int}>
     */
    public static function formatProvider(): array
    {
        return [
            'jpg'       => [ 'photo.jpg', 'image/jpeg', 'photo-thumbnail.jpg', IMAGETYPE_JPEG ],
            'jpeg'      => [ 'photo.jpeg', 'image/jpeg', 'photo-thumbnail.jpeg', IMAGETYPE_JPEG ],
            'png'       => [ 'photo.png', 'image/png', 'photo-thumbnail.png', IMAGETYPE_PNG ],
            'uppercase' => [ 'photo.PNG', 'image/png', 'photo-thumbnail.PNG', IMAGETYPE_PNG ],
            'gif'       => [ 'photo.gif', 'image/gif', 'photo-thumbnail.gif', IMAGETYPE_GIF ],
            'webp'      => [ 'photo.webp', 'image/webp', 'photo-thumbnail.webp', IMAGETYPE_WEBP ],
        ];
    }

    #[DataProvider( 'formatProvider' )]
    public function test_thumbnail_encoding_matches_the_source_format(
        string $filename,
        string $mimeType,
        string $expectedName,
        int $expectedType,
    ): void {
        $media = $this->createImageMedia( $filename, $mimeType );

        $thumbnails = $this->service->generateThumbnails( $media );

        expect( $thumbnails )->toBe( [ 'thumbnail' => 'uploads/' . $expectedName ] );
        expect( getimagesizefromstring( Storage::disk( 'test-disk' )->get( $thumbnails['thumbnail'] ) )[2] )
            ->toBe( $expectedType );
    }

    public function test_avif_thumbnails_are_encoded_as_avif(): void
    {
        if ( ! extension_loaded( 'imagick' ) || [] === Imagick::queryFormats( 'AVIF' ) ) {
            $this->markTestSkipped( 'Imagick AVIF support is not available.' );
        }

        $service = $this->serviceUsing( ImagickDriver::class );
        $image   = ( new ImageManager( new ImagickDriver ) )->create( 80, 80 )->fill( 'ff0000' );

        Storage::disk( 'test-disk' )->put( 'uploads/photo.avif', (string) $image->toAvif() );

        $media = $this->createMediaRow( 'uploads/photo.avif', 'image/avif' );

        $thumbnails = $service->generateThumbnails( $media );

        expect( $thumbnails )->toHaveKey( 'thumbnail' );
        expect( ( new finfo( FILEINFO_MIME_TYPE ) )->buffer( Storage::disk( 'test-disk' )->get( $thumbnails['thumbnail'] ) ) )
            ->toBe( 'image/avif' );
    }

    #[DataProvider( 'driverProvider' )]
    public function test_png_thumbnails_keep_transparency( string $driver ): void
    {
        if ( ImagickDriver::class === $driver && ! extension_loaded( 'imagick' ) ) {
            $this->markTestSkipped( 'Imagick is not available.' );
        }

        $media = $this->createImageMedia( 'logo.png', 'image/png' );

        $thumbnails = $this->serviceUsing( $driver )->generateThumbnails( $media );

        expect( $this->cornerAlpha( $thumbnails['thumbnail'] ) )->toBe( 127 );
    }

    #[DataProvider( 'driverProvider' )]
    public function test_webp_thumbnails_keep_transparency( string $driver ): void
    {
        if ( ImagickDriver::class === $driver && ! extension_loaded( 'imagick' ) ) {
            $this->markTestSkipped( 'Imagick is not available.' );
        }

        $media = $this->createImageMedia( 'logo.webp', 'image/webp' );

        $thumbnails = $this->serviceUsing( $driver )->generateThumbnails( $media );

        expect( $this->cornerAlpha( $thumbnails['thumbnail'] ) )->toBe( 127 );
    }

    #[DataProvider( 'driverProvider' )]
    public function test_svg_sources_get_no_thumbnail_and_fall_back_to_the_original( string $driver ): void
    {
        if ( ImagickDriver::class === $driver && ! extension_loaded( 'imagick' ) ) {
            $this->markTestSkipped( 'Imagick is not available.' );
        }

        Storage::disk( 'test-disk' )->put(
            'uploads/icon.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80"/></svg>',
        );

        $media = $this->createMediaRow( 'uploads/icon.svg', 'image/svg+xml' );

        expect( $this->serviceUsing( $driver )->generateThumbnails( $media ) )->toBe( [] );
        Storage::disk( 'test-disk' )->assertMissing( 'uploads/icon-thumbnail.svg' );
        expect( $media->imageUrl( 'thumbnail' ) )->toBe( $media->url() );
    }

    public function test_unsupported_sources_are_never_decoded(): void
    {
        $driver = Mockery::mock( DriverInterface::class );
        $driver->shouldReceive( 'config' )->andReturn( new Config );
        $driver->shouldNotReceive( 'handleInput' );

        $service = $this->serviceUsing( GdDriver::class );

        ( fn () => $this->imageManager = new ImageManager( $driver ) )->call( $service );

        $media = $this->createMediaRow( 'uploads/icon.svg', 'image/svg+xml' );

        expect( $service->generateThumbnails( $media ) )->toBe( [] );
    }

    public function test_generated_thumbnail_is_what_the_media_model_serves(): void
    {
        $media = $this->createImageMedia( 'logo.png', 'image/png' );

        $this->service->generateThumbnails( $media );

        expect( $media->imageUrl( 'thumbnail' ) )
            ->toBe( Storage::disk( 'test-disk' )->url( 'uploads/logo-thumbnail.png' ) );
    }

    public function test_command_replaces_mislabeled_thumbnails(): void
    {
        $media = $this->createImageMedia( 'logo.png', 'image/png' );

        Storage::disk( 'test-disk' )->put(
            'uploads/logo-thumbnail.png',
            (string) ( new ImageManager( new GdDriver ) )->create( 40, 40 )->toJpeg(),
        );

        $this->artisan( 'media:regenerate-thumbnails' )
            ->expectsOutput( 'Regenerated thumbnails for 1 image(s); 0 failed.' )
            ->assertSuccessful();

        expect( getimagesizefromstring( Storage::disk( 'test-disk' )->get( 'uploads/logo-thumbnail.png' ) )[2] )
            ->toBe( IMAGETYPE_PNG );
        expect( $media->fresh()->metadata['thumbnails'] )->toBe( [ 'thumbnail' => 'uploads/logo-thumbnail.png' ] );
    }

    public function test_command_skips_jpeg_svg_and_non_image_rows(): void
    {
        $this->createImageMedia( 'photo.jpg', 'image/jpeg' );
        Storage::disk( 'test-disk' )->put( 'uploads/icon.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>' );
        $this->createMediaRow( 'uploads/icon.svg', 'image/svg+xml' );
        $this->createMediaRow( 'uploads/doc.pdf', 'application/pdf' );

        $this->artisan( 'media:regenerate-thumbnails' )
            ->expectsOutput( 'Regenerated thumbnails for 0 image(s); 0 failed.' )
            ->assertSuccessful();

        Storage::disk( 'test-disk' )->assertMissing( 'uploads/photo-thumbnail.jpg' );
    }

    public function test_command_leaves_optimization_tracking_untouched(): void
    {
        $media = $this->createImageMedia( 'logo.png', 'image/png' );
        $media->forceFill( [
            'optimization_status'      => Media::OPTIMIZATION_STATUS_OPTIMIZED,
            'optimization_bytes_saved' => 123,
        ] )->save();

        $this->artisan( 'media:regenerate-thumbnails' )->assertSuccessful();

        $media->refresh();

        expect( $media->optimization_status )->toBe( Media::OPTIMIZATION_STATUS_OPTIMIZED );
        expect( $media->optimization_bytes_saved )->toBe( 123 );
    }

    public function test_command_keeps_going_and_fails_when_a_source_is_missing(): void
    {
        $this->createMediaRow( 'uploads/missing.png', 'image/png' );
        $this->createImageMedia( 'logo.png', 'image/png' );

        $this->artisan( 'media:regenerate-thumbnails' )
            ->expectsOutput( 'Regenerated thumbnails for 1 image(s); 1 failed.' )
            ->assertFailed();

        Storage::disk( 'test-disk' )->assertExists( 'uploads/logo-thumbnail.png' );
    }

    /**
     * Builds a processing service locked to one Intervention driver.
     *
     * @param class-string $driver The driver class.
     *
     * @return MediaProcessingService The service instance.
     */
    protected function serviceUsing( string $driver ): MediaProcessingService
    {
        $service = new MediaProcessingService( new MediaStorageService, new ImageOptimizationService );

        ( fn () => $this->imageManager = new ImageManager( new $driver ) )->call( $service );

        return $service;
    }

    /**
     * Stores an 80x80 image that is fully transparent apart from a centered
     * block, and creates its media row.
     *
     * @param string $filename The file name inside the uploads directory.
     * @param string $mimeType The media row's MIME type.
     *
     * @return Media The created media instance.
     */
    protected function createImageMedia( string $filename, string $mimeType ): Media
    {
        $canvas = imagecreatetruecolor( 80, 80 );
        imagealphablending( $canvas, false );
        imagesavealpha( $canvas, true );
        imagefill( $canvas, 0, 0, imagecolorallocatealpha( $canvas, 0, 0, 0, 127 ) );
        imagefilledrectangle( $canvas, 30, 30, 50, 50, imagecolorallocatealpha( $canvas, 255, 0, 0, 0 ) );

        ob_start();
        match ( $mimeType ) {
            'image/png'  => imagepng( $canvas ),
            'image/gif'  => imagegif( $canvas ),
            'image/webp' => imagewebp( $canvas ),
            default      => imagejpeg( $canvas ),
        };
        $contents = ob_get_clean();

        Storage::disk( 'test-disk' )->put( 'uploads/' . $filename, $contents );

        return $this->createMediaRow( 'uploads/' . $filename, $mimeType );
    }

    /**
     * Creates a media row on the test disk.
     *
     * @param string $path     The file path.
     * @param string $mimeType The MIME type.
     *
     * @return Media The created media instance.
     */
    protected function createMediaRow( string $path, string $mimeType ): Media
    {
        return Media::factory()->create( [
            'mime_type'   => $mimeType,
            'file_path'   => $path,
            'disk'        => 'test-disk',
            'uploaded_by' => $this->user->id,
        ] );
    }

    /**
     * Reads the alpha value of a stored image's top-left pixel.
     *
     * @param string $path The stored image path.
     *
     * @return int The GD alpha value (0 is opaque, 127 is transparent).
     */
    protected function cornerAlpha( string $path ): int
    {
        $image = imagecreatefromstring( Storage::disk( 'test-disk' )->get( $path ) );

        return imagecolorsforindex( $image, imagecolorat( $image, 0, 0 ) )['alpha'];
    }
}
