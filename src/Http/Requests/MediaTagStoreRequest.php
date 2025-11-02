<?php

namespace ArtisanPackUI\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Media Tag Store Request
 *
 * Validates data for creating a new media tag.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Http\Requests
 */
class MediaTagStoreRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:media_tags,name'],
            'description' => ['nullable', 'string'],
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
            'name.max' => 'Tag name cannot exceed 255 characters.',
            'name.unique' => 'A tag with this name already exists.',
        ];
    }
}
