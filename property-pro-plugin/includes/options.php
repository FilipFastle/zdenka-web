<?php
/**
 * Číselníky pri zadávaní ponuky (Stav, Vlastníctvo).
 *
 * Doteraz boli tieto možnosti napísané natvrdo v kóde na dvoch miestach –
 * vo wp-admine aj v realitnom paneli. Teraz sa spravujú na jednom mieste
 * a obe formy z nich čerpajú.
 *
 * Do meta poľa ponuky sa naďalej ukladá samotný názov (napr. „Novostavba"),
 * nie kľúč – takto to bolo od začiatku a menia to filtre aj šablóny. Preto
 * pri premenovaní položky rovno prepíšeme aj už uložené ponuky, aby na webe
 * nezostal starý názov.
 */
defined('ABSPATH') || exit;

/** Zoznamy, ktoré sa dajú spravovať. */
function pp_option_lists() {
    return [
        'stav' => [
            'title' => 'Stav nehnuteľnosti',
            'meta'  => '_property_stav',
            'help'  => 'Vyberá sa pri zadávaní ponuky v poli „Stav".',
        ],
        'vlastnictvo' => [
            'title' => 'Vlastníctvo',
            'meta'  => '_property_vlastnictvo',
            'help'  => 'Vyberá sa pri zadávaní ponuky v poli „Vlastníctvo".',
        ],
    ];
}

/** Pôvodné položky – použijú sa, kým si ich maklérka neupraví. */
function pp_option_defaults($key) {
    $d = [
        'stav' => [
            'Novostavba',
            'Kompletná rekonštrukcia',
            'Čiastočná rekonštrukcia',
            'Pôvodný stav',
        ],
        'vlastnictvo' => ['Osobné', 'Družstevné', 'Štátne', 'V príprave prevodu'],
    ];
    $out = [];
    foreach ($d[$key] ?? [] as $label) $out[] = ['label' => $label, 'hidden' => 0];
    return $out;
}

/** Uložený zoznam aj s nastavením skrytia. */
function pp_options($key) {
    if (!isset(pp_option_lists()[$key])) return [];
    $saved = get_option('pp_options_' . $key, null);
    if (!is_array($saved) || !$saved) return pp_option_defaults($key);

    $out = [];
    foreach ($saved as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        if ($label === '') continue;
        $out[] = ['label' => $label, 'hidden' => !empty($row['hidden']) ? 1 : 0];
    }
    return $out ?: pp_option_defaults($key);
}

function pp_options_save($key, $rows) {
    if (!isset(pp_option_lists()[$key])) return;
    update_option('pp_options_' . $key, array_values($rows), false);
}

/**
 * Možnosti do rozbaľovacieho poľa.
 *
 * Skryté položky sa novým ponukám neponúkajú, ale ak ich niektorá ponuka
 * už má uloženú, necháme ju vidieť – inak by sa pri prvom uložení ticho
 * stratila.
 */
function pp_option_choices($key, $current = '') {
    $current = trim((string) $current);
    $out = [];
    foreach (pp_options($key) as $row) {
        if ($row['hidden'] && $row['label'] !== $current) continue;
        $out[] = $row['label'];
    }
    if ($current !== '' && !in_array($current, $out, true)) $out[] = $current;
    return $out;
}

/** Koľko ponúk má danú hodnotu uloženú. */
function pp_option_usage($key, $label) {
    $meta = pp_option_lists()[$key]['meta'] ?? '';
    if (!$meta || $label === '') return 0;
    global $wpdb;
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} m
         INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
         WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type = 'property'",
        $meta, $label
    ));
}

/** Premenovanie položky prepíše aj už uložené ponuky. */
function pp_option_rename($key, $stary, $novy) {
    $meta = pp_option_lists()[$key]['meta'] ?? '';
    if (!$meta || $stary === '' || $novy === '' || $stary === $novy) return 0;
    global $wpdb;
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT m.post_id FROM {$wpdb->postmeta} m
         INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
         WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type = 'property'",
        $meta, $stary
    ));
    foreach ($ids as $id) update_post_meta((int) $id, $meta, $novy);
    return count($ids);
}

/** Hodnoty, ktoré v ponukách sú, ale v zozname už nie sú. */
function pp_option_orphans($key) {
    $meta = pp_option_lists()[$key]['meta'] ?? '';
    if (!$meta) return [];
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT m.meta_value AS hodnota, COUNT(*) AS pocet FROM {$wpdb->postmeta} m
         INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
         WHERE m.meta_key = %s AND m.meta_value <> '' AND p.post_type = 'property'
         GROUP BY m.meta_value", $meta
    ));
    $zoznam = array_column(pp_options($key), 'label');
    $out = [];
    foreach ($rows as $r) {
        if (!in_array($r->hodnota, $zoznam, true)) $out[] = [$r->hodnota, (int) $r->pocet];
    }
    return $out;
}

/* ───────────────────────── Stránka vo wp-admine ───────────────────────── */

add_action('admin_menu', function () {
    $parent = function_exists('zc_hub_slug') ? zc_hub_slug() : 'edit.php?post_type=property';
    add_submenu_page($parent, 'Možnosti pri ponukách', 'Možnosti ponúk', 'manage_options',
        'zc-moznosti', 'pp_options_admin_page');
}, 12);

add_action('admin_init', function () {
    if (empty($_POST['pp_opts_key']) || ($_GET['page'] ?? '') !== 'zc-moznosti') return;
    if (!current_user_can('manage_options') || !check_admin_referer('pp_options')) return;

    $key = sanitize_key($_POST['pp_opts_key']);
    if (!isset(pp_option_lists()[$key])) return;

    if (isset($_POST['pp_opts_reset'])) {
        delete_option('pp_options_' . $key);
        pp_options_notice('Možnosti sú vrátené na pôvodné.', true);
        pp_options_redirect($key);
    }

    $rows        = [];
    $premenovane = 0;
    $dotknute    = 0;
    foreach ((array) ($_POST['opt'] ?? []) as $row) {
        $label = trim(sanitize_text_field(wp_unslash($row['label'] ?? '')));
        if ($label === '') continue;                        // prázdny názov = zmazané
        $povodny = trim(sanitize_text_field(wp_unslash($row['orig'] ?? '')));
        if ($povodny !== '' && $povodny !== $label) {
            $n = pp_option_rename($key, $povodny, $label);
            if ($n) { $premenovane++; $dotknute += $n; }
        }
        $rows[] = ['label' => $label, 'hidden' => !empty($row['hidden']) ? 1 : 0];
    }

    $nova = trim(sanitize_text_field(wp_unslash($_POST['pp_opts_new'] ?? '')));
    if ($nova !== '') $rows[] = ['label' => $nova, 'hidden' => 0];

    if (!$rows) {
        pp_options_notice('Aspoň jedna možnosť musí ostať.', false);
        pp_options_redirect($key);
    }

    // Rovnaký názov dvakrát nemá zmysel
    $videne = [];
    $rows = array_values(array_filter($rows, function ($r) use (&$videne) {
        $k = function_exists('mb_strtolower') ? mb_strtolower($r['label']) : strtolower($r['label']);
        if (isset($videne[$k])) return false;
        $videne[$k] = 1;
        return true;
    }));

    pp_options_save($key, $rows);
    $sprava = 'Možnosti uložené.';
    if ($premenovane) {
        $sprava .= sprintf(' Premenované: %d – upravili sme aj %d %s.',
            $premenovane, $dotknute, $dotknute === 1 ? 'ponuku' : 'ponúk');
    }
    pp_options_notice($sprava, true);
    pp_options_redirect($key);
});

/* Hromadné nahradenie hodnoty, ktorá už v zozname nie je */
add_action('admin_init', function () {
    if (empty($_POST['pp_opts_migrate']) || ($_GET['page'] ?? '') !== 'zc-moznosti') return;
    if (!current_user_can('manage_options') || !check_admin_referer('pp_options')) return;

    $key   = sanitize_key($_POST['pp_opts_key'] ?? '');
    $stara = trim(sanitize_text_field(wp_unslash($_POST['pp_opts_from'] ?? '')));
    $nova  = trim(sanitize_text_field(wp_unslash($_POST['pp_opts_to'] ?? '')));
    if (!isset(pp_option_lists()[$key]) || $stara === '' || $nova === '') return;

    $n = pp_option_rename($key, $stara, $nova);
    pp_options_notice(sprintf('Hodnota „%s" bola v %d %s nahradená za „%s".',
        $stara, $n, $n === 1 ? 'ponuke' : 'ponukách', $nova), true);
    pp_options_redirect($key);
});

function pp_options_notice($msg, $ok = true) {
    set_transient('pp_opts_notice_' . get_current_user_id(),
        ['msg' => (string) $msg, 'ok' => (bool) $ok], MINUTE_IN_SECONDS);
}

function pp_options_redirect($key) {
    wp_safe_redirect(add_query_arg(['page' => 'zc-moznosti', 'zoznam' => $key], admin_url('admin.php')));
    exit;
}

function pp_options_admin_page() {
    if (!current_user_can('manage_options')) wp_die('Bez oprávnenia.');
    if (function_exists('zc_hub_styles')) zc_hub_styles();

    $lists = pp_option_lists();
    $key   = sanitize_key($_GET['zoznam'] ?? '');
    if (!isset($lists[$key])) $key = array_key_first($lists);
    $rows  = pp_options($key);
    $siroty = pp_option_orphans($key);

    $notice_key = 'pp_opts_notice_' . get_current_user_id();
    $notice = get_transient($notice_key);
    if ($notice) delete_transient($notice_key);
    ?>
    <div class="wrap zch">
        <div class="zch-head"><h1>Možnosti pri ponukách</h1></div>

        <?php if ($notice && !empty($notice['msg'])): ?>
        <div class="notice notice-<?php echo !empty($notice['ok']) ? 'success' : 'error'; ?> is-dismissible">
            <p><?php echo esc_html($notice['msg']); ?></p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper" style="margin-bottom:18px">
            <?php foreach ($lists as $k => $l): ?>
            <a class="nav-tab<?php echo $k === $key ? ' nav-tab-active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg(['page' => 'zc-moznosti', 'zoznam' => $k], admin_url('admin.php'))); ?>">
                <?php echo esc_html($l['title']); ?></a>
            <?php endforeach; ?>
        </h2>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px;max-width:820px">
            <p style="color:#555;font-size:13px;line-height:1.7;margin:0 0 18px">
                <?php echo esc_html($lists[$key]['help']); ?><br>
                <strong>Skryté</strong> možnosti sa pri novej ponuke neponúkajú, ale ponuky,
                ktoré ich už majú, si ich nechajú.<br>
                Možnosť <strong>zmažeš tak, že vymažeš jej názov</strong> a uložíš.
                Keď názov <strong>prepíšeš</strong>, opravíme ho aj vo všetkých ponukách,
                ktoré ho majú uložený.
            </p>

            <form method="post">
                <?php wp_nonce_field('pp_options'); ?>
                <input type="hidden" name="pp_opts_key" value="<?php echo esc_attr($key); ?>">
                <table class="wp-list-table widefat striped" style="margin-bottom:16px">
                    <thead><tr>
                        <th style="width:58%">Názov</th>
                        <th style="width:16%">Skryť</th>
                        <th style="width:26%">Použité v ponukách</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row): $pouzite = pp_option_usage($key, $row['label']); ?>
                    <tr>
                        <td>
                            <input type="text" name="opt[<?php echo $i ?>][label]"
                                   value="<?php echo esc_attr($row['label']); ?>" style="width:100%">
                            <input type="hidden" name="opt[<?php echo $i ?>][orig]"
                                   value="<?php echo esc_attr($row['label']); ?>">
                        </td>
                        <td><label><input type="checkbox" name="opt[<?php echo $i ?>][hidden]" value="1"
                                   <?php checked(!empty($row['hidden'])); ?>> skryť</label></td>
                        <td><?php echo $pouzite
                            ? '<strong>' . $pouzite . '</strong> ' . ($pouzite === 1 ? 'ponuka' : 'ponúk')
                            : '<span style="color:#6B6560">zatiaľ nikde</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="pp_opts_new" placeholder="Nová možnosť…" style="width:100%"></td>
                        <td colspan="2" style="color:#6B6560;font-size:12px">pridá sa na koniec</td>
                    </tr>
                    </tbody>
                </table>
                <button type="submit" class="button button-primary">Uložiť</button>
                <button type="submit" name="pp_opts_reset" value="1" class="button"
                        onclick="return confirm('Naozaj vrátiť pôvodné možnosti? Ponuky sa nezmenia.')">
                    Vrátiť pôvodné</button>
            </form>
        </div>

        <?php if ($siroty): ?>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px;max-width:820px;margin-top:18px">
            <h3 style="margin:0 0 6px">Hodnoty, ktoré v zozname nie sú</h3>
            <p style="color:#555;font-size:13px;line-height:1.7;margin:0 0 16px">
                Tieto ponuky majú uloženú hodnotu, ktorá už medzi možnosťami nie je –
                napríklad z čias pred úpravou zoznamu. Na webe sa zobrazujú tak, ako sú.
                Tu ich vieš hromadne nahradiť.
            </p>
            <table class="wp-list-table widefat striped">
                <thead><tr><th>Hodnota</th><th style="width:90px">Ponúk</th><th style="width:340px">Nahradiť za</th></tr></thead>
                <tbody>
                <?php foreach ($siroty as [$hodnota, $pocet]): ?>
                <tr>
                    <td><strong><?php echo esc_html($hodnota); ?></strong></td>
                    <td><?php echo $pocet; ?></td>
                    <td>
                        <form method="post" style="display:flex;gap:8px">
                            <?php wp_nonce_field('pp_options'); ?>
                            <input type="hidden" name="pp_opts_migrate" value="1">
                            <input type="hidden" name="pp_opts_key" value="<?php echo esc_attr($key); ?>">
                            <input type="hidden" name="pp_opts_from" value="<?php echo esc_attr($hodnota); ?>">
                            <select name="pp_opts_to" style="flex:1">
                                <?php foreach ($rows as $r): ?>
                                <option value="<?php echo esc_attr($r['label']); ?>"><?php echo esc_html($r['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="button">Nahradiť</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
