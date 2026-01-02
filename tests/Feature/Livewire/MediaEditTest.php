<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaEdit;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MediaEdit Component Tests
 *
 * Tests for the MediaEdit Livewire component including metadata editing,
 * tag management, and media deletion functionality.
 *
 * @since   1.0.0
 */
class MediaEditTest extends TestCase
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
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->assertStatus(200);
    }

    /**
     * Test component loads media data on mount.
     */
    public function test_component_loads_media_data_on_mount(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create([
            'title' => 'Test Title',
            'alt_text' => 'Test Alt Text',
            'caption' => 'Test Caption',
            'description' => 'Test Description',
        ]);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->assertSet('form.title', 'Test Title')
            ->assertSet('form.alt_text', 'Test Alt Text')
            ->assertSet('form.caption', 'Test Caption')
            ->assertSet('form.description', 'Test Description');
    }

    /**
     * Test component loads folder from media.
     */
    public function test_component_loads_folder_from_media(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        $media = Media::factory()->uploadedBy($this->user)->inFolder($folder)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->assertSet('form.folder_id', $folder->id);
    }

    /**
     * Test component loads tags from media.
     */
    public function test_component_loads_tags_from_media(): void
    {
        $tag1 = MediaTag::factory()->create();
        $tag2 = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $media->tags()->attach([$tag1->id, $tag2->id]);

        $component = Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id]);

        $selectedTags = $component->get('selectedTags');
        expect($selectedTags)->toContain($tag1->id);
        expect($selectedTags)->toContain($tag2->id);
    }

    /**
     * Test updating media title.
     */
    public function test_updates_media_title(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create(['title' => 'Original Title']);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.title', 'Updated Title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('media-updated');

        $media->refresh();
        expect($media->title)->toBe('Updated Title');
    }

    /**
     * Test updating media alt text.
     */
    public function test_updates_media_alt_text(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create(['alt_text' => 'Original Alt']);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.alt_text', 'Updated Alt Text')
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->alt_text)->toBe('Updated Alt Text');
    }

    /**
     * Test updating media caption.
     */
    public function test_updates_media_caption(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create(['caption' => 'Original Caption']);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.caption', 'Updated Caption')
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->caption)->toBe('Updated Caption');
    }

    /**
     * Test updating media description.
     */
    public function test_updates_media_description(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create(['description' => 'Original Description']);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.description', 'Updated Description')
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->description)->toBe('Updated Description');
    }

    /**
     * Test updating media folder.
     */
    public function test_updates_media_folder(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.folder_id', $folder->id)
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->folder_id)->toBe($folder->id);
    }

    /**
     * Test removing media from folder.
     */
    public function test_removes_media_from_folder(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create();
        $media = Media::factory()->uploadedBy($this->user)->inFolder($folder)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.folder_id', null)
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->folder_id)->toBeNull();
    }

    /**
     * Test syncing tags.
     */
    public function test_syncs_tags(): void
    {
        $tag1 = MediaTag::factory()->create();
        $tag2 = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $media->tags()->attach([$tag1->id]);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('selectedTags', [$tag2->id])
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->tags->count())->toBe(1);
        expect($media->tags->first()->id)->toBe($tag2->id);
    }

    /**
     * Test adding multiple tags.
     */
    public function test_adds_multiple_tags(): void
    {
        $tag1 = MediaTag::factory()->create();
        $tag2 = MediaTag::factory()->create();
        $tag3 = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('selectedTags', [$tag1->id, $tag2->id, $tag3->id])
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->tags->count())->toBe(3);
    }

    /**
     * Test removing all tags.
     */
    public function test_removes_all_tags(): void
    {
        $tag1 = MediaTag::factory()->create();
        $tag2 = MediaTag::factory()->create();
        $media = Media::factory()->uploadedBy($this->user)->create();
        $media->tags()->attach([$tag1->id, $tag2->id]);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('selectedTags', [])
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->tags->count())->toBe(0);
    }

    /**
     * Test validation for title max length.
     */
    public function test_validates_title_max_length(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.title', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('form.title');
    }

    /**
     * Test validation for alt_text max length.
     */
    public function test_validates_alt_text_max_length(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.alt_text', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('form.alt_text');
    }

    /**
     * Test validation for caption max length.
     */
    public function test_validates_caption_max_length(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.caption', str_repeat('a', 1001))
            ->call('save')
            ->assertHasErrors('form.caption');
    }

    /**
     * Test validation for description max length.
     */
    public function test_validates_description_max_length(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.description', str_repeat('a', 2001))
            ->call('save')
            ->assertHasErrors('form.description');
    }

    /**
     * Test validation for folder_id exists.
     */
    public function test_validates_folder_id_exists(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.folder_id', 99999)
            ->call('save')
            ->assertHasErrors('form.folder_id');
    }

    /**
     * Test validation for tag ids exist.
     */
    public function test_validates_tag_ids_exist(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('selectedTags', [99999])
            ->call('save')
            ->assertHasErrors('selectedTags.0');
    }

    /**
     * Test isSaving state during save.
     */
    public function test_is_saving_state(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->assertSet('isSaving', false);
    }

    /**
     * Test folders are available for dropdown.
     */
    public function test_folders_available_for_dropdown(): void
    {
        MediaFolder::factory()->createdBy($this->user)->count(3)->create();
        $media = Media::factory()->uploadedBy($this->user)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id]);

        $folders = $component->invade()->folders();
        expect($folders->count())->toBe(3);
    }

    /**
     * Test tags are available for selection.
     */
    public function test_tags_available_for_selection(): void
    {
        MediaTag::factory()->count(3)->create();
        $media = Media::factory()->uploadedBy($this->user)->create();

        $component = Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id]);

        $tags = $component->invade()->tags();
        expect($tags->count())->toBe(3);
    }

    /**
     * Test deleting media.
     */
    public function test_deletes_media(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create();
        $mediaId = $media->id;

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->call('delete')
            ->assertDispatched('media-updated');

        $this->assertSoftDeleted('media', ['id' => $mediaId]);
    }

    /**
     * Test nullable fields can be cleared.
     */
    public function test_nullable_fields_can_be_cleared(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->create([
            'title' => 'Has Title',
            'alt_text' => 'Has Alt',
            'caption' => 'Has Caption',
            'description' => 'Has Description',
        ]);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => $media->id])
            ->set('form.title', '')
            ->set('form.alt_text', '')
            ->set('form.caption', '')
            ->set('form.description', '')
            ->call('save')
            ->assertHasNoErrors();

        $media->refresh();
        expect($media->title)->toBe('');
        expect($media->alt_text)->toBe('');
        expect($media->caption)->toBe('');
        expect($media->description)->toBe('');
    }

    /**
     * Test component throws exception for non-existent media.
     */
    public function test_throws_exception_for_non_existent_media(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(MediaEdit::class, ['mediaId' => 99999]);
    }
}
