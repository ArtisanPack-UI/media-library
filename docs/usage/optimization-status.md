---
title: Optimization Status
---

# Optimization Status

*Added in v1.5.0.*

Every image row now carries its own record of how the optimization pipeline ended, so consumers can render a status badge, show bytes saved, and list the generated formats without recomputing anything at read time.

## Persisted columns

The `2026_09_14_000001_add_optimization_columns_to_media_table.php` migration adds six nullable columns and one index to the `media` table:

| Column | Type | Purpose |
|--------|------|---------|
| `optimization_status` | `string(20)` | One of `pending`, `optimized`, `failed`, or `null` (N/A). Indexed. |
| `optimized_at` | `timestamp` | When the pipeline finished, or `null` if not run. |
| `optimization_bytes_saved` | `unsigned big int` | Delta between the original bytes and the optimized bytes. |
| `optimization_original_size` | `unsigned big int` | The pre-optimization size, kept alongside `bytes_saved` for display. |
| `optimization_formats` | `json` | Map of format → generated (e.g. `{"webp": true, "avif": true}`). |
| `optimization_error` | `string(500)` | Truncated exception message from the failing run. |

Non-image rows leave every column `null`. Consumers should treat `null` as "N/A" rather than "pending."

## Status constants

Three constants live on the `Media` model — always prefer them over hard-coded strings so a future rename doesn't leak into your code:

```php
use ArtisanPackUI\MediaLibrary\Models\Media;

Media::OPTIMIZATION_STATUS_PENDING;   // 'pending'
Media::OPTIMIZATION_STATUS_OPTIMIZED; // 'optimized'
Media::OPTIMIZATION_STATUS_FAILED;    // 'failed'
```

## How the status is written

`MediaProcessingService::processImage()` owns the transitions:

1. A new upload lands as `pending` when the pipeline queues it.
2. On success, the service atomically flips `pending → optimized` and populates `optimized_at`, `optimization_bytes_saved`, `optimization_original_size`, and `optimization_formats`.
3. On any exception, the service flips `pending → failed` and records the truncated message in `optimization_error`. The exception itself is still surfaced to the caller — the persisted status is a display aid, not a substitute for handling the error.

## Reading the status

### From PHP

```php
$media = apGetMedia($mediaId);

if ($media->optimization_status === Media::OPTIMIZATION_STATUS_FAILED) {
    // Show a retry affordance, log $media->optimization_error, etc.
}
```

### From the API

`MediaResource` returns a stable `optimization` block on every media item — non-image rows carry every field as `null`, so clients can encode it as a single interface:

```json
{
    "id": 42,
    "optimization": {
        "status": "optimized",
        "optimized_at": "2026-09-14T12:00:00Z",
        "bytes_saved": 123456,
        "original_size": 789012,
        "formats": { "webp": true, "avif": true },
        "error": null
    }
}
```

### From TypeScript (React / Vue)

The shipped types file exports the shape directly:

```ts
import type { Media, MediaOptimization, OptimizationStatus } from '@/types/media';

function badgeLabel(optimization: MediaOptimization): string {
    switch (optimization.status) {
        case 'optimized': return 'Optimized';
        case 'pending':   return 'Optimizing…';
        case 'failed':    return 'Failed';
        default:          return 'N/A';
    }
}
```

## Backfilling legacy libraries

Existing pre-1.5 image rows arrive as `optimization_status = null`, which reads as "N/A" — not "pending." If you want them to render as `optimized` instead (so a status column doesn't fill with N/A badges), run:

```bash
php artisan media:backfill-optimization-status
```

Options:

- `--chunk=200` — number of rows to update per batch. Default is 200.

The command is idempotent — it only touches rows whose `optimization_status` is still `null`, and each write is guarded by an atomic `whereNull('optimization_status')` check so a concurrent `MediaProcessingService` run cannot be clobbered.

## Related

- [Working with Models](Usage-Models) — general `Media` model reference.
- [Customization](Integration-Customization) — pipeline hooks that fire around processing (`ap.mediaLibrary.beforeProcess`, `ap.mediaLibrary.thumbnailsGenerated`).
