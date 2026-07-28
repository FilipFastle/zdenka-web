<?php
/**
 * Poradie zobrazovania recenzií – ručné presúvanie (šípky) a spôsob radenia.
 * Ovládanie funguje rovnako vo wp-admine aj v realitnom paneli.
 */
defined('ABSPATH') || exit;

// Spôsob radenia: manual | newest | rating | random
function zcr_display_order() {
    $v = get_option('zcr_display_order', 'manual');
    return in_array($v, ['manual', 'newest', 'rating', 'random'], true) ? $v : 'manual';
}
// Kam zaradiť recenzie z Google: after | before | mixed
function zcr_google_position() {
    $v = get_option('zcr_google_position', 'after');
    return in_array($v, ['after', 'before', 'mixed'], true) ? $v : 'after';
}

/** SQL časť ORDER BY podľa zvoleného spôsobu radenia. */
function zcr_order_sql() {
    switch (zcr_display_order()) {
        case 'newest': return 'id DESC';
        case 'rating': return 'rating DESC, sort_order ASC, id ASC';
        case 'random': return 'RAND()';
        default:       return 'sort_order ASC, id ASC';
    }
}

/**
 * Posunie recenziu o jednu pozíciu hore/dole (vymení poradie so susedom).
 */
function zcr_move_review($id, $dir) {
    global $wpdb;
    $t = zcr_table();
    $rows = $wpdb->get_results("SELECT id, sort_order FROM {$t} ORDER BY sort_order ASC, id ASC");
    if (!$rows) return false;

    // Zjednotíme poradie (0,1,2…), aby výmena vždy fungovala aj pri samých nulách
    foreach ($rows as $i => $r) {
        if ((int) $r->sort_order !== $i) {
            $wpdb->update($t, ['sort_order' => $i], ['id' => (int) $r->id]);
            $r->sort_order = $i;
        }
    }

    $pos = null;
    foreach ($rows as $i => $r) {
        if ((int) $r->id === (int) $id) { $pos = $i; break; }
    }
    if ($pos === null) return false;

    $swap = ($dir === 'up') ? $pos - 1 : $pos + 1;
    if ($swap < 0 || $swap >= count($rows)) return false; // už je na kraji

    $a = $rows[$pos];
    $b = $rows[$swap];
    $wpdb->update($t, ['sort_order' => (int) $b->sort_order], ['id' => (int) $a->id]);
    $wpdb->update($t, ['sort_order' => (int) $a->sort_order], ['id' => (int) $b->id]);
    return true;
}

/**
 * Presun cez admin-post.php – obyčajný odkaz, žiadny JavaScript.
 * Šípky tak fungujú aj vtedy, keď na stránke zlyhá iný skript.
 */
function zcr_move_endpoint() {
    $id  = intval($_GET['id'] ?? 0);
    $dir = ($_GET['dir'] ?? '') === 'up' ? 'up' : 'down';
    if (!current_user_can('edit_posts')) wp_die('Bez oprávnenia.');
    if (!$id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'zcr_move_' . $id)) wp_die('Neplatná požiadavka.');

    zcr_move_review($id, $dir);

    $back = wp_get_referer();
    wp_safe_redirect($back ?: admin_url('admin.php?page=zc-reviews'));
    exit;
}
add_action('admin_post_zcr_move', 'zcr_move_endpoint');

/**
 * Šípky ↑ ↓ pre jeden riadok. Štýly sa vypíšu len raz.
 */
function zcr_order_controls($id, $is_first = false, $is_last = false) {
    static $printed = false;
    $id = (int) $id;
    ob_start();
    if (!$printed) {
        $printed = true;
        ?>
        <style>
        .zcr-ord{display:inline-flex;gap:4px}
        .zcr-ord a,.zcr-ord span{width:26px;height:26px;line-height:24px;text-align:center;
            display:inline-block;text-decoration:none;
            border:1px solid #d5cec2;background:#fff;border-radius:6px;color:#7C5E33;font-size:13px}
        .zcr-ord a:hover{background:#B8A47A;color:#1C1A18;border-color:#B8A47A}
        .zcr-ord span{opacity:.35}
        </style>
        <?php
    }
    $link = function ($dir) use ($id) {
        return esc_url(wp_nonce_url(
            admin_url('admin-post.php?action=zcr_move&id=' . $id . '&dir=' . $dir),
            'zcr_move_' . $id
        ));
    };
    ?>
    <span class="zcr-ord">
        <?php if ($is_first): ?>
        <span title="Už je najvyššie">&#9650;</span>
        <?php else: ?>
        <a href="<?php echo $link('up'); ?>" title="Posunúť vyššie">&#9650;</a>
        <?php endif; ?>
        <?php if ($is_last): ?>
        <span title="Už je najnižšie">&#9660;</span>
        <?php else: ?>
        <a href="<?php echo $link('down'); ?>" title="Posunúť nižšie">&#9660;</a>
        <?php endif; ?>
    </span>
    <?php
    return ob_get_clean();
}

/**
 * Nastavenia zobrazovania – použiteľné vo wp-admine aj v paneli.
 */
function zcr_disp_save_endpoint() {
    if (!current_user_can('edit_posts')) wp_die('Bez oprávnenia.');
    check_admin_referer('zcr_disp', 'zcr_disp_nonce');
    $o = sanitize_key($_POST['display_order'] ?? 'manual');
    update_option('zcr_display_order', in_array($o, ['manual','newest','rating','random'], true) ? $o : 'manual');
    $p = sanitize_key($_POST['google_position'] ?? 'after');
    update_option('zcr_google_position', in_array($p, ['after','before','mixed'], true) ? $p : 'after');

    $back = wp_get_referer() ?: admin_url('admin.php?page=zc-reviews');
    wp_safe_redirect(add_query_arg('zcr_disp_ok', '1', remove_query_arg('zcr_disp_ok', $back)));
    exit;
}
add_action('admin_post_zcr_disp_save', 'zcr_disp_save_endpoint');

function zcr_display_settings_box() {
    $saved = !empty($_GET['zcr_disp_ok']);
    ob_start(); ?>
    <div class="zcr-disp">
        <?php if ($saved): ?><div class="zcr-disp-ok">Nastavenie uložené.</div><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="zcr-disp-form">
            <?php wp_nonce_field('zcr_disp', 'zcr_disp_nonce'); ?>
            <input type="hidden" name="action" value="zcr_disp_save">
            <label>Poradie recenzií
                <select name="display_order">
                    <option value="manual" <?php selected(zcr_display_order(), 'manual'); ?>>Vlastné (šípkami nižšie)</option>
                    <option value="newest" <?php selected(zcr_display_order(), 'newest'); ?>>Najnovšie prvé</option>
                    <option value="rating" <?php selected(zcr_display_order(), 'rating'); ?>>Najlepšie hodnotené prvé</option>
                    <option value="random" <?php selected(zcr_display_order(), 'random'); ?>>Náhodne pri každom načítaní</option>
                </select>
            </label>
            <label>Recenzie z Google
                <select name="google_position">
                    <option value="after"  <?php selected(zcr_google_position(), 'after');  ?>>Zaradiť za vlastné</option>
                    <option value="before" <?php selected(zcr_google_position(), 'before'); ?>>Zaradiť pred vlastné</option>
                    <option value="mixed"  <?php selected(zcr_google_position(), 'mixed');  ?>>Premiešať podľa hodnotenia</option>
                </select>
            </label>
            <button type="submit" name="zcr_disp_save" value="1" class="zcr-disp-btn">Uložiť</button>
        </form>
        <p class="zcr-disp-hint">Pri vlastnom poradí presúvaj recenzie šípkami v zozname nižšie. Ostatné voľby poradie určujú automaticky.</p>
    </div>
    <style>
    /* Vyzerá rovnako vo wp-admine aj v realitnom paneli */
    .zcr-disp{background:var(--white,#fff);border:1px solid var(--border,#E2DACE);
        border-radius:var(--r,12px);padding:16px 18px;margin:16px 0}
    .zcr-disp-form{display:flex;gap:18px;align-items:flex-end;flex-wrap:wrap}
    .zcr-disp-form label{display:flex;flex-direction:column;gap:5px;font-size:13px;font-weight:600;color:var(--dark,#1C1A18)}
    .zcr-disp-form select{min-width:220px;padding:9px 11px;border:1.5px solid var(--border,#E2DACE);
        border-radius:8px;background:#fff;font-family:inherit;font-size:13.5px;color:var(--text,#2C2C2C)}
    .zcr-disp-btn{padding:11px 24px;border:none;border-radius:8px;background:var(--accent,#B8A47A);
        color:var(--dark,#1C1A18);font-family:inherit;font-weight:700;font-size:13px;cursor:pointer;line-height:1.2}
    .zcr-disp-btn:hover{background:var(--accent-dk,#9A8660)}
    .zcr-disp-hint{font-size:12.5px;color:var(--muted,#6b6560);margin:10px 0 0}
    .zcr-disp-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:9px 13px;border-radius:8px;margin-bottom:12px;font-size:13px;font-weight:600}
    </style>
    <?php
    return ob_get_clean();
}
