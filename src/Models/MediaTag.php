<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\MediaTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * MediaTag model represents a tag that can be assigned to media items.
 *
 * @since 1.0.0
 */
class MediaTag extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @since 1.0.0
     *
     * @return MediaTagFactory The model factory instance.
     */
    protected static function newFactory(): MediaTagFactory
    {
        return MediaTagFactory::new();
    }

    /**
     * Get count of media items with this tag.
     */
    public function mediaCount(): int
    {
        // Use eager-loaded aggregate if available to avoid an extra query
        if (isset($this->attributes['media_count'])) {
            return (int) $this->attributes['media_count'];
        }

        return $this->media()->count();
    }

    /**
     * Get all media items with this tag.
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_taggables');
    }
}
