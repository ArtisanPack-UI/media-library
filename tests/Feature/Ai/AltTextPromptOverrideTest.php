<?php

declare( strict_types=1 );

namespace Tests\Feature\Ai;

use ArtisanPackUI\Ai\Agents\AltTextGenerationAgent;
use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Contracts\CredentialResolver;
use ArtisanPackUI\Ai\Credentials\ChainedCredentialResolver;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ReflectionMethod;
use Tests\Support\FakeAgentPrompter;
use Tests\TestCase;

/**
 * Regression coverage for the media-library service provider's
 * override of the `ai.alt_text` prompt.
 *
 * The shipped ai package prompt permits an empty alt_text for images
 * the model classifies as "decorative" — undesirable in an asset
 * manager. Media library injects a stricter default via config.
 *
 * @since 1.3.0
 */
class AltTextPromptOverrideTest extends TestCase
{
    protected FakeAgentPrompter $prompter;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ChainedCredentialResolver $resolver */
        $resolver = app( CredentialResolver::class );
        $resolver->setOverride(
            new Credentials( provider: 'anthropic', apiKey: 'sk-test', defaultModel: 'claude-haiku-4-5' ),
        );
        $resolver->useStore( fn () => null );

        $this->prompter = new FakeAgentPrompter();
        $this->app->instance( AgentPrompter::class, $this->prompter );
    }

    public function test_service_provider_registers_stricter_alt_text_prompt(): void
    {
        $features = config( 'artisanpack.ai.features', [] );

        $this->assertArrayHasKey( 'ai.alt_text', $features );
        $this->assertArrayHasKey( 'instructions', $features['ai.alt_text'] );
        $this->assertStringContainsString(
            'ALWAYS return a non-empty',
            $features['ai.alt_text']['instructions'],
        );
    }

    public function test_alt_text_agent_uses_the_media_library_override(): void
    {
        $this->prompter->queue( [
            'alt_text'   => 'A pale banner with a company logo',
            'confidence' => 0.9,
            'warnings'   => [],
        ] );

        AltTextGenerationAgent::for( 'https://example.com/banner.jpg' )->run();

        $this->assertStringContainsString(
            'ALWAYS return a non-empty',
            $this->prompter->calls[0]['instructions'],
        );
    }

    public function test_override_defers_to_pre_existing_instructions(): void
    {
        // Simulate an operator having already customised the prompt via
        // the ai settings admin surface / a published config file.
        $features                                = config( 'artisanpack.ai.features', [] );
        $features['ai.alt_text']                 = ( $features['ai.alt_text'] ?? [] );
        $features['ai.alt_text']['instructions'] = 'Custom operator prompt for alt text';
        config( [ 'artisanpack.ai.features' => $features ] );

        // Force the provider's boot() logic to re-run without wiping
        // the value we just set — reflect and call the protected method.
        $provider   = app()->getProvider( \ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider::class );
        $reflection = new ReflectionMethod( $provider, 'overrideAiInstructions' );
        $reflection->setAccessible( true );
        $reflection->invoke( $provider );

        $this->assertSame(
            'Custom operator prompt for alt text',
            config( 'artisanpack.ai.features' )['ai.alt_text']['instructions'],
        );
    }
}
