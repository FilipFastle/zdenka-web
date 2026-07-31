<?php
defined('ABSPATH') || exit;
add_action('wp_ajax_zcn_send_newsletter', 'zcn_handle_send');

function zcn_handle_send() {
    check_ajax_referer('zcn_send_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_die('Unauthorized');

    // POST dáta prídu od WordPressu „oslashované". Bez wp_unslash() by sa
    // z každej úvodzovky v HTML stalo \" a odkazy aj štýly by sa rozpadli.
    $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
    $body_md = wp_kses_post(wp_unslash($_POST['body'] ?? ''));
    $test    = !empty($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    $preview = !empty($_POST['preview']);
    // „offers" = všetci okrem tých, ktorí si vypli ponuky nehnuteľností.
    // Inak môže byť vybratých aj viac kategórií naraz.
    $raw_interest = (string) ($_POST['interest'] ?? '');
    $only_offers  = (trim($raw_interest) === 'offers');
    $cats         = $only_offers ? [] : zcn_interest_list($raw_interest);
    $interest     = implode(',', $cats);

    if (!$subject || !$body_md) {
        wp_send_json_error(['message' => 'Predmet a obsah sú povinné.']);
    }

    // TinyMCE sends ready HTML; legacy markdown converted otherwise
    $body_html = !empty($_POST['is_html']) ? $body_md : zcn_markdown_to_html($body_md);

    if ($preview) {
        wp_send_json_success(['html' => $body_html]);
    }

    $from_name  = function_exists('zc_agent') ? zc_agent('name', 'Mgr. Zdenka Cibuľová') : 'Mgr. Zdenka Cibuľová';
    $from_email = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $headers    = [
        'Content-Type: text/html; charset=UTF-8',
        "From: {$from_name} <{$from_email}>",
    ];

    if ($test) {
        $t_subj = function_exists('zcn_apply_vars') ? zcn_apply_vars($subject, ['meno'=>'Test','email'=>$test]) : $subject;
        $t_body = function_exists('zcn_apply_vars') ? zcn_apply_vars($body_html, ['meno'=>'Test','email'=>$test]) : $body_html;
        $html = zcn_build_newsletter_email($t_subj, $t_body, zcn_generate_token(), '');
        $ok   = wp_mail($test, "[TEST] {$t_subj}", $html, $headers);
        wp_send_json_success(['message' => $ok ? "Testovací e-mail odoslaný na {$test}" : 'Odoslanie zlyhalo.']);
    }

    // Naplánované odoslanie — uloží do fronty a spustí cez WP-Cron
    $schedule_at = sanitize_text_field($_POST['schedule_at'] ?? '');
    if ($schedule_at) {
        $ts = strtotime($schedule_at);
        if (!$ts || $ts < time() + 60) wp_send_json_error(['message' => 'Zvoľte čas aspoň o pár minút v budúcnosti.']);
        $ts -= (int) (get_option('gmt_offset') * HOUR_IN_SECONDS); // datetime-local je v lokálnom čase
        $queue = get_option('zcn_scheduled', []);
        $key   = 'sch_' . time() . '_' . wp_rand(100, 999);
        $queue[$key] = ['subject' => $subject, 'body' => $body_html, 'interest' => $interest,
                        'only_offers' => $only_offers, 'created' => current_time('mysql'), 'at' => $schedule_at];
        update_option('zcn_scheduled', $queue);
        wp_schedule_single_event($ts, 'zcn_do_scheduled', [$key]);
        wp_send_json_success(['message' => 'Newsletter naplánovaný na ' . esc_html($schedule_at) . '.']);
    }

    global $wpdb;
    // Bez stĺpca „interest" by filtrovaná otázka zlyhala a vyzeralo by to,
    // akoby web nemal žiadnych odberateľov.
    if (function_exists('zcn_ensure_table_ready')) zcn_ensure_table_ready();
    $subscribers = $wpdb->get_results(
        "SELECT * FROM " . zcn_table() . " WHERE status='active'"
        . ($cats ? zcn_interests_sql_where($cats) : ($only_offers ? zcn_offers_sql_where() : ''))
    );
    if (empty($subscribers)) {
        wp_send_json_error(['message' => $cats
            ? 'Vo vybraných kategóriách zatiaľ nikto nie je.'
            : 'Žiadni aktívni odberatelia.']);
    }

    $cid  = function_exists('zcn_new_campaign') ? zcn_new_campaign($subject, count($subscribers)) : '';
    $sent = 0; $failed = 0;
    foreach ($subscribers as $sub) {
        // Premenné {meno}/{email} – dosadené pre každého odberateľa zvlášť
        $vars   = ['meno' => $sub->name ?: '', 'email' => $sub->email];
        $s_subj = function_exists('zcn_apply_vars') ? zcn_apply_vars($subject, $vars) : $subject;
        $s_body = function_exists('zcn_apply_vars') ? zcn_apply_vars($body_html, $vars) : $body_html;
        $html = zcn_build_newsletter_email($s_subj, $s_body, $sub->token, $sub->name);
        if ($cid && function_exists('zcn_apply_tracking')) $html = zcn_apply_tracking($html, $cid, $sub->email);
        $hdr = array_merge($headers, ['List-Unsubscribe: <' . esc_url_raw(zcn_unsubscribe_url($sub->token)) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click']);
        wp_mail($sub->email, $s_subj, $html, $hdr) ? $sent++ : $failed++;
        usleep(150000);
    }
    if ($cid && function_exists('zcn_campaign_set_sent')) zcn_campaign_set_sent($cid, $sent);

    $log = get_option('zcn_send_log', []);
    array_unshift($log, [
        'date'    => current_time('mysql'),
        'subject' => $subject,
        'sent'    => $sent,
        'failed'  => $failed,
    ]);
    update_option('zcn_send_log', array_slice($log, 0, 30));

    wp_send_json_success(['message' => "Odoslané: {$sent}" . ($failed ? " | Zlyhalo: {$failed}" : '')]);
}

// Cron: odoslanie naplánovaného newslettera
add_action('zcn_do_scheduled', function($key) {
    $queue = get_option('zcn_scheduled', []);
    if (empty($queue[$key])) return;
    $item = $queue[$key];
    unset($queue[$key]);
    update_option('zcn_scheduled', $queue);

    global $wpdb;
    $cats = zcn_interest_list($item['interest'] ?? '');
    $subscribers = $wpdb->get_results(
        "SELECT * FROM " . zcn_table() . " WHERE status='active'"
        . ($cats ? zcn_interests_sql_where($cats) : (!empty($item['only_offers']) ? zcn_offers_sql_where() : ''))
    );
    if (empty($subscribers)) return;

    $from_name  = function_exists('zc_agent') ? zc_agent('name', 'Mgr. Zdenka Cibuľová') : 'Mgr. Zdenka Cibuľová';
    $from_email = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $headers    = ['Content-Type: text/html; charset=UTF-8', "From: {$from_name} <{$from_email}>"];

    $cid  = function_exists('zcn_new_campaign') ? zcn_new_campaign($item['subject'], count($subscribers)) : '';
    $sent = 0; $failed = 0;
    foreach ($subscribers as $sub) {
        $vars   = ['meno' => $sub->name ?: '', 'email' => $sub->email];
        $s_subj = function_exists('zcn_apply_vars') ? zcn_apply_vars($item['subject'], $vars) : $item['subject'];
        $s_body = function_exists('zcn_apply_vars') ? zcn_apply_vars($item['body'], $vars) : $item['body'];
        $html   = zcn_build_newsletter_email($s_subj, $s_body, $sub->token, $sub->name);
        if ($cid && function_exists('zcn_apply_tracking')) $html = zcn_apply_tracking($html, $cid, $sub->email);
        $hdr = array_merge($headers, ['List-Unsubscribe: <' . esc_url_raw(zcn_unsubscribe_url($sub->token)) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click']);
        wp_mail($sub->email, $s_subj, $html, $hdr) ? $sent++ : $failed++;
        usleep(150000);
    }
    if ($cid && function_exists('zcn_campaign_set_sent')) zcn_campaign_set_sent($cid, $sent);
    $log = get_option('zcn_send_log', []);
    array_unshift($log, ['date' => current_time('mysql'), 'subject' => '[naplánované] ' . $item['subject'], 'sent' => $sent, 'failed' => $failed]);
    update_option('zcn_send_log', array_slice($log, 0, 30));
}, 10, 1);

function zcn_build_newsletter_email($subject, $body_html, $token, $name = '', $args = []) {
    $unsub = zcn_unsubscribe_url($token);
    // Oslovenie sem nedopĺňame – píše ho maklérka v texte kampane.
    // Ak ho chce mať s menom, použije v texte premennú {meno}.
    $footer = 'Dostávate tento e-mail pretože ste prihlásení na odber noviniek. · <a href="' . esc_url($unsub) . '" style="color:#9A8660">Odhlásiť sa</a><br>';

    return zcn_email_wrap($subject, $body_html, $footer, $args);
}
