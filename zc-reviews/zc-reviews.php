<?php
/**
 * Plugin Name: ZC Recenzie
 * Description: Správa recenzií a referencií pre zdenkacibulova.sk
 * Version: 1.2.0
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZCR_VERSION', '1.0.4');
define('ZCR_TABLE',   'zc_reviews');
define('ZCR_PATH',    plugin_dir_path(__FILE__));
define('ZCR_URL',     plugin_dir_url(__FILE__));

require_once ZCR_PATH . 'includes/db.php';
require_once ZCR_PATH . 'includes/shortcode.php';
require_once ZCR_PATH . 'includes/admin.php';
require_once ZCR_PATH . 'includes/api.php';
require_once ZCR_PATH . 'includes/order.php';
require_once ZCR_PATH . 'includes/google.php';

register_activation_hook(__FILE__, 'zcr_install');
