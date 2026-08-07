<?php /* Template Name: Referencie */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }

$has_plugin = function_exists('zcr_public_reviews');
$has_reviews = $has_plugin && !empty(zcr_public_reviews(1));
$google_url = get_theme_mod('zc_social_google', '');
?>

<style>
.rf-hero{background:var(--section);padding:80px 0 60px;text-align:center}
.rf-hero h1{font-family:var(--serif);font-size:clamp(30px,4.4vw,52px);margin-bottom:16px}
.rf-hero p{font-size:17px;color:var(--muted);max-width:620px;margin:0 auto;line-height:1.8}

.rf-body{padding:76px 0 90px;background:var(--white)}

.rf-empty{text-align:center;padding:70px 24px;border:1px dashed var(--border);
    border-radius:18px;background:var(--section);color:var(--muted)}
.rf-empty strong{display:block;font-family:var(--serif);font-size:21px;color:var(--dark);margin-bottom:10px}

.rf-cta{background:var(--section);padding:84px 0;border-top:1px solid var(--border);text-align:center}
@media(max-width:600px){
    .rf-hero{padding:56px 0 44px}
    .rf-body{padding:52px 0 64px}
}
</style>

<!-- HERO -->
<section class="rf-hero">
<div class="zc-container">
    <div class="zc-eyebrow" style="justify-content:center">Referencie</div>
    <h1>Čo hovoria <em>klienti</em></h1>
    <p>Každý predaj je príbeh rodiny, ktorá začína novú kapitolu. Toto sú slová ľudí,
       ktorým som pri tom mohla byť nablízku.</p>
</div>
</section>

<!-- REFERENCIE: 10 NA STRANU -->
<section class="rf-body">
<div class="zc-container">
    <?php
    if ($has_reviews) {
        // Čistý carousel: 3 karty na PC, 2 na tablete, 1 na mobile.
        echo do_shortcode('[zc_reviews layout="carousel" limit="500"]');
    } else { ?>
        <div class="rf-empty">
            <strong>Zatiaľ tu nie sú žiadne referencie</strong>
            Prvé pribudnú hneď, ako ich pridám do správy recenzií.
            <?php if ($google_url): ?>
            <div style="margin-top:22px">
                <a href="<?php echo esc_url($google_url); ?>" target="_blank" rel="noopener" class="zc-btn zc-btn-outline">Pozrieť recenzie na Google →</a>
            </div>
            <?php endif; ?>
        </div>
    <?php } ?>
</div>
</section>

<!-- CTA -->
<section class="rf-cta">
<div class="zc-container" style="max-width:620px">
    <div class="zc-eyebrow" style="justify-content:center">Nezáväzná konzultácia</div>
    <h2 style="font-family:var(--serif);margin-bottom:16px">Chcete byť ďalší <em>spokojný klient?</em></h2>
    <p style="color:var(--muted);font-size:16px;line-height:1.8;margin-bottom:32px">
        Prvá konzultácia je bezplatná a nezáväzná. Ozvite sa a preberieme vaše možnosti.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-btn zc-btn-primary">Kontaktovať →</a>
        <a href="<?php echo home_url('/odhad/'); ?>" class="zc-btn zc-btn-outline">Odhad nehnuteľnosti</a>
    </div>
</div>
</section>

<?php get_footer(); ?>
