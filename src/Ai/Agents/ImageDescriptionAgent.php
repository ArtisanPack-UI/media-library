<?php

/**
 * Image description agent.
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
 * Vision agent that produces a longer-form description of an image,
 * distinct from alt text. Intended for galleries, portfolios, and any
 * image-heavy layout that benefits from paragraph-length copy.
 *
 * ## Input
 *
 * ```
 * [
 *   'image'  => path|url|base64|[source, value],
 *   'length' => 'short'|'medium'|'long',  // default 'medium'
 * ]
 * ```
 *
 * A bare image reference (string / [source, value] array) is also
 * accepted and treated as `length = 'medium'`.
 *
 * ## Output schema
 *
 * ```
 * {
 *   description: string    // 1-4 sentences depending on requested length
 *   confidence:  float     // 0.0 - 1.0
 *   warnings:    string[]  // model-flagged concerns
 * }
 * ```
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary
 *
 * @since      1.3.0
 */
class ImageDescriptionAgent extends ArtisanPackAgent
{
    use NormalizesImageReference;

    /**
     * Approximate character caps applied post-response to bound cost even
     * when the model over-produces.
     */
    protected const LENGTH_CAPS = [
        'short'  => 200,
        'medium' => 600,
        'long'   => 1500,
    ];

    /**
     * {@inheritDoc}
     */
    public string $featureKey = 'media.image_description';

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
You produce a paragraph-length description of the supplied image, distinct from alt text.

Requirements:
- Describe the scene, subject, mood, and visually notable details.
- Respect the requested length: "short" (1-2 sentences), "medium" (2-3 sentences), "long" (3-4 sentences).
- Do not begin with "Image of" or "This image shows". Prefer active voice.
- Do not repeat the same information across multiple sentences.
- If the image is unreadable, corrupt, or clearly not an image, add a warning describing what you see.

Return a JSON object with keys:
- description: string
- confidence:  number between 0 and 1
- warnings:    array of strings
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
            'required'             => [ 'description', 'confidence', 'warnings' ],
            'properties'           => [
                'description' => [ 'type' => 'string' ],
                'confidence'  => [
                    'type'    => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'warnings'    => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
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
            message: [
                [
                    'type' => 'text',
                    'text' => sprintf( 'Describe the attached image. Requested length: %s.', $normalized['length'] ),
                ],
                $normalized['image'],
            ],
            outputSchema: $this->outputSchema(),
        );

        return [
            'output'        => $this->validateOutput( $result['output'], $normalized['length'] ),
            'input_tokens'  => (int) ( $result['input_tokens'] ?? 0 ),
            'output_tokens' => (int) ( $result['output_tokens'] ?? 0 ),
        ];
    }

    /**
     * Coerce raw agent input into `{ image, length }` shape.
     *
     * @since 1.3.0
     *
     * @param  mixed  $input  Raw agent input.
     *
     * @return array{ image: array{ type: string, source: string, value: string }, length: string }
     */
    protected function normalizeInput( mixed $input ): array
    {
        if ( is_array( $input ) && isset( $input['image'] ) ) {
            $image  = $input['image'];
            $length = isset( $input['length'] ) && is_string( $input['length'] ) ? strtolower( $input['length'] ) : 'medium';
        } else {
            // Treat a bare string / [source, value] payload as a shortcut
            // for medium-length description of that image.
            $image  = $input;
            $length = 'medium';
        }

        if ( ! isset( self::LENGTH_CAPS[ $length ] ) ) {
            throw FeatureError::forFeature(
                $this->featureKey,
                sprintf( '`length` must be one of short, medium, long (got "%s").', $length ),
            );
        }

        return [
            'image'  => $this->normalizeImageReference( $image, $this->featureKey ),
            'length' => $length,
        ];
    }

    /**
     * Enforce output shape and cap description length by requested tier.
     *
     * @since 1.3.0
     *
     * @param  array<string, mixed>  $output  Decoded model output.
     * @param  string                $length  Requested length tier.
     *
     * @return array{ description: string, confidence: float, warnings: array<int, string> }
     */
    protected function validateOutput( array $output, string $length ): array
    {
        $description = isset( $output['description'] ) ? (string) $output['description'] : '';
        $confidence  = isset( $output['confidence'] ) ? (float) $output['confidence'] : 0.0;
        $warnings    = [];

        if ( isset( $output['warnings'] ) && is_array( $output['warnings'] ) ) {
            foreach ( $output['warnings'] as $warning ) {
                if ( is_string( $warning ) && '' !== $warning ) {
                    $warnings[] = $warning;
                }
            }
        }

        $cap = self::LENGTH_CAPS[ $length ];

        if ( mb_strlen( $description ) > $cap ) {
            $description = mb_substr( $description, 0, $cap );
        }

        if ( $confidence < 0.0 ) {
            $confidence = 0.0;
        } elseif ( $confidence > 1.0 ) {
            $confidence = 1.0;
        }

        return [
            'description' => $description,
            'confidence'  => $confidence,
            'warnings'    => $warnings,
        ];
    }
}
