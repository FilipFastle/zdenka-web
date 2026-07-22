<?php
/**
 * Samostatná šablóna pre realitný panel — bez hlavičky a pätičky témy.
 * Panel má vlastný navbar; načítame len wp_head/wp_footer kvôli skriptom.
 */
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('pp-panel-standalone'); ?>>
<?php
while (have_posts()) { the_post(); the_content(); }
wp_footer();
?>
</body>
</html>
