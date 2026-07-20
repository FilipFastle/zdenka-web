<?php
defined('ABSPATH') || exit;
// ── Spam Protection — Honeypot + Time Check ───────────────────────────────

// 1. Output honeypot fields (call inside every form)
function zc_honeypot_fields() {
    $token = wp_create_nonce('zc_form_' . floor(time()/300)); // changes every 5 min
    ob_start(); ?>
    <!-- Honeypot: bots fill these, humans don't see them -->
    <div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
        <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
        <input type="text" name="phone_confirm" tabindex="-1" autocomplete="off" value="">
    </div>
    <input type="hidden" name="zc_form_token" value="<?php echo esc_attr($token) ?>">
    <input type="hidden" name="zc_form_time"  value="<?php echo time() ?>">
    <?php
    return ob_get_clean();
}

// 2. Verify — returns true if valid, string if spam
function zc_check_spam($post = null) {
    if ($post === null) $post = $_POST;

    // Honeypot check — if filled = bot
    if (!empty($post['website']) || !empty($post['phone_confirm'])) {
        return 'spam_honeypot';
    }

    // Time check — submitted too fast (< 3 seconds) = bot
    $submit_time = intval($post['zc_form_time'] ?? 0);
    if ($submit_time && (time() - $submit_time) < 3) {
        return 'spam_too_fast';
    }

    // Token check
    $token = sanitize_text_field($post['zc_form_token'] ?? '');
    $valid1 = wp_verify_nonce($token, 'zc_form_' . floor(time()/300));
    $valid2 = wp_verify_nonce($token, 'zc_form_' . floor((time()-300)/300)); // allow previous window
    if (!$valid1 && !$valid2) {
        return 'spam_invalid_token';
    }

    return true;
}

// 3. WordPress comments — honeypot
add_action('comment_form', function() {
    echo zc_honeypot_fields();
});
add_filter('preprocess_comment', function($data) {
    $check = zc_check_spam();
    if ($check !== true) {
        wp_die('Vaša správa bola označená ako spam. Ak si myslíte, že ide o chybu, kontaktujte nás priamo.');
    }
    return $data;
});

// 4. Akismet integration hint (if active)
add_action('wp_head', function() {
    if (function_exists('akismet_get_key')) return; // already active
    // Silently suggest Akismet via comment — no output
}, 99);
