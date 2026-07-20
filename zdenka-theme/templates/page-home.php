<?php /* Template Name: Homepage */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
// Redirect to front-page.php logic
require get_stylesheet_directory() . '/front-page.php';
