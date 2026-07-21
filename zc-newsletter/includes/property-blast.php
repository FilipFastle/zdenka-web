<?php
defined('ABSPATH') || exit;
// ── Blast novej ponuky odberateľom – jeden klik z Realitného panela ──────

function zcn_property_email_parts($pid) {
    $title  = get_the_title($pid);
    $url    = get_permalink($pid);
    $cena   = get_post_meta($pid, '_property_cena', true);
    if ($cena && strpos($cena, '€') === false) $cena .= ' €';
    $lok    = get_post_meta($pid, '_property_lokalita', true);
    $plocha = get_post_meta($pid, '_property_plocha', true);
    $izby   = get_post_meta($pid, '_property_spalne', true);
    $typ    = get_post_meta($pid, '_property_typ', true);
    $popis  = get_post_meta($pid, '_property_popis_kratky', true);
    $cover  = get_post_meta($pid, '_property_cover_id', true);
    $img    = $cover ? wp_get_attachment_image_url($cover, 'large') : get_the_post_thumbnail_url($pid, 'large');
    $typ_labels = ['predaj' => 'Na predaj', 'prenajom' => 'Na prenájom', 'pozemok' => 'Pozemok'];

    $meta = array_filter([
        $lok    ? '' . esc_html($lok) : '',
        $plocha ? '' . esc_html($plocha) . ' m²' : '',
        $izby   ? '' . esc_html($izby) . ' izby' : '',
    ]);

    $body = '';
    if ($img) $body .= '<a href="' . esc_url($url) . '"><img src="' . esc_url($img) . '" alt="' . esc_attr($title) . '" style="width:100%;border-radius:12px;display:block;margin:0 0 20px"></a>';
    if ($typ && isset($typ_labels[$typ])) $body .= '<p style="margin:0 0 6px"><span style="display:inline-block;background:#F5EEDF;color:#7C5E33;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:4px 12px;border-radius:50px">' . $typ_labels[$typ] . '</span></p>';
    $body .= '<h2 style="font-family:Georgia,serif;font-size:22px;color:#1C1A18;margin:0 0 8px">' . esc_html($title) . '</h2>';
    if ($meta)  $body .= '<p style="color:#7A7068;font-size:14px;margin:0 0 14px">' . implode(' &nbsp;·&nbsp; ', $meta) . '</p>';
    if ($cena)  $body .= '<p style="font-family:Georgia,serif;font-size:24px;font-weight:700;color:#7C5E33;margin:0 0 16px">' . esc_html($cena) . '</p>';
    if ($popis) $body .= '<p style="color:#555;line-height:1.75;margin:0 0 24px">' . esc_html($popis) . '</p>';
    $body .= '<div style="text-align:center;margin:28px 0 8px"><a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 32px;background:#B8A47A;color:#1C1A18;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;font-family:\'DM Sans\',Arial,sans-serif">Pozrieť ponuku →</a></div>';

    return ['subject' => 'Nová ponuka: ' . $title, 'body' => $body];
}

add_action('wp_ajax_zcn_send_property', 'zcn_handle_property_blast');
function zcn_handle_property_blast() {
    check_ajax_referer('zcn_send_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_die('Unauthorized');

    $pid  = intval($_POST['property_id'] ?? 0);
    $post = get_post($pid);
    if (!$post || $post->post_type !== 'property' || $post->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Ponuka neexistuje alebo nie je publikovaná.']);
    }

    $parts      = zcn_property_email_parts($pid);
    $from_name  = function_exists('zc_agent') ? zc_agent('name', 'Zdenka Cibuľová') : get_bloginfo('name');
    $from_email = get_theme_mod('zc_email_from', '') ?: get_option('admin_email');
    $headers    = ['Content-Type: text/html; charset=UTF-8', "From: {$from_name} <{$from_email}>"];

    // Testovací e-mail – pošle iba na zadanú adresu
    $test = !empty($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    if ($test) {
        $html = zcn_build_newsletter_email($parts['subject'], $parts['body'], zcn_generate_token(), '');
        $ok   = wp_mail($test, '[TEST] ' . $parts['subject'], $html, $headers);
        wp_send_json_success(['message' => $ok ? "Test odoslaný na {$test}" : 'Odoslanie zlyhalo.']);
    }

    global $wpdb;
    $subscribers = $wpdb->get_results("SELECT * FROM " . zcn_table() . " WHERE status='active'");
    if (empty($subscribers)) wp_send_json_error(['message' => 'Žiadni aktívni odberatelia.']);

    $sent = 0; $failed = 0;
    foreach ($subscribers as $sub) {
        $vars = ['meno' => $sub->name ?: '', 'email' => $sub->email];
        $subj = zcn_apply_vars($parts['subject'], $vars);
        $body = zcn_apply_vars($parts['body'], $vars);
        $html = zcn_build_newsletter_email($subj, $body, $sub->token, $sub->name);
        wp_mail($sub->email, $subj, $html, $headers) ? $sent++ : $failed++;
        usleep(150000);
    }

    $log = get_option('zcn_send_log', []);
    array_unshift($log, ['date' => current_time('mysql'), 'subject' => $parts['subject'], 'sent' => $sent, 'failed' => $failed]);
    update_option('zcn_send_log', array_slice($log, 0, 30));
    update_post_meta($pid, '_zcn_blast_sent', current_time('mysql'));

    wp_send_json_success(['message' => "Ponuka odoslaná {$sent} odberateľom" . ($failed ? " | Zlyhalo: {$failed}" : '')]);
}
