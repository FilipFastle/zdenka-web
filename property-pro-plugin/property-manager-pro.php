<?php
/**
 * Plugin Name: Property Manager Pro
 * Description: Profesionálny real estate plugin na správu nehnuteľností
 * Version: 5.21
 * Author: Filip
 */

if (!defined('ABSPATH')) exit;

define('PROPERTY_PRO_PATH', plugin_dir_path(__FILE__));
define('PROPERTY_PRO_URL', plugin_dir_url(__FILE__));

// Register Post Type
add_action('init', function() {
    register_post_type('property', [
        'label' => 'Nehnuteľnosti',
        'public' => true,
        'has_archive' => false,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'menu_icon' => 'dashicons-building',
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'ponuka'],
    ]);
});

// Load Shortcodes & Templates
require_once PROPERTY_PRO_PATH . 'includes/helpers.php';
require_once PROPERTY_PRO_PATH . 'includes/roles.php';
require_once PROPERTY_PRO_PATH . 'includes/leads.php';
require_once PROPERTY_PRO_PATH . 'includes/import-export.php';
require_once PROPERTY_PRO_PATH . 'includes/expose.php';
require_once PROPERTY_PRO_PATH . 'includes/amenities.php';
require_once PROPERTY_PRO_PATH . 'includes/shortcodes.php';
require_once PROPERTY_PRO_PATH . 'includes/meta-boxes.php';
require_once PROPERTY_PRO_PATH . 'includes/single-template.php';
require_once PROPERTY_PRO_PATH . 'includes/panel.php';

// Realitný panel beží na vlastnej šablóne — bez hlavičky/pätičky témy
add_filter('template_include', function ($template) {
    if (is_page('realitny-panel')) {
        $tpl = PROPERTY_PRO_PATH . 'templates/panel-standalone.php';
        if (file_exists($tpl)) return $tpl;
    }
    return $template;
});

// Auto-vytvorenie stránky porovnania (raz)
add_action('admin_init', function () {
    if (get_page_by_path('porovnanie')) return;
    if (get_option('pp_porovnanie_created')) return;
    wp_insert_post([
        'post_type'    => 'page',
        'post_title'   => 'Porovnanie ponúk',
        'post_name'    => 'porovnanie',
        'post_content' => '[porovnanie]',
        'post_status'  => 'publish',
    ]);
    update_option('pp_porovnanie_created', 1);
});

// Admin CSS for meta boxes
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_media();
        wp_enqueue_style('property-admin', PROPERTY_PRO_URL . 'assets/css/admin.css');
        wp_enqueue_script('property-admin', PROPERTY_PRO_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
    }
});
