<?php
/**
 * Frontend jadro ebooku: nastavenia, shortcode (pás), modal, AJAX, vloženie
 * položky do menu. Nastavenia sú vo wp_options (zc_ebook_*).
 */
defined('ABSPATH') || exit;

// ── Východiskové hodnoty + gettery ──────────────────────────────────────────
function zc_ebook_defaults() {
    return [
        'enable'   => '',
        'title'    => 'Ako predať nehnuteľnosť za najlepšiu cenu',
        'headline' => 'Stiahnite si PDF sprievodcu ZDARMA',
        'button'   => 'Stiahnuť PDF ZDARMA',
        'menu'     => 'Ebook PDF zdarma',
        'question' => 'Chystáte sa v tomto roku predávať nehnuteľnosť?',
        'subtext'  => 'Praktický sprievodca s tipmi, ako predať nehnuteľnosť rýchlo a za najlepšiu cenu.',
        'options'  => "Áno, do 3 mesiacov\nÁno, tento rok\nZatiaľ len zvažujem\nNie, len ma to zaujíma",
        'pdf'      => '',
        'cover'    => '',
    ];
}
function zc_ebook_get($key) {
    $d = zc_ebook_defaults();
    return get_option('zc_ebook_' . $key, $d[$key] ?? '');
}
function zc_ebook_enabled() {
    return (bool) zc_ebook_get('enable');
}
// Ponechané kvôli spätnej kompatibilite s témou (header/front-page ho volajú)
function zc_ebook_flag($set = false) { return true; }

function zc_ebook_option_list() {
    $raw = zc_ebook_get('options');
    $lines = array_filter(array_map('trim', explode("\n", (string) $raw)));
    return array_values($lines);
}

// ── Shortcode: pás ──────────────────────────────────────────────────────────
add_shortcode('zc_ebook', function () {
    if (!zc_ebook_enabled()) return '';

    $headline = zc_ebook_get('headline');
    $subtext  = zc_ebook_get('subtext');
    $button   = zc_ebook_get('button');
    $cover    = zc_ebook_get('cover');

    ob_start(); ?>
    <section class="zc-ebook" aria-labelledby="zcEbookHead">
        <div class="zc-ebook-inner">
            <?php if ($cover): ?>
            <div class="zc-ebook-cover">
                <img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(zc_ebook_get('title')); ?>" loading="lazy">
            </div>
            <?php endif; ?>
            <div class="zc-ebook-body">
                <span class="zc-ebook-tag">PDF ZDARMA</span>
                <h2 id="zcEbookHead" class="zc-ebook-h"><?php echo esc_html($headline); ?></h2>
                <?php if ($subtext): ?><p class="zc-ebook-sub"><?php echo esc_html($subtext); ?></p><?php endif; ?>
                <button type="button" class="zc-ebook-open zc-btn zc-btn-primary" data-zc-ebook-open>
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    <?php echo esc_html($button); ?>
                </button>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
});

// ── Modal + assets do pätičky – keď je ebook zapnutý, vždy (aby fungovalo
//    hero tlačidlo aj položka v menu na každej stránke) ───────────────────────
add_action('wp_footer', function () {
    if (!zc_ebook_enabled()) return;
    echo zc_ebook_modal_html();
}, 20);

function zc_ebook_modal_html() {
    static $done = false;
    if ($done) return '';
    $done = true;

    $button   = zc_ebook_get('button');
    $question = zc_ebook_get('question');
    $cover    = zc_ebook_get('cover');
    $options  = zc_ebook_option_list();
    $hp       = function_exists('zc_honeypot_fields') ? zc_honeypot_fields() : '';

    ob_start(); ?>
    <div class="zc-ebook-modal" id="zcEbookModal" role="dialog" aria-modal="true" aria-labelledby="zcEbookModalH" hidden>
        <div class="zc-ebook-backdrop" data-zc-ebook-close></div>
        <div class="zc-ebook-dialog">
            <button type="button" class="zc-ebook-x" data-zc-ebook-close aria-label="Zavrieť">&times;</button>
            <div class="zc-ebook-dialog-grid">
                <?php if ($cover): ?>
                <div class="zc-ebook-dialog-cover"><img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy"></div>
                <?php endif; ?>
                <div class="zc-ebook-dialog-form">
                    <h3 id="zcEbookModalH"><?php echo esc_html(zc_ebook_get('title')); ?></h3>
                    <p class="zc-ebook-dialog-sub">Vyplňte údaje a PDF vám pošlem na e-mail — a hneď sa aj otvorí na stiahnutie.</p>
                    <form class="zc-ebook-form" id="zcEbookForm">
                        <?php echo $hp; ?>
                        <label class="zc-ebook-lbl">Meno
                            <input type="text" name="name" autocomplete="name">
                        </label>
                        <label class="zc-ebook-lbl">E-mail <span class="req">*</span>
                            <input type="email" name="email" required autocomplete="email">
                        </label>
                        <?php if ($question && $options): ?>
                        <label class="zc-ebook-lbl"><?php echo esc_html($question); ?>
                            <select name="answer">
                                <option value="">— vyberte —</option>
                                <?php foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php endif; ?>
                        <button type="submit" class="zc-btn zc-btn-primary zc-ebook-submit"><?php echo esc_html($button); ?></button>
                        <p class="zc-ebook-consent-note">Odoslaním získate PDF na e-mail a prihlásite sa na odber noviniek. Odhlásiť sa môžete kedykoľvek jedným klikom.</p>
                        <p class="zc-ebook-msg" id="zcEbookMsg" role="status"></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php echo zc_ebook_assets(); ?>
    <?php
    return ob_get_clean();
}

// ── CSS + JS (raz za stránku) ───────────────────────────────────────────────
function zc_ebook_assets() {
    static $done = false;
    if ($done) return '';
    $done = true;
    $nonce   = wp_create_nonce('zc_nonce');
    $menulbl = zc_ebook_get('menu') ?: 'Ebook PDF zdarma';
    ob_start(); ?>
    <style>
    .zc-ebook{width:100%;margin:0;padding:0;background:linear-gradient(120deg,#ECE1CD 0%,#E3D5BC 100%);border-top:1px solid rgba(155,134,96,.28);border-bottom:1px solid rgba(155,134,96,.28)}
    .zc-ebook-inner{max-width:1120px;margin:0 auto;display:flex;align-items:center;gap:40px;padding:52px clamp(24px,5vw,48px);position:relative;overflow:hidden}
    .zc-ebook-inner::after{content:"";position:absolute;right:-60px;top:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(155,134,96,.18),transparent 70%);pointer-events:none}
    .zc-ebook-cover{flex-shrink:0;width:150px}
    .zc-ebook-cover img{width:100%;height:auto;border-radius:8px;box-shadow:0 16px 34px rgba(60,48,28,.28);transform:rotate(-3deg);transition:transform .4s cubic-bezier(.2,.7,.2,1)}
    .zc-ebook-inner:hover .zc-ebook-cover img{transform:rotate(0) scale(1.03)}
    .zc-ebook-body{flex:1;min-width:0;position:relative;z-index:1}
    .zc-ebook-tag{display:inline-block;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#7C5E33;background:rgba(124,94,51,.10);border:1px solid rgba(124,94,51,.30);padding:4px 12px;border-radius:50px;margin-bottom:14px}
    .zc-ebook-h{font-family:var(--serif,'Playfair Display',serif);color:#1C1A18;font-size:clamp(22px,3vw,30px);line-height:1.2;margin:0 0 10px}
    .zc-ebook-sub{color:#5A5044;font-size:15px;line-height:1.55;margin:0 0 22px;max-width:46ch}
    .zc-ebook-open{display:inline-flex;align-items:center;gap:9px}
    .zc-ebook-modal{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px}
    .zc-ebook-modal[hidden]{display:none}
    .zc-ebook-backdrop{position:absolute;inset:0;background:rgba(18,15,12,.62);backdrop-filter:blur(3px);animation:zcEbFade .25s ease}
    .zc-ebook-dialog{position:relative;background:#F5F1EA;border-radius:20px;max-width:720px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 30px 80px rgba(0,0,0,.45);animation:zcEbUp .3s cubic-bezier(.2,.7,.2,1)}
    @keyframes zcEbFade{from{opacity:0}to{opacity:1}}
    @keyframes zcEbUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
    .zc-ebook-x{position:absolute;top:12px;right:14px;z-index:2;background:rgba(0,0,0,.06);border:none;width:36px;height:36px;border-radius:50%;font-size:24px;line-height:1;color:#1C1A18;cursor:pointer;transition:background .2s}
    .zc-ebook-x:hover{background:rgba(0,0,0,.14)}
    .zc-ebook-dialog-grid{display:flex;gap:0}
    .zc-ebook-dialog-cover{flex-shrink:0;width:220px;background:linear-gradient(150deg,#1C1A18,#2A2621);display:flex;align-items:center;justify-content:center;padding:34px 26px;border-radius:20px 0 0 20px}
    .zc-ebook-dialog-cover img{width:100%;height:auto;border-radius:6px;box-shadow:0 14px 30px rgba(0,0,0,.5)}
    .zc-ebook-dialog-form{flex:1;min-width:0;padding:34px 34px 30px}
    .zc-ebook-dialog-form h3{font-family:var(--serif,'Playfair Display',serif);font-size:23px;color:#1C1A18;margin:0 0 6px;line-height:1.2}
    .zc-ebook-dialog-sub{font-size:14px;color:#6B6155;margin:0 0 18px;line-height:1.5}
    .zc-ebook-lbl{display:block;font-size:13px;font-weight:600;color:#3A342C;margin-bottom:12px}
    .zc-ebook-lbl .req{color:#BF9C5F}
    .zc-ebook-lbl input,.zc-ebook-lbl select{display:block;width:100%;margin-top:5px;padding:11px 13px;border:1.5px solid #D8CFBF;border-radius:10px;font-size:15px;font-family:inherit;background:#fff;color:#1C1A18}
    .zc-ebook-lbl input:focus,.zc-ebook-lbl select:focus{outline:none;border-color:#BF9C5F;box-shadow:0 0 0 3px rgba(191,156,95,.18)}
    .zc-ebook-consent-note{font-size:11.5px;color:#8A8073;line-height:1.45;margin:12px 0 0;text-align:center}
    .zc-ebook-submit{width:100%;justify-content:center}
    .zc-ebook-msg{font-size:13.5px;margin:14px 0 0;text-align:center;min-height:1em}
    .zc-ebook-msg.err{color:#B4232A}
    .zc-ebook-msg.ok{color:#15803D}
    @media(max-width:760px){
        .zc-ebook-inner{flex-direction:column;text-align:center;padding:40px 24px;gap:24px}
        .zc-ebook-sub{margin-left:auto;margin-right:auto}
        .zc-ebook-cover{width:120px}
        .zc-ebook-dialog-grid{flex-direction:column}
        .zc-ebook-dialog-cover{width:100%;border-radius:20px 20px 0 0;padding:24px;flex-direction:row;max-height:150px}
        .zc-ebook-dialog-cover img{max-width:110px}
        .zc-ebook-dialog-form{padding:24px 22px}
    }
    </style>
    <script>
    (function(){
        var modal=document.getElementById('zcEbookModal');
        if(!modal)return;
        var form=document.getElementById('zcEbookForm'),
            msg=document.getElementById('zcEbookMsg'),
            lastFocus=null;
        function open(){lastFocus=document.activeElement;modal.hidden=false;document.body.style.overflow='hidden';var f=modal.querySelector('input,select,button');if(f)f.focus();}
        function close(){modal.hidden=true;document.body.style.overflow='';if(lastFocus)lastFocus.focus();}
        window.zcEbookOpen=open;
        function bind(el){ if(el.getAttribute('data-zceb-bound'))return; el.setAttribute('data-zceb-bound','1'); el.addEventListener('click',function(e){e.preventDefault();open();}); }
        document.querySelectorAll('[data-zc-ebook-open]').forEach(bind);
        modal.querySelectorAll('[data-zc-ebook-close]').forEach(function(b){b.addEventListener('click',close);});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)close();});

        /* ── Vloženie položky „Ebook" do menu (desktop aj mobil), nezávisle od témy ── */
        var LABEL=<?php echo wp_json_encode($menulbl); ?>;
        function makeLink(text){var a=document.createElement('a');a.href='#';a.textContent=text;a.setAttribute('data-zc-ebook-open','');a.className='zc-nav-ebook';return a;}
        function addDesktop(){
            var nav=document.querySelector('.zc-nav'); if(!nav)return;
            if(nav.querySelector('[data-zc-ebook-open]'))return;
            var a=makeLink('Ebook'); bind(a);
            var cta=nav.querySelector('.zc-nav-cta');
            if(cta){nav.insertBefore(a,cta);}else{nav.appendChild(a);}
        }
        function addMobile(){
            var mnav=document.getElementById('zcOvNav'); if(!mnav)return;
            if(mnav.querySelector('[data-zc-ebook-open]'))return;
            var a=makeLink(LABEL); a.style.color='#B8A47A';
            a.addEventListener('click',function(){var ov=document.getElementById('zcOv');if(ov)ov.classList.remove('open','closing');document.body.style.overflow='';});
            mnav.appendChild(a);
        }
        function run(){addDesktop();addMobile();}
        run();
        // mobilné menu môže vzniknúť neskôr (buduje ho téma) → sledovať DOM
        try{
            var mo=new MutationObserver(function(){run();});
            mo.observe(document.body,{childList:true,subtree:true});
        }catch(e){}
        setTimeout(run,600); setTimeout(run,1500);

        /* ── Odoslanie formulára ── */
        if(form){form.addEventListener('submit',function(e){
            e.preventDefault();
            var btn=form.querySelector('.zc-ebook-submit');
            msg.className='zc-ebook-msg';msg.textContent='';
            btn.disabled=true;btn.style.opacity=.6;
            var fd=new FormData(form);
            fd.append('action','zc_ebook_lead');
            fd.append('nonce','<?php echo esc_js($nonce); ?>');
            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>',{method:'POST',body:fd,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(res){
                btn.disabled=false;btn.style.opacity=1;
                if(res&&res.success){
                    msg.className='zc-ebook-msg ok';
                    msg.textContent=res.data.message||'Hotovo! PDF vám otváram…';
                    if(res.data.pdf){window.open(res.data.pdf,'_blank','noopener');}
                    form.reset();
                    /* Pre Správcu značiek Google – konverzia „stiahnutý ebook" */
                    document.dispatchEvent(new CustomEvent('zc:ebook-sent'));
                    setTimeout(close,2600);
                }else{
                    msg.className='zc-ebook-msg err';
                    msg.textContent=(res&&res.data&&res.data.message)||'Nepodarilo sa odoslať. Skúste to znova.';
                }
            })
            .catch(function(){btn.disabled=false;btn.style.opacity=1;msg.className='zc-ebook-msg err';msg.textContent='Chyba spojenia. Skúste to znova.';});
        });}
    })();
    </script>
    <?php
    return ob_get_clean();
}

// ── AJAX: spracovanie odoslania ─────────────────────────────────────────────
add_action('wp_ajax_zc_ebook_lead', 'zc_ebook_handle');
add_action('wp_ajax_nopriv_zc_ebook_lead', 'zc_ebook_handle');
function zc_ebook_handle() {
    check_ajax_referer('zc_nonce', 'nonce');

    if (function_exists('zc_check_spam')) {
        if (zc_check_spam() !== true) wp_send_json_error(['message' => 'Formulár sa nepodarilo odoslať.']);
    } elseif (!empty($_POST['zc_hpf_a']) || !empty($_POST['zc_hpf_b'])) {
        wp_send_json_error(['message' => 'Formulár sa nepodarilo odoslať.']);
    }

    $name   = sanitize_text_field($_POST['name'] ?? '');
    $email  = sanitize_email($_POST['email'] ?? '');
    $answer = sanitize_text_field($_POST['answer'] ?? '');
    if (!$email || !is_email($email)) wp_send_json_error(['message' => 'Zadajte platný e-mail.']);

    $pdf = zc_ebook_get('pdf');
    if (!$pdf) wp_send_json_error(['message' => 'PDF momentálne nie je dostupné. Skúste to neskôr.']);

    $ebook_title = zc_ebook_get('title');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $question = zc_ebook_get('question');

    $msg_parts = [];
    if ($question && $answer) $msg_parts[] = $question . ' → ' . $answer;
    $msg_parts[] = 'Stiahol ebook: ' . $ebook_title;
    if (function_exists('pp_capture_lead')) {
        pp_capture_lead(['name' => $name, 'email' => $email, 'message' => implode("\n", $msg_parts), 'source' => 'ebook', 'ip' => $ip]);
    }

    if (function_exists('zcn_subscribe_forced')) {
        zcn_subscribe_forced($email, $name, 'ebook');
    }

    $from = function_exists('zc_mail_from') ? zc_mail_from() : get_option('admin_email');
    $site = function_exists('zc_agent') ? zc_agent('name', 'Mgr. Zdenka Cibuľová') : get_bloginfo('name');
    $headers = ['Content-Type: text/html; charset=UTF-8', "From: {$site} <{$from}>"];
    $subject = 'Váš PDF sprievodca: ' . $ebook_title;
    $greet = $name ? ('Dobrý deň, ' . $name) : 'Dobrý deň';
    if (function_exists('zc_email_template')) {
        $body = zc_email_template([
            'subject'   => $subject,
            'heading'   => 'Váš PDF na stiahnutie',
            'body_html' => '<p style="margin:0 0 14px">' . esc_html($greet) . ',</p>'
                . '<p style="margin:0 0 18px">ďakujem za váš záujem. Tu je váš PDF sprievodca <strong>' . esc_html($ebook_title) . '</strong> na stiahnutie:</p>'
                . '<p style="margin:0 0 22px"><a href="' . esc_url($pdf) . '" style="display:inline-block;background:#BF9C5F;color:#1C1A18;font-weight:700;text-decoration:none;padding:13px 26px;border-radius:8px">📥 Stiahnuť PDF</a></p>'
                . '<p style="margin:0;color:#6B6155;font-size:14px">Ak by ste sa chceli poradiť o predaji či kúpe nehnuteľnosti, som vám k dispozícii.</p>',
        ]);
    } else {
        $body = '<p>' . esc_html($greet) . ',</p><p>Tu je váš PDF: <a href="' . esc_url($pdf) . '">' . esc_html($ebook_title) . '</a></p>';
    }
    wp_mail($email, $subject, $body, $headers);

    $to_admin = function_exists('zc_notify_to') ? zc_notify_to('ebook') : (get_theme_mod('zc_email_main', '') ?: get_option('admin_email'));
    if ($to_admin) {
        $adm_html = function_exists('zc_email_template')
            ? zc_email_template(['name' => $name, 'email' => $email, 'message' => implode("\n", $msg_parts), 'subject' => 'Nové stiahnutie ebooku', 'heading' => 'Nové stiahnutie ebooku', 'ip' => $ip])
            : '<p>Nové stiahnutie ebooku od ' . esc_html($name) . ' (' . esc_html($email) . ')</p>';
        wp_mail($to_admin, 'Nové stiahnutie ebooku – ' . ($name ?: $email), $adm_html, $headers);
    }

    wp_send_json_success(['message' => 'Hotovo! PDF som poslala na váš e-mail a hneď sa aj otvára.', 'pdf' => esc_url_raw($pdf)]);
}
