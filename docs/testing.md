# Testing Guide

This guide covers testing strategies, running tests, and testing best practices for the ArtisanPack UI Media Library package.

## Running Tests

The package includes comprehensive tests to ensure reliability and performance.

### Basic Test Execution

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test suites
php artisan test --filter=MediaController
php artisan test --filter=PerformanceBenchmarks

# Run tests with verbose output
php artisan test --verbose

# Run parallel tests (if configured)
php artisan test --parallel
```

### PHPUnit Commands

```bash
# Run PHPUnit directly
./vendor/bin/phpunit

# Run with specific configuration
./vendor/bin/phpunit --configuration phpunit.xml

# Run specific test file
./vendor/bin/phpunit tests/Feature/MediaControllerTest.php

# Run with coverage report
./vendor/bin/phpunit --coverage-html coverage-report
```

### Pest Commands

```bash
# Run Pest tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test file
./vendor/bin/pest tests/Feature/MediaControllerTest.php

# Run with parallel execution
./vendor/bin/pest --parallel
```

## Test Categories

The package includes several types of tests to ensure comprehensive coverage:

### Feature Tests

Full HTTP request/response testing that simulates real user interactions:

**MediaController Tests**
- Upload functionality
- Media listing and pagination  
- Media update and deletion
- Authentication and authorization
- File validation and error handling

**API Tests**
- RESTful endpoint functionality
- JSON response validation
- Error response handling
- Rate limiting behavior

**Category and Tag Tests**
- CRUD operations for categories
- CRUD operations for tags  
- Relationship management
- Bulk operations

### Unit Tests

Individual component testing for isolated functionality:

**Model Tests**
- Media model relationships
- MediaCategory model behavior
- MediaTag model behavior
- Model validation rules

**Service Tests**
- MediaManager functionality
- File handling services
- Validation services
- Policy enforcement

**Helper Tests**
- Utility functions
- File processing helpers
- Validation helpers

### Integration Tests

Service provider and package integration testing:

**Service Provider Tests**
- Package registration
- Configuration loading
- Route registration
- Migration publishing

**Database Tests**
- Migration execution
- Foreign key constraints
- Index creation
- Data integrity

### Performance Tests

Database and file operation benchmarks:

**Query Performance**
- Media listing performance
- Relationship loading efficiency
- Search functionality speed
- Pagination performance

**File Operations**
- Upload performance benchmarks
- File deletion speed
- Storage efficiency tests
- Memory usage monitoring

## Setting Up Testing Environment

### Database Configuration

Configure a separate testing database in `phpunit.xml`:

```xml
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_DRIVER" value="sync"/>
        <env name="MAIL_DRIVER" value="array"/>
    </php>
</phpunit>
```

### Storage Configuration

Configure testing storage in `.env.testing`:

```env
# Testing Environment Variables
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Media Library Testing Configuration
MEDIA_DISK=testing
MEDIA_DIRECTORY=test-media
MEDIA_MAX_FILE_SIZE=5242880

# Disable external services
SANCTUM_STATEFUL_DOMAINS=localhost
```

### Test Storage Disk

Add a testing disk in `config/filesystems.php`:

```php
'disks' => [
    'testing' => [
        'driver' => 'local',
        'root' => storage_path('app/testing'),
        'url' => env('APP_URL').'/storage/testing',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

## Writing Tests

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ArtisanPackUI\MediaLibrary\Models\Media;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_media()
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('test.jpg', 600, 400);

        $response = $this->actingAs($user)
            ->postJson('/api/media/items', [
                'file' => $file,
                'alt_text' => 'Test image',
                'caption' => 'Test caption',
                'is_decorative' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
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
                ]
            ]);

        $this->assertDatabaseHas('media', [
            'alt_text' => 'Test image',
            'caption' => 'Test caption',
            'user_id' => $user->id,
        ]);
    }

    public function test_upload_validates_required_fields()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/api/media/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file', 'alt_text']);
    }

    public function test_upload_validates_file_size()
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB

        $response = $this->actingAs($user)
            ->postJson('/api/media/items', [
                'file' => $file,
                'alt_text' => 'Test file',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
```

### Unit Test Example

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_belongs_to_user()
    {
        $user = $this->createUser();
        $media = Media::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $media->user->id);
    }

    public function test_media_can_have_categories()
    {
        $media = Media::factory()->create();
        $categories = MediaCategory::factory()->count(2)->create();

        $media->mediaCategories()->attach($categories->pluck('id'));

        $this->assertCount(2, $media->mediaCategories);
    }

    public function test_media_file_url_accessor()
    {
        $media = Media::factory()->create([
            'file_path' => 'media/2024/08/test.jpg'
        ]);

        $expectedUrl = config('app.url') . '/storage/media/2024/08/test.jpg';
        $this->assertEquals($expectedUrl, $media->url);
    }

    public function test_media_formatted_file_size()
    {
        $media = Media::factory()->create(['file_size' => 1048576]); // 1MB

        $this->assertEquals('1.00 MB', $media->formatted_file_size);
    }
}
```

### Performance Test Example

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ArtisanPackUI\MediaLibrary\Models\Media;

class MediaPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_listing_performance()
    {
        // Create test data
        Media::factory()->count(1000)->create();

        $startTime = microtime(true);
        
        $response = $this->getJson('/api/media/items?per_page=50');
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 500ms
        $this->assertLessThan(500, $executionTime, 
            "Media listing took {$executionTime}ms, expected under 500ms"
        );
    }

    public function test_media_with_relationships_performance()
    {
        $media = Media::factory()->count(100)->create();
        
        // Attach categories and tags
        foreach ($media as $item) {
            $item->mediaCategories()->attach([1, 2]);
            $item->mediaTags()->attach([1, 2, 3]);
        }

        $startTime = microtime(true);
        
        $results = Media::with(['mediaCategories', 'mediaTags'])->get();
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertCount(100, $results);
        $this->assertLessThan(100, $executionTime,
            "Eager loading took {$executionTime}ms, expected under 100ms"
        );
    }
}
```

## Test Helpers and Utilities

### Custom Test Traits

```php
<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaCategory;

trait MediaTestHelpers
{
    protected function createTestMedia($attributes = [])
    {
        return Media::factory()->create($attributes);
    }

    protected function createTestCategory($attributes = [])
    {
        return MediaCategory::factory()->create($attributes);
    }

    protected function createTestFile($name = 'test.jpg', $width = 600, $height = 400)
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    protected function createTestDocument($name = 'test.pdf', $size = 1000)
    {
        return UploadedFile::fake()->create($name, $size);
    }

    protected function assertFileExists($filePath)
    {
        $this->assertTrue(
            \Storage::disk('testing')->exists($filePath),
            "File {$filePath} does not exist in testing storage"
        );
    }

    protected function assertFileNotExists($filePath)
    {
        $this->assertFalse(
            \Storage::disk('testing')->exists($filePath),
            "File {$filePath} still exists in testing storage"
        );
    }
}
```

### Factory Definitions

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ArtisanPackUI\MediaLibrary\Models\Media;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition()
    {
        return [
            'filename' => 'image-' . $this->faker->uuid . '.jpg',
            'original_filename' => $this->faker->words(2, true) . '.jpg',
            'file_path' => 'media/' . date('Y/m') . '/' . $this->faker->uuid . '.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'alt_text' => $this->faker->sentence(),
            'caption' => $this->faker->optional()->paragraph(),
            'is_decorative' => $this->faker->boolean(20),
            'metadata' => [
                'width' => $this->faker->numberBetween(400, 2000),
                'height' => $this->faker->numberBetween(300, 1500),
            ],
            'user_id' => 1,
        ];
    }

    public function image()
    {
        return $this->state(function (array $attributes) {
            return [
                'mime_type' => $this->faker->randomElement([
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp'
                ]),
            ];
        });
    }

    public function document()
    {
        return $this->state(function (array $attributes) {
            return [
                'mime_type' => 'application/pdf',
                'filename' => 'document-' . $this->faker->uuid . '.pdf',
                'original_filename' => $this->faker->words(2, true) . '.pdf',
            ];
        });
    }
}
```

## Continuous Integration

### GitHub Actions Configuration

```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-versions: ['8.2', '8.3']
        laravel-versions: ['10.0', '11.0']
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-versions }}
        extensions: mbstring, dom, fileinfo, mysql
        coverage: xdebug
    
    - name: Install Dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run Tests
      run: composer test
    
    - name: Upload Coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### Local Testing Scripts

```bash
#!/bin/bash
# scripts/test.sh

echo "Running Media Library Tests..."

# Run syntax check
echo "Checking syntax..."
find src/ -name "*.php" -exec php -l {} \;

# Run tests
echo "Running PHPUnit tests..."
./vendor/bin/phpunit --coverage-text

# Run static analysis
echo "Running static analysis..."
./vendor/bin/phpstan analyse

# Run code style check
echo "Checking code style..."
./vendor/bin/php-cs-fixer fix --dry-run --diff

echo "All tests completed!"
```

## Testing Best Practices

### 1. Test Organization

- Group related tests in the same file
- Use descriptive test method names
- Follow AAA pattern: Arrange, Act, Assert

### 2. Data Management

- Use factories for consistent test data
- Clean up after tests with RefreshDatabase
- Use transactions for faster test execution

### 3. Mock External Dependencies

```php
public function test_upload_handles_storage_failure()
{
    Storage::shouldReceive('putFile')
        ->once()
        ->andReturn(false);

    $response = $this->postJson('/api/media/items', [
        'file' => UploadedFile::fake()->image('test.jpg'),
        'alt_text' => 'Test image',
    ]);

    $response->assertStatus(500);
}
```

### 4. Test Edge Cases

- Test validation failures
- Test permission denials
- Test file system errors
- Test large file uploads
- Test concurrent operations

### 5. Performance Testing

- Set acceptable performance thresholds
- Test with realistic data volumes
- Monitor memory usage
- Test database query efficiency

## Troubleshooting Tests

### Common Test Issues

**Permission Denied Errors**
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

**Database Connection Issues**
```bash
# Check SQLite installation
php -m | grep sqlite

# Create testing database
touch database/testing.sqlite
```

**Memory Limit Issues**
```ini
; In php.ini or phpunit.xml
memory_limit = 256M
```

**File Upload Issues**
```ini
; In php.ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
```

## Next Steps

- Review [performance guidelines](performance.md) for optimization
- Check [API documentation](api.md) for endpoint testing
- See [usage examples](usage.md) for implementation patterns
- Explore [configuration options](configuration.md) for test setup