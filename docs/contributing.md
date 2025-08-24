# Contributing Guide

We welcome contributions to the ArtisanPack UI Media Library! This guide will help you get started with contributing to the project.

## Getting Started

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/artisanpack-ui/media-library.git
   cd media-library
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Set up testing environment**
   ```bash
   # Copy testing configuration
   cp .env.testing.example .env.testing
   
   # Create testing database
   touch database/testing.sqlite
   ```

4. **Run tests to ensure everything works**
   ```bash
   composer test
   ```

### Project Structure

Understanding the project structure will help you navigate the codebase:

```
src/
├── Features/
│   └── Media/
│       ├── MediaManager.php          # Main media management logic
│       └── MediaServiceProvider.php  # Feature service provider
├── Http/
│   ├── Controllers/                  # API controllers
│   ├── Requests/                     # Form request validation
│   └── Resources/                    # API response resources
├── Models/
│   ├── Media.php                     # Main media model
│   ├── MediaCategory.php            # Category model
│   └── MediaTag.php                 # Tag model
├── Policies/
│   ├── MediaPolicy.php              # Authorization policies
│   ├── MediaCategoryPolicy.php
│   └── MediaTagPolicy.php
├── MediaLibraryServiceProvider.php   # Main service provider
└── helpers.php                      # Helper functions

tests/
├── Feature/                         # Integration tests
├── Unit/                           # Unit tests
└── TestCase.php                    # Base test case

docs/                               # Documentation
database/migrations/                # Database migrations
config/                            # Configuration files
routes/                            # Route definitions
```

## Code Style Guidelines

This package follows the ArtisanPack UI code style standards:

### PHP Standards

- **Use real tabs for indentation** (not spaces)
- **Use Yoda conditionals**: `if (true === $condition)` instead of `if ($condition === true)`
- **Use single quotes** unless variable escaping is required
- **Follow PSR-12 coding standards**
- **Use type declarations** for method parameters and return types
- **Document all public methods** with proper DocBlocks

### Code Examples

#### Good Code Style

```php
<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Media extends Model
{
	/**
	 * The attributes that are mass assignable.
	 */
	protected array $fillable = [
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
	];

	/**
	 * Get the media categories for this media item.
	 */
	public function mediaCategories(): BelongsToMany
	{
		return $this->belongsToMany(MediaCategory::class, 'media_categories');
	}

	/**
	 * Check if the media item is an image.
	 */
	public function isImage(): bool
	{
		return str_starts_with($this->mime_type, 'image/');
	}

	/**
	 * Get the formatted file size.
	 */
	public function getFormattedFileSizeAttribute(): string
	{
		$bytes = $this->file_size;
		$units = ['B', 'KB', 'MB', 'GB'];
		
		for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
			$bytes /= 1024;
		}
		
		return round($bytes, 2) . ' ' . $units[$i];
	}
}
```

#### Bad Code Style

```php
<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model 
{
    protected $fillable = ["filename", "original_filename"]; // Wrong quotes and spacing
    
    public function mediaCategories() { // Missing return type
        return $this->belongsToMany(MediaCategory::class,"media_categories"); // Missing space
    }
    
    public function isImage() {
        if($this->mime_type === 'image/jpeg') { // Wrong conditional style, missing space
            return true;
        }
        return false;
    }
}
```

### Running Code Style Checks

We use PHP CS Fixer to maintain consistent code style:

```bash
# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style issues
./vendor/bin/php-cs-fixer fix

# Run specific ruleset
./vendor/bin/php-cs-fixer fix --rules=@PSR12
```

## Testing Guidelines

### Writing Tests

All contributions should include appropriate tests. We use both PHPUnit and Pest for testing.

#### Test Structure

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ArtisanPackUI\MediaLibrary\Models\Media;

class MediaFeatureTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * Test that authenticated users can upload media.
	 */
	public function test_authenticated_user_can_upload_media(): void
	{
		// Arrange
		$user = $this->createUser();
		$file = UploadedFile::fake()->image('test.jpg');

		// Act
		$response = $this->actingAs($user)
			->postJson('/api/media/items', [
				'file' => $file,
				'alt_text' => 'Test image',
			]);

		// Assert
		$response->assertStatus(201);
		$this->assertDatabaseHas('media', [
			'alt_text' => 'Test image',
			'user_id' => $user->id,
		]);
	}
}
```

#### Test Requirements

- **Use descriptive test method names** that explain what is being tested
- **Follow the AAA pattern**: Arrange, Act, Assert
- **Test both happy paths and edge cases**
- **Mock external dependencies** when appropriate
- **Use factories for test data** instead of manual creation
- **Clean up after tests** using RefreshDatabase or transactions

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
php artisan test tests/Feature/MediaControllerTest.php

# Run tests with coverage
composer test-coverage

# Run performance tests
php artisan test --filter=Performance
```

## Contribution Workflow

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then clone your fork
git clone https://github.com/your-username/media-library.git
cd media-library

# Add the original repository as upstream
git remote add upstream https://github.com/artisanpack-ui/media-library.git
```

### 2. Create Feature Branch

```bash
# Create a new branch for your feature
git checkout -b feature/your-feature-name

# Or for bug fixes
git checkout -b fix/bug-description
```

### 3. Make Changes

- Write clean, well-documented code
- Follow the code style guidelines
- Add appropriate tests
- Update documentation if needed

### 4. Test Your Changes

```bash
# Run all tests
composer test

# Check code style
./vendor/bin/php-cs-fixer fix --dry-run

# Run static analysis
./vendor/bin/phpstan analyse
```

### 5. Commit Changes

Use clear, descriptive commit messages:

```bash
# Good commit messages
git commit -m "Add support for WebP image uploads"
git commit -m "Fix memory leak in batch file processing"
git commit -m "Improve database query performance for media listing"

# Bad commit messages
git commit -m "Fix bug"
git commit -m "Update code"
git commit -m "Changes"
```

### 6. Update Your Branch

```bash
# Sync with upstream before submitting
git fetch upstream
git rebase upstream/main
```

### 7. Submit Pull Request

1. Push your branch to your fork
2. Create a pull request on GitHub
3. Provide a clear description of your changes
4. Link any related issues

#### Pull Request Template

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Code coverage maintained or improved

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated (if applicable)
- [ ] No breaking changes (or clearly documented)
```

## Types of Contributions

### Bug Reports

When reporting bugs, please include:

1. **Clear description** of the issue
2. **Steps to reproduce** the problem
3. **Expected vs actual behavior**
4. **Environment details** (PHP version, Laravel version, etc.)
5. **Error messages** or logs (if any)

#### Bug Report Template

```markdown
## Bug Description
A clear description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Environment
- PHP Version: 8.2
- Laravel Version: 10.0
- Media Library Version: 1.0.0
- Operating System: Ubuntu 22.04

## Additional Context
Any other context about the problem here.
```

### Feature Requests

For feature requests, please provide:

1. **Problem description** - what problem does this solve?
2. **Proposed solution** - how should it work?
3. **Alternative solutions** - what other approaches did you consider?
4. **Use cases** - when would this be useful?

### Documentation Improvements

Documentation contributions are always welcome:

- Fix typos or unclear explanations
- Add examples for complex features  
- Improve code comments
- Create tutorials or guides

### Security Vulnerabilities

**Do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability, please send an email to security@artisanpack.com. We will address all security vulnerabilities promptly.

## Development Guidelines

### Database Migrations

When creating migrations:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('media', function (Blueprint $table) {
			$table->id();
			$table->string('filename');
			$table->string('original_filename');
			$table->string('file_path');
			$table->string('mime_type');
			$table->unsignedBigInteger('file_size');
			$table->text('alt_text');
			$table->text('caption')->nullable();
			$table->boolean('is_decorative')->default(false);
			$table->json('metadata')->nullable();
			$table->foreignId('user_id')->constrained()->onDelete('cascade');
			$table->timestamps();

			// Add indexes for performance
			$table->index(['user_id', 'created_at']);
			$table->index('mime_type');
			$table->index('file_size');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('media');
	}
};
```

### API Controllers

Follow RESTful conventions:

```php
<?php

namespace ArtisanPackUI\MediaLibrary\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ArtisanPackUI\MediaLibrary\Features\Media\MediaManager;
use ArtisanPackUI\MediaLibrary\Http\Requests\StoreMediaRequest;

class MediaController extends Controller
{
	public function __construct(private MediaManager $mediaManager)
	{
	}

	/**
	 * List media items.
	 */
	public function index(Request $request): JsonResponse
	{
		$media = $this->mediaManager->all(
			perPage: $request->integer('per_page', 15)
		);

		return response()->json($media);
	}

	/**
	 * Store a new media item.
	 */
	public function store(StoreMediaRequest $request): JsonResponse
	{
		$media = $this->mediaManager->upload(
			file: $request->file('file'),
			altText: $request->string('alt_text'),
			caption: $request->string('caption'),
			isDecorative: $request->boolean('is_decorative')
		);

		return response()->json([
			'success' => true,
			'data' => $media
		], 201);
	}
}
```

## Review Process

### Code Review Checklist

When reviewing code, check for:

- [ ] **Functionality** - does it work as intended?
- [ ] **Tests** - are there adequate tests?
- [ ] **Performance** - any performance implications?
- [ ] **Security** - any security concerns?
- [ ] **Code style** - follows project conventions?
- [ ] **Documentation** - is it well documented?
- [ ] **Breaking changes** - any BC breaks?

### Review Timeline

- **Small changes** (bug fixes, documentation): 1-2 days
- **Medium changes** (new features): 3-5 days  
- **Large changes** (major features): 1-2 weeks

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

### Changelog

All notable changes are documented in [CHANGELOG.md](../CHANGELOG.md). When contributing:

- Add entries under "Unreleased" section
- Use format: `- Description (#PR-number)`
- Categorize as Added, Changed, Deprecated, Removed, Fixed, Security

## Getting Help

### Community Resources

- **Documentation**: [docs/](../docs/)
- **Issues**: [GitHub Issues](https://github.com/artisanpack-ui/media-library/issues)
- **Discussions**: [GitHub Discussions](https://github.com/artisanpack-ui/media-library/discussions)

### Contact

- **General questions**: Create a GitHub Discussion
- **Bug reports**: Create a GitHub Issue
- **Security issues**: security@artisanpack.com
- **Maintainer**: [Jacob Martella](https://github.com/jacobmartella)

## License

By contributing to this project, you agree that your contributions will be licensed under the same [MIT License](../LICENSE.md) that covers the project.

Thank you for contributing to the ArtisanPack UI Media Library! 🎉