---
title: Thumbnails
---

# Thumbnails

The media library generates a resized copy of each uploaded image for every configured size. Sizes come from the `image_sizes` and `custom_image_sizes` config options, and the `ap.mediaLibrary.imageSizes` filter can change them per image. See [Configuration](Installation-Configuration) and [Customization](Integration-Customization).

## Output format

*Changed in v1.7.0.* A thumbnail keeps the format and extension of its source file, so transparency survives and the encoded data always matches the extension.

| Source extension | Thumbnail format | Uses `image_quality` |
|------------------|------------------|----------------------|
| `.jpg`, `.jpeg` | JPEG | Yes |
| `.png` | PNG | No (lossless) |
| `.gif` | GIF | No |
| `.webp` | WebP | Yes |
| `.avif` | AVIF | Yes |

The format is chosen from the file's extension, ignoring case. Thumbnails are named `{filename}-{size}.{extension}`, so `logo.png` produces `logo-thumbnail.png`.

Because PNG is lossless, PNG thumbnails are usually larger than the JPEG files that earlier versions saved under the same name.

AVIF requires a driver with AVIF support. If the driver can't read or encode a file, no thumbnail is generated for it.

## Files without thumbnails

Any other format, including SVG, is skipped without being decoded. `imageUrl()` then returns the original file:

```php
$media->imageUrl('thumbnail'); // The original SVG's URL
```

## Regenerating thumbnails

*Added in v1.7.0.* Before v1.7.0, every thumbnail was encoded as JPEG but kept the source file's extension. A PNG's `-thumbnail.png` was really JPEG data with its transparency flattened. Upgrading doesn't change files that already exist, so run this command once to repair them:

```bash
php artisan media:regenerate-thumbnails
```

Options:

- `--chunk=200` — number of rows to process per batch. Default is 200.

The command:

- Selects image rows by the extension in `file_path`, skipping `.jpg`, `.jpeg` and `.svg`. It uses the extension because that's what names the thumbnail, and uploads keep the client's extension, so `mime_type` can disagree with it. Rows with the `image/svg+xml` MIME type are always skipped.
- Overwrites thumbnails in place, so it's safe to run more than once.
- Leaves the [optimization status](Usage-Optimization-Status) columns alone.
- Fires `ap.mediaLibrary.thumbnailsGenerated` for each image, so CDN and indexing subscribers are notified.
- Generates every configured size, even when `enable_thumbnails` is off.

A row counts as failed if an exception is thrown or any configured size is missing, because a missing size keeps its old file. The command prints a warning for each failed row, finishes the remaining rows, prints a summary, and exits with a failure code:

```text
Regenerated thumbnails for 41 image(s); 1 failed.
```

### Purge caches afterward

Thumbnail URLs don't change, so CDNs and browsers can keep serving the old files until their cache expires. Purge any CDN after running the command.

## Related

- [Optimization Status](Usage-Optimization-Status) — per-item pipeline state.
- [Customization](Integration-Customization) — custom image sizes and pipeline hooks.
- [Troubleshooting](Reference-Troubleshooting) — common image processing issues.
