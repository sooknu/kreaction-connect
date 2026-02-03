<?php
/**
 * Kreaction Connect REST API Endpoints
 *
 * Provides optimized REST endpoints for the Kreaction iOS app.
 * All endpoints require authentication via Application Password.
 *
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all REST API routes
 */
add_action('rest_api_init', function() {
    $namespace = 'kreaction/v1';

    // === SYSTEM ENDPOINTS ===

    // GET /kreaction/v1/version - Get plugin version and capabilities
    register_rest_route($namespace, '/version', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_version',
        'permission_callback' => '__return_true', // Public endpoint
    ]);

    // GET /kreaction/v1/health - Health check endpoint
    register_rest_route($namespace, '/health', [
        'methods' => 'GET',
        'callback' => 'kreaction_health_check',
        'permission_callback' => '__return_true', // Public endpoint
    ]);

    // GET /kreaction/v1/me - Get current authenticated user
    register_rest_route($namespace, '/me', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_current_user',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // === DASHBOARD & TYPES ===

    // GET /kreaction/v1/dashboard - Get dashboard summary data
    register_rest_route($namespace, '/dashboard', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_dashboard',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // GET /kreaction/v1/types - Get all registered post types
    register_rest_route($namespace, '/types', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_post_types',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // GET /kreaction/v1/hidden-types - Get post types to hide from the app
    register_rest_route($namespace, '/hidden-types', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_hidden_types',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // === POSTS ===

    // GET /kreaction/v1/posts/{type} - Get paginated posts list
    register_rest_route($namespace, '/posts/(?P<type>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_posts',
        'permission_callback' => 'kreaction_check_auth',
        'args' => kreaction_get_posts_args(),
    ]);

    // POST /kreaction/v1/posts/{type} - Create new post
    register_rest_route($namespace, '/posts/(?P<type>[a-zA-Z0-9_-]+)', [
        'methods' => 'POST',
        'callback' => 'kreaction_create_post',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'type' => ['required' => true, 'type' => 'string'],
        ],
    ]);

    // GET /kreaction/v1/post/{id} - Get single post
    register_rest_route($namespace, '/post/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_handle_post',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer', 'minimum' => 1],
            'fields' => ['type' => 'string', 'description' => 'Comma-separated list of fields to include'],
        ],
    ]);

    // POST /kreaction/v1/post/{id} - Update single post
    register_rest_route($namespace, '/post/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'kreaction_handle_post',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer', 'minimum' => 1],
        ],
    ]);

    // DELETE /kreaction/v1/post/{id} - Delete post
    register_rest_route($namespace, '/post/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'kreaction_delete_post',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer'],
            'force' => ['default' => false, 'type' => 'boolean'],
        ],
    ]);

    // === BATCH OPERATIONS ===

    // POST /kreaction/v1/batch - Batch operations
    register_rest_route($namespace, '/batch', [
        'methods' => 'POST',
        'callback' => 'kreaction_batch_operations',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // === MEDIA ===

    // GET /kreaction/v1/media - Get media library
    register_rest_route($namespace, '/media', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_media',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'page' => ['default' => 1, 'type' => 'integer'],
            'per_page' => ['default' => 20, 'type' => 'integer'],
            'media_type' => ['default' => 'image', 'type' => 'string'],
            'cursor' => ['type' => 'string', 'description' => 'Cursor for pagination'],
        ],
    ]);

    // POST /kreaction/v1/media/upload - Upload media
    register_rest_route($namespace, '/media/upload', [
        'methods' => 'POST',
        'callback' => 'kreaction_upload_media',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // GET /kreaction/v1/media/{id}/optimized - Get optimized image URL
    register_rest_route($namespace, '/media/(?P<id>\d+)/optimized', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_optimized_image',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer'],
            'size' => ['default' => 'medium', 'type' => 'string'],
            'width' => ['type' => 'integer'],
            'height' => ['type' => 'integer'],
        ],
    ]);

    // DELETE /kreaction/v1/media/{id} - Delete media
    register_rest_route($namespace, '/media/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'kreaction_delete_media',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer'],
            'force' => ['default' => true, 'type' => 'boolean'],
        ],
    ]);

    // === SCHEMA & FIELDS ===

    // GET /kreaction/v1/schema - Get full ACF schema for all post types
    register_rest_route($namespace, '/schema', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_schema',
        'permission_callback' => 'kreaction_check_auth',
    ]);

    // GET /kreaction/v1/fields/{post_type} - Get ACF field definitions for a post type
    register_rest_route($namespace, '/fields/(?P<post_type>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_fields_for_post_type',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'post_type' => ['required' => true, 'type' => 'string'],
        ],
    ]);

    // === LOOKUPS (for ACF fields) ===

    // GET /kreaction/v1/search/{type} - Search posts by type (for relationship/post_object fields)
    register_rest_route($namespace, '/search/(?P<type>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_search_posts',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'type' => ['required' => true, 'type' => 'string'],
            'search' => ['type' => 'string', 'description' => 'Search query'],
            'per_page' => ['default' => 20, 'type' => 'integer', 'maximum' => 100],
            'exclude' => ['type' => 'string', 'description' => 'Comma-separated IDs to exclude'],
        ],
    ]);

    // GET /kreaction/v1/terms/{taxonomy} - Get taxonomy terms (for taxonomy fields)
    register_rest_route($namespace, '/terms/(?P<taxonomy>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_terms',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'taxonomy' => ['required' => true, 'type' => 'string'],
            'search' => ['type' => 'string', 'description' => 'Search query'],
            'per_page' => ['default' => 100, 'type' => 'integer', 'maximum' => 500],
            'hide_empty' => ['default' => false, 'type' => 'boolean'],
        ],
    ]);

    // GET /kreaction/v1/users - Get users list (for user fields)
    register_rest_route($namespace, '/users', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_users',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'search' => ['type' => 'string', 'description' => 'Search query'],
            'per_page' => ['default' => 50, 'type' => 'integer', 'maximum' => 100],
            'role' => ['type' => 'string', 'description' => 'Filter by role'],
        ],
    ]);

    // GET /kreaction/v1/media/{id} - Get single media item
    register_rest_route($namespace, '/media/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_single_media',
        'permission_callback' => 'kreaction_check_auth',
        'args' => [
            'id' => ['required' => true, 'type' => 'integer'],
        ],
    ]);

    // === AUDIT LOG ===

    // GET /kreaction/v1/audit-log - Get audit log entries
    register_rest_route($namespace, '/audit-log', [
        'methods' => 'GET',
        'callback' => 'kreaction_get_audit_log',
        'permission_callback' => 'kreaction_check_admin',
        'args' => [
            'page' => ['default' => 1, 'type' => 'integer'],
            'per_page' => ['default' => 50, 'type' => 'integer'],
            'user_id' => ['type' => 'integer'],
            'action' => ['type' => 'string'],
            'object_type' => ['type' => 'string'],
        ],
    ]);

    // === CACHE MANAGEMENT ===

    // POST /kreaction/v1/cache/clear - Clear caches
    register_rest_route($namespace, '/cache/clear', [
        'methods' => 'POST',
        'callback' => 'kreaction_clear_cache',
        'permission_callback' => 'kreaction_check_admin',
    ]);
});

// =============================================================================
// PERMISSION CALLBACKS
// =============================================================================

/**
 * Check if user is authenticated and has edit capability
 */
function kreaction_check_auth() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();

    // Check role-based permissions if admin class is loaded
    if (class_exists('Kreaction_Admin')) {
        // Check if user's role is allowed
        if (!Kreaction_Admin::is_user_role_allowed($user)) {
            return false;
        }

        // Check required capability
        if (!Kreaction_Admin::user_has_required_capability($user)) {
            return false;
        }
    } else {
        // Fallback to default capability check
        if (!current_user_can('edit_posts')) {
            return false;
        }
    }

    // Track API access (non-blocking)
    if (class_exists('Kreaction_App_Tracker')) {
        Kreaction_App_Tracker::track_access($user->ID);
    }

    return true;
}

/**
 * Check if user is an administrator
 */
function kreaction_check_admin() {
    return is_user_logged_in() && current_user_can('manage_options');
}

// =============================================================================
// SYSTEM ENDPOINTS
// =============================================================================

/**
 * GET /version - Return plugin version and capabilities
 */
function kreaction_get_version() {
    return [
        'version' => KREACTION_CONNECT_VERSION,
        'wordpress' => get_bloginfo('version'),
        'php' => PHP_VERSION,
        'acf' => function_exists('acf') ? acf()->version : null,
        'capabilities' => [
            'batch_operations' => true,
            'media_upload' => true,
            'image_optimization' => true,
            'cursor_pagination' => true,
            'selective_fields' => true,
            'field_validation' => true,
            'audit_log' => class_exists('Kreaction_Audit_Log'),
            'caching' => class_exists('Kreaction_Cache'),
        ],
        'endpoints' => [
            'version', 'health', 'me', 'dashboard', 'types',
            'posts', 'post', 'batch', 'media', 'media/upload',
            'schema', 'fields', 'search', 'terms', 'users',
            'audit-log', 'cache/clear',
        ],
    ];
}

/**
 * GET /health - Health check endpoint
 */
function kreaction_health_check() {
    $checks = [
        'status' => 'ok',
        'timestamp' => current_time('c'),
        'checks' => [],
    ];

    // Database check
    global $wpdb;
    $db_ok = $wpdb->get_var("SELECT 1") === '1';
    $checks['checks']['database'] = $db_ok ? 'ok' : 'error';

    // ACF check
    $acf_ok = function_exists('acf_get_field_groups');
    $checks['checks']['acf'] = $acf_ok ? 'ok' : 'not_installed';

    // Cache check
    $cache_ok = class_exists('Kreaction_Cache');
    $checks['checks']['cache'] = $cache_ok ? 'ok' : 'not_available';

    // Upload directory check
    $upload_dir = wp_upload_dir();
    $uploads_ok = empty($upload_dir['error']) && is_writable($upload_dir['basedir']);
    $checks['checks']['uploads'] = $uploads_ok ? 'ok' : 'error';

    // Overall status
    $has_errors = in_array('error', $checks['checks']);
    $checks['status'] = $has_errors ? 'degraded' : 'ok';

    $status_code = $has_errors ? 503 : 200;

    return new WP_REST_Response($checks, $status_code);
}

/**
 * GET /me - Return current user info
 */
function kreaction_get_current_user() {
    $user = wp_get_current_user();

    return [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'avatar' => get_avatar_url($user->ID, ['size' => 192]),
        'roles' => $user->roles,
        'capabilities' => [
            'can_edit_posts' => current_user_can('edit_posts'),
            'can_publish_posts' => current_user_can('publish_posts'),
            'can_delete_posts' => current_user_can('delete_posts'),
            'can_upload_files' => current_user_can('upload_files'),
            'can_manage_options' => current_user_can('manage_options'),
        ],
    ];
}

// =============================================================================
// DASHBOARD & TYPES
// =============================================================================

/**
 * GET /dashboard - Return dashboard summary
 */
function kreaction_get_dashboard() {
    return Kreaction_Cache::remember('dashboard', function() {
        $types = kreaction_get_post_types_array();

        $total = 0;
        foreach ($types as $type) {
            $total += $type['count'];
        }

        $type_slugs = array_column($types, 'slug');
        $recent_posts = get_posts([
            'post_type' => $type_slugs,
            'posts_per_page' => 10,
            'orderby' => 'modified',
            'order' => 'DESC',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
        ]);

        $recent = array_map(function($post) {
            $modified = get_post_modified_time('c', true, $post);
            return [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'type' => $post->post_type,
                'status' => $post->post_status,
                'modified' => $modified ?: get_the_date('c', $post), // Fall back to date if no modified time
            ];
        }, $recent_posts);

        return [
            'types' => $types,
            'total_posts' => $total,
            'recent' => $recent,
        ];
    });
}

/**
 * GET /types - Return all registered post types
 */
function kreaction_get_post_types() {
    return Kreaction_Cache::remember('post_types', function() {
        return kreaction_get_post_types_array();
    });
}

/**
 * GET /hidden-types - Return post types that should be hidden from the app
 *
 * Developers can use the 'kreaction_hidden_post_types' filter to hide post types:
 *
 * add_filter('kreaction_hidden_post_types', function($types) {
 *     $types[] = 'my_templates';
 *     $types[] = 'bricks_template';
 *     return $types;
 * });
 */
function kreaction_get_hidden_types() {
    return Kreaction_Cache::remember('hidden_post_types', function() {
        // Default hidden types (template builders, etc.)
        $hidden = [
            // Bricks Builder
            'bricks_template',
            // My Templates (common custom name)
            'my_templates',
            'my-templates',
        ];

        // Allow developers to add their own hidden types
        $hidden = apply_filters('kreaction_hidden_post_types', $hidden);

        return array_values(array_unique($hidden));
    });
}

/**
 * Helper: Get post types as array
 */
function kreaction_get_post_types_array() {
    // Post types to exclude
    $exclude = [
        // Core WordPress types
        'post', 'page',
        // WordPress internals
        'attachment', 'revision', 'nav_menu_item', 'custom_css',
        'customize_changeset', 'oembed_cache', 'user_request',
        'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
        'wp_navigation', 'wp_font_family', 'wp_font_face', 'wp_pattern',
        // Plugin-generated types
        'acf-field-group', 'acf-field', 'acf-post-type', 'acf-taxonomy',
        'acf-ui-options-page', 'rank_math_schema', 'legal-page',
        // Elementor
        'elementor_library', 'elementor_snippet', 'elementor_font',
        'elementor_icons', 'e-landing-page', 'e-floating-buttons',
        // Divi
        'et_pb_layout', 'et_theme_builder', 'et_template', 'et_header_layout',
        'et_body_layout', 'et_footer_layout', 'et_code_snippet',
        // Beaver Builder
        'fl-builder-template', 'fl-theme-layout',
        // Brizy
        'brizy_template', 'brizy-global-blocks',
        // Bricks Builder
        'bricks_template', 'bricks-template',
        // OceanWP / Astra
        'oceanwp_library', 'astra-advanced-hook',
        // JetEngine / JetThemeCore
        'jet-theme-core', 'jet-menu', 'jet-popup', 'jet-engine', 'jet-smart-filters',
        // Oxygen
        'oxy_user_library', 'ct_template',
        // Kadence
        'kadence_element', 'kadence_form', 'kadence_conversions',
        // GenerateBlocks / GeneratePress
        'gp_elements', 'generateblocks',
        // Stackable
        'stackable_template',
        // Spectra / UAG
        'uagb-template',
        // WPBakery
        'vc_grid_item', 'templatera',
        // Thrive
        'tcb_lightbox', 'tve_form_type', 'tve_lead_shortcode',
        // Redux
        'redux_templates',
        // WooCommerce internal
        'shop_order', 'shop_coupon', 'shop_order_refund',
    ];

    $exclude = apply_filters('kreaction_excluded_post_types', $exclude);

    // Patterns to exclude (if slug contains these)
    $exclude_patterns = [
        '_template', '-template', 'template_',
        '_layout', '-layout', 'layout_',
        '_snippet', '-snippet',
        '_library', '-library',
    ];
    $exclude_patterns = apply_filters('kreaction_excluded_post_type_patterns', $exclude_patterns);

    // Also check the hidden types filter
    $hidden_types = apply_filters('kreaction_hidden_post_types', []);

    $types = get_post_types(['public' => true], 'objects');
    $result = [];

    foreach ($types as $type) {
        // Skip if in exact exclude list
        if (in_array($type->name, $exclude)) {
            continue;
        }

        // Skip if in hidden types list
        if (in_array($type->name, $hidden_types)) {
            continue;
        }

        // Skip if matches any exclude pattern
        $slug_lower = strtolower($type->name);
        $skip = false;
        foreach ($exclude_patterns as $pattern) {
            if (strpos($slug_lower, $pattern) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }

        $counts = wp_count_posts($type->name);
        $total = 0;
        $statuses = ['publish', 'draft', 'pending', 'private', 'future'];
        foreach ($statuses as $status) {
            if (isset($counts->$status)) {
                $total += (int)$counts->$status;
            }
        }

        $result[] = [
            'slug' => $type->name,
            'name' => $type->labels->name,
            'singular' => $type->labels->singular_name,
            'rest_base' => $type->rest_base ?: $type->name,
            'count' => $total,
            'hierarchical' => $type->hierarchical,
        ];
    }

    usort($result, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    return $result;
}

// =============================================================================
// POSTS
// =============================================================================

/**
 * Arguments for GET /posts endpoint
 */
function kreaction_get_posts_args() {
    return [
        'type' => [
            'required' => true,
            'type' => 'string',
            'description' => 'Post type slug or rest_base',
        ],
        'page' => [
            'default' => 1,
            'type' => 'integer',
            'minimum' => 1,
        ],
        'per_page' => [
            'default' => 20,
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => 100,
        ],
        'status' => [
            'default' => 'any',
            'type' => 'string',
        ],
        'search' => [
            'type' => 'string',
        ],
        'cursor' => [
            'type' => 'string',
            'description' => 'Cursor for pagination (base64 encoded)',
        ],
        'orderby' => [
            'default' => 'date',
            'type' => 'string',
            'enum' => ['date', 'modified', 'title', 'id'],
        ],
        'order' => [
            'default' => 'DESC',
            'type' => 'string',
            'enum' => ['ASC', 'DESC'],
        ],
        'fields' => [
            'type' => 'string',
            'description' => 'Comma-separated list of ACF fields to include',
        ],
    ];
}

/**
 * GET /posts/{type} - Return paginated posts list
 */
function kreaction_get_posts($request) {
    $type = sanitize_text_field($request['type']);
    $per_page = (int)$request->get_param('per_page') ?: 20;
    $status = sanitize_text_field($request->get_param('status')) ?: 'any';
    $search = sanitize_text_field($request->get_param('search'));
    $orderby = sanitize_text_field($request->get_param('orderby')) ?: 'date';
    $order = strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC';
    $cursor = $request->get_param('cursor');
    $selected_fields = $request->get_param('fields');

    // Resolve post type
    $post_type = kreaction_resolve_post_type($type);
    if (!$post_type) {
        return kreaction_error('invalid_type', 'Invalid post type', 400);
    }

    $args = [
        'post_type' => $post_type,
        'posts_per_page' => min($per_page, 100),
        'orderby' => $orderby === 'id' ? 'ID' : $orderby,
        'order' => $order,
        'post_status' => $status === 'any'
            ? ['publish', 'draft', 'pending', 'private', 'future']
            : $status,
    ];

    // Cursor-based pagination
    if ($cursor) {
        $decoded = json_decode(base64_decode($cursor), true);
        if ($decoded && isset($decoded['id'])) {
            $compare = $order === 'DESC' ? '<' : '>';
            $args['post__not_in'] = [$decoded['id']];
            // Use date-based cursor for efficiency
            if (isset($decoded['date'])) {
                $args['date_query'] = [
                    [
                        'before' => $order === 'DESC' ? $decoded['date'] : null,
                        'after' => $order === 'ASC' ? $decoded['date'] : null,
                        'inclusive' => false,
                    ],
                ];
            }
        }
    } else {
        $page = (int)$request->get_param('page') ?: 1;
        $args['paged'] = $page;
    }

    if ($search) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    $posts = array_map(function($post) use ($selected_fields) {
        return kreaction_format_post_list_item($post, $selected_fields);
    }, $query->posts);

    // Generate next cursor
    $next_cursor = null;
    if (count($posts) === $per_page && !empty($query->posts)) {
        $last_post = end($query->posts);
        $cursor_data = [
            'id' => $last_post->ID,
            'date' => get_the_date('c', $last_post),
        ];
        $next_cursor = base64_encode(json_encode($cursor_data));
    }

    $response = [
        'posts' => $posts,
        'total' => (int)$query->found_posts,
        'pages' => (int)$query->max_num_pages,
    ];

    if ($cursor) {
        $response['next_cursor'] = $next_cursor;
    } else {
        $response['page'] = (int)$request->get_param('page') ?: 1;
    }

    return $response;
}

/**
 * Helper: Resolve post type from slug or rest_base
 */
function kreaction_resolve_post_type($type) {
    if (post_type_exists($type)) {
        return $type;
    }

    $post_types = get_post_types([], 'objects');
    foreach ($post_types as $pt) {
        if ($pt->rest_base === $type) {
            return $pt->name;
        }
    }

    return null;
}

/**
 * Helper: Format post for list display
 */
function kreaction_format_post_list_item($post, $selected_fields = null) {
    $thumbnail = null;
    $thumb_id = get_post_thumbnail_id($post->ID);
    if ($thumb_id) {
        $thumb_url = wp_get_attachment_image_url($thumb_id, 'thumbnail');
        if ($thumb_url) {
            $thumbnail = $thumb_url;
        }
    }

    $fields = null;
    if (function_exists('get_fields')) {
        $raw_fields = get_fields($post->ID);
        if ($raw_fields && is_array($raw_fields)) {
            $fields = kreaction_format_list_fields($raw_fields, $post->post_type, $selected_fields);
        }
    }

    $modified = get_post_modified_time('c', true, $post);
    $date = get_the_date('c', $post);

    return [
        'id' => $post->ID,
        'title' => get_the_title($post),
        'status' => $post->post_status,
        'date' => $date,
        'modified' => $modified ?: $date, // Fall back to date if no modified time
        'thumbnail' => $thumbnail,
        'fields' => $fields,
    ];
}

/**
 * Helper: Format limited fields for list view
 */
function kreaction_format_list_fields($fields, $post_type, $selected_fields = null) {
    if (!$fields || !is_array($fields)) {
        return null;
    }

    // If specific fields requested, only include those
    if ($selected_fields) {
        $field_names = array_map('trim', explode(',', $selected_fields));
        $fields = array_intersect_key($fields, array_flip($field_names));
    }

    $result = [];
    $count = 0;
    $max_fields = $selected_fields ? 100 : 5;

    foreach ($fields as $key => $value) {
        if ($count >= $max_fields) break;

        if ($value === null || $value === '' || $value === false) continue;
        if (is_array($value) && empty($value)) continue;

        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            $result[$key] = $value;
            $count++;
        }
    }

    return empty($result) ? null : $result;
}

/**
 * GET/POST /post/{id} - Get or update single post
 */
function kreaction_handle_post($request) {
    $id = (int)$request['id'];
    $post = get_post($id);

    if (!$post) {
        return kreaction_error('not_found', 'Post not found', 404);
    }

    // For POST (update), require edit_post capability
    if ($request->get_method() === 'POST') {
        if (!current_user_can('edit_post', $id)) {
            return kreaction_error('forbidden', 'Cannot edit this post', 403);
        }
        return kreaction_update_post($id, $request);
    }

    // For GET (view), only require read_post capability
    // This allows users to view posts even if they can't edit them
    if (!current_user_can('read_post', $id)) {
        return kreaction_error('forbidden', 'Cannot view this post', 403);
    }

    $selected_fields = $request->get_param('fields');
    return kreaction_format_full_post($post, $selected_fields);
}

/**
 * Helper: Format full post with all data
 */
function kreaction_format_full_post($post, $selected_fields = null) {
    $thumbnail = null;
    $thumb_id = get_post_thumbnail_id($post->ID);
    if ($thumb_id) {
        $thumbnail = [
            'id' => (int)$thumb_id,
            'url' => wp_get_attachment_url($thumb_id),
            'thumbnail' => wp_get_attachment_image_url($thumb_id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($thumb_id, 'medium'),
            'large' => wp_get_attachment_image_url($thumb_id, 'large'),
        ];
    }

    $fields = null;
    $field_order = [];

    if (function_exists('get_field_objects')) {
        $field_objects = get_field_objects($post->ID);
        if ($field_objects && is_array($field_objects)) {
            // Filter by selected fields if specified
            if ($selected_fields) {
                $field_names = array_map('trim', explode(',', $selected_fields));
                $field_objects = array_intersect_key($field_objects, array_flip($field_names));
            }

            // Get the correct field order from ACF field groups (same as /fields/{post_type} endpoint)
            $correct_field_order = [];
            if (function_exists('acf_get_field_groups') && function_exists('acf_get_fields')) {
                $field_groups = acf_get_field_groups(['post_type' => $post->post_type]);
                foreach ($field_groups as $group) {
                    $group_fields = acf_get_fields($group['key']);
                    if ($group_fields) {
                        foreach ($group_fields as $gf) {
                            $correct_field_order[] = $gf['name'];
                        }
                    }
                }
            }

            // Build fields array in the correct order
            $fields = [];

            // First, process fields in the correct order
            foreach ($correct_field_order as $field_name) {
                if (!isset($field_objects[$field_name])) {
                    continue;
                }
                $field = $field_objects[$field_name];

                if (kreaction_is_field_hidden($field)) {
                    continue;
                }

                $field_order[] = $field_name;

                // Get value - for number/range fields, always use get_field() with raw format
                // because get_field_objects() returns the formatted display value (with prepend/append)
                // which breaks is_numeric() checks
                if (in_array($field['type'], ['number', 'range'])) {
                    // Always get raw value for number fields to avoid formatting issues
                    $field_value = get_field($field_name, $post->ID, false); // false = return raw value
                } else {
                    $field_value = $field['value'];
                    if ($field_value === null || $field_value === '' || $field_value === false) {
                        $field_value = get_field($field_name, $post->ID, false);
                    }
                }

                $formatted_value = kreaction_format_field_value($field_value, $field);

                $fields[$field_name] = [
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'value' => $formatted_value,
                    'choices' => isset($field['choices']) ? $field['choices'] : null,
                    'required' => !empty($field['required']),
                    'instructions' => kreaction_get_clean_instructions($field),
                ];
            }

            // Also include any fields that weren't in the correct_field_order (fallback for edge cases)
            foreach ($field_objects as $key => $field) {
                if (isset($fields[$key])) {
                    continue; // Already processed
                }
                if (kreaction_is_field_hidden($field)) {
                    continue;
                }

                $field_order[] = $key;

                if (in_array($field['type'], ['number', 'range'])) {
                    $field_value = get_field($key, $post->ID, false);
                } else {
                    $field_value = $field['value'];
                    if ($field_value === null || $field_value === '' || $field_value === false) {
                        $field_value = get_field($key, $post->ID, false);
                    }
                }

                $formatted_value = kreaction_format_field_value($field_value, $field);

                $fields[$key] = [
                    'label' => $field['label'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'value' => $formatted_value,
                    'choices' => isset($field['choices']) ? $field['choices'] : null,
                    'required' => !empty($field['required']),
                    'instructions' => kreaction_get_clean_instructions($field),
                ];
            }
        }
    }

    $modified = get_post_modified_time('c', true, $post);
    $date = get_the_date('c', $post);

    return [
        'id' => $post->ID,
        'title' => get_the_title($post),
        'slug' => $post->post_name,
        'status' => $post->post_status,
        'type' => $post->post_type,
        'content' => apply_filters('the_content', $post->post_content),
        'content_raw' => $post->post_content,
        'excerpt' => get_the_excerpt($post),
        'date' => $date,
        'modified' => $modified ?: $date, // Fall back to date if no modified time
        'author' => (int)$post->post_author,
        'author_name' => get_the_author_meta('display_name', $post->post_author) ?: get_the_author_meta('user_login', $post->post_author),
        'link' => get_permalink($post),
        'thumbnail' => $thumbnail,
        'fields' => $fields,
        'field_order' => $field_order,
    ];
}

/**
 * Helper: Check if field should be hidden
 */
function kreaction_is_field_hidden($field) {
    if (isset($field['wrapper']['class'])) {
        if (strpos($field['wrapper']['class'], 'hide-in-app') !== false) {
            return true;
        }
    }

    if (isset($field['instructions'])) {
        if (strpos($field['instructions'], '[hide_in_app]') !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Helper: Get instructions without hide markers
 */
function kreaction_get_clean_instructions($field) {
    if (!isset($field['instructions']) || empty($field['instructions'])) {
        return null;
    }

    $instructions = $field['instructions'];
    $instructions = str_replace('[hide_in_app]', '', $instructions);
    $instructions = trim($instructions);

    return empty($instructions) ? null : $instructions;
}

/**
 * Helper: Format ACF field value based on type
 */
function kreaction_format_field_value($value, $field) {
    if ($value === null || $value === '' || $value === false) {
        return null;
    }

    $type = $field['type'];

    switch ($type) {
        case 'image':
            return kreaction_format_image_value($value);

        case 'gallery':
            if (!is_array($value)) return null;
            // Return IDs in saved order for the app to use
            // The app should use these IDs to maintain correct ordering
            $ids = [];
            foreach ($value as $image) {
                if (is_array($image) && isset($image['ID'])) {
                    $ids[] = (int)$image['ID'];
                } elseif (is_numeric($image)) {
                    $ids[] = (int)$image;
                }
            }
            return $ids;

        case 'file':
            return kreaction_format_file_value($value);

        case 'post_object':
            return kreaction_format_post_reference($value);

        case 'relationship':
            if (!is_array($value)) {
                return $value ? [kreaction_format_post_reference($value)] : null;
            }
            return array_values(array_filter(array_map('kreaction_format_post_reference', $value)));

        case 'user':
            return kreaction_format_user_value($value);

        case 'taxonomy':
        case 'term':
            return kreaction_format_term_value($value);

        case 'true_false':
            return (bool)$value;

        case 'number':
        case 'range':
            return is_numeric($value) ? (float)$value : null;

        case 'date_picker':
            // Normalize to Ymd format for iOS compatibility
            $timestamp = strtotime($value);
            return $timestamp ? date('Ymd', $timestamp) : null;

        case 'date_time_picker':
            // Normalize to Y-m-d H:i:s format for iOS compatibility
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;

        case 'time_picker':
            // Normalize to H:i:s format for iOS compatibility
            $timestamp = strtotime($value);
            return $timestamp ? date('H:i:s', $timestamp) : null;

        case 'color_picker':
            // Ensure valid hex color format
            if (is_string($value)) {
                $value = trim($value);
                // Add # prefix if missing
                if (preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                    return '#' . $value;
                }
                // Already has # prefix
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                    return $value;
                }
            }
            return null;

        case 'link':
            // Ensure consistent link format {url, title, target}
            if (is_array($value)) {
                return [
                    'url' => $value['url'] ?? '',
                    'title' => $value['title'] ?? '',
                    'target' => $value['target'] ?? '_self',
                ];
            }
            return null;

        case 'page_link':
            // Return URL string(s)
            if (is_array($value)) {
                return array_values(array_filter(array_map(function($item) {
                    return is_string($item) ? $item : (is_numeric($item) ? get_permalink((int)$item) : null);
                }, $value)));
            }
            if (is_string($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return get_permalink((int)$value);
            }
            return null;

        case 'google_map':
            // Ensure consistent google map format
            if (is_array($value)) {
                return [
                    'lat' => isset($value['lat']) ? (float)$value['lat'] : null,
                    'lng' => isset($value['lng']) ? (float)$value['lng'] : null,
                    'address' => $value['address'] ?? '',
                    'zoom' => isset($value['zoom']) ? (int)$value['zoom'] : 14,
                    'place_id' => $value['place_id'] ?? '',
                    'name' => $value['name'] ?? '',
                    'city' => $value['city'] ?? '',
                    'state' => $value['state'] ?? '',
                    'country' => $value['country'] ?? '',
                ];
            }
            return null;

        case 'oembed':
            // Return the raw URL instead of embed HTML
            // The app can render the URL or fetch embed data as needed
            if (is_string($value)) {
                // Value might be the URL or embed HTML
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }
                // Try to extract URL from oembed HTML
                if (preg_match('/src=["\']([^"\']+)["\']/', $value, $matches)) {
                    return $matches[1];
                }
            }
            // If ACF stored the raw URL in the field's raw value
            if (!empty($field['name'])) {
                $post_id = get_the_ID();
                if ($post_id) {
                    $raw_value = get_post_meta($post_id, $field['name'], true);
                    if (is_string($raw_value) && filter_var($raw_value, FILTER_VALIDATE_URL)) {
                        return $raw_value;
                    }
                }
            }
            return $value;

        case 'repeater':
        case 'flexible_content':
        case 'group':
            return kreaction_format_complex_value($value, $field);

        default:
            return $value;
    }
}

/**
 * Helper: Format image field value
 */
function kreaction_format_image_value($value) {
    if (!$value) return null;

    if (is_array($value) && isset($value['ID'])) {
        return [
            'id' => (int)$value['ID'],
            'url' => $value['url'] ?? '',
            'thumbnail' => $value['sizes']['thumbnail'] ?? $value['url'] ?? '',
            'medium' => $value['sizes']['medium'] ?? $value['url'] ?? '',
            'alt' => $value['alt'] ?? '',
            'title' => $value['title'] ?? '',
            'filename' => $value['filename'] ?? basename($value['url'] ?? ''),
        ];
    } elseif (is_numeric($value)) {
        $id = (int)$value;
        $url = wp_get_attachment_url($id);
        if (!$url) return null;

        return [
            'id' => $id,
            'url' => $url,
            'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail') ?: $url,
            'medium' => wp_get_attachment_image_url($id, 'medium') ?: $url,
            'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
            'title' => get_the_title($id),
            'filename' => basename($url),
        ];
    }

    return null;
}

/**
 * Helper: Format file field value
 */
function kreaction_format_file_value($value) {
    if (!$value) return null;

    if (is_array($value) && isset($value['ID'])) {
        return [
            'id' => (int)$value['ID'],
            'url' => $value['url'] ?? '',
            'filename' => $value['filename'] ?? '',
            'filesize' => $value['filesize'] ?? 0,
            'mime_type' => $value['mime_type'] ?? '',
        ];
    } elseif (is_numeric($value)) {
        $id = (int)$value;
        $url = wp_get_attachment_url($id);
        if (!$url) return null;

        $meta = wp_get_attachment_metadata($id);
        return [
            'id' => $id,
            'url' => $url,
            'filename' => basename($url),
            'filesize' => $meta['filesize'] ?? 0,
            'mime_type' => get_post_mime_type($id),
        ];
    }

    return null;
}

/**
 * Helper: Format post reference value
 */
function kreaction_format_post_reference($value) {
    if (!$value) return null;

    if (is_object($value) && isset($value->ID)) {
        return [
            'id' => (int)$value->ID,
            'title' => get_the_title($value),
            'type' => $value->post_type,
            'status' => $value->post_status,
        ];
    } elseif (is_numeric($value)) {
        $post = get_post($value);
        if (!$post) return null;

        return [
            'id' => (int)$post->ID,
            'title' => get_the_title($post),
            'type' => $post->post_type,
            'status' => $post->post_status,
        ];
    }

    return null;
}

/**
 * Helper: Format user value
 */
function kreaction_format_user_value($value) {
    if (!$value) return null;

    $user = null;
    if (is_object($value) && isset($value->ID)) {
        $user = $value;
    } elseif (is_numeric($value)) {
        $user = get_user_by('ID', $value);
    } elseif (is_array($value) && isset($value['ID'])) {
        $user = get_user_by('ID', $value['ID']);
    }

    if (!$user) return null;

    return [
        'id' => (int)$user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
    ];
}

/**
 * Helper: Format term/taxonomy value
 */
function kreaction_format_term_value($value) {
    if (!$value) return null;

    if (is_array($value)) {
        return array_values(array_filter(array_map('kreaction_format_single_term', $value)));
    }

    return kreaction_format_single_term($value);
}

function kreaction_format_single_term($term) {
    if (!$term) return null;

    if (is_object($term) && isset($term->term_id)) {
        return [
            'id' => (int)$term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
        ];
    } elseif (is_numeric($term)) {
        $term_obj = get_term($term);
        if (!$term_obj || is_wp_error($term_obj)) return null;

        return [
            'id' => (int)$term_obj->term_id,
            'name' => $term_obj->name,
            'slug' => $term_obj->slug,
            'taxonomy' => $term_obj->taxonomy,
        ];
    }

    return null;
}

/**
 * Helper: Format complex field values
 */
function kreaction_format_complex_value($value, $field) {
    if (!$value || !is_array($value)) {
        return null;
    }

    $sub_fields = [];
    if (isset($field['sub_fields'])) {
        foreach ($field['sub_fields'] as $sub_field) {
            $sub_fields[$sub_field['name']] = $sub_field;
        }
    }

    if (isset($field['layouts'])) {
        $layouts = [];
        foreach ($field['layouts'] as $layout) {
            $layouts[$layout['name']] = $layout;
        }

        return array_map(function($row) use ($layouts) {
            if (!isset($row['acf_fc_layout'])) {
                return $row;
            }

            $layout_name = $row['acf_fc_layout'];
            $layout = $layouts[$layout_name] ?? null;
            $formatted = ['acf_fc_layout' => $layout_name];

            if ($layout && isset($layout['sub_fields'])) {
                foreach ($layout['sub_fields'] as $sub_field) {
                    $key = $sub_field['name'];
                    if (isset($row[$key])) {
                        $formatted[$key] = kreaction_format_field_value($row[$key], $sub_field);
                    }
                }
            } else {
                foreach ($row as $key => $val) {
                    if ($key !== 'acf_fc_layout') {
                        $formatted[$key] = $val;
                    }
                }
            }

            return $formatted;
        }, $value);
    }

    if ($field['type'] === 'repeater') {
        return array_map(function($row) use ($sub_fields) {
            $formatted = [];
            foreach ($row as $key => $val) {
                if (isset($sub_fields[$key])) {
                    $formatted[$key] = kreaction_format_field_value($val, $sub_fields[$key]);
                } else {
                    $formatted[$key] = $val;
                }
            }
            return $formatted;
        }, $value);
    }

    if ($field['type'] === 'group') {
        $formatted = [];
        foreach ($value as $key => $val) {
            if (isset($sub_fields[$key])) {
                $formatted[$key] = kreaction_format_field_value($val, $sub_fields[$key]);
            } else {
                $formatted[$key] = $val;
            }
        }
        return $formatted;
    }

    return $value;
}

/**
 * POST /post/{id} - Update existing post
 */
function kreaction_update_post($id, $request) {
    $params = $request->get_json_params();
    $post = get_post($id);

    // Validate ACF fields if provided
    if (isset($params['fields']) && is_array($params['fields'])) {
        $validation = Kreaction_Validator::validate_acf_fields($params['fields'], $post->post_type);
        if ($validation !== true) {
            return kreaction_error('validation_failed', 'Field validation failed', 400, [
                'errors' => $validation,
            ]);
        }

        // Sanitize fields
        $params['fields'] = Kreaction_Validator::sanitize_fields($params['fields'], $post->post_type);
    }

    $post_data = ['ID' => $id];

    if (isset($params['title'])) {
        $post_data['post_title'] = sanitize_text_field($params['title']);
    }
    if (isset($params['content'])) {
        $post_data['post_content'] = wp_kses_post($params['content']);
    }
    if (isset($params['excerpt'])) {
        $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
    }
    if (isset($params['status'])) {
        $allowed = ['publish', 'draft', 'pending', 'private', 'future'];
        if (in_array($params['status'], $allowed)) {
            $post_data['post_status'] = $params['status'];
        }
    }
    if (isset($params['slug'])) {
        $post_data['post_name'] = sanitize_title($params['slug']);
    }

    $result = wp_update_post($post_data, true);
    if (is_wp_error($result)) {
        return kreaction_error('update_failed', $result->get_error_message(), 500);
    }

    // Update featured image
    if (array_key_exists('thumbnail_id', $params)) {
        if ($params['thumbnail_id']) {
            set_post_thumbnail($id, (int)$params['thumbnail_id']);
        } else {
            delete_post_thumbnail($id);
        }
    }

    // Update ACF fields
    if (isset($params['fields']) && is_array($params['fields']) && function_exists('update_field')) {
        foreach ($params['fields'] as $key => $value) {
            update_field($key, $value, $id);
        }
    }

    // Clear cache
    Kreaction_Cache::delete("post_{$id}");

    // Log the update
    Kreaction_Audit_Log::log_post_updated($id, $post->post_type, $params);

    clean_post_cache($id);

    return kreaction_format_full_post(get_post($id));
}

/**
 * POST /posts/{type} - Create new post
 */
function kreaction_create_post($request) {
    $type = sanitize_text_field($request['type']);
    $params = $request->get_json_params();

    $post_type = kreaction_resolve_post_type($type);
    if (!$post_type) {
        return kreaction_error('invalid_type', 'Invalid post type', 400);
    }

    $type_obj = get_post_type_object($post_type);
    if (!current_user_can($type_obj->cap->create_posts)) {
        return kreaction_error('forbidden', 'Cannot create posts of this type', 403);
    }

    // Validate ACF fields if provided
    if (isset($params['fields']) && is_array($params['fields'])) {
        $validation = Kreaction_Validator::validate_acf_fields($params['fields'], $post_type);
        if ($validation !== true) {
            return kreaction_error('validation_failed', 'Field validation failed', 400, [
                'errors' => $validation,
            ]);
        }

        $params['fields'] = Kreaction_Validator::sanitize_fields($params['fields'], $post_type);
    }

    $post_data = [
        'post_type' => $post_type,
        'post_title' => sanitize_text_field($params['title'] ?? 'Untitled'),
        'post_content' => wp_kses_post($params['content'] ?? ''),
        'post_status' => $params['status'] ?? 'draft',
        'post_author' => get_current_user_id(),
    ];

    if (isset($params['excerpt'])) {
        $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
    }

    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        return kreaction_error('create_failed', $post_id->get_error_message(), 500);
    }

    if (!empty($params['thumbnail_id'])) {
        set_post_thumbnail($post_id, (int)$params['thumbnail_id']);
    }

    if (isset($params['fields']) && is_array($params['fields']) && function_exists('update_field')) {
        foreach ($params['fields'] as $key => $value) {
            update_field($key, $value, $post_id);
        }
    }

    // Log the creation
    Kreaction_Audit_Log::log_post_created($post_id, $post_type, $params);

    return kreaction_format_full_post(get_post($post_id));
}

/**
 * DELETE /post/{id} - Delete post
 */
function kreaction_delete_post($request) {
    $id = (int)$request['id'];
    $force = (bool)$request->get_param('force');

    $post = get_post($id);
    if (!$post) {
        return kreaction_error('not_found', 'Post not found', 404);
    }

    if (!current_user_can('delete_post', $id)) {
        return kreaction_error('forbidden', 'Cannot delete this post', 403);
    }

    $title = get_the_title($post);
    $post_type = $post->post_type;

    $result = wp_delete_post($id, $force);
    if (!$result) {
        return kreaction_error('delete_failed', 'Failed to delete post', 500);
    }

    // Log the deletion
    Kreaction_Audit_Log::log_post_deleted($id, $post_type, $title, $force);

    return [
        'deleted' => true,
        'id' => $id,
        'force' => $force,
    ];
}

// =============================================================================
// BATCH OPERATIONS
// =============================================================================

/**
 * POST /batch - Perform batch operations
 */
function kreaction_batch_operations($request) {
    $params = $request->get_json_params();
    $operations = $params['operations'] ?? [];

    if (empty($operations)) {
        return kreaction_error('no_operations', 'No operations provided', 400);
    }

    if (count($operations) > 50) {
        return kreaction_error('too_many_operations', 'Maximum 50 operations per batch', 400);
    }

    $results = [];
    $success_count = 0;
    $error_count = 0;

    foreach ($operations as $index => $op) {
        $method = strtoupper($op['method'] ?? 'GET');
        $endpoint = $op['endpoint'] ?? '';
        $body = $op['body'] ?? [];

        try {
            $result = kreaction_execute_batch_operation($method, $endpoint, $body);
            $results[$index] = [
                'success' => true,
                'data' => $result,
            ];
            $success_count++;
        } catch (Exception $e) {
            $results[$index] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
            $error_count++;
        }
    }

    // Log batch operation
    Kreaction_Audit_Log::log_batch_operation('batch', count($operations));

    return [
        'results' => $results,
        'summary' => [
            'total' => count($operations),
            'success' => $success_count,
            'errors' => $error_count,
        ],
    ];
}

/**
 * Execute a single batch operation
 */
function kreaction_execute_batch_operation($method, $endpoint, $body) {
    // Parse endpoint
    $parts = explode('/', trim($endpoint, '/'));

    if (count($parts) >= 2 && $parts[0] === 'post') {
        $post_id = (int)$parts[1];

        if ($method === 'GET') {
            $post = get_post($post_id);
            if (!$post) throw new Exception('Post not found');
            return kreaction_format_full_post($post);
        }

        if ($method === 'POST') {
            // Create a mock request
            $request = new WP_REST_Request('POST');
            $request->set_body(json_encode($body));
            $request->set_header('content-type', 'application/json');
            $request['id'] = $post_id;

            return kreaction_update_post($post_id, $request);
        }

        if ($method === 'DELETE') {
            $force = $body['force'] ?? false;
            $post = get_post($post_id);
            if (!$post) throw new Exception('Post not found');

            $result = wp_delete_post($post_id, $force);
            if (!$result) throw new Exception('Delete failed');

            return ['deleted' => true, 'id' => $post_id];
        }
    }

    throw new Exception('Invalid endpoint');
}

// =============================================================================
// MEDIA
// =============================================================================

/**
 * GET /media - Get media library items
 */
function kreaction_get_media($request) {
    $per_page = (int)$request->get_param('per_page') ?: 20;
    $media_type = sanitize_text_field($request->get_param('media_type')) ?: 'image';
    $cursor = $request->get_param('cursor');

    $args = [
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => min($per_page, 100),
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    if ($media_type === 'image') {
        $args['post_mime_type'] = 'image';
    } elseif ($media_type !== 'all') {
        $args['post_mime_type'] = $media_type;
    }

    if ($cursor) {
        $decoded = json_decode(base64_decode($cursor), true);
        if ($decoded && isset($decoded['id'])) {
            $args['date_query'] = [
                ['before' => $decoded['date'], 'inclusive' => false],
            ];
        }
    } else {
        $page = (int)$request->get_param('page') ?: 1;
        $args['paged'] = $page;
    }

    $query = new WP_Query($args);

    $items = array_map(function($attachment) {
        $id = $attachment->ID;
        return [
            'id' => $id,
            'title' => get_the_title($attachment),
            'filename' => basename(get_attached_file($id)),
            'mime_type' => $attachment->post_mime_type,
            'url' => wp_get_attachment_url($id),
            'thumbnail' => wp_get_attachment_image_url($id, 'thumbnail'),
            'medium' => wp_get_attachment_image_url($id, 'medium'),
            'date' => get_the_date('c', $attachment),
            'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
        ];
    }, $query->posts);

    // Generate cursor
    $next_cursor = null;
    if (count($items) === $per_page && !empty($query->posts)) {
        $last = end($query->posts);
        $next_cursor = base64_encode(json_encode([
            'id' => $last->ID,
            'date' => get_the_date('c', $last),
        ]));
    }

    $response = [
        'items' => $items,
        'total' => (int)$query->found_posts,
        'pages' => (int)$query->max_num_pages,
    ];

    if ($cursor) {
        $response['next_cursor'] = $next_cursor;
    } else {
        $response['page'] = (int)$request->get_param('page') ?: 1;
    }

    return $response;
}

/**
 * POST /media/upload - Upload media file
 */
function kreaction_upload_media($request) {
    if (!current_user_can('upload_files')) {
        return kreaction_error('forbidden', 'Cannot upload files', 403);
    }

    $files = $request->get_file_params();

    if (empty($files['file'])) {
        return kreaction_error('no_file', 'No file provided', 400);
    }

    $file = $files['file'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];
        $message = $error_messages[$file['error']] ?? 'Unknown upload error';
        return kreaction_error('upload_error', $message, 400);
    }

    // Validate file type
    $allowed_types = get_allowed_mime_types();
    $file_type = wp_check_filetype($file['name']);

    if (!$file_type['type'] || !in_array($file_type['type'], $allowed_types)) {
        return kreaction_error('invalid_type', 'File type not allowed', 400);
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Handle the upload
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error'])) {
        return kreaction_error('upload_failed', $upload['error'], 500);
    }

    // Create attachment
    $attachment = [
        'post_mime_type' => $upload['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
        'post_content' => '',
        'post_status' => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $upload['file']);

    if (is_wp_error($attach_id)) {
        return kreaction_error('attachment_failed', $attach_id->get_error_message(), 500);
    }

    // Generate metadata
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Log upload
    Kreaction_Audit_Log::log_media_uploaded($attach_id, $file['name']);

    return [
        'id' => $attach_id,
        'url' => $upload['url'],
        'filename' => basename($upload['file']),
        'mime_type' => $upload['type'],
        'thumbnail' => wp_get_attachment_image_url($attach_id, 'thumbnail'),
        'medium' => wp_get_attachment_image_url($attach_id, 'medium'),
    ];
}

/**
 * DELETE /media/{id} - Delete media attachment
 */
function kreaction_delete_media($request) {
    $id = (int)$request['id'];
    $force = (bool)$request->get_param('force');

    $attachment = get_post($id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return kreaction_error('not_found', 'Media not found', 404);
    }

    if (!current_user_can('delete_post', $id)) {
        return kreaction_error('forbidden', 'Cannot delete this media', 403);
    }

    $filename = basename(get_attached_file($id));

    // Delete the attachment (force=true deletes permanently, false moves to trash)
    $result = wp_delete_attachment($id, $force);
    if (!$result) {
        return kreaction_error('delete_failed', 'Failed to delete media', 500);
    }

    // Log the deletion
    Kreaction_Audit_Log::log('media_deleted', 'attachment', $id, [
        'filename' => $filename,
        'force' => $force,
    ]);

    return [
        'deleted' => true,
        'id' => $id,
        'force' => $force,
    ];
}

/**
 * GET /media/{id}/optimized - Get optimized image URL
 */
function kreaction_get_optimized_image($request) {
    $id = (int)$request['id'];
    $size = sanitize_text_field($request->get_param('size')) ?: 'medium';
    $width = (int)$request->get_param('width');
    $height = (int)$request->get_param('height');

    if (!wp_attachment_is_image($id)) {
        return kreaction_error('not_image', 'Attachment is not an image', 400);
    }

    // If custom dimensions requested
    if ($width || $height) {
        $image = wp_get_attachment_image_src($id, 'full');
        if (!$image) {
            return kreaction_error('not_found', 'Image not found', 404);
        }

        // Try to get resized version
        $resized = image_get_intermediate_size($id, [$width ?: 9999, $height ?: 9999]);

        if ($resized) {
            $upload_dir = wp_upload_dir();
            return [
                'id' => $id,
                'url' => $upload_dir['baseurl'] . '/' . $resized['path'],
                'width' => $resized['width'],
                'height' => $resized['height'],
            ];
        }

        // Fall back to full image with dimensions
        return [
            'id' => $id,
            'url' => $image[0],
            'width' => $image[1],
            'height' => $image[2],
            'note' => 'Exact size not available, returning full image',
        ];
    }

    // Standard size
    $image = wp_get_attachment_image_src($id, $size);
    if (!$image) {
        return kreaction_error('not_found', 'Image not found', 404);
    }

    return [
        'id' => $id,
        'url' => $image[0],
        'width' => $image[1],
        'height' => $image[2],
        'size' => $size,
    ];
}

// =============================================================================
// SCHEMA & FIELDS
// =============================================================================

/**
 * GET /schema - Return full ACF schema
 */
function kreaction_get_schema() {
    return Kreaction_Cache::remember('schema', function() {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return new WP_Error('acf_not_available', 'ACF is not installed', ['status' => 400]);
        }

        $types = kreaction_get_post_types_array();
        $post_types_schema = [];

        foreach ($types as $type) {
            $post_type_slug = $type['slug'];
            $field_groups = acf_get_field_groups(['post_type' => $post_type_slug]);

            $fields = [];
            $field_order = [];

            foreach ($field_groups as $group) {
                $group_fields = acf_get_fields($group['key']);
                if (!$group_fields) continue;

                foreach ($group_fields as $field) {
                    if (kreaction_is_field_hidden($field)) {
                        continue;
                    }

                    $field_name = $field['name'];
                    $field_order[] = $field_name;
                    $fields[$field_name] = kreaction_format_field_schema($field);
                }
            }

            if (!empty($fields)) {
                $post_types_schema[$post_type_slug] = [
                    'slug' => $post_type_slug,
                    'fields' => $fields,
                    'fieldOrder' => $field_order,
                ];
            }
        }

        return [
            'siteURL' => home_url(),
            'fetchedAt' => gmdate('c'),
            'postTypes' => $post_types_schema,
        ];
    }, 3600); // Cache for 1 hour
}

/**
 * GET /fields/{post_type} - Get ACF field definitions for a post type
 */
function kreaction_get_fields_for_post_type($request) {
    $post_type = sanitize_text_field($request['post_type']);

    $resolved_type = kreaction_resolve_post_type($post_type);
    if (!$resolved_type) {
        $resolved_type = $post_type;
    }

    $cache_key = "fields_{$resolved_type}";

    return Kreaction_Cache::remember($cache_key, function() use ($resolved_type) {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return new WP_Error('acf_not_available', 'ACF is not installed', ['status' => 400]);
        }

        $field_groups = acf_get_field_groups(['post_type' => $resolved_type]);
        $fields = [];
        $field_order = [];

        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            if (!$group_fields) continue;

            foreach ($group_fields as $field) {
                if (kreaction_is_field_hidden($field)) {
                    continue;
                }

                $field_name = $field['name'];
                $field_order[] = $field_name;
                $fields[$field_name] = kreaction_format_field_schema($field);
            }
        }

        return [
            'postType' => $resolved_type,
            'fields' => $fields,
            'fieldOrder' => $field_order,
        ];
    }, 3600);
}

/**
 * Helper: Format field schema for API response
 */
function kreaction_format_field_schema($field) {
    $schema = [
        'name' => $field['name'],
        'label' => $field['label'] ?? $field['name'],
        'fieldType' => $field['type'],
        'isRequired' => !empty($field['required']),
        'isHidden' => false,
        'instructions' => kreaction_get_clean_instructions($field),
    ];

    if (!empty($field['choices'])) {
        $schema['choices'] = $field['choices'];
    }

    if (in_array($field['type'], ['post_object', 'relationship'])) {
        $related_types = [];
        if (!empty($field['post_type'])) {
            $related_types = is_array($field['post_type']) ? $field['post_type'] : [$field['post_type']];
        }
        $schema['relatedPostTypes'] = $related_types;
    }

    if ($field['type'] === 'taxonomy') {
        // Taxonomy slug(s)
        if (!empty($field['taxonomy'])) {
            $schema['taxonomy'] = is_array($field['taxonomy']) ? $field['taxonomy'] : [$field['taxonomy']];
        }
        // Field appearance: checkbox, multi_select, radio, select
        if (!empty($field['field_type'])) {
            $schema['fieldType'] = $field['field_type'];
        }
        // Return format: id, object
        if (!empty($field['return_format'])) {
            $schema['returnFormat'] = $field['return_format'];
        }
        // Allow multiple selection
        $schema['allowMultiple'] = in_array($field['field_type'] ?? '', ['checkbox', 'multi_select']);
    }

    if (in_array($field['type'], ['repeater', 'group', 'flexible_content']) && !empty($field['sub_fields'])) {
        $sub_fields = [];
        foreach ($field['sub_fields'] as $sub_field) {
            if (!kreaction_is_field_hidden($sub_field)) {
                $sub_fields[$sub_field['name']] = kreaction_format_field_schema($sub_field);
            }
        }
        $schema['subFields'] = $sub_fields;
    }

    if ($field['type'] === 'flexible_content' && !empty($field['layouts'])) {
        $layouts = [];
        foreach ($field['layouts'] as $layout) {
            $layout_fields = [];
            if (!empty($layout['sub_fields'])) {
                foreach ($layout['sub_fields'] as $sub_field) {
                    if (!kreaction_is_field_hidden($sub_field)) {
                        $layout_fields[$sub_field['name']] = kreaction_format_field_schema($sub_field);
                    }
                }
            }
            $layouts[] = [
                'name' => $layout['name'],
                'label' => $layout['label'],
                'subFields' => $layout_fields,
            ];
        }
        $schema['layouts'] = $layouts;
    }

    if (in_array($field['type'], ['number', 'range'])) {
        if (isset($field['min']) && $field['min'] !== '') {
            $schema['min'] = (float)$field['min'];
        }
        if (isset($field['max']) && $field['max'] !== '') {
            $schema['max'] = (float)$field['max'];
        }
        if (isset($field['step']) && $field['step'] !== '') {
            $schema['step'] = (float)$field['step'];
        }
    }

    // Prepend/append for text, number, range fields
    if (in_array($field['type'], ['text', 'number', 'range', 'email', 'url'])) {
        if (!empty($field['prepend'])) {
            $schema['prepend'] = $field['prepend'];
        }
        if (!empty($field['append'])) {
            $schema['append'] = $field['append'];
        }
    }

    if (!empty($field['return_format'])) {
        $schema['returnFormat'] = $field['return_format'];
    }

    return $schema;
}

// =============================================================================
// LOOKUPS (for ACF fields)
// =============================================================================

/**
 * GET /search/{type} - Search posts by type for relationship/post_object fields
 */
function kreaction_search_posts($request) {
    $type = sanitize_text_field($request['type']);
    $search = sanitize_text_field($request->get_param('search'));
    $per_page = (int)$request->get_param('per_page') ?: 20;
    $exclude = $request->get_param('exclude');

    // Resolve post type
    $post_type = kreaction_resolve_post_type($type);
    if (!$post_type) {
        // Return empty result instead of error to prevent app crashes
        // The post type might not exist on this site
        return [
            'posts' => [],
            'total' => 0,
        ];
    }

    $args = [
        'post_type' => $post_type,
        'posts_per_page' => min($per_page, 100),
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
    ];

    if ($search) {
        $args['s'] = $search;
    }

    if ($exclude) {
        $exclude_ids = array_map('intval', explode(',', $exclude));
        $args['post__not_in'] = $exclude_ids;
    }

    $query = new WP_Query($args);

    $posts = array_map(function($post) {
        $thumbnail = null;
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $thumbnail = wp_get_attachment_image_url($thumb_id, 'thumbnail');
        }

        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'type' => $post->post_type,
            'status' => $post->post_status,
            'thumbnail' => $thumbnail,
        ];
    }, $query->posts);

    return [
        'posts' => $posts,
        'total' => (int)$query->found_posts,
    ];
}

/**
 * GET /terms/{taxonomy} - Get taxonomy terms for taxonomy fields
 */
function kreaction_get_terms($request) {
    $taxonomy = sanitize_text_field($request['taxonomy']);
    $search = sanitize_text_field($request->get_param('search'));
    $per_page = (int)$request->get_param('per_page') ?: 100;
    $hide_empty = (bool)$request->get_param('hide_empty');

    // Check if taxonomy exists
    if (!taxonomy_exists($taxonomy)) {
        return kreaction_error('invalid_taxonomy', 'Invalid taxonomy', 400);
    }

    $args = [
        'taxonomy' => $taxonomy,
        'number' => min($per_page, 500),
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => $hide_empty,
    ];

    if ($search) {
        $args['search'] = $search;
    }

    $terms = get_terms($args);

    if (is_wp_error($terms)) {
        return kreaction_error('terms_error', $terms->get_error_message(), 500);
    }

    $result = array_map(function($term) {
        return [
            'id' => (int)$term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
            'count' => (int)$term->count,
            'parent' => (int)$term->parent,
        ];
    }, $terms);

    return [
        'terms' => $result,
        'total' => count($result),
        'taxonomy' => $taxonomy,
    ];
}

/**
 * GET /users - Get users list for user fields
 */
function kreaction_get_users($request) {
    $search = sanitize_text_field($request->get_param('search'));
    $per_page = (int)$request->get_param('per_page') ?: 50;
    $role = sanitize_text_field($request->get_param('role'));

    $args = [
        'number' => min($per_page, 100),
        'orderby' => 'display_name',
        'order' => 'ASC',
    ];

    if ($search) {
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = ['display_name', 'user_login', 'user_email'];
    }

    if ($role) {
        $args['role'] = $role;
    }

    $users = get_users($args);

    $result = array_map(function($user) {
        return [
            'id' => (int)$user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => get_avatar_url($user->ID, ['size' => 96]),
            'roles' => $user->roles,
        ];
    }, $users);

    return [
        'users' => $result,
        'total' => count($result),
    ];
}

/**
 * GET /media/{id} - Get single media item details
 */
function kreaction_get_single_media($request) {
    $id = (int)$request['id'];

    $attachment = get_post($id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return kreaction_error('not_found', 'Media not found', 404);
    }

    $url = wp_get_attachment_url($id);
    $meta = wp_get_attachment_metadata($id);

    $result = [
        'id' => $id,
        'title' => get_the_title($attachment),
        'filename' => basename(get_attached_file($id)),
        'mime_type' => $attachment->post_mime_type,
        'url' => $url,
        'alt' => get_post_meta($id, '_wp_attachment_image_alt', true) ?: '',
        'caption' => $attachment->post_excerpt,
        'description' => $attachment->post_content,
        'date' => get_the_date('c', $attachment),
    ];

    // Add image-specific data
    if (strpos($attachment->post_mime_type, 'image/') === 0) {
        $result['thumbnail'] = wp_get_attachment_image_url($id, 'thumbnail');
        $result['medium'] = wp_get_attachment_image_url($id, 'medium');
        $result['large'] = wp_get_attachment_image_url($id, 'large');

        if ($meta && isset($meta['width']) && isset($meta['height'])) {
            $result['width'] = (int)$meta['width'];
            $result['height'] = (int)$meta['height'];
        }
    }

    // Add file size
    $file_path = get_attached_file($id);
    if ($file_path && file_exists($file_path)) {
        $result['filesize'] = filesize($file_path);
    }

    return $result;
}

// =============================================================================
// AUDIT LOG
// =============================================================================

/**
 * GET /audit-log - Get audit log entries
 */
function kreaction_get_audit_log($request) {
    $entries = Kreaction_Audit_Log::get_entries([
        'page' => (int)$request->get_param('page') ?: 1,
        'per_page' => (int)$request->get_param('per_page') ?: 50,
        'user_id' => $request->get_param('user_id'),
        'action' => $request->get_param('action'),
        'object_type' => $request->get_param('object_type'),
    ]);

    $total = Kreaction_Audit_Log::get_count([
        'user_id' => $request->get_param('user_id'),
        'action' => $request->get_param('action'),
        'object_type' => $request->get_param('object_type'),
    ]);

    return [
        'entries' => $entries,
        'total' => $total,
        'page' => (int)$request->get_param('page') ?: 1,
        'per_page' => (int)$request->get_param('per_page') ?: 50,
    ];
}

// =============================================================================
// CACHE MANAGEMENT
// =============================================================================

/**
 * POST /cache/clear - Clear caches
 */
function kreaction_clear_cache($request) {
    $params = $request->get_json_params();
    $key = $params['key'] ?? null;

    if ($key) {
        $deleted = Kreaction_Cache::delete($key);
        return [
            'cleared' => $deleted,
            'key' => $key,
        ];
    }

    $count = Kreaction_Cache::flush_all();
    return [
        'cleared' => true,
        'count' => $count,
    ];
}

// =============================================================================
// ERROR HELPER
// =============================================================================

/**
 * Create a standardized error response
 */
function kreaction_error($code, $message, $status = 400, $data = []) {
    $error_data = array_merge(['status' => $status], $data);
    return new WP_Error($code, $message, $error_data);
}
