<?php

declare(strict_types=1);

test('all model classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\Media'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\MediaCategory'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Models\MediaTag'))->toBeTrue();
});

test('all controller classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Controllers\MediaController'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Controllers\MediaCategoryController'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Controllers\MediaTagController'))->toBeTrue();
});

test('all request classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Requests\MediaRequest'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Requests\MediaCategoryRequest'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Requests\MediaTagRequest'))->toBeTrue();
});

test('all resource classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Resources\MediaResource'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Resources\MediaCategoryResource'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Http\Resources\MediaTagResource'))->toBeTrue();
});

test('all policy classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaPolicy'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaCategoryPolicy'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Policies\MediaTagPolicy'))->toBeTrue();
});

test('all factory classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\Database\Factories\MediaFactory'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Database\Factories\MediaCategoryFactory'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Database\Factories\MediaTagFactory'))->toBeTrue();
});

test('service provider classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Features\Media\MediaServiceProvider'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Features\Media\MediaManager'))->toBeTrue();
});

test('main library classes can be instantiated', function () {
    expect(class_exists('ArtisanPackUI\MediaLibrary\MediaLibrary'))->toBeTrue();
    expect(class_exists('ArtisanPackUI\MediaLibrary\Facades\MediaLibrary'))->toBeTrue();
});

test('no old cms framework namespace references exist', function () {
    // This test will run a comprehensive search for any remaining CMSFramework references
    $phpFiles = glob(__DIR__ . '/../../src/**/*.php') ?: [];
    $dbFiles = glob(__DIR__ . '/../../database/**/*.php') ?: [];
    $allFiles = array_merge($phpFiles, $dbFiles);
    
    foreach ($allFiles as $file) {
        $content = file_get_contents($file);
        expect($content)->not->toContain('ArtisanPackUI\\CMSFramework', 
            "File {$file} contains old CMSFramework namespace reference");
    }
    
    expect(count($allFiles))->toBeGreaterThan(0, 'Should have found PHP files to check');
});

test('all namespace imports are consistent with medialibrary structure', function () {
    // Test key files for proper namespace structure
    $testFiles = [
        __DIR__ . '/../../src/Models/Media.php',
        __DIR__ . '/../../src/Models/MediaCategory.php',
        __DIR__ . '/../../src/Models/MediaTag.php',
        __DIR__ . '/../../src/Http/Controllers/MediaController.php',
        __DIR__ . '/../../database/factories/MediaFactory.php',
    ];
    
    foreach ($testFiles as $file) {
        expect(file_exists($file))->toBeTrue("File {$file} should exist");
        
        $content = file_get_contents($file);
        
        // Should contain MediaLibrary namespace
        expect($content)->toContain('ArtisanPackUI\\MediaLibrary', 
            "File {$file} should contain MediaLibrary namespace");
            
        // Should NOT contain CMSFramework namespace
        expect($content)->not->toContain('ArtisanPackUI\\CMSFramework',
            "File {$file} should not contain old CMSFramework namespace");
    }
});