<?php /* Template Name: O mne */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
$name  = zc_agent('name','Mgr. Zdenka Cibuľová');
$title = zc_agent('title','Realitná maklérka');
$name_without_title = trim(preg_replace('/^Mgr\.\s*/u', '', $name));
?>
<section style="background:var(--section);padding:80px 0">
<div class="zc-container zc-omne-hero" style="display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center">
    <div>
        <div class="zc-eyebrow">Spoznajte ma</div>
        <h1 style="font-family:var(--serif);margin-bottom:24px"><?php
            $name_parts = explode(' ', $name);
            $surname = array_pop($name_parts);
            $rest    = trim(implode(' ', $name_parts));
            echo esc_html($rest); echo $rest ? ' ' : ''; ?><em><?php echo esc_html($surname); ?></em></h1>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:20px">Som <?php echo esc_html($name_without_title); ?>, realitná maklérka pôsobiaca vo Zvolene, Banskej Bystrici a okolí. Práca s ľuďmi ma napĺňa a svet realít mi dáva možnosť pomáhať klientom pri jednom z najdôležitejších rozhodnutí v ich živote.</p>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:20px">Ku každému klientovi pristupujem individuálne, s profesionálnym a ľudským prístupom. Pri spolupráci kladiem veľký dôraz na dôveru, úprimnosť, otvorenú komunikáciu a férové jednanie.</p>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:32px">Svojich klientov sprevádzam celým procesom – od prvého stretnutia až po odovzdanie kľúčov, pričom venujem pozornosť každému detailu. Mojím cieľom je spokojný klient, ktorý vie, že sa na mňa môže s dôverou obrátiť aj v budúcnosti.</p>
        <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-btn zc-btn-primary">Kontaktujte ma</a>
    </div>
    <div>
        <?php
        // Portrét: Customizer (Fotky maklérky) → featured image stránky → placeholder
        $zc_omne_img = function_exists('zc_photo') ? zc_photo('portrait') : '';
        if (!$zc_omne_img && has_post_thumbnail()) $zc_omne_img = get_the_post_thumbnail_url(null, 'large');
        ?>
        <?php if($zc_omne_img): ?>
        <img src="<?php echo esc_url($zc_omne_img); ?>" style="width:100%;border-radius:var(--r-lg);box-shadow:var(--sh-lg)" alt="<?php echo esc_attr($name); ?>">
        <?php else: ?>
        <div style="aspect-ratio:3/4;background:linear-gradient(135deg,var(--border),var(--accent));border-radius:var(--r-lg);display:flex;align-items:center;justify-content:center;font-size:64px;font-family:var(--serif);font-weight:700;color:#fff;box-shadow:var(--sh-lg)"><?php echo esc_html(function_exists('zc_initials') ? zc_initials($name) : ''); ?></div>
        <?php endif; ?>
    </div>
</div>
</section>

<section class="zc-section bg-white">
<div class="zc-container">
    <div style="text-align:center;max-width:560px;margin:0 auto 52px">
        <div class="zc-eyebrow" style="justify-content:center">Referencie</div>
        <h2 style="font-family:var(--serif)">Čo hovoria <em>klienti</em></h2>
    </div>
    <?php
    if (function_exists('zcr_table')) {
        echo do_shortcode('[zc_reviews limit="9" cols="3"]');
    } else {
        echo '<p style="text-align:center;color:var(--muted)">Recenzie sa načítavajú…</p>';
    }
    ?>
</div>
</section>

<style>
@media (max-width: 860px) {
    .zc-omne-hero { grid-template-columns: 1fr !important; gap: 32px !important; }
}
</style>
<?php get_footer(); ?>
