<?php
// Database – creates wp_zc_newsletter table
function zcn_install() {
    global $wpdb;
    $table = $wpdb->prefix . ZCN_TABLE;
    $charset = $wpdb->get_charset_collate();

    // Staršie importy mohli obsahovať rovnakú adresu s medzerami alebo inou
    // veľkosťou písmen. Najprv ich zlúčime, potom dbDelta bezpečne udrží UNIQUE.
    zcn_dedupe_subscribers($table);

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email       VARCHAR(191) NOT NULL,
        name        VARCHAR(191) DEFAULT '',
        interest    VARCHAR(32) DEFAULT '',
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

function zcn_interests() {
    return [
        '1izbovy'       => '1-izbový byt',
        '2izbovy'       => '2-izbový byt',
        '3plus_izbovy'  => '3+-izbový byt',
        'dom'           => 'Dom',
        'pozemok'       => 'Pozemok',
    ];
}

function zcn_sanitize_interest($value) {
    $value = sanitize_key((string) $value);
    return isset(zcn_interests()[$value]) ? $value : '';
}

function zcn_interest_label($value, $empty = 'Všetky ponuky') {
    $value = zcn_sanitize_interest($value);
    return $value ? zcn_interests()[$value] : $empty;
}

function zcn_source_labels() {
    return [
        'newsletter_page' => 'Stránka Newsletter',
        'newsletter'      => 'Newsletter formulár',
        'kontakt'         => 'Kontaktný formulár',
        'odhad'           => 'Formulár Odhad',
        'ponuka'          => 'Detail ponuky',
        'detail'          => 'Dopyt z ponuky',
        'ebook'           => 'Ebook formulár',
        'manual_panel'    => 'Ručne v realitnom paneli',
        'manual_batch'    => 'Hromadne v realitnom paneli',
        'import'          => 'Import',
        'web'             => 'Web',
        'form'            => 'Formulár',
    ];
}

/** Jeden kontakt môže mať viac zdrojov oddelených čiarkou. */
function zcn_source_label($source, $separator = ', ') {
    $labels = zcn_source_labels();
    $keys = array_filter(array_map('trim', explode(',', (string) $source)));
    $out = [];
    foreach ($keys as $key) {
        $key = sanitize_key($key);
        $out[] = $labels[$key] ?? ($key ? ucwords(str_replace('_', ' ', $key)) : '');
    }
    $out = array_values(array_unique(array_filter($out)));
    return $out ? implode($separator, $out) : 'Neznámy zdroj';
}

/**
 * Kontakt môže prísť z viacerých formulárov. Zdroje/roly preto zlučujeme
 * namiesto toho, aby sa pôvodný zdroj pri ďalšom odoslaní prepísal.
 */
function zcn_merge_sources($current = '', $next = '') {
    $sources = array_merge(explode(',', (string) $current), explode(',', (string) $next));
    $sources = array_filter(array_map(function($source) {
        return sanitize_key(trim($source));
    }, $sources));
    return substr(implode(', ', array_slice(array_values(array_unique($sources)), 0, 8)), 0, 100);
}

/**
 * Jeden normalizovaný e-mail = jeden kontakt. Ponechá najnovší záznam,
 * doplní doň chýbajúce meno/kategóriu a ostatné duplicity odstráni.
 */
function zcn_dedupe_subscribers($table = '') {
    global $wpdb;
    $table = $table ?: zcn_table();
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return 0;

    $groups = $wpdb->get_col(
        "SELECT LOWER(TRIM(email)) normalized
         FROM {$table}
         WHERE TRIM(email) <> ''
         GROUP BY normalized HAVING COUNT(*) > 1"
    );
    $removed = 0;
    foreach ($groups as $email) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE LOWER(TRIM(email))=%s ORDER BY id DESC",
            $email
        ));
        if (count($rows) < 2) continue;
        $keep = array_shift($rows);
        $name = trim((string) $keep->name);
        $interest = property_exists($keep, 'interest') ? zcn_sanitize_interest($keep->interest) : '';
        $source = property_exists($keep, 'source') ? (string) $keep->source : '';
        foreach ($rows as $row) {
            if (!$name && !empty($row->name)) $name = trim((string) $row->name);
            if (!$interest && property_exists($row, 'interest')) $interest = zcn_sanitize_interest($row->interest);
            if (property_exists($row, 'source')) $source = zcn_merge_sources($source, $row->source);
            $wpdb->delete($table, ['id' => (int) $row->id], ['%d']);
            $removed++;
        }
        $data = ['email' => strtolower(trim($email)), 'name' => $name, 'source' => $source];
        if (property_exists($keep, 'interest')) $data['interest'] = $interest;
        $wpdb->update($table, $data, ['id' => (int) $keep->id]);
    }

    // Normalizácia aj pri neduplikovaných adresách zabráni ich opätovnému vzniku.
    $wpdb->query("UPDATE {$table} SET email=LOWER(TRIM(email))");
    return $removed;
}

// Run table check on every plugin load (ensures table exists after WP migration)
add_action('plugins_loaded', function() {
    if (get_option('zcn_db_version') !== ZCN_VERSION) zcn_install();
});

function zcn_table() {
    global $wpdb;
    return $wpdb->prefix . ZCN_TABLE;
}

/**
 * Overí fyzickú tabuľku, nielen uložené číslo verzie.
 * Chráni formuláre po migrácii alebo neúplnej aktualizácii WordPressu.
 *
 * Kontroluje aj stĺpec „interest“ – dbDelta ho pri niektorých hostingoch
 * nepridá (napr. keď zlyhá ALTER kvôli právam) a potom by každé uloženie
 * kontaktu tíško padalo na neznámom stĺpci.
 */
function zcn_ensure_table_ready() {
    global $wpdb;
    $table = zcn_table();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    if (!$exists) {
        zcn_install();
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
    if ($exists && !zcn_has_interest_column()) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN interest VARCHAR(32) DEFAULT '' AFTER name");
        zcn_has_interest_column(true); // prepočítať po zmene
    }
    return $exists;
}

/** Má tabuľka stĺpec s kategóriou záujmu? Výsledok si v rámci requestu pamätáme. */
function zcn_has_interest_column($refresh = false) {
    static $has = null;
    if ($has !== null && !$refresh) return $has;
    global $wpdb;
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM " . zcn_table() . " LIKE %s", 'interest'));
    return $has = ($col === 'interest');
}

function zcn_generate_token() {
    return bin2hex(random_bytes(32));
}
