<?php
/**
 * Riadkovanie a medzery pre text z editora.
 *
 * Nastavuje sa zvlášť pre odsek, zoznamy a každý nadpis H1–H6 v
 * Prispôsobiť → Text. Rovnaké pravidlá sa vložia aj do editora, takže
 * to, čo vidíš pri písaní, sedí s tým, čo uvidí návštevník.
 */
defined('ABSPATH') || exit;

/** Zoznam nastavení: kľúč => [popis, predvolená hodnota, min, max, krok, jednotka] */
function zc_text_fields() {
    return [
        'zc_lh_p'   => ['Odsek – riadkovanie',            1.85, 1.0, 2.6, 0.05, ''],
        'zc_mb_p'   => ['Odsek – medzera pod odsekom',      14,   0,  60, 1,    'px'],
        'zc_lh_li'  => ['Zoznam – riadkovanie',            1.75, 1.0, 2.6, 0.05, ''],
        'zc_gap_li' => ['Zoznam – medzera medzi bodmi',       7,   0,  40, 1,    'px'],
        'zc_lh_h1'  => ['Nadpis H1 – riadkovanie',         1.15, 0.9, 2.0, 0.05, ''],
        'zc_lh_h2'  => ['Nadpis H2 – riadkovanie',         1.20, 0.9, 2.0, 0.05, ''],
        'zc_lh_h3'  => ['Nadpis H3 – riadkovanie',         1.25, 0.9, 2.0, 0.05, ''],
        'zc_lh_h4'  => ['Nadpis H4 – riadkovanie',         1.30, 0.9, 2.0, 0.05, ''],
        'zc_lh_h5'  => ['Nadpis H5 – riadkovanie',         1.35, 0.9, 2.0, 0.05, ''],
        'zc_lh_h6'  => ['Nadpis H6 – riadkovanie',         1.40, 0.9, 2.0, 0.05, ''],
        'zc_mt_h'   => ['Nadpisy – medzera nad',             26,   0, 100, 1,    'px'],
        'zc_mb_h'   => ['Nadpisy – medzera pod',             10,   0,  60, 1,    'px'],
    ];
}

/** Aktuálne hodnoty (s prenosom zo starších nastavení). */
function zc_text_values() {
    $out = [];
    foreach (zc_text_fields() as $key => $f) {
        $out[$key] = (float) get_theme_mod($key, $f[1]);
    }
    // Kto mal nastavené staré spoločné hodnoty, nech o ne nepríde
    $old_lh   = get_theme_mod('zc_lh', null);
    $old_para = get_theme_mod('zc_para', null);
    $old_gap  = get_theme_mod('zc_list_gap', null);
    if ($old_lh   !== null && get_theme_mod('zc_lh_p', null)   === null) $out['zc_lh_p']   = (float) $old_lh;
    if ($old_para !== null && get_theme_mod('zc_mb_p', null)   === null) $out['zc_mb_p']   = (float) $old_para;
    if ($old_gap  !== null && get_theme_mod('zc_gap_li', null) === null) $out['zc_gap_li'] = (float) $old_gap;
    return $out;
}

/**
 * Vygeneruje pravidlá. $scopes je zoznam predpon selektorov –
 * na webe sú to obaly obsahu, v editore plocha editora.
 */
function zc_text_css($scopes) {
    $v = zc_text_values();
    $n = function ($x, $dec = 2) { return rtrim(rtrim(number_format((float) $x, $dec, '.', ''), '0'), '.'); };
    $px = function ($x) { return (int) round((float) $x) . 'px'; };

    $sel = function ($suffix) use ($scopes) {
        $parts = [];
        foreach ($scopes as $s) $parts[] = trim($s . ' ' . $suffix);
        return implode(',', $parts);
    };

    $css  = $sel('p') . '{line-height:' . $n($v['zc_lh_p']) . ';margin-bottom:' . $px($v['zc_mb_p']) . '}';
    $css .= $sel('p:last-child') . '{margin-bottom:0}';

    $css .= $sel('ul') . ',' . $sel('ol')
          . '{margin:0 0 ' . $px($v['zc_mb_p']) . ';padding-left:1.35em}';
    $css .= $sel('ul') . '{list-style:disc outside}';
    $css .= $sel('ol') . '{list-style:decimal outside}';
    $css .= $sel('li') . '{line-height:' . $n($v['zc_lh_li']) . ';margin-bottom:' . $px($v['zc_gap_li']) . '}';
    $css .= $sel('li:last-child') . '{margin-bottom:0}';
    $css .= $sel('ul ul') . ',' . $sel('ol ol')
          . '{margin-top:' . $px($v['zc_gap_li']) . ';margin-bottom:0}';

    for ($i = 1; $i <= 6; $i++) {
        $css .= $sel('h' . $i) . '{line-height:' . $n($v['zc_lh_h' . $i])
              . ';margin:' . $px($v['zc_mt_h']) . ' 0 ' . $px($v['zc_mb_h']) . '}';
    }
    $css .= $sel('h1:first-child') . ',' . $sel('h2:first-child') . ',' . $sel('h3:first-child')
          . '{margin-top:0}';

    return $css;
}

/** Obaly, v ktorých na webe žije text z editora. */
function zc_text_scopes_front() {
    return ['.pp-desc', '.zc-richtext', '.entry-content'];
}

/* ───────────────────────── Web ───────────────────────── */

add_action('wp_head', function () {
    $v   = zc_text_values();
    $lh  = rtrim(rtrim(number_format((float) $v['zc_lh_p'], 2, '.', ''), '0'), '.');
    $css = ':root{--zc-lh:' . $lh . '}' . zc_text_css(zc_text_scopes_front());
    echo '<style id="zc-text">' . $css . "</style>\n";
}, 20);

/* ───────────────────────── Editor ───────────────────────── */

/**
 * Editor dostane tie isté pravidlá.
 * Hodnoty sú premenlivé, preto ich zapíšeme do súboru v uploadoch –
 * add_editor_style() vie prijať aj úplnú adresu a funguje tak v blokovom
 * aj v klasickom editore.
 */
function zc_editor_css_path() {
    $up = wp_upload_dir();
    return [
        'dir'  => trailingslashit($up['basedir']) . 'zc-editor.css',
        'url'  => trailingslashit($up['baseurl']) . 'zc-editor.css',
    ];
}

function zc_write_editor_css() {
    $scopes = ['.editor-styles-wrapper', '.block-editor-block-list__layout', 'body#tinymce', '.mce-content-body'];
    $css    = ":root{--zc-lh:" . number_format((float) zc_text_values()['zc_lh_p'], 2, '.', '') . "}\n"
            . zc_text_css($scopes);

    $p = zc_editor_css_path();
    if (!wp_mkdir_p(dirname($p['dir']))) return false;
    return (bool) @file_put_contents($p['dir'], $css);
}

// Po uložení v Prispôsobiť sa súbor prepíše
add_action('customize_save_after', 'zc_write_editor_css');

add_action('after_setup_theme', function () {
    add_theme_support('editor-styles');

    $p = zc_editor_css_path();
    if (!file_exists($p['dir'])) zc_write_editor_css();

    if (file_exists($p['dir'])) {
        // verzia v adrese, nech editor nedrží starý súbor v pamäti
        add_editor_style($p['url'] . '?v=' . filemtime($p['dir']));
    }
}, 20);

/** Klasický editor (aj ten v realitnom paneli) – rovnaké pravidlá. */
add_filter('tiny_mce_before_init', function ($init) {
    $css = zc_text_css(['body.mce-content-body']);
    $init['content_style'] = ($init['content_style'] ?? '') . $css;
    return $init;
});

/* ───────────────────────── Nastavenie ───────────────────────── */

add_action('customize_register', function ($wpc) {
    $wpc->add_section('zc_text', [
        'title'       => 'Text – riadkovanie a medzery',
        'priority'    => 33.8,
        'description' => 'Platí pre popis ponuky a texty písané v editore. '
                       . 'To isté uvidíš aj priamo pri písaní v editore.',
    ]);

    foreach (zc_text_fields() as $key => [$label, $default, $min, $max, $step, $unit]) {
        $wpc->add_setting($key, [
            'default'           => $default,
            'sanitize_callback' => function ($v) { return (float) str_replace(',', '.', $v); },
            'transport'         => 'refresh',
        ]);
        $wpc->add_control($key, [
            'label'       => $label . ($unit ? ' (' . $unit . ')' : ''),
            'section'     => 'zc_text',
            'type'        => 'number',
            'input_attrs' => ['min' => $min, 'max' => $max, 'step' => $step],
        ]);
    }
}, 20);
