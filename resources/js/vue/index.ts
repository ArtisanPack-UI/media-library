/**
 * ArtisanPack UI Media Library - Vue Components
 *
 * Main entry point for all Vue media library components and composables.
 *
 * @package ArtisanPackUI\MediaLibrary
 * @since   1.2.0
 */

// =============================================================================
// Composables
// =============================================================================

export { useMediaLibrary } from './composables/useMediaLibrary';
export type { UseMediaLibraryOptions } from './composables/useMediaLibrary';

export { useMediaUpload } from './composables/useMediaUpload';
export type { UseMediaUploadOptions, UploadQueueItem } from './composables/useMediaUpload';

export { useMediaPicker } from './composables/useMediaPicker';
export type { UseMediaPickerOptions } from './composables/useMediaPicker';

// =============================================================================
// Core Components
// =============================================================================

export { default as MediaLibrary } from './components/MediaLibrary.vue';
export { default as MediaUpload } from './components/MediaUpload.vue';
export { default as MediaModal } from './components/MediaModal.vue';
export { default as MediaPicker } from './components/MediaPicker.vue';
export { default as MediaGrid } from './components/MediaGrid.vue';
export { default as MediaItem } from './components/MediaItem.vue';

// =============================================================================
// Management Components
// =============================================================================

export { default as MediaEdit } from './components/MediaEdit.vue';
export { default as FolderManager } from './components/FolderManager.vue';
export { default as TagManager } from './components/TagManager.vue';
export { default as MediaStatistics } from './components/MediaStatistics.vue';

// =============================================================================
// Utilities
// =============================================================================

export {
    configureAuth,
    fetchMedia,
    fetchMediaById,
    uploadMedia,
    updateMedia,
    deleteMedia,
    fetchFolders,
    createFolder,
    updateFolder,
    deleteFolder,
    moveFolder,
    fetchTags,
    createTag,
    updateTag,
    deleteTag,
    attachTag,
    detachTag,
    fetchMediaConfig,
} from './utils/api';
