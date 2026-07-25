<?php
/**
 * Jadro 2FA: stav používateľa, záložné kódy, obmedzenie pokusov, nastavenia.
 */
defined('ABSPATH') || exit;

const ZC2FA_META_SECRET = '_zc2fa_secret';
const ZC2FA_META_ON     = '_zc2fa_enabled';
const ZC2FA_META_BACKUP = '_zc2fa_backup';
const ZC2FA_META_SEEN   = '_zc2fa_last_activity';

// ── Nastavenia ──────────────────────────────────────────────────────────────
function zc2fa_opt($key, $default = null) {
    $defaults = [
        'idle_no2fa' => 30,   // minúty nečinnosti bez 2FA
        'idle_2fa'   => 120,  // minúty nečinnosti s 2FA
        'enforce'    => '',   // vyžadovať 2FA od správcov
    ];
    $val = get_option('zc2fa_' . $key, null);
    if ($key === 'enforce') return $val ? '1' : '';
    // Číselné hodnoty musia byť platné – prázdna alebo chybná hodnota by inak
    // znamenala nezmyselne krátky limit a predčasné odhlasovanie.
    $num = is_numeric($val) ? (int) $val : 0;
    if ($num <= 0) $num = (int) ($defaults[$key] ?? $default);
    return $num;
}

// ── Stav používateľa ────────────────────────────────────────────────────────
function zc2fa_is_enabled($user_id) {
    return (bool) get_user_meta($user_id, ZC2FA_META_ON, true)
        && (string) get_user_meta($user_id, ZC2FA_META_SECRET, true) !== '';
}

function zc2fa_get_secret($user_id) {
    return (string) get_user_meta($user_id, ZC2FA_META_SECRET, true);
}

function zc2fa_enable($user_id, $secret) {
    update_user_meta($user_id, ZC2FA_META_SECRET, $secret);
    update_user_meta($user_id, ZC2FA_META_ON, 1);
    return zc2fa_generate_backup_codes($user_id);
}

function zc2fa_disable($user_id) {
    delete_user_meta($user_id, ZC2FA_META_SECRET);
    delete_user_meta($user_id, ZC2FA_META_ON);
    delete_user_meta($user_id, ZC2FA_META_BACKUP);
}

// ── Záložné kódy (jednorazové) ──────────────────────────────────────────────
function zc2fa_generate_backup_codes($user_id, $count = 8) {
    $plain = [];
    $hashed = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(4))); // 8 znakov
        $plain[]  = $code;
        $hashed[] = wp_hash_password($code);
    }
    update_user_meta($user_id, ZC2FA_META_BACKUP, $hashed);
    return $plain; // zobrazí sa používateľovi len raz
}

function zc2fa_count_backup_codes($user_id) {
    $codes = get_user_meta($user_id, ZC2FA_META_BACKUP, true);
    return is_array($codes) ? count($codes) : 0;
}

// Skúsi kód spotrebovať; true = platný záložný kód (a odstráni sa)
function zc2fa_consume_backup_code($user_id, $code) {
    $codes = get_user_meta($user_id, ZC2FA_META_BACKUP, true);
    if (!is_array($codes) || !$codes) return false;
    $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', (string) $code)));
    if ($code === '') return false;
    foreach ($codes as $i => $hash) {
        if (wp_check_password($code, $hash)) {
            unset($codes[$i]);
            update_user_meta($user_id, ZC2FA_META_BACKUP, array_values($codes));
            return true;
        }
    }
    return false;
}

// ── Obmedzenie pokusov (brute-force na 6-ciferný kód) ───────────────────────
function zc2fa_attempt_key($user_id) {
    return 'zc2fa_try_' . (int) $user_id;
}
function zc2fa_is_locked($user_id) {
    return ((int) get_transient(zc2fa_attempt_key($user_id))) >= 5;
}
function zc2fa_note_failure($user_id) {
    $key = zc2fa_attempt_key($user_id);
    $n = (int) get_transient($key);
    set_transient($key, $n + 1, 15 * MINUTE_IN_SECONDS);
}
function zc2fa_clear_failures($user_id) {
    delete_transient(zc2fa_attempt_key($user_id));
}

// ── Overenie zadaného kódu (TOTP alebo záložný) ─────────────────────────────
function zc2fa_check_user_code($user_id, $code) {
    $code = trim((string) $code);
    if ($code === '') return false;
    if (zc2fa_verify_code(zc2fa_get_secret($user_id), $code)) return true;
    return zc2fa_consume_backup_code($user_id, $code);
}

// ── Musí mať tento používateľ 2FA? (vynútenie pre správcov) ─────────────────
function zc2fa_is_required($user_id) {
    if (!zc2fa_opt('enforce')) return false;
    return user_can($user_id, 'manage_options');
}
