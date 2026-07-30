<?php
/*
 * Template Name: Breakdance – editovateľná stránka
 * Template Post Type: page
 */
defined('ABSPATH') || exit;

get_header();
?>
<main id="main-content" class="zc-breakdance-content">
<?php
while (have_posts()) {
    the_post();
    the_content();
}
?>
</main>
<?php
get_footer();

