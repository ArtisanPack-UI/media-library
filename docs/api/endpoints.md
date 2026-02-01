---
title: API Endpoints Reference
---

# API Endpoints Reference

Complete reference for all Media Library API endpoints with examples.

## Media Endpoints

### List Media

`GET /api/media`

List all media with filtering, sorting, and pagination.

**Query Parameters:**
- `per_page` (int) - Items per page (default: 15, max: 100)
- `page` (int) - Page number (default: 1)
- `folder_id` (int) - Filter by folder ID
- `tag` (string) - Filter by tag slug
- `type` (string) - Filter by type: `image`, `video`, `audio`, `document`
- `search` (string) - Search in title and filename
- `sort_by` (string) - Sort column (default: `created_at`)
- `sort_order` (string) - Sort direction: `asc` or `desc` (default: `desc`)

**Example:**
```bash
curl -X GET "https://example.com/api/media?type=image&folder_id=1&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Product Photo",
            "file_name": "product.jpg",
            "mime_type": "image/jpeg",
            "file_size": 245678,
            "folder_id": 1,
            "url": "https://example.com/storage/media/product.jpg",
            "thumbnail_url": "https://example.com/storage/media/product-thumbnail.jpg",
            "created_at": "2025-01-15T10:30:00.000000Z"
        }
    ],
    "meta": { ... }
}
```

### Upload Media

`POST /api/media`

Upload a new media file.

**Request (multipart/form-data):**
```bash
curl -X POST "https://example.com/api/media" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "title=Product Photo" \
  -F "alt_text=Red sneakers" \
  -F "folder_id=1" \
  -F "tags[]=featured" \
  -F "tags[]=products"
```

**Parameters:**
- `file` (required) - The file to upload
- `title` (optional) - Media title
- `alt_text` (optional) - Alternative text
- `caption` (optional) - Caption
- `description` (optional) - Description
- `folder_id` (optional) - Folder ID
- `tags` (optional) - Array of tag slugs

**Response (201):**
```json
{
    "data": {
        "id": 123,
        "title": "Product Photo",
        "file_name": "product.jpg",
        ...
    }
}
```

### Get Media

`GET /api/media/{id}`

Get a specific media item.

**Example:**
```bash
curl -X GET "https://example.com/api/media/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
    "data": {
        "id": 123,
        "title": "Product Photo",
        "file_name": "product.jpg",
        "mime_type": "image/jpeg",
        "file_size": 245678,
        "folder": {
            "id": 1,
            "name": "Products"
        },
        "tags": [
            {"id": 1, "name": "Featured", "slug": "featured"}
        ],
        "uploaded_by": {
            "id": 5,
            "name": "John Doe"
        }
    }
}
```

### Update Media

`PUT /api/media/{id}`

Update media metadata.

**Request:**
```bash
curl -X PUT "https://example.com/api/media/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "alt_text": "Updated alt text",
    "folder_id": 2,
    "tags": [1, 3, 5]
  }'
```

**Parameters:**
- `title` (optional) - Media title
- `alt_text` (optional) - Alternative text
- `caption` (optional) - Caption
- `description` (optional) - Description
- `folder_id` (optional) - Folder ID
- `tags` (optional) - Array of tag IDs

**Response:**
```json
{
    "data": {
        "id": 123,
        "title": "Updated Title",
        ...
    }
}
```

### Delete Media

`DELETE /api/media/{id}`

Delete a media item (soft delete).

**Example:**
```bash
curl -X DELETE "https://example.com/api/media/123" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response (204):** No content

## Folder Endpoints

### List Folders

`GET /api/media/folders`

List all folders with hierarchy.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Products",
            "slug": "products",
            "parent_id": null,
            "children": [
                {
                    "id": 2,
                    "name": "Electronics",
                    "parent_id": 1
                }
            ]
        }
    ]
}
```

### Create Folder

`POST /api/media/folders`

Create a new folder.

**Request:**
```bash
curl -X POST "https://example.com/api/media/folders" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Folder",
    "description": "Folder description",
    "parent_id": null
  }'
```

**Response (201):**
```json
{
    "data": {
        "id": 10,
        "name": "New Folder",
        "slug": "new-folder",
        ...
    },
    "message": "Folder created successfully"
}
```

### Move Folder

`POST /api/media/folders/{id}/move`

Move a folder to a new parent.

**Request:**
```bash
curl -X POST "https://example.com/api/media/folders/5/move" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"parent_id": 3}'
```

## Tag Endpoints

### List Tags

`GET /api/media/tags`

List all tags with media counts.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Featured",
            "slug": "featured",
            "media_count": 15
        }
    ]
}
```

### Attach Tag to Media

`POST /api/media/tags/{id}/attach`

Attach a tag to multiple media items.

**Request:**
```bash
curl -X POST "https://example.com/api/media/tags/1/attach" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"media_ids": [1, 2, 3]}'
```

**Response:**
```json
{
    "message": "Tag attached to media successfully"
}
```

## Next Steps

- Learn about [Authentication](Authentication)
- Review [Integration Guide](Integration-Cms-Module)
- See [Helper Functions](Usage-Helper-Functions) for PHP usage
