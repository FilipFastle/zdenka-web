<?php
defined('ABSPATH') || exit;
// Single Property Template Loader
add_filter('single_template', function($template) {
    if (get_post_type() === 'property') {
        $plugin_template = PROPERTY_PRO_PATH . 'includes/single-property.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});
