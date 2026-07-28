<?php
/**
 * Správca značiek Google (GTM).
 *
 * Kód sa nevkladá do súborov témy – tie sa pri aktualizácii prepíšu.
 * Stačí zadať ID kontajnera v Prispôsobiť → Miestne SEO (Google).
 */
defined('ABSPATH') || exit;

/** ID kontajnera, ak je vyplnené a v správnom tvare. */
function zc_gtm_id() {
    $id = trim((string) get_theme_mod('zc_gtm_id', ''));
    return preg_match('/^GTM-[A-Z0-9]{4,}$/i', $id) ? strtoupper($id) : '';
}

/** Má sa GTM na tejto stránke načítať? */
function zc_gtm_active() {
    if (!zc_gtm_id()) return false;
    if (is_admin()) return false;
    // Realitný panel je pracovný nástroj, nie web pre návštevníkov
    if (function_exists('pp_is_panel_page') && pp_is_panel_page()) return false;
    if (get_theme_mod('zc_gtm_skip_admins', false) && current_user_can('edit_posts')) return false;
    return true;
}

/**
 * Údaje o stránke do dataLayer – v GTM z nich vieš robiť spúšťače
 * bez toho, aby si musel čokoľvek meniť v kóde webu.
 */
function zc_gtm_datalayer() {
    $data = ['page_type' => 'other'];

    if (is_front_page())            $data['page_type'] = 'home';
    elseif (is_page('ponuky'))      $data['page_type'] = 'listings';
    elseif (is_page('kontakt'))     $data['page_type'] = 'contact';
    elseif (is_page('odhad'))       $data['page_type'] = 'valuation';
    elseif (is_page('referencie'))  $data['page_type'] = 'reviews';
    elseif (is_singular('property'))$data['page_type'] = 'property';

    if (is_singular('property')) {
        $id = get_the_ID();
        $data['property_id']   = (int) $id;
        $data['property_type'] = (string) get_post_meta($id, '_property_typ', true);
        $data['city']          = (string) get_post_meta($id, '_property_mesto', true);
        $price = function_exists('pp_price_num')
               ? pp_price_num(get_post_meta($id, '_property_cena', true)) : 0;
        if ($price > 0) {
            $data['value']    = (float) $price;
            $data['currency'] = 'EUR';
        }
    }
    return $data;
}

// 1) Skript čo najvyššie v <head>
add_action('wp_head', function () {
    if (!zc_gtm_active()) return;
    $id = zc_gtm_id();
    ?>
<!-- Google Tag Manager -->
<script>window.dataLayer = window.dataLayer || [];
dataLayer.push(<?php echo wp_json_encode(zc_gtm_datalayer(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);</script>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js($id); ?>');</script>
<!-- End Google Tag Manager -->
    <?php
}, 1);

// 2) Náhrada pre prehliadače bez JavaScriptu – hneď za <body>
add_action('wp_body_open', function () {
    if (!zc_gtm_active()) return;
    printf(
        '<!-- Google Tag Manager (noscript) -->'
        . '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s"'
        . ' height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'
        . '<!-- End Google Tag Manager (noscript) -->' . "\n",
        esc_attr(zc_gtm_id())
    );
}, 1);

/* ───────────────── Udalosti, ktoré sa oplatí merať ───────────────── */

/**
 * Odoslaný formulár, stiahnutý ebook a kliky na telefón či WhatsApp
 * sa pošlú do dataLayer. V GTM si na ne spravíš konverzie.
 */
add_action('wp_footer', function () {
    if (!zc_gtm_active()) return;
    ?>
<script>
(function(){
    // Naviažeme sa až keď má prehliadač voľno – meranie klikov nemá
    // čo robiť v čase, keď sa stránka ešte vykresľuje.
    var start = function(){
    function push(name, extra){
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(Object.assign({event: name}, extra || {}));
    }
    // Kliky na telefón, e-mail a WhatsApp (pasívne – nezdržuje vykresľovanie)
    document.addEventListener('click', function(e){
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        var href = a.getAttribute('href') || '';
        if (href.indexOf('tel:') === 0)          push('kontakt_telefon');
        else if (href.indexOf('mailto:') === 0)  push('kontakt_email');
        else if (href.indexOf('wa.me') > -1)     push('kontakt_whatsapp');
    }, {passive: true});

    // Odoslané formuláre témy (kontakt, odhad, ebook)
    document.addEventListener('submit', function(e){
        var f = e.target;
        if (!f || !f.matches) return;
        if (f.matches('.zc-form, .pp-cta-form, form[data-zc-form]')) push('generate_lead');
    }, true);

    // Úspešné odoslanie cez AJAX hlási téma vlastnou udalosťou
    document.addEventListener('zc:form-sent', function(e){
        push('generate_lead', (e.detail || {}));
    });
    document.addEventListener('zc:ebook-sent', function(){ push('ebook_stiahnuty'); });
    };
    if ('requestIdleCallback' in window) requestIdleCallback(start, {timeout: 3000});
    else window.addEventListener('load', start, {once: true});
})();
</script>
    <?php
}, 99);
