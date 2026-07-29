<?php
/**
 * Indexovanie v Google – diagnostika a poistky.
 *
 * Google Search Console hlási „Chyba presmerovania“ vtedy, keď sa robot pri
 * načítaní adresy zacyklí (A → B → A) alebo keď je reťaz presmerovaní pridlhá.
 * Najčastejšia príčina na hostingoch s Cloudflare/proxy: WordPress netuší, že
 * návšteva prišla cez HTTPS, tak ju pošle na https://…, proxy ju vráti ako
 * http://… a kolotoč sa točí dokola.
 *
 * Tento súbor robí dve veci:
 *  1) poistku, aby taký kolotoč vôbec nevznikol,
 *  2) stránku Web Zdenky → Indexovanie, ktorá presne ukáže, kde je problém.
 */
defined('ABSPATH') || exit;

/* ───────────────────── 1. Poistka proti zacykleniu ───────────────────── */

/**
 * Keď je pred WordPressom proxy (Cloudflare, load balancer, reverzný nginx),
 * pošle informáciu o HTTPS v hlavičke. WordPress ju sám nečíta, preto mu ju
 * doplníme – inak by redirect_canonical() posielal robota dokola.
 *
 * Nastavuje sa len vtedy, keď to proxy naozaj hlási. Bez proxy sa nedeje nič.
 */
function zc_https_proxy_guard() {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return;

    $https = false;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        // Môže obsahovať reťaz „https, http“ – rozhoduje prvá hodnota
        $proto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        $https = ($proto === 'https');
    }
    if (!$https && !empty($_SERVER['HTTP_X_FORWARDED_SSL'])) {
        $https = strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on';
    }
    if (!$https && !empty($_SERVER['HTTP_CF_VISITOR'])) {
        $https = strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false;
    }
    if (!$https && !empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
        $https = (string) $_SERVER['HTTP_X_FORWARDED_PORT'] === '443';
    }

    // Doplníme len ak je aj adresa webu https – nech sa http-only web nerozbije
    if ($https && strpos(get_option('home'), 'https://') === 0) {
        $_SERVER['HTTPS'] = 'on';
    }
}
zc_https_proxy_guard();

/* ───────────────────── 2. Diagnostika ───────────────────── */

/** Prejde reťaz presmerovaní a vráti jednotlivé kroky. */
function zc_index_trace($url, $max = 8) {
    $hops = [];
    $seen = [];
    $cur  = $url;

    for ($i = 0; $i < $max; $i++) {
        $res = wp_remote_get($cur, [
            'redirection' => 0,
            'timeout'     => 12,
            'sslverify'   => true,
            'headers'     => ['Accept' => 'text/html'],
            'user-agent'  => 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (compatible; ZC-Diagnostika)',
        ]);

        if (is_wp_error($res)) {
            $hops[] = ['url' => $cur, 'code' => 0, 'to' => '', 'err' => $res->get_error_message()];
            return ['hops' => $hops, 'verdict' => 'error'];
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $loc  = trim((string) wp_remote_retrieve_header($res, 'location'));
        $hops[] = ['url' => $cur, 'code' => $code, 'to' => $loc, 'err' => ''];

        if ($code < 300 || $code >= 400 || $loc === '') {
            return ['hops' => $hops, 'verdict' => ($code >= 200 && $code < 300) ? 'ok' : 'status'];
        }

        // Relatívne presmerovanie doplníme na úplnú adresu
        if (strpos($loc, 'http') !== 0) {
            $p   = wp_parse_url($cur);
            $loc = $p['scheme'] . '://' . $p['host'] . (strpos($loc, '/') === 0 ? '' : '/') . $loc;
        }

        $key = strtolower(rtrim($loc, '/'));
        if (isset($seen[$key])) {
            $hops[] = ['url' => $loc, 'code' => -1, 'to' => '', 'err' => 'sem sa už raz išlo'];
            return ['hops' => $hops, 'verdict' => 'loop'];
        }
        $seen[$key] = true;
        $cur = $loc;
    }

    return ['hops' => $hops, 'verdict' => 'toolong'];
}

/** Jednoduchý stavový kód pre jednu adresu (bez sledovania reťaze). */
function zc_index_status($url) {
    $res = wp_remote_get($url, ['timeout' => 12, 'sslverify' => true, 'redirection' => 3]);
    if (is_wp_error($res)) return ['code' => 0, 'err' => $res->get_error_message()];
    return ['code' => (int) wp_remote_retrieve_response_code($res), 'err' => ''];
}

/** Adresy máp stránok, ktoré má zmysel skontrolovať. */
function zc_index_sitemaps() {
    $urls = [];
    if (function_exists('wp_sitemaps_get_server') && get_option('blog_public')) {
        $urls['WordPress'] = home_url('/wp-sitemap.xml');
    }
    if (defined('WPSEO_VERSION'))       $urls['Yoast']    = home_url('/sitemap_index.xml');
    if (class_exists('RankMath'))       $urls['Rank Math'] = home_url('/sitemap_index.xml');
    if (function_exists('aioseo'))      $urls['AIOSEO']   = home_url('/sitemap.xml');
    return $urls;
}

/* ───────────────────── 3. Stránka v admine ───────────────────── */

add_action('admin_menu', function () {
    add_submenu_page(zc_hub_slug(), 'Indexovanie v Google', 'Indexovanie', 'manage_options',
        'zc-indexovanie', 'zc_hub_index_page');
}, 51);

function zc_hub_index_page() {
    if (!current_user_can('manage_options')) wp_die('Nemáš oprávnenie.');
    zc_hub_styles();

    $run = isset($_POST['zc_index_run']) && check_admin_referer('zc_index');

    $home    = untrailingslashit(get_option('home'));
    $site    = untrailingslashit(get_option('siteurl'));
    $public  = (int) get_option('blog_public', 1);
    $maint   = (int) get_option('zc_maintenance_on', 0);
    $host    = (string) wp_parse_url($home, PHP_URL_HOST);
    $bare    = preg_replace('/^www\./i', '', $host);
    ?>
    <div class="wrap zch">
        <div class="zch-head"><h1>Indexovanie v Google</h1></div>

        <div class="zch-note" style="margin-bottom:18px">
            Search Console hlási <strong>„Chyba presmerovania“</strong> vtedy, keď sa robot pri načítaní
            adresy zacyklí alebo keď je reťaz presmerovaní pridlhá. Tu zistíš, či sa to deje,
            a hlavne kde. Kontrola robí skutočné požiadavky na tvoj web, trvá pár sekúnd.
        </div>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Čo môže brániť indexovaniu</h2>
            <table class="zch-tbl">
                <tr><td>Viditeľnosť pre vyhľadávače</td><td><?php echo $public
                    ? '<span class="zch-ok">povolená</span>'
                    : '<span class="zch-bad">ZAKÁZANÁ – Nastavenia → Čítanie → odškrtni „Odrádzať vyhľadávače“</span>'; ?></td></tr>
                <tr><td>Režim údržby</td><td><?php echo $maint
                    ? '<span class="zch-bad">ZAPNUTÝ – web vracia robotom chybu 503, nič sa neindexuje</span>'
                    : '<span class="zch-ok">vypnutý</span>'; ?></td></tr>
                <tr><td>Adresa webu (home)</td><td><code><?php echo esc_html($home); ?></code></td></tr>
                <tr><td>Adresa WordPressu (siteurl)</td><td><?php
                    echo '<code>' . esc_html($site) . '</code>';
                    if (wp_parse_url($home, PHP_URL_HOST) !== wp_parse_url($site, PHP_URL_HOST)
                        || wp_parse_url($home, PHP_URL_SCHEME) !== wp_parse_url($site, PHP_URL_SCHEME)) {
                        echo ' <span class="zch-bad">– nesedí s adresou webu, to zacyklenie priamo spôsobuje</span>';
                    }
                ?></td></tr>
                <tr><td>Toto načítanie prišlo cez</td><td><?php
                    echo is_ssl() ? '<span class="zch-ok">HTTPS</span>' : '<span class="zch-warn">HTTP</span>';
                    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || !empty($_SERVER['HTTP_CF_VISITOR'])) {
                        echo ' · pred webom je proxy (Cloudflare alebo podobná) – poistka proti zacykleniu je zapnutá';
                    }
                ?></td></tr>
                <tr><td>robots.txt</td><td><a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank"><?php
                    echo esc_html(home_url('/robots.txt')); ?></a></td></tr>
            </table>
        </div>

        <form method="post">
            <?php wp_nonce_field('zc_index'); ?>
            <p><button class="button button-primary" name="zc_index_run" value="1">Skontrolovať presmerovania</button></p>
        </form>

        <?php if ($run):
            $tests = [
                'http://'  . $bare . '/',
                'https://' . $bare . '/',
                'http://www.'  . $bare . '/',
                'https://www.' . $bare . '/',
            ];
            $labels = [
                'loop'    => ['zch-bad',  'ZACYKLENÉ – presne toto Google hlási ako Chybu presmerovania'],
                'toolong' => ['zch-bad',  'pridlhá reťaz presmerovaní'],
                'error'   => ['zch-warn', 'nepodarilo sa spojiť'],
                'status'  => ['zch-warn', 'skončilo chybovým stavom'],
                'ok'      => ['zch-ok',   'v poriadku'],
            ];
            $any_loop = false;
        ?>
        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Reťaz presmerovaní</h2>
            <?php foreach ($tests as $t):
                $r = zc_index_trace($t);
                [$cls, $txt] = $labels[$r['verdict']] ?? ['zch-warn', $r['verdict']];
                if ($r['verdict'] === 'loop' || $r['verdict'] === 'toolong') $any_loop = true;
            ?>
            <p style="margin:16px 0 6px"><strong><?php echo esc_html($t); ?></strong>
               → <span class="<?php echo $cls; ?>"><?php echo esc_html($txt); ?></span></p>
            <table class="zch-tbl">
                <?php foreach ($r['hops'] as $h): ?>
                <tr>
                    <td style="width:62%"><code style="font-size:12px"><?php echo esc_html($h['url']); ?></code></td>
                    <td><?php
                        if ($h['err']) { echo '<span class="zch-bad">' . esc_html($h['err']) . '</span>'; }
                        elseif ($h['to']) { echo esc_html($h['code']) . ' → <code style="font-size:12px">' . esc_html($h['to']) . '</code>'; }
                        else { echo esc_html($h['code']); }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endforeach; ?>

            <?php if ($any_loop): ?>
            <div class="zch-note" style="border-color:#fecaca;background:#fef2f2;margin-top:18px">
                <strong>Našlo sa zacyklenie.</strong> Skoro vždy má jednu z týchto troch príčin:
                <ol style="margin:8px 0 0 18px">
                    <li><strong>Cloudflare v režime „Flexible SSL“</strong> – prepni na <em>Full (strict)</em>.
                        Flexible hovorí WordPressu, že návšteva prišla cez http, ten ju pošle na https a dokola.</li>
                    <li><strong>Presmerovanie v .htaccess aj u hostingu naraz</strong> – nechaj len jedno.</li>
                    <li><strong>www a bez www si navzájom posielajú návštevu</strong> – vyber si jednu adresu
                        (odporúčam tú v poli „Adresa webu“ vyššie) a druhá nech ukazuje na ňu, nie naopak.</li>
                </ol>
            </div>
            <?php endif; ?>
        </div>

        <div class="zch-box">
            <h2 style="margin-top:0;font-size:16px">Mapa stránok (sitemap)</h2>
            <table class="zch-tbl">
                <?php $maps = zc_index_sitemaps();
                if (!$maps): ?>
                <tr><td colspan="2"><span class="zch-warn">Žiadna mapa stránok sa nenašla.</span></td></tr>
                <?php else: foreach ($maps as $name => $u): $s = zc_index_status($u); ?>
                <tr>
                    <td><?php echo esc_html($name); ?><br><code style="font-size:11.5px"><?php echo esc_html($u); ?></code></td>
                    <td><?php
                        if ($s['code'] === 200) echo '<span class="zch-ok">200 – dostupná</span>';
                        elseif ($s['code']) echo '<span class="zch-bad">' . esc_html($s['code']) . '</span>';
                        else echo '<span class="zch-warn">' . esc_html($s['err']) . '</span>';
                    ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
            <p style="color:#6b6560;font-size:12.5px;margin-bottom:0">
                Do Search Console sa mapa vkladá presne v tvare, ktorý je tu vypísaný.
                Keď hlási „Dočasná chyba spracovania“, býva to ten istý problém s presmerovaním –
                po jeho oprave daj v Search Console <em>Vyžiadať indexovanie</em> znova.
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
