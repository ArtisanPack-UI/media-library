<?php

declare( strict_types=1 );

use ArtisanPackUI\MediaLibrary\Events\MediaUploaded;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\User;
use ArtisanPackUI\MediaLibrary\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/**
 * MediaUploaded event tests.
 *
 * Verifies the queueable Laravel event dispatched after upload so queued
 * listeners (e.g. Keystone's AI alt-text generator) can subscribe.
 *
 * @since 1.5.0
 */

beforeEach( function (): void {
    Storage::fake( 'test-disk' );

    config( [
        'artisanpack.media.disk'               => 'test-disk',
        'artisanpack.media.allowed_mime_types' => [ 'image/jpeg', 'image/png' ],
        'artisanpack.media.max_file_size'      => 10240,
        'artisanpack.media.enable_thumbnails'  => false,
    ] );

    $this->user = User::factory()->create();
    $this->actingAs( $this->user );
} );

it( 'dispatches MediaUploaded after a media record is persisted', function (): void {
    Event::fake( [ MediaUploaded::class ] );

    $service = app( MediaUploadService::class );
    $media   = $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ) );

    Event::assertDispatched( MediaUploaded::class, function ( MediaUploaded $event ) use ( $media ): bool {
        return $event->media->id === $media->id
            && true === $event->media->exists;
    } );
} );

it( 'exposes the persisted media on the event payload', function (): void {
    $captured = null;
    Event::listen( MediaUploaded::class, function ( MediaUploaded $event ) use ( &$captured ): void {
        $captured = $event->media;
    } );

    $service = app( MediaUploadService::class );
    $media   = $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ), [
        'title'    => 'Fixture',
        'alt_text' => 'A fixture image',
    ] );

    expect( $captured )->toBeInstanceOf( Media::class );
    expect( $captured->id )->toBe( $media->id );
    expect( $captured->title )->toBe( 'Fixture' );
    expect( $captured->alt_text )->toBe( 'A fixture image' );
} );

it( 'fires after tags have been attached', function (): void {
    $tag = ArtisanPackUI\MediaLibrary\Models\MediaTag::create( [
        'name' => 'Featured',
        'slug' => 'featured',
    ] );

    $capturedTagIds = null;
    Event::listen( MediaUploaded::class, function ( MediaUploaded $event ) use ( &$capturedTagIds ): void {
        $capturedTagIds = $event->media->tags()->pluck( 'media_tags.id' )->all();
    } );

    $service = app( MediaUploadService::class );
    $service->upload( UploadedFile::fake()->image( 'photo.jpg', 100, 100 ), [
        'tags' => [ $tag->id ],
    ] );

    expect( $capturedTagIds )->toBe( [ $tag->id ] );
} );
