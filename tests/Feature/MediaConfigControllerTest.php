<?php

declare( strict_types=1 );

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Managers\MediaManager;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Media Config Controller Feature Tests
 *
 * Tests for the GET /api/media/config endpoint that exposes
 * upload configuration for client-side validation.
 *
 * @package Tests\Feature
 *
 * @since   1.2.0
 */
class MediaConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        config( [
            'artisanpack.media.max_file_size'       => 10240,
            'artisanpack.media.allowed_mime_types'  => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'video/mp4',
                'video/webm',
                'audio/mpeg',
                'audio/wav',
                'application/pdf',
                'text/plain',
            ],
            'artisanpack.media.image_sizes' => [
                'thumbnail' => [
                    'width'  => 150,
                    'height' => 150,
                    'crop'   => true,
                ],
                'medium' => [
                    'width'  => 300,
                    'height' => 300,
                    'crop'   => false,
                ],
                'large' => [
                    'width'  => 1024,
                    'height' => 1024,
                    'crop'   => false,
                ],
            ],
            'artisanpack.media.enable_modern_formats' => true,
            'artisanpack.media.modern_format'         => 'webp',
        ] );
    }

    /**
     * Test that the config endpoint is publicly accessible without authentication.
     */
    public function test_config_endpoint_is_publicly_accessible(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $response->assertOk();
    }

    /**
     * Test that the config endpoint returns the correct JSON structure.
     */
    public function test_config_returns_correct_structure(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonStructure( [
                'upload' => [
                    'max_file_size',
                    'max_file_size_human',
                    'allowed_mime_types',
                    'allowed_extensions',
                ],
                'image_sizes' => [
                    'thumbnail' => ['width', 'height', 'crop'],
                    'medium'    => ['width', 'height', 'crop'],
                    'large'     => ['width', 'height', 'crop'],
                ],
                'features' => [
                    'webp_conversion',
                    'avif_conversion',
                ],
            ] );
    }

    /**
     * Test that the config endpoint returns the correct max file size.
     */
    public function test_config_returns_correct_max_file_size(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'upload.max_file_size', 10240 )
            ->assertJsonPath( 'upload.max_file_size_human', '10 MB' );
    }

    /**
     * Test that MIME types are grouped by category.
     */
    public function test_config_groups_mime_types_by_category(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $data = $response->json();

        $this->assertArrayHasKey( 'image', $data['upload']['allowed_mime_types'] );
        $this->assertArrayHasKey( 'video', $data['upload']['allowed_mime_types'] );
        $this->assertArrayHasKey( 'audio', $data['upload']['allowed_mime_types'] );
        $this->assertArrayHasKey( 'document', $data['upload']['allowed_mime_types'] );

        $this->assertContains( 'image/jpeg', $data['upload']['allowed_mime_types']['image'] );
        $this->assertContains( 'video/mp4', $data['upload']['allowed_mime_types']['video'] );
        $this->assertContains( 'audio/mpeg', $data['upload']['allowed_mime_types']['audio'] );
        $this->assertContains( 'application/pdf', $data['upload']['allowed_mime_types']['document'] );
        $this->assertContains( 'text/plain', $data['upload']['allowed_mime_types']['document'] );
    }

    /**
     * Test that allowed extensions are extracted from MIME types.
     */
    public function test_config_returns_allowed_extensions(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $extensions = $response->json( 'upload.allowed_extensions' );

        $this->assertContains( 'jpg', $extensions );
        $this->assertContains( 'png', $extensions );
        $this->assertContains( 'gif', $extensions );
        $this->assertContains( 'webp', $extensions );
        $this->assertContains( 'svg', $extensions );
        $this->assertContains( 'mp4', $extensions );
        $this->assertContains( 'webm', $extensions );
        $this->assertContains( 'mp3', $extensions );
        $this->assertContains( 'wav', $extensions );
        $this->assertContains( 'pdf', $extensions );
        $this->assertContains( 'txt', $extensions );
    }

    /**
     * Test that image sizes include default config values.
     */
    public function test_config_returns_image_sizes(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'image_sizes.thumbnail.width', 150 )
            ->assertJsonPath( 'image_sizes.thumbnail.height', 150 )
            ->assertJsonPath( 'image_sizes.thumbnail.crop', true )
            ->assertJsonPath( 'image_sizes.medium.width', 300 )
            ->assertJsonPath( 'image_sizes.large.width', 1024 );
    }

    /**
     * Test that custom registered image sizes are included.
     */
    public function test_config_includes_custom_image_sizes(): void
    {
        $manager = app( MediaManager::class );
        $manager->registerImageSize( 'hero', 1920, 1080, false );

        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'image_sizes.hero.width', 1920 )
            ->assertJsonPath( 'image_sizes.hero.height', 1080 )
            ->assertJsonPath( 'image_sizes.hero.crop', false );
    }

    /**
     * Test that feature flags reflect WebP configuration.
     */
    public function test_config_returns_webp_feature_flags(): void
    {
        config( [
            'artisanpack.media.enable_modern_formats' => true,
            'artisanpack.media.modern_format'         => 'webp',
        ] );

        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'features.webp_conversion', true )
            ->assertJsonPath( 'features.avif_conversion', false );
    }

    /**
     * Test that feature flags reflect AVIF configuration.
     */
    public function test_config_returns_avif_feature_flags(): void
    {
        config( [
            'artisanpack.media.enable_modern_formats' => true,
            'artisanpack.media.modern_format'         => 'avif',
        ] );

        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'features.webp_conversion', false )
            ->assertJsonPath( 'features.avif_conversion', true );
    }

    /**
     * Test that feature flags are false when modern formats are disabled.
     */
    public function test_config_returns_false_features_when_modern_formats_disabled(): void
    {
        config( [
            'artisanpack.media.enable_modern_formats' => false,
        ] );

        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertJsonPath( 'features.webp_conversion', false )
            ->assertJsonPath( 'features.avif_conversion', false );
    }

    /**
     * Test that authenticated users can also access the config endpoint.
     */
    public function test_config_is_accessible_by_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs( $user )->getJson( '/api/media/config' );

        $response->assertOk();
    }

    /**
     * Test that the response includes cache headers.
     */
    public function test_config_response_includes_cache_headers(): void
    {
        $response = $this->getJson( '/api/media/config' );

        $response->assertOk()
            ->assertHeader( 'Cache-Control', 'max-age=3600, public, s-maxage=3600, stale-while-revalidate=86400' )
            ->assertHeader( 'ETag' );
    }

    /**
     * Test that unknown MIME types still produce extensions via Symfony fallback.
     */
    public function test_config_extracts_extensions_for_unknown_mime_types(): void
    {
        config( [
            'artisanpack.media.allowed_mime_types' => [
                'image/tiff',
            ],
        ] );

        $response = $this->getJson( '/api/media/config' );

        $extensions = $response->json( 'upload.allowed_extensions' );

        $this->assertContains( 'tiff', $extensions );
    }
}
