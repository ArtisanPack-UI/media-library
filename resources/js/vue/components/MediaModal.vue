<!--
  ArtisanPack UI Media Library - MediaModal Component

  Modal-based media picker with library/upload tabs, multi-select,
  recently used tracking, and keyboard navigation.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Modal, Button, Input, Select, Tabs, Badge } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media, MediaType, MediaFolder } from '../types/media';

import { fetchFolders } from '../utils/api';
import { useMediaPicker } from '../composables/useMediaPicker';
import MediaGrid from './MediaGrid.vue';
import MediaUpload from './MediaUpload.vue';

const props = withDefaults( defineProps<{
    multiSelect?: boolean;
    maxSelections?: number;
    allowedTypes?: MediaType[];
    context?: string;
    perPage?: number;
    title?: string;
    showUploadTab?: boolean;
}>(), {
    multiSelect:   false,
    context:       'default',
    perPage:       24,
    title:         'Select Media',
    showUploadTab: true,
} );

const open = defineModel<boolean>( 'open', { default: false } );

const emit = defineEmits<{
    select: [media: Media[], context: string];
}>();

const activeTab = ref( 'library' );
const folders   = ref<MediaFolder[]>( [] );

const picker = useMediaPicker( {
    multiSelect:   props.multiSelect,
    maxSelections: props.maxSelections,
    allowedTypes:  props.allowedTypes,
    context:       props.context,
    perPage:       props.perPage,
    onSelect:      ( media, ctx ) => {
        emit( 'select', media, ctx );
        open.value = false;
    },
} );

// Load folders when modal opens (immediate so it works if open at mount)
watch( open, ( isOpen ) => {
    if ( isOpen ) {
        fetchFolders()
            .then( ( response ) => { folders.value = response.data; } )
            .catch( () => { /* non-critical */ } );
    }
}, { immediate: true } );

function flattenFolders( items: MediaFolder[], depth = 0 ): Array<{ id: string; name: string }> {
    const result: Array<{ id: string; name: string }> = [];
    for ( const folder of items ) {
        const prefix = depth > 0 ? '\u2003'.repeat( depth ) + '\u2014 ' : '';
        result.push( { id: String( folder.id ), name: `${ prefix }${ folder.name }` } );
        if ( folder.children && folder.children.length > 0 ) {
            result.push( ...flattenFolders( folder.children, depth + 1 ) );
        }
    }
    return result;
}

const folderOptions = computed( () => [
    { id: '', name: 'All Folders' },
    ...flattenFolders( folders.value ),
] );

const allTypeOptions = [
    { id: 'image',    name: 'Images' },
    { id: 'video',    name: 'Videos' },
    { id: 'audio',    name: 'Audio' },
    { id: 'document', name: 'Documents' },
];

const typeFilterOptions = computed( () => {
    const filtered = props.allowedTypes && props.allowedTypes.length > 0
        ? allTypeOptions.filter( ( opt ) => props.allowedTypes!.includes( opt.id as MediaType ) )
        : allTypeOptions;
    return [ { id: '', name: 'All Types' }, ...filtered ];
} );

const selectedCount = computed( () => picker.selectedMedia.value.length );

// Keyboard navigation — skip form controls and interactive elements
function handleKeyDown( e: KeyboardEvent ) {
    const target  = e.target as HTMLElement;
    const tagName = target.tagName.toLowerCase();
    if ( tagName === 'input' || tagName === 'textarea' || tagName === 'select' || target.isContentEditable ) {
        return;
    }
    if ( target.closest( 'button, a, [role="button"], [role="tab"], [role="menuitem"]' ) ) {
        return;
    }

    switch ( e.key ) {
        case 'ArrowRight':
            e.preventDefault();
            picker.focusNext();
            break;
        case 'ArrowLeft':
            e.preventDefault();
            picker.focusPrevious();
            break;
        case 'Enter':
        case ' ':
            if ( picker.focusedIndex.value >= 0 ) {
                e.preventDefault();
                picker.selectFocused();
            }
            break;
    }
}

function handleUploadComplete( media: Media ) {
    if ( ! props.multiSelect ) {
        emit( 'select', [ media ], props.context );
        open.value = false;
    }
}

function handleQueueComplete() {
    if ( props.multiSelect ) {
        activeTab.value = 'library';
        picker.refresh();
    }
}

function handleFolderChange( value: string | number ) {
    picker.setFolderId( value ? Number( value ) : undefined );
}

function handleTypeChange( value: string | number ) {
    picker.setTypeFilter( ( value as string ) || undefined as MediaType | undefined );
}

const tabItems = computed( () => [
    { name: 'library', label: 'Library' },
    ...( props.showUploadTab ? [ { name: 'upload', label: 'Upload' } ] : [] ),
] );
</script>

<template>
    <Teleport to="body">
        <Modal
            v-model:open="open"
            :title="title"
        >
            <div @keydown="handleKeyDown">
                <!-- Tabs -->
                <Tabs
                    v-model:active-tab="activeTab"
                    :tabs="tabItems"
                >
                    <template #library>
                        <div class="mt-4">
                            <!-- Filters -->
                            <div class="flex flex-wrap gap-3 mb-4">
                                <div class="flex-1 min-w-48">
                                    <Input
                                        aria-label="Search media"
                                        placeholder="Search media..."
                                        :model-value="picker.search.value"
                                        @update:model-value="picker.setSearch( $event as string )"
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
                                    :model-value="picker.folderId.value ? String( picker.folderId.value ) : ''"
                                    @update:model-value="handleFolderChange"
                                />
                                <Select
                                    aria-label="Filter by type"
                                    :options="typeFilterOptions"
                                    :model-value="picker.typeFilter.value || ''"
                                    @update:model-value="handleTypeChange"
                                />
                            </div>

                            <!-- Recently used -->
                            <div v-if="picker.recentlyUsed.value.length > 0 && ! picker.search.value" class="mb-4">
                                <p class="text-xs font-medium text-base-content/60 mb-2">Recently Used</p>
                                <div class="flex gap-2 overflow-x-auto pb-2">
                                    <button
                                        v-for="item in picker.recentlyUsed.value"
                                        :key="item.id"
                                        type="button"
                                        :class="cn(
                                            'w-14 h-14 shrink-0 rounded-lg overflow-hidden ring-2 transition-all',
                                            picker.selectedMedia.value.some( ( m ) => m.id === item.id )
                                                ? 'ring-primary'
                                                : 'ring-transparent hover:ring-base-300',
                                        )"
                                        @click="picker.toggleSelect( item )"
                                    >
                                        <img
                                            v-if="item.is_image"
                                            :src="item.url"
                                            :alt="item.alt_text || item.file_name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full bg-base-200 flex items-center justify-center">
                                            <span class="text-xs text-base-content/50">
                                                {{ item.file_name.split( '.' ).pop()?.toUpperCase() }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Media grid -->
                            <MediaGrid
                                :media="picker.media.value"
                                :loading="picker.loading.value"
                                view-mode="grid"
                                :selected-ids="new Set( picker.selectedMedia.value.map( ( m ) => m.id ) )"
                                :focused-index="picker.focusedIndex.value"
                                :pagination="picker.pagination.value"
                                @item-click="picker.toggleSelect( $event )"
                                @item-select="picker.toggleSelect( $event )"
                                @page-change="picker.goToPage( $event )"
                            />

                            <!-- Selection info -->
                            <div v-if="selectedCount > 0" class="mt-3 flex items-center gap-2">
                                <Badge :value="`${ selectedCount } selected`" color="primary" />
                                <Button size="sm" color="ghost" @click="picker.clearSelection()">
                                    Clear selection
                                </Button>
                            </div>
                        </div>
                    </template>

                    <template #upload>
                        <div class="mt-4">
                            <MediaUpload
                                auto-upload
                                @upload-complete="handleUploadComplete"
                                @queue-complete="handleQueueComplete"
                            />
                        </div>
                    </template>
                </Tabs>
            </div>

            <template #actions>
                <Button @click="open = false">Cancel</Button>
                <Button
                    color="primary"
                    :loading="picker.loading.value"
                    @click="picker.confirmSelection()"
                >
                    {{ selectedCount > 0 ? `Select ${ selectedCount } item${ selectedCount !== 1 ? 's' : '' }` : 'Select' }}
                </Button>
            </template>
        </Modal>
    </Teleport>
</template>
