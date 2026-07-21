<?php
defined('ABSPATH') || exit;
// ── PDF exposé — tlačová verzia ponuky (?expose=1), používateľ uloží ako PDF ──

add_action('template_redirect', function() {
    if (!is_singular('property') || !isset($_GET['expose'])) return;
    $id = get_the_ID();

    $cover_id    = get_post_meta($id, '_property_cover_id', true);
    $gallery_ids = get_post_meta($id, '_property_gallery_ids', true) ?: [];
    $typ         = get_post_meta($id, '_property_typ', true);
    $cena_raw    = get_post_meta($id, '_property_cena', true);
    $lokalita    = get_post_meta($id, '_property_lokalita', true);
    $amenities   = get_post_meta($id, '_property_amenities', true) ?: [];
    $agent_id    = get_post_meta($id, '_property_agent_id', true) ?: get_post_field('post_author', $id);

    $agent_name  = get_the_author_meta('display_name', $agent_id) ?: 'Realitná maklérka';
    $agent_phone = get_user_meta($agent_id, 'property_phone', true) ?: '+421 907 579 742';
    $agent_email = get_user_meta($agent_id, 'property_email', true) ?: get_the_author_meta('user_email', $agent_id);
    $agent_title = get_user_meta($agent_id, 'property_title', true) ?: 'Realitná maklérka';

    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];
    $cover = $cover_id ? wp_get_attachment_image_url($cover_id, 'large') : (has_post_thumbnail($id) ? get_the_post_thumbnail_url($id, 'large') : '');
    $all_am = get_property_amenities();
    $per_m2 = pp_price_per_m2($cena_raw, get_post_meta($id, '_property_plocha', true));
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=0&data=' . rawurlencode(get_permalink($id));

    $specs = [
        ['area','Úžitková plocha', get_post_meta($id,'_property_plocha',true), 'm²'],
        ['land','Pozemok', get_post_meta($id,'_property_pozemok',true), 'm²'],
        ['rooms','Počet izieb', get_post_meta($id,'_property_spalne',true), ''],
        ['bath','Kúpeľne', get_post_meta($id,'_property_kupelne',true), ''],
        ['wc','WC', get_post_meta($id,'_property_wc',true), ''],
        ['floor','Poschodie', get_post_meta($id,'_property_poschodie',true), ''],
        ['year','Ročník', get_post_meta($id,'_property_rocnik',true), ''],
        ['condition','Stav', get_post_meta($id,'_property_stav',true), ''],
    ];
    $logo = get_stylesheet_directory_uri() . '/assets/images/zc-logo.svg';
    if (!file_exists(get_stylesheet_directory() . '/assets/images/zc-logo.svg')) $logo = '';
    ?><!DOCTYPE html>
<html lang="sk"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html(get_the_title($id)) ?> — exposé</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',Arial,sans-serif;color:#2C2825;background:#fff;line-height:1.6}
.wrap{max-width:800px;margin:0 auto;padding:32px}
.head{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #B8A47A;padding-bottom:16px;margin-bottom:24px}
.head .brand{font-family:'Playfair Display',serif;font-size:20px;font-weight:700}
.head .brand small{display:block;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:#7C5E33;font-family:'DM Sans',sans-serif}
.head img{height:44px}
.badge{display:inline-block;background:#B8A47A;color:#1C1A18;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:5px 14px;border-radius:50px;margin-bottom:10px}
h1{font-family:'Playfair Display',serif;font-size:26px;margin-bottom:6px}
.loc{color:#6B6560;font-size:14px;margin-bottom:14px}
.cover{width:100%;height:360px;object-fit:cover;border-radius:12px;margin-bottom:20px}
.price{font-family:'Playfair Display',serif;font-size:30px;font-weight:700;color:#7C5E33;margin-bottom:4px}
.perm2{color:#6B6560;font-size:13px;margin-bottom:20px}
.specs{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#E2DACE;border-radius:10px;overflow:hidden;margin-bottom:24px}
.spec{background:#fff;padding:14px 8px;text-align:center}
.spec .v{font-family:'Playfair Display',serif;font-size:18px;font-weight:700}
.spec .l{font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6B6560;margin-top:2px}
.sec-t{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#6B6560;margin:22px 0 10px;border-bottom:1px solid #E2DACE;padding-bottom:6px}
.desc{font-size:14px;color:#2C2825}
.am{display:flex;flex-wrap:wrap;gap:6px}
.am span{font-size:12px;background:#F5F1EA;border:1px solid #E2DACE;border-radius:6px;padding:5px 10px}
.gal{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px}
.gal img{width:100%;height:110px;object-fit:cover;border-radius:8px}
.foot{margin-top:28px;padding-top:18px;border-top:2px solid #B8A47A;display:flex;justify-content:space-between;align-items:center;gap:20px}
.agent .n{font-family:'Playfair Display',serif;font-size:17px;font-weight:700}
.agent .r{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#7C5E33;margin-bottom:6px}
.agent .c{font-size:13px;color:#2C2825}
.qr{text-align:center}.qr img{width:110px;height:110px}.qr .t{font-size:10px;color:#6B6560;margin-top:4px}
.print-btn{position:fixed;top:16px;right:16px;background:#B8A47A;color:#1C1A18;border:none;border-radius:8px;padding:12px 22px;font-weight:700;font-size:14px;cursor:pointer;font-family:'DM Sans',sans-serif;box-shadow:0 4px 16px rgba(0,0,0,.15)}
@media print{.print-btn{display:none}.wrap{max-width:none;padding:0}body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style></head>
<body>
<button class="print-btn" onclick="window.print()">Stiahnuť ako PDF / Tlačiť</button>
<div class="wrap">
    <div class="head">
        <div class="brand"><?php echo esc_html(function_exists('zc_agent')?zc_agent('name','Mgr. Zdenka Cibuľová'):'Mgr. Zdenka Cibuľová') ?><small><?php echo esc_html(function_exists('zc_agent')?zc_agent('title','Realitná maklérka'):'Realitná maklérka') ?></small></div>
        <?php if ($logo): ?><img src="<?php echo esc_url($logo) ?>" alt=""><?php endif; ?>
    </div>
    <?php if ($typ && isset($typ_labels[$typ])): ?><span class="badge"><?php echo $typ_labels[$typ] ?></span><?php endif; ?>
    <h1><?php echo esc_html(get_the_title($id)) ?></h1>
    <?php if ($lokalita): ?><div class="loc"><?php echo esc_html($lokalita) ?></div><?php endif; ?>
    <?php if ($cover): ?><img class="cover" src="<?php echo esc_url($cover) ?>" alt=""><?php endif; ?>
    <div class="price"><?php echo esc_html(pp_price_fmt($cena_raw)) ?></div>
    <?php if ($per_m2): ?><div class="perm2"><?php echo esc_html($per_m2) ?></div><?php endif; ?>

    <?php $vis=array_filter($specs, function($s){return $s[2]!=='';}); if($vis): ?>
    <div class="specs">
        <?php foreach($vis as $s): ?>
        <div class="spec"><div class="v"><?php echo esc_html($s[2]) ?><?php echo $s[3]?' '.$s[3]:'' ?></div><div class="l"><?php echo esc_html($s[1]) ?></div></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (get_the_content(null,false,$id)): ?>
    <div class="sec-t">Popis</div>
    <div class="desc"><?php echo wp_kses_post(apply_filters('the_content', get_post_field('post_content',$id))) ?></div>
    <?php endif; ?>

    <?php if ($amenities): ?>
    <div class="sec-t">Vybavenie a okolie</div>
    <div class="am">
        <?php foreach ($all_am as $cat): $items=array_intersect_key($cat['items'], array_flip($amenities)); foreach($items as $lbl): ?>
        <span><?php echo esc_html($lbl) ?></span>
        <?php endforeach; endforeach; ?>
    </div>
    <?php endif; ?>

    <?php $gal = array_slice(array_filter(array_map('intval',(array)$gallery_ids)), 0, 6); if ($gal): ?>
    <div class="sec-t">Galéria</div>
    <div class="gal">
        <?php foreach ($gal as $gid): $u=wp_get_attachment_image_url($gid,'medium'); if($u): ?><img src="<?php echo esc_url($u) ?>" alt=""><?php endif; endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="foot">
        <div class="agent">
            <div class="r"><?php echo esc_html($agent_title) ?></div>
            <div class="n"><?php echo esc_html($agent_name) ?></div>
            <div class="c"><?php echo esc_html($agent_phone) ?> · <?php echo esc_html($agent_email) ?></div>
        </div>
        <div class="qr"><img src="<?php echo esc_url($qr) ?>" alt="QR"><div class="t">Otvoriť ponuku online</div></div>
    </div>
</div>
<script>window.addEventListener('load',function(){setTimeout(function(){window.print()},400)});</script>
</body></html>
    <?php
    exit;
});
