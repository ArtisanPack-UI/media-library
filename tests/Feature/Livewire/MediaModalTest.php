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

    // =========================================================================
    // Visual Editor Features Tests (Issue #51)
    // =========================================================================

    /**
     * Test inline mode initial state.
     */
    public function test_inline_mode_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['inlineMode' => true])
            ->assertSet('inlineMode', true);
    }

    /**
     * Test default inline mode is false.
     */
    public function test_default_inline_mode_is_false(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('inlineMode', false);
    }

    /**
     * Test quick upload select is enabled by default.
     */
    public function test_quick_upload_select_enabled_by_default(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('quickUploadSelect', true);
    }

    /**
     * Test quick upload select can be disabled.
     */
    public function test_quick_upload_select_can_be_disabled(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['quickUploadSelect' => false])
            ->assertSet('quickUploadSelect', false);
    }

    /**
     * Test recently used loads from session on mount.
     */
    public function test_recently_used_loads_from_session(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        session(['media.recently_used' => [$media->id]]);

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('recentlyUsed', [$media->id]);
    }

    /**
     * Test track usage adds media to recently used.
     */
    public function test_track_usage_adds_media_to_recently_used(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('trackUsage', $media->id)
            ->assertSet('recentlyUsed', [$media->id]);

        expect(session('media.recently_used'))->toBe([$media->id]);
    }

    /**
     * Test track usage moves existing media to front.
     */
    public function test_track_usage_moves_existing_to_front(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        session(['media.recently_used' => [$media1->id, $media2->id]]);

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('trackUsage', $media2->id)
            ->assertSet('recentlyUsed', [$media2->id, $media1->id]);
    }

    /**
     * Test track usage limits to 10 items.
     */
    public function test_track_usage_limits_to_ten_items(): void
    {
        $mediaIds = Media::factory()->uploadedBy($this->user)->count(12)->create()->pluck('id')->toArray();

        session(['media.recently_used' => array_slice($mediaIds, 0, 10)]);

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('trackUsage', $mediaIds[11]);

        expect(count(session('media.recently_used')))->toBe(10);
        expect(session('media.recently_used')[0])->toBe($mediaIds[11]);
    }

    /**
     * Test recently used media computed property.
     */
    public function test_recently_used_media_computed(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        session(['media.recently_used' => [$media1->id, $media2->id]]);

        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class);

        $recentMedia = $component->invade()->recentlyUsedMedia();

        expect($recentMedia)->toHaveCount(2);
        expect($recentMedia->first()->id)->toBe($media1->id);
    }

    /**
     * Test recently used media returns empty collection when none.
     */
    public function test_recently_used_media_returns_empty_when_none(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(MediaModal::class);

        $recentMedia = $component->invade()->recentlyUsedMedia();

        expect($recentMedia)->toHaveCount(0);
    }

    /**
     * Test confirm selection tracks usage.
     */
    public function test_confirm_selection_tracks_usage(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('toggleSelect', $media->id)
            ->call('confirmSelection');

        expect(session('media.recently_used'))->toContain($media->id);
    }

    /**
     * Test quick upload select in single mode confirms immediately.
     */
    public function test_quick_upload_select_single_mode_confirms(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => false, 'quickUploadSelect' => true])
            ->call('open')
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertDispatched('media-selected');
    }

    /**
     * Test quick upload select in multi mode adds to selection.
     */
    public function test_quick_upload_select_multi_mode_adds_selection(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true, 'quickUploadSelect' => true])
            ->call('open')
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertSet('selectedMedia', [$media->id])
            ->assertNotDispatched('media-selected');
    }

    /**
     * Test quick upload select disabled does not auto-select.
     */
    public function test_quick_upload_select_disabled(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['quickUploadSelect' => false])
            ->call('open')
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertSet('selectedMedia', [])
            ->assertNotDispatched('media-selected');
    }

    /**
     * Test quick upload select respects max selections.
     */
    public function test_quick_upload_select_respects_max_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();
        $media3 = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true, 'maxSelections' => 2, 'quickUploadSelect' => true])
            ->call('toggleSelect', $media1->id)
            ->call('toggleSelect', $media2->id)
            ->dispatch('media-uploaded', mediaId: $media3->id)
            ->assertSet('selectedMedia', [$media1->id, $media2->id]);
    }

    // =========================================================================
    // Keyboard Navigation Tests
    // =========================================================================

    /**
     * Test focus index initial state.
     */
    public function test_focus_index_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('focusedIndex', -1);
    }

    /**
     * Test focus next moves to next item.
     */
    public function test_focus_next_moves_to_next_item(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 0)
            ->call('focusNext')
            ->assertSet('focusedIndex', 1);
    }

    /**
     * Test focus next wraps around.
     */
    public function test_focus_next_wraps_around(): void
    {
        Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 2)
            ->call('focusNext')
            ->assertSet('focusedIndex', 0);
    }

    /**
     * Test focus next with no media.
     */
    public function test_focus_next_with_no_media(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', -1)
            ->call('focusNext')
            ->assertSet('focusedIndex', -1);
    }

    /**
     * Test focus previous moves to previous item.
     */
    public function test_focus_previous_moves_to_previous_item(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 2)
            ->call('focusPrevious')
            ->assertSet('focusedIndex', 1);
    }

    /**
     * Test focus previous wraps to end.
     */
    public function test_focus_previous_wraps_to_end(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 0)
            ->call('focusPrevious')
            ->assertSet('focusedIndex', 4);
    }

    /**
     * Test focus down moves to next row.
     */
    public function test_focus_down_moves_to_next_row(): void
    {
        Media::factory()->uploadedBy($this->user)->count(12)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 1)
            ->call('focusDown', 5)
            ->assertSet('focusedIndex', 6);
    }

    /**
     * Test focus down stays at last row if would exceed.
     */
    public function test_focus_down_stays_at_last_row(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 3)
            ->call('focusDown', 5)
            ->assertSet('focusedIndex', 3);
    }

    /**
     * Test focus up moves to previous row.
     */
    public function test_focus_up_moves_to_previous_row(): void
    {
        Media::factory()->uploadedBy($this->user)->count(12)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 6)
            ->call('focusUp', 5)
            ->assertSet('focusedIndex', 1);
    }

    /**
     * Test focus up stays at first row if would go negative.
     */
    public function test_focus_up_stays_at_first_row(): void
    {
        Media::factory()->uploadedBy($this->user)->count(12)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 2)
            ->call('focusUp', 5)
            ->assertSet('focusedIndex', 2);
    }

    /**
     * Test focus up does nothing if no focus.
     */
    public function test_focus_up_does_nothing_if_no_focus(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('focusUp', 5)
            ->assertSet('focusedIndex', -1);
    }

    /**
     * Test select focused toggles selection.
     */
    public function test_select_focused_toggles_selection(): void
    {
        // Create media items
        Media::factory()->uploadedBy($this->user)->count(3)->create();

        // Get media in the order the component retrieves them (latest first)
        $sortedMedia = Media::latest()->get();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 0)
            ->call('selectFocused')
            ->assertSet('selectedMedia', [$sortedMedia->first()->id]);
    }

    /**
     * Test select focused does nothing with no focus.
     */
    public function test_select_focused_does_nothing_with_no_focus(): void
    {
        Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('selectFocused')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test select focused does nothing with invalid index.
     */
    public function test_select_focused_does_nothing_with_invalid_index(): void
    {
        Media::factory()->uploadedBy($this->user)->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 100)
            ->call('selectFocused')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test reset focus sets index to -1.
     */
    public function test_reset_focus_sets_index_to_negative_one(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('focusedIndex', 5)
            ->call('resetFocus')
            ->assertSet('focusedIndex', -1);
    }

    /**
     * Test component with all visual editor options.
     */
    public function test_component_with_all_visual_editor_options(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();
        session(['media.recently_used' => [$media->id]]);

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, [
                'inlineMode' => true,
                'quickUploadSelect' => true,
                'multiSelect' => false,
                'context' => 'visual-editor',
            ])
            ->assertSet('inlineMode', true)
            ->assertSet('quickUploadSelect', true)
            ->assertSet('multiSelect', false)
            ->assertSet('context', 'visual-editor')
            ->assertSet('recentlyUsed', [$media->id]);
    }
}
