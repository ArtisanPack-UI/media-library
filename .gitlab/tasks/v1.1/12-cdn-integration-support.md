# CDN Integration - Support Implementation

## Description

Add comprehensive CDN (Content Delivery Network) support for serving media files through CDN providers like CloudFlare, AWS CloudFront, or custom CDN services. This improves media delivery performance globally.

## Acceptance Criteria

- [ ] Create MediaCDNService for CDN URL generation
- [ ] Add CDN configuration options
- [ ] Support multiple CDN providers (CloudFlare, AWS, custom)
- [ ] Add URL signing for private media
- [ ] Add cache purging capabilities
- [ ] Add CDN health checking
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create comprehensive tests

## Technical Details

### Configuration

Add to `config/media.php`:

```php
return [
    // ... existing config ...

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    */
    'cdn' => [
        'enabled' => env('MEDIA_CDN_ENABLED', false),

        'provider' => env('MEDIA_CDN_PROVIDER', 'custom'), // cloudflare, aws, custom

        'url' => env('MEDIA_CDN_URL', ''), // CDN base URL

        'key' => env('MEDIA_CDN_KEY', ''), // API key for provider

        'secret' => env('MEDIA_CDN_SECRET', ''), // API secret for provider

        'zone_id' => env('MEDIA_CDN_ZONE_ID', ''), // CloudFlare zone ID

        'distribution_id' => env('MEDIA_CDN_DISTRIBUTION_ID', ''), // AWS CloudFront distribution ID

        'sign_urls' => env('MEDIA_CDN_SIGN_URLS', false), // Enable URL signing

        'signature_key' => env('MEDIA_CDN_SIGNATURE_KEY', ''), // Key for signing URLs

        'cache_ttl' => env('MEDIA_CDN_CACHE_TTL', 2592000), // Cache TTL in seconds (default 30 days)

        'purge_on_update' => env('MEDIA_CDN_PURGE_ON_UPDATE', true), // Auto-purge on media update

        'purge_on_delete' => env('MEDIA_CDN_PURGE_ON_DELETE', true), // Auto-purge on media delete
    ],
];
```

### MediaCDNService

```php
class MediaCDNService
{
    public function __construct(
        protected MediaStorageService $storage
    ) {}

    /**
     * Check if CDN is enabled.
     */
    public function isEnabled(): bool
    {
        return config('artisanpack.media.cdn.enabled', false);
    }

    /**
     * Get CDN URL for media.
     */
    public function getCDNUrl(Media $media, ?string $size = null): string
    {
        if (!$this->isEnabled()) {
            return $media->imageUrl($size) ?? $media->url();
        }

        $path = null !== $size
            ? $this->getImageSizePath($media, $size)
            : $media->file_path;

        $cdnUrl = rtrim(config('artisanpack.media.cdn.url'), '/');
        $url = "{$cdnUrl}/{$path}";

        // Sign URL if enabled
        if (config('artisanpack.media.cdn.sign_urls', false)) {
            $url = $this->signUrl($url);
        }

        return $url;
    }

    /**
     * Sign a CDN URL for security.
     */
    public function signUrl(string $url, ?int $expiresAt = null): string
    {
        $key = config('artisanpack.media.cdn.signature_key');

        if (empty($key)) {
            return $url;
        }

        $expiresAt = $expiresAt ?? time() + config('artisanpack.media.cdn.cache_ttl', 2592000);

        // Create signature
        $signature = hash_hmac('sha256', $url . $expiresAt, $key);

        // Append signature to URL
        $separator = str_contains($url, '?') ? '&' : '?';
        return "{$url}{$separator}expires={$expiresAt}&signature={$signature}";
    }

    /**
     * Verify a signed URL.
     */
    public function verifySignedUrl(string $url, string $signature, int $expiresAt): bool
    {
        // Check expiration
        if (time() > $expiresAt) {
            return false;
        }

        // Verify signature
        $key = config('artisanpack.media.cdn.signature_key');
        $expectedSignature = hash_hmac('sha256', $url . $expiresAt, $key);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Purge media from CDN cache.
     */
    public function purge(Media $media): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $provider = config('artisanpack.media.cdn.provider');

        return match($provider) {
            'cloudflare' => $this->purgeCloudflare($media),
            'aws' => $this->purgeAWS($media),
            'custom' => $this->purgeCustom($media),
            default => false,
        };
    }

    /**
     * Purge media from CloudFlare CDN.
     */
    protected function purgeCloudflare(Media $media): bool
    {
        $zoneId = config('artisanpack.media.cdn.zone_id');
        $key = config('artisanpack.media.cdn.key');

        if (empty($zoneId) || empty($key)) {
            return false;
        }

        $urls = $this->getAllMediaUrls($media);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$key}",
                'Content-Type' => 'application/json',
            ])->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'files' => $urls,
            ]);

            return $response->successful();
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Purge media from AWS CloudFront.
     */
    protected function purgeAWS(Media $media): bool
    {
        // Implementation using AWS SDK
        // This requires aws/aws-sdk-php package

        $distributionId = config('artisanpack.media.cdn.distribution_id');

        if (empty($distributionId)) {
            return false;
        }

        // AWS CloudFront invalidation implementation
        // ... AWS SDK code here ...

        return true;
    }

    /**
     * Purge media from custom CDN.
     */
    protected function purgeCustom(Media $media): bool
    {
        // Allow custom purge implementation via hook
        return applyFilters('ap.media.cdn.purgeCustom', false, $media);
    }

    /**
     * Get all URLs for a media item (original + all sizes).
     */
    protected function getAllMediaUrls(Media $media): array
    {
        $urls = [$this->getCDNUrl($media)];

        if ($media->isImage()) {
            $sizes = config('artisanpack.media.image_sizes', []);

            foreach (array_keys($sizes) as $size) {
                $urls[] = $this->getCDNUrl($media, $size);
            }
        }

        return $urls;
    }

    /**
     * Get image size path for media.
     */
    protected function getImageSizePath(Media $media, string $size): string
    {
        $pathInfo = pathinfo($media->file_path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        return "{$directory}/{$filename}-{$size}.{$extension}";
    }

    /**
     * Warm up CDN cache for media.
     */
    public function warmCache(Media $media): void
    {
        $urls = $this->getAllMediaUrls($media);

        foreach ($urls as $url) {
            try {
                // Make HEAD request to warm cache
                Http::timeout(5)->head($url);
            } catch (Exception $e) {
                // Silently fail - cache warming is best effort
            }
        }
    }

    /**
     * Check CDN health.
     */
    public function checkHealth(): array
    {
        if (!$this->isEnabled()) {
            return [
                'status' => 'disabled',
                'message' => 'CDN is not enabled',
            ];
        }

        $cdnUrl = config('artisanpack.media.cdn.url');

        try {
            $response = Http::timeout(10)->head($cdnUrl);

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->transferStats->getTransferTime(),
                'message' => $response->successful() ? 'CDN is responding' : 'CDN returned error',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'CDN is unreachable: ' . $e->getMessage(),
            ];
        }
    }
}
```

### Media Model Updates

Update Media model to use CDN URLs:

```php
// Add to Media model

public function url(): string
{
    if (config('artisanpack.media.cdn.enabled', false)) {
        return app(MediaCDNService::class)->getCDNUrl($this);
    }

    return $this->storage->url($this->file_path, $this->disk);
}

public function imageUrl(string $size = 'thumbnail'): ?string
{
    if (!$this->isImage()) {
        return null;
    }

    if (config('artisanpack.media.cdn.enabled', false)) {
        return app(MediaCDNService::class)->getCDNUrl($this, $size);
    }

    // ... existing logic ...
}
```

### Model Observers for Auto-Purging

Create MediaObserver to handle automatic cache purging:

```php
class MediaObserver
{
    public function updated(Media $media): void
    {
        if (config('artisanpack.media.cdn.purge_on_update', true)) {
            app(MediaCDNService::class)->purge($media);
        }
    }

    public function deleted(Media $media): void
    {
        if (config('artisanpack.media.cdn.purge_on_delete', true)) {
            app(MediaCDNService::class)->purge($media);
        }
    }
}
```

## Dependencies

- MediaStorageService (existing)

## Testing Requirements

- [ ] Test CDN URL generation
- [ ] Test URL signing
- [ ] Test signed URL verification
- [ ] Test CloudFlare purge integration
- [ ] Test AWS CloudFront purge integration
- [ ] Test custom CDN purge hook
- [ ] Test automatic purge on update
- [ ] Test automatic purge on delete
- [ ] Test cache warming
- [ ] Test CDN health check
- [ ] Test fallback to storage URLs when CDN disabled
- [ ] Create MediaCDNServiceTest with 20+ tests

## Notes

- CDN integration is optional and disabled by default
- When CDN is enabled, all media URLs use CDN
- URL signing provides security for private media
- Cache purging requires API credentials for CDN provider
- Consider these additional features:
  - Multiple CDN zones (for different regions)
  - CDN statistics/analytics
  - Automatic failover to storage URLs if CDN is down
  - Selective CDN usage (only for images, not videos)
- Add these helper functions:
  ```php
  if (!function_exists('apGetCDNUrl')) {
      function apGetCDNUrl(int $mediaId, ?string $size = null): ?string
      {
          $media = Media::find($mediaId);
          if (null === $media) {
              return null;
          }

          return app(MediaCDNService::class)->getCDNUrl($media, $size);
      }
  }

  if (!function_exists('apPurgeCDN')) {
      function apPurgeCDN(int $mediaId): bool
      {
          $media = Media::find($mediaId);
          if (null === $media) {
              return false;
          }

          return app(MediaCDNService::class)->purge($media);
      }
  }
  ```

## File Locations

- Service: `src/Services/MediaCDNService.php`
- Observer: `src/Observers/MediaObserver.php`
- Config: `config/media.php` (update existing)
- Tests: `tests/Unit/MediaCDNServiceTest.php`
- Helpers: `src/helpers.php` (add to existing)
