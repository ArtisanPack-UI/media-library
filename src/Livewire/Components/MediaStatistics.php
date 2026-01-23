<?php

/**
 * Media Statistics Dashboard Livewire Component
 *
 * Displays comprehensive statistics about the media library including
 * total counts, storage usage, type breakdown, and usage trends.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.1.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPackUI\MediaLibrary\Models\Media;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Media Statistics Dashboard Component
 *
 * Displays comprehensive statistics about the media library including
 * total counts, storage usage, type breakdown, and usage trends.
 *
 * @since   1.1.0
 */
class MediaStatistics extends Component
{
    /**
     * Number of top items to display in rankings.
     *
     * @since 1.1.0
     *
     * @var int
     */
    public int $topItemsLimit = 5;

    /**
     * Number of days to look back for recent uploads.
     *
     * @since 1.1.0
     *
     * @var int
     */
    public int $recentDays = 7;

    /**
     * Mount the component and validate initial values.
     *
     * @since 1.1.0
     */
    public function mount(): void
    {
        $this->topItemsLimit = $this->clampTopItemsLimit($this->topItemsLimit);
        $this->recentDays = $this->clampRecentDays($this->recentDays);
    }

    /**
     * Validate topItemsLimit when updated.
     *
     * @since 1.1.0
     */
    public function updatedTopItemsLimit(mixed $value): void
    {
        $this->topItemsLimit = $this->clampTopItemsLimit($value);
    }

    /**
     * Validate recentDays when updated.
     *
     * @since 1.1.0
     */
    public function updatedRecentDays(mixed $value): void
    {
        $this->recentDays = $this->clampRecentDays($value);
    }

    /**
     * Clamp topItemsLimit to a safe range (1-100).
     *
     * @since 1.1.0
     *
     * @param  mixed  $value  The value to clamp.
     * @return int The clamped value.
     */
    protected function clampTopItemsLimit(mixed $value): int
    {
        $intValue = (int) $value;

        return max(1, min(100, $intValue));
    }

    /**
     * Clamp recentDays to a safe range (1-365).
     *
     * @since 1.1.0
     *
     * @param  mixed  $value  The value to clamp.
     * @return int The clamped value.
     */
    protected function clampRecentDays(mixed $value): int
    {
        $intValue = (int) $value;

        return max(1, min(365, $intValue));
    }

    /**
     * Get total media count.
     *
     * @since 1.1.0
     *
     * @return int The total number of media items.
     */
    #[Computed]
    public function totalMedia(): int
    {
        return Media::count();
    }

    /**
     * Get total storage used in bytes.
     *
     * @since 1.1.0
     *
     * @return int The total storage used in bytes.
     */
    #[Computed]
    public function totalStorageBytes(): int
    {
        return (int) Media::sum('file_size');
    }

    /**
     * Get human-readable total storage used.
     *
     * @since 1.1.0
     *
     * @return string The formatted storage size (e.g., "1.5 GB").
     */
    #[Computed]
    public function totalStorageFormatted(): string
    {
        return $this->formatBytes($this->totalStorageBytes);
    }

    /**
     * Get media count by type.
     *
     * @since 1.1.0
     *
     * @return array<string, int> Array of type names and their counts.
     */
    #[Computed]
    public function mediaByType(): array
    {
        return [
            'images' => Media::images()->count(),
            'videos' => Media::videos()->count(),
            'audio' => Media::audios()->count(),
            'documents' => Media::documents()->count(),
        ];
    }

    /**
     * Get storage used by type.
     *
     * @since 1.1.0
     *
     * @return array<string, array{bytes: int, formatted: string}> Array of type storage data.
     */
    #[Computed]
    public function storageByType(): array
    {
        $imageBytes = (int) Media::images()->sum('file_size');
        $videoBytes = (int) Media::videos()->sum('file_size');
        $audioBytes = (int) Media::audios()->sum('file_size');
        $documentBytes = (int) Media::documents()->sum('file_size');

        return [
            'images' => [
                'bytes' => $imageBytes,
                'formatted' => $this->formatBytes($imageBytes),
            ],
            'videos' => [
                'bytes' => $videoBytes,
                'formatted' => $this->formatBytes($videoBytes),
            ],
            'audio' => [
                'bytes' => $audioBytes,
                'formatted' => $this->formatBytes($audioBytes),
            ],
            'documents' => [
                'bytes' => $documentBytes,
                'formatted' => $this->formatBytes($documentBytes),
            ],
        ];
    }

    /**
     * Get count of media uploaded in the recent period.
     *
     * @since 1.1.0
     *
     * @return int The count of recently uploaded media.
     */
    #[Computed]
    public function recentUploadsCount(): int
    {
        $days = $this->clampRecentDays($this->recentDays);

        return Media::where('created_at', '>=', Carbon::now()->subDays($days))->count();
    }

    /**
     * Get daily upload counts for the recent period.
     *
     * @since 1.1.0
     *
     * @return array<int, int> Array of daily upload counts for sparkline.
     */
    #[Computed]
    public function dailyUploadCounts(): array
    {
        $days = $this->clampRecentDays($this->recentDays);
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $uploads = Media::query()
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing days with zeros
        $counts = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $counts[] = $uploads[$date] ?? 0;
        }

        return $counts;
    }

    /**
     * Get top folders by media count.
     *
     * @since 1.1.0
     *
     * @return Collection<int, object> Collection of top folders with counts.
     */
    #[Computed]
    public function topFolders(): Collection
    {
        $limit = $this->clampTopItemsLimit($this->topItemsLimit);

        return MediaFolder::query()
            ->withCount('media')
            ->orderByDesc('media_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top tags by usage count.
     *
     * @since 1.1.0
     *
     * @return Collection<int, object> Collection of top tags with counts.
     */
    #[Computed]
    public function topTags(): Collection
    {
        $limit = $this->clampTopItemsLimit($this->topItemsLimit);

        return MediaTag::query()
            ->withCount('media')
            ->orderByDesc('media_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get total folder count.
     *
     * @since 1.1.0
     *
     * @return int The total number of folders.
     */
    #[Computed]
    public function totalFolders(): int
    {
        return MediaFolder::count();
    }

    /**
     * Get total tag count.
     *
     * @since 1.1.0
     *
     * @return int The total number of tags.
     */
    #[Computed]
    public function totalTags(): int
    {
        return MediaTag::count();
    }

    /**
     * Get average file size.
     *
     * @since 1.1.0
     *
     * @return string The formatted average file size.
     */
    #[Computed]
    public function averageFileSize(): string
    {
        $avg = (int) Media::avg('file_size');

        return $this->formatBytes($avg);
    }

    /**
     * Get largest file.
     *
     * @since 1.1.0
     *
     * @return Media|null The largest media item or null if none exist.
     */
    #[Computed]
    public function largestFile(): ?Media
    {
        return Media::query()
            ->orderByDesc('file_size')
            ->first();
    }

    /**
     * Format bytes to human-readable string.
     *
     * @since 1.1.0
     *
     * @param  int  $bytes  The number of bytes.
     * @return string The formatted size string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Renders the component.
     *
     * @since 1.1.0
     *
     * @return View The component view.
     */
    public function render(): View
    {
        return view('media::livewire.components.media-statistics');
    }
}
