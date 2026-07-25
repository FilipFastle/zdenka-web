<?php
/**
 * Stránka 404 – neexistujúca adresa.
 */
defined('ABSPATH') || exit;
get_header();

$zc_404_photo = function_exists('zc_photo') ? (zc_photo('card') ?: zc_photo('portrait')) : '';
$zc_404_name  = function_exists('zc_agent') ? zc_agent('name', 'Mgr. Zdenka Cibuľová') : '';
$zc_404_phone = function_exists('zc_agent') ? zc_agent('phone', '') : '';
?>
<style>
.e404{background:var(--bg);min-height:70vh;display:flex;align-items:center;padding:72px 24px 96px}
.e404-in{max-width:720px;margin:0 auto;text-align:center}
.e404-num{font-family:var(--serif);font-size:clamp(84px,16vw,168px);font-weight:800;line-height:.9;color:var(--dark);letter-spacing:-4px;margin-bottom:6px}
.e404-num span{color:var(--accent)}
.e404-h{font-family:var(--serif);font-size:clamp(24px,4vw,34px);color:var(--dark);margin-bottom:14px}
.e404-p{font-size:16px;color:var(--muted);line-height:1.8;max-width:52ch;margin:0 auto 34px}
.e404-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:44px}
.e404-search{max-width:420px;margin:0 auto 44px;display:flex;gap:8px}
.e404-search input{flex:1;padding:13px 16px;border:1.5px solid var(--border);border-radius:var(--r-sm);background:var(--white);font-family:var(--sans);font-size:15px;color:var(--text)}
.e404-search input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(184,164,122,.15)}
.e404-links{border-top:1px solid var(--border);padding-top:32px}
.e404-links-t{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:18px}
.e404-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.e404-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:18px 14px;text-decoration:none;display:block;transition:transform .22s var(--ease),box-shadow .22s,border-color .22s}
.e404-card:hover{transform:translateY(-3px);box-shadow:var(--sh);border-color:var(--accent)}
.e404-card-i{color:var(--accent-txt);margin-bottom:8px;display:flex;justify-content:center}
.e404-card-t{font-family:var(--serif);font-size:15px;color:var(--dark);font-weight:700}
.e404-help{margin-top:34px;display:inline-flex;align-items:center;gap:12px;background:var(--section);border:1px solid var(--border);border-radius:50px;padding:10px 20px 10px 10px}
.e404-help img{width:38px;height:38px;border-radius:50%;object-fit:cover;object-position:center 18%}
.e404-help-txt{font-size:13px;color:var(--muted);text-align:left;line-height:1.4}
.e404-help-txt strong{display:block;color:var(--dark);font-size:14px}
@media(max-width:680px){
    .e404{padding:48px 20px 64px}
    .e404-grid{grid-template-columns:repeat(2,1fr)}
    .e404-btns .zc-btn{width:100%;justify-content:center}
}
</style>

<div class="e404">
    <div class="e404-in">
        <div class="zc-eyebrow" style="justify-content:center">Chyba 404</div>
        <div class="e404-num">4<span>0</span>4</div>
        <h1 class="e404-h">Táto stránka <em>neexistuje</em></h1>
        <p class="e404-p">Adresa je asi zle napísaná alebo už stránka nie je dostupná. Nič sa nedeje — nižšie nájdete, čo hľadáte.</p>

        <div class="e404-btns">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="zc-btn zc-btn-primary">Späť na úvod</a>
            <a href="<?php echo esc_url(home_url('/ponuky/')); ?>" class="zc-btn zc-btn-outline">Pozrieť ponuky</a>
        </div>

        <form class="e404-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" name="s" placeholder="Hľadať na webe…" aria-label="Hľadať">
            <button type="submit" class="zc-btn zc-btn-primary">Hľadať</button>
        </form>

        <div class="e404-links">
            <div class="e404-links-t">Kam ďalej</div>
            <div class="e404-grid">
                <?php foreach ([
                    ['/',          'home',      'Domov'],
                    ['/ponuky/',   'chart',     'Ponuky'],
                    ['/odhad/',    'scale',     'Odhad ZDARMA'],
                    ['/kontakt/',  'phone',     'Kontakt'],
                ] as [$path, $icon, $label]): ?>
                <a href="<?php echo esc_url(home_url($path)); ?>" class="e404-card">
                    <div class="e404-card-i"><?php echo function_exists('zc_svg') ? zc_svg($icon, 22) : ''; ?></div>
                    <div class="e404-card-t"><?php echo esc_html($label); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($zc_404_phone): ?>
        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $zc_404_phone)); ?>" class="e404-help" style="text-decoration:none">
            <?php if ($zc_404_photo): ?>
            <img src="<?php echo esc_url($zc_404_photo); ?>" alt="<?php echo esc_attr($zc_404_name); ?>" loading="lazy">
            <?php endif; ?>
            <span class="e404-help-txt">
                <strong>Neviete si rady? Zavolajte mi.</strong>
                <?php echo esc_html($zc_404_phone); ?>
            </span>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
