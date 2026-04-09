<!--
  ArtisanPack UI Media Library - FolderManager Component

  Folder tree management with create, rename, delete, and hierarchical organization.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { Button, Input, Textarea, Select, Card, Alert, Modal } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { MediaFolder } from '../types/media';

import { fetchFolders, createFolder, updateFolder, deleteFolder } from '../utils/api';

import FolderNode from './FolderNode.vue';

const props = withDefaults( defineProps<{
    selectedFolderId?: number | null;
}>(), {
    selectedFolderId: null,
} );

const emit = defineEmits<{
    foldersChange: [folders: MediaFolder[]];
    folderSelect: [folderId: number | null];
}>();

const folders   = ref<MediaFolder[]>( [] );
const loading   = ref( true );
const error     = ref<string | null>( null );
const showForm  = ref( false );
const editing   = ref<MediaFolder | null>( null );
const deleting          = ref<MediaFolder | null>( null );
const showDeleteConfirm = ref( false );
const saving            = ref( false );

const form = reactive( {
    name:        '',
    slug:        '',
    description: '',
    parent_id:   null as number | null,
} );

function slugify( name: string ): string {
    return name.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );
}

function flattenFolders( items: MediaFolder[] ): MediaFolder[] {
    const result: MediaFolder[] = [];
    for ( const folder of items ) {
        result.push( folder );
        if ( folder.children && folder.children.length > 0 ) {
            result.push( ...flattenFolders( folder.children ) );
        }
    }
    return result;
}

function getDescendantIds( folderId: number, allFolders: MediaFolder[] ): Set<number> {
    const ids   = new Set<number>();
    const queue = [ folderId ];
    while ( queue.length > 0 ) {
        const parentId = queue.shift()!;
        for ( const folder of allFolders ) {
            if ( folder.parent_id === parentId && ! ids.has( folder.id ) ) {
                ids.add( folder.id );
                queue.push( folder.id );
            }
        }
    }
    return ids;
}

async function loadFolders() {
    loading.value = true;
    try {
        const response = await fetchFolders();
        folders.value  = response.data;
        emit( 'foldersChange', response.data );
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to load folders';
    } finally {
        loading.value = false;
    }
}

onMounted( () => loadFolders() );

function resetForm() {
    form.name        = '';
    form.slug        = '';
    form.description = '';
    form.parent_id   = null;
    editing.value    = null;
    showForm.value   = false;
}

function handleEdit( folder: MediaFolder ) {
    form.name        = folder.name;
    form.slug        = folder.slug;
    form.description = folder.description || '';
    form.parent_id   = folder.parent_id;
    editing.value    = folder;
    showForm.value   = true;
}

async function handleSave() {
    saving.value = true;
    error.value  = null;

    try {
        const data = {
            name:        form.name,
            slug:        form.slug || slugify( form.name ),
            description: form.description || undefined,
            parent_id:   form.parent_id,
        };

        if ( editing.value ) {
            await updateFolder( editing.value.id, data );
        } else {
            await createFolder( data );
        }

        resetForm();
        await loadFolders();
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to save folder';
    } finally {
        saving.value = false;
    }
}

async function handleDelete() {
    if ( ! deleting.value ) {
        return;
    }

    saving.value = true;
    error.value  = null;

    try {
        await deleteFolder( deleting.value.id );
        deleting.value          = null;
        showDeleteConfirm.value = false;
        await loadFolders();
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to delete folder';
    } finally {
        saving.value = false;
    }
}

function handleSelect( id: number | null ) {
    emit( 'folderSelect', props.selectedFolderId === id ? null : id );
}

const allFlat    = computed( () => flattenFolders( folders.value ) );
const excludedIds = computed( () => editing.value ? getDescendantIds( editing.value.id, allFlat.value ) : new Set<number>() );
const parentOptions = computed( () => [
    { id: '', name: 'No parent (root)' },
    ...allFlat.value
        .filter( ( f ) => ! editing.value || ( f.id !== editing.value.id && ! excludedIds.value.has( f.id ) ) )
        .map( ( f ) => ( { id: String( f.id ), name: f.name } ) ),
] );
</script>

<template>
    <Card>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold">Folders</h3>
            <Button size="sm" color="primary" @click="resetForm(); showForm = true">
                <svg class="w-4 h-4 mr-1" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New
            </Button>
        </div>

        <Alert v-if="error" color="error" class="mb-3">{{ error }}</Alert>

        <!-- All media option -->
        <div
            role="treeitem"
            :aria-selected="selectedFolderId === null"
            :class="cn(
                'flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-colors hover:bg-base-200',
                selectedFolderId === null && 'bg-primary/10 text-primary',
            )"
            tabindex="0"
            @click="handleSelect( null )"
            @keydown.enter="handleSelect( null )"
        >
            <svg class="w-4 h-4 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
            </svg>
            <span class="flex-1 text-sm">All Media</span>
        </div>

        <!-- Folder tree -->
        <div class="mt-1" role="tree" aria-label="Folder tree">
            <template v-for="folder in folders.filter( ( f ) => ! f.parent_id )" :key="folder.id">
                <FolderNode
                    :folder="folder"
                    :depth="0"
                    :selected-folder-id="selectedFolderId"
                    @select="handleSelect"
                    @edit="handleEdit"
                    @delete="( f ) => { deleting = f; showDeleteConfirm = true; }"
                />
            </template>
        </div>

        <!-- Create/Edit form -->
        <Teleport to="body">
            <Modal v-if="showForm" v-model:open="showForm" :title="editing ? 'Edit Folder' : 'New Folder'" @update:open="( v ) => { if ( ! v ) resetForm(); }">
                <div class="flex flex-col gap-4">
                    <Input label="Name" v-model="form.name" placeholder="Folder name" @update:model-value="( v ) => { if ( ! editing ) form.slug = slugify( v as string ); }" />
                    <Input label="Slug" v-model="form.slug" placeholder="folder-slug" hint="URL-friendly identifier" />
                    <Textarea label="Description" v-model="form.description" placeholder="Optional description" />
                    <Select label="Parent Folder" :options="parentOptions" :model-value="form.parent_id ? String( form.parent_id ) : ''" @update:model-value="( v ) => form.parent_id = v ? Number( v ) : null" />
                </div>
                <template #actions>
                    <Button @click="resetForm()">Cancel</Button>
                    <Button color="primary" :loading="saving" @click="handleSave()">{{ editing ? 'Update' : 'Create' }}</Button>
                </template>
            </Modal>
        </Teleport>

        <!-- Delete confirmation -->
        <Teleport to="body">
            <Modal v-if="showDeleteConfirm" v-model:open="showDeleteConfirm" :title="'Delete Folder'" @update:open="( v ) => { if ( ! v ) { deleting = null; showDeleteConfirm = false; } }">
                <p>Are you sure you want to delete the folder <strong>{{ deleting?.name }}</strong>? Media items in this folder will be moved to the root level.</p>
                <template #actions>
                    <Button @click="deleting = null; showDeleteConfirm = false">Cancel</Button>
                    <Button color="error" :loading="saving" @click="handleDelete()">Delete</Button>
                </template>
            </Modal>
        </Teleport>
    </Card>
</template>
