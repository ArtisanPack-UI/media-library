---
title: ArtisanPack UI Media Library Documentation Home
---

# ArtisanPack UI Media Library Documentation

Welcome to the documentation for the ArtisanPack UI Media Library package. This comprehensive guide covers installation, configuration, usage patterns, API endpoints, integration with the Digital Shopfront CMS, and troubleshooting.

The Media Library provides a complete solution for managing media files in Laravel applications, including hierarchical folder organization, tagging, image processing with modern format conversion (WebP/AVIF), video thumbnail extraction, and a powerful Livewire-based UI.

Use the sidebar or the links below to navigate. Each page begins with a header and concise summary, and many include copy-paste examples.

## Table of Contents

- **Getting Started**
  - [Quick Start Guide](Getting-Started)
  - [Upgrading to v1.1](Upgrading)

- **Installation**
  - [Installation Overview](Installation-Installation)
  - [Requirements](Installation-Requirements)
  - [Configuration](Installation-Configuration)

- **Usage**
  - [Helper Functions](Usage-Helper-Functions)
  - [Working with Models](Usage-Models)
  - [Livewire Components](Usage-Livewire-Components)
  - [Streaming Uploads](Usage-Streaming-Uploads) *(v1.1)*
  - [Table Export](Usage-Table-Export) *(v1.1)*

- **Visual Editor Integration** *(v1.1)*
  - [Overview](Visual-Editor)
  - [MediaPicker Component](Visual-Editor-Media-Picker)
  - [Block Content Helpers](Visual-Editor-Block-Helpers)
  - [Integration Examples](Visual-Editor-Examples)

- **Dashboard & Statistics** *(v1.1)*
  - [Overview](Dashboard)
  - [Media Statistics](Dashboard-Statistics)
  - [Glass Effects](Dashboard-Glass-Effects)

- **API**
  - [API Overview](Api-Api)
  - [Endpoints Reference](Api-Endpoints)
  - [Authentication](Api-Authentication)

- **Integration**
  - [CMS Module Integration](Integration-Cms-Module)
  - [Permissions & Access Control](Integration-Permissions)
  - [Customization](Integration-Customization)

- **Reference**
  - [Troubleshooting](Reference-Troubleshooting)
  - [FAQ](Reference-Faq)
  - [Changelog](Changelog)

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

- [Installation Guide](Installation-Installation) - Get started quickly
- [Helper Functions](Usage-Helper-Functions) - Common usage patterns
- [API Endpoints](Api-Endpoints) - Complete API reference
- [Troubleshooting](Reference-Troubleshooting) - Common issues and solutions

### v1.1 Features
- [Streaming Uploads](Usage-Streaming-Uploads) - Livewire 4 real-time progress
- [MediaPicker Component](Visual-Editor-Media-Picker) - Visual editor integration
- [Media Statistics](Dashboard-Statistics) - KPI dashboard
- [Table Export](Usage-Table-Export) - Export to CSV/XLSX/PDF

## About This Documentation

This documentation is structured to support both standalone package usage and integration with the Digital Shopfront CMS. Examples are provided for both scenarios where applicable.

The package follows Laravel and ArtisanPack UI best practices, ensuring compatibility and maintainability.
