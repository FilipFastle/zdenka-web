<?php
/**
 * Prihlásenie s 2FA + časovanie relácií (odhlásenie pri nečinnosti).
 */
defined('ABSPATH') || exit;

/* ───────────────────────── 2FA pri prihlásení ───────────────────────── */

// Beží až po overení hesla (priorita 30 = za wp_authenticate_username_password).
add_filter('authenticate', 'zc2fa_authenticate', 30, 3);
function zc2fa_authenticate($user, $username, $password) {
    // NÚDZOVÉ VYPNUTIE: do wp-config.php stačí pridať
    // define('ZC2FA_DISABLE', true);  → 2FA sa dočasne preskočí
    if (defined('ZC2FA_DISABLE') && ZC2FA_DISABLE) return $user;
    if (!($user instanceof WP_User)) return $user;   // zlé heslo → nechaj tak
    if (empty($password)) return $user;              // iné spôsoby prihlásenia
    // Aplikačné heslá / REST / XML-RPC 2FA nepoužívajú
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return $user;
    if (defined('REST_REQUEST') && REST_REQUEST) return $user;
    if (!zc2fa_is_enabled($user->ID)) return $user;

    if (zc2fa_is_locked($user->ID)) {
        return new WP_Error('zc2fa_locked',
            '<strong>Príliš veľa pokusov.</strong> Skúste to znova o 15 minút.');
    }

    $code = isset($_POST['zc2fa_code']) ? trim((string) $_POST['zc2fa_code']) : '';
    if ($code === '') {
        return new WP_Error('zc2fa_required',
            '<strong>Zadajte overovací kód</strong> z aplikácie (Google Authenticator).');
    }
    if (!zc2fa_check_user_code($user->ID, $code)) {
        zc2fa_note_failure($user->ID);
        return new WP_Error('zc2fa_invalid',
            '<strong>Neplatný overovací kód.</strong> Skúste to znova (kód platí 30 sekúnd).');
    }
    zc2fa_clear_failures($user->ID);
    return $user;
}

// Pole na kód v prihlasovacom formulári
add_action('login_form', function () {
    ?>
    <p class="zc2fa-field">
        <label for="zc2fa_code">Overovací kód <span style="font-weight:400;color:#666">(ak máte zapnuté 2FA)</span></label>
        <input type="text" name="zc2fa_code" id="zc2fa_code" class="input" value="" size="20"
               inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9A-Za-z]*" placeholder="6-ciferný kód">
    </p>
    <?php
});

// Informácia po automatickom odhlásení pre nečinnosť
add_filter('login_message', function ($message) {
    if (!empty($_GET['zc2fa_timeout'])) {
        $message .= '<p class="message">Boli ste odhlásení z bezpečnostných dôvodov (nečinnosť). Prihláste sa znova.</p>';
    }
    return $message;
});

/* ─────────────── Dĺžka prihlásenia + odhlásenie pri nečinnosti ─────────────── */

// Absolútna platnosť prihlasovacej cookie podľa toho, či má používateľ 2FA
add_filter('auth_cookie_expiration', function ($length, $user_id, $remember) {
    $mins = zc2fa_is_enabled($user_id)
        ? (int) zc2fa_opt('idle_2fa')
        : (int) zc2fa_opt('idle_no2fa');
    $mins = max(5, $mins);
    // Malá rezerva, aby cookie nevypršala skôr než náš limit nečinnosti
    return ($mins * MINUTE_IN_SECONDS) + (5 * MINUTE_IN_SECONDS);
}, 10, 3);

/**
 * Odhlásenie po nečinnosti. WordPress má len absolútnu platnosť cookie,
 * preto si čas poslednej aktivity strážime sami.
 */
add_action('init', function () {
    if (!is_user_logged_in()) return;
    // REST a cron nechávame na pokoji (nesmieme ich presmerovať ani odhlásiť)
    if (defined('REST_REQUEST') && REST_REQUEST) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return;

    // Heartbeat beží na pozadí sám – nesmie sa počítať ako aktivita
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
    if ($action === 'heartbeat') return;
    // Prihlasovacie/odhlasovacie obrazovky nechávame na pokoji
    if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') return;

    $user_id = get_current_user_id();
    $limit   = (zc2fa_is_enabled($user_id)
        ? (int) zc2fa_opt('idle_2fa')
        : (int) zc2fa_opt('idle_no2fa')) * MINUTE_IN_SECONDS;
    $limit = max(5 * MINUTE_IN_SECONDS, $limit);

    $last = (int) get_user_meta($user_id, ZC2FA_META_SEEN, true);
    $now  = time();

    if ($last && ($now - $last) > $limit) {
        delete_user_meta($user_id, ZC2FA_META_SEEN);
        wp_logout();
        $redirect = add_query_arg('zc2fa_timeout', '1', wp_login_url());
        // Pri AJAX len vrátime chybu, inak presmerujeme na prihlásenie
        if (wp_doing_ajax()) {
            wp_send_json_error(['message' => 'Relácia vypršala, prihláste sa znova.'], 401);
        }
        wp_safe_redirect($redirect);
        exit;
    }

    // Zapisujeme najviac raz za minútu (šetríme databázu)
    if (!$last || ($now - $last) > MINUTE_IN_SECONDS) {
        update_user_meta($user_id, ZC2FA_META_SEEN, $now);
    }
}, 1);

// Po prihlásení začíname počítať nečinnosť odznova
add_action('wp_login', function ($login, $user) {
    if ($user instanceof WP_User) {
        update_user_meta($user->ID, ZC2FA_META_SEEN, time());
    }
}, 10, 2);

/* ─────────────── Vynútenie 2FA pre správcov (voliteľné) ─────────────── */

add_action('admin_init', function () {
    if (!is_user_logged_in()) return;
    $uid = get_current_user_id();
    if (!zc2fa_is_required($uid) || zc2fa_is_enabled($uid)) return;

    $screen = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $pagenow = $GLOBALS['pagenow'] ?? '';
    // Povolíme profil a stránku 2FA, aby si ho vedel zapnúť
    $allowed = in_array($pagenow, ['profile.php', 'user-edit.php', 'admin-ajax.php', 'admin-post.php'], true)
        || $screen === 'zc-2fa';
    if ($allowed) return;

    wp_safe_redirect(admin_url('options-general.php?page=zc-2fa&zc2fa_required=1'));
    exit;
});

add_action('admin_notices', function () {
    $uid = get_current_user_id();
    if (!$uid || zc2fa_is_enabled($uid)) return;
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-warning"><p><strong>Dvojfaktorové overenie nie je zapnuté.</strong> '
        . 'Chráni web aj v prípade, že vám niekto ukradne heslo. '
        . '<a href="' . esc_url(admin_url('options-general.php?page=zc-2fa')) . '">Zapnúť teraz →</a></p></div>';
});
