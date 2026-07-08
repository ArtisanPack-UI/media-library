<?php

declare( strict_types=1 );

namespace Tests\Feature\Ai\Http;

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use ReflectionObject;
use Tests\Support\FakeAgentPrompter;
use Tests\TestCase;

class MediaAiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected FakeAgentPrompter $prompter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        $this->user = User::factory()->create();

        Storage::fake( 'test-disk' );

        config( [
            'artisanpack.media.disk'       => 'test-disk',
            'artisanpack.media.user_model' => User::class,
        ] );

        Gate::before( fn ( $user, $ability ) => true );

        /** @var ChainedCredentialResolver $resolver */
        $resolver = app( CredentialResolver::class );
        $resolver->setOverride(
            new Credentials( provider: 'anthropic', apiKey: 'sk-test', defaultModel: 'claude-haiku-4-5' ),
        );
        $resolver->useStore( fn () => null );

        $this->prompter = new FakeAgentPrompter();
        $this->app->instance( AgentPrompter::class, $this->prompter );
    }

    public function test_alt_text_endpoint_returns_agent_output(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = $this->createImageMedia();

        $this->prompter->queue( [
            'alt_text'   => 'A wooden bench in a park',
            'confidence' => 0.9,
            'warnings'   => [],
        ] );

        $response = $this->postJson( "/api/media/{$media->id}/ai/alt-text" );

        $response->assertOk()
            ->assertJson( [
                'data' => [
                    'alt_text'   => 'A wooden bench in a park',
                    'confidence' => 0.9,
                ],
            ] );
    }

    public function test_suggest_tags_endpoint_uses_stored_taxonomy(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = $this->createImageMedia();

        MediaTag::create( [ 'name' => 'Nature', 'slug' => 'nature' ] );
        MediaTag::create( [ 'name' => 'Portrait', 'slug' => 'portrait' ] );

        $this->prompter->queue( [
            'tags'       => [ 'nature' ],
            'new_tags'   => [ 'sunset' ],
            'confidence' => 0.7,
        ] );

        $response = $this->postJson(
            "/api/media/{$media->id}/ai/tags",
            [ 'allow_new' => true ],
        );

        $response->assertOk();

        // Taxonomy sent to the prompter is the DB tag list, not caller-supplied.
        $text = $this->prompter->calls[0]['message'][0]['text'];
        $this->assertStringContainsString( '"existing_tags":["Nature","Portrait"]', $text );
        $this->assertStringContainsString( '"allow_new":true', $text );

        $data = $response->json( 'data' );
        $this->assertSame( [ 'Nature' ], $data['tags'] );
        $this->assertSame( [ 'sunset' ], $data['new_tags'] );
    }

    public function test_describe_endpoint_honors_length_param(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = $this->createImageMedia();

        $this->prompter->queue( [
            'description' => 'A quiet street after rain.',
            'confidence'  => 0.8,
            'warnings'    => [],
        ] );

        $response = $this->postJson(
            "/api/media/{$media->id}/ai/description",
            [ 'length' => 'long' ],
        );

        $response->assertOk();
        $this->assertStringContainsString( 'long', $this->prompter->calls[0]['message'][0]['text'] );
    }

    public function test_endpoint_rejects_non_image_media(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = Media::factory()->create( [
            'uploaded_by' => $this->user->id,
            'mime_type'   => 'video/mp4',
        ] );

        $this->postJson( "/api/media/{$media->id}/ai/alt-text" )
            ->assertStatus( 422 );
    }

    public function test_endpoint_returns_403_when_feature_key_is_unregistered(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = $this->createImageMedia();

        // Simulate a fresh install where an operator has not registered
        // the feature — the endpoint must refuse rather than silently
        // running the agent, matching the Livewire trait's semantics.
        /** @var FeatureRegistry $registry */
        $registry   = app( FeatureRegistry::class );
        $reflection = new ReflectionObject( $registry );
        $property   = $reflection->getProperty( 'features' );
        $property->setAccessible( true );
        $features = $property->getValue( $registry );
        unset( $features['media.suggest_tags'] );
        $property->setValue( $registry, $features );

        $this->postJson( "/api/media/{$media->id}/ai/tags" )
            ->assertStatus( 403 );
    }

    public function test_suggest_tags_endpoint_forwards_the_media_file_name(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = Media::factory()->create( [
            'uploaded_by' => $this->user->id,
            'mime_type'   => 'image/jpeg',
            'file_name'   => 'red-sunset.jpg',
        ] );

        Storage::disk( $media->disk )->put( $media->file_path, 'fake-bytes' );

        $this->prompter->queue( [
            'tags'       => [],
            'new_tags'   => [],
            'confidence' => 0.5,
        ] );

        $this->postJson( "/api/media/{$media->id}/ai/tags" )->assertOk();

        $text = $this->prompter->calls[0]['message'][0]['text'];
        $this->assertStringContainsString( '"filename":"red-sunset.jpg"', $text );
    }

    public function test_endpoint_returns_403_when_feature_disabled(): void
    {
        Sanctum::actingAs( $this->user, [ '*' ] );

        $media = $this->createImageMedia();

        /** @var FeatureRegistry $registry */
        $registry = app( FeatureRegistry::class );
        $registry->disable( 'media.suggest_tags' );

        $this->postJson( "/api/media/{$media->id}/ai/tags" )
            ->assertStatus( 403 );
    }

    public function test_endpoint_requires_authentication(): void
    {
        $media = Media::factory()->create( [ 'mime_type' => 'image/jpeg' ] );

        $this->postJson( "/api/media/{$media->id}/ai/alt-text" )
            ->assertStatus( 401 );
    }

    protected function createImageMedia(): Media
    {
        $media = Media::factory()->create( [
            'uploaded_by' => $this->user->id,
            'mime_type'   => 'image/jpeg',
        ] );

        // The controller falls back to a filesystem path when the disk URL
        // is not http(s). Put a real file at that path so the agent's
        // readability check passes.
        Storage::disk( $media->disk )->put( $media->file_path, 'fake-image-bytes' );

        return $media;
    }
}
