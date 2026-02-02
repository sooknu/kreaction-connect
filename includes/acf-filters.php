<?php
/**
 * Kreaction Connect ACF Filters
 *
 * Filters ACF data for the Kreaction iOS app.
 * Handles field visibility based on 'hide-in-app' class or [hide_in_app] marker.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter ACF REST API responses to exclude hidden fields
 * This works with the native ACF REST API endpoint (/wp-json/acf/v3/...)
 */
add_filter('acf/rest/format_value', function($value, $post_id, $field) {
    // Check if field should be hidden
    if (kreaction_acf_is_field_hidden($field)) {
        return new WP_Error('hidden_field', 'Field hidden from app', ['status' => 200]);
    }
    return $value;
}, 10, 3);

/**
 * Filter the entire ACF REST response to remove hidden fields
 * This ensures fields are completely removed, not just nulled
 */
add_filter('acf/rest/load_fields', function($fields, $resource, $http_method) {
    if (!is_array($fields)) {
        return $fields;
    }

    // Filter out hidden fields
    return array_filter($fields, function($field) {
        return !kreaction_acf_is_field_hidden($field);
    });
}, 10, 3);

/**
 * Filter to exclude hidden fields from ACF field objects
 */
add_filter('acf/get_field', function($field) {
    if (!$field) return $field;

    // Don't filter in admin
    if (is_admin() && !wp_doing_ajax()) {
        return $field;
    }

    // Only filter for REST requests
    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return $field;
    }

    if (kreaction_acf_is_field_hidden($field)) {
        return false;
    }

    return $field;
}, 20);

/**
 * Also filter when getting field values via get_fields()
 * This ensures hidden fields don't appear in our custom endpoints
 */
add_filter('acf/format_value', function($value, $post_id, $field) {
    // Only filter for REST requests
    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return $value;
    }

    if (kreaction_acf_is_field_hidden($field)) {
        return null;
    }
    return $value;
}, 20, 3);

/**
 * Check if an ACF field should be hidden from the app
 *
 * Fields can be marked as hidden in two ways:
 * 1. Add 'hide-in-app' to the field's wrapper class
 * 2. Add [hide_in_app] anywhere in the field's instructions
 *
 * @param array $field ACF field array
 * @return bool True if field should be hidden
 */
function kreaction_acf_is_field_hidden($field) {
    if (!is_array($field)) {
        return false;
    }

    // Method 1: Check wrapper class
    if (!empty($field['wrapper']['class'])) {
        $classes = $field['wrapper']['class'];
        if (strpos($classes, 'hide-in-app') !== false) {
            return true;
        }
    }

    // Method 2: Check instructions for marker
    if (!empty($field['instructions'])) {
        if (strpos($field['instructions'], '[hide_in_app]') !== false) {
            return true;
        }
    }

    // Allow custom filtering
    return apply_filters('kreaction_is_field_hidden', false, $field);
}

/**
 * Utility: Get all hidden field names for a post type
 * Useful for client-side caching of which fields to skip
 */
function kreaction_get_hidden_fields_for_post_type($post_type) {
    if (!function_exists('acf_get_field_groups')) {
        return [];
    }

    $hidden = [];
    $groups = acf_get_field_groups(['post_type' => $post_type]);

    foreach ($groups as $group) {
        $fields = acf_get_fields($group['key']);
        if (!$fields) continue;

        foreach ($fields as $field) {
            if (kreaction_acf_is_field_hidden($field)) {
                $hidden[] = $field['name'];
            }

            // Check sub fields for groups, repeaters, etc.
            if (!empty($field['sub_fields'])) {
                foreach ($field['sub_fields'] as $sub_field) {
                    if (kreaction_acf_is_field_hidden($sub_field)) {
                        $hidden[] = $field['name'] . '.' . $sub_field['name'];
                    }
                }
            }
        }
    }

    return $hidden;
}

/**
 * REST endpoint to get hidden fields for a post type
 * GET /kreaction/v1/hidden-fields/{post_type}
 */
add_action('rest_api_init', function() {
    register_rest_route('kreaction/v1', '/hidden-fields/(?P<post_type>[a-zA-Z0-9_-]+)', [
        'methods' => 'GET',
        'callback' => function($request) {
            $post_type = sanitize_text_field($request['post_type']);
            return kreaction_get_hidden_fields_for_post_type($post_type);
        },
        'permission_callback' => function() {
            return is_user_logged_in() && current_user_can('edit_posts');
        },
    ]);
});
