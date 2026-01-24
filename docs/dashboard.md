---
title: Dashboard & Statistics
---

# Dashboard & Statistics

The v1.1 release introduces a powerful media statistics dashboard with KPI cards, sparkline charts, and modern glass effects powered by livewire-ui-components v2.0.

## Overview

The dashboard features include:

- **Media Statistics Component** - KPI cards showing upload trends, storage usage, and type distribution
- **Sparkline Charts** - Visual trends over configurable time periods
- **Glass Effects** - Modern glassmorphism UI styling
- **Real-time Updates** - Optional auto-refresh for live dashboards

## Components

### Media Statistics

Display key performance indicators for your media library:

```blade
<livewire:media-statistics
    :show-sparklines="true"
    :sparkline-days="30"
/>
```

**[Learn More: Media Statistics](./dashboard/statistics.md)**

### Glass Effects

Modern glassmorphism styling for cards and overlays:

```blade
<x-artisanpack-card :glass="true">
    Content with glass effect
</x-artisanpack-card>
```

**[Learn More: Glass Effects](./dashboard/glass-effects.md)**

## Quick Example

```blade
<div class="p-6 bg-gradient-to-br from-primary/20 to-secondary/20">
    <!-- Statistics with glass styling -->
    <livewire:media-statistics
        :glass="true"
        :show-sparklines="true"
        :sparkline-days="30"
    />
</div>
```

## Configuration

Dashboard settings are configured in `config/artisanpack.php` under the `media.ui` key.

See the [Configuration Guide](./installation/configuration.md#ui-settings-v11) for all options.

## Next Steps

- [Media Statistics](./dashboard/statistics.md) - KPI cards and sparklines
- [Glass Effects](./dashboard/glass-effects.md) - Glassmorphism customization
- [Configuration](./installation/configuration.md#ui-settings-v11) - All UI configuration options
