<?php
defined('ABSPATH') || exit;
// Confirmation + Unsubscribe URL handler
add_action('init', function() {
    $action = sanitize_text_field($_GET['zcn_action'] ?? '');
    $token  = sanitize_text_field($_GET['token']      ?? '');
    if (!$action || !$token) return;

    global $wpdb;
    $table = zcn_table();
    $row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE token = %s", $token));

    if (!$row) {
        wp_die('<h2>Neplatný odkaz.</h2><p><a href="' . home_url() . '">← Späť na web</a></p>', 'Chyba', ['response' => 400]);
    }

    if ($action === 'confirm') {
        if ($row->status === 'active') {
            wp_die('<div style="font-family:sans-serif;max-width:480px;margin:80px auto;text-align:center"><h2>Odber už potvrdený</h2><p>Ste prihlásený na odber noviniek.</p><a href="' . home_url() . '">← Späť na web</a></div>', 'Potvrdené');
        }
        $wpdb->update($table,
            ['status' => 'active', 'confirmed_at' => current_time('mysql')],
            ['token'  => $token]
        );
        wp_die(zcn_page_response(
            'Prihlásenie potvrdené!',
            'Ste prihlásený na odber noviniek. Budeme vás informovať o nových ponukách.',
            '← Späť na web'
        ), 'Potvrdené');
    }

    if ($action === 'unsubscribe') {
        $wpdb->update($table,
            ['status' => 'unsubscribed'],
            ['token'  => $token]
        );
        wp_die(zcn_page_response(
            'Odhlásenie úspešné',
            'Boli ste odhlásený z odberu noviniek. Nebudeme vás viac kontaktovať.',
            '← Späť na web'
        ), 'Odhlásenie');
    }
});

function zcn_page_response($title, $text, $btn_label) {
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>*{margin:0;padding:0;box-sizing:border-box}body{background:linear-gradient(135deg,#F5EEDF 0%,#EBDCC0 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:"DM Sans",sans-serif;padding:24px}
    .card{background:#fff;border:1px solid #E0D8CE;border-radius:20px;padding:48px 40px;max-width:440px;width:100%;text-align:center;box-shadow:0 16px 56px rgba(40,32,20,.14)}
    h2{font-family:"Playfair Display",serif;font-size:26px;color:#1C1A18;margin-bottom:14px}
    p{font-size:15px;color:#6B6560;line-height:1.75;margin-bottom:28px}
    a{display:inline-block;padding:12px 28px;background:#B8A47A;color:#1C1A18;text-decoration:none;border-radius:8px;font-weight:700;font-size:13px;transition:background .2s}
    a:hover{background:#9A8660}</style>
    </head><body><div class="card">
    <h2>' . esc_html($title) . '</h2>
    <p>' . esc_html($text) . '</p>
    <a href="' . home_url() . '">' . esc_html($btn_label) . '</a>
    </div></body></html>';
}
