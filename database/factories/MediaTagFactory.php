<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Database\Factories;

use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for MediaTag model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ArtisanPackUI\MediaLibrary\Models\MediaTag>
 *
 * @since 1.0.0
 */
class MediaTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ArtisanPackUI\MediaLibrary\Models\MediaTag>
     */
    protected $model = MediaTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
