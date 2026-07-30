<?php
/**
 * Vlastná rola „Realitný maklér" — plný prístup do realitného panelu
 * a vo wp-admin iba view-only dashboard Google Site Kit.
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

    // Samoliečenie po starších verziách: ak rola niekedy omylom získala
    // správcovské oprávnenie, pri aktualizácii ho výslovne odoberieme.
    $role = get_role(PP_AGENT_ROLE);
    if ($role) {
        foreach ([
            'manage_options', 'edit_theme_options', 'switch_themes',
            'activate_plugins', 'install_plugins', 'update_plugins', 'delete_plugins',
            'install_themes', 'update_themes', 'delete_themes',
            'edit_users', 'create_users', 'delete_users', 'promote_users', 'list_users',
            'manage_categories', 'manage_links', 'import', 'export',
            'edit_pages', 'edit_others_pages', 'publish_pages', 'delete_pages', 'delete_others_pages',
        ] as $cap) {
            $role->remove_cap($cap);
        }
    }
}
add_action('init', function () {
    if (get_option('pp_agent_role_v') !== '3') {
        pp_register_agent_role();
        update_option('pp_agent_role_v', '3');
    }
});
register_activation_hook(PROPERTY_PRO_PATH . 'property-manager-pro.php', 'pp_register_agent_role');

function pp_is_agent($user = null) {
    $user = $user ?: wp_get_current_user();
    return $user && in_array(PP_AGENT_ROLE, (array) $user->roles, true);
}

/**
 * Je požiadavka určená iba na čítanie zdieľaného prehľadu Site Kit?
 *
 * Site Kit si oprávnenie na dáta kontroluje sám. Tu iba dovolíme maklérke
 * načítať jeho jedinú obrazovku vo vloženom okne panela; ostatný wp-admin
 * zostáva naďalej neprístupný.
 */
function pp_is_sitekit_dashboard_request() {
    $script = basename($_SERVER['PHP_SELF'] ?? '');
    $page   = sanitize_key($_GET['page'] ?? '');
    // Site Kit má viac obrazoviek (dashboard, splash, detail modulu) a sám
    // medzi nimi presmerúva. Keby sme povolili len jednu, maklérka by sa
    // po prvom presmerovaní vrátila do panela a nikam by sa nedostala.
    return $script === 'admin.php' && strpos($page, 'googlesitekit') === 0;
}

function pp_sitekit_dashboard_url() {
    return add_query_arg('zc_panel', '1', admin_url('admin.php?page=googlesitekit-dashboard'));
}

function pp_sitekit_available() {
    if (defined('GOOGLESITEKIT_VERSION')) return true;
    return in_array('google-site-kit/google-site-kit.php', (array) get_option('active_plugins', []), true)
        || (is_multisite() && isset(get_site_option('active_sitewide_plugins', [])['google-site-kit/google-site-kit.php']));
}

/**
 * Oprávnenia, ktoré maklérka potrebuje, aby Site Kit vôbec zobrazil prehľad.
 * Sú výhradne na čítanie – nastavovanie, pripájanie účtov ani správa modulov
 * medzi nimi nie sú, tie ostávajú správcovi.
 */
function pp_sitekit_view_caps() {
    return [
        'googlesitekit_view_dashboard',
        'googlesitekit_view_shared_dashboard',
        'googlesitekit_view_module_details',
        'googlesitekit_read_shared_module_data',
    ];
}

/**
 * Site Kit si oprávnenia nastavuje sám cez user_has_cap (priorita 20),
 * preto sa musíme pripojiť až za neho – inak by našu voľbu prepísal.
 */
add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
    if (!($user instanceof WP_User) || !pp_is_agent($user)) return $allcaps;
    if (!empty($allcaps['manage_options'])) return $allcaps;

    foreach (pp_sitekit_view_caps() as $cap) $allcaps[$cap] = true;
    return $allcaps;
}, 30, 4);

// Skryť hornú WordPress lištu maklérovi
add_filter('show_admin_bar', function ($show) {
    if (pp_is_agent() && !current_user_can('manage_options')) return false;
    return $show;
});

// Vo wp-admin povoliť maklérke iba Site Kit. Všetky ostatné obrazovky
// presmerujeme na Site Kit; technické endpointy panela a médií ostávajú funkčné.
add_action('admin_init', function () {
    if (!pp_is_agent() || current_user_can('manage_options')) return;
    if (wp_doing_ajax()) return;
    // Tieto koncové body maklérka potrebuje – cez ne odosiela formuláre
    // z panela a nahráva fotky. Bez admin-post.php by sa nič neuložilo.
    $script = basename($_SERVER['PHP_SELF'] ?? '');
    if (in_array($script, ['admin-ajax.php', 'admin-post.php', 'async-upload.php', 'media-upload.php'], true)) return;
    if (pp_is_sitekit_dashboard_request()) return;

    // Bez Site Kitu (alebo keby ho odmietol) posielame do panela – nikdy
    // nie na obrazovku, z ktorej by sa hneď presmerovalo naspäť.
    $target = pp_sitekit_available()
        ? pp_sitekit_dashboard_url()
        : (function_exists('pp_panel_url') ? pp_panel_url('action=sitekit') : home_url('/'));
    wp_safe_redirect($target);
    exit;
});

/**
 * Site Kit ukáže dáta inej role len vtedy, keď má správca zapnuté
 * „Dashboard sharing". Bez toho by maklérka videla prázdnu obrazovku
 * a nevedela prečo – tak jej to rovno napíšeme.
 */
add_action('admin_notices', function () {
    if (!pp_is_agent() || current_user_can('manage_options')) return;
    if (!pp_is_sitekit_dashboard_request()) return;
    if (current_user_can('googlesitekit_view_shared_dashboard')) return;
    echo '<div class="notice notice-warning"><p>Prehľad zatiaľ nie je zdieľaný. '
       . 'Správca ho zapne v <strong>Site Kit → Settings → Dashboard sharing</strong> '
       . 'pre rolu <em>Realitný maklér</em>.</p></div>';
});

// V ľavom menu wp-adminu zostane maklérke iba položka Site Kit.
// Technické endpointy pre ukladanie panela nie sú položky menu a fungujú ďalej.
add_action('admin_menu', function () {
    if (!pp_is_agent() || current_user_can('manage_options')) return;

    global $menu, $submenu;
    // Site Kit registruje menu pod rôznymi slugmi podľa toho, či je už
    // nastavený (dashboard) alebo ešte nie (splash) – necháme oba.
    $keep = static function ($slug) {
        return strpos((string) $slug, 'googlesitekit') === 0;
    };
    foreach ((array) $menu as $item) {
        $slug = (string) ($item[2] ?? '');
        if ($keep($slug)) continue;
        remove_menu_page($slug);
    }
    foreach (array_keys((array) $submenu) as $parent) {
        if (!$keep($parent)) unset($submenu[$parent]);
    }
}, 999);

// Site Kit sa v paneli načíta v iframe. Maklérke preto schováme celé rozhranie
// wp-adminu a necháme iba samotný prehľad, ktorý jej správca zdieľal.
add_action('admin_head', function () {
    if (!pp_is_agent() || current_user_can('manage_options') || !pp_is_sitekit_dashboard_request()) return;
    ?>
    <style>
    html.wp-toolbar{padding-top:0!important}
    #wpadminbar,#adminmenumain,#wpfooter,.update-nag,.notice:not(.googlesitekit-notice){display:none!important}
    #wpcontent,#wpfooter{margin-left:0!important}
    #wpbody-content{padding-bottom:0!important}
    #wpbody{padding-top:0!important}
    body{background:#fff!important}
    .pp-sitekit-back{position:fixed;right:16px;bottom:16px;z-index:100000;background:#1C1A18;color:#fff!important;
        text-decoration:none;padding:10px 15px;border-radius:9px;font:600 13px/1.2 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        box-shadow:0 6px 18px rgba(0,0,0,.2)}
    @media(max-width:782px){
        html.wp-toolbar{padding-top:0!important}
        #wpcontent{padding-left:0!important}
    }
    </style>
    <?php
}, 100);

add_action('admin_footer', function () {
    if (!pp_is_agent() || current_user_can('manage_options') || !pp_is_sitekit_dashboard_request()) return;
    ?>
    <script>
    if (window.top === window.self) {
        var back = document.createElement('a');
        back.className = 'pp-sitekit-back';
        back.href = <?php echo wp_json_encode(function_exists('pp_panel_url') ? pp_panel_url('action=sitekit') : home_url('/')); ?>;
        back.textContent = '← Späť do realitného panela';
        document.body.appendChild(back);
    }
    </script>
    <?php
}, 100);

// Po prihlásení cez wp-admin otvor maklérke jedinú povolenú obrazovku – Site Kit.
// Z nej má stále viditeľné tlačidlo späť do samostatného realitného panela.
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    if ($user instanceof WP_User && pp_is_agent($user) && !user_can($user, 'manage_options')) {
        $panel_url = function_exists('pp_panel_url') ? pp_panel_url() : '';
        $panel_path = $panel_url ? wp_parse_url($panel_url, PHP_URL_PATH) : '';
        $requested_path = $requested ? wp_parse_url($requested, PHP_URL_PATH) : '';
        // Prihlásenie otvorené priamo z realitného panela sa musí vrátiť doň.
        if ($panel_path && $requested_path === $panel_path) return $panel_url;

        return pp_sitekit_available()
            ? pp_sitekit_dashboard_url()
            : (function_exists('pp_panel_url') ? pp_panel_url('action=sitekit') : home_url('/'));
    }
    return $redirect_to;
}, 10, 3);
