/**
 * ArtisanPack UI Media Library - useMediaLibrary Composable
 *
 * Manages media browsing state including search, filtering, sorting,
 * pagination, selection, and bulk actions.
 *
 * @package ArtisanPackUI\MediaLibrary
 * @since   1.2.0
 */

import { ref, reactive, watch } from 'vue';

import type {
    Media,
    MediaFilter,
    MediaFolder,
    MediaTag,
    MediaType,
    MediaSortField,
    SortDirection,
    PaginationMeta,
} from '../types/media';

import {
    fetchMedia,
    fetchFolders,
    fetchTags,
    deleteMedia as apiDeleteMedia,
} from '../utils/api';

/**
 * Options for the useMediaLibrary composable.
 */
export interface UseMediaLibraryOptions {
    perPage?: number;
    sortBy?: MediaSortField;
    sortOrder?: SortDirection;
    autoFetch?: boolean;
}

/**
 * Composable for managing media library browsing state.
 */
export function useMediaLibrary( options: UseMediaLibraryOptions = {} ) {
    const {
        perPage   = 24,
        sortBy    = 'created_at',
        sortOrder = 'desc',
        autoFetch = true,
    } = options;

    const media      = ref<Media[]>( [] );
    const loading    = ref( false );
    const error      = ref<string | null>( null );
    const pagination = ref<PaginationMeta | null>( null );
    const folders    = ref<MediaFolder[]>( [] );
    const tags       = ref<MediaTag[]>( [] );

    const filters = reactive<MediaFilter>( {
        per_page:   perPage,
        sort_by:    sortBy,
        sort_order: sortOrder,
        page:       1,
    } );

    const selectedIds     = ref<Set<number>>( new Set() );
    const bulkSelectMode  = ref( false );
    const viewMode        = ref<'grid' | 'list'>( 'grid' );

    async function loadMedia() {
        loading.value = true;
        error.value   = null;

        try {
            const response   = await fetchMedia( { ...filters } );
            media.value      = response.data;
            pagination.value = response.meta;
        } catch ( err ) {
            error.value = err instanceof Error ? err.message : 'Failed to load media';
        } finally {
            loading.value = false;
        }
    }

    async function loadRelatedData() {
        try {
            const [ foldersResponse, tagsResponse ] = await Promise.all( [
                fetchFolders(),
                fetchTags(),
            ] );
            folders.value = foldersResponse.data;
            tags.value    = tagsResponse.data;
        } catch {
            // Non-critical
        }
    }

    // Initial fetch — called directly during setup so it works
    // regardless of whether onMounted fires (e.g. SSR, keep-alive).
    if ( autoFetch ) {
        loadMedia();
        loadRelatedData();
    }

    // Refetch when filters change after the initial load
    let initialised = autoFetch;
    watch(
        () => ( { ...filters } ),
        () => {
            if ( initialised ) {
                loadMedia();
            }
            initialised = true;
        },
        { deep: true },
    );

    function setSearch( search: string ) {
        filters.search    = search || undefined;
        filters.page      = 1;
        selectedIds.value = new Set();
    }

    function setFolderId( folderId: number | undefined ) {
        filters.folder_id = folderId;
        filters.page      = 1;
        selectedIds.value = new Set();
    }

    function setType( type: MediaType | undefined ) {
        filters.type      = type;
        filters.page      = 1;
        selectedIds.value = new Set();
    }

    function setTag( tag: string | undefined ) {
        filters.tag       = tag;
        filters.page      = 1;
        selectedIds.value = new Set();
    }

    function setSort( field: MediaSortField, direction?: SortDirection ) {
        filters.sort_by    = field;
        filters.sort_order = direction ?? ( filters.sort_by === field && filters.sort_order === 'asc' ? 'desc' : 'asc' );
        filters.page       = 1;
        selectedIds.value  = new Set();
    }

    function goToPage( page: number ) {
        filters.page = page;
    }

    async function refresh() {
        await Promise.all( [ loadMedia(), loadRelatedData() ] );
    }

    function toggleSelection( mediaId: number ) {
        const next = new Set( selectedIds.value );
        if ( next.has( mediaId ) ) {
            next.delete( mediaId );
        } else {
            next.add( mediaId );
        }
        selectedIds.value = next;
    }

    function selectAll() {
        selectedIds.value = new Set( media.value.map( ( m ) => m.id ) );
    }

    function clearSelection() {
        selectedIds.value = new Set();
    }

    function toggleBulkSelectMode() {
        if ( bulkSelectMode.value ) {
            selectedIds.value = new Set();
        }
        bulkSelectMode.value = ! bulkSelectMode.value;
    }

    function toggleViewMode() {
        viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
    }

    async function deleteSelected() {
        if ( selectedIds.value.size === 0 ) {
            return;
        }

        loading.value = true;
        error.value   = null;

        try {
            const ids     = Array.from( selectedIds.value );
            const results = await Promise.allSettled(
                ids.map( ( id ) => apiDeleteMedia( id ) ),
            );

            const failedIds: number[]  = [];
            const succeededIds: number[] = [];

            results.forEach( ( result, index ) => {
                if ( 'fulfilled' === result.status ) {
                    succeededIds.push( ids[index] );
                } else {
                    failedIds.push( ids[index] );
                }
            } );

            const remaining = new Set( selectedIds.value );
            for ( const id of succeededIds ) {
                remaining.delete( id );
            }
            selectedIds.value = remaining;

            const deleteError = failedIds.length > 0
                ? `Failed to delete ${ failedIds.length } item(s)`
                : null;

            await loadMedia();

            if ( deleteError ) {
                error.value = deleteError;
            }
        } catch ( err ) {
            error.value = err instanceof Error ? err.message : 'Failed to delete media';
        } finally {
            loading.value = false;
        }
    }

    return {
        media,
        loading,
        error,
        pagination,
        folders,
        tags,
        filters,
        selectedIds,
        bulkSelectMode,
        viewMode,
        setSearch,
        setFolderId,
        setType,
        setTag,
        setSort,
        goToPage,
        refresh,
        toggleSelection,
        selectAll,
        clearSelection,
        toggleBulkSelectMode,
        toggleViewMode,
        deleteSelected,
    };
}
