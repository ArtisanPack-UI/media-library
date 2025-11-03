# Collections/Albums - API Endpoints

## Description

Create RESTful API endpoints for managing collections including CRUD operations, media management, and reordering functionality.

## Acceptance Criteria

- [ ] Create MediaCollectionController with all CRUD methods
- [ ] Implement collection media management endpoints (add/remove/sync/reorder)
- [ ] Create form request classes for validation
- [ ] Create MediaCollectionResource for API responses
- [ ] Add API routes with proper middleware
- [ ] Implement authorization using MediaCollectionPolicy
- [ ] Follow ArtisanPack UI Code Standards
- [ ] Run phpcs validation

## Technical Details

### Controller Endpoints

```php
class MediaCollectionController extends Controller
{
    // Standard CRUD
    public function index(Request $request): AnonymousResourceCollection
    public function store(MediaCollectionStoreRequest $request): MediaCollectionResource
    public function show(int $id): MediaCollectionResource
    public function update(MediaCollectionUpdateRequest $request, int $id): MediaCollectionResource
    public function destroy(int $id): Response

    // Media Management
    public function addMedia(Request $request, int $id): MediaCollectionResource
    public function removeMedia(Request $request, int $id): MediaCollectionResource
    public function syncMedia(Request $request, int $id): MediaCollectionResource
    public function reorderMedia(Request $request, int $id): MediaCollectionResource

    // Additional Features
    public function setCoverImage(Request $request, int $id): MediaCollectionResource
}
```

### Form Requests

**MediaCollectionStoreRequest:**
- name: required, string, max 255
- slug: nullable, string, max 255, unique
- description: nullable, string
- cover_image_id: nullable, exists:media,id
- media_ids: nullable, array (for creating with media)

**MediaCollectionUpdateRequest:**
- Same as store but all fields optional except name

### API Routes

```php
Route::middleware('auth:sanctum')->prefix('media/collections')->group(function () {
    Route::get('/', [MediaCollectionController::class, 'index']);
    Route::post('/', [MediaCollectionController::class, 'store']);
    Route::get('/{id}', [MediaCollectionController::class, 'show']);
    Route::put('/{id}', [MediaCollectionController::class, 'update']);
    Route::delete('/{id}', [MediaCollectionController::class, 'destroy']);

    Route::post('/{id}/media/add', [MediaCollectionController::class, 'addMedia']);
    Route::post('/{id}/media/remove', [MediaCollectionController::class, 'removeMedia']);
    Route::post('/{id}/media/sync', [MediaCollectionController::class, 'syncMedia']);
    Route::post('/{id}/media/reorder', [MediaCollectionController::class, 'reorderMedia']);
    Route::post('/{id}/cover-image', [MediaCollectionController::class, 'setCoverImage']);
});
```

## Dependencies

- Task 01: Collections database schema
- Task 02: Collections models

## Testing Requirements

- [ ] Test listing collections with pagination
- [ ] Test creating collections
- [ ] Test updating collections
- [ ] Test deleting collections
- [ ] Test adding media to collections
- [ ] Test removing media from collections
- [ ] Test syncing media
- [ ] Test reordering media
- [ ] Test setting cover images
- [ ] Test validation errors
- [ ] Test authorization checks
- [ ] Create MediaCollectionControllerTest with 20+ tests

## Notes

- Follow the same patterns used in MediaController and MediaFolderController
- All media management endpoints should return the updated collection resource
- Reordering should accept array of media IDs in desired order
- Use eager loading to prevent N+1 queries
- Validate that cover_image_id exists in the collection's media
- All authorization should use MediaCollectionPolicy with hook integration

## File Locations

- Controller: `src/Http/Controllers/MediaCollectionController.php`
- Requests: `src/Http/Requests/MediaCollectionStoreRequest.php`, `MediaCollectionUpdateRequest.php`
- Resource: `src/Http/Resources/MediaCollectionResource.php`
- Policy: `src/Policies/MediaCollectionPolicy.php`
- Routes: `src/routes/api.php` (add to existing file)
- Tests: `tests/Feature/MediaCollectionControllerTest.php`
