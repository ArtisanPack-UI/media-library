---
title: Visual Editor Integration
---

# Visual Editor Integration

This section covers integrating the Media Library with visual editors, providing seamless media selection and management within your content editing experience.

## Overview

The v1.1 release introduces comprehensive visual editor support through:

- **MediaPicker Component** - A Livewire component designed for embedding in visual editors
- **Block Content Helpers** - PHP helpers for working with block-based content
- **Block Requirements** - Configurable media constraints per block type
- **Recently Used Media** - Quick access to frequently selected items

## Components

### MediaPicker

The primary component for visual editor integration. Supports single and multi-select modes, type filtering, and context-based events.

**[Learn More: MediaPicker Component](./visual-editor/media-picker.md)**

### Block Content Helpers

Helper functions and traits for working with media in block-based content structures.

**[Learn More: Block Content Helpers](./visual-editor/block-helpers.md)**

## Integration Guides

### CMS Integration Examples

Complete examples for integrating with various CMS platforms and visual editors.

**[View Examples](./visual-editor/examples.md)**

## Quick Start

### Basic MediaPicker Usage

```blade
<livewire:media::media-picker
    context="featured-image"
    :allowed-types="['image']"
    :multi-select="false"
/>
```

### Handling Selection Events

```javascript
Livewire.on('media-picked', (event) => {
    if (event.context === 'featured-image') {
        // Handle the selected media
        console.log('Selected:', event.media);
    }
});
```

## Configuration

Visual editor settings are configured in `config/media.php` under the `visual_editor` key.

See the [Configuration Guide](./installation/configuration.md#visual-editor-integration-v11) for all options.

## Next Steps

- [MediaPicker Component](./visual-editor/media-picker.md) - Detailed component documentation
- [Block Helpers](./visual-editor/block-helpers.md) - Working with block content
- [Integration Examples](./visual-editor/examples.md) - Complete integration guides
