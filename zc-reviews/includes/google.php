<?php
/**
 * Napojenie na Google firmu – načítanie recenzií cez Google Places API.
 * Recenzie sa sťahujú na serveri a ukladajú do medzipamäte (transient),
 * takže sa nevolá API pri každom načítaní stránky a IP návštevníka nikam
 * neodchádza.
 */
defined('ABSPATH') || exit;

const ZCR_G_CACHE = 'zcr_google_reviews';
const ZCR_G_TTL   = 12 * HOUR_IN_SECONDS;

function zcr_g_opt($key, $default = '') {
    return get_option('zcr_google_' . $key, $default);
}
function zcr_google_enabled() {
    return zcr_g_opt('on') && zcr_g_opt('key') && zcr_g_opt('place');
}

/**
 * Načíta recenzie z Google (max 5 – toľko API vracia). Vracia pole objektov
 * v rovnakom tvare ako riadky z databázy, aby sa dali priamo vykresliť.
 */
function zcr_google_fetch($force = false) {
    if (!zcr_google_enabled()) return [];

    if (!$force) {
        $cached = get_transient(ZCR_G_CACHE);
        if (is_array($cached)) return $cached;
    }

    $url = add_query_arg([
        'place_id' => zcr_g_opt('place'),
        'fields'   => 'name,rating,user_ratings_total,reviews,url',
        'language' => 'sk',
        'reviews_no_translations' => 'true',
        'key'      => zcr_g_opt('key'),
    ], 'https://maps.googleapis.com/maps/api/place/details/json');

    $res = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($res)) {
        update_option('zcr_google_error', $res->get_error_message());
        return (array) get_transient(ZCR_G_CACHE);
    }

    $body = json_decode(wp_remote_retrieve_body($res), true);
    $status = $body['status'] ?? 'ERROR';
    if ($status !== 'OK') {
        update_option('zcr_google_error', $status . ' – ' . ($body['error_message'] ?? 'neznáma chyba'));
        return (array) get_transient(ZCR_G_CACHE);
    }
    delete_option('zcr_google_error');

    $min = (int) zcr_g_opt('min_rating', 4);
    $out = [];
    foreach (($body['result']['reviews'] ?? []) as $rev) {
        $rating = (int) ($rev['rating'] ?? 0);
        $text   = trim((string) ($rev['text'] ?? ''));
        if ($rating < $min || $text === '') continue; // bez textu nemá zmysel zobrazovať
        $o = new stdClass();
        $o->id          = 0;
        $o->author_name = sanitize_text_field($rev['author_name'] ?? 'Google používateľ');
        $o->author_role = sanitize_text_field($rev['relative_time_description'] ?? '');
        $o->body        = wp_strip_all_tags($text);
        $o->rating      = $rating;
        $o->avatar_url  = esc_url_raw($rev['profile_photo_url'] ?? '');
        $o->published   = 1;
        $o->sort_order  = 999;
        $o->source      = 'google';
        $out[] = $o;
    }

    update_option('zcr_google_meta', [
        'name'   => $body['result']['name'] ?? '',
        'rating' => $body['result']['rating'] ?? '',
        'total'  => $body['result']['user_ratings_total'] ?? 0,
        'url'    => $body['result']['url'] ?? '',
        'synced' => current_time('mysql'),
    ]);
    set_transient(ZCR_G_CACHE, $out, ZCR_G_TTL);
    return $out;
}

/**
 * Vyhľadá podnik na Google Mapách podľa názvu a vráti kandidátov
 * (názov, adresa, Place ID) – aby sa Place ID nemuselo hľadať ručne.
 */
function zcr_google_find_place($query) {
    $key = zcr_g_opt('key');
    if (!$key) {
        update_option('zcr_google_error', 'Najprv ulož API kľúč.');
        return [];
    }
    $url = add_query_arg([
        'input'     => $query,
        'inputtype' => 'textquery',
        'fields'    => 'place_id,name,formatted_address,rating,user_ratings_total',
        'language'  => 'sk',
        'key'       => $key,
    ], 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json');

    $res = wp_remote_get($url, ['timeout' => 15]);
    if (is_wp_error($res)) {
        update_option('zcr_google_error', $res->get_error_message());
        return [];
    }
    $body = json_decode(wp_remote_retrieve_body($res), true);
    $status = $body['status'] ?? 'ERROR';
    if (!in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
        update_option('zcr_google_error', $status . ' – ' . ($body['error_message'] ?? 'neznáma chyba'));
        return [];
    }
    delete_option('zcr_google_error');
    return $body['candidates'] ?? [];
}

/* ─────────────────────── Nastavenia vo wp-admin ─────────────────────── */

add_action('admin_menu', function () {
    $parent = function_exists('zc_hub_slug') ? zc_hub_slug() : 'zc-reviews';
    add_submenu_page($parent, 'Google recenzie', 'Recenzie z Google', 'manage_options',
        'zc-reviews-google', 'zcr_google_settings_page');
}, 20);

function zcr_google_settings_page() {
    if (!current_user_can('manage_options')) return;
    $msg = '';

    if (isset($_POST['zcr_g_save']) && check_admin_referer('zcr_g')) {
        update_option('zcr_google_on',    empty($_POST['on']) ? '' : '1');
        update_option('zcr_google_key',   sanitize_text_field(trim($_POST['key'] ?? '')));
        update_option('zcr_google_place', sanitize_text_field(trim($_POST['place'] ?? '')));
        update_option('zcr_google_min_rating', max(1, min(5, (int) ($_POST['min_rating'] ?? 4))));
        delete_transient(ZCR_G_CACHE);
        $msg = 'Nastavenia uložené.';
    }
    if (isset($_POST['zcr_g_sync']) && check_admin_referer('zcr_g')) {
        $n = count(zcr_google_fetch(true));
        $msg = $n ? "Načítaných recenzií: {$n}." : 'Nenačítala sa žiadna recenzia – pozri chybu nižšie.';
    }
    // Vyhľadanie podniku podľa názvu → doplní Place ID bez ručného hľadania
    $candidates = [];
    if (isset($_POST['zcr_g_find']) && check_admin_referer('zcr_g')) {
        $q = sanitize_text_field(trim($_POST['q'] ?? ''));
        if ($q === '') {
            $msg = 'Zadaj názov podniku (ideálne aj mesto).';
        } else {
            $candidates = zcr_google_find_place($q);
            $msg = $candidates ? 'Nájdené podniky – vyber ten správny.' : 'Nič sa nenašlo. Skús presnejší názov aj s mestom.';
        }
    }
    if (isset($_POST['zcr_g_pick']) && check_admin_referer('zcr_g')) {
        update_option('zcr_google_place', sanitize_text_field($_POST['pick'] ?? ''));
        delete_transient(ZCR_G_CACHE);
        $n = count(zcr_google_fetch(true));
        $msg = 'Podnik prepojený. Načítaných recenzií: ' . $n . '.';
    }

    $meta = (array) get_option('zcr_google_meta', []);
    $err  = get_option('zcr_google_error', '');
    ?>
    <div class="wrap" style="max-width:820px">
        <h1>Google firma – recenzie</h1>
        <?php if ($msg): ?><div class="notice notice-success"><p><?php echo esc_html($msg); ?></p></div><?php endif; ?>
        <?php if ($err): ?><div class="notice notice-error"><p><strong>Google vrátil chybu:</strong> <?php echo esc_html($err); ?></p></div><?php endif; ?>

        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;margin:16px 0">
            <h2 style="margin-top:0;font-size:15px">Ako to nastaviť</h2>
            <ol style="line-height:1.9;margin:0">
                <li>V <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a> vytvor projekt a zapni <strong>Places API</strong>.</li>
                <li>Vytvor <strong>API kľúč</strong> a obmedz ho na Places API (ideálne aj na IP servera).</li>
                <li>Nájdi <strong>Place ID</strong> firmy cez <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">Place ID Finder</a>.</li>
                <li>Vlož oboje nižšie a klikni <em>Načítať recenzie</em>.</li>
            </ol>
            <p style="margin-bottom:0;color:#666;font-size:13px">Google cez API sprístupňuje <strong>maximálne 5 najnovších recenzií</strong> – to je jeho obmedzenie, nie webu. Vlastné recenzie zadané ručne sa zobrazujú spolu s nimi.</p>
        </div>

        <form method="post" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;margin-bottom:16px">
            <?php wp_nonce_field('zcr_g'); ?>
            <h2 style="margin-top:0;font-size:15px">Nájsť podnik na Mapách</h2>
            <p class="description" style="margin-bottom:10px">Napíš názov firmy aj s mestom – Place ID doplním za teba. (Najprv ulož API kľúč nižšie.)</p>
            <p>
                <input type="text" name="q" class="regular-text" placeholder="napr. Zdenka Cibuľová reality Banská Bystrica"
                       value="<?php echo esc_attr($_POST['q'] ?? ''); ?>">
                <button type="submit" name="zcr_g_find" value="1" class="button">Vyhľadať</button>
            </p>
            <?php if ($candidates): ?>
            <table class="widefat striped" style="margin-top:12px">
                <thead><tr><th>Podnik</th><th>Adresa</th><th style="width:110px">Hodnotenie</th><th style="width:110px"></th></tr></thead>
                <tbody>
                <?php foreach ($candidates as $c): ?>
                <tr>
                    <td><strong><?php echo esc_html($c['name'] ?? ''); ?></strong></td>
                    <td style="font-size:13px;color:#555"><?php echo esc_html($c['formatted_address'] ?? ''); ?></td>
                    <td><?php echo isset($c['rating']) ? esc_html($c['rating']) . ' ★ (' . (int) ($c['user_ratings_total'] ?? 0) . ')' : '–'; ?></td>
                    <td><button type="submit" name="zcr_g_pick" value="1" class="button button-primary"
                                onclick="this.form.pick.value='<?php echo esc_js($c['place_id'] ?? ''); ?>'">Prepojiť</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <input type="hidden" name="pick" value="">
        </form>

        <form method="post">
            <?php wp_nonce_field('zcr_g'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Zobrazovať Google recenzie</th>
                    <td><label><input type="checkbox" name="on" value="1" <?php checked(zcr_g_opt('on'), '1'); ?>> Zapnúť</label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="zcrgkey">API kľúč</label></th>
                    <td><input type="text" id="zcrgkey" name="key" class="regular-text" value="<?php echo esc_attr(zcr_g_opt('key')); ?>" autocomplete="off"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="zcrgplace">Place ID</label></th>
                    <td><input type="text" id="zcrgplace" name="place" class="regular-text" value="<?php echo esc_attr(zcr_g_opt('place')); ?>" placeholder="ChIJ…"></td>
                </tr>
                <tr>
                    <th scope="row">Minimálne hodnotenie</th>
                    <td>
                        <select name="min_rating">
                            <?php foreach ([5,4,3,2,1] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php selected((int) zcr_g_opt('min_rating', 4), $s); ?>><?php echo $s; ?> hviezdičky a viac</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Slabšie hodnotenia sa na web nezobrazia.</p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" name="zcr_g_save" value="1" class="button button-primary">Uložiť</button>
                <button type="submit" name="zcr_g_sync" value="1" class="button">Načítať recenzie teraz</button>
            </p>
        </form>

        <?php if ($meta): ?>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px">
            <h2 style="margin-top:0;font-size:15px">Napojená firma</h2>
            <p style="margin:0;line-height:1.9">
                <strong><?php echo esc_html($meta['name'] ?? ''); ?></strong><br>
                Hodnotenie: <strong><?php echo esc_html($meta['rating'] ?? '–'); ?></strong>
                z <?php echo (int) ($meta['total'] ?? 0); ?> recenzií<br>
                Naposledy načítané: <?php echo esc_html($meta['synced'] ?? '–'); ?>
                <?php if (!empty($meta['url'])): ?><br><a href="<?php echo esc_url($meta['url']); ?>" target="_blank" rel="noopener">Zobraziť na Google →</a><?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
