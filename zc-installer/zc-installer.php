<?php
/**
 * Plugin Name: ZC Inštalátor (hromadná inštalácia témy a pluginov)
 * Description: Nahraj tému a všetky pluginy naraz — viac .zip súborov v jednom kroku, alebo jedným klikom nainštaluj celý priložený balík. Po inštalácii môžeš tento plugin zmazať.
 * Version: 1.0.0
 * Author: Filip
 */
defined('ABSPATH') || exit;

define('ZCI_PATH', plugin_dir_path(__FILE__));

add_action('admin_menu', function() {
    add_menu_page('ZC Inštalátor', 'ZC Inštalátor', 'install_plugins',
        'zc-installer', 'zci_render_page', 'dashicons-download', 2);
});

// ── Jadro: nainštaluje jeden .zip (téma alebo plugin) ───────────────────
function zci_install_zip($zip_path, $orig_name = '') {
    global $wp_filesystem;
    require_once ABSPATH . 'wp-admin/includes/file.php';
    if (!function_exists('WP_Filesystem') || !WP_Filesystem()) {
        return ['ok' => false, 'name' => $orig_name, 'msg' => 'Nepodarilo sa inicializovať súborový systém (WP_Filesystem).'];
    }

    // Rozbaliť do dočasného priečinka
    $tmp = trailingslashit(get_temp_dir()) . 'zci_' . wp_generate_password(10, false);
    $wp_filesystem->mkdir($tmp);
    $unzip = unzip_file($zip_path, $tmp);
    if (is_wp_error($unzip)) {
        $wp_filesystem->delete($tmp, true);
        return ['ok' => false, 'name' => $orig_name, 'msg' => 'Poškodený alebo neplatný ZIP: ' . $unzip->get_error_message()];
    }

    // Nájsť koreňový priečinok balíka
    $entries = array_values(array_diff((array) scandir($tmp), ['.', '..']));
    $root    = $tmp;
    if (count($entries) === 1 && is_dir($tmp . '/' . $entries[0])) {
        $root = $tmp . '/' . $entries[0];
    }
    $folder = basename($root);

    // Detekcia typu: téma má style.css s hlavičkou "Theme Name"
    $is_theme = false;
    if (file_exists($root . '/style.css')) {
        $head = (string) $wp_filesystem->get_contents($root . '/style.css');
        if (stripos($head, 'Theme Name:') !== false) $is_theme = true;
    }

    if ($is_theme) {
        $dest_parent = get_theme_root();
    } else {
        $dest_parent = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
    }
    $dest = trailingslashit($dest_parent) . $folder;

    // Prepísať existujúcu inštaláciu
    if ($wp_filesystem->exists($dest)) $wp_filesystem->delete($dest, true);
    $wp_filesystem->mkdir($dest);
    $copied = copy_dir($root, $dest);
    $wp_filesystem->delete($tmp, true);

    if (is_wp_error($copied)) {
        return ['ok' => false, 'name' => $orig_name ?: $folder, 'msg' => 'Kopírovanie zlyhalo: ' . $copied->get_error_message()];
    }

    return [
        'ok'     => true,
        'name'   => $orig_name ?: $folder,
        'type'   => $is_theme ? 'theme' : 'plugin',
        'folder' => $folder,
        'msg'    => ($is_theme ? 'Téma' : 'Plugin') . ' „' . $folder . '" nainštalované.',
    ];
}

// Aktivovať / prepnúť po inštalácii
function zci_activate_results($results) {
    foreach ($results as $r) {
        if (empty($r['ok'])) continue;
        if ($r['type'] === 'theme' && current_user_can('switch_themes')) {
            switch_theme($r['folder']);
        } elseif ($r['type'] === 'plugin' && current_user_can('activate_plugins')) {
            $plugins = get_plugins('/' . $r['folder']);
            if ($plugins) {
                $file = $r['folder'] . '/' . array_key_first($plugins);
                activate_plugin($file);
            }
        }
    }
}

function zci_render_page() {
    if (!current_user_can('install_plugins')) wp_die('Nedostatočné oprávnenie.');
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $results = [];

    // A) Hromadný upload viacerých .zip
    if (isset($_POST['zci_upload']) && check_admin_referer('zci_install')) {
        if (!empty($_FILES['zci_zips']['name'][0])) {
            $files = $_FILES['zci_zips'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $name = sanitize_file_name($files['name'][$i]);
                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
                    $results[] = ['ok' => false, 'name' => $name, 'msg' => 'Nie je to .zip súbor.'];
                    continue;
                }
                $tmp = wp_tempnam($name);
                if (!@move_uploaded_file($files['tmp_name'][$i], $tmp)) {
                    $results[] = ['ok' => false, 'name' => $name, 'msg' => 'Nahrávanie zlyhalo.'];
                    continue;
                }
                $results[] = zci_install_zip($tmp, $name);
                @unlink($tmp);
            }
            zci_activate_results($results);
        } else {
            $results[] = ['ok' => false, 'name' => '', 'msg' => 'Nevybral si žiadny súbor.'];
        }
    }

    // B) Jedno-klikový priložený balík
    if (isset($_POST['zci_bundle']) && check_admin_referer('zci_install')) {
        $bundles = glob(ZCI_PATH . 'bundles/*.zip') ?: [];
        foreach ($bundles as $b) {
            $results[] = zci_install_zip($b, basename($b));
        }
        zci_activate_results($results);
    }

    $bundle_files = glob(ZCI_PATH . 'bundles/*.zip') ?: [];
    ?>
    <div class="wrap">
        <h1>ZC Inštalátor</h1>
        <p style="font-size:14px;color:#555;max-width:720px">Nahraj tému a všetky pluginy <strong>naraz</strong> — nemusíš ich inštalovať po jednom. Inštalátor sám rozpozná, čo je téma a čo plugin, a umiestni ich správne. Existujúce verzie prepíše (aktualizuje).</p>

        <?php if ($results): ?>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin:18px 0;max-width:720px">
            <h2 style="font-size:16px;margin:0 0 10px">Výsledok inštalácie</h2>
            <?php foreach ($results as $r): ?>
            <div style="padding:8px 0;border-bottom:1px solid #f1f5f9;display:flex;gap:10px;align-items:flex-start">
                <span style="font-weight:800;color:<?php echo !empty($r['ok']) ? '#16a34a' : '#dc2626'; ?>"><?php echo !empty($r['ok']) ? '✓' : '×'; ?></span>
                <div>
                    <strong><?php echo esc_html($r['name']); ?></strong><br>
                    <span style="color:#666;font-size:13px"><?php echo esc_html($r['msg']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <p style="margin:14px 0 0"><a href="<?php echo admin_url('themes.php'); ?>">Témy →</a> &nbsp; <a href="<?php echo admin_url('plugins.php'); ?>">Pluginy →</a></p>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px;margin-top:20px">
            <!-- Hromadný upload -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:26px">
                <h2 style="font-size:17px;margin:0 0 8px">Nahrať viac .zip naraz</h2>
                <p style="color:#666;font-size:13px;margin:0 0 16px">Vyber všetky zipy témy a pluginov (podrž Ctrl / Cmd pri výbere) a nahraj ich jedným kliknutím.</p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('zci_install'); ?>
                    <input type="file" name="zci_zips[]" accept=".zip" multiple required
                        style="display:block;width:100%;padding:10px;border:1.5px dashed #cbd5e1;border-radius:8px;margin-bottom:14px;background:#f8fafc">
                    <button type="submit" name="zci_upload" value="1" class="button button-primary button-large">Nainštalovať vybrané</button>
                </form>
            </div>

            <!-- Priložený balík -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:26px">
                <h2 style="font-size:17px;margin:0 0 8px">Priložený balík</h2>
                <?php if ($bundle_files): ?>
                <p style="color:#666;font-size:13px;margin:0 0 12px">V tomto inštalátore je pribalené:</p>
                <ul style="margin:0 0 16px;padding-left:18px;color:#444;font-size:13px">
                    <?php foreach ($bundle_files as $b): ?>
                    <li><?php echo esc_html(basename($b)); ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="post">
                    <?php wp_nonce_field('zci_install'); ?>
                    <button type="submit" name="zci_bundle" value="1" class="button button-primary button-large"
                        onclick="return confirm('Nainštalovať a aktivovať celý priložený balík?')">Nainštalovať všetko jedným klikom</button>
                </form>
                <?php else: ?>
                <p style="color:#999;font-size:13px;margin:0">Žiadny priložený balík. Použi hromadný upload vľavo.</p>
                <?php endif; ?>
            </div>
        </div>

        <p style="margin-top:24px;color:#94a3b8;font-size:12px;max-width:720px">Po dokončení inštalácie môžeš tento inštalátor pokojne deaktivovať a zmazať — nie je potrebný na chod webu.</p>
    </div>
    <?php
}
