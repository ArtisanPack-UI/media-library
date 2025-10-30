<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\MediaFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * MediaFolder model representing hierarchical media folders.
 *
 * @since 1.0.0
 */
class MediaFolder extends Model
{
	use HasFactory;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'media_folders';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<string>
	 */
	protected $fillable = [
		'name',
		'slug',
		'description',
		'parent_id',
		'created_by',
	];

	/**
	 * Create a new factory instance for the model.
	 */
	protected static function newFactory(): MediaFolderFactory
	{
		return MediaFolderFactory::new();
	}

	/**
	 * Parent folder relationship.
	 */
	public function parent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'parent_id');
	}

	/**
	 * Children folders relationship.
	 */
	public function children(): HasMany
	{
		return $this->hasMany(self::class, 'parent_id');
	}

	/**
	 * Recursive children relationship.
	 */
	public function childrenRecursive(): HasMany
	{
		return $this->children()->with('childrenRecursive');
	}

	/**
	 * Media items in this folder.
	 */
	public function media(): HasMany
	{
		return $this->hasMany(Media::class, 'folder_id');
	}

	/**
	 * Creator relationship.
	 */
	public function creator(): BelongsTo
	{
		$userModel = config('artisanpack.media.user_model')
			?: config('artisanpack.cms-framework.user_model')
			?: 'App\\Models\\User';

		return $this->belongsTo($userModel, 'created_by');
	}

	/**
	 * Get full hierarchical path: parent/child/grandchild.
	 */
	public function fullPath(): string
	{
		$ancestors = $this->ancestors()->pluck('name')->toArray();
		$parts     = array_map(static fn (string $name): string => trim($name), $ancestors);
		$parts[]   = $this->name;

		return implode('/', $parts);
	}

	/**
	 * Get all ancestor folders, ordered from root to direct parent.
	 *
	 * @return Collection<int, MediaFolder>
	 */
	public function ancestors(): Collection
	{
		$ancestors = collect();
		$current  = $this->parent;

		while (null !== $current) {
			$ancestors->prepend($current);
			$current = $current->parent;
		}

		return $ancestors;
	}

	/**
	 * Get all descendant folders.
	 *
	 * @return Collection<int, MediaFolder>
	 */
	public function descendants(): Collection
	{
		$descendants = collect();

		$walk = function (MediaFolder $folder) use (&$descendants, &$walk): void {
			foreach ($folder->children as $child) {
				$descendants->push($child);
				$walk($child);
			}
		};

		$walk($this->loadMissing('children'));

		return $descendants;
	}

	/**
	 * Move folder to a new parent.
	 */
	public function moveTo(?int $parentId): bool
	{
		if (null === $parentId) {
			$this->parent_id = null;
			return $this->save();
		}

		$newParent = self::find($parentId);
		if (null === $newParent) {
			return false;
		}

		// Prevent circular references
		if ($this->id === $newParent->id) {
			return false;
		}

		$cursor = $newParent->parent;
		while (null !== $cursor) {
			if ($cursor->id === $this->id) {
				return false;
			}
			$cursor = $cursor->parent;
		}

		$this->parent_id = $parentId;

		return $this->save();
	}
}
