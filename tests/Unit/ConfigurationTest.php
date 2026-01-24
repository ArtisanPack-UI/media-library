<?php

/**
 * Configuration Tests
 *
 * Tests for v1.1 configuration options including features, UI enhancements,
 * visual editor settings, and block requirements.
 *
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Configuration Test Class
 */
class ConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure config is loaded fresh
        config([
            'artisanpack.media' => include dirname(__DIR__, 2).'/config/media.php',
        ]);
    }

    // =========================================================================
    // Feature Flags Tests
    // =========================================================================

    /**
     * Test streaming_upload config is accessible.
     */
    public function test_streaming_upload_config_is_accessible(): void
    {
        expect(config('artisanpack.media.features.streaming_upload'))->not->toBeNull();
        expect(config('artisanpack.media.features.streaming_upload'))->toBeBool();
    }

    /**
     * Test streaming_upload defaults to true.
     */
    public function test_streaming_upload_defaults_to_true(): void
    {
        expect(config('artisanpack.media.features.streaming_upload'))->toBeTrue();
    }

    /**
     * Test streaming_fallback_interval config is accessible.
     */
    public function test_streaming_fallback_interval_is_accessible(): void
    {
        expect(config('artisanpack.media.features.streaming_fallback_interval'))->not->toBeNull();
        expect(config('artisanpack.media.features.streaming_fallback_interval'))->toBeInt();
    }

    /**
     * Test streaming_fallback_interval defaults to 500.
     */
    public function test_streaming_fallback_interval_defaults_to_500(): void
    {
        expect(config('artisanpack.media.features.streaming_fallback_interval'))->toBe(500);
    }

    // =========================================================================
    // UI Enhancement Tests
    // =========================================================================

    /**
     * Test glass_effects config structure.
     */
    public function test_glass_effects_config_structure(): void
    {
        $glassEffects = config('artisanpack.media.ui.glass_effects');

        expect($glassEffects)->toBeArray();
        expect($glassEffects)->toHaveKey('enabled');
        expect($glassEffects)->toHaveKey('card_overlay');
        expect($glassEffects)->toHaveKey('modal_backdrop');
    }

    /**
     * Test glass_effects defaults.
     */
    public function test_glass_effects_defaults(): void
    {
        expect(config('artisanpack.media.ui.glass_effects.enabled'))->toBeTrue();
        expect(config('artisanpack.media.ui.glass_effects.card_overlay'))->toBe('frost');
        expect(config('artisanpack.media.ui.glass_effects.modal_backdrop'))->toBe('blur');
    }

    /**
     * Test stats_dashboard config structure.
     */
    public function test_stats_dashboard_config_structure(): void
    {
        $statsDashboard = config('artisanpack.media.ui.stats_dashboard');

        expect($statsDashboard)->toBeArray();
        expect($statsDashboard)->toHaveKey('enabled');
        expect($statsDashboard)->toHaveKey('sparkline_days');
        expect($statsDashboard)->toHaveKey('refresh_interval');
    }

    /**
     * Test stats_dashboard defaults.
     */
    public function test_stats_dashboard_defaults(): void
    {
        expect(config('artisanpack.media.ui.stats_dashboard.enabled'))->toBeTrue();
        expect(config('artisanpack.media.ui.stats_dashboard.sparkline_days'))->toBe(7);
        expect(config('artisanpack.media.ui.stats_dashboard.refresh_interval'))->toBe(60);
    }

    /**
     * Test table_export config structure.
     */
    public function test_table_export_config_structure(): void
    {
        $tableExport = config('artisanpack.media.ui.table_export');

        expect($tableExport)->toBeArray();
        expect($tableExport)->toHaveKey('enabled');
        expect($tableExport)->toHaveKey('formats');
        expect($tableExport)->toHaveKey('max_rows');
    }

    /**
     * Test table_export defaults.
     */
    public function test_table_export_defaults(): void
    {
        expect(config('artisanpack.media.ui.table_export.enabled'))->toBeTrue();
        expect(config('artisanpack.media.ui.table_export.formats'))->toContain('csv');
        expect(config('artisanpack.media.ui.table_export.formats'))->toContain('xlsx');
        expect(config('artisanpack.media.ui.table_export.formats'))->toContain('pdf');
        expect(config('artisanpack.media.ui.table_export.max_rows'))->toBe(10000);
    }

    // =========================================================================
    // Visual Editor Tests
    // =========================================================================

    /**
     * Test visual_editor config structure.
     */
    public function test_visual_editor_config_structure(): void
    {
        $visualEditor = config('artisanpack.media.visual_editor');

        expect($visualEditor)->toBeArray();
        expect($visualEditor)->toHaveKey('track_recently_used');
        expect($visualEditor)->toHaveKey('recently_used_limit');
        expect($visualEditor)->toHaveKey('quick_upload_select');
        expect($visualEditor)->toHaveKey('picker');
    }

    /**
     * Test visual_editor defaults.
     */
    public function test_visual_editor_defaults(): void
    {
        expect(config('artisanpack.media.visual_editor.track_recently_used'))->toBeTrue();
        expect(config('artisanpack.media.visual_editor.recently_used_limit'))->toBe(20);
        expect(config('artisanpack.media.visual_editor.quick_upload_select'))->toBeTrue();
    }

    /**
     * Test picker config structure.
     */
    public function test_picker_config_structure(): void
    {
        $picker = config('artisanpack.media.visual_editor.picker');

        expect($picker)->toBeArray();
        expect($picker)->toHaveKey('default_view');
        expect($picker)->toHaveKey('per_page');
        expect($picker)->toHaveKey('show_upload_tab');
        expect($picker)->toHaveKey('enable_reorder');
        expect($picker)->toHaveKey('show_details_panel');
    }

    /**
     * Test picker defaults.
     */
    public function test_picker_defaults(): void
    {
        expect(config('artisanpack.media.visual_editor.picker.default_view'))->toBe('grid');
        expect(config('artisanpack.media.visual_editor.picker.per_page'))->toBe(24);
        expect(config('artisanpack.media.visual_editor.picker.show_upload_tab'))->toBeTrue();
        expect(config('artisanpack.media.visual_editor.picker.enable_reorder'))->toBeTrue();
        expect(config('artisanpack.media.visual_editor.picker.show_details_panel'))->toBeTrue();
    }

    // =========================================================================
    // Block Requirements Tests
    // =========================================================================

    /**
     * Test block_requirements has default block.
     */
    public function test_block_requirements_has_default_block(): void
    {
        $blockRequirements = config('artisanpack.media.block_requirements');

        expect($blockRequirements)->toBeArray();
        expect($blockRequirements)->toHaveKey('default');
    }

    /**
     * Test default block requirements structure.
     */
    public function test_default_block_requirements_structure(): void
    {
        $default = config('artisanpack.media.block_requirements.default');

        expect($default)->toBeArray();
        expect($default)->toHaveKey('types');
        expect($default)->toHaveKey('max_files');
        expect($default)->toHaveKey('min_files');
    }

    /**
     * Test image block requirements.
     */
    public function test_image_block_requirements(): void
    {
        $image = config('artisanpack.media.block_requirements.image');

        expect($image)->toBeArray();
        expect($image['types'])->toBe(['image']);
        expect($image['max_files'])->toBe(1);
        expect($image['min_files'])->toBe(1);
        expect($image)->toHaveKey('allowed_extensions');
    }

    /**
     * Test gallery block requirements.
     */
    public function test_gallery_block_requirements(): void
    {
        $gallery = config('artisanpack.media.block_requirements.gallery');

        expect($gallery)->toBeArray();
        expect($gallery['types'])->toBe(['image']);
        expect($gallery['max_files'])->toBe(50);
        expect($gallery['min_files'])->toBe(1);
    }

    /**
     * Test video block requirements.
     */
    public function test_video_block_requirements(): void
    {
        $video = config('artisanpack.media.block_requirements.video');

        expect($video)->toBeArray();
        expect($video['types'])->toBe(['video']);
        expect($video['max_files'])->toBe(1);
    }

    /**
     * Test audio block requirements.
     */
    public function test_audio_block_requirements(): void
    {
        $audio = config('artisanpack.media.block_requirements.audio');

        expect($audio)->toBeArray();
        expect($audio['types'])->toBe(['audio']);
        expect($audio['max_files'])->toBe(1);
    }

    /**
     * Test document block requirements.
     */
    public function test_document_block_requirements(): void
    {
        $document = config('artisanpack.media.block_requirements.document');

        expect($document)->toBeArray();
        expect($document['types'])->toBe(['document']);
        expect($document['max_files'])->toBe(10);
    }

    /**
     * Test hero block requirements.
     */
    public function test_hero_block_requirements(): void
    {
        $hero = config('artisanpack.media.block_requirements.hero');

        expect($hero)->toBeArray();
        expect($hero['types'])->toContain('image');
        expect($hero['types'])->toContain('video');
        expect($hero)->toHaveKey('recommended_dimensions');
    }

    /**
     * Test background block requirements.
     */
    public function test_background_block_requirements(): void
    {
        $background = config('artisanpack.media.block_requirements.background');

        expect($background)->toBeArray();
        expect($background['types'])->toContain('image');
        expect($background['types'])->toContain('video');
    }

    // =========================================================================
    // Environment Variable Override Tests
    // =========================================================================

    /**
     * Test config can be overridden.
     */
    public function test_config_can_be_overridden(): void
    {
        config(['artisanpack.media.features.streaming_upload' => false]);
        expect(config('artisanpack.media.features.streaming_upload'))->toBeFalse();

        config(['artisanpack.media.features.streaming_fallback_interval' => 1000]);
        expect(config('artisanpack.media.features.streaming_fallback_interval'))->toBe(1000);
    }

    /**
     * Test UI config can be overridden.
     */
    public function test_ui_config_can_be_overridden(): void
    {
        config(['artisanpack.media.ui.glass_effects.enabled' => false]);
        expect(config('artisanpack.media.ui.glass_effects.enabled'))->toBeFalse();

        config(['artisanpack.media.ui.stats_dashboard.sparkline_days' => 14]);
        expect(config('artisanpack.media.ui.stats_dashboard.sparkline_days'))->toBe(14);
    }

    /**
     * Test visual editor config can be overridden.
     */
    public function test_visual_editor_config_can_be_overridden(): void
    {
        config(['artisanpack.media.visual_editor.recently_used_limit' => 50]);
        expect(config('artisanpack.media.visual_editor.recently_used_limit'))->toBe(50);

        config(['artisanpack.media.visual_editor.picker.per_page' => 48]);
        expect(config('artisanpack.media.visual_editor.picker.per_page'))->toBe(48);
    }

    /**
     * Test custom block requirements can be added.
     */
    public function test_custom_block_requirements_can_be_added(): void
    {
        config(['artisanpack.media.block_requirements.custom_block' => [
            'types' => ['image'],
            'max_files' => 5,
            'min_files' => 2,
        ]]);

        $customBlock = config('artisanpack.media.block_requirements.custom_block');

        expect($customBlock)->toBeArray();
        expect($customBlock['types'])->toBe(['image']);
        expect($customBlock['max_files'])->toBe(5);
        expect($customBlock['min_files'])->toBe(2);
    }
}
