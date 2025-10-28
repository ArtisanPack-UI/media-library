<?php

declare(strict_types = 1);

namespace ArtisanPackUI\MediaLibrary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Media Store Request
 *
 * Validates data for creating/uploading new media.
 *
 * @since   1.0.0
 * @package ArtisanPackUI\MediaLibrary\Http\Requests
 *
 */
class MediaStoreRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool True if authorized, false otherwise.
	 */
	public function authorize(): bool
	{
		return true; // Authorization handled by policy
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, mixed> The validation rules.
	 */
	public function rules(): array
	{
		$maxFileSize         = config( 'artisanpack.media.max_file_size', 10240 );
		$allowedExtensions   = $this->getAllowedExtensions();
		$allowedExtensionStr = implode( ',', $allowedExtensions );

		return [
			'file'        => [
				'required',
				'file',
				'max:' . $maxFileSize,
				'mimes:' . $allowedExtensionStr,
			],
			'title'       => [ 'nullable', 'string', 'max:255' ],
			'alt_text'    => [ 'nullable', 'string', 'max:255' ],
			'caption'     => [ 'nullable', 'string' ],
			'description' => [ 'nullable', 'string' ],
			'folder_id'   => [ 'nullable', 'exists:media_folders,id' ],
			'tags'        => [ 'nullable', 'array' ],
			'tags.*'      => [ 'string', 'max:255' ],
		];
	}

	/**
	 * Get allowed file extensions from MIME types configuration.
	 *
	 * @return array<string> Array of allowed file extensions.
	 */
	protected function getAllowedExtensions(): array
	{
		$mimeTypes = config( 'artisanpack.media.allowed_mime_types', [] );
		$extensions = [];

		$mimeToExtension = [
			'image/jpeg'                                                                         => 'jpg,jpeg',
			'image/jpg'                                                                          => 'jpg',
			'image/png'                                                                          => 'png',
			'image/gif'                                                                          => 'gif',
			'image/webp'                                                                         => 'webp',
			'image/avif'                                                                         => 'avif',
			'image/svg+xml'                                                                      => 'svg',
			'application/pdf'                                                                    => 'pdf',
			'application/msword'                                                                 => 'doc',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'           => 'docx',
			'application/vnd.ms-excel'                                                           => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'                 => 'xlsx',
			'video/mp4'                                                                          => 'mp4',
			'video/mpeg'                                                                         => 'mpeg,mpg',
			'video/quicktime'                                                                    => 'mov',
			'video/webm'                                                                         => 'webm',
			'audio/mpeg'                                                                         => 'mp3',
			'audio/wav'                                                                          => 'wav',
			'audio/ogg'                                                                          => 'ogg',
		];

		foreach ( $mimeTypes as $mimeType ) {
			if ( isset( $mimeToExtension[ $mimeType ] ) ) {
				$exts = explode( ',', $mimeToExtension[ $mimeType ] );
				$extensions = array_merge( $extensions, $exts );
			}
		}

		return array_unique( $extensions );
	}

	/**
	 * Get custom messages for validator errors.
	 *
	 * @return array<string, string> Custom error messages.
	 */
	public function messages(): array
	{
		return [
			'file.required'       => 'A file is required for upload.',
			'file.file'           => 'The uploaded item must be a valid file.',
			'file.max'            => 'The file size exceeds the maximum allowed size of :max KB.',
			'file.mimes'          => 'The file type is not allowed. Allowed types: :values.',
			'title.string'        => 'The title must be a string.',
			'title.max'           => 'The title must not exceed :max characters.',
			'alt_text.string'     => 'The alt text must be a string.',
			'alt_text.max'        => 'The alt text must not exceed :max characters.',
			'caption.string'      => 'The caption must be a string.',
			'description.string'  => 'The description must be a string.',
			'folder_id.exists'    => 'The selected folder does not exist.',
			'tags.array'          => 'Tags must be provided as an array.',
			'tags.*.string'       => 'Each tag must be a string.',
			'tags.*.max'          => 'Each tag must not exceed :max characters.',
		];
	}
}
