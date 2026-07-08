<?php

declare( strict_types=1 );

namespace Tests\Feature\Livewire;

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Contracts\FeatureRegistry;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaEdit;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeAgentPrompter;
use Tests\TestCase;

/**
 * MediaEdit AI action tests.
 *
 * Covers the AI-suggest actions added to the MediaEdit Livewire
 * component in release/1.3.
 *
 * @since 1.3.0
 */
class MediaEditAiTest extends TestCase
{
    protected User $user;

    protected FakeAgentPrompter $prompter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake( 'public' );

        $this->user = User::factory()->create();

        config( [
            'artisanpack.media.disk'       => 'public',
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

    public function test_suggest_alt_text_populates_form_and_marks_ai_suggested(): void
    {
        $media = $this->makeMedia();

        $this->prompter->queue( [
            'alt_text'   => 'A red barn at sunset',
            'confidence' => 0.9,
            'warnings'   => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'form.alt_text', 'A red barn at sunset' )
            ->assertSet( 'altTextIsAiSuggested', true );
    }

    public function test_editing_alt_text_clears_ai_flag(): void
    {
        $media = $this->makeMedia();

        $this->prompter->queue( [
            'alt_text'   => 'AI suggestion',
            'confidence' => 0.9,
            'warnings'   => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'altTextIsAiSuggested', true )
            // Simulates the client-side $wire.set('form.alt_text', ...)
            // echo that suggestAltText queues via $this->js(). This
            // second round-trip must NOT clear the AI badge (it's the
            // agent's own value coming back), so the suppression flag
            // consumes here.
            ->set( 'form.alt_text', 'AI suggestion' )
            ->assertSet( 'altTextIsAiSuggested', true )
            // Now a real user edit — the suppression flag is spent, so
            // the property-updated hook clears the badge.
            ->set( 'form.alt_text', 'User edit' )
            ->assertSet( 'altTextIsAiSuggested', false );
    }

    public function test_suggest_tags_selects_matching_taxonomy_entries(): void
    {
        $media   = $this->makeMedia();
        $nature  = MediaTag::create( [ 'name' => 'Nature', 'slug' => 'nature' ] );
        $barn    = MediaTag::create( [ 'name' => 'Barn', 'slug' => 'barn' ] );
        $unused  = MediaTag::create( [ 'name' => 'Portrait', 'slug' => 'portrait' ] );

        $this->prompter->queue( [
            'tags'       => [ 'nature', 'barn' ],
            'new_tags'   => [],
            'confidence' => 0.9,
        ] );

        $component = Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestTags', false );

        $selected = $component->get( 'selectedTags' );

        $this->assertContains( $nature->id, $selected );
        $this->assertContains( $barn->id, $selected );
        $this->assertNotContains( $unused->id, $selected );
    }

    public function test_suggest_tags_with_allow_new_creates_missing_tags(): void
    {
        $media = $this->makeMedia();
        MediaTag::create( [ 'name' => 'Nature', 'slug' => 'nature' ] );

        $this->prompter->queue( [
            'tags'       => [ 'nature' ],
            'new_tags'   => [ 'sunset' ],
            'confidence' => 0.8,
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestTags', true );

        $this->assertDatabaseHas( 'media_tags', [ 'name' => 'sunset' ] );
    }

    public function test_suggest_description_populates_and_marks_ai(): void
    {
        $media = $this->makeMedia();

        $this->prompter->queue( [
            'description' => 'Rolling hills under a violet sky.',
            'confidence'  => 0.9,
            'warnings'    => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->set( 'aiDescriptionLength', 'long' )
            ->call( 'suggestDescription' )
            ->assertSet( 'form.description', 'Rolling hills under a violet sky.' )
            ->assertSet( 'descriptionIsAiSuggested', true );
    }

    public function test_suggest_alt_text_falls_back_to_filename_when_agent_returns_empty(): void
    {
        $media = Media::factory()->uploadedBy( $this->user )->create( [
            'mime_type' => 'image/jpeg',
            'file_name' => 'sunset-over-hills.jpg',
        ] );

        Storage::disk( $media->disk )->put( $media->file_path, 'fake-bytes' );

        $this->prompter->queue( [
            'alt_text'   => '',
            'confidence' => 0.1,
            'warnings'   => [ 'decorative image' ],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'form.alt_text', 'Sunset over hills' )
            ->assertSet( 'altTextIsAiSuggested', true );
    }

    public function test_fallback_uses_title_verbatim_when_no_filename(): void
    {
        $media = Media::factory()->uploadedBy( $this->user )->create( [
            'mime_type' => 'image/jpeg',
            // Deliberately no file_name so the fallback lands on title.
            'file_name' => '',
            'title'     => 'Roadmap v1.0',
        ] );

        Storage::disk( $media->disk )->put( $media->file_path, 'fake-bytes' );

        $this->prompter->queue( [
            'alt_text'   => '',
            'confidence' => 0.1,
            'warnings'   => [],
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestAltText' )
            // Title must NOT be run through pathinfo — `Roadmap v1.0`
            // must survive intact, not become `Roadmap v1`.
            ->assertSet( 'form.alt_text', 'Roadmap v1.0' );
    }

    public function test_suggest_tags_forwards_the_media_file_name_as_the_filename_hint(): void
    {
        $media = Media::factory()->uploadedBy( $this->user )->create( [
            'mime_type' => 'image/jpeg',
            'file_name' => 'red-sunset.jpg',
        ] );

        Storage::disk( $media->disk )->put( $media->file_path, 'fake-bytes' );

        $this->prompter->queue( [
            'tags'       => [],
            'new_tags'   => [],
            'confidence' => 0.5,
        ] );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestTags', false );

        $text = $this->prompter->calls[0]['message'][0]['text'];
        $this->assertStringContainsString( '"filename":"red-sunset.jpg"', $text );
    }

    public function test_suggest_alt_text_no_ops_when_feature_disabled(): void
    {
        $media = $this->makeMedia();

        /** @var FeatureRegistry $registry */
        $registry = app( FeatureRegistry::class );
        $registry->disable( 'ai.alt_text' );

        Livewire::actingAs( $this->user )
            ->test( MediaEdit::class, [ 'mediaId' => $media->id ] )
            ->call( 'suggestAltText' )
            ->assertSet( 'form.alt_text', $media->alt_text ?? '' );

        // Prompter should never have been called.
        $this->assertSame( [], $this->prompter->calls );
    }

    protected function makeMedia(): Media
    {
        $media = Media::factory()->uploadedBy( $this->user )->create( [
            'mime_type' => 'image/jpeg',
        ] );

        Storage::disk( $media->disk )->put( $media->file_path, 'fake-bytes' );

        return $media;
    }
}
