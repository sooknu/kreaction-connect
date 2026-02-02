<?php
/**
 * Kreaction Connect Cache Handler
 *
 * Provides server-side caching using WordPress transients.
 * Supports automatic cache invalidation and pattern-based deletion.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kreaction_Cache {

    /**
     * Cache prefix for all transients
     */
    const PREFIX = 'kreaction_';

    /**
     * Default cache expiry in seconds (5 minutes)
     */
    const DEFAULT_EXPIRY = 300;

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public static function get($key) {
        if (!self::is_enabled()) {
            return false;
        }

        $transient_key = self::PREFIX . md5($key);
        $data = get_transient($transient_key);

        if ($data !== false) {
            // Track cache hit for debugging
            self::log_hit($key);
        }

        return $data;
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $expiry Expiry time in seconds (null = default)
     * @return bool Success
     */
    public static function set($key, $data, $expiry = null) {
        if (!self::is_enabled()) {
            return false;
        }

        if ($expiry === null) {
            $settings = get_option('kreaction_connect_settings', []);
            $expiry = isset($settings['cache_expiry']) ? (int)$settings['cache_expiry'] : self::DEFAULT_EXPIRY;
        }

        $transient_key = self::PREFIX . md5($key);

        // Store the original key mapping for pattern deletion
        self::store_key_mapping($key, $transient_key);

        return set_transient($transient_key, $data, $expiry);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public static function delete($key) {
        $transient_key = self::PREFIX . md5($key);
        return delete_transient($transient_key);
    }

    /**
     * Delete all cached data matching a pattern
     *
     * @param string $pattern Key pattern to match (e.g., 'posts_' matches 'posts_puppies', 'posts_litters')
     * @return int Number of deleted entries
     */
    public static function delete_pattern($pattern) {
        global $wpdb;

        $mappings = get_option('kreaction_cache_key_mappings', []);
        $deleted = 0;

        foreach ($mappings as $original_key => $transient_key) {
            if (strpos($original_key, $pattern) === 0) {
                if (delete_transient(str_replace('_transient_', '', $transient_key))) {
                    $deleted++;
                }
                unset($mappings[$original_key]);
            }
        }

        update_option('kreaction_cache_key_mappings', $mappings);

        return $deleted;
    }

    /**
     * Flush all Kreaction caches
     *
     * @return int Number of deleted entries
     */
    public static function flush_all() {
        global $wpdb;

        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::PREFIX . '%',
                '_transient_timeout_' . self::PREFIX . '%'
            )
        );

        // Clear key mappings
        delete_option('kreaction_cache_key_mappings');

        return $count;
    }

    /**
     * Get or set cached data (convenience method)
     *
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int|null $expiry Expiry time in seconds
     * @return mixed Cached or generated data
     */
    public static function remember($key, $callback, $expiry = null) {
        $cached = self::get($key);

        if ($cached !== false) {
            return $cached;
        }

        $data = call_user_func($callback);

        if ($data !== null && $data !== false) {
            self::set($key, $data, $expiry);
        }

        return $data;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option('kreaction_connect_settings', []);
        return !empty($settings['cache_enabled']);
    }

    /**
     * Store key mapping for pattern deletion
     *
     * @param string $original_key Original cache key
     * @param string $transient_key Hashed transient key
     */
    private static function store_key_mapping($original_key, $transient_key) {
        $mappings = get_option('kreaction_cache_key_mappings', []);
        $mappings[$original_key] = $transient_key;

        // Limit stored mappings to prevent bloat
        if (count($mappings) > 1000) {
            $mappings = array_slice($mappings, -500, 500, true);
        }

        update_option('kreaction_cache_key_mappings', $mappings, false);
    }

    /**
     * Log cache hit for debugging
     *
     * @param string $key Cache key
     */
    private static function log_hit($key) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Optional: Log cache hits for debugging
            // error_log("Kreaction Cache HIT: $key");
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Cache stats
     */
    public static function get_stats() {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_' . self::PREFIX . '%'
            )
        );

        return [
            'entries' => (int)$count,
            'enabled' => self::is_enabled(),
            'prefix' => self::PREFIX,
        ];
    }
}
