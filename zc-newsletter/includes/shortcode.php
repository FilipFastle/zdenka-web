<?php
defined('ABSPATH') || exit;

// [zc_newsletter] – kompaktný formulár bez otázky na kategóriu.
// [zc_newsletter_full] – plný formulár na samostatnej stránke Newsletter.
function zcn_newsletter_shortcode($atts) {
    $atts = shortcode_atts([
        'title'       => 'Odber noviniek',
        'description' => 'Nové ponuky priamo do vašej schránky.',
        'source'      => 'newsletter',
        'dark'        => '0',
        'show_interest'=> '0',
        'full'         => '0',
    ], $atts);

    $dark = $atts['dark'] === '1';
    $full = $atts['full'] === '1';
    $show_interest = $atts['show_interest'] === '1';
    $bg   = $dark ? '#1C1A18' : '#F2EEE8';
    $text = $dark ? '#fff'     : '#2C2825';
    $sub  = $dark ? 'rgba(255,255,255,.65)' : '#7A7068';

    wp_enqueue_script('zc-newsletter-js',
        plugin_dir_url(dirname(__FILE__)) . 'assets/newsletter.js',
        [], ZCN_VERSION, true
    );
    // Kategórie posielame do JS, nech ich okno na úpravu tém vie vykresliť
    $zcn_groups = [];
    foreach (zcn_interest_groups() as $glabel => $items) {
        $opts = [];
        foreach ($items as $value => $label) $opts[] = ['value' => $value, 'label' => $label];
        if ($opts) $zcn_groups[] = ['group' => $glabel, 'items' => $opts];
    }
    wp_localize_script('zc-newsletter-js', 'zcnData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('zcn_nonce'),
        'groups'  => $zcn_groups,
    ]);

    ob_start(); ?>
    <style>
    .zcn-form-wrap,.zcn-form-wrap *{box-sizing:border-box}
    .zcn-email-row{display:flex;gap:8px}
    .zcn-email-row input{min-width:0}
    .zcn-form-wrap input,.zcn-form-wrap select,.zcn-form-wrap button{min-height:48px}
    .zcn-picks{text-align:left;margin:2px 0 4px}
    .zcn-picks-hd{font:700 11px/1.4 'DM Sans',sans-serif;letter-spacing:.8px;text-transform:uppercase;margin-bottom:10px}
    .zcn-picks-hd span{font-weight:500;text-transform:none;letter-spacing:0;opacity:.8}
    .zcn-picks-grp{font:700 10px/1.4 'DM Sans',sans-serif;letter-spacing:1.2px;text-transform:uppercase;opacity:.75;margin:12px 0 7px}
    .zcn-picks-row{display:flex;flex-wrap:wrap;gap:7px}
    .zcn-pick{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1.5px solid;border-radius:9px;
        font:600 13.5px/1.2 'DM Sans',sans-serif;cursor:pointer;transition:border-color .15s,background .15s;min-height:auto}
    .zcn-pick input{width:16px;height:16px;min-height:auto;accent-color:#B8A47A;margin:0;flex:0 0 auto}
    .zcn-pick:hover{border-color:#B8A47A!important}
    .zcn-pick:has(input:checked){border-color:#B8A47A!important;box-shadow:inset 0 0 0 1px #B8A47A}
    .zcn-picks-note{font-size:11.5px;line-height:1.6;margin-top:11px}
    @media(max-width:560px){
        .zcn-picks-row{flex-direction:column}
        .zcn-pick{width:100%}
        .zcn-form-wrap{padding:24px 18px!important;border-radius:13px!important}
        .zcn-email-row{flex-direction:column}
        .zcn-email-row button{width:100%}
        .zcn-form-wrap input,.zcn-form-wrap select{font-size:16px!important;width:100%}
    }
    </style>
    <div class="zcn-form-wrap" style="background:<?php echo $bg ?>;border-radius:16px;padding:32px 28px;max-width:520px;margin:0 auto">
        <?php if ($atts['title']): ?>
        <h3 style="font-family:'Playfair Display',Georgia,serif;font-size:22px;color:<?php echo $text ?>;margin-bottom:8px"><?php echo esc_html($atts['title']); ?></h3>
        <?php endif; ?>
        <?php if ($atts['description']): ?>
        <p style="font-size:14px;color:<?php echo $sub ?>;margin-bottom:20px;line-height:1.7"><?php echo esc_html($atts['description']); ?></p>
        <?php endif; ?>
        <form class="zcn-form" data-source="<?php echo esc_attr($atts['source']); ?>">
            <div style="display:flex;flex-direction:column;gap:10px">
                <input type="text" name="zcn_name" placeholder="Vaše meno (nepovinné)"
                    style="padding:13px 16px;border:1.5px solid <?php echo $dark ? 'rgba(255,255,255,.2)' : '#E0D8CE' ?>;border-radius:8px;
                           font-family:'DM Sans',sans-serif;font-size:15px;background:<?php echo $dark ? 'rgba(255,255,255,.08)' : '#fff' ?>;
                           color:<?php echo $text ?>;outline:none;transition:border .2s">
                <?php if ($show_interest): ?>
                <div class="zcn-picks">
                    <div class="zcn-picks-hd" style="color:<?php echo $sub ?>">Čo vás zaujíma? <span>(môžete označiť aj viac)</span></div>
                    <?php foreach (zcn_interest_groups() as $glabel => $items): ?>
                    <div class="zcn-picks-grp" style="color:<?php echo $sub ?>"><?php echo esc_html($glabel); ?></div>
                    <div class="zcn-picks-row">
                        <?php foreach ($items as $value => $label): ?>
                        <label class="zcn-pick" style="color:<?php echo $text ?>;border-color:<?php echo $dark ? 'rgba(255,255,255,.2)' : '#E0D8CE' ?>;background:<?php echo $dark ? 'rgba(255,255,255,.06)' : '#fff' ?>">
                            <input type="checkbox" name="zcn_interest[]" value="<?php echo esc_attr($value); ?>">
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    <div class="zcn-picks-note" style="color:<?php echo $sub ?>">Nič neoznačené = pošleme vám všetko.</div>
                </div>
                <?php endif; ?>
                <div class="zcn-email-row">
                    <input type="email" name="zcn_email" placeholder="Váš e-mail *" required
                        style="flex:1;padding:13px 16px;border:1.5px solid <?php echo $dark ? 'rgba(255,255,255,.2)' : '#E0D8CE' ?>;border-radius:8px;
                               font-family:'DM Sans',sans-serif;font-size:15px;background:<?php echo $dark ? 'rgba(255,255,255,.08)' : '#fff' ?>;
                               color:<?php echo $text ?>;outline:none;transition:border .2s">
                    <button type="submit"
                        style="padding:13px 20px;background:#B8A47A;color:#1C1A18;border:none;border-radius:8px;
                               font-family:'DM Sans',sans-serif;font-size:12px;font-weight:700;letter-spacing:.8px;
                               cursor:pointer;white-space:nowrap;transition:all .2s">
                        Prihlásiť →
                    </button>
                </div>
                <?php if ($full): ?>
                <label style="display:flex;align-items:flex-start;gap:9px;font-size:12px;color:<?php echo $sub ?>;cursor:pointer;line-height:1.6;margin-top:3px">
                    <input type="checkbox" required style="min-height:auto;margin-top:3px;accent-color:#B8A47A;flex:0 0 auto">
                    <span>Súhlasím so <a href="<?php echo esc_url(home_url('/ochrana-osobnych-udajov/')) ?>" style="color:#9A8660">spracovaním osobných údajov</a> na účel zasielania newslettera. *</span>
                </label>
                <?php endif; ?>
            </div>
            <div class="zcn-msg" style="display:none;margin-top:12px;padding:12px 16px;border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif"></div>

            <p style="font-size:11px;color:<?php echo $sub ?>;margin-top:10px;line-height:1.6">
                Odoslaním súhlasíte so spracovaním e-mailovej adresy za účelom zasielania noviniek.
                Odhlásiť sa môžete kedykoľvek kliknutím na odkaz v e-maile.
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('zc_newsletter', 'zcn_newsletter_shortcode');

add_shortcode('zc_newsletter_full', function() {
    ob_start(); ?>
    <section class="zcn-page" style="background:#FCFBF8;padding:72px 20px 90px;min-height:65vh">
        <div style="max-width:760px;margin:0 auto;text-align:center">
            <div style="font:700 11px/1.2 'DM Sans',sans-serif;letter-spacing:2px;text-transform:uppercase;color:#7C5E33;margin-bottom:13px">Newsletter</div>
            <h1 style="font-family:'Playfair Display',Georgia,serif;font-size:clamp(34px,6vw,56px);line-height:1.12;color:#1C1A18;margin:0 0 15px">Ponuky a realitné tipy <em style="color:#7C5E33">priamo do e-mailu</em></h1>
            <p style="max-width:620px;margin:0 auto 34px;color:#6B6560;font:16px/1.75 'DM Sans',sans-serif">Vyberte si, čo vás zaujíma – môžete označiť aj viac možností naraz. Pri novej relevantnej ponuke alebo užitočnej realitnej informácii budete medzi prvými, ktorí sa o nej dozvedia. Odhlásiť sa dá jedným klikom v každom e-maile.</p>
            <?php echo zcn_newsletter_shortcode([
                'title'         => 'Prihlásenie na odber',
                'description'   => 'Označte, čo vás zaujíma – a nechajte nám e-mail. Nič viac netreba.',
                'source'        => 'newsletter_page',
                'show_interest' => '1',
                'full'          => '1',
            ]); ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
});

/** Vytvorí samostatnú stránku iba raz; existujúci vlastný obsah neprepisuje. */
function zcn_ensure_newsletter_page() {
    if (get_option('zcn_newsletter_page_v') === '1') return;
    $page = get_page_by_path('newsletter', OBJECT, 'page');
    if (!$page) {
        $id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Newsletter',
            'post_name'    => 'newsletter',
            'post_content' => '[zc_newsletter_full]',
        ]);
        if (is_wp_error($id) || !$id) return;
    } elseif (strpos((string) $page->post_content, '[zc_newsletter_full]') === false) {
        $content = trim((string) $page->post_content);
        $content = ($content === '' || $content === '[zc_newsletter]')
            ? '[zc_newsletter_full]'
            : $content . "\n\n[zc_newsletter_full]";
        wp_update_post(['ID' => $page->ID, 'post_content' => $content]);
    }
    update_option('zcn_newsletter_page_v', '1', false);
}
add_action('init', 'zcn_ensure_newsletter_page', 20);
