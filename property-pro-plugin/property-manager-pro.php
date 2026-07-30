<?php
/**
 * Plugin Name: Property Manager Pro
 * Description: Profesionálny real estate plugin na správu nehnuteľností
 * Version: 5.53
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
require_once PROPERTY_PRO_PATH . 'includes/audit-log.php';
require_once PROPERTY_PRO_PATH . 'includes/backup.php';
require_once PROPERTY_PRO_PATH . 'includes/media-folders.php';

/**
 * WordPress 7 vypisuje content_style TinyMCE do inline JavaScriptu.
 * Úvodzovky okolo viacslovných názvov fontov môžu rozbiť celý inicializačný
 * skript a editor potom zostane prázdny. Oprava platí pre panel, newsletter
 * aj ďalšie natívne wp_editor() polia bez zmeny uloženého obsahu.
 */
add_filter('tiny_mce_before_init', function ($init) {
    if (!empty($init['content_style'])) {
        $init['content_style'] = str_replace(
            ['font-family:"DM Sans"', 'font-family:"Playfair Display"'],
            ['font-family:DM Sans', 'font-family:Playfair Display'],
            (string) $init['content_style']
        );
    }
    return $init;
}, PHP_INT_MAX);

// Obranné vypnutie starého porovnávača aj v prípade, že niekde zostal shortcode.
add_action('init', function () {
    remove_shortcode('porovnanie');
    add_shortcode('porovnanie', '__return_empty_string');
    remove_action('wp_ajax_pp_compare', 'pp_ajax_compare');
    remove_action('wp_ajax_nopriv_pp_compare', 'pp_ajax_compare');
}, 99);

// Realitný panel beží na vlastnej šablóne — bez hlavičky/pätičky témy
add_filter('template_include', function ($template) {
    if (function_exists('pp_is_panel_page') ? pp_is_panel_page() : is_page('realitny-panel')) {
        $tpl = PROPERTY_PRO_PATH . 'templates/panel-standalone.php';
        if (file_exists($tpl)) return $tpl;
    }
    return $template;
});

// Porovnávanie ponúk bolo odstránené. Starú automatickú stránku iba skryjeme
// do konceptov, aby sa nič nenávratne nemazalo.
add_action('admin_init', function () {
    if (get_option('pp_porovnanie_removed') === '1') return;
    $page = get_page_by_path('porovnanie', OBJECT, 'page');
    if ($page && $page->post_status === 'publish') {
        wp_update_post(['ID' => $page->ID, 'post_status' => 'draft']);
    }
    update_option('pp_porovnanie_removed', '1', false);
});

// Admin CSS for meta boxes
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_media();
        wp_enqueue_style('property-admin', PROPERTY_PRO_URL . 'assets/css/admin.css');
        wp_enqueue_script('property-admin', PROPERTY_PRO_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
    }
});
