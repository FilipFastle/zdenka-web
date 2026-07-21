<?php
/**
 * Import / Export nehnuteľností cez CSV.
 * Export: admin-post endpoint (bezpečné streamovanie so správnymi hlavičkami).
 * Import: spracovanie nahratého CSV priamo v paneli.
 */
defined('ABSPATH') || exit;

// Stĺpce CSV (poradie = poradie v súbore). Kľúč = meta bez prefixu, 'title'/'content' špeciálne.
function pp_csv_columns() {
    return [
        'title'        => 'Názov',
        'typ'          => 'Typ (predaj/prenajom/pozemok)',
        'cena'         => 'Cena',
        'cena_povodna' => 'Pôvodná cena',
        'lokalita'     => 'Lokalita',
        'mesto'        => 'Mesto',
        'okres'        => 'Okres',
        'plocha'       => 'Plocha m2',
        'pozemok'      => 'Pozemok m2',
        'spalne'       => 'Spálne',
        'kupelne'      => 'Kúpeľne',
        'wc'           => 'WC',
        'poschodie'    => 'Poschodie',
        'rocnik'       => 'Rok stavby',
        'stav'         => 'Stav',
        'vlastnictvo'  => 'Vlastníctvo',
        'energie'      => 'Energ. trieda',
        'stav_predaja' => 'Stav predaja (rezervovane/predane)',
        'popis_kratky' => 'Krátky popis',
        'content'      => 'Popis',
    ];
}

// ── EXPORT ─────────────────────────────────────────────────────────────────
add_action('admin_post_pp_export_properties', 'pp_export_properties');
function pp_export_properties() {
    if (!current_user_can('edit_posts')) wp_die('Nemáš oprávnenie.');
    check_admin_referer('pp_export');

    $cols = pp_csv_columns();
    $q = new WP_Query(['post_type' => 'property', 'posts_per_page' => -1, 'post_status' => ['publish','draft']]);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nehnutelnosti-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM pre Excel (diakritika)
    fputcsv($out, array_values($cols));

    foreach ($q->posts as $p) {
        $row = [];
        foreach ($cols as $key => $label) {
            if ($key === 'title')        $val = $p->post_title;
            elseif ($key === 'content')  $val = wp_strip_all_tags($p->post_content);
            else                         $val = (string) get_post_meta($p->ID, '_property_' . $key, true);
            // Ochrana proti CSV/formula injection
            if ($val !== '' && in_array($val[0], ['=', '+', '-', '@'], true)) $val = "'" . $val;
            $row[] = $val;
        }
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ── IMPORT ─────────────────────────────────────────────────────────────────
// Spracuje nahratý súbor; vracia [created, updated, skipped, errors[]]
function pp_import_properties_csv($file) {
    $res = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $res['errors'][] = 'Súbor sa nenahral.';
        return $res;
    }
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) { $res['errors'][] = 'Súbor sa nedá otvoriť.'; return $res; }

    $cols   = array_keys(pp_csv_columns()); // poradie kľúčov
    $header = fgetcsv($handle);
    if ($header === false) { fclose($handle); $res['errors'][] = 'Prázdny súbor.'; return $res; }
    // Odstrániť BOM z prvej bunky
    if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

    $line = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if (count(array_filter($data, function ($c) { return trim((string)$c) !== ''; })) === 0) continue; // prázdny riadok

        // Namapovať podľa poradia stĺpcov
        $vals = [];
        foreach ($cols as $i => $key) {
            $vals[$key] = isset($data[$i]) ? trim((string)$data[$i]) : '';
        }
        if ($vals['title'] === '') { $res['skipped']++; continue; }

        // Existuje ponuka s rovnakým názvom? → update, inak create
        $existing = get_posts([
            'post_type'   => 'property',
            'title'       => $vals['title'],
            'post_status' => ['publish','draft'],
            'numberposts' => 1,
            'fields'      => 'ids',
        ]);

        $postarr = [
            'post_type'    => 'property',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field($vals['title']),
            'post_content' => wp_kses_post($vals['content']),
        ];

        if (!empty($existing)) {
            $postarr['ID'] = $existing[0];
            $pid = wp_update_post($postarr, true);
            $is_new = false;
        } else {
            $pid = wp_insert_post($postarr, true);
            $is_new = true;
        }

        if (is_wp_error($pid) || !$pid) {
            $res['errors'][] = "Riadok $line: chyba pri ukladaní.";
            continue;
        }

        foreach ($cols as $key) {
            if ($key === 'title' || $key === 'content') continue;
            $val = sanitize_text_field($vals[$key]);
            if (($key === 'cena' || $key === 'cena_povodna') && $val && strpos($val, '€') === false) $val .= ' €';
            update_post_meta($pid, '_property_' . $key, $val);
        }

        if ($is_new) $res['created']++; else $res['updated']++;
        if (function_exists('pp_log')) pp_log($is_new ? 'Import: nová ponuka' : 'Import: aktualizovaná', $pid);
    }
    fclose($handle);
    return $res;
}

// ── Panel: sekcia Import/Export ────────────────────────────────────────────
function panel_import_export() {
    $result = null;
    if (isset($_POST['pp_do_import']) && check_admin_referer('pp_import', 'pp_import_nonce')) {
        $result = pp_import_properties_csv($_FILES['pp_csv'] ?? []);
    }
    $export_url = wp_nonce_url(admin_url('admin-post.php?action=pp_export_properties'), 'pp_export');
    $cols = pp_csv_columns();

    ob_start(); ?>
    <div style="max-width:820px">
        <h2 style="font-family:var(--serif);font-size:22px;color:var(--dark);margin-bottom:6px">Import &amp; Export</h2>
        <p style="color:var(--muted);margin-bottom:24px;font-size:14px">Hromadné nahranie alebo zálohovanie ponúk cez CSV (otvoríš v Exceli aj Google Sheets).</p>

        <?php if ($result): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:14px 16px;border-radius:var(--r-sm);margin-bottom:20px;font-size:14px">
            Hotovo — vytvorených: <strong><?php echo (int)$result['created'] ?></strong>,
            aktualizovaných: <strong><?php echo (int)$result['updated'] ?></strong>,
            preskočených: <strong><?php echo (int)$result['skipped'] ?></strong>.
            <?php if (!empty($result['errors'])): ?>
            <div style="color:#b45309;margin-top:8px"><?php echo esc_html(implode(' ', array_slice($result['errors'], 0, 10))) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px" class="pp-ie-grid">
            <!-- EXPORT -->
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:22px;box-shadow:var(--sh)">
                <h3 style="font-size:15px;margin-bottom:8px;color:var(--dark)">Export ponúk</h3>
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.5">Stiahne všetky ponuky do CSV. Vhodné na zálohu alebo úpravu vo väčšom.</p>
                <a href="<?php echo esc_url($export_url) ?>" class="btn btn-primary">Stiahnuť CSV</a>
            </div>
            <!-- IMPORT -->
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:22px;box-shadow:var(--sh)">
                <h3 style="font-size:15px;margin-bottom:8px;color:var(--dark)">Import ponúk</h3>
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;line-height:1.5">Nahraj CSV so stĺpcami nižšie. Ponuka s rovnakým názvom sa aktualizuje, nová sa vytvorí.</p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('pp_import', 'pp_import_nonce') ?>
                    <input type="file" name="pp_csv" accept=".csv,text/csv" required style="margin-bottom:14px;font-size:13px;width:100%">
                    <button type="submit" name="pp_do_import" value="1" class="btn btn-primary">Nahrať a importovať</button>
                </form>
            </div>
        </div>

        <div style="background:var(--section);border-radius:var(--r);padding:18px 20px;margin-top:22px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:10px">Poradie stĺpcov v CSV</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach ($cols as $key => $label): ?>
                <span style="background:var(--white);border:1px solid var(--border);border-radius:50px;padding:4px 11px;font-size:12px;color:var(--text)"><?php echo esc_html($label) ?></span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:var(--muted);margin-top:12px;line-height:1.5">Tip: najprv si sprav <strong>Export</strong> — dostaneš CSV s presnými stĺpcami, ktoré potom stačí doplniť a nahrať späť. Fotky sa cez CSV nedajú, tie pridáš v editácii ponuky.</p>
        </div>
    </div>
    <style>@media(max-width:640px){.pp-ie-grid{grid-template-columns:1fr !important}}</style>
    <?php
    return ob_get_clean();
}
