<?php
/**
 * Generátor QR kódu (byte mode, úroveň korekcie M, verzie 1–10).
 * Vlastná implementácia podľa ISO/IEC 18004 – QR sa vykreslí priamo na tomto
 * serveri ako SVG, takže tajný kľúč 2FA nikdy neopustí web (žiadne externé
 * QR generátory). Nevyžaduje GD ani žiadnu knižnicu.
 */
defined('ABSPATH') || exit;

// Počet dátových codewords a štruktúra blokov pre úroveň M (verzie 1–10)
// [ec_codewords_per_block, [ [pocet_blokov, dat_codewords_v_bloku], ... ] ]
function zc2fa_qr_blocks_m($version) {
    $t = [
        1  => [10, [[1, 16]]],
        2  => [16, [[1, 28]]],
        3  => [26, [[1, 44]]],
        4  => [18, [[2, 32]]],
        5  => [24, [[2, 43]]],
        6  => [16, [[4, 27]]],
        7  => [18, [[4, 31]]],
        8  => [22, [[2, 38], [2, 39]]],
        9  => [22, [[3, 36], [2, 37]]],
        10 => [26, [[4, 43], [1, 44]]],
    ];
    return $t[$version] ?? null;
}

// Pozície zarovnávacích vzorov
function zc2fa_qr_align_positions($version) {
    $t = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];
    return $t[$version] ?? [];
}

// Počet doplnkových bitov na konci (remainder bits)
function zc2fa_qr_remainder_bits($version) {
    if ($version === 1) return 0;
    if ($version >= 2 && $version <= 6) return 7;
    return 0; // verzie 7–13
}

/* ── Galoisovo teleso GF(256) pre Reed-Solomon ── */
function &zc2fa_qr_gf() {
    static $gf = null;
    if ($gf === null) {
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) $x ^= 0x11D; // generátorový polynóm
        }
        for ($i = 255; $i < 512; $i++) $exp[$i] = $exp[$i - 255];
        $gf = ['exp' => $exp, 'log' => $log];
    }
    return $gf;
}

function zc2fa_qr_gf_mul($a, $b) {
    if ($a === 0 || $b === 0) return 0;
    $gf = &zc2fa_qr_gf();
    return $gf['exp'][$gf['log'][$a] + $gf['log'][$b]];
}

// Generátorový polynóm pre daný počet EC codewords
function zc2fa_qr_rs_poly($ec_len) {
    $poly = [1];
    $gf = &zc2fa_qr_gf();
    for ($i = 0; $i < $ec_len; $i++) {
        $next = array_fill(0, count($poly) + 1, 0);
        foreach ($poly as $j => $coef) {
            $next[$j]     ^= zc2fa_qr_gf_mul($coef, $gf['exp'][$i]);
            $next[$j + 1] ^= $coef;
        }
        $poly = $next;
    }
    // Koeficienty potrebujeme zostupne (poly[0] = vedúci člen = 1)
    return array_reverse($poly);
}

// Vypočíta EC codewords pre jeden blok
function zc2fa_qr_rs_encode($data, $ec_len) {
    $gen = zc2fa_qr_rs_poly($ec_len);
    $res = array_fill(0, $ec_len, 0);
    foreach ($data as $byte) {
        $factor = $byte ^ $res[0];
        array_shift($res);
        $res[] = 0;
        if ($factor !== 0) {
            foreach ($gen as $i => $g) {
                if ($i === 0) continue;
                $res[$i - 1] ^= zc2fa_qr_gf_mul($g, $factor);
            }
        }
    }
    return $res;
}

/* ── Zostavenie dátového toku ── */
function zc2fa_qr_build_data($text, $version) {
    [$ec_per_block, $groups] = zc2fa_qr_blocks_m($version);
    $total_data = 0;
    foreach ($groups as [$cnt, $len]) $total_data += $cnt * $len;

    $bits = '';
    $bits .= '0100'; // byte mode
    $len_bits = ($version <= 9) ? 8 : 16;
    $bits .= str_pad(decbin(strlen($text)), $len_bits, '0', STR_PAD_LEFT);
    for ($i = 0, $n = strlen($text); $i < $n; $i++) {
        $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
    }
    // Terminátor (max 4 nuly)
    $cap = $total_data * 8;
    $bits .= str_repeat('0', min(4, max(0, $cap - strlen($bits))));
    // Doplnenie na celé bajty
    if (strlen($bits) % 8) $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
    // Výplňové bajty
    $pad = ['11101100', '00010001'];
    $i = 0;
    while (strlen($bits) < $cap) { $bits .= $pad[$i % 2]; $i++; }

    // Rozdelenie do blokov
    $codewords = [];
    foreach (str_split($bits, 8) as $b) $codewords[] = bindec($b);

    $blocks = [];
    $ecblocks = [];
    $pos = 0;
    foreach ($groups as [$cnt, $len]) {
        for ($b = 0; $b < $cnt; $b++) {
            $block = array_slice($codewords, $pos, $len);
            $pos += $len;
            $blocks[] = $block;
            $ecblocks[] = zc2fa_qr_rs_encode($block, $ec_per_block);
        }
    }

    // Prekladanie (interleaving)
    $out = '';
    $maxlen = 0;
    foreach ($blocks as $b) $maxlen = max($maxlen, count($b));
    for ($i = 0; $i < $maxlen; $i++) {
        foreach ($blocks as $b) {
            if (isset($b[$i])) $out .= str_pad(decbin($b[$i]), 8, '0', STR_PAD_LEFT);
        }
    }
    for ($i = 0; $i < $ec_per_block; $i++) {
        foreach ($ecblocks as $b) {
            if (isset($b[$i])) $out .= str_pad(decbin($b[$i]), 8, '0', STR_PAD_LEFT);
        }
    }
    $out .= str_repeat('0', zc2fa_qr_remainder_bits($version));
    return $out;
}

/* ── Matica modulov ── */
function zc2fa_qr_matrix($version) {
    $size = 17 + 4 * $version;
    $m = array_fill(0, $size, array_fill(0, $size, null)); // null = voľné
    return [$m, $size];
}

function zc2fa_qr_place_function_patterns(&$m, $size, $version) {
    // Vyhľadávacie vzory + oddeľovače
    $finder = function (&$m, $r, $c) use ($size) {
        for ($i = -1; $i <= 7; $i++) {
            for ($j = -1; $j <= 7; $j++) {
                $rr = $r + $i; $cc = $c + $j;
                if ($rr < 0 || $cc < 0 || $rr >= $size || $cc >= $size) continue;
                $on = ($i >= 0 && $i <= 6 && ($j === 0 || $j === 6))
                   || ($j >= 0 && $j <= 6 && ($i === 0 || $i === 6))
                   || ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4);
                $m[$rr][$cc] = $on ? 1 : 0;
            }
        }
    };
    $finder($m, 0, 0);
    $finder($m, 0, $size - 7);
    $finder($m, $size - 7, 0);

    // Časovacie vzory
    for ($i = 8; $i < $size - 8; $i++) {
        $m[6][$i] = ($i % 2 === 0) ? 1 : 0;
        $m[$i][6] = ($i % 2 === 0) ? 1 : 0;
    }

    // Zarovnávacie vzory
    $pos = zc2fa_qr_align_positions($version);
    foreach ($pos as $r) {
        foreach ($pos as $c) {
            // preskočiť rohy s vyhľadávacími vzormi
            if (($r === 6 && $c === 6) || ($r === 6 && $c === $size - 7) || ($r === $size - 7 && $c === 6)) continue;
            for ($i = -2; $i <= 2; $i++) {
                for ($j = -2; $j <= 2; $j++) {
                    $on = (max(abs($i), abs($j)) !== 1);
                    $m[$r + $i][$c + $j] = $on ? 1 : 0;
                }
            }
        }
    }

    // Tmavý modul
    $m[4 * $version + 9][8] = 1;

    // Rezervované miesta pre formát
    for ($i = 0; $i < 9; $i++) {
        if ($m[8][$i] === null) $m[8][$i] = 0;
        if ($m[$i][8] === null) $m[$i][8] = 0;
    }
    for ($i = 0; $i < 8; $i++) {
        if ($m[8][$size - 1 - $i] === null) $m[8][$size - 1 - $i] = 0;
        if ($m[$size - 1 - $i][8] === null) $m[$size - 1 - $i][8] = 0;
    }

    // Informácia o verzii (verzie 7+)
    if ($version >= 7) {
        $bits = zc2fa_qr_version_bits($version);
        for ($i = 0; $i < 18; $i++) {
            $bit = (int) $bits[17 - $i];
            $r = intdiv($i, 3);
            $c = $i % 3;
            $m[$r][$size - 11 + $c] = $bit;
            $m[$size - 11 + $c][$r] = $bit;
        }
    }
}

// 18-bitová informácia o verzii = 6 bitov verzia + 12 bitov BCH (gen. 0x1F25)
function zc2fa_qr_version_bits($version) {
    $rem = $version;
    for ($i = 0; $i < 12; $i++) {
        $rem = ($rem << 1) ^ (0x1F25 * (($rem >> 11) & 1));
    }
    $bits = ($version << 12) | ($rem & 0xFFF);
    return str_pad(decbin($bits), 18, '0', STR_PAD_LEFT);
}

// 15-bitová informácia o formáte = 5 bitov (úroveň M = 00 + maska)
// + 10 bitov BCH (gen. 0x537), celé XOR 0x5412
function zc2fa_qr_format_bits($mask) {
    $data = (0b00 << 3) | $mask; // úroveň korekcie M = 00
    $rem = $data;
    for ($i = 0; $i < 10; $i++) {
        $rem = ($rem << 1) ^ (0x537 * (($rem >> 9) & 1));
    }
    $bits = ((($data << 10) | ($rem & 0x3FF)) ^ 0x5412) & 0x7FFF;
    return str_pad(decbin($bits), 15, '0', STR_PAD_LEFT);
}

function zc2fa_qr_place_format(&$m, $size, $mask) {
    $str = zc2fa_qr_format_bits($mask); // index 0 = najvyšší bit (bit 14)
    // bit $i (počítané od najnižšieho) = $str[14 - $i]
    for ($i = 0; $i < 15; $i++) {
        $b = (int) $str[14 - $i];
        // Zvislá kópia pri ľavom hornom rohu / ľavom dolnom vzore
        if ($i < 6)      $m[$i][8]              = $b;
        elseif ($i < 8)  $m[$i + 1][8]          = $b;
        else             $m[$size - 15 + $i][8] = $b;
        // Vodorovná kópia pri ľavom hornom rohu / pravom hornom vzore
        if ($i < 8)      $m[8][$size - $i - 1]  = $b;
        elseif ($i < 9)  $m[8][15 - $i - 1 + 1] = $b;
        else             $m[8][15 - $i - 1]     = $b;
    }
    // Tmavý modul
    $m[$size - 8][8] = 1;
}

function zc2fa_qr_mask_fn($mask, $r, $c) {
    switch ($mask) {
        case 0: return ($r + $c) % 2 === 0;
        case 1: return $r % 2 === 0;
        case 2: return $c % 3 === 0;
        case 3: return ($r + $c) % 3 === 0;
        case 4: return (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0;
        case 5: return (($r * $c) % 2) + (($r * $c) % 3) === 0;
        case 6: return ((($r * $c) % 2) + (($r * $c) % 3)) % 2 === 0;
        case 7: return ((($r + $c) % 2) + (($r * $c) % 3)) % 2 === 0;
    }
    return false;
}

// Uloženie dát cikcakom + maskovanie
function zc2fa_qr_place_data(&$m, $size, $bits, $mask) {
    $idx = 0;
    $len = strlen($bits);
    $up = true;
    for ($col = $size - 1; $col > 0; $col -= 2) {
        if ($col === 6) $col--; // preskočiť časovací stĺpec
        for ($i = 0; $i < $size; $i++) {
            $row = $up ? ($size - 1 - $i) : $i;
            for ($k = 0; $k < 2; $k++) {
                $c = $col - $k;
                if ($m[$row][$c] !== null) continue;
                $bit = ($idx < $len) ? (int) $bits[$idx] : 0;
                $idx++;
                if (zc2fa_qr_mask_fn($mask, $row, $c)) $bit ^= 1;
                $m[$row][$c] = $bit;
            }
        }
        $up = !$up;
    }
}

// Hodnotenie masky (pravidlá 1–4 podľa normy)
function zc2fa_qr_penalty($m, $size) {
    $p = 0;
    // 1: séria 5+ rovnakých v riadku/stĺpci
    for ($i = 0; $i < $size; $i++) {
        for ($dir = 0; $dir < 2; $dir++) {
            $run = 1;
            for ($j = 1; $j < $size; $j++) {
                $a = $dir ? $m[$j][$i] : $m[$i][$j];
                $b = $dir ? $m[$j - 1][$i] : $m[$i][$j - 1];
                if ($a === $b) { $run++; }
                else { if ($run >= 5) $p += 3 + ($run - 5); $run = 1; }
            }
            if ($run >= 5) $p += 3 + ($run - 5);
        }
    }
    // 2: bloky 2×2
    for ($r = 0; $r < $size - 1; $r++) {
        for ($c = 0; $c < $size - 1; $c++) {
            $v = $m[$r][$c];
            if ($v === $m[$r][$c + 1] && $v === $m[$r + 1][$c] && $v === $m[$r + 1][$c + 1]) $p += 3;
        }
    }
    // 3: vzor 1:1:3:1:1 s prázdnou oblasťou
    $pat1 = [1,0,1,1,1,0,1,0,0,0,0];
    $pat2 = [0,0,0,0,1,0,1,1,1,0,1];
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c <= $size - 11; $c++) {
            $row = array_slice($m[$r], $c, 11);
            if ($row === $pat1 || $row === $pat2) $p += 40;
            $col = [];
            for ($k = 0; $k < 11; $k++) $col[] = $m[$c + $k][$r];
            if ($col === $pat1 || $col === $pat2) $p += 40;
        }
    }
    // 4: pomer tmavých modulov
    $dark = 0;
    foreach ($m as $row) foreach ($row as $v) if ($v === 1) $dark++;
    $pct = ($dark * 100) / ($size * $size);
    $p += 10 * (int) floor(abs($pct - 50) / 5);
    return $p;
}

/**
 * Vráti maticu QR kódu (pole 0/1) pre daný text, alebo null pri príliš dlhom texte.
 */
function zc2fa_qr_encode($text) {
    $text = (string) $text;
    $len  = strlen($text);
    // Kapacity byte mode pre úroveň M, verzie 1–10
    $cap = [1 => 14, 2 => 26, 3 => 42, 4 => 62, 5 => 84, 6 => 106, 7 => 122, 8 => 152, 9 => 180, 10 => 213];
    $version = 0;
    foreach ($cap as $v => $c) { if ($len <= $c) { $version = $v; break; } }
    if (!$version) return null;

    $bits = zc2fa_qr_build_data($text, $version);

    $best = null; $best_p = PHP_INT_MAX;
    for ($mask = 0; $mask < 8; $mask++) {
        [$m, $size] = zc2fa_qr_matrix($version);
        zc2fa_qr_place_function_patterns($m, $size, $version);
        zc2fa_qr_place_data($m, $size, $bits, $mask);
        zc2fa_qr_place_format($m, $size, $mask);
        $p = zc2fa_qr_penalty($m, $size);
        if ($p < $best_p) { $best_p = $p; $best = $m; }
    }
    return $best;
}

/**
 * Vykreslí QR ako inline SVG (ostré na každom displeji, bez GD).
 */
function zc2fa_qr_svg($text, $px = 220, $quiet = 4) {
    $m = zc2fa_qr_encode($text);
    if (!$m) return '';
    $size  = count($m);
    $total = $size + 2 * $quiet;
    $path = '';
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if ($m[$r][$c] === 1) {
                $path .= 'M' . ($c + $quiet) . ' ' . ($r + $quiet) . 'h1v1h-1z';
            }
        }
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int) $px . '" height="' . (int) $px . '"'
        . ' viewBox="0 0 ' . $total . ' ' . $total . '" shape-rendering="crispEdges" role="img"'
        . ' aria-label="QR kód na nastavenie 2FA">'
        . '<rect width="' . $total . '" height="' . $total . '" fill="#ffffff"/>'
        . '<path d="' . $path . '" fill="#000000"/></svg>';
}
