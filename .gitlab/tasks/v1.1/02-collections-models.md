# Collections/Albums - Models and Relationships

## Description

Create the MediaCollection model with all relationships, methods, scopes, and proper Eloquent configuration.

## Acceptance Criteria

- [ ] Create MediaCollection model with all properties
- [ ] Define all relationships (creator, media, coverImage)
- [ ] Implement key methods (addMedia, removeMedia, reorder, etc.)
- [ ] Create useful scopes (byUser, withMediaCount, etc.)
- [ ] Configure casts properly
- [ ] Create MediaCollectionFactory for testing
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Add comprehensive PHPDoc blocks
- [ ] Run phpcs validation

## Technical Details

### Model Structure

```php
class MediaCollection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image_id',
        'created_by',
    ];

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    public function media(): BelongsToMany
    public function coverImage(): BelongsTo

    /**
     * Key Methods
     */
    public function addMedia(int|array $mediaIds, bool $preserveOrder = true): void
    public function removeMedia(int|array $mediaIds): void
    public function syncMedia(array $mediaIds): void
    public function reorderMedia(array $mediaIds): void
    public function getMediaCount(): int
    public function setCoverImage(int $mediaId): bool
    public function clearCoverImage(): void

    /**
     * Scopes
     */
    public function scopeByUser($query, int $userId)
    public function scopeWithMediaCount($query)
    public function scopePublic($query)
    public function scopePrivate($query)
    public function scopeOrderedByNewest($query)
}
```

### MediaCollectionFactory

```php
class MediaCollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'created_by' => 1, // Default to ID 1, can be overridden
        ];
    }

    /**
     * Create with specific number of media items.
     */
    public function withMedia(int $count = 5): static
    {
        // Implementation
    }
}
```

## Dependencies

- Task 01: Collections database schema must be completed

## Testing Requirements

- [ ] Test all relationships work correctly
- [ ] Test addMedia() with single and multiple IDs
- [ ] Test removeMedia() functionality
- [ ] Test syncMedia() functionality
- [ ] Test reorderMedia() functionality
- [ ] Test media count methods
- [ ] Test cover image methods
- [ ] Test all scopes
- [ ] Test factory creates valid collections
- [ ] Test factory withMedia() state

## Notes

- Follow the same patterns used in Media and MediaFolder models
- The media relationship should use withPivot(['order']) to access ordering
- Cover image should be validated to exist in the collection's media
- Use Yoda conditionals: `if (null !== $mediaId)` not `if ($mediaId !== null)`
- All methods need explicit return type declarations
- Import Builder class at top to avoid long lines in scope type hints

## File Locations

- Model: `src/Models/MediaCollection.php`
- Factory: `database/factories/MediaCollectionFactory.php`
- Tests: `tests/Unit/MediaCollectionTest.php`
