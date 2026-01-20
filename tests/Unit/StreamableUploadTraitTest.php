<?php

/**
 * StreamableUpload Trait Tests
 *
 * Tests for the StreamableUpload trait functionality including streaming
 * detection, progress updates, and fallback behavior.
 *
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Helpers\LivewireHelper;
use ArtisanPackUI\MediaLibrary\Traits\StreamableUpload;
use Tests\TestCase;

/**
 * Mock class that uses the StreamableUpload trait for testing.
 */
class StreamableUploadTestComponent
{
    use StreamableUpload;

    public int $uploadProgress = 0;

    public bool $hasStreamMethod = false;

    public array $streamCalls = [];

    /**
     * Mock the stream method to track calls.
     */
    public function stream(string $to, string $content, bool $replace = false): void
    {
        $this->streamCalls[] = [
            'to' => $to,
            'content' => $content,
            'replace' => $replace,
        ];
    }

    /**
     * Override method_exists check for testing.
     */
    public function setHasStreamMethod(bool $hasStream): void
    {
        $this->hasStreamMethod = $hasStream;
    }
}

/**
 * StreamableUpload Trait Test Class
 */
class StreamableUploadTraitTest extends TestCase
{
    protected StreamableUploadTestComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->component = new StreamableUploadTestComponent;

        // Set up default config
        config([
            'artisanpack.media.features.streaming_upload' => true,
            'artisanpack.media.features.streaming_fallback_interval' => 500,
        ]);
    }

    protected function tearDown(): void
    {
        LivewireHelper::clearCache();

        parent::tearDown();
    }

    /**
     * Test isStreamingEnabled returns true when config is enabled and stream method exists.
     */
    public function test_is_streaming_enabled_returns_true_when_enabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        // The component has a stream method
        expect($this->component->isStreamingEnabled())->toBeTrue();
    }

    /**
     * Test isStreamingEnabled returns false when config is disabled.
     */
    public function test_is_streaming_enabled_returns_false_when_config_disabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => false]);

        expect($this->component->isStreamingEnabled())->toBeFalse();
    }

    /**
     * Test getStreamingFallbackInterval returns configured value.
     */
    public function test_get_streaming_fallback_interval_returns_config_value(): void
    {
        config(['artisanpack.media.features.streaming_fallback_interval' => 750]);

        expect($this->component->getStreamingFallbackInterval())->toBe(750);
    }

    /**
     * Test getStreamingFallbackInterval returns default when not configured.
     */
    public function test_get_streaming_fallback_interval_returns_default(): void
    {
        config(['artisanpack.media.features.streaming_fallback_interval' => null]);

        // Returns 0 when not configured (cast from null)
        expect($this->component->getStreamingFallbackInterval())->toBe(0);
    }

    /**
     * Test trait initializes currentFileName property.
     */
    public function test_trait_has_current_file_name_property(): void
    {
        expect($this->component->currentFileName)->toBe('');
    }

    /**
     * Test trait initializes currentFileProgress property.
     */
    public function test_trait_has_current_file_progress_property(): void
    {
        expect($this->component->currentFileProgress)->toBe(0);
    }

    /**
     * Test streamProgress updates properties when streaming is disabled.
     */
    public function test_stream_progress_updates_properties_when_streaming_disabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => false]);

        $method = new \ReflectionMethod($this->component, 'streamProgress');
        $method->setAccessible(true);

        $method->invoke(
            $this->component,
            50,
            'test-file.jpg',
            75,
            1,
            2,
            'Processing...'
        );

        expect($this->component->uploadProgress)->toBe(50);
        expect($this->component->currentFileName)->toBe('test-file.jpg');
        expect($this->component->currentFileProgress)->toBe(75);
    }

    /**
     * Test streamProgress calls stream method when streaming is enabled.
     */
    public function test_stream_progress_calls_stream_when_enabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamProgress');
        $method->setAccessible(true);

        $method->invoke(
            $this->component,
            50,
            'test-file.jpg',
            75,
            1,
            2,
            'Processing...'
        );

        expect($this->component->streamCalls)->toHaveCount(1);
        expect($this->component->streamCalls[0]['to'])->toBe('upload-progress');
        expect($this->component->streamCalls[0]['replace'])->toBeTrue();

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['progress'])->toBe(50);
        expect($content['fileName'])->toBe('test-file.jpg');
        expect($content['fileProgress'])->toBe(75);
        expect($content['current'])->toBe(1);
        expect($content['total'])->toBe(2);
        expect($content['status'])->toBe('Processing...');
    }

    /**
     * Test streamComplete does nothing when streaming is disabled.
     */
    public function test_stream_complete_does_nothing_when_disabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => false]);

        $method = new \ReflectionMethod($this->component, 'streamComplete');
        $method->setAccessible(true);

        $method->invoke($this->component, 5, 1, 6);

        expect($this->component->streamCalls)->toHaveCount(0);
    }

    /**
     * Test streamComplete calls stream when enabled.
     */
    public function test_stream_complete_calls_stream_when_enabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamComplete');
        $method->setAccessible(true);

        $method->invoke($this->component, 5, 1, 6);

        expect($this->component->streamCalls)->toHaveCount(1);
        expect($this->component->streamCalls[0]['to'])->toBe('upload-progress');

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['progress'])->toBe(100);
        expect($content['complete'])->toBeTrue();
        expect($content['successCount'])->toBe(5);
        expect($content['errorCount'])->toBe(1);
    }

    /**
     * Test streamError does nothing when streaming is disabled.
     */
    public function test_stream_error_does_nothing_when_disabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => false]);

        $method = new \ReflectionMethod($this->component, 'streamError');
        $method->setAccessible(true);

        $method->invoke($this->component, 'test.jpg', 'Upload failed');

        expect($this->component->streamCalls)->toHaveCount(0);
    }

    /**
     * Test streamError calls stream when enabled.
     */
    public function test_stream_error_calls_stream_when_enabled(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamError');
        $method->setAccessible(true);

        $method->invoke($this->component, 'test.jpg', 'Upload failed');

        expect($this->component->streamCalls)->toHaveCount(1);
        expect($this->component->streamCalls[0]['to'])->toBe('upload-errors');
        expect($this->component->streamCalls[0]['replace'])->toBeFalse();

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['error'])->toBeTrue();
        expect($content['fileName'])->toBe('test.jpg');
        expect($content['message'])->toBe('Upload failed');
    }

    /**
     * Test stream progress generates default status message when none provided.
     */
    public function test_stream_progress_generates_default_status(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamProgress');
        $method->setAccessible(true);

        $method->invoke(
            $this->component,
            50,
            'test-file.jpg',
            75,
            1,
            2,
            null // No status provided
        );

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['status'])->toContain('test-file.jpg');
    }

    /**
     * Test stream complete generates appropriate status message with errors.
     */
    public function test_stream_complete_status_message_with_errors(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamComplete');
        $method->setAccessible(true);

        $method->invoke($this->component, 3, 2, 5);

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['status'])->toContain('3');
        expect($content['status'])->toContain('5');
        expect($content['status'])->toContain('2');
    }

    /**
     * Test stream complete generates appropriate status message without errors.
     */
    public function test_stream_complete_status_message_without_errors(): void
    {
        config(['artisanpack.media.features.streaming_upload' => true]);

        $method = new \ReflectionMethod($this->component, 'streamComplete');
        $method->setAccessible(true);

        $method->invoke($this->component, 5, 0, 5);

        $content = json_decode($this->component->streamCalls[0]['content'], true);
        expect($content['status'])->toContain('5');
    }
}
