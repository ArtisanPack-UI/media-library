---
title: React Components
---

# React Components

The Media Library provides a complete set of React components for building media management UIs in React/Inertia.js applications.

*Added in v1.2.0*

## Installation

```bash
php artisan media:install-frontend --stack=react
npm install react@"^18.0 || ^19.0" react-dom@"^18.0 || ^19.0"
npm install -D typescript@"^5.0"
```

## Components

### MediaLibrary

Full-featured media management interface with grid view, upload, folders, tags, search, and filtering.

```tsx
import { MediaLibrary } from '@/vendor/media-library';

export default function MediaPage() {
    return <MediaLibrary />;
}
```

### MediaModal

Modal dialog for selecting media items. Supports single and multi-select modes.

```tsx
import { MediaModal } from '@/vendor/media-library';

function MyComponent() {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button onClick={() => setOpen(true)}>Select Media</button>
            <MediaModal
                open={open}
                onClose={() => setOpen(false)}
                multiSelect={false}
                onSelect={(media) => {
                    console.log('Selected:', media);
                    setOpen(false);
                }}
            />
        </>
    );
}
```

### MediaGrid

Displays media items in a responsive grid layout.

```tsx
import { MediaGrid } from '@/vendor/media-library';

<MediaGrid
    items={mediaItems}
    onSelect={(item) => handleSelect(item)}
    selectedIds={selectedIds}
/>
```

### MediaItem

Renders a single media item card with preview, title, and actions.

```tsx
import { MediaItem } from '@/vendor/media-library';

<MediaItem
    media={mediaItem}
    selected={isSelected}
    onSelect={() => toggleSelection(mediaItem.id)}
    onDelete={() => handleDelete(mediaItem.id)}
/>
```

### MediaUpload

Drag-and-drop file upload with progress tracking.

```tsx
import { MediaUpload } from '@/vendor/media-library';

<MediaUpload
    folderId={currentFolderId}
    onUploadComplete={(media) => refreshList()}
    allowedTypes={['image/*', 'video/*']}
/>
```

### MediaEdit

Edit media metadata (title, alt text, caption, description, tags).

```tsx
import { MediaEdit } from '@/vendor/media-library';

<MediaEdit
    media={selectedMedia}
    onSave={(updated) => handleSave(updated)}
    onCancel={() => setEditing(false)}
/>
```

### MediaPicker

Inline media picker with search, filtering, and folder browsing.

```tsx
import { MediaPicker } from '@/vendor/media-library';

<MediaPicker
    multiSelect={true}
    maxSelections={5}
    allowedTypes={['image']}
    onSelect={(media) => setSelectedMedia(media)}
/>
```

### MediaStatistics

Dashboard showing upload statistics, storage usage, and media type breakdowns.

```tsx
import { MediaStatistics } from '@/vendor/media-library';

<MediaStatistics />
```

### FolderManager

Folder tree management with create, rename, move, and delete operations.

```tsx
import { FolderManager } from '@/vendor/media-library';

<FolderManager
    onFolderSelect={(folderId) => setCurrentFolder(folderId)}
    selectedFolderId={currentFolderId}
/>
```

### TagManager

Tag CRUD interface with media count badges.

```tsx
import { TagManager } from '@/vendor/media-library';

<TagManager
    onTagSelect={(tagSlug) => filterByTag(tagSlug)}
/>
```

## Hooks

### useMediaLibrary

Core hook for media library state management.

```tsx
import { useMediaLibrary } from '@/vendor/media-library';

function MyComponent() {
    const {
        media,
        loading,
        filters,
        setFilters,
        refresh,
        deleteMedia,
    } = useMediaLibrary();

    return (
        <div>
            {loading ? <p>Loading...</p> : (
                media.map(item => <MediaItem key={item.id} media={item} />)
            )}
        </div>
    );
}
```

### useMediaPicker

Manages media selection state for picker components.

```tsx
import { useMediaPicker } from '@/vendor/media-library';

function MyPicker() {
    const {
        selected,
        toggleSelection,
        clearSelection,
        isSelected,
    } = useMediaPicker({ multiSelect: true, maxSelections: 5 });

    // ...
}
```

### useMediaUpload

Manages file upload state and progress.

```tsx
import { useMediaUpload } from '@/vendor/media-library';

function MyUploader() {
    const {
        uploading,
        progress,
        uploadFiles,
        cancelUpload,
    } = useMediaUpload({ folderId: currentFolderId });

    // ...
}
```

## API Client

The `api.ts` utility handles Sanctum-authenticated requests to the media API:

```tsx
import { mediaApi } from '@/vendor/media-library';

// List media
const response = await mediaApi.list({ folder_id: 1, type: 'image' });

// Upload
const media = await mediaApi.upload(file, { title: 'My Image' });

// Update
await mediaApi.update(mediaId, { alt_text: 'Updated alt text' });

// Delete
await mediaApi.delete(mediaId);
```

## See Also

- [Frontend Installation](Frontend-Components-Installation) - Setup guide
- [Vue Components](Frontend-Components-Vue) - Vue equivalent
- [Config API Endpoint](Frontend-Components-Config-Api) - Client-side validation
