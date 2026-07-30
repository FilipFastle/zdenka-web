<?php
defined('ABSPATH') || exit;

/**
 * Všetky zverejnené recenzie (vlastné + Google, ak je firma napojená)
 * zoradené podľa nastavenia. Používa to shortcode aj šablóna Referencie.
 */
function zcr_public_reviews($limit = 500) {
    global $wpdb;
    $limit = max(1, intval($limit));
    $order = function_exists('zcr_order_sql') ? zcr_order_sql() : 'sort_order ASC, id ASC';
    $rows  = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . zcr_table() . " WHERE published=1 ORDER BY {$order} LIMIT %d",
        $limit
    ));
    $rows = $rows ?: [];

    // Recenzie z Google firmy (ak je napojená) – zaradenie podľa nastavenia
    if (function_exists('zcr_google_enabled') && zcr_google_enabled()) {
        $g   = zcr_google_fetch();
        $pos = function_exists('zcr_google_position') ? zcr_google_position() : 'after';
        if ($pos === 'before') {
            $rows = array_merge($g, $rows);
        } elseif ($pos === 'mixed') {
            $rows = array_merge($rows, $g);
            usort($rows, function ($a, $b) { return (int) $b->rating <=> (int) $a->rating; });
        } else {
            $rows = array_merge($rows, $g);
        }
        $rows = array_slice($rows, 0, $limit);
    }
    return $rows;
}

/** Počet a priemerné hodnotenie zverejnených recenzií. */
function zcr_public_stats() {
    $rows = zcr_public_reviews(500);
    $n    = count($rows);
    if (!$n) return ['count' => 0, 'avg' => 0];
    $sum = 0;
    foreach ($rows as $r) $sum += intval($r->rating);
    return ['count' => $n, 'avg' => round($sum / $n, 1)];
}

add_shortcode('zc_reviews', function($atts) {
    $atts = shortcode_atts([
        'limit'    => '500',
        'cols'     => '3',
        'per_page' => '10',
        'carousel' => 'auto',
        'layout'   => '',      // '' | grid | mosaic | carousel | carousel-grid | paged
        'rows'     => '2',
        'clamp'    => '',      // '0' = zobraziť celý text
        'google'   => '1',     // '0' = skryť tlačidlo na Google
    ], $atts);
    $rows = zcr_public_reviews(intval($atts['limit']));

    // Tlačidlo na Google recenzie (ak je v Customizeri nastavený odkaz)
    $google = ($atts['google'] === '0') ? '' : (function_exists('get_theme_mod') ? get_theme_mod('zc_social_google', '') : '');
    $gicon  = '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1H12v3.83h5.35c-.23 1.4-1.66 4.1-5.35 4.1a5.9 5.9 0 0 1 0-11.8c1.87 0 3.13.8 3.85 1.48l2.62-2.53C16.9 3.6 14.66 2.6 12 2.6A9.4 9.4 0 1 0 21.35 11.1z"/></svg>';
    $gbtn   = $google ? '<div style="text-align:center;margin-top:34px"><a href="' . esc_url($google) . '" target="_blank" rel="noopener" class="zc-btn zc-btn-primary" style="display:inline-flex;align-items:center;gap:9px">' . $gicon . ' Pozrieť recenzie na Google →</a></div>' : '';

    if (!$rows) return $gbtn ? '<div class="zcr-only-google">' . $gbtn . '</div>' : '';

    $count  = count($rows);
    $cols   = max(1, min(4, intval($atts['cols'])));
    $layout = $atts['layout'];
    $clamp  = $atts['clamp'] !== '0';

    if ($layout === '') {
        $carousel = $atts['carousel'] === 'auto' ? ($count > 3) : ($atts['carousel'] === '1');
        $layout   = $carousel ? 'carousel' : 'grid';
    }

    if ($layout === 'paged')         return zcr_paged_grid($rows, $cols, intval($atts['per_page']), $clamp) . $gbtn;
    if ($layout === 'carousel')      return zcr_carousel($rows) . $gbtn;
    if ($layout === 'carousel-grid') return zcr_grid_carousel($rows, $cols, intval($atts['rows']), $clamp) . $gbtn;
    if ($layout === 'mosaic')        return zcr_mosaic($rows, $cols, $clamp) . $gbtn;

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
    <?php foreach ($rows as $r): echo zcr_card($r, $clamp); endforeach; ?>
    </div>
    <?php return ob_get_clean();
});

/**
 * Mozaika – karty v stĺpcoch s prirodzenou výškou (nie rovnaká výška v riadku).
 * Používa CSS columns, takže dlhá aj krátka recenzia vyzerajú prirodzene.
 */
function zcr_mosaic($rows, $cols = 3, $clamp = false) {
    $cols = max(1, min(4, intval($cols)));
    ob_start(); ?>
    <style>
    .zcr-mosaic{columns:<?php echo $cols ?>;column-gap:22px}
    .zcr-mosaic>.zcr-m-item{break-inside:avoid;-webkit-column-break-inside:avoid;
        page-break-inside:avoid;margin:0 0 22px;display:block}
    /* v mozaike nechceme naťahovanie na rovnakú výšku */
    .zcr-mosaic .zc-testimonial{height:auto}
    .zcr-mosaic .zc-testimonial-author{margin-top:16px}
    @media(max-width:900px){.zcr-mosaic{columns:2}}
    @media(max-width:600px){.zcr-mosaic{columns:1}}
    </style>
    <div class="zcr-mosaic">
    <?php foreach ($rows as $r): ?>
        <div class="zcr-m-item"><?php echo zcr_card($r, $clamp); ?></div>
    <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

/**
 * Spoločné štýly kariet – rovnaká výška, meno vždy dole, dlhý text skrátený
 * na jednotný počet riadkov (dá sa rozbaliť). Vypíše sa len raz.
 */
function zcr_card_styles() {
    static $done = false;
    if ($done) return '';
    $done = true;
    ob_start(); ?>
    <style>
    /* Karty v riadku majú rovnakú výšku */
    .zcr-grid{align-items:stretch}
    .zcrc-track{align-items:stretch}
    .zcrc-slide{display:flex}
    .zcr-grid>.zc-testimonial,.zcrc-slide>.zc-testimonial{width:100%}
    .zc-testimonial{display:flex;flex-direction:column;height:100%}
    /* Text vyplní priestor, meno ostane prilepené dole */
    .zc-testimonial-quote{flex:1 1 auto}
    .zc-testimonial-author{margin-top:auto;padding-top:16px}
    /* Jednotná dĺžka textu – dlhšie recenzie sa skrátia */
    .zcr-clamp{display:-webkit-box;-webkit-line-clamp:8;-webkit-box-orient:vertical;overflow:hidden}
    .zcr-clamp.is-open{display:block;-webkit-line-clamp:unset;overflow:visible}
    .zcr-more{align-self:flex-start;background:none;border:none;padding:4px 0 0;margin:0;
        font-family:inherit;font-size:12.5px;font-weight:700;color:var(--accent-txt,#7C5E33);
        cursor:pointer;text-decoration:underline;text-underline-offset:3px}
    .zcr-more:hover{color:var(--accent,#B8A47A)}
    /* Google recenzie – odlíšenie */
    .zcr-src{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--muted,#7A7068);margin-top:4px}
    .zcr-src svg{flex-shrink:0}
    </style>
    <script>
    (function(){
        function init(){
            document.querySelectorAll('.zcr-clamp:not([data-zcr-done])').forEach(function(q){
                q.setAttribute('data-zcr-done','1');
                if (q.scrollHeight - q.clientHeight < 6) return; // text sa zmestil
                var b=document.createElement('button');
                b.type='button'; b.className='zcr-more'; b.textContent='Zobraziť celé';
                b.addEventListener('click',function(){
                    var open=q.classList.toggle('is-open');
                    b.textContent=open?'Skryť':'Zobraziť celé';
                });
                q.parentNode.insertBefore(b,q.nextSibling);
            });
        }
        if(document.readyState!=='loading')init();else document.addEventListener('DOMContentLoaded',init);
        window.addEventListener('load',init);
    })();
    </script>
    <?php
    return ob_get_clean();
}

function zcr_card($r, $clamp = true) {
    $stars = str_repeat('★', intval($r->rating)) . str_repeat('☆', 5 - intval($r->rating));
    $is_google = !empty($r->source) && $r->source === 'google';
    ob_start();
    echo zcr_card_styles(); ?>
    <div class="zc-testimonial">
        <div class="zc-stars"><?php echo $stars ?></div>
        <div class="zc-testimonial-quote<?php echo $clamp ? ' zcr-clamp' : '' ?>"><?php echo esc_html($r->body) ?></div>
        <div class="zc-testimonial-author">
            <?php if ($r->avatar_url): ?>
            <div class="zc-testimonial-avatar">
                <img src="<?php echo esc_url($r->avatar_url) ?>" alt="" style="width:42px;height:42px;border-radius:50%;object-fit:cover">
            </div>
            <?php endif; ?>
            <div>
                <div class="zc-testimonial-name"><?php echo esc_html($r->author_name) ?></div>
                <?php if (!empty($r->author_role)): ?>
                <div class="zc-testimonial-meta"><?php echo esc_html($r->author_role) ?></div>
                <?php endif; ?>
                <?php if ($is_google): ?>
                <span class="zcr-src"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1H12v3.83h5.35c-.23 1.4-1.66 4.1-5.35 4.1a5.9 5.9 0 0 1 0-11.8c1.87 0 3.13.8 3.85 1.48l2.62-2.53C16.9 3.6 14.66 2.6 12 2.6A9.4 9.4 0 1 0 21.35 11.1z"/></svg> Recenzia z Google</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

/**
 * SEO-friendly stránkovaný zoznam recenzií bez JavaScriptového carouselu.
 * Používa samostatný query parameter, takže nekoliduje s WordPress pagináciou.
 */
function zcr_paged_grid($rows, $cols = 2, $per_page = 10, $clamp = true) {
    $cols       = max(1, min(3, intval($cols)));
    $per_page   = max(1, min(50, intval($per_page)));
    $param      = 'referencie_strana';
    $total      = count($rows);
    $page_count = max(1, (int) ceil($total / $per_page));
    $current    = isset($_GET[$param]) ? absint(wp_unslash($_GET[$param])) : 1;
    $current    = max(1, min($current, $page_count));
    $page_rows  = array_slice($rows, ($current - 1) * $per_page, $per_page);
    $base_url   = remove_query_arg($param);

    $page_url = function ($page) use ($base_url, $param) {
        return $page <= 1 ? $base_url : add_query_arg($param, $page, $base_url);
    };

    // Prvá, posledná a okolie aktuálnej strany. Pri veľkom počte strán
    // zostane navigácia krátka a medzi vzdialenými stranami sa ukáže elipsa.
    $visible = [1, $page_count];
    for ($i = $current - 2; $i <= $current + 2; $i++) {
        if ($i >= 1 && $i <= $page_count) $visible[] = $i;
    }
    $visible = array_values(array_unique($visible));
    sort($visible);

    ob_start();
    echo zcr_card_styles(); ?>
    <style>
    .zcr-paged-grid{display:grid;
        grid-template-columns:repeat(<?php echo $cols ?>,minmax(0,1fr));
        gap:24px;align-items:stretch}
    .zcr-paged-item{display:flex;min-width:0}
    .zcr-paged-item>.zc-testimonial{width:100%}
    .zcr-pagination{display:flex;align-items:center;justify-content:center;
        flex-wrap:wrap;gap:8px;margin-top:36px}
    .zcr-page-link,.zcr-page-current{min-width:42px;height:42px;padding:0 13px;
        display:inline-flex;align-items:center;justify-content:center;
        border:1.5px solid var(--border,#E0D8CE);border-radius:10px;
        background:var(--white,#fff);color:var(--dark,#1C1A18);
        font:700 13px/1 var(--sans,'DM Sans',sans-serif);
        text-decoration:none;transition:all .2s}
    .zcr-page-link:hover,.zcr-page-link:focus-visible{background:var(--section,#F5EEDF);
        border-color:var(--accent,#B8A47A);color:var(--dark,#1C1A18);
        transform:translateY(-2px);outline:none}
    .zcr-page-current{background:var(--accent,#B8A47A);
        border-color:var(--accent,#B8A47A);box-shadow:0 4px 13px rgba(184,164,122,.28)}
    .zcr-page-nav{padding:0 16px;white-space:nowrap}
    .zcr-page-gap{display:inline-flex;align-items:center;justify-content:center;
        width:24px;height:42px;color:var(--muted,#7A7068)}
    @media(max-width:760px){
        .zcr-paged-grid{grid-template-columns:1fr;gap:16px}
        .zcr-pagination{gap:6px;margin-top:28px}
        .zcr-page-link,.zcr-page-current{min-width:40px;height:40px;padding:0 11px}
        .zcr-page-nav{font-size:0;min-width:40px;padding:0}
        .zcr-page-nav.zcr-prev::after{content:'‹';font-size:20px}
        .zcr-page-nav.zcr-next::after{content:'›';font-size:20px}
    }
    </style>
    <div class="zcr-paged-grid">
        <?php foreach ($page_rows as $r): ?>
        <div class="zcr-paged-item"><?php echo zcr_card($r, $clamp); ?></div>
        <?php endforeach; ?>
    </div>
    <?php if ($page_count > 1): ?>
    <nav class="zcr-pagination" aria-label="Stránkovanie referencií">
        <?php if ($current > 1): ?>
        <a class="zcr-page-link zcr-page-nav zcr-prev"
           href="<?php echo esc_url($page_url($current - 1)); ?>"
           rel="prev">Predchádzajúca</a>
        <?php endif; ?>

        <?php $previous = 0; foreach ($visible as $page): ?>
            <?php if ($previous && $page > $previous + 1): ?>
            <span class="zcr-page-gap" aria-hidden="true">…</span>
            <?php endif; ?>

            <?php if ($page === $current): ?>
            <span class="zcr-page-current" aria-current="page"><?php echo $page; ?></span>
            <?php else: ?>
            <a class="zcr-page-link" href="<?php echo esc_url($page_url($page)); ?>"
               aria-label="Referencie – strana <?php echo $page; ?>"><?php echo $page; ?></a>
            <?php endif; ?>
        <?php $previous = $page; endforeach; ?>

        <?php if ($current < $page_count): ?>
        <a class="zcr-page-link zcr-page-nav zcr-next"
           href="<?php echo esc_url($page_url($current + 1)); ?>"
           rel="next">Ďalšia</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

/**
 * Stránkovaný carousel recenzií. Na desktope zobrazuje 3 stĺpce × 2 riadky,
 * na tablete 2 × 2 a na mobile 1 × 2. Počet strán nie je obmedzený.
 */
function zcr_grid_carousel($rows, $cols = 3, $row_count = 2, $clamp = true) {
    $uid       = 'zcrgc_' . wp_rand(1000, 9999);
    $cols      = max(1, min(3, intval($cols)));
    $row_count = max(1, min(3, intval($row_count)));
    $per_page  = $cols * $row_count;
    $pages     = array_chunk($rows, $per_page);

    ob_start();
    echo zcr_card_styles(); ?>
    <style>
    #<?php echo $uid ?>{position:relative}
    #<?php echo $uid ?> .zcrgc-viewport{overflow:hidden;padding:3px}
    #<?php echo $uid ?> .zcrgc-track{display:flex;align-items:flex-start;
        transition:transform .55s cubic-bezier(.4,0,.2,1);will-change:transform}
    #<?php echo $uid ?> .zcrgc-page{flex:0 0 100%;min-width:0;display:grid;
        grid-template-columns:repeat(<?php echo $cols ?>,minmax(0,1fr));
        grid-template-rows:repeat(<?php echo $row_count ?>,minmax(0,1fr));
        gap:22px;padding:0 1px}
    #<?php echo $uid ?> .zcrgc-item{display:flex;min-width:0}
    #<?php echo $uid ?> .zcrgc-item>.zc-testimonial{width:100%}
    #<?php echo $uid ?> .zcrgc-nav{display:flex;align-items:center;justify-content:center;
        gap:18px;margin-top:28px}
    #<?php echo $uid ?> .zcrgc-btn{width:44px;height:44px;border-radius:50%;
        background:var(--white,#fff);border:1.5px solid var(--border,#E0D8CE);
        color:var(--dark,#1C1A18);font-size:21px;line-height:1;cursor:pointer;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 3px 14px rgba(40,32,20,.09);transition:all .22s}
    #<?php echo $uid ?> .zcrgc-btn:hover,
    #<?php echo $uid ?> .zcrgc-btn:focus-visible{background:var(--accent,#B8A47A);
        border-color:var(--accent,#B8A47A);transform:translateY(-2px);outline:none}
    #<?php echo $uid ?> .zcrgc-dots{display:flex;align-items:center;justify-content:center;
        flex-wrap:wrap;gap:7px;max-width:min(560px,65vw)}
    #<?php echo $uid ?> .zcrgc-dot{width:8px;height:8px;border:0;border-radius:50%;
        padding:0;background:var(--border,#E0D8CE);cursor:pointer;transition:all .25s}
    #<?php echo $uid ?> .zcrgc-dot.is-active{width:25px;border-radius:5px;
        background:var(--accent,#B8A47A)}
    #<?php echo $uid ?> .zcrgc-status{position:absolute;width:1px;height:1px;
        padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
    @media(max-width:900px){
        #<?php echo $uid ?> .zcrgc-page{grid-template-columns:repeat(2,minmax(0,1fr));
            grid-template-rows:repeat(2,minmax(0,1fr));gap:18px}
    }
    @media(max-width:620px){
        #<?php echo $uid ?> .zcrgc-page{grid-template-columns:1fr;
            grid-template-rows:repeat(2,minmax(0,1fr));gap:14px}
        #<?php echo $uid ?> .zcrgc-nav{gap:12px;margin-top:22px}
        #<?php echo $uid ?> .zcrgc-btn{width:42px;height:42px}
        #<?php echo $uid ?> .zcrgc-dots{max-width:calc(100vw - 150px)}
    }
    @media(prefers-reduced-motion:reduce){
        #<?php echo $uid ?> .zcrgc-track{transition:none}
    }
    </style>
    <div class="zcrgc-shell" id="<?php echo $uid ?>" tabindex="0"
         aria-roledescription="carousel" aria-label="Referencie klientov">
        <div class="zcrgc-viewport">
            <div class="zcrgc-track">
            <?php foreach ($pages as $page_index => $page): ?>
                <div class="zcrgc-page" data-page="<?php echo $page_index ?>">
                <?php foreach ($page as $r): ?>
                    <div class="zcrgc-item"><?php echo zcr_card($r, $clamp); ?></div>
                <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="zcrgc-nav">
            <button type="button" class="zcrgc-btn zcrgc-prev" aria-label="Predchádzajúce referencie">&#8249;</button>
            <div class="zcrgc-dots" aria-label="Stránky referencií"></div>
            <button type="button" class="zcrgc-btn zcrgc-next" aria-label="Ďalšie referencie">&#8250;</button>
        </div>
        <div class="zcrgc-status" aria-live="polite"></div>
    </div>
    <script>
    (function(){
        var shell=document.getElementById('<?php echo $uid ?>');
        if(!shell)return;
        var track=shell.querySelector('.zcrgc-track');
        var dots=shell.querySelector('.zcrgc-dots');
        var status=shell.querySelector('.zcrgc-status');
        var prev=shell.querySelector('.zcrgc-prev');
        var next=shell.querySelector('.zcrgc-next');
        var items=Array.prototype.slice.call(shell.querySelectorAll('.zcrgc-item'));
        var index=0,pages=[],timer=null,resizeTimer=null;

        function perPage(){
            if(window.innerWidth<=620)return 2;
            if(window.innerWidth<=900)return 4;
            return <?php echo $per_page ?>;
        }
        function stop(){
            if(timer){window.clearInterval(timer);timer=null}
        }
        function start(){
            stop();
            if(pages.length>1&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){
                timer=window.setInterval(function(){go(index+1,false)},7000);
            }
        }
        function build(){
            var size=perPage();
            var fragment=document.createDocumentFragment();
            track.innerHTML='';
            for(var i=0;i<items.length;i+=size){
                var page=document.createElement('div');
                page.className='zcrgc-page';
                page.setAttribute('data-page',String(i/size));
                items.slice(i,i+size).forEach(function(item){page.appendChild(item)});
                fragment.appendChild(page);
            }
            track.appendChild(fragment);
            pages=Array.prototype.slice.call(track.querySelectorAll('.zcrgc-page'));
            index=Math.min(index,Math.max(0,pages.length-1));
            buildDots();
            go(index,false);
            start();
        }
        function buildDots(){
            dots.innerHTML='';
            pages.forEach(function(page,i){
                var dot=document.createElement('button');
                dot.type='button';
                dot.className='zcrgc-dot';
                dot.setAttribute('aria-label','Zobraziť referencie – strana '+(i+1));
                dot.addEventListener('click',function(){go(i,true)});
                dots.appendChild(dot);
            });
            var multiple=pages.length>1;
            shell.querySelector('.zcrgc-nav').style.display=multiple?'flex':'none';
        }
        function go(target,restart){
            if(!pages.length)return;
            index=(target%pages.length+pages.length)%pages.length;
            track.style.transform='translateX(-'+(index*100)+'%)';
            pages.forEach(function(page,i){
                page.setAttribute('aria-hidden',i===index?'false':'true');
                page.toggleAttribute('inert',i!==index);
            });
            dots.querySelectorAll('.zcrgc-dot').forEach(function(dot,i){
                dot.classList.toggle('is-active',i===index);
                dot.setAttribute('aria-current',i===index?'true':'false');
            });
            status.textContent='Strana '+(index+1)+' z '+pages.length;
            if(restart)start();
        }

        prev.addEventListener('click',function(){go(index-1,true)});
        next.addEventListener('click',function(){go(index+1,true)});
        shell.addEventListener('keydown',function(e){
            if(e.key==='ArrowLeft'){e.preventDefault();go(index-1,true)}
            if(e.key==='ArrowRight'){e.preventDefault();go(index+1,true)}
        });
        shell.addEventListener('mouseenter',stop);
        shell.addEventListener('mouseleave',start);
        shell.addEventListener('focusin',stop);
        shell.addEventListener('focusout',start);

        var touchX=0;
        track.addEventListener('touchstart',function(e){touchX=e.touches[0].clientX},{passive:true});
        track.addEventListener('touchend',function(e){
            var distance=touchX-e.changedTouches[0].clientX;
            if(Math.abs(distance)>45)go(index+(distance>0?1:-1),true);
        },{passive:true});

        window.addEventListener('resize',function(){
            window.clearTimeout(resizeTimer);
            resizeTimer=window.setTimeout(build,140);
        });
        build();
    })();
    </script>
    <?php
    return ob_get_clean();
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
    @media(max-width:768px){
        .zcrc-shell{padding:0!important}
        .zcrc-slide{flex:0 0 100%;padding:0}
        .zcrc-btn{display:none}
    }
    </style>
    <div class="zcrc-shell" style="position:relative;padding:0 20px">
    <div class="zcrc-wrap" id="<?php echo $uid ?>w">
        <div class="zcrc-track" id="<?php echo $uid ?>t">
        <?php foreach ($rows as $r): ?>
        <div class="zcrc-slide"><?php echo zcr_card($r); ?></div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php if ($total > 1): ?>
    <button class="zcrc-btn zcrc-prev" onclick="<?php echo $uid ?>P()">&#8249;</button>
    <button class="zcrc-btn zcrc-next" onclick="<?php echo $uid ?>N()">&#8250;</button>
    <?php endif; ?>
    </div>
    <div class="zcrc-dots" id="<?php echo $uid ?>d"></div>
    <script>
    (function(){
        var idx=0,total=<?php echo $total ?>;
        var tr=document.getElementById('<?php echo $uid ?>t');
        var dots=document.getElementById('<?php echo $uid ?>d');
        var shell=tr.closest('.zcrc-shell');
        var ds=[];
        // Responsive per-page
        function getPer(){return window.innerWidth<=768?1:window.innerWidth<=900?2:3}
        function rebuildDots(){
            var pages=Math.ceil(total/getPer());
            dots.innerHTML='';
            for(var i=0;i<pages;i++){
                var b=document.createElement('button');
                b.type='button';
                b.className='zcrc-dot'+(i===idx?' on':'');
                b.setAttribute('aria-label','Zobraziť skupinu recenzií '+(i+1));
                (function(page){b.addEventListener('click',function(){go(page)})})(i);
                dots.appendChild(b);
            }
            ds=dots.querySelectorAll('.zcrc-dot');
            dots.style.display=pages>1?'flex':'none';
            if(shell){
                shell.querySelectorAll('.zcrc-btn').forEach(function(btn){
                    btn.style.display=(pages>1&&window.innerWidth>768)?'flex':'none';
                });
            }
        }
        function go(i){
            var p=getPer(),max=Math.ceil(total/p)-1;
            idx=Math.max(0,Math.min(i,max));
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
        var resizeTimer;
        window.addEventListener('resize',function(){
            clearTimeout(resizeTimer);
            resizeTimer=setTimeout(function(){rebuildDots();go(idx)},120);
        });
        rebuildDots();
        go(0);
    })();
    </script>
    <?php return ob_get_clean();
}
