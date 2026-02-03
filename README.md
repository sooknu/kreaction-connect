# Kreaction Connect

Expose ACF fields via REST API with optimized endpoints, role-based permissions, connected app tracking, and an admin dashboard. Perfect for headless WordPress and mobile app development.

## Features

- **Custom REST endpoints** at `/wp-json/kreaction/v1/`
- **Full ACF support** - All field types including repeaters, flexible content, and galleries
- **Native WordPress content** - Full support for Posts, Pages, and custom post types
- **Native taxonomy support** - Categories, Tags, and custom taxonomies with read/write access
- **Role-based API permissions** - Control which WordPress roles can access the API
- **Connected app tracking** - Monitor which apps access your API, revoke access anytime
- **Server-side caching** - Automatic invalidation when content changes
- **Audit logging** - Track all content changes made through the API
- **Scheduled publishing** - Support for scheduling posts via date parameter
- **Optimized responses** - Up to 97% smaller payloads than standard WordPress REST API

## Requirements

- WordPress 6.0+
- PHP 7.4+
- ACF (Advanced Custom Fields) - Free or Pro (optional, for ACF field support)

## Installation

1. Download the latest release
2. Upload to `/wp-content/plugins/`
3. Activate the plugin
4. Go to **Settings > Kreaction Connect** to configure

## Authentication

All endpoints (except `/version` and `/health`) require authentication via WordPress Application Passwords:

1. Go to **Users > Your Profile**
2. Scroll to "Application Passwords"
3. Enter a name and click "Add New"
4. Use Basic Auth with your username and the generated password

## Endpoints

### Core Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/version` | GET | No | Plugin version and capabilities |
| `/kreaction/v1/health` | GET | No | Health check status |
| `/kreaction/v1/me` | GET | Yes | Current user info with roles/capabilities |
| `/kreaction/v1/dashboard` | GET | Yes | Dashboard summary (types, counts, recent) |

### Content Types

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/types` | GET | Yes | Post types list with taxonomy info |
| `/kreaction/v1/posts/{type}` | GET | Yes | Posts list (paginated) |
| `/kreaction/v1/posts/{type}` | POST | Yes | Create new post |
| `/kreaction/v1/post/{id}` | GET | Yes | Single post with ACF fields & taxonomies |
| `/kreaction/v1/post/{id}` | POST | Yes | Update post (content, ACF fields, taxonomies) |
| `/kreaction/v1/post/{id}` | DELETE | Yes | Delete post |

### ACF Fields

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/fields/{post_type}` | GET | Yes | ACF field definitions with choices |
| `/kreaction/v1/hidden-fields/{post_type}` | GET | Yes | Fields marked as hidden |

### Taxonomies

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/terms/{taxonomy}` | GET | Yes | Get terms for a taxonomy |

### Media

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/media` | GET | Yes | Media library (paginated) |
| `/kreaction/v1/media/{id}` | GET | Yes | Single media item |
| `/kreaction/v1/media/upload` | POST | Yes | Upload media file |
| `/kreaction/v1/media/{id}` | DELETE | Yes | Delete media |

### Lookup Endpoints (for ACF relationship/user fields)

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/kreaction/v1/search/{type}` | GET | Yes | Search posts by type |
| `/kreaction/v1/users` | GET | Yes | Get users list |

## Native Taxonomy Support

Post types now include their registered taxonomies:

```json
{
  "slug": "post",
  "name": "Posts",
  "taxonomies": [
    {"slug": "category", "name": "Category", "hierarchical": true},
    {"slug": "post_tag", "name": "Tag", "hierarchical": false}
  ]
}
```

Posts include assigned taxonomy terms:

```json
{
  "id": 123,
  "title": "My Post",
  "taxonomies": {
    "category": [
      {"id": 5, "name": "News", "slug": "news"}
    ],
    "post_tag": [
      {"id": 12, "name": "Featured", "slug": "featured"}
    ]
  }
}
```

Update taxonomies when saving:

```json
{
  "title": "Updated Post",
  "taxonomies": {
    "category": [5, 8],
    "post_tag": [12, 15, 20]
  }
}
```

## Scheduled Publishing

Set post date for scheduled publishing:

```json
{
  "title": "Future Post",
  "status": "future",
  "date": "2025-03-15T10:00:00"
}
```

## Admin Settings

Access at **Settings > Kreaction Connect**:

- **General** - Cache toggle, audit log, cache expiry
- **Connected Apps** - View and revoke app access
- **Health Check** - Test endpoints, view system info
- **Permissions** - Control API access by role

## Hiding Fields

Control which ACF fields appear in the API:

1. Add `hide-in-app` to the field's Wrapper Attributes > Class
2. Or add `[hide_in_app]` in the field's Instructions

## Role-Based Content Visibility

Control which content types each user role can see in the app. Configure this in **Settings > Kreaction Connect > Content Visibility**.

Features:
- Matrix UI showing content types vs user roles
- Checkboxes to enable/disable access per role
- Administrator role always has full access (cannot be restricted)
- Unconfigured content types are visible to all allowed roles by default

This is useful when handing off the app to customers - configure their role to only see relevant content types without requiring them to manually hide types in the app.

## Filters

```php
// Exclude post types from the API
add_filter('kreaction_excluded_post_types', function($excluded) {
    $excluded[] = 'my_private_type';
    return $excluded;
});
```

## Changelog

### 1.2.0
- Added role-based content visibility settings
- New "Content Visibility" tab in admin settings
- Configure which content types each user role can access
- Administrator role always has full access
- Unconfigured types visible to all allowed roles (backward compatible)

### 1.1.0
- Added native WordPress taxonomy support (Categories, Tags, custom taxonomies)
- Added support for native Posts and Pages content types
- Added scheduled publishing via date parameter
- Added user roles and capabilities to `/me` endpoint
- Improved empty taxonomy handling for JSON decoding compatibility

### 1.0.0
- Initial release

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

[Kreaction](https://kreaction.co)
