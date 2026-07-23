<?php
defined('ABSPATH') || exit;
// AJAX subscribe handler
add_action('wp_ajax_zcn_subscribe',        'zcn_handle_subscribe');
add_action('wp_ajax_nopriv_zcn_subscribe', 'zcn_handle_subscribe');

function zcn_handle_subscribe() {
    check_ajax_referer('zcn_nonce', 'nonce');

    // SECURITY: rate limit – max 5 subscribe requests / 10 min per IP
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = 'zcn_sub_' . md5($ip);
    $attempts = (int) get_transient($key);
    if ($attempts >= 5) {
        wp_send_json_error(['message' => 'Príliš veľa pokusov. Skúste neskôr.']);
    }
    set_transient($key, $attempts + 1, 10 * MINUTE_IN_SECONDS);

    $email = sanitize_email($_POST['email'] ?? '');
    $name  = sanitize_text_field($_POST['name']  ?? '');

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Zadajte platnú e-mailovú adresu.']);
    }

    global $wpdb;
    $table = zcn_table();

    // Check existing
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE email = %s", $email
    ));

    if ($existing) {
        if ($existing->status === 'active') {
            wp_send_json_error(['message' => 'Táto adresa je už prihlásená na odber.']);
        }
        if ($existing->status === 'pending') {
            // Resend confirmation
            zcn_send_confirmation($email, $name ?: $existing->name, $existing->token);
            wp_send_json_success(['message' => 'Potvrdzovací e-mail bol znovu odoslaný. Skontrolujte schránku.']);
        }
        if ($existing->status === 'unsubscribed') {
            // Resubscribe
            $token = zcn_generate_token();
            $wpdb->update($table,
                ['status' => 'pending', 'token' => $token, 'name' => $name, 'subscribed_at' => current_time('mysql')],
                ['email' => $email]
            );
            zcn_send_confirmation($email, $name, $token);
            wp_send_json_success(['message' => 'Skontrolujte e-mail a potvrďte prihlásenie.']);
        }
    }

    // New subscriber
    $token = zcn_generate_token();
    $wpdb->insert($table, [
        'email'  => $email,
        'name'   => $name,
        'status' => 'pending',
        'token'  => $token,
        'source' => sanitize_text_field($_POST['source'] ?? 'web'),
    ]);

    zcn_send_confirmation($email, $name, $token);
    wp_send_json_success(['message' => 'Skontrolujte e-mail a potvrďte prihlásenie na odber. ']);
}

function zcn_send_confirmation($email, $name, $token) {
    $confirm_url = add_query_arg(['zcn_action' => 'confirm', 'token' => $token], home_url('/'));
    $unsub_url   = add_query_arg(['zcn_action' => 'unsubscribe', 'token' => $token], home_url('/'));
    $site        = function_exists('zc_agent') ? zc_agent('name', 'Zdenka Cibuľová') : get_bloginfo('name');
    $from_email  = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $greeting    = $name ? "Dobrý deň {$name}," : 'Dobrý deň,';

    $subject = "Potvrďte prihlásenie na odber – {$site}";
    $body    = zcn_email_wrap($subject, "
        <p style='font-size:16px;color:#2C2825;margin:0 0 20px'>{$greeting}</p>
        <p style='color:#555;line-height:1.75;margin:0 0 28px'>
            Dostali sme žiadosť o prihlásenie na odber noviniek z webu <strong>{$site}</strong>.<br>
            Ak ste to boli vy, kliknite na tlačidlo nižšie pre potvrdenie.
        </p>
        <div style='text-align:center;margin:32px 0'>
            <a href='" . esc_url($confirm_url) . "'
               style='display:inline-block;padding:14px 32px;background:#B8A47A;color:#1C1A18;
                      text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;
                      letter-spacing:.5px;font-family:DM Sans,sans-serif'>
                Potvrdiť prihlásenie
            </a>
        </div>
        <p style='font-size:12px;color:#999;text-align:center'>
            Ak ste o prihlásenie nežiadali, ignorujte tento e-mail.<br>
            <a href='" . esc_url($unsub_url) . "' style='color:#bbb'>Odhlásiť sa</a>
        </p>
    ");

    wp_mail($email, $subject, $body, [
        'Content-Type: text/html; charset=UTF-8',
        "From: {$site} <{$from_email}>",
    ]);
}

// Uvítací e-mail po potvrdení / priamom prihlásení na odber
function zcn_send_welcome($email, $name = '') {
    if (!is_email($email)) return;
    $site       = function_exists('zc_agent') ? zc_agent('name', 'Zdenka Cibuľová') : get_bloginfo('name');
    $from_email = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $greeting   = $name ? "Dobrý deň {$name}," : 'Dobrý deň,';
    $subject    = "Vitajte v odbere noviniek — {$site}";
    $body = zcn_email_wrap($subject, "
        <p style='font-size:16px;color:#2C2825;margin:0 0 20px'>{$greeting}</p>
        <p style='color:#555;line-height:1.75;margin:0 0 20px'>
            ďakujeme za prihlásenie na odber noviniek. Odteraz vám budem posielať
            <strong>nové ponuky nehnuteľností</strong> a tipy zo sveta realít ako prvým.
        </p>
        <p style='color:#555;line-height:1.75;margin:0 0 8px'>
            Ak by ste čokoľvek potrebovali, pokojne mi napíšte alebo zavolajte.
        </p>
        <p style='color:#2C2825;margin:20px 0 0'>S pozdravom,<br><strong>{$site}</strong></p>
    ");
    wp_mail($email, $subject, $body, [
        'Content-Type: text/html; charset=UTF-8',
        "From: {$site} <{$from_email}>",
    ]);
}

// Forced subscription - direct active (no confirmation email, used from form opt-in)
function zcn_subscribe_forced($email, $name = '', $source = 'form') {
    if (!is_email($email)) return false;
    global $wpdb;
    $table = zcn_table();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE email=%s", $email));
    if ($existing) {
        if ($existing->status === 'active') return true; // already subscribed
        $token = zcn_generate_token();
        $wpdb->update($table,
            ['status'=>'active','token'=>$token,'name'=>$name,
             'subscribed_at'=>current_time('mysql'),'confirmed_at'=>current_time('mysql'),'source'=>$source],
            ['email'=>$email]
        );
    } else {
        $token = zcn_generate_token();
        $wpdb->insert($table, [
            'email'        => $email,
            'name'         => $name,
            'status'       => 'active',
            'token'        => $token,
            'source'       => $source,
            'confirmed_at' => current_time('mysql'),
        ]);
    }
    zcn_send_welcome($email, $name);
    return true;
}

// Direct subscription (from other forms) - sends confirmation email
function zcn_subscribe_direct($email, $name = '', $source = 'web') {
    if (!is_email($email)) return false;
    global $wpdb;
    $table = zcn_table();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE email=%s", $email));
    if ($existing && in_array($existing->status, ['active','pending'])) return false;

    $token = zcn_generate_token();
    if ($existing) {
        $wpdb->update($table, ['status'=>'pending','token'=>$token,'name'=>$name,'subscribed_at'=>current_time('mysql'),'source'=>$source], ['email'=>$email]);
    } else {
        $wpdb->insert($table, ['email'=>$email,'name'=>$name,'status'=>'pending','token'=>$token,'source'=>$source]);
    }
    zcn_send_confirmation($email, $name, $token);
    return true;
}
