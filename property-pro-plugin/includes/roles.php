<?php
/**
 * Vlastná rola „Realitný maklér" — plný prístup do realitného panelu,
 * ale bez prístupu do wp-admin a bez hornej WordPress lišty.
 */
defined('ABSPATH') || exit;

define('PP_AGENT_ROLE', 'realitny_makler');

// Vytvoriť/aktualizovať rolu
function pp_register_agent_role() {
    $caps = [
        'read'                   => true,
        'upload_files'           => true,
        'edit_posts'             => true,
        'edit_others_posts'      => true,
        'edit_published_posts'   => true,
        'publish_posts'          => true,
        'delete_posts'           => true,
        'delete_others_posts'    => true,
        'delete_published_posts' => true,
    ];
    $role = get_role(PP_AGENT_ROLE);
    if (!$role) {
        add_role(PP_AGENT_ROLE, 'Realitný maklér', $caps);
    } else {
        foreach ($caps as $c => $g) $role->add_cap($c, $g);
    }
}
add_action('init', function () {
    if (get_option('pp_agent_role_v') !== '2') {
        pp_register_agent_role();
        update_option('pp_agent_role_v', '2');
    }
});
register_activation_hook(PROPERTY_PRO_PATH . 'property-manager-pro.php', 'pp_register_agent_role');

function pp_is_agent($user = null) {
    $user = $user ?: wp_get_current_user();
    return $user && in_array(PP_AGENT_ROLE, (array) $user->roles, true);
}

// Skryť hornú WordPress lištu maklérovi
add_filter('show_admin_bar', function ($show) {
    if (pp_is_agent() && !current_user_can('manage_options')) return false;
    return $show;
});

// Zablokovať vstup do wp-admin (okrem AJAX a uploadu médií) — presmerovať do panelu
add_action('admin_init', function () {
    if (!pp_is_agent() || current_user_can('manage_options')) return;
    if (wp_doing_ajax()) return;
    $script = basename($_SERVER['PHP_SELF'] ?? '');
    if (in_array($script, ['admin-ajax.php', 'async-upload.php', 'media-upload.php'], true)) return;

    $panel = get_page_by_path('realitny-panel');
    wp_safe_redirect($panel ? get_permalink($panel) : home_url('/'));
    exit;
});

// Po prihlásení makléra ho pošli rovno do panelu
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    if ($user instanceof WP_User && pp_is_agent($user) && !user_can($user, 'manage_options')) {
        $panel = get_page_by_path('realitny-panel');
        return $panel ? get_permalink($panel) : home_url('/');
    }
    return $redirect_to;
}, 10, 3);
