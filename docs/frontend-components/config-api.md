---
title: Config API Endpoint
---

# Config API Endpoint

The media config endpoint exposes upload constraints to frontend components so they can perform client-side validation before uploading files.

*Added in v1.2.0*

## Endpoint

```
GET /api/media/config
```

This is a **public endpoint** — no authentication required. The response is cached with ETags for efficient repeated requests.

## Response Format

```json
{
    "upload": {
        "max_file_size": 10240,
        "max_file_size_human": "10 MB",
        "allowed_mime_types": {
            "image": [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
                "image/avif",
                "image/svg+xml"
            ],
            "video": [
                "video/mp4",
                "video/mpeg",
                "video/quicktime",
                "video/webm"
            ],
            "audio": [
                "audio/mpeg",
                "audio/wav",
                "audio/ogg"
            ],
            "document": [
                "application/pdf",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            ]
        },
        "allowed_extensions": [
            "jpg", "jpeg", "png", "gif", "webp", "avif", "svg",
            "mp4", "mpeg", "mov", "webm",
            "mp3", "wav", "ogg",
            "pdf", "doc", "docx"
        ]
    },
    "image_sizes": {
        "thumbnail": { "width": 150, "height": 150, "crop": true },
        "medium": { "width": 300, "height": 300, "crop": false },
        "large": { "width": 1024, "height": 1024, "crop": false }
    },
    "features": {
        "webp_conversion": true,
        "avif_conversion": false
    }
}
```

## Response Fields

### `upload`

| Field | Type | Description |
|-------|------|-------------|
| `max_file_size` | `integer` | Maximum file size in kilobytes |
| `max_file_size_human` | `string` | Human-readable file size (e.g., "10 MB") |
| `allowed_mime_types` | `object` | MIME types grouped by category |
| `allowed_extensions` | `string[]` | Allowed file extensions |

### `image_sizes`

Object mapping size names to their configuration. Each size includes:

| Field | Type | Description |
|-------|------|-------------|
| `width` | `integer\|null` | Maximum width in pixels |
| `height` | `integer\|null` | Maximum height in pixels |
| `crop` | `boolean` | Whether to crop to exact dimensions |

### `features`

| Field | Type | Description |
|-------|------|-------------|
| `webp_conversion` | `boolean` | Whether WebP conversion is enabled |
| `avif_conversion` | `boolean` | Whether AVIF conversion is enabled |

## Caching

The endpoint uses HTTP caching headers:

- `Cache-Control: public, s-maxage=3600, max-age=3600, stale-while-revalidate=86400`
- `ETag` header for conditional requests

Frontend clients can cache the response and use `If-None-Match` for efficient revalidation.

## Usage in React/Vue

The published React and Vue API utilities automatically fetch and cache the config:

```ts
import { mediaApi } from '@/vendor/media-library';

const config = await mediaApi.getConfig();

// Validate file before upload
function isFileAllowed(file: File): boolean {
    const maxBytes = config.upload.max_file_size * 1024;
    if (file.size > maxBytes) return false;

    const ext = file.name.split('.').pop()?.toLowerCase();
    return config.upload.allowed_extensions.includes(ext ?? '');
}
```

## See Also

- [API Endpoints](Api-Endpoints) - Full API reference
- [Frontend Installation](Frontend-Components-Installation) - Setup guide
- [Configuration](Installation-Configuration) - Server-side configuration
