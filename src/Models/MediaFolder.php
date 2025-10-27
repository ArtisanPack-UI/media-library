<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Models;

use App\Models\User;
use ArtisanPackUI\MediaLibrary\Database\Factories\MediaFolderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    /**
     * Get the child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    /**
     * Get all media items in this folder.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    /**
     * Get the user who created this folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get full path of folder (parent/child/grandchild).
     */
    public function fullPath(): string
    {
        $ancestors = $this->ancestors();
        $ancestors->push($this);

        return $ancestors->pluck('name')->implode('/');
    }

    /**
     * Get all ancestor folders.
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendant folders.
     */
    public function descendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Move folder to a new parent.
     */
    public function moveTo(?int $parentId): bool
    {
        // Prevent circular references
        if ($parentId !== null) {
            $newParent = static::find($parentId);

            if (! $newParent) {
                return false;
            }

            // Check if the new parent is a descendant of this folder
            if ($newParent->id === $this->id || $this->descendants()->contains('id', $parentId)) {
                return false;
            }
        }

        $this->parent_id = $parentId;

        return $this->save();
    }
}
