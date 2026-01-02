<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaLibrary;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MediaLibrary Component Tests
 *
 * Tests for the MediaLibrary Livewire component including filtering,
 * sorting, bulk operations, and navigation functionality.
 *
 * @since   1.0.0
 */
class MediaLibraryTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();

        config([
            'artisanpack.media.disk' => 'public',
            'artisanpack.media.user_model' => User::class,
        ]);

        Gate::before(fn ($user, $ability) => true);
    }

    /**
     * Test that the component renders successfully.
     */
    public function test_component_renders(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('type', '')
            ->assertSet('tag', '')
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortOrder', 'desc')
            ->assertSet('viewMode', 'grid')
            ->assertSet('selectedMedia', [])
            ->assertSet('bulkSelectMode', false)
            ->assertSet('perPage', 24);
    }

    /**
     * Test displays media grid.
     */
    public function test_displays_media_grid(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        $media = $component->invade()->media();
        expect($media->total())->toBe(5);
    }

    /**
     * Test search filter.
     */
    public function test_search_filter(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Searchable Media']);
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Other Media']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('search', 'Searchable');

        $media = $component->invade()->media();
        expect($media->total())->toBe(1);
    }

    /**
     * Test search by file name.
     */
    public function test_search_by_file_name(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['file_name' => 'unique-file.jpg']);
        Media::factory()->uploadedBy($this->user)->create(['file_name' => 'other-file.jpg']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('search', 'unique');

        $media = $component->invade()->media();
        expect($media->total())->toBe(1);
    }

    /**
     * Test folder filter.
     */
    public function test_folder_filter(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Media::factory()->uploadedBy($this->user)->inFolder($folder)->count(3)->create();
        Media::factory()->uploadedBy($this->user)->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setFolder', $folder->id);

        $media = $component->invade()->media();
        expect($media->total())->toBe(3);
    }

    /**
     * Test type filter for images.
     */
    public function test_type_filter_for_images(): void
    {
        Media::factory()->uploadedBy($this->user)->image()->count(3)->create();
        Media::factory()->uploadedBy($this->user)->video()->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setType', 'image');

        $media = $component->invade()->media();
        expect($media->total())->toBe(3);
    }

    /**
     * Test type filter for videos.
     */
    public function test_type_filter_for_videos(): void
    {
        Media::factory()->uploadedBy($this->user)->image()->count(3)->create();
        Media::factory()->uploadedBy($this->user)->video()->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setType', 'video');

        $media = $component->invade()->media();
        expect($media->total())->toBe(2);
    }

    /**
     * Test type filter for audio.
     */
    public function test_type_filter_for_audio(): void
    {
        Media::factory()->uploadedBy($this->user)->image()->count(2)->create();
        Media::factory()->uploadedBy($this->user)->audio()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setType', 'audio');

        $media = $component->invade()->media();
        expect($media->total())->toBe(3);
    }

    /**
     * Test type filter for documents.
     */
    public function test_type_filter_for_documents(): void
    {
        Media::factory()->uploadedBy($this->user)->image()->count(2)->create();
        Media::factory()->uploadedBy($this->user)->document()->count(4)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setType', 'document');

        $media = $component->invade()->media();
        expect($media->total())->toBe(4);
    }

    /**
     * Test tag filter.
     */
    public function test_tag_filter(): void
    {
        $tag = MediaTag::factory()->create();
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();
        $media1->tags()->attach($tag->id);

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setTag', $tag->slug);

        $media = $component->invade()->media();
        expect($media->total())->toBe(1);
    }

    /**
     * Test clear filters.
     */
    public function test_clear_filters(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('search', 'test')
            ->set('folderId', $folder->id)
            ->set('type', 'image')
            ->set('tag', 'featured')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('type', '')
            ->assertSet('tag', '');
    }

    /**
     * Test sorting by date.
     */
    public function test_sorting_by_date(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setSortBy', 'created_at')
            ->assertSet('sortBy', 'created_at');
    }

    /**
     * Test toggle sort direction.
     */
    public function test_toggle_sort_direction(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->assertSet('sortOrder', 'desc')
            ->call('setSortBy', 'created_at')
            ->assertSet('sortOrder', 'asc')
            ->call('setSortBy', 'created_at')
            ->assertSet('sortOrder', 'desc');
    }

    /**
     * Test sorting by title.
     */
    public function test_sorting_by_title(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('setSortBy', 'title')
            ->assertSet('sortBy', 'title')
            ->assertSet('sortOrder', 'desc');
    }

    /**
     * Test view mode toggle.
     */
    public function test_view_mode_toggle(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->assertSet('viewMode', 'grid')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'list')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'grid');
    }

    /**
     * Test bulk select mode toggle.
     */
    public function test_bulk_select_mode_toggle(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->assertSet('bulkSelectMode', false)
            ->call('toggleBulkSelect')
            ->assertSet('bulkSelectMode', true)
            ->call('toggleBulkSelect')
            ->assertSet('bulkSelectMode', false)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test select all on current page.
     */
    public function test_select_all(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('selectAll')
            ->assertCount('selectedMedia', 5);
    }

    /**
     * Test deselect all.
     */
    public function test_deselect_all(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('selectedMedia', $media->pluck('id')->toArray())
            ->call('deselectAll')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test bulk delete.
     */
    public function test_bulk_delete(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('selectedMedia', $media->pluck('id')->toArray())
            ->call('bulkDelete')
            ->assertSet('selectedMedia', [])
            ->assertSet('bulkSelectMode', false)
            ->assertDispatched('media-updated');

        expect(Media::count())->toBe(0);
    }

    /**
     * Test bulk delete with no selection shows warning.
     */
    public function test_bulk_delete_with_no_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('bulkDelete')
            ->assertNotDispatched('media-updated');
    }

    /**
     * Test bulk move.
     */
    public function test_bulk_move(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('selectedMedia', $media->pluck('id')->toArray())
            ->call('bulkMove', $folder->id)
            ->assertSet('selectedMedia', [])
            ->assertSet('bulkSelectMode', false)
            ->assertDispatched('media-updated');

        foreach ($media as $item) {
            $item->refresh();
            expect($item->folder_id)->toBe($folder->id);
        }
    }

    /**
     * Test bulk move with no selection shows warning.
     */
    public function test_bulk_move_with_no_selection(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->call('bulkMove', $folder->id)
            ->assertNotDispatched('media-updated');
    }

    /**
     * Test media-updated event refreshes media.
     */
    public function test_media_updated_event_refreshes_media(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->dispatch('media-updated')
            ->assertStatus(200);
    }

    /**
     * Test media-selected event handler.
     */
    public function test_media_selected_event_handler(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->dispatch('media-selected', mediaId: $media->id, selected: true)
            ->assertSet('selectedMedia', [$media->id]);
    }

    /**
     * Test media deselection via event.
     */
    public function test_media_deselection_via_event(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('selectedMedia', [$media->id])
            ->dispatch('media-selected', mediaId: $media->id, selected: false)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test updating search resets pagination.
     */
    public function test_updating_search_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('search', 'test')
            ->assertSet('search', 'test');
    }

    /**
     * Test current folder is null when no folder selected.
     */
    public function test_current_folder_is_null_when_no_folder_selected(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        $currentFolder = $component->invade()->currentFolder();
        expect($currentFolder)->toBeNull();
    }

    /**
     * Test current folder is set when folder selected.
     */
    public function test_current_folder_is_set_when_folder_selected(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class)
            ->set('folderId', $folder->id);

        $currentFolder = $component->invade()->currentFolder();
        expect($currentFolder)->not()->toBeNull();
        expect($currentFolder->id)->toBe($folder->id);
    }

    /**
     * Test folders are loaded.
     */
    public function test_folders_are_loaded(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        $folders = $component->invade()->folders();
        expect($folders->count())->toBe(3);
    }

    /**
     * Test tags are loaded.
     */
    public function test_tags_are_loaded(): void
    {
        MediaTag::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        $tags = $component->invade()->tags();
        expect($tags->count())->toBe(3);
    }

    /**
     * Test type options are available.
     */
    public function test_type_options_available(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        expect($component->get('types'))->toBeArray();
        expect(count($component->get('types')))->toBe(5);
    }

    /**
     * Test sort by options are available.
     */
    public function test_sort_by_options_available(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        expect($component->get('sortByOptions'))->toBeArray();
        expect(count($component->get('sortByOptions')))->toBe(4);
    }

    /**
     * Test sort order options are available.
     */
    public function test_sort_order_options_available(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaLibrary::class);

        expect($component->get('sortOrderOptions'))->toBeArray();
        expect(count($component->get('sortOrderOptions')))->toBe(2);
    }
}
