---
title: API
---

# API

The Media Library provides a comprehensive REST API for managing media, folders, and tags programmatically. All endpoints require authentication via Laravel Sanctum.

## API Documentation

### [API Overview](./api/api.md)

Introduction to the REST API:
- Base URL and endpoints
- Authentication requirements
- Response format (success, collections, errors)
- Rate limiting
- Pagination
- Filtering and sorting

### [Endpoints Reference](./api/endpoints.md)

Complete reference for all API endpoints:

**Media Endpoints:**
- `GET /api/media` - List media with filtering
- `POST /api/media` - Upload new media
- `GET /api/media/{id}` - Get specific media
- `PUT /api/media/{id}` - Update metadata
- `DELETE /api/media/{id}` - Delete media

**Folder Endpoints:**
- `GET /api/media/folders` - List folders
- `POST /api/media/folders` - Create folder
- `GET /api/media/folders/{id}` - Get folder
- `PUT /api/media/folders/{id}` - Update folder
- `DELETE /api/media/folders/{id}` - Delete folder
- `POST /api/media/folders/{id}/move` - Move folder

**Tag Endpoints:**
- `GET /api/media/tags` - List tags
- `POST /api/media/tags` - Create tag
- `PUT /api/media/tags/{id}` - Update tag
- `DELETE /api/media/tags/{id}` - Delete tag
- `POST /api/media/tags/{id}/attach` - Attach to media
- `POST /api/media/tags/{id}/detach` - Detach from media

### [Authentication](./api/authentication.md)

API authentication with Laravel Sanctum:
- Generating tokens
- Using tokens in requests
- Token abilities and permissions
- Revoking tokens
- Configuration

## Quick Example

### Upload via API

```bash
curl -X POST "https://example.com/api/media" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "title=Product Photo" \
  -F "folder_id=1"
```

### List Media

```bash
curl -X GET "https://example.com/api/media?type=image&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Response Format

```json
{
    "data": [
        {
            "id": 1,
            "title": "Product Photo",
            "file_name": "product.jpg",
            "url": "https://example.com/storage/media/product.jpg",
            "thumbnail_url": "https://example.com/storage/media/product-thumbnail.jpg"
        }
    ],
    "meta": {
        "current_page": 1,
        "total": 73
    }
}
```

## Rate Limits

- **60 requests per minute** for authenticated users
- **30 requests per minute** for guests

## Next Steps

- Review [Authentication](./api/authentication.md) setup
- See complete [Endpoints Reference](./api/endpoints.md)
- Learn about [Helper Functions](./usage.md) for PHP usage
