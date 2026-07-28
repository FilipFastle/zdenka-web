<?php
/**
 * Plugin Name: ZC Ebook – PDF lead-magnet
 * Description: PDF ebook (lead-magnet) – pás na stránke + modal formulár. Zbiera kontakty do Formulárov, prihlasuje na newsletter a posiela PDF. Správa cez wp-admin aj realitný panel.
 * Version: 1.0.3
 * Author: Filip
 * Text Domain: zc-ebook
 */
defined('ABSPATH') || exit;

define('ZC_EBOOK_VER', '1.0.1');
define('ZC_EBOOK_PATH', plugin_dir_path(__FILE__));
define('ZC_EBOOK_URL', plugin_dir_url(__FILE__));

require_once ZC_EBOOK_PATH . 'includes/core.php';
require_once ZC_EBOOK_PATH . 'includes/admin.php';
