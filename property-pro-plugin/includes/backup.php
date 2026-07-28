<?php
/**
 * Denná záloha celého realitného panela – ponuky, formuláre, recenzie,
 * newsletter aj nastavenia. Drží sa posledných 30 záloh, staršie sa mažú.
 */
defined('ABSPATH') || exit;

define('ZC_BACKUP_MAX', 30);

/** Priečinok so zálohami (chránený pred priamym stiahnutím). */
function zc_backup_dir() {
    $up  = wp_upload_dir();
    $dir = trailingslashit($up['basedir']) . 'zc-zalohy';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        // Zálohy sa dajú stiahnuť len cez wp-admin, nie priamym odkazom
        file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
        file_put_contents($dir . '/index.php', "<?php // Ticho.\n");
    }
    return $dir;
}

/* ───────────────────────── Zber údajov ───────────────────────── */

/** Príspevky daného typu aj s metadátami. */
function zc_backup_posts($type) {
    $out   = [];
    $posts = get_posts([
        'post_type'   => $type,
        'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
        'numberposts' => -1,
        'orderby'     => 'ID',
        'order'       => 'ASC',
    ]);
    foreach ($posts as $p) {
        $meta = [];
        foreach (get_post_meta($p->ID) as $k => $v) {
            $meta[$k] = maybe_unserialize($v[0]);
        }
        $out[] = [
            'ID'           => $p->ID,
            'post_title'   => $p->post_title,
            'post_name'    => $p->post_name,
            'post_content' => $p->post_content,
            'post_excerpt' => $p->post_excerpt,
            'post_status'  => $p->post_status,
            'post_date'    => $p->post_date,
            'post_author'  => $p->post_author,
            'thumbnail'    => get_post_thumbnail_id($p->ID) ?: 0,
            'meta'         => $meta,
        ];
    }
    return $out;
}

/** Celá tabuľka ako pole riadkov (ak existuje). */
function zc_backup_table($table) {
    global $wpdb;
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return null;
    return $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
}

/** Naše nastavenia – všetko s prefixom zc_ / zcr_ / zcn_ / pp_. */
function zc_backup_options() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
         WHERE (option_name LIKE 'zc\_%' OR option_name LIKE 'zcr\_%'
             OR option_name LIKE 'zcn\_%' OR option_name LIKE 'pp\_%')
           AND option_name NOT LIKE '%transient%'
           AND option_name NOT LIKE 'zc\_audit%'
           AND option_name NOT LIKE 'zc\_backup%'",
        ARRAY_A
    );
    $out = [];
    foreach ($rows as $r) $out[$r['option_name']] = maybe_unserialize($r['option_value']);
    return $out;
}

function zc_backup_collect() {
    $data = [
        'meta' => [
            'created' => current_time('mysql'),
            'site'    => home_url(),
            'wp'      => get_bloginfo('version'),
            'version' => 2,
        ],
        'ponuky'     => zc_backup_posts('property'),
        'formulare'  => post_type_exists('pp_lead') ? zc_backup_posts('pp_lead') : [],
        'recenzie'   => function_exists('zcr_table') ? zc_backup_table(zcr_table()) : null,
        'newsletter' => function_exists('zcn_table') ? zc_backup_table(zcn_table()) : null,
        'nastavenia' => zc_backup_options(),
        'vzhlad'     => get_theme_mods() ?: [],
    ];
    return $data;
}

/* ───────────────────────── Vytvorenie a údržba ───────────────────────── */

/** Spustí zálohu. Vracia [ok, správa, názov súboru]. */
function zc_backup_run($trigger = 'cron') {
    $data = zc_backup_collect();
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return [false, 'Údaje sa nepodarilo previesť do JSON.', ''];

    $name = 'zaloha-' . date_i18n('Y-m-d-Hi') . '-' . wp_generate_password(6, false) . '.json';
    $blob = $json;
    if (function_exists('gzencode')) {
        $gz = gzencode($json, 6);
        if ($gz !== false) { $blob = $gz; $name .= '.gz'; }
    }

    $path = trailingslashit(zc_backup_dir()) . $name;
    if (file_put_contents($path, $blob) === false) {
        update_option('zc_backup_last_error', 'Do priečinka so zálohami sa nedá zapisovať.');
        return [false, 'Do priečinka so zálohami sa nedá zapisovať.', ''];
    }

    delete_option('zc_backup_last_error');
    update_option('zc_backup_last', current_time('mysql'));
    zc_backup_prune();

    if (function_exists('zc_audit_log')) {
        zc_audit_log('settings', 'Záloha panela',
            ($trigger === 'manual' ? 'Ručná záloha' : 'Automatická denná záloha') . ' · ' . $name);
    }
    return [true, 'Záloha vytvorená.', $name];
}

/** Nechá len posledných N záloh. */
function zc_backup_prune($max = ZC_BACKUP_MAX) {
    $max   = max(1, (int) get_option('zc_backup_max', $max));
    $files = zc_backup_list();
    foreach (array_slice($files, $max) as $f) {
        @unlink($f['path']);
    }
}

/** Zoznam záloh, najnovšie prvé. */
function zc_backup_list() {
    $dir   = zc_backup_dir();
    $out   = [];
    foreach ((array) glob($dir . '/zaloha-*.json*') as $path) {
        if (!is_file($path)) continue;
        $out[] = [
            'name' => basename($path),
            'path' => $path,
            'size' => filesize($path),
            'time' => filemtime($path),
        ];
    }
    usort($out, function ($a, $b) { return $b['time'] <=> $a['time']; });
    return $out;
}

/** Bezpečne získa cestu k zálohe podľa názvu (žiadne vychádzanie z priečinka). */
function zc_backup_path($name) {
    $name = basename((string) $name);
    if (!preg_match('/^zaloha-[\w\-]+\.json(\.gz)?$/', $name)) return '';
    $path = trailingslashit(zc_backup_dir()) . $name;
    return file_exists($path) ? $path : '';
}

/** Načíta obsah zálohy do poľa. */
function zc_backup_read($name) {
    $path = zc_backup_path($name);
    if (!$path) return null;
    $blob = file_get_contents($path);
    if ($blob === false) return null;
    if (substr($path, -3) === '.gz' && function_exists('gzdecode')) {
        $un = @gzdecode($blob);
        if ($un !== false) $blob = $un;
    }
    $data = json_decode($blob, true);
    return is_array($data) ? $data : null;
}

/* ───────────────────────── Obnova ───────────────────────── */

/**
 * Obnoví vybrané časti zo zálohy.
 * Ponuky a formuláre sa dopĺňajú/aktualizujú – nič, čo v zálohe nie je, sa nemaže.
 * Tabuľky recenzií a newslettera sa nahradia obsahom zo zálohy.
 */
function zc_backup_restore($name, $parts) {
    global $wpdb;
    $data = zc_backup_read($name);
    if (!$data) return [false, 'Zálohu sa nepodarilo prečítať.'];

    $done = [];

    if (in_array('ponuky', $parts, true) && !empty($data['ponuky'])) {
        $done[] = zc_backup_restore_posts($data['ponuky'], 'property') . ' ponúk';
    }
    if (in_array('formulare', $parts, true) && !empty($data['formulare'])) {
        $done[] = zc_backup_restore_posts($data['formulare'], 'pp_lead') . ' formulárov';
    }
    if (in_array('recenzie', $parts, true) && !empty($data['recenzie']) && function_exists('zcr_table')) {
        $t = zcr_table();
        $wpdb->query("DELETE FROM {$t}");
        foreach ($data['recenzie'] as $row) $wpdb->insert($t, $row);
        $done[] = count($data['recenzie']) . ' recenzií';
    }
    if (in_array('newsletter', $parts, true) && !empty($data['newsletter']) && function_exists('zcn_table')) {
        $t = zcn_table();
        $wpdb->query("DELETE FROM {$t}");
        foreach ($data['newsletter'] as $row) $wpdb->insert($t, $row);
        $done[] = count($data['newsletter']) . ' odberateľov';
    }
    if (in_array('nastavenia', $parts, true) && !empty($data['nastavenia'])) {
        foreach ($data['nastavenia'] as $k => $v) update_option($k, $v);
        $done[] = count($data['nastavenia']) . ' nastavení';
    }
    if (in_array('vzhlad', $parts, true) && !empty($data['vzhlad'])) {
        foreach ($data['vzhlad'] as $k => $v) set_theme_mod($k, $v);
        $done[] = 'vzhľad';
    }

    if (function_exists('zc_audit_log')) {
        zc_audit_log('settings', 'Obnova zo zálohy', $name . ' · ' . implode(', ', $done));
    }
    return [true, $done ? 'Obnovené: ' . implode(', ', $done) . '.' : 'Nebolo čo obnoviť.'];
}

function zc_backup_restore_posts($items, $type) {
    $n = 0;
    foreach ($items as $it) {
        $id   = (int) ($it['ID'] ?? 0);
        $post = $id ? get_post($id) : null;
        $args = [
            'post_type'    => $type,
            'post_title'   => (string) ($it['post_title'] ?? ''),
            'post_name'    => (string) ($it['post_name'] ?? ''),
            'post_content' => (string) ($it['post_content'] ?? ''),
            'post_excerpt' => (string) ($it['post_excerpt'] ?? ''),
            'post_status'  => (string) ($it['post_status'] ?? 'publish'),
            'post_date'    => (string) ($it['post_date'] ?? current_time('mysql')),
        ];
        if ($post && $post->post_type === $type) {
            $args['ID'] = $id;
            $new_id = wp_update_post($args);
        } else {
            $new_id = wp_insert_post($args);
        }
        if (!$new_id || is_wp_error($new_id)) continue;

        foreach ((array) ($it['meta'] ?? []) as $k => $v) {
            if (strpos($k, '_edit_') === 0) continue;
            update_post_meta($new_id, $k, $v);
        }
        if (!empty($it['thumbnail'])) set_post_thumbnail($new_id, (int) $it['thumbnail']);
        $n++;
    }
    return $n;
}

/* ───────────────────────── Cron ───────────────────────── */

add_action('zc_backup_daily', function () { zc_backup_run('cron'); });

add_action('init', function () {
    if (get_option('zc_backup_on', '1') !== '1') return;
    if (!wp_next_scheduled('zc_backup_daily')) {
        wp_schedule_event(strtotime('tomorrow 3:20'), 'daily', 'zc_backup_daily');
    }
}, 20);

/* ───────────────────────── Stránka vo wp-admine ───────────────────────── */

add_action('admin_menu', function () {
    $parent = function_exists('zc_hub_slug') ? zc_hub_slug() : 'tools.php';
    add_submenu_page($parent, 'Zálohy panela', 'Zálohy', 'manage_options',
        'zc-zalohy', 'zc_backup_admin_page');
}, 13);

add_action('admin_post_zc_backup_download', function () {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    check_admin_referer('zc_backup_dl');
    $path = zc_backup_path($_GET['file'] ?? '');
    if (!$path) wp_die('Záloha sa nenašla.');

    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($path));
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
});

function zc_backup_admin_page() {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');

    $notice = '';
    $error  = '';

    if (isset($_POST['zc_backup_now']) && check_admin_referer('zc_backup')) {
        list($ok, $msg) = zc_backup_run('manual');
        if ($ok) $notice = $msg; else $error = $msg;
    }
    if (isset($_POST['zc_backup_delete']) && check_admin_referer('zc_backup')) {
        $path = zc_backup_path($_POST['file'] ?? '');
        if ($path && @unlink($path)) $notice = 'Záloha zmazaná.';
        else $error = 'Zálohu sa nepodarilo zmazať.';
    }
    if (isset($_POST['zc_backup_restore']) && check_admin_referer('zc_backup')) {
        $parts = array_map('sanitize_key', (array) ($_POST['parts'] ?? []));
        list($ok, $msg) = zc_backup_restore($_POST['file'] ?? '', $parts);
        if ($ok) $notice = $msg; else $error = $msg;
    }
    if (isset($_POST['zc_backup_settings']) && check_admin_referer('zc_backup')) {
        update_option('zc_backup_on',  empty($_POST['on']) ? '0' : '1');
        update_option('zc_backup_max', max(1, min(180, (int) ($_POST['max'] ?? ZC_BACKUP_MAX))));
        if (get_option('zc_backup_on') !== '1') {
            wp_clear_scheduled_hook('zc_backup_daily');
        } elseif (!wp_next_scheduled('zc_backup_daily')) {
            wp_schedule_event(strtotime('tomorrow 3:20'), 'daily', 'zc_backup_daily');
        }
        zc_backup_prune();
        $notice = 'Nastavenie uložené.';
    }

    $files  = zc_backup_list();
    $on     = get_option('zc_backup_on', '1') === '1';
    $max    = (int) get_option('zc_backup_max', ZC_BACKUP_MAX);
    $last   = get_option('zc_backup_last', '');
    $next   = wp_next_scheduled('zc_backup_daily');
    $err    = get_option('zc_backup_last_error', '');
    $total  = 0;
    foreach ($files as $f) $total += $f['size'];
    $restore_file = sanitize_text_field($_GET['restore'] ?? '');
    ?>
    <div class="wrap zc-bak">
        <h1>Zálohy realitného panela</h1>
        <?php if ($notice): ?><div class="notice notice-success"><p><?php echo esc_html($notice) ?></p></div><?php endif; ?>
        <?php if ($error):  ?><div class="notice notice-error"><p><?php echo esc_html($error) ?></p></div><?php endif; ?>
        <?php if ($err):    ?><div class="notice notice-error"><p>Posledná automatická záloha zlyhala: <?php echo esc_html($err) ?></p></div><?php endif; ?>

        <p class="zc-bak-sub">
            Každý deň sa uloží celý obsah panela – <strong>ponuky, formuláre, recenzie,
            odberatelia newslettera, šablóny aj nastavenia</strong>. Fotky sa nekopírujú
            (tie ostávajú v Médiách), ukladá sa odkaz na ne.
        </p>

        <div class="zc-bak-cards">
            <div class="zc-bak-card">
                <span>Automatická záloha</span>
                <b style="color:<?php echo $on ? '#15803d' : '#b45309' ?>"><?php echo $on ? 'Zapnutá' : 'Vypnutá' ?></b>
                <?php if ($on && $next): ?><small>najbližšie <?php echo esc_html(date_i18n('j.n.Y H:i', $next)) ?></small><?php endif; ?>
            </div>
            <div class="zc-bak-card">
                <span>Posledná záloha</span>
                <b><?php echo $last ? esc_html(date_i18n('j.n.Y H:i', strtotime($last))) : '—' ?></b>
            </div>
            <div class="zc-bak-card">
                <span>Uložených záloh</span>
                <b><?php echo count($files) ?> / <?php echo (int) $max ?></b>
                <small><?php echo esc_html(size_format($total)) ?> celkom</small>
            </div>
        </div>

        <form method="post" style="margin:18px 0">
            <?php wp_nonce_field('zc_backup') ?>
            <button class="button button-primary" name="zc_backup_now" value="1">Zálohovať teraz</button>
        </form>

        <?php if ($restore_file && zc_backup_path($restore_file)):
            $d = zc_backup_read($restore_file); ?>
        <form method="post" class="zc-bak-restore">
            <?php wp_nonce_field('zc_backup') ?>
            <input type="hidden" name="file" value="<?php echo esc_attr($restore_file) ?>">
            <h2>Obnoviť zo zálohy <?php echo esc_html($restore_file) ?></h2>
            <p>Vyber, čo sa má obnoviť. Ponuky a formuláre sa <strong>doplnia a prepíšu</strong> podľa zálohy –
               nič, čo v zálohe nie je, sa nezmaže. Recenzie a odberatelia sa <strong>nahradia</strong> obsahom zálohy.</p>
            <div class="zc-bak-parts">
                <?php foreach ([
                    'ponuky'     => ['Ponuky',        is_array($d['ponuky'] ?? null)     ? count($d['ponuky'])     : 0],
                    'formulare'  => ['Formuláre',     is_array($d['formulare'] ?? null)  ? count($d['formulare'])  : 0],
                    'recenzie'   => ['Recenzie',      is_array($d['recenzie'] ?? null)   ? count($d['recenzie'])   : 0],
                    'newsletter' => ['Newsletter',    is_array($d['newsletter'] ?? null) ? count($d['newsletter']) : 0],
                    'nastavenia' => ['Nastavenia',    is_array($d['nastavenia'] ?? null) ? count($d['nastavenia']) : 0],
                    'vzhlad'     => ['Vzhľad (Customizer)', is_array($d['vzhlad'] ?? null) ? count($d['vzhlad'])   : 0],
                ] as $key => $info): ?>
                <label class="zc-bak-chk<?php echo $info[1] ? '' : ' is-empty' ?>">
                    <input type="checkbox" name="parts[]" value="<?php echo esc_attr($key) ?>" <?php disabled(!$info[1]); ?>>
                    <span><?php echo esc_html($info[0]) ?> <small><?php echo (int) $info[1] ?> položiek</small></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p>
                <button class="button button-primary" name="zc_backup_restore" value="1"
                        onclick="return confirm('Naozaj obnoviť vybrané časti? Súčasné údaje sa prepíšu.')">Obnoviť vybrané</button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zc-zalohy')) ?>" class="button">Zrušiť</a>
            </p>
        </form>
        <?php endif; ?>

        <table class="widefat striped">
            <thead><tr>
                <th>Záloha</th><th style="width:150px">Kedy</th>
                <th style="width:100px">Veľkosť</th><th style="width:280px">Akcie</th>
            </tr></thead>
            <tbody>
            <?php if (!$files): ?>
                <tr><td colspan="4" style="padding:26px;text-align:center;color:#777">
                    Zatiaľ žiadne zálohy. Prvá sa vytvorí automaticky, alebo klikni na „Zálohovať teraz".
                </td></tr>
            <?php else: foreach ($files as $f): ?>
                <tr>
                    <td><code><?php echo esc_html($f['name']) ?></code></td>
                    <td><?php echo esc_html(date_i18n('j.n.Y H:i', $f['time'])) ?></td>
                    <td><?php echo esc_html(size_format($f['size'])) ?></td>
                    <td>
                        <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(
                            admin_url('admin-post.php?action=zc_backup_download&file=' . rawurlencode($f['name'])), 'zc_backup_dl')) ?>">Stiahnuť</a>
                        <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=zc-zalohy&restore=' . rawurlencode($f['name']))) ?>">Obnoviť</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Zmazať túto zálohu?')">
                            <?php wp_nonce_field('zc_backup') ?>
                            <input type="hidden" name="file" value="<?php echo esc_attr($f['name']) ?>">
                            <button class="button button-small" name="zc_backup_delete" value="1" style="color:#b91c1c">Zmazať</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <form method="post" class="zc-bak-set">
            <?php wp_nonce_field('zc_backup') ?>
            <h2>Nastavenie</h2>
            <label class="zc-bak-chk2">
                <input type="checkbox" name="on" value="1" <?php checked($on) ?>>
                <span>Zálohovať automaticky každý deň</span>
            </label>
            <label>Držať posledných <input type="number" name="max" min="1" max="180" value="<?php echo (int) $max ?>"> záloh</label>
            <button class="button button-primary" name="zc_backup_settings" value="1">Uložiť</button>
        </form>

        <?php if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON): ?>
        <p class="zc-bak-warn">Pozor: na webe je vypnutý WP-Cron (<code>DISABLE_WP_CRON</code>).
           Automatická záloha sa spustí len vtedy, keď máš na hostingu nastavenú vlastnú cron úlohu.</p>
        <?php endif; ?>
    </div>
    <style>
    .zc-bak-sub{max-width:760px;color:#555;font-size:13.5px}
    .zc-bak-cards{display:flex;gap:14px;flex-wrap:wrap;margin:18px 0 0}
    .zc-bak-card{background:#fff;border:1px solid #dcdcde;border-radius:11px;padding:14px 20px;min-width:180px}
    .zc-bak-card span{display:block;font-size:11px;letter-spacing:.6px;text-transform:uppercase;color:#777}
    .zc-bak-card b{display:block;font-size:19px;margin-top:5px}
    .zc-bak-card small{display:block;color:#888;font-size:12px;margin-top:3px}
    .zc-bak-restore{background:#fff;border:1.5px solid #B8A47A;border-radius:11px;padding:18px 22px;margin-bottom:20px;max-width:820px}
    .zc-bak-restore h2{margin-top:0;font-size:16px}
    .zc-bak-parts{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;margin:14px 0}
    .zc-bak-chk{display:flex;align-items:center;gap:9px;padding:10px 12px;border:1.5px solid #E6DFD2;border-radius:9px;cursor:pointer;font-size:13px}
    .zc-bak-chk.is-empty{opacity:.45;cursor:default}
    .zc-bak-chk small{display:block;color:#888;font-size:11px}
    .zc-bak-set{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 20px;margin-top:22px;max-width:640px;
        display:flex;gap:18px;align-items:center;flex-wrap:wrap}
    .zc-bak-set h2{width:100%;margin:0;font-size:15px}
    .zc-bak-chk2{display:flex;align-items:center;gap:8px;font-size:13.5px}
    .zc-bak-set input[type=number]{width:70px}
    .zc-bak-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:12px 16px;border-radius:9px;max-width:760px;margin-top:18px}
    </style>
    <?php
}
