<?php

declare( strict_types=1 );

namespace Tests\Support;

use ArtisanPackUI\Ai\Contracts\AgentPrompter;
use ArtisanPackUI\Ai\Credentials\Credentials;

/**
 * Records agent prompter invocations and returns canned responses.
 *
 * Local copy of the ai package's own FakeAgentPrompter so the
 * media-library test suite can exercise its agents in isolation.
 */
class FakeAgentPrompter implements AgentPrompter
{
    /** @var array<int, array<string, mixed>> */
    public array $calls = [];

    /** @var array<int, array<string, mixed>> */
    protected array $queue = [];

    /**
     * @param  array<string, mixed>  $output
     */
    public function queue( array $output, int $inputTokens = 100, int $outputTokens = 50 ): void
    {
        $this->queue[] = [
            'output'        => $output,
            'input_tokens'  => $inputTokens,
            'output_tokens' => $outputTokens,
        ];
    }

    public function prompt(
        Credentials $credentials,
        string $model,
        string $instructions,
        string|array $message,
        array $outputSchema,
    ): array {
        $this->calls[] = [
            'credentials'   => $credentials,
            'model'         => $model,
            'instructions'  => $instructions,
            'message'       => $message,
            'output_schema' => $outputSchema,
        ];

        if ( [] === $this->queue ) {
            return [
                'output'        => [],
                'input_tokens'  => 0,
                'output_tokens' => 0,
            ];
        }

        return array_shift( $this->queue );
    }
}
