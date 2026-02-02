# Kreaction Connect

Expose ACF fields via REST API with optimized endpoints, role-based permissions, connected app tracking, and an admin dashboard. Perfect for headless WordPress and mobile app development.

## Features

- **Custom REST endpoints** at `/wp-json/kreaction/v1/`
- **Full ACF support** - All field types including repeaters, flexible content, and galleries
- **Role-based API permissions** - Control which WordPress roles can access the API
- **Connected app tracking** - Monitor which apps access your API, revoke access anytime
- **Server-side caching** - Automatic invalidation when content changes
- **Audit logging** - Track all content changes made through the API
- **Optimized responses** - Up to 97% smaller payloads than standard WordPress REST API

## Requirements

- WordPress 6.0+
- PHP 7.4+
- ACF (Advanced Custom Fields) - Free or Pro

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

| Endpoint | Auth | Description |
|----------|------|-------------|
| `GET /kreaction/v1/version` | No | Plugin version and capabilities |
| `GET /kreaction/v1/health` | No | Health check status |
| `GET /kreaction/v1/me` | Yes | Current user info |
| `GET /kreaction/v1/dashboard` | Yes | Dashboard summary |
| `GET /kreaction/v1/types` | Yes | Post types list |
| `GET /kreaction/v1/posts/{type}` | Yes | Posts list (paginated) |
| `GET /kreaction/v1/post/{id}` | Yes | Single post with ACF fields |
| `POST /kreaction/v1/post/{id}` | Yes | Update post |
| `POST /kreaction/v1/posts/{type}` | Yes | Create post |
| `DELETE /kreaction/v1/post/{id}` | Yes | Delete post |
| `GET /kreaction/v1/media` | Yes | Media library |
| `POST /kreaction/v1/media/upload` | Yes | Upload media |
| `GET /kreaction/v1/fields/{post_type}` | Yes | ACF field definitions |

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

## Filters

```php
// Exclude post types from the API
add_filter('kreaction_excluded_post_types', function($excluded) {
    $excluded[] = 'my_private_type';
    return $excluded;
});
```

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

[Kreaction](https://kreaction.co)
