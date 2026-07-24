<?php /* Template Name: Odhad nehnuteľnosti */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }

$sent  = false;
$error = '';

$odhad_spam = (isset($_POST['odhad_send']) && function_exists('zc_check_spam')) ? (zc_check_spam() !== true) : false;
if (isset($_POST['odhad_send']) && !wp_verify_nonce($_POST['odhad_nonce'] ?? '', 'odhad_form')) {
    $error = 'Platnosť formulára vypršala. Obnovte stránku (Ctrl+F5) a skúste znova.';
} elseif (isset($_POST['odhad_send']) && $odhad_spam) {
    $error = 'Správu sa nepodarilo overiť. Obnovte stránku (Ctrl+F5) a skúste znova.';
} elseif (isset($_POST['odhad_send'])) {
    $typ_ponuky = sanitize_text_field($_POST['typ_ponuky'] ?? 'Predaj');
    $typ_nehnut = sanitize_text_field($_POST['typ_nehnut'] ?? 'Byt');
    $meno       = sanitize_text_field($_POST['meno']       ?? '');
    $priezvisko = sanitize_text_field($_POST['priezvisko'] ?? '');
    $email_od   = sanitize_email($_POST['email']           ?? '');
    $telefon    = sanitize_text_field($_POST['telefon']    ?? '');
    $popis      = sanitize_textarea_field($_POST['popis']  ?? '');
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '';

    $subject = "Nová žiadosť o odhad – {$meno} {$priezvisko}";

    if (function_exists('zc_email_template')) {
        $html = zc_email_template([
            'name'    => "{$meno} {$priezvisko}",
            'email'   => $email_od,
            'phone'   => $telefon,
            'message' => $popis,
            'subject' => $subject,
            'ip'      => $ip,
            'extra'   => [
                'Typ ponuky'        => $typ_ponuky,
                'Typ nehnuteľnosti' => $typ_nehnut,
            ],
        ]);
    } else {
        $html = "<p>Typ: {$typ_ponuky} / {$typ_nehnut}<br>Meno: {$meno} {$priezvisko}<br>Email: {$email_od}<br>Tel: {$telefon}<br><br>{$popis}<br><br>IP: {$ip}</p>";
    }

    $to      = get_theme_mod('zc_email_odhad', '') ?: get_option('admin_email');
    $bcc     = get_theme_mod('zc_email_bcc', '');
    $from    = function_exists('zc_mail_from') ? zc_mail_from() : (get_theme_mod('zc_email_from', '') ?: get_option('admin_email'));
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . zc_agent('name', 'Mgr. Zdenka Cibuľová') . " <{$from}>",
        "Reply-To: =?UTF-8?B?".base64_encode("{$meno} {$priezvisko}")."?= <{$email_od}>",
    ];
    if ($bcc) $headers[] = "Bcc: {$bcc}";

    $sent = wp_mail($to, $subject, $html, $headers);
    if (!$sent) $error = 'Správu sa nepodarilo odoslať. Kontaktujte nás priamo.';

    // Zápis do databázy klientov (CRM)
    if ($sent && function_exists('pp_capture_lead')) {
        pp_capture_lead([
            'name'    => trim("{$meno} {$priezvisko}"),
            'email'   => $email_od,
            'phone'   => $telefon,
            'message' => "Odhad ({$typ_ponuky} / {$typ_nehnut}): {$popis}",
            'source'  => 'odhad',
        ]);
    }

    // Newsletter opt-in
    if ($sent && !empty($_POST['newsletter']) && $email_od && function_exists('zcn_subscribe_forced')) {
        zcn_subscribe_forced($email_od, trim("{$meno} {$priezvisko}"), 'odhad-form');
    }
}
?>

<style>
/* ── PAGE LAYOUT ── */
.odhad-page { background: var(--bg,#FCFBF8); min-height: 60vh; padding: 0 0 80px; }
.odhad-hero { background: var(--section,#F5F1EA); padding: 60px 0 0; text-align: center; }
.odhad-hero-inner { max-width: 680px; margin: 0 auto; padding: 0 24px 48px; }
.odhad-card { max-width: 880px; margin: -40px auto 0; padding: 0 32px; }

/* ── FORM CARD ── */
.odhad-form-wrap {
    background: var(--white,#fff);
    border: 1px solid var(--border,#E2DACE);
    border-radius: 18px;
    box-shadow: 0 8px 40px rgba(60,50,30,.1);
    overflow: hidden;
}

/* ── TOGGLES ── */
.odhad-toggles { padding: 28px 36px 0; display: flex; gap: 20px; flex-wrap: wrap; }
.odhad-toggle-group { display: flex; border: 1.5px solid var(--border,#E2DACE); border-radius: 8px; overflow: hidden; }
.odhad-toggle-btn {
    flex: 1; padding: 11px 22px;
    border: none; background: transparent;
    font-family: var(--sans,'DM Sans',sans-serif);
    font-size: 12px; font-weight: 700; letter-spacing: .8px;
    text-transform: uppercase; color: var(--muted,#6B6560);
    cursor: pointer; transition: all .2s; white-space: nowrap;
}
.odhad-toggle-btn:not(:last-child) { border-right: 1.5px solid var(--border,#E2DACE); }
.odhad-toggle-btn.active { background: var(--accent,#B8A47A); color: var(--dark,#1C1A18); }
.odhad-toggle-btn:hover:not(.active) { background: var(--section,#F5F1EA); }

/* ── FIELDS ── */
.odhad-fields { padding: 24px 36px 0; }
.odhad-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.odhad-field {
    display: flex; flex-direction: column;
    padding: 8px 0;
}
.odhad-field.full { grid-column: 1/-1; }
.odhad-field-label {
    font-size: 10px; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; color: var(--muted,#6B6560);
    margin-bottom: 8px; font-family: var(--sans,'DM Sans',sans-serif);
}
/* boxed inputs – rovnaký štýl ako kontaktný formulár na domovskej */
.odhad-field-input,
.odhad-textarea {
    border: 1.5px solid var(--border,#E2DACE); border-radius: 10px;
    padding: 13px 15px; background: #FCFBF8;
    font-family: var(--sans,'DM Sans',sans-serif);
    font-size: 15px; color: var(--text,#2C2C2C);
    outline: none; transition: border-color .2s, box-shadow .2s; width: 100%;
}
.odhad-textarea { resize: vertical; min-height: 110px; max-height: 300px; line-height: 1.6; }
.odhad-field-input:focus,
.odhad-textarea:focus { border-color: var(--accent,#B8A47A); background:#fff; box-shadow:0 0 0 3px rgba(184,164,122,.12); }
.odhad-field-input::placeholder,
.odhad-textarea::placeholder { color: #B0A898; }

/* ── FOOTER ── */
.odhad-form-footer {
    padding: 24px 36px 32px;
    display: flex; justify-content: space-between;
    align-items: center; flex-wrap: wrap; gap: 16px;
}
.odhad-form-note { display: flex; flex-direction: column; gap: 10px; }
.odhad-note-text { font-size: 13px; color: var(--muted,#6B6560); }
.odhad-note-text strong { color: var(--dark,#1C1A18); }
.odhad-gdpr { display: flex; align-items: flex-start; gap: 9px; font-size: 12px; color: var(--muted,#6B6560); cursor: pointer; }
.odhad-gdpr input { accent-color: var(--accent,#B8A47A); width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; cursor: pointer; }
.odhad-submit {
    padding: 14px 32px;
    background: var(--accent,#B8A47A); color: var(--dark,#1C1A18);
    border: none; border-radius: var(--r-sm,8px);
    font-family: var(--sans,'DM Sans',sans-serif);
    font-size: 13px; font-weight: 700; letter-spacing: .8px;
    text-transform: uppercase; cursor: pointer;
    transition: all .2s; box-shadow: 0 3px 14px rgba(184,164,122,.3);
    white-space: nowrap;
}
.odhad-submit:hover { background: var(--accent-dk,#9A8660); transform: translateY(-2px); }

/* ── SUCCESS ── */
.odhad-success {
    background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;
    padding: 20px 28px; border-radius: 10px; font-size: 15px; margin-bottom: 24px;
}

/* ── INFO BOXES ── */
.odhad-info { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-top: 32px; }
.odhad-info-box {
    background: var(--white,#fff); border: 1px solid var(--border,#E2DACE);
    border-radius: 12px; padding: 22px; text-align: center;
}
.odhad-info-icon { font-size: 28px; margin-bottom: 10px; }
.odhad-info-title { font-family: var(--serif,'Playfair Display',serif); font-size: 15px; font-weight: 700; color: var(--dark,#1C1A18); margin-bottom: 6px; }
.odhad-info-text { font-size: 13px; color: var(--muted,#6B6560); line-height: 1.6; }

/* ── RESPONSIVE ── */
@media (max-width: 700px) {
    .odhad-card { padding: 0 16px; }
    .odhad-toggles { padding: 20px 20px 0; gap: 12px; }
    .odhad-toggle-btn { padding: 10px 14px; font-size: 11px; }
    .odhad-fields { padding: 16px 16px 0; }
    .odhad-row { grid-template-columns: 1fr; gap: 11px; }
    .odhad-field-input, .odhad-textarea { font-size: 16px; padding: 12px 14px; } /* 16px = žiadny auto-zoom na iOS */
    .odhad-textarea { min-height: 92px; }
    .odhad-form-footer { padding: 20px; flex-direction: column; align-items: stretch; }
    .odhad-submit { width: 100%; text-align: center; justify-content: center; }
    .odhad-info { grid-template-columns: 1fr; }
}
</style>

<div class="odhad-page">
    <div class="odhad-hero">
        <div class="odhad-hero-inner">
            <div class="zc-eyebrow" style="justify-content:center">Bezplatná služba</div>
            <h1 style="font-family:var(--serif);font-size:clamp(26px,4vw,42px);margin-bottom:16px">Cenový odhad nehnuteľnosti <em>ZDARMA</em></h1>
            <p style="font-size:16px;color:var(--muted);line-height:1.8;max-width:520px;margin:0 auto">Vyplňte formulár a ja sa vám do <strong>24 hodín</strong> ozvem s odborným odhadom trhovej hodnoty vašej nehnuteľnosti. Nezáväzne a zdarma.</p>
        </div>
    </div>

    <div class="odhad-card">

        <?php if ($sent): ?>
        <div class="odhad-success">
            <strong>Ďakujeme!</strong> Vaša žiadosť bola odoslaná. Ozvem sa vám najneskôr do <strong>24 hodín</strong>.
        </div>
        <?php elseif ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:20px 28px;border-radius:10px;margin-bottom:24px"><?php echo esc_html($error); ?></div>
        <?php endif; ?>

        <?php if (!$sent): ?>
        <div class="odhad-form-wrap">
            <form method="post" style="position:relative">
                <?php echo zc_honeypot_fields(); ?>
                <?php wp_nonce_field('odhad_form','odhad_nonce'); ?>

                <div class="odhad-toggles">
                    <!-- Predaj / Prenájom -->
                    <div class="odhad-toggle-group" id="togPonuky">
                        <button type="button" class="odhad-toggle-btn active" onclick="zcSetToggle('togPonuky','typ_ponuky',this)">Predaj</button>
                        <button type="button" class="odhad-toggle-btn" onclick="zcSetToggle('togPonuky','typ_ponuky',this)">Prenájom</button>
                    </div>
                    <!-- Byt / Dom / Iné -->
                    <div class="odhad-toggle-group" id="togNehnut">
                        <button type="button" class="odhad-toggle-btn active" onclick="zcSetToggle('togNehnut','typ_nehnut',this)">Byt</button>
                        <button type="button" class="odhad-toggle-btn" onclick="zcSetToggle('togNehnut','typ_nehnut',this)">Dom</button>
                        <button type="button" class="odhad-toggle-btn" onclick="zcSetToggle('togNehnut','typ_nehnut',this)">Iné</button>
                    </div>
                    <input type="hidden" name="typ_ponuky" id="typ_ponuky" value="Predaj">
                    <input type="hidden" name="typ_nehnut" id="typ_nehnut" value="Byt">
                </div>

                <div class="odhad-fields">
                    <div class="odhad-row">
                        <div class="odhad-field">
                            <label class="odhad-field-label">Meno</label>
                            <input type="text" name="meno" class="odhad-field-input" placeholder="Ján" required>
                        </div>
                        <div class="odhad-field">
                            <label class="odhad-field-label">Priezvisko</label>
                            <input type="text" name="priezvisko" class="odhad-field-input" placeholder="Novák">
                        </div>
                        <div class="odhad-field">
                            <label class="odhad-field-label">Emailová adresa</label>
                            <input type="email" name="email" class="odhad-field-input" placeholder="jan.novak@email.sk" required>
                        </div>
                        <div class="odhad-field">
                            <label class="odhad-field-label">Telefónne číslo</label>
                            <input type="tel" name="telefon" class="odhad-field-input" placeholder="+421 9XX XXX XXX">
                        </div>
                        <div class="odhad-field full">
                            <label class="odhad-field-label">Popis nehnuteľnosti</label>
                            <textarea name="popis" class="odhad-textarea" placeholder="Lokalita, výmera, počet izieb, stav, poschodie... Čím viac info, tým presnejší odhad."></textarea>
                        </div>
                    </div>
                </div>

                <div class="odhad-form-footer">
                    <div class="odhad-form-note">
                        <p class="odhad-note-text">Ozvem sa najneskôr do <strong>24 hodín</strong></p>
                        <label class="odhad-gdpr">
                            <input type="checkbox" required>
                            <span>Súhlasím so <a href="/ochrana-osobnych-udajov/" style="color:var(--accent-txt)">spracovaním osobných údajov</a> za účelom kontaktovania. *</span>
                        </label>
                        <label class="odhad-gdpr" style="margin-top:8px">
                            <input type="checkbox" name="newsletter">
                            <span>Chcem dostávať novinky a nové ponuky nehnuteľností na e-mail.</span>
                        </label>
                    </div>
                    <button type="submit" name="odhad_send" class="odhad-submit">Vyžiadať odhad ZDARMA</button>
                </div>
            </form>
        </div>

        <!-- INFO BOXES -->
        <div class="odhad-info">
            <?php foreach ([
                ['home', 'Bezplatne', 'Odhad je úplne zdarma a nezáväzný. Bez skrytých poplatkov.'],
                ['clock', 'Do 24 hodín', 'Ozvem sa vám najneskôr do jedného pracovného dňa.'],
                ['chart', 'Odborný odhad', 'Vychádzam z aktuálnych dát trhu a lokality vašej nehnuteľnosti.'],
            ] as [$icon, $title, $text]): ?>
            <div class="odhad-info-box">
                <div class="odhad-info-icon" style="color:var(--accent-txt);display:flex;justify-content:center"><?php echo zc_svg($icon, 28); ?></div>
                <div class="odhad-info-title"><?php echo $title; ?></div>
                <div class="odhad-info-text"><?php echo $text; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function zcSetToggle(groupId, inputId, btn) {
    document.querySelectorAll('#' + groupId + ' .odhad-toggle-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    btn.classList.add('active');
    document.getElementById(inputId).value = btn.textContent.trim();
}
</script>

<?php /* PDF ebook – zobrazí sa len keď je zapnutý v paneli → Ebook */ ?>
<?php echo do_shortcode('[zc_ebook]'); ?>

<?php get_footer(); ?>
