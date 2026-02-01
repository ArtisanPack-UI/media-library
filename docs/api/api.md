---
title: API Overview
---

# API Overview

The Media Library provides a comprehensive REST API for managing media, folders, and tags programmatically. All endpoints require authentication via Laravel Sanctum.

## Base URL

```
/api/media
```

## Authentication

All API endpoints require authentication using Laravel Sanctum:

```http
GET /api/media HTTP/1.1
Host: your-domain.com
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

See [Authentication](Authentication) for details on obtaining and using tokens.

## Endpoints Overview

### Media Endpoints

- `GET /api/media` - List all media (paginated)
- `POST /api/media` - Upload new media
- `GET /api/media/{id}` - Get specific media
- `PUT /api/media/{id}` - Update media metadata
- `DELETE /api/media/{id}` - Delete media

### Folder Endpoints

- `GET /api/media/folders` - List all folders
- `POST /api/media/folders` - Create folder
- `GET /api/media/folders/{id}` - Get specific folder
- `PUT /api/media/folders/{id}` - Update folder
- `DELETE /api/media/folders/{id}` - Delete folder
- `POST /api/media/folders/{id}/move` - Move folder

### Tag Endpoints

- `GET /api/media/tags` - List all tags
- `POST /api/media/tags` - Create tag
- `GET /api/media/tags/{id}` - Get specific tag
- `PUT /api/media/tags/{id}` - Update tag
- `DELETE /api/media/tags/{id}` - Delete tag
- `POST /api/media/tags/{id}/attach` - Attach tag to media
- `POST /api/media/tags/{id}/detach` - Detach tag from media

## Response Format

All API responses follow a consistent JSON structure:

### Success Response

```json
{
    "data": {
        "id": 1,
        "title": "Example Media",
        "file_name": "example.jpg",
        ...
    }
}
```

### Collection Response

```json
{
    "data": [
        { "id": 1, ... },
        { "id": 2, ... }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "to": 15,
        "total": 73
    }
}
```

### Error Response

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": [
            "The field is required."
        ]
    }
}
```

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **60 requests per minute** for authenticated users
- **30 requests per minute** for guest users (where applicable)

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640000000
```

## Pagination

List endpoints support pagination with these parameters:

- `per_page` - Items per page (default: 15, max: 100)
- `page` - Page number (default: 1)

Example:
```
GET /api/media?per_page=20&page=2
```

## Filtering & Sorting

Many endpoints support filtering and sorting. See [Endpoints Reference](Endpoints) for details.

## Next Steps

- Review [Endpoints Reference](Endpoints) for detailed API documentation
- Learn about [Authentication](Authentication)
- See example implementations in [Integration Guide](Integration-Cms-Module)
