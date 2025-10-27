<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\MediaTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
	];

	/**
	 * Create a new factory instance for the model.
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
		return $this->media()->count();
	}

	/**
	 * Get all media items with this tag.
	 */
	public function media(): BelongsToMany
	{
		return $this->belongsToMany( Media::class, 'media_taggables' );
	}
}
