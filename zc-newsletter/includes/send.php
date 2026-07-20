<?php
defined('ABSPATH') || exit;
add_action('wp_ajax_zcn_send_newsletter', 'zcn_handle_send');

function zcn_handle_send() {
    check_ajax_referer('zcn_send_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $body_md = wp_kses_post($_POST['body'] ?? '');
    $test    = !empty($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    $preview = !empty($_POST['preview']);

    if (!$subject || !$body_md) {
        wp_send_json_error(['message' => 'Predmet a obsah sú povinné.']);
    }

    // TinyMCE sends ready HTML; legacy markdown converted otherwise
    $body_html = !empty($_POST['is_html']) ? $body_md : zcn_markdown_to_html($body_md);

    if ($preview) {
        wp_send_json_success(['html' => $body_html]);
    }

    $from_name  = function_exists('zc_agent') ? zc_agent('name', 'Zdenka Cibuľová') : get_bloginfo('name');
    $from_email = get_theme_mod('zc_email_from', 'noreply@zdenkacibulova.sk');
    $headers    = [
        'Content-Type: text/html; charset=UTF-8',
        "From: {$from_name} <{$from_email}>",
    ];

    if ($test) {
        $t_subj = function_exists('zcn_apply_vars') ? zcn_apply_vars($subject, ['meno'=>'Test','email'=>$test]) : $subject;
        $t_body = function_exists('zcn_apply_vars') ? zcn_apply_vars($body_html, ['meno'=>'Test','email'=>$test]) : $body_html;
        $html = zcn_build_newsletter_email($t_subj, $t_body, zcn_generate_token(), '');
        $ok   = wp_mail($test, "[TEST] {$t_subj}", $html, $headers);
        wp_send_json_success(['message' => $ok ? "✅ Testovací e-mail odoslaný na {$test}" : '❌ Odoslanie zlyhalo.']);
    }

    global $wpdb;
    $subscribers = $wpdb->get_results("SELECT * FROM " . zcn_table() . " WHERE status='active'");
    if (empty($subscribers)) {
        wp_send_json_error(['message' => 'Žiadni aktívni odberatelia.']);
    }

    $sent = 0; $failed = 0;
    foreach ($subscribers as $sub) {
        // Premenné {meno}/{email} — dosadené pre každého odberateľa zvlášť
        $vars   = ['meno' => $sub->name ?: '', 'email' => $sub->email];
        $s_subj = function_exists('zcn_apply_vars') ? zcn_apply_vars($subject, $vars) : $subject;
        $s_body = function_exists('zcn_apply_vars') ? zcn_apply_vars($body_html, $vars) : $body_html;
        $html = zcn_build_newsletter_email($s_subj, $s_body, $sub->token, $sub->name);
        wp_mail($sub->email, $s_subj, $html, $headers) ? $sent++ : $failed++;
        usleep(150000);
    }

    $log = get_option('zcn_send_log', []);
    array_unshift($log, [
        'date'    => current_time('mysql'),
        'subject' => $subject,
        'sent'    => $sent,
        'failed'  => $failed,
    ]);
    update_option('zcn_send_log', array_slice($log, 0, 30));

    wp_send_json_success(['message' => "✅ Odoslané: {$sent}" . ($failed ? " | ⚠️ Zlyhalo: {$failed}" : '')]);
}

function zcn_build_newsletter_email($subject, $body_html, $token, $name = '') {
    $unsub    = zcn_unsubscribe_url($token);
    $greeting = $name ? '<p style="font-size:15px;color:#2C2825;margin:0 0 20px;font-family:\'DM Sans\',Arial,sans-serif">Dobrý deň <strong>' . esc_html($name) . '</strong>,</p>' : '';

    $footer = 'Dostávate tento e-mail pretože ste prihlásení na odber noviniek. · <a href="' . esc_url($unsub) . '" style="color:#9A8660">Odhlásiť sa</a><br>';

    return zcn_email_wrap($subject, $greeting . $body_html, $footer);
}
