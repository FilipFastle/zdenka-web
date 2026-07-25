<?php
/**
 * Plugin Name: ZC Zabezpečenie – 2FA + časovanie relácií
 * Description: Dvojfaktorové overenie (Google Authenticator / TOTP) pre wp-admin aj realitný panel, záložné kódy a automatické odhlásenie pri nečinnosti (30 min bez 2FA, 2 h s 2FA).
 * Version: 1.0.0
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZC2FA_VER', '1.0.0');
define('ZC2FA_PATH', plugin_dir_path(__FILE__));

require_once ZC2FA_PATH . 'includes/totp.php';
require_once ZC2FA_PATH . 'includes/core.php';
require_once ZC2FA_PATH . 'includes/login.php';
require_once ZC2FA_PATH . 'includes/admin.php';
