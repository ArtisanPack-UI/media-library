<?php

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up storage fake for file uploads
    Storage::fake('public');
    
    // Create a test user for authentication
    $this->user = User::factory()->create();
});

describe('MediaController - Authenticated Routes', function () {
    
    it('can list all media items with pagination', function () {
        Sanctum::actingAs($this->user);
        
        // Create test media items
        Media::factory()->count(5)->create(['user_id' => $this->user->id]);
        
        $response = $this->getJson('/api/media/items');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'original_filename',
                        'file_path',
                        'mime_type',
                        'file_size',
                        'alt_text',
                        'caption',
                        'is_decorative',
                        'metadata',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    });
    
    it('can create a new media item with file upload', function () {
        Sanctum::actingAs($this->user);
        
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $data = [
            'file' => $file,
            'alt_text' => 'Test image alt text',
            'caption' => 'Test image caption',
            'is_decorative' => false,
            'metadata' => ['test' => 'data']
        ];
        
        $response = $this->postJson('/api/media/items', $data);
        
        $response->assertCreated()
            ->assertJson([
                'message' => 'Media uploaded successfully.'
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'filename',
                    'original_filename',
                    'file_path',
                    'mime_type',
                    'file_size',
                    'alt_text',
                    'caption',
                    'is_decorative',
                    'user_id'
                ]
            ]);
            
        // Assert file was stored
        Storage::disk('public')->assertExists($response->json('data.file_path'));
        
        // Assert database record was created
        $this->assertDatabaseHas('media', [
            'alt_text' => 'Test image alt text',
            'caption' => 'Test image caption',
            'user_id' => $this->user->id
        ]);
    });
    
    it('can create media with categories and tags', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create();
        $tag = MediaTag::factory()->create();
        
        $file = UploadedFile::fake()->image('test.jpg');
        
        $data = [
            'file' => $file,
            'alt_text' => 'Test alt text',
            'media_categories' => [$category->id],
            'media_tags' => [$tag->id]
        ];
        
        $response = $this->postJson('/api/media/items', $data);
        
        $response->assertCreated();
        
        $media = Media::latest()->first();
        expect($media->mediaCategories)->toHaveCount(1);
        expect($media->mediaTags)->toHaveCount(1);
    });
    
    it('can show a specific media item', function () {
        Sanctum::actingAs($this->user);
        
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->getJson("/api/media/items/{$media->id}");
        
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $media->id,
                    'filename' => $media->filename,
                    'alt_text' => $media->alt_text
                ]
            ]);
    });
    
    it('returns 404 when media item not found', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/media/items/999');
        
        $response->assertNotFound()
            ->assertJson([
                'message' => 'Media not found.'
            ]);
    });
    
    it('can update a media item', function () {
        Sanctum::actingAs($this->user);
        
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        
        $updateData = [
            'alt_text' => 'Updated alt text',
            'caption' => 'Updated caption',
            'is_decorative' => true
        ];
        
        $response = $this->putJson("/api/media/items/{$media->id}", $updateData);
        
        $response->assertOk()
            ->assertJson([
                'message' => 'Media updated successfully.'
            ]);
            
        $media->refresh();
        expect($media->alt_text)->toBe('Updated alt text');
        expect($media->caption)->toBe('Updated caption');
        expect($media->is_decorative)->toBeTrue();
    });
    
    it('can update media categories and tags', function () {
        Sanctum::actingAs($this->user);
        
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        $category1 = MediaCategory::factory()->create();
        $category2 = MediaCategory::factory()->create();
        $tag1 = MediaTag::factory()->create();
        $tag2 = MediaTag::factory()->create();
        
        $updateData = [
            'alt_text' => 'Updated alt text',
            'media_categories' => [$category1->id, $category2->id],
            'media_tags' => [$tag1->id, $tag2->id]
        ];
        
        $response = $this->putJson("/api/media/items/{$media->id}", $updateData);
        
        $response->assertOk();
        
        $media->refresh();
        expect($media->mediaCategories)->toHaveCount(2);
        expect($media->mediaTags)->toHaveCount(2);
    });
    
    it('can delete own media item', function () {
        Sanctum::actingAs($this->user);
        
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->deleteJson("/api/media/items/{$media->id}");
        
        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('media', [
            'id' => $media->id
        ]);
    });
    
    it('cannot delete media item owned by another user', function () {
        Sanctum::actingAs($this->user);
        
        $otherUser = User::factory()->create();
        $media = Media::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->deleteJson("/api/media/items/{$media->id}");
        
        $response->assertForbidden()
            ->assertJson([
                'message' => 'Unauthorized to delete this media or media not found.'
            ]);
            
        $this->assertDatabaseHas('media', [
            'id' => $media->id
        ]);
    });
    
    it('requires authentication for protected endpoints', function () {
        $response = $this->getJson('/api/media/items');
        $response->assertUnauthorized();
        
        $response = $this->postJson('/api/media/items', []);
        $response->assertUnauthorized();
        
        $response = $this->putJson('/api/media/items/1', []);
        $response->assertUnauthorized();
        
        $response = $this->deleteJson('/api/media/items/1');
        $response->assertUnauthorized();
    });
    
});

describe('MediaController - Public Routes', function () {
    
    it('can access public media items list without authentication', function () {
        Media::factory()->count(3)->create();
        
        $response = $this->getJson('/media/items');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'original_filename',
                        'file_path',
                        'mime_type',
                        'file_size',
                        'alt_text',
                        'caption'
                    ]
                ]
            ]);
    });
    
    it('can access specific public media item without authentication', function () {
        $media = Media::factory()->create();
        
        $response = $this->getJson("/media/items/{$media->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'filename',
                    'alt_text'
                ]
            ]);
    });
    
});

describe('MediaController - Validation', function () {
    
    it('validates required file for media upload', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/items', [
            'alt_text' => 'Test alt text'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });
    
    it('validates file type for media upload', function () {
        Sanctum::actingAs($this->user);
        
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        
        $response = $this->postJson('/api/media/items', [
            'file' => $file,
            'alt_text' => 'Test alt text'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });
    
    it('validates file size for media upload', function () {
        Sanctum::actingAs($this->user);
        
        $file = UploadedFile::fake()->image('huge.jpg')->size(20000); // 20MB
        
        $response = $this->postJson('/api/media/items', [
            'file' => $file,
            'alt_text' => 'Test alt text'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });
    
    it('validates boolean fields correctly', function () {
        Sanctum::actingAs($this->user);
        
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->postJson('/api/media/items', [
            'file' => $file,
            'alt_text' => 'Test alt text',
            'is_decorative' => 'not-a-boolean'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['is_decorative']);
    });
    
});

describe('MediaController - Error Handling', function () {
    
    it('handles media upload failure gracefully', function () {
        Sanctum::actingAs($this->user);
        
        // Mock storage to fail
        Storage::shouldReceive('disk->putFile')
            ->andReturn(false);
            
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->postJson('/api/media/items', [
            'file' => $file,
            'alt_text' => 'Test alt text'
        ]);
        
        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Media upload failed.'
            ]);
    });
    
    it('handles media update failure gracefully', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson('/api/media/items/999', [
            'alt_text' => 'Updated alt text'
        ]);
        
        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Media update failed or media not found.'
            ]);
    });
    
});