<?php
/**
 * Správa ebooku – zdieľané UI pre wp-admin aj realitný panel.
 */
defined('ABSPATH') || exit;

// ── Uloženie z POST (spoločné pre obe umiestnenia) ──────────────────────────
function zc_ebook_save_from_post() {
    if (!isset($_POST['zceb_save']) || !current_user_can('manage_options')) return false;
    if (!isset($_POST['zceb_nonce']) || !wp_verify_nonce($_POST['zceb_nonce'], 'zceb_save')) return false;

    update_option('zc_ebook_enable',   empty($_POST['enable']) ? '' : '1');
    update_option('zc_ebook_title',    sanitize_text_field($_POST['title'] ?? ''));
    update_option('zc_ebook_menu',     sanitize_text_field($_POST['menu'] ?? ''));
    update_option('zc_ebook_headline', sanitize_text_field($_POST['headline'] ?? ''));
    update_option('zc_ebook_subtext',  sanitize_textarea_field($_POST['subtext'] ?? ''));
    update_option('zc_ebook_button',   sanitize_text_field($_POST['button'] ?? ''));
    update_option('zc_ebook_question', sanitize_text_field($_POST['question'] ?? ''));
    update_option('zc_ebook_options',  sanitize_textarea_field($_POST['options'] ?? ''));
    update_option('zc_ebook_pdf',      esc_url_raw(trim($_POST['pdf'] ?? '')));
    update_option('zc_ebook_cover',    esc_url_raw(trim($_POST['cover'] ?? '')));
    return true;
}

// ── Zdieľané UI (vracia HTML) ───────────────────────────────────────────────
// $ctx: 'admin' | 'panel' – iba drobné rozdiely v obale.
function zc_ebook_render_manager($ctx = 'admin') {
    $saved = zc_ebook_save_from_post();

    $on       = zc_ebook_enabled();
    $title    = zc_ebook_get('title');
    $menu     = zc_ebook_get('menu');
    $headline = zc_ebook_get('headline');
    $subtext  = zc_ebook_get('subtext');
    $button   = zc_ebook_get('button');
    $question = zc_ebook_get('question');
    $options  = zc_ebook_get('options');
    $pdf      = zc_ebook_get('pdf');
    $cover    = zc_ebook_get('cover');

    ob_start(); ?>
    <div class="zceb">
        <?php if ($saved): ?><div class="zceb-toast">✓ Uložené</div><?php endif; ?>

        <form method="post" class="zceb-form">
            <?php wp_nonce_field('zceb_save', 'zceb_nonce'); ?>

            <!-- Hlavička -->
            <div class="zceb-head">
                <div>
                    <h2>PDF Ebook</h2>
                    <p>Lead-magnet: pás + modal formulár. Zbiera kontakty, prihlasuje na newsletter a posiela PDF.</p>
                </div>
                <label class="zceb-switch" title="Zapnúť / vypnúť ebook">
                    <input type="checkbox" name="enable" value="1" <?php checked($on, true); ?>>
                    <span class="zceb-track"><span class="zceb-thumb"></span></span>
                    <span class="zceb-switch-lbl"><?php echo $on ? 'Zapnutý' : 'Vypnutý'; ?></span>
                </label>
            </div>

            <div class="zceb-grid">
                <!-- Ľavý stĺpec: obsah -->
                <div class="zceb-col">
                    <div class="zceb-card">
                        <div class="zceb-card-t">Texty</div>
                        <label class="zceb-f">Názov ebooku <small>(nadpis modalu + do e-mailu)</small>
                            <input type="text" name="title" value="<?php echo esc_attr($title); ?>">
                        </label>
                        <label class="zceb-f">Nadpis v páse
                            <input type="text" name="headline" value="<?php echo esc_attr($headline); ?>">
                        </label>
                        <label class="zceb-f">Podnadpis / popis
                            <textarea name="subtext" rows="2"><?php echo esc_textarea($subtext); ?></textarea>
                        </label>
                        <div class="zceb-row2">
                            <label class="zceb-f">Text tlačidla
                                <input type="text" name="button" value="<?php echo esc_attr($button); ?>">
                            </label>
                            <label class="zceb-f">Názov položky v menu
                                <input type="text" name="menu" value="<?php echo esc_attr($menu); ?>">
                            </label>
                        </div>
                    </div>

                    <div class="zceb-card">
                        <div class="zceb-card-t">Otázka vo formulári <small>(voliteľné)</small></div>
                        <label class="zceb-f">Otázka
                            <input type="text" name="question" value="<?php echo esc_attr($question); ?>">
                        </label>
                        <label class="zceb-f">Možnosti odpovede <small>(každá na nový riadok)</small>
                            <textarea name="options" rows="4"><?php echo esc_textarea($options); ?></textarea>
                        </label>
                    </div>
                </div>

                <!-- Pravý stĺpec: súbory + náhľad -->
                <div class="zceb-col">
                    <div class="zceb-card">
                        <div class="zceb-card-t">Súbory</div>
                        <label class="zceb-f">PDF súbor
                            <div class="zceb-file">
                                <input type="url" name="pdf" id="zcebPdf" value="<?php echo esc_attr($pdf); ?>" placeholder="https://…/ebook.pdf">
                                <button type="button" class="zceb-btn-ghost" data-zceb-pick="zcebPdf" data-type="application/pdf">Vybrať</button>
                            </div>
                            <?php if ($pdf): ?><a href="<?php echo esc_url($pdf); ?>" target="_blank" class="zceb-hint">↗ otvoriť aktuálne PDF</a><?php endif; ?>
                        </label>
                        <label class="zceb-f">Obálka ebooku (obrázok)
                            <div class="zceb-file">
                                <input type="url" name="cover" id="zcebCover" value="<?php echo esc_attr($cover); ?>" placeholder="https://…/obalka.jpg">
                                <button type="button" class="zceb-btn-ghost" data-zceb-pick="zcebCover" data-type="image">Vybrať</button>
                            </div>
                            <div id="zcebCoverPrev" class="zceb-cover-prev"><?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt=""><?php endif; ?></div>
                        </label>
                    </div>

                    <div class="zceb-card zceb-preview-card">
                        <div class="zceb-card-t">Náhľad pásu</div>
                        <div class="zceb-preview">
                            <div class="zceb-prev-cover"><?php if ($cover): ?><img src="<?php echo esc_url($cover); ?>" alt=""><?php else: ?><span>obálka</span><?php endif; ?></div>
                            <div class="zceb-prev-body">
                                <span class="zceb-prev-tag">PDF ZDARMA</span>
                                <div class="zceb-prev-h"><?php echo esc_html($headline ?: 'Nadpis v páse'); ?></div>
                                <div class="zceb-prev-sub"><?php echo esc_html($subtext); ?></div>
                                <span class="zceb-prev-btn"><?php echo esc_html($button ?: 'Stiahnuť PDF'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="zceb-foot">
                <button type="submit" name="zceb_save" value="1" class="zceb-btn-primary">Uložiť ebook</button>
                <span class="zceb-shortcode">Umiestnenie na stránke: <code>[zc_ebook]</code> — už je pod kontaktnými formulármi a na Odhade. Tlačidlo je aj v hero a v menu.</span>
            </div>
        </form>
    </div>

    <style>
    .zceb{--g:#B8A47A;--gd:#7C5E33;--dk:#1C1A18;--ln:#E6DFD2;--cream:#FBF8F2;max-width:1080px;font-family:-apple-system,'Segoe UI',Roboto,sans-serif;color:#2C2825}
    .zceb *{box-sizing:border-box}
    .zceb-toast{background:#e9f9ee;border:1px solid #a9e2ba;color:#15803d;padding:11px 16px;border-radius:10px;margin:0 0 16px;font-size:14px;font-weight:600}
    .zceb-head{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;background:linear-gradient(120deg,#1C1A18,#2A2620);color:#fff;border-radius:16px;padding:22px 24px;margin-bottom:18px;flex-wrap:wrap}
    .zceb-head h2{margin:0 0 4px;font-size:20px;color:#fff}
    .zceb-head p{margin:0;font-size:13px;color:rgba(255,255,255,.72);max-width:52ch;line-height:1.5}
    .zceb-switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer;flex-shrink:0}
    .zceb-switch input{position:absolute;opacity:0;width:0;height:0}
    .zceb-track{width:52px;height:30px;background:rgba(255,255,255,.25);border-radius:50px;position:relative;transition:background .2s;flex-shrink:0}
    .zceb-thumb{position:absolute;top:3px;left:3px;width:24px;height:24px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 2px 6px rgba(0,0,0,.3)}
    .zceb-switch input:checked+.zceb-track{background:var(--g)}
    .zceb-switch input:checked+.zceb-track .zceb-thumb{transform:translateX(22px)}
    .zceb-switch-lbl{font-size:13px;font-weight:700;color:#fff;min-width:56px}
    .zceb-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .zceb-col{display:flex;flex-direction:column;gap:18px;min-width:0}
    .zceb-card{background:#fff;border:1px solid var(--ln);border-radius:14px;padding:18px 20px;box-shadow:0 2px 14px rgba(40,32,20,.05)}
    .zceb-card-t{font-size:12px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--gd);margin-bottom:14px}
    .zceb-card-t small{font-weight:400;text-transform:none;letter-spacing:0;color:#9a9188}
    .zceb-f{display:block;font-size:13px;font-weight:600;color:var(--dk);margin-bottom:14px}
    .zceb-f:last-child{margin-bottom:0}
    .zceb-f small{font-weight:400;color:#9a9188}
    .zceb-f input,.zceb-f textarea{display:block;width:100%;margin-top:6px;padding:10px 12px;border:1.5px solid var(--ln);border-radius:9px;font-size:14px;font-family:inherit;color:#2C2825;background:#fff}
    .zceb-f input:focus,.zceb-f textarea:focus{outline:none;border-color:var(--g);box-shadow:0 0 0 3px rgba(184,164,122,.18)}
    .zceb-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .zceb-file{display:flex;gap:8px;margin-top:6px}
    .zceb-file input{margin-top:0;flex:1}
    .zceb-btn-ghost{white-space:nowrap;flex-shrink:0;background:var(--cream);border:1.5px solid var(--ln);color:var(--gd);font-weight:700;font-size:13px;padding:0 16px;border-radius:9px;cursor:pointer;transition:all .15s}
    .zceb-btn-ghost:hover{border-color:var(--g);background:#fff}
    .zceb-hint{display:inline-block;margin-top:8px;font-size:12px;color:var(--gd);text-decoration:none}
    .zceb-hint:hover{text-decoration:underline}
    .zceb-cover-prev{margin-top:10px}
    .zceb-cover-prev img{max-width:130px;border-radius:8px;box-shadow:0 6px 18px rgba(40,32,20,.18)}
    .zceb-preview-card{background:linear-gradient(120deg,#ECE1CD,#E3D5BC);border-color:rgba(155,134,96,.3)}
    .zceb-preview{display:flex;gap:16px;align-items:center}
    .zceb-prev-cover{width:70px;height:96px;border-radius:6px;flex-shrink:0;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 8px 20px rgba(60,48,28,.28);transform:rotate(-3deg)}
    .zceb-prev-cover img{width:100%;height:100%;object-fit:cover}
    .zceb-prev-cover span{font-size:10px;color:#b3a88f}
    .zceb-prev-body{min-width:0}
    .zceb-prev-tag{display:inline-block;font-size:9px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--gd);background:rgba(124,94,51,.12);padding:3px 9px;border-radius:50px;margin-bottom:6px}
    .zceb-prev-h{font-size:16px;font-weight:800;color:var(--dk);line-height:1.2}
    .zceb-prev-sub{font-size:12px;color:#5A5044;margin:4px 0 8px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .zceb-prev-btn{display:inline-block;background:var(--g);color:#1C1A18;font-weight:700;font-size:11px;padding:7px 14px;border-radius:7px}
    .zceb-foot{display:flex;align-items:center;gap:16px;margin-top:18px;flex-wrap:wrap}
    .zceb-btn-primary{background:var(--g);color:#1C1A18;font-weight:800;font-size:14px;border:none;padding:13px 30px;border-radius:10px;cursor:pointer;box-shadow:0 4px 16px rgba(184,164,122,.35);transition:all .18s}
    .zceb-btn-primary:hover{background:var(--gd);color:#fff;transform:translateY(-1px)}
    .zceb-shortcode{font-size:12px;color:#8a8178;line-height:1.5}
    .zceb-shortcode code{background:#f1ece2;padding:2px 7px;border-radius:5px;color:var(--gd);font-weight:700}
    @media(max-width:820px){
        .zceb-grid{grid-template-columns:1fr}
        .zceb-head{flex-direction:column;align-items:stretch}
        .zceb-row2{grid-template-columns:1fr}
    }
    </style>
    <script>
    (function(){
        if(typeof jQuery==='undefined'||!window.wp||!wp.media){return;}
        document.querySelectorAll('[data-zceb-pick]').forEach(function(btn){
            btn.addEventListener('click',function(){
                var target=btn.getAttribute('data-zceb-pick');
                var type=btn.getAttribute('data-type');
                var lib=(type==='image')?{type:'image'}:{type:type};
                var frame=wp.media({title:'Vyber súbor',button:{text:'Použiť'},multiple:false,library:lib});
                frame.on('select',function(){
                    var a=frame.state().get('selection').first().toJSON();
                    var inp=document.getElementById(target); if(inp)inp.value=a.url;
                    if(target==='zcebCover'){var p=document.getElementById('zcebCoverPrev');if(p)p.innerHTML='<img src="'+a.url+'" alt="">';}
                });
                frame.open();
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ── wp-admin: samostatná stránka ────────────────────────────────────────────
add_action('admin_menu', function () {
    $hook = add_menu_page('Ebook (PDF lead-magnet)', 'Ebook', 'manage_options', 'zc-ebook', 'zc_ebook_admin_page', 'dashicons-book-alt', 26);
    add_action('load-' . $hook, function () { wp_enqueue_media(); });
});

function zc_ebook_admin_page() {
    echo '<div class="wrap" style="max-width:1120px">';
    echo zc_ebook_render_manager('admin');
    echo '</div>';
}

// ── Realitný panel: property-pro zavolá panel_ebook(), ak existuje ──────────
if (!function_exists('panel_ebook')) {
    function panel_ebook() {
        return zc_ebook_render_manager('panel');
    }
}
