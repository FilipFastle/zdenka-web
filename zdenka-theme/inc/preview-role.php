<?php
/**
 * Rola „Náhľad webu“ – účet, ktorý smie iba pozerať web.
 * Žiadne úpravy, žiadny wp-admin, žiadna horná lišta. Slúži na to,
 * aby si klient mohol web pozrieť aj keď je zapnutá údržba.
 */
defined('ABSPATH') || exit;

define('ZC_PREVIEW_ROLE', 'zc_nahlad');

function zc_register_preview_role() {
    // 'read' je jediná schopnosť, ktorú WordPress potrebuje na prihlásenie.
    // Nič iné rola nemá – žiadne písanie, mazanie ani nastavenia.
    remove_role(ZC_PREVIEW_ROLE);
    add_role(ZC_PREVIEW_ROLE, 'Náhľad webu', ['read' => true]);
}

add_action('init', function () {
    if (get_option('zc_preview_role_v') !== '1') {
        zc_register_preview_role();
        update_option('zc_preview_role_v', '1');
    }
});
add_action('after_switch_theme', 'zc_register_preview_role');

function zc_is_preview_user($user = null) {
    $user = $user ?: wp_get_current_user();
    return $user instanceof WP_User && $user->ID && in_array(ZC_PREVIEW_ROLE, (array) $user->roles, true);
}

// Žiadna horná WordPress lišta
add_filter('show_admin_bar', function ($show) {
    return zc_is_preview_user() ? false : $show;
});

// Do wp-adminu sa nedostane – vždy späť na web
add_action('admin_init', function () {
    if (!zc_is_preview_user() || wp_doing_ajax()) return;
    if (basename($_SERVER['PHP_SELF'] ?? '') === 'admin-ajax.php') return;
    wp_safe_redirect(home_url('/'));
    exit;
});

// Po prihlásení rovno na úvodnú stránku
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    return zc_is_preview_user($user) ? home_url('/') : $redirect_to;
}, 10, 3);

// Poistka – aj keby niekto roli pridal schopnosť, tu ju nedostane
add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
    if (!zc_is_preview_user($user)) return $allcaps;
    return ['read' => true];
}, 99, 4);

/** Vytvorí (alebo obnoví heslo) náhľadového účtu. Vracia [login, heslo]. */
function zc_create_preview_user($login = 'nahlad') {
    $login = sanitize_user($login, true) ?: 'nahlad';
    $pass  = wp_generate_password(14, false);
    $user  = get_user_by('login', $login);

    if ($user) {
        wp_set_password($pass, $user->ID);
        $u = new WP_User($user->ID);
        $u->set_role(ZC_PREVIEW_ROLE);
        return [$login, $pass, $user->ID];
    }

    $domain = wp_parse_url(home_url(), PHP_URL_HOST) ?: 'example.com';
    $id = wp_insert_user([
        'user_login'   => $login,
        'user_pass'    => $pass,
        'user_email'   => $login . '@' . ltrim($domain, 'www.'),
        'display_name' => 'Náhľad webu',
        'role'         => ZC_PREVIEW_ROLE,
    ]);
    if (is_wp_error($id)) return null;
    return [$login, $pass, $id];
}
