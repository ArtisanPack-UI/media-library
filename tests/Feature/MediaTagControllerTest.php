<?php

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Media Tag Controller Feature Tests
 *
 * Tests for the MediaTagController API endpoints including
 * listing, creating, updating, deleting, attaching, and detaching tags.
 *
 * @since   1.0.0
 */
class MediaTagControllerTest extends TestCase
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
     * Test index returns all tags.
     */
    public function test_index_returns_all_tags(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaTag::factory()->count(3)->create();

        $response = $this->getJson('/api/media/tags');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test index returns tags with media count.
     */
    public function test_index_returns_tags_with_media_count(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->count(2)->create();
        $tag->media()->attach($media->pluck('id'));

        $response = $this->getJson('/api/media/tags');

        $response->assertOk()
            ->assertJsonPath('data.0.media_count', 2);
    }

    /**
     * Test store creates new tag.
     */
    public function test_store_creates_new_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/media/tags', [
            'name' => 'Test Tag',
            'description' => 'A test tag',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Tag')
            ->assertJsonPath('message', 'Tag created successfully');

        $this->assertDatabaseHas('media_tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);
    }

    /**
     * Test store validates required name.
     */
    public function test_store_validates_required_name(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/media/tags', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test show returns single tag.
     */
    public function test_show_returns_single_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->getJson('/api/media/tags/'.$tag->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', $tag->name);
    }

    /**
     * Test show returns 404 for non-existent tag.
     */
    public function test_show_returns_404_for_non_existent_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/media/tags/99999');

        $response->assertNotFound();
    }

    /**
     * Test update modifies tag.
     */
    public function test_update_modifies_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->putJson('/api/media/tags/'.$tag->id, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Tag updated successfully');

        $tag->refresh();
        expect($tag->name)->toBe('Updated Name');
    }

    /**
     * Test destroy deletes tag.
     */
    public function test_destroy_deletes_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->deleteJson('/api/media/tags/'.$tag->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('media_tags', ['id' => $tag->id]);
    }

    /**
     * Test destroy detaches media before deleting.
     */
    public function test_destroy_detaches_media_before_deleting(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $tag->media()->attach($media->id);

        $response = $this->deleteJson('/api/media/tags/'.$tag->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('media_tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('media_taggables', ['media_tag_id' => $tag->id]);
    }

    /**
     * Test attach tag to media.
     */
    public function test_attach_tag_to_media(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->count(2)->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/attach', [
            'media_ids' => $media->pluck('id')->toArray(),
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Tag attached to media successfully');

        expect($tag->media()->count())->toBe(2);
    }

    /**
     * Test attach handles already attached media.
     */
    public function test_attach_handles_already_attached_media(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $tag->media()->attach($media->id);

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/attach', [
            'media_ids' => [$media->id],
        ]);

        $response->assertOk();

        expect($tag->media()->count())->toBe(1);
    }

    /**
     * Test attach validates media_ids required.
     */
    public function test_attach_validates_media_ids_required(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/attach', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids']);
    }

    /**
     * Test attach validates media_ids are array.
     */
    public function test_attach_validates_media_ids_are_array(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/attach', [
            'media_ids' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids']);
    }

    /**
     * Test attach validates media exists.
     */
    public function test_attach_validates_media_exists(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/attach', [
            'media_ids' => [99999],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids.0']);
    }

    /**
     * Test detach tag from media.
     */
    public function test_detach_tag_from_media(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->count(2)->create();
        $tag->media()->attach($media->pluck('id'));

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/detach', [
            'media_ids' => [$media[0]->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Tag detached from media successfully');

        expect($tag->media()->count())->toBe(1);
    }

    /**
     * Test detach handles not attached media.
     */
    public function test_detach_handles_not_attached_media(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/detach', [
            'media_ids' => [$media->id],
        ]);

        $response->assertOk();
    }

    /**
     * Test detach validates media_ids required.
     */
    public function test_detach_validates_media_ids_required(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/detach', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids']);
    }

    /**
     * Test detach validates media_ids are array.
     */
    public function test_detach_validates_media_ids_are_array(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/detach', [
            'media_ids' => 'not-an-array',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids']);
    }

    /**
     * Test detach validates media exists.
     */
    public function test_detach_validates_media_exists(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $tag = MediaTag::factory()->create();

        $response = $this->postJson('/api/media/tags/'.$tag->id.'/detach', [
            'media_ids' => [99999],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['media_ids.0']);
    }

    /**
     * Test unauthorized requests are rejected.
     */
    public function test_unauthorized_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/media/tags');

        $response->assertUnauthorized();
    }

    /**
     * Test store validates unique name.
     */
    public function test_store_validates_unique_name(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaTag::factory()->create([
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $response = $this->postJson('/api/media/tags', [
            'name' => 'Test Tag',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test update validates unique name when changing.
     */
    public function test_update_validates_unique_name_when_changing(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        MediaTag::factory()->create([
            'name' => 'Existing Tag',
            'slug' => 'existing-tag',
        ]);

        $tag = MediaTag::factory()->create([
            'name' => 'Original Tag',
            'slug' => 'original-tag',
        ]);

        $response = $this->putJson('/api/media/tags/'.$tag->id, [
            'name' => 'Existing Tag',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test attach returns 404 for non-existent tag.
     */
    public function test_attach_returns_404_for_non_existent_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $media = Media::factory()->uploadedBy($this->user)->create();

        $response = $this->postJson('/api/media/tags/99999/attach', [
            'media_ids' => [$media->id],
        ]);

        $response->assertNotFound();
    }

    /**
     * Test detach returns 404 for non-existent tag.
     */
    public function test_detach_returns_404_for_non_existent_tag(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $media = Media::factory()->uploadedBy($this->user)->create();

        $response = $this->postJson('/api/media/tags/99999/detach', [
            'media_ids' => [$media->id],
        ]);

        $response->assertNotFound();
    }
}
