---
title: ArtisanPack UI Media Library Documentation Home
---

# ArtisanPack UI Media Library Documentation

Welcome to the documentation for the ArtisanPack UI Media Library package. This comprehensive guide covers installation, configuration, usage patterns, API endpoints, integration with the Digital Shopfront CMS, and troubleshooting.

The Media Library provides a complete solution for managing media files in Laravel applications, including hierarchical folder organization, tagging, image processing with modern format conversion (WebP/AVIF), video thumbnail extraction, and a powerful Livewire-based UI.

Use the sidebar or the links below to navigate. Each page begins with a header and concise summary, and many include copy-paste examples.

## Table of Contents

- **Getting Started**
  - [Quick Start Guide](./getting-started.md)

- **Installation**
  - [Installation Overview](./installation/installation.md)
  - [Requirements](./installation/requirements.md)
  - [Configuration](./installation/configuration.md)

- **Usage**
  - [Helper Functions](./usage/helper-functions.md)
  - [Working with Models](./usage/models.md)
  - [Livewire Components](./usage/livewire-components.md)
  - [Streaming Uploads](./usage/streaming-uploads.md) *(v1.1)*
  - [Table Export](./usage/table-export.md) *(v1.1)*

- **Visual Editor Integration** *(v1.1)*
  - [Overview](./visual-editor.md)
  - [MediaPicker Component](./visual-editor/media-picker.md)
  - [Block Content Helpers](./visual-editor/block-helpers.md)
  - [Integration Examples](./visual-editor/examples.md)

- **Dashboard & Statistics** *(v1.1)*
  - [Overview](./dashboard.md)
  - [Media Statistics](./dashboard/statistics.md)
  - [Glass Effects](./dashboard/glass-effects.md)

- **API**
  - [API Overview](./api/api.md)
  - [Endpoints Reference](./api/endpoints.md)
  - [Authentication](./api/authentication.md)

- **Integration**
  - [CMS Module Integration](./integration/cms-module.md)
  - [Permissions & Access Control](./integration/permissions.md)
  - [Customization](./integration/customization.md)

- **Reference**
  - [Troubleshooting](./reference/troubleshooting.md)
  - [FAQ](./reference/faq.md)
  - [Changelog](../CHANGELOG.md)

## Key Features

### Core Features
- 📁 **Hierarchical Folder Organization** - Organize media into nested folders
- 🏷️ **Tag Management** - Tag media items for easy categorization
- 🖼️ **Image Processing** - Automatic thumbnail generation in multiple sizes
- 🚀 **Modern Image Formats** - Automatic conversion to WebP and AVIF
- 📦 **Storage Abstraction** - Support for multiple storage backends
- 🎬 **Video Support** - Video thumbnail extraction using FFmpeg
- 🔍 **Advanced Search & Filtering** - Search, filter by type, folder, or tag
- 🎯 **Drag & Drop Upload** - Modern upload interface with progress tracking
- 🖱️ **Media Modal Component** - Single/multi-select modal with context support
- 🔐 **Permission-based Access Control** - Granular capability-based permissions

### New in v1.1
- ⚡ **Livewire 4 Streaming Uploads** - Real-time upload progress with automatic Livewire 3 fallback
- 📊 **Media Statistics Dashboard** - KPI cards with sparklines showing upload trends
- 📤 **Table Export** - Export media library data to CSV, XLSX, or PDF formats
- 🪟 **Glass Effects** - Modern glassmorphism UI with livewire-ui-components v2.0
- 🧩 **Visual Editor Integration** - MediaPicker component for CMS visual editors
- ⌨️ **Keyboard Navigation** - Full keyboard support for media selection
- 🕐 **Recently Used Media** - Quick access to recently selected media items
- ⚙️ **Feature Flags** - Granular control over features via configuration

## Quick Links

- [Installation Guide](./installation/installation.md) - Get started quickly
- [Helper Functions](./usage/helper-functions.md) - Common usage patterns
- [API Endpoints](./api/endpoints.md) - Complete API reference
- [Troubleshooting](./reference/troubleshooting.md) - Common issues and solutions

### v1.1 Features
- [Streaming Uploads](./usage/streaming-uploads.md) - Livewire 4 real-time progress
- [MediaPicker Component](./visual-editor/media-picker.md) - Visual editor integration
- [Media Statistics](./dashboard/statistics.md) - KPI dashboard
- [Table Export](./usage/table-export.md) - Export to CSV/XLSX/PDF

## About This Documentation

This documentation is structured to support both standalone package usage and integration with the Digital Shopfront CMS. Examples are provided for both scenarios where applicable.

The package follows Laravel and ArtisanPack UI best practices, ensuring compatibility and maintainability.
