<?php
/**
 * Connected Apps Partial
 *
 * @package Kreaction_Connect
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get tracked apps
$apps = [];
if (class_exists('Kreaction_App_Tracker')) {
    $apps = Kreaction_App_Tracker::get_apps([
        'per_page' => 100,
        'orderby' => 'last_access',
        'order' => 'DESC',
    ]);
}
?>

<h3><?php esc_html_e('Connected Apps', 'kreaction-connect'); ?></h3>
<p class="description">
    <?php esc_html_e('Apps that have connected to your site via the Kreaction API. You can revoke access to remove the Application Password.', 'kreaction-connect'); ?>
</p>

<?php if (empty($apps)): ?>
    <div class="kreaction-empty-state">
        <span class="dashicons dashicons-smartphone"></span>
        <p><?php esc_html_e('No connected apps yet. Apps will appear here when they connect via the API.', 'kreaction-connect'); ?></p>
    </div>
<?php else: ?>
    <table class="kreaction-apps-table widefat">
        <thead>
            <tr>
                <th><?php esc_html_e('App Name', 'kreaction-connect'); ?></th>
                <th><?php esc_html_e('User', 'kreaction-connect'); ?></th>
                <th><?php esc_html_e('Last Access', 'kreaction-connect'); ?></th>
                <th><?php esc_html_e('IP Address', 'kreaction-connect'); ?></th>
                <th><?php esc_html_e('Requests', 'kreaction-connect'); ?></th>
                <th><?php esc_html_e('Actions', 'kreaction-connect'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apps as $app): ?>
                <tr>
                    <td class="app-name">
                        <?php echo esc_html($app['app_name']); ?>
                        <br>
                        <span class="user-agent" title="<?php echo esc_attr($app['user_agent']); ?>">
                            <?php echo esc_html(wp_trim_words($app['user_agent'] ?? '', 5, '...')); ?>
                        </span>
                    </td>
                    <td class="app-user">
                        <?php
                        $user_display = $app['user_display_name'] ?? '';
                        $user_email = $app['user_email'] ?? '';
                        if ($user_display):
                        ?>
                            <strong><?php echo esc_html($user_display); ?></strong>
                            <br>
                            <small><?php echo esc_html($user_email); ?></small>
                        <?php else: ?>
                            <em><?php esc_html_e('Unknown user', 'kreaction-connect'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td class="app-stats">
                        <?php
                        $last_access = strtotime($app['last_access']);
                        echo esc_html(human_time_diff($last_access, current_time('timestamp'))) . ' ' . esc_html__('ago', 'kreaction-connect');
                        ?>
                        <br>
                        <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_access)); ?></small>
                    </td>
                    <td class="ip-address">
                        <?php echo esc_html($app['last_ip'] ?? '-'); ?>
                    </td>
                    <td>
                        <?php echo esc_html(number_format_i18n($app['access_count'])); ?>
                    </td>
                    <td>
                        <a href="#"
                           class="revoke-btn"
                           data-uuid="<?php echo esc_attr($app['app_uuid']); ?>"
                           data-user="<?php echo esc_attr($app['user_id']); ?>">
                            <?php esc_html_e('Revoke', 'kreaction-connect'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="description" style="margin-top: 15px;">
        <?php
        printf(
            esc_html__('Total: %s connected app(s)', 'kreaction-connect'),
            '<strong>' . count($apps) . '</strong>'
        );
        ?>
    </p>
<?php endif; ?>
