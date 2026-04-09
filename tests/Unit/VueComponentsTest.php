<?php

/**
 * Vue Components Tests
 *
 * Tests for the publishable Vue component files, ensuring all required
 * files exist, are publishable via the 'media-vue' tag, and contain
 * expected exports and TypeScript/Vue patterns.
 *
 * @since   1.2.0
 */

declare( strict_types=1 );

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Vue Components Test Class
 */
class VueComponentsTest extends TestCase
{
    // =========================================================================
    // File Existence Tests
    // =========================================================================

    public function test_vue_directory_exists(): void
    {
        expect( is_dir( dirname( __DIR__, 2 ) . '/resources/js/vue' ) )->toBeTrue();
    }

    public function test_vue_index_file_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/index.ts' ) )->toBeTrue();
    }

    public function test_api_utility_file_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/utils/api.ts' ) )->toBeTrue();
    }

    public function test_types_reexport_file_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/types/media.d.ts' ) )->toBeTrue();
    }

    // =========================================================================
    // Composable File Tests
    // =========================================================================

    public function test_use_media_library_composable_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/composables/useMediaLibrary.ts' ) )->toBeTrue();
    }

    public function test_use_media_upload_composable_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/composables/useMediaUpload.ts' ) )->toBeTrue();
    }

    public function test_use_media_picker_composable_exists(): void
    {
        expect( file_exists( dirname( __DIR__, 2 ) . '/resources/js/vue/composables/useMediaPicker.ts' ) )->toBeTrue();
    }

    // =========================================================================
    // Component File Tests
    // =========================================================================

    public function test_all_vue_component_files_exist(): void
    {
        $components = [
            'MediaLibrary.vue',
            'MediaUpload.vue',
            'MediaModal.vue',
            'MediaPicker.vue',
            'MediaGrid.vue',
            'MediaItem.vue',
            'MediaEdit.vue',
            'FolderManager.vue',
            'FolderNode.vue',
            'TagManager.vue',
            'MediaStatistics.vue',
        ];

        foreach ( $components as $file ) {
            expect( file_exists(
                dirname( __DIR__, 2 ) . '/resources/js/vue/components/' . $file,
            ) )->toBeTrue();
        }
    }

    // =========================================================================
    // Publishable Tag Tests
    // =========================================================================

    public function test_vue_components_are_publishable(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;

        expect( $publishGroups )->toHaveKey( 'media-vue' );
    }

    public function test_vue_publish_source_path_is_correct(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaVue      = $publishGroups['media-vue'];

        $sourceKeys = array_keys( $mediaVue );
        $hasVue     = false;
        foreach ( $sourceKeys as $key ) {
            if ( str_ends_with( $key, 'resources/js/vue' ) ) {
                $hasVue = true;
                break;
            }
        }

        expect( $hasVue )->toBeTrue();
    }

    public function test_vue_publish_target_path_is_correct(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaVue      = $publishGroups['media-vue'];

        expect( array_values( $mediaVue ) )->toContain( resource_path( 'js/vendor/media-library-vue' ) );
    }

    public function test_vue_publish_includes_types(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaVue      = $publishGroups['media-vue'];

        $sourceKeys = array_keys( $mediaVue );
        $hasTypes   = false;
        foreach ( $sourceKeys as $key ) {
            if ( str_ends_with( $key, 'resources/types/media.d.ts' ) ) {
                $hasTypes = true;
                break;
            }
        }

        expect( $hasTypes )->toBeTrue();
    }

    // =========================================================================
    // Index File Export Tests
    // =========================================================================

    public function test_index_exports_all_composables(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'useMediaLibrary' );
        expect( $contents )->toContain( 'useMediaUpload' );
        expect( $contents )->toContain( 'useMediaPicker' );
    }

    public function test_index_exports_all_core_components(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'MediaLibrary' );
        expect( $contents )->toContain( 'MediaUpload' );
        expect( $contents )->toContain( 'MediaModal' );
        expect( $contents )->toContain( 'MediaPicker' );
        expect( $contents )->toContain( 'MediaGrid' );
        expect( $contents )->toContain( 'MediaItem' );
    }

    public function test_index_exports_all_management_components(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'MediaEdit' );
        expect( $contents )->toContain( 'FolderManager' );
        expect( $contents )->toContain( 'TagManager' );
        expect( $contents )->toContain( 'MediaStatistics' );
    }

    public function test_index_exports_api_utilities(): void
    {
        $contents = $this->getIndexContents();

        expect( $contents )->toContain( 'fetchMedia' );
        expect( $contents )->toContain( 'uploadMedia' );
        expect( $contents )->toContain( 'deleteMedia' );
        expect( $contents )->toContain( 'fetchFolders' );
        expect( $contents )->toContain( 'fetchTags' );
        expect( $contents )->toContain( 'configureAuth' );
    }

    // =========================================================================
    // Component Content Tests
    // =========================================================================

    public function test_components_use_artisanpack_vue(): void
    {
        $componentFiles = [
            'MediaLibrary.vue',
            'MediaUpload.vue',
            'MediaModal.vue',
            'MediaGrid.vue',
            'MediaItem.vue',
            'MediaEdit.vue',
            'FolderManager.vue',
            'TagManager.vue',
            'MediaStatistics.vue',
        ];

        foreach ( $componentFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/vue/components/' . $file,
            );

            expect( $contents )->toContain( '@artisanpack-ui/vue' );
        }
    }

    public function test_components_use_script_setup(): void
    {
        $componentFiles = [
            'MediaLibrary.vue',
            'MediaUpload.vue',
            'MediaModal.vue',
            'MediaPicker.vue',
            'MediaGrid.vue',
            'MediaItem.vue',
            'MediaEdit.vue',
            'FolderManager.vue',
            'TagManager.vue',
            'MediaStatistics.vue',
        ];

        foreach ( $componentFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/vue/components/' . $file,
            );

            expect( $contents )->toContain( '<script setup lang="ts">' );
        }
    }

    public function test_composables_import_media_types(): void
    {
        $composableFiles = [
            'useMediaLibrary.ts',
            'useMediaUpload.ts',
            'useMediaPicker.ts',
        ];

        foreach ( $composableFiles as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/vue/composables/' . $file,
            );

            expect( $contents )->toContain( "from '../types/media'" );
        }
    }

    public function test_api_utility_imports_media_types(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/vue/utils/api.ts',
        );

        expect( $contents )->toContain( "from '../types/media'" );
    }

    public function test_api_utility_has_all_endpoints(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/vue/utils/api.ts',
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

    public function test_api_handles_sanctum_auth(): void
    {
        $contents = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/vue/utils/api.ts',
        );

        expect( $contents )->toContain( 'XSRF-TOKEN' );
        expect( $contents )->toContain( 'configureAuth' );
        expect( $contents )->toContain( 'Bearer' );
    }

    public function test_components_include_aria_attributes(): void
    {
        $mediaItem = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/vue/components/MediaItem.vue',
        );

        expect( $mediaItem )->toContain( 'aria-selected' );
        expect( $mediaItem )->toContain( 'aria-label' );
        expect( $mediaItem )->toContain( 'role=' );

        $folderManager = file_get_contents(
            dirname( __DIR__, 2 ) . '/resources/js/vue/components/FolderManager.vue',
        );

        expect( $folderManager )->toContain( 'role="tree"' );
        expect( $folderManager )->toContain( 'role="treeitem"' );
    }

    public function test_modals_use_teleport_to_body(): void
    {
        $components = [
            'MediaModal.vue',
            'MediaEdit.vue',
            'FolderManager.vue',
            'TagManager.vue',
        ];

        foreach ( $components as $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/vue/components/' . $file,
            );

            expect( $contents )->toContain( '<Teleport to="body">' );
        }
    }

    public function test_composables_export_functions(): void
    {
        $composableExports = [
            'useMediaLibrary' => 'useMediaLibrary.ts',
            'useMediaUpload'  => 'useMediaUpload.ts',
            'useMediaPicker'  => 'useMediaPicker.ts',
        ];

        foreach ( $composableExports as $name => $file ) {
            $contents = file_get_contents(
                dirname( __DIR__, 2 ) . '/resources/js/vue/composables/' . $file,
            );

            expect( $contents )->toContain( "export function $name" );
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function getIndexContents(): string
    {
        return file_get_contents( dirname( __DIR__, 2 ) . '/resources/js/vue/index.ts' );
    }
}
