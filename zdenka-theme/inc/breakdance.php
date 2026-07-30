<?php
/**
 * Bezpečná integrácia Breakdance Free.
 *
 * Pôvodné PHP šablóny zostávajú fallbackom. Breakdance dostane prednosť iba
 * vtedy, keď už pre aktuálnu požiadavku vybral vlastný template, alebo keď
 * administrátor stránke ručne nastaví template „Breakdance – editovateľná stránka“.
 */
defined('ABSPATH') || exit;

/** Je aktívny plugin Breakdance (bez závislosti od jeho interných tried)? */
function zc_breakdance_active() {
    if (post_type_exists('breakdance_template') || post_type_exists('breakdance_block')) return true;
    foreach ((array) get_option('active_plugins', []) as $plugin) {
        if (stripos((string) $plugin, 'breakdance') !== false) return true;
    }
    return is_multisite() && (bool) array_filter((array) get_site_option('active_sitewide_plugins', []), static function ($value, $plugin) {
        return stripos((string) $plugin, 'breakdance') !== false;
    }, ARRAY_FILTER_USE_BOTH);
}

/** Má stránka skutočne uložený dizajn z Breakdance? */
function zc_breakdance_has_content($post_id = 0) {
    $post_id = $post_id ?: get_queried_object_id();
    if (!$post_id || get_post_type($post_id) !== 'page') return false;
    $data = get_post_meta($post_id, '_breakdance_data', true);
    return $data !== '' && $data !== null && $data !== false;
}

function zc_breakdance_template_selected() {
    return is_page()
        && get_page_template_slug(get_queried_object_id()) === 'templates/page-breakdance.php';
}

function zc_breakdance_owns_template($template) {
    if (!is_string($template) || $template === '') return false;

    $path = strtolower(wp_normalize_path($template));

    // Breakdance používa vlastný template súbor, keď je stránka vytvorená
    // v builderi. Nekontrolujeme interné meta kľúče pluginu, ktoré sa môžu meniť.
    return strpos($path, '/breakdance/') !== false
        || strpos($path, '/breakdance-zero-theme/') !== false
        || strpos($path, '/breakdance-plugin/') !== false;
}

function zc_should_respect_breakdance_template($template) {
    return zc_breakdance_template_selected()
        || zc_breakdance_has_content()
        || zc_breakdance_owns_template($template);
}

add_filter('body_class', function ($classes) {
    if (zc_breakdance_template_selected() || zc_breakdance_has_content()) $classes[] = 'zc-breakdance-page';
    return $classes;
});

/**
 * Breakdance musí dostať prednosť na stránke, ktorú v ňom používateľ uložil.
 * Funkčné systémové obrazovky (realitný panel a detail ponuky) nepreberá.
 */
add_filter('breakdance_should_override_template', function ($should_override) {
    if (function_exists('pp_is_panel_page') && pp_is_panel_page()) return false;
    if (is_singular('property')) return false;
    if (is_page() && (zc_breakdance_template_selected() || zc_breakdance_has_content())) return true;
    return $should_override;
}, 20, 2);

/** Počet stránok, ktoré už majú uložený Breakdance obsah (pre admin prehľad). */
function zc_breakdance_page_count() {
    $query = new WP_Query([
        'post_type'              => 'page',
        'post_status'            => ['publish', 'draft', 'private'],
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => false,
        'meta_query'             => [[
            'key'     => '_breakdance_data',
            'value'   => '',
            'compare' => '!=',
        ]],
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);
    return (int) $query->found_posts;
}

/**
 * Kontaktný formulár pre Breakdance.
 *
 * Textový nadpis a popis sa zámerne nevkladajú – tie sa majú upravovať priamo
 * cez Heading/Text elementy v Breakdance. Shortcode drží iba funkčnú logiku.
 */
add_shortcode('zc_contact_form', function ($atts) {
    $atts = shortcode_atts([
        'button'     => 'Odoslať správu',
        'interest'   => '1',
        'newsletter' => '1',
    ], $atts, 'zc_contact_form');

    $show_interest   = $atts['interest'] === '1';
    $show_newsletter = $atts['newsletter'] === '1';

    ob_start(); ?>
    <style>
    .zcb-contact,.zcb-contact *{box-sizing:border-box}
    .zcb-contact{width:100%;max-width:720px;margin:0 auto;background:var(--white,#fff);
        border:1px solid var(--border,#E0D8CE);border-radius:16px;
        box-shadow:var(--sh-sm,0 1px 4px rgba(40,32,20,.06));overflow:hidden}
    .zcb-contact-fields{padding:28px 28px 0}
    .zcb-contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .zcb-contact-field{display:flex;flex-direction:column;min-width:0}
    .zcb-contact-field.is-full{grid-column:1/-1}
    .zcb-contact-label{font:700 10px/1.3 var(--sans,'DM Sans',sans-serif);
        letter-spacing:1.4px;text-transform:uppercase;color:var(--muted,#7A7068);
        margin-bottom:7px}
    .zcb-contact-input,.zcb-contact-textarea{width:100%;border:1.5px solid var(--border,#E0D8CE);
        border-radius:var(--r-sm,8px);padding:13px 15px;background:var(--bg,#FBF7EE);
        color:var(--text,#2C2825);font:16px/1.45 var(--sans,'DM Sans',sans-serif);
        outline:none;transition:border-color .2s,box-shadow .2s,background .2s}
    .zcb-contact-textarea{resize:vertical;min-height:120px;max-height:340px}
    .zcb-contact-input:focus,.zcb-contact-textarea:focus{border-color:var(--accent,#B8A47A);
        background:#fff;box-shadow:0 0 0 3px rgba(184,164,122,.13)}
    .zcb-contact-footer{padding:22px 28px 28px}
    .zcb-contact-check{display:flex;align-items:flex-start;gap:9px;
        color:var(--muted,#7A7068);font:12px/1.6 var(--sans,'DM Sans',sans-serif);
        cursor:pointer;margin-bottom:11px}
    .zcb-contact-check input{margin-top:3px;accent-color:var(--accent,#B8A47A);flex:0 0 auto}
    .zcb-contact-check a{color:var(--accent-txt,#7C5E33)}
    .zcb-contact-submit{width:100%;min-height:50px;margin-top:7px;padding:14px 24px;
        border:0;border-radius:var(--r-sm,8px);background:var(--accent,#B8A47A);
        color:var(--dark,#1C1A18);font:700 12px/1.2 var(--sans,'DM Sans',sans-serif);
        letter-spacing:.8px;text-transform:uppercase;cursor:pointer;
        box-shadow:0 3px 14px rgba(184,164,122,.3);transition:all .22s}
    .zcb-contact-submit:hover{background:var(--accent-dk,#9A8660);transform:translateY(-2px)}
    .zcb-contact-submit:disabled{opacity:.65;cursor:wait;transform:none}
    @media(max-width:620px){
        .zcb-contact{border-radius:13px}
        .zcb-contact-fields{padding:20px 18px 0}
        .zcb-contact-grid{grid-template-columns:1fr;gap:11px}
        .zcb-contact-field.is-full{grid-column:auto}
        .zcb-contact-footer{padding:18px 18px 22px}
    }
    </style>
    <div class="zcb-contact">
        <form class="zc-contact-form" data-zc-form="kontakt-breakdance">
            <?php echo function_exists('zc_honeypot_fields') ? zc_honeypot_fields() : ''; ?>
            <div class="zcb-contact-fields">
                <div class="zcb-contact-grid">
                    <div class="zcb-contact-field">
                        <label class="zcb-contact-label">Meno *</label>
                        <input type="text" name="name" class="zcb-contact-input" autocomplete="name" required>
                    </div>
                    <div class="zcb-contact-field">
                        <label class="zcb-contact-label">Telefón</label>
                        <input type="tel" name="phone" class="zcb-contact-input" autocomplete="tel">
                    </div>
                    <div class="zcb-contact-field is-full">
                        <label class="zcb-contact-label">E-mail *</label>
                        <input type="email" name="email" class="zcb-contact-input" autocomplete="email" required>
                    </div>
                    <div class="zcb-contact-field is-full">
                        <label class="zcb-contact-label">Správa</label>
                        <textarea name="message" class="zcb-contact-textarea"
                            placeholder="Napíšte dôvod kontaktu alebo o akú nehnuteľnosť máte záujem…"></textarea>
                    </div>
                </div>
            </div>
            <div class="zcb-contact-footer">
                <div class="zc-form-msg" aria-live="polite"></div>
                <label class="zcb-contact-check">
                    <input type="checkbox" name="gdpr" required>
                    <span>Súhlasím so <a href="<?php echo esc_url(home_url('/ochrana-osobnych-udajov/')); ?>">spracovaním osobných údajov</a> na účel odpovede na môj dopyt. *</span>
                </label>
                <?php if ($show_newsletter): ?>
                <label class="zcb-contact-check">
                    <input type="checkbox" name="newsletter">
                    <span>Chcem dostávať novinky a nové ponuky nehnuteľností na e-mail.</span>
                </label>
                <?php endif; ?>
                <button type="submit" class="zcb-contact-submit"><?php echo esc_html($atts['button']); ?> →</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
});
