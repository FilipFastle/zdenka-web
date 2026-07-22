<?php
defined('ABSPATH') || exit;
// ── Maintenance Mode ─────────────────────────────────────────────────────

add_action('template_redirect', function() {
    if (!get_option('zc_maintenance_on', 0)) return;
    if (is_admin()) return;
    // Admin má web normálne prístupný – okrem náhľadu údržby (?preview_maintenance=1)
    if (current_user_can('manage_options') && !isset($_GET['preview_maintenance'])) return;

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

// ── Dashboard widget: rýchle zapnutie/vypnutie stránky ─────────────────────
add_action('wp_dashboard_setup', function () {
    if (!current_user_can('manage_options')) return;
    wp_add_dashboard_widget('zc_site_toggle', 'Stav webu', 'zc_dashboard_toggle_widget');
});

function zc_dashboard_toggle_widget() {
    $on  = (int) get_option('zc_maintenance_on', 0);
    $url = wp_nonce_url(admin_url('admin-post.php?action=zc_toggle_maint'), 'zc_toggle_maint');
    ?>
    <div style="text-align:center;padding:6px 0">
        <div style="display:inline-flex;align-items:center;gap:9px;font-size:14px;font-weight:600;margin-bottom:16px;color:<?php echo $on ? '#b45309' : '#15803d' ?>">
            <span style="width:11px;height:11px;border-radius:50%;background:<?php echo $on ? '#f59e0b' : '#16a34a' ?>;box-shadow:0 0 0 4px <?php echo $on ? 'rgba(245,158,11,.18)' : 'rgba(22,163,74,.18)' ?>"></span>
            <?php echo $on ? 'Web je ZATVORENÝ pre návštevníkov' : 'Web je online a viditeľný'; ?>
        </div>
        <p style="color:#666;font-size:12px;margin:0 0 16px">
            <?php echo $on
                ? 'Návštevníci vidia stránku „Pracujeme na webe". Ty ako prihlásený admin vidíš web normálne.'
                : 'Zatvorením zobrazíš návštevníkom oznam, že sa na webe pracuje. Ty budeš mať prístup ďalej.'; ?>
        </p>
        <a href="<?php echo esc_url($url); ?>" class="button <?php echo $on ? 'button-primary' : ''; ?>"
           style="<?php echo $on ? '' : 'background:#b45309;border-color:#b45309;color:#fff'; ?>">
            <?php echo $on ? 'Otvoriť web (online)' : 'Zatvoriť web (údržba)'; ?>
        </a>
        <?php if ($on): ?>
        <a href="<?php echo esc_url(home_url('/?preview_maintenance=1')); ?>" target="_blank" class="button" style="margin-left:6px">Náhľad</a>
        <?php endif; ?>
    </div>
    <?php
}

add_action('admin_post_zc_toggle_maint', function () {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('zc_toggle_maint');
    $on = (int) get_option('zc_maintenance_on', 0);
    update_option('zc_maintenance_on', $on ? 0 : 1);
    wp_safe_redirect(admin_url() . '?zc_maint=' . ($on ? 'off' : 'on'));
    exit;
});

// Potvrdzovacia hláška po prepnutí
add_action('admin_notices', function () {
    if (empty($_GET['zc_maint'])) return;
    $on = $_GET['zc_maint'] === 'on';
    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
        $on ? 'warning' : 'success',
        $on ? 'Web je teraz <strong>zatvorený</strong> pre návštevníkov (režim údržby).'
            : 'Web je opäť <strong>online</strong> a viditeľný pre všetkých.'
    );
});
