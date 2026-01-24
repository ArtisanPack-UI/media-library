<?php

/**
 * Integration Tests
 *
 * Tests for v1.1 feature integration including event handling between
 * components, feature toggles, and cross-component communication.
 *
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal;
use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaPicker;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Integration Test Class
 */
class IntegrationTest extends TestCase
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

    // =========================================================================
    // Feature Toggle Tests
    // =========================================================================

    /**
     * Test streaming upload toggle affects component behavior.
     */
    public function test_streaming_upload_toggle_affects_behavior(): void
    {
        // Enabled by default
        config(['artisanpack.media.features.streaming_upload' => true]);

        // Create a mock component that uses the StreamableUpload trait
        $component = new class
        {
            use \ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;

            public int $uploadProgress = 0;

            public function stream(string $to, string $content, bool $replace = false): void
            {
                // Mock stream method
            }
        };

        expect($component->isStreamingEnabled())->toBeTrue();

        // Disable streaming
        config(['artisanpack.media.features.streaming_upload' => false]);
        expect($component->isStreamingEnabled())->toBeFalse();
    }

    /**
     * Test fallback interval is used from config.
     */
    public function test_fallback_interval_from_config(): void
    {
        config(['artisanpack.media.features.streaming_fallback_interval' => 750]);

        $component = new class
        {
            use \ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;

            public int $uploadProgress = 0;
        };

        expect($component->getStreamingFallbackInterval())->toBe(750);
    }

    // =========================================================================
    // Event Dispatch Tests
    // =========================================================================

    /**
     * Test MediaPicker dispatches media-picked event with context.
     */
    public function test_media_picker_dispatches_event_with_context(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => 'featured-image', 'multiSelect' => true])
            ->call('toggleSelect', $media->id)
            ->call('confirmSelection')
            ->assertDispatched('media-picked', function ($name, $params) {
                return $params['context'] === 'featured-image';
            });
    }

    /**
     * Test MediaModal dispatches media-selected event with context.
     */
    public function test_media_modal_dispatches_event_with_context(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['context' => 'gallery-image'])
            ->call('toggleSelect', $media->id)
            ->call('confirmSelection')
            ->assertDispatched('media-selected', function ($name, $params) {
                return $params['context'] === 'gallery-image';
            });
    }

    /**
     * Test MediaPicker open/close events include context.
     */
    public function test_media_picker_lifecycle_events_include_context(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => 'hero-media'])
            ->call('open', 'hero-media')
            ->assertDispatched('media-picker-opened', function ($name, $params) {
                return $params['context'] === 'hero-media';
            })
            ->call('close')
            ->assertDispatched('media-picker-closed', function ($name, $params) {
                return $params['context'] === 'hero-media';
            });
    }

    // =========================================================================
    // Context Matching Tests
    // =========================================================================

    /**
     * Test multiple pickers with different contexts.
     */
    public function test_multiple_pickers_with_different_contexts(): void
    {
        // First picker with context "featured"
        $picker1 = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => 'featured']);

        // Second picker with context "gallery"
        $picker2 = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => 'gallery']);

        // Opening with "featured" context should only open first picker
        $picker1->call('open', 'featured')->assertSet('isOpen', true);
        $picker2->call('open', 'featured')->assertSet('isOpen', false);

        // Opening with "gallery" context should only open second picker
        $picker1->call('close');
        $picker1->call('open', 'gallery')->assertSet('isOpen', false);
        $picker2->call('open', 'gallery')->assertSet('isOpen', true);
    }

    /**
     * Test modal responds only to matching context.
     */
    public function test_modal_responds_only_to_matching_context(): void
    {
        $modal = Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['context' => 'specific-context']);

        // Different context should not open
        $modal->dispatch('open-media-modal', context: 'other-context')
            ->assertSet('isOpen', false);

        // Matching context should open
        $modal->dispatch('open-media-modal', context: 'specific-context')
            ->assertSet('isOpen', true);
    }

    /**
     * Test empty context acts as wildcard.
     */
    public function test_empty_context_acts_as_wildcard(): void
    {
        // Component with empty context should respond to any open
        $picker = Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['context' => '']);

        $picker->call('open', 'any-context-here')
            ->assertSet('isOpen', true);

        $picker->call('close');

        $picker->call('open', '')
            ->assertSet('isOpen', true);
    }

    // =========================================================================
    // Recently Used Integration Tests
    // =========================================================================

    /**
     * Test recently used is tracked across modal selections.
     */
    public function test_recently_used_tracked_across_selections(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->create();
        $media2 = Media::factory()->uploadedBy($this->user)->create();

        // First selection
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('toggleSelect', $media1->id)
            ->call('confirmSelection');

        expect(session('media.recently_used'))->toContain($media1->id);

        // Second selection should add to front
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->call('toggleSelect', $media2->id)
            ->call('confirmSelection');

        $recentlyUsed = session('media.recently_used');
        expect($recentlyUsed[0])->toBe($media2->id);
        expect($recentlyUsed)->toContain($media1->id);
    }

    /**
     * Test recently used persists across component instances.
     */
    public function test_recently_used_persists_across_instances(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        // Set session
        session(['media.recently_used' => [$media->id]]);

        // New modal instance should load recently used
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->assertSet('recentlyUsed', [$media->id]);
    }

    // =========================================================================
    // Quick Upload Select Integration Tests
    // =========================================================================

    /**
     * Test quick upload select workflow in single mode.
     */
    public function test_quick_upload_select_workflow_single_mode(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => false, 'quickUploadSelect' => true])
            ->call('open')
            ->assertSet('isOpen', true)
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertDispatched('media-selected')
            ->assertSet('isOpen', false); // Should auto-close after selection
    }

    /**
     * Test quick upload select workflow in multi mode.
     */
    public function test_quick_upload_select_workflow_multi_mode(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true, 'quickUploadSelect' => true])
            ->call('open')
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertSet('selectedMedia', [$media->id])
            ->assertNotDispatched('media-selected') // Should not auto-confirm in multi mode
            ->assertSet('isOpen', true); // Should stay open
    }

    /**
     * Test quick upload disabled does not auto-select.
     */
    public function test_quick_upload_disabled_workflow(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['quickUploadSelect' => false])
            ->call('open')
            ->dispatch('media-uploaded', mediaId: $media->id)
            ->assertSet('selectedMedia', [])
            ->assertSet('activeTab', 'library'); // Should switch to library tab
    }

    // =========================================================================
    // Keyboard Navigation Integration Tests
    // =========================================================================

    /**
     * Test keyboard navigation workflow in picker.
     */
    public function test_keyboard_navigation_workflow_in_picker(): void
    {
        Media::factory()->uploadedBy($this->user)->count(10)->create();
        $sortedMedia = Media::latest()->get();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            // Start navigation
            ->call('focusFirst')
            ->assertSet('focusedIndex', 0)
            // Move right
            ->call('focusNext')
            ->assertSet('focusedIndex', 1)
            // Move down
            ->call('focusDown', 5)
            ->assertSet('focusedIndex', 6)
            // Select focused
            ->call('selectFocused')
            ->assertSet('selectedMedia', [$sortedMedia[6]->id])
            // Move up
            ->call('focusUp', 5)
            ->assertSet('focusedIndex', 1)
            // Select another
            ->call('selectFocused')
            ->assertSet('selectedMedia', [$sortedMedia[6]->id, $sortedMedia[1]->id]);
    }

    /**
     * Test keyboard navigation workflow in modal.
     */
    public function test_keyboard_navigation_workflow_in_modal(): void
    {
        Media::factory()->uploadedBy($this->user)->count(5)->create();
        $sortedMedia = Media::latest()->get();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true])
            ->call('open')
            // Navigate to last item
            ->call('focusLast')
            ->assertSet('focusedIndex', 4)
            // Select it
            ->call('selectFocused')
            ->assertSet('selectedMedia', [$sortedMedia[4]->id])
            // Navigate to first
            ->call('focusFirst')
            ->assertSet('focusedIndex', 0)
            // Reset focus
            ->call('resetFocus')
            ->assertSet('focusedIndex', -1);
    }

    // =========================================================================
    // Max Selections Integration Tests
    // =========================================================================

    /**
     * Test max selections enforced across selection methods.
     */
    public function test_max_selections_enforced_across_methods(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(5)->create();
        $mediaIds = $media->pluck('id')->toArray();
        $sortedMedia = Media::latest()->get();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true, 'maxSelections' => 2])
            // Select via toggleSelect
            ->call('toggleSelect', $mediaIds[0])
            ->call('toggleSelect', $mediaIds[1])
            // Try to select third via toggleSelect - should not add
            ->call('toggleSelect', $mediaIds[2])
            ->assertSet('selectedMedia', [$mediaIds[0], $mediaIds[1]])
            // Deselect one
            ->call('toggleSelect', $mediaIds[0])
            ->assertSet('selectedMedia', [$mediaIds[1]])
            // Now keyboard select should work
            ->set('focusedIndex', 0)
            ->call('selectFocused')
            ->assertSet('selectedMedia', [$mediaIds[1], $sortedMedia[0]->id]);
    }

    /**
     * Test quick upload respects max selections.
     */
    public function test_quick_upload_respects_max_selections(): void
    {
        $existingMedia = Media::factory()->uploadedBy($this->user)->count(2)->create();
        $newMedia = Media::factory()->uploadedBy($this->user)->create();
        $existingIds = $existingMedia->pluck('id')->toArray();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, [
                'multiSelect' => true,
                'maxSelections' => 2,
                'quickUploadSelect' => true,
            ])
            ->call('toggleSelect', $existingIds[0])
            ->call('toggleSelect', $existingIds[1])
            // Quick upload should not add since max reached
            ->dispatch('media-uploaded', mediaId: $newMedia->id)
            ->assertSet('selectedMedia', [$existingIds[0], $existingIds[1]]);
    }

    // =========================================================================
    // Filter Reset Integration Tests
    // =========================================================================

    /**
     * Test filters reset when component opens.
     */
    public function test_filters_reset_when_picker_opens(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaPicker::class)
            ->set('search', 'test search')
            ->set('folderId', 1)
            ->call('open')
            ->assertSet('search', '')
            ->assertSet('folderId', null);
    }

    /**
     * Test filters reset when modal opens.
     */
    public function test_filters_reset_when_modal_opens(): void
    {
        Livewire::actingAs($this->user)
            ->test(MediaModal::class)
            ->set('search', 'test search')
            ->set('folderId', 1)
            ->set('typeFilter', 'image')
            ->call('open')
            ->assertSet('search', '')
            ->assertSet('folderId', null)
            ->assertSet('typeFilter', '');
    }

    // =========================================================================
    // Selection State Integration Tests
    // =========================================================================

    /**
     * Test selections cleared when component closes.
     */
    public function test_selections_cleared_on_close(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaPicker::class, ['multiSelect' => true])
            ->call('open')
            ->call('toggleSelect', $media->id)
            ->assertSet('selectedMedia', [$media->id])
            ->call('close')
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test selections preserved until confirmed.
     */
    public function test_selections_preserved_until_confirmed(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $mediaIds = $media->pluck('id')->toArray();

        Livewire::actingAs($this->user)
            ->test(MediaModal::class, ['multiSelect' => true])
            ->call('open')
            ->call('toggleSelect', $mediaIds[0])
            ->call('toggleSelect', $mediaIds[1])
            ->call('toggleSelect', $mediaIds[2])
            ->assertSet('selectedMedia', $mediaIds)
            // Selections preserved during various operations
            ->set('search', 'test')
            ->assertSet('selectedMedia', $mediaIds)
            ->call('switchTab', 'upload')
            ->assertSet('selectedMedia', $mediaIds);
    }
}
