<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\TagManager;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TagManager Component Tests
 *
 * Tests for the TagManager Livewire component including tag creation,
 * editing, deletion, and management functionality.
 *
 * @since   1.0.0
 */
class TagManagerTest extends TestCase
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
            ->test(TagManager::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->assertSet('isOpen', false)
            ->assertSet('isEditing', false)
            ->assertSet('editingTag', null)
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '')
            ->assertSet('form.description', '');
    }

    /**
     * Test modal opens when triggered.
     */
    public function test_modal_opens_when_triggered(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->assertSet('isOpen', false)
            ->call('open')
            ->assertSet('isOpen', true);
    }

    /**
     * Test modal opens via event.
     */
    public function test_modal_opens_via_event(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-tag-manager')
            ->assertSet('isOpen', true);
    }

    /**
     * Test modal closes correctly.
     */
    public function test_modal_closes_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('open')
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    }

    /**
     * Test form resets when modal closes.
     */
    public function test_form_resets_when_modal_closes(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'Test Tag')
            ->set('form.slug', 'test-tag')
            ->set('form.description', 'A test tag')
            ->call('close')
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '')
            ->assertSet('form.description', '')
            ->assertSet('isEditing', false)
            ->assertSet('editingTag', null);
    }

    /**
     * Test creating a new tag.
     */
    public function test_creates_new_tag(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'New Tag')
            ->set('form.slug', 'new-tag')
            ->set('form.description', 'A new tag')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('tags-updated');

        $this->assertDatabaseHas('media_tags', [
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ]);
    }

    /**
     * Test auto-generating slug from name.
     */
    public function test_auto_generates_slug_from_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'My Test Tag')
            ->assertSet('form.slug', 'my-test-tag');
    }

    /**
     * Test slug is not auto-generated when editing.
     */
    public function test_slug_not_auto_generated_when_editing(): void
    {
        $tag = MediaTag::factory()->create([
            'name' => 'Original',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('edit', $tag->id)
            ->set('form.name', 'Updated Name')
            ->assertSet('form.slug', 'original-slug');
    }

    /**
     * Test validation requires name.
     */
    public function test_validation_requires_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.slug', 'test-slug')
            ->call('save')
            ->assertHasErrors('form.name');
    }

    /**
     * Test validation requires slug.
     */
    public function test_validation_requires_slug(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'Test Tag')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors('form.slug');
    }

    /**
     * Test validation requires unique slug.
     */
    public function test_validation_requires_unique_slug(): void
    {
        MediaTag::factory()->create(['slug' => 'existing-slug']);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'Test Tag')
            ->set('form.slug', 'existing-slug')
            ->call('save')
            ->assertHasErrors('form.slug');
    }

    /**
     * Test editing a tag.
     */
    public function test_edits_tag(): void
    {
        $tag = MediaTag::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('edit', $tag->id)
            ->assertSet('isEditing', true)
            ->assertSet('form.name', 'Original Name')
            ->assertSet('form.slug', 'original-slug');
    }

    /**
     * Test updating a tag.
     */
    public function test_updates_tag(): void
    {
        $tag = MediaTag::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('edit', $tag->id)
            ->set('form.name', 'Updated Name')
            ->set('form.slug', 'updated-slug')
            ->set('form.description', 'Updated description')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('tags-updated');

        $tag->refresh();
        expect($tag->name)->toBe('Updated Name');
        expect($tag->slug)->toBe('updated-slug');
        expect($tag->description)->toBe('Updated description');
    }

    /**
     * Test cancel editing.
     */
    public function test_cancel_editing(): void
    {
        $tag = MediaTag::factory()->create();

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('edit', $tag->id)
            ->assertSet('isEditing', true)
            ->call('cancelEdit')
            ->assertSet('isEditing', false)
            ->assertSet('editingTag', null)
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '');
    }

    /**
     * Test deleting a tag.
     */
    public function test_deletes_tag(): void
    {
        $tag = MediaTag::factory()->create();

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('delete', $tag->id)
            ->assertDispatched('tags-updated');

        $this->assertDatabaseMissing('media_tags', ['id' => $tag->id]);
    }

    /**
     * Test deleting tag detaches from media.
     */
    public function test_deleting_tag_detaches_from_media(): void
    {
        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $media->tags()->attach($tag->id);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('delete', $tag->id)
            ->assertDispatched('tags-updated');

        $this->assertDatabaseMissing('media_tags', ['id' => $tag->id]);
        expect($media->fresh()->tags()->count())->toBe(0);
    }

    /**
     * Test tags are loaded on mount.
     */
    public function test_tags_loaded_on_mount(): void
    {
        MediaTag::factory()->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(TagManager::class);

        expect($component->get('tags')->count())->toBe(3);
    }

    /**
     * Test reset form clears validation errors.
     */
    public function test_reset_form_clears_validation_errors(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('save')
            ->assertHasErrors()
            ->call('resetForm')
            ->assertHasNoErrors();
    }

    /**
     * Test opening modal refreshes tags.
     */
    public function test_opening_modal_refreshes_tags(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TagManager::class);

        MediaTag::factory()->create();

        $component->call('open');
        expect($component->get('tags')->count())->toBe(1);
    }

    /**
     * Test validation allows same slug when editing same tag.
     */
    public function test_validation_allows_same_slug_when_editing(): void
    {
        $tag = MediaTag::factory()->create([
            'name' => 'Original',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->call('edit', $tag->id)
            ->set('form.name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors();
    }

    /**
     * Test description is nullable.
     */
    public function test_description_is_nullable(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'Test Tag')
            ->set('form.slug', 'test-tag')
            ->set('form.description', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);
    }

    /**
     * Test tags are ordered by name.
     */
    public function test_tags_ordered_by_name(): void
    {
        MediaTag::factory()->create(['name' => 'Zebra']);
        MediaTag::factory()->create(['name' => 'Apple']);
        MediaTag::factory()->create(['name' => 'Mango']);

        $component = Livewire::actingAs($this->user)
            ->test(TagManager::class);

        $tags = $component->get('tags');
        expect($tags->first()->name)->toBe('Apple');
        expect($tags->last()->name)->toBe('Zebra');
    }

    /**
     * Test save clears form after success.
     */
    public function test_save_clears_form_after_success(): void
    {
        Livewire::actingAs($this->user)
            ->test(TagManager::class)
            ->set('form.name', 'New Tag')
            ->set('form.slug', 'new-tag')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '')
            ->assertSet('isEditing', false);
    }
}
