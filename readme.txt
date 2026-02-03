=== Kreaction Connect ===
Contributors: kreaction
Donate link: https://kreaction.co
Tags: acf, rest-api, headless, api, custom-fields
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Expose ACF fields via REST API with optimized endpoints, role-based permissions, and connected app tracking. Perfect for headless WordPress.

== Description ==

Kreaction Connect extends the WordPress REST API to properly expose Advanced Custom Fields (ACF) data. It provides clean, flat JSON responses optimized for mobile apps, JavaScript frameworks, and headless WordPress setups.

**Why Kreaction Connect?**

The standard WordPress REST API doesn't include ACF field data by default. When you do expose it, responses are often bloated with unnecessary metadata. Kreaction Connect solves this by providing:

* **Flat, optimized responses** - Up to 97% smaller payloads
* **Full ACF support** - All field types including repeaters, flexible content, and galleries
* **Relationship resolution** - Get readable titles instead of just IDs
* **Image size URLs** - All registered sizes included automatically
* **Admin dashboard** - Manage permissions and monitor API usage

**Key Features:**

* Custom REST endpoints at `/wp-json/kreaction/v1/`
* Role-based API access control
* Connected app tracking and management
* Server-side caching with automatic invalidation
* Audit logging of all content changes
* Field validation before saving
* Batch operations (up to 50 per request)
* Direct media upload endpoint
* Cursor-based pagination for large datasets
* Health check and system status endpoints

**Use Cases:**

* Headless WordPress with React, Vue, or Next.js
* Mobile app backends (iOS, Android, React Native)
* Static site generators (Gatsby, Eleventy, Hugo)
* Third-party integrations and automations
* Custom admin interfaces and dashboards

**Endpoints:**

*Public (no auth required):*

* `GET /kreaction/v1/version` - Plugin version and capabilities
* `GET /kreaction/v1/health` - Health check status

*Authenticated:*

* `GET /kreaction/v1/me` - Current user info
* `GET /kreaction/v1/dashboard` - Dashboard summary
* `GET /kreaction/v1/types` - Post types list
* `GET /kreaction/v1/posts/{type}` - Posts list (paginated)
* `GET /kreaction/v1/post/{id}` - Single post with ACF fields
* `POST /kreaction/v1/post/{id}` - Update post
* `POST /kreaction/v1/posts/{type}` - Create post
* `DELETE /kreaction/v1/post/{id}` - Delete post
* `GET /kreaction/v1/media` - Media library
* `POST /kreaction/v1/media/upload` - Upload media
* `GET /kreaction/v1/fields/{post_type}` - ACF field definitions
* `GET /kreaction/v1/schema` - Full ACF schema
* `POST /kreaction/v1/batch` - Batch operations

**Hiding Fields:**

Control which ACF fields appear in the API:

1. Add `hide-in-app` to the field's Wrapper Attributes > Class
2. Or add `[hide_in_app]` anywhere in the field's Instructions

== Installation ==

1. Upload the `kreaction-connect` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Kreaction Connect to configure permissions
4. Create an Application Password for API access (Users > Your Profile)

== Frequently Asked Questions ==

= Does this require ACF Pro? =

The plugin works with both ACF Free and ACF Pro. Some advanced field types (repeater, flexible content) require ACF Pro.

= How do I authenticate API requests? =

Use WordPress Application Passwords:

1. Go to Users > Your Profile
2. Scroll to "Application Passwords"
3. Enter a name and click "Add New"
4. Use Basic Auth with your username and the generated password

= Is this secure? =

Yes. All authenticated endpoints require Application Passwords and respect WordPress capabilities. The admin panel lets you control which user roles can access the API.

= Can I restrict API access to specific roles? =

Yes. Go to Settings > Kreaction Connect > Permissions tab to select which WordPress roles can access the API. Administrators always have access.

= How do I monitor API usage? =

The Connected Apps tab shows all applications that have accessed your API, including last access time, IP address, and request count. You can revoke access directly from this screen.

= How does caching work? =

The plugin caches expensive queries using WordPress transients. Caches are automatically invalidated when content changes. You can manually clear caches from Settings > Kreaction Connect.

= What is cursor-based pagination? =

Cursor pagination is more efficient than page-based pagination for large datasets. Instead of page numbers, you receive a `next_cursor` token to fetch the next batch.

= Can I customize which post types appear? =

Yes. Use the `kreaction_excluded_post_types` filter:

`add_filter('kreaction_excluded_post_types', function($excluded) {
    $excluded[] = 'my_private_type';
    return $excluded;
});`

== Screenshots ==

1. Settings page - General tab with cache and audit options
2. Connected Apps - Monitor which apps access your API
3. Health Check - Test endpoints and view system info
4. Permissions - Control API access by user role

== Changelog ==

= 1.0.1 =
* Initial public release
* Custom REST endpoints for posts, media, and ACF fields
* Admin settings page with tabbed interface
* Connected app tracking and revocation
* Role-based API permissions
* Health check and system status
* Server-side caching with automatic invalidation
* Audit logging of content changes
* Batch operations support
* Field validation before saving
* Cursor-based pagination
* Settings link in plugins list

== Upgrade Notice ==

= 1.0.1 =
Initial public release.
