<!--
  ArtisanPack UI Media Library - MediaItem Component

  Individual media card with thumbnail, metadata overlay, and selection state.
  Supports grid and list view modes.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { Badge, Tooltip } from '@artisanpack-ui/vue';
import { cn } from '@artisanpack-ui/tokens';

import type { Media } from '../types/media';

const props = withDefaults( defineProps<{
    media: Media;
    selected?: boolean;
    bulkSelectMode?: boolean;
    viewMode?: 'grid' | 'list';
    focused?: boolean;
}>(), {
    selected:       false,
    bulkSelectMode: false,
    viewMode:       'grid',
    focused:        false,
} );

const emit = defineEmits<{
    click: [media: Media];
    select: [media: Media];
}>();

function getMediaTypeIcon( media: Media ): string {
    if ( media.is_video ) {
        return 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z';
    }
    if ( media.is_audio ) {
        return 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3';
    }
    return 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z';
}

function formatDuration( seconds: number | null ): string {
    if ( seconds === null || seconds === undefined ) {
        return '';
    }
    const mins = Math.floor( seconds / 60 );
    const secs = Math.floor( seconds % 60 );
    return `${ mins }:${ secs.toString().padStart( 2, '0' ) }`;
}

function handleClick( e: MouseEvent ) {
    if ( props.bulkSelectMode ) {
        e.preventDefault();
        emit( 'select', props.media );
    } else {
        emit( 'click', props.media );
    }
}

function handleKeyDown( e: KeyboardEvent ) {
    if ( e.key === 'Enter' || e.key === ' ' ) {
        e.preventDefault();
        if ( props.bulkSelectMode ) {
            emit( 'select', props.media );
        } else {
            emit( 'click', props.media );
        }
    }
}
</script>

<template>
    <!-- List view -->
    <div
        v-if="viewMode === 'list'"
        role="option"
        :aria-selected="selected"
        tabindex="0"
        :class="cn(
            'flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors',
            'hover:bg-base-200',
            selected && 'bg-primary/10 ring-2 ring-primary',
            focused && 'ring-2 ring-info',
        )"
        @click="handleClick"
        @keydown="handleKeyDown"
    >
        <input
            v-if="bulkSelectMode || selected"
            type="checkbox"
            class="checkbox checkbox-primary checkbox-sm"
            :checked="selected"
            :aria-label="`Select ${ media.title || media.file_name }`"
            @change="emit( 'select', media )"
            @click.stop
        />

        <div class="w-12 h-12 shrink-0 rounded overflow-hidden bg-base-200 flex items-center justify-center">
            <img
                v-if="media.is_image"
                :src="media.url"
                :alt="media.alt_text || media.file_name"
                class="w-full h-full object-cover"
            />
            <svg v-else class="w-6 h-6 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" :d="getMediaTypeIcon( media )" />
            </svg>
        </div>

        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ media.title || media.file_name }}</p>
            <p class="text-xs text-base-content/60">
                {{ media.human_size }}
                <template v-if="media.is_image && media.width && media.height">
                    &middot; {{ media.width }}&times;{{ media.height }}
                </template>
                <template v-if="media.is_video && media.duration">
                    &middot; {{ formatDuration( media.duration ) }}
                </template>
            </p>
        </div>

        <Badge
            :value="media.is_image ? 'Image' : media.is_video ? 'Video' : media.is_audio ? 'Audio' : 'Doc'"
            :color="media.is_image ? 'info' : media.is_video ? 'accent' : media.is_audio ? 'warning' : 'neutral'"
        />
    </div>

    <!-- Grid view -->
    <div
        v-else
        role="option"
        :aria-selected="selected"
        tabindex="0"
        :class="cn(
            'group relative rounded-lg overflow-hidden cursor-pointer transition-all',
            'ring-2 ring-transparent hover:ring-base-300',
            selected && 'ring-primary ring-2',
            focused && 'ring-info ring-2',
        )"
        @click="handleClick"
        @keydown="handleKeyDown"
    >
        <div class="aspect-square bg-base-200 flex items-center justify-center overflow-hidden">
            <img
                v-if="media.is_image"
                :src="media.url"
                :alt="media.alt_text || media.file_name"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <svg v-else class="w-12 h-12 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" :d="getMediaTypeIcon( media )" />
            </svg>
        </div>

        <div v-if="bulkSelectMode || selected" class="absolute top-2 left-2">
            <input
                type="checkbox"
                class="checkbox checkbox-primary checkbox-sm bg-base-100"
                :checked="selected"
                :aria-label="`Select ${ media.title || media.file_name }`"
                @change="emit( 'select', media )"
                @click.stop
            />
        </div>

        <div v-if="media.is_video && media.duration" class="absolute bottom-14 right-2">
            <Badge :value="formatDuration( media.duration )" color="neutral" />
        </div>

        <div class="p-2">
            <Tooltip :text="media.title || media.file_name">
                <p class="text-xs font-medium truncate">{{ media.title || media.file_name }}</p>
            </Tooltip>
            <p class="text-xs text-base-content/50 mt-0.5">{{ media.human_size }}</p>
        </div>
    </div>
</template>
