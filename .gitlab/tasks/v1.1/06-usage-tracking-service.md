# Usage Tracking - Service Implementation

## Description

Create the MediaUsageTracker service to automatically track and report where media is being used throughout the application.

## Acceptance Criteria

- [ ] Create MediaUsage model with relationships
- [ ] Create MediaUsageTracker service with tracking methods
- [ ] Implement automatic tracking via model observers/events
- [ ] Implement usage reporting methods
- [ ] Create helper functions for easy integration
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Add comprehensive PHPDoc blocks
- [ ] Create comprehensive tests

## Technical Details

### MediaUsage Model

```php
class MediaUsage extends Model
{
    protected $fillable = [
        'media_id',
        'usable_type',
        'usable_id',
        'context',
        'metadata',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function media(): BelongsTo
    public function usable(): MorphTo

    /**
     * Methods
     */
    public function touch(): void // Update last_used_at
}
```

### MediaUsageTracker Service

```php
class MediaUsageTracker
{
    /**
     * Track media usage.
     */
    public function track(int $mediaId, Model $usable, ?string $context = null, ?array $metadata = null): MediaUsage

    /**
     * Update last used timestamp.
     */
    public function touch(int $mediaId, Model $usable, ?string $context = null): void

    /**
     * Remove usage tracking.
     */
    public function untrack(int $mediaId, Model $usable, ?string $context = null): void

    /**
     * Get all places where media is used.
     */
    public function getUsageFor(int $mediaId): Collection

    /**
     * Get usage count for media.
     */
    public function getUsageCount(int $mediaId): int

    /**
     * Check if media is used anywhere.
     */
    public function isUsed(int $mediaId): bool

    /**
     * Get all media used by a model.
     */
    public function getMediaFor(Model $usable): Collection

    /**
     * Cleanup old usage records.
     */
    public function cleanup(int $daysOld = 90): int
}
```

### HasMediaUsage Trait

Create a trait for models that use media:

```php
trait HasMediaUsage
{
    /**
     * Track media usage for this model.
     */
    public function trackMedia(int|array $mediaIds, ?string $context = null): void

    /**
     * Untrack media usage for this model.
     */
    public function untrackMedia(int|array $mediaIds, ?string $context = null): void

    /**
     * Get all media used by this model.
     */
    public function usedMedia(): MorphMany

    /**
     * Sync media usage (track new, untrack removed).
     */
    public function syncMediaUsage(array $mediaIds, ?string $context = null): void
}
```

### Helper Functions

```php
if (!function_exists('apTrackMediaUsage')) {
    function apTrackMediaUsage(int $mediaId, Model $usable, ?string $context = null, ?array $metadata = null): MediaUsage
    {
        return app(MediaUsageTracker::class)->track($mediaId, $usable, $context, $metadata);
    }
}

if (!function_exists('apGetMediaUsage')) {
    function apGetMediaUsage(int $mediaId): Collection
    {
        return app(MediaUsageTracker::class)->getUsageFor($mediaId);
    }
}

if (!function_exists('apIsMediaUsed')) {
    function apIsMediaUsed(int $mediaId): bool
    {
        return app(MediaUsageTracker::class)->isUsed($mediaId);
    }
}
```

## Dependencies

- Task 05: Usage tracking database schema must be completed

## Testing Requirements

- [ ] Test MediaUsage model and relationships
- [ ] Test track() creates usage records
- [ ] Test touch() updates last_used_at
- [ ] Test untrack() removes usage records
- [ ] Test getUsageFor() returns all usage
- [ ] Test getUsageCount() returns correct count
- [ ] Test isUsed() detection
- [ ] Test getMediaFor() returns media for model
- [ ] Test cleanup() removes old records
- [ ] Test HasMediaUsage trait methods
- [ ] Test duplicate tracking prevention
- [ ] Create MediaUsageTrackerTest with 20+ tests

## Notes

- Consider using model observers to automatically track usage when media is attached
- Usage tracking should be opt-in via the HasMediaUsage trait
- Add a config option to enable/disable usage tracking globally
- Consider adding a queue job for cleanup to avoid blocking requests
- The metadata field can be used to store additional context:
  ```php
  apTrackMediaUsage($mediaId, $post, 'featured_image', [
      'size' => 'large',
      'alt_text' => 'Custom alt text',
  ]);
  ```

## File Locations

- Model: `src/Models/MediaUsage.php`
- Service: `src/Services/MediaUsageTracker.php`
- Trait: `src/Models/Concerns/HasMediaUsage.php`
- Helpers: `src/helpers.php` (add to existing file)
- Tests: `tests/Unit/MediaUsageTrackerTest.php`
