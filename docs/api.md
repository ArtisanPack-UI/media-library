# API Documentation

The ArtisanPack UI Media Library provides a comprehensive RESTful API for managing media files, categories, and tags.

## Authentication

All protected endpoints require authentication via Laravel Sanctum. Include the bearer token in your requests:

```
Authorization: Bearer {your-token}
```

### Getting an Authentication Token

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "token": "1|abc123def456...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

## Media Items API

### List Media Items

Retrieve a paginated list of media items for the authenticated user.

```http
GET /api/media/items?page=1&per_page=15&category=photos&tag=featured&mime_type=image/jpeg
```

**Query Parameters:**
- `page` (integer): Page number for pagination (default: 1)
- `per_page` (integer): Number of items per page (default: 15, max: 100)
- `category` (string): Filter by category slug
- `tag` (string): Filter by tag slug
- `mime_type` (string): Filter by MIME type
- `search` (string): Search in filename, alt text, and caption

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "filename": "image-2024-08-24-001.jpg",
      "original_filename": "my-photo.jpg",
      "file_path": "media/2024/08/image-2024-08-24-001.jpg",
      "mime_type": "image/jpeg",
      "file_size": 2048576,
      "alt_text": "Beautiful landscape photo",
      "caption": "Sunset over the mountains",
      "is_decorative": false,
      "metadata": {"width": 1920, "height": 1080},
      "user_id": 1,
      "created_at": "2024-08-24T10:00:00.000000Z",
      "updated_at": "2024-08-24T10:00:00.000000Z",
      "media_categories": [
        {
          "id": 1,
          "name": "Photos",
          "slug": "photos",
          "description": "Photography collection"
        }
      ],
      "media_tags": [
        {
          "id": 1,
          "name": "Featured",
          "slug": "featured",
          "description": "Featured content"
        }
      ]
    }
  ],
  "links": {
    "first": "http://example.com/api/media/items?page=1",
    "last": "http://example.com/api/media/items?page=5",
    "prev": null,
    "next": "http://example.com/api/media/items?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://example.com/api/media/items",
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### Upload Media

Upload a new media file with metadata and relationships.

```http
POST /api/media/items
Content-Type: multipart/form-data
Authorization: Bearer {your-token}

file: (binary)
alt_text: "Description of image"
caption: "Optional caption"
is_decorative: false
metadata: {"custom": "data"}
media_categories: [1, 2]
media_tags: [3, 4]
```

**Form Parameters:**
- `file` (file, required): The media file to upload
- `alt_text` (string, required): Alternative text for accessibility
- `caption` (string, optional): Caption for the media
- `is_decorative` (boolean, optional): Whether the image is decorative (default: false)
- `metadata` (json, optional): Additional metadata as JSON object
- `media_categories` (array, optional): Array of category IDs
- `media_tags` (array, optional): Array of tag IDs

**Response:**
```json
{
  "success": true,
  "message": "Media uploaded successfully",
  "data": {
    "id": 2,
    "filename": "image-2024-08-24-002.jpg",
    "original_filename": "new-photo.jpg",
    "file_path": "media/2024/08/image-2024-08-24-002.jpg",
    "mime_type": "image/jpeg",
    "file_size": 1024000,
    "alt_text": "Description of image",
    "caption": "Optional caption",
    "is_decorative": false,
    "metadata": {"custom": "data"},
    "user_id": 1,
    "created_at": "2024-08-24T10:30:00.000000Z",
    "updated_at": "2024-08-24T10:30:00.000000Z",
    "url": "http://example.com/storage/media/2024/08/image-2024-08-24-002.jpg"
  }
}
```

### Get Single Media Item

Retrieve a specific media item by ID.

```http
GET /api/media/items/{id}
Authorization: Bearer {your-token}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "filename": "image-2024-08-24-001.jpg",
    "original_filename": "my-photo.jpg",
    "file_path": "media/2024/08/image-2024-08-24-001.jpg",
    "mime_type": "image/jpeg",
    "file_size": 2048576,
    "alt_text": "Beautiful landscape photo",
    "caption": "Sunset over the mountains",
    "is_decorative": false,
    "metadata": {"width": 1920, "height": 1080},
    "user_id": 1,
    "created_at": "2024-08-24T10:00:00.000000Z",
    "updated_at": "2024-08-24T10:00:00.000000Z",
    "url": "http://example.com/storage/media/2024/08/image-2024-08-24-001.jpg",
    "media_categories": [],
    "media_tags": []
  }
}
```

### Update Media Item

Update an existing media item's metadata and relationships.

```http
PUT /api/media/items/{id}
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "alt_text": "Updated description",
  "caption": "Updated caption",
  "is_decorative": true,
  "metadata": {"updated": "metadata"},
  "media_categories": [1, 3],
  "media_tags": [2, 5]
}
```

**JSON Parameters:**
- `alt_text` (string, optional): Updated alternative text
- `caption` (string, optional): Updated caption
- `is_decorative` (boolean, optional): Updated decorative flag
- `metadata` (object, optional): Updated metadata object
- `media_categories` (array, optional): Array of category IDs (replaces existing)
- `media_tags` (array, optional): Array of tag IDs (replaces existing)

**Response:**
```json
{
  "success": true,
  "message": "Media updated successfully",
  "data": {
    "id": 1,
    "alt_text": "Updated description",
    "caption": "Updated caption",
    "is_decorative": true,
    "metadata": {"updated": "metadata"},
    "updated_at": "2024-08-24T11:00:00.000000Z"
  }
}
```

### Delete Media Item

Delete a media item and its associated file.

```http
DELETE /api/media/items/{id}
Authorization: Bearer {your-token}
```

**Response:**
```json
{
  "success": true,
  "message": "Media deleted successfully"
}
```

## Categories API

### List Categories

Retrieve all media categories.

```http
GET /api/media/categories
Authorization: Bearer {your-token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Photos",
      "slug": "photos",
      "description": "Photography collection",
      "media_count": 25,
      "created_at": "2024-08-24T09:00:00.000000Z",
      "updated_at": "2024-08-24T09:00:00.000000Z"
    }
  ]
}
```

### Create Category

Create a new media category.

```http
POST /api/media/categories
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "name": "Product Photos",
  "slug": "product-photos",
  "description": "Product photography collection"
}
```

**JSON Parameters:**
- `name` (string, required): Category name
- `slug` (string, optional): URL-friendly slug (auto-generated if not provided)
- `description` (string, optional): Category description

**Response:**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 2,
    "name": "Product Photos",
    "slug": "product-photos",
    "description": "Product photography collection",
    "media_count": 0,
    "created_at": "2024-08-24T11:30:00.000000Z",
    "updated_at": "2024-08-24T11:30:00.000000Z"
  }
}
```

### Get Single Category

Retrieve a specific category by ID.

```http
GET /api/media/categories/{id}
Authorization: Bearer {your-token}
```

### Update Category

Update an existing category.

```http
PUT /api/media/categories/{id}
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "name": "Updated Category Name",
  "description": "Updated description"
}
```

### Delete Category

Delete a category (will not delete associated media).

```http
DELETE /api/media/categories/{id}
Authorization: Bearer {your-token}
```

## Tags API

### List Tags

Retrieve all media tags.

```http
GET /api/media/tags
Authorization: Bearer {your-token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Featured",
      "slug": "featured",
      "description": "Featured content",
      "media_count": 12,
      "created_at": "2024-08-24T09:00:00.000000Z",
      "updated_at": "2024-08-24T09:00:00.000000Z"
    }
  ]
}
```

### Create Tag

Create a new media tag.

```http
POST /api/media/tags
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "name": "Summer 2024",
  "slug": "summer-2024",
  "description": "Summer campaign content"
}
```

### Update Tag

Update an existing tag.

```http
PUT /api/media/tags/{id}
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "name": "Updated Tag Name",
  "description": "Updated description"
}
```

### Delete Tag

Delete a tag (will not delete associated media).

```http
DELETE /api/media/tags/{id}
Authorization: Bearer {your-token}
```

## Public Endpoints

Public read-only access (no authentication required) for publicly accessible media:

### Public Media Items

```http
GET /media/items
GET /media/items/{id}
```

### Public Categories

```http
GET /media/categories
GET /media/categories/{id}
```

### Public Tags

```http
GET /media/tags
GET /media/tags/{id}
```

## Bulk Operations

### Bulk Delete Media

Delete multiple media items at once.

```http
DELETE /api/media/items/bulk
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "ids": [1, 2, 3, 4, 5]
}
```

### Bulk Update Categories

Update categories for multiple media items.

```http
PUT /api/media/items/bulk/categories
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "media_ids": [1, 2, 3],
  "category_ids": [4, 5]
}
```

### Bulk Update Tags

Update tags for multiple media items.

```http
PUT /api/media/items/bulk/tags
Content-Type: application/json
Authorization: Bearer {your-token}

{
  "media_ids": [1, 2, 3],
  "tag_ids": [6, 7, 8]
}
```

## Error Responses

The API uses conventional HTTP response codes and returns JSON error responses:

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "alt_text": [
      "The alt text field is required."
    ],
    "file": [
      "The file must be an image.",
      "The file may not be greater than 10240 kilobytes."
    ]
  }
}
```

### Not Found (404)

```json
{
  "success": false,
  "message": "Media not found"
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

### Server Error (500)

```json
{
  "success": false,
  "message": "An error occurred while processing your request."
}
```

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Authenticated requests**: 60 requests per minute
- **Upload endpoints**: 10 requests per minute
- **Public endpoints**: 30 requests per minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1692870060
```

## Response Headers

All API responses include these headers:

```
Content-Type: application/json
X-Media-Library-Version: 1.0.0
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

## JavaScript/Frontend Examples

### Using Fetch API

```javascript
// Upload media
const uploadMedia = async (file, altText, caption) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('alt_text', altText);
  formData.append('caption', caption);
  
  const response = await fetch('/api/media/items', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
    body: formData
  });
  
  return await response.json();
};

// Get media items
const getMediaItems = async (page = 1) => {
  const response = await fetch(`/api/media/items?page=${page}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};

// Update media
const updateMedia = async (id, data) => {
  const response = await fetch(`/api/media/items/${id}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  });
  
  return await response.json();
};
```

### Using Axios

```javascript
// Configure axios defaults
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// Upload media
const uploadMedia = async (file, altText, caption) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('alt_text', altText);
  formData.append('caption', caption);
  
  try {
    const response = await axios.post('/api/media/items', formData);
    return response.data;
  } catch (error) {
    console.error('Upload failed:', error.response.data);
    throw error;
  }
};

// Get media items with filtering
const getMediaItems = async (filters = {}) => {
  try {
    const response = await axios.get('/api/media/items', {
      params: filters
    });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch media:', error.response.data);
    throw error;
  }
};
```

## Next Steps

- Review the [usage guide](usage.md) for implementation examples
- Check [performance guidelines](performance.md) for optimization tips
- See [installation guide](installation.md) for setup instructions