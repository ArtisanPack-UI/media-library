# Advanced Search - Implementation

## Description

Enhance the media search functionality with advanced filters including date ranges, file size ranges, dimensions, orientation, usage status, and full-text search capabilities.

## Acceptance Criteria

- [ ] Create MediaSearchService for advanced search logic
- [ ] Implement date range filtering (uploaded between dates)
- [ ] Implement file size range filtering
- [ ] Implement dimension filtering (width/height ranges)
- [ ] Implement orientation filtering (portrait, landscape, square)
- [ ] Implement usage status filtering (used, unused)
- [ ] Implement full-text search across metadata
- [ ] Add support for saved searches
- [ ] Enhance API endpoints with advanced filters
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Create comprehensive tests

## Technical Details

### MediaSearchService

```php
class MediaSearchService
{
    /**
     * Perform advanced search on media.
     */
    public function search(array $filters): Builder
    {
        $query = Media::query();

        // Basic search (existing)
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('file_name', 'like', "%{$filters['search']}%")
                  ->orWhere('alt_text', 'like', "%{$filters['search']}%")
                  ->orWhere('caption', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // File size range filter
        if (!empty($filters['size_min'])) {
            $query->where('file_size', '>=', $filters['size_min']);
        }
        if (!empty($filters['size_max'])) {
            $query->where('file_size', '<=', $filters['size_max']);
        }

        // Dimension filters
        if (!empty($filters['width_min'])) {
            $query->where('width', '>=', $filters['width_min']);
        }
        if (!empty($filters['width_max'])) {
            $query->where('width', '<=', $filters['width_max']);
        }
        if (!empty($filters['height_min'])) {
            $query->where('height', '>=', $filters['height_min']);
        }
        if (!empty($filters['height_max'])) {
            $query->where('height', '<=', $filters['height_max']);
        }

        // Orientation filter
        if (!empty($filters['orientation'])) {
            $query->where(function($q) use ($filters) {
                switch ($filters['orientation']) {
                    case 'portrait':
                        $q->whereColumn('height', '>', 'width');
                        break;
                    case 'landscape':
                        $q->whereColumn('width', '>', 'height');
                        break;
                    case 'square':
                        $q->whereColumn('width', '=', 'height');
                        break;
                }
            });
        }

        // Usage status filter
        if (isset($filters['is_used'])) {
            if ($filters['is_used']) {
                $query->whereHas('usage');
            } else {
                $query->whereDoesntHave('usage');
            }
        }

        // Uploader filter
        if (!empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        // MIME type group filters
        if (!empty($filters['mime_group'])) {
            $query->where('mime_type', 'like', "{$filters['mime_group']}/%");
        }

        // Folder filter
        if (isset($filters['folder_id'])) {
            if (null === $filters['folder_id']) {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $filters['folder_id']);
            }
        }

        // Tag filter
        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function($q) use ($filters) {
                $q->whereIn('slug', $filters['tags']);
            });
        }

        // Collection filter
        if (!empty($filters['collection_id'])) {
            $query->whereHas('collections', function($q) use ($filters) {
                $q->where('media_collections.id', $filters['collection_id']);
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * Get search filter options.
     */
    public function getFilterOptions(): array
    {
        return [
            'orientations' => ['portrait', 'landscape', 'square'],
            'mime_groups' => ['image', 'video', 'audio', 'application'],
            'sort_fields' => ['created_at', 'title', 'file_name', 'file_size', 'width', 'height'],
            'sort_orders' => ['asc', 'desc'],
        ];
    }

    /**
     * Save a search for later use.
     */
    public function saveSearch(string $name, array $filters, int $userId): SavedSearch
    {
        return SavedSearch::create([
            'name' => $name,
            'filters' => $filters,
            'user_id' => $userId,
        ]);
    }

    /**
     * Load a saved search.
     */
    public function loadSearch(int $savedSearchId): ?array
    {
        $savedSearch = SavedSearch::find($savedSearchId);
        return $savedSearch?->filters;
    }

    /**
     * Get user's saved searches.
     */
    public function getUserSavedSearches(int $userId): Collection
    {
        return SavedSearch::where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }
}
```

### SavedSearch Model

```php
class SavedSearch extends Model
{
    protected $fillable = ['name', 'filters', 'user_id'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('artisanpack.media.user_model', User::class));
    }
}
```

### Migration for Saved Searches

```php
Schema::create('media_saved_searches', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->json('filters');
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();

    $table->index('user_id');
});
```

### Enhanced API Endpoint

```php
// In MediaController

public function index(Request $request): AnonymousResourceCollection
{
    $filters = $request->only([
        'search',
        'date_from',
        'date_to',
        'size_min',
        'size_max',
        'width_min',
        'width_max',
        'height_min',
        'height_max',
        'orientation',
        'is_used',
        'uploaded_by',
        'mime_group',
        'folder_id',
        'tags',
        'collection_id',
        'sort_by',
        'sort_order',
    ]);

    $query = app(MediaSearchService::class)->search($filters);

    $media = $query->with(['folder', 'uploadedBy', 'tags'])
        ->paginate($request->input('per_page', 15));

    return MediaResource::collection($media);
}
```

## Dependencies

- Usage tracking (for is_used filter) - Task 06
- Collections (for collection filter) - Task 02

## Testing Requirements

- [ ] Test basic text search
- [ ] Test date range filtering
- [ ] Test file size range filtering
- [ ] Test dimension filtering
- [ ] Test orientation filtering (portrait, landscape, square)
- [ ] Test usage status filtering
- [ ] Test uploader filtering
- [ ] Test MIME group filtering
- [ ] Test folder filtering
- [ ] Test tag filtering
- [ ] Test collection filtering
- [ ] Test multiple filters combined
- [ ] Test saved search creation
- [ ] Test saved search loading
- [ ] Test API endpoint with all filters
- [ ] Create MediaSearchServiceTest with 25+ tests

## Notes

- Advanced search should maintain existing simple search functionality
- Consider adding these additional filters in future:
  - Color search (dominant colors in images)
  - EXIF data search (camera model, ISO, etc.)
  - GPS location search
  - Face detection
- Saved searches are user-specific
- Consider adding shared/public saved searches for teams
- Add validation for filter values to prevent invalid queries
- Use query builder efficiently to avoid N+1 problems
- Consider adding search presets:
  - "Large Images" (> 2MB, > 1920x1080)
  - "Recent Uploads" (last 7 days)
  - "Unused Media" (not in usage tracking)
  - "Portrait Photos" (height > width, image type)

## File Locations

- Service: `src/Services/MediaSearchService.php`
- Model: `src/Models/SavedSearch.php`
- Migration: `database/migrations/YYYY_MM_DD_create_media_saved_searches_table.php`
- Controller Update: `src/Http/Controllers/MediaController.php`
- Tests: `tests/Unit/MediaSearchServiceTest.php`
