<?php

/**
 * Image tag suggestion agent.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Ai\Agents;

use ArtisanPackUI\Ai\Agents\ArtisanPackAgent;
use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Credentials\Credentials;
use ArtisanPackUI\Ai\Exceptions\FeatureError;
use ArtisanPackUI\MediaLibrary\Ai\Concerns\NormalizesImageReference;

/**
 * Vision agent that suggests media-library tags for an image based on
 * its visual content, filename, and (optional) folder context.
 *
 * ## Input
 *
 * ```
 * [
 *   'image'         => path|url|base64|[source, value],
 *   'existing_tags' => string[],          // taxonomy the model may pick from
 *   'filename'      => ?string,           // optional, hints filenames convey
 *   'folder'        => ?string,           // optional, containing folder name
 *   'allow_new'     => bool,              // default false; may propose new tags
 * ]
 * ```
 *
 * ## Output schema
 *
 * ```
 * {
 *   tags:       string[]  // subset of existing_tags
 *   new_tags:   string[]  // only populated when allow_new is true
 *   confidence: float     // 0.0 - 1.0
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */
class ImageTagSuggestionAgent extends ArtisanPackAgent
{
    use NormalizesImageReference;

    /**
     * {@inheritDoc}
     */
    public string $featureKey = 'media.suggest_tags';

    /**
     * {@inheritDoc}
     */
    public string $package = 'artisanpack-ui/media-library';

    /**
     * {@inheritDoc}
     */
    public string $defaultModel = 'claude-haiku-4-5';

    /**
     * {@inheritDoc}
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
You suggest media-library tags for the supplied image.

Requirements:
- Prefer tags from the supplied taxonomy ("existing_tags"). Match on meaning, not casing.
- Only return `new_tags` if the caller has set `allow_new` to true AND the existing taxonomy has no reasonable match.
- Return between 0 and 8 tags total across `tags` + `new_tags`.
- Never return duplicates. Never invent a taxonomy tag that was not supplied.
- Use the filename and folder as weak hints, not authoritative labels.

Return a JSON object with keys:
- tags:       array of strings drawn from `existing_tags`
- new_tags:   array of strings the taxonomy does not currently cover (may be empty)
- confidence: number between 0 and 1
PROMPT;
    }

    /**
     * {@inheritDoc}
     */
    public function outputSchema(): array
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => [ 'tags', 'new_tags', 'confidence' ],
            'properties'           => [
                'tags'       => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'new_tags'   => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'confidence' => [
                    'type'    => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function execute( Credentials $credentials, string $model, string $instructions ): array
    {
        $normalized = $this->normalizeInput( $this->input() );

        $prompter = app( AgentPrompter::class );

        $result = $prompter->prompt(
            credentials: $credentials,
            model: $model,
            instructions: $instructions,
            message: $this->buildMessage( $normalized ),
            outputSchema: $this->outputSchema(),
        );

        return [
            'output'        => $this->validateOutput( $result['output'], $normalized ),
            'input_tokens'  => (int) ( $result['input_tokens'] ?? 0 ),
            'output_tokens' => (int) ( $result['output_tokens'] ?? 0 ),
        ];
    }

    /**
     * Coerce raw agent input into the shape this agent operates on.
     *
     * @since 1.3.0
     *
     * @param  mixed  $input  Raw agent input.
     *
     * @return array{
     *     image: array{ type: string, source: string, value: string },
     *     existing_tags: array<int, string>,
     *     filename: string,
     *     folder: string,
     *     allow_new: bool,
     * }
     */
    protected function normalizeInput( mixed $input ): array
    {
        if ( ! is_array( $input ) ) {
            throw FeatureError::forFeature(
                $this->featureKey,
                'input must be an array with an `image` key.',
            );
        }

        $image = $input['image'] ?? null;

        if ( null === $image ) {
            throw FeatureError::forFeature(
                $this->featureKey,
                '`image` is required.',
            );
        }

        $existingTags = [];

        if ( isset( $input['existing_tags'] ) && is_array( $input['existing_tags'] ) ) {
            foreach ( $input['existing_tags'] as $tag ) {
                if ( is_string( $tag ) && '' !== trim( $tag ) ) {
                    $existingTags[] = trim( $tag );
                }
            }
        }

        $existingTags = array_values( array_unique( $existingTags ) );

        return [
            'image'         => $this->normalizeImageReference( $image, $this->featureKey ),
            'existing_tags' => $existingTags,
            'filename'      => isset( $input['filename'] ) && is_string( $input['filename'] ) ? $input['filename'] : '',
            'folder'        => isset( $input['folder'] ) && is_string( $input['folder'] ) ? $input['folder'] : '',
            'allow_new'     => ! empty( $input['allow_new'] ),
        ];
    }

    /**
     * Assemble the structured message body for the prompter.
     *
     * @since 1.3.0
     *
     * @param  array<string, mixed>  $normalized  Normalized input.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildMessage( array $normalized ): array
    {
        $context = [
            'existing_tags' => $normalized['existing_tags'],
            'allow_new'     => $normalized['allow_new'],
        ];

        if ( '' !== $normalized['filename'] ) {
            $context['filename'] = $normalized['filename'];
        }

        if ( '' !== $normalized['folder'] ) {
            $context['folder'] = $normalized['folder'];
        }

        return [
            [
                'type' => 'text',
                'text' => 'Suggest tags for the attached image. Context: '
                    . json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ],
            $normalized['image'],
        ];
    }

    /**
     * Enforce the output schema shape and taxonomy constraints.
     *
     * @since 1.3.0
     *
     * @param  array<string, mixed>  $output      Decoded model output.
     * @param  array<string, mixed>  $normalized  Normalized input (for taxonomy).
     *
     * @return array{ tags: array<int, string>, new_tags: array<int, string>, confidence: float }
     */
    protected function validateOutput( array $output, array $normalized ): array
    {
        $existing = array_map( 'strtolower', $normalized['existing_tags'] );

        $tags     = [];
        $newTags  = [];
        $seen     = [];

        if ( isset( $output['tags'] ) && is_array( $output['tags'] ) ) {
            foreach ( $output['tags'] as $tag ) {
                if ( ! is_string( $tag ) ) {
                    continue;
                }

                $trimmed = trim( $tag );

                if ( '' === $trimmed || isset( $seen[ strtolower( $trimmed ) ] ) ) {
                    continue;
                }

                if ( in_array( strtolower( $trimmed ), $existing, true ) ) {
                    // Preserve the caller's exact casing for the taxonomy tag.
                    $index                          = array_search( strtolower( $trimmed ), $existing, true );
                    $tags[]                         = $normalized['existing_tags'][ $index ];
                    $seen[ strtolower( $trimmed ) ] = true;
                }
            }
        }

        if ( $normalized['allow_new'] && isset( $output['new_tags'] ) && is_array( $output['new_tags'] ) ) {
            foreach ( $output['new_tags'] as $tag ) {
                if ( ! is_string( $tag ) ) {
                    continue;
                }

                $trimmed = trim( $tag );

                if ( '' === $trimmed || isset( $seen[ strtolower( $trimmed ) ] ) ) {
                    continue;
                }

                // Skip model attempts to re-suggest existing taxonomy under `new_tags`.
                if ( in_array( strtolower( $trimmed ), $existing, true ) ) {
                    continue;
                }

                $newTags[]                      = $trimmed;
                $seen[ strtolower( $trimmed ) ] = true;
            }
        }

        $confidence = isset( $output['confidence'] ) ? (float) $output['confidence'] : 0.0;

        if ( $confidence < 0.0 ) {
            $confidence = 0.0;
        } elseif ( $confidence > 1.0 ) {
            $confidence = 1.0;
        }

        return [
            'tags'       => array_slice( $tags, 0, 8 ),
            'new_tags'   => array_slice( $newTags, 0, 8 ),
            'confidence' => $confidence,
        ];
    }
}
