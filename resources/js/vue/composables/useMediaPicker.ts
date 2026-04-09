/**
 * ArtisanPack UI Media Library - useMediaPicker Composable
 *
 * Manages picker state for single/multi-select media workflows,
 * including recently used tracking and keyboard navigation.
 *
 * @package ArtisanPackUI\MediaLibrary
 * @since   1.2.0
 */

import { ref, watch, onMounted } from 'vue';

import type {
    Media,
    MediaType,
    MediaFilter,
    PaginationMeta,
} from '../types/media';

import { fetchMedia } from '../utils/api';

/**
 * Options for the useMediaPicker composable.
 */
export interface UseMediaPickerOptions {
    multiSelect?: boolean;
    maxSelections?: number;
    allowedTypes?: MediaType[];
    context?: string;
    perPage?: number;
    onSelect?: ( media: Media[], context: string ) => void;
}

const RECENTLY_USED_KEY = 'artisanpack-media-recently-used';
const MAX_RECENT_ITEMS  = 10;

function loadRecentIds(): number[] {
    try {
        const stored = sessionStorage.getItem( RECENTLY_USED_KEY );
        if ( ! stored ) {
            return [];
        }
        const parsed: unknown = JSON.parse( stored );
        if ( Array.isArray( parsed ) && parsed.every( ( item ) => typeof item === 'number' ) ) {
            return parsed as number[];
        }
        return [];
    } catch {
        return [];
    }
}

function saveRecentIds( ids: number[] ): void {
    try {
        sessionStorage.setItem( RECENTLY_USED_KEY, JSON.stringify( ids.slice( 0, MAX_RECENT_ITEMS ) ) );
    } catch {
        // Session storage not available
    }
}

/**
 * Composable for managing media picker state.
 */
export function useMediaPicker( options: UseMediaPickerOptions = {} ) {
    const {
        multiSelect  = false,
        maxSelections,
        allowedTypes,
        context    = 'default',
        perPage    = 24,
    } = options;

    let currentOptions = options;

    const media         = ref<Media[]>( [] );
    const loading       = ref( false );
    const error         = ref<string | null>( null );
    const pagination    = ref<PaginationMeta | null>( null );
    const selectedMedia = ref<Media[]>( [] );
    const focusedIndex  = ref( -1 );
    const search        = ref( '' );
    const folderId      = ref<number | undefined>();
    const typeFilter    = ref<MediaType | undefined>();
    const recentlyUsed  = ref<Media[]>( [] );
    const currentPage   = ref( 1 );

    function buildFilters(): MediaFilter {
        const filters: MediaFilter = {
            per_page:   perPage,
            sort_by:    'created_at',
            sort_order: 'desc',
            page:       currentPage.value,
            search:     search.value || undefined,
            folder_id:  folderId.value,
        };

        const activeType = typeFilter.value ?? ( allowedTypes?.length === 1 ? allowedTypes[0] : undefined );
        if ( activeType ) {
            filters.type = activeType;
        }

        return filters;
    }

    async function loadMedia() {
        loading.value = true;
        error.value   = null;

        try {
            const response   = await fetchMedia( buildFilters() );
            media.value      = response.data;
            pagination.value = response.meta;
        } catch ( err ) {
            error.value = err instanceof Error ? err.message : 'Failed to load media';
        } finally {
            loading.value = false;
        }
    }

    // Watch filter changes
    watch( [ search, folderId, typeFilter ], () => {
        currentPage.value = 1;
        loadMedia();
    } );

    watch( currentPage, () => {
        loadMedia();
    } );

    onMounted( () => {
        loadMedia();

        // Load recently used
        const recentIds = loadRecentIds();
        if ( recentIds.length > 0 ) {
            fetchMedia( { per_page: MAX_RECENT_ITEMS } )
                .then( ( response ) => {
                    recentlyUsed.value = response.data.filter( ( m ) => recentIds.includes( m.id ) );
                } )
                .catch( () => { /* non-critical */ } );
        }
    } );

    function setSearch( value: string ) {
        search.value = value;
    }

    function setFolderId( value: number | undefined ) {
        folderId.value = value;
    }

    function setTypeFilter( value: MediaType | undefined ) {
        typeFilter.value = value;
    }

    function toggleSelect( item: Media ) {
        const isSelected = selectedMedia.value.some( ( m ) => m.id === item.id );

        if ( isSelected ) {
            selectedMedia.value = selectedMedia.value.filter( ( m ) => m.id !== item.id );
            return;
        }

        if ( ! multiSelect ) {
            selectedMedia.value = [ item ];
            return;
        }

        if ( maxSelections && selectedMedia.value.length >= maxSelections ) {
            return;
        }

        selectedMedia.value = [ ...selectedMedia.value, item ];
    }

    function confirmSelection() {
        if ( selectedMedia.value.length === 0 ) {
            return;
        }

        const recentIds = loadRecentIds();
        const newIds    = selectedMedia.value.map( ( m ) => m.id );
        const merged    = [ ...new Set( [ ...newIds, ...recentIds ] ) ];
        saveRecentIds( merged );

        currentOptions.onSelect?.( selectedMedia.value, context );
    }

    function clearSelection() {
        selectedMedia.value = [];
    }

    function goToPage( page: number ) {
        currentPage.value = page;
    }

    async function refresh() {
        await loadMedia();
    }

    function focusNext() {
        focusedIndex.value = focusedIndex.value < media.value.length - 1 ? focusedIndex.value + 1 : 0;
    }

    function focusPrevious() {
        focusedIndex.value = focusedIndex.value > 0 ? focusedIndex.value - 1 : media.value.length - 1;
    }

    function selectFocused() {
        if ( focusedIndex.value >= 0 && focusedIndex.value < media.value.length ) {
            toggleSelect( media.value[focusedIndex.value] );
        }
    }

    return {
        media,
        loading,
        error,
        pagination,
        selectedMedia,
        focusedIndex,
        search,
        folderId,
        typeFilter,
        recentlyUsed,
        setSearch,
        setFolderId,
        setTypeFilter,
        toggleSelect,
        confirmSelection,
        clearSelection,
        goToPage,
        refresh,
        focusNext,
        focusPrevious,
        selectFocused,
    };
}
