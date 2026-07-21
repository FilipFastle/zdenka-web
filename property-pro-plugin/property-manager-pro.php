<?php
/**
 * Plugin Name: Property Manager Pro
 * Description: Profesionálny real estate plugin na správu nehnuteľností
 * Version: 4.8
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
require_once PROPERTY_PRO_PATH . 'includes/amenities.php';
require_once PROPERTY_PRO_PATH . 'includes/shortcodes.php';
require_once PROPERTY_PRO_PATH . 'includes/meta-boxes.php';
require_once PROPERTY_PRO_PATH . 'includes/single-template.php';
require_once PROPERTY_PRO_PATH . 'includes/panel.php';

// Admin CSS for meta boxes
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_media();
        wp_enqueue_style('property-admin', PROPERTY_PRO_URL . 'assets/css/admin.css');
        wp_enqueue_script('property-admin', PROPERTY_PRO_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
    }
});
