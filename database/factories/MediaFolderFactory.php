<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Database\Factories;

use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Factory for MediaFolder model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ArtisanPackUI\MediaLibrary\Models\MediaFolder>
 *
 * @since 1.0.0
 */
class MediaFolderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ArtisanPackUI\MediaLibrary\Models\MediaFolder>
     */
    protected $model = MediaFolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $userModel = config('artisanpack.media.user_model');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'parent_id' => null,
            'created_by' => $userModel::factory(),
        ];
    }

    /**
     * Indicate that the folder is a child of another folder.
     *
     * @param  int|MediaFolder  $parent  The parent folder ID or instance.
     *
     * @since 1.0.0
     */
    public function childOf(int|MediaFolder $parent): static
    {
        $parentId = $parent instanceof MediaFolder ? $parent->id : $parent;

        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Indicate that the folder was created by a specific user.
     *
     * @param  int|Model  $user  The user ID or user model instance.
     *
     * @since 1.0.0
     */
    public function createdBy(int|Model $user): static
    {
        $userId = $user instanceof Model ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'created_by' => $userId,
        ]);
    }
}
