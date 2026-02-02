<?php
/**
 * Plugin Name: Kreaction Connect
 * Plugin URI: https://github.com/sooknu/kreaction-connect
 * Description: Expose ACF fields via REST API with optimized endpoints, role-based permissions, connected app tracking, and an admin dashboard. Perfect for headless WordPress and mobile app development.
 * Version: 1.0.1
 * Author: Kreaction
 * Author URI: https://kreaction.co
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kreaction-connect
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('KREACTION_CONNECT_VERSION', '1.0.1');
define('KREACTION_CONNECT_PATH', plugin_dir_path(__FILE__));
define('KREACTION_CONNECT_URL', plugin_dir_url(__FILE__));
define('KREACTION_CONNECT_CACHE_GROUP', 'kreaction_connect');
define('KREACTION_CONNECT_CACHE_EXPIRY', 5 * MINUTE_IN_SECONDS);

// Include core files
require_once KREACTION_CONNECT_PATH . 'includes/class-cache.php';
require_once KREACTION_CONNECT_PATH . 'includes/class-validator.php';
require_once KREACTION_CONNECT_PATH . 'includes/class-audit-log.php';
require_once KREACTION_CONNECT_PATH . 'includes/class-app-tracker.php';
Kreaction_App_Tracker::init(); // Initialize app tracking hooks
require_once KREACTION_CONNECT_PATH . 'includes/rest-api.php';
require_once KREACTION_CONNECT_PATH . 'includes/acf-filters.php';

// Load admin class (only in admin context)
if (is_admin()) {
    require_once KREACTION_CONNECT_PATH . 'includes/class-admin.php';
    Kreaction_Admin::instance();
}

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=kreaction-connect') . '">' . __('Settings', 'kreaction-connect') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Activation hook
register_activation_hook(__FILE__, 'kreaction_connect_activate');

function kreaction_connect_activate() {
    // Create audit log table
    Kreaction_Audit_Log::create_table();

    // Create app tracking table
    Kreaction_App_Tracker::create_table();

    // Flush rewrite rules
    flush_rewrite_rules();

    // Set default options
    add_option('kreaction_connect_settings', [
        'cache_enabled' => true,
        'audit_enabled' => true,
        'cache_expiry' => 300,
        'allowed_roles' => ['administrator', 'editor'],
        'require_capability' => 'edit_posts',
    ]);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'kreaction_connect_deactivate');

function kreaction_connect_deactivate() {
    // Clear all caches
    Kreaction_Cache::flush_all();
    flush_rewrite_rules();

    // Clear scheduled cron events
    $timestamp = wp_next_scheduled('kreaction_audit_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'kreaction_audit_cleanup');
    }
}

// Uninstall hook (cleanup)
register_uninstall_hook(__FILE__, 'kreaction_connect_uninstall');

function kreaction_connect_uninstall() {
    global $wpdb;

    // Remove database tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kreaction_audit_log");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kreaction_app_access");

    // Remove plugin options
    delete_option('kreaction_connect_settings');

    // Clear all transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_kreaction_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_kreaction_%'");

    // Clear scheduled cron events
    wp_clear_scheduled_hook('kreaction_audit_cleanup');

    // Clear any object cache entries
    wp_cache_flush();
}

/**
 * Clear caches when posts are modified
 */
add_action('save_post', function($post_id, $post) {
    if (wp_is_post_revision($post_id)) return;

    // Clear related caches
    Kreaction_Cache::delete('dashboard');
    Kreaction_Cache::delete('post_types');
    Kreaction_Cache::delete("posts_{$post->post_type}");
    Kreaction_Cache::delete("post_{$post_id}");
}, 10, 2);

add_action('delete_post', function($post_id) {
    $post = get_post($post_id);
    if ($post) {
        Kreaction_Cache::delete('dashboard');
        Kreaction_Cache::delete("posts_{$post->post_type}");
        Kreaction_Cache::delete("post_{$post_id}");
    }
});

/**
 * Clear schema cache when ACF fields change
 */
add_action('acf/update_field_group', function($field_group) {
    Kreaction_Cache::delete('schema');
    Kreaction_Cache::delete_pattern('fields_');
});

add_action('acf/delete_field_group', function($field_group) {
    Kreaction_Cache::delete('schema');
    Kreaction_Cache::delete_pattern('fields_');
});
