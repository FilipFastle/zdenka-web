<?php /* Template Name: Obľúbené */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
?>
<section style="background:var(--section);padding:60px 0;text-align:center">
<div class="zc-container" style="max-width:640px">
    <div class="zc-eyebrow" style="justify-content:center">Uložené nehnuteľnosti</div>
    <h1 style="font-family:var(--serif);margin-bottom:12px">Moje <em>obľúbené</em></h1>
    <p style="font-size:16px;color:var(--muted)">Nehnuteľnosti ktoré si si uložil pre neskôr.</p>
</div>
</section>
<section style="padding:48px 0 80px">
<div class="zc-container">
    <?php echo do_shortcode('[property_favorites]'); ?>
</div>
</section>
<?php get_footer(); ?>
