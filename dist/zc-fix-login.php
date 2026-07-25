<?php
/**
 * NÚDZOVÁ OPRAVA PRIHLÁSENIA (reauth=1 / nekonečná slučka)
 *
 * POUŽITIE:
 *   1. Nahraj tento súbor do KOREŇA webu (tam, kde je wp-config.php).
 *   2. Otvor v prehliadači: https://www.zdenkacibulova.sk/zc-fix-login.php
 *   3. Prihlás sa.
 *   4. SÚBOR POTOM ZMAŽ.
 *
 * Súbor NEPOTREBUJE prihlásenie a NIČ nemení na webe – iba zmaže
 * prihlasovacie cookies v TVOJOM prehliadači (vo všetkých variantoch:
 * host-only aj doménové, na všetkých cestách). Nikomu inému nič neurobí,
 * pretože pracuje výhradne s cookies toho, kto ho otvorí.
 */

$host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
$base = preg_replace('/^www\./i', '', $host);

/* ── DIAGNOSTIKA (spustí sa pred mazaním) ───────────────────────────────────
 * PHP v $_COOKIE ponechá pri rovnakom názve len poslednú hodnotu, preto
 * duplicity čítame z hlavičky Cookie, kde sú všetky. */
$raw = (string) ($_SERVER['HTTP_COOKIE'] ?? '');
$names = [];
foreach (explode(';', $raw) as $part) {
    $part = trim($part);
    if ($part === '') continue;
    $n = trim(explode('=', $part, 2)[0]);
    if ($n === '') continue;
    $names[$n] = ($names[$n] ?? 0) + 1;
}
$wp_names  = array_filter($names, function ($n) { return stripos($n, 'wordpress') === 0; }, ARRAY_FILTER_USE_KEY);
$dupes     = array_filter($wp_names, function ($c) { return $c > 1; });

// Kontrola wp-config.php – vypisujeme len počty a nastavenia, žiadne tajomstvá
$cfg_path = __DIR__ . '/wp-config.php';
$cfg_ok   = is_readable($cfg_path);
$cfg      = $cfg_ok ? (string) file_get_contents($cfg_path) : '';
$salt_dupes = [];
foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $k) {
    $c = preg_match_all("/define\(\s*['\"]" . $k . "['\"]/", $cfg);
    if ($c > 1) $salt_dupes[] = $k . ' (' . $c . '×)';
}
preg_match("/define\(\s*['\"]COOKIE_DOMAIN['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $cfg, $m_cd);
$cookie_domain = $m_cd[1] ?? null;
$has_cache = file_exists(__DIR__ . '/wp-content/advanced-cache.php')
          || file_exists(__DIR__ . '/wp-content/object-cache.php');

// Varianty domén, pod ktorými mohla cookie vzniknúť
$domains = array_unique(array_filter([
    '',                 // host-only (bez atribútu Domain)
    $host,              // www.domena.sk
    '.' . $host,        // .www.domena.sk
    $base,              // domena.sk
    '.' . $base,        // .domena.sk
]));
$paths = ['/', '/wp-admin', '/wp-admin/', '/wp-content/plugins'];

$deleted = [];
foreach (array_keys($_COOKIE) as $name) {
    // Mažeme len prihlasovacie / nastavovacie cookies WordPressu
    if (stripos($name, 'wordpress') !== 0 && stripos($name, 'wp-settings') !== 0
        && stripos($name, 'wp_') !== 0 && stripos($name, 'comment_') !== 0) {
        continue;
    }
    $deleted[] = $name;
    foreach ($domains as $d) {
        foreach ($paths as $p) {
            if ($d === '') {
                setcookie($name, '', time() - 31536000, $p);
            } else {
                setcookie($name, '', time() - 31536000, $p, $d);
            }
        }
    }
    unset($_COOKIE[$name]);
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
?><!DOCTYPE html>
<html lang="sk"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Oprava prihlásenia</title>
<style>
body{font-family:-apple-system,'Segoe UI',Roboto,sans-serif;background:#F5F1EA;margin:0;padding:32px 20px;color:#2C2825;line-height:1.6}
.box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #E0D8CE;border-radius:16px;padding:28px 30px;box-shadow:0 8px 32px rgba(60,50,30,.10)}
h1{font-size:21px;margin:0 0 6px;color:#1C1A18}
.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:12px 15px;border-radius:9px;margin:16px 0;font-weight:600}
.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e;padding:12px 15px;border-radius:9px;margin:16px 0;font-size:14px}
code{background:#f1ece2;padding:2px 7px;border-radius:5px;font-size:13px}
.btn{display:inline-block;background:#B8A47A;color:#1C1A18;font-weight:700;text-decoration:none;padding:13px 26px;border-radius:9px;margin-top:8px}
ol{padding-left:20px}li{margin-bottom:8px}
small{color:#6B6560}
</style></head><body>
<div class="box">
    <h1>Oprava prihlásenia</h1>
    <p><small>Vyčistenie pokazených prihlasovacích cookies</small></p>

    <?php if ($deleted): ?>
    <div class="ok">✓ Vymazaných cookies: <?php echo count($deleted); ?></div>
    <?php else: ?>
    <div class="warn">V prehliadači už neboli žiadne WordPress cookies. Ak ťa to aj tak loopuje, pozri diagnostiku nižšie.</div>
    <?php endif; ?>

    <h2 style="font-size:15px;margin-top:24px">Diagnostika – čo spôsobuje slučku</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px">
        <tr><td style="padding:8px 0;border-bottom:1px solid #eee">Duplicitné prihlasovacie cookies</td>
            <td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">
                <?php if ($dupes): ?><strong style="color:#b91c1c">ÁNO – toto je príčina</strong>
                <?php else: ?><strong style="color:#15803d">nie</strong><?php endif; ?>
            </td></tr>
        <?php if ($dupes): foreach ($dupes as $n => $c): ?>
        <tr><td colspan="2" style="padding:2px 0 8px;border-bottom:1px solid #eee;color:#b91c1c;font-size:12px">
            <code><?php echo htmlspecialchars($n); ?></code> poslaná <?php echo (int) $c; ?>× (rôzne domény)</td></tr>
        <?php endforeach; endif; ?>

        <tr><td style="padding:8px 0;border-bottom:1px solid #eee">Duplicitné bezpečnostné kľúče v <code>wp-config.php</code></td>
            <td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">
                <?php if (!$cfg_ok): ?><span style="color:#92400e">neviem prečítať</span>
                <?php elseif ($salt_dupes): ?><strong style="color:#b91c1c">ÁNO – toto je príčina</strong>
                <?php else: ?><strong style="color:#15803d">nie</strong><?php endif; ?>
            </td></tr>
        <?php if ($salt_dupes): ?>
        <tr><td colspan="2" style="padding:2px 0 8px;border-bottom:1px solid #eee;color:#b91c1c;font-size:12px">
            Viackrát definované: <?php echo htmlspecialchars(implode(', ', $salt_dupes)); ?> – nechaj od každého len jeden riadok.</td></tr>
        <?php endif; ?>

        <tr><td style="padding:8px 0;border-bottom:1px solid #eee"><code>COOKIE_DOMAIN</code></td>
            <td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">
                <?php if ($cookie_domain === null): ?><span style="color:#15803d">nie je nastavená</span>
                <?php else: ?>
                    <code><?php echo htmlspecialchars($cookie_domain); ?></code>
                    <?php if ($cookie_domain !== '' && stripos($host, ltrim($cookie_domain, '.')) === false): ?>
                        <br><strong style="color:#b91c1c">nesedí s doménou <?php echo htmlspecialchars($host); ?> – toto je príčina</strong>
                    <?php endif; ?>
                <?php endif; ?>
            </td></tr>

        <tr><td style="padding:8px 0;border-bottom:1px solid #eee">Vyrovnávacia pamäť (cache) na serveri</td>
            <td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right">
                <?php echo $has_cache ? '<strong style="color:#92400e">áno – môže cachovať prihlásenie</strong>' : '<strong style="color:#15803d">nie</strong>'; ?>
            </td></tr>

        <tr><td style="padding:8px 0">Čas servera</td>
            <td style="padding:8px 0;text-align:right"><code><?php echo gmdate('H:i:s'); ?> UTC</code><span id="clk"></span></td></tr>
    </table>
    <script>
    (function(){
        var s=<?php echo time(); ?>*1000, e=document.getElementById('clk');
        var d=Math.round(Math.abs(Date.now()-s)/1000);
        e.innerHTML = d<120 ? ' <span style="color:#15803d">✓</span>'
            : '<br><strong style="color:#b91c1c">rozdiel '+d+' s oproti tvojmu zariadeniu – zle nastavený čas servera spôsobuje slučku</strong>';
    })();
    </script>

    <p><a class="btn" href="/wp-login.php">Prihlásiť sa →</a></p>

    <h2 style="font-size:15px;margin-top:26px">Ak ťa to stále loopuje</h2>
    <ol>
        <li>Otvor <code>wp-config.php</code> a <strong>zmaž tieto riadky</strong> (ak tam sú):
            <br><code>define('COOKIE_DOMAIN', …);</code>
            <br><code>define('COOKIEPATH', …);</code>
            <br><code>define('SITECOOKIEPATH', …);</code>
            <br><code>define('ADMIN_COOKIE_PATH', …);</code>
        </li>
        <li>Otvor tento súbor ešte raz (aby sa cookies vyčistili nanovo).</li>
        <li>Prihlás sa. Ak to ide, funguje to – panel na subdoméne doriešime potom.</li>
    </ol>

    <div class="warn" style="margin-top:22px">
        ⚠️ Keď sa prihlásiš, <strong>tento súbor zmaž zo servera</strong> (<code>zc-fix-login.php</code>).
    </div>
</div>
</body></html>
