<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Database\Factories;

use ArtisanPackUI\MediaLibrary\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User Factory
 *
 * Factory for creating User model instances for testing.
 *
 * @package ArtisanPackUI\MediaLibrary\Database\Factories
 *
 * @since   1.0.0
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @since 1.0.0
     *
     * @var class-string<\ArtisanPackUI\MediaLibrary\Models\User>
     */
    protected $model = User::class;

    /**
     * Defines the model's default state.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed> The default model attributes.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicates that the model's email address should be unverified.
     *
     * @since 1.0.0
     *
     * @return static The factory instance.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
