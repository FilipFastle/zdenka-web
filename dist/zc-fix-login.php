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
    <div class="warn">V prehliadači už neboli žiadne WordPress cookies. Ak ťa to aj tak loopuje, príčina je v <code>wp-config.php</code> – pozri krok 2 nižšie.</div>
    <?php endif; ?>

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
