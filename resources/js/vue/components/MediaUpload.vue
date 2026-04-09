<!--
  ArtisanPack UI Media Library - MediaUpload Component

  Drag-and-drop file uploader with multi-file support, upload progress
  indicators, and file type/size validation.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref } from 'vue';
import { Button, Alert, Progress, Card } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media } from '../types/media';

import { useMediaUpload } from '../composables/useMediaUpload';
import type { UseMediaUploadOptions } from '../composables/useMediaUpload';

const props = withDefaults( defineProps<UseMediaUploadOptions & {
    label?: string;
    accept?: string;
    autoUpload?: boolean;
}>(), {
    label:      'Drop files here or click to browse',
    autoUpload: false,
} );

const emit = defineEmits<{
    uploadComplete: [media: Media];
    queueComplete: [results: Media[]];
}>();

const upload = useMediaUpload( {
    ...props,
    onUploadComplete: ( media ) => emit( 'uploadComplete', media ),
    onQueueComplete:  ( results ) => emit( 'queueComplete', results ),
} );

const fileInputRef = ref<HTMLInputElement | null>( null );

function handleFileSelect( e: Event ) {
    const input = e.target as HTMLInputElement;
    if ( input.files && input.files.length > 0 ) {
        upload.addFiles( input.files );

        if ( props.autoUpload ) {
            setTimeout( () => upload.startUpload(), 0 );
        }
    }
    input.value = '';
}

function handleBrowseClick() {
    fileInputRef.value?.click();
}

function handleKeyDown( e: KeyboardEvent ) {
    if ( e.key === 'Enter' || e.key === ' ' ) {
        e.preventDefault();
        handleBrowseClick();
    }
}
</script>

<template>
    <div>
        <!-- Drop zone -->
        <div
            role="button"
            tabindex="0"
            :aria-label="label"
            :class="cn(
                'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                'hover:border-primary hover:bg-primary/5',
                upload.isDragging.value && 'border-primary bg-primary/10',
                ! upload.isDragging.value && 'border-base-300',
            )"
            v-on="upload.dropZoneHandlers"
            @click="handleBrowseClick"
            @keydown="handleKeyDown"
        >
            <svg class="w-10 h-10 mx-auto text-base-content/30 mb-3" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            <p class="text-sm text-base-content/60">{{ label }}</p>
            <p class="text-xs text-base-content/40 mt-1">Supports images, videos, audio, and documents</p>

            <input
                ref="fileInputRef"
                type="file"
                multiple
                :accept="accept"
                class="hidden"
                aria-hidden="true"
                @change="handleFileSelect"
            />
        </div>

        <!-- Validation errors -->
        <div v-if="upload.validationErrors.value.length > 0" class="mt-3 flex flex-col gap-2">
            <Alert v-for="( err, i ) in upload.validationErrors.value" :key="i" color="error">
                {{ err }}
            </Alert>
        </div>

        <!-- Upload queue -->
        <Card v-if="upload.queue.value.length > 0" class="mt-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium">
                    {{ upload.isUploading.value ? 'Uploading...' : `${ upload.queue.value.length } file${ upload.queue.value.length !== 1 ? 's' : '' } queued` }}
                    <template v-if="upload.queue.value.filter( q => q.status === 'complete' ).length > 0">
                        ({{ upload.queue.value.filter( q => q.status === 'complete' ).length }} complete)
                    </template>
                </p>
                <div class="flex gap-2">
                    <Button
                        v-if="upload.queue.value.filter( q => q.status === 'pending' ).length > 0 && ! upload.isUploading.value"
                        size="sm"
                        color="primary"
                        @click="upload.startUpload()"
                    >
                        Upload {{ upload.queue.value.filter( q => q.status === 'pending' ).length }} file{{ upload.queue.value.filter( q => q.status === 'pending' ).length !== 1 ? 's' : '' }}
                    </Button>
                    <Button
                        v-if="! upload.isUploading.value"
                        size="sm"
                        color="ghost"
                        @click="upload.clearQueue()"
                    >
                        Clear
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-2 max-h-60 overflow-y-auto">
                <div
                    v-for="item in upload.queue.value"
                    :key="item.id"
                    class="flex items-center gap-3 p-3 bg-base-200 rounded-lg"
                >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ item.file.name }}</p>
                        <p class="text-xs text-base-content/60">{{ ( item.file.size / 1024 ).toFixed( 1 ) }} KB</p>

                        <Progress v-if="item.status === 'uploading'" :value="item.progress" color="primary" class="mt-1" />
                        <p v-if="item.status === 'error'" class="text-xs text-error mt-1">{{ item.error }}</p>
                        <p v-if="item.status === 'complete'" class="text-xs text-success mt-1">Uploaded successfully</p>
                    </div>

                    <Button
                        v-if="item.status !== 'uploading'"
                        size="sm"
                        color="ghost"
                        :disabled="upload.isUploading.value"
                        :aria-label="`Remove ${ item.file.name }`"
                        @click="upload.removeFromQueue( item.id )"
                    >
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </Button>
                </div>
            </div>
        </Card>
    </div>
</template>
