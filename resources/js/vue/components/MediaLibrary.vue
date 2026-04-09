<!--
  ArtisanPack UI Media Library - MediaLibrary Component

  Full media browsing/management interface with grid/list view toggle,
  search, filtering, sorting, pagination, and bulk actions.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button, Input, Select, Card, Alert } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media, MediaType, MediaSortField, SortDirection } from '../types/media';

import { useMediaLibrary } from '../composables/useMediaLibrary';
import type { UseMediaLibraryOptions } from '../composables/useMediaLibrary';
import MediaGrid from './MediaGrid.vue';
import MediaUpload from './MediaUpload.vue';
import MediaEdit from './MediaEdit.vue';

const props = withDefaults( defineProps<UseMediaLibraryOptions & {
    showUpload?: boolean;
}>(), {
    showUpload: true,
    autoFetch:  true,
} );

const emit = defineEmits<{
    mediaClick: [media: Media];
    selectionChange: [media: Media[]];
}>();

const lib = useMediaLibrary( {
    perPage:   props.perPage,
    sortBy:    props.sortBy,
    sortOrder: props.sortOrder,
    autoFetch: props.autoFetch,
} );

const showUploadPanel = ref( false );
const editingMedia    = ref<Media | null>( null );

// Close upload panel when parent disables showUpload
watch( () => props.showUpload, ( show ) => {
    if ( ! show ) {
        showUploadPanel.value = false;
    }
} );

// Notify parent when selection changes
watch(
    [ () => lib.selectedIds.value, () => lib.media.value ],
    () => {
        const selected = lib.media.value.filter( ( m ) => lib.selectedIds.value.has( m.id ) );
        emit( 'selectionChange', selected );
    },
);

function handleItemClick( item: Media ) {
    emit( 'mediaClick', item );
    editingMedia.value = item;
}

const folderOptions = computed( () => [
    { id: '', name: 'All Folders' },
    ...lib.folders.value.map( ( f ) => ( { id: String( f.id ), name: f.name } ) ),
] );

const tagOptions = computed( () => [
    { id: '', name: 'All Tags' },
    ...lib.tags.value.map( ( t ) => ( { id: t.slug, name: t.name } ) ),
] );

const typeFilterOptions = [
    { id: '',         name: 'All Types' },
    { id: 'image',    name: 'Images' },
    { id: 'video',    name: 'Videos' },
    { id: 'audio',    name: 'Audio' },
    { id: 'document', name: 'Documents' },
];

const sortOptions = [
    { id: 'created_at:desc',  name: 'Newest first' },
    { id: 'created_at:asc',   name: 'Oldest first' },
    { id: 'title:asc',        name: 'Title A-Z' },
    { id: 'title:desc',       name: 'Title Z-A' },
    { id: 'file_size:desc',   name: 'Largest first' },
    { id: 'file_size:asc',    name: 'Smallest first' },
];

function handleSortChange( value: string | number ) {
    const [ field, direction ] = String( value ).split( ':' );
    lib.setSort( field as MediaSortField, direction as SortDirection );
}

function handleFolderChange( value: string | number ) {
    lib.setFolderId( value ? Number( value ) : undefined );
}

function handleTypeChange( value: string | number ) {
    lib.setType( ( value as string ) || undefined as MediaType | undefined );
}

function handleTagChange( value: string | number ) {
    lib.setTag( ( value as string ) || undefined );
}
</script>

<template>
    <div :class="cn( 'flex flex-col gap-4' )">
        <!-- Error display -->
        <Alert v-if="lib.error.value" color="error">{{ lib.error.value }}</Alert>

        <!-- Toolbar -->
        <Card>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-48">
                    <Input
                        aria-label="Search media"
                        placeholder="Search media..."
                        :model-value="lib.filters.search || ''"
                        @update:model-value="lib.setSearch( $event as string )"
                    >
                        <template #icon>
                            <svg class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </template>
                    </Input>
                </div>

                <Select
                    aria-label="Filter by folder"
                    :options="folderOptions"
                    :model-value="lib.filters.folder_id ? String( lib.filters.folder_id ) : ''"
                    @update:model-value="handleFolderChange"
                />
                <Select
                    aria-label="Filter by type"
                    :options="typeFilterOptions"
                    :model-value="( lib.filters.type as string ) || ''"
                    @update:model-value="handleTypeChange"
                />
                <Select
                    aria-label="Filter by tag"
                    :options="tagOptions"
                    :model-value="lib.filters.tag || ''"
                    @update:model-value="handleTagChange"
                />
                <Select
                    aria-label="Sort media"
                    :options="sortOptions"
                    :model-value="`${ lib.filters.sort_by || 'created_at' }:${ lib.filters.sort_order || 'desc' }`"
                    @update:model-value="handleSortChange"
                />
            </div>

            <!-- Actions row -->
            <div class="flex items-center gap-2 mt-3">
                <Button
                    v-if="showUpload"
                    size="sm"
                    color="primary"
                    @click="showUploadPanel = ! showUploadPanel"
                >
                    <svg class="w-4 h-4 mr-1" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    Upload
                </Button>

                <Button
                    size="sm"
                    :color="lib.bulkSelectMode.value ? 'warning' : 'ghost'"
                    @click="lib.toggleBulkSelectMode()"
                >
                    {{ lib.bulkSelectMode.value ? 'Cancel selection' : 'Select' }}
                </Button>

                <template v-if="lib.bulkSelectMode.value && lib.selectedIds.value.size > 0">
                    <Button size="sm" color="ghost" @click="lib.selectAll()">
                        Select all
                    </Button>
                    <Button size="sm" color="error" @click="lib.deleteSelected()">
                        Delete {{ lib.selectedIds.value.size }} item{{ lib.selectedIds.value.size !== 1 ? 's' : '' }}
                    </Button>
                </template>

                <div class="flex-1" />

                <!-- View toggle -->
                <div class="join">
                    <button
                        type="button"
                        :class="cn( 'join-item btn btn-sm', lib.viewMode.value === 'grid' && 'btn-active' )"
                        aria-label="Grid view"
                        :aria-pressed="lib.viewMode.value === 'grid'"
                        @click="lib.viewMode.value !== 'grid' && lib.toggleViewMode()"
                    >
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        :class="cn( 'join-item btn btn-sm', lib.viewMode.value === 'list' && 'btn-active' )"
                        aria-label="List view"
                        :aria-pressed="lib.viewMode.value === 'list'"
                        @click="lib.viewMode.value !== 'list' && lib.toggleViewMode()"
                    >
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                    </button>
                </div>

                <Button size="sm" color="ghost" aria-label="Refresh library" @click="lib.refresh()">
                    <svg class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                    </svg>
                </Button>
            </div>
        </Card>

        <!-- Upload panel -->
        <Card v-if="showUploadPanel">
            <MediaUpload auto-upload @upload-complete="lib.refresh()" />
        </Card>

        <!-- Media grid -->
        <MediaGrid
            :media="lib.media.value"
            :loading="lib.loading.value"
            :view-mode="lib.viewMode.value"
            :bulk-select-mode="lib.bulkSelectMode.value"
            :selected-ids="lib.selectedIds.value"
            :pagination="lib.pagination.value"
            @item-click="handleItemClick"
            @item-select="( m ) => lib.toggleSelection( m.id )"
            @page-change="lib.goToPage"
        />

        <!-- Edit panel -->
        <MediaEdit
            v-if="editingMedia"
            :media="editingMedia"
            @close="editingMedia = null"
            @save="editingMedia = null; lib.refresh()"
            @delete="editingMedia = null; lib.refresh()"
        />
    </div>
</template>
