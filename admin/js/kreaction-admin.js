/**
 * Kreaction Connect Admin Scripts
 *
 * @package Kreaction_Connect
 * @since 2.1.0
 */

(function($) {
    'use strict';

    // Tab switching
    function initTabs() {
        $('.kreaction-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();

            var target = $(this).attr('href');

            // Update tab state
            $('.kreaction-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            // Show target content
            $('.kreaction-tab-content').removeClass('active');
            $(target).addClass('active');

            // Update URL hash
            window.location.hash = target;
        });

        // Handle initial hash
        var hash = window.location.hash;
        if (hash && $(hash).length) {
            $('.kreaction-tabs .nav-tab[href="' + hash + '"]').click();
        }
    }

    // Revoke app access
    function initRevokeApp() {
        $(document).on('click', '.revoke-btn', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.hasClass('disabled')) return;

            if (!confirm(kreactionAdmin.strings.confirmRevoke)) {
                return;
            }

            var appUuid = $btn.data('uuid');
            var userId = $btn.data('user');
            var $row = $btn.closest('tr');

            $btn.addClass('disabled').text(kreactionAdmin.strings.revoking);

            $.ajax({
                url: kreactionAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kreaction_revoke_app',
                    nonce: kreactionAdmin.nonce,
                    app_uuid: appUuid,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            updateAppsCount();
                        });
                        showNotice('success', response.data.message);
                    } else {
                        $btn.removeClass('disabled').text('Revoke');
                        showNotice('error', response.data.message || kreactionAdmin.strings.error);
                    }
                },
                error: function() {
                    $btn.removeClass('disabled').text('Revoke');
                    showNotice('error', kreactionAdmin.strings.error);
                }
            });
        });
    }

    // Update apps count after removal
    function updateAppsCount() {
        var count = $('.kreaction-apps-table tbody tr').length;
        if (count === 0) {
            $('.kreaction-apps-table').replaceWith(
                '<div class="kreaction-empty-state">' +
                '<span class="dashicons dashicons-smartphone"></span>' +
                '<p>No connected apps yet. Apps will appear here when they connect via the API.</p>' +
                '</div>'
            );
        }
    }

    // Test health endpoints
    function initHealthCheck() {
        $('#kreaction-test-health').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $results = $('#kreaction-health-results');

            $btn.prop('disabled', true).html('<span class="kreaction-loading"></span>' + kreactionAdmin.strings.testing);
            $results.html('<p>Running health checks...</p>');

            $.ajax({
                url: kreactionAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kreaction_test_health',
                    nonce: kreactionAdmin.nonce
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Run Health Check');

                    if (response.success) {
                        renderHealthResults(response.data, $results);
                    } else {
                        $results.html('<div class="kreaction-notice error">' + (response.data.message || kreactionAdmin.strings.error) + '</div>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Run Health Check');
                    $results.html('<div class="kreaction-notice error">' + kreactionAdmin.strings.error + '</div>');
                }
            });
        });
    }

    // Render health check results
    function renderHealthResults(data, $container) {
        var html = '<div class="kreaction-health-grid">';

        // Endpoint tests
        html += '<div class="kreaction-health-card">';
        html += '<h4>API Endpoints</h4>';

        if (data.version) {
            var vStatus = data.version.status === 200 ? 'ok' : 'error';
            html += '<p><strong>' + data.version.endpoint + '</strong> <span class="status ' + vStatus + '">' + data.version.status + '</span></p>';
        }

        if (data.health) {
            var hStatus = data.health.status === 200 ? 'ok' : 'error';
            html += '<p><strong>' + data.health.endpoint + '</strong> <span class="status ' + hStatus + '">' + data.health.status + '</span></p>';
        }

        html += '</div>';

        // System info
        if (data.system) {
            html += '<div class="kreaction-health-card">';
            html += '<h4>System Information</h4>';
            html += '<table class="kreaction-system-table">';
            html += '<tr><th>WordPress</th><td>' + escapeHtml(data.system.wordpress) + '</td></tr>';
            html += '<tr><th>PHP</th><td>' + escapeHtml(data.system.php) + '</td></tr>';
            html += '<tr><th>Plugin Version</th><td>' + escapeHtml(data.system.plugin) + '</td></tr>';
            html += '<tr><th>ACF</th><td>' + escapeHtml(data.system.acf) + '</td></tr>';
            html += '<tr><th>REST URL</th><td>' + escapeHtml(data.system.rest_url) + '</td></tr>';
            html += '<tr><th>Memory Limit</th><td>' + escapeHtml(data.system.memory_limit) + '</td></tr>';
            html += '</table>';
            html += '</div>';
        }

        html += '</div>';
        $container.html(html);
    }

    // Clear cache
    function initClearCache() {
        $('#kreaction-clear-cache').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);

            $btn.prop('disabled', true).html('<span class="kreaction-loading"></span>' + kreactionAdmin.strings.clearing);

            $.ajax({
                url: kreactionAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'kreaction_clear_cache',
                    nonce: kreactionAdmin.nonce
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Clear Cache');

                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || kreactionAdmin.strings.error);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Clear Cache');
                    showNotice('error', kreactionAdmin.strings.error);
                }
            });
        });
    }

    // Show admin notice
    function showNotice(type, message) {
        var $notice = $('<div class="kreaction-notice ' + type + '">' + escapeHtml(message) + '</div>');
        $('.kreaction-wrap .kreaction-header').after($notice);

        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize on document ready
    $(document).ready(function() {
        initTabs();
        initRevokeApp();
        initHealthCheck();
        initClearCache();
    });

})(jQuery);
