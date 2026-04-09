<?php

/**
 * React Components Tests
 *
 * Tests for the publishable React component files, ensuring all required
 * files exist, are publishable via the 'media-react' tag, and contain
 * expected exports and TypeScript patterns.
 *
 * @since   1.2.0
 */

declare( strict_types=1 );

namespace Tests\Unit;

use Tests\TestCase;

/**
 * React Components Test Class
 */
class ReactComponentsTest extends TestCase
{
    // =========================================================================
    // File Existence Tests
    // =========================================================================

    /**
     * Test the React components directory exists.
     */
    public function test_react_directory_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react';

        expect( is_dir( $path ) )->toBeTrue();
    }

    /**
     * Test the React index file exists.
     */
    public function test_react_index_file_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/index.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test the API utility file exists.
     */
    public function test_api_utility_file_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/utils/api.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    // =========================================================================
    // Hook File Tests
    // =========================================================================

    /**
     * Test useMediaLibrary hook file exists.
     */
    public function test_use_media_library_hook_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/hooks/useMediaLibrary.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test useMediaUpload hook file exists.
     */
    public function test_use_media_upload_hook_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/hooks/useMediaUpload.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test useMediaPicker hook file exists.
     */
    public function test_use_media_picker_hook_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/hooks/useMediaPicker.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    // =========================================================================
    // Core Component File Tests
    // =========================================================================

    /**
     * Test MediaLibrary component file exists.
     */
    public function test_media_library_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaLibrary.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaUpload component file exists.
     */
    public function test_media_upload_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaUpload.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaModal component file exists.
     */
    public function test_media_modal_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaModal.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaPicker component file exists.
     */
    public function test_media_picker_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaPicker.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaGrid component file exists.
     */
    public function test_media_grid_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaGrid.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaItem component file exists.
     */
    public function test_media_item_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaItem.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    // =========================================================================
    // Management Component File Tests
    // =========================================================================

    /**
     * Test MediaEdit component file exists.
     */
    public function test_media_edit_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaEdit.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test FolderManager component file exists.
     */
    public function test_folder_manager_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/FolderManager.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test TagManager component file exists.
     */
    public function test_tag_manager_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/TagManager.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test MediaStatistics component file exists.
     */
    public function test_media_statistics_component_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaStatistics.tsx';

        expect( file_exists( $path ) )->toBeTrue();
    }

    // =========================================================================
    // Publishable Tag Tests
    // =========================================================================

    /**
     * Test React components are registered as publishable.
     */
    public function test_react_components_are_publishable(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;

        expect( $publishGroups )->toHaveKey( 'media-react' );
    }

    /**
     * Test the React publish source path is correct.
     */
    public function test_react_publish_source_path_is_correct(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaReact    = $publishGroups['media-react'];

        $sourceKeys = array_keys( $mediaReact );
        $hasReact   = false;
        foreach ( $sourceKeys as $key ) {
            if ( str_ends_with( $key, 'resources/js/react' ) ) {
                $hasReact = true;
                break;
            }
        }

        expect( $hasReact )->toBeTrue();
    }

    /**
     * Test the React publish target path is correct.
     */
    public function test_react_publish_target_path_is_correct(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaReact    = $publishGroups['media-react'];

        $targetPath = resource_path( 'js/vendor/media-library' );

        expect( array_values( $mediaReact ) )->toContain( $targetPath );
    }

    // =========================================================================
    // Index File Export Tests
    // =========================================================================

    /**
     * Test index file exports all hooks.
     */
    public function test_index_exports_all_hooks(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'export { useMediaLibrary }' );
        expect( $contents )->toContain( 'export { useMediaUpload }' );
        expect( $contents )->toContain( 'export { useMediaPicker }' );
    }

    /**
     * Test index file exports all core components.
     */
    public function test_index_exports_all_core_components(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'export { MediaLibrary }' );
        expect( $contents )->toContain( 'export { MediaUpload }' );
        expect( $contents )->toContain( 'export { MediaModal }' );
        expect( $contents )->toContain( 'export { MediaPicker }' );
        expect( $contents )->toContain( 'export { MediaGrid }' );
        expect( $contents )->toContain( 'export { MediaItem }' );
    }

    /**
     * Test index file exports all management components.
     */
    public function test_index_exports_all_management_components(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'export { MediaEdit }' );
        expect( $contents )->toContain( 'export { FolderManager }' );
        expect( $contents )->toContain( 'export { TagManager }' );
        expect( $contents )->toContain( 'export { MediaStatistics }' );
    }

    /**
     * Test index file exports API utility functions.
     */
    public function test_index_exports_api_utilities(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'fetchMedia' );
        expect( $contents )->toContain( 'uploadMedia' );
        expect( $contents )->toContain( 'updateMedia' );
        expect( $contents )->toContain( 'deleteMedia' );
        expect( $contents )->toContain( 'fetchFolders' );
        expect( $contents )->toContain( 'fetchTags' );
        expect( $contents )->toContain( 'fetchMediaConfig' );
    }

    // =========================================================================
    // Component Content Tests
    // =========================================================================

    /**
     * Test all components import from @artisanpack-ui/react.
     */
    public function test_components_use_artisanpack_react(): void
    {
        $componentFiles = [
            'MediaLibrary.tsx',
            'MediaUpload.tsx',
            'MediaModal.tsx',
            'MediaPicker.tsx',
            'MediaGrid.tsx',
            'MediaItem.tsx',
            'MediaEdit.tsx',
            'FolderManager.tsx',
            'TagManager.tsx',
            'MediaStatistics.tsx',
        ];

        foreach ( $componentFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/react/components/' . $file,
            );

            expect( $contents )->toContain( '@artisanpack-ui/react' );
        }
    }

    /**
     * Test components that use cn() import from @artisanpack-ui/tokens.
     */
    public function test_components_use_artisanpack_tokens(): void
    {
        // Only components that directly use cn() or token utilities
        $componentFiles = [
            'MediaLibrary.tsx',
            'MediaModal.tsx',
            'MediaPicker.tsx',
            'MediaItem.tsx',
            'FolderManager.tsx',
            'TagManager.tsx',
            'MediaStatistics.tsx',
        ];

        foreach ( $componentFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/react/components/' . $file,
            );

            expect( $contents )->toContain( '@artisanpack-ui/tokens' );
        }
    }

    /**
     * Test hooks use proper TypeScript typing with media types.
     */
    public function test_hooks_import_media_types(): void
    {
        $hookFiles = [
            'useMediaLibrary.ts',
            'useMediaUpload.ts',
            'useMediaPicker.ts',
        ];

        foreach ( $hookFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/react/hooks/' . $file,
            );

            expect( $contents )->toContain( "from '../../../types/media'" );
        }
    }

    /**
     * Test the API utility imports media types.
     */
    public function test_api_utility_imports_media_types(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/react/utils/api.ts',
        );

        expect( $contents )->toContain( "from '../../types/media'" );
    }

    /**
     * Test the API utility includes all required endpoint functions.
     */
    public function test_api_utility_has_all_endpoints(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/react/utils/api.ts',
        );

        $expectedFunctions = [
            'fetchMediaConfig',
            'fetchMedia',
            'fetchMediaById',
            'uploadMedia',
            'updateMedia',
            'deleteMedia',
            'fetchFolders',
            'createFolder',
            'updateFolder',
            'deleteFolder',
            'moveFolder',
            'fetchTags',
            'createTag',
            'updateTag',
            'deleteTag',
            'attachTag',
            'detachTag',
        ];

        foreach ( $expectedFunctions as $fn ) {
            expect( $contents )->toContain( "export async function $fn" );
        }
    }

    /**
     * Test components export proper React.FC types.
     */
    public function test_components_export_react_fc(): void
    {
        $componentExports = [
            'MediaLibrary'    => 'MediaLibrary.tsx',
            'MediaUpload'     => 'MediaUpload.tsx',
            'MediaModal'      => 'MediaModal.tsx',
            'MediaPicker'     => 'MediaPicker.tsx',
            'MediaGrid'       => 'MediaGrid.tsx',
            'MediaItem'       => 'MediaItem.tsx',
            'MediaEdit'       => 'MediaEdit.tsx',
            'FolderManager'   => 'FolderManager.tsx',
            'TagManager'      => 'TagManager.tsx',
            'MediaStatistics' => 'MediaStatistics.tsx',
        ];

        foreach ( $componentExports as $name => $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/react/components/' . $file,
            );

            expect( $contents )->toContain( "export const $name: React.FC" );
        }
    }

    /**
     * Test hooks export proper function declarations.
     */
    public function test_hooks_export_functions(): void
    {
        $hookExports = [
            'useMediaLibrary' => 'useMediaLibrary.ts',
            'useMediaUpload'  => 'useMediaUpload.ts',
            'useMediaPicker'  => 'useMediaPicker.ts',
        ];

        foreach ( $hookExports as $name => $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/react/hooks/' . $file,
            );

            expect( $contents )->toContain( "export function $name" );
        }
    }

    /**
     * Test the API utility handles Sanctum XSRF token.
     */
    public function test_api_handles_sanctum_xsrf(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/react/utils/api.ts',
        );

        expect( $contents )->toContain( 'XSRF-TOKEN' );
        expect( $contents )->toContain( 'X-XSRF-TOKEN' );
        expect( $contents )->toContain( "credentials: 'include'" );
    }

    /**
     * Test components are accessible (use ARIA attributes).
     */
    public function test_components_include_aria_attributes(): void
    {
        $mediaItem = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/react/components/MediaItem.tsx',
        );

        expect( $mediaItem )->toContain( 'aria-selected' );
        expect( $mediaItem )->toContain( 'aria-label' );
        expect( $mediaItem )->toContain( 'role=' );

        $folderManager = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/react/components/FolderManager.tsx',
        );

        expect( $folderManager )->toContain( 'role="tree"' );
        expect( $folderManager )->toContain( 'role="treeitem"' );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get the contents of the React index file.
     */
    private function getIndexContents(): string
    {
        return file_get_contents( dirname( __DIR__, 2 ) . '/resources/js/react/index.ts' );
    }
}
