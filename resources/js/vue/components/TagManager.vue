<!--
  ArtisanPack UI Media Library - TagManager Component

  Tag management with create, rename, delete, and attach/detach support.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Button, Input, Textarea, Card, Alert, Badge, Modal } from '@artisanpack-ui/vue';

import type { MediaTag } from '../types/media';

import { fetchTags, createTag, updateTag, deleteTag } from '../utils/api';

const props = withDefaults( defineProps<{
    selectedTag?: string | null;
}>(), {
    selectedTag: null,
} );

const emit = defineEmits<{
    tagsChange: [tags: MediaTag[]];
    tagSelect: [tagSlug: string | null];
}>();

const tags       = ref<MediaTag[]>( [] );
const loading    = ref( true );
const error      = ref<string | null>( null );
const showForm   = ref( false );
const editing    = ref<MediaTag | null>( null );
const deleting          = ref<MediaTag | null>( null );
const showDeleteConfirm = ref( false );
const saving            = ref( false );

const form = reactive( {
    name:        '',
    slug:        '',
    description: '',
} );

function slugify( name: string ): string {
    return name.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );
}

async function loadTags() {
    loading.value = true;
    try {
        const response = await fetchTags();
        tags.value     = response.data;
        emit( 'tagsChange', response.data );
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to load tags';
    } finally {
        loading.value = false;
    }
}

onMounted( () => loadTags() );

function resetForm() {
    form.name        = '';
    form.slug        = '';
    form.description = '';
    editing.value    = null;
    showForm.value   = false;
}

function handleEdit( tag: MediaTag ) {
    form.name        = tag.name;
    form.slug        = tag.slug;
    form.description = tag.description || '';
    editing.value    = tag;
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
        };

        if ( editing.value ) {
            await updateTag( editing.value.id, data );
        } else {
            await createTag( data );
        }

        resetForm();
        await loadTags();
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to save tag';
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
        const deletedSlug = deleting.value.slug;
        await deleteTag( deleting.value.id );
        deleting.value          = null;
        showDeleteConfirm.value = false;
        if ( props.selectedTag === deletedSlug ) {
            emit( 'tagSelect', null );
        }
        await loadTags();
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to delete tag';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Card>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold">Tags</h3>
            <Button size="sm" color="primary" @click="resetForm(); showForm = true">
                <svg class="w-4 h-4 mr-1" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New
            </Button>
        </div>

        <Alert v-if="error" color="error" class="mb-3">{{ error }}</Alert>

        <!-- Tag list -->
        <div class="flex flex-wrap gap-2">
            <button type="button" :aria-pressed="selectedTag === null" @click="emit( 'tagSelect', null )">
                <Badge value="All" :color="selectedTag === null ? 'primary' : 'neutral'" />
            </button>

            <div v-for="tag in tags" :key="tag.id" class="group relative inline-flex" tabindex="0">
                <button type="button" :aria-pressed="selectedTag === tag.slug" @click="emit( 'tagSelect', tag.slug )">
                    <Badge
                        :value="`${ tag.name }${ tag.media_count !== undefined ? ` (${ tag.media_count })` : '' }`"
                        :color="selectedTag === tag.slug ? 'primary' : 'neutral'"
                    />
                </button>

                <div class="absolute -top-1 -right-1 invisible flex gap-0.5 group-hover:visible group-focus-within:visible">
                    <button type="button" class="w-4 h-4 rounded-full bg-base-300 flex items-center justify-center" :aria-label="`Edit ${ tag.name }`" @click="handleEdit( tag )">
                        <svg class="w-2.5 h-2.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </button>
                    <button type="button" class="w-4 h-4 rounded-full bg-error text-error-content flex items-center justify-center" :aria-label="`Delete ${ tag.name }`" @click="deleting = tag; showDeleteConfirm = true">
                        <svg class="w-2.5 h-2.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit form -->
        <Teleport to="body">
            <Modal v-if="showForm" v-model:open="showForm" :title="editing ? 'Edit Tag' : 'New Tag'" @update:open="( v ) => { if ( ! v ) resetForm(); }">
                <div class="flex flex-col gap-4">
                    <Input label="Name" v-model="form.name" placeholder="Tag name" @update:model-value="( v ) => { if ( ! editing ) form.slug = slugify( v as string ); }" />
                    <Input label="Slug" v-model="form.slug" placeholder="tag-slug" hint="URL-friendly identifier" />
                    <Textarea label="Description" v-model="form.description" placeholder="Optional description" />
                </div>
                <template #actions>
                    <Button @click="resetForm()">Cancel</Button>
                    <Button color="primary" :loading="saving" @click="handleSave()">{{ editing ? 'Update' : 'Create' }}</Button>
                </template>
            </Modal>
        </Teleport>

        <!-- Delete confirmation -->
        <Teleport to="body">
            <Modal v-if="showDeleteConfirm" v-model:open="showDeleteConfirm" title="Delete Tag" @update:open="( v ) => { if ( ! v ) { deleting = null; showDeleteConfirm = false; } }">
                <p>Are you sure you want to delete the tag <strong>{{ deleting?.name }}</strong>? It will be detached from all media items.</p>
                <template #actions>
                    <Button @click="deleting = null; showDeleteConfirm = false">Cancel</Button>
                    <Button color="error" :loading="saving" @click="handleDelete()">Delete</Button>
                </template>
            </Modal>
        </Teleport>
    </Card>
</template>
