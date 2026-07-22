<?php
defined('ABSPATH') || exit;
add_shortcode('zc_reviews', function($atts) {
    $atts = shortcode_atts(['limit'=>'100','cols'=>'3','carousel'=>'auto'], $atts);
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM ".zcr_table()." WHERE published=1 ORDER BY sort_order ASC,id ASC LIMIT %d",
        intval($atts['limit'])
    ));
    if (!$rows) return '';

    $count    = count($rows);
    $cols     = max(1, min(4, intval($atts['cols'])));
    $carousel = $atts['carousel'] === 'auto' ? ($count > 3) : ($atts['carousel'] === '1');

    if ($carousel) return zcr_carousel($rows);

    ob_start(); ?>
    <style>
    <?php if ($cols === 3): ?>
    /* 3 stĺpce cez 6-stĺpcový grid – osirotené karty v poslednom riadku
       sa roztiahnu (2 → 50/50, 1 → celá šírka), žiadne prázdne diery */
    .zcr-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:22px}
    .zcr-grid>*{grid-column:span 2}
    .zcr-grid>*:nth-child(3n+1):nth-last-child(2),
    .zcr-grid>*:nth-child(3n+2):nth-last-child(1){grid-column:span 3}
    .zcr-grid>*:nth-child(3n+1):nth-last-child(1){grid-column:span 6}
    @media(max-width:900px){
        .zcr-grid{grid-template-columns:repeat(2,1fr)}
        .zcr-grid>*{grid-column:auto !important}
        .zcr-grid>*:nth-child(odd):nth-last-child(1){grid-column:1/-1 !important}
    }
    @media(max-width:560px){.zcr-grid{grid-template-columns:1fr}.zcr-grid>*{grid-column:auto !important}}
    <?php else: ?>
    .zcr-grid{display:grid;grid-template-columns:repeat(<?php echo $cols ?>,1fr);gap:22px}
    @media(max-width:900px){.zcr-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:560px){.zcr-grid{grid-template-columns:1fr}}
    <?php endif; ?>
    </style>
    <div class="zcr-grid">
    <?php foreach ($rows as $r): echo zcr_card($r); endforeach; ?>
    </div>
    <?php return ob_get_clean();
});

function zcr_card($r) {
    $stars = str_repeat('★', intval($r->rating)) . str_repeat('☆', 5 - intval($r->rating));
    ob_start(); ?>
    <div class="zc-testimonial">
        <div class="zc-stars"><?php echo $stars ?></div>
        <div class="zc-testimonial-quote"><?php echo esc_html($r->body) ?></div>
        <div class="zc-testimonial-author">
            <?php if ($r->avatar_url): ?>
            <div class="zc-testimonial-avatar">
                <img src="<?php echo esc_url($r->avatar_url) ?>" alt="" style="width:42px;height:42px;border-radius:50%;object-fit:cover">
            </div>
            <?php endif; ?>
            <div>
                <div class="zc-testimonial-name"><?php echo esc_html($r->author_name) ?></div>
                <?php if ($r->author_role): ?>
                <div class="zc-testimonial-meta"><?php echo esc_html($r->author_role) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function zcr_carousel($rows) {
    $uid   = 'zcrc_' . wp_rand(100,999);
    $total = count($rows);
    ob_start(); ?>
    <style>
    .zcrc-wrap{position:relative;overflow:hidden}
    .zcrc-track{display:flex;transition:transform .5s cubic-bezier(.4,0,.2,1)}
    .zcrc-slide{flex:0 0 calc(100%/3);padding:0 11px;box-sizing:border-box}
    .zcrc-btn{position:absolute;top:50%;transform:translateY(-50%);
        width:44px;height:44px;border-radius:50%;background:var(--white,#fff);
        border:1.5px solid var(--border,#E0D8CE);cursor:pointer;
        display:flex;align-items:center;justify-content:center;
        font-size:20px;color:var(--dark,#1C1A18);z-index:2;
        box-shadow:0 2px 12px rgba(0,0,0,.08);transition:all .2s}
    .zcrc-btn:hover{background:var(--accent,#B8A47A);border-color:var(--accent,#B8A47A);color:var(--dark)}
    .zcrc-prev{left:-16px}.zcrc-next{right:-16px}
    .zcrc-dots{display:flex;gap:7px;justify-content:center;margin-top:24px}
    .zcrc-dot{width:7px;height:7px;border-radius:50%;background:var(--border,#E0D8CE);
        border:none;cursor:pointer;transition:all .3s;padding:0}
    .zcrc-dot.on{background:var(--accent,#B8A47A);width:22px;border-radius:4px}
    @media(max-width:900px){.zcrc-slide{flex:0 0 50%}}
    @media(max-width:560px){.zcrc-slide{flex:0 0 100%}.zcrc-btn{display:none}}
    </style>
    <div style="position:relative;padding:0 20px">
    <div class="zcrc-wrap" id="<?php echo $uid ?>w">
        <div class="zcrc-track" id="<?php echo $uid ?>t">
        <?php foreach ($rows as $r): ?>
        <div class="zcrc-slide"><?php echo zcr_card($r); ?></div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php if ($total > 3): ?>
    <button class="zcrc-btn zcrc-prev" onclick="<?php echo $uid ?>P()">&#8249;</button>
    <button class="zcrc-btn zcrc-next" onclick="<?php echo $uid ?>N()">&#8250;</button>
    <?php endif; ?>
    </div>
    <div class="zcrc-dots" id="<?php echo $uid ?>d">
    <?php
    $per = 3;
    $pages = ceil($total / $per);
    for ($i = 0; $i < $pages; $i++):
    ?>
    <button class="zcrc-dot <?php echo $i===0?'on':'' ?>" onclick="<?php echo $uid ?>G(<?php echo $i ?>)"></button>
    <?php endfor; ?>
    </div>
    <script>
    (function(){
        var per=3,idx=0,total=<?php echo $total ?>,pages=Math.ceil(total/per);
        var tr=document.getElementById('<?php echo $uid ?>t');
        var ds=document.querySelectorAll('#<?php echo $uid ?>d .zcrc-dot');
        // Responsive per-page
        function getPer(){return window.innerWidth<=560?1:window.innerWidth<=900?2:3}
        function go(i){
            var p=getPer(),max=Math.ceil(total/p)-1;
            idx=Math.max(0,Math.min(i,max));
            tr.style.transform='translateX(-'+(idx*100/p*p)+'%)';
            // Simpler: move by card width
            var w=tr.parentElement.offsetWidth;
            tr.style.transform='translateX(-'+(idx*w)+'px)';
            ds.forEach(function(d,j){d.classList.toggle('on',j===idx)});
        }
        window['<?php echo $uid ?>N']=function(){go(idx+1<Math.ceil(total/getPer())?idx+1:0)};
        window['<?php echo $uid ?>P']=function(){go(idx>0?idx-1:Math.ceil(total/getPer())-1)};
        window['<?php echo $uid ?>G']=function(i){go(i)};
        // Touch swipe
        var sx=0;
        tr.addEventListener('touchstart',function(e){sx=e.touches[0].clientX},{passive:true});
        tr.addEventListener('touchend',function(e){
            var d=sx-e.changedTouches[0].clientX;
            if(Math.abs(d)>50){d>0?window['<?php echo $uid ?>N']():window['<?php echo $uid ?>P']()}
        });
        // Auto-play
        var timer=setInterval(function(){window['<?php echo $uid ?>N']()},6000);
        tr.parentElement.addEventListener('mouseenter',function(){clearInterval(timer)});
        tr.parentElement.addEventListener('mouseleave',function(){
            timer=setInterval(function(){window['<?php echo $uid ?>N']()},6000)
        });
        window.addEventListener('resize',function(){go(idx)});
    })();
    </script>
    <?php return ob_get_clean();
}
