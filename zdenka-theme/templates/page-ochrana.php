<?php
/* Template Name: Ochrana osobných údajov */
defined('ABSPATH') || exit;
get_header();
?>
<div class="ochrana-hero" style="background:var(--section);padding:56px 0 32px;text-align:center">
    <div class="zc-container" style="max-width:760px">
        <div class="zc-eyebrow" style="justify-content:center">Právne informácie</div>
        <h1 style="font-family:var(--serif);font-size:clamp(26px,4vw,40px)">Ochrana osobných údajov</h1>
    </div>
</div>

<section style="padding:48px 0 80px">
<div class="zc-container ochrana-body" style="max-width:760px">
    <?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
</div>
</section>

<style>
.ochrana-body h2{font-family:var(--serif);font-size:22px;margin:36px 0 12px;color:var(--dark)}
.ochrana-body h2:first-child{margin-top:0}
.ochrana-body p{color:var(--text);line-height:1.85;margin-bottom:16px}
.ochrana-body ul{margin:0 0 18px;padding-left:0;display:flex;flex-direction:column;gap:8px}
.ochrana-body li{position:relative;padding-left:26px;color:var(--text);line-height:1.7}
.ochrana-body li::before{content:'';position:absolute;left:6px;top:11px;width:6px;height:6px;border-radius:50%;background:var(--accent)}
.ochrana-body a{color:var(--accent-txt);text-decoration:underline}
.ochrana-body strong{color:var(--dark)}
</style>
<?php get_footer(); ?>
