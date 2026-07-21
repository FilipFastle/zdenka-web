<?php
defined('ABSPATH') || exit;
// ── Favorites shortcode ───────────────────────────────────────────────────
add_shortcode('property_favorites', function () {
    ob_start(); ?>
    <div id="zcFavPage">
        <div id="zcFavEmpty" style="text-align:center;padding:80px 20px;display:none">
            <div style="margin-bottom:16px;color:var(--accent,#B8A47A)"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
            <h3 style="font-family:var(--serif);margin-bottom:10px">Žiadne obľúbené</h3>
            <p style="color:var(--muted)">Klikni na srdiečko pri nehnuteľnosti a uloží sa sem.</p>
            <a href="<?php echo home_url('/ponuky/'); ?>" class="zc-btn zc-btn-primary" style="margin-top:20px">Pozrieť ponuky</a>
        </div>
        <div class="property-grid" id="zcFavGrid"></div>
    </div>
    <script>
    (function(){
        var favs = JSON.parse(localStorage.getItem('zc_favorites') || '[]');
        var grid = document.getElementById('zcFavGrid');
        var empty = document.getElementById('zcFavEmpty');
        if (!favs.length) { empty.style.display='block'; return; }

        // Fetch all favorite properties via AJAX
        var data = new FormData();
        data.append('action', 'zc_get_favorites');
        data.append('ids', JSON.stringify(favs));
        data.append('nonce', (window.zcData||{}).nonce||'');

        fetch((window.zcData||{}).ajaxurl||'/wp-admin/admin-ajax.php', {method:'POST',body:data})
        .then(function(r){return r.json();})
        .then(function(res){
            if (res.success && res.data.html) {
                grid.innerHTML = res.data.html;
                // Attach remove handlers
                grid.querySelectorAll('.zc-fav-remove').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var id = String(this.dataset.id);
                        var favs2 = JSON.parse(localStorage.getItem('zc_favorites')||'[]');
                        favs2 = favs2.filter(function(x){return String(x)!==id;});
                        localStorage.setItem('zc_favorites', JSON.stringify(favs2));
                        this.closest('.zc-prop-card').remove();
                        if (!grid.children.length) { empty.style.display='block'; }
                        if(window.zcUpdateFavBadge)window.zcUpdateFavBadge();else location.reload();
                    });
                });
            } else {
                empty.style.display = 'block';
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// AJAX handler for favorites
add_action('wp_ajax_zc_get_favorites',        'zc_get_favorites_handler');
add_action('wp_ajax_nopriv_zc_get_favorites', 'zc_get_favorites_handler');
function zc_get_favorites_handler() {
    check_ajax_referer('zc_nonce', 'nonce');
    $ids = json_decode(sanitize_text_field($_POST['ids'] ?? '[]'), true);
    if (empty($ids)) { wp_send_json_error(); }

    $ids = array_map('intval', (array)$ids);
    $query = new WP_Query([
        'post_type' => 'property',
        'post__in'  => $ids,
        'orderby'   => 'post__in',
        'posts_per_page' => -1,
    ]);

    $html = '';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $id   = get_the_ID();
            $cena = get_post_meta($id,'_property_cena',true);
            if ($cena && strpos($cena,'€')===false) $cena .= ' €';
            $cover  = get_post_meta($id,'_property_cover_id',true);
            $typ    = get_post_meta($id,'_property_typ',true);
            $local  = get_post_meta($id,'_property_lokalita',true);
            $plocha = get_post_meta($id,'_property_plocha',true);
            $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];

            $html .= '<div class="zc-prop-card">';
            $html .= '<div class="zc-prop-img">';
            $html .= $cover ? wp_get_attachment_image($cover,'medium',false,['loading'=>'lazy']) : (has_post_thumbnail() ? get_the_post_thumbnail($id,'medium',['loading'=>'lazy']) : '<div style="height:100%;display:flex;align-items:center;justify-content:center;background:var(--section);color:#ccc"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>');
            if ($typ) $html .= '<span class="zc-prop-badge '.esc_attr($typ).'">'.($typ_labels[$typ]??$typ).'</span>';
            $html .= '<button class="zc-fav-remove" data-id="'.$id.'" style="position:absolute;top:12px;right:12px;width:32px;height:32px;background:rgba(255,255,255,.9);border:none;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .2s" title="Odstrániť z obľúbených">✕</button>';
            $html .= '</div>';
            $html .= '<div class="zc-prop-body">';
            $html .= '<div class="zc-prop-title"><a href="'.get_permalink().'">'.get_the_title().'</a></div>';
            if ($local) $html .= '<div class="zc-prop-location"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>'.esc_html($local).'</div>';
            if ($plocha) $html .= '<div class="zc-prop-specs"><span>'.esc_html($plocha).' m²</span></div>';
            $html .= '<div class="zc-prop-price'.($cena ? '' : ' zc-prop-price--nego').'">'.esc_html($cena ?: 'Cena dohodou').'</div>';
            $html .= '<a href="'.get_permalink().'" class="zc-prop-btn">Zobraziť ponuku →</a>';
            $html .= '</div></div>';
        }
    }
    wp_reset_postdata();
    wp_send_json_success(['html' => $html]);
}
