<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaGrid;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MediaGrid Component Tests
 *
 * Tests for the MediaGrid Livewire component including display modes,
 * selection handling, and rendering functionality.
 *
 * @since   1.0.0
 */
class MediaGridTest extends TestCase
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
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->assertStatus(200);
    }

    /**
     * Test default view mode is grid.
     */
    public function test_default_view_mode_is_grid(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->assertSet('viewMode', 'grid');
    }

    /**
     * Test can set view mode to list.
     */
    public function test_can_set_view_mode_to_list(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator, 'viewMode' => 'list'])
            ->assertSet('viewMode', 'list');
    }

    /**
     * Test default bulk select mode is false.
     */
    public function test_default_bulk_select_mode_is_false(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->assertSet('bulkSelectMode', false);
    }

    /**
     * Test can enable bulk select mode.
     */
    public function test_can_enable_bulk_select_mode(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator, 'bulkSelectMode' => true])
            ->assertSet('bulkSelectMode', true);
    }

    /**
     * Test default selected media is empty.
     */
    public function test_default_selected_media_is_empty(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->assertSet('selectedMedia', []);
    }

    /**
     * Test can mount with pre-selected media.
     */
    public function test_can_mount_with_preselected_media(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);
        $selectedIds = [$media[0]->id, $media[1]->id];

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator, 'selectedMedia' => $selectedIds])
            ->assertSet('selectedMedia', $selectedIds);
    }

    /**
     * Test toggle selection adds media to selection.
     */
    public function test_toggle_selection_adds_media(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->call('toggleSelection', $media[0]->id)
            ->assertSet('selectedMedia', [$media[0]->id])
            ->assertDispatched('selection-changed');
    }

    /**
     * Test toggle selection removes media from selection.
     */
    public function test_toggle_selection_removes_media(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator, 'selectedMedia' => [$media[0]->id]])
            ->call('toggleSelection', $media[0]->id)
            ->assertSet('selectedMedia', [])
            ->assertDispatched('selection-changed');
    }

    /**
     * Test toggle selection with multiple items.
     */
    public function test_toggle_selection_with_multiple_items(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->call('toggleSelection', $media[0]->id)
            ->call('toggleSelection', $media[1]->id)
            ->assertSet('selectedMedia', [$media[0]->id, $media[1]->id]);
    }

    /**
     * Test selection-changed event dispatches correct data.
     */
    public function test_selection_changed_event_dispatches_correct_data(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->call('toggleSelection', $media[0]->id)
            ->assertDispatched('selection-changed', selectedMedia: [$media[0]->id]);
    }

    /**
     * Test component with empty media collection.
     */
    public function test_component_with_empty_media_collection(): void
    {
        $paginator = new LengthAwarePaginator(collect(), 0, 12);

        Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->assertStatus(200);
    }

    /**
     * Test component stores media items as collection.
     */
    public function test_component_stores_media_items(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        $component = Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator]);

        expect($component->get('mediaItems'))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($component->get('mediaItems')->count())->toBe(3);
    }

    /**
     * Test toggle selection maintains array indices.
     */
    public function test_toggle_selection_maintains_array_indices(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->count(3)->create();
        $paginator = new LengthAwarePaginator($media, 3, 12);

        $component = Livewire::actingAs($this->user)
            ->test(MediaGrid::class, ['media' => $paginator])
            ->call('toggleSelection', $media[0]->id)
            ->call('toggleSelection', $media[1]->id)
            ->call('toggleSelection', $media[2]->id)
            ->call('toggleSelection', $media[1]->id);

        $selectedMedia = $component->get('selectedMedia');
        expect(array_keys($selectedMedia))->toBe([0, 1]);
        expect($selectedMedia)->toBe([$media[0]->id, $media[2]->id]);
    }
}
