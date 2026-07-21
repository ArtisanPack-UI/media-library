<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaEdit;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\FakeAgentPrompter;

/**
 * Integration tests that prove the `ap.mediaLibrary.altTextSuggestion`
 * filter is actually invoked at both fire sites — the MediaEdit Livewire
 * component and the MediaAiController JSON endpoint. Removing either
 * fire site MUST fail one of these tests; the direct-invocation cases
 * live in MediaLibraryHooksTest and only exercise the filter contract.
 *
 * @since 1.4.0
 */

beforeEach( function (): void {
    if ( ! function_exists( 'addFilter' ) ) {
        $this->markTestSkipped( 'Hook system not available' );
    }

    removeAllFilters( 'ap.mediaLibrary.altTextSuggestion' );

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

    $this->user  = User::factory()->create();
    $this->media = Media::factory()->create( [
        'uploaded_by' => $this->user->id,
        'mime_type'   => 'image/jpeg',
    ] );

    Storage::disk( $this->media->disk )->put( $this->media->file_path, 'fake-image-bytes' );
} );

afterEach( function (): void {
    removeAllFilters( 'ap.mediaLibrary.altTextSuggestion' );
} );

it( 'invokes the altTextSuggestion filter from MediaEdit::suggestAltText', function (): void {
    addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
        return 'FILTERED: ' . $suggestion;
    } );

    $this->prompter->queue( [
        'alt_text'   => 'a wooden bench',
        'confidence' => 0.9,
        'warnings'   => [],
    ] );

    Livewire::actingAs( $this->user )
        ->test( MediaEdit::class, [ 'mediaId' => $this->media->id ] )
        ->call( 'suggestAltText' )
        ->assertSet( 'form.alt_text', 'FILTERED: a wooden bench' )
        ->assertSet( 'altTextIsAiSuggested', true );
} );

it( 'lets a null return from the filter skip the MediaEdit suggestion entirely', function (): void {
    addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
        return null;
    } );

    $this->prompter->queue( [
        'alt_text'   => 'a wooden bench',
        'confidence' => 0.9,
        'warnings'   => [],
    ] );

    Livewire::actingAs( $this->user )
        ->test( MediaEdit::class, [ 'mediaId' => $this->media->id ] )
        ->call( 'suggestAltText' )
        // form.alt_text was pre-populated by mount(); the filter returning
        // null must not overwrite it with an empty string and must not
        // flip the AI-suggested flag.
        ->assertSet( 'form.alt_text', $this->media->alt_text ?? '' )
        ->assertSet( 'altTextIsAiSuggested', false );
} );

it( 'invokes the altTextSuggestion filter from the MediaAiController JSON endpoint', function (): void {
    addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
        return 'FILTERED: ' . $suggestion;
    } );

    $this->prompter->queue( [
        'alt_text'   => 'a wooden bench',
        'confidence' => 0.9,
        'warnings'   => [],
    ] );

    Sanctum::actingAs( $this->user, [ '*' ] );

    $response = $this->postJson( "/api/media/{$this->media->id}/ai/alt-text" );

    $response->assertOk()
        ->assertJson( [
            'data' => [
                'alt_text' => 'FILTERED: a wooden bench',
            ],
        ] );
} );

it( 'lets a null return from the filter null out the JSON endpoint alt_text', function (): void {
    addFilter( 'ap.mediaLibrary.altTextSuggestion', function ( ?string $suggestion, Media $media ): ?string {
        return null;
    } );

    $this->prompter->queue( [
        'alt_text'   => 'a wooden bench',
        'confidence' => 0.9,
        'warnings'   => [],
    ] );

    Sanctum::actingAs( $this->user, [ '*' ] );

    $response = $this->postJson( "/api/media/{$this->media->id}/ai/alt-text" );

    $response->assertOk();
    expect( $response->json( 'data.alt_text' ) )->toBeNull();
} );
