<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MediaModal Component Tests
 *
 * Tests for the MediaModal Livewire component including opening/closing,
 * selection modes, filtering, and media selection functionality.
 *
 * @since   1.0.0
 */
class MediaModalTest extends TestCase
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
            ->test(MediaModal::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('isOpen', false)
            ->assertSet('multiSelect', false)
            ->assertSet('maxSelections', 0)
            ->assertSet('selectedMedia', [])
            ->assertSet('activeTab', 'library')
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('typeFilter', '')
            ->assertSet('perPage', 12)
            ->assertSet('context', '');
    }

    /**
     * Test that modal opens when triggered.
     */
    public function test_modal_opens_when_triggered(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('isOpen', false)
            ->call('open')
            ->assertSet('isOpen', true);
    }

    /**
     * Test that modal opens via event.
     */
    public function test_modal_opens_via_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-media-modal')
            ->assertSet('isOpen', true);
    }

    /**
     * Test that modal opens with specific context.
     */
    public function test_modal_opens_with_context(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['context' => 'featured-image'])
            ->dispatch('open-media-modal', context: 'featured-image')
            ->assertSet('isOpen', true);
    }

    /**
     * Test that modal ignores open event with different context.
     */
    public function test_modal_ignores_different_context(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['context' => 'featured-image'])
            ->dispatch('open-media-modal', context: 'gallery-images')
            ->assertSet('isOpen', false);
    }

    /**
     * Test that modal closes correctly.
     */
    public function test_modal_closes_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test single select mode.
     */
    public function test_single_select_mode(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => false])
            ->call('toggleSelect', $media1->id)
            ->assertSet('selectedMedia', [$media1->id])
            ->call('toggleSelect', $media2->id)
            ->assertSet('selectedMedia', [$media2->id]);
    }

    /**
     * Test multi-select mode.
     */
    public function test_multi_select_mode(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id]);
    }

    /**
     * Test multi-select respects max selections.
     */
    public function test_multi_select_respects_max_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();
        $media3 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true, 'maxSelections' => 2])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->call('toggleSelect', $media3->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id]);
    }

    /**
     * Test deselect media item.
     */
    public function test_deselect_media_item(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true])
            ->call('toggleSelect', $media->id)
            ->assertSet('selectedMedia', [$media->id])
            ->call('toggleSelect', $media->id)
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test clear all selections.
     */
    public function test_clear_all_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->call('clearSelections')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test confirm selection dispatches event.
     */
    public function test_confirm_selection_dispatches_event(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['context' => 'test-context'])
            ->call('toggleSelect', $media->id)
            ->call('confirmSelection')
            ->assertDispatched('media-selected');
    }

    /**
     * Test confirm selection requires at least one selection.
     */
    public function test_confirm_selection_requires_selection(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('confirmSelection')
            ->assertNotDispatched('media-selected');
    }

    /**
     * Test search filter.
     */
    public function test_search_filter_works(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Searchable Item']);
        Media::factory()->uploadedBy($this->user)->create(['title' => 'Other Item']);

        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('search', 'Searchable');

        $media = $component->invade()->media();
        expect($media->total())->toBe(1);
    }

    /**
     * Test type filter for images.
     */
    public function test_type_filter_for_images(): void
    {
        Media::factory()->uploadedBy($this->user)->image()->count(3)->create();
        Media::factory()->uploadedBy($this->user)->video()->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('typeFilter', 'image');

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
            ->test(MediaModal::class)
            ->set('typeFilter', 'video');

        $media = $component->invade()->media();
        expect($media->total())->toBe(2);
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
            ->test(MediaModal::class)
            ->set('folderId', $folder->id);

        $media = $component->invade()->media();
        expect($media->total())->toBe(3);
    }

    /**
     * Test reset filters.
     */
    public function test_reset_filters(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('search', 'test')
            ->set('folderId', $folder->id)
            ->set('typeFilter', 'image')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('typeFilter', '');
    }

    /**
     * Test tab switching.
     */
    public function test_tab_switching(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('activeTab', 'library')
            ->call('switchTab', 'upload')
            ->assertSet('activeTab', 'upload')
            ->call('switchTab', 'library')
            ->assertSet('activeTab', 'library');
    }

    /**
     * Test media-uploaded event refreshes media and switches tab.
     */
    public function test_media_uploaded_event_switches_to_library(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('activeTab', 'upload')
            ->dispatch('media-uploaded')
            ->assertSet('activeTab', 'library');
    }

    /**
     * Test type filter options are available.
     */
    public function test_type_filter_options_available(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class);

        $options = $component->invade()->typeFilterOptions();
        expect($options)->toBeArray();
        expect(count($options))->toBe(5);
    }

    /**
     * Test folder options are available.
     */
    public function test_folder_options_available(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class);

        $options = $component->invade()->folderOptions();
        expect($options)->toBeArray();
        expect(count($options))->toBeGreaterThanOrEqual(3);
    }

    /**
     * Test updating search resets pagination.
     */
    public function test_updating_search_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('search', 'test')
            ->assertSet('search', 'test');
    }

    /**
     * Test updating folder filter resets pagination.
     */
    public function test_updating_folder_resets_pagination(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('folderId', $folder->id)
            ->assertSet('folderId', $folder->id);
    }

    /**
     * Test updating type filter resets pagination.
     */
    public function test_updating_type_filter_resets_pagination(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('typeFilter', 'image')
            ->assertSet('typeFilter', 'image');
    }

    /**
     * Test mounting with pre-selected media.
     */
    public function test_mounting_with_preselected_media(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['selectedMedia' => [$media->id]])
            ->assertSet('selectedMedia', [$media->id]);
    }
}
