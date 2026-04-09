/**
 * ArtisanPack UI Media Library - useMediaUpload Composable
 *
 * Manages file upload state including drag-and-drop, upload progress,
 * file validation, and queue management.
 *
 * @package ArtisanPackUI\MediaLibrary
 * @since   1.2.0
 */

import { ref, onMounted } from 'vue';

import type {
    Media,
    MediaConfigResponse,
} from '../types/media';

import { uploadMedia, fetchMediaConfig } from '../utils/api';

/**
 * Represents a file in the upload queue.
 */
export interface UploadQueueItem {
    id: string;
    file: File;
    progress: number;
    status: 'pending' | 'uploading' | 'complete' | 'error';
    error: string | null;
    media: Media | null;
}

/**
 * Options for the useMediaUpload composable.
 */
export interface UseMediaUploadOptions {
    folderId?: number;
    tagIds?: number[];
    onUploadComplete?: ( media: Media ) => void;
    onQueueComplete?: ( results: Media[] ) => void;
    autoFetchConfig?: boolean;
}

let queueIdCounter = 0;

/**
 * Composable for managing media file uploads.
 */
export function useMediaUpload( options: UseMediaUploadOptions = {} ) {
    const {
        autoFetchConfig = true,
    } = options;

    // Store options in a ref-like pattern so callbacks see latest values
    let currentOptions = options;
    function updateOptions( opts: UseMediaUploadOptions ) {
        currentOptions = opts;
    }

    const queue            = ref<UploadQueueItem[]>( [] );
    const isUploading      = ref( false );
    const isDragging       = ref( false );
    const config           = ref<MediaConfigResponse | null>( null );
    const validationErrors = ref<string[]>( [] );
    const uploadedMedia    = ref<Media[]>( [] );

    let dragCounter = 0;
    let configPromise: Promise<void> | null = null;

    onMounted( () => {
        if ( autoFetchConfig ) {
            configPromise = fetchMediaConfig()
                .then( ( c ) => { config.value = c; } )
                .catch( () => { configPromise = null; } );
        }
    } );

    /**
     * Ensure config is loaded before validating files.
     */
    async function ensureConfig(): Promise<void> {
        if ( config.value ) {
            return;
        }
        if ( configPromise ) {
            await configPromise;
            if ( config.value ) {
                return;
            }
        }
        try {
            config.value = await fetchMediaConfig();
        } catch {
            // Non-critical — validation will be skipped
        }
    }

    function validateFile( file: File ): string | null {
        if ( ! config.value ) {
            return null;
        }

        const maxSizeBytes = config.value.upload.max_file_size * 1024;
        if ( file.size > maxSizeBytes ) {
            return `${ file.name } exceeds the maximum file size of ${ config.value.upload.max_file_size_human }`;
        }

        // Check MIME type against config keys (e.g. "image/png", "application/pdf")
        const allowedMimeKeys = Object.keys( config.value.upload.allowed_mime_types );
        if ( allowedMimeKeys.length > 0 && ! allowedMimeKeys.includes( file.type ) ) {
            // Fall back to extension check
            const allowedExtensions = Object.values( config.value.upload.allowed_mime_types )
                .flat()
                .map( ( ext ) => ext.toLowerCase() );
            const fileExt = '.' + ( file.name.split( '.' ).pop() || '' ).toLowerCase();
            if ( allowedExtensions.length > 0 && ! allowedExtensions.includes( fileExt ) ) {
                return `${ file.name } has an unsupported file type (${ file.type })`;
            }
        }

        return null;
    }

    async function addFiles( files: FileList | File[] ) {
        await ensureConfig();
        const fileArray = Array.from( files );
        const errors: string[]              = [];
        const validItems: UploadQueueItem[] = [];

        for ( const file of fileArray ) {
            const validationError = validateFile( file );
            if ( validationError ) {
                errors.push( validationError );
            } else {
                queueIdCounter += 1;
                validItems.push( {
                    id:       `upload-${ queueIdCounter }`,
                    file,
                    progress: 0,
                    status:   'pending',
                    error:    null,
                    media:    null,
                } );
            }
        }

        validationErrors.value = errors;
        if ( validItems.length > 0 ) {
            queue.value = [ ...queue.value, ...validItems ];
        }
    }

    async function startUpload() {
        if ( isUploading.value ) {
            return;
        }

        const pendingItems = queue.value.filter( ( item ) => item.status === 'pending' );
        if ( pendingItems.length === 0 ) {
            return;
        }

        isUploading.value = true;
        const results: Media[] = [];

        for ( const item of pendingItems ) {
            // Mark as uploading
            queue.value = queue.value.map( ( q ) =>
                q.id === item.id ? { ...q, status: 'uploading' as const } : q,
            );

            try {
                const metadata: Record<string, string | number | number[]> = {};
                if ( currentOptions.folderId ) {
                    metadata.folder_id = currentOptions.folderId;
                }
                if ( currentOptions.tagIds && currentOptions.tagIds.length > 0 ) {
                    metadata.tags = currentOptions.tagIds;
                }

                const response = await uploadMedia(
                    item.file,
                    metadata,
                    ( percent ) => {
                        queue.value = queue.value.map( ( q ) =>
                            q.id === item.id ? { ...q, progress: percent } : q,
                        );
                    },
                );

                queue.value = queue.value.map( ( q ) =>
                    q.id === item.id
                        ? { ...q, status: 'complete' as const, progress: 100, media: response.data }
                        : q,
                );

                uploadedMedia.value = [ ...uploadedMedia.value, response.data ];
                results.push( response.data );

                try {
                    currentOptions.onUploadComplete?.( response.data );
                } catch {
                    // Callback error should not affect upload state
                }
            } catch ( err ) {
                queue.value = queue.value.map( ( q ) =>
                    q.id === item.id
                        ? {
                            ...q,
                            status: 'error' as const,
                            error:  err instanceof Error ? err.message : 'Upload failed',
                        }
                        : q,
                );
            }
        }

        isUploading.value = false;
        currentOptions.onQueueComplete?.( results );
    }

    function removeFromQueue( queueId: string ) {
        queue.value = queue.value.filter( ( item ) => item.id !== queueId );
    }

    function clearQueue() {
        queue.value            = [];
        validationErrors.value = [];
    }

    function clearUploaded() {
        uploadedMedia.value = [];
    }

    // Drag-and-drop handlers
    function onDragEnter( e: DragEvent ) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter += 1;
        if ( e.dataTransfer && e.dataTransfer.items.length > 0 ) {
            isDragging.value = true;
        }
    }

    function onDragOver( e: DragEvent ) {
        e.preventDefault();
        e.stopPropagation();
    }

    function onDragLeave( e: DragEvent ) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter = Math.max( 0, dragCounter - 1 );
        if ( dragCounter <= 0 ) {
            isDragging.value = false;
        }
    }

    function onDrop( e: DragEvent ) {
        e.preventDefault();
        e.stopPropagation();
        isDragging.value = false;
        dragCounter      = 0;

        if ( e.dataTransfer && e.dataTransfer.files.length > 0 ) {
            addFiles( e.dataTransfer.files );
        }
    }

    return {
        queue,
        isUploading,
        isDragging,
        config,
        validationErrors,
        uploadedMedia,
        addFiles,
        startUpload,
        removeFromQueue,
        clearQueue,
        clearUploaded,
        updateOptions,
        dropZoneHandlers: {
            dragenter: onDragEnter,
            dragover:  onDragOver,
            dragleave: onDragLeave,
            drop:      onDrop,
        },
    };
}
