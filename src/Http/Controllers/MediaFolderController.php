<?php

namespace ArtisanPackUI\MediaLibrary\Http\Controllers;

use ArtisanPackUI\MediaLibrary\Http\Requests\MediaFolderStoreRequest;
use ArtisanPackUI\MediaLibrary\Http\Requests\MediaFolderUpdateRequest;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Media Folder Controller
 *
 * Handles API endpoints for media folder management including listing,
 * creating, updating, deleting, and moving folders.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Http\Controllers
 */
class MediaFolderController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of media folders.
     *
     * Returns all folders with their relationships.
     *
     * @param  Request  $request  The HTTP request instance.
     * @return JsonResponse The folders collection.
     */
    public function index(Request $request): JsonResponse
    {
        $folders = MediaFolder::query()
            ->with(['parent', 'children', 'creator'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => $folders,
        ]);
    }

    /**
     * Store a newly created folder.
     *
     * @param  MediaFolderStoreRequest  $request  The validated request.
     * @return JsonResponse The created folder.
     */
    public function store(MediaFolderStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Generate slug from name
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = auth()->id();

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (MediaFolder::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug.'-'.$counter;
            $counter++;
        }

        $folder = MediaFolder::create($data);

        return response()->json([
            'data' => $folder->load(['parent', 'children', 'creator']),
            'message' => 'Folder created successfully',
        ], 201);
    }

    /**
     * Display the specified folder.
     *
     * @param  int  $id  The folder ID.
     * @return JsonResponse The folder data.
     */
    public function show(int $id): JsonResponse
    {
        $folder = MediaFolder::with(['parent', 'children', 'creator', 'media'])->findOrFail($id);

        return response()->json([
            'data' => $folder,
        ]);
    }

    /**
     * Remove the specified folder.
     *
     * @param  int  $id  The folder ID.
     * @return Response|JsonResponse The response with no content or error message.
     */
    public function destroy(int $id): Response|JsonResponse
    {
        $folder = MediaFolder::findOrFail($id);

        // Check if folder has children
        if ($folder->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete folder with subfolders. Please delete or move subfolders first.',
            ], 422);
        }

        // Check if folder has media
        if ($folder->media()->exists()) {
            return response()->json([
                'message' => 'Cannot delete folder with media items. Please delete or move media first.',
            ], 422);
        }

        $folder->delete();

        return response()->noContent();
    }

    /**
     * Move a folder to a new parent.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  int  $id  The folder ID to move.
     * @return JsonResponse The updated folder.
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'parent_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $folder = MediaFolder::findOrFail($id);
        $parentId = $request->input('parent_id');

        // Prevent moving folder into itself or its descendants
        if ($parentId !== null) {
            $parent = MediaFolder::findOrFail($parentId);

            // Check if target parent is a descendant
            $descendants = $folder->descendants();
            if ($descendants->contains('id', $parentId) || $folder->id === $parentId) {
                return response()->json([
                    'message' => 'Cannot move folder into itself or its descendants.',
                ], 422);
            }
        }

        $folder->update(['parent_id' => $parentId]);

        return response()->json([
            'data' => $folder->load(['parent', 'children', 'creator']),
            'message' => 'Folder moved successfully',
        ]);
    }

    /**
     * Update the specified folder.
     *
     * @param  MediaFolderUpdateRequest  $request  The validated request.
     * @param  int  $id  The folder ID.
     * @return JsonResponse The updated folder.
     */
    public function update(MediaFolderUpdateRequest $request, int $id): JsonResponse
    {
        $folder = MediaFolder::findOrFail($id);
        $data = $request->validated();

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $folder->name) {
            $data['slug'] = Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (MediaFolder::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                $data['slug'] = $originalSlug.'-'.$counter;
                $counter++;
            }
        }

        $folder->update($data);

        return response()->json([
            'data' => $folder->load(['parent', 'children', 'creator']),
            'message' => 'Folder updated successfully',
        ]);
    }
}
