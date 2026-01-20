<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaPicker;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MediaPicker Component Tests
 *
 * Tests for the MediaPicker Livewire component including filtering,
 * selection, infinite scroll, and event dispatching functionality.
 *
 * @since   1.1.0
 */
class MediaPickerTest extends TestCase
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
            ->test(MediaPicker::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->assertSet('isOpen', false)
            ->assertSet('multiSelect', false)
            ->assertSet('maxSelections', 0)
            ->assertSet('selectedMedia', [])
            ->assertSet('acceptTypes', '')
            ->assertSet('loadCount', 20)
            ->assertSet('loadedCount', 20)
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('context', '');
    }

    /**
     * Test component can be mounted with custom options.
     */
    public function test_component_mounts_with_custom_options(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, [
                'multiSelect' => true,
                'maxSelections' => 5,
                'acceptTypes' => 'image/*',
                'loadCount' => 30,
                'context' => 'gallery',
                'isOpen' => true,
            ])
            ->assertSet('multiSelect', true)
            ->assertSet('maxSelections', 5)
            ->assertSet('acceptTypes', 'image/*')
            ->assertSet('loadCount', 30)
            ->assertSet('loadedCount', 30)
            ->assertSet('context', 'gallery')
            ->assertSet('isOpen', true);
    }

    /**
     * Test open and close functionality.
     */
    public function test_open_and_close(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->assertSet('isOpen', false)
            ->call('open')
            ->assertSet('isOpen', true)
            ->assertDispatched('media-picker-opened')
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertDispatched('media-picker-closed');
    }

    /**
     * Test open with context matching.
     */
    public function test_open_with_context_matching(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => 'featured-image'])
            ->assertSet('isOpen', false)
            ->call('open', 'other-context')
            ->assertSet('isOpen', false) // Should not open, context doesn't match
            ->call('open', 'featured-image')
            ->assertSet('isOpen', true); // Should open, context matches
    }

    /**
     * Test displays media items.
     */
    public function test_displays_media_items(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class);

        $media = $component->invade()->media();
        expect($media->count())->toBe(5);
    }

    /**
     * Test search filter.
     */
    public function test_search_filter(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Searchable Media']);
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Other Media']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->set('search', 'Searchable');

        $media = $component->invade()->media();
        expect($media->count())->toBe(1);
        expect($media->first()->title)->toBe('Searchable Media');
    }

    /**
     * Test folder filter.
     */
    public function test_folder_filter(): void
    {
        $folder = MediaFolder::factory()->create();

        Media::factory()->uploadedBy($this->user)->create(['folder_id' => $folder->id]);
        Media::factory()->uploadedBy($this->user)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->set('folderId', $folder->id);

        $media = $component->invade()->media();
        expect($media->count())->toBe(1);
    }

    /**
     * Test MIME type filter with wildcard.
     */
    public function test_accept_types_filter_wildcard(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'image/jpeg']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'image/png']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'video/mp4']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['acceptTypes' => 'image/*']);

        $media = $component->invade()->media();
        expect($media->count())->toBe(2);
    }

    /**
     * Test MIME type filter with exact match.
     */
    public function test_accept_types_filter_exact(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'image/jpeg']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'image/png']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'application/pdf']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['acceptTypes' => 'application/pdf']);

        $media = $component->invade()->media();
        expect($media->count())->toBe(1);
    }

    /**
     * Test MIME type filter with multiple types.
     */
    public function test_accept_types_filter_multiple(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'image/jpeg']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'video/mp4']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'application/pdf']);
        Media::factory()->uploadedBy($this->user)->create(['mime_type' => 'audio/mp3']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['acceptTypes' => 'image/*, application/pdf']);

        $media = $component->invade()->media();
        expect($media->count())->toBe(2);
    }

    /**
     * Test single select mode.
     */
    public function test_single_select_mode(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->call('toggleSelect', $media1->id)
            ->assertDispatched('media-picked'); // In single select, it should pick immediately
    }

    /**
     * Test multi select mode.
     */
    public function test_multi_select_mode(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            ->call('toggleSelect', $media1->id)
            ->assertSet('selectedMedia', [$media1->id])
            ->call('toggleSelect', $media2->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id]);
    }

    /**
     * Test multi select with max selections.
     */
    public function test_multi_select_with_max_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();
        $media3 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true, 'maxSelections' => 2])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id])
            ->call('toggleSelect', $media3->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id]); // Should not add third
    }

    /**
     * Test toggle deselect.
     */
    public function test_toggle_deselect(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            ->call('toggleSelect', $media->id)
            ->assertSet('selectedMedia', [$media->id])
            ->call('toggleSelect', $media->id)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test clear selections.
     */
    public function test_clear_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id])
            ->call('clearSelections')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test confirm selection dispatches event.
     */
    public function test_confirm_selection(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true, 'context' => 'test-context'])
            ->call('toggleSelect', $media->id)
            ->call('confirmSelection')
            ->assertDispatched('media-picked')
            ->assertDispatched('media-picker-closed')
            ->assertSet('isOpen', false)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test confirm selection does nothing when empty.
     */
    public function test_confirm_selection_does_nothing_when_empty(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            ->call('confirmSelection')
            ->assertNotDispatched('media-picked');
    }

    /**
     * Test load more for infinite scroll.
     */
    public function test_load_more(): void
    {
        Media::factory()->uploadedBy($this->user)->count(50)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 20])
            ->assertSet('loadedCount', 20);

        $media = $component->invade()->media();
        expect($media->count())->toBe(20);

        $component->call('loadMore')
            ->assertSet('loadedCount', 40);

        $media = $component->invade()->media();
        expect($media->count())->toBe(40);
    }

    /**
     * Test has more calculation.
     */
    public function test_has_more(): void
    {
        Media::factory()->uploadedBy($this->user)->count(30)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 20]);

        expect($component->invade()->hasMore())->toBeTrue();

        $component->call('loadMore');

        expect($component->invade()->hasMore())->toBeFalse();
    }

    /**
     * Test reset filters.
     */
    public function test_reset_filters(): void
    {
        $folder = MediaFolder::factory()->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->set('search', 'test')
            ->set('folderId', $folder->id)
            ->assertSet('search', 'test')
            ->assertSet('folderId', $folder->id)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('folderId', null);
    }

    /**
     * Test folders are loaded.
     */
    public function test_loads_folders(): void
    {
        MediaFolder::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class);

        $folders = $component->invade()->folders();
        expect($folders->count())->toBe(3);
    }

    /**
     * Test folder options includes all folders option.
     */
    public function test_folder_options(): void
    {
        MediaFolder::factory()->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class);

        $options = $component->invade()->folderOptions();
        expect(count($options))->toBe(3); // "All Folders" + 2 folders
        expect($options[0]['key'])->toBeNull();
        expect($options[0]['label'])->toBe(__('All Folders'));
    }

    /**
     * Test maxSelections is clamped.
     */
    public function test_max_selections_is_clamped(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['maxSelections' => 5000])
            ->assertSet('maxSelections', 1000); // Clamped to max 1000

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['maxSelections' => -5])
            ->assertSet('maxSelections', 0); // Clamped to min 0
    }

    /**
     * Test loadCount is clamped.
     */
    public function test_load_count_is_clamped(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 500])
            ->assertSet('loadCount', 100); // Clamped to max 100

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 0])
            ->assertSet('loadCount', 1); // Clamped to min 1
    }

    /**
     * Test search resets loaded count.
     */
    public function test_search_resets_loaded_count(): void
    {
        Media::factory()->uploadedBy($this->user)->count(50)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 20])
            ->call('loadMore')
            ->assertSet('loadedCount', 40)
            ->set('search', 'test')
            ->assertSet('loadedCount', 20); // Reset to initial loadCount
    }

    /**
     * Test folder change resets loaded count.
     */
    public function test_folder_change_resets_loaded_count(): void
    {
        $folder = MediaFolder::factory()->create();
        Media::factory()->uploadedBy($this->user)->count(50)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 20])
            ->call('loadMore')
            ->assertSet('loadedCount', 40)
            ->set('folderId', $folder->id)
            ->assertSet('loadedCount', 20); // Reset to initial loadCount
    }

    /**
     * Test handles media uploaded event.
     */
    public function test_handles_media_uploaded_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->dispatch('media-uploaded');
        // Should not throw - just refreshes the media list
    }

    /**
     * Test total count is computed correctly.
     */
    public function test_total_count(): void
    {
        Media::factory()->uploadedBy($this->user)->count(50)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['loadCount' => 20]);

        expect($component->invade()->totalCount())->toBe(50);
    }

    /**
     * Test total count respects filters.
     */
    public function test_total_count_respects_filters(): void
    {
        Media::factory()->uploadedBy($this->user)->count(10)->create(['mime_type' => 'image/jpeg']);
        Media::factory()->uploadedBy($this->user)->count(5)->create(['mime_type' => 'video/mp4']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['acceptTypes' => 'image/*']);

        expect($component->invade()->totalCount())->toBe(10);
    }
}
