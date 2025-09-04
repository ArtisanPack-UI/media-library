---
title: AI Guidelines
---

# AI Guidelines

## Media Library (artisanpack-ui-media-library)

**Primary Goal:** To guide the development and use of a secure and efficient media library for managing assets.

### Core Principles for the AI:

**Security:** Sanitize all filenames and validate file uploads to prevent security vulnerabilities.

**Performance:** Optimize images on upload to reduce file sizes and improve page load times.

**User Experience:** Provide a user-friendly interface for uploading, managing, and deleting media assets.

### Specific Instructions for the AI:

- When generating code that handles file uploads, use the `sanitizeFilename` function from the `artisanpack-ui-security` package to prevent directory traversal attacks.

- When creating the UI for the media library, ensure that it is responsive and provides a clear and intuitive user experience.

- Generate code that automatically optimizes images on upload, such as by resizing them to appropriate dimensions and compressing them without significant quality loss.