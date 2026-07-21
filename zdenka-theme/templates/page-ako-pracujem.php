<?php /* Template Name: Ako pracujem */
defined('ABSPATH') || exit;
get_header();
if (have_posts()) { while (have_posts()) { the_post(); } }

// Photos + video from Customizer
$foto_1 = get_theme_mod('zc_apfoto1','');
$foto_2 = get_theme_mod('zc_apfoto2','');
$video  = get_theme_mod('zc_apvideo','');

$video_data = function_exists('zc_video_embed') ? zc_video_embed($video) : [];
$embed      = $video_data['url'] ?? '';
$embed_vert = !empty($video_data['vertical']);
?>

<style>
/* ── Hero ── */
.ap-hero{background:var(--section);padding:72px 0 56px;text-align:center}
.ap-hero h1{font-family:var(--serif);font-size:clamp(28px,4vw,48px);margin-bottom:16px}
.ap-hero p{font-size:17px;color:var(--muted);max-width:600px;margin:0 auto;line-height:1.8}

/* ── Services grid ── */
.ap-services{padding:80px 0;background:var(--white)}
/* 6-stĺpcový grid, karta = span 2 (t.j. 3 na riadok). Osirotené karty v
   poslednom riadku sa roztiahnu: 2 zvyšné → 50/50, 1 zvyšná → celá šírka. */
.ap-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:24px}
.ap-grid>.ap-card{grid-column:span 2}
.ap-grid>.ap-card:nth-child(3n+1):nth-last-child(2),
.ap-grid>.ap-card:nth-child(3n+2):nth-last-child(1){grid-column:span 3}
.ap-grid>.ap-card:nth-child(3n+1):nth-last-child(1){grid-column:span 6}
@media(max-width:900px){
    .ap-grid{grid-template-columns:repeat(2,1fr)}
    .ap-grid>.ap-card{grid-column:auto !important}
    .ap-grid>.ap-card:nth-child(odd):nth-last-child(1){grid-column:1/-1 !important}
}
@media(max-width:560px){.ap-grid{grid-template-columns:1fr}.ap-grid>.ap-card{grid-column:auto !important}}

.ap-card{background:var(--white);border:1px solid var(--border);border-radius:16px;
    padding:28px 24px;transition:transform .25s,box-shadow .25s;display:flex;flex-direction:column;gap:14px}
.ap-card:hover{transform:translateY(-4px);box-shadow:var(--sh)}
.ap-card-num{width:38px;height:38px;border-radius:50%;background:var(--accent);
    color:var(--dark);font-family:var(--serif);font-size:17px;font-weight:800;
    display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ap-card-icon{font-size:28px}
.ap-card-title{font-family:var(--serif);font-size:19px;font-weight:700;color:var(--dark);line-height:1.3}
.ap-card-text{font-size:14px;color:var(--muted);line-height:1.8;flex:1}
.ap-card-img{width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:10px;margin-top:4px}

/* ── Photo sections ── */
.ap-section{padding:72px 0}
.ap-section:nth-child(even){background:var(--section)}
.ap-2col{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.ap-2col.reverse{direction:rtl}
.ap-2col.reverse > *{direction:ltr}
.ap-photo{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:16px;
    box-shadow:var(--sh-lg);display:block}
.ap-photo-placeholder{width:100%;aspect-ratio:4/3;background:var(--section);
    border-radius:16px;display:flex;align-items:center;justify-content:center;
    flex-direction:column;gap:10px;border:2px dashed var(--border);color:var(--muted)}

/* ── Video ── */
.ap-video-wrap{position:relative;padding-bottom:56.25%;height:0;overflow:hidden;
    border-radius:16px;box-shadow:var(--sh-lg)}
.ap-video-wrap iframe{position:absolute;inset:0;width:100%;height:100%;border:none}
/* Zvislý formát pre YouTube Shorts (9:16), vycentrovaný a s rozumnou šírkou */
.ap-video-wrap.vertical{max-width:360px;margin:0 auto;padding-bottom:177.78%}
.ap-video-placeholder{aspect-ratio:16/9;background:var(--section);border:2px dashed var(--border);border-radius:16px;
    display:flex;align-items:center;justify-content:center;flex-direction:column;
    gap:12px;color:var(--muted)}

/* ── Process steps ── */
.ap-step{display:flex;gap:20px;align-items:flex-start;padding:20px 0;
    border-bottom:1px solid var(--border)}
.ap-step:last-child{border-bottom:none}
.ap-step-n{width:44px;height:44px;border-radius:50%;background:var(--accent);
    color:var(--dark);font-family:var(--serif);font-size:18px;font-weight:800;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}

/* ── CTA ── */
.ap-cta{background:var(--section);border-top:1px solid var(--border);padding:80px 0;text-align:center}

@media(max-width:768px){
    .ap-2col,.ap-2col.reverse{grid-template-columns:1fr;gap:28px;direction:ltr}
    .ap-section{padding:48px 0}
    .ap-services{padding:48px 0}
    .ap-hero{padding:48px 0 36px}
}
</style>

<!-- HERO -->
<div class="ap-hero">
    <div class="zc-container" style="max-width:700px">
        <div class="zc-eyebrow" style="justify-content:center">Postup spolupráce</div>
        <h1>Ako <em>pracujem</em></h1>
        <p>Transparentný, overený proces – od prvého stretnutia po odovzdanie kľúčov. Každý krok robím osobne a vždy v záujme klienta.</p>
    </div>
</div>

<!-- SERVICES GRID -->
<section class="ap-services">
<div class="zc-container">
    <div style="text-align:center;max-width:560px;margin:0 auto 48px">
        <div class="zc-eyebrow" style="justify-content:center">Čo robím pre vás</div>
        <h2 style="font-family:var(--serif)">Kompletný servis <em>v každom kroku</em></h2>
    </div>
    <div class="ap-grid">

        <?php
        $services = [
            ['📷','Profesionálne fotografie',
             'Prvý dojem rozhoduje. Vašu nehnuteľnosť zachytíme tak, aby vynikla medzi ostatnými ponukami. Spolupracujem s profesionálnym fotografom, ktorý dokáže vyzdvihnúť jej priestor, atmosféru a potenciál.',
             $foto_1],
            ['🎬','Video prehliadka',
             'Video dokáže preniesť emóciu aj atmosféru priestoru. Pripravíme modernú video prezentáciu, ktorá nehnuteľnosť predstaví prirodzene, atraktívne a pomôže osloviť širší okruh záujemcov.',
             ''],
            ['✍️','Copywriting',
             'Každá nehnuteľnosť má svoj príbeh. Vytvorím profesionálny text inzerátu, ktorý jasne vyzdvihne výhody nehnuteľnosti, osloví správnych kupujúcich a podporí výsledok predaja.',
             ''],
            ['📣','Moderný marketing a inzercia',
             'Vašu nehnuteľnosť prezentujem cielene a efektívne – na realitných portáloch, sociálnych sieťach, v online reklame aj medzi overenými kontaktmi z databázy. Cieľom nie je len zobrazenie, ale oslovenie správneho kupujúceho.',
             $foto_2],
            ['⚖️','Právne služby a katastrálny servis',
             'Zabezpečím kompletný právny servis spojený s prevodom nehnuteľnosti – prípravu zmluvnej dokumentácie, katastrálny servis aj koordináciu jednotlivých krokov až po úspešné odovzdanie. Chránim záujmy všetkých zúčastnených strán.',
             ''],
        ];
        foreach ($services as $i => [$icon, $title, $text, $foto]):
        ?>
        <div class="ap-card zc-card">
            <div class="ap-card-num"><?php echo $i+1 ?></div>
            <div class="ap-card-title"><span class="ap-card-icon"><?php echo $icon ?></span> <?php echo $title ?></div>
            <div class="ap-card-text"><?php echo esc_html($text) ?></div>
            <?php if ($foto): ?>
            <img src="<?php echo esc_url($foto) ?>" alt="<?php echo esc_attr($title) ?>" class="ap-card-img" loading="lazy">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>
</div>
</section>

<!-- VIDEO SECTION -->
<?php if ($embed || current_user_can('manage_options')): ?>
<section style="padding:72px 0;background:var(--white)">
<div class="zc-container" style="max-width:900px">
    <div style="text-align:center;margin-bottom:40px">
        <div class="zc-eyebrow" style="justify-content:center">Ukážka práce</div>
        <h2 style="font-family:var(--serif)">Video <em>prehliadka</em></h2>
        <p style="color:var(--muted);max-width:520px;margin:12px auto 0">Pozrite si, ako vyzerá naša video prezentácia nehnuteľnosti.</p>
    </div>
    <?php if ($embed): ?>
    <div class="ap-video-wrap<?php echo $embed_vert ? ' vertical' : '' ?>">
        <iframe src="<?php echo esc_url($embed) ?>" title="Video prehliadka"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
            allowfullscreen loading="lazy"></iframe>
    </div>
    <?php elseif (current_user_can('manage_options')): ?>
    <div class="ap-video-placeholder">
        <div style="font-size:40px">🎬</div>
        <div style="font-size:14px">Nastav video: <strong>Vzhľad → Prispôsobiť → Ako pracujem → Video URL</strong><br><small style="opacity:.7">Funguje bežné video aj YouTube Shorts.</small></div>
    </div>
    <?php endif; ?>
</div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="ap-cta">
<div class="zc-container" style="max-width:600px;text-align:center">
    <div class="zc-eyebrow" style="justify-content:center">Nezáväzná konzultácia</div>
    <h2 style="font-family:var(--serif);margin-bottom:16px">Začnime <em>spolupracovať</em></h2>
    <p style="color:var(--muted);font-size:16px;line-height:1.8;margin-bottom:32px">
        Prvá konzultácia je bezplatná a nezáväzná. Rada sa s vami stretnem a preberieme vaše možnosti.
    </p>
    <a href="<?php echo home_url('/kontakt/'); ?>" class="zc-btn zc-btn-primary">Kontaktovať →</a>
</div>
</section>

<?php get_footer(); ?>
