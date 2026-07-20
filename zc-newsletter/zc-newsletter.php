<?php
/**
 * Plugin Name: ZC Newsletter
 * Description: Vlastný newsletter pre zdenkacibulova.sk — databáza v WP, double opt-in, markdown editor, unsubscribe link.
 * Version: 1.2.1
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZCN_VERSION', '1.2.1');
define('ZCN_TABLE',   'zc_newsletter');
define('ZCN_URL',     plugin_dir_url(__FILE__));
define('ZCN_PATH',    plugin_dir_path(__FILE__));

require_once ZCN_PATH . 'includes/db.php';
require_once ZCN_PATH . 'includes/templates.php';
require_once ZCN_PATH . 'includes/email-template.php';
require_once ZCN_PATH . 'includes/property-blast.php';
require_once ZCN_PATH . 'includes/subscribe.php';
require_once ZCN_PATH . 'includes/confirm.php';
require_once ZCN_PATH . 'includes/unsubscribe.php';
require_once ZCN_PATH . 'includes/shortcode.php';
require_once ZCN_PATH . 'includes/admin.php';
require_once ZCN_PATH . 'includes/send.php';

register_activation_hook(__FILE__, 'zcn_install');



// AJAX: get subscriber count (for forms)
add_action('wp_ajax_zcn_count',        'zcn_get_count');
add_action('wp_ajax_nopriv_zcn_count', 'zcn_get_count');
function zcn_get_count() {
    global $wpdb;
    echo intval($wpdb->get_var("SELECT COUNT(*) FROM " . zcn_table() . " WHERE status='active'"));
    exit;
}
