<?php

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Helpers\BlockMediaHelper;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Block Media Helper Tests
 *
 * Tests for the BlockMediaHelper class that provides utilities
 * for visual editor block integration.
 *
 *
 * @since   1.1.0
 */
class BlockMediaHelperTest extends TestCase
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
    // getBlockMediaUrl Tests
    // =========================================================================

    /**
     * Test getBlockMediaUrl returns null for null media ID.
     */
    public function test_get_block_media_url_returns_null_for_null_id(): void
    {
        $result = BlockMediaHelper::getBlockMediaUrl(null);

        expect($result)->toBeNull();
    }

    /**
     * Test getBlockMediaUrl returns null for non-existent media.
     */
    public function test_get_block_media_url_returns_null_for_non_existent_media(): void
    {
        $result = BlockMediaHelper::getBlockMediaUrl(999999);

        expect($result)->toBeNull();
    }

    /**
     * Test getBlockMediaUrl returns image URL for images.
     */
    public function test_get_block_media_url_returns_url_for_image(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::getBlockMediaUrl($media->id, 'medium');

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    }

    /**
     * Test getBlockMediaUrl returns URL for non-image media.
     */
    public function test_get_block_media_url_returns_url_for_non_image(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create();

        $result = BlockMediaHelper::getBlockMediaUrl($media->id);

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    }

    /**
     * Test getBlockMediaUrl accepts different sizes.
     */
    public function test_get_block_media_url_accepts_different_sizes(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $thumbnail = BlockMediaHelper::getBlockMediaUrl($media->id, 'thumbnail');
        $medium = BlockMediaHelper::getBlockMediaUrl($media->id, 'medium');
        $large = BlockMediaHelper::getBlockMediaUrl($media->id, 'large');
        $full = BlockMediaHelper::getBlockMediaUrl($media->id, 'full');

        expect($thumbnail)->toBeString();
        expect($medium)->toBeString();
        expect($large)->toBeString();
        expect($full)->toBeString();
    }

    // =========================================================================
    // getBlockMediaData Tests
    // =========================================================================

    /**
     * Test getBlockMediaData returns null for null media ID.
     */
    public function test_get_block_media_data_returns_null_for_null_id(): void
    {
        $result = BlockMediaHelper::getBlockMediaData(null);

        expect($result)->toBeNull();
    }

    /**
     * Test getBlockMediaData returns null for non-existent media.
     */
    public function test_get_block_media_data_returns_null_for_non_existent_media(): void
    {
        $result = BlockMediaHelper::getBlockMediaData(999999);

        expect($result)->toBeNull();
    }

    /**
     * Test getBlockMediaData returns expected structure for images.
     */
    public function test_get_block_media_data_returns_structure_for_image(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create([
            'title' => 'Test Image',
            'alt_text' => 'Test Alt Text',
            'width' => 800,
            'height' => 600,
        ]);

        $result = BlockMediaHelper::getBlockMediaData($media->id);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('id');
        expect($result)->toHaveKey('url');
        expect($result)->toHaveKey('alt');
        expect($result)->toHaveKey('title');
        expect($result)->toHaveKey('mime_type');
        expect($result)->toHaveKey('file_name');
        expect($result)->toHaveKey('file_size');
        expect($result)->toHaveKey('thumbnail');
        expect($result)->toHaveKey('medium');
        expect($result)->toHaveKey('large');
        expect($result)->toHaveKey('width');
        expect($result)->toHaveKey('height');
        expect($result)->toHaveKey('sizes');

        expect($result['id'])->toBe($media->id);
        expect($result['title'])->toBe('Test Image');
        expect($result['alt'])->toBe('Test Alt Text');
        expect($result['width'])->toBe(800);
        expect($result['height'])->toBe(600);
    }

    /**
     * Test getBlockMediaData returns expected structure for video.
     */
    public function test_get_block_media_data_returns_structure_for_video(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create([
            'duration' => 120,
        ]);

        $result = BlockMediaHelper::getBlockMediaData($media->id);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('id');
        expect($result)->toHaveKey('url');
        expect($result)->toHaveKey('mime_type');
        expect($result)->toHaveKey('duration');
        expect($result['duration'])->toBe(120);

        // Should not have image-specific keys
        expect($result)->not->toHaveKey('thumbnail');
        expect($result)->not->toHaveKey('width');
    }

    /**
     * Test getBlockMediaData returns expected structure for audio.
     */
    public function test_get_block_media_data_returns_structure_for_audio(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->audio()->create([
            'duration' => 180,
        ]);

        $result = BlockMediaHelper::getBlockMediaData($media->id);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('duration');
        expect($result['duration'])->toBe(180);
    }

    // =========================================================================
    // validateForBlock Tests
    // =========================================================================

    /**
     * Test validateForBlock returns false for non-existent media.
     */
    public function test_validate_for_block_returns_false_for_non_existent_media(): void
    {
        $result = BlockMediaHelper::validateForBlock(999999, 'image');

        expect($result)->toBeFalse();
    }

    /**
     * Test validateForBlock returns true for valid image in image block.
     */
    public function test_validate_for_block_validates_image_in_image_block(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create([
            'file_name' => 'photo.jpg',
        ]);

        $result = BlockMediaHelper::validateForBlock($media->id, 'image');

        expect($result)->toBeTrue();
    }

    /**
     * Test validateForBlock returns false for video in image block.
     */
    public function test_validate_for_block_rejects_video_in_image_block(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create();

        $result = BlockMediaHelper::validateForBlock($media->id, 'image');

        expect($result)->toBeFalse();
    }

    /**
     * Test validateForBlock returns true for video in video block.
     */
    public function test_validate_for_block_validates_video_in_video_block(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create([
            'file_name' => 'video.mp4',
        ]);

        $result = BlockMediaHelper::validateForBlock($media->id, 'video');

        expect($result)->toBeTrue();
    }

    /**
     * Test validateForBlock validates audio in audio block.
     */
    public function test_validate_for_block_validates_audio_in_audio_block(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->audio()->create([
            'file_name' => 'audio.mp3',
        ]);

        $result = BlockMediaHelper::validateForBlock($media->id, 'audio');

        expect($result)->toBeTrue();
    }

    /**
     * Test validateForBlock validates multiple types in hero block.
     */
    public function test_validate_for_block_validates_multiple_types_in_hero_block(): void
    {
        $image = Media::factory()->uploadedBy($this->user)->image()->create();
        $video = Media::factory()->uploadedBy($this->user)->video()->create();

        expect(BlockMediaHelper::validateForBlock($image->id, 'hero'))->toBeTrue();
        expect(BlockMediaHelper::validateForBlock($video->id, 'hero'))->toBeTrue();
    }

    /**
     * Test validateForBlock uses default requirements for unknown block type.
     */
    public function test_validate_for_block_uses_default_for_unknown_type(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::validateForBlock($media->id, 'unknown_block_type');

        expect($result)->toBeTrue();
    }

    // =========================================================================
    // getBlockRequirements Tests
    // =========================================================================

    /**
     * Test getBlockRequirements returns config for known block type.
     */
    public function test_get_block_requirements_returns_config_for_known_type(): void
    {
        $requirements = BlockMediaHelper::getBlockRequirements('image');

        expect($requirements)->toBeArray();
        expect($requirements)->toHaveKey('types');
        expect($requirements['types'])->toContain('image');
    }

    /**
     * Test getBlockRequirements returns default for unknown block type.
     */
    public function test_get_block_requirements_returns_default_for_unknown_type(): void
    {
        $requirements = BlockMediaHelper::getBlockRequirements('completely_unknown_type');

        expect($requirements)->toBeArray();
        expect($requirements)->toHaveKey('types');
        expect($requirements['types'])->toContain('image');
        expect($requirements['types'])->toContain('video');
        expect($requirements['types'])->toContain('audio');
        expect($requirements['types'])->toContain('document');
    }

    // =========================================================================
    // getMediaTypeCategory Tests
    // =========================================================================

    /**
     * Test getMediaTypeCategory returns correct type for image.
     */
    public function test_get_media_type_category_returns_image(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::getMediaTypeCategory($media);

        expect($result)->toBe('image');
    }

    /**
     * Test getMediaTypeCategory returns correct type for video.
     */
    public function test_get_media_type_category_returns_video(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create();

        $result = BlockMediaHelper::getMediaTypeCategory($media);

        expect($result)->toBe('video');
    }

    /**
     * Test getMediaTypeCategory returns correct type for audio.
     */
    public function test_get_media_type_category_returns_audio(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->audio()->create();

        $result = BlockMediaHelper::getMediaTypeCategory($media);

        expect($result)->toBe('audio');
    }

    /**
     * Test getMediaTypeCategory returns document for other types.
     */
    public function test_get_media_type_category_returns_document(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->document()->create();

        $result = BlockMediaHelper::getMediaTypeCategory($media);

        expect($result)->toBe('document');
    }

    // =========================================================================
    // getMultipleBlockMediaData Tests
    // =========================================================================

    /**
     * Test getMultipleBlockMediaData returns empty array for empty input.
     */
    public function test_get_multiple_block_media_data_returns_empty_for_empty_input(): void
    {
        $result = BlockMediaHelper::getMultipleBlockMediaData([]);

        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    }

    /**
     * Test getMultipleBlockMediaData returns data for multiple media.
     */
    public function test_get_multiple_block_media_data_returns_data_for_multiple_media(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->image()->create();
        $media2 = Media::factory()->uploadedBy($this->user)->image()->create();
        $media3 = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::getMultipleBlockMediaData([$media1->id, $media2->id, $media3->id]);

        expect($result)->toHaveCount(3);
        expect($result[0]['id'])->toBe($media1->id);
        expect($result[1]['id'])->toBe($media2->id);
        expect($result[2]['id'])->toBe($media3->id);
    }

    /**
     * Test getMultipleBlockMediaData skips non-existent media.
     */
    public function test_get_multiple_block_media_data_skips_non_existent(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::getMultipleBlockMediaData([$media->id, 999999, 888888]);

        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe($media->id);
    }

    // =========================================================================
    // validateMultipleForBlock Tests
    // =========================================================================

    /**
     * Test validateMultipleForBlock validates min file requirement.
     */
    public function test_validate_multiple_for_block_validates_min_files(): void
    {
        $result = BlockMediaHelper::validateMultipleForBlock([], 'gallery');

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->not->toBeEmpty();
    }

    /**
     * Test validateMultipleForBlock validates max file requirement.
     */
    public function test_validate_multiple_for_block_validates_max_files(): void
    {
        // Create more than 50 media (gallery max)
        $mediaIds = [];
        for ($i = 0; $i < 52; $i++) {
            $media = Media::factory()->uploadedBy($this->user)->image()->create();
            $mediaIds[] = $media->id;
        }

        $result = BlockMediaHelper::validateMultipleForBlock($mediaIds, 'gallery');

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->not->toBeEmpty();
    }

    /**
     * Test validateMultipleForBlock returns valid for correct media.
     */
    public function test_validate_multiple_for_block_returns_valid_for_correct_media(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->image()->create(['file_name' => 'photo1.jpg']);
        $media2 = Media::factory()->uploadedBy($this->user)->image()->create(['file_name' => 'photo2.jpg']);

        $result = BlockMediaHelper::validateMultipleForBlock([$media1->id, $media2->id], 'gallery');

        expect($result['valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    }

    /**
     * Test validateMultipleForBlock reports invalid media items.
     */
    public function test_validate_multiple_for_block_reports_invalid_items(): void
    {
        $image = Media::factory()->uploadedBy($this->user)->image()->create(['file_name' => 'photo.jpg']);
        $video = Media::factory()->uploadedBy($this->user)->video()->create();

        $result = BlockMediaHelper::validateMultipleForBlock([$image->id, $video->id], 'gallery');

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->not->toBeEmpty();
    }

    // =========================================================================
    // getOptimizedBlockMediaUrl Tests
    // =========================================================================

    /**
     * Test getOptimizedBlockMediaUrl returns null for null media ID.
     */
    public function test_get_optimized_block_media_url_returns_null_for_null_id(): void
    {
        $result = BlockMediaHelper::getOptimizedBlockMediaUrl(null, 'hero');

        expect($result)->toBeNull();
    }

    /**
     * Test getOptimizedBlockMediaUrl returns null for non-existent media.
     */
    public function test_get_optimized_block_media_url_returns_null_for_non_existent(): void
    {
        $result = BlockMediaHelper::getOptimizedBlockMediaUrl(999999, 'hero');

        expect($result)->toBeNull();
    }

    /**
     * Test getOptimizedBlockMediaUrl returns URL for valid media.
     */
    public function test_get_optimized_block_media_url_returns_url(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = BlockMediaHelper::getOptimizedBlockMediaUrl($media->id, 'hero');

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    }

    /**
     * Test getOptimizedBlockMediaUrl returns original URL for non-images.
     */
    public function test_get_optimized_block_media_url_returns_original_for_non_images(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->video()->create();

        $result = BlockMediaHelper::getOptimizedBlockMediaUrl($media->id, 'video');

        expect($result)->toBe($media->url());
    }

    // =========================================================================
    // Global Helper Function Tests
    // =========================================================================

    /**
     * Test apBlockMedia helper function works.
     */
    public function test_ap_block_media_helper_function(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = apBlockMedia($media->id);

        expect($result)->toBeArray();
        expect($result['id'])->toBe($media->id);
    }

    /**
     * Test apBlockMediaUrl helper function works.
     */
    public function test_ap_block_media_url_helper_function(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = apBlockMediaUrl($media->id, 'medium');

        expect($result)->toBeString();
    }

    /**
     * Test apValidateBlockMedia helper function works.
     */
    public function test_ap_validate_block_media_helper_function(): void
    {
        $media = Media::factory()->uploadedBy($this->user)->image()->create(['file_name' => 'photo.jpg']);

        $result = apValidateBlockMedia($media->id, 'image');

        expect($result)->toBeTrue();
    }

    /**
     * Test apBlockMediaMultiple helper function works.
     */
    public function test_ap_block_media_multiple_helper_function(): void
    {
        $media1 = Media::factory()->uploadedBy($this->user)->image()->create();
        $media2 = Media::factory()->uploadedBy($this->user)->image()->create();

        $result = apBlockMediaMultiple([$media1->id, $media2->id]);

        expect($result)->toHaveCount(2);
    }
}
