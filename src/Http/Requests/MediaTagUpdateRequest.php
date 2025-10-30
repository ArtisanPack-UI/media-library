<?php

declare(strict_types = 1);

namespace ArtisanPackUI\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Media Tag Update Request
 *
 * Validates data for updating an existing media tag.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Http\Requests
 */
class MediaTagUpdateRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool True if authorized, false otherwise.
	 */
	public function authorize(): bool
	{
		return true; // Authorization is handled by the controller via policies
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, mixed> The validation rules.
	 */
	public function rules(): array
	{
		$tagId = $this->route( 'tag' );

		return [
			'name'        => [
				'sometimes',
				'required',
				'string',
				'max:255',
				Rule::unique( 'media_tags', 'name' )->ignore( $tagId ),
			],
			'description' => [ 'nullable', 'string' ],
		];
	}

	/**
	 * Get custom error messages for validation rules.
	 *
	 * @return array<string, string> The custom error messages.
	 */
	public function messages(): array
	{
		return [
			'name.required' => 'Tag name is required.',
			'name.max'      => 'Tag name cannot exceed 255 characters.',
			'name.unique'   => 'A tag with this name already exists.',
		];
	}
}
