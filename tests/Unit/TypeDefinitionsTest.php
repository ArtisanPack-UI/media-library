<?php

/**
 * TypeScript Type Definitions Tests
 *
 * Tests for the publishable TypeScript type definitions file,
 * ensuring the file exists, is publishable, and contains all
 * expected type declarations matching the API response shapes.
 *
 * @since   1.2.0
 */

declare( strict_types=1 );

namespace Tests\Unit;

use Tests\TestCase;

/**
 * TypeScript Type Definitions Test Class
 */
class TypeDefinitionsTest extends TestCase
{
    // =========================================================================
    // File Existence Tests
    // =========================================================================

    /**
     * Test the type definitions file exists in the package.
     */
    public function test_type_definitions_file_exists(): void
    {
        $path = dirname( __DIR__, 2 ) . '/resources/types/media.d.ts';

        expect( file_exists( $path ) )->toBeTrue();
    }

    /**
     * Test the type definitions file is not empty.
     */
    public function test_type_definitions_file_is_not_empty(): void
    {
        $path     = dirname( __DIR__, 2 ) . '/resources/types/media.d.ts';
        $contents = file_get_contents( $path );

        expect( $contents )->not->toBeEmpty();
    }

    // =========================================================================
    // Publishable Tag Tests
    // =========================================================================

    /**
     * Test type definitions are registered as a publishable asset.
     */
    public function test_type_definitions_are_publishable(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;

        expect( $publishGroups )->toHaveKey( 'media-types' );

        $mediaTypes = $publishGroups['media-types'];

        // The service provider registers the source path via __DIR__,
        // so we check that at least one key ends with the expected filename.
        $sourceKeys  = array_keys( $mediaTypes );
        $hasMediaDts = false;
        foreach ( $sourceKeys as $key ) {
            if ( str_ends_with( $key, 'resources/types/media.d.ts' ) ) {
                $hasMediaDts = true;
                break;
            }
        }

        expect( $hasMediaDts )->toBeTrue();
    }

    /**
     * Test the publish target path is correct.
     */
    public function test_publish_target_path_is_correct(): void
    {
        $publishGroups = \Illuminate\Support\ServiceProvider::$publishGroups;
        $mediaTypes    = $publishGroups['media-types'];

        $targetPath = resource_path( 'types/media.d.ts' );

        expect( array_values( $mediaTypes ) )->toContain( $targetPath );
    }

    // =========================================================================
    // Model Type Declaration Tests
    // =========================================================================

    /**
     * Test Media interface is declared.
     */
    public function test_media_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface Media {' );
    }

    /**
     * Test Media interface has all MediaResource fields.
     */
    public function test_media_interface_has_all_resource_fields(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        // Normalize whitespace so indentation doesn't matter
        $normalized = preg_replace( '/\s+/', ' ', $contents );

        $requiredFields = [
            'id: number;',
            'title: string | null;',
            'file_name: string;',
            'file_path: string;',
            'url: string;',
            'disk: string;',
            'mime_type: string;',
            'file_size: number;',
            'human_size: string;',
            'alt_text: string | null;',
            'caption: string | null;',
            'description: string | null;',
            'width: number | null;',
            'height: number | null;',
            'duration: number | null;',
            'is_image: boolean;',
            'is_video: boolean;',
            'is_audio: boolean;',
            'is_document: boolean;',
            'created_at: string | null;',
            'updated_at: string | null;',
            'deleted_at: string | null;',
        ];

        foreach ( $requiredFields as $field ) {
            expect( $normalized )->toContain( $field );
        }
    }

    /**
     * Test Media interface has folder reference.
     */
    public function test_media_interface_has_folder_reference(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'folder: MediaFolderRef' );
        expect( $contents )->toContain( 'export interface MediaFolderRef {' );
    }

    /**
     * Test Media interface has uploaded_by reference.
     */
    public function test_media_interface_has_uploaded_by_reference(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'uploaded_by: MediaUserRef' );
        expect( $contents )->toContain( 'export interface MediaUserRef {' );
    }

    /**
     * Test Media interface has tags.
     */
    public function test_media_interface_has_tags(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'tags: MediaTag[]' );
    }

    /**
     * Test MediaFolder interface is declared.
     */
    public function test_media_folder_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaFolder {' );
    }

    /**
     * Test MediaFolder has required fields.
     */
    public function test_media_folder_has_required_fields(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'parent_id: number | null' );
        expect( $contents )->toContain( 'children: MediaFolder[]' );
    }

    /**
     * Test MediaTag interface is declared.
     */
    public function test_media_tag_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaTag {' );
    }

    // =========================================================================
    // Metadata Discriminated Union Tests
    // =========================================================================

    /**
     * Test metadata union type is declared.
     */
    public function test_metadata_union_type_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'ImageMetadata' );
        expect( $contents )->toContain( 'VideoMetadata' );
        expect( $contents )->toContain( 'AudioMetadata' );
        expect( $contents )->toContain( 'DocumentMetadata' );
    }

    /**
     * Test Media metadata field uses discriminated union.
     */
    public function test_media_metadata_field_uses_discriminated_union(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'metadata: ImageMetadata | VideoMetadata | AudioMetadata | DocumentMetadata | null' );
    }

    // =========================================================================
    // Enum / Union Type Tests
    // =========================================================================

    /**
     * Test MediaType union is declared.
     */
    public function test_media_type_union_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( "export type MediaType = 'image' | 'video' | 'audio' | 'document'" );
    }

    /**
     * Test ImageSize union is declared with built-in sizes.
     */
    public function test_image_size_union_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export type ImageSize =' );
        expect( $contents )->toContain( "'thumbnail'" );
        expect( $contents )->toContain( "'medium'" );
        expect( $contents )->toContain( "'large'" );
        expect( $contents )->toContain( "'full'" );
    }

    /**
     * Test MediaSortField union is declared.
     */
    public function test_media_sort_field_union_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export type MediaSortField' );
        expect( $contents )->toContain( "'created_at'" );
        expect( $contents )->toContain( "'file_size'" );
        expect( $contents )->toContain( "'title'" );
    }

    // =========================================================================
    // Response Type Tests
    // =========================================================================

    /**
     * Test MediaListResponse is declared.
     */
    public function test_media_list_response_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaListResponse {' );
        expect( $contents )->toContain( 'data: Media[]' );
    }

    /**
     * Test MediaListResponse includes pagination.
     */
    public function test_media_list_response_includes_pagination(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'links: PaginationLinks' );
        expect( $contents )->toContain( 'meta: PaginationMeta' );
        expect( $contents )->toContain( 'export interface PaginationLinks {' );
        expect( $contents )->toContain( 'export interface PaginationMeta {' );
    }

    /**
     * Test MediaUploadResponse is declared.
     */
    public function test_media_upload_response_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaUploadResponse {' );
    }

    /**
     * Test FolderTreeResponse is declared.
     */
    public function test_folder_tree_response_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface FolderTreeResponse {' );
    }

    /**
     * Test MediaStatisticsResponse is declared.
     */
    public function test_media_statistics_response_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaStatisticsResponse {' );
        expect( $contents )->toContain( 'total_count: number' );
        expect( $contents )->toContain( 'total_size: number' );
        expect( $contents )->toContain( 'type_breakdown: MediaTypeBreakdown[]' );
    }

    // =========================================================================
    // Filter / Request Type Tests
    // =========================================================================

    /**
     * Test MediaFilter interface is declared.
     */
    public function test_media_filter_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaFilter {' );
        expect( $contents )->toContain( 'folder_id?: number' );
        expect( $contents )->toContain( 'tag?: string' );
        expect( $contents )->toContain( 'search?: string' );
    }

    /**
     * Test MediaSort interface is declared.
     */
    public function test_media_sort_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaSort {' );
        expect( $contents )->toContain( 'field: MediaSortField' );
        expect( $contents )->toContain( 'direction: SortDirection' );
    }

    /**
     * Test MediaUploadPayload is declared.
     */
    public function test_media_upload_payload_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaUploadPayload {' );
        expect( $contents )->toContain( 'file: File' );
    }

    /**
     * Test MediaUpdatePayload is declared.
     */
    public function test_media_update_payload_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaUpdatePayload {' );
    }

    // =========================================================================
    // Configuration Type Tests
    // =========================================================================

    /**
     * Test UploadConfig interface is declared.
     */
    public function test_upload_config_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface UploadConfig {' );
        expect( $contents )->toContain( 'max_file_size: number' );
        expect( $contents )->toContain( 'allowed_mime_types: string[]' );
    }

    /**
     * Test ImageSizeConfig interface is declared.
     */
    public function test_image_size_config_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface ImageSizeConfig {' );
    }

    /**
     * Test BlockMediaRequirements interface is declared.
     */
    public function test_block_media_requirements_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface BlockMediaRequirements {' );
        expect( $contents )->toContain( 'types: MediaType[]' );
        expect( $contents )->toContain( 'max_files: number' );
        expect( $contents )->toContain( 'min_files: number' );
    }

    // =========================================================================
    // Component Props Type Tests
    // =========================================================================

    /**
     * Test MediaPickerOptions interface is declared.
     */
    public function test_media_picker_options_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaPickerOptions {' );
        expect( $contents )->toContain( 'multi_select: boolean' );
        expect( $contents )->toContain( 'max_selections?: number' );
        expect( $contents )->toContain( 'allowed_types?: MediaType[]' );
    }

    /**
     * Test MediaSelectedEvent interface is declared.
     */
    public function test_media_selected_event_interface_is_declared(): void
    {
        $contents = $this->getTypeDefinitionsContents();

        expect( $contents )->toContain( 'export interface MediaSelectedEvent {' );
        expect( $contents )->toContain( 'media: Media[]' );
        expect( $contents )->toContain( 'context: string' );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get the contents of the type definitions file.
     */
    private function getTypeDefinitionsContents(): string
    {
        return file_get_contents( dirname( __DIR__, 2 ) . '/resources/types/media.d.ts');
    }
}
