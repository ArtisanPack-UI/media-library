<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\FolderManager;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FolderManager Component Tests
 *
 * Tests for the FolderManager Livewire component including folder creation,
 * editing, deletion, and navigation functionality.
 *
 * @since   1.0.0
 */
class FolderManagerTest extends TestCase
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
            ->test(FolderManager::class)
            ->assertStatus(200);
    }

    /**
     * Test that component has expected initial state.
     */
    public function test_component_has_initial_state(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->assertSet('isOpen', false)
            ->assertSet('isEditing', false)
            ->assertSet('editingFolder', null)
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '')
            ->assertSet('form.description', '')
            ->assertSet('form.parent_id', null);
    }

    /**
     * Test modal opens when triggered.
     */
    public function test_modal_opens_when_triggered(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
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
            ->test(FolderManager::class)
            ->assertSet('isOpen', false)
            ->dispatch('open-folder-manager')
            ->assertSet('isOpen', true);
    }

    /**
     * Test modal closes correctly.
     */
    public function test_modal_closes_correctly(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
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
            ->test(FolderManager::class)
            ->set('form.name', 'Test Folder')
            ->set('form.slug', 'test-folder')
            ->set('form.description', 'A test folder')
            ->call('close')
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '')
            ->assertSet('form.description', '')
            ->assertSet('isEditing', false)
            ->assertSet('editingFolder', null);
    }

    /**
     * Test creating a new folder.
     */
    public function test_creates_new_folder(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->set('form.name', 'New Folder')
            ->set('form.slug', 'new-folder')
            ->set('form.description', 'A new folder')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('folders-updated');

        $this->assertDatabaseHas('media_folders', [
            'name' => 'New Folder',
            'slug' => 'new-folder',
            'description' => 'A new folder',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test auto-generating slug from name.
     */
    public function test_auto_generates_slug_from_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->set('form.name', 'My Test Folder')
            ->assertSet('form.slug', 'my-test-folder');
    }

    /**
     * Test slug is not auto-generated when editing.
     */
    public function test_slug_not_auto_generated_when_editing(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id)
            ->set('form.name', 'Updated Name')
            ->assertSet('form.slug', 'original-slug');
    }

    /**
     * Test creating folder with parent.
     */
    public function test_creates_folder_with_parent(): void
    {
        $parent = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->set('form.name', 'Child Folder')
            ->set('form.slug', 'child-folder')
            ->set('form.parent_id', $parent->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Test validation requires name.
     */
    public function test_validation_requires_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
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
            ->test(FolderManager::class)
            ->set('form.name', 'Test Folder')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors('form.slug');
    }

    /**
     * Test validation requires unique slug.
     */
    public function test_validation_requires_unique_slug(): void
    {
        MediaFolder::factory()->createdBy($this->user)->create(['slug' => 'existing-slug']);

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->set('form.name', 'Test Folder')
            ->set('form.slug', 'existing-slug')
            ->call('save')
            ->assertHasErrors('form.slug');
    }

    /**
     * Test editing a folder.
     */
    public function test_edits_folder(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id)
            ->assertSet('isEditing', true)
            ->assertSet('form.name', 'Original Name')
            ->assertSet('form.slug', 'original-slug');
    }

    /**
     * Test updating a folder.
     */
    public function test_updates_folder(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original Name',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id)
            ->set('form.name', 'Updated Name')
            ->set('form.slug', 'updated-slug')
            ->set('form.description', 'Updated description')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('folders-updated');

        $folder->refresh();
        expect($folder->name)->toBe('Updated Name');
        expect($folder->slug)->toBe('updated-slug');
        expect($folder->description)->toBe('Updated description');
    }

    /**
     * Test cancel editing.
     */
    public function test_cancel_editing(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id)
            ->assertSet('isEditing', true)
            ->call('cancelEdit')
            ->assertSet('isEditing', false)
            ->assertSet('editingFolder', null)
            ->assertSet('form.name', '')
            ->assertSet('form.slug', '');
    }

    /**
     * Test deleting an empty folder.
     */
    public function test_deletes_empty_folder(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('delete', $folder->id)
            ->assertDispatched('folders-updated');

        $this->assertDatabaseMissing('media_folders', ['id' => $folder->id]);
    }

    /**
     * Test cannot delete folder with children.
     */
    public function test_cannot_delete_folder_with_children(): void
    {
        $parent = MediaFolder::factory()->createdBy($this->user)->create();
        MediaFolder::factory()->createdBy($this->user)->childOf($parent)->create();

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('delete', $parent->id)
            ->assertNotDispatched('folders-updated');

        $this->assertDatabaseHas('media_folders', ['id' => $parent->id]);
    }

    /**
     * Test cannot delete folder with media.
     */
    public function test_cannot_delete_folder_with_media(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        Media::factory()->uploadedBy($this->user)->inFolder($folder)->create();

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('delete', $folder->id)
            ->assertNotDispatched('folders-updated');

        $this->assertDatabaseHas('media_folders', ['id' => $folder->id]);
    }

    /**
     * Test folders are loaded on mount.
     */
    public function test_folders_loaded_on_mount(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(3)->create();

        $component = Livewire::actingAs($this->user)
            ->test(FolderManager::class);

        expect($component->get('folders')->count())->toBe(3);
    }

    /**
     * Test parent folder options include all folders.
     */
    public function test_parent_folder_options_available(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(2)->create();

        $component = Livewire::actingAs($this->user)
            ->test(FolderManager::class);

        // Access computed property through invocable method
        $options = $component->invade()->parentFolderOptions();
        expect($options)->toBeArray();
        expect(count($options))->toBeGreaterThanOrEqual(3);
    }

    /**
     * Test editing folder excludes itself from parent options.
     */
    public function test_editing_folder_excludes_itself_from_parent_options(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        $otherFolder = MediaFolder::factory()->createdBy($this->user)->create();

        $component = Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id);

        // Access computed property through invocable method
        $options = $component->invade()->parentFolderOptions();
        $keys = collect($options)->pluck('key')->toArray();

        expect($keys)->not()->toContain($folder->id);
        expect($keys)->toContain($otherFolder->id);
    }

    /**
     * Test reset form clears validation errors.
     */
    public function test_reset_form_clears_validation_errors(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('save')
            ->assertHasErrors()
            ->call('resetForm')
            ->assertHasNoErrors();
    }

    /**
     * Test opening modal refreshes folders.
     */
    public function test_opening_modal_refreshes_folders(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(FolderManager::class);

        MediaFolder::factory()->createdBy($this->user)->create();

        $component->call('open');
        expect($component->get('folders')->count())->toBe(1);
    }

    /**
     * Test validation allows same slug when editing same folder.
     */
    public function test_validation_allows_same_slug_when_editing(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original',
            'slug' => 'original-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->call('edit', $folder->id)
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
            ->test(FolderManager::class)
            ->set('form.name', 'Test Folder')
            ->set('form.slug', 'test-folder')
            ->set('form.description', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_folders', [
            'name' => 'Test Folder',
            'slug' => 'test-folder',
        ]);
    }

    /**
     * Test parent_id validation checks folder exists.
     */
    public function test_parent_id_validation_checks_folder_exists(): void
    {
        Livewire::actingAs($this->user)
            ->test(FolderManager::class)
            ->set('form.name', 'Test Folder')
            ->set('form.slug', 'test-folder')
            ->set('form.parent_id', 99999)
            ->call('save')
            ->assertHasErrors('form.parent_id');
    }
}
