---
title: Frontend Installation
---

# Frontend Installation

The `media:install-frontend` Artisan command publishes React or Vue media library components to your application and displays the required npm peer dependencies.

*Added in v1.2.0*

## Quick Start

```bash
# Interactive — prompts for stack choice
php artisan media:install-frontend

# Specify stack directly
php artisan media:install-frontend --stack=react
php artisan media:install-frontend --stack=vue

# Overwrite previously published files
php artisan media:install-frontend --stack=react --force
```

## What Gets Published

### React Stack (`--stack=react`)

Published to `resources/js/vendor/media-library/`:

| File | Description |
|------|-------------|
| `components/MediaLibrary.tsx` | Full media management interface |
| `components/MediaModal.tsx` | Media selection modal |
| `components/MediaGrid.tsx` | Grid display of media items |
| `components/MediaItem.tsx` | Individual media item card |
| `components/MediaUpload.tsx` | File upload with drag-and-drop |
| `components/MediaEdit.tsx` | Media metadata editor |
| `components/MediaPicker.tsx` | Inline media picker |
| `components/MediaStatistics.tsx` | Statistics dashboard |
| `components/FolderManager.tsx` | Folder tree management |
| `components/TagManager.tsx` | Tag CRUD management |
| `hooks/useMediaLibrary.ts` | Core media library state hook |
| `hooks/useMediaPicker.ts` | Media picker selection hook |
| `hooks/useMediaUpload.ts` | File upload management hook |
| `utils/api.ts` | Sanctum-authenticated API client |
| `utils/Portal.tsx` | React portal utility for modals |
| `index.ts` | Barrel exports |
| `types/media.d.ts` | TypeScript type definitions |

### Vue Stack (`--stack=vue`)

Published to `resources/js/vendor/media-library-vue/`:

| File | Description |
|------|-------------|
| `components/MediaLibrary.vue` | Full media management interface |
| `components/MediaModal.vue` | Media selection modal |
| `components/MediaGrid.vue` | Grid display of media items |
| `components/MediaItem.vue` | Individual media item card |
| `components/MediaUpload.vue` | File upload with drag-and-drop |
| `components/MediaEdit.vue` | Media metadata editor |
| `components/MediaPicker.vue` | Inline media picker |
| `components/MediaStatistics.vue` | Statistics dashboard |
| `components/FolderManager.vue` | Folder tree management |
| `components/FolderNode.vue` | Recursive folder tree node |
| `components/TagManager.vue` | Tag CRUD management |
| `composables/useMediaLibrary.ts` | Core media library composable |
| `composables/useMediaPicker.ts` | Media picker composable |
| `composables/useMediaUpload.ts` | File upload composable |
| `utils/api.ts` | Sanctum-authenticated API client |
| `index.ts` | Barrel exports |
| `types/media.d.ts` | TypeScript type definitions |

### Shared Type Definitions

Both stacks also publish `resources/types/media.d.ts` (via the `media-types` publish tag), which provides shared TypeScript interfaces used across the application.

## Peer Dependencies

The command displays required npm packages after publishing:

### React

```bash
npm install react@"^18.0 || ^19.0" react-dom@"^18.0 || ^19.0"
npm install -D typescript@"^5.0"
```

### Vue

```bash
npm install vue@"^3.4"
npm install -D typescript@"^5.0"
```

## Manual Publishing

If you prefer to publish assets manually without the command:

```bash
# React components
php artisan vendor:publish --tag=media-react

# Vue components
php artisan vendor:publish --tag=media-vue

# TypeScript type definitions only
php artisan vendor:publish --tag=media-types
```

## Usage After Installation

### React (with Inertia.js)

```tsx
import { MediaLibrary } from '@/vendor/media-library';

export default function MediaPage() {
    return <MediaLibrary />;
}
```

### Vue (with Inertia.js)

```vue
<script setup lang="ts">
import { MediaLibrary } from '@/vendor/media-library-vue';
</script>

<template>
    <MediaLibrary />
</template>
```

## See Also

- [React Components](Frontend-Components-React) - Detailed React component API
- [Vue Components](Frontend-Components-Vue) - Detailed Vue component API
- [Config API Endpoint](Frontend-Components-Config-Api) - Client-side validation
