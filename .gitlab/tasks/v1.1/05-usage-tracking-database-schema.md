# Usage Tracking - Database Schema

## Description

Create database schema to track where media items are being used throughout the application. This allows users to see which pages, posts, or other content use specific media files.

## Acceptance Criteria

- [ ] Create `media_usage` migration with all required fields
- [ ] Include proper indexes for fast lookups
- [ ] Include foreign key constraints
- [ ] Support polymorphic relationships for various usable types
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Run phpcs validation with ArtisanPackUIStandard ruleset

## Technical Details

### media_usage Table

```php
Schema::create('media_usage', function (Blueprint $table) {
    $table->id();
    $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
    $table->morphs('usable'); // usable_type, usable_id
    $table->string('context')->nullable(); // e.g., 'featured_image', 'content', 'gallery'
    $table->text('metadata')->nullable(); // JSON for additional context
    $table->timestamp('last_used_at')->useCurrent();
    $table->timestamps();

    // Composite unique constraint to prevent duplicate tracking
    $table->unique(['media_id', 'usable_type', 'usable_id', 'context'], 'media_usage_unique');

    // Indexes
    $table->index(['media_id', 'last_used_at']);
    $table->index('last_used_at');
});
```

### Key Design Decisions

- **Polymorphic Relationship**: Allows tracking usage in any model (Page, Post, Product, etc.)
- **Context Field**: Identifies how the media is being used (featured image, gallery, content body, etc.)
- **Metadata Field**: Stores additional contextual information as JSON
- **last_used_at**: Tracks when the media was last accessed/used
- **Unique Constraint**: Prevents duplicate entries for the same media-usable-context combination

## Dependencies

- None - This is a foundational task

## Testing Requirements

- [ ] Create migration test to ensure table is created correctly
- [ ] Test foreign key constraints work
- [ ] Test unique constraints work
- [ ] Test polymorphic indexes work
- [ ] Verify all indexes are created
- [ ] Test that duplicate usage records are prevented

## Notes

- This table will grow over time, so proper indexing is critical
- Consider adding a cleanup mechanism for old/outdated usage records
- The metadata field can store information like:
  - Image size used (thumbnail, medium, large)
  - Position in gallery (order)
  - Alt text overrides
  - Custom CSS classes
- Usage tracking should be automatic (via model observers or events)
- Consider adding usage count to Media model as computed property

## File Locations

- Migration: `database/migrations/YYYY_MM_DD_create_media_usage_table.php`
- Test: `tests/Unit/MediaUsageMigrationTest.php`
