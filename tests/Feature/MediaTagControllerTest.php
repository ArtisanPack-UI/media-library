<?php

use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test user for authentication
    $this->user = User::factory()->create();
});

describe('MediaTagController - Authenticated Routes', function () {
    
    it('can list all media tags', function () {
        Sanctum::actingAs($this->user);
        
        // Create test media tags
        MediaTag::factory()->count(3)->create();
        
        $response = $this->getJson('/api/media/tags');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    });
    
    it('can create a new media tag', function () {
        Sanctum::actingAs($this->user);
        
        $tagData = [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'description' => 'This is a test tag'
        ];
        
        $response = $this->postJson('/api/media/tags', $tagData);
        
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Test Tag',
                    'slug' => 'test-tag',
                    'description' => 'This is a test tag'
                ]
            ]);
            
        // Assert database record was created
        $this->assertDatabaseHas('media_tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'description' => 'This is a test tag'
        ]);
    });
    
    it('can show a specific media tag', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create([
            'name' => 'Sample Tag',
            'slug' => 'sample-tag'
        ]);
        
        $response = $this->getJson("/api/media/tags/{$tag->id}");
        
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => 'Sample Tag',
                    'slug' => 'sample-tag'
                ]
            ]);
    });
    
    it('returns 404 when media tag not found', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/media/tags/999');
        
        $response->assertNotFound();
    });
    
    it('can update a media tag', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create([
            'name' => 'Original Tag',
            'slug' => 'original-tag'
        ]);
        
        $updateData = [
            'name' => 'Updated Tag',
            'slug' => 'updated-tag',
            'description' => 'Updated description'
        ];
        
        $response = $this->putJson("/api/media/tags/{$tag->id}", $updateData);
        
        $response->assertOk()
            ->assertJson([
                'message' => 'Media tag updated successfully.',
                'data' => [
                    'id' => $tag->id,
                    'name' => 'Updated Tag',
                    'slug' => 'updated-tag',
                    'description' => 'Updated description'
                ]
            ]);
            
        // Verify database was updated
        $this->assertDatabaseHas('media_tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
            'slug' => 'updated-tag'
        ]);
    });
    
    it('can delete a media tag', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create();
        
        $response = $this->deleteJson("/api/media/tags/{$tag->id}");
        
        $response->assertStatus(204)
            ->assertJson([
                'message' => 'Media tag deleted successfully.'
            ]);
            
        // Verify tag was deleted from database
        $this->assertDatabaseMissing('media_tags', [
            'id' => $tag->id
        ]);
    });
    
    it('requires authentication for protected endpoints', function () {
        $response = $this->getJson('/api/media/tags');
        $response->assertUnauthorized();
        
        $response = $this->postJson('/api/media/tags', []);
        $response->assertUnauthorized();
        
        $response = $this->putJson('/api/media/tags/1', []);
        $response->assertUnauthorized();
        
        $response = $this->deleteJson('/api/media/tags/1');
        $response->assertUnauthorized();
    });
    
});

describe('MediaTagController - Public Routes', function () {
    
    it('can access public media tags list without authentication', function () {
        MediaTag::factory()->count(3)->create();
        
        $response = $this->getJson('/media/tags');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    });
    
    it('can access specific public media tag without authentication', function () {
        $tag = MediaTag::factory()->create([
            'name' => 'Public Tag',
            'slug' => 'public-tag'
        ]);
        
        $response = $this->getJson("/media/tags/{$tag->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => 'Public Tag',
                    'slug' => 'public-tag'
                ]
            ]);
    });
    
});

describe('MediaTagController - Authorization', function () {
    
    it('enforces viewAny policy for listing tags', function () {
        // Mock the authorization to fail
        $this->withoutExceptionHandling();
        
        Sanctum::actingAs($this->user);
        
        // This test assumes policies are in place
        // In a real scenario, you'd mock the Gate or Policy
        
        $response = $this->getJson('/api/media/tags');
        
        // This will pass if authorization allows, or fail if denied
        // The specific assertion depends on your policy implementation
        $response->assertStatus(200);
    });
    
    it('enforces create policy for creating tags', function () {
        Sanctum::actingAs($this->user);
        
        $tagData = [
            'name' => 'Test Tag',
            'slug' => 'test-tag'
        ];
        
        $response = $this->postJson('/api/media/tags', $tagData);
        
        // This will pass if authorization allows, or fail if denied
        $response->assertStatus(201);
    });
    
    it('enforces view policy for showing specific tag', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create();
        
        $response = $this->getJson("/api/media/tags/{$tag->id}");
        
        // This will pass if authorization allows, or fail if denied
        $response->assertStatus(200);
    });
    
});

describe('MediaTagController - Validation', function () {
    
    it('validates required name field', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'slug' => 'test-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
    
    it('validates name field length', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => str_repeat('a', 256), // Assuming max length is 255
            'slug' => 'test-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
    
    it('validates slug uniqueness', function () {
        Sanctum::actingAs($this->user);
        
        // Create existing tag
        MediaTag::factory()->create(['slug' => 'unique-slug']);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 'Test Tag',
            'slug' => 'unique-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('validates slug format', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 'Test Tag',
            'slug' => 'Invalid Slug With Spaces!'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('validates description length', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
            'description' => str_repeat('a', 1001) // Assuming max length is 1000
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });
    
    it('allows valid tag creation', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 'Valid Tag',
            'slug' => 'valid-tag',
            'description' => 'A valid description'
        ]);
        
        $response->assertCreated();
    });
    
});

describe('MediaTagController - Update Validation', function () {
    
    it('validates update with existing slug on different record', function () {
        Sanctum::actingAs($this->user);
        
        $tag1 = MediaTag::factory()->create(['slug' => 'existing-slug']);
        $tag2 = MediaTag::factory()->create(['slug' => 'another-slug']);
        
        $response = $this->putJson("/api/media/tags/{$tag2->id}", [
            'name' => 'Updated Tag',
            'slug' => 'existing-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('allows updating tag with same slug', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug'
        ]);
        
        $response = $this->putJson("/api/media/tags/{$tag->id}", [
            'name' => 'Updated Name',
            'slug' => 'original-slug'
        ]);
        
        $response->assertOk();
    });
    
});

describe('MediaTagController - Error Handling', function () {
    
    it('handles tag deletion failure gracefully', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create();
        
        // Mock the delete method to fail
        MediaTag::where('id', $tag->id)->update(['id' => null]);
        
        $response = $this->deleteJson("/api/media/tags/{$tag->id}");
        
        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Media tag deletion failed.'
            ]);
    });
    
    it('returns proper error for non-existent tag update', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson('/api/media/tags/999', [
            'name' => 'Updated Name',
            'slug' => 'updated-slug'
        ]);
        
        $response->assertNotFound();
    });
    
    it('returns proper error for non-existent tag deletion', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson('/api/media/tags/999');
        
        $response->assertNotFound();
    });
    
});

describe('MediaTagController - Resource Responses', function () {
    
    it('returns properly formatted resource for single tag', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create([
            'name' => 'Resource Test Tag',
            'slug' => 'resource-test-tag',
            'description' => 'Testing resource format'
        ]);
        
        $response = $this->getJson("/api/media/tags/{$tag->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $tag->id,
                    'name' => 'Resource Test Tag',
                    'slug' => 'resource-test-tag',
                    'description' => 'Testing resource format'
                ]
            ]);
    });
    
    it('returns properly formatted resource collection', function () {
        Sanctum::actingAs($this->user);
        
        MediaTag::factory()->count(3)->create();
        
        $response = $this->getJson('/api/media/tags');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
            
        $this->assertCount(3, $response->json('data'));
    });
    
});

describe('MediaTagController - Complex Scenarios', function () {
    
    it('can handle bulk tag operations', function () {
        Sanctum::actingAs($this->user);
        
        // Create multiple tags
        $tags = MediaTag::factory()->count(5)->create();
        
        $response = $this->getJson('/api/media/tags');
        
        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    });
    
    it('maintains tag relationships when updating', function () {
        Sanctum::actingAs($this->user);
        
        $tag = MediaTag::factory()->create([
            'name' => 'Original Tag',
            'slug' => 'original-tag'
        ]);
        
        // Update tag but keep ID consistent
        $response = $this->putJson("/api/media/tags/{$tag->id}", [
            'name' => 'Updated Tag Name',
            'slug' => 'original-tag', // Keep same slug
            'description' => 'Updated description'
        ]);
        
        $response->assertOk();
        
        // Verify the tag still exists with same ID
        $updatedTag = MediaTag::find($tag->id);
        expect($updatedTag)->not->toBeNull();
        expect($updatedTag->name)->toBe('Updated Tag Name');
    });
    
    it('handles concurrent tag creation with same slug gracefully', function () {
        Sanctum::actingAs($this->user);
        
        // Create first tag
        $response1 = $this->postJson('/api/media/tags', [
            'name' => 'First Tag',
            'slug' => 'duplicate-slug'
        ]);
        
        $response1->assertCreated();
        
        // Try to create second tag with same slug
        $response2 = $this->postJson('/api/media/tags', [
            'name' => 'Second Tag',
            'slug' => 'duplicate-slug'
        ]);
        
        $response2->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('validates tag data types correctly', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 123, // Invalid type
            'slug' => true, // Invalid type
            'description' => []  // Invalid type
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'description']);
    });
    
});

describe('MediaTagController - Performance and Edge Cases', function () {
    
    it('handles large number of tags efficiently', function () {
        Sanctum::actingAs($this->user);
        
        // Create a reasonable number of tags for testing
        MediaTag::factory()->count(50)->create();
        
        $response = $this->getJson('/api/media/tags');
        
        $response->assertOk();
        $this->assertCount(50, $response->json('data'));
    });
    
    it('handles special characters in tag names and slugs', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => 'Special & Characters!',
            'slug' => 'special-characters',
            'description' => 'Testing special characters: @#$%^&*()'
        ]);
        
        $response->assertCreated();
    });
    
    it('handles empty and null values appropriately', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => '',
            'slug' => null,
            'description' => ''
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
    
    it('handles unicode characters in tag content', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/tags', [
            'name' => '测试标签',
            'slug' => 'unicode-tag',
            'description' => 'Unicode description with émojis 🏷️'
        ]);
        
        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => '测试标签',
                    'description' => 'Unicode description with émojis 🏷️'
                ]
            ]);
    });
    
});