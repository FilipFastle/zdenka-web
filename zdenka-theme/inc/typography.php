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

// Vlastné wp-admin stránky s wp_editor() sa vykresľujú až po admin_head.
// Editor preto zaradíme vopred, inak môže chýbať jeho vizuálna vrstva.
add_action('admin_enqueue_scripts', function () {
    $page = sanitize_key($_GET['page'] ?? '');
    if (in_array($page, ['zc-newsletter'], true)) wp_enqueue_editor();
}, 5);

/**
 * Jednotné nastavenie vizuálnych editorov na webe aj v realitnom paneli.
 * Zachováva natívny WordPress editor a jeho formát dát, takže sa nestratia
 * existujúce texty ani kompatibilita s klasickým wp-adminom.
 */
function zc_rich_editor_settings($textarea_name, $rows = 10, $args = []) {
    $args = wp_parse_args($args, [
        'media_buttons' => false,
        'compact'       => false,
    ]);

    $style_formats = [
        [
            'title' => 'Riadkovanie',
            'items' => [
                ['title' => 'Úzke (1,4)',    'selector' => 'p,li,div', 'styles' => ['line-height' => '1.4']],
                ['title' => 'Bežné (1,7)',   'selector' => 'p,li,div', 'styles' => ['line-height' => '1.7']],
                ['title' => 'Vzdušné (2,0)', 'selector' => 'p,li,div', 'styles' => ['line-height' => '2']],
            ],
        ],
        [
            'title' => 'Užitočné bloky',
            'items' => [
                [
                    'title'  => 'Úvodný zvýraznený text',
                    'block'  => 'p',
                    'styles' => ['font-size' => '1.12em', 'line-height' => '1.8', 'color' => '#4f463d'],
                ],
                [
                    'title'   => 'Zvýraznený informačný box',
                    'block'   => 'div',
                    'wrapper' => true,
                    'styles'  => [
                        'background-color' => '#f5f1ea',
                        'border-left'      => '4px solid #b8a47a',
                        'padding'          => '14px 16px',
                        'margin'           => '16px 0',
                    ],
                ],
                [
                    'title'  => 'Malý nadpis / štítok',
                    'inline' => 'span',
                    'styles' => [
                        'font-size'      => '0.78em',
                        'font-weight'    => '700',
                        'letter-spacing' => '0.08em',
                        'text-transform' => 'uppercase',
                        'color'          => '#7c5e33',
                    ],
                ],
            ],
        ],
    ];

    $toolbar1 = 'formatselect,styleselect,|,bold,italic,underline,strikethrough,forecolor,|,bullist,numlist,blockquote';
    $toolbar2 = 'alignleft,aligncenter,alignright,alignjustify,|,outdent,indent,|,link,unlink,hr,charmap,|,removeformat,undo,redo,fullscreen';
    if (!$args['compact']) {
        $toolbar2 = 'alignleft,aligncenter,alignright,alignjustify,|,bullist,numlist,outdent,indent,|,link,unlink,hr,charmap,|,removeformat,undo,redo,fullscreen';
    }

    return [
        'textarea_name'   => $textarea_name,
        'textarea_rows'   => max(4, (int) $rows),
        'media_buttons'   => (bool) $args['media_buttons'],
        'teeny'           => false,
        'drag_drop_upload'=> false,
        'quicktags'       => [
            'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close',
        ],
        'tinymce'         => [
            'toolbar1'            => $toolbar1,
            'toolbar2'            => $toolbar2,
            'block_formats'       => 'Odsek=p;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Citácia=blockquote;Predformátované=pre',
            'style_formats'       => wp_json_encode($style_formats),
            'style_formats_merge' => true,
            'browser_spellcheck'  => true,
            'paste_as_text'       => false,
            'resize'              => true,
            'statusbar'           => true,
            'menubar'             => false,
            'content_style'       => 'body{font-family:"DM Sans",system-ui,sans-serif;font-size:16px;line-height:1.75;color:#2C2825;padding:14px;max-width:none}a{color:#7C5E33}blockquote{border-left:4px solid #B8A47A;margin:18px 0;padding:4px 0 4px 16px;color:#6B6560}',
        ],
    ];
}

/**
 * Ovládanie nad editorom: výška, rýchle vloženie štruktúry, zalomenie,
 * počítadlo a stručný návod.
 *
 * Zámerne sa nemieša do toho, ako WordPress prepína Vizuálny a Textový
 * režim. Predchádzajúca verzia to robila cez !important a po pár sekundách
 * editor násilne prepla do HTML – práve preto sa TinyMCE tváril rozbito.
 */
function zc_render_editor_tools($editor_id, $args = []) {
    static $assets_printed = false;
    $args = wp_parse_args($args, [
        'template' => '',
        'label'    => 'Editor textu',
    ]);
    $editor_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $editor_id);
    if (!$editor_id) return;

    if (!$assets_printed):
        $assets_printed = true;
        ?>
        <style id="zc-editor-tools-css">
        .zce-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 8px;padding:9px 10px;background:#f8f6f1;border:1px solid #e2dace;border-radius:9px;font-family:"DM Sans",system-ui,sans-serif}
        .zce-tools-label{font-size:11px;font-weight:800;letter-spacing:.55px;text-transform:uppercase;color:#6b6560;margin-right:2px}
        .zce-tools button{min-height:34px;padding:6px 10px;border:1px solid #ddd4c7;border-radius:7px;background:#fff;color:#2c2825;font:700 11px/1.2 "DM Sans",system-ui,sans-serif;cursor:pointer;touch-action:manipulation}
        .zce-tools button:hover,.zce-tools button:focus-visible,.zce-tools button.is-active{border-color:#b8a47a;background:#f5f1ea;outline:none}
        .zce-tools-sep{width:1px;height:24px;background:#ddd4c7}
        .zce-count{margin-left:auto;font-size:11px;color:#6b6560;white-space:nowrap}
        .zce-help{position:relative}
        .zce-help summary{cursor:pointer;list-style:none;min-height:34px;display:flex;align-items:center;padding:6px 10px;border:1px solid #ddd4c7;border-radius:7px;background:#fff;font-size:11px;font-weight:700;color:#2c2825}
        .zce-help summary::-webkit-details-marker{display:none}
        .zce-help-card{position:absolute;right:0;top:calc(100% + 7px);z-index:120;width:min(330px,calc(100vw - 32px));padding:13px 15px;background:#1c1a18;color:#fff;border-radius:9px;box-shadow:0 12px 32px rgba(0,0,0,.22);font-size:12px;line-height:1.6}
        .zce-help-card strong{color:#e8dfd0}
        /* Rám okolo editora. Bez overflow:hidden a bez display – prepínanie
           Vizuálny/Text si riadi WordPress sám a nesmieme mu do toho hovoriť. */
        .zce-tools + .wp-editor-wrap{border:1px solid #ddd4c7;border-radius:9px;background:#fff}
        .zce-tools + .wp-editor-wrap .wp-editor-area{background:#fff;color:#2c2825}
        @media(max-width:768px){
            .zce-tools{align-items:stretch;gap:6px;padding:8px}
            .zce-tools-label{flex:1 0 100%;margin-bottom:1px}
            .zce-tools button,.zce-help summary{min-height:44px;padding:9px 12px;font-size:12px}
            .zce-tools-sep{display:none}
            .zce-count{order:20;flex:1 0 100%;margin:2px 0 0;text-align:right}
            .zce-help{margin-left:auto}
            .zce-help-card{position:fixed;left:16px;right:16px;top:auto;bottom:16px;width:auto;z-index:10010}
            /* Lišta nástrojov TinyMCE sa na úzkom displeji posúva do strán */
            .wp-editor-wrap .mce-toolbar-grp{overflow-x:auto;-webkit-overflow-scrolling:touch}
            .wp-editor-wrap .mce-toolbar .mce-container-body{min-width:max-content}
            .wp-editor-wrap .mce-btn button{min-width:40px;min-height:40px}
            .wp-editor-wrap .mce-listbox button{min-width:auto}
        }
        </style>
        <script id="zc-editor-tools-js">
        (function(){
            var templates={
                property:'<h2>Lokalita a okolie</h2><p>Opíšte lokalitu, dostupnosť a občiansku vybavenosť.</p><h2>Dispozícia a stav</h2><p>Opíšte rozloženie miestností, rekonštrukciu a technický stav.</p><h2>Hlavné výhody</h2><ul><li>Doplňte prvú výhodu</li><li>Doplňte druhú výhodu</li><li>Doplňte tretiu výhodu</li></ul><h2>Pre koho je ponuka vhodná</h2><p>Doplňte odporúčanie a výzvu na obhliadku.</p>',
                short:'<p><strong>Hlavná výhoda nehnuteľnosti.</strong> Stručne doplňte lokalitu, dispozíciu a stav.</p><ul><li>Výhoda 1</li><li>Výhoda 2</li></ul>',
                newsletter:'<h2>Hlavná novinka</h2><p>Doplňte krátky úvod.</p><h3>Čo je dôležité</h3><ul><li>Prvý bod</li><li>Druhý bod</li></ul><p>Na záver pridajte výzvu alebo kontakt.</p>'
            };
            // Aktívny TinyMCE pre dané pole; v textovom režime vráti null.
            function ed(id){return window.tinymce&&tinymce.get(id)&&!tinymce.get(id).isHidden()?tinymce.get(id):null}
            function ta(id){return document.getElementById(id)}
            function text(id){
                var e=ed(id),raw=e?e.getContent({format:'text'}):((ta(id)||{}).value||'');
                var d=document.createElement('div');d.innerHTML=raw;
                return (d.textContent||d.innerText||raw).replace(/\s+/g,' ').trim();
            }
            function insert(id,html,plain){
                var e=ed(id);
                if(e){e.focus();e.execCommand('mceInsertContent',false,html);e.fire('change');return}
                var t=ta(id);if(!t)return;
                var start=t.selectionStart||0,end=t.selectionEnd||0,value=t.value;
                t.value=value.slice(0,start)+(plain||html.replace(/<[^>]+>/g,''))+value.slice(end);
                t.focus();t.dispatchEvent(new Event('input',{bubbles:true}));
            }
            function height(id,px,box){
                try{localStorage.setItem('zce_height_'+id,String(px))}catch(ignore){}
                var t=ta(id);if(t)t.style.height=px+'px';
                var e=window.tinymce&&tinymce.get(id);
                if(e){
                    var iframe=e.iframeElement||document.getElementById(id+'_ifr');
                    if(iframe)iframe.style.height=px+'px';
                    try{e.theme.resizeTo(null,px)}catch(ignore){}
                }
                box.querySelectorAll('[data-zce-height]').forEach(function(b){b.classList.toggle('is-active',Number(b.dataset.zceHeight)===px)});
            }
            function update(box){
                var value=text(box.dataset.editor),words=value?value.split(/\s+/).length:0;
                var out=box.querySelector('.zce-count');
                if(out)out.textContent=words+' slov · '+value.length+' znakov';
            }
            function bind(box){
                if(box.dataset.bound)return;box.dataset.bound='1';
                var id=box.dataset.editor;
                box.querySelectorAll('[data-zce-height]').forEach(function(b){b.addEventListener('click',function(){height(id,Number(b.dataset.zceHeight),box)})});
                box.querySelectorAll('[data-zce-action]').forEach(function(b){b.addEventListener('click',function(){
                    if(b.dataset.zceAction==='break')insert(id,'<br>','\n');
                    if(b.dataset.zceAction==='template'){
                        var key=box.dataset.template||'';
                        if(!templates[key])return;
                        if(text(id)&&!confirm('Editor už obsahuje text. Vložiť vzor na aktuálnu pozíciu?'))return;
                        insert(id,templates[key],templates[key].replace(/<\/(p|h2|h3|li)>/g,'\n').replace(/<[^>]+>/g,''));
                    }
                    setTimeout(function(){update(box)},30);
                })});
                var textarea=ta(id);if(textarea)textarea.addEventListener('input',function(){update(box)});

                // Počkáme na TinyMCE, ale nikdy do neho nezasahujeme.
                // Keď sa nenačíta, ostáva textový režim – bez strašenia hláškou.
                if(window.tinymce&&tinymce.get(id)){onReady(tinymce.get(id))}
                else if(typeof jQuery!=='undefined'){
                    jQuery(document).on('tinymce-editor-init.zce',function(event,editor){
                        if(editor&&editor.id===id)onReady(editor);
                    });
                }
                function onReady(editor){
                    editor.on('keyup change SetContent Undo Redo',function(){update(box)});
                    var saved=0;try{saved=Number(localStorage.getItem('zce_height_'+id)||0)}catch(ignore){}
                    if(saved)height(id,saved,box);
                    update(box);
                }

                var initial=0;try{initial=Number(localStorage.getItem('zce_height_'+id)||0)}catch(ignore){}
                if(initial)height(id,initial,box);
                update(box);
            }
            function init(){document.querySelectorAll('[data-zce-tools]').forEach(bind)}
            if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
        })();
        </script>
        <?php
    endif;
    ?>
    <div class="zce-tools" data-zce-tools data-editor="<?php echo esc_attr($editor_id) ?>" data-template="<?php echo esc_attr($args['template']) ?>">
        <span class="zce-tools-label"><?php echo esc_html($args['label']) ?></span>
        <button type="button" data-zce-height="180">Malý</button>
        <button type="button" data-zce-height="300">Bežný</button>
        <button type="button" data-zce-height="480">Veľký</button>
        <span class="zce-tools-sep" aria-hidden="true"></span>
        <button type="button" data-zce-action="break">↵ Zalomenie</button>
        <?php if ($args['template']): ?><button type="button" data-zce-action="template">＋ Vložiť vzor</button><?php endif; ?>
        <details class="zce-help">
            <summary>Pomoc</summary>
            <div class="zce-help-card">
                <strong>Enter</strong> vytvorí nový odsek, <strong>Shift + Enter</strong> iba nový riadok.
                V ponuke <strong>Štýly</strong> nastavíte riadkovanie a zvýraznené bloky.
                Záložka <strong>Text</strong> slúži na priame HTML; bežný text píšte vo <strong>Vizuálnom</strong> režime.
            </div>
        </details>
        <span class="zce-count" aria-live="polite">0 slov · 0 znakov</span>
    </div>
    <?php
}

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
