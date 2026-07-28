<?php
/**
 * Bezpečnostný denník – kto sa kedy prihlásil, odkiaľ a čo zmenil.
 * Záznamy sa držia 30 dní, potom sa samy mažú.
 */
defined('ABSPATH') || exit;

define('ZC_AUDIT_DAYS', 30);

function zc_audit_table() {
    global $wpdb;
    return $wpdb->prefix . 'zc_audit';
}

function zc_audit_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t       = zc_audit_table();
    $collate = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$t} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        created DATETIME NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        user_login VARCHAR(120) NOT NULL DEFAULT '',
        user_role VARCHAR(80) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        event VARCHAR(40) NOT NULL DEFAULT '',
        object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        object VARCHAR(190) NOT NULL DEFAULT '',
        detail TEXT NULL,
        ua VARCHAR(255) NOT NULL DEFAULT '',
        snap_type VARCHAR(20) NOT NULL DEFAULT '',
        snapshot LONGTEXT NULL,
        undone TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY created (created),
        KEY event (event),
        KEY user_id (user_id),
        KEY snap_type (snap_type)
    ) {$collate};");
    update_option('zc_audit_db', '2');
}

add_action('init', function () {
    if (get_option('zc_audit_db') !== '2') zc_audit_install();
});

/** Skutočná IP adresa aj za proxy / Cloudflare. */
function zc_audit_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (empty($_SERVER[$k])) continue;
        foreach (explode(',', (string) $_SERVER[$k]) as $part) {
            $ip = trim($part);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '';
}

/** Ľudský názov udalosti. */
function zc_audit_events() {
    return [
        'login'          => ['Prihlásenie',            '#15803d'],
        'login_failed'   => ['Neúspešné prihlásenie',  '#b91c1c'],
        'logout'         => ['Odhlásenie',             '#6b7280'],
        'post_new'       => ['Nový obsah',             '#7C5E33'],
        'post_edit'      => ['Úprava obsahu',          '#7C5E33'],
        'post_trash'     => ['Presun do koša',         '#b45309'],
        'post_delete'    => ['Trvalé zmazanie',        '#b91c1c'],
        'media'          => ['Nahratý súbor',          '#4338CA'],
        'review'         => ['Recenzia',               '#7C5E33'],
        'newsletter'     => ['Newsletter',             '#4338CA'],
        'settings'       => ['Zmena nastavenia',       '#b45309'],
        'user_new'       => ['Nový používateľ',        '#4338CA'],
        'user_edit'      => ['Úprava používateľa',     '#b45309'],
        'user_delete'    => ['Zmazaný používateľ',     '#b91c1c'],
        'plugin'         => ['Plugin / téma',          '#b45309'],
        'undo'           => ['Vrátená zmena',          '#15803d'],
        'other'          => ['Ostatné',                '#6b7280'],
    ];
}

/**
 * Zapíše záznam do denníka.
 *
 * @param string $event   kľúč z zc_audit_events()
 * @param string $object  čoho sa to týka (názov ponuky, meno používateľa…)
 * @param string $detail  doplňujúci text
 * @param int    $obj_id  ID objektu
 * @param int    $user_id koho sa to týka (0 = prihlásený)
 */
function zc_audit_log($event, $object = '', $detail = '', $obj_id = 0, $user_id = null) {
    global $wpdb;
    if (get_option('zc_audit_on', '1') !== '1') return;
    // Počas vracania zmeny nezaznamenávame – inak by vznikol záznam o zázname
    if (!empty($GLOBALS['zc_audit_restoring'])) return;

    $user  = ($user_id === null) ? wp_get_current_user() : get_userdata($user_id);
    $login = $user && !empty($user->user_login) ? $user->user_login : '';
    $roles = $user && !empty($user->roles) ? implode(', ', (array) $user->roles) : '';

    // Stav pred zmenou – vďaka nemu sa dá zmena vrátiť
    $snap      = zc_audit_take_snapshot();
    $snap_type = '';
    $snap_json = null;
    if ($snap) {
        $json = wp_json_encode($snap['data'], JSON_UNESCAPED_UNICODE);
        if ($json !== false && strlen($json) <= 900000) {
            $snap_type = $snap['type'];
            $snap_json = $json;
        }
    }

    $wpdb->insert(zc_audit_table(), [
        'created'    => current_time('mysql'),
        'user_id'    => $user && $user->ID ? (int) $user->ID : 0,
        'user_login' => substr($login, 0, 120),
        'user_role'  => substr($roles, 0, 80),
        'ip'         => zc_audit_ip(),
        'event'      => substr($event, 0, 40),
        'object_id'  => (int) $obj_id,
        'object'     => substr(wp_strip_all_tags((string) $object), 0, 190),
        'detail'     => wp_strip_all_tags((string) $detail),
        'ua'         => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'snap_type'  => $snap_type,
        'snapshot'   => $snap_json,
    ]);
}

/* ───────────────────── Snímky – aby sa zmena dala vrátiť ───────────────────── */

/** Odloží stav pred zmenou; najbližší zápis do denníka si ho vezme. */
function zc_audit_stage($type, $data) {
    if (!empty($GLOBALS['zc_audit_restoring'])) return;
    $GLOBALS['zc_audit_snap'] = ['type' => $type, 'data' => $data];
}

/** Vyberie odloženú snímku (a zahodí ju, nech sa nepoužije dvakrát). */
function zc_audit_take_snapshot() {
    if (empty($GLOBALS['zc_audit_snap'])) return null;
    $snap = $GLOBALS['zc_audit_snap'];
    unset($GLOBALS['zc_audit_snap']);
    return $snap;
}

/** Celý príspevok aj s metadátami – presne tak, ako vyzeral pred zmenou. */
function zc_audit_post_snapshot($id) {
    $p = get_post($id);
    if (!$p) return null;
    $meta = [];
    foreach (get_post_meta($p->ID) as $k => $v) {
        if (strpos($k, '_edit_') === 0) continue;
        $meta[$k] = maybe_unserialize($v[0]);
    }
    return [
        'ID'             => $p->ID,
        'post_type'      => $p->post_type,
        'post_title'     => $p->post_title,
        'post_name'      => $p->post_name,
        'post_content'   => $p->post_content,
        'post_excerpt'   => $p->post_excerpt,
        'post_status'    => $p->post_status,
        'post_author'    => $p->post_author,
        'post_parent'    => $p->post_parent,
        'menu_order'     => $p->menu_order,
        'post_date'      => $p->post_date,
        'post_date_gmt'  => $p->post_date_gmt,
        'comment_status' => $p->comment_status,
        'ping_status'    => $p->ping_status,
        'meta'           => $meta,
    ];
}

/** Typy obsahu, ktoré sa oplatí snímať. */
function zc_audit_snap_types() {
    return apply_filters('zc_audit_snap_types', ['property', 'pp_lead', 'page', 'post']);
}

/* ───────────────────────── Čo sledujeme ───────────────────────── */

// Prihlásenie / odhlásenie
add_action('wp_login', function ($login, $user) {
    zc_audit_log('login', $login, 'Úspešné prihlásenie', 0, $user ? $user->ID : null);
}, 10, 2);

add_action('wp_login_failed', function ($login) {
    zc_audit_log('login_failed', $login, 'Zadané zlé meno alebo heslo', 0, 0);
});

add_action('wp_logout', function ($user_id) {
    zc_audit_log('logout', '', 'Odhlásenie', 0, $user_id ?: null);
});

// Obsah – ponuky, stránky, príspevky, dopyty
add_action('transition_post_status', function ($new, $old, $post) {
    if (!$post instanceof WP_Post) return;
    $skip = ['revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache',
             'user_request', 'wp_global_styles', 'wp_template', 'wp_template_part'];
    if (in_array($post->post_type, $skip, true)) return;
    if ($new === 'auto-draft' || ($old === 'new' && $new === 'draft')) return;
    if ($new === $old && $new !== 'publish') return;

    $obj = get_post_type_object($post->post_type);
    $typ = $obj ? $obj->labels->singular_name : $post->post_type;

    if ($new === 'trash')            $event = 'post_trash';
    elseif ($old === 'auto-draft' || $old === 'new') $event = 'post_new';
    else                             $event = 'post_edit';

    zc_audit_log($event, $post->post_title, $typ . ' · stav: ' . $new, $post->ID);
}, 10, 3);

// Pred úpravou si odložíme, ako obsah vyzeral doteraz
add_action('pre_post_update', function ($id, $data = []) {
    $post = get_post($id);
    if (!$post || !in_array($post->post_type, zc_audit_snap_types(), true)) return;
    if (wp_is_post_autosave($id) || wp_is_post_revision($id)) return;
    $snap = zc_audit_post_snapshot($id);
    if ($snap) zc_audit_stage('post', $snap);
}, 10, 2);

// Pred presunom do koša
add_action('wp_trash_post', function ($id) {
    $post = get_post($id);
    if (!$post || !in_array($post->post_type, zc_audit_snap_types(), true)) return;
    $snap = zc_audit_post_snapshot($id);
    if ($snap) zc_audit_stage('post', $snap);
});

add_action('before_delete_post', function ($id, $post = null) {
    $post = $post ?: get_post($id);
    if (!$post || $post->post_type === 'revision') return;
    if (in_array($post->post_type, zc_audit_snap_types(), true)) {
        $snap = zc_audit_post_snapshot($id);
        if ($snap) zc_audit_stage('post', $snap);
    }
    zc_audit_log('post_delete', $post->post_title, 'Zmazané natrvalo (' . $post->post_type . ')', $id);
}, 10, 2);

add_action('add_attachment', function ($id) {
    zc_audit_log('media', get_the_title($id), 'Nahratý súbor', $id);
});

// Používatelia
add_action('user_register', function ($id) {
    $u = get_userdata($id);
    zc_audit_log('user_new', $u ? $u->user_login : '#' . $id, 'Vytvorený účet', $id);
});
add_action('profile_update', function ($id) {
    $u = get_userdata($id);
    zc_audit_log('user_edit', $u ? $u->user_login : '#' . $id, 'Upravený profil', $id);
});
add_action('set_user_role', function ($id, $role, $old) {
    $u = get_userdata($id);
    if ($old) zc_audit_stage('user_roles', ['user_id' => (int) $id, 'roles' => array_values((array) $old)]);
    zc_audit_log('user_edit', $u ? $u->user_login : '#' . $id,
        'Zmena roly: ' . implode(', ', (array) $old) . ' → ' . $role, $id);
}, 10, 3);
add_action('deleted_user', function ($id) {
    zc_audit_log('user_delete', '#' . $id, 'Účet zmazaný', $id);
});

// Pluginy a téma
add_action('activated_plugin',   function ($p) { zc_audit_log('plugin', $p, 'Plugin zapnutý'); });
add_action('deactivated_plugin', function ($p) { zc_audit_log('plugin', $p, 'Plugin vypnutý'); });
add_action('switch_theme',       function ($n) { zc_audit_log('plugin', $n, 'Zmenená téma'); });

// Nastavenia – len naše vlastné, nie interné veci WordPressu
add_action('updated_option', function ($name, $old, $new) {
    if (!is_user_logged_in()) return;
    if (!preg_match('/^(zc_|zcr_|zcn_|pp_|theme_mods_|blogname|blogdescription|admin_email|users_can_register|default_role)/', $name)) return;
    if (strpos($name, 'zc_audit') === 0 || strpos($name, '_transient') === 0) return;
    if (in_array($name, ['pp_activity_log', 'zc_cache_version'], true)) return;

    $short = function ($v) {
        if (is_scalar($v)) return substr((string) $v, 0, 120);
        return substr((string) wp_json_encode($v), 0, 120);
    };
    zc_audit_stage('option', ['name' => $name, 'value' => $old]);
    zc_audit_log('settings', $name, 'z „' . $short($old) . '" na „' . $short($new) . '"');
}, 10, 3);

/* ───────────────────── Vrátenie zmeny (undo) ───────────────────── */

/** Koľko dní sa dá zmena vrátiť. */
function zc_audit_undo_days() {
    return max(1, min(90, (int) get_option('zc_audit_undo_days', 7)));
}

/** Dá sa tento záznam ešte vrátiť? */
function zc_audit_can_undo($row) {
    if (empty($row->snapshot) || empty($row->snap_type) || !empty($row->undone)) return false;
    return strtotime($row->created) >= strtotime('-' . zc_audit_undo_days() . ' days', current_time('timestamp'));
}

/** Vráti zmenu podľa uloženej snímky. Vracia [ok, správa]. */
function zc_audit_undo($log_id) {
    global $wpdb;
    $t   = zc_audit_table();
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", (int) $log_id));
    if (!$row)                  return [false, 'Záznam sa nenašiel.'];
    if (!zc_audit_can_undo($row)) return [false, 'Túto zmenu sa už vrátiť nedá (uplynula lehota alebo už bola vrátená).'];

    $data = json_decode($row->snapshot, true);
    if (!is_array($data)) return [false, 'Uložený stav sa nepodarilo prečítať.'];

    $GLOBALS['zc_audit_restoring'] = true;
    switch ($row->snap_type) {
        case 'post':   $res = zc_audit_undo_post($data);   break;
        case 'review': $res = zc_audit_undo_review($data); break;
        case 'option':
            update_option($data['name'], $data['value']);
            $res = [true, 'Nastavenie „' . $data['name'] . '" je späť na pôvodnej hodnote.'];
            break;
        case 'user_roles':
            $u = new WP_User((int) $data['user_id']);
            if (!$u->ID) { $res = [false, 'Používateľ už neexistuje.']; break; }
            $u->set_role('');
            foreach ((array) $data['roles'] as $r) $u->add_role($r);
            $res = [true, 'Roly používateľa sú späť: ' . implode(', ', (array) $data['roles']) . '.'];
            break;
        default:
            $res = [false, 'Tento typ zmeny sa vrátiť nedá.'];
    }
    unset($GLOBALS['zc_audit_restoring']);

    if ($res[0]) {
        $wpdb->update($t, ['undone' => 1], ['id' => (int) $row->id]);
        zc_audit_log('undo', $row->object, 'Vrátená zmena z ' . $row->created . ' · ' . $res[1], (int) $row->object_id);
    }
    return $res;
}

/** Obnoví príspevok – aj taký, ktorý bol zmazaný natrvalo. */
function zc_audit_undo_post($d) {
    $id   = (int) ($d['ID'] ?? 0);
    $post = $id ? get_post($id) : null;

    $args = [
        'post_type'      => $d['post_type'] ?? 'post',
        'post_title'     => $d['post_title'] ?? '',
        'post_name'      => $d['post_name'] ?? '',
        'post_content'   => $d['post_content'] ?? '',
        'post_excerpt'   => $d['post_excerpt'] ?? '',
        'post_status'    => $d['post_status'] ?? 'publish',
        'post_author'    => $d['post_author'] ?? get_current_user_id(),
        'post_parent'    => $d['post_parent'] ?? 0,
        'menu_order'     => $d['menu_order'] ?? 0,
        'post_date'      => $d['post_date'] ?? current_time('mysql'),
        'comment_status' => $d['comment_status'] ?? 'closed',
        'ping_status'    => $d['ping_status'] ?? 'closed',
    ];

    if ($post) {
        $args['ID'] = $id;
        $new_id = wp_update_post($args, true);
    } else {
        $args['import_id'] = $id;         // vrátime aj pôvodné ID, nech sedia odkazy
        $new_id = wp_insert_post($args, true);
    }
    if (is_wp_error($new_id) || !$new_id) {
        return [false, 'Obsah sa nepodarilo obnoviť.'];
    }

    // Metadáta vrátime presne do pôvodného stavu
    $snap_meta = (array) ($d['meta'] ?? []);
    foreach (get_post_meta($new_id) as $k => $v) {
        if (strpos($k, '_edit_') === 0) continue;
        if (!array_key_exists($k, $snap_meta)) delete_post_meta($new_id, $k);
    }
    foreach ($snap_meta as $k => $v) update_post_meta($new_id, $k, $v);

    $title = $d['post_title'] ?: '#' . $new_id;
    return [true, $post ? 'Obsah „' . $title . '" je späť v pôvodnom stave.'
                        : 'Zmazaný obsah „' . $title . '" bol obnovený.'];
}

/** Obnoví recenziu (aj zmazanú). */
function zc_audit_undo_review($d) {
    global $wpdb;
    if (!function_exists('zcr_table')) return [false, 'Plugin ZC Recenzie nie je aktívny.'];
    $t  = zcr_table();
    $id = (int) ($d['id'] ?? 0);
    if (!$id) return [false, 'Neznáma recenzia.'];

    $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE id = %d", $id));
    if ($exists) {
        $wpdb->update($t, $d, ['id' => $id]);
    } else {
        $wpdb->insert($t, $d);
    }
    return [true, 'Recenzia od „' . ($d['author_name'] ?? '') . '" je späť.'];
}

add_action('admin_post_zc_audit_undo', function () {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    $id = (int) ($_GET['log'] ?? 0);
    check_admin_referer('zc_audit_undo_' . $id);
    list($ok, $msg) = zc_audit_undo($id);
    wp_safe_redirect(add_query_arg([
        'page'      => 'zc-dennik',
        'undo_ok'   => $ok ? 1 : 0,
        'undo_msg'  => rawurlencode($msg),
    ], admin_url('admin.php')));
    exit;
});

/* ───────────────────────── Upratovanie (30 dní) ───────────────────────── */

add_action('zc_audit_cleanup', 'zc_audit_cleanup');
function zc_audit_cleanup() {
    global $wpdb;
    $t    = zc_audit_table();
    $days = max(1, (int) get_option('zc_audit_days', ZC_AUDIT_DAYS));
    $wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE created < DATE_SUB(NOW(), INTERVAL %d DAY)", $days));

    // Po uplynutí lehoty na vrátenie zahodíme uložené stavy – záznam v denníku
    // ostáva, len sa už nedá vrátiť. Databáza tak zbytočne nerastie.
    $wpdb->query($wpdb->prepare(
        "UPDATE {$t} SET snapshot = NULL, snap_type = ''
         WHERE snapshot IS NOT NULL AND created < DATE_SUB(NOW(), INTERVAL %d DAY)",
        zc_audit_undo_days()
    ));
}

add_action('init', function () {
    if (!wp_next_scheduled('zc_audit_cleanup')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'zc_audit_cleanup');
    }
}, 20);

/* ───────────────────────── Stránka vo wp-admine ───────────────────────── */

add_action('admin_menu', function () {
    $parent = function_exists('zc_hub_slug') ? zc_hub_slug() : 'tools.php';
    add_submenu_page($parent, 'Bezpečnostný denník', 'Denník', 'manage_options',
        'zc-dennik', 'zc_audit_admin_page');
}, 12);

/** Export do CSV. */
add_action('admin_post_zc_audit_export', function () {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    check_admin_referer('zc_audit_export');
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . zc_audit_table() . ' ORDER BY id DESC', ARRAY_A);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=dennik-' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM, nech Excel zobrazí diakritiku
    fputcsv($out, ['Kedy', 'Používateľ', 'Rola', 'IP', 'Udalosť', 'Čoho sa týka', 'Detail']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['created'], $r['user_login'], $r['user_role'], $r['ip'],
                       $r['event'], $r['object'], $r['detail']]);
    }
    fclose($out);
    exit;
});

/** Vyprázdnenie denníka. */
add_action('admin_post_zc_audit_clear', function () {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    check_admin_referer('zc_audit_clear');
    global $wpdb;
    $wpdb->query('TRUNCATE TABLE ' . zc_audit_table());
    zc_audit_log('settings', 'Bezpečnostný denník', 'Denník bol vyprázdnený');
    wp_safe_redirect(admin_url('admin.php?page=zc-dennik&cleared=1'));
    exit;
});

function zc_audit_admin_page() {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    global $wpdb;
    $t      = zc_audit_table();
    $events = zc_audit_events();

    if (isset($_POST['zc_audit_settings']) && check_admin_referer('zc_audit_set')) {
        update_option('zc_audit_on', empty($_POST['on']) ? '0' : '1');
        update_option('zc_audit_days', max(1, min(365, (int) ($_POST['days'] ?? 30))));
        update_option('zc_audit_undo_days', max(1, min(90, (int) ($_POST['undo_days'] ?? 7))));
        echo '<div class="notice notice-success"><p>Nastavenie uložené.</p></div>';
    }
    if (!empty($_GET['cleared'])) {
        echo '<div class="notice notice-success"><p>Denník bol vyprázdnený.</p></div>';
    }
    if (isset($_GET['undo_msg'])) {
        printf('<div class="notice notice-%s"><p>%s</p></div>',
            empty($_GET['undo_ok']) ? 'error' : 'success',
            esc_html(rawurldecode((string) $_GET['undo_msg'])));
    }

    $f_event = sanitize_key($_GET['event'] ?? '');
    $f_user  = (int) ($_GET['u'] ?? 0);
    $f_q     = sanitize_text_field($_GET['q'] ?? '');
    $f_undo  = !empty($_GET['undoable']);
    $undo_d  = zc_audit_undo_days();
    $paged   = max(1, (int) ($_GET['paged'] ?? 1));
    $per     = 50;

    $where = ['1=1'];
    $args  = [];
    if ($f_event && isset($events[$f_event])) { $where[] = 'event = %s';   $args[] = $f_event; }
    if ($f_user)                              { $where[] = 'user_id = %d'; $args[] = $f_user; }
    if ($f_q !== '') {
        $like    = '%' . $wpdb->esc_like($f_q) . '%';
        $where[] = '(object LIKE %s OR detail LIKE %s OR user_login LIKE %s OR ip LIKE %s)';
        array_push($args, $like, $like, $like, $like);
    }
    if ($f_undo) {
        $where[] = "snapshot IS NOT NULL AND undone = 0 AND created >= DATE_SUB(NOW(), INTERVAL %d DAY)";
        $args[]  = $undo_d;
    }
    $wsql = implode(' AND ', $where);

    $total_sql = "SELECT COUNT(*) FROM {$t} WHERE {$wsql}";
    $total = (int) ($args ? $wpdb->get_var($wpdb->prepare($total_sql, $args)) : $wpdb->get_var($total_sql));

    $list_args = array_merge($args, [$per, ($paged - 1) * $per]);
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$t} WHERE {$wsql} ORDER BY id DESC LIMIT %d OFFSET %d", $list_args
    ));

    $pages   = max(1, (int) ceil($total / $per));
    $on      = get_option('zc_audit_on', '1') === '1';
    $days    = (int) get_option('zc_audit_days', ZC_AUDIT_DAYS);
    $users   = $wpdb->get_results("SELECT DISTINCT user_id, user_login FROM {$t} WHERE user_id > 0 ORDER BY user_login");
    $base_url = admin_url('admin.php?page=zc-dennik');
    ?>
    <div class="wrap zc-aud">
        <h1>Bezpečnostný denník</h1>
        <p class="zc-aud-sub">
            Kto sa kedy prihlásil, odkiaľ a čo na webe zmenil.
            Záznamy sa automaticky mažú po <strong><?php echo (int) $days ?> dňoch</strong>.
            Zmeny označené <span class="zc-aud-undo-hint">Vrátiť</span> sa dajú vrátiť do
            <strong><?php echo (int) $undo_d ?> dní</strong> – aj natrvalo zmazané ponuky.
            Momentálne je záznamov <strong><?php echo number_format_i18n($total) ?></strong>.
        </p>

        <form method="get" class="zc-aud-bar">
            <input type="hidden" name="page" value="zc-dennik">
            <select name="event">
                <option value="">Všetky udalosti</option>
                <?php foreach ($events as $k => $e): ?>
                <option value="<?php echo esc_attr($k) ?>" <?php selected($f_event, $k) ?>><?php echo esc_html($e[0]) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="u">
                <option value="0">Všetci používatelia</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo (int) $u->user_id ?>" <?php selected($f_user, (int) $u->user_id) ?>><?php echo esc_html($u->user_login) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?php echo esc_attr($f_q) ?>" placeholder="Hľadať v texte alebo IP…">
            <label class="zc-aud-only">
                <input type="checkbox" name="undoable" value="1" <?php checked($f_undo) ?>>
                <span>Len vrátiteľné</span>
            </label>
            <button class="button">Filtrovať</button>
            <?php if ($f_event || $f_user || $f_q !== '' || $f_undo): ?>
            <a href="<?php echo esc_url($base_url) ?>" class="button-link">Zrušiť filter</a>
            <?php endif; ?>
            <span class="zc-aud-spacer"></span>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zc_audit_export'), 'zc_audit_export')) ?>">Stiahnuť CSV</a>
            <a class="button" style="color:#b91c1c"
               onclick="return confirm('Naozaj zmazať všetky záznamy denníka?')"
               href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=zc_audit_clear'), 'zc_audit_clear')) ?>">Vyprázdniť</a>
        </form>

        <table class="widefat striped zc-aud-tbl">
            <thead><tr>
                <th style="width:150px">Kedy</th>
                <th style="width:150px">Kto</th>
                <th style="width:130px">IP</th>
                <th style="width:160px">Udalosť</th>
                <th>Čoho sa to týka</th>
                <th style="width:110px">Vrátiť</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" style="padding:30px;text-align:center;color:#777">Žiadne záznamy.</td></tr>
            <?php else: foreach ($rows as $r):
                $e = $events[$r->event] ?? [$r->event, '#6b7280'];
                $ts = strtotime($r->created); ?>
                <tr>
                    <td>
                        <?php echo esc_html(date_i18n('j.n.Y H:i:s', $ts)) ?><br>
                        <small style="color:#888"><?php echo esc_html(human_time_diff($ts, current_time('timestamp'))) ?> dozadu</small>
                    </td>
                    <td>
                        <strong><?php echo esc_html($r->user_login ?: '—') ?></strong>
                        <?php if ($r->user_role): ?><br><small style="color:#888"><?php echo esc_html($r->user_role) ?></small><?php endif; ?>
                    </td>
                    <td><code><?php echo esc_html($r->ip ?: '—') ?></code></td>
                    <td><span class="zc-aud-tag" style="background:<?php echo esc_attr($e[1]) ?>1a;color:<?php echo esc_attr($e[1]) ?>"><?php echo esc_html($e[0]) ?></span></td>
                    <td>
                        <?php if ($r->object): ?><strong><?php echo esc_html($r->object) ?></strong><?php endif; ?>
                        <?php if ($r->detail): ?><div style="color:#666;font-size:12.5px"><?php echo esc_html($r->detail) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($r->undone)): ?>
                            <span class="zc-aud-done">Vrátené</span>
                        <?php elseif (zc_audit_can_undo($r)):
                            $url = wp_nonce_url(
                                admin_url('admin-post.php?action=zc_audit_undo&log=' . (int) $r->id),
                                'zc_audit_undo_' . (int) $r->id
                            ); ?>
                            <a class="button button-small" href="<?php echo esc_url($url) ?>"
                               onclick="return confirm('Vrátiť túto zmenu do pôvodného stavu?')">Vrátiť</a>
                        <?php else: ?>
                            <span style="color:#bbb">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?php echo paginate_links([
                'base'    => add_query_arg('paged', '%#%'),
                'format'  => '',
                'current' => $paged,
                'total'   => $pages,
                'prev_text' => '‹',
                'next_text' => '›',
            ]); ?>
        </div></div>
        <?php endif; ?>

        <form method="post" class="zc-aud-set">
            <?php wp_nonce_field('zc_audit_set') ?>
            <h2>Nastavenie</h2>
            <label class="zc-aud-chk">
                <input type="checkbox" name="on" value="1" <?php checked($on) ?>>
                <span>Zaznamenávať udalosti</span>
            </label>
            <label class="zc-aud-days">
                Uchovávať záznamy
                <input type="number" name="days" min="1" max="365" value="<?php echo (int) $days ?>"> dní
            </label>
            <label class="zc-aud-days">
                Zmeny sa dajú vrátiť
                <input type="number" name="undo_days" min="1" max="90" value="<?php echo (int) $undo_d ?>"> dní
            </label>
            <button class="button button-primary" name="zc_audit_settings" value="1">Uložiť</button>
        </form>
    </div>
    <style>
    .zc-aud-sub{max-width:760px;color:#555;font-size:13.5px}
    .zc-aud-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:16px 0 12px}
    .zc-aud-bar select,.zc-aud-bar input[type=search]{padding:5px 8px;min-width:170px}
    .zc-aud-spacer{flex:1}
    .zc-aud-tbl td{vertical-align:top;padding:10px}
    .zc-aud-tag{display:inline-block;padding:3px 10px;border-radius:50px;font-size:11.5px;font-weight:700;white-space:nowrap}
    .zc-aud-set{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 20px;margin-top:22px;max-width:640px;
        display:flex;gap:18px;align-items:center;flex-wrap:wrap}
    .zc-aud-set h2{width:100%;margin:0;font-size:15px}
    .zc-aud-chk{display:flex;align-items:center;gap:8px;font-size:13.5px}
    .zc-aud-days input{width:70px}
    .zc-aud-only{display:flex;align-items:center;gap:7px;font-size:13px;white-space:nowrap}
    .zc-aud-done{display:inline-block;padding:3px 10px;border-radius:50px;font-size:11.5px;
        font-weight:700;background:#dcfce7;color:#15803d}
    .zc-aud-undo-hint{display:inline-block;padding:1px 8px;border-radius:50px;font-size:11.5px;
        font-weight:700;background:#f1f5f9;color:#475569}
    @media(max-width:782px){.zc-aud-bar select,.zc-aud-bar input[type=search]{min-width:0;flex:1}}
    </style>
    <?php
}
