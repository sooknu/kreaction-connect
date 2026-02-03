<?php
/**
 * Kreaction Connect Admin
 *
 * Admin interface for managing plugin settings, connected apps, and permissions.
 *
 * @package Kreaction_Connect
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kreaction_Admin {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Settings option name
     */
    const OPTION_NAME = 'kreaction_connect_settings';

    /**
     * Admin menu slug
     */
    const MENU_SLUG = 'kreaction-connect';

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_kreaction_revoke_app', [$this, 'ajax_revoke_app']);
        add_action('wp_ajax_kreaction_test_health', [$this, 'ajax_test_health']);
        add_action('wp_ajax_kreaction_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_kreaction_save_content_visibility', [$this, 'ajax_save_content_visibility']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            __('Kreaction Connect', 'kreaction-connect'),
            __('Kreaction Connect', 'kreaction-connect'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'kreaction_connect_settings_group',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );

        // General Section
        add_settings_section(
            'kreaction_general',
            __('General Settings', 'kreaction-connect'),
            null,
            'kreaction-connect-general'
        );

        add_settings_field(
            'cache_enabled',
            __('Enable Caching', 'kreaction-connect'),
            [$this, 'render_checkbox_field'],
            'kreaction-connect-general',
            'kreaction_general',
            [
                'id' => 'cache_enabled',
                'description' => __('Cache API responses to improve performance.', 'kreaction-connect'),
            ]
        );

        add_settings_field(
            'cache_expiry',
            __('Cache Duration', 'kreaction-connect'),
            [$this, 'render_number_field'],
            'kreaction-connect-general',
            'kreaction_general',
            [
                'id' => 'cache_expiry',
                'description' => __('Cache expiry time in seconds.', 'kreaction-connect'),
                'min' => 60,
                'max' => 3600,
                'step' => 60,
            ]
        );

        add_settings_field(
            'audit_enabled',
            __('Enable Audit Log', 'kreaction-connect'),
            [$this, 'render_checkbox_field'],
            'kreaction-connect-general',
            'kreaction_general',
            [
                'id' => 'audit_enabled',
                'description' => __('Track all content changes made through the API.', 'kreaction-connect'),
            ]
        );

        // Permissions Section
        add_settings_section(
            'kreaction_permissions',
            __('API Permissions', 'kreaction-connect'),
            [$this, 'render_permissions_section_description'],
            'kreaction-connect-permissions'
        );

        add_settings_field(
            'allowed_roles',
            __('Allowed Roles', 'kreaction-connect'),
            [$this, 'render_roles_field'],
            'kreaction-connect-permissions',
            'kreaction_permissions',
            [
                'id' => 'allowed_roles',
                'description' => __('Select which user roles can access the API.', 'kreaction-connect'),
            ]
        );

        add_settings_field(
            'require_capability',
            __('Required Capability', 'kreaction-connect'),
            [$this, 'render_capability_field'],
            'kreaction-connect-permissions',
            'kreaction_permissions',
            [
                'id' => 'require_capability',
                'description' => __('The minimum capability required to access the API.', 'kreaction-connect'),
            ]
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ('settings_page_' . self::MENU_SLUG !== $hook) {
            return;
        }

        wp_enqueue_style(
            'kreaction-admin',
            KREACTION_CONNECT_URL . 'admin/css/kreaction-admin.css',
            [],
            KREACTION_CONNECT_VERSION
        );

        wp_enqueue_script(
            'kreaction-admin',
            KREACTION_CONNECT_URL . 'admin/js/kreaction-admin.js',
            ['jquery'],
            KREACTION_CONNECT_VERSION,
            true
        );

        wp_localize_script('kreaction-admin', 'kreactionAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kreaction_admin_nonce'),
            'strings' => [
                'confirmRevoke' => __('Are you sure you want to revoke access for this app? This will remove the Application Password.', 'kreaction-connect'),
                'revoking' => __('Revoking...', 'kreaction-connect'),
                'revoked' => __('Access revoked', 'kreaction-connect'),
                'testing' => __('Testing...', 'kreaction-connect'),
                'clearing' => __('Clearing...', 'kreaction-connect'),
                'error' => __('An error occurred. Please try again.', 'kreaction-connect'),
            ],
        ]);
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include KREACTION_CONNECT_PATH . 'admin/views/page-settings.php';
    }

    /**
     * Get default settings
     */
    public function get_default_settings() {
        return [
            'cache_enabled' => true,
            'audit_enabled' => true,
            'cache_expiry' => 300,
            'allowed_roles' => ['administrator', 'editor'],
            'require_capability' => 'edit_posts',
        ];
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        $defaults = $this->get_default_settings();

        // Booleans
        $sanitized['cache_enabled'] = !empty($input['cache_enabled']);
        $sanitized['audit_enabled'] = !empty($input['audit_enabled']);

        // Cache expiry
        $sanitized['cache_expiry'] = isset($input['cache_expiry'])
            ? absint($input['cache_expiry'])
            : $defaults['cache_expiry'];
        $sanitized['cache_expiry'] = max(60, min(3600, $sanitized['cache_expiry']));

        // Allowed roles - validate against real roles
        $valid_roles = array_keys(wp_roles()->roles);
        $sanitized['allowed_roles'] = [];
        if (!empty($input['allowed_roles']) && is_array($input['allowed_roles'])) {
            foreach ($input['allowed_roles'] as $role) {
                if (in_array($role, $valid_roles, true)) {
                    $sanitized['allowed_roles'][] = $role;
                }
            }
        }
        // Ensure administrator is always allowed
        if (!in_array('administrator', $sanitized['allowed_roles'], true)) {
            array_unshift($sanitized['allowed_roles'], 'administrator');
        }

        // Required capability
        $valid_capabilities = ['edit_posts', 'publish_posts', 'edit_others_posts', 'manage_options'];
        $sanitized['require_capability'] = in_array($input['require_capability'] ?? '', $valid_capabilities, true)
            ? $input['require_capability']
            : $defaults['require_capability'];

        return $sanitized;
    }

    /**
     * Get settings
     */
    public static function get_settings() {
        $defaults = [
            'cache_enabled' => true,
            'audit_enabled' => true,
            'cache_expiry' => 300,
            'allowed_roles' => ['administrator', 'editor'],
            'require_capability' => 'edit_posts',
        ];
        return wp_parse_args(get_option(self::OPTION_NAME, []), $defaults);
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $settings = self::get_settings();
        $id = $args['id'];
        $checked = !empty($settings[$id]);
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr(self::OPTION_NAME . '[' . $id . ']'); ?>"
                   value="1"
                   <?php checked($checked); ?>>
            <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    /**
     * Render number field
     */
    public function render_number_field($args) {
        $settings = self::get_settings();
        $id = $args['id'];
        $value = isset($settings[$id]) ? $settings[$id] : '';
        ?>
        <input type="number"
               name="<?php echo esc_attr(self::OPTION_NAME . '[' . $id . ']'); ?>"
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($args['min'] ?? 0); ?>"
               max="<?php echo esc_attr($args['max'] ?? 9999); ?>"
               step="<?php echo esc_attr($args['step'] ?? 1); ?>"
               class="small-text">
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * Render permissions section description
     */
    public function render_permissions_section_description() {
        echo '<p>' . esc_html__('Control which users can access the Kreaction REST API endpoints.', 'kreaction-connect') . '</p>';
    }

    /**
     * Render roles checkboxes
     */
    public function render_roles_field($args) {
        $settings = self::get_settings();
        $allowed_roles = $settings['allowed_roles'] ?? [];
        $roles = wp_roles()->roles;
        ?>
        <fieldset>
            <?php foreach ($roles as $role_slug => $role_data): ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="<?php echo esc_attr(self::OPTION_NAME . '[allowed_roles][]'); ?>"
                           value="<?php echo esc_attr($role_slug); ?>"
                           <?php checked(in_array($role_slug, $allowed_roles, true)); ?>
                           <?php disabled($role_slug === 'administrator'); ?>>
                    <?php echo esc_html(translate_user_role($role_data['name'])); ?>
                    <?php if ($role_slug === 'administrator'): ?>
                        <span class="description"><?php esc_html_e('(always allowed)', 'kreaction-connect'); ?></span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * Render capability dropdown
     */
    public function render_capability_field($args) {
        $settings = self::get_settings();
        $current = $settings['require_capability'] ?? 'edit_posts';
        $capabilities = [
            'edit_posts' => __('Edit Posts (Contributor+)', 'kreaction-connect'),
            'publish_posts' => __('Publish Posts (Author+)', 'kreaction-connect'),
            'edit_others_posts' => __('Edit Others\' Posts (Editor+)', 'kreaction-connect'),
            'manage_options' => __('Manage Options (Administrator)', 'kreaction-connect'),
        ];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME . '[require_capability]'); ?>">
            <?php foreach ($capabilities as $cap => $label): ?>
                <option value="<?php echo esc_attr($cap); ?>" <?php selected($current, $cap); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php
    }

    /**
     * AJAX: Revoke app access
     */
    public function ajax_revoke_app() {
        check_ajax_referer('kreaction_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'kreaction-connect')]);
        }

        $app_uuid = sanitize_text_field($_POST['app_uuid'] ?? '');
        $user_id = absint($_POST['user_id'] ?? 0);

        if (empty($app_uuid) || empty($user_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'kreaction-connect')]);
        }

        // Try to revoke the Application Password
        $revoked = $this->revoke_application_password($user_id, $app_uuid);

        // Remove from tracking table
        if (class_exists('Kreaction_App_Tracker')) {
            Kreaction_App_Tracker::remove_app($user_id, $app_uuid);
        }

        if ($revoked) {
            wp_send_json_success(['message' => __('App access revoked successfully.', 'kreaction-connect')]);
        } else {
            // App was removed from tracking but password might have been already removed
            wp_send_json_success(['message' => __('App removed from tracking.', 'kreaction-connect')]);
        }
    }

    /**
     * Revoke an Application Password
     */
    private function revoke_application_password($user_id, $app_uuid) {
        if (!function_exists('wp_get_application_passwords_for_user')) {
            return false;
        }

        $passwords = WP_Application_Passwords::get_user_application_passwords($user_id);

        foreach ($passwords as $password) {
            if (isset($password['uuid']) && $password['uuid'] === $app_uuid) {
                return WP_Application_Passwords::delete_application_password($user_id, $password['uuid']);
            }
        }

        return false;
    }

    /**
     * AJAX: Test health endpoints
     */
    public function ajax_test_health() {
        check_ajax_referer('kreaction_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'kreaction-connect')]);
        }

        $results = [];

        // Test version endpoint
        $version_url = rest_url('kreaction/v1/version');
        $version_response = wp_remote_get($version_url, ['timeout' => 10]);
        $results['version'] = [
            'endpoint' => '/kreaction/v1/version',
            'status' => is_wp_error($version_response) ? 'error' : wp_remote_retrieve_response_code($version_response),
            'message' => is_wp_error($version_response) ? $version_response->get_error_message() : 'OK',
        ];

        // Test health endpoint
        $health_url = rest_url('kreaction/v1/health');
        $health_response = wp_remote_get($health_url, ['timeout' => 10]);
        $results['health'] = [
            'endpoint' => '/kreaction/v1/health',
            'status' => is_wp_error($health_response) ? 'error' : wp_remote_retrieve_response_code($health_response),
            'message' => is_wp_error($health_response) ? $health_response->get_error_message() : 'OK',
        ];

        // System info
        $results['system'] = [
            'wordpress' => get_bloginfo('version'),
            'php' => PHP_VERSION,
            'plugin' => KREACTION_CONNECT_VERSION,
            'acf' => class_exists('ACF') ? (defined('ACF_VERSION') ? ACF_VERSION : 'Active') : 'Not installed',
            'rest_url' => rest_url(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        wp_send_json_success($results);
    }

    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('kreaction_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'kreaction-connect')]);
        }

        if (class_exists('Kreaction_Cache')) {
            Kreaction_Cache::flush_all();
        }

        wp_send_json_success(['message' => __('Cache cleared successfully.', 'kreaction-connect')]);
    }

    /**
     * AJAX: Save content visibility settings
     */
    public function ajax_save_content_visibility() {
        check_ajax_referer('kreaction_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'kreaction-connect')]);
        }

        $visibility = [];
        $data = isset($_POST['visibility']) ? $_POST['visibility'] : [];

        // Get valid roles for validation
        $valid_roles = array_keys(wp_roles()->roles);

        if (is_array($data)) {
            foreach ($data as $post_type => $roles) {
                $post_type = sanitize_key($post_type);
                if (empty($post_type)) continue;

                $sanitized_roles = [];
                if (is_array($roles)) {
                    foreach ($roles as $role) {
                        $role = sanitize_key($role);
                        if (in_array($role, $valid_roles, true)) {
                            $sanitized_roles[] = $role;
                        }
                    }
                }

                // Only store if there are restrictions (not all roles selected)
                // This keeps backward compatibility - unconfigured types are visible to all
                if (!empty($sanitized_roles)) {
                    $visibility[$post_type] = $sanitized_roles;
                }
            }
        }

        self::save_content_visibility($visibility);

        // Clear cache since content visibility changed
        if (class_exists('Kreaction_Cache')) {
            Kreaction_Cache::flush_all();
        }

        wp_send_json_success(['message' => __('Content visibility settings saved.', 'kreaction-connect')]);
    }

    /**
     * Check if a user's role is allowed to access the API
     */
    public static function is_user_role_allowed($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        $settings = self::get_settings();
        $allowed_roles = $settings['allowed_roles'] ?? ['administrator', 'editor'];

        foreach ($user->roles as $role) {
            if (in_array($role, $allowed_roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has the required capability
     */
    public static function user_has_required_capability($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }

        $settings = self::get_settings();
        $required_cap = $settings['require_capability'] ?? 'edit_posts';

        return user_can($user, $required_cap);
    }

    /**
     * Check if a user can access a specific post type
     *
     * @param WP_User|null $user The user to check (defaults to current user)
     * @param string $post_type_slug The post type slug
     * @return bool Whether the user can access the post type
     */
    public static function can_user_access_post_type($user = null, $post_type_slug = '') {
        if (!$user) {
            $user = wp_get_current_user();
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        // Administrators always have full access
        if (in_array('administrator', (array) $user->roles, true)) {
            return true;
        }

        $visibility = get_option('kreaction_content_visibility', []);

        // If not configured for this post type, allow access (backward compatible)
        if (empty($visibility) || !isset($visibility[$post_type_slug])) {
            return true;
        }

        $allowed_roles = $visibility[$post_type_slug];

        // Check if user has any of the allowed roles
        return (bool) array_intersect((array) $user->roles, (array) $allowed_roles);
    }

    /**
     * Get content visibility settings
     *
     * @return array Associative array of post_type => allowed_roles
     */
    public static function get_content_visibility() {
        return get_option('kreaction_content_visibility', []);
    }

    /**
     * Save content visibility settings
     *
     * @param array $visibility Associative array of post_type => allowed_roles
     * @return bool Whether the option was updated
     */
    public static function save_content_visibility($visibility) {
        return update_option('kreaction_content_visibility', $visibility);
    }
}
