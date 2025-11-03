# Collections/Albums - Database Schema

## Description

Create the database schema for the Collections/Albums feature. Collections allow users to group related media items together into organized albums or galleries.

## Acceptance Criteria

- [ ] Create `media_collections` migration with all required fields
- [ ] Create `media_collectionables` pivot table migration
- [ ] Include proper indexes for performance
- [ ] Include foreign key constraints
- [ ] Support soft deletes for collections
- [ ] Follow ArtisanPack UI Code Standards (Yoda conditionals, spacing, type declarations)
- [ ] Run phpcs validation with ArtisanPackUIStandard ruleset

## Technical Details

### media_collections Table

```php
Schema::create('media_collections', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('cover_image_id')->nullable(); // Featured/cover image
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('slug');
    $table->index('created_by');
    $table->index('created_at');
});
```

### media_collectionables Pivot Table

```php
Schema::create('media_collectionables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('media_collection_id')->constrained('media_collections')->cascadeOnDelete();
    $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
    $table->unsignedInteger('order')->default(0); // For manual ordering
    $table->timestamps();

    // Composite unique constraint
    $table->unique(['media_collection_id', 'media_id']);

    // Indexes
    $table->index('order');
});
```

## Dependencies

- None - This is a foundational task

## Testing Requirements

- [ ] Create migration test to ensure tables are created correctly
- [ ] Test foreign key constraints work
- [ ] Test unique constraints work
- [ ] Test soft deletes work on collections
- [ ] Verify indexes are created

## Notes

- Collections are similar to folders but allow many-to-many relationships (media can be in multiple collections)
- The `order` column allows users to manually arrange media within a collection
- Cover image is optional but useful for displaying collection thumbnails
- Soft deletes allow recovery of accidentally deleted collections
- Follow the same pattern as existing media-library migrations

## File Locations

- Migration 1: `database/migrations/YYYY_MM_DD_create_media_collections_table.php`
- Migration 2: `database/migrations/YYYY_MM_DD_create_media_collectionables_table.php`
