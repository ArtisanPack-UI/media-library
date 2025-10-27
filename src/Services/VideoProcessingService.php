<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Services;

use ArtisanPackUI\MediaLibrary\Models\Media;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

/**
 * Video Processing Service
 *
 * Handles video-related operations such as thumbnail extraction,
 * metadata extraction, and preview image generation using FFmpeg.
 *
 * @package ArtisanPackUI\MediaLibrary\Services
 *
 * @since   1.0.0
 */
class VideoProcessingService
{
    /**
     * Media storage service instance.
     */
    protected MediaStorageService $storageService;

    /**
     * FFMpeg instance.
     */
    protected ?FFMpeg $ffmpeg = null;

    /**
     * FFProbe instance.
     */
    protected ?FFProbe $ffprobe = null;

    /**
     * Create a new video processing service instance.
     *
     * @param  MediaStorageService  $storageService  The storage service instance.
     */
    public function __construct(MediaStorageService $storageService)
    {
        $this->storageService = $storageService;

        // Initialize FFMpeg if available
        if (class_exists(FFMpeg::class)) {
            try {
                $this->ffmpeg = FFMpeg::create();
                $this->ffprobe = FFProbe::create();
            } catch (\Exception $e) {
                // FFmpeg not available on system
            }
        }
    }

    /**
     * Check if FFmpeg is available.
     *
     * @return bool True if FFmpeg is available, false otherwise.
     */
    public function isAvailable(): bool
    {
        return null !== $this->ffmpeg && null !== $this->ffprobe;
    }

    /**
     * Extract a thumbnail from a video at a specific time.
     *
     * @param  Media  $media  The media instance.
     * @param  int  $atSecond  The second at which to extract the thumbnail.
     * @return string|null The path to the generated thumbnail or null on failure.
     */
    public function extractThumbnail(Media $media, int $atSecond = 1): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $videoPath = $this->storageService->path($media->file_path, $media->disk);
            $video = $this->ffmpeg->open($videoPath);

            // Generate thumbnail filename
            $pathInfo = pathinfo($media->file_path);
            $thumbnailName = $pathInfo['filename'].'-thumbnail.jpg';
            $thumbnailPath = $pathInfo['dirname'].'/'.$thumbnailName;

            // Create temp file for thumbnail
            $tempThumbPath = sys_get_temp_dir().'/'.uniqid().'-thumbnail.jpg';

            // Extract frame
            $frame = $video->frame(TimeCode::fromSeconds($atSecond));
            $frame->save($tempThumbPath);

            // Upload to storage
            if (file_exists($tempThumbPath)) {
                $contents = file_get_contents($tempThumbPath);
                if (false !== $contents) {
                    $this->storageService->put($thumbnailPath, $contents, $media->disk);
                }

                // Clean up temp file
                unlink($tempThumbPath);

                return $thumbnailPath;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract metadata from a video file.
     *
     * @param  string  $path  The video file path.
     * @param  string  $disk  The storage disk.
     * @return array<string, mixed> The extracted metadata.
     */
    public function extractMetadata(string $path, string $disk): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        try {
            $videoPath = $this->storageService->path($path, $disk);

            // Get video dimensions
            $videoStream = $this->ffprobe
                ->streams($videoPath)
                ->videos()
                ->first();

            $dimensions = null !== $videoStream ? $videoStream->getDimensions() : null;

            // Get duration
            $duration = $this->ffprobe
                ->format($videoPath)
                ->get('duration');

            return [
                'width' => null !== $dimensions ? $dimensions->getWidth() : null,
                'height' => null !== $dimensions ? $dimensions->getHeight() : null,
                'duration' => null !== $duration ? (int) round((float) $duration) : null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate multiple preview images from a video.
     *
     * @param  Media  $media  The media instance.
     * @param  int  $count  The number of preview images to generate.
     * @return array<string> Array of generated preview image paths.
     */
    public function generatePreviewImages(Media $media, int $count = 3): array
    {
        if (! $this->isAvailable() || null === $media->duration) {
            return [];
        }

        $previews = [];

        try {
            $videoPath = $this->storageService->path($media->file_path, $media->disk);
            $video = $this->ffmpeg->open($videoPath);

            // Calculate time intervals
            $interval = $media->duration / ($count + 1);

            for ($i = 1; $count >= $i; $i++) {
                $second = (int) round($interval * $i);

                // Generate preview filename
                $pathInfo = pathinfo($media->file_path);
                $previewName = $pathInfo['filename'].'-preview-'.$i.'.jpg';
                $previewPath = $pathInfo['dirname'].'/'.$previewName;

                // Create temp file
                $tempPath = sys_get_temp_dir().'/'.uniqid().'-preview-'.$i.'.jpg';

                // Extract frame
                $frame = $video->frame(TimeCode::fromSeconds($second));
                $frame->save($tempPath);

                // Upload to storage
                if (file_exists($tempPath)) {
                    $contents = file_get_contents($tempPath);
                    if (false !== $contents) {
                        $this->storageService->put($previewPath, $contents, $media->disk);
                        $previews[] = $previewPath;
                    }

                    // Clean up temp file
                    unlink($tempPath);
                }
            }

            return $previews;
        } catch (\Exception $e) {
            return [];
        }
    }
}
