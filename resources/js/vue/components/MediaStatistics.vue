<!--
  ArtisanPack UI Media Library - MediaStatistics Component

  KPI dashboard with file count, storage usage, type breakdown,
  and sparkline charts for recent upload trends.

  @package ArtisanPackUI\MediaLibrary
  @since   1.2.0
-->

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Card, Stat, Badge, Progress, Loading, Sparkline, Alert } from '@artisanpack-ui/vue';

import type { Media, MediaType } from '../types/media';

import { fetchMedia } from '../utils/api';

interface MediaStats {
    totalCount: number;
    totalSize: number;
    totalSizeFormatted: string;
    byType: Record<MediaType, { count: number; size: number; sizeFormatted: string }>;
    recentUploads: number;
    dailyUploads: number[];
    detailsUnavailable: boolean;
}

const props = withDefaults( defineProps<{
    recentDays?: number;
}>(), {
    recentDays: 30,
} );

const stats   = ref<MediaStats | null>( null );
const loading = ref( true );
const error   = ref<string | null>( null );

function formatBytes( bytes: number ): string {
    if ( bytes === 0 ) {
        return '0 B';
    }
    const units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
    const i     = Math.floor( Math.log( bytes ) / Math.log( 1024 ) );
    const size  = bytes / Math.pow( 1024, i );
    return `${ size.toFixed( i > 0 ? 1 : 0 ) } ${ units[i] }`;
}

const typeColors: Record<MediaType, string> = {
    image:    'info',
    video:    'accent',
    audio:    'warning',
    document: 'neutral',
};

onMounted( async () => {
    loading.value = true;

    try {
        const probe      = await fetchMedia( { per_page: 1 } );
        const totalCount = probe.meta.total;
        const SAFE_THRESHOLD       = 2000;
        const detailsUnavailable   = totalCount > SAFE_THRESHOLD;

        let allMedia: Media[];
        if ( detailsUnavailable ) {
            allMedia = [];
        } else {
            const response = await fetchMedia( { per_page: totalCount || 1 } );
            allMedia = response.data;
        }

        const byType: MediaStats['byType'] = {
            image:    { count: 0, size: 0, sizeFormatted: '' },
            video:    { count: 0, size: 0, sizeFormatted: '' },
            audio:    { count: 0, size: 0, sizeFormatted: '' },
            document: { count: 0, size: 0, sizeFormatted: '' },
        };

        let totalSize = 0;

        for ( const media of allMedia ) {
            totalSize += media.file_size;
            if ( media.is_image ) { byType.image.count += 1; byType.image.size += media.file_size; }
            else if ( media.is_video ) { byType.video.count += 1; byType.video.size += media.file_size; }
            else if ( media.is_audio ) { byType.audio.count += 1; byType.audio.size += media.file_size; }
            else { byType.document.count += 1; byType.document.size += media.file_size; }
        }

        for ( const type of Object.keys( byType ) as MediaType[] ) {
            byType[type].sizeFormatted = formatBytes( byType[type].size );
        }

        let recentUploads = 0;
        let dailyUploads: number[] = [];

        if ( detailsUnavailable ) {
            recentUploads = 0;
            dailyUploads  = [];
        } else {
            const cutoff = new Date();
            cutoff.setDate( cutoff.getDate() - props.recentDays );
            recentUploads = allMedia.filter( ( m ) => m.created_at && new Date( m.created_at ) >= cutoff ).length;

            for ( let d = props.recentDays - 1; d >= 0; d-- ) {
                const dayStart = new Date();
                dayStart.setDate( dayStart.getDate() - d );
                dayStart.setHours( 0, 0, 0, 0 );
                const dayEnd = new Date( dayStart );
                dayEnd.setDate( dayEnd.getDate() + 1 );
                dailyUploads.push( allMedia.filter( ( m ) => m.created_at && new Date( m.created_at ) >= dayStart && new Date( m.created_at ) < dayEnd ).length );
            }
        }

        stats.value = {
            totalCount:         detailsUnavailable ? totalCount : allMedia.length,
            totalSize,
            totalSizeFormatted: detailsUnavailable ? 'N/A' : formatBytes( totalSize ),
            byType,
            recentUploads,
            dailyUploads,
            detailsUnavailable,
        };
    } catch ( err ) {
        stats.value = null;
        error.value = err instanceof Error ? err.message : 'Failed to load statistics';
    } finally {
        loading.value = false;
    }
} );
</script>

<template>
    <div v-if="loading" class="flex items-center justify-center py-12">
        <Loading />
    </div>

    <Alert v-else-if="error" color="error">{{ error }}</Alert>

    <div v-else-if="stats" class="flex flex-col gap-4">
        <!-- KPI Stats -->
        <div class="stats shadow w-full">
            <Stat title="Total Files" :value="stats.totalCount.toLocaleString()" description="All media items" />
            <Stat title="Storage Used" :value="stats.totalSizeFormatted" description="Total file size" />
            <Stat :title="`Last ${ recentDays } Days`" :value="stats.recentUploads.toLocaleString()" description="Recent uploads" />
        </div>

        <!-- Threshold warning -->
        <div v-if="stats.detailsUnavailable" class="alert alert-warning">
            <span>Detailed breakdown is unavailable for libraries with more than 2,000 items. Total file count is shown above.</span>
        </div>

        <!-- Detail panels -->
        <div v-if="! stats.detailsUnavailable" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Type breakdown -->
            <Card title="By Type">
                <div class="flex flex-col gap-3">
                    <div v-for="type in ( Object.keys( stats.byType ) as MediaType[] )" :key="type">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <Badge :value="type.charAt( 0 ).toUpperCase() + type.slice( 1 )" :color="typeColors[type]" />
                                <span class="text-sm">{{ stats.byType[type].count }} files</span>
                            </div>
                            <span class="text-xs text-base-content/60">{{ stats.byType[type].sizeFormatted }}</span>
                        </div>
                        <Progress :value="Math.round( ( stats.byType[type].count / ( stats.totalCount || 1 ) ) * 100 )" :color="typeColors[type]" />
                    </div>
                </div>
            </Card>

            <!-- Upload trend -->
            <Card title="Upload Trend">
                <div class="h-24">
                    <Sparkline :data="stats.dailyUploads" color="primary" />
                </div>
                <p class="text-xs text-base-content/60 mt-2">Daily uploads over the last {{ recentDays }} days</p>
            </Card>
        </div>
    </div>
</template>
