<?php
/**
 * Health Check Partial
 *
 * @package Kreaction_Connect
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php esc_html_e('API Health Check', 'kreaction-connect'); ?></h3>
<p class="description">
    <?php esc_html_e('Test the Kreaction API endpoints and view system information.', 'kreaction-connect'); ?>
</p>

<div class="kreaction-actions" style="margin-top: 15px; border-top: none; padding-top: 0;">
    <button type="button" id="kreaction-test-health" class="button button-primary">
        <?php esc_html_e('Run Health Check', 'kreaction-connect'); ?>
    </button>
</div>

<div id="kreaction-health-results">
    <!-- Results will be loaded here via AJAX -->
    <div class="kreaction-health-grid">
        <div class="kreaction-health-card">
            <h4><?php esc_html_e('Quick Info', 'kreaction-connect'); ?></h4>
            <table class="kreaction-system-table">
                <tr>
                    <th><?php esc_html_e('Plugin Version', 'kreaction-connect'); ?></th>
                    <td><?php echo esc_html(KREACTION_CONNECT_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('WordPress', 'kreaction-connect'); ?></th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('PHP', 'kreaction-connect'); ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('ACF', 'kreaction-connect'); ?></th>
                    <td>
                        <?php
                        if (class_exists('ACF')) {
                            echo defined('ACF_VERSION') ? esc_html(ACF_VERSION) : esc_html__('Active', 'kreaction-connect');
                        } else {
                            echo '<span style="color: #a00;">' . esc_html__('Not installed', 'kreaction-connect') . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('REST URL', 'kreaction-connect'); ?></th>
                    <td style="word-break: break-all;"><?php echo esc_html(rest_url()); ?></td>
                </tr>
            </table>
        </div>

        <div class="kreaction-health-card">
            <h4><?php esc_html_e('Available Endpoints', 'kreaction-connect'); ?></h4>
            <table class="kreaction-system-table">
                <tr>
                    <th>/kreaction/v1/version</th>
                    <td><span class="status ok"><?php esc_html_e('Public', 'kreaction-connect'); ?></span></td>
                </tr>
                <tr>
                    <th>/kreaction/v1/health</th>
                    <td><span class="status ok"><?php esc_html_e('Public', 'kreaction-connect'); ?></span></td>
                </tr>
                <tr>
                    <th>/kreaction/v1/me</th>
                    <td><span class="status warning"><?php esc_html_e('Auth Required', 'kreaction-connect'); ?></span></td>
                </tr>
                <tr>
                    <th>/kreaction/v1/dashboard</th>
                    <td><span class="status warning"><?php esc_html_e('Auth Required', 'kreaction-connect'); ?></span></td>
                </tr>
                <tr>
                    <th>/kreaction/v1/types</th>
                    <td><span class="status warning"><?php esc_html_e('Auth Required', 'kreaction-connect'); ?></span></td>
                </tr>
                <tr>
                    <th>/kreaction/v1/posts/{type}</th>
                    <td><span class="status warning"><?php esc_html_e('Auth Required', 'kreaction-connect'); ?></span></td>
                </tr>
            </table>
            <p class="description" style="margin-top: 10px;">
                <?php esc_html_e('Click "Run Health Check" to test endpoint connectivity.', 'kreaction-connect'); ?>
            </p>
        </div>
    </div>
</div>

<div style="margin-top: 20px;">
    <h4><?php esc_html_e('Cache Status', 'kreaction-connect'); ?></h4>
    <?php
    $settings = Kreaction_Admin::get_settings();
    $cache_enabled = !empty($settings['cache_enabled']);
    ?>
    <p>
        <strong><?php esc_html_e('Caching:', 'kreaction-connect'); ?></strong>
        <?php if ($cache_enabled): ?>
            <span class="status ok"><?php esc_html_e('Enabled', 'kreaction-connect'); ?></span>
            (<?php printf(esc_html__('%d seconds expiry', 'kreaction-connect'), $settings['cache_expiry'] ?? 300); ?>)
        <?php else: ?>
            <span class="status warning"><?php esc_html_e('Disabled', 'kreaction-connect'); ?></span>
        <?php endif; ?>
    </p>

    <h4><?php esc_html_e('Audit Log Status', 'kreaction-connect'); ?></h4>
    <?php
    $audit_enabled = !empty($settings['audit_enabled']);
    $audit_count = 0;
    if (class_exists('Kreaction_Audit_Log')) {
        $audit_count = Kreaction_Audit_Log::get_count();
    }
    ?>
    <p>
        <strong><?php esc_html_e('Audit Logging:', 'kreaction-connect'); ?></strong>
        <?php if ($audit_enabled): ?>
            <span class="status ok"><?php esc_html_e('Enabled', 'kreaction-connect'); ?></span>
            (<?php printf(esc_html__('%s entries', 'kreaction-connect'), number_format_i18n($audit_count)); ?>)
        <?php else: ?>
            <span class="status warning"><?php esc_html_e('Disabled', 'kreaction-connect'); ?></span>
        <?php endif; ?>
    </p>
</div>
