<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageTagSuggestionAgent;
use Tests\Support\FakeAgentPrompter;

beforeEach( function (): void {
    /** @var ChainedCredentialResolver $resolver */
    $resolver = app( CredentialResolver::class );
    $resolver->setOverride(
        new Credentials( provider: 'anthropic', apiKey: 'sk-test', defaultModel: 'claude-haiku-4-5' ),
    );
    $resolver->useStore( fn () => null );

    $this->prompter = new FakeAgentPrompter();
    $this->app->instance( AgentPrompter::class, $this->prompter );
} );

it( 'filters model tags down to the caller-supplied taxonomy', function (): void {
    $this->prompter->queue( [
        'tags'       => [ 'landscape', 'nature', 'FABRICATED' ],
        'new_tags'   => [],
        'confidence' => 0.9,
    ] );

    $result = ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/mountain.jpg',
        'existing_tags' => [ 'Landscape', 'Nature', 'Portrait' ],
    ] )->run();

    expect( $result['tags'] )
        ->toEqualCanonicalizing( [ 'Landscape', 'Nature' ] );
    expect( $result['new_tags'] )->toBe( [] );
} );

it( 'ignores new_tags when allow_new is false', function (): void {
    $this->prompter->queue( [
        'tags'       => [ 'nature' ],
        'new_tags'   => [ 'macro-photography' ],
        'confidence' => 0.7,
    ] );

    $result = ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/x.jpg',
        'existing_tags' => [ 'Nature' ],
        'allow_new'     => false,
    ] )->run();

    expect( $result['new_tags'] )->toBe( [] );
} );

it( 'accepts new_tags when allow_new is true and dedupes against taxonomy', function (): void {
    $this->prompter->queue( [
        'tags'       => [ 'nature' ],
        // Second entry is a re-suggestion of an existing taxonomy tag
        // under `new_tags` — must be dropped.
        'new_tags'   => [ 'macro-photography', 'Nature' ],
        'confidence' => 0.6,
    ] );

    $result = ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/x.jpg',
        'existing_tags' => [ 'Nature' ],
        'allow_new'     => true,
    ] )->run();

    expect( $result['tags'] )->toBe( [ 'Nature' ] );
    expect( $result['new_tags'] )->toBe( [ 'macro-photography' ] );
} );

it( 'clamps confidence into [0, 1]', function (): void {
    $this->prompter->queue( [
        'tags'       => [],
        'new_tags'   => [],
        'confidence' => 3.5,
    ] );

    $result = ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/x.jpg',
        'existing_tags' => [],
    ] )->run();

    expect( $result['confidence'] )->toBe( 1.0 );
} );

it( 'rejects non-array input', function (): void {
    expect( fn () => ImageTagSuggestionAgent::for( null )->run() )
        ->toThrow( FeatureError::class, 'array with an `image` key' );
} );

it( 'rejects input missing the image key', function (): void {
    expect( fn () => ImageTagSuggestionAgent::for( [ 'existing_tags' => [] ] )->run() )
        ->toThrow( FeatureError::class, '`image` is required' );
} );

it( 'includes context (filename, folder, allow_new, existing_tags) in the message', function (): void {
    $this->prompter->queue( [
        'tags'       => [],
        'new_tags'   => [],
        'confidence' => 0.5,
    ] );

    ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/x.jpg',
        'existing_tags' => [ 'A', 'B' ],
        'filename'      => 'sunset.jpg',
        'folder'        => 'Nature',
        'allow_new'     => true,
    ] )->run();

    $textPart = $this->prompter->calls[0]['message'][0]['text'];
    expect( $textPart )->toContain( '"filename":"sunset.jpg"' );
    expect( $textPart )->toContain( '"folder":"Nature"' );
    expect( $textPart )->toContain( '"allow_new":true' );
    expect( $textPart )->toContain( '"existing_tags":["A","B"]' );
} );

it( 'caps output to 8 tags per bucket', function (): void {
    $this->prompter->queue( [
        'tags'       => array_map( fn ( int $i ): string => "tag{$i}", range( 1, 20 ) ),
        'new_tags'   => array_map( fn ( int $i ): string => "new{$i}", range( 1, 20 ) ),
        'confidence' => 0.5,
    ] );

    $result = ImageTagSuggestionAgent::for( [
        'image'         => 'https://example.com/x.jpg',
        'existing_tags' => array_map( fn ( int $i ): string => "tag{$i}", range( 1, 20 ) ),
        'allow_new'     => true,
    ] )->run();

    expect( count( $result['tags'] ) )->toBe( 8 );
    expect( count( $result['new_tags'] ) )->toBe( 8 );
} );
