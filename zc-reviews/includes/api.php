<?php
defined('ABSPATH') || exit;
// AJAX handlers for panel
add_action('wp_ajax_zcr_save',   'zcr_ajax_save');
add_action('wp_ajax_zcr_delete', 'zcr_ajax_delete');
add_action('wp_ajax_zcr_sort',   'zcr_ajax_sort');

function zcr_ajax_save() {
    check_ajax_referer('zcr_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb; $t = zcr_table();

    $id   = intval($_POST['id'] ?? 0);
    $data = [
        // wp_unslash() – bez neho by sa do recenzie uložilo \" namiesto "
        'author_name' => sanitize_text_field(wp_unslash($_POST['author_name'] ?? '')),
        'author_role' => sanitize_text_field(wp_unslash($_POST['author_role'] ?? '')),
        'body'        => sanitize_textarea_field(wp_unslash($_POST['body'] ?? '')),
        'rating'      => max(1, min(5, intval($_POST['rating'] ?? 5))),
        'avatar_url'  => esc_url_raw($_POST['avatar_url'] ?? ''),
        'published'   => intval($_POST['published'] ?? 1),
    ];

    if (!$data['author_name'] || !$data['body']) {
        wp_send_json_error(['message' => 'Meno a text sú povinné.']);
    }

    if ($id) {
        zcr_stage_snapshot($id);
        $wpdb->update($t, $data, ['id' => $id]);
        zcr_write_log('Upravená recenzia', $data['author_name'], $id);
        wp_send_json_success(['id' => $id, 'message' => 'Recenzia uložená.']);
    } else {
        $data['sort_order'] = (int)$wpdb->get_var("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$t}");
        $wpdb->insert($t, $data);
        zcr_write_log('Pridaná recenzia', $data['author_name'], $wpdb->insert_id);
        wp_send_json_success(['id' => $wpdb->insert_id, 'message' => 'Recenzia pridaná.']);
    }
}

function zcr_ajax_delete() {
    check_ajax_referer('zcr_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb;
    $id   = intval($_POST['id'] ?? 0);
    $name = (string) $wpdb->get_var($wpdb->prepare('SELECT author_name FROM ' . zcr_table() . ' WHERE id = %d', $id));
    zcr_stage_snapshot($id);
    $wpdb->delete(zcr_table(), ['id' => $id]);
    zcr_write_log('Zmazaná recenzia', $name, $id);
    wp_send_json_success();
}

/** Odloží podobu recenzie pred zmenou – aby sa dala vrátiť z denníka. */
function zcr_stage_snapshot($id) {
    global $wpdb;
    if (!function_exists('zc_audit_stage') || !$id) return;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . zcr_table() . ' WHERE id = %d', (int) $id), ARRAY_A);
    if ($row) zc_audit_stage('review', $row);
}

/** Zápis do bezpečnostného denníka (ak je dostupný). */
function zcr_write_log($what, $who = '', $id = 0) {
    if (function_exists('zc_audit_log')) zc_audit_log('review', $who, $what, (int) $id);
}

function zcr_ajax_sort() {
    check_ajax_referer('zcr_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb; $t = zcr_table();
    $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    foreach ($ids as $i => $id) {
        $wpdb->update($t, ['sort_order' => $i], ['id' => $id]);
    }
    wp_send_json_success();
}
