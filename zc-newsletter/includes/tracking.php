<?php
/**
 * Sledovanie otvorení a klikov v newsletteri.
 * - open: neviditeľný 1×1 pixel na konci e-mailu
 * - click: odkazy sa prepíšu cez presmerovací endpoint, ktorý zaráta klik
 * Dáta sa ukladajú do options 'zcn_campaigns' (bez extra DB tabuľky).
 */
defined('ABSPATH') || exit;

// Vytvorí kampaň a vráti jej ID
function zcn_new_campaign($subject, $sent = 0) {
    $camps = get_option('zcn_campaigns', []);
    $cid = 'c' . time() . wp_rand(100, 999);
    $camps[$cid] = [
        'subject'    => sanitize_text_field($subject),
        'date'       => current_time('mysql'),
        'sent'       => (int) $sent,
        'opens'      => [],   // unikátne hashe e-mailov
        'clicks'     => [],   // unikátne hashe e-mailov
        'click_urls' => [],   // url => počet
    ];
    // Uchovať posledných 60 kampaní
    if (count($camps) > 60) {
        $camps = array_slice($camps, -60, null, true);
    }
    update_option('zcn_campaigns', $camps, false);
    return $cid;
}

function zcn_campaign_set_sent($cid, $sent) {
    $camps = get_option('zcn_campaigns', []);
    if (isset($camps[$cid])) {
        $camps[$cid]['sent'] = (int) $sent;
        update_option('zcn_campaigns', $camps, false);
    }
}

// Krátky hash e-mailu (kvôli súkromiu neukladáme čistý e-mail)
function zcn_hash_email($email) {
    return substr(md5('zcn|' . strtolower(trim($email))), 0, 12);
}

/**
 * Prepíše odkazy na tracked verzie a pridá open-pixel.
 */
function zcn_apply_tracking($html, $cid, $email) {
    if (!$cid) return $html;
    $eh = zcn_hash_email($email);

    // Prepis <a href="http...">
    $html = preg_replace_callback('/<a\b([^>]*?)href=(["\'])(https?:\/\/[^"\']+)\2/i', function ($m) use ($cid, $eh) {
        $url = $m[3];
        // Nepredplietať unsubscribe / mailto / už trackované odkazy
        if (strpos($url, 'zcn_track') !== false || strpos($url, 'zcn_unsub') !== false || strpos($url, 'zcn_action') !== false) {
            return $m[0];
        }
        $track = add_query_arg([
            'zcn_track' => 'click',
            'c'         => $cid,
            'e'         => $eh,
            'u'         => rawurlencode($url),
        ], home_url('/'));
        return '<a' . $m[1] . 'href=' . $m[2] . esc_url($track) . $m[2];
    }, $html);

    // Open pixel
    $pixel = add_query_arg(['zcn_track' => 'open', 'c' => $cid, 'e' => $eh], home_url('/'));
    $img = '<img src="' . esc_url($pixel) . '" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0">';
    if (stripos($html, '</body>') !== false) {
        $html = str_ireplace('</body>', $img . '</body>', $html);
    } else {
        $html .= $img;
    }
    return $html;
}

// ── Endpoint: záznam otvorenia / kliku ─────────────────────────────────────
add_action('init', function () {
    if (empty($_GET['zcn_track'])) return;
    $type = sanitize_text_field($_GET['zcn_track']);
    $cid  = sanitize_text_field($_GET['c'] ?? '');
    $eh   = preg_replace('/[^a-f0-9]/', '', $_GET['e'] ?? '');

    $camps = get_option('zcn_campaigns', []);
    if ($cid && isset($camps[$cid]) && $eh) {
        if ($type === 'open') {
            if (!in_array($eh, $camps[$cid]['opens'], true)) {
                $camps[$cid]['opens'][] = $eh;
                update_option('zcn_campaigns', $camps, false);
            }
        } elseif ($type === 'click') {
            if (!in_array($eh, $camps[$cid]['clicks'], true)) {
                $camps[$cid]['clicks'][] = $eh;
            }
            $url = isset($_GET['u']) ? esc_url_raw(rawurldecode($_GET['u'])) : '';
            if ($url) {
                $camps[$cid]['click_urls'][$url] = ($camps[$cid]['click_urls'][$url] ?? 0) + 1;
            }
            update_option('zcn_campaigns', $camps, false);
        }
    }

    if ($type === 'click') {
        $url = isset($_GET['u']) ? esc_url_raw(rawurldecode($_GET['u'])) : home_url('/');
        // Bezpečnosť: presmeruj len na http(s)
        if (!preg_match('#^https?://#i', $url)) $url = home_url('/');
        wp_redirect($url, 302);
        exit;
    }

    // open → priehľadný 1×1 GIF
    nocache_headers();
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
});

// Súhrnné čísla pre panel
function zcn_campaign_stats($cid) {
    $camps = get_option('zcn_campaigns', []);
    if (!isset($camps[$cid])) return null;
    $c = $camps[$cid];
    $sent   = max(1, (int) $c['sent']);
    $opens  = count($c['opens']);
    $clicks = count($c['clicks']);
    return [
        'subject'    => $c['subject'],
        'date'       => $c['date'],
        'sent'       => (int) $c['sent'],
        'opens'      => $opens,
        'clicks'     => $clicks,
        'open_rate'  => round($opens / $sent * 100),
        'click_rate' => round($clicks / $sent * 100),
        'click_urls' => $c['click_urls'] ?? [],
    ];
}

function zcn_all_campaigns() {
    $camps = get_option('zcn_campaigns', []);
    $out = [];
    foreach (array_reverse($camps, true) as $cid => $c) {
        $out[$cid] = zcn_campaign_stats($cid);
    }
    return $out;
}
