<?php
/**
 * Kreaction Connect Settings Page
 *
 * @package Kreaction_Connect
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kreaction-wrap">
    <div class="kreaction-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <span class="version">v<?php echo esc_html(KREACTION_CONNECT_VERSION); ?></span>
    </div>

    <?php settings_errors(); ?>

    <nav class="kreaction-tabs nav-tab-wrapper">
        <a href="#tab-general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'kreaction-connect'); ?></a>
        <a href="#tab-apps" class="nav-tab"><?php esc_html_e('Connected Apps', 'kreaction-connect'); ?></a>
        <a href="#tab-health" class="nav-tab"><?php esc_html_e('Health Check', 'kreaction-connect'); ?></a>
        <a href="#tab-permissions" class="nav-tab"><?php esc_html_e('Permissions', 'kreaction-connect'); ?></a>
        <a href="#tab-content-visibility" class="nav-tab"><?php esc_html_e('Content Visibility', 'kreaction-connect'); ?></a>
    </nav>

    <!-- General Tab -->
    <div id="tab-general" class="kreaction-tab-content active">
        <form method="post" action="options.php">
            <?php
            settings_fields('kreaction_connect_settings_group');
            do_settings_sections('kreaction-connect-general');
            ?>

            <div class="kreaction-actions">
                <?php submit_button(__('Save Settings', 'kreaction-connect'), 'primary', 'submit', false); ?>
                <button type="button" id="kreaction-clear-cache" class="button button-secondary">
                    <?php esc_html_e('Clear Cache', 'kreaction-connect'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Connected Apps Tab -->
    <div id="tab-apps" class="kreaction-tab-content">
        <?php include KREACTION_CONNECT_PATH . 'admin/views/partial-apps.php'; ?>
    </div>

    <!-- Health Check Tab -->
    <div id="tab-health" class="kreaction-tab-content">
        <?php include KREACTION_CONNECT_PATH . 'admin/views/partial-health.php'; ?>
    </div>

    <!-- Permissions Tab -->
    <div id="tab-permissions" class="kreaction-tab-content">
        <form method="post" action="options.php">
            <?php
            settings_fields('kreaction_connect_settings_group');
            do_settings_sections('kreaction-connect-permissions');
            submit_button(__('Save Permissions', 'kreaction-connect'));
            ?>
        </form>
    </div>

    <!-- Content Visibility Tab -->
    <div id="tab-content-visibility" class="kreaction-tab-content">
        <?php
        // Get all public post types
        $post_types = get_post_types(['public' => true], 'objects');
        $exclude_types = ['attachment'];

        // Get all roles
        $roles = wp_roles()->roles;
        $allowed_api_roles = Kreaction_Admin::get_settings()['allowed_roles'] ?? ['administrator', 'editor'];

        // Get current visibility settings
        $visibility = Kreaction_Admin::get_content_visibility();
        ?>

        <div class="kreaction-content-visibility">
            <h2><?php esc_html_e('Content Type Visibility by Role', 'kreaction-connect'); ?></h2>
            <p class="description">
                <?php esc_html_e('Control which content types each user role can see in the app. Unchecked types will be hidden from that role. Administrator always has full access.', 'kreaction-connect'); ?>
            </p>

            <div class="kreaction-visibility-matrix">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th class="column-content-type"><?php esc_html_e('Content Type', 'kreaction-connect'); ?></th>
                            <?php foreach ($roles as $role_slug => $role_data):
                                // Only show roles that have API access
                                if (!in_array($role_slug, $allowed_api_roles, true)) continue;
                            ?>
                                <th class="column-role">
                                    <?php echo esc_html(translate_user_role($role_data['name'])); ?>
                                    <?php if ($role_slug === 'administrator'): ?>
                                        <span class="dashicons dashicons-lock" title="<?php esc_attr_e('Always has access', 'kreaction-connect'); ?>"></span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($post_types as $type_slug => $type_obj):
                            if (in_array($type_slug, $exclude_types, true)) continue;

                            // Get configured roles for this type, default to all allowed roles
                            $type_roles = isset($visibility[$type_slug]) ? $visibility[$type_slug] : $allowed_api_roles;
                        ?>
                            <tr data-post-type="<?php echo esc_attr($type_slug); ?>">
                                <td class="column-content-type">
                                    <strong><?php echo esc_html($type_obj->labels->name); ?></strong>
                                    <br><code><?php echo esc_html($type_slug); ?></code>
                                </td>
                                <?php foreach ($roles as $role_slug => $role_data):
                                    if (!in_array($role_slug, $allowed_api_roles, true)) continue;

                                    $is_admin = ($role_slug === 'administrator');
                                    $is_checked = $is_admin || in_array($role_slug, $type_roles, true);
                                ?>
                                    <td class="column-role">
                                        <input type="checkbox"
                                               class="visibility-checkbox"
                                               data-post-type="<?php echo esc_attr($type_slug); ?>"
                                               data-role="<?php echo esc_attr($role_slug); ?>"
                                               <?php checked($is_checked); ?>
                                               <?php disabled($is_admin); ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="kreaction-visibility-actions">
                <button type="button" id="kreaction-save-visibility" class="button button-primary">
                    <?php esc_html_e('Save Content Visibility', 'kreaction-connect'); ?>
                </button>
                <span class="spinner"></span>
                <span class="kreaction-save-status"></span>
            </p>

            <div class="kreaction-visibility-note">
                <p><strong><?php esc_html_e('Note:', 'kreaction-connect'); ?></strong></p>
                <ul>
                    <li><?php esc_html_e('Only roles with API access (configured in Permissions tab) are shown.', 'kreaction-connect'); ?></li>
                    <li><?php esc_html_e('Administrator role always has access to all content types.', 'kreaction-connect'); ?></li>
                    <li><?php esc_html_e('Unconfigured content types are visible to all allowed roles by default.', 'kreaction-connect'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
