<?php

/**
 * Install Frontend Command
 *
 * Artisan command that publishes React or Vue media library components
 * and provides npm peer dependency installation instructions.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Console\Commands;

use Illuminate\Console\Command;

/**
 * Artisan command to install frontend media library assets.
 *
 * Publishes the appropriate React or Vue components to the consuming
 * application and displays the required npm peer dependencies.
 *
 * @since 1.2.0
 */
class InstallFrontendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $signature = 'media:install-frontend
        {--stack= : The frontend stack to install (react or vue)}
        {--force : Overwrite existing published files}';

    /**
     * The console command description.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $description = 'Publish media library frontend components and display required npm dependencies';

    /**
     * Peer dependencies required for React media components.
     *
     * @since 1.2.0
     *
     * @var array<string, string>
     */
    protected static array $reactDependencies = [
        'react'     => '^18.0 || ^19.0',
        'react-dom' => '^18.0 || ^19.0',
    ];

    /**
     * Peer dependencies required for Vue media components.
     *
     * @since 1.2.0
     *
     * @var array<string, string>
     */
    protected static array $vueDependencies = [
        'vue' => '^3.4',
    ];

    /**
     * Shared TypeScript dev dependency.
     *
     * @since 1.2.0
     *
     * @var array<string, string>
     */
    protected static array $sharedDevDependencies = [
        'typescript' => '^5.0',
    ];

    /**
     * Execute the console command.
     *
     * @since 1.2.0
     *
     * @return int Command exit code.
     */
    public function handle(): int
    {
        $stack = $this->option( 'stack' );

        if ( empty( $stack ) ) {
            $stack = $this->choice(
                __( 'Which frontend stack would you like to install?' ),
                ['react', 'vue'],
                0,
            );
        }

        $stack = strtolower( (string) $stack );

        if ( ! in_array( $stack, ['react', 'vue'], true ) ) {
            $this->error( __( 'Invalid stack ":stack". Supported stacks: react, vue.', ['stack' => $stack] ) );

            return self::FAILURE;
        }

        return $this->installStack( $stack );
    }

    /**
     * Get the React peer dependencies.
     *
     * @since 1.2.0
     *
     * @return array<string, string>
     */
    public static function getReactDependencies(): array
    {
        return static::$reactDependencies;
    }

    /**
     * Get the Vue peer dependencies.
     *
     * @since 1.2.0
     *
     * @return array<string, string>
     */
    public static function getVueDependencies(): array
    {
        return static::$vueDependencies;
    }

    /**
     * Get the shared dev dependencies.
     *
     * @since 1.2.0
     *
     * @return array<string, string>
     */
    public static function getSharedDevDependencies(): array
    {
        return static::$sharedDevDependencies;
    }

    /**
     * Install the specified frontend stack.
     *
     * @since 1.2.0
     *
     * @param  string  $stack  The stack to install (react or vue).
     *
     * @return int Command exit code.
     */
    protected function installStack( string $stack ): int
    {
        $this->info( __( 'Installing :stack media library components...', ['stack' => ucfirst( $stack )] ) );
        $this->newLine();

        // Publish components
        $assetsExitCode = $this->publishAssets( $stack );
        if ( 0 !== $assetsExitCode ) {
            $this->error( __( 'Failed to publish :stack components.', ['stack' => $stack] ) );

            return self::FAILURE;
        }

        // Publish shared type definitions
        $typesExitCode = $this->publishTypes();
        if ( 0 !== $typesExitCode ) {
            $this->error( __( 'Failed to publish type definitions.' ) );

            return self::FAILURE;
        }

        // Display peer dependencies
        $this->displayDependencies( $stack );

        $this->newLine();
        $this->info( __( ':stack media library components installed successfully!', ['stack' => ucfirst( $stack )] ) );

        return self::SUCCESS;
    }

    /**
     * Publish the frontend assets for the given stack.
     *
     * @since 1.2.0
     *
     * @param  string  $stack  The stack to publish (react or vue).
     *
     * @return int Exit code from vendor:publish.
     */
    protected function publishAssets( string $stack ): int
    {
        $tag    = 'media-' . $stack;
        $params = [
            '--tag'      => $tag,
            '--provider' => 'ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider',
        ];

        if ( $this->option( 'force' ) ) {
            $params['--force'] = true;
        }

        return $this->call( 'vendor:publish', $params );
    }

    /**
     * Publish the shared TypeScript type definitions.
     *
     * @since 1.2.0
     *
     * @return int Exit code from vendor:publish.
     */
    protected function publishTypes(): int
    {
        $params = [
            '--tag'      => 'media-types',
            '--provider' => 'ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider',
        ];

        if ( $this->option( 'force' ) ) {
            $params['--force'] = true;
        }

        return $this->call( 'vendor:publish', $params );
    }

    /**
     * Display the npm peer dependencies for the given stack.
     *
     * @since 1.2.0
     *
     * @param  string  $stack  The stack (react or vue).
     */
    protected function displayDependencies( string $stack ): void
    {
        $dependencies    = 'react' === $stack ? static::$reactDependencies : static::$vueDependencies;
        $devDependencies = static::$sharedDevDependencies;

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=green;options=bold>' . __( 'Required Peer Dependencies' ) . '</>',
            '<fg=green;options=bold>' . __( 'Version' ) . '</>',
        );

        foreach ( $dependencies as $package => $version ) {
            $this->components->twoColumnDetail( $package, $version );
        }

        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=yellow;options=bold>' . __( 'Recommended Dev Dependencies' ) . '</>',
            '<fg=yellow;options=bold>' . __( 'Version' ) . '</>',
        );

        foreach ( $devDependencies as $package => $version ) {
            $this->components->twoColumnDetail( $package, $version );
        }

        $this->newLine();
        $this->info( __( 'Install with:' ) );

        $installParts = [];
        foreach ( $dependencies as $package => $version ) {
            $installParts[] = $package . '@"' . $version . '"';
        }
        $this->line( '  npm install ' . implode( ' ', $installParts ) );

        $devInstallParts = [];
        foreach ( $devDependencies as $package => $version ) {
            $devInstallParts[] = $package . '@"' . $version . '"';
        }
        $this->line( '  npm install -D ' . implode( ' ', $devInstallParts ) );
    }
}
