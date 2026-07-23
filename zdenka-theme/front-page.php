<?php
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }
$phone=zc_agent('phone','+421 907 579 742');
$wa=zc_agent('wa','421907579742');
$email=zc_agent('email',get_option('admin_email'));
$name=zc_agent('name','Mgr. Zdenka Cibuľová');
$title=zc_agent('title','Realitná maklérka');
?>
<?php $zc_hero_portrait = function_exists('zc_photo') ? zc_photo('portrait') : ''; ?>
<style>
/* Sticky hero – zvyšok stránky sa naň pri scrolle nasunie ako opona */
.zc-home-hero{height:100vh;min-height:560px;margin-top:calc(-1 * var(--hh,72px));position:sticky;top:0;z-index:0;overflow:hidden;display:flex;align-items:center}
.zc-home-hero ~ section{position:relative;z-index:2}
.zc-home-hero ~ footer.zc-footer{position:relative;z-index:2}
.zc-hero-bg{position:absolute;inset:0;background-size:cover;background-position:67% center;background-repeat:no-repeat;will-change:transform;transform-origin:67% 35%;filter:brightness(1.13)}
.zc-hero-text{will-change:transform,opacity}
/* Postupné nabehnutie hero obsahu pri načítaní (beží vždy, aj na PC) */
@keyframes zcRise{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:none}}
.zc-hero-text>*{animation:zcRise .7s cubic-bezier(.22,.9,.36,1) both}
.zc-hero-text>*:nth-child(1){animation-delay:.12s}
.zc-hero-text>*:nth-child(2){animation-delay:.24s}
.zc-hero-text>*:nth-child(3){animation-delay:.36s}
.zc-hero-text>*:nth-child(4){animation-delay:.48s}
.zc-hero-text>*:nth-child(5){animation-delay:.60s}
.zc-hero-overlay{position:absolute;inset:0;background:linear-gradient(105deg,rgba(20,18,15,.88) 0%,rgba(20,18,15,.6) 55%,rgba(20,18,15,.15) 100%);z-index:1}
.zc-hero-text{position:relative;z-index:2;padding:calc(var(--hh,72px) + 40px) 64px 60px;max-width:660px}
.zc-hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--accent,#B8A47A);margin-bottom:20px;font-family:var(--sans,sans-serif)}
.zc-hero-eyebrow::before{content:'';width:28px;height:1.5px;background:var(--accent,#B8A47A);display:block}
.zc-hero-h1{font-family:var(--serif,'Playfair Display',serif);font-size:clamp(36px,4.5vw,64px);font-weight:800;line-height:1.15;color:#fff;margin-bottom:24px;text-shadow:0 2px 20px rgba(0,0,0,.3)}
.zc-hero-h1 em{color:var(--accent,#B8A47A);font-style:italic}
.zc-hero-p{font-size:clamp(15px,1.4vw,18px);color:rgba(255,255,255,.78);line-height:1.8;margin-bottom:40px;font-family:var(--sans,sans-serif)}
.zc-hero-btns{display:flex;gap:14px;flex-wrap:wrap}
.zc-hero-name-card{margin-top:52px;display:inline-flex;align-items:center;gap:14px;padding:14px 20px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:12px;backdrop-filter:blur(10px)}
.zc-hero-name-card-name{font-family:var(--serif,serif);font-size:15px;font-weight:700;color:#fff}
.zc-hero-name-card-role{font-size:10px;color:var(--accent,#B8A47A);letter-spacing:1.5px;text-transform:uppercase;margin-top:2px;font-family:var(--sans,sans-serif)}
.zc-hero-scroll{position:absolute;bottom:28px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;color:rgba(255,255,255,.4);font-size:10px;letter-spacing:1.5px;text-transform:uppercase;font-family:var(--sans,sans-serif);z-index:5;animation:heroScroll 2s ease-in-out infinite}
@keyframes heroScroll{0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(7px)}}
<?php if ($zc_hero_portrait): ?>
/* Mobil: hero = vertikálny portrét – tvár je vycentrovaná z podstaty fotky */
@media(max-width:768px){
    /* fotka na mobile stmavená, aby nezanikal text */
    .zc-hero-bg{background-image:url('<?php echo esc_url($zc_hero_portrait); ?>') !important;background-position:center 22% !important;transform-origin:center 25%;filter:brightness(.82) !important}
    .zc-home-hero{align-items:flex-end}
    .zc-hero-text{padding:calc(var(--hh,60px) + 32px) 24px 88px}
    /* silnejší gradient zdola hore – text dole je vždy čitateľný */
    .zc-hero-overlay{background:linear-gradient(to top,rgba(18,15,12,.94) 0%,rgba(18,15,12,.82) 24%,rgba(18,15,12,.5) 52%,rgba(18,15,12,.24) 78%,rgba(18,15,12,.14) 100%)}
}
<?php else: ?>
@media(max-width:768px){.zc-hero-text{padding:calc(var(--hh,60px) + 32px) 24px 48px}.zc-hero-overlay{background:linear-gradient(to top,rgba(18,15,12,.92) 0%,rgba(18,15,12,.72) 40%,rgba(18,15,12,.4) 100%)}.zc-hero-bg{background-position:73% 28%;filter:brightness(.85) !important}}
<?php endif; ?>
</style>

<script>
(function(){
    var hdr=document.getElementById('zcHeader');
    if(!hdr)return;
    hdr.classList.add('transparent');
    // Sticky hero má rect.bottom vždy = výška viewportu – meriame preto scrollY
    function u(){if(!document.getElementById('zcHomeHero'))return;hdr.classList.toggle('transparent',window.scrollY < 24);}
    window.addEventListener('scroll',u,{passive:true});
})();
</script>

<div class="zc-home-hero" id="zcHomeHero">
    <?php
    // Hero fotka: Customizer (Fotky maklérky) → featured image stránky → tmavé pozadie
    $zc_hero_img = function_exists('zc_photo') ? zc_photo('hero') : '';
    if (!$zc_hero_img && has_post_thumbnail()) $zc_hero_img = get_the_post_thumbnail_url(null, 'full');
    ?>
    <?php if($zc_hero_img): ?>
    <div class="zc-hero-bg" id="zcHeroBg" style="background-image:url('<?php echo esc_url($zc_hero_img); ?>')"></div>
    <?php else: ?><div class="zc-hero-bg" id="zcHeroBg" style="background:#1C1A18"></div><?php endif; ?>
    <div class="zc-hero-overlay"></div>
    <div class="zc-hero-text">
        <div class="zc-hero-eyebrow">Banská Bystrica · Zvolen</div>
        <h1 class="zc-hero-h1">Predáme váš domov <em>za najlepšiu cenu</em></h1>
        <p class="zc-hero-p">Profesionálna realitná maklérka s bohatými skúsenosťami. Predaj, prenájom aj poradenstvo – vždy s osobným prístupom.</p>
        <div class="zc-hero-btns">
            <a href="<?php echo home_url('/ponuky/'); ?>" class="zc-btn zc-btn-primary">Pozrieť ponuky</a>
            <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-btn" style="background:rgba(255,255,255,.12);color:#fff;border:1.5px solid rgba(255,255,255,.3)">Bezplatná konzultácia</a>
        </div>
        <div class="zc-hero-name-card">
            <?php $zc_portrait = function_exists('zc_photo') ? zc_photo('portrait') : ''; ?>
            <?php if($zc_portrait): ?>
            <img src="<?php echo esc_url($zc_portrait); ?>" alt="<?php echo esc_attr($name); ?>" style="width:44px;height:44px;border-radius:50%;border:2px solid var(--accent,#B8A47A);object-fit:cover;object-position:center 18%;flex-shrink:0">
            <?php else: ?>
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid var(--accent,#B8A47A);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;font-family:var(--serif);flex-shrink:0"><?php echo esc_html(function_exists('zc_initials') ? zc_initials($name) : ''); ?></div>
            <?php endif; ?>
            <div><div class="zc-hero-name-card-name"><?php echo esc_html($name); ?></div><div class="zc-hero-name-card-role"><?php echo esc_html($title); ?></div></div>
        </div>
    </div>
    <div class="zc-hero-scroll">
        <span>Scrolluj</span>
        <svg width="16" height="20" viewBox="0 0 16 20" fill="none"><rect x="1" y="1" width="14" height="18" rx="7" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="6" r="2" fill="currentColor"/></svg>
    </div>
</div>

<script>
/* Hero zoom pri scrolle: obsah stránky sa nasúva na prilepený hero
   a fotka sa plynulo približuje; text jemne mizne. Beží vždy (PC aj mobil). */
(function(){
    var hero = document.getElementById('zcHomeHero');
    var bg   = document.getElementById('zcHeroBg');
    var txt  = document.querySelector('.zc-hero-text');
    var hint = document.querySelector('.zc-hero-scroll');
    if (!hero || !bg) return;
    // Zoom aj pohyb textu bežia vždy – nezávisle od systémového „obmedziť pohyb"
    var ticking = false;
    function update(){
        var h = hero.offsetHeight || 1;
        var p = Math.min(Math.max(window.scrollY / h, 0), 1); // 0 → 1 kým hero zmizne
        bg.style.transform = 'scale(' + (1 + p * 0.15).toFixed(4) + ')';
        if (txt)  { txt.style.opacity = Math.max(1 - p * 1.15, 0).toFixed(3); txt.style.transform = 'translateY(' + (-p * 40).toFixed(1) + 'px)'; }
        if (hint) { hint.style.opacity = Math.max(1 - p * 3, 0).toFixed(3); }
        ticking = false;
    }
    window.addEventListener('scroll', function(){
        if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, {passive:true});
    window.addEventListener('resize', update, {passive:true});
    update();
})();
</script>

<!-- MOJE HODNOTY -->
<section class="zc-section bg-section">
<div class="zc-container">
    <div style="text-align:center;max-width:580px;margin:0 auto 52px">
        <div class="zc-eyebrow" style="justify-content:center">Moje hodnoty</div>
        <h2 style="font-family:var(--serif)">Čo ma riadi <em>pri práci</em></h2>
    </div>
    <div class="zc-precoja-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:22px">
    <?php foreach([
        ['heart','Záujem','Vaša spokojnosť je môj úspech. Každý prípad riešim osobne.'],
        ['bulb','Odbornosť','Trh sledujem denne. Znalosti využívam v prospech klienta.'],
        ['bolt','Rýchlosť','Váš čas je cenný. Komunikujem promptne a procesy urýchľujem.'],
        ['handshake','Férovosť','Vždy poviem pravdu, aj keď nie je príjemná. Žiadne skryté poplatky.'],
    ] as [$ic,$t,$d]): ?>
    <div class="zc-card"><div class="zc-card-icon" style="color:var(--accent-txt)"><?php echo zc_svg($ic, 26); ?></div><h3 style="font-size:17px;margin-bottom:8px"><?php echo $t; ?></h3><p style="font-size:14px;color:var(--muted);margin:0;line-height:1.7"><?php echo $d; ?></p></div>
    <?php endforeach; ?>
    </div>
</div>
</section>

<!-- PONUKY -->
<section class="zc-section bg-white">
<div class="zc-container">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:48px;flex-wrap:wrap;gap:20px">
        <div><div class="zc-eyebrow">Aktuálne na trhu</div><h2 style="font-family:var(--serif)">Vybrané <em>nehnuteľnosti</em></h2></div>
        <a href="<?php echo home_url('/ponuky/'); ?>" class="zc-btn zc-btn-outline">Všetky ponuky →</a>
    </div>
    <?php echo do_shortcode('[property_grid per_page="3"]'); ?>
</div>
</section>

<!-- REFERENCIE -->
<section class="zc-section bg-section">
<div class="zc-container">
    <div style="text-align:center;max-width:560px;margin:0 auto 52px">
        <div class="zc-eyebrow" style="justify-content:center">Referencie</div>
        <h2 style="font-family:var(--serif)">Čo hovoria <em>klienti</em></h2>
    </div>
    <?php
    if (function_exists('zcr_table')) {
        // Dynamic reviews from plugin
        echo do_shortcode('[zc_reviews limit="6" cols="3"]');
    } else {
        // Fallback – hardcoded kým plugin nie je aktívny
        $fallback = [
            ['Jana a Peter K.','Predaj rodinného domu','Zdenka predala náš dom za 3 týždne za cenu, o akej sme ani nesnívali. Profesionálny prístup, skvelá komunikácia.'],
            ['Miroslav T.','Kúpa 3-izbového bytu','Pomohla nám nájsť presne to, čo sme hľadali. Ušetrila nám kopu času a stresu.'],
            ['Katarína L.','Prenájom bytu','Rýchle, bezproblémové. Zdenka všetko zorganizovala, stačilo podpísať.'],
        ];
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:22px">';
        foreach ($fallback as [$a,$m,$q]) {
            echo '<div class="zc-testimonial"><div class="zc-stars">★★★★★</div>';
            echo '<div class="zc-testimonial-quote">'.esc_html($q).'</div>';
            echo '<div class="zc-testimonial-author"><div class="zc-testimonial-avatar"></div>';
            echo '<div><div class="zc-testimonial-name">'.esc_html($a).'</div>';
            echo '<div class="zc-testimonial-meta">'.esc_html($m).'</div></div></div></div>';
        }
        echo '</div>';
    }
    ?>
</div>
</section>

<!-- CTA -->
<section style="background:var(--section);padding:100px 0;border-top:1px solid var(--border)">
<div class="zc-container zc-cta-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center">
    <div>
        <div class="zc-eyebrow">Nezáväzná konzultácia</div>
        <h2 style="font-family:var(--serif);margin-bottom:16px">Predávate alebo hľadáte <em>nový domov?</em></h2>
        <p style="color:var(--muted);font-size:16px;line-height:1.75;margin-bottom:32px">Prvá konzultácia je bezplatná a nezáväzná.</p>
        <?php foreach([
            ['<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>',$phone,"tel:".preg_replace('/[^0-9+]/','',$phone)],
            ['<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>','WhatsApp',"https://wa.me/".preg_replace('/[^0-9]/','',$wa)],
            ['<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>',$email,"mailto:".esc_attr($email)],
        ] as [$ic,$lbl,$href]): ?>
        <a href="<?php echo $href; ?>" class="zc-cta-contact" style="display:flex;align-items:center;gap:12px;color:var(--text);font-size:15px;text-decoration:none;margin-bottom:14px">
            <span class="zc-cta-contact-icon" style="width:44px;height:44px;background:var(--white);border:1px solid var(--border);color:var(--accent-dk);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .25s var(--ease)"><?php echo $ic; ?></span>
            <?php echo esc_html($lbl); ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="zc-cta-card" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:40px;box-shadow:var(--sh)">
        <h3 style="font-family:var(--serif);font-size:22px;margin-bottom:24px">Napíšte mi správu</h3>
        <form id="zcContactForm">
            <?php foreach([['name','text','Vaše meno *'],['email','email','E-mail *'],['phone','tel','Telefón']] as [$n,$t,$p]): ?>
            <div style="margin-bottom:12px"><input type="<?php echo $t; ?>" name="<?php echo $n; ?>" placeholder="<?php echo $p; ?>" class="zc-cta-input" style="background:var(--bg);border:1.5px solid var(--border);color:var(--text);padding:13px 16px;border-radius:var(--r-sm);width:100%;font-family:var(--sans);font-size:16px" <?php if(substr($p, -strlen('*')) === '*') echo 'required'; ?>></div>
            <?php endforeach; ?>
            <div style="margin-bottom:16px"><textarea name="message" placeholder="Správa" rows="4" class="zc-cta-input" style="background:var(--bg);border:1.5px solid var(--border);color:var(--text);padding:13px 16px;border-radius:var(--r-sm);width:100%;font-family:var(--sans);font-size:16px;resize:vertical"></textarea></div>
            <div id="zcFormMsg"></div>
            <button type="submit" class="zc-btn zc-btn-primary" style="width:100%;justify-content:center">Odoslať správu →</button>
        </form>
    </div>
</div>
</section>
<?php get_footer(); ?>
