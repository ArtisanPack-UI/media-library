<?php

declare( strict_types=1 );

namespace Tests\Unit;

use ArtisanPackUI\MediaLibrary\Helpers\LivewireHelper;
use Tests\TestCase;

/**
 * Livewire Helper Tests
 *
 * Tests for the LivewireHelper class that provides Livewire version
 * detection and feature capability checking.
 *
 * @package Tests\Unit
 *
 * @since   1.1.0
 */
class LivewireHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cached version before each test
        LivewireHelper::clearCache();
    }

    protected function tearDown(): void
    {
        // Ensure cache is cleared after tests
        LivewireHelper::clearCache();

        parent::tearDown();
    }

    /**
     * Test that version returns a valid version string.
     */
    public function test_version_returns_string(): void
    {
        $version = LivewireHelper::version();

        expect( $version )->toBeString();
        expect( $version )->not->toBeEmpty();
    }

    /**
     * Test that version returns the actual installed Livewire version.
     */
    public function test_version_returns_installed_version(): void
    {
        $version = LivewireHelper::version();

        // The version should match a semantic versioning pattern
        expect( $version )->toMatch( '/^\d+\.\d+\.\d+/' );
    }

    /**
     * Test that version result is cached.
     */
    public function test_version_is_cached(): void
    {
        $version1 = LivewireHelper::version();
        $version2 = LivewireHelper::version();

        expect( $version1 )->toBe( $version2 );
    }

    /**
     * Test that majorVersion returns an integer.
     */
    public function test_major_version_returns_integer(): void
    {
        $majorVersion = LivewireHelper::majorVersion();

        expect( $majorVersion )->toBeInt();
        expect( $majorVersion )->toBeGreaterThanOrEqual( 0 );
    }

    /**
     * Test that majorVersion extracts the first segment of the version.
     */
    public function test_major_version_extracts_first_segment(): void
    {
        LivewireHelper::setMockVersion( '4.2.1' );

        expect( LivewireHelper::majorVersion() )->toBe( 4 );
    }

    /**
     * Test isLivewire4 returns true for version 4.x.
     */
    public function test_is_livewire_4_returns_true_for_version_4(): void
    {
        LivewireHelper::setMockVersion( '4.0.0' );

        expect( LivewireHelper::isLivewire4() )->toBeTrue();
    }

    /**
     * Test isLivewire4 returns true for version 5.x (future versions).
     */
    public function test_is_livewire_4_returns_true_for_version_5(): void
    {
        LivewireHelper::setMockVersion( '5.0.0' );

        expect( LivewireHelper::isLivewire4() )->toBeTrue();
    }

    /**
     * Test isLivewire4 returns false for version 3.x.
     */
    public function test_is_livewire_4_returns_false_for_version_3(): void
    {
        LivewireHelper::setMockVersion( '3.7.4' );

        expect( LivewireHelper::isLivewire4() )->toBeFalse();
    }

    /**
     * Test isLivewire3 returns true for version 3.x.
     */
    public function test_is_livewire_3_returns_true_for_version_3(): void
    {
        LivewireHelper::setMockVersion( '3.6.0' );

        expect( LivewireHelper::isLivewire3() )->toBeTrue();
    }

    /**
     * Test isLivewire3 returns false for version 4.x.
     */
    public function test_is_livewire_3_returns_false_for_version_4(): void
    {
        LivewireHelper::setMockVersion( '4.0.0' );

        expect( LivewireHelper::isLivewire3() )->toBeFalse();
    }

    /**
     * Test isLivewire3 returns false for version 2.x.
     */
    public function test_is_livewire_3_returns_false_for_version_2(): void
    {
        LivewireHelper::setMockVersion( '2.12.0' );

        expect( LivewireHelper::isLivewire3() )->toBeFalse();
    }

    /**
     * Test supportsStreaming returns true for Livewire 4.
     */
    public function test_supports_streaming_returns_true_for_livewire_4(): void
    {
        LivewireHelper::setMockVersion( '4.0.0' );

        expect( LivewireHelper::supportsStreaming() )->toBeTrue();
    }

    /**
     * Test supportsStreaming returns false for Livewire 3.
     */
    public function test_supports_streaming_returns_false_for_livewire_3(): void
    {
        LivewireHelper::setMockVersion( '3.7.4' );

        expect( LivewireHelper::supportsStreaming() )->toBeFalse();
    }

    /**
     * Test isAtLeast returns true when version meets requirement.
     */
    public function test_is_at_least_returns_true_when_meets_requirement(): void
    {
        LivewireHelper::setMockVersion( '3.7.4' );

        expect( LivewireHelper::isAtLeast( '3.6.0' ) )->toBeTrue();
        expect( LivewireHelper::isAtLeast( '3.7.0' ) )->toBeTrue();
        expect( LivewireHelper::isAtLeast( '3.7.4' ) )->toBeTrue();
    }

    /**
     * Test isAtLeast returns false when version is below requirement.
     */
    public function test_is_at_least_returns_false_when_below_requirement(): void
    {
        LivewireHelper::setMockVersion( '3.6.0' );

        expect( LivewireHelper::isAtLeast( '3.7.0' ) )->toBeFalse();
        expect( LivewireHelper::isAtLeast( '4.0.0' ) )->toBeFalse();
    }

    /**
     * Test clearCache resets the cached version.
     */
    public function test_clear_cache_resets_cached_version(): void
    {
        // Capture the real installed version first
        LivewireHelper::clearCache();
        $realVersion = LivewireHelper::version();

        // Set a mock version and verify it works
        LivewireHelper::setMockVersion( '99.0.0' );

        expect( LivewireHelper::version() )->toBe( '99.0.0' );

        // Clear the cache
        LivewireHelper::clearCache();

        // After clearing, the actual installed version should be retrieved
        $restoredVersion = LivewireHelper::version();

        expect( $restoredVersion )->toBe( $realVersion );
    }

    /**
     * Test setMockVersion allows setting a custom version.
     */
    public function test_set_mock_version_sets_custom_version(): void
    {
        LivewireHelper::setMockVersion( '99.1.2' );

        expect( LivewireHelper::version() )->toBe( '99.1.2' );
        expect( LivewireHelper::majorVersion() )->toBe( 99 );
    }

    /**
     * Test that the actual installed Livewire is a supported version (3.x or 4.x).
     */
    public function test_installed_livewire_is_supported_version(): void
    {
        // Clear any mocked version
        LivewireHelper::clearCache();

        $majorVersion = LivewireHelper::majorVersion();

        // This test verifies that a supported Livewire version (3 or 4) is installed
        expect( $majorVersion )->toBeGreaterThanOrEqual( 3 );
        expect( $majorVersion )->toBeLessThanOrEqual( 4 );

        // Verify that either isLivewire3() or isLivewire4() returns true
        $isSupported = LivewireHelper::isLivewire3() || LivewireHelper::isLivewire4();

        expect( $isSupported )->toBeTrue();
    }

    /**
     * Test version handles edge cases in version string.
     */
    public function test_major_version_handles_version_with_suffix(): void
    {
        LivewireHelper::setMockVersion( '4.0.0-beta1' );

        expect( LivewireHelper::majorVersion() )->toBe( 4 );
        expect( LivewireHelper::isLivewire4() )->toBeTrue();
    }

    /**
     * Test that version 0.0.0 is handled gracefully.
     */
    public function test_handles_zero_version(): void
    {
        LivewireHelper::setMockVersion( '0.0.0' );

        expect( LivewireHelper::majorVersion() )->toBe( 0 );
        expect( LivewireHelper::isLivewire3() )->toBeFalse();
        expect( LivewireHelper::isLivewire4() )->toBeFalse();
    }
}
