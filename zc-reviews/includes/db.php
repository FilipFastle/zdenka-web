<?php
function zcr_install() {
    global $wpdb;
    $t  = $wpdb->prefix . ZCR_TABLE;
    $ch = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$t} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        author_name VARCHAR(191) NOT NULL,
        author_role VARCHAR(191) DEFAULT '',
        body        TEXT NOT NULL,
        rating      TINYINT DEFAULT 5,
        avatar_url  VARCHAR(500) DEFAULT '',
        published   TINYINT(1) DEFAULT 1,
        sort_order  INT DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$ch};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('zcr_db_version', ZCR_VERSION);
}
add_action('plugins_loaded', function() {
    if (get_option('zcr_db_version') !== ZCR_VERSION) zcr_install();
});
function zcr_table() { global $wpdb; return $wpdb->prefix . ZCR_TABLE; }
