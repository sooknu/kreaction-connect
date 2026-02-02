<?php
/**
 * Kreaction App Tracker
 *
 * Tracks which apps are accessing the Kreaction API via Application Passwords.
 *
 * @package Kreaction_Connect
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kreaction_App_Tracker {

    /**
     * Table name (without prefix)
     */
    const TABLE_NAME = 'kreaction_app_access';

    /**
     * Store current app password info (set during auth)
     */
    private static $current_app = null;

    /**
     * Initialize hooks
     */
    public static function init() {
        // Hook into Application Password authentication to capture app info
        add_action('application_password_did_authenticate', [__CLASS__, 'capture_app_info'], 10, 2);
    }

    /**
     * Capture app info when authentication happens
     *
     * @param WP_User $user The authenticated user
     * @param array $app The Application Password used
     */
    public static function capture_app_info($user, $app) {
        self::$current_app = $app;
    }

    /**
     * Create the tracking table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            app_uuid varchar(36) NOT NULL,
            app_name varchar(255) NOT NULL,
            last_access datetime NOT NULL,
            last_ip varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            access_count bigint(20) UNSIGNED DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_app (user_id, app_uuid),
            KEY user_id (user_id),
            KEY last_access (last_access)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Drop the tracking table
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    /**
     * Check if the tracking table exists
     *
     * @return bool
     */
    public static function table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }

    /**
     * Ensure the table exists, create if not
     */
    public static function maybe_create_table() {
        if (!self::table_exists()) {
            self::create_table();
        }
    }

    /**
     * Track API access
     *
     * Called on each authenticated API request.
     *
     * @param int $user_id The user ID
     * @return bool Whether tracking was successful
     */
    public static function track_access($user_id) {
        global $wpdb;

        // Ensure table exists (handles upgrades from older versions)
        static $table_checked = false;
        if (!$table_checked) {
            self::maybe_create_table();
            $table_checked = true;
        }

        // Get the Application Password UUID from the request
        $app_password = self::get_current_app_password($user_id);

        if (!$app_password) {
            return false;
        }

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $app_uuid = $app_password['uuid'];
        $app_name = $app_password['name'];
        $now = current_time('mysql');
        $ip = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

        // Try to update existing record
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name
             SET last_access = %s,
                 last_ip = %s,
                 user_agent = %s,
                 access_count = access_count + 1,
                 app_name = %s
             WHERE user_id = %d AND app_uuid = %s",
            $now,
            $ip,
            $user_agent,
            $app_name,
            $user_id,
            $app_uuid
        ));

        // If no rows updated, insert new record
        if ($updated === 0) {
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'app_uuid' => $app_uuid,
                    'app_name' => $app_name,
                    'last_access' => $now,
                    'last_ip' => $ip,
                    'user_agent' => $user_agent,
                    'access_count' => 1,
                    'created_at' => $now,
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
            );
        }

        return true;
    }

    /**
     * Get the current Application Password from the request
     *
     * @param int $user_id The user ID
     * @return array|null The Application Password data or null
     */
    private static function get_current_app_password($user_id) {
        // First check if we captured app info via the action hook
        if (self::$current_app !== null) {
            return self::$current_app;
        }

        // Fallback: try to get UUID from WP_Application_Passwords
        if (!class_exists('WP_Application_Passwords')) {
            return null;
        }

        // WordPress sets this when authenticating via Application Passwords
        // This method is available since WordPress 5.6
        if (!method_exists('WP_Application_Passwords', 'get_current_uuid')) {
            return null;
        }

        $app_uuid = WP_Application_Passwords::get_current_uuid();

        if (!$app_uuid) {
            return null;
        }

        // Get the password details
        $passwords = WP_Application_Passwords::get_user_application_passwords($user_id);

        foreach ($passwords as $password) {
            if (isset($password['uuid']) && $password['uuid'] === $app_uuid) {
                return $password;
            }
        }

        return null;
    }

    /**
     * Get all tracked apps
     *
     * @param array $args Query arguments
     * @return array Array of tracked apps
     */
    public static function get_apps($args = []) {
        global $wpdb;

        $defaults = [
            'per_page' => 50,
            'page' => 1,
            'user_id' => null,
            'orderby' => 'last_access',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $where = ['1=1'];
        $values = [];

        if ($args['user_id']) {
            $where[] = 'a.user_id = %d';
            $values[] = $args['user_id'];
        }

        $where_clause = implode(' AND ', $where);
        $orderby = in_array($args['orderby'], ['last_access', 'created_at', 'access_count', 'app_name'], true)
            ? $args['orderby']
            : 'last_access';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT a.*, u.display_name as user_display_name, u.user_email
                FROM $table_name a
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE $where_clause
                ORDER BY a.$orderby $order
                LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);
    }

    /**
     * Get total count of tracked apps
     *
     * @return int
     */
    public static function get_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Remove an app from tracking
     *
     * @param int $user_id User ID
     * @param string $app_uuid App UUID
     * @return bool Whether removal was successful
     */
    public static function remove_app($user_id, $app_uuid) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return (bool) $wpdb->delete(
            $table_name,
            [
                'user_id' => $user_id,
                'app_uuid' => $app_uuid,
            ],
            ['%d', '%s']
        );
    }

    /**
     * Clean up old entries
     *
     * @param int $days Remove entries older than this many days without access
     * @return int Number of deleted entries
     */
    public static function cleanup($days = 90) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE last_access < %s",
            $date
        ));
    }

    /**
     * Get client IP address
     *
     * @return string|null
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }
}
