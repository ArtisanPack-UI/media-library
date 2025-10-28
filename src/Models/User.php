<?php

namespace ArtisanPackUI\MediaLibrary\Models;

use ArtisanPackUI\MediaLibrary\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Simple user model for testing purposes only.
 * In production, applications should use their own user model
 * configured via artisanpack.media.user_model.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Models
 *
 */
class User extends Authenticatable
{
	use HasApiTokens;
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<string>
	 */
	protected $fillable = [
		'name',
		'email',
		'password',
	];
	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];

	/**
	 * Create a new factory instance for the model.
	 */
	protected static function newFactory()
	{
		return UserFactory::new();
	}

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password'          => 'hashed',
		];
	}
}
