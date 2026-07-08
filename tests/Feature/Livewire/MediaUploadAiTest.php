<?php

declare( strict_types=1 );

namespace Tests\Feature\Livewire;

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaUpload;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeAgentPrompter;
use Tests\TestCase;

/**
 * Upload-time AI action tests for the MediaUpload Livewire component.
 *
 * @since 1.3.0
 */
class MediaUploadAiTest extends TestCase
{
    protected User $user;

    protected FakeAgentPrompter $prompter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake( 'public' );

        $this->user = User::factory()->create();

        config( [
            'artisanpack.media.disk'               => 'public',
            'artisanpack.media.user_model'         => User::class,
            'artisanpack.media.allowed_mime_types' => [ 'image/jpeg', 'image/png' ],
            'artisanpack.media.max_file_size'      => 10240,
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

    public function test_suggest_alt_text_populates_metadata_and_marks_ai(): void
    {
        $this->prompter->queue( [
            'alt_text'   => 'A sunlit meadow',
            'confidence' => 0.9,
            'warnings'   => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaUpload::class )
            ->set( 'files', [ UploadedFile::fake()->image( 'photo.jpg' ) ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'metadata.alt_text', 'A sunlit meadow' )
            ->assertSet( 'altTextIsAiSuggested', true );
    }

    public function test_suggest_alt_text_errors_when_no_image_queued(): void
    {
        Livewire::actingAs( $this->user )
            ->test( MediaUpload::class )
            ->call( 'suggestAltText' )
            ->assertSet( 'metadata.alt_text', '' );

        $this->assertSame( [], $this->prompter->calls );
    }

    public function test_suggest_description_uses_selected_length(): void
    {
        $this->prompter->queue( [
            'description' => 'A wide meadow beneath a summer sky.',
            'confidence'  => 0.8,
            'warnings'    => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaUpload::class )
            ->set( 'droppedFiles', [ UploadedFile::fake()->image( 'photo.png' ) ] )
            ->set( 'aiDescriptionLength', 'long' )
            ->call( 'suggestDescription' )
            ->assertSet( 'metadata.description', 'A wide meadow beneath a summer sky.' )
            ->assertSet( 'descriptionIsAiSuggested', true );

        $this->assertStringContainsString( 'long', $this->prompter->calls[0]['message'][0]['text'] );
    }

    public function test_suggest_alt_text_falls_back_to_filename_when_agent_returns_empty(): void
    {
        $this->prompter->queue( [
            'alt_text'   => '',
            'confidence' => 0.1,
            'warnings'   => [ 'decorative image' ],
        ] );

        $component = Livewire::actingAs( $this->user )
            ->test( MediaUpload::class )
            ->set( 'files', [ UploadedFile::fake()->image( 'company-banner.jpg' ) ] )
            ->call( 'suggestAltText' );

        $component->assertSet( 'metadata.alt_text', 'Company banner' );
        $component->assertSet( 'altTextIsAiSuggested', true );
        $this->assertNotSame( '', $component->get( 'altTextAiNote' ) );
    }

    public function test_suggest_alt_text_no_ops_when_feature_disabled(): void
    {
        /** @var FeatureRegistry $registry */
        $registry = app( FeatureRegistry::class );
        $registry->disable( 'ai.alt_text' );

        Livewire::actingAs( $this->user )
            ->test( MediaUpload::class )
            ->set( 'files', [ UploadedFile::fake()->image( 'photo.jpg' ) ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'metadata.alt_text', '' );

        $this->assertSame( [], $this->prompter->calls );
    }
}
