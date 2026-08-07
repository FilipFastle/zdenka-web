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
        <div class="zc-eyebrow"><?php echo zc_t('about.eyebrow') ?></div>
        <h1 style="font-family:var(--serif);margin-bottom:24px"><?php
            $name_parts = explode(' ', $name);
            $surname = array_pop($name_parts);
            $rest    = trim(implode(' ', $name_parts));
            echo esc_html($rest); echo $rest ? ' ' : ''; ?><em><?php echo esc_html($surname); ?></em></h1>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:20px">Som <?php echo esc_html($name_without_title); ?><?php echo zc_t('about.p1') ?></p>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:20px"><?php echo zc_t('about.p2') ?></p>
        <p style="font-size:16px;color:var(--muted);line-height:1.85;margin-bottom:32px"><?php echo zc_t('about.p3') ?></p>
        <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-btn zc-btn-primary"><?php echo zc_t('about.btn') ?></a>
    </div>
    <div>
        <?php
        // Fotka O mne má vlastné pole v Prispôsobiť – hero sekcia ju už neurčuje.
        // Ak ho maklérka nevyplní, spadne sa na portrét, aby stránka nikdy nebola prázdna.
        $zc_omne_img = function_exists('zc_photo') ? (zc_photo('about') ?: zc_photo('portrait')) : '';
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
        <h2 style="font-family:var(--serif)"><?php echo zc_t('about.refs_t') ?> <em><?php echo zc_t('about.refs_hl') ?></em></h2>
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
