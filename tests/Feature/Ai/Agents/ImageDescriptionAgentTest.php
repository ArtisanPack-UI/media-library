<?php

declare( strict_types=1 );

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent;
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

it( 'returns a description from a plain image URL', function (): void {
    $this->prompter->queue( [
        'description' => 'A sunlit ridge under a clearing storm.',
        'confidence'  => 0.85,
        'warnings'    => [],
    ] );

    $result = ImageDescriptionAgent::for( 'https://example.com/mountain.jpg' )->run();

    expect( $result['description'] )->toBe( 'A sunlit ridge under a clearing storm.' );
    expect( $result['confidence'] )->toBe( 0.85 );
    expect( $result['warnings'] )->toBe( [] );

    // Bare-string input defaults to medium length.
    expect( $this->prompter->calls[0]['message'][0]['text'] )
        ->toContain( 'medium' );
} );

it( 'passes the requested length to the model prompt', function (): void {
    $this->prompter->queue( [
        'description' => 'x',
        'confidence'  => 1.0,
        'warnings'    => [],
    ] );

    ImageDescriptionAgent::for( [
        'image'  => 'https://example.com/x.jpg',
        'length' => 'long',
    ] )->run();

    expect( $this->prompter->calls[0]['message'][0]['text'] )
        ->toContain( 'long' );
} );

it( 'rejects an unknown length tier', function (): void {
    expect( fn () => ImageDescriptionAgent::for( [
        'image'  => 'https://example.com/x.jpg',
        'length' => 'novella',
    ] )->run() )->toThrow( FeatureError::class, 'short, medium, long' );
} );

it( 'caps description length by requested tier', function (): void {
    $this->prompter->queue( [
        'description' => str_repeat( 'x', 5000 ),
        'confidence'  => 0.5,
        'warnings'    => [],
    ] );

    $result = ImageDescriptionAgent::for( [
        'image'  => 'https://example.com/x.jpg',
        'length' => 'short',
    ] )->run();

    // "short" caps at 200 chars.
    expect( mb_strlen( $result['description'] ) )->toBe( 200 );
} );

it( 'clamps confidence into [0, 1] and drops non-string warnings', function (): void {
    $this->prompter->queue( [
        'description' => 'A river.',
        'confidence'  => -0.5,
        'warnings'    => [ 'blurry', 123, '', 'low light' ],
    ] );

    $result = ImageDescriptionAgent::for( 'https://example.com/x.jpg' )->run();

    expect( $result['confidence'] )->toBe( 0.0 );
    expect( $result['warnings'] )->toBe( [ 'blurry', 'low light' ] );
} );

it( 'rejects non-image input', function (): void {
    expect( fn () => ImageDescriptionAgent::for( [ 'image' => null ] )->run() )
        ->toThrow( FeatureError::class );
} );
