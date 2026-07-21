<?php
defined('ABSPATH') || exit;
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
