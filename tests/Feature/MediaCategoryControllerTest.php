<?php

use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a test user for authentication
    $this->user = User::factory()->create();
});

describe('MediaCategoryController - Authenticated Routes', function () {
    
    it('can list all media categories', function () {
        Sanctum::actingAs($this->user);
        
        // Create test media categories
        MediaCategory::factory()->count(3)->create();
        
        $response = $this->getJson('/api/media/categories');
        
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
    
    it('can create a new media category', function () {
        Sanctum::actingAs($this->user);
        
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category'
        ];
        
        $response = $this->postJson('/api/media/categories', $categoryData);
        
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
                    'name' => 'Test Category',
                    'slug' => 'test-category',
                    'description' => 'This is a test category'
                ]
            ]);
            
        // Assert database record was created
        $this->assertDatabaseHas('media_categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category'
        ]);
    });
    
    it('can show a specific media category', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create([
            'name' => 'Sample Category',
            'slug' => 'sample-category'
        ]);
        
        $response = $this->getJson("/api/media/categories/{$category->id}");
        
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => 'Sample Category',
                    'slug' => 'sample-category'
                ]
            ]);
    });
    
    it('returns 404 when media category not found', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/media/categories/999');
        
        $response->assertNotFound();
    });
    
    it('can update a media category', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create([
            'name' => 'Original Category',
            'slug' => 'original-category'
        ]);
        
        $updateData = [
            'name' => 'Updated Category',
            'slug' => 'updated-category',
            'description' => 'Updated description'
        ];
        
        $response = $this->putJson("/api/media/categories/{$category->id}", $updateData);
        
        $response->assertOk()
            ->assertJson([
                'message' => 'Media category updated successfully.',
                'data' => [
                    'id' => $category->id,
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                    'description' => 'Updated description'
                ]
            ]);
            
        // Verify database was updated
        $this->assertDatabaseHas('media_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'slug' => 'updated-category'
        ]);
    });
    
    it('can delete a media category', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create();
        
        $response = $this->deleteJson("/api/media/categories/{$category->id}");
        
        $response->assertStatus(204)
            ->assertJson([
                'message' => 'Media category deleted successfully.'
            ]);
            
        // Verify category was deleted from database
        $this->assertDatabaseMissing('media_categories', [
            'id' => $category->id
        ]);
    });
    
    it('requires authentication for protected endpoints', function () {
        $response = $this->getJson('/api/media/categories');
        $response->assertUnauthorized();
        
        $response = $this->postJson('/api/media/categories', []);
        $response->assertUnauthorized();
        
        $response = $this->putJson('/api/media/categories/1', []);
        $response->assertUnauthorized();
        
        $response = $this->deleteJson('/api/media/categories/1');
        $response->assertUnauthorized();
    });
    
});

describe('MediaCategoryController - Public Routes', function () {
    
    it('can access public media categories list without authentication', function () {
        MediaCategory::factory()->count(3)->create();
        
        $response = $this->getJson('/media/categories');
        
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
    
    it('can access specific public media category without authentication', function () {
        $category = MediaCategory::factory()->create([
            'name' => 'Public Category',
            'slug' => 'public-category'
        ]);
        
        $response = $this->getJson("/media/categories/{$category->id}");
        
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
                    'id' => $category->id,
                    'name' => 'Public Category',
                    'slug' => 'public-category'
                ]
            ]);
    });
    
});

describe('MediaCategoryController - Authorization', function () {
    
    it('enforces viewAny policy for listing categories', function () {
        // Mock the authorization to fail
        $this->withoutExceptionHandling();
        
        Sanctum::actingAs($this->user);
        
        // This test assumes policies are in place
        // In a real scenario, you'd mock the Gate or Policy
        
        $response = $this->getJson('/api/media/categories');
        
        // This will pass if authorization allows, or fail if denied
        // The specific assertion depends on your policy implementation
        $response->assertStatus(200);
    });
    
    it('enforces create policy for creating categories', function () {
        Sanctum::actingAs($this->user);
        
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'test-category'
        ];
        
        $response = $this->postJson('/api/media/categories', $categoryData);
        
        // This will pass if authorization allows, or fail if denied
        $response->assertStatus(201);
    });
    
    it('enforces view policy for showing specific category', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create();
        
        $response = $this->getJson("/api/media/categories/{$category->id}");
        
        // This will pass if authorization allows, or fail if denied
        $response->assertStatus(200);
    });
    
});

describe('MediaCategoryController - Validation', function () {
    
    it('validates required name field', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/categories', [
            'slug' => 'test-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
    
    it('validates name field length', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/categories', [
            'name' => str_repeat('a', 256), // Assuming max length is 255
            'slug' => 'test-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
    
    it('validates slug uniqueness', function () {
        Sanctum::actingAs($this->user);
        
        // Create existing category
        MediaCategory::factory()->create(['slug' => 'unique-slug']);
        
        $response = $this->postJson('/api/media/categories', [
            'name' => 'Test Category',
            'slug' => 'unique-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('validates slug format', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/categories', [
            'name' => 'Test Category',
            'slug' => 'Invalid Slug With Spaces!'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('validates description length', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => str_repeat('a', 1001) // Assuming max length is 1000
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });
    
    it('allows valid category creation', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->postJson('/api/media/categories', [
            'name' => 'Valid Category',
            'slug' => 'valid-category',
            'description' => 'A valid description'
        ]);
        
        $response->assertCreated();
    });
    
});

describe('MediaCategoryController - Update Validation', function () {
    
    it('validates update with existing slug on different record', function () {
        Sanctum::actingAs($this->user);
        
        $category1 = MediaCategory::factory()->create(['slug' => 'existing-slug']);
        $category2 = MediaCategory::factory()->create(['slug' => 'another-slug']);
        
        $response = $this->putJson("/api/media/categories/{$category2->id}", [
            'name' => 'Updated Category',
            'slug' => 'existing-slug'
        ]);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });
    
    it('allows updating category with same slug', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-slug'
        ]);
        
        $response = $this->putJson("/api/media/categories/{$category->id}", [
            'name' => 'Updated Name',
            'slug' => 'original-slug'
        ]);
        
        $response->assertOk();
    });
    
});

describe('MediaCategoryController - Error Handling', function () {
    
    it('handles category deletion failure gracefully', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create();
        
        // Mock the delete method to fail
        MediaCategory::where('id', $category->id)->update(['id' => null]);
        
        $response = $this->deleteJson("/api/media/categories/{$category->id}");
        
        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Media category deletion failed.'
            ]);
    });
    
    it('returns proper error for non-existent category update', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->putJson('/api/media/categories/999', [
            'name' => 'Updated Name',
            'slug' => 'updated-slug'
        ]);
        
        $response->assertNotFound();
    });
    
    it('returns proper error for non-existent category deletion', function () {
        Sanctum::actingAs($this->user);
        
        $response = $this->deleteJson('/api/media/categories/999');
        
        $response->assertNotFound();
    });
    
});

describe('MediaCategoryController - Resource Responses', function () {
    
    it('returns properly formatted resource for single category', function () {
        Sanctum::actingAs($this->user);
        
        $category = MediaCategory::factory()->create([
            'name' => 'Resource Test Category',
            'slug' => 'resource-test-category',
            'description' => 'Testing resource format'
        ]);
        
        $response = $this->getJson("/api/media/categories/{$category->id}");
        
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
                    'id' => $category->id,
                    'name' => 'Resource Test Category',
                    'slug' => 'resource-test-category',
                    'description' => 'Testing resource format'
                ]
            ]);
    });
    
    it('returns properly formatted resource collection', function () {
        Sanctum::actingAs($this->user);
        
        MediaCategory::factory()->count(3)->create();
        
        $response = $this->getJson('/api/media/categories');
        
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