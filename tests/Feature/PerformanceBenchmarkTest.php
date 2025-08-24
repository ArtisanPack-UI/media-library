<?php

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    
    // Create test user manually without factory
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
});

describe('Performance Benchmarks - Query Performance', function () {
    
    it('measures database query performance for media listing', function () {
        Sanctum::actingAs($this->user);
        
        // Create test data manually
        $mediaItems = [];
        for ($i = 0; $i < 100; $i++) {
            $mediaItems[] = [
                'filename' => "test-file-{$i}.jpg",
                'original_filename' => "original-{$i}.jpg",
                'file_path' => "media/2024/08/test-file-{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1024 * ($i + 1),
                'alt_text' => "Alt text for image {$i}",
                'caption' => "Caption for image {$i}",
                'is_decorative' => false,
                'metadata' => json_encode(['width' => 800, 'height' => 600]),
                'user_id' => $this->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('media')->insert($mediaItems);
        
        // Measure query performance
        $startTime = microtime(true);
        DB::enableQueryLog();
        
        $response = $this->getJson('/api/media/items?per_page=50');
        
        $endTime = microtime(true);
        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertOk();
        
        // Performance assertions
        expect($executionTime)->toBeLessThan(500); // Should complete in less than 500ms
        expect(count($queryLog))->toBeLessThan(5); // Should not have excessive queries (N+1 problem)
        
        // Log performance metrics
        echo "\n--- Media Listing Performance ---\n";
        echo "Execution time: {$executionTime}ms\n";
        echo "Queries executed: " . count($queryLog) . "\n";
        echo "Memory usage: " . memory_get_peak_usage(true) / 1024 / 1024 . "MB\n";
    });
    
    it('measures query performance with relationships', function () {
        Sanctum::actingAs($this->user);
        
        // Create categories and tags manually
        $categoryIds = [];
        $tagIds = [];
        
        for ($i = 0; $i < 10; $i++) {
            $categoryId = DB::table('media_categories')->insertGetId([
                'name' => "Category {$i}",
                'slug' => "category-{$i}",
                'description' => "Description for category {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $categoryIds[] = $categoryId;
            
            $tagId = DB::table('media_tags')->insertGetId([
                'name' => "Tag {$i}",
                'slug' => "tag-{$i}",
                'description' => "Description for tag {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $tagIds[] = $tagId;
        }
        
        // Create media items with relationships
        for ($i = 0; $i < 50; $i++) {
            $mediaId = DB::table('media')->insertGetId([
                'filename' => "test-file-{$i}.jpg",
                'original_filename' => "original-{$i}.jpg",
                'file_path' => "media/2024/08/test-file-{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1024 * ($i + 1),
                'alt_text' => "Alt text for image {$i}",
                'user_id' => $this->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Add relationships
            DB::table('media_media_category')->insert([
                'media_id' => $mediaId,
                'media_category_id' => $categoryIds[$i % count($categoryIds)],
            ]);
            
            DB::table('media_media_tag')->insert([
                'media_id' => $mediaId,
                'media_tag_id' => $tagIds[$i % count($tagIds)],
            ]);
        }
        
        // Test query performance with eager loading
        DB::enableQueryLog();
        $startTime = microtime(true);
        
        $media = Media::with(['mediaCategories', 'mediaTags'])->take(20)->get();
        
        $endTime = microtime(true);
        $queryLog = DB::getQueryLog();
        DB::disableQueryLog();
        
        $executionTime = ($endTime - $startTime) * 1000;
        
        // Performance assertions
        expect($executionTime)->toBeLessThan(100); // Should complete quickly with eager loading
        expect(count($queryLog))->toBeLessThan(4); // Should use eager loading efficiently (1 main + 2 relationship queries)
        expect($media)->toHaveCount(20);
        
        echo "\n--- Relationship Query Performance ---\n";
        echo "Execution time: {$executionTime}ms\n";
        echo "Queries executed: " . count($queryLog) . "\n";
        foreach ($queryLog as $query) {
            echo "Query time: {$query['time']}ms\n";
        }
    });
    
});

describe('Performance Benchmarks - Memory Usage', function () {
    
    it('measures memory usage during bulk operations', function () {
        Sanctum::actingAs($this->user);
        
        $initialMemory = memory_get_usage();
        
        // Create large dataset
        $mediaData = [];
        for ($i = 0; $i < 1000; $i++) {
            $mediaData[] = [
                'filename' => "bulk-file-{$i}.jpg",
                'original_filename' => "original-bulk-{$i}.jpg",
                'file_path' => "media/2024/08/bulk-file-{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1024 * 100,
                'alt_text' => "Bulk alt text {$i}",
                'user_id' => $this->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        $beforeInsertMemory = memory_get_usage();
        
        // Bulk insert
        DB::table('media')->insert($mediaData);
        
        $afterInsertMemory = memory_get_usage();
        
        // Query all records
        $allMedia = DB::table('media')->get();
        
        $finalMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        // Memory usage assertions
        $insertMemoryDiff = ($afterInsertMemory - $beforeInsertMemory) / 1024 / 1024; // MB
        $totalMemoryDiff = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        $peakMemoryMB = $peakMemory / 1024 / 1024; // MB
        
        expect($insertMemoryDiff)->toBeLessThan(50); // Should not use excessive memory for bulk insert
        expect($totalMemoryDiff)->toBeLessThan(100); // Total memory usage should be reasonable
        expect($peakMemoryMB)->toBeLessThan(256); // Peak memory should not exceed 256MB
        
        echo "\n--- Memory Usage Performance ---\n";
        echo "Initial memory: " . ($initialMemory / 1024 / 1024) . "MB\n";
        echo "Insert memory diff: {$insertMemoryDiff}MB\n";
        echo "Total memory diff: {$totalMemoryDiff}MB\n";
        echo "Peak memory: {$peakMemoryMB}MB\n";
        echo "Records processed: " . count($allMedia) . "\n";
    });
    
});

describe('Performance Benchmarks - File Operations', function () {
    
    it('measures file upload performance', function () {
        Sanctum::actingAs($this->user);
        
        $uploadTimes = [];
        
        // Test multiple file uploads
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->image("test-upload-{$i}.jpg", 800, 600)->size(1000); // 1MB
            
            $startTime = microtime(true);
            
            $response = $this->postJson('/api/media/items', [
                'file' => $file,
                'alt_text' => "Performance test image {$i}",
                'caption' => "Caption for performance test {$i}",
                'is_decorative' => false,
            ]);
            
            $endTime = microtime(true);
            $uploadTime = ($endTime - $startTime) * 1000; // milliseconds
            $uploadTimes[] = $uploadTime;
            
            $response->assertCreated();
        }
        
        $averageUploadTime = array_sum($uploadTimes) / count($uploadTimes);
        $maxUploadTime = max($uploadTimes);
        $minUploadTime = min($uploadTimes);
        
        // Performance assertions
        expect($averageUploadTime)->toBeLessThan(2000); // Average should be under 2 seconds
        expect($maxUploadTime)->toBeLessThan(5000); // Max should be under 5 seconds
        
        echo "\n--- File Upload Performance ---\n";
        echo "Average upload time: {$averageUploadTime}ms\n";
        echo "Min upload time: {$minUploadTime}ms\n";
        echo "Max upload time: {$maxUploadTime}ms\n";
        echo "Files uploaded: " . count($uploadTimes) . "\n";
    });
    
    it('measures file retrieval performance', function () {
        Sanctum::actingAs($this->user);
        
        // Create test files
        $mediaIds = [];
        for ($i = 0; $i < 20; $i++) {
            $mediaId = DB::table('media')->insertGetId([
                'filename' => "retrieval-test-{$i}.jpg",
                'original_filename' => "original-retrieval-{$i}.jpg",
                'file_path' => "media/2024/08/retrieval-test-{$i}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 1024 * 500, // 500KB
                'alt_text' => "Retrieval test {$i}",
                'user_id' => $this->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mediaIds[] = $mediaId;
        }
        
        $retrievalTimes = [];
        
        // Test individual file retrieval
        foreach ($mediaIds as $mediaId) {
            $startTime = microtime(true);
            
            $response = $this->getJson("/api/media/items/{$mediaId}");
            
            $endTime = microtime(true);
            $retrievalTime = ($endTime - $startTime) * 1000; // milliseconds
            $retrievalTimes[] = $retrievalTime;
            
            $response->assertOk();
        }
        
        $averageRetrievalTime = array_sum($retrievalTimes) / count($retrievalTimes);
        $maxRetrievalTime = max($retrievalTimes);
        
        // Performance assertions
        expect($averageRetrievalTime)->toBeLessThan(50); // Should be very fast for individual retrieval
        expect($maxRetrievalTime)->toBeLessThan(200); // Max should still be reasonable
        
        echo "\n--- File Retrieval Performance ---\n";
        echo "Average retrieval time: {$averageRetrievalTime}ms\n";
        echo "Max retrieval time: {$maxRetrievalTime}ms\n";
        echo "Files retrieved: " . count($retrievalTimes) . "\n";
    });
    
});

describe('Performance Benchmarks - Pagination Performance', function () {
    
    it('measures pagination performance with large datasets', function () {
        Sanctum::actingAs($this->user);
        
        // Create large dataset in batches to avoid memory issues
        $batchSize = 500;
        for ($batch = 0; $batch < 4; $batch++) {
            $mediaData = [];
            for ($i = 0; $i < $batchSize; $i++) {
                $index = ($batch * $batchSize) + $i;
                $mediaData[] = [
                    'filename' => "pagination-test-{$index}.jpg",
                    'original_filename' => "original-pagination-{$index}.jpg",
                    'file_path' => "media/2024/08/pagination-test-{$index}.jpg",
                    'mime_type' => 'image/jpeg',
                    'file_size' => 1024 * 200,
                    'alt_text' => "Pagination test {$index}",
                    'user_id' => $this->user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('media')->insert($mediaData);
        }
        
        // Test pagination performance at different pages
        $pageTests = [1, 10, 20, 40]; // Test different page positions
        $paginationTimes = [];
        
        foreach ($pageTests as $page) {
            DB::enableQueryLog();
            $startTime = microtime(true);
            
            $response = $this->getJson("/api/media/items?page={$page}&per_page=50");
            
            $endTime = microtime(true);
            $queryLog = DB::getQueryLog();
            DB::disableQueryLog();
            
            $paginationTime = ($endTime - $startTime) * 1000;
            $paginationTimes[$page] = $paginationTime;
            
            $response->assertOk();
            
            // Should have consistent query performance regardless of page
            expect($paginationTime)->toBeLessThan(500);
            expect(count($queryLog))->toBeLessThan(3); // Should be efficient queries
        }
        
        echo "\n--- Pagination Performance ---\n";
        foreach ($paginationTimes as $page => $time) {
            echo "Page {$page}: {$time}ms\n";
        }
        
        // Check that later pages don't take significantly longer
        $firstPageTime = $paginationTimes[1];
        $lastPageTime = $paginationTimes[40];
        $performanceDegradation = ($lastPageTime - $firstPageTime) / $firstPageTime;
        
        expect($performanceDegradation)->toBeLessThan(2.0); // Should not be more than 200% slower
    });
    
});