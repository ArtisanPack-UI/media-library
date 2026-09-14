<!--
  ArtisanPack UI Media Library - MediaEdit Component

  Metadata editor for media items including title, alt text, caption,
  description, folder assignment, and tag management.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue';
import { Button, Input, Textarea, Select, Alert, Badge, Modal } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media, MediaFolder, MediaTag, MediaUpdatePayload } from '../types/media';

import {
    updateMedia,
    deleteMedia,
    fetchFolders,
    fetchTags,
    suggestMediaAltText,
    suggestMediaTags,
    suggestMediaDescription,
    filenameFallbackAltText,
    type AiDescriptionLength,
} from '../utils/api';

const props = defineProps<{
    media: Media;
}>();

const emit = defineEmits<{
    close: [];
    save: [media: Media];
    delete: [];
}>();

const editOpen         = ref( true );
const showDeleteConfirm = ref( false );
const saving           = ref( false );
const error            = ref<string | null>( null );
const folders          = ref<MediaFolder[]>( [] );
const tags             = ref<MediaTag[]>( [] );
const selectedTagIds   = ref<number[]>( props.media.tags?.map( ( t ) => t.id ) || [] );
const aiBusy               = ref<'alt' | 'tags' | 'description' | null>( null );
const aiNotice             = ref<string | null>( null );
const aiSuggestedTagIds    = ref<number[]>( [] );
const aiDescriptionLength  = ref<AiDescriptionLength>( 'medium' );

const form = reactive( {
    title:       props.media.title || '',
    alt_text:    props.media.alt_text || '',
    caption:     props.media.caption || '',
    description: props.media.description || '',
    folder_id:   props.media.folder?.id ?? null,
} );

// Sync form state when media prop changes
watch( () => props.media.id, () => {
    form.title       = props.media.title || '';
    form.alt_text    = props.media.alt_text || '';
    form.caption     = props.media.caption || '';
    form.description = props.media.description || '';
    form.folder_id   = props.media.folder?.id ?? null;
    selectedTagIds.value = props.media.tags?.map( ( t ) => t.id ) || [];
} );

onMounted( () => {
    Promise.all( [ fetchFolders(), fetchTags() ] )
        .then( ( [ foldersRes, tagsRes ] ) => {
            folders.value = foldersRes.data;
            tags.value    = tagsRes.data;
        } )
        .catch( () => { /* non-critical */ } );
} );

function toggleTag( tagId: number ) {
    if ( selectedTagIds.value.includes( tagId ) ) {
        selectedTagIds.value = selectedTagIds.value.filter( ( id ) => id !== tagId );
    } else {
        selectedTagIds.value = [ ...selectedTagIds.value, tagId ];
    }
}

async function handleSuggestAltText() {
    aiBusy.value   = 'alt';
    aiNotice.value = null;
    try {
        const result  = await suggestMediaAltText( props.media.id );
        const altText = result.alt_text?.trim() ||
            filenameFallbackAltText( props.media.file_name, props.media.title );
        form.alt_text = altText;
        if ( ! result.alt_text?.trim() ) {
            aiNotice.value = 'AI could not confidently describe this image; a filename-based placeholder was inserted.';
        }
    } catch ( err ) {
        aiNotice.value = err instanceof Error ? err.message : 'AI request failed';
    } finally {
        aiBusy.value = null;
    }
}

async function handleSuggestTags( allowNew: boolean ) {
    aiBusy.value   = 'tags';
    aiNotice.value = null;
    try {
        const result   = await suggestMediaTags( props.media.id, { allowNew } );
        const nameToId = new Map<string, number>();
        tags.value.forEach( ( t ) => nameToId.set( t.name.toLowerCase(), t.id ) );
        const nextSelected = new Set( selectedTagIds.value );
        const nextAi: number[] = [];

        for ( const name of result.tags ) {
            const id = nameToId.get( name.toLowerCase() );
            if ( id !== undefined ) {
                nextSelected.add( id );
                nextAi.push( id );
            }
        }

        if ( allowNew && result.new_tags.length > 0 ) {
            aiNotice.value = `AI proposed ${ result.new_tags.length } new tag(s): ${ result.new_tags.join( ', ' ) }. Create them from the tag manager to attach.`;
        }

        selectedTagIds.value    = Array.from( nextSelected );
        aiSuggestedTagIds.value = nextAi;
    } catch ( err ) {
        aiNotice.value = err instanceof Error ? err.message : 'AI request failed';
    } finally {
        aiBusy.value = null;
    }
}

async function handleSuggestDescription() {
    aiBusy.value   = 'description';
    aiNotice.value = null;
    try {
        const result = await suggestMediaDescription( props.media.id, aiDescriptionLength.value );
        if ( result.description ) {
            form.description = result.description;
        } else {
            aiNotice.value = 'AI did not produce a description for this image.';
        }
    } catch ( err ) {
        aiNotice.value = err instanceof Error ? err.message : 'AI request failed';
    } finally {
        aiBusy.value = null;
    }
}

async function handleSave() {
    saving.value = true;
    error.value  = null;

    try {
        const payload: MediaUpdatePayload = {
            title:       form.title,
            alt_text:    form.alt_text,
            caption:     form.caption,
            description: form.description,
            folder_id:   form.folder_id,
            tags:        selectedTagIds.value,
        };

        const response = await updateMedia( props.media.id, payload );
        emit( 'save', response.data );
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to save';
    } finally {
        saving.value = false;
    }
}

async function handleDelete() {
    saving.value = true;
    error.value  = null;

    try {
        await deleteMedia( props.media.id );
        emit( 'delete' );
    } catch ( err ) {
        error.value = err instanceof Error ? err.message : 'Failed to delete';
    } finally {
        saving.value         = false;
        showDeleteConfirm.value = false;
    }
}

function handleClose() {
    editOpen.value = false;
    emit( 'close' );
}

const folderOptions = () => [
    { id: '', name: 'No folder' },
    ...folders.value.map( ( f ) => ( { id: String( f.id ), name: f.name } ) ),
];
</script>

<template>
    <Teleport to="body">
        <Modal
            v-model:open="editOpen"
            title="Edit Media"
            @update:open="( v ) => { if ( ! v ) handleClose(); }"
        >
            <Alert v-if="error" color="error" class="mb-4">{{ error }}</Alert>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Preview -->
                <div>
                    <img
                        v-if="media.is_image"
                        :src="media.url"
                        :alt="media.alt_text || media.file_name"
                        class="w-full rounded-lg"
                    />
                    <div v-else class="w-full aspect-square bg-base-200 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-base-content/30 mb-2" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-base-content/50">{{ media.file_name }}</p>
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-base-content/60 space-y-1">
                        <p><strong>File:</strong> {{ media.file_name }}</p>
                        <p><strong>Type:</strong> {{ media.mime_type }}</p>
                        <p><strong>Size:</strong> {{ media.human_size }}</p>
                        <p v-if="media.is_image && media.width && media.height">
                            <strong>Dimensions:</strong> {{ media.width }} &times; {{ media.height }}
                        </p>
                        <p><strong>URL:</strong> <code class="text-xs break-all">{{ media.url }}</code></p>
                    </div>
                </div>

                <!-- Form -->
                <div class="flex flex-col gap-4">
                    <Input label="Title" v-model="form.title" placeholder="Media title" />
                    <div v-if="media.is_image">
                        <Input
                            label="Alt Text"
                            v-model="form.alt_text"
                            placeholder="Describe this image for accessibility"
                            hint="Important for accessibility and SEO"
                        />
                        <div class="mt-2 flex items-center gap-3">
                            <Button
                                size="sm"
                                :loading="aiBusy === 'alt'"
                                :disabled="aiBusy !== null"
                                @click="handleSuggestAltText()"
                            >
                                ✨ Suggest with AI
                            </Button>
                        </div>
                    </div>
                    <Input label="Caption" v-model="form.caption" placeholder="Optional caption" />
                    <div>
                        <Textarea label="Description" v-model="form.description" placeholder="Optional description" />
                        <div v-if="media.is_image" class="mt-2 flex items-center gap-3">
                            <Select
                                :options="[
                                    { value: 'short', label: 'Short' },
                                    { value: 'medium', label: 'Medium' },
                                    { value: 'long', label: 'Long' },
                                ]"
                                :model-value="aiDescriptionLength"
                                @update:model-value="( v ) => aiDescriptionLength = v as AiDescriptionLength"
                                class="w-32"
                            />
                            <Button
                                size="sm"
                                :loading="aiBusy === 'description'"
                                :disabled="aiBusy !== null"
                                @click="handleSuggestDescription()"
                            >
                                ✨ Describe with AI
                            </Button>
                        </div>
                    </div>
                    <Select
                        label="Folder"
                        :options="folderOptions()"
                        :model-value="form.folder_id ? String( form.folder_id ) : ''"
                        @update:model-value="( v ) => form.folder_id = v ? Number( v ) : null"
                    />

                    <!-- Tags -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="label"><span class="label-text">Tags</span></label>
                            <div v-if="media.is_image" class="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    :loading="aiBusy === 'tags'"
                                    :disabled="aiBusy !== null"
                                    @click="handleSuggestTags( false )"
                                >
                                    ✨ Suggest tags
                                </Button>
                                <Button
                                    size="sm"
                                    :disabled="aiBusy !== null"
                                    title="Allow the AI to propose brand-new tags outside the existing taxonomy"
                                    @click="handleSuggestTags( true )"
                                >
                                    + Allow new
                                </Button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in tags"
                                :key="tag.id"
                                type="button"
                                class="cursor-pointer rounded-full hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :aria-pressed="selectedTagIds.includes( tag.id )"
                                @click="toggleTag( tag.id )"
                            >
                                <Badge
                                    :value="aiSuggestedTagIds.includes( tag.id ) ? `${ tag.name } (AI)` : tag.name"
                                    :color="selectedTagIds.includes( tag.id ) ? 'primary' : 'neutral'"
                                />
                            </button>
                            <p v-if="tags.length === 0" class="text-sm text-base-content/50">No tags available</p>
                        </div>
                        <p v-if="aiNotice" class="mt-2 text-xs text-info">{{ aiNotice }}</p>
                    </div>
                </div>
            </div>

            <template #actions>
                <Button color="error" @click="showDeleteConfirm = true">Delete</Button>
                <div class="flex-1" />
                <Button @click="handleClose()">Cancel</Button>
                <Button color="primary" :loading="saving" @click="handleSave()">Save</Button>
            </template>
        </Modal>

        <!-- Delete confirmation -->
        <Modal
            v-if="showDeleteConfirm"
            v-model:open="showDeleteConfirm"
            title="Delete Media"
        >
            <p>
                Are you sure you want to delete <strong>{{ media.title || media.file_name }}</strong>?
                This will remove the file and all generated thumbnails. This action cannot be undone.
            </p>
            <template #actions>
                <Button @click="showDeleteConfirm = false">Cancel</Button>
                <Button color="error" :loading="saving" @click="handleDelete()">Delete permanently</Button>
            </template>
        </Modal>
    </Teleport>
</template>
