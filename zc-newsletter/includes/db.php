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
        interest    VARCHAR(191) DEFAULT '',
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

/**
 * Kategórie záujmu. Kontakt ich môže mať naraz viac – v databáze sú
 * uložené ako zoznam oddelený čiarkou (napr. „dom,pozemok,tipy").
 * Prázdna hodnota znamená „všetko".
 */
function zcn_interest_groups() {
    $groups = [];
    foreach (zcn_categories() as $key => $cat) {
        $groups[$cat['group']][$key] = $cat['label'];
    }
    return $groups;
}

/** Predvolené kategórie – použijú sa, kým si ich správca neupraví. */
function zcn_default_categories() {
    return [
        '1izbovy'      => ['label' => '1-izbový byt',              'group' => 'Nehnuteľnosti', 'offer' => 1],
        '2izbovy'      => ['label' => '2-izbový byt',              'group' => 'Nehnuteľnosti', 'offer' => 1],
        '3plus_izbovy' => ['label' => '3+-izbový byt',             'group' => 'Nehnuteľnosti', 'offer' => 1],
        'dom'          => ['label' => 'Dom',                       'group' => 'Nehnuteľnosti', 'offer' => 1],
        'pozemok'      => ['label' => 'Pozemok',                   'group' => 'Nehnuteľnosti', 'offer' => 1],
        'ebook'        => ['label' => 'Ebook a materiály zdarma',  'group' => 'Ostatné',       'offer' => 0],
        'tipy'         => ['label' => 'Realitné tipy a novinky',   'group' => 'Ostatné',       'offer' => 0],
    ];
}

/**
 * Kategórie tak, ako ich má nastavené správca.
 * Upravujú sa vo wp-admine (Newsletter → Kategórie).
 */
function zcn_categories() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $saved = get_option('zcn_categories', null);
    if (!is_array($saved) || !$saved) return $cache = zcn_default_categories();

    $out = [];
    foreach ($saved as $key => $cat) {
        $key = sanitize_key($key);
        if ($key === '' || $key === 'ziadne') continue;
        $label = trim((string) ($cat['label'] ?? ''));
        if ($label === '') continue;
        $out[$key] = [
            'label' => $label,
            'group' => trim((string) ($cat['group'] ?? 'Ostatné')) ?: 'Ostatné',
            'offer' => !empty($cat['offer']) ? 1 : 0,
        ];
    }
    return $cache = ($out ?: zcn_default_categories());
}

/** Uloží kategórie a zabudne vyrovnávaciu pamäť v tomto requeste. */
function zcn_save_categories($cats) {
    update_option('zcn_categories', $cats);
    wp_cache_delete('zcn_categories', 'options');
}

/** Kategórie, ktoré znamenajú záujem o nehnuteľnosti. */
function zcn_offer_interests() {
    $out = [];
    foreach (zcn_categories() as $key => $cat) {
        if (!empty($cat['offer'])) $out[$key] = $cat['label'];
    }
    return $out;
}

/** Plochý zoznam všetkých kategórií vrátane staršej hodnoty „ziadne". */
function zcn_interests() {
    $flat = [];
    foreach (zcn_categories() as $key => $cat) $flat[$key] = $cat['label'];
    // Staršie kontakty môžu mať ešte pôvodnú hodnotu – nech ju vieme pomenovať
    $flat['ziadne'] = 'Bez ponúk – iba novinky a ebook';
    return $flat;
}

/** Jedna platná kategória, alebo prázdny reťazec. */
function zcn_sanitize_interest($value) {
    $value = sanitize_key((string) $value);
    return isset(zcn_interests()[$value]) ? $value : '';
}

/** Zoznam kategórií z reťazca alebo poľa → pole platných kľúčov. */
function zcn_interest_list($value) {
    if (is_string($value)) $value = explode(',', $value);
    if (!is_array($value)) return [];
    $out = [];
    foreach ($value as $item) {
        $key = zcn_sanitize_interest(trim((string) $item));
        if ($key !== '' && !in_array($key, $out, true)) $out[] = $key;
    }
    return $out;
}

/**
 * Normalizovaná hodnota do databázy: „dom,pozemok" alebo prázdny reťazec.
 *
 * Pravidlo, ktoré platí v celom systéme:
 *   nič neoznačené  = všetko
 *   všetko označené = tiež všetko (uloží sa prázdna hodnota)
 *   čokoľvek medzi  = presne to, čo je označené
 * Vďaka tomu nemôže vzniknúť stav „mám vybraté všetky, a predsa to filtruje".
 */
function zcn_sanitize_interests($value) {
    $list = zcn_interest_list($value);
    if (!$list) return '';
    if (zcn_is_all_interests($list)) return '';
    return implode(',', array_slice($list, 0, 20));
}

/** Pokrýva tento výber všetky ponúkané kategórie? */
function zcn_is_all_interests($value) {
    $list = zcn_interest_list($value);
    if (!$list) return false;
    $all = array_keys(zcn_categories());   // bez staršej hodnoty „ziadne"
    if (!$all) return false;
    return !array_diff($all, $list);
}

/** Popis kategórií na výpis. */
function zcn_interest_label($value, $empty = 'Všetko') {
    $keys = zcn_interest_list($value);
    if (!$keys) return $empty;
    $all = zcn_interests();
    $out = [];
    foreach ($keys as $key) $out[] = $all[$key] ?? $key;
    return implode(', ', $out);
}

/** Má tento kontakt dostávať ponuky nehnuteľností? */
function zcn_wants_offers($value) {
    $keys = zcn_interest_list($value);
    if (!$keys) return true;                       // prázdne = všetko
    if ($keys === ['ziadne']) return false;        // staršia hodnota
    return (bool) array_intersect($keys, array_keys(zcn_offer_interests()));
}

/**
 * Podmienka do SQL: komu sa smú posielať ponuky nehnuteľností.
 * Prázdna kategória = „všetko", tomu sa posiela vždy.
 */
function zcn_offers_sql_where() {
    $parts = ["interest IS NULL", "interest = ''"];
    foreach (array_keys(zcn_offer_interests()) as $key) {
        $parts[] = "FIND_IN_SET('" . esc_sql($key) . "', interest)";
    }
    return ' AND (' . implode(' OR ', $parts) . ')';
}

/**
 * Podmienka do SQL pre viac kategórií naraz.
 * Kontakt s prázdnou kategóriou („všetko") sa trafí vždy.
 */
function zcn_interests_sql_where($cats) {
    $cats = zcn_interest_list($cats);
    if (!$cats) return '';
    if (zcn_is_all_interests($cats)) return '';   // všetky = bez obmedzenia
    $or = ["interest IS NULL", "interest = ''"];
    foreach ($cats as $key) $or[] = "FIND_IN_SET('" . esc_sql($key) . "', interest)";
    return ' AND (' . implode(' OR ', $or) . ')';
}

/** Podmienka do SQL pre jednu konkrétnu kategóriu (prázdna = všetko). */
function zcn_interest_sql_where($interest) {
    $key = zcn_sanitize_interest($interest);
    if ($key === '') return '';
    return " AND (interest IS NULL OR interest = '' OR FIND_IN_SET('" . esc_sql($key) . "', interest))";
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
        'manual_admin'    => 'Ručne vo wp-admine',
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
        $interest = property_exists($keep, 'interest') ? zcn_sanitize_interests($keep->interest) : '';
        $source = property_exists($keep, 'source') ? (string) $keep->source : '';
        foreach ($rows as $row) {
            if (!$name && !empty($row->name)) $name = trim((string) $row->name);
            if (property_exists($row, 'interest')) $interest = zcn_sanitize_interests($interest . ',' . $row->interest);
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
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN interest VARCHAR(191) DEFAULT '' AFTER name");
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
