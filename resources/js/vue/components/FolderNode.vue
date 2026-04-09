<!--
  ArtisanPack UI Media Library - FolderNode Component

  Recursive folder tree node used by FolderManager to render
  hierarchical folder structures with indentation based on depth.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { cn } from '@artisanpack-ui/tokens';

import type { MediaFolder } from '../types/media';

const props = defineProps<{
    folder: MediaFolder;
    depth: number;
    selectedFolderId?: number | null;
}>();

const emit = defineEmits<{
    select: [folderId: number | null];
    edit: [folder: MediaFolder];
    delete: [folder: MediaFolder];
}>();

function handleSelect( id: number | null ) {
    emit( 'select', id );
}

function handleEdit( folder: MediaFolder ) {
    emit( 'edit', folder );
}

function handleDelete( folder: MediaFolder ) {
    emit( 'delete', folder );
}
</script>

<template>
    <div
        role="treeitem"
        :aria-selected="selectedFolderId === folder.id"
        :class="cn(
            'group flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-colors hover:bg-base-200',
            selectedFolderId === folder.id && 'bg-primary/10 text-primary',
        )"
        :style="{ paddingLeft: `${ ( depth * 1 ) + 0.75 }rem` }"
        tabindex="0"
        @click="handleSelect( folder.id )"
        @keydown.enter="handleSelect( folder.id )"
    >
        <svg class="w-4 h-4 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
        </svg>
        <span class="flex-1 text-sm truncate">{{ folder.name }}</span>
        <span v-if="folder.media_count !== undefined" class="text-xs text-base-content/40">{{ folder.media_count }}</span>

        <div class="flex gap-1 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100">
            <button type="button" class="btn btn-ghost btn-xs" :aria-label="`Edit ${ folder.name }`" @click.stop="handleEdit( folder )">
                <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </button>
            <button type="button" class="btn btn-ghost btn-xs text-error" :aria-label="`Delete ${ folder.name }`" @click.stop="handleDelete( folder )">
                <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Recursive children -->
    <template v-if="folder.children && folder.children.length > 0">
        <div role="group">
            <FolderNode
                v-for="child in folder.children"
                :key="child.id"
                :folder="child"
                :depth="depth + 1"
                :selected-folder-id="selectedFolderId"
                @select="handleSelect"
                @edit="handleEdit"
                @delete="handleDelete"
            />
        </div>
    </template>
</template>
