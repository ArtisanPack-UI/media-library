---
title: AI Features
---

# AI Features

*Added in v1.3.0*

The Media Library integrates with [`artisanpack-ui/ai`](https://github.com/ArtisanPack-UI/ai) to offer three vision-powered helpers for image media items:

| Feature key                | Agent                                                          | What it does                                                                       |
|----------------------------|----------------------------------------------------------------|------------------------------------------------------------------------------------|
| `ai.alt_text`              | `ArtisanPackUI\Ai\Agents\AltTextGenerationAgent` (from `artisanpack-ui/ai`) | Accessibility-friendly alt text for an image.                                     |
| `media.suggest_tags`       | `ArtisanPackUI\MediaLibrary\Ai\Agents\ImageTagSuggestionAgent` | Picks matching tags from the existing `media_tags` taxonomy (opt-in new tags).    |
| `media.image_description`  | `ArtisanPackUI\MediaLibrary\Ai\Agents\ImageDescriptionAgent`  | Paragraph-length image description for galleries, portfolios, and image-heavy layouts. |

## Requirements

- `artisanpack-ui/media-library` `^1.3`
- `artisanpack-ui/ai` `^1.0` (required dependency starting in v1.3)
- Credentials configured via `config/artisanpack.php` or the AI Settings admin surface

## Configuration

### Credentials

Configure the AI provider credentials once in `config/artisanpack.php` (or via `.env` values documented by the ai package). Every feature honors the resolver chain so a single credential set covers all three agents.

### Feature toggles

Each feature is toggled independently through the ai package's feature registry. Operators can flip a feature off via the AI Settings admin surface (`/admin/packages/ai/features`) or via config:

```php
// config/artisanpack.php
return [
    'ai' => [
        'features' => [
            'ai.alt_text'             => [ 'enabled' => true ],
            'media.suggest_tags'      => [ 'enabled' => true ],
            'media.image_description' => [ 'enabled' => false ],
        ],
    ],
];
```

Unknown feature keys resolve to disabled (Livewire, React, Vue, and the JSON API all agree on this default), so a missing entry never silently opts a user into an unregistered feature.

### Alt-text prompt override

Media Library injects a stricter default prompt for `ai.alt_text` so the model never returns an empty string for images it classifies as "decorative". If you have set your own prompt via `artisanpack.ai.features.ai.alt_text.instructions` (config file or AI Settings admin), your value continues to take precedence.

## HTTP API

Three JSON endpoints under the existing `api/media` prefix expose all three features:

```
POST /api/media/{id}/ai/alt-text
POST /api/media/{id}/ai/tags       { "allow_new": bool }
POST /api/media/{id}/ai/description { "length": "short" | "medium" | "long" }
```

All three:

- Require Sanctum authentication and the `update` policy on the target media item.
- Return `403` when the requested feature is disabled or unregistered.
- Return `422` when the target media isn't an image.
- Return `503` when no AI credentials are configured.
- Return `200` with a `{ data: {...} }` envelope on success.

### Response shapes

```jsonc
// POST /api/media/{id}/ai/alt-text
{ "data": { "alt_text": "string", "confidence": 0.0, "warnings": ["string"] } }

// POST /api/media/{id}/ai/tags
{ "data": { "tags": ["string"], "new_tags": ["string"], "confidence": 0.0 } }

// POST /api/media/{id}/ai/description
{ "data": { "description": "string", "confidence": 0.0, "warnings": ["string"] } }
```

`tags` is drawn verbatim from the existing `media_tags` table. `new_tags` is only populated when the request sets `"allow_new": true` and the model considered the existing taxonomy insufficient.

## Livewire

Both `MediaEdit` (post-upload) and `MediaUpload` (pre-upload) gain "AI suggest" buttons for alt text and description; `MediaEdit` also gains tag suggestions. Buttons hide automatically when:

- `artisanpack-ui/ai` is not installed
- The corresponding feature is disabled/unregistered
- The queued media item is not an image (uploads only)

At upload time, the alt-text and description agents receive the pending `TemporaryUploadedFile` path directly — no persisted media record is required. On the edit surface, the agents receive the media's public URL (or a local disk path for private-disk media).

### Blade example

```blade
<livewire:media::media-edit :media-id="$media->id" />

{{-- The component renders the suggest buttons when the features are
     enabled; you don't need to render anything special. --}}
```

## React and Vue

The shipped `MediaEdit.tsx` (React) and `MediaEdit.vue` (Vue) components consume typed helpers exported from `resources/js/{react,vue}/utils/api.ts`:

```ts
import {
    suggestMediaAltText,
    suggestMediaTags,
    suggestMediaDescription,
    filenameFallbackAltText,
    type AiAltTextResult,
    type AiTagSuggestionResult,
    type AiDescriptionResult,
    type AiDescriptionLength,
} from 'media-library/utils/api';

const result = await suggestMediaAltText( media.id );
const altText = result.alt_text?.trim() ||
    filenameFallbackAltText( media.file_name, media.title );
```

React/Vue AI is post-upload only because the JSON endpoints require a persisted media item; the Livewire `MediaUpload` supports pre-upload suggestions by handing the AI agent a `TemporaryUploadedFile` in-process.

## Filename fallback

When the model refuses to describe an image (rare, but possible with heavily stylised graphics), all four surfaces fall back to a filename-derived stub so the alt-text field is never silently empty:

- `company-banner.jpg` → `Company banner`
- `sunset-over-hills.jpg` → `Sunset over hills`

The fallback consults the media item's `file_name` first, then its `title`. Titles containing periods (e.g. `Roadmap v1.0`) are used verbatim — only true filenames are stripped of their extension.

## Cost and telemetry

Every agent run dispatches an `AgentUsageRecorded` event carrying token counts, cache-hit status, feature key, and provider. The AI Settings admin surface (`/admin/packages/ai/usage`) aggregates these into a per-feature dashboard. Cache is honored per the ai package's global cache settings — the same alt text won't be re-generated for the same image and model unless the cache is cleared.

## Extending

### Registering a new media-library AI feature

Add an entry to `MediaLibraryServiceProvider::aiFeatures()` (or your own service provider's `aiFeatures()` method):

```php
public function aiFeatures(): array
{
    return [
        'media.your_feature' => [
            'agent'   => YourAgentClass::class,
            'package' => 'your-vendor/your-package',
        ],
    ];
}
```

Any class that extends `ArtisanPackUI\Ai\Agents\ArtisanPackAgent` is a valid agent target. See the ai package documentation for the full agent contract.

### Overriding an agent's prompt

Set `artisanpack.ai.features.{feature_key}.instructions` in your app's config file, or persist an override via the AI Settings admin surface. The config override takes precedence over the class-level default; the admin-persisted override takes precedence over the config.

## Troubleshooting

**"AI features are not installed" toast** — `artisanpack-ui/ai` is missing. Run `composer require artisanpack-ui/ai:^1.0`.

**"This AI feature is disabled" 403** — The feature key is either not registered or explicitly toggled off. Register it via `aiFeatures()` on a service provider or enable it in AI Settings.

**"AI credentials are not configured" 503** — No provider credentials could be resolved. Configure them via `config/artisanpack.php` or the AI Settings admin surface.

**Empty alt text populated with a filename stub** — The model classified the image as low-content. Media Library's fallback ensures the field is never blank; edit it manually or accept the placeholder.

## See Also

- [Livewire Components](Usage-Livewire-Components)
- [API Endpoints](Api)
- [Configuration](Installation-Configuration)
