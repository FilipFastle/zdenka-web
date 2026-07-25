<?php
/**
 * Plugin Name: ZC Panel doména – realitný panel na subdoméne
 * Description: Umožní prevádzkovať realitný panel na vlastnej subdoméne (napr. panel.zdenkacibulova.sk) nad tým istým WordPressom. Kým nie je subdoména nastavená, plugin nič nemení.
 * Version: 1.2.1
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZC_PD_VER', '1.2.1');
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

// Základná doména bez „www." – na návrh subdomény a cookie domény
function zc_pd_base_domain() {
    return preg_replace('/^www\./i', '', (string) zc_pd_main_host());
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

/* ───────────────────────────── Režim prevádzky ─────────────────────────────
 * alias  = subdoména má ROVNAKÝ document root ako web (ideálne)
 * loader = subdoména má VLASTNÝ priečinok, v ktorom je loader (index.php),
 *          ktorý spustí hlavný WordPress. Vtedy musia wp-admin, wp-includes
 *          a wp-content ostať na hlavnej doméne.
 */
function zc_pd_mode() {
    $m = get_option('zc_pd_mode', 'alias');
    return ($m === 'loader') ? 'loader' : 'alias';
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
// „home" (verejné adresy) prepíname vždy – vďaka tomu je panel na subdoméne
add_filter('option_home', 'zc_pd_swap_url');
// „siteurl" (wp-admin, wp-includes) prepíname len v režime alias; v režime
// loader tieto súbory na subdoméne neexistujú, musia ostať na hlavnej doméne.
add_filter('option_siteurl', function ($url) {
    if (zc_pd_mode() === 'loader') return $url;
    return zc_pd_swap_url($url);
});

/* ── CORS pre AJAX/upload zo subdomény (potrebné v režime loader) ──────────
 * Prihlasovacia cookie sa medzi subdomémami posiela (rovnaká doména), ale
 * prehliadač potrebuje aj CORS hlavičky, aby odpoveď smel prečítať.
 */
function zc_pd_send_cors() {
    $panel = zc_pd_panel_host();
    if (!$panel) return;
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
    if (!$origin) return;
    $oh = strtolower((string) wp_parse_url($origin, PHP_URL_HOST));
    if ($oh !== $panel) return; // povolíme výhradne vlastnú subdoménu

    header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin', false);
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-WP-Nonce');
        exit;
    }
}
add_action('init', function () {
    $script = basename($_SERVER['PHP_SELF'] ?? '');
    if (wp_doing_ajax() || in_array($script, ['admin-ajax.php', 'async-upload.php', 'media-upload.php'], true)) {
        zc_pd_send_cors();
    }
}, 0);
add_action('admin_init', 'zc_pd_send_cors', 0);

/* ── Diagnostika: /?zcpd_check=1 vypíše, ako plugin vidí požiadavku ──────────
 * Beží veľmi skoro a končí výpisom, takže sa NEUPLATNÍ žiadne presmerovanie
 * WordPressu. Ak sa tento výpis nezobrazí, presmerovanie robí hosting/.htaccess.
 */
add_action('init', function () {
    if (empty($_GET['zcpd_check'])) return;
    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    $page = get_page_by_path(ZC_PD_SLUG);
    echo "ZC Panel doména – diagnostika\n";
    echo "─────────────────────────────\n";
    echo "verzia pluginu:      " . ZC_PD_VER . "\n";
    echo "aktuálny host:       " . zc_pd_current_host() . "\n";
    echo "nastavený panel host: " . (zc_pd_panel_host() ?: '(NENASTAVENÝ – doplň v Nastavenia → Panel doména)') . "\n";
    echo "je to panel request:  " . (zc_pd_is_panel_request() ? 'ÁNO' : 'NIE') . "\n";
    echo "režim:               " . zc_pd_mode() . "\n";
    echo "presmerovanie na sub: " . (get_option('zc_pd_force', '') ? 'zapnuté' : 'vypnuté') . "\n";
    echo "home_url():          " . home_url() . "\n";
    echo "site_url():          " . site_url() . "\n";
    echo "stránka panela:      " . ($page ? '/' . ZC_PD_SLUG . '/ (ID ' . $page->ID . ')' : 'NENÁJDENÁ') . "\n";
    echo "COOKIE_DOMAIN:       " . (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '(nedefinovaná)') . "\n";
    echo "prihlásený:          " . (is_user_logged_in() ? wp_get_current_user()->user_login : 'nie') . "\n";
    echo "\nAk tento výpis vidíš na subdoméne, WordPress sa na ňu dostane\n";
    echo "a presmerovanie NEROBÍ hosting. Ak ťa to hodí na hlavnú doménu,\n";
    echo "presmerovanie je nastavené v hostingu alebo v .htaccess.\n";
    exit;
}, 0);

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

    // 3) Koreň subdomény = priamo panel (natvrdo prepíšeme dopyt na stránku panela)
    add_action('parse_request', function ($wp) {
        if (zc_pd_req_path() !== '') return;
        $page = get_page_by_path(ZC_PD_SLUG);
        if (!$page) return;
        $wp->query_vars   = ['page_id' => $page->ID];
        $wp->request      = ZC_PD_SLUG;
        $wp->matched_rule = '';
        $wp->matched_query = '';
    });
});

// Cesta požiadavky bez lomítok („" = koreň)
function zc_pd_req_path() {
    return trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
}

// 4) Ostatné stránky webu na subdoméne presmerovať na hlavnú doménu
add_action('template_redirect', function () {
    if (!zc_pd_is_panel_request()) return;
    if (is_admin() || wp_doing_ajax()) return;

    header('X-Robots-Tag: noindex, nofollow', true);

    // Panel (a jeho podstránky/akcie) necháme byť
    if (is_page(ZC_PD_SLUG)) return;
    // Koreň subdomény nikdy nepresmerovávame (patrí panelu)
    if (zc_pd_req_path() === '') return;

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
    $warn  = '';
    if (isset($_POST['zc_pd_save']) && check_admin_referer('zc_pd_save')) {
        $host = strtolower(trim((string) ($_POST['host'] ?? '')));
        $host = preg_replace('~^https?://~i', '', $host);
        $host = trim($host, '/ ');
        $host = preg_replace('/[^a-z0-9\.\-]/', '', $host);
        // Kontrola: musí to byť subdoména tej istej domény (bez www.)
        $base = zc_pd_base_domain();
        if ($host && $base && substr($host, -strlen($base)) !== $base) {
            $warn = 'Zadaný host <code>' . esc_html($host) . '</code> nepatrí k doméne <code>' . esc_html($base) . '</code>. Ulož radšej <code>panel.' . esc_html($base) . '</code>.';
        } elseif ($host && strpos($host, '.www.') !== false) {
            $warn = 'Host obsahuje <code>www.</code> na nesprávnom mieste. Správne je <code>panel.' . esc_html($base) . '</code>.';
        }
        // Ak je host zamknutý konštantou, pole je disabled a POST ho neposiela –
        // vtedy uloženú hodnotu nechávame na pokoji (nech sa nevynuluje).
        if (!(defined('ZC_PANEL_HOST') && ZC_PANEL_HOST)) {
            update_option('zc_pd_host', $host);
        }
        update_option('zc_pd_force', empty($_POST['force']) ? '' : '1');
        $mode = ($_POST['mode'] ?? 'alias') === 'loader' ? 'loader' : 'alias';
        update_option('zc_pd_mode', $mode);
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
        <?php if ($warn): ?>
        <div class="notice notice-warning"><p><?php echo wp_kses_post($warn); ?></p></div>
        <?php endif; ?>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;margin:18px 0">
            <h2 style="margin-top:0;font-size:15px">Stav nastavenia</h2>
            <ul style="margin:0;line-height:2">
                <li><?php echo $host ? '✅' : '⬜'; ?> Subdoména: <code><?php echo $host ? esc_html($host) : 'nenastavená'; ?></code><?php echo $const ? ' <em>(z wp-config.php)</em>' : ''; ?></li>
                <li><?php echo $panel_page ? '✅' : '❌'; ?> Stránka panela: <code>/<?php echo esc_html(ZC_PD_SLUG); ?>/</code></li>
                <li><?php echo $cookie_ok ? '✅' : '❌'; ?> <code>COOKIE_DOMAIN</code>: <?php echo $cookie ? '<code>' . esc_html($cookie) . '</code>' : '<strong>nie je nastavená</strong> – bez nej sa neudrží prihlásenie medzi doménami'; ?></li>
                <li><?php echo is_ssl() ? '✅' : '⚠️'; ?> HTTPS <?php echo is_ssl() ? 'aktívne' : '– skontroluj certifikát aj pre subdoménu'; ?></li>
            </ul>
            <?php if ($host): ?>
            <p style="margin:12px 0 0">
                <a href="<?php echo esc_url('https://' . $host); ?>" target="_blank" class="button">Otestovať <?php echo esc_html($host); ?> ↗</a>
                <a href="<?php echo esc_url('https://' . $host . '/?zcpd_check=1'); ?>" target="_blank" class="button button-primary">Diagnostika subdomény ↗</a>
            </p>
            <p class="description" style="margin-top:8px">Diagnostika vypíše čistý text. <strong>Ak ťa namiesto výpisu hodí na hlavnú doménu, presmerovanie robí hosting alebo <code>.htaccess</code>, nie WordPress.</strong></p>
            <?php endif; ?>
        </div>

        <form method="post">
            <?php wp_nonce_field('zc_pd_save'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="zcpdhost">Host subdomény</label></th>
                    <td>
                        <input name="host" id="zcpdhost" type="text" class="regular-text" value="<?php echo esc_attr($const ? ZC_PANEL_HOST : get_option('zc_pd_host', '')); ?>" placeholder="panel.<?php echo esc_attr(zc_pd_base_domain()); ?>" <?php disabled($const, true); ?>>
                        <p class="description">
                            <?php if ($const): ?>Nastavené v <code>wp-config.php</code> cez <code>ZC_PANEL_HOST</code>, tu sa nedá zmeniť.
                            <?php else: ?>Bez <code>https://</code> a bez lomítka. Prázdne = funkcia vypnutá (panel ostane len na hlavnej doméne).<?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Režim subdomény</th>
                    <td>
                        <label style="display:block;margin-bottom:8px"><input type="radio" name="mode" value="alias" <?php checked(zc_pd_mode(), 'alias'); ?>> <strong>Alias</strong> – subdoména má <em>rovnaký</em> document root ako web</label>
                        <label style="display:block"><input type="radio" name="mode" value="loader" <?php checked(zc_pd_mode(), 'loader'); ?>> <strong>Loader</strong> – subdoména má <em>vlastný</em> priečinok (vložíš doň 2 súbory nižšie)</label>
                        <p class="description">Ak ti hosting nedovolí rovnaký document root, zvoľ <strong>Loader</strong>. Plugin vtedy nechá <code>wp-admin</code>, <code>wp-includes</code> a <code>wp-content</code> na hlavnej doméne a povolí AJAX/upload zo subdomény.</p>
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
            <input type="hidden" name="zc_pd_save" value="1">
            <?php submit_button('Uložiť', 'primary', 'zc_pd_save'); ?>
        </form>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px">
            <h2 style="margin-top:0;font-size:15px">Postup nastavenia (Websupport)</h2>
            <ol style="line-height:1.9">
                <li><strong>DNS:</strong> A záznam <code>panel</code> na rovnakú IP ako hlavný web.</li>
                <li><strong>Hosting (toto sa najčastejšie zabúda):</strong> pridaj <code>panel.<?php echo esc_html(zc_pd_base_domain()); ?></code> aj do webhostingu ako <em>doménu / alias</em> s <strong>rovnakým document rootom</strong> ako hlavný web. Bez tohto kroku vráti server <code>Not Found</code>, aj keď DNS funguje.</li>
                <li>Zapni pre ňu <strong>SSL certifikát</strong> (Let's Encrypt).</li>
                <li>Do <code>wp-config.php</code> nad riadok <code>/* That's all */</code> pridaj:
                    <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto">define('COOKIE_DOMAIN', '.<?php echo esc_html(zc_pd_base_domain()); ?>');
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
define('ADMIN_COOKIE_PATH', '/');</pre>
                </li>
                <li>Ak máš v <code>.htaccess</code> pravidlo, ktoré vynucuje <code>www</code>, vylúč z neho subdoménu – inak ťa presmeruje na hlavnú doménu:
                    <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto">RewriteCond %{HTTP_HOST} !^panel\.<?php echo esc_html(preg_quote(zc_pd_base_domain(), '/')); ?>$ [NC]</pre>
                    (vlož ako ďalšiu podmienku pred riadok, ktorý presmerováva na <code>www</code>)
                </li>
                <li>Vyplň host subdomény vyššie a ulož. Otestuj <code>https://panel.<?php echo esc_html(zc_pd_base_domain()); ?></code> – má sa otvoriť prihlásenie do panela.</li>
                <li>Až keď to funguje, zapni presmerovanie (voľba vyššie).</li>
            </ol>
            <p style="margin-bottom:0;color:#666">Po zmene cookie nastavení sa všetci používatelia odhlásia – treba sa prihlásiť znova.</p>

            <h2 style="font-size:15px;margin-top:22px">Súbory pre režim „Loader"</h2>
            <p style="margin-top:4px">Ak subdoména dostala <strong>vlastný priečinok</strong> (napr. <code>/panel</code>), vlož doň tieto dva súbory. Cesta je už vyplnená podľa tvojho servera – netreba nič dopisovať.</p>
            <p style="margin:0 0 6px"><strong>1.</strong> <code>index.php</code></p>
            <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto">&lt;?php
/* Loader: subdoména panela spustí WordPress z hlavného webu */
define('WP_USE_THEMES', true);
require '<?php echo esc_html(rtrim(ABSPATH, '/\\')); ?>/wp-blog-header.php';</pre>
            <p style="margin:14px 0 6px"><strong>2.</strong> <code>.htaccess</code></p>
            <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto">RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]</pre>
            <p style="color:#666">Potom vyššie prepni <strong>Režim subdomény</strong> na <em>Loader</em> a ulož.</p>

            <h2 style="font-size:15px;margin-top:22px">Text pre podporu Websupportu</h2>
            <p style="margin-top:4px">Ak si nie si istý, kde sa subdoména pridáva, pošli im toto:</p>
            <pre style="background:#f6f7f7;padding:12px;border-radius:6px;overflow:auto;white-space:pre-wrap">Dobrý deň,
prosím o pridanie subdomény panel.<?php echo esc_html(zc_pd_base_domain()); ?> do webhostingu tak,
aby ju server obsluhoval (nie len DNS záznam), a o vystavenie Let's Encrypt
certifikátu pre túto subdoménu.

Ideálne s rovnakým document rootom ako hlavná doména
(<?php echo esc_html(rtrim(ABSPATH, '/\\')); ?>).
Ak to nie je možné, stačí vlastný priečinok – obsah si doplním sám.

Ďakujem.</pre>

            <h2 style="font-size:15px;margin-top:22px">Keď to nefunguje</h2>
            <table class="widefat striped" style="margin-top:8px">
                <tr><td style="width:38%"><strong><code>Not Found</code></strong> (biela stránka, text od servera)</td><td>Subdoména nie je pridaná v <strong>hostingu</strong> (krok 2), alebo má iný document root. DNS je v poriadku.</td></tr>
                <tr><td><strong>Presmeruje na hlavnú doménu</strong></td><td>Pravidlo na <code>www</code> v <code>.htaccess</code> (krok 5), alebo nie je vyplnený host subdomény vyššie.</td></tr>
                <tr><td><strong>Chyba certifikátu</strong></td><td>Pre subdoménu nie je vystavený SSL certifikát (krok 3).</td></tr>
                <tr><td><strong>Odhlasuje / nepustí do panela</strong></td><td>Chýba <code>COOKIE_DOMAIN</code> (krok 4). Musí byť <code>.<?php echo esc_html(zc_pd_base_domain()); ?></code> – s bodkou na začiatku, bez <code>www</code>.</td></tr>
            </table>
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
