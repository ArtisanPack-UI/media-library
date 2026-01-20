<?php

declare(strict_types=1);

namespace Tests\Feature;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaStatistics;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Media Statistics Component Tests
 *
 * Tests for the MediaStatistics Livewire component that displays
 * comprehensive statistics about the media library.
 *
 *
 * @since   1.1.0
 */
class MediaStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineDatabaseMigrations();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test component can be rendered.
     */
    public function test_component_can_be_rendered(): void
    {
        Livewire::test(MediaStatistics::class)
            ->assertStatus(200);
    }

    /**
     * Test totalMedia returns correct count.
     */
    public function test_total_media_returns_correct_count(): void
    {
        Media::factory()->count(5)->uploadedBy($this->user)->create();

        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalMedia'))->toBe(5);
    }

    /**
     * Test totalMedia returns zero when no media exists.
     */
    public function test_total_media_returns_zero_when_empty(): void
    {
        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalMedia'))->toBe(0);
    }

    /**
     * Test totalStorageBytes returns correct sum.
     */
    public function test_total_storage_bytes_returns_correct_sum(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 1000]);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 2000]);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 3000]);

        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalStorageBytes'))->toBe(6000);
    }

    /**
     * Test totalStorageFormatted returns human-readable string.
     */
    public function test_total_storage_formatted_returns_readable_string(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 1536000]); // ~1.5 MB

        $component = Livewire::test(MediaStatistics::class);

        $formatted = $component->get('totalStorageFormatted');
        expect($formatted)->toContain('MB');
    }

    /**
     * Test totalStorageFormatted returns 0 B when no media.
     */
    public function test_total_storage_formatted_returns_zero_when_empty(): void
    {
        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalStorageFormatted'))->toBe('0 B');
    }

    /**
     * Test mediaByType returns correct counts for each type.
     */
    public function test_media_by_type_returns_correct_counts(): void
    {
        // Create media of different types
        Media::factory()->count(3)->image()->uploadedBy($this->user)->create();
        Media::factory()->count(2)->video()->uploadedBy($this->user)->create();
        Media::factory()->count(1)->audio()->uploadedBy($this->user)->create();
        Media::factory()->count(4)->document()->uploadedBy($this->user)->create();

        $component = Livewire::test(MediaStatistics::class);
        $mediaByType = $component->get('mediaByType');

        expect($mediaByType['images'])->toBe(3);
        expect($mediaByType['videos'])->toBe(2);
        expect($mediaByType['audio'])->toBe(1);
        expect($mediaByType['documents'])->toBe(4);
    }

    /**
     * Test storageByType returns bytes and formatted for each type.
     */
    public function test_storage_by_type_returns_bytes_and_formatted(): void
    {
        Media::factory()->image()->uploadedBy($this->user)->create(['file_size' => 5000]);
        Media::factory()->video()->uploadedBy($this->user)->create(['file_size' => 10000]);

        $component = Livewire::test(MediaStatistics::class);
        $storageByType = $component->get('storageByType');

        expect($storageByType['images']['bytes'])->toBe(5000);
        expect($storageByType['images']['formatted'])->toContain('KB');
        expect($storageByType['videos']['bytes'])->toBe(10000);
        expect($storageByType['videos']['formatted'])->toContain('KB');
    }

    /**
     * Test recentUploadsCount counts media from last N days.
     */
    public function test_recent_uploads_count_returns_correct_count(): void
    {
        // Create recent media
        Media::factory()->count(3)->uploadedBy($this->user)->create([
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // Create old media
        Media::factory()->count(2)->uploadedBy($this->user)->create([
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $component = Livewire::test(MediaStatistics::class);

        // Default recentDays is 7
        expect($component->get('recentUploadsCount'))->toBe(3);
    }

    /**
     * Test dailyUploadCounts returns array with correct length.
     */
    public function test_daily_upload_counts_returns_array_with_correct_length(): void
    {
        $component = Livewire::test(MediaStatistics::class);
        $dailyCounts = $component->get('dailyUploadCounts');

        // Default recentDays is 7, so should have 7 entries
        expect($dailyCounts)->toHaveCount(7);
    }

    /**
     * Test dailyUploadCounts fills missing days with zeros.
     */
    public function test_daily_upload_counts_fills_missing_days_with_zeros(): void
    {
        // Only create media for today
        Media::factory()->count(2)->uploadedBy($this->user)->create([
            'created_at' => Carbon::now(),
        ]);

        $component = Livewire::test(MediaStatistics::class);
        $dailyCounts = $component->get('dailyUploadCounts');

        // Should have 6 zeros for the 6 days without uploads (today has 2)
        $zeroCount = count(array_filter($dailyCounts, fn ($count) => $count === 0));
        expect($zeroCount)->toBe(6);
        
        // Last entry (today) should have the uploads
        expect($dailyCounts[6])->toBe(2);
    }

    /**
     * Test topFolders returns folders ordered by media count.
     */
    public function test_top_folders_returns_ordered_by_media_count(): void
    {
        $folder1 = MediaFolder::factory()->createdBy($this->user)->create(['name' => 'Folder 1']);
        $folder2 = MediaFolder::factory()->createdBy($this->user)->create(['name' => 'Folder 2']);
        $folder3 = MediaFolder::factory()->createdBy($this->user)->create(['name' => 'Folder 3']);

        // Create different amounts of media in each folder
        Media::factory()->count(5)->inFolder($folder1)->uploadedBy($this->user)->create();
        Media::factory()->count(10)->inFolder($folder2)->uploadedBy($this->user)->create();
        Media::factory()->count(3)->inFolder($folder3)->uploadedBy($this->user)->create();

        $component = Livewire::test(MediaStatistics::class);
        $topFolders = $component->get('topFolders');

        expect($topFolders->first()->name)->toBe('Folder 2');
        expect($topFolders->first()->media_count)->toBe(10);
    }

    /**
     * Test topFolders respects the limit.
     */
    public function test_top_folders_respects_limit(): void
    {
        // Create more folders than the default limit
        for ($i = 0; $i < 10; $i++) {
            $folder = MediaFolder::factory()->createdBy($this->user)->create();
            Media::factory()->count($i + 1)->inFolder($folder)->uploadedBy($this->user)->create();
        }

        $component = Livewire::test(MediaStatistics::class);
        $topFolders = $component->get('topFolders');

        // Default limit is 5
        expect($topFolders)->toHaveCount(5);
    }

    /**
     * Test topTags returns tags ordered by media count.
     */
    public function test_top_tags_returns_ordered_by_media_count(): void
    {
        $tag1 = MediaTag::factory()->create(['name' => 'Tag 1']);
        $tag2 = MediaTag::factory()->create(['name' => 'Tag 2']);
        $tag3 = MediaTag::factory()->create(['name' => 'Tag 3']);

        // Attach different amounts of media to each tag
        $media1 = Media::factory()->count(3)->uploadedBy($this->user)->create();
        $media2 = Media::factory()->count(7)->uploadedBy($this->user)->create();
        $media3 = Media::factory()->count(2)->uploadedBy($this->user)->create();

        foreach ($media1 as $media) {
            $media->tags()->attach($tag1);
        }
        foreach ($media2 as $media) {
            $media->tags()->attach($tag2);
        }
        foreach ($media3 as $media) {
            $media->tags()->attach($tag3);
        }

        $component = Livewire::test(MediaStatistics::class);
        $topTags = $component->get('topTags');

        expect($topTags->first()->name)->toBe('Tag 2');
        expect($topTags->first()->media_count)->toBe(7);
    }

    /**
     * Test totalFolders returns correct count.
     */
    public function test_total_folders_returns_correct_count(): void
    {
        MediaFolder::factory()->count(8)->createdBy($this->user)->create();

        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalFolders'))->toBe(8);
    }

    /**
     * Test totalTags returns correct count.
     */
    public function test_total_tags_returns_correct_count(): void
    {
        MediaTag::factory()->count(12)->create();

        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('totalTags'))->toBe(12);
    }

    /**
     * Test averageFileSize returns formatted string.
     */
    public function test_average_file_size_returns_formatted_string(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 1000]);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 2000]);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 3000]);

        $component = Livewire::test(MediaStatistics::class);
        $avgSize = $component->get('averageFileSize');

        // Average is 2000 bytes, which should be displayed as ~1.95 KB
        expect($avgSize)->toContain('KB');
    }

    /**
     * Test largestFile returns the media with largest file_size.
     */
    public function test_largest_file_returns_largest_media(): void
    {
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 500, 'title' => 'Small']);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 5000, 'title' => 'Large']);
        Media::factory()->uploadedBy($this->user)->create(['file_size' => 1000, 'title' => 'Medium']);

        $component = Livewire::test(MediaStatistics::class);
        $largestFile = $component->get('largestFile');

        expect($largestFile->title)->toBe('Large');
        expect($largestFile->file_size)->toBe(5000);
    }

    /**
     * Test largestFile returns null when no media exists.
     */
    public function test_largest_file_returns_null_when_empty(): void
    {
        $component = Livewire::test(MediaStatistics::class);

        expect($component->get('largestFile'))->toBeNull();
    }

    /**
     * Test component displays overview stats section.
     */
    public function test_component_displays_overview_stats(): void
    {
        Media::factory()->count(5)->uploadedBy($this->user)->create();

        Livewire::test(MediaStatistics::class)
            ->assertSee(__('Total Media'))
            ->assertSee(__('Storage Used'))
            ->assertSee(__('Avg. File Size'));
    }

    /**
     * Test component displays media by type section.
     */
    public function test_component_displays_media_by_type_section(): void
    {
        Media::factory()->image()->uploadedBy($this->user)->create();

        // Verify component renders and contains the type labels in some form
        $component = Livewire::test(MediaStatistics::class);
        $html = $component->html();

        // Check that the component rendered and contains expected elements
        expect($html)->toContain('Images');
        expect($html)->toContain('Videos');
        expect($html)->toContain('Audio');
        expect($html)->toContain('Documents');
    }

    /**
     * Test component displays storage by type section.
     */
    public function test_component_displays_storage_by_type_section(): void
    {
        Media::factory()->video()->uploadedBy($this->user)->create();

        // Verify component renders and contains storage type elements
        $component = Livewire::test(MediaStatistics::class);
        $html = $component->html();

        // Check that the component rendered with storage-related progress bars and labels
        // The storage section uses the same type labels as media by type
        expect($html)->toContain('progress'); // Progress component class
    }

    /**
     * Test component displays top folders section.
     */
    public function test_component_displays_top_folders_section(): void
    {
        $folder = MediaFolder::factory()->createdBy($this->user)->create(['name' => 'Test Folder']);
        Media::factory()->inFolder($folder)->uploadedBy($this->user)->create();

        // Verify component renders with folder data
        $component = Livewire::test(MediaStatistics::class);
        $html = $component->html();

        // Check that the component rendered with folder elements
        expect($html)->toContain('Test Folder');
    }

    /**
     * Test component displays empty state for folders.
     */
    public function test_component_displays_empty_state_for_folders(): void
    {
        Livewire::test(MediaStatistics::class)
            ->assertSee(__('No folders created yet'));
    }

    /**
     * Test component displays top tags section.
     */
    public function test_component_displays_top_tags_section(): void
    {
        $tag = MediaTag::factory()->create(['name' => 'Featured']);
        $media = Media::factory()->uploadedBy($this->user)->create();
        $media->tags()->attach($tag);

        // Verify component renders with tag data
        $component = Livewire::test(MediaStatistics::class);
        $html = $component->html();

        // Check that the component rendered with tag elements
        expect($html)->toContain('Featured');
    }

    /**
     * Test component displays empty state for tags.
     */
    public function test_component_displays_empty_state_for_tags(): void
    {
        Livewire::test(MediaStatistics::class)
            ->assertSee(__('No tags created yet'));
    }

    /**
     * Test component displays largest file section when media exists.
     */
    public function test_component_displays_largest_file_section(): void
    {
        Media::factory()->uploadedBy($this->user)->create([
            'file_size' => 5000000,
            'title' => 'Big File',
        ]);

        // Verify component renders with largest file data
        $component = Livewire::test(MediaStatistics::class);
        $html = $component->html();

        // Check that the component rendered with the file title
        expect($html)->toContain('Big File');
    }

    /**
     * Test custom topItemsLimit can be set.
     */
    public function test_custom_top_items_limit_can_be_set(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $folder = MediaFolder::factory()->createdBy($this->user)->create();
            Media::factory()->count($i + 1)->inFolder($folder)->uploadedBy($this->user)->create();
        }

        $component = Livewire::test(MediaStatistics::class)
            ->set('topItemsLimit', 3);

        $topFolders = $component->get('topFolders');

        expect($topFolders)->toHaveCount(3);
    }

    /**
     * Test custom recentDays can be set.
     */
    public function test_custom_recent_days_can_be_set(): void
    {
        // Create media from 20 days ago
        Media::factory()->count(5)->uploadedBy($this->user)->create([
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $component = Livewire::test(MediaStatistics::class)
            ->set('recentDays', 30);

        // With 30 day window, should find the media
        expect($component->get('recentUploadsCount'))->toBe(5);
    }
}
