<?php
/**
 * TOTP (RFC 6238) – kompatibilné s Google Authenticator, Authy, 1Password…
 * Bez externých knižníc a bez akéhokoľvek volania na cudzie servery.
 */
defined('ABSPATH') || exit;

// ── Base32 (RFC 4648) ───────────────────────────────────────────────────────
function zc2fa_base32_alphabet() {
    return 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
}

function zc2fa_base32_encode($bin) {
    $alphabet = zc2fa_base32_alphabet();
    $out = '';
    $bits = '';
    for ($i = 0, $n = strlen($bin); $i < $n; $i++) {
        $bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
    }
    foreach (str_split($bits, 5) as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $out .= $alphabet[bindec($chunk)];
    }
    return $out;
}

function zc2fa_base32_decode($b32) {
    $alphabet = zc2fa_base32_alphabet();
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/i', '', (string) $b32));
    if ($b32 === '') return '';
    $bits = '';
    for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
        $pos = strpos($alphabet, $b32[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) < 8) break; // neúplný zvyšok zahodíme
        $out .= chr(bindec($byte));
    }
    return $out;
}

// ── Tajný kľúč ──────────────────────────────────────────────────────────────
function zc2fa_generate_secret($length = 20) {
    return zc2fa_base32_encode(random_bytes($length));
}

// ── Výpočet kódu ────────────────────────────────────────────────────────────
function zc2fa_totp_code($secret_b32, $timestamp = null, $digits = 6, $period = 30) {
    $key = zc2fa_base32_decode($secret_b32);
    if ($key === '') return '';
    $ts = ($timestamp === null) ? time() : (int) $timestamp;
    $counter = (int) floor($ts / $period);

    // 8-bajtový counter (big-endian)
    $bin = '';
    for ($i = 7; $i >= 0; $i--) {
        $bin = chr($counter & 0xFF) . $bin;
        $counter >>= 8;
    }

    $hash   = hash_hmac('sha1', $bin, $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $part   = substr($hash, $offset, 4);
    $value  = (ord($part[0]) & 0x7F) << 24 | (ord($part[1]) & 0xFF) << 16
            | (ord($part[2]) & 0xFF) << 8  | (ord($part[3]) & 0xFF);

    return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}

/**
 * Overí kód s toleranciou ±1 okno (kvôli rozdielu času na mobile/serveri).
 * Porovnanie je časovo konštantné (hash_equals) – bráni timing útokom.
 */
function zc2fa_verify_code($secret_b32, $code, $window = 2, $digits = 6, $period = 30) {
    $code = preg_replace('/\D/', '', (string) $code);
    if (strlen($code) !== $digits) return false;
    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        $expected = zc2fa_totp_code($secret_b32, $now + ($i * $period), $digits, $period);
        if ($expected !== '' && hash_equals($expected, $code)) return true;
    }
    return false;
}

// ── otpauth:// URI pre aplikáciu (na naskenovanie/ručné zadanie) ────────────
function zc2fa_otpauth_uri($secret_b32, $account, $issuer) {
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
        . '?secret=' . rawurlencode($secret_b32)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

// Tajný kľúč po štvoriciach – ľahšie sa prepisuje do mobilu
function zc2fa_format_secret($secret) {
    return trim(chunk_split((string) $secret, 4, ' '));
}
