<?php

namespace ArtisanPackUI\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Media Folder Store Request
 *
 * Validates data for creating a new media folder.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Http\Requests
 */
class MediaFolderStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:media_folders,id'],
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
            'name.required' => 'Folder name is required.',
            'name.max' => 'Folder name cannot exceed 255 characters.',
            'parent_id.exists' => 'The selected parent folder does not exist.',
        ];
    }
}
