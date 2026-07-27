<?php
/**
 * Komu chodia notifikácie z formulárov.
 * Predtým išlo všetko na admin_email – teda každému účtu s touto adresou.
 * Tu sa dá presne vybrať, ktoré roly, ktorí používatelia a ktoré e-maily
 * majú notifikácie dostávať.
 */
defined('ABSPATH') || exit;

/**
 * Vráti pole e-mailov, ktorým sa posielajú notifikácie z formulárov.
 * Ak nie je nastavené nič, použije sa e-mail z Customizera, inak admin e-mail.
 */
function zc_notify_recipients() {
    $emails = [];

    // 1) Konkrétne e-mailové adresy (jedna na riadok alebo oddelené čiarkou)
    $raw = (string) get_option('zc_notify_emails', '');
    foreach (preg_split('/[\s,;]+/', $raw) as $e) {
        $e = trim($e);
        if ($e && is_email($e)) $emails[] = $e;
    }

    // 2) Vybrané roly
    $roles = (array) get_option('zc_notify_roles', []);
    if ($roles) {
        foreach (get_users(['role__in' => $roles, 'number' => 100]) as $u) {
            if (is_email($u->user_email)) $emails[] = $u->user_email;
        }
    }

    // 3) Vybraní používatelia
    $users = array_map('intval', (array) get_option('zc_notify_users', []));
    foreach ($users as $uid) {
        $u = get_userdata($uid);
        if ($u && is_email($u->user_email)) $emails[] = $u->user_email;
    }

    $emails = array_values(array_unique(array_filter($emails)));

    // Záloha – keď nie je nastavené nič, nech sa notifikácia nestratí
    if (!$emails) {
        $fallback = get_theme_mod('zc_email_main', '') ?: get_option('admin_email');
        if ($fallback && is_email($fallback)) $emails[] = $fallback;
    }
    return $emails;
}

/** Adresáti pre konkrétny typ formulára (odhad má vlastnú adresu v Customizeri). */
function zc_notify_to($type = 'contact') {
    if ($type === 'odhad') {
        $special = get_theme_mod('zc_email_odhad', '');
        if ($special && is_email($special)) return [$special];
    }
    return zc_notify_recipients();
}

/* ───────────────────── Nastavenie (zdieľané admin + panel) ───────────────────── */

function zc_notify_settings_box() {
    if (!current_user_can('manage_options')) {
        return '<p style="padding:20px;color:#b91c1c">Nastavenia notifikácií môže meniť len správca.</p>';
    }
    $saved = false;
    if (isset($_POST['zc_notify_save']) && isset($_POST['zc_notify_nonce'])
        && wp_verify_nonce($_POST['zc_notify_nonce'], 'zc_notify')) {
        update_option('zc_notify_roles',  array_map('sanitize_key', (array) ($_POST['roles'] ?? [])));
        update_option('zc_notify_users',  array_map('intval', (array) ($_POST['users'] ?? [])));
        update_option('zc_notify_emails', sanitize_textarea_field($_POST['emails'] ?? ''));
        $saved = true;
    }

    $sel_roles  = (array) get_option('zc_notify_roles', []);
    $sel_users  = array_map('intval', (array) get_option('zc_notify_users', []));
    $emails     = (string) get_option('zc_notify_emails', '');
    $all_roles  = function_exists('get_editable_roles') ? get_editable_roles() : wp_roles()->roles;
    $staff      = get_users(['number' => 100, 'orderby' => 'display_name']);
    $current    = zc_notify_recipients();

    ob_start(); ?>
    <div class="zcn-box">
        <?php if ($saved): ?><div class="zcn-ok">Nastavenie uložené.</div><?php endif; ?>

        <div class="zcn-current">
            <strong>Notifikácie teraz chodia na:</strong>
            <?php if ($current): ?>
                <?php foreach ($current as $e): ?><code><?php echo esc_html($e); ?></code><?php endforeach; ?>
            <?php else: ?><em>nikam</em><?php endif; ?>
        </div>

        <form method="post">
            <?php wp_nonce_field('zc_notify', 'zc_notify_nonce'); ?>

            <div class="zcn-card">
                <div class="zcn-t">E-mailové adresy</div>
                <p class="zcn-hint">Jedna adresa na riadok. Toto je najspoľahlivejšie – nezávisí od účtov vo WordPresse.</p>
                <textarea name="emails" rows="3" placeholder="zdenka@zdenkacibulova.sk"><?php echo esc_textarea($emails); ?></textarea>
            </div>

            <div class="zcn-card">
                <div class="zcn-t">Roly</div>
                <p class="zcn-hint">Notifikácie dostanú všetci používatelia s označenou rolou.</p>
                <div class="zcn-grid">
                    <?php foreach ($all_roles as $rk => $rv): ?>
                    <label class="zcn-chk<?php echo in_array($rk, $sel_roles, true) ? ' on' : '' ?>">
                        <input type="checkbox" name="roles[]" value="<?php echo esc_attr($rk); ?>" <?php checked(in_array($rk, $sel_roles, true)); ?>>
                        <span><?php echo esc_html(translate_user_role($rv['name'])); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="zcn-card">
                <div class="zcn-t">Konkrétni používatelia</div>
                <div class="zcn-grid">
                    <?php foreach ($staff as $u): ?>
                    <label class="zcn-chk<?php echo in_array($u->ID, $sel_users, true) ? ' on' : '' ?>">
                        <input type="checkbox" name="users[]" value="<?php echo (int) $u->ID; ?>" <?php checked(in_array($u->ID, $sel_users, true)); ?>>
                        <span><?php echo esc_html($u->display_name); ?> <small><?php echo esc_html($u->user_email); ?></small></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="zc_notify_save" value="1" class="button button-primary zcn-btn">Uložiť nastavenie</button>
            <p class="zcn-hint" style="margin-top:10px">Keď nevyberieš nič, notifikácie idú na e-mail z Customizera, prípadne na administrátorský e-mail webu.</p>
        </form>
    </div>
    <style>
    .zcn-box{max-width:820px;font-family:-apple-system,'Segoe UI',Roboto,sans-serif;color:#2C2825}
    .zcn-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:11px 15px;border-radius:9px;margin-bottom:14px;font-weight:600;font-size:14px}
    .zcn-current{background:#FBF8F2;border:1px solid #E6DFD2;border-radius:9px;padding:12px 15px;margin-bottom:16px;font-size:13.5px}
    .zcn-current code{background:#fff;border:1px solid #E6DFD2;padding:2px 8px;border-radius:5px;margin-left:6px}
    .zcn-card{background:#fff;border:1px solid #E6DFD2;border-radius:11px;padding:16px 18px;margin-bottom:14px}
    .zcn-t{font-size:12px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:#7C5E33;margin-bottom:8px}
    .zcn-hint{font-size:12.5px;color:#6b6560;margin:0 0 10px;line-height:1.5}
    .zcn-box textarea{width:100%;padding:10px 12px;border:1.5px solid #E6DFD2;border-radius:9px;font-family:inherit;font-size:14px}
    .zcn-box textarea:focus{outline:none;border-color:#B8A47A}
    .zcn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:8px}
    .zcn-chk{display:flex;align-items:center;gap:9px;padding:10px 12px;border:1.5px solid #E6DFD2;border-radius:9px;cursor:pointer;font-size:13px;transition:all .15s}
    .zcn-chk:hover{border-color:#B8A47A}
    .zcn-chk.on{background:#FBF8F2;border-color:#B8A47A}
    .zcn-chk input{accent-color:#B8A47A;width:16px;height:16px;flex-shrink:0}
    .zcn-chk small{display:block;color:#8a8178;font-size:11px}
    /* Vyzerá rovnako vo wp-admine aj v realitnom paneli */
    .zcn-btn{display:inline-flex !important;align-items:center;padding:11px 26px !important;height:auto !important;
        background:#B8A47A !important;border:none !important;border-radius:9px !important;color:#2C2825 !important;
        font-family:inherit;font-weight:700;font-size:13.5px;line-height:1.2;cursor:pointer;
        box-shadow:none !important;text-shadow:none !important}
    .zcn-btn:hover{background:#9A8660 !important;color:#2C2825 !important}
    </style>
    <script>
    document.querySelectorAll('.zcn-chk input').forEach(function(c){
        c.addEventListener('change',function(){c.closest('.zcn-chk').classList.toggle('on',c.checked);});
    });
    </script>
    <?php
    return ob_get_clean();
}
