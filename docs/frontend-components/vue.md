---
title: Vue Components
---

# Vue Components

The Media Library provides a complete set of Vue 3 components for building media management UIs in Vue/Inertia.js applications.

*Added in v1.2.0*

## Installation

```bash
php artisan media:install-frontend --stack=vue
npm install vue@"^3.4"
npm install -D typescript@"^5.0"
```

## Components

### MediaLibrary

Full-featured media management interface with grid view, upload, folders, tags, search, and filtering.

```vue
<script setup lang="ts">
import { MediaLibrary } from '@/vendor/media-library-vue';
</script>

<template>
    <MediaLibrary />
</template>
```

### MediaModal

Modal dialog for selecting media items. Supports single and multi-select modes.

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { MediaModal } from '@/vendor/media-library-vue';

const open = ref(false);

function handleSelect(media: any[]) {
    console.log('Selected:', media);
    open.value = false;
}
</script>

<template>
    <button @click="open = true">Select Media</button>
    <MediaModal
        v-model:open="open"
        :multi-select="false"
        @select="handleSelect"
    />
</template>
```

### MediaGrid

Displays media items in a responsive grid layout.

```vue
<MediaGrid
    :items="mediaItems"
    :selected-ids="selectedIds"
    @select="handleSelect"
/>
```

### MediaItem

Renders a single media item card with preview, title, and actions.

```vue
<MediaItem
    :media="mediaItem"
    :selected="isSelected"
    @select="toggleSelection(mediaItem.id)"
    @delete="handleDelete(mediaItem.id)"
/>
```

### MediaUpload

Drag-and-drop file upload with progress tracking.

```vue
<MediaUpload
    :folder-id="currentFolderId"
    :allowed-types="['image/*', 'video/*']"
    @upload-complete="refreshList"
/>
```

### MediaEdit

Edit media metadata (title, alt text, caption, description, tags).

```vue
<MediaEdit
    :media="selectedMedia"
    @save="handleSave"
    @cancel="editing = false"
/>
```

### MediaPicker

Inline media picker with search, filtering, and folder browsing.

```vue
<MediaPicker
    :multi-select="true"
    :max-selections="5"
    :allowed-types="['image']"
    @select="setSelectedMedia"
/>
```

### MediaStatistics

Dashboard showing upload statistics, storage usage, and media type breakdowns.

```vue
<MediaStatistics />
```

### FolderManager

Folder tree management with create, rename, move, and delete operations. Uses the recursive `FolderNode` component internally.

```vue
<FolderManager
    :selected-folder-id="currentFolderId"
    @folder-select="setCurrentFolder"
/>
```

### TagManager

Tag CRUD interface with media count badges.

```vue
<TagManager @tag-select="filterByTag" />
```

## Composables

### useMediaLibrary

Core composable for media library state management.

```vue
<script setup lang="ts">
import { useMediaLibrary } from '@/vendor/media-library-vue';

const {
    media,
    loading,
    filters,
    setFilters,
    refresh,
    deleteMedia,
} = useMediaLibrary();
</script>
```

### useMediaPicker

Manages media selection state for picker components.

```vue
<script setup lang="ts">
import { useMediaPicker } from '@/vendor/media-library-vue';

const {
    selected,
    toggleSelection,
    clearSelection,
    isSelected,
} = useMediaPicker({ multiSelect: true, maxSelections: 5 });
</script>
```

### useMediaUpload

Manages file upload state and progress.

```vue
<script setup lang="ts">
import { useMediaUpload } from '@/vendor/media-library-vue';

const {
    uploading,
    progress,
    uploadFiles,
    cancelUpload,
} = useMediaUpload({ folderId: currentFolderId });
</script>
```

## API Client

The `api.ts` utility handles Sanctum-authenticated requests to the media API:

```ts
import { mediaApi } from '@/vendor/media-library-vue';

// List media
const response = await mediaApi.list({ folder_id: 1, type: 'image' });

// Upload
const media = await mediaApi.upload(file, { title: 'My Image' });

// Update
await mediaApi.update(mediaId, { alt_text: 'Updated alt text' });

// Delete
await mediaApi.delete(mediaId);
```

## Teleport for Modals

Vue components use `<Teleport to="body">` for modals and pickers to ensure they render above other content regardless of DOM nesting.

## See Also

- [Frontend Installation](Frontend-Components-Installation) - Setup guide
- [React Components](Frontend-Components-React) - React equivalent
- [Config API Endpoint](Frontend-Components-Config-Api) - Client-side validation
