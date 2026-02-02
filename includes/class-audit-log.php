<?php
/**
 * Kreaction Connect Audit Log
 *
 * Tracks all content changes made through the Kreaction API.
 * Stores who changed what and when.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kreaction_Audit_Log {

    /**
     * Table name (without prefix)
     */
    const TABLE_NAME = 'kreaction_audit_log';

    /**
     * Action types
     */
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_UPLOAD = 'upload';
    const ACTION_BATCH = 'batch';

    /**
     * Create the audit log table
     */
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            user_name varchar(100) NOT NULL,
            action varchar(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) UNSIGNED DEFAULT NULL,
            object_title varchar(200) DEFAULT NULL,
            changes longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log an action
     *
     * @param string $action Action type (create, update, delete, etc.)
     * @param string $object_type Object type (post, media, etc.)
     * @param int|null $object_id Object ID
     * @param string|null $object_title Object title
     * @param array|null $changes Changed data
     * @return int|false Insert ID or false on failure
     */
    public static function log($action, $object_type, $object_id = null, $object_title = null, $changes = null) {
        if (!self::is_enabled()) {
            return false;
        }

        global $wpdb;

        $user = wp_get_current_user();
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $data = [
            'user_id' => $user->ID,
            'user_name' => $user->display_name ?: $user->user_login,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'object_title' => $object_title ? mb_substr($object_title, 0, 200) : null,
            'changes' => $changes ? wp_json_encode($changes) : null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        ];

        $result = $wpdb->insert($table_name, $data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Log a post creation
     *
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @param array $data Post data
     * @return int|false
     */
    public static function log_post_created($post_id, $post_type, $data = []) {
        $title = $data['title'] ?? get_the_title($post_id);
        return self::log(self::ACTION_CREATE, $post_type, $post_id, $title, [
            'status' => $data['status'] ?? 'draft',
            'fields' => isset($data['fields']) ? array_keys($data['fields']) : [],
        ]);
    }

    /**
     * Log a post update
     *
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @param array $changes Changed fields
     * @return int|false
     */
    public static function log_post_updated($post_id, $post_type, $changes = []) {
        $title = get_the_title($post_id);
        return self::log(self::ACTION_UPDATE, $post_type, $post_id, $title, [
            'changed_fields' => array_keys($changes),
        ]);
    }

    /**
     * Log a post deletion
     *
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @param string $title Post title
     * @param bool $force Whether it was a force delete
     * @return int|false
     */
    public static function log_post_deleted($post_id, $post_type, $title, $force = false) {
        return self::log(self::ACTION_DELETE, $post_type, $post_id, $title, [
            'force' => $force,
        ]);
    }

    /**
     * Log a media upload
     *
     * @param int $attachment_id Attachment ID
     * @param string $filename Original filename
     * @return int|false
     */
    public static function log_media_uploaded($attachment_id, $filename) {
        return self::log(self::ACTION_UPLOAD, 'media', $attachment_id, $filename, [
            'mime_type' => get_post_mime_type($attachment_id),
        ]);
    }

    /**
     * Log a batch operation
     *
     * @param string $operation Operation type
     * @param int $count Number of items affected
     * @param array $ids Item IDs
     * @return int|false
     */
    public static function log_batch_operation($operation, $count, $ids = []) {
        return self::log(self::ACTION_BATCH, 'batch', null, $operation, [
            'count' => $count,
            'ids' => $ids,
        ]);
    }

    /**
     * Get audit log entries
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public static function get_entries($args = []) {
        global $wpdb;

        $defaults = [
            'per_page' => 50,
            'page' => 1,
            'user_id' => null,
            'action' => null,
            'object_type' => null,
            'object_id' => null,
            'from_date' => null,
            'to_date' => null,
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $where = ['1=1'];
        $values = [];

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        if ($args['action']) {
            $where[] = 'action = %s';
            $values[] = $args['action'];
        }

        if ($args['object_type']) {
            $where[] = 'object_type = %s';
            $values[] = $args['object_type'];
        }

        if ($args['object_id']) {
            $where[] = 'object_id = %d';
            $values[] = $args['object_id'];
        }

        if ($args['from_date']) {
            $where[] = 'created_at >= %s';
            $values[] = $args['from_date'];
        }

        if ($args['to_date']) {
            $where[] = 'created_at <= %s';
            $values[] = $args['to_date'];
        }

        $where_clause = implode(' AND ', $where);
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at $order LIMIT %d OFFSET %d";
        $values[] = $args['per_page'];
        $values[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A);

        // Decode JSON changes
        foreach ($results as &$row) {
            if ($row['changes']) {
                $row['changes'] = json_decode($row['changes'], true);
            }
        }

        return $results;
    }

    /**
     * Get total count of entries
     *
     * @param array $args Filter arguments
     * @return int
     */
    public static function get_count($args = []) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $where = ['1=1'];
        $values = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        if (!empty($args['action'])) {
            $where[] = 'action = %s';
            $values[] = $args['action'];
        }

        if (!empty($args['object_type'])) {
            $where[] = 'object_type = %s';
            $values[] = $args['object_type'];
        }

        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";

        if (!empty($values)) {
            return (int)$wpdb->get_var($wpdb->prepare($sql, $values));
        }

        return (int)$wpdb->get_var($sql);
    }

    /**
     * Clean up old log entries
     *
     * @param int $days Keep entries newer than this many days
     * @return int Number of deleted entries
     */
    public static function cleanup($days = 90) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $date
        ));
    }

    /**
     * Check if audit logging is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option('kreaction_connect_settings', []);
        return !empty($settings['audit_enabled']);
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
                // Handle comma-separated IPs (X-Forwarded-For)
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

/**
 * Schedule cleanup cron job
 */
add_action('kreaction_audit_cleanup', function() {
    Kreaction_Audit_Log::cleanup(90);
});

if (!wp_next_scheduled('kreaction_audit_cleanup')) {
    wp_schedule_event(time(), 'daily', 'kreaction_audit_cleanup');
}
