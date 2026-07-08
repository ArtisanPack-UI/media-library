<?php

/**
 * Media Library Service Provider
 *
 * Bootstraps the Media Library package by registering configuration,
 * views, migrations, routes, Livewire components, and Blade components.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary;

use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent;
use ArtisanPackUI\MediaLibrary\Ai\Agents\ImageTagSuggestionAgent;
use ArtisanPackUI\MediaLibrary\Console\Commands\InstallFrontendCommand;
use ArtisanPackUI\MediaLibrary\Livewire\Components\FolderManager;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaEdit;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaGrid;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaItem;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaLibrary;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaPicker;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaStatistics;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaUpload;
use ArtisanPackUI\MediaLibrary\Livewire\Components\TagManager;
use ArtisanPackUI\MediaLibrary\Managers\MediaManager;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Policies\MediaPolicy;
use ArtisanPackUI\MediaLibrary\Services\ImageOptimizationService;
use ArtisanPackUI\MediaLibrary\Services\MediaProcessingService;
use ArtisanPackUI\MediaLibrary\Services\MediaStorageService;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use ArtisanPackUI\MediaLibrary\Services\VideoProcessingService;
use ArtisanPackUI\MediaLibrary\View\Components\MediaPickerButton;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Service provider for the Media Library package.
 *
 * Bootstraps the Media Library by registering configuration, views,
 * database migrations, and API routes. Configuration is merged into
 * the main artisanpack.php config file following the ArtisanPack UI
 * package conventions.
 *
 * @since   1.0.0
 */
class MediaLibraryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method merges the package's local media configuration into a temporary key.
     * The `boot` method will then handle merging this into the main `artisanpack` config.
     *
     * @since 1.0.0
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/media.php',
            'artisanpack-media-temp',
        );

        // Register services as singletons
        $this->app->singleton( MediaManager::class );
        $this->app->singleton( MediaStorageService::class );
        $this->app->singleton( VideoProcessingService::class );
        $this->app->singleton( ImageOptimizationService::class );
        $this->app->singleton( MediaProcessingService::class );
        $this->app->singleton( MediaUploadService::class );
    }

    /**
     * Bootstrap any application services.
     *
     * This method publishes the configuration, merges it into the main `artisanpack`
     * config array, registers views, and loads database migrations.
     *
     * @since 1.0.0
     */
    public function boot(): void
    {
        $this->mergeConfiguration();
        $this->overrideAiInstructions();
        $this->publishConfiguration();
        $this->publishTypeDefinitions();
        $this->publishReactComponents();
        $this->publishVueComponents();
        $this->registerViews();
        $this->loadMigrationsFrom( __DIR__ . '/../database/migrations' );
        $this->registerPolicies();
        $this->registerRoutes();
        $this->registerLivewireComponents();
        $this->registerBladeComponents();
        $this->registerCommands();
    }

    /**
     * AI features exposed by the media library.
     *
     * Picked up by the `artisanpack-ui/ai` service provider's feature
     * discovery pass — see `discoverFeaturesFromProviders()` in ai's
     * service provider. When the ai package is not installed this method
     * is simply never called.
     *
     * `ai.alt_text` is not re-declared here — the cross-cutting agent lives
     * in `artisanpack-ui/ai` and is registered by that package's own
     * service provider.
     *
     * @since 1.3.0
     *
     * @return array<string, array{ agent: class-string, package: string }>
     */
    public function aiFeatures(): array
    {
        return [
            'media.suggest_tags'      => [
                'agent'   => ImageTagSuggestionAgent::class,
                'package' => 'artisanpack-ui/media-library',
            ],
            'media.image_description' => [
                'agent'   => ImageDescriptionAgent::class,
                'package' => 'artisanpack-ui/media-library',
            ],
        ];
    }

    /**
     * Override the cross-cutting alt-text agent's default prompt so it
     * never returns an empty suggestion for media-library uploads.
     *
     * The shipped ai package prompt permits an empty `alt_text` when the
     * model classifies an image as decorative, which is unhelpful in an
     * asset-management context where every image needs a caption. This
     * override tightens the instruction to always produce descriptive
     * text.
     *
     * Honors any pre-existing `artisanpack.ai.features.ai.alt_text.instructions`
     * value so operators (via ai settings) still take precedence.
     *
     * @since 1.3.0
     */
    protected function overrideAiInstructions(): void
    {
        // Feature keys are literal dot-notation strings inside the
        // `artisanpack.ai.features` array (looked up via `$features[$key]`
        // in ArtisanPackAgent::featureConfig()), so we can't use dot-path
        // config() — that would build a nested tree. Mutate the array
        // in place and write it back.
        $features = (array) config( 'artisanpack.ai.features', [] );

        $entry = isset( $features['ai.alt_text'] ) && is_array( $features['ai.alt_text'] )
            ? $features['ai.alt_text']
            : [];

        if ( isset( $entry['instructions'] ) && is_string( $entry['instructions'] ) && '' !== $entry['instructions'] ) {
            return;
        }

        $entry['instructions']     = $this->defaultAltTextInstructions();
        $features['ai.alt_text']   = $entry;

        config( [ 'artisanpack.ai.features' => $features ] );
    }

    /**
     * Media-library's stricter alt-text prompt: always describe the
     * image, never return empty for "decorative" classification.
     *
     * @since 1.3.0
     *
     * @return string
     */
    protected function defaultAltTextInstructions(): string
    {
        return <<<'PROMPT'
You generate concise, accessibility-friendly alt text for the supplied image.

Requirements:
- ALWAYS return a non-empty `alt_text`. Never return an empty string, even if the image looks decorative — describe what is visually present.
- Describe the image's meaningful content in <= 150 characters.
- Prefer active voice; do not start with "Image of" or "Picture of".
- Do not include a trailing period.
- If the image is a screenshot of text, transcribe the visible text and add a warning "screenshot of text — provide the transcription in body content".
- If the image is unreadable or corrupt, still return your best-guess description of what you can see and add a warning describing the problem.

Return a JSON object with keys: alt_text (string, always non-empty), confidence (float 0..1), warnings (array of strings).
PROMPT;
    }

    /**
     * Merges the package's default configuration with the user's customizations.
     *
     * This method ensures that the user's settings under the 'media' key
     * in `config/artisanpack.php` take precedence over the package's default values.
     *
     * @since 1.0.0
     */
    protected function mergeConfiguration(): void
    {
        $packageDefaults = config( 'artisanpack-media-temp', [] );
        $userConfig      = config( 'artisanpack.media', [] );
        $mergedConfig    = array_replace_recursive( $packageDefaults, $userConfig );
        config( ['artisanpack.media' => $mergedConfig] );
    }

    /**
     * Publish the configuration file to the application's config directory.
     *
     * Configuration will be published to config/artisanpack/media.php to maintain
     * the unified ArtisanPack UI configuration structure.
     *
     * @since 1.0.0
     */
    protected function publishConfiguration(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../config/media.php' => config_path( 'artisanpack/media.php' ),
            ], 'artisanpack-package-config' );
        }
    }

    /**
     * Publish TypeScript type definitions for the media API.
     *
     * Publishes type definitions to the application's resources/types directory
     * so React/Vue consumers have full type safety.
     *
     * @since 1.2.0
     */
    protected function publishTypeDefinitions(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../resources/types/media.d.ts' => resource_path( 'types/media.d.ts' ),
            ], 'media-types' );
        }
    }

    /**
     * Publish React components for the media library.
     *
     * Publishes the React component source files to the application's
     * resources directory so React/Inertia.js consumers can import
     * and use the media library UI components.
     *
     * @since 1.2.0
     */
    protected function publishReactComponents(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../resources/js/react'         => resource_path( 'js/vendor/media-library' ),
                __DIR__ . '/../resources/types/media.d.ts' => resource_path( 'js/vendor/media-library/types/media.d.ts' ),
            ], 'media-react' );
        }
    }

    /**
     * Publish Vue components for the media library.
     *
     * Publishes the Vue component source files to the application's
     * resources directory so Vue/Inertia.js consumers can import
     * and use the media library UI components.
     *
     * @since 1.2.0
     */
    protected function publishVueComponents(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../resources/js/vue'           => resource_path( 'js/vendor/media-library-vue' ),
                __DIR__ . '/../resources/types/media.d.ts' => resource_path( 'js/vendor/media-library-vue/types/media.d.ts' ),
            ], 'media-vue' );
        }
    }

    /**
     * Register the Media Library views.
     *
     * Publishes views to the application's resources path and loads views
     * from both the published and package source paths.
     *
     * @since 1.0.0
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom( __DIR__ . '/../resources/views', 'media' );

        $this->publishes( [
            __DIR__ . '/../resources/views' => resource_path( 'views/vendor/media' ),
        ], 'media-views' );
    }

    /**
     * Register the Media Library policies.
     *
     * @since 1.0.0
     */
    protected function registerPolicies(): void
    {
        Gate::policy( Media::class, MediaPolicy::class );
    }

    /**
     * Register the Media Library API routes.
     *
     * @since 1.0.0
     */
    protected function registerRoutes(): void
    {
        Route::middleware( 'api' )
            ->prefix( 'api' )
            ->group( __DIR__ . '/routes/api.php' );

        // Register web route for media downloads (no auth required since files are public)
        Route::middleware( ['web'] )
            ->get( 'media/{id}/download', [
                Http\Controllers\MediaController::class,
                'download',
            ] )
            ->name( 'media.download' );
    }

    /**
     * Register Livewire components.
     *
     * Only registers if Livewire is available (i.e., in a full Laravel application context).
     *
     * @since 1.0.0
     */
    protected function registerLivewireComponents(): void
    {
        // Only register Livewire components if Livewire is bound in the container
        if ( ! $this->app->bound( 'livewire' ) ) {
            return;
        }

        // Livewire 4 uses namespace-based discovery for namespaced components.
        // In v4, Livewire::component() stores into classComponents but the resolver
        // skips classComponents for names with a namespace prefix (e.g. "media::*"),
        // only checking classNamespaces. Use addNamespace() so the resolver can
        // derive the class from the namespace + component name.
        if ( method_exists( Livewire::getFacadeRoot(), 'addNamespace' ) ) {
            Livewire::addNamespace(
                'media',
                classNamespace: 'ArtisanPackUI\\MediaLibrary\\Livewire\\Components',
            );

            return;
        }

        // Livewire 3 uses explicit component registration
        Livewire::component( 'media::media-library', MediaLibrary::class );
        Livewire::component( 'media::media-upload', MediaUpload::class );
        Livewire::component( 'media::media-edit', MediaEdit::class );
        Livewire::component( 'media::media-grid', MediaGrid::class );
        Livewire::component( 'media::media-item', MediaItem::class );
        Livewire::component( 'media::media-modal', MediaModal::class );
        Livewire::component( 'media::media-picker', MediaPicker::class );
        Livewire::component( 'media::folder-manager', FolderManager::class );
        Livewire::component( 'media::tag-manager', TagManager::class );
        Livewire::component( 'media::media-statistics', MediaStatistics::class );
    }

    /**
     * Register Blade components.
     *
     * @since 1.1.0
     */
    protected function registerBladeComponents(): void
    {
        Blade::component( 'media-picker-button', MediaPickerButton::class );
    }

    /**
     * Register Artisan commands.
     *
     * @since 1.2.0
     */
    protected function registerCommands(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->commands( [
                InstallFrontendCommand::class,
            ] );
        }
    }
}
