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
    $sort     = sanitize_text_field($_POST['sort']     ?? 'date_desc');
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
        default:           $args['orderby']='date'; $args['order']='DESC';
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
    $init_query = new WP_Query([
        'post_type'      => 'property',
        'posts_per_page' => intval($atts['per_page']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

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
                }
                grid.classList.remove('zc-grid-loading');
            })
            .catch(function(){ grid.classList.remove('zc-grid-loading'); });
        };

        window[uid + '_reset'] = function() {
            ['typ','sort','city'].forEach(function(id){
                var el = document.getElementById(uid + '_' + id);
                if (el) el.value = id === 'sort' ? 'date_desc' : '';
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
    $cena     = get_post_meta($id, '_property_cena',    true);
    $lokalita = get_post_meta($id, '_property_lokalita',true);
    $plocha   = get_post_meta($id, '_property_plocha',  true);
    $spalne   = get_post_meta($id, '_property_spalne',  true);
    $kupelne  = get_post_meta($id, '_property_kupelne', true);
    $cover_id = get_post_meta($id, '_property_cover_id',true);

    if ($cena) {
        $cena_num = preg_replace('/[^0-9]/', '', $cena);
        if ($cena_num && is_numeric($cena_num)) {
            $cena = number_format(intval($cena_num), 0, ',', ' ') . ' €';
        } elseif (strpos($cena,'€') === false) {
            $cena .= ' €';
        }
    }

    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok','rezervovane'=>'Rezervované'];
    $typ_label  = $typ_labels[$typ] ?? '';

    ob_start(); ?>
    <div class="zc-prop-card">
        <div class="zc-prop-img">
            <?php
            if ($cover_id) echo wp_get_attachment_image($cover_id,'medium',false,['loading'=>'lazy']);
            elseif (has_post_thumbnail()) the_post_thumbnail('medium',['loading'=>'lazy']);
            else echo '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#F5F1EA;color:#ccc;font-size:36px">🏠</div>';
            ?>
            <?php if ($typ_label): ?>
            <span class="zc-prop-badge <?php echo esc_attr($typ) ?>"><?php echo $typ_label ?></span>
            <?php endif; ?>
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
                <?php if ($plocha):  ?><span>📐 <?php echo esc_html($plocha) ?> m²</span><?php endif; ?>
                <?php if ($spalne):  ?><span>🚪 <?php echo esc_html($spalne) ?> izby</span><?php endif; ?>
                <?php if ($kupelne): ?><span>🚿 <?php echo esc_html($kupelne) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($cena): ?>
            <div class="zc-prop-price"><?php echo esc_html($cena) ?></div>
            <?php endif; ?>
            <a href="<?php the_permalink() ?>" class="zc-prop-btn">Zobraziť ponuku →</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Legacy shortcode — empty
add_shortcode('property_filter', '__return_empty_string');

// ── CAROUSEL ─────────────────────────────────────────────────────────────
add_shortcode('property_carousel', function($atts) {
    $atts  = shortcode_atts(['per_page'=>'6'], $atts);
    $query = new WP_Query(['post_type'=>'property','posts_per_page'=>intval($atts['per_page']),'post_status'=>'publish','orderby'=>'date','order'=>'DESC']);
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
        $typ_colors = ['predaj'=>'#B8A47A','prenajom'=>'#FFFFFF','pozemok'=>'#6a9e77'];
        $img = $cover_id ? wp_get_attachment_image($cover_id,'large',false,['style'=>'position:absolute;inset:0;width:100%;height:100%;object-fit:cover']) : (has_post_thumbnail() ? get_the_post_thumbnail(null,'large',['style'=>'position:absolute;inset:0;width:100%;height:100%;object-fit:cover']) : '');
        $slides[] = ['img'=>$img,'title'=>get_the_title(),'cena'=>$cena,'lokalita'=>$lokalita,'typ'=>$typ_labels[$typ]??'','typ_color'=>$typ_colors[$typ]??'#B8A47A','typ_text'=>$typ==='pozemok'?'#fff':'#1C1A18','url'=>get_permalink()];
    }
    wp_reset_postdata();
    $total = count($slides);

    ob_start(); ?>
    <style>
    .prop-sc{position:relative;border-radius:14px;overflow:hidden;background:#111}
    .prop-sc-track{display:flex;transition:transform .55s cubic-bezier(.4,0,.2,1)}
    .prop-sc-slide{min-width:100%;position:relative;aspect-ratio:16/9;max-height:500px;overflow:hidden;background:#111}
    .prop-sc-overlay{position:absolute;inset:0;background:linear-gradient(transparent 35%,rgba(0,0,0,.78));z-index:1}
    .prop-sc-info{position:absolute;bottom:0;left:0;right:0;padding:clamp(18px,4vw,48px);color:#fff;z-index:2}
    .prop-sc-badge{display:inline-block;padding:5px 13px;border-radius:50px;font-size:10px;font-weight:800;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px}
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
            <?php echo $s['img'] ?: '<div style="position:absolute;inset:0;background:#2c2c2c;display:flex;align-items:center;justify-content:center;color:#555;font-size:40px">🏠</div>'; ?>
            <div class="prop-sc-overlay"></div>
            <div class="prop-sc-info">
                <?php if ($s['typ']): ?><span class="prop-sc-badge" style="background:<?php echo $s['typ_color'] ?>;color:<?php echo $s['typ_text'] ?>"><?php echo $s['typ'] ?></span><?php endif; ?>
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
        <div class="prop-sc-dots"><?php for($i=0;$i<$total;$i++): ?><button class="prop-sc-dot<?php echo $i===0?' active':'' ?>" onclick="<?php echo $uid ?>G(<?php echo $i ?>)"></button><?php endfor; ?></div>
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
