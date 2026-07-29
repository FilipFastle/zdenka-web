<?php
defined('ABSPATH') || exit;
// ── SVG líniové ikony (náhrada za emoji) ────────────────────────────────
function pp_svg($name, $size = 18) {
    $p = [
        'area'      => '<path d="M3 3h18v18H3z"/><path d="M3 9h6M3 15h6M9 3v6M15 3v6"/>',
        'land'      => '<path d="M2 20h20"/><path d="M4 20V10l8-6 8 6v10"/><path d="M9 20v-6h6v6"/>',
        'rooms'     => '<path d="M3 21V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v16"/><path d="M3 21h18M9 3v18M15 3v18"/>',
        'bath'      => '<path d="M4 12V5a2 2 0 0 1 2-2 2 2 0 0 1 2 2"/><path d="M2 12h20v3a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4z"/><path d="M6 19l-1 2M18 19l1 2"/>',
        'wc'        => '<path d="M6 3v7a3 3 0 0 0 3 3h1v8"/><path d="M18 3v18M15 3h6"/>',
        'floor'     => '<path d="M3 21V7l9-4 9 4v14"/><path d="M3 21h18M8 21v-5h8v5"/>',
        'year'      => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'condition' => '<path d="M14 7l-9 9 3 3 9-9M14 7l3-3 3 3-3 3M14 7l3 3"/>',
        'pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'heart'     => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'bulb'      => '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/>',
        'bolt'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
        'handshake' => '<path d="m11 17 2 2a1 1 0 0 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 0 0 3-3l-3.9-3.9a2 2 0 0 0-2.8 0l-.6.6a2 2 0 0 1-2.8 0l-1-1a2 2 0 0 1 0-2.8l1.5-1.5a4 4 0 0 1 3-1.1l3.4.2"/><path d="M3 12v-2a4 4 0 0 1 4-4h1"/>',
        'camera'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'video'     => '<rect x="2" y="5" width="15" height="14" rx="2"/><path d="m17 9 5-3v12l-5-3z"/>',
        'pen'       => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 13z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'scale'     => '<path d="M12 3v18M5 7l-3 6h6zM19 7l-3 6h6zM5 7l7-2 7 2M4 21h16"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9"/><path d="M9 21v-6h6v6"/>',
        'sofa'      => '<path d="M5 11V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4"/><path d="M3 13a2 2 0 0 1 2 2v2h14v-2a2 2 0 0 1 2-2 2 2 0 0 1 2 2v5H1v-5a2 2 0 0 1 2-2z"/>',
        'tree'      => '<path d="M12 22v-7M9 9a3 3 0 1 1 6 0M7 13a3 3 0 1 1 3-4M17 13a3 3 0 1 0-3-4M8 13a3 3 0 1 0 8 0"/>',
        'building'  => '<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
        'car'       => '<path d="M5 17H3v-5l2-5h14l2 5v5h-2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M9 17h6"/>',
        'flame'     => '<path d="M12 2c1 4 5 5 5 9a5 5 0 0 1-10 0c0-1 .5-2 1-3 .5 2 2 2 2 0 0-2-1-3 2-6z"/>',
        'plug'      => '<path d="M9 2v6M15 2v6M6 8h12v3a6 6 0 0 1-12 0zM12 17v5"/>',
        'star'      => '<path d="M12 2l3 6.5 7 .7-5.2 4.7 1.5 6.9L12 17l-6.3 3.8 1.5-6.9L2 9.2l7-.7z"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'email'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone'     => '<path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/>',
        'trash'     => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6M10 11v6M14 11v6"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
    ];
    $body = $p[$name] ?? '';
    if (!$body) return '';
    return '<svg class="pp-ic" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}

// ── Živý kurz mien (EUR → CZK, USD) ─────────────────────────────────────
// Načíta sa raz za 12 h a uloží do transientu; fallback na rozumné hodnoty.
function pp_fx_rates() {
    $cached = get_transient('pp_fx_rates');
    if (is_array($cached) && !empty($cached['CZK'])) return $cached;

    $fallback = [
        'CZK'  => (float) (function_exists('get_theme_mod') ? get_theme_mod('zc_czk_rate', 25.2) : 25.2),
        'USD'  => 1.08,
        'date' => '',
        'live' => false,
    ];

    $resp = wp_remote_get('https://api.frankfurter.app/latest?from=EUR&to=CZK,USD', ['timeout' => 6]);
    if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
        set_transient('pp_fx_rates', $fallback, 2 * HOUR_IN_SECONDS); // skús znova o 2 h
        return $fallback;
    }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['rates']['CZK'])) {
        set_transient('pp_fx_rates', $fallback, 2 * HOUR_IN_SECONDS);
        return $fallback;
    }
    $rates = [
        'CZK'  => (float) $data['rates']['CZK'],
        'USD'  => (float) ($data['rates']['USD'] ?? $fallback['USD']),
        'date' => sanitize_text_field($data['date'] ?? ''),
        'live' => true,
    ];
    set_transient('pp_fx_rates', $rates, 12 * HOUR_IN_SECONDS);
    return $rates;
}

// ── Zdieľané pomocné funkcie pre ponuky ─────────────────────────────────

// Stav predaja (nad rámec typu predaj/prenájom): aktívne / rezervované / predané
function pp_sale_states() {
    return [
        ''            => '',
        'rezervovane' => 'Rezervované',
        'predane'     => 'Predané',
    ];
}
function pp_sale_state($pid) {
    $s = get_post_meta($pid, '_property_stav_predaja', true);
    return isset(pp_sale_states()[$s]) ? $s : '';
}
function pp_sale_badge($state) {
    if ($state === 'rezervovane') return ['label' => 'Rezervované', 'bg' => '#C6902B', 'fg' => '#fff'];
    if ($state === 'predane')     return ['label' => 'Predané',     'bg' => '#7A7068', 'fg' => '#fff'];
    return null;
}

// „NOVÉ" — ponuka publikovaná za posledných 7 dní
function pp_is_new($pid) {
    $t = get_post_time('U', true, $pid);
    return $t && (time() - $t) < 7 * DAY_IN_SECONDS;
}

// Číslo z ceny (odstráni € a medzery)
function pp_price_num($raw) {
    $n = preg_replace('/[^0-9]/', '', (string) $raw);
    return $n !== '' ? (int) $n : 0;
}
// Pekné formátovanie ceny s medzerami + €
function pp_price_fmt($raw) {
    $n = pp_price_num($raw);
    if ($n) return number_format($n, 0, ',', ' ') . ' €';
    $t = trim((string) $raw);
    return $t !== '' ? $t : 'Cena dohodou';
}
// Cena za m² (ak je cena aj plocha číselná)
function pp_price_per_m2($cena_raw, $plocha_raw) {
    $cena   = pp_price_num($cena_raw);
    $plocha = (float) str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) $plocha_raw));
    if ($cena > 0 && $plocha > 0) {
        return number_format($cena / $plocha, 0, ',', ' ') . ' €/m²';
    }
    return '';
}

// ── Log aktivity ────────────────────────────────────────────────────────
function pp_log($action, $pid = 0, $extra = '') {
    $log = get_option('pp_activity_log', []);
    if (!is_array($log)) $log = [];
    $user = wp_get_current_user();
    array_unshift($log, [
        'time'   => current_time('mysql'),
        'user'   => $user ? $user->display_name : 'systém',
        'action' => $action,
        'pid'    => (int) $pid,
        'title'  => $pid ? get_the_title($pid) : '',
        'extra'  => $extra,
    ]);
    update_option('pp_activity_log', array_slice($log, 0, 120));
}

// AJAX: rýchle prepnutie stavu ponuky z listu v paneli
add_action('wp_ajax_pp_quick_status', function() {
    check_ajax_referer('pp_quick_status', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $pid = intval($_POST['id'] ?? 0);
    $st  = sanitize_key($_POST['status'] ?? '');
    if (!isset(pp_sale_states()[$st])) $st = '';
    $post = get_post($pid);
    if (!$post || $post->post_type !== 'property') wp_send_json_error(['message' => 'Neplatná ponuka.']);
    if (!current_user_can('edit_post', $pid)) wp_send_json_error(['message' => 'Bez oprávnenia.']);
    update_post_meta($pid, '_property_stav_predaja', $st);
    pp_log('Zmena stavu na „' . (pp_sale_states()[$st] ?: 'Aktívna') . '"', $pid);
    wp_send_json_success(['status' => $st]);
});

// AJAX: hromadné akcie (viac ponúk naraz)
add_action('wp_ajax_pp_bulk', function() {
    check_ajax_referer('pp_bulk', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));
    $op  = sanitize_key($_POST['op'] ?? '');
    if (!$ids) wp_send_json_error(['message' => 'Nič nie je označené.']);
    $done = 0;
    foreach ($ids as $pid) {
        $post = get_post($pid);
        if (!$post || $post->post_type !== 'property') continue;
        if ($op === 'delete') {
            if (current_user_can('delete_post', $pid)) { pp_log('Hromadne zmazaná ponuka', $pid); wp_delete_post($pid, true); $done++; }
        } elseif (in_array($op, ['', 'rezervovane', 'predane'], true)) {
            if (current_user_can('edit_post', $pid)) { update_post_meta($pid, '_property_stav_predaja', $op); $done++; }
        }
    }
    if ($op !== 'delete') pp_log('Hromadná zmena stavu (' . $done . ' ponúk)');
    wp_send_json_success(['done' => $done]);
});

// Počítadlo zobrazení — bezpečné zvýšenie (raz za reláciu prehliadača)
function pp_bump_views($pid) {
    if (is_admin() || !is_singular('property')) return;
    if (current_user_can('edit_posts')) return; // nezapočítavaj makléra
    $ck = 'ppseen_' . $pid;
    if (!empty($_COOKIE[$ck])) return;
    $views = (int) get_post_meta($pid, '_property_views', true);
    update_post_meta($pid, '_property_views', $views + 1);
    @setcookie($ck, '1', time() + DAY_IN_SECONDS, defined('COOKIEPATH') ? COOKIEPATH : '/');
}

/* ───────────────────────── Maklérka pri ponuke ─────────────────────────
   Ponuku môže do systému zapísať ktokoľvek (napr. webmaster cez panel),
   ale na webe má byť vždy podpísaná maklérka. Preto sa autor príspevku
   berie až ako posledná možnosť a všetky údaje sa ťahajú z jedného
   zdroja – nemôže sa stať, že sedí meno, ale telefón je cudzí.        */

/** Zoznam používateľov, ktorí sa dajú priradiť ako maklér/ka. */
function pp_agent_candidates() {
    $roles = ['administrator', 'editor', 'author'];
    if (defined('PP_AGENT_ROLE')) $roles[] = PP_AGENT_ROLE;
    return get_users(['role__in' => $roles, 'orderby' => 'display_name']);
}

/** Predvolená maklérka – z Prispôsobiť, inak prvý účet s rolou makléra. */
function pp_default_agent_id() {
    $set = (int) get_theme_mod('zc_agent_user', 0);
    if ($set && get_userdata($set)) return $set;

    if (defined('PP_AGENT_ROLE')) {
        $found = get_users(['role' => PP_AGENT_ROLE, 'number' => 1, 'fields' => 'ID']);
        if ($found) return (int) $found[0];
    }
    return 0;
}

/** Koho ukázať pri konkrétnej ponuke. */
function pp_agent_id_for($post_id) {
    $id = (int) get_post_meta($post_id, '_property_agent_id', true);
    if ($id && get_userdata($id)) return $id;

    $def = pp_default_agent_id();
    if ($def) return $def;

    return (int) get_post_field('post_author', $post_id);
}

/**
 * Kontaktné údaje maklérky pre ponuku.
 * Poradie: jej WP profil → nastavenie v Prispôsobiť → prázdne.
 */
function pp_agent_data($post_id) {
    $uid  = pp_agent_id_for($post_id);
    $user = $uid ? get_userdata($uid) : null;
    $mod  = function ($k, $d = '') {
        return function_exists('zc_agent') ? zc_agent($k, $d) : $d;
    };

    $name = $user ? $user->display_name : '';
    if (!$name) $name = $mod('name', 'Realitná maklérka');

    return [
        'id'    => $uid,
        'name'  => $name,
        'title' => get_user_meta($uid, 'property_title', true) ?: $mod('title', 'Realitná maklérka'),
        'phone' => get_user_meta($uid, 'property_phone', true) ?: $mod('phone', ''),
        'wa'    => preg_replace('/[^0-9]/', '',
                    get_user_meta($uid, 'property_whatsapp', true) ?: $mod('wa', '')),
        'email' => get_user_meta($uid, 'property_email', true)
                    ?: ($mod('email', '') ?: ($user ? $user->user_email : '')),
        'photo' => (int) get_user_meta($uid, 'property_photo_id', true),
    ];
}

/**
 * Fotka maklérky. Keď nemá vlastnú v profile, použije sa portrét
 * z Prispôsobiť – prázdny krúžok s Gravatarom nevyzerá dobre.
 */
function pp_agent_photo_html($agent, $size = 144) {
    if (!empty($agent['photo'])) {
        return wp_get_attachment_image($agent['photo'], 'thumbnail', false,
            ['alt' => esc_attr($agent['name'])]);
    }
    if (function_exists('zc_photo')) {
        $url = zc_photo('card') ?: zc_photo('portrait');
        if ($url) {
            return '<img src="' . esc_url($url) . '" alt="' . esc_attr($agent['name']) . '" loading="lazy">';
        }
    }
    return get_avatar($agent['id'], $size);
}

/* ── Doplnok pri kontakte na ponuke ──────────────────────────────────────
 * Do poľa sa dá vložiť shortcode (napr. [porovnanie]) alebo vlastný text.
 * Keď je pole prázdne, kontaktný formulár ostáva cez celú šírku.
 */

/** Uloženie – bežnému účtu bez práva na HTML necháme len bezpečné značky. */
function pp_cta_extra_sanitize($raw) {
    $raw = is_string($raw) ? trim($raw) : '';
    if ($raw === '') return '';
    return current_user_can('unfiltered_html') ? $raw : wp_kses_post($raw);
}

/** Hotový obsah na výpis; prázdny reťazec = doplnok sa nezobrazí. */
function pp_cta_extra_html($post_id) {
    $raw = get_post_meta($post_id, '_property_cta_extra', true);
    if (!is_string($raw) || trim($raw) === '') return '';
    // Rovnaké poradie ako pri obsahu stránky – odseky nerozbijú shortcode
    $html = do_shortcode(shortcode_unautop(wpautop(trim($raw))));
    return trim($html) === '' ? '' : $html;
}

/** Popis poľa – rovnaký vo wp-admin aj v realitnom paneli. */
function pp_cta_extra_hint() {
    return 'Vlož shortcode (napr. [property_carousel]) alebo vlastný text. '
         . 'Keď je pole prázdne, kontaktný formulár ostane cez celú šírku ako doteraz.';
}
