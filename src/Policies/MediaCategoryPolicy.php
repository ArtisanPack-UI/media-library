<?php

namespace ArtisanPackUI\MediaLibrary\Policies;

use ArtisanPackUI\MediaLibrary\Models\MediaCategory;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaCategoryPolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{
		return true;
	}

	public function view( User $user, MediaCategory $mediaCategory ): bool
	{
		return true;
	}

	public function create( User $user ): bool
	{
		return true;
	}

	public function update( User $user, MediaCategory $mediaCategory ): bool
	{
		return $user->can( 'manage_categories' );
	}

	public function delete( User $user, MediaCategory $mediaCategory ): bool
	{
		return $user->can( 'manage_categories' );
	}

	public function restore( User $user, MediaCategory $mediaCategory ): bool
	{
		return $user->can( 'manage_categories' );
	}

	public function forceDelete( User $user, MediaCategory $mediaCategory ): bool
	{
		return $user->can( 'manage_categories' );
	}
}
