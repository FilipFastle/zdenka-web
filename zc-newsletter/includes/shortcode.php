<?php
defined('ABSPATH') || exit;
// [zc_newsletter] shortcode — subscribe form
add_shortcode('zc_newsletter', function($atts) {
    $atts = shortcode_atts([
        'title'       => 'Odber noviniek',
        'description' => 'Nové ponuky priamo do vašej schránky.',
        'source'      => 'web',
        'dark'        => '0',
    ], $atts);

    $dark = $atts['dark'] === '1';
    $bg   = $dark ? '#1C1A18' : '#F2EEE8';
    $text = $dark ? '#fff'     : '#2C2825';
    $sub  = $dark ? 'rgba(255,255,255,.65)' : '#7A7068';

    wp_enqueue_script('zc-newsletter-js',
        plugin_dir_url(dirname(__FILE__)) . 'assets/newsletter.js',
        [], ZCN_VERSION, true
    );
    wp_localize_script('zc-newsletter-js', 'zcnData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('zcn_nonce'),
    ]);

    ob_start(); ?>
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
                <div style="display:flex;gap:8px">
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
});
