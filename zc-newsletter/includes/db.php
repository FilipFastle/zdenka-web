<?php
// Database – creates wp_zc_newsletter table
function zcn_install() {
    global $wpdb;
    $table = $wpdb->prefix . ZCN_TABLE;
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email       VARCHAR(191) NOT NULL,
        name        VARCHAR(191) DEFAULT '',
        status      ENUM('pending','active','unsubscribed') NOT NULL DEFAULT 'pending',
        token       VARCHAR(64)  NOT NULL,
        source      VARCHAR(100) DEFAULT 'web',
        subscribed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        confirmed_at   DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('zcn_db_version', ZCN_VERSION);
}

// Run table check on every plugin load (ensures table exists after WP migration)
add_action('plugins_loaded', function() {
    if (get_option('zcn_db_version') !== ZCN_VERSION) zcn_install();
});

function zcn_table() {
    global $wpdb;
    return $wpdb->prefix . ZCN_TABLE;
}

function zcn_generate_token() {
    return bin2hex(random_bytes(32));
}
