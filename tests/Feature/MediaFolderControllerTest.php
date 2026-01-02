<?php

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Media Folder Controller Feature Tests
 *
 * Tests for the MediaFolderController API endpoints including
 * listing, creating, updating, deleting, and moving folders.
 *
 * @since   1.0.0
 */
class MediaFolderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        $this->user = User::factory()->create();

        Storage::fake('test-disk');

        config([
            'artisanpack.media.disk' => 'test-disk',
            'artisanpack.media.user_model' => User::class,
        ]);

        Gate::before(fn ($user, $ability) => true);
    }

    /**
     * Test index returns all folders.
     */
    public function test_index_returns_all_folders(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaFolder::factory()->createdBy($this->user)->count(3)->create();

        $response = $this->getJson('/api/media/folders');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test store creates new folder.
     */
    public function test_store_creates_new_folder(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/media/folders', [
            'name' => 'Test Folder',
            'description' => 'A test folder',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Folder')
            ->assertJsonPath('message', 'Folder created successfully');

        $this->assertDatabaseHas('media_folders', [
            'name' => 'Test Folder',
            'slug' => 'test-folder',
        ]);
    }

    /**
     * Test store validates required name.
     */
    public function test_store_validates_required_name(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/media/folders', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test show returns single folder.
     */
    public function test_show_returns_single_folder(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->getJson('/api/media/folders/'.$folder->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $folder->id)
            ->assertJsonPath('data.name', $folder->name);
    }

    /**
     * Test show returns 404 for non-existent folder.
     */
    public function test_show_returns_404_for_non_existent_folder(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/media/folders/99999');

        $response->assertNotFound();
    }

    /**
     * Test update modifies folder.
     */
    public function test_update_modifies_folder(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->putJson('/api/media/folders/'.$folder->id, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Folder updated successfully');

        $folder->refresh();
        expect($folder->name)->toBe('Updated Name');
    }

    /**
     * Test destroy deletes empty folder.
     */
    public function test_destroy_deletes_empty_folder(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->deleteJson('/api/media/folders/'.$folder->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('media_folders', ['id' => $folder->id]);
    }

    /**
     * Test destroy fails with subfolders.
     */
    public function test_destroy_fails_with_subfolders(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $parent = MediaFolder::factory()->createdBy($this->user)->create();
        MediaFolder::factory()->createdBy($this->user)->childOf($parent)->create();

        $response = $this->deleteJson('/api/media/folders/'.$parent->id);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete folder with subfolders. Please delete or move subfolders first.');

        $this->assertDatabaseHas('media_folders', ['id' => $parent->id]);
    }

    /**
     * Test destroy fails with media.
     */
    public function test_destroy_fails_with_media(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        Media::factory()->uploadedBy($this->user)->inFolder($folder)->create();

        $response = $this->deleteJson('/api/media/folders/'.$folder->id);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete folder with media items. Please delete or move media first.');

        $this->assertDatabaseHas('media_folders', ['id' => $folder->id]);
    }

    /**
     * Test move folder to new parent.
     */
    public function test_move_folder_to_new_parent(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $parent = MediaFolder::factory()->createdBy($this->user)->create();
        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->postJson('/api/media/folders/'.$folder->id.'/move', [
            'parent_id' => $parent->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('message', 'Folder moved successfully');

        $folder->refresh();
        expect($folder->parent_id)->toBe($parent->id);
    }

    /**
     * Test move folder to root.
     */
    public function test_move_folder_to_root(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $parent = MediaFolder::factory()->createdBy($this->user)->create();
        $folder = MediaFolder::factory()->createdBy($this->user)->childOf($parent)->create();

        $response = $this->postJson('/api/media/folders/'.$folder->id.'/move', [
            'parent_id' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.parent_id', null);

        $folder->refresh();
        expect($folder->parent_id)->toBeNull();
    }

    /**
     * Test move prevents moving to self.
     */
    public function test_move_prevents_moving_to_self(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->postJson('/api/media/folders/'.$folder->id.'/move', [
            'parent_id' => $folder->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot move folder into itself or its descendants.');
    }

    /**
     * Test move prevents moving to descendant.
     */
    public function test_move_prevents_moving_to_descendant(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $parent = MediaFolder::factory()->createdBy($this->user)->create();
        $child = MediaFolder::factory()->createdBy($this->user)->childOf($parent)->create();
        $grandchild = MediaFolder::factory()->createdBy($this->user)->childOf($child)->create();

        $response = $this->postJson('/api/media/folders/'.$parent->id.'/move', [
            'parent_id' => $grandchild->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot move folder into itself or its descendants.');
    }

    /**
     * Test move validates parent_id exists.
     */
    public function test_move_validates_parent_id_exists(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $folder = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->postJson('/api/media/folders/'.$folder->id.'/move', [
            'parent_id' => 99999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    /**
     * Test unauthorized requests are rejected.
     */
    public function test_unauthorized_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/media/folders');

        $response->assertUnauthorized();
    }

    /**
     * Test store generates unique slug.
     */
    public function test_store_generates_unique_slug(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Test Folder',
            'slug' => 'test-folder',
        ]);

        $response = $this->postJson('/api/media/folders', [
            'name' => 'Test Folder',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('media_folders', ['slug' => 'test-folder-1']);
    }

    /**
     * Test update generates unique slug when name changes.
     */
    public function test_update_generates_unique_slug_when_name_changes(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Existing Folder',
            'slug' => 'existing-folder',
        ]);

        $folder = MediaFolder::factory()->createdBy($this->user)->create([
            'name' => 'Original Folder',
            'slug' => 'original-folder',
        ]);

        $response = $this->putJson('/api/media/folders/'.$folder->id, [
            'name' => 'Existing Folder',
        ]);

        $response->assertOk();

        $folder->refresh();
        expect($folder->slug)->toBe('existing-folder-1');
    }

    /**
     * Test store with parent_id.
     */
    public function test_store_with_parent_id(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $parent = MediaFolder::factory()->createdBy($this->user)->create();

        $response = $this->postJson('/api/media/folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    }
}
