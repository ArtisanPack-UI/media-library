---
title: Media Statistics
---

# Media Statistics

The MediaStatistics component displays key performance indicators (KPIs) for your media library, including upload trends, storage usage, and media type distribution with optional sparkline visualizations.

## Basic Usage

```blade
<livewire:media-statistics />
```

This renders a dashboard with four KPI cards:
- **Total Media** - Count of all media items
- **Total Storage** - Combined file size of all media
- **Images** - Count of image files
- **This Month** - Uploads in the current month

## Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `showSparklines` | bool | `true` | Show sparkline charts |
| `sparklineDays` | int | `30` | Number of days for sparkline data |
| `refreshInterval` | int | `0` | Auto-refresh interval in seconds (0 = disabled) |
| `glass` | bool | `false` | Enable glass effect styling |
| `columns` | int | `4` | Number of columns in grid (1-4) |

## Sparkline Charts

Sparklines show upload trends over time:

```blade
<livewire:media-statistics
    :show-sparklines="true"
    :sparkline-days="30"
/>
```

### Customizing Sparkline Period

```blade
<!-- Last 7 days -->
<livewire:media-statistics :sparkline-days="7" />

<!-- Last 90 days -->
<livewire:media-statistics :sparkline-days="90" />
```

### Disabling Sparklines

```blade
<livewire:media-statistics :show-sparklines="false" />
```

## Glass Effect Styling

Enable modern glassmorphism styling:

```blade
<div class="bg-gradient-to-br from-primary/20 to-secondary/20 p-6 rounded-lg">
    <livewire:media-statistics :glass="true" />
</div>
```

The glass effect works best on gradient or image backgrounds.

## Auto-Refresh

Enable automatic refresh for live dashboards:

```blade
<!-- Refresh every 30 seconds -->
<livewire:media-statistics :refresh-interval="30" />

<!-- Refresh every 5 minutes -->
<livewire:media-statistics :refresh-interval="300" />
```

**Note:** Use sparingly to avoid unnecessary server load.

## Grid Layout

Customize the column layout:

```blade
<!-- 2 columns -->
<livewire:media-statistics :columns="2" />

<!-- Full width cards -->
<livewire:media-statistics :columns="1" />
```

## KPI Cards

### Default Cards

The component includes four default KPI cards:

#### Total Media
- **Value:** Count of all media items
- **Sparkline:** Daily upload counts
- **Change:** Percentage change from previous period

#### Total Storage
- **Value:** Human-readable storage size (e.g., "1.5 GB")
- **Sparkline:** Daily storage growth
- **Change:** Storage change from previous period

#### Images
- **Value:** Count of image media items
- **Sparkline:** Daily image uploads
- **Change:** Percentage change from previous period

#### This Month
- **Value:** Uploads in current month
- **Sparkline:** Daily uploads this month
- **Change:** Comparison to previous month

### Custom KPI Cards

Add custom KPI cards by extending the component:

```php
<?php

namespace App\Livewire;

use ArtisanPackUI\MediaLibrary\Livewire\Components\MediaStatistics;

class CustomMediaStatistics extends MediaStatistics
{
    public function getCustomStats(): array
    {
        return [
            [
                'title' => 'Videos',
                'value' => Media::videos()->count(),
                'icon' => 'o-video-camera',
                'color' => 'text-secondary',
                'sparkline' => $this->showSparklines
                    ? $this->getSparklineData('video')
                    : null,
            ],
            [
                'title' => 'Documents',
                'value' => Media::documents()->count(),
                'icon' => 'o-document',
                'color' => 'text-accent',
                'sparkline' => $this->showSparklines
                    ? $this->getSparklineData('document')
                    : null,
            ],
        ];
    }
}
```

```blade
<livewire:custom-media-statistics />
```

## Stat Card Component

Use individual stat cards for custom layouts:

```blade
<div class="grid grid-cols-2 gap-4">
    <x-artisanpack-stat
        title="Total Media"
        :value="$totalMedia"
        icon="o-photo"
        color="text-primary"
        :sparkline-data="$uploadTrends"
        sparkline-color="primary"
    />

    <x-artisanpack-stat
        title="Storage Used"
        :value="$storageFormatted"
        icon="o-server"
        color="text-secondary"
        :change="$storageChange"
        change-label="vs last month"
    />
</div>
```

### Stat Card Properties

| Property | Type | Description |
|----------|------|-------------|
| `title` | string | Card title |
| `value` | string/int | Main display value |
| `icon` | string | Icon name (Heroicons) |
| `color` | string | Icon/accent color class |
| `description` | string | Optional description text |
| `change` | float | Percentage change |
| `changeLabel` | string | Change comparison label |
| `sparklineData` | array | Array of numeric values for sparkline |
| `sparklineColor` | string | Sparkline color (primary, secondary, etc.) |
| `sparklineType` | string | Sparkline type (line, bar, area) |
| `glass` | bool | Enable glass effect |

## Configuration

Configure default behavior in `config/artisanpack.php`:

```php
'media' => [
    'ui' => [
        'stats_dashboard' => [
            'enabled' => true,
            'sparkline_days' => 30,
            'refresh_interval' => 0,
        ],
    ],
],
```

### Environment Variables

```env
MEDIA_STATS_ENABLED=true
```

## Performance Considerations

### Caching

Statistics are cached automatically:

```php
// Cache key: media_statistics_{user_id}
// Cache duration: 5 minutes
```

### Query Optimization

The component uses optimized queries:
- Aggregate queries for counts and sums
- Date-grouped queries for sparklines
- Eager loading for relationships

### Large Libraries

For media libraries with 100,000+ items:

1. Increase cache duration:
```php
// In a service provider
MediaStatistics::$cacheDuration = 3600; // 1 hour
```

2. Use queue for sparkline calculation:
```php
// In config
'media' => [
    'ui' => [
        'stats_dashboard' => [
            'queue_sparklines' => true,
        ],
    ],
],
```

## Events

### `statistics-refreshed`

Dispatched when statistics are refreshed:

```javascript
Livewire.on('statistics-refreshed', (event) => {
    console.log('Stats updated:', event.stats);
});
```

### Manual Refresh

Trigger a refresh programmatically:

```javascript
Livewire.dispatch('refresh-statistics');
```

## Next Steps

- [Glass Effects](./glass-effects.md) - Customize glass styling
- [Configuration](../installation/configuration.md#ui-settings-v11) - All UI options
- [Livewire Components](../usage/livewire-components.md) - Other components
