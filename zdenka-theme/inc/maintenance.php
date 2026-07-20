<?php
defined('ABSPATH') || exit;
// ── Maintenance Mode ─────────────────────────────────────────────────────

add_action('template_redirect', function() {
    if (!get_option('zc_maintenance_on', 0)) return;
    if (is_admin()) return;
    if (current_user_can('manage_options')) return;

    // Check allowed roles
    $allowed_roles = get_option('zc_maintenance_roles', []);
    $allowed_users = get_option('zc_maintenance_users', []);

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        foreach ($allowed_roles as $role) {
            if (in_array($role, (array)$user->roles)) return;
        }
        if (in_array($user->ID, (array)$allowed_users)) return;
    }

    if ($_SERVER['REQUEST_URI'] === '/wp-login.php') return;
    // Preview for admin
    if (current_user_can('manage_options') && isset($_GET['preview_maintenance'])) { /* fall through */ }
    elseif (current_user_can('manage_options')) return;

    status_header(503);
    nocache_headers();
    include get_stylesheet_directory() . '/inc/maintenance-page.php';
    exit;
}, 1);
