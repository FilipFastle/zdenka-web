<?php
/**
 * Jedno miesto pre všetko vlastné („bublina“ v ľavom menu).
 * Vlastné pluginy sa zaradia sem namiesto toho, aby sa miešali
 * s bežnými položkami WordPressu.
 */
defined('ABSPATH') || exit;

define('ZC_HUB_SLUG', 'zdenka-hub');

/** Slug hlavnej bubliny – pluginy si podľa nej nájdu rodiča. */
function zc_hub_slug() { return ZC_HUB_SLUG; }

/** Rodič pre podstránku pluginu; ak bublina neexistuje, vráti záložný slug. */
function zc_hub_parent($fallback = '') { return ZC_HUB_SLUG; }

function zc_hub_icon() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="black"><path d="M10 2.2 2.4 8.1v9.4h5.1v-5.2h4.9v5.2h5.2V8.1L10 2.2Z"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/* ───────────────────────── Menu ───────────────────────── */

add_action('admin_menu', function () {
    global $menu;

    add_menu_page(
        'Web Zdenky – správa', 'Web Zdenky', 'read',
        ZC_HUB_SLUG, 'zc_hub_page', zc_hub_icon(), 2
    );
    add_submenu_page(ZC_HUB_SLUG, 'Prehľad', 'Prehľad', 'read', ZC_HUB_SLUG, 'zc_hub_page');

    // Nehnuteľnosti (vlastný typ obsahu) presunieme pod bublinu
    if (post_type_exists('property')) {
        remove_menu_page('edit.php?post_type=property');
        add_submenu_page(ZC_HUB_SLUG, 'Nehnuteľnosti', 'Nehnuteľnosti', 'edit_posts', 'edit.php?post_type=property');
        add_submenu_page(ZC_HUB_SLUG, 'Pridať nehnuteľnosť', '– Pridať novú', 'edit_posts', 'post-new.php?post_type=property');
    }

    // Oddeľovače, aby bublina naozaj stála samostatne
    $menu['1.9']  = ['', 'read', 'zc-sep-top', '', 'wp-menu-separator'];
    $menu['2.9']  = ['', 'read', 'zc-sep-bot', '', 'wp-menu-separator'];
}, 9);

// Stránky témy pridáme až po pluginoch, nech sú v menu naspodku
add_action('admin_menu', function () {
    add_submenu_page(ZC_HUB_SLUG, 'Notifikácie z formulárov', 'Notifikácie', 'manage_options',
        'zc-notifikacie', 'zc_hub_notify_page');
    add_submenu_page(ZC_HUB_SLUG, 'Náhľad webu pre klienta', 'Náhľad pre klienta', 'manage_options',
        'zc-nahlad', 'zc_hub_preview_page');
    add_submenu_page(ZC_HUB_SLUG, 'Stránky a údržba', 'Stránky a údržba', 'manage_options',
        'zdenka-setup', 'zdenka_setup_page');
    add_submenu_page(ZC_HUB_SLUG, 'Nástroje pre webmastera', 'Nástroje', 'manage_options',
        'zc-nastroje', 'zc_hub_tools_page');
}, 50);

/* ───────────────────────── Spoločný vzhľad ───────────────────────── */

function zc_hub_styles() {
    ?>
    <style>
    .zch{max-width:1080px}
    .zch-head{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:18px 0 22px}
    .zch-head h1{margin:0;font-size:23px;font-weight:700}
    .zch-pill{font-size:12px;font-weight:700;padding:4px 12px;border-radius:50px}
    .zch-pill.on{background:#dcfce7;color:#15803d}
    .zch-pill.off{background:#fef3c7;color:#b45309}
    .zch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px}
    .zch-card{background:#fff;border:1px solid #E6DFD2;border-radius:14px;padding:20px 22px;
        text-decoration:none;color:#2C2825;display:block;transition:.16s;box-shadow:0 1px 2px rgba(40,32,20,.04)}
    a.zch-card:hover{border-color:#B8A47A;transform:translateY(-2px);box-shadow:0 8px 22px rgba(40,32,20,.09);color:#2C2825}
    .zch-card h3{margin:0 0 6px;font-size:15.5px;font-weight:700}
    .zch-card p{margin:0;font-size:12.5px;color:#6b6560;line-height:1.55}
    .zch-card .zch-ico{font-size:19px;line-height:1;margin-bottom:11px;display:block}
    .zch-sec{margin:30px 0 12px;font-size:12px;font-weight:800;letter-spacing:.7px;
        text-transform:uppercase;color:#7C5E33}
    .zch-box{background:#fff;border:1px solid #E6DFD2;border-radius:14px;padding:20px 22px;margin-bottom:16px}
    .zch-tbl{width:100%;border-collapse:collapse;font-size:13.5px}
    .zch-tbl td{padding:9px 4px;border-bottom:1px solid #F1EBE0}
    .zch-tbl tr:last-child td{border-bottom:none}
    .zch-tbl td:first-child{color:#6b6560;width:45%}
    .zch-ok{color:#15803d;font-weight:700}
    .zch-warn{color:#b45309;font-weight:700}
    .zch-bad{color:#b91c1c;font-weight:700}
    .zch-note{background:#FBF8F2;border:1px solid #E6DFD2;border-radius:11px;padding:13px 16px;
        font-size:13px;line-height:1.6;color:#5b544c}
    .zch-actions{display:flex;flex-wrap:wrap;gap:9px}
    .zch-cred{background:#0f172a;color:#e2e8f0;border-radius:11px;padding:16px 18px;
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13.5px;line-height:1.9}
    .zch-cred b{color:#fff}
    </style>
    <?php
}

/** Existuje admin stránka s týmto slugom? */
function zc_hub_has($slug) {
    global $submenu;
    if (empty($submenu[ZC_HUB_SLUG])) return false;
    foreach ($submenu[ZC_HUB_SLUG] as $item) {
        if (($item[2] ?? '') === $slug) return true;
    }
    return false;
}

/* ───────────────────────── Prehľad ───────────────────────── */

function zc_hub_page() {
    $maint = (int) get_option('zc_maintenance_on', 0);
    zc_hub_styles();
    ?>
    <div class="wrap zch">
        <div class="zch-head">
            <h1>Web Zdenky</h1>
            <span class="zch-pill <?php echo $maint ? 'off' : 'on' ?>">
                <?php echo $maint ? 'Údržba – web je zatvorený' : 'Web je online' ?>
            </span>
            <a href="<?php echo esc_url(home_url('/')) ?>" target="_blank" class="button">Pozrieť web →</a>
        </div>

        <?php if (current_user_can('manage_options')): ?>
        <div class="zch-note" style="margin-bottom:22px">
            Všetko vlastné pre tento web nájdeš tu v menu. Bežné veci WordPressu
            (stránky, médiá, používatelia, pluginy) sú ako vždy nižšie.
        </div>
        <?php endif; ?>

        <div class="zch-sec">Obsah</div>
        <div class="zch-grid">
            <?php
            $panel = function_exists('pp_panel_page_id') ? get_post(pp_panel_page_id()) : get_page_by_path('realitny-panel');
            zc_hub_card('Nehnuteľnosti', 'Ponuky, fotky, ceny a stavy.', admin_url('edit.php?post_type=property'), '🏠', post_type_exists('property'));
            zc_hub_card('Recenzie', 'Referencie klientov a napojenie na Google.', admin_url('admin.php?page=zc-reviews'), '★', zc_hub_has('zc-reviews'));
            zc_hub_card('Newsletter', 'Odberatelia, kampane a rozposielanie ponúk.', admin_url('admin.php?page=zc-newsletter'), '✉', zc_hub_has('zc-newsletter'));
            zc_hub_card('Ebook', 'PDF lead-magnet a jeho formulár.', admin_url('admin.php?page=zc-ebook'), '📘', zc_hub_has('zc-ebook'));
            zc_hub_card('Realitný panel', 'Jednoduchá správa pre maklérku (bez wp-adminu).', $panel ? get_permalink($panel) : '', '⌂', (bool) $panel);
            zc_hub_card('Priečinky fotiek', 'Médiá roztriedené po ponukách.', admin_url('edit-tags.php?taxonomy=zc_media_folder'), '📁', taxonomy_exists('zc_media_folder'));
            ?>
        </div>

        <?php if (current_user_can('manage_options')): ?>
        <div class="zch-sec">Nastavenia</div>
        <div class="zch-grid">
            <?php
            zc_hub_card('Notifikácie', 'Komu chodia správy z formulárov.', admin_url('admin.php?page=zc-notifikacie'), '🔔', true);
            zc_hub_card('Stránky a údržba', 'Vytvorenie stránok, režim údržby, WebP.', admin_url('admin.php?page=zdenka-setup'), '⚙', true);
            zc_hub_card('Náhľad pre klienta', 'Účet, ktorý smie iba pozerať web.', admin_url('admin.php?page=zc-nahlad'), '👁', true);
            zc_hub_card('Vzhľad a texty', 'Fotky, kontakty, farby – Prispôsobiť.', admin_url('customize.php'), '🎨', true);
            zc_hub_card('Zabezpečenie (2FA)', 'Dvojfaktorové prihlásenie.', admin_url('admin.php?page=zc-2fa'), '🔒', zc_hub_has('zc-2fa'));
            zc_hub_card('Nástroje', 'Stav systému, cache, test e-mailu.', admin_url('admin.php?page=zc-nastroje'), '🛠', true);
            ?>
        </div>

        <div class="zch-sec">Dohľad</div>
        <div class="zch-grid">
            <?php
            zc_hub_card('Bezpečnostný denník', 'Kto sa prihlásil, odkiaľ a čo zmenil.', admin_url('admin.php?page=zc-dennik'), '📋', zc_hub_has('zc-dennik'));
            zc_hub_card('Zálohy', 'Denná záloha celého panela.', admin_url('admin.php?page=zc-zalohy'), '💾', zc_hub_has('zc-zalohy'));
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function zc_hub_card($title, $desc, $url, $icon, $enabled = true) {
    if (!$enabled || !$url) return;
    printf(
        '<a class="zch-card" href="%s"><span class="zch-ico">%s</span><h3>%s</h3><p>%s</p></a>',
        esc_url($url), esc_html($icon), esc_html($title), esc_html($desc)
    );
}

/* ───────────────────────── Notifikácie ───────────────────────── */

function zc_hub_notify_page() {
    zc_hub_styles();
    echo '<div class="wrap zch"><div class="zch-head"><h1>Notifikácie z formulárov</h1></div>';
    echo '<div class="zch-note" style="margin-bottom:20px">Tu určíš, komu prídu e-maily z kontaktu, odhadu a ebooku.
          Bez tohto nastavenia chodia na administrátorský e-mail webu – teda každému účtu s tou istou adresou.
          <strong>Odberateľov newslettera sa to netýka, tým sa nič neposiela automaticky.</strong></div>';
    echo function_exists('zc_notify_settings_box') ? zc_notify_settings_box() : '';
    echo '</div>';
}

/* ───────────────────────── Náhľad pre klienta ───────────────────────── */

function zc_hub_preview_page() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    zc_hub_styles();

    $created = null;
    if (isset($_POST['zc_prev_create']) && check_admin_referer('zc_prev')) {
        $created = zc_create_preview_user(sanitize_user($_POST['login'] ?? 'nahlad', true));
    }

    $users = get_users(['role' => ZC_PREVIEW_ROLE, 'number' => 20]);
    ?>
    <div class="wrap zch">
        <div class="zch-head"><h1>Náhľad webu pre klienta</h1></div>

        <div class="zch-note" style="margin-bottom:20px">
            Účet s rolou <strong>Náhľad webu</strong> nemá žiadne oprávnenia – nevie nič upraviť,
            nedostane sa do wp-adminu ani do realitného panelu. Slúži iba na to, aby si mohol
            klient web pozrieť aj vtedy, keď je zapnutý režim údržby.
        </div>

        <?php if ($created): ?>
        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Prístup vytvorený – pošli ho klientovi</h2>
            <div class="zch-cred">
                <div>Adresa: <b><?php echo esc_html(wp_login_url(home_url('/'))); ?></b></div>
                <div>Meno: <b><?php echo esc_html($created[0]); ?></b></div>
                <div>Heslo: <b><?php echo esc_html($created[1]); ?></b></div>
            </div>
            <p style="color:#b45309;font-size:12.5px;margin-bottom:0">Heslo sa už znovu nezobrazí. Ulož si ho teraz.</p>
        </div>
        <?php endif; ?>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Vytvoriť / obnoviť prístup</h2>
            <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <?php wp_nonce_field('zc_prev'); ?>
                <input type="text" name="login" value="nahlad" style="padding:7px 11px;border-radius:8px;border:1.5px solid #E6DFD2">
                <button type="submit" name="zc_prev_create" value="1" class="button button-primary">Vytvoriť prístup</button>
                <span style="color:#6b6560;font-size:12.5px">Ak účet už existuje, vygeneruje sa mu nové heslo.</span>
            </form>
        </div>

        <?php if ($users): ?>
        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Existujúce náhľadové účty</h2>
            <table class="zch-tbl">
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo esc_html($u->user_login); ?></strong></td>
                    <td>
                        <a href="<?php echo esc_url(get_edit_user_link($u->ID)); ?>">Upraviť</a> ·
                        <a href="<?php echo esc_url(admin_url('users.php')); ?>">Zmazať v Používateľoch</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/* ───────────────────────── Nástroje pre webmastera ───────────────────────── */

function zc_hub_tools_page() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    zc_hub_styles();

    $msg = '';
    if (isset($_POST['zc_tool']) && check_admin_referer('zc_tools')) {
        switch ($_POST['zc_tool']) {
            case 'rewrite':
                flush_rewrite_rules();
                $msg = 'Trvalé odkazy boli obnovené.';
                break;
            case 'cache':
                update_option('zc_cache_version', 'v' . time());
                $msg = 'Vyrovnávacia pamäť webu (PWA) dostala novú verziu – návštevníkom sa načíta aktuálny web.';
                break;
            case 'transients':
                $msg = zc_hub_clear_transients() . ' dočasných záznamov zmazaných (Google recenzie, kurzy mien…).';
                break;
            case 'webp':
                $on = get_option('zc_webp_sizes', '1') === '1' ? '0' : '1';
                update_option('zc_webp_sizes', $on);
                $msg = $on === '1'
                    ? 'Zmenšeniny sa budú ukladať ako WebP. Spusti ešte „Prepočítať veľkosti fotiek", nech sa prerobia aj staršie.'
                    : 'Zmenšeniny sa budú ukladať ako JPEG.';
                break;
            case 'mincss':
                $on = get_option('zc_min_css', '1') === '1' ? '0' : '1';
                update_option('zc_min_css', $on);
                $msg = $on === '1'
                    ? 'Zmenšené štýly sú zapnuté (rýchlejšie načítanie).'
                    : 'Zmenšené štýly sú vypnuté – web beží na pôvodnom main.css.';
                break;
            case 'thumbs':
                $msg = zc_hub_regenerate_thumbs(15);
                break;
            case 'mail':
                $to = function_exists('zc_notify_to') ? zc_notify_to('contact') : [get_option('admin_email')];
                $ok = wp_mail($to, 'Testovací e-mail z webu', 'Ak čítaš túto správu, odosielanie e-mailov funguje.');
                $msg = $ok
                    ? 'Testovací e-mail odoslaný na: ' . esc_html(implode(', ', (array) $to))
                    : 'E-mail sa nepodarilo odoslať. Skontroluj SMTP na hostingu.';
                break;
        }
    }

    $php_ok  = version_compare(PHP_VERSION, '7.4', '>=');
    $https   = is_ssl() || strpos(home_url(), 'https://') === 0;
    $perma   = get_option('permalink_structure');
    $cron    = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    $debug   = defined('WP_DEBUG') && WP_DEBUG;
    $cookie  = defined('COOKIE_DOMAIN') && COOKIE_DOMAIN;
    $recips  = function_exists('zc_notify_recipients') ? zc_notify_recipients() : [];
    ?>
    <div class="wrap zch">
        <div class="zch-head"><h1>Nástroje pre webmastera</h1></div>
        <?php if ($msg): ?><div class="notice notice-success"><p><?php echo wp_kses_post($msg); ?></p></div><?php endif; ?>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Stav systému</h2>
            <table class="zch-tbl">
                <tr><td>PHP</td><td><span class="<?php echo $php_ok ? 'zch-ok' : 'zch-bad' ?>"><?php echo esc_html(PHP_VERSION); ?></span></td></tr>
                <tr><td>WordPress</td><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
                <tr><td>Téma</td><td><?php $t = wp_get_theme(); echo esc_html($t->get('Name') . ' ' . $t->get('Version')); ?></td></tr>
                <tr><td>HTTPS</td><td><span class="<?php echo $https ? 'zch-ok' : 'zch-bad' ?>"><?php echo $https ? 'zapnuté' : 'chýba – zapni SSL na hostingu' ?></span></td></tr>
                <tr><td>Trvalé odkazy</td><td><?php echo $perma ? '<span class="zch-ok">v poriadku</span>' : '<span class="zch-bad">nastav Nastavenia → Trvalé odkazy</span>'; ?></td></tr>
                <tr><td>Limit pamäte</td><td><?php echo esc_html(ini_get('memory_limit')); ?></td></tr>
                <tr><td>Max. veľkosť súboru</td><td><?php echo esc_html(size_format(wp_max_upload_size())); ?></td></tr>
                <tr><td>WP-Cron</td><td><?php echo $cron ? '<span class="zch-warn">vypnutý (DISABLE_WP_CRON)</span>' : 'zapnutý'; ?></td></tr>
                <tr><td>Režim ladenia</td><td><?php echo $debug ? '<span class="zch-warn">WP_DEBUG je zapnutý – na ostrom webe vypni</span>' : 'vypnutý'; ?></td></tr>
                <tr><td>COOKIE_DOMAIN</td><td><?php echo $cookie
                    ? '<span class="zch-warn">nastavená (' . esc_html(COOKIE_DOMAIN) . ') – subdoména panela sa zrušila, riadok vo wp-config.php môžeš zmazať</span>'
                    : 'nenastavená (správne)'; ?></td></tr>
                <tr><td>Formát zmenšenín</td><td><?php
                    $webp_ok = !function_exists('zc_webp_supported') || zc_webp_supported();
                    if (!$webp_ok) {
                        echo '<span class="zch-warn">server nepodporuje WebP – používa sa JPEG</span>';
                    } elseif (get_option('zc_webp_sizes', '1') === '1') {
                        echo '<span class="zch-ok">WebP</span> · asi o tretinu menšie súbory než JPEG';
                    } else {
                        echo 'JPEG';
                    }
                ?></td></tr>
                <tr><td>Správca značiek Google</td><td><?php
                    if (!function_exists('zc_gtm_id') || !zc_gtm_id()) {
                        echo '<span class="zch-warn">nenastavený</span> – ID vlož v Prispôsobiť → Miestne SEO';
                    } else {
                        echo '<span class="zch-ok">' . esc_html(zc_gtm_id()) . '</span>';
                        if (get_theme_mod('zc_gtm_skip_admins', false)) echo ' · prihlásení redaktori sa nemerajú';
                    }
                ?></td></tr>
                <tr><td>Notifikácie chodia na</td><td><?php echo $recips ? esc_html(implode(', ', $recips)) : '<span class="zch-warn">nikam</span>'; ?></td></tr>
                <?php if (function_exists('pp_panel_page_id')):
                    $pid   = pp_panel_page_id();
                    $ppage = $pid ? get_post($pid) : null; ?>
                <tr><td>Realitný panel</td><td><?php
                    if (!$ppage) {
                        echo '<span class="zch-bad">stránka sa nenašla – otvor Stránky a údržba</span>';
                    } else {
                        echo '<a href="' . esc_url(get_permalink($pid)) . '" target="_blank">' . esc_html(get_permalink($pid)) . '</a>';
                        if ($ppage->post_name !== 'realitny-panel') {
                            echo ' <span class="zch-warn">(slug je „' . esc_html($ppage->post_name) . '" – v koši zrejme leží stará stránka, zmaž ju natrvalo)</span>';
                        }
                    }
                ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Vlastné pluginy</h2>
            <table class="zch-tbl">
            <?php
            $plugins = [
                'Property Manager Pro' => defined('PROPERTY_PRO_PATH'),
                'ZC Recenzie'          => function_exists('zcr_table'),
                'ZC Newsletter'        => function_exists('zcn_table'),
                'ZC Ebook'             => function_exists('zc_ebook_enabled'),
                'ZC Zabezpečenie 2FA'  => function_exists('zc2fa_admin_page'),
            ];
            foreach ($plugins as $name => $active): ?>
                <tr><td><?php echo esc_html($name); ?></td>
                    <td><?php echo $active ? '<span class="zch-ok">aktívny</span>' : '<span style="color:#94a3b8">neaktívny</span>'; ?></td></tr>
            <?php endforeach; ?>
            </table>
        </div>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Rýchle akcie</h2>
            <form method="post" class="zch-actions">
                <?php wp_nonce_field('zc_tools'); ?>
                <button class="button" name="zc_tool" value="rewrite">Obnoviť trvalé odkazy</button>
                <button class="button" name="zc_tool" value="cache">Vynulovať cache webu</button>
                <button class="button" name="zc_tool" value="transients">Zmazať dočasné dáta</button>
                <button class="button" name="zc_tool" value="thumbs">Prepočítať veľkosti fotiek</button>
                <button class="button" name="zc_tool" value="webp"><?php
                    echo get_option('zc_webp_sizes', '1') === '1'
                        ? 'Vypnúť WebP zmenšeniny' : 'Zapnúť WebP zmenšeniny';
                ?></button>
                <button class="button" name="zc_tool" value="mincss"><?php
                    echo get_option('zc_min_css', '1') === '1'
                        ? 'Vypnúť zmenšené štýly' : 'Zapnúť zmenšené štýly';
                ?></button>
                <button class="button button-primary" name="zc_tool" value="mail">Poslať testovací e-mail</button>
            </form>
            <p style="color:#6b6560;font-size:12.5px;margin-bottom:0">
                Obnovenie odkazov pomôže, keď stránka hlási „nenájdené“.
                Vynulovanie cache použi po väčšej zmene vzhľadu.
                <strong>Prepočítanie veľkostí</strong> dorobí k starším fotkám väčšie verzie pre
                veľké monitory – beží po dávkach, klikaj, kým nenapíše „hotovo“.
            </p>
        </div>
    </div>
    <?php
}

/**
 * Dorobí chýbajúce veľkosti k už nahratým fotkám.
 * Beží po dávkach, aby stránka nespadla na časový limit – postup si pamätá.
 */
function zc_hub_regenerate_thumbs($batch = 15) {
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $all = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'numberposts'    => -1,
        'fields'         => 'ids',
    ]);
    $total = count($all);
    if (!$total) return 'Na webe nie sú žiadne fotky.';

    $done = array_map('intval', (array) get_option('zc_regen_done', []));
    $todo = array_values(array_diff($all, $done));

    if (!$todo) {
        delete_option('zc_regen_done');
        return 'Hotovo – všetkých ' . $total . ' fotiek má prepočítané veľkosti.';
    }

    foreach (array_slice($todo, 0, $batch) as $id) {
        $file = get_attached_file($id);
        if ($file && file_exists($file)) {
            $meta = wp_generate_attachment_metadata($id, $file);
            if ($meta && !is_wp_error($meta)) wp_update_attachment_metadata($id, $meta);
        }
        $done[] = (int) $id;
    }
    update_option('zc_regen_done', $done, false);

    $left = $total - count($done);
    if ($left <= 0) {
        delete_option('zc_regen_done');
        return 'Hotovo – prepočítaných ' . $total . ' fotiek.';
    }
    return 'Prepočítané ' . count($done) . ' z ' . $total . '. Zostáva ' . $left
         . ' – klikni na tlačidlo znova.';
}

function zc_hub_clear_transients() {
    global $wpdb;
    $n = $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '\_transient\_zc%'
            OR option_name LIKE '\_transient\_timeout\_zc%'
            OR option_name LIKE '\_transient\_pp%'
            OR option_name LIKE '\_transient\_timeout\_pp%'"
    );
    return max(0, (int) $n);
}
