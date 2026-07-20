<?php /* Template Name: Ponuky */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
?>
<style>
.ponuky-hero{background:var(--section);padding:60px 0;text-align:center;border-bottom:1px solid var(--border)}
.ponuky-hero h1{font-family:var(--serif);font-size:clamp(28px,4vw,44px);margin-bottom:12px}
.ponuky-hero p{font-size:16px;color:var(--muted);max-width:500px;margin:0 auto}
</style>
<div class="ponuky-hero">
    <div class="zc-container">
        <div class="zc-eyebrow" style="justify-content:center">Realitná ponuka</div>
        <h1>Nehnuteľnosti <em>na predaj a prenájom</em></h1>
        <p>Aktuálna databáza nehnuteľností v Banskej Bystrici, Zvolene a okolí.</p>
    </div>
</div>
<section style="padding:48px 0 80px">
    <div class="zc-container">
        <?php echo do_shortcode('[property_grid per_page="24"]'); ?>
    </div>
</section>
<?php get_footer(); ?>
