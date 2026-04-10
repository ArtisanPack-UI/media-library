<!--
  ArtisanPack UI Media Library - MediaPicker Component

  Lightweight inline media selector for embedding in forms.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media, MediaType } from '../types/media';

import MediaModal from './MediaModal.vue';

const props = withDefaults( defineProps<{
    multiSelect?: boolean;
    maxSelections?: number;
    allowedTypes?: MediaType[];
    context?: string;
    label?: string;
    hint?: string;
    error?: string;
}>(), {
    multiSelect: false,
    context:     'default',
} );

const modelValue = defineModel<Media[]>( { default: () => [] } );
const modalOpen  = ref( false );

function handleSelect( media: Media[] ) {
    modelValue.value = media;
}

function handleRemove( mediaId: number ) {
    modelValue.value = modelValue.value.filter( ( m ) => m.id !== mediaId );
}
</script>

<template>
    <div class="form-control">
        <label v-if="label" class="label">
            <span class="label-text">{{ label }}</span>
        </label>

        <!-- Selected media preview -->
        <div v-if="modelValue.length > 0" class="flex flex-wrap gap-2 mb-2">
            <div
                v-for="media in modelValue"
                :key="media.id"
                class="relative group w-20 h-20 rounded-lg overflow-hidden ring-1 ring-base-300"
            >
                <img
                    v-if="media.is_image"
                    :src="media.url"
                    :alt="media.alt_text || media.file_name"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full bg-base-200 flex items-center justify-center">
                    <span class="text-xs text-base-content/50 text-center px-1">
                        {{ media.file_name.split( '.' ).pop()?.toUpperCase() }}
                    </span>
                </div>

                <button
                    type="button"
                    class="absolute top-1 right-1 w-5 h-5 bg-error text-error-content rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 focus:opacity-100 focus-visible:opacity-100 transition-opacity"
                    :aria-label="`Remove ${ media.title || media.file_name }`"
                    @click="handleRemove( media.id )"
                >
                    <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Select button -->
        <Button
            :color="modelValue.length > 0 ? 'ghost' : 'neutral'"
            size="sm"
            @click="modalOpen = true"
        >
            <svg class="w-4 h-4 mr-1" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
            </svg>
            {{ modelValue.length > 0 ? ( multiSelect ? 'Change selection' : 'Change media' ) : 'Select media' }}
        </Button>

        <label v-if="hint && ! error" class="label">
            <span class="label-text-alt text-base-content/60">{{ hint }}</span>
        </label>
        <label v-if="error" class="label">
            <span class="label-text-alt text-error">{{ error }}</span>
        </label>

        <!-- Media modal -->
        <MediaModal
            v-model:open="modalOpen"
            :multi-select="multiSelect"
            :max-selections="maxSelections"
            :allowed-types="allowedTypes"
            :context="context"
            @select="handleSelect"
        />
    </div>
</template>
