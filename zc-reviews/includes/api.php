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
        'author_name' => sanitize_text_field($_POST['author_name'] ?? ''),
        'author_role' => sanitize_text_field($_POST['author_role'] ?? ''),
        'body'        => sanitize_textarea_field($_POST['body'] ?? ''),
        'rating'      => max(1, min(5, intval($_POST['rating'] ?? 5))),
        'avatar_url'  => esc_url_raw($_POST['avatar_url'] ?? ''),
        'published'   => intval($_POST['published'] ?? 1),
    ];

    if (!$data['author_name'] || !$data['body']) {
        wp_send_json_error(['message' => 'Meno a text sú povinné.']);
    }

    if ($id) {
        $wpdb->update($t, $data, ['id' => $id]);
        wp_send_json_success(['id' => $id, 'message' => 'Recenzia uložená.']);
    } else {
        $data['sort_order'] = (int)$wpdb->get_var("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$t}");
        $wpdb->insert($t, $data);
        wp_send_json_success(['id' => $wpdb->insert_id, 'message' => 'Recenzia pridaná.']);
    }
}

function zcr_ajax_delete() {
    check_ajax_referer('zcr_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error();
    global $wpdb;
    $wpdb->delete(zcr_table(), ['id' => intval($_POST['id'] ?? 0)]);
    wp_send_json_success();
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
