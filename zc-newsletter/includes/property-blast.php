<?php
defined('ABSPATH') || exit;
// ── Blast novej ponuky odberateľom – jeden klik z Realitného panela ──────

// Nastavenia šablóny e-mailu novej ponuky (editovateľné v paneli)
function zcn_blast_settings() {
    $d = [
        'subject_prefix' => 'Nová ponuka: ',
        'intro'          => "Dobrý deň,\n\npridali sme do ponuky novú nehnuteľnosť, ktorá by vás mohla zaujať:",
        'outro'          => 'Ak vás ponuka zaujala, kliknite na tlačidlo vyššie alebo mi napíšte — rada vám poskytnem viac informácií aj osobnú obhliadku.',
        'show_contact'   => 1,
    ];
    $s = get_option('zcn_blast_tpl', []);
    return array_merge($d, is_array($s) ? $s : []);
}

// Kontaktný blok makléra (z Customizeru / profilu)
function zcn_blast_contact_html() {
    // Rovnaký zdroj ako vizitka: profil používateľa → Prispôsobiť
    $d = function_exists('pp_agent_data') ? pp_agent_data(0) : [];
    $pick = function ($key, $mod, $def = '') use ($d) {
        $v = trim((string) ($d[$key] ?? ''));
        if ($v !== '') return $v;
        return function_exists('zc_agent') ? (string) zc_agent($mod, $def) : $def;
    };
    $name  = $pick('name',  'name',  'Mgr. Zdenka Cibuľová');
    $title = $pick('title', 'title', 'Realitná maklérka');
    $phone = $pick('phone', 'phone');
    $email = $pick('email', 'email');
    if (!$email) $email = get_option('admin_email');

    $h  = '<div style="margin:28px 0 4px;padding:20px 22px;background:#F7F3EC;border-radius:12px">';
    $h .= '<div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#9A8660;margin-bottom:8px">Kontakt</div>';
    $h .= '<div style="font-family:Georgia,serif;font-size:17px;color:#1C1A18;font-weight:700">' . esc_html($name) . '</div>';
    if ($title) $h .= '<div style="font-size:13px;color:#7A7068;margin-bottom:8px">' . esc_html($title) . '</div>';
    $rows = [];
    if ($phone) $rows[] = '<a href="tel:' . esc_attr(preg_replace('/[^0-9+]/','',$phone)) . '" style="color:#7C5E33;text-decoration:none">' . esc_html($phone) . '</a>';
    if ($email) $rows[] = '<a href="mailto:' . esc_attr($email) . '" style="color:#7C5E33;text-decoration:none">' . esc_html($email) . '</a>';
    if ($rows) $h .= '<div style="font-size:14px;color:#2C2825">' . implode(' &nbsp;·&nbsp; ', $rows) . '</div>';
    $h .= '</div>';
    return $h;
}

// Prevod textu (s riadkami) na jednoduché HTML odseky, so zachovaním premenných {meno}
function zcn_text_to_html($text) {
    $text = trim((string) $text);
    if ($text === '') return '';
    $blocks = preg_split('/\n\s*\n/', $text);
    $out = '';
    foreach ($blocks as $b) {
        $b = nl2br(esc_html(trim($b)));
        // premenné {meno}/{email} nechať funkčné (esc_html ich nemení)
        $out .= '<p style="color:#2C2825;line-height:1.75;font-size:15px;margin:0 0 16px">' . $b . '</p>';
    }
    return $out;
}

function zcn_property_email_parts($pid) {
    $cfg = zcn_blast_settings();
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
    // 1) Úvodný text (editovateľný)
    $body .= zcn_text_to_html($cfg['intro']);
    // 2) Karta nehnuteľnosti
    if ($img) $body .= '<a href="' . esc_url($url) . '"><img src="' . esc_url($img) . '" alt="' . esc_attr($title) . '" style="width:100%;border-radius:12px;display:block;margin:0 0 20px"></a>';
    if ($typ && isset($typ_labels[$typ])) $body .= '<p style="margin:0 0 6px"><span style="display:inline-block;background:#F5EEDF;color:#7C5E33;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:4px 12px;border-radius:50px">' . $typ_labels[$typ] . '</span></p>';
    $body .= '<h2 style="font-family:Georgia,serif;font-size:22px;color:#1C1A18;margin:0 0 8px">' . esc_html($title) . '</h2>';
    if ($meta)  $body .= '<p style="color:#7A7068;font-size:14px;margin:0 0 14px">' . implode(' &nbsp;·&nbsp; ', $meta) . '</p>';
    if ($cena)  $body .= '<p style="font-family:Georgia,serif;font-size:24px;font-weight:700;color:#7C5E33;margin:0 0 16px">' . esc_html($cena) . '</p>';
    if ($popis) {
        $body .= '<div style="color:#555;line-height:1.75;margin:0 0 24px">'
              . wp_kses_post(wpautop($popis))
              . '</div>';
    }
    $body .= '<div style="text-align:center;margin:28px 0 8px"><a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 32px;background:#B8A47A;color:#1C1A18;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;font-family:\'DM Sans\',Arial,sans-serif">Pozrieť ponuku →</a></div>';
    // 3) Záverečný text (editovateľný)
    $outro = zcn_text_to_html($cfg['outro']);
    if ($outro) $body .= '<div style="margin-top:20px">' . $outro . '</div>';
    // 4) Kontakt na makléra
    if (!empty($cfg['show_contact'])) $body .= zcn_blast_contact_html();

    $prefix = $cfg['subject_prefix'] !== '' ? $cfg['subject_prefix'] : 'Nová ponuka: ';
    return ['subject' => $prefix . $title, 'body' => $body];
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

    $parts = zcn_property_email_parts($pid);
    // Šablóna ponuky má vlastný kontaktný blok. Keby sme nechali aj vizitku
    // v pätičke, kontakt by bol v e-maile dvakrát pod sebou.
    $cfg       = zcn_blast_settings();
    $wrap_args = ['signature' => empty($cfg['show_contact'])];
    $from_name  = function_exists('zc_agent') ? zc_agent('name', 'Mgr. Zdenka Cibuľová') : 'Mgr. Zdenka Cibuľová';
    $from_email = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $headers    = ['Content-Type: text/html; charset=UTF-8', "From: {$from_name} <{$from_email}>"];

    // Náhľad – vráti hotové HTML bez odoslania
    if (!empty($_POST['preview'])) {
        $body = zcn_apply_vars($parts['body'], ['meno' => 'Jana', 'email' => 'jana@email.sk']);
        $html = zcn_build_newsletter_email($parts['subject'], $body, zcn_generate_token(), 'Jana', $wrap_args);
        wp_send_json_success(['html' => $html]);
    }

    // Testovací e-mail – pošle iba na zadanú adresu
    $test = !empty($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    if ($test) {
        $html = zcn_build_newsletter_email($parts['subject'], $parts['body'], zcn_generate_token(), '', $wrap_args);
        $ok   = wp_mail($test, '[TEST] ' . $parts['subject'], $html, $headers);
        wp_send_json_success(['message' => $ok ? "Test odoslaný na {$test}" : 'Odoslanie zlyhalo.']);
    }

    global $wpdb;
    // Príjemcov si maklérka vyberie v okne pri tlačidle: skupiny, konkrétni
    // ľudia, alebo všetci. Kontakty bez záujmu o ponuky sa vynechávajú vždy.
    $mode   = sanitize_key($_POST['mode'] ?? 'all');
    $where  = "status='active'" . zcn_offers_sql_where();
    $note   = '';

    if ($mode === 'groups') {
        $groups = zcn_interest_list($_POST['groups'] ?? '');
        if (!$groups) wp_send_json_error(['message' => 'Nevybral si žiadnu skupinu.']);
        if (zcn_is_all_interests($groups)) {
            // Označené všetky = neobmedzujeme, ostáva len vynechanie tých,
            // ktorí ponuky nechcú (to je už v $where).
            $note = ' (všetky kategórie)';
        } else {
            $or = ["interest IS NULL", "interest = ''"]; // „všetko" dostáva vždy
            foreach ($groups as $g) $or[] = "FIND_IN_SET('" . esc_sql($g) . "', interest)";
            $where = "status='active' AND (" . implode(' OR ', $or) . ')';
            $note  = ' (skupiny: ' . zcn_interest_label(implode(',', $groups)) . ')';
        }
    } elseif ($mode === 'people') {
        $emails = [];
        foreach (explode(',', (string) ($_POST['emails'] ?? '')) as $e) {
            $e = strtolower(sanitize_email(trim($e)));
            if (is_email($e)) $emails[] = $e;
        }
        $emails = array_slice(array_unique($emails), 0, 500);
        if (!$emails) wp_send_json_error(['message' => 'Nevybral si žiadneho príjemcu.']);
        $in    = "'" . implode("','", array_map('esc_sql', $emails)) . "'";
        $where = "status='active' AND email IN ({$in})";
        $note  = ' (vybraní príjemcovia)';
    }

    $subscribers = $wpdb->get_results("SELECT * FROM " . zcn_table() . " WHERE " . $where);
    if (empty($subscribers)) wp_send_json_error(['message' => 'Pre tento výber sa nenašiel žiadny odberateľ.']);

    $sent = 0; $failed = 0;
    foreach ($subscribers as $sub) {
        $vars = ['meno' => $sub->name ?: '', 'email' => $sub->email];
        $subj = zcn_apply_vars($parts['subject'], $vars);
        $body = zcn_apply_vars($parts['body'], $vars);
        $html = zcn_build_newsletter_email($subj, $body, $sub->token, $sub->name, $wrap_args);
        $hdr = array_merge($headers, ['List-Unsubscribe: <' . esc_url_raw(zcn_unsubscribe_url($sub->token)) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click']);
        wp_mail($sub->email, $subj, $html, $hdr) ? $sent++ : $failed++;
        usleep(150000);
    }

    $log = get_option('zcn_send_log', []);
    array_unshift($log, ['date' => current_time('mysql'), 'subject' => $parts['subject'], 'sent' => $sent, 'failed' => $failed]);
    update_option('zcn_send_log', array_slice($log, 0, 30));
    update_post_meta($pid, '_zcn_blast_sent', current_time('mysql'));

    wp_send_json_success(['message' => "Ponuka odoslaná {$sent} odberateľom{$note}" . ($failed ? " | Zlyhalo: {$failed}" : '')]);
}
