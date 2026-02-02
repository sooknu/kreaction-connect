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
</div>
