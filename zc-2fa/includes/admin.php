<?php
/**
 * Rozhranie na zapnutie 2FA – zdieľané pre wp-admin aj realitný panel.
 */
defined('ABSPATH') || exit;

const ZC2FA_META_PENDING = '_zc2fa_pending';

/**
 * Spracuje odoslaný formulár. Vracia pole [typ, správa, backup_kódy].
 */
function zc2fa_handle_post() {
    $out = ['', '', []];
    if (empty($_POST['zc2fa_action']) || !is_user_logged_in()) return $out;
    if (!isset($_POST['zc2fa_nonce']) || !wp_verify_nonce($_POST['zc2fa_nonce'], 'zc2fa')) return $out;

    $uid = get_current_user_id();
    $act = sanitize_key($_POST['zc2fa_action']);

    if ($act === 'activate') {
        $secret = (string) get_user_meta($uid, ZC2FA_META_PENDING, true);
        $code   = isset($_POST['code']) ? trim((string) $_POST['code']) : '';
        if (!$secret) return ['err', 'Kľúč vypršal, načítajte stránku znova.', []];
        if (!zc2fa_verify_code($secret, $code)) {
            return ['err', 'Kód nesedí. Skontrolujte, či ste ho prepísali správne – platí 30 sekúnd.', []];
        }
        $codes = zc2fa_enable($uid, $secret);
        delete_user_meta($uid, ZC2FA_META_PENDING);
        return ['ok', 'Dvojfaktorové overenie je zapnuté. Uložte si záložné kódy nižšie!', $codes];
    }

    if ($act === 'disable') {
        // Vypnutie potvrdzujeme kódom – aby to nemohol spraviť nikto cudzí pri otvorenom počítači
        $code = isset($_POST['code']) ? trim((string) $_POST['code']) : '';
        if (!zc2fa_check_user_code($uid, $code)) {
            return ['err', 'Na vypnutie zadajte platný kód z aplikácie alebo záložný kód.', []];
        }
        zc2fa_disable($uid);
        return ['ok', 'Dvojfaktorové overenie je vypnuté.', []];
    }

    if ($act === 'newcodes') {
        $code = isset($_POST['code']) ? trim((string) $_POST['code']) : '';
        if (!zc2fa_verify_code(zc2fa_get_secret($uid), $code)) {
            return ['err', 'Zadajte platný kód z aplikácie.', []];
        }
        return ['ok', 'Nové záložné kódy sú vygenerované – staré už neplatia.', zc2fa_generate_backup_codes($uid)];
    }

    return $out;
}

/**
 * Vykreslí rozhranie 2FA (pre aktuálne prihláseného používateľa).
 * $ctx: 'admin' | 'panel' – mení len rámovanie/štýl.
 */
function zc2fa_render_setup($ctx = 'admin') {
    if (!is_user_logged_in()) return '';
    $uid  = get_current_user_id();
    $user = wp_get_current_user();
    [$type, $msg, $codes] = zc2fa_handle_post();
    $on = zc2fa_is_enabled($uid);

    // Pripravíme kľúč pre zapnutie
    $secret = '';
    if (!$on) {
        $secret = (string) get_user_meta($uid, ZC2FA_META_PENDING, true);
        if (!$secret) {
            $secret = zc2fa_generate_secret();
            update_user_meta($uid, ZC2FA_META_PENDING, $secret);
        }
    }
    $issuer = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) ?: 'WordPress';
    $uri    = $secret ? zc2fa_otpauth_uri($secret, $user->user_login, $issuer) : '';

    ob_start(); ?>
    <div class="zc2fa">
        <?php if ($msg): ?>
        <div class="zc2fa-msg zc2fa-<?php echo esc_attr($type); ?>"><?php echo wp_kses_post($msg); ?></div>
        <?php endif; ?>

        <?php if ($codes): ?>
        <div class="zc2fa-card zc2fa-codes">
            <div class="zc2fa-card-t">Záložné kódy – uložte si ich teraz</div>
            <p class="zc2fa-hint">Každý kód sa dá použiť raz, keď nemáte po ruke mobil. <strong>Zobrazujú sa len teraz.</strong></p>
            <div class="zc2fa-codelist">
                <?php foreach ($codes as $c): ?><code><?php echo esc_html($c); ?></code><?php endforeach; ?>
            </div>
            <button type="button" class="zc2fa-btn-ghost" onclick="zc2faCopy(this)">Skopírovať kódy</button>
        </div>
        <?php endif; ?>

        <div class="zc2fa-head">
            <div>
                <h2>Dvojfaktorové overenie (2FA)</h2>
                <p>Pri prihlásení sa okrem hesla zadáva 6-ciferný kód z mobilu. Aj keď niekto pozná heslo, bez telefónu sa dnu nedostane.</p>
            </div>
            <span class="zc2fa-state <?php echo $on ? 'is-on' : 'is-off'; ?>"><?php echo $on ? '✓ Zapnuté' : 'Vypnuté'; ?></span>
        </div>

        <?php if (!$on): ?>
        <div class="zc2fa-card">
            <div class="zc2fa-card-t">Zapnutie – 3 kroky</div>
            <ol class="zc2fa-steps">
                <li>
                    <strong>Nainštalujte si aplikáciu</strong> do mobilu – <em>Google Authenticator</em> (Android/iPhone), prípadne Microsoft Authenticator či Authy.
                </li>
                <li>
                    <strong>Naskenujte QR kód</strong> – v aplikácii zvoľte <em>„Skenovať QR kód"</em>:
                    <div class="zc2fa-qr-wrap">
                        <div class="zc2fa-qr"><?php echo zc2fa_qr_svg($uri, 210); ?></div>
                        <div class="zc2fa-qr-side">
                            <p class="zc2fa-hint" style="margin-bottom:8px"><strong>Nejde naskenovať?</strong> Zvoľte v aplikácii <em>„Zadať kód nastavenia"</em> a prepíšte:</p>
                            <div class="zc2fa-secret">
                                <div><span>Účet:</span> <code><?php echo esc_html($user->user_login); ?></code></div>
                                <div><span>Kľúč:</span> <code id="zc2faKey"><?php echo esc_html(zc2fa_format_secret($secret)); ?></code>
                                    <button type="button" class="zc2fa-btn-ghost zc2fa-mini" onclick="zc2faCopyKey()">Kopírovať</button></div>
                                <div><span>Typ:</span> <code>Time-based</code></div>
                            </div>
                        </div>
                    </div>
                    <p class="zc2fa-hint">QR aj kľúč obsahujú tajomstvo vášho účtu – nefoťte ich a nikomu neposielajte. QR sa vytvára priamo na tomto webe, nikam sa neodosiela.</p>
                </li>
                <li>
                    <strong>Opíšte kód z aplikácie</strong> a potvrďte:
                    <form method="post" class="zc2fa-form">
                        <?php wp_nonce_field('zc2fa', 'zc2fa_nonce'); ?>
                        <input type="hidden" name="zc2fa_action" value="activate">
                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                               placeholder="123456" maxlength="6" required>
                        <button type="submit" class="zc2fa-btn">Zapnúť 2FA</button>
                    </form>
                </li>
            </ol>
        </div>
        <?php else: ?>
        <div class="zc2fa-card">
            <div class="zc2fa-card-t">Stav</div>
            <p class="zc2fa-hint">Zostávajúce záložné kódy: <strong><?php echo (int) zc2fa_count_backup_codes($uid); ?></strong></p>
            <div class="zc2fa-two">
                <form method="post" class="zc2fa-form">
                    <?php wp_nonce_field('zc2fa', 'zc2fa_nonce'); ?>
                    <input type="hidden" name="zc2fa_action" value="newcodes">
                    <label>Nové záložné kódy <small>(zadajte kód z aplikácie)</small></label>
                    <div class="zc2fa-row">
                        <input type="text" name="code" inputmode="numeric" placeholder="123456" maxlength="6" required>
                        <button type="submit" class="zc2fa-btn-ghost">Vygenerovať</button>
                    </div>
                </form>
                <form method="post" class="zc2fa-form">
                    <?php wp_nonce_field('zc2fa', 'zc2fa_nonce'); ?>
                    <input type="hidden" name="zc2fa_action" value="disable">
                    <label>Vypnúť 2FA <small>(potvrďte kódom)</small></label>
                    <div class="zc2fa-row">
                        <input type="text" name="code" placeholder="123456 alebo záložný kód" required>
                        <button type="submit" class="zc2fa-btn-danger">Vypnúť</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="zc2fa-card">
            <div class="zc2fa-card-t">Automatické odhlásenie</div>
            <p class="zc2fa-hint">
                Bez 2FA: po <strong><?php echo (int) zc2fa_opt('idle_no2fa'); ?> min</strong> nečinnosti ·
                So zapnutým 2FA: po <strong><?php echo (int) zc2fa_opt('idle_2fa'); ?> min</strong> nečinnosti.
                <?php if (!$on): ?><br>Zapnutím 2FA teda získate aj dlhšie prihlásenie.<?php endif; ?>
            </p>
        </div>
    </div>

    <style>
    .zc2fa{--g:#B8A47A;--gd:#7C5E33;--dk:#1C1A18;--ln:#E6DFD2;max-width:820px;font-family:-apple-system,'Segoe UI',Roboto,sans-serif;color:#2C2825}
    .zc2fa *{box-sizing:border-box}
    .zc2fa-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;background:linear-gradient(120deg,#1C1A18,#2A2620);color:#fff;border-radius:14px;padding:20px 22px;margin-bottom:16px;flex-wrap:wrap}
    .zc2fa-head h2{margin:0 0 4px;font-size:19px;color:#fff}
    .zc2fa-head p{margin:0;font-size:13px;color:rgba(255,255,255,.72);max-width:60ch;line-height:1.5}
    .zc2fa-state{font-size:12px;font-weight:800;padding:6px 14px;border-radius:50px;white-space:nowrap}
    .zc2fa-state.is-on{background:#dcfce7;color:#15803d}
    .zc2fa-state.is-off{background:rgba(255,255,255,.15);color:#fff}
    .zc2fa-card{background:#fff;border:1px solid var(--ln);border-radius:12px;padding:18px 20px;margin-bottom:14px}
    .zc2fa-card-t{font-size:12px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--gd);margin-bottom:12px}
    .zc2fa-steps{margin:0;padding-left:20px;line-height:1.7}
    .zc2fa-steps li{margin-bottom:16px}
    .zc2fa-steps li:last-child{margin-bottom:0}
    .zc2fa-qr-wrap{display:flex;gap:22px;align-items:flex-start;margin:12px 0;flex-wrap:wrap}
    .zc2fa-qr{background:#fff;border:1px solid var(--ln);border-radius:10px;padding:10px;line-height:0;flex-shrink:0}
    .zc2fa-qr svg{display:block;width:210px;height:210px}
    .zc2fa-qr-side{flex:1;min-width:250px}
    .zc2fa-secret{background:#FBF8F2;border:1px solid var(--ln);border-radius:9px;padding:12px 14px;margin:10px 0;font-size:13px;line-height:2}
    .zc2fa-secret span{display:inline-block;min-width:110px;color:#8a8178}
    .zc2fa-secret code{background:#fff;border:1px solid var(--ln);padding:3px 8px;border-radius:5px;font-size:14px;letter-spacing:1px}
    .zc2fa-uri{display:block;word-break:break-all;background:#FBF8F2;padding:10px;border-radius:6px;font-size:11px;margin:8px 0}
    .zc2fa-details{margin-top:8px;font-size:13px}
    .zc2fa-details summary{cursor:pointer;color:var(--gd)}
    .zc2fa-hint{font-size:13px;color:#6b6560;line-height:1.6;margin:0 0 10px}
    .zc2fa-form{margin-top:10px}
    .zc2fa-form label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
    .zc2fa-form label small{font-weight:400;color:#8a8178}
    .zc2fa-form input{padding:10px 12px;border:1.5px solid var(--ln);border-radius:8px;font-size:15px;letter-spacing:2px;width:190px;font-family:inherit}
    .zc2fa-form input:focus{outline:none;border-color:var(--g)}
    .zc2fa-row{display:flex;gap:8px;flex-wrap:wrap}
    .zc2fa-two{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .zc2fa-btn,.zc2fa-btn-ghost,.zc2fa-btn-danger{border:none;border-radius:8px;font-weight:700;font-size:13px;padding:11px 20px;cursor:pointer;font-family:inherit}
    .zc2fa-btn{background:var(--g);color:#1C1A18}
    .zc2fa-btn:hover{background:var(--gd);color:#fff}
    .zc2fa-btn-ghost{background:#FBF8F2;border:1.5px solid var(--ln);color:var(--gd)}
    .zc2fa-btn-danger{background:#fee2e2;color:#b91c1c}
    .zc2fa-mini{padding:4px 10px;font-size:11px}
    .zc2fa-msg{padding:12px 16px;border-radius:9px;margin-bottom:16px;font-size:14px;font-weight:600}
    .zc2fa-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
    .zc2fa-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
    .zc2fa-codes{background:#FFFBEB;border-color:#FDE68A}
    .zc2fa-codelist{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;margin-bottom:12px}
    .zc2fa-codelist code{background:#fff;border:1px solid #FDE68A;padding:8px;border-radius:6px;text-align:center;font-size:14px;letter-spacing:1px}
    @media(max-width:700px){.zc2fa-two{grid-template-columns:1fr}.zc2fa-form input{width:100%}}
    </style>
    <script>
    function zc2faCopyKey(){var t=document.getElementById('zc2faKey');if(!t)return;navigator.clipboard&&navigator.clipboard.writeText(t.textContent.replace(/\s/g,''));}
    function zc2faCopy(btn){
        var codes=[].map.call(document.querySelectorAll('.zc2fa-codelist code'),function(c){return c.textContent;}).join('\n');
        if(navigator.clipboard){navigator.clipboard.writeText(codes);btn.textContent='Skopírované ✓';}
    }
    </script>
    <?php
    return ob_get_clean();
}

/* ───────────────────────── wp-admin: stránka 2FA ───────────────────────── */

add_action('admin_menu', function () {
    add_options_page('Zabezpečenie (2FA)', 'Zabezpečenie (2FA)', 'read', 'zc-2fa', 'zc2fa_admin_page');
});

function zc2fa_admin_page() {
    echo '<div class="wrap" style="max-width:900px"><h1>Zabezpečenie prihlásenia</h1>';
    if (!empty($_GET['zc2fa_required'])) {
        echo '<div class="notice notice-error"><p><strong>Správca webu musí mať zapnuté 2FA.</strong> Zapnite ho nižšie, potom budete môcť pokračovať.</p></div>';
    }
    echo zc2fa_render_setup('admin');
    if (current_user_can('manage_options')) echo zc2fa_admin_settings();
    echo '</div>';
}

// Nastavenia (len správca): vynútenie + časy nečinnosti + prehľad používateľov
function zc2fa_admin_settings() {
    $saved = false;
    if (isset($_POST['zc2fa_settings']) && check_admin_referer('zc2fa_settings')) {
        update_option('zc2fa_enforce', empty($_POST['enforce']) ? '' : '1');
        update_option('zc2fa_idle_no2fa', max(5, (int) ($_POST['idle_no2fa'] ?? 30)));
        update_option('zc2fa_idle_2fa',   max(5, (int) ($_POST['idle_2fa'] ?? 120)));
        $saved = true;
    }
    ob_start(); ?>
    <div class="zc2fa" style="margin-top:26px">
        <?php if ($saved): ?><div class="zc2fa-msg zc2fa-ok">Nastavenia uložené.</div><?php endif; ?>
        <div class="zc2fa-card">
            <div class="zc2fa-card-t">Nastavenia (len správca)</div>
            <form method="post">
                <?php wp_nonce_field('zc2fa_settings'); ?>
                <p><label><input type="checkbox" name="enforce" value="1" <?php checked(zc2fa_opt('enforce'), '1'); ?>>
                    <strong>Vyžadovať 2FA od správcov</strong> – bez zapnutého 2FA sa nedostanú do wp-admin.</label></p>
                <p>
                    <label>Odhlásiť po nečinnosti <strong>bez 2FA</strong> (min):
                        <input type="number" name="idle_no2fa" min="5" max="1440" value="<?php echo (int) zc2fa_opt('idle_no2fa'); ?>" style="width:90px;letter-spacing:0">
                    </label>
                </p>
                <p>
                    <label>Odhlásiť po nečinnosti <strong>s 2FA</strong> (min):
                        <input type="number" name="idle_2fa" min="5" max="1440" value="<?php echo (int) zc2fa_opt('idle_2fa'); ?>" style="width:90px;letter-spacing:0">
                    </label>
                </p>
                <button type="submit" name="zc2fa_settings" value="1" class="zc2fa-btn">Uložiť nastavenia</button>
            </form>
        </div>

        <div class="zc2fa-card">
            <div class="zc2fa-card-t">Prehľad používateľov</div>
            <table class="widefat striped">
                <thead><tr><th>Používateľ</th><th>Rola</th><th>2FA</th><th>Záložné kódy</th></tr></thead>
                <tbody>
                <?php foreach (get_users(['number' => 50]) as $u): $has = zc2fa_is_enabled($u->ID); ?>
                    <tr>
                        <td><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></td>
                        <td><?php echo esc_html(implode(', ', (array) $u->roles)); ?></td>
                        <td><?php echo $has ? '<span style="color:#15803d;font-weight:700">✓ zapnuté</span>' : '<span style="color:#b45309">vypnuté</span>'; ?></td>
                        <td><?php echo $has ? (int) zc2fa_count_backup_codes($u->ID) : '–'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="zc2fa-hint" style="margin-top:10px">2FA si zapína každý používateľ sám vo svojom účte – kľúč nikto iný nevidí.</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Odkaz aj priamo v profile používateľa
add_action('show_user_profile', function ($user) {
    if ((int) $user->ID !== get_current_user_id()) return;
    echo '<h2>Dvojfaktorové overenie</h2><p>Nastavenie 2FA nájdete na stránke <a href="'
        . esc_url(admin_url('options-general.php?page=zc-2fa')) . '">Nastavenia → Zabezpečenie (2FA)</a>.</p>';
});

/* ─────────── Realitný panel: sekcia Zabezpečenie (pre maklérku) ─────────── */
if (!function_exists('panel_security')) {
    function panel_security() {
        return zc2fa_render_setup('panel');
    }
}
