<?php

defined('ABSPATH') || exit;
// SVG icons helper
function zc_icon($name) {
    $icons = [
        'phone'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>',
        'whatsapp' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
        'email'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>',
        'pin'      => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'filter'   => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
    ];
    return $icons[$name] ?? '';
}

// ── AJAX handler for grid ─────────────────────────────────────────────────
add_action('wp_ajax_zc_property_grid',        'zc_ajax_property_grid');
add_action('wp_ajax_nopriv_zc_property_grid', 'zc_ajax_property_grid');

function zc_ajax_property_grid() {
    $typ      = sanitize_text_field($_POST['typ']      ?? '');
    $sort     = sanitize_text_field($_POST['sort']     ?? 'manual');
    $city     = sanitize_text_field($_POST['city']     ?? '');
    $per_page = intval($_POST['per_page']              ?? 24);

    $args = [
        'post_type'      => 'property',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
    ];

    if ($typ) {
        $args['meta_query'] = [['key'=>'_property_typ','value'=>$typ,'compare'=>'=']];
    }

    if ($city) {
        $city_q = ['key'=>'_property_mesto','value'=>$city,'compare'=>'='];
        $args['meta_query'] = isset($args['meta_query'])
            ? ['relation'=>'AND', $args['meta_query'][0], $city_q]
            : [$city_q];
    }

    switch ($sort) {
        case 'price_desc': $args['meta_key']='_property_cena';  $args['orderby']='meta_value_num'; $args['order']='DESC'; break;
        case 'price_asc':  $args['meta_key']='_property_cena';  $args['orderby']='meta_value_num'; $args['order']='ASC';  break;
        case 'area_desc':  $args['meta_key']='_property_plocha';$args['orderby']='meta_value_num'; $args['order']='DESC'; break;
        case 'area_asc':   $args['meta_key']='_property_plocha';$args['orderby']='meta_value_num'; $args['order']='ASC';  break;
        case 'city_az':    $args['meta_key']='_property_mesto'; $args['orderby']='meta_value';     $args['order']='ASC';  break;
        case 'date_desc':  $args['orderby']='date'; $args['order']='DESC'; break;
        default:           $args = pp_property_order_args($args);
    }

    $query = new WP_Query($args);
    $cards = '';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $cards .= render_property_card();
        }
    } else {
        $cards = '<p style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#888">Žiadne ponuky nezodpovedajú filtru.</p>';
    }
    wp_reset_postdata();

    wp_send_json_success([
        'cards' => $cards,
        'count' => $query->found_posts,
    ]);
}

// ── PROPERTY GRID shortcode ───────────────────────────────────────────────
add_shortcode('property_grid', function($atts) {
    $atts = shortcode_atts(['per_page' => '24'], $atts);

    // Get all cities for filter dropdown
    $all_cities = [];
    $cq = new WP_Query(['post_type'=>'property','posts_per_page'=>-1,'fields'=>'ids','post_status'=>'publish']);
    foreach ($cq->posts as $pid) {
        $c = get_post_meta($pid, '_property_mesto', true);
        if ($c) $all_cities[$c] = $c;
    }
    ksort($all_cities);
    wp_reset_postdata();

    // Initial query
    $init_query = new WP_Query(pp_property_order_args([
        'post_type'      => 'property',
        'posts_per_page' => intval($atts['per_page']),
        'post_status'    => 'publish',
    ]));

    $uid = 'pgrid_' . wp_rand(100,999);

    ob_start();
    ?>
    <style>
    .zc-filters{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:20px 24px;background:var(--white,#fff);border:1px solid var(--border,#E2DACE);border-radius:14px;margin-bottom:32px}
    .zc-filter-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:130px}
    .zc-filter-label{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted,#6B6560);font-family:var(--sans,sans-serif)}
    .zc-filter-select{padding:10px 34px 10px 12px;border:1.5px solid var(--border,#E2DACE);border-radius:8px;font-family:var(--sans,sans-serif);font-size:13px;color:var(--text,#2C2C2C);background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B6560' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 10px center;appearance:none;cursor:pointer;transition:border .2s}
    .zc-filter-select:focus{outline:none;border-color:var(--accent,#B8A47A)}
    .zc-filter-actions{display:flex;gap:8px;align-items:flex-end;flex-shrink:0}
    .zc-filter-btn{padding:10px 18px;background:var(--accent,#B8A47A);color:var(--dark,#1C1A18);border:none;border-radius:8px;font-family:var(--sans,sans-serif);font-size:12px;font-weight:700;letter-spacing:.6px;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap}
    .zc-filter-btn:hover{background:var(--accent-dk,#9A8660)}
    .zc-filter-reset{padding:10px 14px;background:var(--section,#F5F1EA);color:var(--muted,#6B6560);border:1.5px solid var(--border,#E2DACE);border-radius:8px;font-size:12px;cursor:pointer;font-family:var(--sans,sans-serif);white-space:nowrap;transition:all .2s}
    .zc-filter-reset:hover{color:var(--dark,#1C1A18);border-color:#ccc}
    .zc-filter-count{font-size:13px;color:var(--muted,#6B6560);font-family:var(--sans,sans-serif);padding-bottom:2px;white-space:nowrap}
    .property-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
    .zc-grid-loading{opacity:.5;pointer-events:none;transition:opacity .2s}
    @media(max-width:768px){
        .zc-filters{padding:16px}
        .zc-filter-group{min-width:calc(50% - 6px)}
    }
    @media(max-width:480px){
        .zc-filter-group{min-width:100%}
    }
    </style>

    <div id="<?php echo $uid ?>_wrap">
        <!-- FILTERS -->
        <div class="zc-filters">
            <div class="zc-filter-group">
                <span class="zc-filter-label">Typ ponuky</span>
                <select class="zc-filter-select" id="<?php echo $uid ?>_typ">
                    <option value="">Všetky</option>
                    <option value="predaj">Na predaj</option>
                    <option value="prenajom">Na prenájom</option>
                    <option value="pozemok">Pozemok</option>
                </select>
            </div>
            <div class="zc-filter-group">
                <span class="zc-filter-label">Zoradiť</span>
                <select class="zc-filter-select" id="<?php echo $uid ?>_sort">
                    <option value="manual">Odporúčané poradie</option>
                    <option value="date_desc">Najnovšie</option>
                    <option value="price_asc">Cena vzostupne</option>
                    <option value="price_desc">Cena zostupne</option>
                    <option value="area_desc">Plocha (m²) ↓</option>
                    <option value="area_asc">Plocha (m²) ↑</option>
                    <option value="city_az">Lokalita A–Z</option>
                </select>
            </div>
            <?php if (!empty($all_cities)): ?>
            <div class="zc-filter-group">
                <span class="zc-filter-label">Mesto</span>
                <select class="zc-filter-select" id="<?php echo $uid ?>_city">
                    <option value="">Všetky mestá</option>
                    <?php foreach ($all_cities as $city): ?>
                    <option value="<?php echo esc_attr($city) ?>"><?php echo esc_html($city) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="zc-filter-actions">
                <button class="zc-filter-btn" onclick="<?php echo $uid ?>_filter()">
                    <?php echo zc_icon('filter') ?> Filtrovať
                </button>
                <button class="zc-filter-reset" onclick="<?php echo $uid ?>_reset()">× Reset</button>
                <span class="zc-filter-count" id="<?php echo $uid ?>_count"><?php echo $init_query->found_posts ?> ponúk</span>
            </div>
        </div>

        <!-- GRID -->
        <div class="property-grid" id="<?php echo $uid ?>_grid">
        <?php
        if ($init_query->have_posts()) {
            while ($init_query->have_posts()) {
                $init_query->the_post();
                echo render_property_card();
            }
        } else {
            echo '<p style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#888">Zatiaľ žiadne ponuky.</p>';
        }
        wp_reset_postdata();
        ?>
        </div>
    </div>

    <script>
    (function(){
        var uid   = '<?php echo $uid ?>';
        var wrap  = document.getElementById(uid + '_wrap');
        var grid  = document.getElementById(uid + '_grid');
        var count = document.getElementById(uid + '_count');
        var perPage = <?php echo intval($atts['per_page']) ?>;

        function getVal(id) {
            var el = document.getElementById(uid + '_' + id);
            return el ? el.value : '';
        }

        window[uid + '_filter'] = function() {
            grid.classList.add('zc-grid-loading');
            var data = new FormData();
            data.append('action',   'zc_property_grid');
            data.append('nonce',    '<?php echo wp_create_nonce("zc_grid") ?>');
            data.append('typ',      getVal('typ'));
            data.append('sort',     getVal('sort'));
            data.append('city',     getVal('city'));
            data.append('per_page', perPage);

            fetch('<?php echo admin_url("admin-ajax.php") ?>', {method:'POST', body:data})
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res.success) {
                    grid.innerHTML = res.data.cards;
                    count.textContent = res.data.count + ' ponúk';
                    // Karty nabehnú postupne namiesto skokového preblknutia
                    grid.querySelectorAll('.zc-prop-card').forEach(function(c, i){
                        c.style.animation = 'zcCardIn .45s cubic-bezier(.22,.9,.36,1) both';
                        c.style.animationDelay = (i * 45) + 'ms';
                    });
                    if (window.zcMarkFavs) window.zcMarkFavs();
                    if (window.zcRevealImages) window.zcRevealImages(grid);
                    if (window.zcCmpSync) window.zcCmpSync();
                }
                grid.classList.remove('zc-grid-loading');
            })
            .catch(function(){ grid.classList.remove('zc-grid-loading'); });
        };

        window[uid + '_reset'] = function() {
            ['typ','sort','city'].forEach(function(id){
                var el = document.getElementById(uid + '_' + id);
                if (el) el.value = id === 'sort' ? 'manual' : '';
            });
            window[uid + '_filter']();
        };

        // Auto-filter on select change
        ['typ','sort','city'].forEach(function(id){
            var el = document.getElementById(uid + '_' + id);
            if (el) el.addEventListener('change', function(){
                window[uid + '_filter']();
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// ── PROPERTY CARD ─────────────────────────────────────────────────────────
function render_property_card() {
    $id       = get_the_ID();
    $typ      = get_post_meta($id, '_property_typ',     true);
    $cena_raw = get_post_meta($id, '_property_cena',    true);
    $cena_pov = get_post_meta($id, '_property_cena_povodna', true);
    $lokalita = get_post_meta($id, '_property_lokalita',true);
    $plocha   = get_post_meta($id, '_property_plocha',  true);
    $spalne   = get_post_meta($id, '_property_spalne',  true);
    $kupelne  = get_post_meta($id, '_property_kupelne', true);
    $cover_id = get_post_meta($id, '_property_cover_id',true);

    $cena     = $cena_raw ? pp_price_fmt($cena_raw) : '';
    $per_m2   = pp_price_per_m2($cena_raw, $plocha);
    $znizena  = pp_price_num($cena_pov) > pp_price_num($cena_raw) && pp_price_num($cena_raw) > 0;

    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];
    $typ_label  = $typ_labels[$typ] ?? '';
    $sale       = pp_sale_state($id);
    $sale_badge = pp_sale_badge($sale);

    ob_start(); ?>
    <div class="zc-prop-card<?php echo $sale === 'predane' ? ' is-sold' : '' ?>">
        <div class="zc-prop-img">
            <?php /* Na fotku sa klikne ako prvé – nech otvorí ponuku rovnako ako tlačidlo.
                     tabindex=-1, aby klávesnica nemala tri rovnaké zastávky na jednej karte. */ ?>
            <a href="<?php the_permalink() ?>" class="zc-prop-imglink" tabindex="-1" aria-hidden="true">
            <?php
            /* Karta má na PC cez 400 px, na retine 800 px. Veľkosť „medium" má
               300 px – preto pôsobili fotky mäkko. Dáme prehliadaču správne
               „sizes" a on si z ponuky vyberie dosť veľkú fotku sám. */
            $img_attr = ['loading' => 'lazy', 'sizes' => '(max-width:560px) 92vw, (max-width:900px) 46vw, 31vw'];
            if ($cover_id) echo wp_get_attachment_image($cover_id, 'large', false, $img_attr);
            elseif (has_post_thumbnail()) the_post_thumbnail('large', $img_attr);
            else echo '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#F5F1EA;color:#ccc"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
            ?>
            </a>
            <div class="zc-prop-badges">
                <?php if ($typ_label): ?><span class="zc-prop-badge <?php echo esc_attr($typ) ?>"><?php echo $typ_label ?></span><?php endif; ?>
                <?php if (pp_is_new($id) && !$sale): ?><span class="zc-prop-badge is-new">Nové</span><?php endif; ?>
                <?php if ($znizena && !$sale): ?><span class="zc-prop-badge is-reduced">Znížená cena</span><?php endif; ?>
                <?php if ($sale_badge): ?><span class="zc-prop-badge <?php echo $sale === 'predane' ? 'is-sold' : 'is-reserved' ?>"><?php echo $sale_badge['label'] ?></span><?php endif; ?>
            </div>
            <button class="zc-fav-btn" data-id="<?php echo $id ?>" title="Pridať do obľúbených" onclick="zcToggleFav(this,<?php echo $id ?>)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
        </div>
        <div class="zc-prop-body">
            <div class="zc-prop-title"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></div>
            <?php if ($lokalita): ?>
            <div class="zc-prop-location"><?php echo zc_icon('pin') ?> <?php echo esc_html($lokalita) ?></div>
            <?php endif; ?>
            <?php if ($plocha || $spalne || $kupelne): ?>
            <div class="zc-prop-specs">
                <?php if ($plocha):  ?><span><?php echo pp_svg('area',14) ?> <?php echo esc_html($plocha) ?> m²</span><?php endif; ?>
                <?php if ($spalne):  ?><span><?php echo pp_svg('rooms',14) ?> <?php echo esc_html($spalne) ?> izby</span><?php endif; ?>
                <?php if ($kupelne): ?><span><?php echo pp_svg('bath',14) ?> <?php echo esc_html($kupelne) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="zc-prop-price<?php echo $cena ? '' : ' zc-prop-price--nego' ?>">
                <?php if ($znizena): ?><span class="zc-prop-price-old"><?php echo esc_html(pp_price_fmt($cena_pov)) ?></span> <?php endif; ?>
                <?php echo esc_html($cena ?: 'Cena dohodou') ?>
                <?php if ($per_m2): ?><small class="zc-prop-perm2"><?php echo esc_html($per_m2) ?></small><?php endif; ?>
            </div>
            <a href="<?php the_permalink() ?>" class="zc-prop-btn">Zobraziť ponuku →</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Legacy shortcode – empty
add_shortcode('property_filter', '__return_empty_string');

// ── PREDANÉ NEHNUTEĽNOSTI ─────────────────────────────────────────────────
add_shortcode('predane_nehnutelnosti', function($atts) {
    $atts = shortcode_atts(['per_page' => '6'], $atts);
    $q = new WP_Query(pp_property_order_args([
        'post_type'      => 'property',
        'posts_per_page' => intval($atts['per_page']),
        'post_status'    => 'publish',
        'meta_query'     => [['key' => '_property_stav_predaja', 'value' => 'predane', 'compare' => '=']],
    ]));
    if (!$q->have_posts()) return '';
    ob_start(); ?>
    <div class="property-grid">
        <?php while ($q->have_posts()): $q->the_post(); echo render_property_card(); endwhile; wp_reset_postdata(); ?>
    </div>
    <?php return ob_get_clean();
});

// ── CAROUSEL ─────────────────────────────────────────────────────────────
add_shortcode('property_carousel', function($atts) {
    $atts  = shortcode_atts(['per_page'=>'6'], $atts);
    $query = new WP_Query(pp_property_order_args(['post_type'=>'property','posts_per_page'=>intval($atts['per_page']),'post_status'=>'publish']));
    if (!$query->have_posts()) return '';

    $uid    = 'pcar_' . wp_rand(100,999);
    $slides = [];

    while ($query->have_posts()) {
        $query->the_post();
        $id       = get_the_ID();
        $cover_id = get_post_meta($id,'_property_cover_id',true);
        $cena     = get_post_meta($id,'_property_cena',true);
        if ($cena && strpos($cena,'€')===false) $cena .= ' €';
        $lokalita = get_post_meta($id,'_property_lokalita',true);
        $typ      = get_post_meta($id,'_property_typ',true);
        $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];
        $slide_attr = [
            'style' => 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover',
            'sizes' => '100vw',
        ];
        $img = $cover_id
            ? wp_get_attachment_image($cover_id, 'full', false, $slide_attr)
            : (has_post_thumbnail() ? get_the_post_thumbnail(null, 'full', $slide_attr) : '');
        $slides[] = ['img'=>$img,'title'=>get_the_title(),'cena'=>$cena,'lokalita'=>$lokalita,'typ'=>$typ_labels[$typ]??'','typ_key'=>$typ,'url'=>get_permalink()];
    }
    wp_reset_postdata();
    $total = count($slides);

    ob_start(); ?>
    <style>
    .prop-sc{position:relative;border-radius:14px;overflow:hidden;background:#111}
    .prop-sc-track{display:flex;transition:transform .55s cubic-bezier(.4,0,.2,1)}
    .prop-sc-slide{min-width:100%;position:relative;aspect-ratio:16/9;max-height:500px;overflow:hidden;background:#111}
    .prop-sc-imglink{position:absolute;inset:0;display:block;z-index:0}
    /* prekrytie len farbí, klik prejde na fotku pod ním */
    .prop-sc-overlay{position:absolute;inset:0;background:linear-gradient(transparent 35%,rgba(0,0,0,.78));z-index:1;pointer-events:none}
    .prop-sc-info{position:absolute;bottom:0;left:0;right:0;padding:clamp(18px,4vw,48px);color:#fff;z-index:2}
    .prop-sc-badge{display:inline-flex;align-items:center;min-height:31px;padding:6px 13px;border-radius:50px;border:1px solid rgba(255,255,255,.55);font-size:9px;font-weight:900;letter-spacing:1.15px;text-transform:uppercase;margin-bottom:10px;box-shadow:0 6px 18px rgba(0,0,0,.3),inset 0 1px 0 rgba(255,255,255,.3)}
    .prop-sc-badge.predaj{background:linear-gradient(135deg,#E8D39F,#B89450)!important;color:#2A2114!important}
    .prop-sc-badge.prenajom{background:linear-gradient(135deg,#2A7EA4,#174B78)!important;color:#fff!important}
    .prop-sc-badge.pozemok{background:linear-gradient(135deg,#5A9B71,#2E684A)!important;color:#fff!important}
    .prop-sc-title{font-size:clamp(16px,3vw,26px);font-weight:700;margin-bottom:7px;font-family:var(--serif,'Playfair Display',serif);text-shadow:0 2px 10px rgba(0,0,0,.5)}
    .prop-sc-title a{color:#fff;text-decoration:none}
    .prop-sc-meta{display:flex;gap:16px;font-size:13px;opacity:.85}
    .prop-sc-btn{position:absolute;top:50%;transform:translateY(-50%);width:46px;height:46px;border-radius:50%;background:rgba(255,255,255,.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);color:#fff;font-size:22px;cursor:pointer;z-index:5;display:flex;align-items:center;justify-content:center;transition:all .2s;line-height:1}
    .prop-sc-btn:hover{background:rgba(255,255,255,.28)}
    .prop-sc-prev{left:14px}.prop-sc-next{right:14px}
    .prop-sc-dots{position:absolute;bottom:16px;right:clamp(18px,4vw,48px);display:flex;gap:7px;z-index:5}
    .prop-sc-dot{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.35);border:none;cursor:pointer;transition:all .3s;padding:0}
    .prop-sc-dot.active{background:#fff;width:22px;border-radius:4px}
    </style>
    <div class="prop-sc" id="<?php echo $uid ?>">
        <div class="prop-sc-track" id="<?php echo $uid ?>T">
        <?php foreach ($slides as $s): ?>
        <div class="prop-sc-slide">
            <a href="<?php echo esc_url($s['url']) ?>" class="prop-sc-imglink" tabindex="-1" aria-hidden="true">
            <?php echo $s['img'] ?: '<div style="position:absolute;inset:0;background:#2c2c2c;display:flex;align-items:center;justify-content:center;color:#555"><svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>'; ?>
            </a>
            <div class="prop-sc-overlay"></div>
            <div class="prop-sc-info">
                <?php if ($s['typ']): ?><span class="prop-sc-badge <?php echo esc_attr($s['typ_key']) ?>"><?php echo $s['typ'] ?></span><?php endif; ?>
                <div class="prop-sc-title"><a href="<?php echo $s['url'] ?>"><?php echo esc_html($s['title']) ?></a></div>
                <div class="prop-sc-meta">
                    <?php if ($s['lokalita']): ?><span><?php echo zc_icon('pin') ?> <?php echo esc_html($s['lokalita']) ?></span><?php endif; ?>
                    <?php if ($s['cena']): ?><span><?php echo esc_html($s['cena']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php if ($total > 1): ?>
        <button class="prop-sc-btn prop-sc-prev" onclick="<?php echo $uid ?>P()">&#8249;</button>
        <button class="prop-sc-btn prop-sc-next" onclick="<?php echo $uid ?>N()">&#8250;</button>
        <div class="prop-sc-dots"><?php for($i=0;$i<$total;$i++): ?><button type="button" class="prop-sc-dot<?php echo $i===0?' active':'' ?>" onclick="<?php echo $uid ?>G(<?php echo $i ?>)" aria-label="Fotka <?php echo $i+1 ?> z <?php echo $total ?>"></button><?php endfor; ?></div>
        <?php endif; ?>
    </div>
    <script>
    (function(){
        var c=0,t=<?php echo $total ?>,tr=document.getElementById('<?php echo $uid ?>T'),ds=document.querySelectorAll('#<?php echo $uid ?> .prop-sc-dot');
        function u(){tr.style.transform='translateX(-'+(c*100)+'%)';ds.forEach(function(d,i){d.classList.toggle('active',i===c)})}
        window['<?php echo $uid ?>N']=function(){c=(c+1)%t;u()};
        window['<?php echo $uid ?>P']=function(){c=(c-1+t)%t;u()};
        window['<?php echo $uid ?>G']=function(i){c=i;u()};
        var el=document.getElementById('<?php echo $uid ?>'),sx=0;
        el.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
        el.addEventListener('touchend',function(e){var d=sx-e.changedTouches[0].clientX;if(Math.abs(d)>50){d>0?window['<?php echo $uid ?>N']():window['<?php echo $uid ?>P']()}});
        if(t>1)setInterval(function(){window['<?php echo $uid ?>N']()},7000);
    })();
    </script>
    <?php
    return ob_get_clean();
});

// ── POROVNÁVAČ NEHNUTEĽNOSTÍ ──────────────────────────────────────────────
// Plávajúca lišta (max 3 ponuky) + stránka [porovnanie]
if (false) add_action('wp_footer', function () {
    if (is_admin()) return;
    $cmp_url = home_url('/porovnanie/');
    ?>
    <div id="zcCmpBar" aria-live="polite">
        <div class="zc-cmp-bar-in">
            <span class="zc-cmp-bar-count"><span id="zcCmpN">0</span>/3 na porovnanie</span>
            <div class="zc-cmp-bar-thumbs" id="zcCmpThumbs"></div>
            <a href="<?php echo esc_url($cmp_url) ?>" class="zc-cmp-bar-go">Porovnať →</a>
            <button class="zc-cmp-bar-clear" onclick="zcClearCompare()" aria-label="Vyčistiť">Vyčistiť</button>
        </div>
    </div>
    <style>
    .zc-cmp-btn{position:absolute;top:12px;right:52px;width:32px;height:32px;background:rgba(255,255,255,.92);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B6560;transition:all .2s;z-index:3;box-shadow:0 2px 8px rgba(0,0,0,.1)}
    .zc-cmp-btn:hover{color:#B8A47A}
    .zc-cmp-btn.active{background:#B8A47A;color:#1C1A18}
    #zcCmpBar{position:fixed;left:0;right:0;bottom:0;z-index:900;transform:translateY(110%);transition:transform .35s cubic-bezier(.4,0,.2,1);padding:0 16px 16px}
    #zcCmpBar.show{transform:translateY(0)}
    .zc-cmp-bar-in{max-width:900px;margin:0 auto;background:#1C1A18;color:#fff;border-radius:14px;padding:12px 18px;display:flex;align-items:center;gap:16px;box-shadow:0 10px 40px rgba(0,0,0,.3);flex-wrap:wrap}
    .zc-cmp-bar-count{font-size:13px;font-weight:600;white-space:nowrap}
    .zc-cmp-bar-thumbs{display:flex;gap:8px;flex:1;min-width:0;overflow-x:auto}
    .zc-cmp-bar-thumbs img{width:44px;height:34px;object-fit:cover;border-radius:6px;flex-shrink:0}
    .zc-cmp-bar-go{background:#B8A47A;color:#1C1A18;text-decoration:none;font-weight:700;font-size:13px;padding:9px 16px;border-radius:8px;white-space:nowrap;transition:background .2s}
    .zc-cmp-bar-go:hover{background:#C9B98F}
    .zc-cmp-bar-clear{background:transparent;border:1px solid rgba(255,255,255,.3);color:#fff;font-size:12px;padding:8px 12px;border-radius:8px;cursor:pointer;white-space:nowrap}
    .zc-cmp-bar-clear:hover{background:rgba(255,255,255,.1)}
    @media(max-width:600px){.zc-cmp-bar-thumbs{display:none}}
    </style>
    <script>
    (function(){
        window.zcCompareGet=function(){try{return JSON.parse(localStorage.getItem('zc_compare')||'[]');}catch(e){return [];}};
        function save(a){localStorage.setItem('zc_compare',JSON.stringify(a));}
        window.zcToggleCompare=function(btn,id){
            var a=zcCompareGet();id=parseInt(id);var i=a.indexOf(id);
            if(i>-1){a.splice(i,1);}
            else{if(a.length>=3){alert('Porovnať môžeš najviac 3 ponuky naraz.');return;}a.push(id);}
            save(a);zcCmpSync();
        };
        window.zcClearCompare=function(){save([]);zcCmpSync();};
        window.zcCmpSync=function(){
            var a=zcCompareGet();
            document.querySelectorAll('.zc-cmp-btn').forEach(function(b){
                b.classList.toggle('active',a.indexOf(parseInt(b.dataset.id))>-1);
            });
            var bar=document.getElementById('zcCmpBar');if(!bar)return;
            // Na stránke porovnania plávajúcu lištu nezobrazujeme (má vlastné tlačidlá)
            if(document.getElementById('zcCmpPage')){bar.classList.remove('show');return;}
            document.getElementById('zcCmpN').textContent=a.length;
            bar.classList.toggle('show',a.length>0);
            var th=document.getElementById('zcCmpThumbs');
            if(th){th.innerHTML='';a.forEach(function(id){
                var card=document.querySelector('.zc-cmp-btn[data-id="'+id+'"]');
                var img=card?card.closest('.zc-prop-img').querySelector('img'):null;
                if(img){var c=document.createElement('img');c.src=img.currentSrc||img.src;th.appendChild(c);}
            });}
        };
        document.addEventListener('DOMContentLoaded',zcCmpSync);
        zcCmpSync();
    })();
    </script>
    <?php
});

// ── LOKÁLNE SEO STRÁNKY: [reality_mesto mesto="Banská Bystrica"] ───────────
add_shortcode('reality_mesto', function ($atts) {
    $atts = shortcode_atts([
        'mesto'  => '',
        'nadpis' => '',
        'text'   => '',
        'count'  => '12',
    ], $atts);
    $mesto = trim($atts['mesto']);
    if ($mesto === '') return '';

    // Nájsť ponuky v meste – podľa _property_mesto alebo lokality
    $q = new WP_Query(pp_property_order_args([
        'post_type'      => 'property',
        'posts_per_page' => intval($atts['count']),
        'post_status'    => 'publish',
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => '_property_mesto',    'value' => $mesto, 'compare' => 'LIKE'],
            ['key' => '_property_lokalita', 'value' => $mesto, 'compare' => 'LIKE'],
        ],
    ]));

    $nadpis = $atts['nadpis'] ?: ('Reality ' . $mesto);
    $text   = $atts['text'] ?: ('Aktuálne ponuky nehnuteľností v lokalite ' . $mesto . ' a okolí. Byty, domy aj pozemky na predaj a prenájom so serióznym prístupom a osobnou obhliadkou.');

    ob_start(); ?>
    <section class="zc-local-seo">
        <h1 class="zc-local-seo-h1"><?php echo esc_html($nadpis) ?></h1>
        <p class="zc-local-seo-lead"><?php echo esc_html($text) ?></p>
        <?php if ($q->have_posts()): ?>
        <div class="property-grid">
            <?php while ($q->have_posts()): $q->the_post(); echo render_property_card(); endwhile; wp_reset_postdata(); ?>
        </div>
        <?php else: ?>
        <p style="color:var(--muted,#6B6560);padding:20px 0">Momentálne tu nemáme aktívne ponuky. <a href="<?php echo home_url('/kontakt/') ?>" style="color:var(--accent-txt,#7C5E33)">Ozvite sa</a> a nájdeme vám nehnuteľnosť na mieru.</p>
        <?php endif; ?>
    </section>
    <style>
    .zc-local-seo{max-width:1200px;margin:0 auto;padding:20px 0 40px}
    .zc-local-seo-h1{font-family:var(--serif,'Playfair Display',serif);font-size:clamp(28px,5vw,42px);color:var(--dark,#1C1A18);margin-bottom:14px;line-height:1.15}
    .zc-local-seo-lead{font-size:clamp(15px,2vw,17px);color:var(--muted,#6B6560);max-width:720px;line-height:1.7;margin-bottom:32px}
    </style>
    <?php
    return ob_get_clean();
});

// AJAX: dáta na porovnanie
// Starý porovnávač zostáva iba ako migračný kód a nie je verejne registrovaný.
function pp_ajax_compare() {
    $ids = json_decode(sanitize_text_field($_POST['ids'] ?? '[]'), true);
    $ids = array_slice(array_map('intval', (array)$ids), 0, 3);
    if (empty($ids)) wp_send_json_error();

    // Riadky tabuľky: [meta_key, label, suffix]
    $rows = [
        ['_property_cena',      'Cena',        '€',  'price'],
        ['_property_typ',       'Typ',         '',   'typ'],
        ['_property_lokalita',  'Lokalita',    '',   ''],
        ['_property_plocha',    'Plocha',      'm²', ''],
        ['_property_pozemok',   'Pozemok',     'm²', ''],
        ['_property_spalne',    'Spálne',      '',   ''],
        ['_property_kupelne',   'Kúpeľne',     '',   ''],
        ['_property_wc',        'WC',          '',   ''],
        ['_property_poschodie', 'Poschodie',   '',   ''],
        ['_property_rocnik',    'Rok stavby',  '',   ''],
        ['_property_energie',   'Energ. trieda','',  ''],
        ['_property_stav',      'Stav',        '',   ''],
        ['_property_vlastnictvo','Vlastníctvo','',   ''],
    ];
    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];

    $props = [];
    foreach ($ids as $id) {
        if (get_post_type($id) !== 'property') continue;
        $cover = get_post_meta($id, '_property_cover_id', true);
        $props[] = [
            'id'    => $id,
            'title' => get_the_title($id),
            'url'   => get_permalink($id),
            'img'   => $cover ? wp_get_attachment_image_url($cover, 'medium') : (get_post_thumbnail_id($id) ? wp_get_attachment_image_url(get_post_thumbnail_id($id), 'medium') : ''),
        ];
    }
    if (empty($props)) wp_send_json_error();

    ob_start(); ?>
    <div class="zc-cmp-scroll">
    <table class="zc-cmp-table">
        <thead><tr>
            <th class="zc-cmp-lbl"></th>
            <?php foreach ($props as $p): ?>
            <th>
                <div class="zc-cmp-th">
                    <?php if ($p['img']): ?><img src="<?php echo esc_url($p['img']) ?>" alt=""><?php else: ?><div class="zc-cmp-noimg"></div><?php endif; ?>
                    <a href="<?php echo esc_url($p['url']) ?>"><?php echo esc_html($p['title']) ?></a>
                    <button class="zc-cmp-x" data-id="<?php echo $p['id'] ?>" title="Odobrať">✕</button>
                </div>
            </th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as [$key,$label,$suffix,$kind]):
            $vals = [];
            $any  = false;
            foreach ($props as $p) {
                $v = get_post_meta($p['id'], $key, true);
                if ($kind === 'price' && $v !== '')      $v = function_exists('pp_price_fmt') ? pp_price_fmt($v) : ($v.' '.$suffix);
                elseif ($kind === 'typ')                 $v = $typ_labels[$v] ?? $v;
                elseif ($v !== '' && $suffix)            $v = $v.' '.$suffix;
                if ($v !== '' && $v !== null) $any = true;
                $vals[] = $v;
            }
            if (!$any) continue; // skry riadok kde nikto nemá hodnotu
        ?>
            <tr>
                <td class="zc-cmp-lbl"><?php echo esc_html($label) ?></td>
                <?php foreach ($vals as $v): ?>
                <td class="<?php echo $kind==='price'?'zc-cmp-price':'' ?>"><?php echo $v !== '' && $v !== null ? esc_html($v) : '<span class="zc-cmp-dash">—</span>' ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}

// Stránka porovnania
if (false) add_shortcode('porovnanie', function () {
    ob_start(); ?>
    <div id="zcCmpPage">
        <div id="zcCmpEmpty" style="text-align:center;padding:70px 20px;display:none">
            <h3 style="font-family:var(--serif);margin-bottom:10px">Žiadne ponuky na porovnanie</h3>
            <p style="color:var(--muted)">Na kartách ponúk klikni na ikonu porovnania a vyber až 3 nehnuteľnosti.</p>
            <a href="<?php echo home_url('/ponuky/') ?>" class="zc-btn zc-btn-primary" style="margin-top:20px;display:inline-block">Pozrieť ponuky</a>
        </div>
        <div id="zcCmpResult"></div>
        <div id="zcCmpActions" style="display:none;gap:12px;flex-wrap:wrap;justify-content:center;margin-top:26px">
            <a href="<?php echo home_url('/ponuky/') ?>" class="zc-cmp-act zc-cmp-act--primary">+ Pridať ďalšiu ponuku</a>
            <button type="button" class="zc-cmp-act" onclick="zcCmpBack()">← Späť na ponuky</button>
        </div>
    </div>
    <style>
    .zc-cmp-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
    .zc-cmp-act{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:12px 22px;border-radius:10px;font-family:var(--sans,sans-serif);font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;border:1.5px solid var(--border,#E2DACE);background:#fff;color:var(--text,#2C2C2C);transition:all .2s}
    .zc-cmp-act:hover{border-color:var(--accent,#B8A47A);color:var(--dark,#1C1A18)}
    .zc-cmp-act--primary{background:var(--accent,#B8A47A);border-color:var(--accent,#B8A47A);color:#1C1A18}
    .zc-cmp-act--primary:hover{background:var(--accent-dk,#9A8660);color:#1C1A18}
    .zc-cmp-table{width:100%;border-collapse:collapse;min-width:520px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 16px rgba(60,50,30,.08)}
    .zc-cmp-table th,.zc-cmp-table td{padding:14px 16px;text-align:center;border-bottom:1px solid #EFEAE0;font-size:14px}
    .zc-cmp-table thead th{background:#F5F1EA;vertical-align:top}
    .zc-cmp-lbl{text-align:left !important;font-weight:700;color:#6B6560;font-size:12px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;background:#FCFBF8}
    .zc-cmp-th{display:flex;flex-direction:column;align-items:center;gap:8px;position:relative;min-width:130px}
    .zc-cmp-th img{width:100%;max-width:160px;height:100px;object-fit:cover;border-radius:10px}
    .zc-cmp-noimg{width:160px;height:100px;background:#EFEAE0;border-radius:10px}
    .zc-cmp-th a{font-family:var(--serif,'Playfair Display',serif);font-weight:700;color:#1C1A18;text-decoration:none;font-size:14px;line-height:1.3}
    .zc-cmp-th a:hover{color:#B8A47A}
    .zc-cmp-x{position:absolute;top:-6px;right:-6px;width:24px;height:24px;border-radius:50%;background:#fff;border:1px solid #E2DACE;color:#999;cursor:pointer;font-size:12px;line-height:1;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    .zc-cmp-x:hover{color:#dc2626;border-color:#dc2626}
    .zc-cmp-price{font-weight:800;color:#7C5E33;font-family:var(--sans,'DM Sans',sans-serif);font-variant-numeric:tabular-nums}
    .zc-cmp-dash{color:#ccc}
    </style>
    <script>
    window.zcCmpBack=function(){
        if(document.referrer && document.referrer.indexOf(location.host)>-1 && document.referrer.indexOf('/porovnanie')===-1){history.back();}
        else{location.href='<?php echo home_url('/ponuky/') ?>';}
    };
    (function(){
        var box=document.getElementById('zcCmpResult'),empty=document.getElementById('zcCmpEmpty'),acts=document.getElementById('zcCmpActions');
        function load(){
            var a=(window.zcCompareGet?zcCompareGet():JSON.parse(localStorage.getItem('zc_compare')||'[]'));
            if(!a.length){box.innerHTML='';empty.style.display='block';if(acts)acts.style.display='none';return;}
            empty.style.display='none';
            var d=new FormData();d.append('action','pp_compare');d.append('ids',JSON.stringify(a));
            fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:d,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(res){
                if(res.success&&res.data.html){box.innerHTML=res.data.html;bindX();if(acts)acts.style.display='flex';}
                else{box.innerHTML='';empty.style.display='block';if(acts)acts.style.display='none';}
            });
        }
        function bindX(){
            box.querySelectorAll('.zc-cmp-x').forEach(function(b){
                b.addEventListener('click',function(){
                    var id=parseInt(this.dataset.id);
                    var a=(window.zcCompareGet?zcCompareGet():[]).filter(function(x){return x!==id;});
                    localStorage.setItem('zc_compare',JSON.stringify(a));
                    if(window.zcCmpSync)zcCmpSync();
                    load();
                });
            });
        }
        load();
    })();
    </script>
    <?php
    return ob_get_clean();
});
