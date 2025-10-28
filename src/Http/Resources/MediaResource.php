<?php

declare(strict_types = 1);

namespace ArtisanPackUI\MediaLibrary\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Media API Resource
 *
 * Transforms Media model into JSON response format for API endpoints.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Http\Resources
 *
 */
class MediaResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param Request $request The HTTP request instance.
	 * @return array<string, mixed> The resource array representation.
	 */
	public function toArray( Request $request ): array
	{
		return [
			'id'          => $this->id,
			'title'       => $this->title,
			'file_name'   => $this->file_name,
			'file_path'   => $this->file_path,
			'url'         => $this->url(),
			'disk'        => $this->disk,
			'mime_type'   => $this->mime_type,
			'file_size'   => $this->file_size,
			'human_size'  => $this->humanFileSize(),
			'alt_text'    => $this->alt_text,
			'caption'     => $this->caption,
			'description' => $this->description,
			'width'       => $this->width,
			'height'      => $this->height,
			'duration'    => $this->duration,
			'metadata'    => $this->metadata,
			'is_image'    => $this->isImage(),
			'is_video'    => $this->isVideo(),
			'is_audio'    => $this->isAudio(),
			'is_document' => $this->isDocument(),
			'folder'      => [
				'id'   => $this->folder_id,
				'name' => $this->folder?->name,
			],
			'uploaded_by' => [
				'id'   => $this->uploaded_by,
				'name' => $this->uploadedBy?->name,
			],
			'tags'        => $this->whenLoaded( 'tags', function () {
				return $this->tags->map( function ( $tag ) {
					return [
						'id'   => $tag->id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					];
				} );
			} ),
			'created_at'  => $this->created_at?->toIso8601String(),
			'updated_at'  => $this->updated_at?->toIso8601String(),
			'deleted_at'  => $this->deleted_at?->toIso8601String(),
		];
	}
}
