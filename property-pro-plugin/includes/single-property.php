<?php
defined('ABSPATH') || exit;
// Pull header in TRANSPARENT mode – we overlay the hero
get_header();

require_once PROPERTY_PRO_PATH . 'includes/amenities.php';

$id          = get_the_ID();
$cover_id    = get_post_meta($id, '_property_cover_id', true);
$gallery_ids = get_post_meta($id, '_property_gallery_ids', true) ?: [];
$video_url   = get_post_meta($id, '_property_video_url', true);
$typ         = get_post_meta($id, '_property_typ', true);
$cena_raw    = get_post_meta($id, '_property_cena', true);
$lokalita    = get_post_meta($id, '_property_lokalita', true);
$plocha      = get_post_meta($id, '_property_plocha', true);
$pozemok     = get_post_meta($id, '_property_pozemok', true);
$spalne      = get_post_meta($id, '_property_spalne', true);
$kupelne     = get_post_meta($id, '_property_kupelne', true);
$wc          = get_post_meta($id, '_property_wc', true);
$poschodie   = get_post_meta($id, '_property_poschodie', true);
$rocnik      = get_post_meta($id, '_property_rocnik', true);
$stav        = get_post_meta($id, '_property_stav', true);
$amenities   = get_post_meta($id, '_property_amenities', true) ?: [];
$agent_id    = get_post_meta($id, '_property_agent_id', true) ?: get_post_field('post_author', $id);

// Auto-format price with €
function zc_format_price($raw) {
    if (!$raw) return '';
    $clean = trim($raw);
    // If it already contains € sign, return as-is
    if (strpos($clean, '€') !== false) return $clean;
    // Otherwise append €
    return $clean . ' €';
}
$cena = zc_format_price($cena_raw);

// Meno maklérky: z WP profilu priradenej maklérky (Users → Profil → Zobrazovať
// meno ako). Web tak zvládne aj viac maklérov – každá ponuka ukáže svojho.
$agent_name  = get_the_author_meta('display_name', $agent_id) ?: 'Realitná maklérka';
$agent_phone = get_user_meta($agent_id, 'property_phone', true) ?: '+421 907 579 742';
$agent_wa    = get_user_meta($agent_id, 'property_whatsapp', true) ?: '421907579742';
$agent_email = get_user_meta($agent_id, 'property_email', true) ?: get_the_author_meta('user_email', $agent_id);
$agent_photo = get_user_meta($agent_id, 'property_photo_id', true); // optional manual override
$agent_title = get_user_meta($agent_id, 'property_title', true) ?: 'Realitná maklérka';

$types     = ['predaj' => 'Na predaj', 'prenajom' => 'Na prenájom', 'pozemok' => 'Pozemok'];
$typ_label = $types[$typ] ?? 'Ponuka';
$typ_colors = ['predaj' => '#B8A47A', 'prenajom' => '#FFFFFF', 'pozemok' => '#6a9e77'];
$typ_color  = $typ_colors[$typ] ?? '#B8A47A';
$typ_text_color = $typ === 'pozemok' ? '#fff' : '#1C1A18';

// Robustne – žiadne prázdne/nulové/duplicitné obrázky
$raw_ids    = array_merge($cover_id ? [$cover_id] : [], is_array($gallery_ids) ? $gallery_ids : []);
$all_images = []; $images_json = [];
foreach ($raw_ids as $img_id) {
    $img_id = intval($img_id);
    if ($img_id <= 0) continue;
    $src = wp_get_attachment_image_src($img_id, 'large');
    if (!$src) continue;
    if (in_array($img_id, $all_images)) continue;
    $all_images[]  = $img_id;
    $images_json[] = $src[0];
}
$all_images  = array_values($all_images);
$images_json = array_values($images_json);
$total_images = count($all_images);
$all_amenities = get_property_amenities();
?>
<style>
/* ── HERO – overlaps header (negative margin-top) ── */
.pp-hero {
    position: relative;
    background: #111;
    overflow: hidden;
    /* Full viewport height including header area */
    height: 100vh;
    min-height: 500px;
    /* Pull up behind the header */
    margin-top: calc(-1 * var(--hh, 72px));
}
/* Bez fotiek stačí nižší hero – žiadna prázdna plocha na celú obrazovku */
.pp-hero--nophoto { height:62vh; min-height:420px; }
.pp-hero-track { display:flex; height:100%; transition:transform .6s cubic-bezier(.4,0,.2,1); will-change:transform; }
/* Each slide: absolute-positioned img fills 100% regardless of source size */
.pp-hero-slide { min-width:100%; height:100%; flex-shrink:0; position:relative; overflow:hidden; background:#111; }
.pp-hero-slide img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; transform-origin:center 40%; will-change:transform; }
.pp-hero-overlay {
    position:absolute; inset:0;
    background:linear-gradient(to bottom,rgba(0,0,0,.25) 0%,rgba(0,0,0,.0) 30%,rgba(0,0,0,.55) 72%,rgba(0,0,0,.82) 100%);
}
.pp-hero-info {
    position:absolute; bottom:0; left:0; right:0;
    padding:clamp(24px,4vw,52px) clamp(20px,5vw,60px) 52px;
    color:#fff;
}
.pp-hero-badge {
    display:inline-flex; align-items:center;
    background:<?php echo $typ_color ?>; color:<?php echo $typ_text_color ?>;
    padding:6px 16px; border-radius:50px;
    font-size:11px; font-weight:800; letter-spacing:1.2px; text-transform:uppercase;
    margin-bottom:14px; font-family:var(--sans,'DM Sans',sans-serif);
}
.pp-hero-title {
    font-size:clamp(22px,4vw,40px); font-weight:800; line-height:1.2;
    font-family:var(--serif,'Playfair Display',serif);
    color:#ffffff;
    text-shadow:0 2px 8px rgba(0,0,0,.6), 0 4px 24px rgba(0,0,0,.5);
    margin-bottom:12px;
}
.pp-hero-meta { display:flex; gap:18px; flex-wrap:wrap; font-size:14px; color:#fff; opacity:1; text-shadow:0 1px 6px rgba(0,0,0,.6); }

/* Nav arrows – glass */
.pp-hero-btn {
    position:absolute; top:50%; transform:translateY(-50%);
    width:50px; height:50px; border-radius:50%;
    background:rgba(255,255,255,.14); backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.22);
    color:#fff; font-size:24px; cursor:pointer; z-index:5;
    display:flex; align-items:center; justify-content:center;
    transition:all .2s; outline:none;
}
.pp-hero-btn:hover { background:rgba(255,255,255,.28); transform:translateY(-50%) scale(1.08); }
.pp-hero-prev { left:18px; } .pp-hero-next { right:18px; }
.pp-hero-counter {
    position:absolute; top:calc(var(--hh,72px) + 14px); right:18px;
    background:rgba(0,0,0,.38); backdrop-filter:blur(8px);
    color:#fff; padding:6px 14px; border-radius:50px;
    font-size:12px; font-weight:600; z-index:5; font-family:var(--sans,sans-serif);
}
body.admin-bar .pp-hero-counter { top:calc(var(--hh,72px) + 46px); }
.pp-hero-fav {
    position:absolute; top:calc(var(--hh,72px) + 14px); left:18px; z-index:6;
    width:44px; height:44px; border-radius:50%;
    background:rgba(255,255,255,.92); backdrop-filter:blur(8px);
    border:none; cursor:pointer; display:flex; align-items:center; justify-content:center;
    box-shadow:0 2px 12px rgba(0,0,0,.2); transition:transform .2s;
}
body.admin-bar .pp-hero-fav { top:calc(var(--hh,72px) + 46px); }
.pp-hero-fav svg { stroke:#6B6560; fill:none; transition:fill .25s,stroke .25s; }
.pp-hero-fav:hover { transform:scale(1.1); }
.pp-hero-fav:hover svg { stroke:#e2476d; }
.pp-hero-fav.is-fav svg { fill:#e2476d; stroke:#e2476d; }
.pp-hero-dots {
    position:absolute; bottom:18px; left:50%; transform:translateX(-50%);
    display:flex; gap:7px; z-index:5; white-space:nowrap;
}
.pp-hero-dot {
    width:7px; height:7px; border-radius:50%;
    background:rgba(255,255,255,.35); border:none; cursor:pointer;
    transition:all .3s; padding:0;
}
.pp-hero-dot.active { background:#fff; width:22px; border-radius:4px; }

/* ── PAGE BODY ── */
.pp-body {
    max-width:1300px; margin:0 auto;
    padding:48px 40px 80px;
    display:grid; grid-template-columns:1fr 360px; gap:32px;
    align-items:start;
    grid-template-rows: auto;
}
@media(max-width:1080px){
    .pp-body { grid-template-columns:1fr; padding:32px 20px 60px; }
    /* Na mobile cena + maklérka hneď pod hero, nie až úplne dole za všetkými detailmi */
    .pp-body > aside { order:-1; }
    .pp-body > main  { order:0; }
}
@media(max-width:600px)  { .pp-body { padding:24px 16px 48px; } }

.pp-card {
    background:#fff; border-radius:14px;
    box-shadow:0 4px 20px rgba(60,50,30,.09);
    padding:28px; margin-bottom:22px;
}
.pp-card:last-child { margin-bottom:0; }

.pp-sec-title {
    font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase;
    color:var(--muted,#6B6560); margin-bottom:18px;
    display:flex; align-items:center; gap:8px; font-family:var(--sans,sans-serif);
}
.pp-sec-title::after { content:''; flex:1; height:1px; background:#E2DACE; }

/* Specs – flex s grow: bunky sa roztiahnu a vždy vyplnia riadok (žiadne prázdne pole) */
.pp-specs {
    display:flex; flex-wrap:wrap;
    gap:1px; background:#E2DACE; border-radius:12px; overflow:hidden;
}
.pp-spec {
    flex:1 1 130px; min-width:110px;
    background:#fff; padding:18px 12px;
    display:flex; flex-direction:column; align-items:center; gap:5px; text-align:center;
    transition:background .2s;
}
.pp-spec:hover { background:#FCFBF8; }
.pp-spec-icon { font-size:20px; }
.pp-spec-value { font-size:19px; font-weight:800; color:#1C1A18; line-height:1; font-family:var(--serif,serif); }
.pp-spec-label { font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6B6560; }

/* Description */
.pp-desc { font-size:15px; line-height:1.85; color:#2C2C2C; }
.pp-desc p { margin-bottom:14px; }

/* Amenities */
.am-group { margin-bottom:20px; }
.am-group:last-child { margin-bottom:0; }
.am-group-hd {
    font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
    color:#6B6560; margin-bottom:10px; padding:5px 10px;
    background:#F5F1EA; border-radius:6px; display:inline-block; font-family:var(--sans,sans-serif);
}
.am-tags { display:flex; flex-wrap:wrap; gap:8px; }
.am-tag {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 12px; background:#F5F1EA;
    border:1px solid #E2DACE; border-radius:8px;
    font-size:13px; color:#2C2C2C; transition:all .2s; cursor:default;
}
.am-tag::before { content:'✓'; color:#B8A47A; font-weight:800; font-size:10px; }
.am-tag:hover { border-color:#B8A47A; background:#fff; transform:translateY(-1px); }

/* CTA form */
.pp-cta {
    background:#fff; border:1px solid #E2DACE;
    border-radius:14px; padding:28px; margin-bottom:22px; color:#2C2C2C;
    box-shadow:0 4px 20px rgba(60,50,30,.09);
}
.pp-cta-title { font-size:17px; font-weight:700; margin-bottom:6px; font-family:var(--serif,serif); color:#1C1A18; }
.pp-cta-sub { font-size:13px; color:#6B6560; margin-bottom:20px; }
.pp-cta-form { display:flex; flex-direction:column; gap:10px; }
.pp-cta-input {
    padding:11px 14px; border-radius:9px;
    border:1.5px solid #E2DACE; background:#FCFBF8;
    color:#2C2C2C; font-size:14px; font-family:var(--sans,sans-serif); transition:border .2s, box-shadow .2s;
}
.pp-cta-input::placeholder { color:#B0A898; }
.pp-cta-input:focus { outline:none; border-color:#B8A47A; background:#fff; box-shadow:0 0 0 3px rgba(184,164,122,.12); }
.pp-cta-btn {
    padding:13px; border-radius:9px; background:#B8A47A; color:#1C1A18;
    border:none; font-size:14px; font-weight:700; cursor:pointer; transition:all .2s;
    font-family:var(--sans,sans-serif);
}
.pp-cta-btn:hover { filter:brightness(1.1); transform:translateY(-1px); }

/* Video */
.pp-video { border-radius:14px; overflow:hidden; aspect-ratio:16/9; margin-bottom:22px; }
.pp-video.vertical { aspect-ratio:9/16; max-width:340px; margin-left:auto; margin-right:auto; }
.pp-video iframe { width:100%; height:100%; border:none; display:block; }

/* Masonry gallery */
.pp-gallery { columns:3 180px; column-gap:10px; }
.pp-gal-item { break-inside:avoid; margin-bottom:10px; border-radius:10px; overflow:hidden; cursor:pointer; position:relative; }
.pp-gal-item img { width:100%; height:auto; display:block; transition:transform .35s; }
.pp-gal-item::after { content:''; position:absolute; inset:0; background:rgba(0,0,0,0); transition:background .3s; border-radius:10px; }
.pp-gal-item:hover img { transform:scale(1.04); }
.pp-gal-item:hover::after { background:rgba(0,0,0,.15); }
@media(max-width:600px){ .pp-gallery{columns:2 140px} }

/* ── SIDEBAR ── */
.pp-sidebar { display:flex; flex-direction:column; gap:18px; position:sticky; top:calc(var(--hh,72px) + 20px); align-self:start; }
@media(max-width:1080px){ .pp-sidebar { position:static; } }

.pp-price-card {
    background:#fff; border-radius:14px;
    box-shadow:0 12px 40px rgba(60,50,30,.14); overflow:hidden;
}
.pp-price-top {
    padding:22px 24px 18px;
    background:#F5F1EA; border-bottom:2px solid #B8A47A; color:#1C1A18;
}
.pp-price-lbl { font-size:10px; text-transform:uppercase; letter-spacing:1px; color:#6B6560; margin-bottom:5px; font-family:var(--sans,sans-serif); }
.pp-price-val {
    font-size:30px; font-weight:900; color:#7C5E33; line-height:1;
    letter-spacing:-1px; font-family:var(--serif,serif);
}
.pp-price-body { padding:16px 18px 18px; display:flex; flex-direction:column; gap:10px; }

.pp-btn {
    display:flex; align-items:center; justify-content:center; gap:10px;
    padding:13px 18px; border-radius:10px;
    font-size:13px; font-weight:700; text-decoration:none;
    cursor:pointer; border:none; transition:all .2s; width:100%;
    font-family:var(--sans,sans-serif);
}
.pp-btn:hover { transform:translateY(-2px); }
/* Farbu textu držíme aj v :hover – inak ju prebije globálne a:hover (zlatá) a text zmizne */
.pp-btn-call, .pp-btn-call:hover  { background:#B8A47A; color:#1C1A18; }
.pp-btn-call:hover  { background:#9A8660; box-shadow:0 6px 18px rgba(184,164,122,.4); }
.pp-btn-wa, .pp-btn-wa:hover    { background:#22c55e; color:#fff; }
.pp-btn-wa:hover    { background:#16a34a; box-shadow:0 6px 18px rgba(34,197,94,.3); }
.pp-btn-email { background:#F5F1EA; color:#2C2C2C; border:1.5px solid #E2DACE; }
.pp-btn-email:hover { border-color:#B8A47A; color:#2C2C2C; background:#fff; }

.pp-agent { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(60,50,30,.09); padding:22px; text-align:center; }
.pp-agent-avatar { width:72px; height:72px; border-radius:50%; margin:0 auto 14px; overflow:hidden; background:#F5F1EA; display:flex; align-items:center; justify-content:center; font-size:28px; box-shadow:0 0 0 3px #fff,0 0 0 5px #B8A47A; }
.pp-agent-avatar img { width:100%; height:100%; object-fit:cover; }
.pp-agent-name { font-size:15px; font-weight:700; color:#1C1A18; margin-bottom:3px; font-family:var(--serif,serif); }
.pp-agent-role { font-size:11px; color:#6B6560; text-transform:uppercase; letter-spacing:.5px; }

/* Lightbox – plynulé otvorenie namiesto skokového display:none */
.pp-lb { display:flex; visibility:hidden; opacity:0; position:fixed; inset:0; background:rgba(5,5,10,.96); z-index:99999; align-items:center; justify-content:center; transition:opacity .28s ease, visibility 0s .28s; }
.pp-lb.open { visibility:visible; opacity:1; transition:opacity .28s ease; }
.pp-lb-img { max-width:92vw; max-height:88vh; border-radius:10px; object-fit:contain; box-shadow:0 20px 60px rgba(0,0,0,.5); transform:scale(.96); transition:transform .3s cubic-bezier(.22,.9,.36,1); }
.pp-lb.open .pp-lb-img { transform:scale(1); }
.pp-lb-close { position:absolute; top:14px; right:14px; width:42px; height:42px; background:rgba(255,255,255,.1); backdrop-filter:blur(8px); color:#fff; border:none; border-radius:50%; font-size:20px; cursor:pointer; }
.pp-lb-side { position:absolute; top:50%; transform:translateY(-50%); width:48px; height:48px; background:rgba(255,255,255,.1); backdrop-filter:blur(8px); color:#fff; border:none; border-radius:50%; font-size:22px; cursor:pointer; }
.pp-lb-side:hover { background:rgba(255,255,255,.2); }
.pp-lb-prev { left:14px; } .pp-lb-next { right:14px; }
.pp-lb-info { position:absolute; bottom:16px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,.5); font-size:13px; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .pp-hero { height: 100vh; height: 100svh; min-height: 480px; }
    .pp-hero-info { padding: 20px; }
    .pp-hero-title { font-size: clamp(18px,5vw,26px); margin-bottom:8px; }
    .pp-hero-meta { font-size:13px; gap:12px; }
    .pp-hero-btn { width:38px; height:38px; font-size:20px; }
    .pp-hero-prev { left:10px; } .pp-hero-next { right:10px; }
    .pp-hero-dots { left:50%; transform:translateX(-50%); bottom:14px; }
    .pp-body { padding:24px 16px 48px; gap:20px; }
    .pp-card { padding:20px 18px; }
    .pp-specs .pp-spec { flex-basis: calc(33.333% - 1px); }
    .pp-price-val { font-size:24px; }
    .pp-btn { padding:11px 12px; font-size:12px; }
    .pp-cta { padding:20px 16px; }
    .pp-gallery { columns: 2 100px; }
}
@media (max-width: 480px) {
    .pp-hero { height: 100vh; height: 100svh; min-height: 380px; }
    .pp-specs .pp-spec { flex-basis: calc(50% - 1px); }
    .pp-hero-counter { font-size:11px; padding:4px 10px; }
}
</style>

<!-- TRANSPARENT HEADER SCRIPT – must run before scroll -->
<script>
(function(){
    var hdr = document.getElementById('zcHeader');
    if (!hdr) return;
    hdr.classList.add('transparent');
    window.addEventListener('scroll', function() {
        var hero = document.getElementById('ppHero');
        var heroBottom = hero ? hero.getBoundingClientRect().bottom : 0;
        if (heroBottom <= 0) {
            hdr.classList.remove('transparent');
        } else {
            hdr.classList.add('transparent');
        }
    }, { passive: true });
})();



</script>

<!-- ═══ HERO ═══ -->
<div class="pp-hero<?php echo $all_images ? '' : ' pp-hero--nophoto' ?>" id="ppHero">
    <?php if ($all_images): ?>
    <div class="pp-hero-track" id="ppTrack">
        <?php foreach ($all_images as $img_id): ?>
        <div class="pp-hero-slide">
            <?php echo wp_get_attachment_image($img_id, 'large', false, ['loading'=>'eager','decoding'=>'async']); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Bez fotky: nižší zlatý hero namiesto tmavej 100vh plochy -->
    <div style="height:100%;background:linear-gradient(135deg,#B8A47A,#8F7B55);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.55);font-size:72px;">🏠</div>
    <?php endif; ?>
    <div class="pp-hero-overlay"></div>
    <button class="zc-fav-btn pp-hero-fav" data-id="<?php echo $id ?>" title="Pridať do obľúbených" onclick="zcToggleFav(this,<?php echo $id ?>)" aria-label="Pridať do obľúbených">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </button>
    <div class="pp-hero-info">
        <?php if ($typ): ?><div class="pp-hero-badge"><?php echo $typ_label ?></div><?php endif; ?>
        <h1 class="pp-hero-title"><?php the_title() ?></h1>
        <?php if ($lokalita || $plocha || $spalne): ?>
        <div class="pp-hero-meta">
            <?php if ($lokalita): ?><span>📍 <?php echo esc_html($lokalita) ?></span><?php endif; ?>
            <?php if ($plocha): ?><span>📐 <?php echo esc_html($plocha) ?> m²</span><?php endif; ?>
            <?php if ($spalne): ?><span>🚪 <?php echo esc_html($spalne) ?> izby</span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($total_images > 1): ?>
    <button class="pp-hero-btn pp-hero-prev" onclick="ppPrev()" aria-label="Predošlá">&#8249;</button>
    <button class="pp-hero-btn pp-hero-next" onclick="ppNext()" aria-label="Ďalšia">&#8250;</button>
    <div class="pp-hero-counter"><span id="ppCur">1</span> / <?php echo $total_images ?></div>
    <?php $show_dots = ($total_images <= 5); ?>
    <div class="pp-hero-dots" id="ppDots"<?php echo $show_dots ? '' : ' style="display:none"'; ?>>
        <?php for ($i = 0; $i < $total_images; $i++): ?>
        <button class="pp-hero-dot<?php echo $i === 0 ? ' active' : '' ?>" onclick="ppGo(<?php echo $i ?>)" aria-label="Fotka <?php echo $i+1 ?>"></button>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div>

<!-- ═══ BODY ═══ -->
<div class="pp-body">
<main>

    <?php $has_specs = $plocha||$pozemok||$spalne||$kupelne||$wc||$poschodie||$rocnik||$stav; ?>
    <?php if ($has_specs): ?>
    <div class="pp-card">
        <div class="pp-sec-title">Parametre nehnuteľnosti</div>
        <div class="pp-specs">
            <?php $specs=[['📐','Úžitk. plocha','m²',$plocha],['🌍','Pozemok','m²',$pozemok],['🚪','Izby','',$spalne],['🚿','Kúpeľne','',$kupelne],['🚽','WC','',$wc],['🏢','Poschodie','',$poschodie],['📅','Ročník','',$rocnik],['🔨','Stav','',$stav]];
            foreach($specs as [$icon,$lbl,$unit,$val]):
                if(!$val) continue; ?>
            <div class="pp-spec">
                <span class="pp-spec-icon"><?php echo $icon ?></span>
                <span class="pp-spec-value" <?php if(strlen((string)$val)>5)echo 'style="font-size:13px"' ?>><?php echo esc_html($val) ?><?php if($unit)echo '<small style="font-size:10px;color:#888;margin-left:2px">'.$unit.'</small>' ?></span>
                <span class="pp-spec-label"><?php echo $lbl ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (get_the_content()): ?>
    <div class="pp-card">
        <div class="pp-sec-title">Popis</div>
        <div class="pp-desc"><?php the_content() ?></div>
    </div>
    <?php endif; ?>

    <?php if ($amenities): ?>
    <div class="pp-card">
        <div class="pp-sec-title">Vybavenie a okolie</div>
        <?php foreach ($all_amenities as $cat):
            $cat_items = array_intersect_key($cat['items'], array_flip($amenities));
            if (!$cat_items) continue; ?>
        <div class="am-group">
            <div class="am-group-hd"><?php echo $cat['label'] ?></div>
            <div class="am-tags">
                <?php foreach ($cat_items as $label): ?>
                <span class="am-tag"><?php echo esc_html($label) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="pp-cta">
        <div class="pp-cta-title">📩 Mám záujem o túto nehnuteľnosť</div>
        <div class="pp-cta-sub">Zanechajte kontakt a ozveme sa vám čo najskôr</div>
        <?php if (isset($_POST['cta_send']) && wp_verify_nonce($_POST['cta_nonce']??'','cta_form')): ?>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;padding:14px;border-radius:10px;text-align:center;color:#15803d">✅ Správa odoslaná!</div>
            <?php wp_mail($agent_email,'Záujem o: '.get_the_title(),"Meno: ".sanitize_text_field($_POST['cta_name']??'')."\nTel: ".sanitize_text_field($_POST['cta_phone']??'')."\n\n".sanitize_textarea_field($_POST['cta_msg']??'')."\n\n".get_permalink()); ?>
        <?php else: ?>
        <form method="post" class="pp-cta-form">
            <?php wp_nonce_field('cta_form','cta_nonce') ?>
            <input name="cta_name" class="pp-cta-input" placeholder="Vaše meno *" required>
            <input name="cta_phone" class="pp-cta-input" placeholder="Telefónne číslo">
            <textarea name="cta_msg" class="pp-cta-input" rows="3" placeholder="Správa"></textarea>
            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
                <label style="display:flex;align-items:flex-start;gap:8px;font-size:11px;color:#6B6560;cursor:pointer;line-height:1.6">
                    <input type="checkbox" name="gdpr" required style="margin-top:2px;accent-color:#B8A47A;flex-shrink:0">
                    <span>Súhlasím so <a href="/ochrana-osobnych-udajov/" style="color:#7C5E33">spracovaním OÚ</a>. *</span>
                </label>
                <label style="display:flex;align-items:flex-start;gap:8px;font-size:11px;color:#6B6560;cursor:pointer;line-height:1.6">
                    <input type="checkbox" name="newsletter" style="margin-top:2px;accent-color:#B8A47A;flex-shrink:0">
                    <span>Chcem dostávať novinky a nové ponuky na e-mail.</span>
                </label>
            </div>
            <button type="submit" name="cta_send" class="pp-cta-btn">Odoslať dopyt →</button>
        </form>
        <?php endif; ?>
    </div>

    <?php
    // Univerzálny embed – YouTube (aj Shorts), Vimeo; Shorts sa zobrazí zvislo
    $pp_video = function_exists('zc_video_embed') ? zc_video_embed($video_url) : [];
    if (!empty($pp_video['url'])): ?>
    <div class="pp-video<?php echo !empty($pp_video['vertical']) ? ' vertical' : '' ?>">
        <iframe src="<?php echo esc_url($pp_video['url']) ?>" allowfullscreen loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"></iframe>
    </div>
    <?php endif; ?>

    <?php if (count($all_images) > 1): ?>
    <div class="pp-card">
        <div class="pp-sec-title">Galéria</div>
        <div class="pp-gallery">
            <?php foreach ($all_images as $i => $img_id): ?>
            <div class="pp-gal-item" onclick="ppLbOpen(<?php echo $i ?>)">
                <?php echo wp_get_attachment_image($img_id,'medium',false,['loading'=>'lazy']) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- aside sa musí natiahnuť na výšku riadku gridu, inak sticky sidebar nemá kade cestovať -->
<aside style="align-self:stretch;min-width:0;">
<div class="pp-sidebar">
    <div class="pp-price-card">
        <div class="pp-price-top">
            <div class="pp-price-lbl">Cena</div>
            <?php
            // Format price with space as thousands separator
            $cena_num = preg_replace('/[^0-9]/', '', $cena);
            if ($cena_num && is_numeric($cena_num)) {
                $cena_fmt = number_format(intval($cena_num), 0, ',', ' ') . ' €';
            } else {
                $cena_fmt = $cena;
            }
            ?>
            <div class="pp-price-val"<?php if (!$cena_fmt) echo ' style="font-size:20px;letter-spacing:0"'; ?>><?php echo esc_html($cena_fmt ?: 'Cena dohodou'); ?></div>
        </div>
        <div class="pp-price-body">
            <a href="tel:<?php echo preg_replace('/[^0-9+]/','',$agent_phone) ?>" class="pp-btn pp-btn-call">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
                <?php echo esc_html($agent_phone) ?>
            </a>
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/','',$agent_wa) ?>?text=<?php echo urlencode('Záujem o: '.get_the_title().' '.get_permalink()) ?>" target="_blank" class="pp-btn pp-btn-wa">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp
            </a>
            <a href="mailto:<?php echo esc_attr($agent_email) ?>?subject=<?php echo urlencode('Záujem: '.get_the_title()) ?>" class="pp-btn pp-btn-email">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
                Napísať email
            </a>
        </div>
    </div>
    <div class="pp-agent">
        <div class="pp-agent-avatar">
            <?php
            // Fotka maklérky z jej WP profilu: nahratá profilová fotka
            // (Users → Profil → Profilová fotka), inak Gravatar podľa e-mailu.
            if ($agent_photo) echo wp_get_attachment_image($agent_photo, 'thumbnail', false, ['alt' => esc_attr($agent_name)]);
            else echo get_avatar($agent_id, 144);
            ?>
        </div>
        <div class="pp-agent-name"><?php echo esc_html($agent_name) ?></div>
        <div class="pp-agent-role"><?php echo esc_html($agent_title) ?></div>
    </div>
</div>
</aside>
</div>

<!-- LIGHTBOX -->
<div class="pp-lb" id="ppLb">
    <button class="pp-lb-close" onclick="ppLbClose()">✕</button>
    <button class="pp-lb-side pp-lb-prev" onclick="ppLbPrev()">&#8249;</button>
    <button class="pp-lb-side pp-lb-next" onclick="ppLbNext()">&#8250;</button>
    <img class="pp-lb-img" id="ppLbImg" src="" alt="">
    <div class="pp-lb-info" id="ppLbInfo"></div>
</div>

<script>
(function(){
/* CAROUSEL */
var cur=0,total=<?php echo $total_images ?>;
var track=document.getElementById('ppTrack');
var dots=document.querySelectorAll('.pp-hero-dot');
var counter=document.getElementById('ppCur');
function ppUpd(){
    if(track)track.style.transform='translateX(-'+(cur*100)+'%)';
    dots.forEach(function(d,i){d.classList.toggle('active',i===cur)});
    if(counter)counter.textContent=cur+1;
}
window.ppNext=function(){cur=(cur+1)%total;ppUpd()};
window.ppPrev=function(){cur=(cur-1+total)%total;ppUpd()};
window.ppGo=function(i){cur=i;ppUpd()};

// Touch/drag
var hero=document.getElementById('ppHero'),sx=0,sy=0,dragging=false;
if(hero&&total>1){
    hero.addEventListener('mousedown',function(e){sx=e.clientX;sy=e.clientY;dragging=true});
    document.addEventListener('mouseup',function(e){
        if(!dragging)return;dragging=false;
        var dx=sx-e.clientX;
        if(Math.abs(dx)>60&&Math.abs(sy-e.clientY)<60){dx>0?ppNext():ppPrev()}
    });
    hero.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
    hero.addEventListener('touchend',function(e){
        var dx=sx-e.changedTouches[0].clientX;
        if(Math.abs(dx)>50){dx>0?ppNext():ppPrev()}
    });
    setInterval(function(){ppNext()},7000);
}

/* Zoom hero fotky pri scrolle (ako na úvodnej stránke) */
if(hero && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    var zoomImgs=hero.querySelectorAll('.pp-hero-slide img');
    var zTick=false;
    function ppZoom(){
        var h=hero.offsetHeight||1;
        var p=Math.min(Math.max(window.scrollY/h,0),1);
        var s='scale('+(1+p*0.18).toFixed(4)+')';
        zoomImgs.forEach(function(im){im.style.transform=s});
        zTick=false;
    }
    window.addEventListener('scroll',function(){if(!zTick){requestAnimationFrame(ppZoom);zTick=true}},{passive:true});
    ppZoom();
}

/* LIGHTBOX */
var imgs=<?php echo json_encode($images_json) ?>;
var lbIdx=0;
function lbSet(i){
    document.getElementById('ppLbImg').src=imgs[i];
    document.getElementById('ppLbInfo').textContent=(i+1)+' / '+imgs.length;
}
window.ppLbOpen=function(i){lbIdx=i;lbSet(i);document.getElementById('ppLb').classList.add('open');document.body.style.overflow='hidden'};
window.ppLbClose=function(){document.getElementById('ppLb').classList.remove('open');document.body.style.overflow=''};
window.ppLbNext=function(){lbIdx=(lbIdx+1)%imgs.length;lbSet(lbIdx)};
window.ppLbPrev=function(){lbIdx=(lbIdx-1+imgs.length)%imgs.length;lbSet(lbIdx)};
document.getElementById('ppLb').addEventListener('click',function(e){if(e.target===this)ppLbClose()});
document.addEventListener('keydown',function(e){
    var lb=document.getElementById('ppLb');
    if(!lb.classList.contains('open'))return;
    if(e.key==='Escape')ppLbClose();
    if(e.key==='ArrowRight')ppLbNext();
    if(e.key==='ArrowLeft')ppLbPrev();
});
})();



</script>

<script>
/* Sticky sidebar handled by CSS */
</script>
<?php get_footer(); ?>