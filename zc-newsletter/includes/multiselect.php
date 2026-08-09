<?php
/**
 * Výber kategórií – rozbaľovacie okienko so zaškrtávacími políčkami.
 *
 * Natívny <select multiple> vyžaduje Ctrl+klik, na dotyku sa ovláda mizerne
 * a nie je z neho vidieť, čo je vybraté. Toto je jeden komponent pre
 * realitný panel aj wp-admin, aby sa oba správali rovnako.
 */
defined('ABSPATH') || exit;

/**
 * @param string $name     názov poľa (pridá sa [] – posiela pole)
 * @param mixed  $selected reťazec „dom,pozemok" alebo pole kľúčov
 * @param array  $args     empty (text pri prázdnom výbere), compact, id
 */
function zcn_multiselect($name, $selected = '', $args = []) {
    $args = wp_parse_args($args, [
        'empty'   => 'Všetko',
        'compact' => false,
        'id'      => '',
        'groups'  => null,
    ]);

    $selected = zcn_interest_list($selected);
    $groups   = is_array($args['groups']) ? $args['groups'] : zcn_interest_groups();
    $id       = $args['id'] ?: 'zcms' . substr(md5($name . wp_rand()), 0, 7);

    zcn_multiselect_assets();

    $labels = [];
    foreach ($groups as $items) {
        foreach ($items as $key => $label) {
            if (in_array($key, $selected, true)) $labels[] = $label;
        }
    }
    ?>
    <div class="zc-ms<?php echo $args['compact'] ? ' is-compact' : ''; ?>" data-zc-ms
         data-empty="<?php echo esc_attr($args['empty']); ?>">
        <button type="button" class="zc-ms-btn" aria-expanded="false" aria-controls="<?php echo esc_attr($id); ?>">
            <span class="zc-ms-label"><?php echo esc_html($labels ? implode(', ', $labels) : $args['empty']); ?></span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div class="zc-ms-menu" id="<?php echo esc_attr($id); ?>" hidden>
            <?php foreach ($groups as $glabel => $items): ?>
                <?php if ($glabel !== '' && count($groups) > 1): ?>
                <div class="zc-ms-grp"><?php echo esc_html($glabel); ?></div>
                <?php endif; ?>
                <?php foreach ($items as $key => $label): ?>
                <label class="zc-ms-opt">
                    <input type="checkbox" name="<?php echo esc_attr($name); ?>[]"
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked(in_array($key, $selected, true)); ?>>
                    <span><?php echo esc_html($label); ?></span>
                </label>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="zc-ms-note">Nič alebo všetko označené = <strong>všetko</strong>.</div>
            <div class="zc-ms-foot">
                <button type="button" data-zc-ms-all>Označiť všetko</button>
                <button type="button" data-zc-ms-none>Zrušiť výber</button>
                <button type="button" data-zc-ms-done class="is-done">Hotovo</button>
            </div>
        </div>
    </div>
    <?php
}

/** Štýly a obsluha – vypíšu sa raz za stránku. */
function zcn_multiselect_assets() {
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
    <style id="zc-ms-css">
    .zc-ms{position:relative;display:block;width:100%;font-family:inherit}
    .zc-ms *{box-sizing:border-box}
    .zc-ms-btn{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;
        min-height:42px;padding:10px 13px;border:1.5px solid #E0D8CE;border-radius:9px;background:#fff;
        color:#2C2825;font:600 13.5px/1.35 inherit;text-align:left;cursor:pointer;transition:border-color .15s}
    .zc-ms-btn:hover,.zc-ms.is-open .zc-ms-btn{border-color:#B8A47A}
    .zc-ms-btn svg{flex:0 0 auto;transition:transform .18s;color:#9A8660}
    .zc-ms.is-open .zc-ms-btn svg{transform:rotate(180deg)}
    .zc-ms-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .zc-ms-label.is-empty{color:#9A9186;font-weight:500}
    .zc-ms-menu{position:absolute;z-index:100010;left:0;right:0;top:calc(100% + 5px);min-width:230px;
        max-height:320px;overflow:auto;padding:8px;background:#fff;border:1px solid #E0D8CE;border-radius:11px;
        box-shadow:0 14px 38px rgba(40,32,20,.16)}
    .zc-ms-menu[hidden]{display:none}
    .zc-ms-grp{font:800 9.5px/1.4 inherit;letter-spacing:1.1px;text-transform:uppercase;color:#7C5E33;padding:9px 9px 5px}
    .zc-ms-opt{display:flex;align-items:center;gap:9px;padding:8px 9px;border-radius:7px;cursor:pointer;
        font:500 13.5px/1.35 inherit;color:#2C2825}
    .zc-ms-opt:hover{background:#FBF8F2}
    .zc-ms-opt input{width:16px;height:16px;min-height:auto;accent-color:#B8A47A;margin:0;flex:0 0 auto}
    .zc-ms-note{padding:9px 9px 2px;font-size:11px;line-height:1.5;color:#7C5E33}
    .zc-ms-foot{display:flex;gap:6px;margin-top:6px;padding-top:8px;border-top:1px solid #F1EBE0}
    .zc-ms-foot button{flex:1;padding:7px;border:1px solid #E0D8CE;border-radius:7px;background:#fff;
        color:#6B6560;font:600 11px/1.2 inherit;cursor:pointer}
    .zc-ms-foot button:hover{border-color:#B8A47A;color:#2C2825}
    .zc-ms-foot button.is-done{background:#B8A47A;border-color:#B8A47A;color:#1C1A18;font-weight:800}
    .zc-ms-foot button.is-done:hover{background:#9A8660;border-color:#9A8660;color:#1C1A18}
    .zc-ms.is-compact .zc-ms-btn{min-height:34px;padding:7px 10px;font-size:12px;border-radius:7px}
    .zc-ms.is-compact .zc-ms-menu{min-width:210px}
    @media(max-width:600px){
        .zc-ms-btn{min-height:46px;font-size:14px}
        .zc-ms-opt{padding:11px 9px;font-size:15px}
    }
    </style>
    <script id="zc-ms-js">
    (function(){
        function labelOf(box){
            var all=box.querySelectorAll('.zc-ms-opt input');
            var on=box.querySelectorAll('.zc-ms-opt input:checked');
            var out=box.querySelector('.zc-ms-label');
            if(!out)return;
            // Označené všetko znamená to isté ako neoznačené nič – všetko.
            var everything=(on.length===0)||(all.length&&on.length===all.length);
            var names=[].map.call(on,function(c){return c.parentNode.textContent.trim()});
            out.textContent=everything?(box.dataset.empty||'Všetko'):names.join(', ');
            out.classList.toggle('is-empty',everything);
            out.title=everything?'':names.join(', ');
        }
        // Okienko sa otvára ako fixed – aby ho neorezala tabuľka ani modálne okno.
        // Nesmie však prekryť odosielacie tlačidlo formulára: klik doň by sa
        // stratil v okienku a používateľovi by sa zdalo, že tlačidlo nereaguje.
        function blocker(box){
            var form=box.closest&&box.closest('form'); if(!form)return null;
            var r=box.getBoundingClientRect(), best=null;
            [].forEach.call(form.querySelectorAll('button,input[type=submit]'),function(el){
                if(el.closest('.zc-ms-menu'))return;                       // vlastné tlačidlá okienka
                if(el.type&&el.type!=='submit'&&el.tagName==='INPUT')return;
                if(el.tagName==='BUTTON'&&el.type==='button')return;
                var b=el.getBoundingClientRect();
                if(b.height===0)return;
                if(b.top<r.bottom)return;                                   // je nad výberom, nevadí
                if(!best||b.top<best.top)best=b;
            });
            return best;
        }
        function place(box,btn,menu){
            var r=btn.getBoundingClientRect();
            var w=Math.max(r.width,230);
            menu.style.position='fixed';
            menu.style.left=Math.min(r.left,window.innerWidth-w-10)+'px';
            menu.style.width=w+'px';
            menu.style.right='auto';

            var below=window.innerHeight-r.bottom-12;
            var above=r.top-12;
            // Miesto pod výberom končí tam, kde začína odosielacie tlačidlo
            var stop=blocker(box);
            if(stop)below=Math.min(below,stop.top-r.bottom-8);

            if(below<190&&above>below){
                menu.style.top='auto';
                menu.style.bottom=(window.innerHeight-r.top+5)+'px';
                menu.style.maxHeight=Math.min(320,above)+'px';
            }else{
                menu.style.bottom='auto';
                menu.style.top=(r.bottom+5)+'px';
                menu.style.maxHeight=Math.max(120,Math.min(320,below))+'px';
            }
        }
        function close(box){
            box.classList.remove('is-open');
            var m=box.querySelector('.zc-ms-menu'); if(m)m.hidden=true;
            var b=box.querySelector('.zc-ms-btn'); if(b)b.setAttribute('aria-expanded','false');
        }
        function bind(box){
            if(box.dataset.msBound)return; box.dataset.msBound='1';
            var btn=box.querySelector('.zc-ms-btn'), menu=box.querySelector('.zc-ms-menu');
            btn.addEventListener('click',function(e){
                e.preventDefault(); e.stopPropagation();
                var open=box.classList.contains('is-open');
                document.querySelectorAll('.zc-ms.is-open').forEach(close);
                if(open)return;
                box.classList.add('is-open'); menu.hidden=false; btn.setAttribute('aria-expanded','true');
                place(box,btn,menu);
            });
            menu.addEventListener('click',function(e){ e.stopPropagation(); });
            menu.addEventListener('change',function(){ labelOf(box); });
            var all=menu.querySelector('[data-zc-ms-all]'), none=menu.querySelector('[data-zc-ms-none]');
            if(all)all.addEventListener('click',function(){menu.querySelectorAll('input').forEach(function(c){c.checked=true});labelOf(box)});
            if(none)none.addEventListener('click',function(){menu.querySelectorAll('input').forEach(function(c){c.checked=false});labelOf(box)});
            var done=menu.querySelector('[data-zc-ms-done]');
            if(done)done.addEventListener('click',function(){close(box)});
            labelOf(box);
        }
        function init(){ document.querySelectorAll('[data-zc-ms]').forEach(bind); }
        // Po programovom prepnutí políčok treba obnoviť popis v tlačidle
        window.zcMsRefresh=function(root){
            (root||document).querySelectorAll('[data-zc-ms]').forEach(function(b){bind(b);labelOf(b)});
        };
        document.addEventListener('click',function(){ document.querySelectorAll('.zc-ms.is-open').forEach(close); });
        document.addEventListener('keydown',function(e){ if(e.key==='Escape')document.querySelectorAll('.zc-ms.is-open').forEach(close); });

        // Pri scrollovaní okienko len presunieme za tlačidlom – nezatvárame ho.
        // Zatvorí sa až vtedy, keď tlačidlo odscrolluje mimo obrazovky.
        function reposition(){
            document.querySelectorAll('.zc-ms.is-open').forEach(function(box){
                var btn=box.querySelector('.zc-ms-btn'), menu=box.querySelector('.zc-ms-menu');
                if(!btn||!menu)return;
                var r=btn.getBoundingClientRect();
                if(r.bottom<0||r.top>window.innerHeight){close(box);return;}
                place(box,btn,menu);
            });
        }
        window.addEventListener('resize',reposition);
        window.addEventListener('scroll',function(e){
            // scrollovanie vnútri samotného okienka nechávame na pokoji
            var t=e.target;
            if(t&&t.closest&&t.closest('.zc-ms-menu'))return;
            reposition();
        },true);
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init); else init();
    })();
    </script>
    <?php
}

/**
 * Výber konkrétnych odberateľov – zoznam s vyhľadávaním.
 * Používa sa pri odosielaní newslettera v paneli aj vo wp-admine.
 */
function zcn_people_picker($id = 'zcnPeople', $limit = 800) {
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT email, name FROM " . zcn_table() . "
         WHERE status='active' ORDER BY name ASC, email ASC LIMIT %d", $limit
    ));
    ?>
    <div class="zc-people" id="<?php echo esc_attr($id); ?>">
        <input type="search" class="zc-people-search" placeholder="Hľadať meno alebo e-mail…"
               aria-label="Hľadať odberateľa">
        <div class="zc-people-list">
            <?php if ($rows): foreach ($rows as $r): ?>
            <label class="zc-people-row">
                <input type="checkbox" value="<?php echo esc_attr($r->email); ?>">
                <span><strong><?php echo esc_html($r->name ?: $r->email); ?></strong>
                    <?php if ($r->name): ?><em><?php echo esc_html($r->email); ?></em><?php endif; ?>
                </span>
            </label>
            <?php endforeach; else: ?>
            <p class="zc-people-empty">Zatiaľ nemáš žiadnych aktívnych odberateľov.</p>
            <?php endif; ?>
        </div>
        <div class="zc-people-foot">
            <button type="button" data-zc-people-all>Označiť zobrazených</button>
            <button type="button" data-zc-people-none>Zrušiť výber</button>
            <span class="zc-people-count">0 vybraných</span>
        </div>
    </div>
    <?php
    zcn_people_picker_assets();
}

function zcn_people_picker_assets() {
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
    <style id="zc-people-css">
    .zc-people{border:1.5px solid #E0D8CE;border-radius:10px;background:#fff;overflow:hidden}
    .zc-people *{box-sizing:border-box}
    .zc-people-search{width:100%;padding:11px 13px;border:none;border-bottom:1px solid #F1EBE0;
        font:500 13.5px/1.3 inherit;color:#2C2825;outline:none}
    .zc-people-list{max-height:260px;overflow:auto}
    .zc-people-row{display:flex;align-items:center;gap:10px;padding:9px 13px;cursor:pointer;
        border-bottom:1px solid #F7F3EC;font:500 13px/1.35 inherit;color:#2C2825}
    .zc-people-row:hover{background:#FBF8F2}
    .zc-people-row input{width:16px;height:16px;min-height:auto;accent-color:#B8A47A;margin:0;flex:0 0 auto}
    .zc-people-row strong{font-weight:700}
    .zc-people-row em{font-style:normal;color:#9A8660;margin-left:6px}
    .zc-people-empty{padding:16px;margin:0;font-size:13px;color:#9A8660}
    .zc-people-foot{display:flex;align-items:center;gap:7px;padding:9px 11px;background:#FBF8F2;
        border-top:1px solid #F1EBE0}
    .zc-people-foot button{padding:6px 10px;border:1px solid #E0D8CE;border-radius:7px;background:#fff;
        color:#6B6560;font:600 11px/1.2 inherit;cursor:pointer}
    .zc-people-foot button:hover{border-color:#B8A47A;color:#2C2825}
    .zc-people-count{margin-left:auto;font-size:11.5px;color:#9A8660;font-weight:700}
    </style>
    <script id="zc-people-js">
    (function(){
        function count(box){
            var n=box.querySelectorAll('.zc-people-row input:checked').length;
            var out=box.querySelector('.zc-people-count');
            if(out)out.textContent=n+' vybraných';
        }
        function bind(box){
            if(box.dataset.peopleBound)return; box.dataset.peopleBound='1';
            var search=box.querySelector('.zc-people-search');
            if(search)search.addEventListener('input',function(){
                var q=this.value.toLowerCase();
                box.querySelectorAll('.zc-people-row').forEach(function(r){
                    r.style.display=r.textContent.toLowerCase().indexOf(q)>-1?'flex':'none';
                });
            });
            box.addEventListener('change',function(){count(box)});
            var all=box.querySelector('[data-zc-people-all]'), none=box.querySelector('[data-zc-people-none]');
            if(all)all.addEventListener('click',function(){
                box.querySelectorAll('.zc-people-row').forEach(function(r){
                    if(r.style.display!=='none')r.querySelector('input').checked=true;
                });
                count(box);
            });
            if(none)none.addEventListener('click',function(){
                box.querySelectorAll('.zc-people-row input').forEach(function(c){c.checked=false});
                count(box);
            });
            count(box);
        }
        function init(){ document.querySelectorAll('.zc-people').forEach(bind); }
        window.zcPeoplePicked=function(id){
            var box=document.getElementById(id); if(!box)return [];
            return [].map.call(box.querySelectorAll('.zc-people-row input:checked'),function(c){return c.value});
        };
        if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init); else init();
    })();
    </script>
    <?php
}
