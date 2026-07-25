<?php
/**
 * Plugin Name: ZC Panel doména – realitný panel na subdoméne
 * Description: Umožní prevádzkovať realitný panel na vlastnej subdoméne (napr. panel.zdenkacibulova.sk) nad tým istým WordPressom. Kým nie je subdoména nastavená, plugin nič nemení.
 * Version: 1.0.0
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZC_PD_VER', '1.0.0');
define('ZC_PD_SLUG', 'realitny-panel'); // slug stránky s panelom

/* ───────────────────────── Konfigurácia hostov ───────────────────────── */

// Host subdomény: konštanta ZC_PANEL_HOST vo wp-config.php má prednosť.
function zc_pd_panel_host() {
    if (defined('ZC_PANEL_HOST') && ZC_PANEL_HOST) {
        return strtolower(trim((string) ZC_PANEL_HOST));
    }
    $o = get_option('zc_pd_host', '');
    return $o ? strtolower(trim($o)) : '';
}

// Aktuálny host požiadavky (bez portu)
function zc_pd_current_host() {
    $h = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    return strtolower(preg_replace('/:\d+$/', '', $h));
}

// Beží táto požiadavka na subdoméne panela?
function zc_pd_is_panel_request() {
    $h = zc_pd_panel_host();
    if (!$h) return false;
    return zc_pd_current_host() === $h;
}

// Pôvodný (hlavný) host webu – čítame surovú hodnotu bez našich filtrov
function zc_pd_main_host() {
    static $host = null;
    if ($host !== null) return $host;
    remove_filter('option_home', 'zc_pd_swap_url');
    $url = get_option('home');
    add_filter('option_home', 'zc_pd_swap_url');
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    return $host;
}

/* ──────────── Prepnutie adries webu na panel host (len na ňom) ──────────── */

function zc_pd_swap_url($url) {
    if (!is_string($url) || $url === '') return $url;
    if (!zc_pd_is_panel_request()) return $url;
    $panel = zc_pd_panel_host();
    $host  = wp_parse_url($url, PHP_URL_HOST);
    if (!$host || strtolower($host) === $panel) return $url;
    return preg_replace('~://' . preg_quote($host, '~') . '~i', '://' . $panel, $url, 1);
}
add_filter('option_home', 'zc_pd_swap_url');
add_filter('option_siteurl', 'zc_pd_swap_url');

/* ─────────────────── Chovanie na subdoméne panela ─────────────────── */

add_action('plugins_loaded', function () {
    if (!zc_pd_is_panel_request()) return;

    // 1) Vypnúť kanonické presmerovanie (inak WP hodí návštevníka na hlavnú doménu)
    remove_action('template_redirect', 'redirect_canonical');
    add_filter('redirect_canonical', '__return_false');

    // 2) Subdoména sa nemá indexovať (rovnaký obsah ako hlavná doména)
    add_filter('wp_robots', function ($robots) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    });

    // 3) Koreň subdomény = priamo panel
    add_filter('request', function ($qv) {
        $path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (trim($path, '/') === '') {
            $qv['pagename'] = ZC_PD_SLUG;
            unset($qv['error']);
        }
        return $qv;
    });
});

// 4) Ostatné stránky webu na subdoméne presmerovať na hlavnú doménu
add_action('template_redirect', function () {
    if (!zc_pd_is_panel_request()) return;
    if (is_admin() || wp_doing_ajax()) return;

    header('X-Robots-Tag: noindex, nofollow', true);

    // Panel (a jeho podstránky/akcie) necháme byť
    if (is_page(ZC_PD_SLUG)) return;

    $main = zc_pd_main_host();
    if (!$main) return;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    wp_redirect('https://' . $main . $uri, 301);
    exit;
}, 1);

// 5) Voliteľne: /realitny-panel/ na hlavnej doméne presmerovať na subdoménu
add_action('template_redirect', function () {
    if (zc_pd_is_panel_request()) return;
    if (!get_option('zc_pd_force', '')) return;
    $panel = zc_pd_panel_host();
    if (!$panel || !is_page(ZC_PD_SLUG)) return;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    wp_redirect('https://' . $panel . $uri, 301);
    exit;
}, 2);

/* ─────────────────────────── Nastavenia v admine ─────────────────────────── */

add_action('admin_menu', function () {
    add_options_page('Panel doména', 'Panel doména', 'manage_options', 'zc-panel-domain', 'zc_pd_settings_page');
});

function zc_pd_settings_page() {
    if (!current_user_can('manage_options')) return;

    $saved = false;
    if (isset($_POST['zc_pd_save']) && check_admin_referer('zc_pd_save')) {
        $host = strtolower(trim((string) ($_POST['host'] ?? '')));
        $host = preg_replace('~^https?://~i', '', $host);
        $host = trim($host, '/ ');
        $host = preg_replace('/[^a-z0-9\.\-]/', '', $host);
        update_option('zc_pd_host', $host);
        update_option('zc_pd_force', empty($_POST['force']) ? '' : '1');
        $saved = true;
    }

    $const  = defined('ZC_PANEL_HOST') && ZC_PANEL_HOST;
    $host   = $const ? ZC_PANEL_HOST : get_option('zc_pd_host', '');
    $force  = get_option('zc_pd_force', '');
    $main   = zc_pd_main_host();
    $cookie = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
    $cookie_ok = $cookie && strpos((string) $main, ltrim((string) $cookie, '.')) !== false;
    $panel_page = get_page_by_path(ZC_PD_SLUG);
    ?>
    <div class="wrap" style="max-width:860px">
        <h1>Realitný panel na subdoméne</h1>

        <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible"><p>Uložené.</p></div>
        <?php endif; ?>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;margin:18px 0">
            <h2 style="margin-top:0;font-size:15px">Stav nastavenia</h2>
            <ul style="margin:0;line-height:2">
                <li><?php echo $host ? '✅' : '⬜'; ?> Subdoména: <code><?php echo $host ? esc_html($host) : 'nenastavená'; ?></code><?php echo $const ? ' <em>(z wp-config.php)</em>' : ''; ?></li>
                <li><?php echo $panel_page ? '✅' : '❌'; ?> Stránka panela: <code>/<?php echo esc_html(ZC_PD_SLUG); ?>/</code></li>
                <li><?php echo $cookie_ok ? '✅' : '❌'; ?> <code>COOKIE_DOMAIN</code>: <?php echo $cookie ? '<code>' . esc_html($cookie) . '</code>' : '<strong>nie je nastavená</strong> – bez nej sa neudrží prihlásenie medzi doménami'; ?></li>
                <li><?php echo is_ssl() ? '✅' : '⚠️'; ?> HTTPS <?php echo is_ssl() ? 'aktívne' : '– skontroluj certifikát aj pre subdoménu'; ?></li>
            </ul>
        </div>

        <form method="post">
            <?php wp_nonce_field('zc_pd_save'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="zcpdhost">Host subdomény</label></th>
                    <td>
                        <input name="host" id="zcpdhost" type="text" class="regular-text" value="<?php echo esc_attr($const ? ZC_PANEL_HOST : get_option('zc_pd_host', '')); ?>" placeholder="panel.<?php echo esc_attr($main); ?>" <?php disabled($const, true); ?>>
                        <p class="description">
                            <?php if ($const): ?>Nastavené v <code>wp-config.php</code> cez <code>ZC_PANEL_HOST</code>, tu sa nedá zmeniť.
                            <?php else: ?>Bez <code>https://</code> a bez lomítka. Prázdne = funkcia vypnutá (panel ostane len na hlavnej doméne).<?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Presmerovanie</th>
                    <td>
                        <label><input type="checkbox" name="force" value="1" <?php checked($force, '1'); ?>> Presmerovať <code>/<?php echo esc_html(ZC_PD_SLUG); ?>/</code> na subdoménu</label>
                        <p class="description"><strong>Zapni až keď subdoména overene funguje.</strong> Ak by nefungovala, panel by nebol dostupný ani na hlavnej doméne (vtedy stačí odškrtnúť túto voľbu priamo tu vo wp-admin).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Uložiť'); ?>
        </form>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px">
            <h2 style="margin-top:0;font-size:15px">Postup nastavenia (Websupport)</h2>
            <ol style="line-height:1.9">
                <li>Vytvor subdoménu <code>panel.<?php echo esc_html($main); ?></code> a nastav jej <strong>rovnaký document root</strong> ako má hlavný web (alias, nie nový priestor).</li>
                <li>Zapni pre ňu <strong>SSL certifikát</strong> (Let's Encrypt).</li>
                <li>Do <code>wp-config.php</code> nad riadok <code>/* That's all */</code> pridaj:
                    <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto">define('COOKIE_DOMAIN', '.<?php echo esc_html($main); ?>');
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
define('ADMIN_COOKIE_PATH', '/');</pre>
                </li>
                <li>Vyplň host subdomény vyššie a ulož. Otestuj <code>https://panel.<?php echo esc_html($main); ?></code> – má sa otvoriť prihlásenie do panela.</li>
                <li>Až keď to funguje, zapni presmerovanie (voľba vyššie).</li>
            </ol>
            <p style="margin-bottom:0;color:#666">Po zmene cookie nastavení sa všetci používatelia odhlásia – treba sa prihlásiť znova.</p>
        </div>
    </div>
    <?php
}

// Upozornenie, ak je subdoména nastavená bez COOKIE_DOMAIN
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    if (!zc_pd_panel_host()) return;
    if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) return;
    echo '<div class="notice notice-warning"><p><strong>Panel doména:</strong> subdoména je nastavená, ale vo <code>wp-config.php</code> chýba <code>COOKIE_DOMAIN</code> – prihlásenie sa nemusí udržať. <a href="' . esc_url(admin_url('options-general.php?page=zc-panel-domain')) . '">Zobraziť postup</a></p></div>';
});
