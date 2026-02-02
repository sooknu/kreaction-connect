<?php
/**
 * Role Permissions Partial
 *
 * @package Kreaction_Connect
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// This partial is included within the form in page-settings.php
// The actual role fields are rendered via the WordPress Settings API
?>

<p class="description" style="margin-bottom: 20px;">
    <?php esc_html_e('Control which WordPress user roles can access the Kreaction REST API. Administrators always have access.', 'kreaction-connect'); ?>
</p>

<div class="kreaction-notice warning" style="margin-bottom: 20px;">
    <strong><?php esc_html_e('Note:', 'kreaction-connect'); ?></strong>
    <?php esc_html_e('Changes to permissions take effect immediately for new API requests. Existing authenticated sessions may need to re-authenticate.', 'kreaction-connect'); ?>
</div>
