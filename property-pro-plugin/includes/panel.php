<?php
defined('ABSPATH') || exit;
require_once PROPERTY_PRO_PATH . 'includes/amenities.php';

// Create panel page on activation
register_activation_hook(PROPERTY_PRO_PATH . 'property-manager-pro.php', 'zcpp_ensure_panel_page');

// Also ensure page exists on every admin load (handles file-only updates)
add_action('admin_init', 'zcpp_ensure_panel_page');

/**
 * Zabezpečí, že stránka panela existuje a je funkčná.
 * Hľadá zámerne aj v KOŠI a aj pod iným typom obsahu – stránka v koši totiž
 * naďalej blokuje slug „realitny-panel", takže novovytvorená by dostala
 * „realitny-panel-2" a pôvodná adresa by trvalo vracala 404.
 */
function zcpp_ensure_panel_page() {
    $found = get_posts([
        'post_type'   => ['page', 'property', 'post'],
        'name'        => 'realitny-panel',
        'post_status' => ['publish', 'draft', 'private', 'pending', 'future', 'trash'],
        'numberposts' => 1,
    ]);

    if (empty($found)) {
        wp_insert_post([
            'post_type'    => 'page',
            'post_title'   => 'Realitný Panel',
            'post_name'    => 'realitny-panel',
            'post_content' => '[realitny_panel]',
            'post_status'  => 'publish',
        ]);
        flush_rewrite_rules(false);
        return;
    }

    $page    = $found[0];
    $changed = false;

    // V koši → obnoviť
    if ($page->post_status === 'trash') {
        wp_untrash_post($page->ID);
        $page = get_post($page->ID);
        $changed = true;
    }
    // Zlý typ obsahu (napr. omylom prepísaná na ponuku) → vrátiť na stránku
    if ($page && $page->post_type !== 'page') {
        wp_update_post(['ID' => $page->ID, 'post_type' => 'page']);
        $changed = true;
    }
    // Nie je zverejnená → zverejniť
    if ($page && $page->post_status !== 'publish') {
        wp_update_post(['ID' => $page->ID, 'post_status' => 'publish']);
        $changed = true;
    }
    // Chýba shortcode → doplniť (inak by sa zobrazila prázdna stránka)
    if ($page && strpos((string) $page->post_content, '[realitny_panel]') === false) {
        wp_update_post(['ID' => $page->ID, 'post_content' => trim($page->post_content . "\n\n[realitny_panel]")]);
        $changed = true;
    }

    if ($changed) flush_rewrite_rules(false);
    if ($page) update_option('pp_panel_page_id', (int) $page->ID);
}

/**
 * ID stránky panela – zámerne NIE podľa slugu.
 * Keď stránka raz skončí v koši, nová dostane „realitny-panel-2" a všetky
 * kontroly typu is_page('realitny-panel') zrazu zlyhajú: panel sa síce
 * zobrazí (shortcode), ale formuláre sa neuložia. Preto hľadáme podľa
 * shortcodu a nájdené ID si zapamätáme.
 */
function pp_panel_page_id() {
    static $cached = null;
    if ($cached !== null) return $cached;

    $ok = function ($p) {
        return $p && $p->post_type === 'page' && $p->post_status === 'publish'
            && strpos((string) $p->post_content, '[realitny_panel]') !== false;
    };

    $id = (int) get_option('pp_panel_page_id', 0);
    if ($id && $ok(get_post($id))) return $cached = $id;

    $page = get_page_by_path('realitny-panel');
    if (!$ok($page)) {
        global $wpdb;
        $found = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type='page' AND post_status='publish'
               AND post_content LIKE '%[realitny\_panel]%'
             ORDER BY ID ASC LIMIT 1"
        );
        $page = $found ? get_post((int) $found) : null;
    }

    $cached = $page ? (int) $page->ID : 0;
    if ($cached) update_option('pp_panel_page_id', $cached);
    return $cached;
}

/** Adresa panela (voliteľne s query reťazcom). Vždy vráti úplnú adresu. */
function pp_panel_url($query = '') {
    $id  = pp_panel_page_id();
    $url = $id ? get_permalink($id) : '';
    if (!$url) $url = home_url('/realitny-panel/');
    return $query === '' ? $url : $url . '?' . ltrim($query, '?&');
}

/**
 * Presmerovanie, ktoré sa nedá „stratiť".
 * Keď už boli hlavičky odoslané, wp_safe_redirect() ticho zlyhá a používateľ
 * ostane visieť na prázdnej stránke s odoslaným formulárom – a obnovenie (F5)
 * ho pošle znova. Preto v takom prípade presmerujeme aspoň cez HTML.
 */
function pp_go($url) {
    $url = $url ?: home_url('/');
    if (!headers_sent()) {
        wp_safe_redirect($url);
        exit;
    }
    printf(
        '<!doctype html><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=%s">'
        . '<p style="font-family:system-ui,sans-serif;padding:28px;font-size:15px">'
        . 'Hotovo. <a href="%s">Pokračovať do panela →</a></p>',
        esc_url($url), esc_url($url)
    );
    exit;
}

/** CSV export odberateľov dostupný aj maklérke bez vstupu do wp-adminu. */
add_action('admin_post_pp_export_subscribers', function () {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        wp_die('Nemáte oprávnenie exportovať kontakty.', 403);
    }
    check_admin_referer('pp_export_subscribers');
    if (!function_exists('zcn_table')) {
        wp_die('Newsletter nie je aktívny.', 400);
    }

    global $wpdb;
    $rows = $wpdb->get_results('SELECT email,name,interest,status,source,subscribed_at,confirmed_at FROM ' . zcn_table() . ' ORDER BY subscribed_at DESC', ARRAY_A);
    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="zc-newsletter-kontakty-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['E-mail', 'Meno', 'Kategória', 'Stav', 'Zdroj', 'Prihlásený', 'Potvrdený'], ';');
    foreach ($rows as $row) {
        $row['interest'] = function_exists('zcn_interest_label') ? pp_nl_interest_label($row['interest']) : $row['interest'];
        $row['source'] = function_exists('zcn_source_label') ? pp_nl_source_label($row['source']) : $row['source'];
        fputcsv($out, array_values($row), ';');
    }
    fclose($out);
    exit;
});

/** Sme práve na stránke panela? */
function pp_is_panel_page() {
    $id = pp_panel_page_id();
    return $id ? is_page($id) : is_page('realitny-panel');
}

/**
 * Editor a knižnica médií musia byť zaradené ešte pred wp_head().
 *
 * Keď sa wp_enqueue_editor() zavolá až vo vnútri shortcodu, WordPress už
 * nestihne vypísať časť štýlov editora. Výsledkom je prázdna/neviditeľná
 * plocha TinyMCE, hoci samotné pole v HTML existuje.
 */
function pp_enqueue_panel_assets() {
    if (!pp_is_panel_page() || !is_user_logged_in() || !current_user_can('edit_posts')) return;

    $edit_id = intval($_GET['id'] ?? 0);
    $media_args = (
        (($_GET['action'] ?? '') === 'edit')
        && $edit_id
        && get_post_type($edit_id) === 'property'
        && current_user_can('edit_post', $edit_id)
    ) ? ['post' => $edit_id] : [];

    wp_enqueue_media($media_args);
    wp_enqueue_editor();
}
add_action('wp_enqueue_scripts', 'pp_enqueue_panel_assets', 5);

/**
 * Spoľahlivá konfigurácia natívneho WordPress TinyMCE.
 * Je uložená v plugine, preto funguje aj po prepnutí z témy Zdenky na inú tému.
 */
function pp_panel_editor_settings($textarea_name, $rows = 10) {
    return [
        'textarea_name' => $textarea_name,
        'textarea_rows' => max(5, (int) $rows),
        'media_buttons' => false,
        'teeny' => false,
        'quicktags' => true,
        'tinymce' => [
            'toolbar1' => 'undo,redo,formatselect,|,bold,italic,underline,strikethrough,forecolor,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,outdent,indent,|,link,unlink,|,removeformat',
            'toolbar2' => '',
            'block_formats' => 'Odsek=p;Nadpis 1=h1;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Nadpis 5=h5;Nadpis 6=h6;Predformátované=pre',
            'browser_spellcheck' => true,
            'resize' => true,
            'content_style' => 'body{font-family:DM Sans,sans-serif;font-size:16px;line-height:1.8;color:#2C2825;padding:12px}',
        ],
    ];
}

/**
 * Samoliečenie na frontende: keď adresa panela vráti 404 (napr. po presune do
 * koša), stránku opravíme a používateľa pošleme späť. Beží len pri 404 na
 * tejto adrese, takže bežné načítanie webu to nespomaľuje.
 */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if (strpos($path, 'realitny-panel') !== 0) return;

    zcpp_ensure_panel_page();
    $page = get_page_by_path('realitny-panel');
    if (!$page || $page->post_status !== 'publish') return; // nepodarilo sa – necháme 404

    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    wp_safe_redirect(get_permalink($page) . ($qs !== '' ? '?' . $qs : ''), 302);
    exit;
}, 5);

add_shortcode('realitny_panel', function() {
    if (!is_user_logged_in()) return panel_login_page();
    if (!current_user_can('edit_posts')) return '<p style="text-align:center;padding:40px;color:#e74c3c">Nemáš prístup.</p>';

    // Pri úprave ponuky povieme knižnici médií, ku ktorej ponuke práve pracujeme.
    // Nahraté fotky sa tak rovno pripnú k ponuke a server ich zaradí do jej
    // priečinka – nemusí sa čakať na uloženie formulára.
    $pp_edit_id = intval($_GET['id'] ?? 0);
    $pp_media   = ((($_GET['action'] ?? '') === 'edit') && $pp_edit_id
                   && get_post_type($pp_edit_id) === 'property'
                   && current_user_can('edit_post', $pp_edit_id))
                ? ['post' => $pp_edit_id] : [];
    // Bezpečnostný fallback pre netypické témy, ktoré shortcode vykreslia
    // mimo štandardného cyklu. Pri bežnom paneli sú assety už načítané vyššie.
    wp_enqueue_media($pp_media);
    wp_enqueue_editor();
    return panel_dashboard();
});

function panel_login_page() {
    $url = wp_login_url(pp_panel_url());
    return '
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F5F1EA;padding:20px;font-family:-apple-system,BlinkMacSystemFont,\'DM Sans\',sans-serif">
        <div style="background:#fff;padding:48px 40px;border-radius:20px;max-width:400px;width:100%;box-shadow:0 12px 48px rgba(60,50,30,.12);border:1px solid #E2DACE;text-align:center">
            <div style="width:64px;height:64px;background:#F5F1EA;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;color:#B8A47A"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
            <h1 style="font-family:\'Playfair Display\',Georgia,serif;font-size:22px;margin-bottom:8px;color:#1C1A18">Realitný Panel</h1>
            <p style="color:#6B6560;font-size:14px;margin-bottom:32px">Správa nehnuteľností</p>
            <a href="'.esc_url($url).'" style="display:block;padding:14px;background:#B8A47A;color:#1C1A18;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;letter-spacing:.8px;text-transform:uppercase;transition:all .2s">Prihlásiť sa</a>
        </div>
    </div>';
}

function panel_dashboard() {
    $action = sanitize_text_field($_GET['action'] ?? 'home');
    $pid    = intval($_GET['id'] ?? 0);
    $user   = wp_get_current_user();
    $logout = wp_logout_url(home_url());
    ob_start();
    ?>
<style>
/* Lokálne fonty z aktívnej témy (bez Google serverov) */
@font-face{font-family:'DM Sans';font-weight:100 1000;font-display:swap;src:url('<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/fonts/DMSans.woff2') format('woff2')}
@font-face{font-family:'Playfair Display';font-style:normal;font-weight:400 900;font-display:swap;src:url('<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/fonts/PlayfairDisplay.woff2') format('woff2')}
@font-face{font-family:'Playfair Display';font-style:italic;font-weight:400 900;font-display:swap;src:url('<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/fonts/PlayfairDisplay-Italic.woff2') format('woff2')}
</style>
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --bg:#FCFBF8; --section:#F5F1EA; --accent:#B8A47A; --accent-dk:#9A8660; --accent-txt:#7C5E33;
    --text:#2C2C2C; --muted:#6B6560; --white:#fff; --border:#E2DACE; --dark:#1C1A18;
    --serif:'Playfair Display',Georgia,serif; --sans:'DM Sans',system-ui,sans-serif;
    --r:12px; --r-sm:8px; --sh:0 2px 12px rgba(60,50,30,.08); --sh-sm:0 1px 4px rgba(60,50,30,.06);
}
html{overflow-x:clip}
body{font-family:var(--sans);background:var(--bg);color:var(--text);min-height:100vh;overflow-x:clip;max-width:100%}
/* Nič nesmie vytlačiť stránku do šírky */
.pc,.ph,.pf,.pnl-hero,.pnl-stats,.pnl-quick,.pnl-home-grid{max-width:100%;box-sizing:border-box}
.pc img,.pc table,.pc pre,.pc iframe{max-width:100%}
.pc input,.pc select,.pc textarea{max-width:100%}

/* HEADER */
.ph{background:var(--white);border-bottom:1px solid var(--border);padding:0 28px;height:66px;display:flex;align-items:center;gap:24px;position:sticky;top:0;z-index:100;box-shadow:0 1px 12px rgba(60,50,30,.06)}
.ph-logo{font-family:var(--serif);font-size:16px;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:11px;flex-shrink:0}
.ph-logo-icon{width:38px;height:38px;background:var(--section);border-radius:11px;display:flex;align-items:center;justify-content:center}
.ph-logo small{font-family:var(--sans);font-size:10px;font-weight:500;letter-spacing:1.2px;text-transform:uppercase;color:var(--accent-txt);display:block;margin-top:1px}
.ph-nav{display:flex;gap:3px;align-items:center;margin:0 auto 0 8px}
.ph-nav a{position:relative;padding:9px 15px;border-radius:9px;font-size:13.5px;font-weight:600;letter-spacing:.1px;text-decoration:none;color:var(--muted);transition:color .18s,background .18s;white-space:nowrap;display:inline-flex;align-items:center;gap:6px}
.ph-nav a:hover{background:var(--section);color:var(--dark)}
.ph-nav a.active{color:var(--dark)}
.ph-nav a.active::after{content:'';position:absolute;left:15px;right:15px;bottom:-1px;height:2.5px;border-radius:3px;background:var(--accent)}
.ph-right{display:flex;align-items:center;gap:12px;flex-shrink:0}
.ph-user{font-size:13px;color:var(--muted);font-weight:600}
.ph-logout{padding:9px 16px;background:var(--section);border:1.5px solid var(--border);border-radius:9px;font-size:12.5px;font-weight:600;cursor:pointer;color:var(--text);font-family:var(--sans);transition:all .2s}
.ph-logout:hover{background:#e8e0d5;border-color:#ccc}

/* CONTENT */
.pc{max-width:1440px;margin:0 auto;padding:32px 40px;min-height:calc(100vh - 66px)}
/* Písanie newslettera a šablóna ponuky: na PC využijeme šírku monitora,
   na mobile ostáva všetko pod sebou. Text sa nikdy neroztiahne donekonečna –
   editor je vľavo, nastavenia vpravo. */
.pnl-wide{max-width:100%}
.pnl-nl-form{display:grid;grid-template-columns:minmax(0,1fr);gap:18px;align-items:start}
@media(min-width:1100px){
    .pnl-nl-form{grid-template-columns:minmax(0,1.9fr) minmax(300px,.85fr)}
    .pnl-nl-form .pnl-nl-main{min-width:0}
    .pnl-nl-form .pnl-nl-side{position:sticky;top:86px}
}
.pnl-nl-side{display:flex;flex-direction:column;gap:14px;min-width:0}
.pnl-wide .wp-editor-wrap .mce-edit-area iframe,
.pnl-wide textarea.wp-editor-area{min-height:420px}
@media(max-width:900px){
    .pnl-wide .wp-editor-wrap .mce-edit-area iframe,
    .pnl-wide textarea.wp-editor-area{min-height:260px}
}
.pc--home{display:flex;flex-direction:column}
/* Grid deti musia môcť zmenšiť pod obsah – inak dlhé texty vytláčajú stránku */
.pnl-home-grid>*,.pnl-stats>*,.prop-list>*{min-width:0}

/* PROPERTY LIST */
.prop-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:20px}
.prop-item{background:var(--white);border-radius:var(--r);overflow:hidden;border:1px solid var(--border);box-shadow:var(--sh);transition:all .2s;display:flex;flex-direction:column}
.prop-item:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(60,50,30,.1);border-color:transparent}
.prop-item-img{height:190px;background:var(--section);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:28px;overflow:hidden;position:relative;flex-shrink:0}
.prop-item-img img{width:100%;height:100%;object-fit:cover}
.prop-item-badge{position:absolute;bottom:10px;left:10px;background:var(--accent);color:var(--dark);font-size:9px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:4px 10px;border-radius:50px}
.prop-item-body{padding:15px 16px;display:flex;flex-direction:column;flex:1}
.prop-item-title{font-family:var(--serif);font-size:15px;font-weight:700;color:var(--dark);margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prop-item-price{font-size:17px;font-weight:800;color:var(--accent-txt);font-family:var(--sans);font-variant-numeric:tabular-nums;margin-bottom:10px}
.prop-item-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto}
.prop-drag{display:none;align-items:center;justify-content:center;width:42px;flex:0 0 42px;color:var(--muted);font-size:22px;cursor:grab;user-select:none}
.prop-mobile-order{display:none}
.prop-list.is-rows{display:flex;flex-direction:column;gap:10px}
.prop-list.is-rows .prop-item{display:grid;grid-template-columns:42px 130px minmax(0,1fr);min-height:116px;border-radius:12px}
.prop-list.is-rows .prop-item:hover{transform:none;border-color:var(--accent)}
.prop-list.is-rows .prop-drag{display:flex}
.prop-list.is-rows .prop-item-img{height:100%;min-height:116px}
.prop-list.is-rows .prop-item-body{display:grid;grid-template-columns:minmax(180px,1fr) 150px 170px minmax(280px,auto);gap:14px;align-items:center;padding:13px 16px}
.prop-list.is-rows .prop-item-title,.prop-list.is-rows .prop-item-price{margin:0}
.prop-list.is-rows .prop-item-actions{margin:0;justify-content:flex-end}
.prop-list.is-rows .pnl-check{left:50px!important}
.prop-item.is-dragging{opacity:.35}
.prop-item[hidden]{display:none!important}
.pnl-list-tools{display:flex;gap:10px;align-items:end;flex-wrap:wrap;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:14px;margin-bottom:16px}
.pnl-list-tools label{display:flex;flex-direction:column;gap:4px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.pnl-list-tools input,.pnl-list-tools select{min-height:38px;padding:8px 11px;border:1.5px solid var(--border);border-radius:8px;background:#fff;font:13px var(--sans);color:var(--text)}
.pnl-view-toggle{display:flex;gap:4px;margin-left:auto}
.pnl-view-toggle button{min-height:38px;padding:8px 13px;border:1.5px solid var(--border);border-radius:8px;background:var(--section);font:700 12px var(--sans);cursor:pointer;color:var(--muted)}
.pnl-view-toggle button.active{background:var(--dark);border-color:var(--dark);color:#fff}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 14px;border-radius:var(--r-sm);font-size:11px;font-weight:700;border:none;cursor:pointer;text-decoration:none;font-family:var(--sans);letter-spacing:.3px;transition:all .2s}
.btn-primary{background:var(--accent);color:var(--dark)}
.btn-primary:hover{background:var(--accent-dk)}
.btn-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn-danger:hover{background:#fee2e2}
.btn-ghost{background:var(--section);color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{background:#e8e0d5;color:var(--dark)}
.btn-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.btn-success:hover{background:#dcfce7}

/* EMPTY STATE */
.empty{text-align:center;padding:80px 20px;color:var(--muted)}
.empty-icon{font-size:52px;margin-bottom:16px;opacity:.4}

/* FORM */
.pf{background:var(--white);border-radius:var(--r);border:1px solid var(--border);box-shadow:var(--sh);overflow:hidden;max-width:1120px;margin:0 auto}
.pf-head{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--section)}
.pf-head h2{font-family:var(--serif);font-size:18px;color:var(--dark)}
.pf-tabs{display:flex;background:var(--white);border-bottom:1px solid var(--border)}
.pf-tab{padding:12px 20px;border:none;background:none;font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);cursor:pointer;position:relative;font-family:var(--sans);transition:color .2s}
.pf-tab.active{color:var(--accent-txt)}
.pf-tab.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--accent)}
.pf-body{padding:28px}
.pf-panel{display:none}.pf-panel.active{display:block}
.ff{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.ff label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.ff input,.ff select,.ff textarea{padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;color:var(--text);background:var(--white);transition:border .2s;width:100%}
.ff input:focus,.ff select:focus,.ff textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(184,164,122,.12)}
.ff textarea{min-height:90px;resize:vertical}
.ff-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ff-row-3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.pf-sep{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);padding:10px 0 8px;border-bottom:1px solid var(--border);margin:8px 0 16px}
.pf-footer{padding:18px 28px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--section)}

/* GALLERY PREVIEW */
.gp{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;margin-bottom:10px}
.gp-thumb{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;background:var(--section)}
.gp-thumb img{width:100%;height:100%;object-fit:cover}
.gp-rm{position:absolute;top:2px;right:2px;width:20px;height:20px;background:#dc2626;color:#fff;border:none;border-radius:50%;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1}
.gp-order{position:absolute;left:4px;bottom:4px;display:none;gap:3px;z-index:3}
.gp-order button{width:30px;height:30px;padding:0;border:1px solid rgba(255,255,255,.7);border-radius:6px;background:rgba(28,26,24,.82);color:#fff;font:800 15px/1 var(--sans);cursor:pointer;touch-action:manipulation}
.gp-thumb[draggable="true"]{cursor:grab;touch-action:manipulation}
.gp-thumb.is-dragging{opacity:.38}
.gp-thumb.is-over{outline:3px solid var(--accent);outline-offset:2px}
#cv-pre,#gal-pre{transition:outline-color .18s,background-color .18s}
#cv-pre.is-drop-zone,#gal-pre.is-drop-zone{outline:3px dashed var(--accent);outline-offset:5px;background:rgba(184,164,122,.09);border-radius:10px}
.gp-manager{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:13px 15px;margin:0 0 18px;background:var(--section);border:1px solid var(--border);border-radius:10px}
.gp-manager-info{display:flex;align-items:flex-start;gap:10px;min-width:220px}
.gp-manager-info strong{display:block;font-size:12px;color:var(--dark);margin-bottom:2px}
.gp-manager-info small{display:block;font-size:11.5px;line-height:1.45;color:var(--muted)}
.gp-manager-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.gp-manager-actions select{min-height:38px;padding:8px 32px 8px 11px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font:600 12px var(--sans)}

/* AMENITIES */
.am-cat{margin-bottom:20px}
.am-cat-hd{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:10px;padding:6px 10px;background:var(--section);border-radius:6px;display:inline-block}
.am-items{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:6px}
.am-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;cursor:pointer;font-size:13px;color:var(--text);transition:background .15s}
.am-item:hover{background:var(--section)}
.am-item input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer;flex-shrink:0}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;background:var(--dark);color:#fff;padding:14px 20px;border-radius:var(--r);font-size:13px;font-weight:600;z-index:9999;transform:translateY(60px);opacity:0;transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px}
.toast.show{transform:translateY(0);opacity:1}

.pnl-rev-grid{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
@media(max-width:900px){
    .pnl-rev-grid{grid-template-columns:1fr !important}
    .pnl-rev-grid > div:last-child{position:static !important}
}
@media(max-width:600px){
    .pnl-nl-stats-grid{grid-template-columns:1fr !important}
    .ff-row-3{grid-template-columns:1fr !important}
}
/* TinyMCE */
.wp-editor-wrap{border:1.5px solid var(--border);border-radius:var(--r-sm);overflow:hidden;max-width:100%;background:#fff;min-height:220px}
.wp-editor-container{background:#fff;min-height:180px}
.wp-editor-wrap .mce-tinymce,.wp-editor-wrap .mce-container,.wp-editor-wrap .mce-container-body{box-sizing:border-box;max-width:100%}
.wp-editor-wrap .mce-edit-area{background:#fff;min-height:180px}
.wp-editor-wrap .mce-edit-area iframe{display:block!important;width:100%!important;min-height:180px!important;background:#fff!important}
.wp-editor-wrap textarea.wp-editor-area{width:100%!important;min-height:180px;color:var(--text);background:#fff;border:0!important;padding:14px!important;line-height:1.65}
.wp-editor-wrap.html-active textarea.wp-editor-area{display:block!important}
.wp-editor-wrap .wp-editor-tabs{background:var(--section);padding:5px 7px 0}
.mce-toolbar .mce-btn button{padding:6px 8px !important}
.mce-tinymce{max-width:100% !important}
.pp-editor-lineheight{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 8px;padding:9px 11px;background:var(--section);border:1px solid var(--border);border-radius:9px}
.pp-editor-lineheight label{font-size:11px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)}
.pp-editor-lineheight select{min-height:38px;padding:7px 32px 7px 10px;border:1.5px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font:600 12px var(--sans)}
.pp-editor-lineheight small{flex:1;min-width:200px;color:var(--muted);font-size:11.5px;line-height:1.45}

.pnl-rev-item{display:flex;gap:14px;align-items:flex-start}

/* ═══ MOBILE (≤768px) ═══════════════════════════════════════ */
@media(max-width:768px){

    /* --- TinyMCE --- */
    .wp-editor-wrap .mce-toolbar-grp{overflow-x:auto !important;-webkit-overflow-scrolling:touch}
    .mce-toolbar .mce-btn button{padding:8px 10px !important}
    .mce-toolbar-grp .mce-flow-layout{flex-wrap:nowrap !important}
    .wp-editor-tabs{display:flex}
    .wp-switch-editor{font-size:12px !important;padding:4px 10px !important}
    textarea.wp-editor-area{font-size:16px !important} /* prevents iOS zoom-in on focus */

    /* --- Header – wraps to 2 rows: [logo][right] then [nav] --- */
    .ph{
        flex-wrap:wrap; height:auto; min-height:56px;
        padding:10px 14px; row-gap:8px;
    }
    .ph-logo{font-size:14px;gap:8px}
    .ph-logo-icon{width:30px;height:30px;font-size:15px}
    .ph-logo small{font-size:9px}
    .ph-right{gap:8px}
    .ph-user{display:none}
    .ph-logout{padding:7px 12px;font-size:11px}
    .ph-nav{
        order:3; flex-basis:100%; width:100%;
        overflow-x:auto; -webkit-overflow-scrolling:touch;
        gap:4px; scrollbar-width:none;
        padding-top:8px; border-top:1px solid var(--border);
    }
    .ph-nav::-webkit-scrollbar{display:none}
    .ph-nav a{white-space:nowrap;flex-shrink:0;padding:7px 12px;font-size:11px}

    /* --- Content spacing --- */
    .pc{padding:16px 12px}

    /* --- Property list --- */
    .prop-list{grid-template-columns:1fr}
    .prop-item{display:flex;gap:12px}
    .prop-item-img{width:100px;height:80px !important;flex-shrink:0;border-radius:var(--r-sm) 0 0 var(--r-sm) !important}
    .prop-item-body{padding:10px 12px;min-width:0}
    .prop-item-actions{flex-wrap:wrap}

    /* --- Property form --- */
    .pf-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap !important;scrollbar-width:none}
    .pf-tabs::-webkit-scrollbar{display:none}
    .pf-tab{white-space:nowrap;flex-shrink:0;padding:11px 14px;font-size:11px}
    .pf-head{padding:16px;flex-wrap:wrap;gap:10px}
    .pf-body{padding:16px}
    .pf-panel{padding:0 !important}
    .pf-footer{padding:14px 16px;flex-direction:column;gap:8px}
    .pf-footer .btn{width:100%;justify-content:center;text-align:center}
    .ff-row,.ff-row-3{grid-template-columns:1fr}

    /* --- Gallery preview --- */
    .gp{grid-template-columns:repeat(3,1fr) !important}

    /* --- Amenities --- */
    .am-items{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}

    /* --- Newsletter / Reviews grids --- */
    .pnl-nl-stats-grid{grid-template-columns:1fr !important}
    .pnl-nl-subtabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap !important;scrollbar-width:none}
    .pnl-nl-subtabs::-webkit-scrollbar{display:none}
    .pnl-nl-subtabs a{white-space:nowrap;flex-shrink:0}
    .pnl-rev-item{flex-direction:column}
    .pnl-rev-item > div:last-child{flex-direction:row;align-items:center;width:100%;flex-wrap:wrap}

    /* --- Forms & inputs (16px prevents iOS auto-zoom) --- */
    input,textarea,select{font-size:16px !important}

    /* --- Toast --- */
    .toast{left:16px;right:16px;bottom:16px;text-align:center;justify-content:center}
}

@media(max-width:420px){
    .ph-logo small{display:none}
    .am-items{grid-template-columns:1fr}
    .gp{grid-template-columns:repeat(2,1fr) !important}
}

/* ═══ HEADER v2: hamburger + dropdown ═══════════════════════ */
.ph-burger{display:none;flex-direction:column;justify-content:center;gap:5px;width:42px;height:42px;border:1.5px solid var(--border);border-radius:10px;background:var(--white);cursor:pointer;padding:0}
.ph-burger span{display:block;width:20px;height:2px;background:var(--dark);margin:0 auto;border-radius:2px;transition:transform .25s,opacity .2s}
.ph-burger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.ph-burger.open span:nth-child(2){opacity:0}
.ph-burger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
.ph-badge{background:var(--accent);color:var(--dark);border-radius:50px;padding:1px 7px;font-size:10px;font-weight:800}
.ph-nav-foot{display:none}

/* ═══ ÚVOD dashboard ════════════════════════════════════════ */
.pnl-hero{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.pnl-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:22px}
.pnl-stat{background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:18px 16px;display:flex;flex-direction:column;gap:2px;text-decoration:none;transition:all .2s;box-shadow:var(--sh)}
a.pnl-stat:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(60,50,30,.1);border-color:transparent}
.pnl-stat-ic{margin-bottom:6px}
.pnl-stat-n{font-family:var(--serif);font-size:30px;font-weight:800;line-height:1}
.pnl-stat-l{font-size:12px;color:var(--muted)}
.pnl-quick{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:30px}
.pnl-quick-btn{display:inline-flex;align-items:center;gap:8px;background:var(--white);border:1px solid var(--border);border-radius:50px;padding:9px 16px;font-size:13px;font-weight:600;color:var(--text);text-decoration:none;transition:all .2s}
.pnl-quick-btn:hover{background:var(--section);border-color:var(--accent);color:var(--dark)}
.pnl-quick-btn .pp-ic{color:var(--accent-txt)}
.pnl-home-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start}
.pnl-sec-hd{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:14px}
.pnl-sec-hd h2{font-family:var(--serif);font-size:18px;color:var(--dark)}
.pnl-sec-link{font-size:12px;color:var(--accent-txt);text-decoration:none;font-weight:600}
.pnl-sec-link:hover{text-decoration:underline}
.pnl-card-flat{background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh)}
.pnl-recent{display:flex;flex-direction:column;gap:10px}
.pnl-recent-item{display:flex;align-items:center;gap:14px;background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:10px 14px;text-decoration:none;transition:all .2s;box-shadow:var(--sh)}
.pnl-recent-item:hover{transform:translateX(3px);border-color:var(--accent)}
.pnl-recent-img{width:58px;height:46px;border-radius:8px;overflow:hidden;background:var(--section);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--muted)}
.pnl-recent-img img{width:100%;height:100%;object-fit:cover}
.pnl-recent-body{flex:1;min-width:0}
.pnl-recent-title{font-family:var(--serif);font-size:14px;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pnl-recent-price{font-size:13px;font-weight:700;color:var(--accent-txt);margin-top:2px}
.pnl-recent-badge{flex-shrink:0;color:#fff;font-size:9px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;padding:4px 9px;border-radius:50px}
.pnl-activity{padding:6px 0;max-height:420px;overflow-y:auto}
.pnl-act-row{display:flex;gap:10px;padding:9px 16px;align-items:flex-start}
.pnl-act-row+.pnl-act-row{border-top:1px solid var(--border)}
.pnl-act-dot{width:7px;height:7px;border-radius:50%;background:var(--accent);margin-top:6px;flex-shrink:0}
.pnl-act-txt{font-size:13px;color:var(--dark);font-weight:600;line-height:1.4}
.pnl-act-time{font-size:11px;color:var(--muted);margin-top:2px}

/* ═══ Google Site Kit – vložený prehľad bez wp-adminu ═══════════════ */
.pnl-sitekit-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px;flex-wrap:wrap}
.pnl-sitekit-head h1{font-family:var(--serif);font-size:clamp(22px,3vw,30px);color:var(--dark);margin-bottom:5px}
.pnl-sitekit-head p{font-size:13px;color:var(--muted);line-height:1.6;max-width:720px}
.pnl-sitekit-note{background:#fffbeb;border:1px solid #fde68a;color:#854d0e;border-radius:10px;padding:12px 15px;margin-bottom:16px;font-size:13px;line-height:1.55}
.pnl-sitekit-frame{width:100%;height:calc(100vh - 165px);min-height:720px;background:#fff;border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--sh);display:block}
.pnl-sitekit-empty{max-width:720px;margin:40px auto;background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:36px;text-align:center;box-shadow:var(--sh)}
.pnl-sitekit-empty h2{font-family:var(--serif);font-size:22px;color:var(--dark);margin:14px 0 8px}
.pnl-sitekit-empty p{font-size:14px;color:var(--muted);line-height:1.7}

/* ═══ Karty ponúk – tlačidlá sa už neorezávajú ═══════════════ */
.prop-item-actions{display:flex;gap:6px;flex-wrap:wrap}
.prop-item-actions .btn{flex:0 0 auto}

@media(max-width:960px){
    .pnl-home-grid{grid-template-columns:1fr}
}
/* Hamburger namiesto natlačeného menu už od stredných obrazoviek */
@media(max-width:900px){
    .ph{position:relative}
    .ph-burger{display:flex}
    .ph-right{display:none}
    .ph-nav{
        position:absolute;top:calc(100% + 1px);left:0;right:0;
        flex-direction:column;gap:2px;background:var(--white);
        border-bottom:1px solid var(--border);box-shadow:0 12px 30px rgba(60,50,30,.14);
        padding:10px;display:none;z-index:200;max-height:80vh;overflow-y:auto
    }
    .ph-nav.open{display:flex}
    .ph-nav a{padding:13px 16px;font-size:14px;border-radius:10px;width:100%;text-transform:none;letter-spacing:0;justify-content:flex-start;display:flex;align-items:center;gap:8px}
    .ph-nav a.active{background:var(--accent);color:var(--dark)}
    .ph-nav-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:8px;padding:12px 8px 4px;border-top:1px solid var(--border)}
    .ph-nav-user{font-size:13px;color:var(--muted);font-weight:600}
    .ph-nav-foot .ph-logout{padding:9px 16px}
}
@media(max-width:768px){
    /* karty ponúk – celá karta vertikálne, fotka hore (viac miesta pre tlačidlá) */
    .prop-list{grid-template-columns:1fr !important}
    .prop-item{display:block !important}
    .prop-item-img{width:100% !important;height:170px !important;border-radius:var(--r) var(--r) 0 0 !important}
    .prop-item-actions .btn{flex:1 1 auto;justify-content:center;text-align:center}
    .pnl-stat-n{font-size:26px}
    /* široké tabuľky (odberatelia, história, kampane) sa posúvajú vodorovne, nevytláčajú stránku */
    .pc table{display:block;width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;white-space:nowrap}
    /* vnorené grid/flex bloky nech sa zalamujú */
    .pnl-nl-stats-grid,.ff-row,.ff-row-3,.pnl-rev-grid{grid-template-columns:1fr !important}
    .pnl-quick{gap:8px}
    .pnl-quick-btn{flex:1 1 auto;justify-content:center}
    /* dlhé názvy/e-maily nech sa zalamujú a nepretečú */
    .lead-contact a,.lead-msg,.pnl-recent-title,.prop-item-title{word-break:break-word;overflow-wrap:anywhere}
    .lead-contact{gap:8px 14px}
    #pnlBulkBar{top:60px}
}
@media(max-width:600px){
    .pc{padding:16px 12px}
    .pnl-hero{flex-direction:column;align-items:flex-start;gap:12px}
    .pnl-hero .btn{width:100%;text-align:center;justify-content:center}
}
@media(max-width:1080px){
    .prop-list.is-rows .prop-item-body{grid-template-columns:minmax(150px,1fr) 130px 150px}
    .prop-list.is-rows .prop-item-actions{grid-column:1/-1;justify-content:flex-start}
}
@media(max-width:768px){
    .gp-order{display:flex}
    .gp-thumb[draggable="true"]{cursor:default}
    .prop-list.is-rows .prop-item{display:grid!important;grid-template-columns:32px 92px minmax(0,1fr)}
    .prop-list.is-rows .prop-drag{width:32px;flex-basis:32px}
    .prop-list.is-rows .prop-mobile-order{display:flex;position:absolute;left:2px;top:50%;z-index:4;transform:translateY(-50%);flex-direction:column;gap:5px}
    .prop-mobile-order button{width:28px;height:34px;padding:0;border:1px solid var(--border);border-radius:7px;background:#fff;color:var(--dark);font:800 16px/1 var(--sans);box-shadow:0 2px 8px rgba(60,50,30,.08);touch-action:manipulation}
    .prop-list.is-rows .prop-drag{visibility:hidden}
    .prop-list.is-rows .prop-item-img{height:auto!important;min-height:100px;border-radius:0!important}
    .prop-list.is-rows .prop-item-body{display:flex;gap:5px;align-items:flex-start;padding:10px}
    .prop-list.is-rows .pnl-check{display:none!important}
    .prop-list.is-rows .prop-item-actions .btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center}
    .pnl-view-toggle{margin-left:0}
    .pnl-sitekit-frame{height:calc(100dvh - 190px);min-height:520px;border-radius:8px}
    .pnl-subscriber-add-form{grid-template-columns:1fr!important}
    .pnl-subscriber-add-form .btn{width:100%;min-height:46px}
    .pnl-subscriber-batch-form{grid-template-columns:1fr!important}
    .pnl-subscriber-batch-form .btn{width:100%;min-height:46px}
    .pnl-subscriber-filters{width:100%;display:grid!important;grid-template-columns:1fr 1fr}
    .pnl-subscriber-filters input,.pnl-subscriber-filters select,.pnl-subscriber-filters button{width:100%;min-height:44px;font-size:16px!important}
    table.pnl-subs-table{display:block!important;width:100%;white-space:normal!important;overflow:visible!important}
    .pnl-subs-table thead{display:none}
    .pnl-subs-table tbody,.pnl-subs-table tr,.pnl-subs-table td{display:block;width:100%}
    .pnl-subs-table tr{padding:10px 13px;border-bottom:1px solid var(--border)}
    .pnl-subs-table td{display:grid!important;grid-template-columns:92px minmax(0,1fr);gap:9px;align-items:start;padding:7px 0!important;border:0!important;overflow-wrap:anywhere}
    .pnl-subs-table td::before{content:attr(data-label);font-size:10px;font-weight:800;letter-spacing:.45px;text-transform:uppercase;color:var(--muted);padding-top:4px}
    .pnl-subs-table td.pnl-subs-actions{display:flex!important;flex-wrap:wrap;gap:6px;padding-left:101px!important}
    .pnl-subs-table td.pnl-subs-actions::before{display:none}
    .pnl-subs-table td form{max-width:100%}
    .pnl-subs-table td select{max-width:100%!important;min-height:40px;font-size:16px!important}
    .pnl-subs-table td button{min-height:40px}
}
@media(max-width:520px){
    .pnl-subscriber-filters{grid-template-columns:1fr}
}
</style>

<div>
<div class="ph">
    <a href="?action=home" class="ph-logo" style="text-decoration:none">
        <div class="ph-logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" style="color:var(--accent-txt)"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
        <div style="color:var(--dark)">Realitný Panel<small>Správa nehnuteľností</small></div>
    </a>
    <button class="ph-burger" id="phBurger" aria-label="Menu" aria-expanded="false" onclick="phToggleNav()"><span></span><span></span><span></span></button>
    <nav class="ph-nav" id="phNav">
        <?php $lead_cnt = (int) wp_count_posts('pp_lead')->publish; ?>
        <a href="?action=home" class="<?php echo $action==='home'?'active':'' ?>">Úvod</a>
        <a href="?action=sitekit" class="<?php echo $action==='sitekit'?'active':'' ?>">Google štatistiky</a>
        <a href="?action=list" class="<?php echo $action==='list'?'active':'' ?>">Ponuky</a>
        <a href="?action=add" class="<?php echo ($action==='add'||$action==='edit')?'active':'' ?>">+ Nová ponuka</a>
        <a href="?action=leads" class="<?php echo $action==='leads'?'active':'' ?>">Formuláre<?php if ($lead_cnt): ?> <span class="ph-badge"><?php echo $lead_cnt ?></span><?php endif; ?></a>
        <?php if (function_exists('zcn_table')): ?>
        <a href="?action=newsletter" class="<?php echo $action==='newsletter'?'active':'' ?>">Newsletter</a>
        <?php endif; ?>
        <?php if (function_exists('zcr_table')): ?>
        <a href="?action=reviews" class="<?php echo $action==='reviews'?'active':'' ?>">Recenzie</a>
        <?php endif; ?>
        <?php if (function_exists('panel_ebook') && (!function_exists('zc_ebook_can_manage') || zc_ebook_can_manage())): ?>
        <a href="?action=ebook" class="<?php echo $action==='ebook'?'active':'' ?>">Ebook</a>
        <?php endif; ?>
        <?php if (function_exists('panel_security')): ?>
        <a href="?action=zabezpecenie" class="<?php echo $action==='zabezpecenie'?'active':'' ?>">Zabezpečenie</a>
        <?php endif; ?>
        <a href="?action=import" class="<?php echo $action==='import'?'active':'' ?>">Import/Export</a>
        <?php if (current_user_can('manage_options')): ?>
        <a href="?action=settings" class="<?php echo $action==='settings'?'active':'' ?>">Nastavenia</a>
        <?php endif; ?>
        <div class="ph-nav-foot">
            <span class="ph-nav-user"><?php echo esc_html($user->display_name) ?></span>
            <button class="ph-logout" onclick="window.location='<?php echo esc_url($logout) ?>'">Odhlásiť</button>
        </div>
    </nav>
    <div class="ph-right">
        <span class="ph-user"><?php echo esc_html($user->display_name) ?></span>
        <button class="ph-logout" onclick="window.location='<?php echo esc_url($logout) ?>'">Odhlásiť</button>
    </div>
</div>

<div class="pc<?php echo $action==='home'?' pc--home':'' ?>">
    <?php if(isset($_GET['saved'])): $saved_pid = intval($_GET['pid'] ?? 0); ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:12px 16px;border-radius:var(--r-sm);margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <span>Nehnuteľnosť uložená!</span>
        <?php if ($saved_pid && function_exists('zcn_handle_property_blast')): ?>
        <button class="btn btn-primary" onclick="pnlBlast(<?php echo $saved_pid ?>, this)">Poslať odberateľom newslettera</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php
    if ($action==='add'||$action==='edit') panel_form($pid);
    elseif ($action==='sitekit') panel_sitekit();
    elseif ($action==='list') panel_list();
    elseif ($action==='leads') echo panel_leads();
    elseif ($action==='import') echo panel_import_export();
    elseif ($action==='settings' && current_user_can('manage_options')) echo panel_settings();
    elseif ($action==='ebook'    && function_exists('panel_ebook') && (!function_exists('zc_ebook_can_manage') || zc_ebook_can_manage())) echo panel_ebook();
    elseif ($action==='zabezpecenie' && function_exists('panel_security')) echo panel_security();
    elseif ($action==='newsletter' && function_exists('zcn_table')) panel_newsletter();
    elseif ($action==='reviews'    && function_exists('zcr_table'))  panel_reviews();
    else panel_home();
    ?>
</div>
<div class="toast" id="toast"></div>
</div>

<script>
function phToggleNav(){
    var n=document.getElementById('phNav'), b=document.getElementById('phBurger');
    var open=n.classList.toggle('open');
    b.classList.toggle('open',open);
    b.setAttribute('aria-expanded',open?'true':'false');
}
// Zavri mobilné menu po kliku mimo neho
document.addEventListener('click',function(e){
    var n=document.getElementById('phNav'), b=document.getElementById('phBurger');
    if(!n||!b||!n.classList.contains('open'))return;
    if(!n.contains(e.target)&&!b.contains(e.target)){n.classList.remove('open');b.classList.remove('open');b.setAttribute('aria-expanded','false');}
});
function showTab(name,btn){
    document.querySelectorAll('.pf-panel').forEach(function(p){p.classList.remove('active')});
    document.querySelectorAll('.pf-tab').forEach(function(t){t.classList.remove('active')});
    document.getElementById('pftab_'+name).classList.add('active');
    btn.classList.add('active');
    // TinyMCE si pri zobrazení skrytého panelu znovu prepočíta rozmery.
    if(window.tinymce){
        window.setTimeout(function(){
            ['zcpp_short','zcpp_content'].forEach(function(id){
                var editor=tinymce.get(id);
                if(!editor)return;
                var iframe=editor.iframeElement||document.getElementById(id+'_ifr');
                if(iframe&&iframe.offsetHeight<120)iframe.style.height='240px';
                try{editor.fire('ResizeEditor')}catch(ignore){}
            });
        },40);
    }
}
function ppEditorLineHeight(editorId,value,select){
    if(!value)return;
    var editor=window.tinymce&&tinymce.get(editorId)&&!tinymce.get(editorId).isHidden()?tinymce.get(editorId):null;
    if(editor){
        editor.focus();
        var node=editor.selection.getNode();
        while(node&&node!==editor.getBody()&&!/^(P|DIV|LI|H[1-6]|BLOCKQUOTE)$/.test(node.nodeName)){node=node.parentNode}
        if(!node||node===editor.getBody()){
            editor.execCommand('FormatBlock',false,'p');
            node=editor.selection.getNode();
            while(node&&node!==editor.getBody()&&!/^(P|DIV|LI|H[1-6]|BLOCKQUOTE)$/.test(node.nodeName)){node=node.parentNode}
        }
        if(node&&node!==editor.getBody()){
            editor.dom.setStyle(node,'line-height',value==='default'?'':value);
            editor.fire('change');
            editor.nodeChanged();
            toast(value==='default'?'Riadkovanie odseku je opäť podľa webu.':'Riadkovanie odseku nastavené na '+value+'.',true);
        }
    }else{
        var textarea=document.getElementById(editorId);
        if(!textarea)return;
        var start=textarea.selectionStart||0,end=textarea.selectionEnd||0;
        var selected=textarea.value.slice(start,end);
        if(value==='default'){
            toast('V textovom režime odstráňte štýl line-height priamo z HTML.',false);
        }else{
            var html='<p style="line-height:'+value+'">'+selected+'</p>';
            textarea.value=textarea.value.slice(0,start)+html+textarea.value.slice(end);
            textarea.focus();textarea.setSelectionRange(start+html.length-selected.length-4,start+html.length-4);
            toast('Riadkovanie bolo vložené do HTML.',true);
        }
    }
    if(select)select.value='';
}
function toast(msg,ok){
    var t=document.getElementById('toast');
    t.innerHTML=(ok?'':'')+msg;
    t.classList.add('show');
    setTimeout(function(){t.classList.remove('show')},3000);
}
// Titulná fotka ide cez celú šírku obrazovky – malá predloha bude rozmazaná
function pnlCheckSize(a){
    if (a && a.width && a.width < 1600) {
        toast('Pozor: fotka má len ' + a.width + ' px na šírku. Na celú obrazovku bude mäkká – ideál je aspoň 2000 px.');
    }
}
function pnlPropertyMediaLibrary(extra){
    var library={type:'image',orderby:'date',order:'DESC'};
    if(window.zcCurrentPropertyFolder){
        library.zc_folder=Number(window.zcCurrentPropertyFolder);
    }
    if(extra){
        Object.keys(extra).forEach(function(key){library[key]=extra[key]});
    }
    // Nové uploady pri úprave existujúcej ponuky dostanú správneho rodiča.
    // Server ich tak zaradí do priečinka ponuky už počas nahrávania.
    if(window.zcCurrentPropertyId&&window.wp&&wp.media&&wp.media.model&&wp.media.model.settings&&wp.media.model.settings.post){
        wp.media.model.settings.post.id=Number(window.zcCurrentPropertyId);
    }
    return library;
}
function selCover(){
    var f=wp.media({title:'Cover foto – priečinok aktuálnej ponuky',button:{text:'Nastav'},multiple:false,library:pnlPropertyMediaLibrary()});
    f.on('select',function(){
        var a=f.state().get('selection').first().toJSON();
        pnlCheckSize(a);
        var t=a.sizes.thumbnail||a.sizes.full;
        var old=document.querySelector('#cv-pre .gp-thumb');
        var sameGallery=document.querySelector('#gal-pre .gp-thumb[data-id="'+Number(a.id)+'"]');
        if(sameGallery)sameGallery.remove();
        if(old&&Number(old.dataset.id)!==Number(a.id))gpThumbToGallery(old);
        document.getElementById('cv').value=a.id;
        document.getElementById('cv-pre').innerHTML='<div class="gp-thumb" data-id="'+Number(a.id)+'" data-name="'+pnlEscAttr((a.filename||a.title||'').toLowerCase())+'" data-date="'+pnlEscAttr(a.date||a.dateFormatted||'')+'"><img src="'+t.url+'" alt=""><button type="button" class="gp-rm" onclick="rmCv()">✕</button></div>';
        gpSync();gpBind();
    });f.open();
}
function rmCv(){document.getElementById('cv').value='';document.getElementById('cv-pre').innerHTML='';gpBind()}
function selGal(){
    var f=wp.media({title:'Galéria – priečinok aktuálnej ponuky',button:{text:'Pridať do galérie'},multiple:true,library:pnlPropertyMediaLibrary()});
    f.on('select',function(){
        var g=JSON.parse(document.getElementById('gal').value||'[]');
        var p=document.getElementById('gal-pre');
        f.state().get('selection').forEach(function(a){
            a=a.toJSON();
            pnlCheckSize(a);
            if(!g.includes(a.id)&&Number(document.getElementById('cv').value)!==Number(a.id)){
                g.push(a.id);
                var t=a.sizes.thumbnail||a.sizes.full;
                var name=(a.filename||a.title||'').toLowerCase(),date=a.date||a.dateFormatted||'';
                p.insertAdjacentHTML('beforeend','<div class="gp-thumb" draggable="true" data-id="'+a.id+'" data-name="'+pnlEscAttr(name)+'" data-date="'+pnlEscAttr(date)+'"><img src="'+t.url+'" alt=""><button type="button" class="gp-rm" onclick="rmGal(this)" aria-label="Odobrať z galérie">✕</button><span class="gp-order"><button type="button" onclick="gpMove(this,-1)" aria-label="Posunúť fotku doľava">←</button><button type="button" onclick="gpMove(this,1)" aria-label="Posunúť fotku doprava">→</button></span></div>');
            }
        });
        document.getElementById('gal').value=JSON.stringify(g);
        gpBind();
    });f.open();
}
function pnlEscAttr(value){
    return String(value||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function gpSync(){
    var ids=Array.prototype.map.call(document.querySelectorAll('#gal-pre .gp-thumb'),function(el){return Number(el.dataset.id)});
    document.getElementById('gal').value=JSON.stringify(ids);
}
function gpMove(btn,direction){
    var item=btn.closest('.gp-thumb'),wrap=document.getElementById('gal-pre');
    if(!item||!wrap)return;
    if(direction<0&&item.previousElementSibling)wrap.insertBefore(item,item.previousElementSibling);
    if(direction>0&&item.nextElementSibling)wrap.insertBefore(item.nextElementSibling,item);
    gpSync();
}
var gpDragged=null;
function gpThumbToGallery(item,before){
    if(!item)return;
    item.innerHTML=item.querySelector('img').outerHTML+'<button type="button" class="gp-rm" onclick="rmGal(this)" aria-label="Odobrať z galérie">✕</button><span class="gp-order"><button type="button" onclick="gpMove(this,-1)" aria-label="Posunúť fotku doľava">←</button><button type="button" onclick="gpMove(this,1)" aria-label="Posunúť fotku doprava">→</button></span>';
    var wrap=document.getElementById('gal-pre');
    if(before)wrap.insertBefore(item,before);else wrap.appendChild(item);
}
function gpThumbToCover(item){
    if(!item||!item.dataset.id)return;
    var coverWrap=document.getElementById('cv-pre'),old=coverWrap.querySelector('.gp-thumb');
    if(old&&old!==item)gpThumbToGallery(old);
    item.innerHTML=item.querySelector('img').outerHTML+'<button type="button" class="gp-rm" onclick="rmCv()">✕</button>';
    coverWrap.appendChild(item);
    document.getElementById('cv').value=Number(item.dataset.id);
    gpSync();gpBind();
    toast('Cover fotografia bola zmenená. Pôvodný cover zostal v galérii.',true);
}
function gpClearDragState(){
    document.querySelectorAll('.gp-thumb').forEach(function(el){el.classList.remove('is-dragging','is-over')});
    document.querySelectorAll('#cv-pre,#gal-pre').forEach(function(el){el.classList.remove('is-drop-zone')});
}
function gpBind(){
    var mobile=window.matchMedia&&window.matchMedia('(max-width:768px)').matches;
    var coverWrap=document.getElementById('cv-pre'),galleryWrap=document.getElementById('gal-pre');
    if(!coverWrap||!galleryWrap)return;
    var cover=coverWrap.querySelector('.gp-thumb');
    if(cover){
        cover.setAttribute('draggable',mobile?'false':'true');
        cover.ondragstart=mobile?null:function(e){gpDragged=cover;cover.classList.add('is-dragging');e.dataTransfer.effectAllowed='move'};
        cover.ondragend=function(){gpClearDragState();gpDragged=null};
    }
    coverWrap.ondragover=mobile?null:function(e){
        if(!gpDragged||gpDragged.parentElement===coverWrap)return;e.preventDefault();coverWrap.classList.add('is-drop-zone');
    };
    coverWrap.ondragleave=function(){coverWrap.classList.remove('is-drop-zone')};
    coverWrap.ondrop=mobile?null:function(e){
        e.preventDefault();coverWrap.classList.remove('is-drop-zone');
        if(gpDragged&&gpDragged.parentElement===galleryWrap)gpThumbToCover(gpDragged);
        gpDragged=null;
    };
    galleryWrap.ondragover=mobile?null:function(e){
        if(!gpDragged||gpDragged.parentElement!==coverWrap)return;e.preventDefault();galleryWrap.classList.add('is-drop-zone');
    };
    galleryWrap.ondragleave=function(e){if(e.target===galleryWrap)galleryWrap.classList.remove('is-drop-zone')};
    galleryWrap.ondrop=mobile?null:function(e){
        if(e.target!==galleryWrap||!gpDragged||gpDragged.parentElement!==coverWrap)return;
        e.preventDefault();gpThumbToGallery(gpDragged);document.getElementById('cv').value='';gpSync();gpBind();
        toast('Cover fotografia bola presunutá do galérie.',true);gpDragged=null;
    };
    document.querySelectorAll('#gal-pre .gp-thumb').forEach(function(item){
        item.setAttribute('draggable',mobile?'false':'true');
        item.ondragstart=mobile?null:function(e){gpDragged=item;item.classList.add('is-dragging');e.dataTransfer.effectAllowed='move'};
        item.ondragover=function(e){if(!gpDragged||gpDragged===item)return;e.preventDefault();item.classList.add('is-over')};
        item.ondragleave=function(){item.classList.remove('is-over')};
        item.ondrop=function(e){
            e.preventDefault();item.classList.remove('is-over');
            if(!gpDragged||gpDragged===item)return;
            var wrap=document.getElementById('gal-pre'),box=item.getBoundingClientRect();
            if(gpDragged.parentElement===coverWrap){
                gpThumbToGallery(gpDragged,e.clientX<box.left+box.width/2?item:item.nextSibling);
                document.getElementById('cv').value='';
                toast('Cover fotografia bola presunutá do galérie.',true);
            }else{
                wrap.insertBefore(gpDragged,e.clientX<box.left+box.width/2?item:item.nextSibling);
            }
            gpSync();gpBind();
        };
        item.ondragend=function(){
            gpClearDragState();
            gpDragged=null;gpSync();
        };
    });
}
function gpSort(mode){
    if(!mode)return;
    var wrap=document.getElementById('gal-pre'),items=Array.prototype.slice.call(wrap.querySelectorAll('.gp-thumb'));
    items.sort(function(a,b){
        if(mode==='name')return (a.dataset.name||'').localeCompare(b.dataset.name||'','sk',{numeric:true});
        var av=Date.parse(a.dataset.date||'')||Number(a.dataset.id)||0;
        var bv=Date.parse(b.dataset.date||'')||Number(b.dataset.id)||0;
        return mode==='oldest'?av-bv:bv-av;
    });
    items.forEach(function(item){wrap.appendChild(item)});gpSync();gpBind();
    toast('Poradie fotografií bolo upravené. Uložením ponuky sa použije aj na webe.',true);
}
document.addEventListener('DOMContentLoaded',gpBind);
function rmGal(btn){
    btn.parentElement.remove();
    gpSync();
}
function delProp(id, nonce){
    if(confirm('Naozaj vymazať túto nehnuteľnosť?'))window.location='?action=delete&id='+id+'&_wpnonce='+nonce;
}
function pnlStatus(id, sel){
    var val=sel.value;
    var item=sel.closest('.prop-item');if(item)item.dataset.status=val||'active';
    sel.style.background = val==='predane'?'#f1f5f9':(val==='rezervovane'?'#fffbeb':'#f0fdf4');
    var data=new FormData();
    data.append('action','pp_quick_status');
    data.append('nonce','<?php echo wp_create_nonce('pp_quick_status') ?>');
    data.append('id',id); data.append('status',val);
    fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
    .then(function(r){return r.json()}).then(function(res){
        toast(res.success?'Stav ponuky uložený':(res.data&&res.data.message)||'Chyba',res.success);
    }).catch(function(){toast('Chyba pripojenia',false)});
}
<?php if (function_exists('zcn_handle_property_blast')): ?>
/* Výber príjemcov pred odoslaním ponuky – skupiny alebo konkrétni ľudia. */
function pnlBlast(id, btn){
    var box=document.getElementById('pnlBlastPick');
    if(!box){pnlBlastRun(id,btn,{});return;}
    box.dataset.pid=id;
    box.dataset.btn='';
    window._pnlBlastBtn=btn;
    box.querySelectorAll('input[type=checkbox]').forEach(function(c){c.checked=false});
    if(window.zcMsRefresh)window.zcMsRefresh(box);
    var m=box.querySelector('[data-mode]');if(m)m.value='groups';
    pnlBlastMode();
    box.style.display='flex';
    pnlBlastCount();
}
function pnlBlastClose(){var b=document.getElementById('pnlBlastPick');if(b)b.style.display='none';}
function pnlBlastMode(){
    var box=document.getElementById('pnlBlastPick');if(!box)return;
    var mode=box.querySelector('[data-mode]').value;
    box.querySelector('[data-pane=groups]').style.display=(mode==='groups')?'block':'none';
    box.querySelector('[data-pane=people]').style.display=(mode==='people')?'block':'none';
    pnlBlastCount();
}
function pnlBlastCount(){
    var box=document.getElementById('pnlBlastPick');if(!box)return;
    var mode=box.querySelector('[data-mode]').value;
    var out=box.querySelector('[data-count]');if(!out)return;
    if(mode==='all'){out.textContent='Pošle sa všetkým so záujmom o ponuky.';return;}
    var sel=box.querySelectorAll('[data-pane='+mode+'] input:checked').length;
    out.textContent=sel?('Vybrané: '+sel):'Zatiaľ nie je nič vybrané – vyber aspoň jednu položku.';
}
document.addEventListener('change',function(e){
    var box=document.getElementById('pnlBlastPick');
    if(box&&box.contains(e.target))pnlBlastCount();
});
function pnlBlastConfirm(){
    var box=document.getElementById('pnlBlastPick');if(!box)return;
    var mode=box.querySelector('[data-mode]').value;
    var extra={mode:mode};
    if(mode!=='all'){
        var vals=[].map.call(box.querySelectorAll('[data-pane='+mode+'] input:checked'),function(c){return c.value});
        if(!vals.length){alert('Vyber aspoň jednu položku.');return;}
        extra[mode==='groups'?'groups':'emails']=vals.join(',');
    }
    pnlBlastClose();
    pnlBlastRun(box.dataset.pid,window._pnlBlastBtn,extra);
}
function pnlBlastRun(id, btn, extra){
    btn=btn||{};
    var orig=btn.innerHTML;
    if(btn.disabled!==undefined){btn.disabled=true;btn.innerHTML='Odosielam…';}
    var data=new FormData();
    data.append('action','zcn_send_property');
    data.append('nonce','<?php echo wp_create_nonce('zcn_send_nonce') ?>');
    data.append('property_id',id);
    Object.keys(extra||{}).forEach(function(k){data.append(k,extra[k])});
    fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
    .then(function(r){return r.json()}).then(function(res){
        if(btn.disabled!==undefined){btn.disabled=false;btn.innerHTML=res.success?'✓':orig;}
        toast((res.data&&res.data.message)||(res.success?'Odoslané':'Chyba'),res.success);
    }).catch(function(){if(btn.disabled!==undefined){btn.disabled=false;btn.innerHTML=orig;}toast('Chyba pripojenia',false)});
}
<?php endif; ?>
</script>

<?php if (function_exists('zcn_handle_property_blast') && function_exists('zcn_table') && function_exists('zcn_interest_groups')):
    global $wpdb;
    $zc_subs = $wpdb->get_results("SELECT email,name,interest FROM " . zcn_table()
        . " WHERE status='active' ORDER BY name ASC, email ASC LIMIT 500");
?>
<div id="pnlBlastPick" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(20,17,14,.55);
     align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--white);border-radius:16px;max-width:620px;width:100%;max-height:88vh;
         display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.3)">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <strong style="font-size:15px">Komu poslať túto ponuku?</strong>
            <button type="button" onclick="pnlBlastClose()" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--muted)">✕</button>
        </div>
        <div style="padding:20px 22px;overflow:auto">
            <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Príjemcovia</label>
            <select data-mode onchange="pnlBlastMode()" style="width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;margin-bottom:16px">
                <option value="groups">Vybrané skupiny</option>
                <option value="people">Vybraní ľudia</option>
                <option value="all">Všetci so záujmom o ponuky</option>
            </select>

            <div data-pane="groups">
                <?php pp_nl_multiselect('blast_groups', '', ['empty' => 'Vyber kategórie']) ?>
                <p style="font-size:12px;color:var(--muted);line-height:1.6;margin:12px 0 0">
                    Ponuka odíde tým, čo majú niektorú z označených kategórií –
                    a tiež tým, ktorí chcú dostávať všetko.
                </p>
            </div>

            <div data-pane="people" style="display:none">
                <input type="search" placeholder="Hľadať meno alebo e-mail"
                    oninput="var q=this.value.toLowerCase();this.parentNode.querySelectorAll('label').forEach(function(l){l.style.display=l.textContent.toLowerCase().indexOf(q)>-1?'flex':'none'})"
                    style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;margin-bottom:10px;font-size:13.5px">
                <?php if ($zc_subs): foreach ($zc_subs as $sub): ?>
                <label style="display:flex;align-items:center;gap:9px;padding:7px 10px;border-bottom:1px solid var(--border);cursor:pointer;font-size:13px">
                    <input type="checkbox" value="<?php echo esc_attr($sub->email) ?>" onchange="pnlBlastCount()" style="accent-color:#B8A47A;width:16px;height:16px">
                    <span><?php echo esc_html($sub->name ?: $sub->email) ?>
                        <?php if ($sub->name): ?><span style="color:var(--muted)"> · <?php echo esc_html($sub->email) ?></span><?php endif; ?>
                    </span>
                </label>
                <?php endforeach; else: ?>
                <p style="color:var(--muted);font-size:13px">Zatiaľ nemáš žiadnych aktívnych odberateľov.</p>
                <?php endif; ?>
            </div>
        </div>
        <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <span data-count style="font-size:12.5px;color:var(--muted)"></span>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-ghost" onclick="pnlBlastClose()">Zrušiť</button>
                <button type="button" class="btn btn-primary" onclick="pnlBlastConfirm()">Odoslať ponuku</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
    return ob_get_clean();
}

// ── ÚVOD (dashboard) ───────────────────────────────────────────────────────
function panel_home() {
    $user  = wp_get_current_user();
    $props = get_posts(['post_type'=>'property','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']);
    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];

    $st_active=0;$st_rez=0;$st_sold=0;$views_total=0;
    foreach($props as $pp){
        $sp=get_post_meta($pp->ID,'_property_stav_predaja',true);
        if($sp==='predane')$st_sold++;elseif($sp==='rezervovane')$st_rez++;else $st_active++;
        $views_total+=(int)get_post_meta($pp->ID,'_property_views',true);
    }
    $lead_cnt = (int) wp_count_posts('pp_lead')->publish;
    $lead_new = 0;
    if ($lead_cnt) {
        $lq = new WP_Query(['post_type'=>'pp_lead','posts_per_page'=>-1,'fields'=>'ids','meta_query'=>[['key'=>'_lead_status','value'=>'novy']]]);
        $lead_new = $lq->found_posts; wp_reset_postdata();
    }
    $hour = (int) current_time('G');
    $greet = $hour < 10 ? 'Dobré ráno' : ($hour < 18 ? 'Dobrý deň' : 'Dobrý večer');
    $first = trim(preg_replace('/^(Mgr\.|Ing\.|Bc\.|JUDr\.|MUDr\.|PhDr\.)\s*/u','',$user->display_name));
    $first = explode(' ', $first)[0];
    ?>
    <div class="pnl-hero">
        <div>
            <h1 style="font-family:var(--serif);font-size:clamp(22px,3vw,30px);color:var(--dark)"><?php echo esc_html($greet) ?>, <?php echo esc_html($first ?: $user->display_name) ?></h1>
            <p style="font-size:13px;color:var(--muted);margin-top:4px"><?php echo esc_html(date_i18n('l j. F Y', current_time('timestamp'))) ?></p>
        </div>
        <a href="?action=add" class="btn btn-primary" style="padding:12px 24px">+ Nová ponuka</a>
    </div>

    <div class="pnl-stats">
        <?php
        $cards = [
            ['Aktívne ponuky', $st_active, '#16a34a', 'home', '?action=list'],
            ['Rezervované',     $st_rez,    '#C6902B', 'clock', '?action=list'],
            ['Predané',         $st_sold,   '#746A62', 'star', '?action=list'],
            ['Zobrazenia spolu',$views_total,'#7C5E33','chart', ''],
        ];
        if (function_exists('panel_leads')) $cards[] = ['Nové správy', $lead_new, '#4338CA', 'megaphone', '?action=leads'];
        foreach ($cards as [$l,$n,$c,$ic,$href]):
            $tag = $href ? 'a' : 'div'; ?>
        <<?php echo $tag ?> class="pnl-stat"<?php echo $href?' href="'.esc_attr($href).'"':'' ?>>
            <span class="pnl-stat-ic" style="color:<?php echo $c ?>"><?php echo pp_svg($ic,20) ?></span>
            <span class="pnl-stat-n" style="color:<?php echo $c ?>"><?php echo number_format($n,0,',',' ') ?></span>
            <span class="pnl-stat-l"><?php echo esc_html($l) ?></span>
        </<?php echo $tag ?>>
        <?php endforeach; ?>
    </div>

    <!-- Rýchle akcie -->
    <div class="pnl-quick">
        <a href="?action=add" class="pnl-quick-btn"><?php echo pp_svg('home',18) ?> Pridať ponuku</a>
        <a href="?action=list" class="pnl-quick-btn"><?php echo pp_svg('building',18) ?> Všetky ponuky</a>
        <a href="?action=leads" class="pnl-quick-btn"><?php echo pp_svg('megaphone',18) ?> Formuláre<?php if($lead_new):?> (<?php echo $lead_new ?>)<?php endif; ?></a>
        <?php if (function_exists('zcn_table')): ?><a href="?action=newsletter" class="pnl-quick-btn"><?php echo pp_svg('email',18) ?> Newsletter</a><?php endif; ?>
        <?php if (function_exists('zcr_table')): ?><a href="?action=reviews&new_review=1" class="pnl-quick-btn"><?php echo pp_svg('star',18) ?> Pridať recenziu</a><?php endif; ?>
        <a href="?action=sitekit" class="pnl-quick-btn"><?php echo pp_svg('chart',18) ?> Google štatistiky</a>
        <a href="?action=import" class="pnl-quick-btn"><?php echo pp_svg('chart',18) ?> Import/Export</a>
        <a href="<?php echo home_url('/') ?>" target="_blank" class="pnl-quick-btn"><?php echo pp_svg('pin',18) ?> Otvoriť web</a>
    </div>

    <div class="pnl-home-grid">
        <!-- Najnovšie ponuky -->
        <div>
            <div class="pnl-sec-hd">
                <h2>Najnovšie ponuky</h2>
                <a href="?action=list" class="pnl-sec-link">Všetky →</a>
            </div>
            <?php if (empty($props)): ?>
            <div class="pnl-card-flat" style="text-align:center;padding:36px 20px;color:var(--muted)">
                <div style="color:var(--accent);margin-bottom:10px"><?php echo pp_svg('home',36) ?></div>
                Zatiaľ žiadne ponuky.<br><a href="?action=add" class="btn btn-primary" style="margin-top:14px">Pridať prvú</a>
            </div>
            <?php else: ?>
            <div class="pnl-recent">
                <?php foreach (array_slice($props,0,5) as $p):
                    $cid = get_post_meta($p->ID,'_property_cover_id',true);
                    $cena= get_post_meta($p->ID,'_property_cena',true);
                    $typ = get_post_meta($p->ID,'_property_typ',true);
                    $sp  = get_post_meta($p->ID,'_property_stav_predaja',true);
                    if ($cena && strpos($cena,'€')===false) $cena.=' €';
                    $badge = $sp==='predane'?['Predané','#746A62']:($sp==='rezervovane'?['Rezervované','#C6902B']:[$typ_labels[$typ]??'Aktívna','#16a34a']);
                ?>
                <a href="?action=edit&id=<?php echo $p->ID ?>" class="pnl-recent-item">
                    <div class="pnl-recent-img"><?php echo $cid ? wp_get_attachment_image($cid,'thumbnail') : '<span class="pnl-recent-noimg">'.pp_svg('home',22).'</span>' ?></div>
                    <div class="pnl-recent-body">
                        <div class="pnl-recent-title"><?php echo esc_html($p->post_title) ?></div>
                        <div class="pnl-recent-price"><?php echo esc_html($cena ?: 'Cena dohodou') ?></div>
                    </div>
                    <span class="pnl-recent-badge" style="background:<?php echo $badge[1] ?>"><?php echo esc_html($badge[0]) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Aktivita -->
        <div>
            <div class="pnl-sec-hd"><h2>Posledná aktivita</h2></div>
            <?php $log = get_option('pp_activity_log', []); if (is_array($log) && $log): ?>
            <div class="pnl-card-flat pnl-activity">
                <?php foreach (array_slice($log,0,10) as $l): ?>
                <div class="pnl-act-row">
                    <span class="pnl-act-dot"></span>
                    <div style="min-width:0">
                        <div class="pnl-act-txt"><?php echo esc_html($l['action']) ?><?php if(!empty($l['title'])):?> <span style="color:var(--muted)">— <?php echo esc_html($l['title']) ?></span><?php endif; ?></div>
                        <div class="pnl-act-time"><?php echo esc_html(date('d.m.Y H:i', strtotime($l['time']))) ?> · <?php echo esc_html($l['user']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="pnl-card-flat" style="padding:28px 20px;text-align:center;color:var(--muted);font-size:13px">Zatiaľ žiadna aktivita.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
// ── GOOGLE SITE KIT ───────────────────────────────────────────────────────
function panel_sitekit() {
    $active = function_exists('pp_sitekit_available') ? pp_sitekit_available() : defined('GOOGLESITEKIT_VERSION');
    $url    = function_exists('pp_sitekit_dashboard_url')
        ? pp_sitekit_dashboard_url()
        : add_query_arg('zc_panel', '1', admin_url('admin.php?page=googlesitekit-dashboard'));
    ?>
    <div class="pnl-sitekit-head">
        <div>
            <h1>Google štatistiky</h1>
            <p>Návštevnosť, vyhľadávanie a správanie návštevníkov. Údaje sú iba na čítanie a pochádzajú priamo z Google Site Kitu.</p>
        </div>
        <?php if ($active): ?>
        <a class="btn btn-ghost" href="<?php echo esc_url($url) ?>" target="_blank" rel="noopener">Otvoriť samostatne ↗</a>
        <?php endif; ?>
    </div>

    <?php if (!$active): ?>
        <div class="pnl-sitekit-empty">
            <div style="color:var(--accent)"><?php echo pp_svg('chart', 40) ?></div>
            <h2>Google Site Kit nie je aktívny</h2>
            <p>Správca webu ho musí najprv nainštalovať, pripojiť ku Google účtu a zdieľať prehľad s rolou <strong>Realitný maklér</strong>.</p>
        </div>
    <?php else: ?>
        <?php if (!current_user_can('googlesitekit_view_dashboard')): ?>
        <div class="pnl-sitekit-note">
            Ak sa štatistiky nezobrazia, správca musí v Site Kite otvoriť zdieľanie a povoliť
            Analytics alebo Search Console pre rolu <strong>Realitný maklér</strong>.
        </div>
        <?php endif; ?>
        <iframe
            class="pnl-sitekit-frame"
            src="<?php echo esc_url($url) ?>"
            title="Google Site Kit – štatistiky webu"
            loading="eager"
        ></iframe>
    <?php endif; ?>
    <?php
}

function panel_list() {
    $props = get_posts(pp_property_order_args(['post_type'=>'property','posts_per_page'=>-1,'post_status'=>'any']));
    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];
    $cities = [];
    foreach ($props as $prop) {
        $city = trim((string) get_post_meta($prop->ID, '_property_mesto', true));
        if ($city) $cities[$city] = $city;
    }
    natcasesort($cities);
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:14px">
        <div>
            <h1 style="font-family:var(--serif);font-size:22px;color:var(--dark)">Nehnuteľnosti</h1>
            <p style="font-size:13px;color:var(--muted);margin-top:3px"><?php echo count($props) ?> ponúk celkovo</p>
        </div>
        <a href="?action=add" class="btn btn-primary" style="padding:10px 22px;font-size:12px">+ Nová ponuka</a>
    </div>
    <?php if (empty($props)): ?>
    <div class="empty">
        <div class="empty-icon" style="color:var(--accent)"><?php echo pp_svg('home',48) ?></div>
        <p style="font-size:16px;font-weight:600;margin-bottom:8px;color:var(--dark)">Žiadne ponuky</p>
        <p style="font-size:14px;margin-bottom:24px">Pridajte prvú nehnuteľnosť</p>
        <a href="?action=add" class="btn btn-primary" style="padding:12px 28px">Pridať ponuku</a>
    </div>
    <?php else: ?>
    <div class="pnl-list-tools">
        <label>Hľadať
            <input type="search" id="pnlPropSearch" placeholder="Názov alebo mesto">
        </label>
        <label>Stav
            <select id="pnlPropStatus">
                <option value="">Všetky</option>
                <option value="active">Aktívne</option>
                <option value="rezervovane">Rezervované</option>
                <option value="predane">Predané</option>
            </select>
        </label>
        <label>Typ
            <select id="pnlPropType">
                <option value="">Všetky</option>
                <?php foreach ($typ_labels as $value => $label): ?>
                <option value="<?php echo esc_attr($value) ?>"><?php echo esc_html($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($cities): ?>
        <label>Mesto
            <select id="pnlPropCity">
                <option value="">Všetky</option>
                <?php foreach ($cities as $city): ?><option value="<?php echo esc_attr($city) ?>"><?php echo esc_html($city) ?></option><?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <span id="pnlPropShown" style="font-size:12px;color:var(--muted);padding-bottom:10px"></span>
        <div class="pnl-view-toggle" aria-label="Zobrazenie ponúk">
            <button type="button" id="pnlCardsBtn" onclick="pnlSetView('cards')">Okná</button>
            <button type="button" id="pnlRowsBtn" onclick="pnlSetView('rows')">Riadky</button>
        </div>
    </div>
    <p id="pnlOrderHint" style="display:none;margin:-5px 0 14px;color:var(--muted);font-size:12px">Potiahnite riadok za bodky. Toto poradie sa použije aj v oknách a na verejných stránkach.</p>
    <!-- Hromadné akcie -->
    <div id="pnlBulkBar" style="display:none;align-items:center;gap:10px;flex-wrap:wrap;background:var(--dark);color:#fff;border-radius:var(--r);padding:12px 18px;margin-bottom:16px;position:sticky;top:74px;z-index:50">
        <strong id="pnlBulkCount" style="font-size:13px">0 označených</strong>
        <span style="opacity:.5">|</span>
        <button class="btn btn-ghost" style="background:rgba(255,255,255,.12);color:#fff;border-color:transparent" onclick="pnlBulk('')">Aktívne</button>
        <button class="btn btn-ghost" style="background:rgba(255,255,255,.12);color:#fff;border-color:transparent" onclick="pnlBulk('rezervovane')">Rezervované</button>
        <button class="btn btn-ghost" style="background:rgba(255,255,255,.12);color:#fff;border-color:transparent" onclick="pnlBulk('predane')">Predané</button>
        <button class="btn btn-danger" onclick="pnlBulk('delete')">Zmazať označené</button>
        <button class="btn btn-ghost" style="background:transparent;color:rgba(255,255,255,.7);border-color:transparent;margin-left:auto" onclick="pnlBulkClear()">Zrušiť výber</button>
    </div>
    <div class="prop-list" id="pnlPropList">
    <?php foreach ($props as $p):
        $typ  = get_post_meta($p->ID,'_property_typ',true);
        $cena = get_post_meta($p->ID,'_property_cena',true);
        $cid  = get_post_meta($p->ID,'_property_cover_id',true);
        if ($cena && strpos($cena,'€')===false) $cena .= ' €';
        ?>
        <?php
        $sp = get_post_meta($p->ID,'_property_stav_predaja',true);
        $city = get_post_meta($p->ID,'_property_mesto',true);
        ?>
        <div class="prop-item"
             data-id="<?php echo $p->ID ?>"
             data-title="<?php echo esc_attr(mb_strtolower($p->post_title . ' ' . $city)) ?>"
             data-type="<?php echo esc_attr($typ) ?>"
             data-city="<?php echo esc_attr($city) ?>"
             data-status="<?php echo esc_attr($sp ?: 'active') ?>"
             style="position:relative">
            <div class="prop-drag" title="Potiahnuť a zmeniť poradie" aria-label="Potiahnuť a zmeniť poradie" role="button">⋮⋮</div>
            <div class="prop-mobile-order" aria-label="Zmeniť poradie ponuky">
                <button type="button" onclick="pnlMoveItem(this,-1)" aria-label="Posunúť ponuku vyššie">↑</button>
                <button type="button" onclick="pnlMoveItem(this,1)" aria-label="Posunúť ponuku nižšie">↓</button>
            </div>
            <label class="pnl-check" style="position:absolute;top:8px;left:8px;z-index:3;background:rgba(255,255,255,.9);border-radius:5px;padding:2px;display:flex;cursor:pointer">
                <input type="checkbox" class="pnl-cb" value="<?php echo $p->ID ?>" onchange="pnlBulkUpd()" style="width:17px;height:17px;cursor:pointer;accent-color:var(--accent)">
            </label>
            <div class="prop-item-img">
                <?php if($cid) echo wp_get_attachment_image($cid,'medium'); else echo ''; ?>
                <?php if($typ): ?><span class="prop-item-badge"><?php echo $typ_labels[$typ]??$typ ?></span><?php endif; ?>
            </div>
            <div class="prop-item-body">
                <div class="prop-item-title"><?php echo esc_html($p->post_title) ?></div>
                <div class="prop-item-price"<?php if(!$cena) echo ' style="color:var(--muted);font-size:13px;font-weight:600"'; ?>><?php echo esc_html($cena ?: 'Cena dohodou') ?></div>
                <select class="pnl-status" onchange="pnlStatus(<?php echo $p->ID ?>,this)" title="Rýchlo zmeniť stav"
                    style="margin-bottom:8px;padding:6px 10px;border:1.5px solid var(--border);border-radius:6px;font-size:12px;font-family:var(--sans);background:<?php echo $sp==='predane'?'#f1f5f9':($sp==='rezervovane'?'#fffbeb':'#f0fdf4') ?>">
                    <option value="" <?php selected($sp,'') ?>>● Aktívna</option>
                    <option value="rezervovane" <?php selected($sp,'rezervovane') ?>>● Rezervované</option>
                    <option value="predane" <?php selected($sp,'predane') ?>>● Predané</option>
                </select>
                <div class="prop-item-actions">
                    <a href="?action=edit&id=<?php echo $p->ID ?>" class="btn btn-primary">Upraviť</a>
                    <a href="<?php echo get_permalink($p->ID) ?>" target="_blank" class="btn btn-ghost">Zobraziť</a>
                    <a href="?action=duplicate&id=<?php echo $p->ID ?>&_wpnonce=<?php echo wp_create_nonce('panel_dup_'.$p->ID) ?>" class="btn btn-ghost">Duplikovať</a>
                    <?php if (function_exists('zcn_handle_property_blast')):
                        $blast_sent = get_post_meta($p->ID, '_zcn_blast_sent', true); ?>
                    <button class="btn btn-success" onclick="pnlBlast(<?php echo $p->ID ?>, this)"
                        title="<?php echo $blast_sent ? 'Odoslané '.esc_attr(date('d.m.Y H:i', strtotime($blast_sent))).' – kliknutím pošleš znova' : 'Poslať ponuku odberateľom newslettera' ?>">
                        Newsletter<?php echo $blast_sent ? ' ✓' : '' ?>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-danger" onclick="delProp(<?php echo $p->ID ?>, '<?php echo wp_create_nonce('panel_delete_'.$p->ID) ?>')">Zmazať</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <script>
    var pnlBulkNonce='<?php echo wp_create_nonce('pp_bulk') ?>';
    var pnlOrderNonce='<?php echo wp_create_nonce('pp_reorder') ?>';
    var pnlList=document.getElementById('pnlPropList'),pnlDragged=null;
    function pnlFiltersActive(){
        return ['pnlPropSearch','pnlPropStatus','pnlPropType','pnlPropCity'].some(function(id){var el=document.getElementById(id);return el&&el.value});
    }
    function pnlSetView(view){
        view=view==='rows'?'rows':'cards';
        pnlList.classList.toggle('is-rows',view==='rows');
        document.getElementById('pnlRowsBtn').classList.toggle('active',view==='rows');
        document.getElementById('pnlCardsBtn').classList.toggle('active',view==='cards');
        document.getElementById('pnlOrderHint').style.display=view==='rows'?'block':'none';
        pnlList.querySelectorAll('.prop-item').forEach(function(item){
            item.draggable=false;
            var handle=item.querySelector('.prop-drag');
            if(handle)handle.draggable=view==='rows';
        });
        try{localStorage.setItem('pnl_property_view',view)}catch(e){}
    }
    function pnlApplyFilters(){
        var search=(document.getElementById('pnlPropSearch').value||'').toLocaleLowerCase();
        var status=document.getElementById('pnlPropStatus').value;
        var type=document.getElementById('pnlPropType').value;
        var city=document.getElementById('pnlPropCity')?document.getElementById('pnlPropCity').value:'';
        var shown=0;
        pnlList.querySelectorAll('.prop-item').forEach(function(item){
            var ok=(!search||item.dataset.title.indexOf(search)>-1)&&(!status||item.dataset.status===status)&&(!type||item.dataset.type===type)&&(!city||item.dataset.city===city);
            item.hidden=!ok;if(ok)shown++;
        });
        document.getElementById('pnlPropShown').textContent=shown+' zobrazených';
        var hint=document.getElementById('pnlOrderHint');
        if(hint&&pnlList.classList.contains('is-rows')) hint.textContent=pnlFiltersActive()?'Pre zmenu poradia najprv zrušte filtre.':'Potiahnite riadok za bodky. Toto poradie sa použije aj v oknách a na verejných stránkach.';
    }
    ['pnlPropSearch','pnlPropStatus','pnlPropType','pnlPropCity'].forEach(function(id){
        var el=document.getElementById(id);if(el)el.addEventListener(id==='pnlPropSearch'?'input':'change',pnlApplyFilters);
    });
    pnlList.addEventListener('dragstart',function(e){
        var handle=e.target.closest('.prop-drag');
        var item=handle?handle.closest('.prop-item'):null;
        if(!item||!pnlList.classList.contains('is-rows')||pnlFiltersActive()){e.preventDefault();return}
        pnlDragged=item;item.classList.add('is-dragging');e.dataTransfer.effectAllowed='move';
    });
    pnlList.addEventListener('dragover',function(e){
        if(!pnlDragged)return;e.preventDefault();
        var target=e.target.closest('.prop-item');if(!target||target===pnlDragged)return;
        var box=target.getBoundingClientRect();
        pnlList.insertBefore(pnlDragged,e.clientY<box.top+box.height/2?target:target.nextSibling);
    });
    function pnlSaveOrder(){
        var ids=Array.prototype.map.call(pnlList.querySelectorAll('.prop-item'),function(i){return i.dataset.id});
        var data=new FormData();data.append('action','pp_reorder');data.append('nonce',pnlOrderNonce);
        ids.forEach(function(id){data.append('ids[]',id)});
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(function(r){return r.json()}).then(function(res){toast(res.success?'Poradie uložené':'Poradie sa nepodarilo uložiť',res.success)});
    }
    function pnlMoveItem(btn,direction){
        if(pnlFiltersActive()){toast('Pre zmenu poradia najprv zrušte filtre.',false);return}
        var item=btn.closest('.prop-item');
        if(!item)return;
        if(direction<0&&item.previousElementSibling)pnlList.insertBefore(item,item.previousElementSibling);
        if(direction>0&&item.nextElementSibling)pnlList.insertBefore(item.nextElementSibling,item);
        pnlSaveOrder();
    }
    pnlList.addEventListener('dragend',function(){
        if(!pnlDragged)return;pnlDragged.classList.remove('is-dragging');pnlDragged=null;
        pnlSaveOrder();
    });
    try{pnlSetView(localStorage.getItem('pnl_property_view')||'cards')}catch(e){pnlSetView('cards')}
    pnlApplyFilters();
    function pnlChecked(){return Array.prototype.map.call(document.querySelectorAll('.pnl-cb:checked'),function(c){return c.value})}
    function pnlBulkUpd(){
        var n=pnlChecked().length, bar=document.getElementById('pnlBulkBar');
        if(bar){bar.style.display=n?'flex':'none';document.getElementById('pnlBulkCount').textContent=n+' označených';}
    }
    function pnlBulkClear(){document.querySelectorAll('.pnl-cb:checked').forEach(function(c){c.checked=false});pnlBulkUpd();}
    function pnlBulk(op){
        var ids=pnlChecked(); if(!ids.length)return;
        var msg = op==='delete' ? ('Naozaj zmazať '+ids.length+' ponúk? Nedá sa vrátiť.') : ('Zmeniť stav '+ids.length+' ponúk?');
        if(!confirm(msg))return;
        var data=new FormData();
        data.append('action','pp_bulk');data.append('nonce',pnlBulkNonce);data.append('op',op);
        ids.forEach(function(id){data.append('ids[]',id)});
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(function(r){return r.json()}).then(function(res){
            if(res.success){toast('Hotovo ('+res.data.done+')',true);setTimeout(function(){location.reload()},600);}
            else toast((res.data&&res.data.message)||'Chyba',false);
        }).catch(function(){toast('Chyba pripojenia',false)});
    }
    </script>
    <?php endif;
}

function panel_form($pid) {
    // SECURITY: nezobrazovať údaje iného typu obsahu ani bez oprávnenia
    if ($pid) {
        $chk = get_post($pid);
        if (!$chk || $chk->post_type !== 'property' || !current_user_can('edit_post', $pid)) {
            echo '<div style="padding:40px;text-align:center;color:#e74c3c">Táto ponuka neexistuje alebo k nej nemáš prístup. <a href="?action=list">← Späť na ponuky</a></div>';
            return;
        }
    }
    $f = function($k) use ($pid) { return $pid ? get_post_meta($pid,'_property_'.$k,true) : ''; };
    $post = $pid ? get_post($pid) : null;
    $ams_sel = $pid ? (get_post_meta($pid,'_property_amenities',true)?:[]) : [];
    $cover_id = $f('cover_id');
    $gallery_ids = $pid ? (get_post_meta($pid,'_property_gallery_ids',true)?:[]) : [];
    $all_am = get_property_amenities();
    ?>
    <div class="pf">
        <div class="pf-head">
            <h2><?php echo $pid?'Upraviť nehnuteľnosť':'Nová nehnuteľnosť' ?></h2>
            <a href="?action=list" class="btn btn-ghost">← Späť</a>
        </div>
        <div class="pf-tabs">
            <button class="pf-tab active" onclick="showTab('basic',this)">Základné</button>
            <button class="pf-tab" onclick="showTab('details',this)">Detaily</button>
            <button class="pf-tab" onclick="showTab('amenities',this)">Vybavenie</button>
            <button class="pf-tab" onclick="showTab('photos',this)">Fotky</button>
        </div>
        <form method="post">
        <?php wp_nonce_field('panel_save') ?>
        <div class="pf-body">

        <div id="pftab_basic" class="pf-panel active">
            <div class="ff"><label>Názov nehnuteľnosti *</label><input type="text" name="title" value="<?php echo esc_attr($post ? $post->post_title : '') ?>" required></div>
            <div class="ff-row">
                <div class="ff"><label>Typ ponuky</label>
                    <select name="typ">
                        <option value="">Vyber...</option>
                        <option value="predaj" <?php selected($f('typ'),'predaj') ?>>Na predaj</option>
                        <option value="prenajom" <?php selected($f('typ'),'prenajom') ?>>Na prenájom</option>
                        <option value="pozemok" <?php selected($f('typ'),'pozemok') ?>>Pozemok</option>
                    </select>
                </div>
                <div class="ff"><label>Cena (bez €, doplní sa auto)</label><input type="text" name="cena" value="<?php echo esc_attr(str_replace(' €','',$f('cena'))) ?>" placeholder="184 900"></div>
            </div>
            <div class="ff-row">
                <div class="ff"><label>Stav ponuky</label>
                    <select name="stav_predaja">
                        <option value="">Aktívna</option>
                        <option value="rezervovane" <?php selected($f('stav_predaja'),'rezervovane') ?>>Rezervované</option>
                        <option value="predane" <?php selected($f('stav_predaja'),'predane') ?>>Predané</option>
                    </select>
                </div>
                <div class="ff"><label>Pôvodná cena (pre „Znížená cena", voliteľné)</label><input type="text" name="cena_povodna" value="<?php echo esc_attr(str_replace(' €','',$f('cena_povodna'))) ?>" placeholder="199 000"></div>
            </div>
            <div class="ff-row-3">
                <div class="ff"><label>Lokalita / Ulica</label><input type="text" name="lokalita" value="<?php echo esc_attr($f('lokalita')) ?>" placeholder="Sokolská, Zvolen"></div>
                <div class="ff"><label>Mesto</label><input type="text" name="mesto" value="<?php echo esc_attr($f('mesto')) ?>" placeholder="Zvolen"></div>
                <div class="ff"><label>Okres</label><input type="text" name="okres" value="<?php echo esc_attr($f('okres')) ?>" placeholder="Zvolen"></div>
            </div>
            <div class="ff-row">
                <div class="ff"><label>Náklady na bývanie / mesiac (voliteľné)</label><input type="text" name="energie" value="<?php echo esc_attr($f('energie')) ?>" placeholder="180 €/mes."></div>
                <div class="ff"><label>Interná poznámka (nezobrazí sa návštevníkom)</label><input type="text" name="poznamka" value="<?php echo esc_attr($f('poznamka')) ?>" placeholder="napr. dohodnutá provízia, kontakt na majiteľa"></div>
            </div>
            <div class="ff">
                <label for="zcpp_short">Krátky popis</label>
                <small style="color:var(--muted);font-size:12px;line-height:1.5">Stručné predstavenie ponuky. Používa sa v SEO a newsletteri; odporúčaná dĺžka je približne 30–60 slov.</small>
                <?php wp_editor(
                    $f('popis_kratky'),
                    'zcpp_short',
                    pp_panel_editor_settings('popis_kratky', 6)
                ); ?>
            </div>
            <div class="ff">
            <label>Detailný popis</label>
            <small style="color:var(--muted);font-size:12px;line-height:1.5">Obnovený pôvodný editor. Nadpisy, odrážky, odkazy a zarovnanie fungujú rovnako ako predtým.</small>
            <div class="pp-editor-lineheight">
                <label for="pp-lineheight-detail">Riadkovanie odseku</label>
                <select id="pp-lineheight-detail" onchange="ppEditorLineHeight('zcpp_content',this.value,this)">
                    <option value="">Vyberte…</option>
                    <option value="1.4">Úzke – 1,4</option>
                    <option value="1.7">Bežné – 1,7</option>
                    <option value="2">Vzdušné – 2,0</option>
                    <option value="default">Podľa nastavenia webu</option>
                </select>
                <small>Kliknite do odseku a potom vyberte riadkovanie. Netreba označovať celý text.</small>
            </div>
            <?php wp_editor(
                $post ? $post->post_content : '',
                'zcpp_content',
                pp_panel_editor_settings('content', 10)
            ); ?>
            </div>
        </div>

        <div id="pftab_photos" class="pf-panel">
            <?php
            $property_folder = ($pid && function_exists('zc_folder_for_property'))
                ? zc_folder_for_property($pid, true)
                : 0;
            $property_folder_term = $property_folder && defined('ZC_FOLDER_TAX')
                ? get_term($property_folder, ZC_FOLDER_TAX)
                : null;
            ?>
            <div class="gp-manager">
                <div class="gp-manager-info">
                    <span aria-hidden="true"><?php echo pp_svg('camera', 22) ?></span>
                    <div>
                        <strong><?php echo $property_folder_term && !is_wp_error($property_folder_term)
                            ? 'Priečinok: ' . esc_html($property_folder_term->name)
                            : 'Priečinok sa vytvorí po prvom uložení'; ?></strong>
                        <small>Nahrané fotky sa zaradia automaticky. Odobratie z galérie súbor nevymaže z knižnice médií.</small>
                    </div>
                </div>
                <div class="gp-manager-actions">
                    <label for="gp-sort" class="screen-reader-text">Zoradiť galériu</label>
                    <select id="gp-sort" onchange="gpSort(this.value);this.value=''">
                        <option value="">Zoradiť galériu…</option>
                        <option value="newest">Najnovšie najprv</option>
                        <option value="oldest">Najstaršie najprv</option>
                        <option value="name">Názov A – Z</option>
                    </select>
                </div>
            </div>
            <div class="pf-sep">Cover foto</div>
            <div id="cv-pre" class="gp">
                <?php if($cover_id&&$img=wp_get_attachment_image_src($cover_id,'thumbnail')): ?>
                <?php
                $cover_attachment = get_post($cover_id);
                $cover_file = get_attached_file($cover_id);
                $cover_sort_name = $cover_file ? wp_basename($cover_file) : ($cover_attachment ? $cover_attachment->post_title : '');
                $cover_sort_date = $cover_attachment ? $cover_attachment->post_date_gmt : '';
                ?>
                <div class="gp-thumb" data-id="<?php echo (int) $cover_id ?>" data-name="<?php echo esc_attr(strtolower($cover_sort_name)) ?>" data-date="<?php echo esc_attr($cover_sort_date) ?>"><img src="<?php echo esc_url($img[0]) ?>" alt=""><button type="button" class="gp-rm" onclick="rmCv()">✕</button></div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-primary" onclick="selCover()" style="margin-bottom:24px">Vyber cover foto</button>
            <input type="hidden" name="cover_id" id="cv" value="<?php echo esc_attr($cover_id) ?>">

            <div class="pf-sep">Galéria fotos</div>
            <div id="gal-pre" class="gp">
                <?php foreach($gallery_ids as $gid): if($img=wp_get_attachment_image_src($gid,'thumbnail')):
                    $attachment = get_post($gid);
                    $file_name  = get_attached_file($gid);
                    $sort_name  = $file_name ? wp_basename($file_name) : ($attachment ? $attachment->post_title : '');
                    $sort_date  = $attachment ? $attachment->post_date_gmt : '';
                ?>
                <div class="gp-thumb" draggable="true" data-id="<?php echo (int) $gid ?>" data-name="<?php echo esc_attr(strtolower($sort_name)) ?>" data-date="<?php echo esc_attr($sort_date) ?>"><img src="<?php echo esc_url($img[0]) ?>" alt=""><button type="button" class="gp-rm" onclick="rmGal(this)" aria-label="Odobrať z galérie">✕</button><span class="gp-order"><button type="button" onclick="gpMove(this,-1)" aria-label="Posunúť fotku doľava">←</button><button type="button" onclick="gpMove(this,1)" aria-label="Posunúť fotku doprava">→</button></span></div>
                <?php endif; endforeach; ?>
            </div>
            <button type="button" class="btn btn-success" onclick="selGal()" style="margin-bottom:8px">Pridať alebo nahrať fotky</button>
            <p style="margin:0 0 24px;color:var(--muted);font-size:11.5px">Na počítači fotografie presúvajte potiahnutím – aj medzi coverom a galériou. Na telefóne použite šípky. Uložené poradie sa použije v galérii ponuky.</p>
            <input type="hidden" name="gallery_ids" id="gal" value="<?php echo esc_attr(json_encode($gallery_ids)) ?>">

            <div class="pf-sep">Video</div>
            <div class="ff"><label>YouTube / Vimeo URL</label><input type="text" name="video_url" value="<?php echo esc_attr($f('video_url')) ?>" placeholder="https://www.youtube.com/watch?v=..."></div>
        </div>

        <div id="pftab_details" class="pf-panel">
            <div class="ff-row-3">
                <?php foreach(['plocha'=>'Plocha (m²)','pozemok'=>'Pozemok (m²)','spalne'=>'Počet izieb','kupelne'=>'Kúpeľne','wc'=>'WC','poschodie'=>'Poschodie','rocnik'=>'Ročník'] as $k=>$l): ?>
                <div class="ff"><label><?php echo $l ?></label><input type="text" name="<?php echo $k ?>" value="<?php echo esc_attr($f($k)) ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="ff-row">
                <div class="ff"><label>Stav</label>
                    <select name="stav">
                        <option value="">Vyber</option>
                        <?php foreach(['Novostavba','Veľmi dobrý','Dobrý','Vyhovujúci','Rekonštrukcia potrebná'] as $s): ?>
                        <option <?php selected($f('stav'),$s) ?>><?php echo $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ff"><label>Vlastníctvo</label>
                    <select name="vlastnictvo">
                        <option value="">Vyber</option>
                        <?php foreach(['Osobné','Družstevné','Štátne'] as $v): ?>
                        <option <?php selected($f('vlastnictvo'),$v) ?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pf-sep">Kontakt – doplnok vedľa formulára</div>
            <div class="ff">
                <label>Shortcode alebo vlastný text</label>
                <textarea name="cta_extra" rows="4" placeholder="[property_carousel limit=&quot;3&quot;]" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:Menlo,Consolas,monospace;font-size:13px;resize:vertical"><?php echo esc_textarea($f('cta_extra')) ?></textarea>
                <small style="color:var(--muted);font-size:12px;line-height:1.6;display:block;margin-top:6px">
                    <?php echo esc_html(pp_cta_extra_hint()) ?> Na mobile sa doplnok zobrazí pod formulárom.
                </small>
            </div>
        </div>

        <div id="pftab_amenities" class="pf-panel" data-amtab>
            <?php foreach ($all_am as $catkey => $cat): ?>
            <div class="am-cat" data-amcat="<?php echo esc_attr($catkey) ?>">
                <div class="am-cat-hd"><?php echo pp_svg($cat['icon'] ?? '', 15) ?><?php echo esc_html($cat['label']) ?></div>
                <div class="am-items" id="amItems-<?php echo esc_attr($catkey) ?>">
                    <?php foreach ($cat['items'] as $k => $lbl): $is_custom = strpos($k,'custom_')===0; ?>
                    <label class="am-item" <?php echo $is_custom ? 'data-key="'.esc_attr($k).'"' : '' ?>>
                        <input type="checkbox" name="amenities[]" value="<?php echo esc_attr($k) ?>" <?php checked(in_array($k,$ams_sel)) ?>>
                        <?php echo esc_html($lbl) ?>
                        <?php if ($is_custom): ?>
                        <button type="button" class="pp-am-del" onclick="ppDelAm('<?php echo esc_js($k) ?>',this)" title="Zmazať položku"
                            style="margin-left:auto;width:20px;height:20px;border:none;border-radius:50%;background:#fef2f2;color:#dc2626;font-size:11px;cursor:pointer;line-height:1;flex-shrink:0">✕</button>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Správa vlastného vybavenia -->
            <div class="am-cat" id="ppAddAmBox" style="background:var(--section);border-radius:var(--r-sm);padding:16px">
                <div class="am-cat-hd" style="background:var(--white)">Pridať vlastné vybavenie</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px">
                    <input type="text" id="ppNewAm" placeholder="Napr. Vínna pivnica" maxlength="60"
                        style="flex:2;min-width:180px;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();ppAddAm();}">
                    <select id="ppNewAmCat" title="Do ktorej kategórie" style="flex:1;min-width:150px;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px;background:#fff">
                        <?php foreach (pp_amenity_category_options() as $ck => $cl): ?>
                        <option value="<?php echo esc_attr($ck) ?>"><?php echo esc_html($cl) ?></option>
                        <?php endforeach; ?>
                        <option value="">Iné</option>
                    </select>
                    <button type="button" class="btn btn-primary" onclick="ppAddAm()">+ Pridať</button>
                </div>
                <p style="font-size:11px;color:var(--muted);margin:8px 0 0">Vyber kategóriu, do ktorej sa položka zaradí (alebo „Iné"). Po pridaní sa objaví v danej kategórii vyššie. Uloží sa natrvalo pre všetky nehnuteľnosti.</p>
            </div>

            <script>
            var ppAmNonce = '<?php echo wp_create_nonce('pp_amenity') ?>';
            function ppAddAm(){
                var inp = document.getElementById('ppNewAm');
                var sel = document.getElementById('ppNewAmCat');
                var label = inp.value.trim();
                if (!label) { toast('Zadaj názov položky', false); return; }
                var cat = sel ? sel.value : '';
                var data = new FormData();
                data.append('action','pp_amenity_add');
                data.append('nonce', ppAmNonce);
                data.append('label', label);
                data.append('cat', cat);
                fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
                .then(function(r){return r.json()}).then(function(res){
                    if (!res.success) { toast((res.data&&res.data.message)||'Chyba', false); return; }
                    var target = res.data.cat || 'ine';
                    var wrap = document.getElementById('amItems-' + target);
                    if (!wrap) {
                        // kategória „Iné" ešte neexistuje – vytvor ju
                        var block = document.createElement('div');
                        block.className = 'am-cat'; block.setAttribute('data-amcat','ine');
                        block.innerHTML = '<div class="am-cat-hd">Iné</div><div class="am-items" id="amItems-ine"></div>';
                        var tab = document.querySelector('[data-amtab]');
                        var addBox = document.getElementById('ppAddAmBox');
                        tab.insertBefore(block, addBox);
                        wrap = block.querySelector('#amItems-ine');
                    }
                    var l = document.createElement('label');
                    l.className = 'am-item';
                    l.dataset.key = res.data.key;
                    l.innerHTML = '<input type="checkbox" name="amenities[]" value="' + res.data.key + '" checked> ' +
                        res.data.label.replace(/</g,'&lt;') +
                        '<button type="button" class="pp-am-del" onclick="ppDelAm(\'' + res.data.key + '\',this)" title="Zmazať položku" style="margin-left:auto;width:20px;height:20px;border:none;border-radius:50%;background:#fef2f2;color:#dc2626;font-size:11px;cursor:pointer;line-height:1;flex-shrink:0">✕</button>';
                    wrap.appendChild(l);
                    inp.value = '';
                    toast('Položka pridaná do kategórie „' + (sel && sel.selectedIndex>-1 ? sel.options[sel.selectedIndex].text : 'Iné') + '"', true);
                });
            }
            function ppDelAm(key, btn){
                if (!confirm('Natrvalo zmazať túto položku vybavenia? Zmizne zo zoznamu pre všetky ponuky.')) return;
                var data = new FormData();
                data.append('action','pp_amenity_del');
                data.append('nonce', ppAmNonce);
                data.append('key', key);
                fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
                .then(function(r){return r.json()}).then(function(res){
                    if (!res.success) { toast((res.data&&res.data.message)||'Chyba', false); return; }
                    var item = btn.closest('.am-item');
                    if (item) item.remove();
                    toast('Položka zmazaná', true);
                });
            }
            </script>
        </div>

        </div>
        <div class="pf-footer">
            <a href="?action=list" class="btn btn-ghost">Zrušiť</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:13px"><?php echo $pid?'Uložiť zmeny':'Vytvoriť ponuku' ?></button>
        </div>
        </form>
    </div>
    <?php
}

// SAVE
add_action('template_redirect', function() {
    if (!pp_is_panel_page()||!is_user_logged_in()) return;
    // SECURITY: capability check for ALL panel write operations
    if (!current_user_can('edit_posts')) return;

    if (($_GET['action']??'')==='delete'&&isset($_GET['id'])) {
        // SECURITY: nonce required for delete + only property post type + must own or be editor
        if (!wp_verify_nonce($_GET['_wpnonce']??'', 'panel_delete_'.intval($_GET['id']))) {
            wp_die('Neplatný bezpečnostný token.');
        }
        $del_id = intval($_GET['id']);
        $post   = get_post($del_id);
        if (!$post || $post->post_type !== 'property') {
            wp_die('Neplatná požiadavka.');
        }
        if (!current_user_can('delete_post', $del_id)) {
            wp_die('Nemáte oprávnenie vymazať túto ponuku.');
        }
        if (function_exists('pp_log')) pp_log('Zmazaná ponuka', $del_id);
        wp_delete_post($del_id, true);
        wp_redirect(pp_panel_url('action=list'));exit;
    }

    // Duplikovať ponuku
    if (($_GET['action']??'')==='duplicate' && isset($_GET['id'])) {
        if (!wp_verify_nonce($_GET['_wpnonce']??'', 'panel_dup_'.intval($_GET['id']))) wp_die('Neplatný token.');
        $src = get_post(intval($_GET['id']));
        if (!$src || $src->post_type !== 'property') wp_die('Neplatná požiadavka.');
        if (!current_user_can('edit_post', $src->ID)) wp_die('Nemáte oprávnenie duplikovať túto ponuku.');
        $new_id = wp_insert_post([
            'post_title'  => $src->post_title . ' (kópia)',
            'post_content'=> $src->post_content,
            'post_type'   => 'property',
            'post_status' => 'draft',
        ]);
        if ($new_id && !is_wp_error($new_id)) {
            foreach (get_post_meta(intval($_GET['id'])) as $k => $v) {
                if (strpos($k, '_property_') === 0) update_post_meta($new_id, $k, maybe_unserialize($v[0]));
            }
            update_post_meta($new_id, '_property_stav_predaja', '');
            update_post_meta($new_id, '_property_views', 0);
            update_post_meta($new_id, '_property_sort_order', pp_next_property_order());
        }
        if (function_exists('pp_log')) pp_log('Duplikovaná ponuka', $new_id);
        wp_redirect(pp_panel_url('action=edit&id='.$new_id));exit;
    }

    if (!isset($_POST['title'])||!wp_verify_nonce($_POST['_wpnonce']??'','panel_save')) return;
    $pid = intval($_GET['id']??0);
    // SECURITY: pri úprave musí ísť skutočne o ponuku (nie inú stránku/príspevok)
    // a používateľ ju musí smieť upravovať. Bez toho by sa dala cez ?id=<čokoľvek>
    // prepísať ľubovoľná stránka webu a premeniť na nehnuteľnosť.
    if ($pid) {
        $existing = get_post($pid);
        if (!$existing || $existing->post_type !== 'property') {
            wp_die('Neplatná požiadavka.');
        }
        if (!current_user_can('edit_post', $pid)) {
            wp_die('Nemáte oprávnenie upraviť túto ponuku.');
        }
    }
    $is_new = !$pid;
    $data = ['post_title'=>sanitize_text_field($_POST['title']),'post_content'=>wp_kses_post(wp_unslash($_POST['content']??'')),'post_type'=>'property','post_status'=>'publish'];
    if ($pid){$data['ID']=$pid;wp_update_post($data);}else{$pid=wp_insert_post($data);}
    if ($is_new && $pid && !is_wp_error($pid) && get_post_meta($pid, '_property_sort_order', true) === '') {
        update_post_meta($pid, '_property_sort_order', pp_next_property_order());
    }
    if (function_exists('pp_log')) pp_log($is_new ? 'Vytvorená ponuka' : 'Upravená ponuka', $pid);
    foreach(['typ','cena','cena_povodna','lokalita','mesto','okres','plocha','pozemok','spalne','kupelne','wc','poschodie','rocnik','stav','vlastnictvo','stav_predaja','energie','poznamka'] as $f) {
        if (isset($_POST[$f])) {
            $val = sanitize_text_field($_POST[$f]);
            if (($f==='cena'||$f==='cena_povodna') && $val && strpos($val,'€')===false) $val = $val.' €';
            update_post_meta($pid,'_property_'.$f,$val);
        }
    }
    if (isset($_POST['popis_kratky'])) {
        update_post_meta($pid, '_property_popis_kratky', wp_kses_post(wp_unslash($_POST['popis_kratky'])));
    }
    update_post_meta($pid,'_property_cover_id',intval($_POST['cover_id']??0));
    $gids=json_decode(sanitize_text_field($_POST['gallery_ids']??'[]'),true);
    update_post_meta($pid,'_property_gallery_ids',is_array($gids)?array_map('intval',$gids):[]);
    update_post_meta($pid,'_property_video_url',esc_url_raw($_POST['video_url']??''));
    if (isset($_POST['cta_extra'])) {
        $cta_extra = pp_cta_extra_sanitize(wp_unslash($_POST['cta_extra']));
        if ($cta_extra === '') delete_post_meta($pid,'_property_cta_extra');
        else                   update_post_meta($pid,'_property_cta_extra',$cta_extra);
    }
    $ams=isset($_POST['amenities'])?array_map('sanitize_text_field',$_POST['amenities']):[];
    update_post_meta($pid,'_property_amenities',$ams);
    // Fotky ponuky zaradíme do jej priečinka v Médiách (meta sú už uložené)
    if (function_exists('zc_folder_sync_property')) zc_folder_sync_property($pid);
    wp_redirect(pp_panel_url('action=list&saved=1&pid='.$pid));exit;
});

// ── Newsletter Panel ───────────────────────────────────────────────────────
/**
 * Načítanie riadkov „Meno;Priezvisko;email" rieši plugin ZC Newsletter,
 * aby sa panel a wp-admin nesprávali rozdielne. Tu ostáva len obal.
 */
function pp_parse_subscriber_batch($raw, $limit = 500) {
    if (function_exists('zcn_parse_contacts')) return zcn_parse_contacts($raw, $limit);
    return ['contacts' => [], 'invalid_rows' => [], 'duplicate_rows' => 0, 'truncated' => false];
}

function panel_newsletter() {
    if (!function_exists('zcn_table')) {
        echo '<div style="padding:40px;text-align:center;color:#e74c3c">Plugin ZC Newsletter nie je nainštalovaný.</div>';
        return;
    }
    // Kategórie odberateľov pribudli v novšej verzii pluginu. Bez nej by
    // panel spadol na neznámej funkcii, preto to radšej povieme zrozumiteľne.
    if (!pp_nl_has_interests()) {
        echo '<div style="padding:36px;text-align:center;color:#b45309;line-height:1.7">'
           . '<strong>Plugin ZC Newsletter je v staršej verzii.</strong><br>'
           . 'Nahraj jeho aktuálny balík (Pluginy → Pridať nový → Nahrať plugin) a Newsletter tu bude fungovať v plnom rozsahu.'
           . '</div>';
        return;
    }
    global $wpdb;
    $table = zcn_table();
    // Poistka: stĺpec „interest" musí v tabuľke naozaj byť
    if (function_exists('zcn_ensure_table_ready')) zcn_ensure_table_ready();
    $subtab = sanitize_text_field($_GET['sub'] ?? 'send');
    $subscriber_notice = '';
    $subscriber_notice_ok = false;

    // Handle delete/unsub from panel
    if (isset($_POST['pnl_nadd']) && wp_verify_nonce($_POST['_pnlnonce'] ?? '', 'pnl_nl')) {
        $add_email = strtolower(sanitize_email($_POST['email'] ?? ''));
        $add_name = sanitize_text_field($_POST['name'] ?? '');
        $add_interest = pp_nl_interests_value($_POST['interest'] ?? '');
        $add_mode = sanitize_key($_POST['add_mode'] ?? 'active');
        if (function_exists('zcn_add_contact')) {
            [$subscriber_notice, $subscriber_notice_ok] =
                zcn_add_contact($add_email, $add_name, $add_interest, $add_mode, 'manual_panel');
        } else {
            $subscriber_notice = 'Plugin ZC Newsletter neponúka pridanie kontaktu – aktualizuj ho na najnovšiu verziu.';
        }
    }
    if (isset($_POST['pnl_nbatch']) && wp_verify_nonce($_POST['_pnlnonce'] ?? '', 'pnl_nl')) {
        $batch = pp_parse_subscriber_batch(wp_unslash($_POST['batch_contacts'] ?? ''));
        $batch_interest = pp_nl_interests_value($_POST['batch_interest'] ?? '');
        $batch_mode = sanitize_key($_POST['batch_mode'] ?? 'active');
        $counts = ['created' => 0, 'reactivated' => 0, 'updated' => 0, 'failed' => 0];

        if (!$batch['contacts']) {
            $subscriber_notice = 'Nenašiel sa žiadny platný kontakt. Použite jeden riadok na osobu: Meno;Priezvisko;email.';
        } elseif (!function_exists('zcn_upsert_manual_active')) {
            $subscriber_notice = 'Aktualizujte aj plugin ZC Newsletter – hromadné pridanie potrebuje jeho novú verziu.';
        } else {
            foreach ($batch['contacts'] as $contact) {
                $result = ($batch_mode === 'pending' && function_exists('zcn_subscribe_direct'))
                    ? (zcn_subscribe_direct($contact['email'], $contact['name'], 'manual_batch', $batch_interest) ? 'created' : new WP_Error('zcn', 'zlyhalo'))
                    : zcn_upsert_manual_active($contact['email'], $contact['name'], 'manual_batch', $batch_interest);
                if (is_wp_error($result)) {
                    $counts['failed']++;
                } elseif (isset($counts[$result])) {
                    $counts[$result]++;
                }
            }

            $subscriber_notice = sprintf(
                'Hromadné pridanie dokončené: %d nových, %d znovu aktivovaných, %d existujúcich aktualizovaných.',
                $counts['created'],
                $counts['reactivated'],
                $counts['updated']
            );
            if ($batch['duplicate_rows']) {
                $subscriber_notice .= ' Duplicity v zozname preskočené: ' . (int) $batch['duplicate_rows'] . '.';
            }
            if ($batch['invalid_rows']) {
                $subscriber_notice .= ' Neplatné riadky: ' . implode(', ', array_slice($batch['invalid_rows'], 0, 12)) . '.';
            }
            if ($counts['failed']) {
                $subscriber_notice .= ' Neuložené pre chybu databázy: ' . (int) $counts['failed'] . '.';
            }
            if ($batch['truncated']) {
                $subscriber_notice .= ' Naraz sa spracuje najviac 500 riadkov; zvyšok vložte v ďalšej dávke.';
            }
            $subscriber_notice .= ($batch_mode === 'pending')
                ? ' Každému odišiel potvrdzovací e-mail.'
                : ' Potvrdzovacie ani uvítacie e-maily sa neposielali.';
            $subscriber_notice_ok = ($counts['created'] + $counts['reactivated'] + $counts['updated']) > 0
                && $counts['failed'] === 0;
        }
    }
    if (isset($_POST['pnl_nsub']) && wp_verify_nonce($_POST['_pnlnonce'],'pnl_nl')) {
        $wpdb->update($table, ['status'=>'unsubscribed'], ['id'=>intval($_POST['pnl_nsub'])]);
        $subscriber_notice = 'Kontakt bol odhlásený z newslettera.';
        $subscriber_notice_ok = true;
    }
    if (isset($_POST['pnl_ndel']) && wp_verify_nonce($_POST['_pnlnonce'],'pnl_nl')) {
        $wpdb->delete($table, ['id'=>intval($_POST['pnl_ndel'])]);
    }
    if (isset($_POST['pnl_ninterest']) && wp_verify_nonce($_POST['_pnlnonce'],'pnl_nl')) {
        $wpdb->update(
            $table,
            ['interest' => pp_nl_interests_value($_POST['interest'] ?? '')],
            ['id' => intval($_POST['pnl_ninterest'])]
        );
        $subscriber_notice = 'Kategória kontaktu bola uložená.';
        $subscriber_notice_ok = true;
    }
    if (isset($_POST['pnl_nconfirm']) && wp_verify_nonce($_POST['_pnlnonce'] ?? '', 'pnl_nl')) {
        if (function_exists('zcn_resend_confirmation')) {
            $result = zcn_resend_confirmation(intval($_POST['pnl_nconfirm']));
            if (is_wp_error($result)) {
                $subscriber_notice = $result->get_error_message();
            } else {
                $subscriber_notice = 'Bol odoslaný nový potvrdzovací e-mail.';
                $subscriber_notice_ok = true;
            }
        } else {
            $subscriber_notice = 'Aktualizujte aj plugin ZC Newsletter – bezpečné opätovné odoslanie potrebuje jeho novú verziu.';
        }
    }
    // Uloženie šablóny „Nová ponuka"
    $blast_saved = false;
    if (isset($_POST['pnl_blast_save']) && wp_verify_nonce($_POST['_pnlnonce'] ?? '', 'pnl_nl')) {
        update_option('zcn_blast_tpl', [
            'subject_prefix' => sanitize_text_field(wp_unslash($_POST['blast_subject'] ?? 'Nová ponuka: ')),
            'intro'          => sanitize_textarea_field(wp_unslash($_POST['blast_intro'] ?? '')),
            'outro'          => sanitize_textarea_field(wp_unslash($_POST['blast_outro'] ?? '')),
            'show_contact'   => empty($_POST['blast_contact']) ? 0 : 1,
        ]);
        $blast_saved = true;
    }

    $stats = [
        'active'  => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active'"),
        'pending' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='pending'"),
        'unsubscribed' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='unsubscribed'"),
        'month'   => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active' AND subscribed_at>=DATE_FORMAT(NOW(),'%Y-%m-01')"),
    ];
    $interest_counts = [];
    foreach (pp_nl_interests() as $value => $label) {
        $interest_counts[$value] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status='active' AND FIND_IN_SET(%s, interest)",
            $value
        ));
    }
    ?>

    <!-- Stats -->
    <div class="pnl-nl-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
        <?php foreach([
            ['Aktívni odberatelia', $stats['active'],  '#B8A47A'],
            ['Tento mesiac',        $stats['month'],   '#22c55e'],
            ['Čakajú na potvrd.',   $stats['pending'], '#f59e0b'],
            ['Odhlásení',           $stats['unsubscribed'], '#dc2626'],
        ] as [$l,$n,$c]): ?>
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:<?php echo $c ?>;font-family:var(--serif)"><?php echo $n ?></div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px"><?php echo $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sub-tabs -->
    <div class="pnl-nl-subtabs" style="display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:20px">
    <?php foreach(['send'=>'Odoslať','blast'=>'Šablóna novej ponuky','subscribers'=>'Odberatelia','log'=>'História'] as $st=>$sl): ?>
    <a href="?action=newsletter&sub=<?php echo $st ?>"
       style="padding:9px 16px;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.4px;
              text-transform:uppercase;border-radius:8px 8px 0 0;margin-bottom:-2px;
              border:1px solid <?php echo $subtab===$st?'var(--border)':'transparent' ?>;
              border-bottom:<?php echo $subtab===$st?'2px solid var(--white)':'none' ?>;
              background:<?php echo $subtab===$st?'var(--white)':'transparent' ?>;
              color:<?php echo $subtab===$st?'var(--accent-txt)':'var(--muted)' ?>">
        <?php echo $sl ?>
    </a>
    <?php endforeach; ?>
    </div>

    <?php if ($subtab === 'blast'):
        $bcfg = function_exists('zcn_blast_settings') ? zcn_blast_settings() : ['subject_prefix'=>'Nová ponuka: ','intro'=>'','outro'=>'','show_contact'=>1];
        $sample = get_posts(['post_type'=>'property','posts_per_page'=>1,'post_status'=>'publish','fields'=>'ids']);
        $sample_id = $sample[0] ?? 0;
    ?>
    <div class="pnl-wide">
        <?php if (!empty($blast_saved)): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px">Šablóna uložená.</div>
        <?php endif; ?>
        <p style="color:var(--muted);font-size:14px;margin-bottom:18px;line-height:1.6">Takto vyzerá e-mail, ktorý sa odošle odberateľom po kliknutí na <strong>„Poslať odberateľom newslettera"</strong> pri ponuke. Uprav si úvodný text, záver aj kontakt. Môžeš použiť premennú <code style="background:var(--section);padding:1px 6px;border-radius:4px">{meno}</code>.</p>
        <form method="post">
            <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:24px">
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Predmet e-mailu (pred názvom ponuky)</label>
                    <input type="text" name="blast_subject" value="<?php echo esc_attr($bcfg['subject_prefix']) ?>" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px">
                    <div style="font-size:11px;color:var(--muted);margin-top:5px">Napr. „Nová ponuka: " → výsledok: <em>Nová ponuka: 3-izbový byt…</em></div>
                </div>
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Úvodný text (pred ponukou)</label>
                    <textarea name="blast_intro" rows="4" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;resize:vertical;line-height:1.6"><?php echo esc_textarea($bcfg['intro']) ?></textarea>
                </div>
                <div style="margin-bottom:16px">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Záverečný text (za ponukou)</label>
                    <textarea name="blast_outro" rows="3" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;resize:vertical;line-height:1.6"><?php echo esc_textarea($bcfg['outro']) ?></textarea>
                </div>
                <label style="display:flex;align-items:center;gap:9px;cursor:pointer;margin-bottom:20px">
                    <input type="checkbox" name="blast_contact" value="1" <?php checked(!empty($bcfg['show_contact'])) ?> style="accent-color:#B8A47A;width:16px;height:16px">
                    <span style="font-size:14px;color:var(--dark)">Pridať na koniec kontakt na makléra (meno, telefón, e-mail z profilu)</span>
                </label>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button type="submit" name="pnl_blast_save" value="1" class="btn btn-primary" style="padding:12px 26px">Uložiť šablónu</button>
                    <?php if ($sample_id): ?>
                    <button type="button" class="btn btn-ghost" onclick="pnlBlastPreview()">Ukázať náhľad</button>
                    <?php endif; ?>
                </div>
                <?php if (!$sample_id): ?>
                <p style="font-size:12px;color:var(--muted);margin-top:12px">Náhľad sa zobrazí, keď budeš mať aspoň jednu publikovanú ponuku.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <!-- Preview modal -->
    <div id="pnlBlastModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;overflow:auto;padding:20px">
        <div style="max-width:640px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.3)">
            <div style="padding:12px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#1C1A18">
                <strong style="color:#fff;font-size:14px">Náhľad e-mailu novej ponuky</strong>
                <button onclick="document.getElementById('pnlBlastModal').style.display='none'" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;font-size:14px">✕</button>
            </div>
            <iframe id="pnlBlastFrame" style="width:100%;height:600px;border:none"></iframe>
        </div>
    </div>
    <script>
    function pnlBlastPreview(){
        var d=new FormData();
        d.append('action','zcn_send_property');
        d.append('nonce','<?php echo wp_create_nonce('zcn_send_nonce') ?>');
        d.append('property_id','<?php echo (int)$sample_id ?>');
        d.append('preview','1');
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:d,credentials:'same-origin'})
        .then(function(r){return r.json();}).then(function(res){
            if(res.success&&res.data.html){document.getElementById('pnlBlastModal').style.display='block';document.getElementById('pnlBlastFrame').srcdoc=res.data.html;}
            else{alert((res.data&&res.data.message)||'Náhľad sa nepodaril.');}
        });
    }
    </script>
    <?php endif; ?>

    <?php if ($subtab === 'send'): ?>
    <!-- SEND -->
    <div class="pnl-wide">
        <div id="pnlNlMsg" style="display:none;margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:14px"></div>
        <div class="pnl-nl-form">
        <div class="pnl-nl-main" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:28px">
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Predmet *</label>
                <input type="text" id="pnlSubject"
                    style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;color:var(--text);outline:none;transition:border .2s"
                    placeholder="Nová ponuka – 3-izbový byt Banská Bystrica">
            </div>
            <?php if (function_exists('zcn_render_tpl_toolbar')) zcn_render_tpl_toolbar('pnlBody', 'pnlSubject'); ?>
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Obsah *</label>
                <?php
                if (function_exists('zc_render_editor_tools')) {
                    zc_render_editor_tools('pnlBody', ['template'=>'newsletter','label'=>'Obsah newslettera']);
                }
                wp_editor('', 'pnlBody', pp_panel_editor_settings('pnl_body', 18));
                ?>
            </div>
        </div>

        <div class="pnl-nl-side">
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:20px">
                <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Príjemcovia</label>
                <select id="pnlScope" onchange="pnlScopeChange()" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;margin-bottom:8px">
                    <option value="all">Všetci aktívni odberatelia</option>
                    <option value="offers">Všetci so záujmom o ponuky</option>
                    <option value="cats">Vybrané kategórie…</option>
                    <option value="people">Vybraní ľudia…</option>
                </select>
                <div id="pnlCatsWrap" style="display:none"><?php pp_nl_multiselect('pnl_interest', '', ['empty' => 'Vyber kategórie']) ?></div>
                <div id="pnlPeopleWrap" style="display:none"><?php if (function_exists('zcn_people_picker')) zcn_people_picker('pnlPeople'); ?></div>
            </div>
            <div style="background:var(--section);border-radius:var(--r-sm);padding:14px;margin-top:16px;display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                <div style="flex:1">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Testovací e-mail</label>
                    <input type="email" id="pnlTestEmail"
                        style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px"
                        placeholder="vas@email.sk">
                </div>
                <button onclick="pnlNlSend(true)"
                    style="padding:9px 16px;background:var(--section);border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .2s">
                    Test
                </button>
            </div>
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
                <div style="font-size:13px;color:var(--muted);margin-bottom:12px">Odošle sa <strong id="pnlNlCount" style="color:var(--dark)"><?php echo $stats['active'] ?></strong> odberateľom
                    <a href="?action=newsletter&sub=subscribers" style="font-size:12px;color:var(--accent-txt);margin-left:8px;text-decoration:none">zobraziť →</a>
                </div>
                <button onclick="pnlNlPreview()"
                    style="width:100%;padding:11px 18px;margin-bottom:8px;background:var(--section);border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:12px;font-weight:700;cursor:pointer">
                    Náhľad
                </button>
                <button onclick="pnlNlSend(false)" class="btn btn-primary" style="width:100%;padding:12px;font-size:12px">
                    Odoslať →
                </button>
            </div>
        </div>
        </div>
    </div>

    <!-- Preview modal -->
    <div id="pnlNlModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;overflow:auto;padding:20px">
        <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.3)">
            <div style="padding:12px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#1C1A18">
                <strong style="color:#fff;font-size:14px">Náhľad e-mailu</strong>
                <button onclick="document.getElementById('pnlNlModal').style.display='none'"
                    style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;font-size:14px">✕</button>
            </div>
            <iframe id="pnlNlFrame" style="width:100%;height:580px;border:none"></iframe>
        </div>
    </div>

    <script>
    function pnlNlMsg(msg, ok) {
        var el = document.getElementById('pnlNlMsg');
        el.style.display = 'block';
        el.style.background = ok ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
        el.style.color = ok ? '#15803d' : '#dc2626';
        el.style.border = '1px solid ' + (ok ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)');
        el.textContent = msg;
        el.scrollIntoView({behavior:'smooth',block:'nearest'});
    }
    var pnlCounts = <?php echo wp_json_encode($interest_counts); ?>;
    function pnlScopeChange(){
        var scope=document.getElementById('pnlScope').value;
        document.getElementById('pnlCatsWrap').style.display=(scope==='cats')?'block':'none';
        var pw=document.getElementById('pnlPeopleWrap');
        if(pw)pw.style.display=(scope==='people')?'block':'none';
        pnlRecalc();
    }
    function pnlPickedCats(){
        return [].map.call(document.querySelectorAll('#pnlCatsWrap input:checked'),function(c){return c.value});
    }
    function pnlRecalc(){
        var scope=document.getElementById('pnlScope').value, out=document.getElementById('pnlNlCount');
        var total='<?php echo (int) $stats['active'] ?>';
        if(scope==='people'){
            var picked=window.zcPeoplePicked?zcPeoplePicked('pnlPeople'):[];
            out.textContent=picked.length; return;
        }
        if(scope!=='cats'){ out.textContent=total; return; }
        var boxes=document.querySelectorAll('#pnlCatsWrap .zc-ms-opt input');
        var cats=pnlPickedCats();
        // Nič alebo všetko označené = všetci
        if(!cats.length||cats.length===boxes.length){ out.textContent=total; return; }
        var n=0; cats.forEach(function(c){ n+=Number(pnlCounts[c]||0) });
        out.textContent='max. '+n;
    }
    document.addEventListener('change',function(e){
        if(!e.target.closest)return;
        if(e.target.closest('#pnlCatsWrap')||e.target.closest('#pnlPeopleWrap')) pnlRecalc();
    });
    document.addEventListener('click',function(e){
        if(e.target.hasAttribute&&(e.target.hasAttribute('data-zc-people-all')||e.target.hasAttribute('data-zc-people-none')))
            setTimeout(pnlRecalc,10);
    });
    function pnlNlGetBody() {
        // TinyMCE (visual mode) or plain textarea (text mode)
        if (window.tinymce && tinymce.get('pnlBody') && !tinymce.get('pnlBody').isHidden()) {
            return tinymce.get('pnlBody').getContent();
        }
        var ta = document.getElementById('pnlBody');
        return ta ? ta.value : '';
    }
    function pnlNlApi(extra) {
        var s = document.getElementById('pnlSubject').value.trim();
        var b = pnlNlGetBody().trim();
        if (!s||!b) { alert('Vyplňte predmet aj obsah.'); return null; }
        var data = new FormData();
        data.append('action','zcn_send_newsletter');
        data.append('nonce','<?php echo wp_create_nonce('zcn_send_nonce') ?>');
        data.append('subject',s); data.append('body',b);
        data.append('is_html','1');
        var scope=document.getElementById('pnlScope').value;
        if(scope==='people'){
            var picked=window.zcPeoplePicked?zcPeoplePicked('pnlPeople'):[];
            if(!picked.length){alert('Vyber aspoň jedného príjemcu.');btn&&(btn.disabled=false);return;}
            data.append('emails',picked.join(','));
        } else if(scope==='cats'){
            var cats=pnlPickedCats();
            var boxes=document.querySelectorAll('#pnlCatsWrap .zc-ms-opt input');
            if(!cats.length){alert('Vyber aspoň jednu kategóriu.');btn&&(btn.disabled=false);return;}
            // Všetko označené znamená všetkých – filter vtedy neposielame
            if(cats.length<boxes.length) data.append('interest',cats.join(','));
        } else if(scope==='offers'){
            data.append('interest','offers');
        }
        Object.keys(extra||{}).forEach(function(k){ data.append(k,extra[k]); });
        return data;
    }
    function pnlNlSend(isTest) {
        var data = pnlNlApi({});
        if (!data) return;
        var t = document.getElementById('pnlTestEmail').value.trim();
        if (isTest && !t) { alert('Zadajte testovací e-mail.'); return; }
        if (!isTest && !confirm('Odoslať newsletter '+document.getElementById('pnlNlCount').textContent+' odberateľom?')) return;
        if (isTest && t) data.append('test_email', t);
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(r=>r.json()).then(res=>{ pnlNlMsg(res.data.message, res.success); });
    }
    function pnlNlPreview() {
        var data = pnlNlApi({preview:'1'});
        if (!data) return;
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(r=>r.json()).then(res=>{
            if (res.success) {
                document.getElementById('pnlNlModal').style.display='block';
                document.getElementById('pnlNlFrame').srcdoc = res.data.html;
            }
        });
    }
    </script>

    <?php elseif ($subtab === 'subscribers'):
        $nl_interest = pp_nl_interest($_GET['interest'] ?? '');
        $nl_status = sanitize_key($_GET['status'] ?? 'all');
        if (!in_array($nl_status, ['all','active','pending','unsubscribed'], true)) $nl_status = 'all';
        $nl_source = sanitize_key($_GET['source'] ?? '');
        $nl_search = sanitize_text_field($_GET['search'] ?? '');
        $where = ['1=1'];
        $where_args = [];
        if ($nl_interest) {
            $where[] = 'FIND_IN_SET(%s, interest)';
            $where_args[] = $nl_interest;
        }
        if ($nl_status !== 'all') {
            $where[] = 'status=%s';
            $where_args[] = $nl_status;
        }
        if ($nl_source) {
            $where[] = 'source LIKE %s';
            $where_args[] = '%' . $wpdb->esc_like($nl_source) . '%';
        }
        if ($nl_search !== '') {
            $like = '%' . $wpdb->esc_like($nl_search) . '%';
            $where[] = '(email LIKE %s OR name LIKE %s)';
            $where_args[] = $like;
            $where_args[] = $like;
        }
        $rows_sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY subscribed_at DESC';
        $rows = $where_args
            ? $wpdb->get_results($wpdb->prepare($rows_sql, $where_args))
            : $wpdb->get_results($rows_sql);
        $source_labels = function_exists('zcn_source_labels') ? zcn_source_labels() : [];
    ?>
    <?php if ($subscriber_notice): ?>
    <div style="padding:12px 15px;margin-bottom:16px;border-radius:8px;background:<?php echo $subscriber_notice_ok?'#f0fdf4':'#fef2f2' ?>;border:1px solid <?php echo $subscriber_notice_ok?'#bbf7d0':'#fecaca' ?>;color:<?php echo $subscriber_notice_ok?'#15803d':'#dc2626' ?>;font-size:13px">
        <?php echo esc_html($subscriber_notice) ?>
    </div>
    <?php endif; ?>

    <details class="pnl-subscriber-add" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:800;color:var(--dark);font-size:14px">＋ Pridať odberateľa ručne</summary>
        <form method="post" class="pnl-subscriber-add-form" style="display:grid;grid-template-columns:1.2fr 1.2fr 1fr 1fr auto;gap:10px;align-items:end;margin-top:15px">
            <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
            <input type="hidden" name="pnl_nadd" value="1">
            <label style="font-size:11px;font-weight:700;color:var(--muted)">Meno
                <input type="text" name="name" style="display:block;width:100%;margin-top:5px;padding:10px;border:1px solid var(--border);border-radius:7px">
            </label>
            <label style="font-size:11px;font-weight:700;color:var(--muted)">E-mail *
                <input type="email" name="email" required style="display:block;width:100%;margin-top:5px;padding:10px;border:1px solid var(--border);border-radius:7px">
            </label>
            <label style="font-size:11px;font-weight:700;color:var(--muted)">Kategória
                <div style="margin-top:5px"><?php pp_nl_multiselect('interest', '', ['empty' => 'Všetko']) ?></div>
            </label>
            <label style="font-size:11px;font-weight:700;color:var(--muted)">Spôsob pridania
                <select name="add_mode" style="display:block;width:100%;margin-top:5px;padding:10px;border:1px solid var(--border);border-radius:7px">
                    <option value="active">Aktívny – pridať priamo</option>
                    <option value="pending">Poslať potvrdenie e-mailom</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary" style="min-height:42px">Pridať</button>
        </form>
        <p style="font-size:11px;color:var(--muted);margin:10px 0 0;line-height:1.5">Aktívny kontakt pridajte iba vtedy, ak vám preukázateľne udelil súhlas. Zdroj sa uloží ako „Ručne v realitnom paneli“.</p>
    </details>

    <details class="pnl-subscriber-add" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px;margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:800;color:var(--dark);font-size:14px">＋ Pridať viac ľudí naraz</summary>
        <form method="post" class="pnl-subscriber-batch-form" style="display:grid;grid-template-columns:minmax(0,2fr) minmax(190px,.8fr);gap:14px;align-items:end;margin-top:15px">
            <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
            <input type="hidden" name="pnl_nbatch" value="1">
            <label style="font-size:11px;font-weight:700;color:var(--muted)">Kontakty – jeden človek na riadok
                <textarea name="batch_contacts" rows="8" required spellcheck="false" style="display:block;width:100%;margin-top:5px;padding:11px;border:1px solid var(--border);border-radius:7px;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;line-height:1.55" placeholder="Meno;Priezvisko;email@example.sk&#10;Jana;Nováková;jana@example.sk"></textarea>
            </label>
            <div style="display:grid;gap:10px">
                <label style="font-size:11px;font-weight:700;color:var(--muted)">Spoločná kategória
                    <div style="margin-top:5px"><?php pp_nl_multiselect('batch_interest', '', ['empty' => 'Všetko']) ?></div>
                </label>
                <label style="font-size:11px;font-weight:700;color:var(--muted)">Spôsob pridania
                    <select name="batch_mode" style="display:block;width:100%;margin-top:5px;padding:10px;border:1px solid var(--border);border-radius:7px">
                        <option value="active">Pridať priamo, bez potvrdenia</option>
                        <option value="pending">Poslať potvrdzovací e-mail</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary" style="min-height:44px">Pridať celú dávku</button>
            </div>
        </form>
        <p style="font-size:11px;color:var(--muted);margin:10px 0 0;line-height:1.55">
            Formát: <strong>Meno;Priezvisko;e-mail</strong> (funguje aj CSV s čiarkou alebo tabulátorom).
            Kontakty sa pridajú priamo ako aktívne, bez potvrdzovacieho a bez uvítacieho e-mailu.
            Rovnaký e-mail sa nevytvorí druhýkrát; existujúci záznam sa bezpečne aktualizuje.
        </p>
    </details>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <span style="font-size:13px;color:var(--muted)"><strong style="color:var(--dark)"><?php echo count($rows) ?></strong> zobrazených · databáza obsahuje aj čakajúcich a odhlásených</span>
        <form method="get" class="pnl-subscriber-filters" style="display:flex;gap:7px;flex-wrap:wrap">
            <input type="hidden" name="action" value="newsletter"><input type="hidden" name="sub" value="subscribers">
            <input type="search" name="search" value="<?php echo esc_attr($nl_search) ?>" placeholder="Meno alebo e-mail" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px">
            <select name="status" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px">
                <option value="all" <?php selected($nl_status,'all') ?>>Všetky stavy</option>
                <option value="active" <?php selected($nl_status,'active') ?>>Aktívni</option>
                <option value="pending" <?php selected($nl_status,'pending') ?>>Čakajúci</option>
                <option value="unsubscribed" <?php selected($nl_status,'unsubscribed') ?>>Odhlásení</option>
            </select>
            <select name="interest" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px">
                <option value="">Všetky kategórie</option>
                <?php foreach (pp_nl_interests() as $value => $label): ?><option value="<?php echo esc_attr($value) ?>" <?php selected($nl_interest,$value) ?>><?php echo esc_html($label) ?></option><?php endforeach; ?>
            </select>
            <select name="source" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px">
                <option value="">Všetky zdroje</option>
                <?php foreach ($source_labels as $value => $label): ?><option value="<?php echo esc_attr($value) ?>" <?php selected($nl_source,$value) ?>><?php echo esc_html($label) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-ghost" type="submit">Filtrovať</button>
        </form>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pp_export_subscribers'), 'pp_export_subscribers')) ?>" class="btn btn-ghost" style="font-size:12px;padding:8px 14px">Export CSV</a>
    </div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
    <?php if ($rows): ?>
    <table class="pnl-subs-table" style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--section);border-bottom:1px solid var(--border)">
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">E-mail</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Meno</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Kategória</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Stav</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Zdroj</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Dátum</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Akcie</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            if ($r->status === 'active') {
                $badge = '<span style="background:#f0fdf4;color:#15803d;padding:3px 10px;border-radius:50px;font-size:10px;font-weight:700">Aktívny</span>';
            } elseif ($r->status === 'unsubscribed') {
                $badge = '<span style="background:#fef2f2;color:#b91c1c;padding:3px 10px;border-radius:50px;font-size:10px;font-weight:700">Odhlásený</span>';
            } else {
                $badge = '<span style="background:#fffbeb;color:#92400e;padding:3px 10px;border-radius:50px;font-size:10px;font-weight:700">Čaká na potvrdenie</span>';
            }
            $source_label = function_exists('zcn_source_label')
                ? pp_nl_source_label($r->source ?? '')
                : (($r->source ?? '') ?: 'Neznámy zdroj');
        ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td data-label="E-mail" style="padding:11px 16px;color:var(--dark);font-weight:600"><?php echo esc_html($r->email) ?></td>
            <td data-label="Meno" style="padding:11px 16px;color:var(--muted)"><?php echo esc_html($r->name ?: '–') ?></td>
            <td data-label="Kategória" style="padding:11px 16px">
                <form method="post" style="display:flex;gap:5px">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_ninterest" value="<?php echo (int) $r->id ?>">
                    <div style="min-width:170px"><?php pp_nl_multiselect('interest', $r->interest ?? '', ['empty' => 'Všetko', 'compact' => true]) ?></div>
                    <button class="btn btn-ghost" style="padding:5px 8px" title="Uložiť">✓</button>
                </form>
            </td>
            <td data-label="Stav" style="padding:11px 16px"><?php echo $badge ?></td>
            <td data-label="Zdroj" style="padding:11px 16px;color:var(--muted);font-size:12px"><?php echo esc_html($source_label) ?></td>
            <td data-label="Pridaný" style="padding:11px 16px;color:var(--muted);font-size:12px"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($r->subscribed_at))) ?></td>
            <td data-label="Akcie" class="pnl-subs-actions" style="padding:11px 16px;white-space:nowrap">
                <?php if ($r->status === 'active'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Odhlásiť odberateľa?')">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_nsub" value="<?php echo $r->id ?>">
                    <button type="submit" style="padding:5px 10px;background:var(--section);border:1px solid var(--border);border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">Odhlásiť</button>
                </form>
                <?php else: ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Odoslať nový potvrdzovací e-mail?')">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_nconfirm" value="<?php echo $r->id ?>">
                    <button type="submit" style="padding:5px 10px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">Poslať potvrdenie</button>
                </form>
                <?php endif; ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Natrvalo vymazať?')">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_ndel" value="<?php echo $r->id ?>">
                    <button type="submit" style="padding:5px 10px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">Vymazať</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:48px;text-align:center;color:var(--muted)">Zatiaľ žiadni odberatelia.</div>
    <?php endif; ?>
    </div>

    <?php elseif ($subtab === 'log'):
        $log = get_option('zcn_send_log', []);
        $campaigns = function_exists('zcn_all_campaigns') ? zcn_all_campaigns() : [];
    ?>
    <?php if ($campaigns): ?>
    <div style="margin-bottom:26px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:12px">Otvorenia &amp; kliky</div>
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead><tr style="background:var(--section);border-bottom:1px solid var(--border)">
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Predmet</th>
                <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Odoslané</th>
                <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Otvorenia</th>
                <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Kliky</th>
            </tr></thead>
            <tbody>
            <?php foreach ($campaigns as $cs): if (!$cs) continue; ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:11px 16px;color:var(--dark);font-weight:500"><?php echo esc_html($cs['subject']) ?><br><span style="color:var(--muted);font-size:11px;font-weight:400"><?php echo date('d.m.Y H:i', strtotime($cs['date'])) ?></span></td>
                <td style="padding:11px 16px;text-align:right;color:var(--muted)"><?php echo (int)$cs['sent'] ?></td>
                <td style="padding:11px 16px;text-align:right;font-weight:700;color:#15803d"><?php echo (int)$cs['opens'] ?> <span style="color:var(--muted);font-weight:400;font-size:11px">(<?php echo (int)$cs['open_rate'] ?>%)</span></td>
                <td style="padding:11px 16px;text-align:right;font-weight:700;color:var(--accent-txt)"><?php echo (int)$cs['clicks'] ?> <span style="color:var(--muted);font-weight:400;font-size:11px">(<?php echo (int)$cs['click_rate'] ?>%)</span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p style="font-size:11px;color:var(--muted);margin-top:8px">Otvorenia sa merajú neviditeľným obrázkom — reálne číslo býva vyššie (časť e-mailových klientov obrázky blokuje).</p>
    </div>
    <?php endif; ?>
    <?php if ($log): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:12px">História odoslaní</div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:var(--section);border-bottom:1px solid var(--border)">
            <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Dátum</th>
            <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Predmet</th>
            <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Odoslané</th>
            <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Zlyhalo</th>
        </tr></thead>
        <tbody>
        <?php foreach ($log as $l): ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:11px 16px;color:var(--muted);font-size:12px"><?php echo date('d.m.Y H:i', strtotime($l['date'])) ?></td>
            <td style="padding:11px 16px;color:var(--dark);font-weight:500"><?php echo esc_html($l['subject']) ?></td>
            <td style="padding:11px 16px;text-align:right;color:#15803d;font-weight:700"><?php echo $l['sent'] ?></td>
            <td style="padding:11px 16px;text-align:right;color:<?php echo $l['failed']>0?'#dc2626':'#94a3b8' ?>"><?php echo $l['failed'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="padding:48px;text-align:center;color:var(--muted)">Zatiaľ nebol odoslaný žiadny newsletter.</div>
    <?php endif; ?>
    <?php endif; ?>
    <?php
}

// ── Reviews Panel ─────────────────────────────────────────────────────────
/**
 * Uloženie recenzie z panela.
 *
 * Zámerne to NEZÁVISÍ od toho, či sme „na stránke panela" – stačí platný
 * bezpečnostný token a oprávnenie. Predtým sa kontrolovalo is_page('realitny-panel');
 * keď stránka niekedy skončila v koši a nová dostala iný slug, formulár sa ticho
 * ignoroval – recenzia sa tvárila, že sa uložila, ale nestalo sa nič.
 *
 * Vracia kód výsledku ('' = tento request nič neukladá). Spustí sa najviac raz.
 */
/** Zápis do bezpečnostného denníka (ak je k dispozícii). */
function zcr_audit($what, $who = '', $id = 0) {
    if (function_exists('zc_audit_log')) zc_audit_log('review', $who, $what, (int) $id);
}

/** Odloží podobu recenzie pred zmenou, nech sa zmena dá vrátiť. */
function zcr_audit_snapshot($id) {
    global $wpdb;
    if (!function_exists('zc_audit_stage') || !$id) return;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . zcr_table() . ' WHERE id = %d', (int) $id), ARRAY_A);
    if ($row) zc_audit_stage('review', $row);
}

function zcr_panel_handle_post() {
    static $done = null;
    if ($done !== null) return $done;

    $is_save   = isset($_POST['zcr_panel_save']);
    $is_del    = isset($_POST['zcr_panel_delete']);
    $is_toggle = isset($_POST['zcr_panel_toggle']);
    if (!$is_save && !$is_del && !$is_toggle) return $done = '';

    if (!is_user_logged_in() || !current_user_can('edit_posts')) return $done = 'perm';
    if (!function_exists('zcr_table'))                            return $done = 'noplugin';
    if (!wp_verify_nonce($_POST['_zcrnonce'] ?? '', 'zcr_panel')) return $done = 'nonce';

    global $wpdb;
    $t = zcr_table();

    // Tabuľka môže chýbať, ak sa plugin nainštaloval bez spustenia aktivácie
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) !== $t) {
        if (function_exists('zcr_install')) zcr_install();
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) !== $t) return $done = 'notable';
    }

    // Rovnaké odoslanie spracujeme len raz
    $token = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['zcr_token'] ?? ''));
    if ($token) {
        if (get_transient('zcr_tok_' . $token)) return $done = 'dup';
        set_transient('zcr_tok_' . $token, 1, 30 * MINUTE_IN_SECONDS);
    }

    $fail = function ($msg) {
        set_transient('zcr_panel_err_' . get_current_user_id(), (string) $msg, 120);
        return 'fail';
    };

    if ($is_save) {
        $id   = intval($_POST['zcr_id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['author_name'] ?? ''));
        $body = sanitize_textarea_field(wp_unslash($_POST['body'] ?? ''));
        if ($name === '' || $body === '') return $done = 'empty';

        $data = [
            'author_name' => $name,
            'author_role' => sanitize_text_field(wp_unslash($_POST['author_role'] ?? '')),
            'body'        => $body,
            'rating'      => max(1, min(5, intval($_POST['rating'] ?? 5))),
            'avatar_url'  => esc_url_raw($_POST['avatar_url'] ?? ''),
            'published'   => empty($_POST['published']) ? 0 : 1,
        ];
        if ($id) {
            zcr_audit_snapshot($id);
            $r = $wpdb->update($t, $data, ['id' => $id]);
            if ($r === false) return $done = $fail($wpdb->last_error);
            zcr_audit('Upravená recenzia', $name, $id);
            return $done = 'saved';
        }
        $data['sort_order'] = (int) $wpdb->get_var("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$t}");
        $wpdb->insert($t, $data);
        if (!$wpdb->insert_id) return $done = $fail($wpdb->last_error);
        zcr_audit('Pridaná recenzia', $name, $wpdb->insert_id);
        return $done = 'added';
    }

    if ($is_del) {
        $id   = intval($_POST['zcr_id']);
        $gone = $wpdb->get_var($wpdb->prepare("SELECT author_name FROM {$t} WHERE id=%d", $id));
        zcr_audit_snapshot($id);
        $wpdb->delete($t, ['id' => $id]);
        zcr_audit('Zmazaná recenzia', (string) $gone, $id);
        return $done = 'deleted';
    }

    $id  = intval($_POST['zcr_id']);
    $who = (string) $wpdb->get_var($wpdb->prepare("SELECT author_name FROM {$t} WHERE id=%d", $id));
    $cur = (int) $wpdb->get_var($wpdb->prepare("SELECT published FROM {$t} WHERE id=%d", $id));
    zcr_audit_snapshot($id);
    $wpdb->update($t, ['published' => $cur ? 0 : 1], ['id' => $id]);
    zcr_audit($cur ? 'Recenzia skrytá z webu' : 'Recenzia zobrazená na webe', $who, $id);
    return $done = $cur ? 'hidden' : 'shown';
}

/**
 * Formuláre recenzií odosielame na admin-post.php – to je oficiálny cieľ
 * WordPressu na formuláre z frontendu. Nezávisí od šablóny, permalinku ani
 * od slugu stránky, nikdy sa nekešuje a nezasiahne ho kanonické presmerovanie
 * (to pri POST-e zahodí odoslané dáta a vyzerá to ako obyčajné obnovenie stránky).
 */
define('ZCR_PANEL_ACTION', 'zcr_panel');

function zcr_panel_post_endpoint() {
    $code = zcr_panel_handle_post();
    pp_go(pp_panel_url('action=reviews' . ($code !== '' ? '&zcr_msg=' . $code : '')));
}
add_action('admin_post_' . ZCR_PANEL_ACTION,        'zcr_panel_post_endpoint');
add_action('admin_post_nopriv_' . ZCR_PANEL_ACTION, 'zcr_panel_post_endpoint');

/** Skryté polia, ktoré patria do každého formulára recenzií v paneli. */
function zcr_panel_form_fields($id = 0) {
    wp_nonce_field('zcr_panel', '_zcrnonce');
    echo '<input type="hidden" name="action" value="' . esc_attr(ZCR_PANEL_ACTION) . '">';
    echo '<input type="hidden" name="zcr_id" value="' . (int) $id . '">';
    // Jednorazový kľúč – to isté odoslanie sa nikdy nespracuje dvakrát
    // (dvojklik, opätovné odoslanie po F5, pomalé pripojenie…)
    echo '<input type="hidden" name="zcr_token" value="' . esc_attr(md5(uniqid('zcr', true))) . '">';
}

/**
 * Poistka: keby sa formulár aj tak odoslal na stránku panela (staršia
 * vyrovnávacia pamäť prehliadača), spracujeme ho aj tam a presmerujeme späť.
 */
add_action('template_redirect', function () {
    $code = zcr_panel_handle_post();
    if ($code === '' || headers_sent()) return; // ak sa už tlačilo, hlášku vypíše panel
    pp_go(pp_panel_url('action=reviews&zcr_msg=' . $code));
}, 6);

function panel_reviews() {
    if (!function_exists('zcr_table')) {
        echo '<div style="padding:40px;text-align:center;color:#e74c3c">Plugin ZC Recenzie nie je nainštalovaný.</div>';
        return;
    }
    global $wpdb;
    $t   = zcr_table();
    $msg = '';
    $err = '';

    $notes = [
        'added'   => 'Recenzia pridaná. Nájdeš ju nižšie v zozname.',
        'saved'   => 'Zmeny v recenzii uložené.',
        'deleted' => 'Recenzia vymazaná.',
        'shown'   => 'Recenzia sa už zobrazuje na webe.',
        'hidden'  => 'Recenzia je skrytá – na webe ju nikto neuvidí.',
        'dup'     => 'Toto odoslanie už bolo spracované – recenzia sa nepridala druhýkrát.',
    ];
    // Kód výsledku príde z presmerovania; ak presmerovanie nestihlo prebehnúť
    // (hlavička už bola odoslaná), spracujeme formulár aj tu – nikdy sa nestratí.
    $code = sanitize_key($_GET['zcr_msg'] ?? '');
    if ($code === '' && function_exists('zcr_panel_handle_post')) $code = zcr_panel_handle_post();

    if (isset($notes[$code]))      $msg = $notes[$code];
    elseif ($code === 'empty')     $err = 'Vyplň meno klienta aj text recenzie.';
    elseif ($code === 'perm')      $err = 'Na úpravu recenzií nemáš oprávnenie.';
    elseif ($code === 'noplugin')  $err = 'Plugin ZC Recenzie nie je aktívny.';
    elseif ($code === 'nonce')     $err = 'Formulár vypršal (bol si dlho neaktívny alebo je zapnutá cache). Načítaj stránku znova a skús to ešte raz.';
    elseif ($code === 'notable')   $err = 'V databáze chýba tabuľka recenzií. Skús plugin ZC Recenzie deaktivovať a znova aktivovať.';
    elseif ($code === 'fail') {
        $db  = get_transient('zcr_panel_err_' . get_current_user_id());
        delete_transient('zcr_panel_err_' . get_current_user_id());
        $err = 'Recenziu sa nepodarilo uložiť do databázy.' . ($db ? ' (' . $db . ')' : '');
    }

    // Ak by tabuľka chýbala, skúsime ju vytvoriť – panel tak nezostane prázdny
    $has_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t;
    if (!$has_table && function_exists('zcr_install')) {
        zcr_install();
        $has_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t;
    }

    $base     = pp_panel_url('action=reviews');
    $endpoint = admin_url('admin-post.php');
    $edit_id  = intval($_GET['edit_review'] ?? 0);
    $adding  = isset($_GET['new_review']);
    $zcr_ord = function_exists('zcr_order_sql') ? zcr_order_sql() : 'sort_order ASC, id ASC';
    if ($zcr_ord === 'RAND()') $zcr_ord = 'sort_order ASC, id ASC'; // v paneli chceme stabilné poradie
    $rows    = $has_table ? $wpdb->get_results("SELECT * FROM {$t} ORDER BY {$zcr_ord}") : [];
    $rows    = $rows ?: [];
    $manual  = function_exists('zcr_display_order') && zcr_display_order() === 'manual';

    $shown = 0;
    foreach ($rows as $r) if ($r->published) $shown++;
    $avg = 0;
    if ($rows) { foreach ($rows as $r) $avg += (int) $r->rating; $avg = round($avg / count($rows), 1); }

    // Upravovaná recenzia
    $cur = null;
    if ($edit_id) {
        foreach ($rows as $r) if ((int) $r->id === $edit_id) { $cur = $r; break; }
        if (!$cur) { $edit_id = 0; $err = $err ?: 'Recenzia sa nenašla – možno ju už niekto vymazal.'; }
    }
    $show_form = $adding || $cur;
    ?>

    <?php if ($msg): ?>
    <div class="pnl-note pnl-note--ok"><?php echo esc_html($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="pnl-note pnl-note--err"><?php echo esc_html($err) ?></div>
    <?php endif; ?>

    <div class="rev-head">
        <div>
            <h2>Recenzie</h2>
            <p><?php echo count($rows) ?> celkom · <?php echo $shown ?> na webe<?php if ($rows): ?> · priemer <?php echo esc_html($avg) ?> ★<?php endif; ?></p>
        </div>
        <?php if (!$show_form): ?>
        <a href="<?php echo esc_url($base . '&new_review=1') ?>" class="btn btn-primary rev-add"><?php echo pp_svg('plus',16) ?> Pridať recenziu</a>
        <?php endif; ?>
    </div>

    <?php if ($show_form):
        $f_name   = $cur ? $cur->author_name : '';
        $f_role   = $cur ? $cur->author_role : '';
        $f_body   = $cur ? $cur->body        : '';
        $f_rating = $cur ? (int) $cur->rating : 5;
        $f_pub    = $cur ? (int) $cur->published : 1;
    ?>
    <form method="post" action="<?php echo esc_url($endpoint) ?>" class="rev-form-card">
        <?php zcr_panel_form_fields($cur ? (int) $cur->id : 0); ?>
        <input type="hidden" name="avatar_url" value="<?php echo $cur ? esc_attr($cur->avatar_url) : '' ?>">

        <div class="rev-form-head"><?php echo $cur ? 'Upraviť recenziu' : 'Nová recenzia' ?></div>

        <div class="rev-form-body">
            <div class="rev-f2">
                <label class="rev-f">
                    <span class="rev-f-lbl">Meno klienta <b>*</b></span>
                    <input type="text" name="author_name" required autofocus placeholder="Jana Nováková" value="<?php echo esc_attr($f_name) ?>">
                </label>
                <label class="rev-f">
                    <span class="rev-f-lbl">Typ spolupráce <small>(voliteľné)</small></span>
                    <input type="text" name="author_role" placeholder="Predaj bytu" value="<?php echo esc_attr($f_role) ?>">
                </label>
            </div>

            <label class="rev-f">
                <span class="rev-f-lbl">Text recenzie <b>*</b></span>
                <textarea name="body" rows="5" required placeholder="Napíš, čo klient povedal…"><?php echo esc_textarea($f_body) ?></textarea>
            </label>

            <div class="rev-f">
                <span class="rev-f-lbl">Hodnotenie</span>
                <div class="rev-starpick">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="zcrst<?php echo $i ?>" name="rating" value="<?php echo $i ?>" <?php checked($f_rating, $i) ?>>
                    <label for="zcrst<?php echo $i ?>" title="<?php echo $i ?> z 5">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <label class="rev-check">
                <input type="checkbox" name="published" value="1" <?php checked($f_pub, 1) ?>>
                <span>Zobraziť na webe</span>
            </label>
        </div>

        <div class="rev-form-foot">
            <a href="<?php echo esc_url($base) ?>" class="btn btn-ghost">Zrušiť</a>
            <button type="submit" name="zcr_panel_save" value="1" class="btn btn-primary">Uložiť recenziu</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if (function_exists('zcr_display_settings_box')) echo zcr_display_settings_box(); ?>

    <?php if ($rows): ?>
    <div class="rev-list">
        <?php $last = count($rows) - 1; foreach ($rows as $i => $r): ?>
        <div class="rev-row<?php echo $r->published ? '' : ' is-hidden' ?><?php echo ($edit_id === (int) $r->id) ? ' is-editing' : '' ?>">
            <?php if ($manual): ?>
            <div class="rev-row-ord"><?php echo zcr_order_controls($r->id, $i === 0, $i === $last); ?></div>
            <?php endif; ?>

            <div class="rev-row-main">
                <div class="rev-row-top">
                    <span class="rev-name"><?php echo esc_html($r->author_name) ?></span>
                    <?php if ($r->author_role): ?><span class="rev-role"><?php echo esc_html($r->author_role) ?></span><?php endif; ?>
                    <span class="rev-stars"><?php echo str_repeat('★', (int) $r->rating) . str_repeat('☆', 5 - (int) $r->rating) ?></span>
                    <span class="rev-badge <?php echo $r->published ? 'on' : 'off' ?>"><?php echo $r->published ? 'Na webe' : 'Skrytá' ?></span>
                </div>
                <div class="rev-row-body"><?php echo esc_html($r->body) ?></div>
            </div>

            <div class="rev-row-actions">
                <a href="<?php echo esc_url($base . '&edit_review=' . (int) $r->id) ?>" class="btn btn-ghost"><?php echo pp_svg('pen',13) ?> Upraviť</a>
                <form method="post" action="<?php echo esc_url($endpoint) ?>" class="rev-inline">
                    <?php zcr_panel_form_fields((int) $r->id); ?>
                    <button type="submit" name="zcr_panel_toggle" value="1" class="btn btn-ghost">
                        <?php echo $r->published ? 'Skryť' : 'Zobraziť' ?>
                    </button>
                </form>
                <form method="post" action="<?php echo esc_url($endpoint) ?>" class="rev-inline" onsubmit="return confirm('Naozaj vymazať túto recenziu?')">
                    <?php zcr_panel_form_fields((int) $r->id); ?>
                    <button type="submit" name="zcr_panel_delete" value="1" class="btn btn-danger" title="Zmazať"><?php echo pp_svg('trash',13) ?></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (!$show_form): ?>
    <div class="rev-empty">
        <div class="rev-empty-ic"><?php echo pp_svg('star',44) ?></div>
        <h3>Zatiaľ žiadne recenzie</h3>
        <p>Pridaj prvú spokojnú klientku alebo klienta – na webe sa zobrazí hneď.</p>
        <a href="<?php echo esc_url($base . '&new_review=1') ?>" class="btn btn-primary"><?php echo pp_svg('plus',16) ?> Pridať prvú recenziu</a>
    </div>
    <?php endif; ?>

    <p class="rev-hint">
        Na úvodnej stránke sa recenzie točia v karuseli, všetky naraz sú na stránke
        <a href="<?php echo esc_url(home_url('/referencie/')); ?>" target="_blank">Referencie</a>.
        Kamkoľvek inam sa dajú vložiť cez <code>[zc_reviews]</code>
        alebo ako mozaika <code>[zc_reviews layout="mosaic"]</code>.
    </p>

    <?php if (current_user_can('manage_options')):
        $ppid  = function_exists('pp_panel_page_id') ? pp_panel_page_id() : 0;
        $ppage = $ppid ? get_post($ppid) : null;
    ?>
    <details class="rev-diag">
        <summary>Diagnostika (vidí len správca)</summary>
        <table>
            <tr><td>Stránka panela</td><td><?php echo $ppage
                ? '#' . (int) $ppid . ' · slug „' . esc_html($ppage->post_name) . '" · ' . esc_html(get_permalink($ppid))
                : 'NENAŠLA SA'; ?></td></tr>
            <tr><td>Cieľ formulárov</td><td><?php echo esc_html($endpoint) ?></td></tr>
            <tr><td>Návrat po uložení</td><td><?php echo esc_html($base) ?></td></tr>
            <tr><td>Tabuľka <?php echo esc_html($t) ?></td><td><?php echo $has_table ? 'existuje' : 'CHÝBA'; ?> · <?php echo count($rows) ?> záznamov</td></tr>
            <tr><td>Výsledok posledného odoslania</td><td><?php echo $code !== '' ? esc_html($code) : 'nič sa neodosielalo'; ?></td></tr>
            <tr><td>Prijaté POST polia</td><td><?php echo $_POST ? esc_html(implode(', ', array_keys($_POST))) : 'žiadne'; ?></td></tr>
            <tr><td>Prihlásený</td><td><?php
                $u = wp_get_current_user();
                echo esc_html($u->user_login) . ' · rola: ' . esc_html(implode(', ', (array) $u->roles)) . ' · ';
                echo current_user_can('edit_posts') ? 'smie upravovať' : 'NEMÁ oprávnenie edit_posts';
            ?></td></tr>
        </table>
        <p>Ak sa recenzia neuloží, odfoť túto tabuľku – je v nej presne vidieť, kde sa to zaseklo.</p>
    </details>
    <?php endif; ?>

    <style>
    .pnl-note{padding:12px 16px;border-radius:var(--r-sm);margin-bottom:18px;font-size:14px;font-weight:600}
    .pnl-note--ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
    .pnl-note--err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
    .rev-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap}
    .rev-head h2{font-family:var(--serif);font-size:22px;color:var(--dark);margin:0}
    .rev-head p{margin:4px 0 0;font-size:13px;color:var(--muted)}
    .rev-add{padding:11px 20px}

    /* Formulár priamo na stránke – bez okna a bez JavaScriptu */
    .rev-form-card{background:var(--white);border:1.5px solid var(--accent);border-radius:var(--r);
        margin-bottom:20px;overflow:hidden;box-shadow:var(--sh)}
    .rev-form-head{padding:14px 20px;background:var(--section);border-bottom:1px solid var(--border);
        font-family:var(--serif);font-size:17px;font-weight:700;color:var(--dark)}
    .rev-form-body{padding:20px}
    .rev-form-foot{padding:14px 20px;background:var(--section);border-top:1px solid var(--border);
        display:flex;gap:10px;justify-content:flex-end}
    .rev-form-foot .btn{padding:11px 22px}
    .rev-f2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .rev-f{display:block;margin-bottom:15px}
    .rev-f-lbl{display:block;margin-bottom:6px;font-size:11px;font-weight:700;
        text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
    .rev-f-lbl b{color:var(--accent-txt)}
    .rev-f-lbl small{font-weight:400;text-transform:none;letter-spacing:0}
    .rev-f input[type=text],.rev-f textarea{display:block;width:100%;padding:11px 13px;
        border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);
        font-size:14.5px;color:var(--text);background:var(--white)}
    .rev-f input[type=text]:focus,.rev-f textarea:focus{outline:none;border-color:var(--accent);
        box-shadow:0 0 0 3px rgba(184,164,122,.15)}
    .rev-f textarea{resize:vertical;min-height:110px;line-height:1.6}
    /* Hviezdičky ako prepínače – fungujú aj bez JavaScriptu */
    .rev-starpick{display:inline-flex;flex-direction:row-reverse;justify-content:flex-end}
    .rev-starpick input{position:absolute;opacity:0;width:0;height:0}
    .rev-starpick label{font-size:30px;line-height:1;padding:0 2px;cursor:pointer;
        color:#E0D8CE;transition:color .12s,transform .12s}
    .rev-starpick label:hover,.rev-starpick label:hover ~ label{color:var(--accent-dk)}
    .rev-starpick label:hover{transform:scale(1.12)}
    .rev-starpick input:checked ~ label{color:var(--accent)}
    .rev-starpick input:focus-visible + label{outline:2px solid var(--accent);border-radius:4px}
    .rev-check{display:flex;align-items:center;gap:9px;cursor:pointer;font-size:14px;color:var(--dark)}
    .rev-check input{accent-color:var(--accent);width:17px;height:17px}

    /* Zoznam v riadkoch – jedna recenzia = jeden riadok cez celú šírku */
    .rev-list{background:var(--white);border:1px solid var(--border);border-radius:var(--r);
        overflow:hidden;box-shadow:var(--sh-sm)}
    .rev-row{display:flex;align-items:flex-start;gap:16px;padding:16px 18px;
        border-bottom:1px solid var(--border);transition:background .15s}
    .rev-row:last-child{border-bottom:none}
    .rev-row:hover{background:#FCFBF8}
    .rev-row.is-hidden{background:#FBFAF7}
    .rev-row.is-hidden .rev-row-main{opacity:.6}
    .rev-row.is-editing{background:#FBF6EC;box-shadow:inset 3px 0 0 var(--accent)}
    .rev-row-ord{flex-shrink:0;padding-top:2px}
    .rev-row-main{flex:1;min-width:0}
    .rev-row-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:5px}
    .rev-row-body{font-size:13.5px;color:var(--muted);line-height:1.6;
        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .rev-row-actions{display:flex;gap:6px;align-items:center;flex-shrink:0}
    .rev-row-actions .btn{padding:7px 12px;font-size:12px;white-space:nowrap}
    .rev-stars{color:var(--accent);letter-spacing:2px;font-size:13px}
    .rev-name{font-family:var(--serif);font-weight:700;font-size:15px;color:var(--dark)}
    .rev-role{font-size:10.5px;color:var(--accent-txt);text-transform:uppercase;letter-spacing:.5px;
        background:var(--section);padding:2px 8px;border-radius:50px}
    .rev-badge{font-size:10px;font-weight:700;padding:3px 9px;border-radius:50px;white-space:nowrap}
    .rev-badge.on{background:#f0fdf4;color:#15803d}
    .rev-badge.off{background:#f1f5f9;color:#94a3b8}
    .rev-inline{display:inline}

    .rev-empty{padding:56px 24px;text-align:center;background:var(--white);border:1px solid var(--border);border-radius:var(--r)}
    .rev-empty-ic{color:var(--accent);opacity:.5;margin-bottom:12px}
    .rev-empty h3{font-family:var(--serif);font-size:19px;color:var(--dark);margin:0 0 6px}
    .rev-empty p{font-size:14px;color:var(--muted);margin:0 0 18px}
    .rev-hint{font-size:11.5px;color:var(--muted);margin-top:16px;line-height:1.7}
    .rev-hint code{background:var(--section);padding:2px 7px;border-radius:4px}

    .rev-diag{margin-top:18px;background:var(--white);border:1px solid var(--border);
        border-radius:var(--r);padding:12px 16px;font-size:12.5px;color:var(--muted)}
    .rev-diag summary{cursor:pointer;font-weight:700;color:var(--accent-txt)}
    .rev-diag table{width:100%;margin-top:10px;border-collapse:collapse}
    .rev-diag td{padding:5px 6px;border-bottom:1px solid var(--border);vertical-align:top;word-break:break-all}
    .rev-diag td:first-child{width:34%;color:var(--text);font-weight:600}
    .rev-diag p{margin:10px 0 0}

    @media(max-width:820px){
        .rev-row{flex-wrap:wrap}
        .rev-row-actions{width:100%;justify-content:flex-start}
        .rev-row-body{-webkit-line-clamp:3}
    }
    @media(max-width:640px){
        .rev-head{flex-direction:column}
        .rev-add{width:100%;justify-content:center}
        .rev-f2{grid-template-columns:1fr}
        .rev-row{padding:14px}
        .rev-row-actions .btn{flex:1;justify-content:center}
        .rev-form-foot .btn{flex:1;justify-content:center}
    }
    </style>
    <?php
}
