<?php /* Template Name: Kontakt */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
$name  = zc_agent('name',  'Mgr. Zdenka Cibuľová');
$title = zc_agent('title', 'Realitná maklérka');
$phone = zc_agent('phone', '+421 907 579 742');
$wa    = preg_replace('/[^0-9]/', '', zc_agent('wa','421907579742'));
$email = zc_agent('email', get_option('admin_email'));
?>
<style>
/* ── Page layout ── */
.ko-page { background: var(--bg); }
.ko-hero  { background: var(--section); padding: 56px 0 32px; text-align: center; }
.ko-body  { max-width: 900px; margin: 0 auto; padding: 40px 24px 80px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 48px; align-items: start; }

/* ── Contact card ── */
.ko-card  { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 28px 24px; box-shadow: var(--sh-sm); }
.ko-agent { display: flex; align-items: center; gap: 14px; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
.ko-avatar{ width: 56px; height: 56px; border-radius: 50%; background: var(--section); border: 2.5px solid var(--accent); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; }
.ko-name  { font-family: var(--serif); font-size: 16px; font-weight: 700; color: var(--dark); }
.ko-role  { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--accent); font-family: var(--sans); margin-top: 2px; }
.ko-link  { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--border); text-decoration: none; transition: padding-left .2s; }
.ko-link:last-child { border-bottom: none; }
.ko-link:hover { padding-left: 4px; }
.ko-link-icon { width: 40px; height: 40px; background: var(--section); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--accent-txt); }
.ko-link-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); font-family: var(--sans); }
.ko-link-val   { font-size: 14px; font-weight: 600; color: var(--dark); margin-top: 2px; }

/* ── Odhad-style form ── */
.ko-form-wrap { background: var(--white); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: var(--sh-sm); }
.ko-form-head { padding: 24px 28px 0; }
.ko-form-head h2 { font-family: var(--serif); font-size: 22px; margin-bottom: 4px; }
.ko-form-head p  { font-size: 13px; color: var(--muted); margin: 0 0 8px; }
.ko-fields { padding: 0 28px; }
/* Rovnaký boxed štýl ako formulár na domovskej stránke */
.ko-row    { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ko-field  { display: flex; flex-direction: column; }
.ko-field.full { grid-column: 1/-1; }
.ko-label { font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; font-family: var(--sans); }
.ko-input, .ko-textarea {
    border: 1.5px solid var(--border); border-radius: var(--r-sm);
    padding: 13px 15px; background: var(--bg);
    font-family: var(--sans); font-size: 16px; color: var(--text);
    outline: none; transition: border-color .2s, box-shadow .2s; width: 100%;
}
.ko-textarea { resize: vertical; min-height: 110px; max-height: 320px; line-height: 1.6; }
.ko-input:focus, .ko-textarea:focus { border-color: var(--accent); background: var(--white); box-shadow: 0 0 0 3px rgba(184,164,122,.12); }
.ko-input::placeholder, .ko-textarea::placeholder { color: #B0A898; }
.ko-form-footer {
    padding: 20px 28px 28px;
    display: flex; flex-direction: column; gap: 14px;
}
.ko-note { font-size: 13px; color: var(--muted); }
.ko-submit {
    width: 100%; padding: 15px 28px;
    background: var(--accent); color: var(--dark);
    border: none; border-radius: var(--r-sm);
    font-family: var(--sans); font-size: 12px; font-weight: 700;
    letter-spacing: .8px; text-transform: uppercase;
    cursor: pointer; transition: all .22s;
    box-shadow: 0 3px 14px rgba(184,164,122,.3);
}
.ko-submit:hover { background: var(--accent-dk); transform: translateY(-2px); }

@media(max-width: 768px) {
    .ko-body  { grid-template-columns: 1fr; gap: 24px; padding: 24px 16px 56px; }
    .ko-row   { grid-template-columns: 1fr; }
    .ko-row .ko-field:nth-child(odd)  { padding-right: 0; border-right: none; }
    .ko-row .ko-field:nth-child(even) { padding-left: 0; }
    .ko-fields { padding: 0 20px; }
    .ko-form-head { padding: 20px 20px 0; }
    .ko-form-footer { padding: 16px 20px 24px; flex-direction: column; align-items: stretch; }
    .ko-submit { text-align: center; }
    .ko-input, .ko-textarea { font-size: 16px; }
}
</style>

<div class="ko-page">
    <div class="ko-hero">
        <div class="zc-eyebrow" style="justify-content:center">Kontakt</div>
        <h1 style="font-family:var(--serif);font-size:clamp(26px,4vw,38px);margin-bottom:8px">Napíšte mi <em>správu</em></h1>
        <p style="font-size:15px;color:var(--muted)">Prvá konzultácia je bezplatná a nezáväzná.</p>
    </div>

    <div class="ko-body">
        <!-- Contact info -->
        <div class="ko-card">
            <div class="ko-agent">
                <?php $zc_portrait = function_exists('zc_photo') ? zc_photo('portrait') : ''; ?>
                <?php if($zc_portrait): ?>
                <img src="<?php echo esc_url($zc_portrait); ?>" alt="<?php echo esc_attr($name); ?>" class="ko-avatar" style="object-fit:cover;object-position:center 18%">
                <?php else: ?>
                <div class="ko-avatar" style="font-family:var(--serif);font-size:18px;font-weight:700;color:var(--accent-txt)"><?php echo esc_html(function_exists('zc_initials') ? zc_initials($name) : ''); ?></div>
                <?php endif; ?>
                <div>
                    <div class="ko-name"><?php echo esc_html($name); ?></div>
                    <div class="ko-role"><?php echo esc_html($title); ?></div>
                </div>
            </div>
            <?php foreach([
                ['tel:'.preg_replace('/[^0-9+]/','',$phone),
                 '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>',
                 'Telefón', esc_html($phone)],
                ['https://wa.me/'.$wa,
                 '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
                 'WhatsApp', 'Napísať správu'],
                ['mailto:'.esc_attr($email),
                 '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>',
                 'E-mail', esc_html($email)],
            ] as [$href,$icon,$label,$val]): ?>
            <a href="<?php echo $href; ?>" class="ko-link">
                <span class="ko-link-icon"><?php echo $icon; ?></span>
                <div>
                    <div class="ko-link-label"><?php echo $label; ?></div>
                    <div class="ko-link-val"><?php echo $val; ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Form -->
        <div class="ko-form-wrap">
            <div class="ko-form-head">
                <h2>Odošlite správu</h2>
                <p>Odpoviem do 24 hodín.</p>
            </div>
            <form id="zcContactForm" style="position:relative">
                <?php echo zc_honeypot_fields(); ?>
                <div class="ko-fields">
                    <div class="ko-row">
                        <div class="ko-field">
                            <label class="ko-label">Meno *</label>
                            <input type="text" name="name" class="ko-input" placeholder="Ján" required>
                        </div>
                        <div class="ko-field">
                            <label class="ko-label">Telefón</label>
                            <input type="tel" name="phone" class="ko-input" placeholder="+421 9XX XXX XXX">
                        </div>
                        <div class="ko-field full">
                            <label class="ko-label">E-mail *</label>
                            <input type="email" name="email" class="ko-input" placeholder="vas@email.sk" required>
                        </div>
                        <div class="ko-field full">
                            <label class="ko-label">Zámer / Správa</label>
                            <textarea name="message" class="ko-textarea" placeholder="Napíšte dôvod kontaktu, o akú nehnuteľnosť máte záujem..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="ko-form-footer">
                    <div id="zcFormMsg"></div>
                    <div style="margin-bottom:12px">
                        <label style="display:flex;align-items:flex-start;gap:9px;font-size:12px;color:var(--muted);cursor:pointer;line-height:1.6">
                            <input type="checkbox" name="gdpr" required style="margin-top:2px;accent-color:var(--accent);flex-shrink:0">
                            <span>Súhlasím so <a href="/ochrana-osobnych-udajov/" style="color:var(--accent-txt)">spracovaním osobných údajov</a> za účelom odpovede na môj dopyt. *</span>
                        </label>
                    </div>
                    <label style="display:flex;align-items:flex-start;gap:9px;font-size:12px;color:var(--muted);cursor:pointer;line-height:1.6;margin-bottom:16px">
                        <input type="checkbox" name="newsletter" style="margin-top:2px;accent-color:var(--accent);flex-shrink:0">
                        <span>Chcem dostávať novinky a nové ponuky nehnuteľností na e-mail.</span>
                    </label>
                    <button type="submit" class="ko-submit">Odoslať správu →</button>
                    <span class="ko-note" style="text-align:center">Ozvem sa najskôr do <strong>24 hodín</strong></span>
                </div>
            </form>
        </div>
    </div>
</div>
<?php get_footer(); ?>
