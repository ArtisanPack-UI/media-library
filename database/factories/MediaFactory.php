<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Database\Factories;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Factory for Media model.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ArtisanPackUI\MediaLibrary\Models\Media>
 *
 * @since 1.0.0
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ArtisanPackUI\MediaLibrary\Models\Media>
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @since 1.0.0
     */
    public function definition(): array
    {
        $userModel = config('artisanpack.media.user_model');

        return [
            'title' => fake()->sentence(3),
            'file_name' => fake()->word().'.jpg',
            'file_path' => fake()->filePath(),
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'alt_text' => fake()->sentence(),
            'caption' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
            'duration' => null,
            'folder_id' => null,
            'uploaded_by' => $userModel::factory(),
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the media is an image.
     *
     * @since 1.0.0
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'file_name' => fake()->word().'.'.fake()->randomElement(['jpg', 'png', 'gif', 'webp']),
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
            'duration' => null,
        ]);
    }

    /**
     * Indicate that the media is a video.
     *
     * @since 1.0.0
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement(['video/mp4', 'video/webm', 'video/quicktime']),
            'file_name' => fake()->word().'.'.fake()->randomElement(['mp4', 'webm', 'mov']),
            'width' => fake()->numberBetween(640, 1920),
            'height' => fake()->numberBetween(480, 1080),
            'duration' => fake()->numberBetween(10, 3600),
        ]);
    }

    /**
     * Indicate that the media is an audio file.
     *
     * @since 1.0.0
     */
    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement(['audio/mpeg', 'audio/wav', 'audio/ogg']),
            'file_name' => fake()->word().'.'.fake()->randomElement(['mp3', 'wav', 'ogg']),
            'width' => null,
            'height' => null,
            'duration' => fake()->numberBetween(30, 600),
        ]);
    }

    /**
     * Indicate that the media is a document.
     *
     * @since 1.0.0
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => fake()->randomElement([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]),
            'file_name' => fake()->word().'.'.fake()->randomElement(['pdf', 'doc', 'docx']),
            'width' => null,
            'height' => null,
            'duration' => null,
        ]);
    }

    /**
     * Indicate that the media is in a specific folder.
     *
     * @param  int|MediaFolder  $folder  The folder ID or instance.
     *
     * @since 1.0.0
     */
    public function inFolder(int|MediaFolder $folder): static
    {
        $folderId = $folder instanceof MediaFolder ? $folder->id : $folder;

        return $this->state(fn (array $attributes) => [
            'folder_id' => $folderId,
        ]);
    }

    /**
     * Indicate that the media is uploaded by a specific user.
     *
     * @param  int|Model  $user  The user ID or user model instance.
     *
     * @since 1.0.0
     */
    public function uploadedBy(int|Model $user): static
    {
        $userId = $user instanceof Model ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $userId,
        ]);
    }
}
