<?php

/**
 * Install Frontend Command Tests
 *
 * Tests for the media:install-frontend Artisan command, ensuring it
 * publishes the correct assets and displays dependency information
 * for both React and Vue stacks.
 *
 * @since   1.2.0
 */

declare( strict_types=1 );

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Console\Commands\InstallFrontendCommand;
use Tests\TestCase;

/**
 * Install Frontend Command Test Class
 */
class InstallFrontendCommandTest extends TestCase
{
    // =========================================================================
    // Command Registration Tests
    // =========================================================================

    /**
     * Test the command is registered.
     */
    public function test_command_is_registered(): void
    {
        $this->artisan( 'list' )
            ->assertSuccessful();

        $commands = \Illuminate\Support\Facades\Artisan::all();

        expect( $commands )->toHaveKey( 'media:install-frontend' );
    }

    // =========================================================================
    // React Stack Tests
    // =========================================================================

    /**
     * Test the command succeeds with react stack option.
     */
    public function test_command_succeeds_with_react_stack(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react'] )
            ->assertSuccessful();
    }

    /**
     * Test the command outputs react installation message.
     */
    public function test_command_outputs_react_installation_message(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react'] )
            ->expectsOutputToContain( 'Installing React media library components' )
            ->expectsOutputToContain( 'React media library components installed successfully' )
            ->assertSuccessful();
    }

    /**
     * Test the command displays react peer dependency info.
     */
    public function test_command_displays_react_peer_dependencies(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react'] )
            ->expectsOutputToContain( 'Required Peer Dependencies' )
            ->assertSuccessful();
    }

    /**
     * Test the command displays npm install instructions for react.
     */
    public function test_command_displays_npm_install_for_react(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react'] )
            ->expectsOutputToContain( 'npm install react@"^18.0 || ^19.0" react-dom@"^18.0 || ^19.0"' )
            ->assertSuccessful();
    }

    // =========================================================================
    // Vue Stack Tests
    // =========================================================================

    /**
     * Test the command succeeds with vue stack option.
     */
    public function test_command_succeeds_with_vue_stack(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'vue'] )
            ->assertSuccessful();
    }

    /**
     * Test the command outputs vue installation message.
     */
    public function test_command_outputs_vue_installation_message(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'vue'] )
            ->expectsOutputToContain( 'Installing Vue media library components' )
            ->expectsOutputToContain( 'Vue media library components installed successfully' )
            ->assertSuccessful();
    }

    /**
     * Test the command displays vue peer dependencies.
     */
    public function test_command_displays_vue_peer_dependencies(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'vue'] )
            ->expectsOutputToContain( 'vue' )
            ->assertSuccessful();
    }

    /**
     * Test the command displays npm install instructions for vue.
     */
    public function test_command_displays_npm_install_for_vue(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'vue'] )
            ->expectsOutputToContain( 'npm install vue@"^3.4"' )
            ->assertSuccessful();
    }

    // =========================================================================
    // Invalid Stack Tests
    // =========================================================================

    /**
     * Test the command fails with an invalid stack.
     */
    public function test_command_fails_with_invalid_stack(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'angular'] )
            ->expectsOutputToContain( 'Invalid stack' )
            ->assertFailed();
    }

    // =========================================================================
    // Interactive Prompt Tests
    // =========================================================================

    /**
     * Test the command prompts for stack when not provided.
     */
    public function test_command_prompts_for_stack_when_not_provided(): void
    {
        $this->artisan( 'media:install-frontend' )
            ->expectsChoice( 'Which frontend stack would you like to install?', 'react', ['react', 'vue'] )
            ->assertSuccessful();
    }

    // =========================================================================
    // Dev Dependencies Tests
    // =========================================================================

    /**
     * Test the command displays recommended dev dependencies.
     */
    public function test_command_displays_recommended_dev_dependencies(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react'] )
            ->expectsOutputToContain( 'Recommended Dev Dependencies' )
            ->assertSuccessful();
    }

    // =========================================================================
    // Static Accessor Tests
    // =========================================================================

    /**
     * Test getReactDependencies returns expected packages.
     */
    public function test_get_react_dependencies_returns_expected_packages(): void
    {
        $deps = InstallFrontendCommand::getReactDependencies();

        expect( $deps )->toHaveKey( 'react' );
        expect( $deps )->toHaveKey( 'react-dom' );
    }

    /**
     * Test getVueDependencies returns expected packages.
     */
    public function test_get_vue_dependencies_returns_expected_packages(): void
    {
        $deps = InstallFrontendCommand::getVueDependencies();

        expect( $deps )->toHaveKey( 'vue' );
    }

    /**
     * Test getSharedDevDependencies returns expected packages.
     */
    public function test_get_shared_dev_dependencies_returns_expected_packages(): void
    {
        $deps = InstallFrontendCommand::getSharedDevDependencies();

        expect( $deps )->toHaveKey( 'typescript' );
    }

    // =========================================================================
    // Force Option Tests
    // =========================================================================

    /**
     * Test the command accepts the force option.
     */
    public function test_command_accepts_force_option(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'react', '--force' => true] )
            ->assertSuccessful();
    }

    // =========================================================================
    // Case Insensitivity Tests
    // =========================================================================

    /**
     * Test the command handles uppercase stack values.
     */
    public function test_command_handles_uppercase_stack(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'REACT'] )
            ->assertSuccessful();
    }

    /**
     * Test the command handles mixed case stack values.
     */
    public function test_command_handles_mixed_case_stack(): void
    {
        $this->artisan( 'media:install-frontend', ['--stack' => 'Vue'] )
            ->assertSuccessful();
    }
}
