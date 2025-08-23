<?php

namespace ArtisanPackUI\MediaLibrary\Policies;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
	use HandlesAuthorization;

	public function viewAny( User $user ): bool
	{
		return true;
	}

	public function view( User $user, Media $media ): bool
	{
		return true;
	}

	public function create( User $user ): bool
	{
		return $user->can( 'upload_files' );
	}

	public function update( User $user, Media $media ): bool
	{
		return $user->id === $media->user->id || $user->can( 'edit_files' );
	}

	public function delete( User $user, Media $media ): bool
	{
		return $user->can( 'edit_files' );
	}

	public function restore( User $user, Media $media ): bool
	{
		return $user->can( 'edit_files' );
	}

	public function forceDelete( User $user, Media $media ): bool
	{
		return $user->can( 'edit_files' );
	}
}
