<!--
  ArtisanPack UI Media Library - MediaGrid Component

  Responsive grid/list display for media items with selection and pagination.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { watch } from 'vue';
import { Grid, Pagination, EmptyState, Loading } from '@artisanpack-ui/vue';

import type { Media, PaginationMeta } from '../types/media';

import MediaItem from './MediaItem.vue';

const props = withDefaults( defineProps<{
    media: Media[];
    loading?: boolean;
    viewMode?: 'grid' | 'list';
    bulkSelectMode?: boolean;
    selectedIds?: Set<number>;
    focusedIndex?: number;
    pagination?: PaginationMeta | null;
}>(), {
    loading:        false,
    viewMode:       'grid',
    bulkSelectMode: false,
    selectedIds:    () => new Set(),
    focusedIndex:   -1,
    pagination:     null,
} );

const emit = defineEmits<{
    itemClick: [media: Media];
    itemSelect: [media: Media];
    pageChange: [page: number];
}>();

const currentPage = defineModel<number>( 'currentPage', { default: props.pagination?.current_page ?? 1 } );

watch( () => props.pagination?.current_page, ( newPage ) => {
    if ( newPage !== undefined && newPage !== currentPage.value ) {
        currentPage.value = newPage;
    }
} );

function handlePageChange( page: number ) {
    currentPage.value = page;
    emit( 'pageChange', page );
}
</script>

<template>
    <div v-if="loading" class="flex items-center justify-center py-12">
        <Loading />
    </div>

    <EmptyState
        v-else-if="media.length === 0"
        heading="No media found"
        description="Try adjusting your filters or upload new media."
    />

    <div v-else>
        <!-- Grid view -->
        <Grid
            v-if="viewMode === 'grid'"
            :cols="2"
            :cols-sm="3"
            :cols-md="4"
            :cols-lg="6"
            :gap="4"
            role="listbox"
            aria-label="Media items"
        >
            <MediaItem
                v-for="( item, index ) in media"
                :key="item.id"
                :media="item"
                :selected="selectedIds.has( item.id )"
                :bulk-select-mode="bulkSelectMode"
                view-mode="grid"
                :focused="index === focusedIndex"
                @click="emit( 'itemClick', $event )"
                @select="emit( 'itemSelect', $event )"
            />
        </Grid>

        <!-- List view -->
        <div v-else class="flex flex-col gap-1" role="listbox" aria-label="Media items">
            <MediaItem
                v-for="( item, index ) in media"
                :key="item.id"
                :media="item"
                :selected="selectedIds.has( item.id )"
                :bulk-select-mode="bulkSelectMode"
                view-mode="list"
                :focused="index === focusedIndex"
                @click="emit( 'itemClick', $event )"
                @select="emit( 'itemSelect', $event )"
            />
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-center">
            <Pagination
                v-model:current-page="currentPage"
                :total-pages="pagination.last_page"
                @update:current-page="handlePageChange"
            />
        </div>
    </div>
</template>
