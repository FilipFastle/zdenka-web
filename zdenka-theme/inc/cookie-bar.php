<?php
defined('ABSPATH') || exit;
// ── Cookie lišta (GDPR/ePrivacy) — slim, full-width, súhlas si pamätá ──────
add_action('wp_footer', function() {
    if (is_admin()) return;
    // Ak už raz rozhodol (cookie prišla v requeste), lištu vôbec nevykresli.
    if (!empty($_COOKIE['zc_cookie_consent'])) return;
    $priv = home_url('/ochrana-osobnych-udajov/');
    ?>
    <div id="zcCookie" class="zc-cookie" role="dialog" aria-label="Súhlas s cookies" hidden>
        <div class="zc-cookie-in">
            <div class="zc-cookie-txt">
                Používame cookies na správne fungovanie webu a meranie návštevnosti. Kliknutím na „Prijať" súhlasíte s ich používaním.
                <a href="<?php echo esc_url($priv); ?>">Viac info</a>
            </div>
            <div class="zc-cookie-btns">
                <button type="button" class="zc-cookie-btn zc-cookie-decline" onclick="zcCookieSet(0)">Odmietnuť</button>
                <button type="button" class="zc-cookie-btn zc-cookie-accept" onclick="zcCookieSet(1)">Prijať</button>
            </div>
        </div>
    </div>
    <style>
    .zc-cookie{position:fixed;left:0;right:0;bottom:0;width:100%;z-index:9995;
        background:var(--white,#fff);border-top:1px solid var(--border,#E0D8CE);
        box-shadow:0 -4px 24px rgba(40,32,20,.10);
        animation:zcCookieIn .45s var(--ease,ease) both}
    @keyframes zcCookieIn{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:none}}
    .zc-cookie-in{max-width:1180px;margin:0 auto;padding:11px 24px;
        display:flex;align-items:center;gap:18px;flex-wrap:wrap}
    .zc-cookie-txt{flex:1;min-width:240px;font-size:12.5px;line-height:1.55;color:var(--text,#2C2825)}
    .zc-cookie-txt a{color:var(--accent-txt,#7C5E33);text-decoration:underline}
    .zc-cookie-btns{display:flex;gap:8px;flex-shrink:0}
    .zc-cookie-btn{padding:8px 20px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;
        font-family:var(--sans,sans-serif);border:1.5px solid var(--border,#E0D8CE);transition:all .2s;white-space:nowrap}
    .zc-cookie-decline{background:transparent;color:var(--muted,#7A7068)}
    .zc-cookie-decline:hover{border-color:#bbb;color:var(--dark,#1C1A18)}
    .zc-cookie-accept{background:var(--accent,#B8A47A);color:var(--dark,#1C1A18);border-color:var(--accent,#B8A47A)}
    .zc-cookie-accept:hover{background:var(--accent-dk,#9A8660)}
    @media(max-width:600px){
        .zc-cookie-in{padding:12px 16px;gap:12px}
        .zc-cookie-btns{width:100%}
        .zc-cookie-btn{flex:1}
    }
    </style>
    <script>
    (function(){
        var bar=document.getElementById('zcCookie');
        if(!bar)return;
        var KEY='zc_cookie_consent';
        function hasConsent(){
            if(document.cookie.indexOf(KEY+'=')!==-1)return true;   // cookie má prednosť
            try{ if(localStorage.getItem(KEY))return true; }catch(e){}
            return false;
        }
        if(hasConsent()){ bar.parentNode&&bar.parentNode.removeChild(bar); return; } // rozhodnuté → preč
        bar.hidden=false;
        window.zcCookieSet=function(v){
            var val=v?'accepted':'declined';
            try{localStorage.setItem(KEY,val);}catch(e){}
            // cookie na 180 dní (prežije refresh aj prechod na inú stránku)
            var d=new Date();d.setTime(d.getTime()+180*24*60*60*1000);
            document.cookie=KEY+'='+val+';expires='+d.toUTCString()+';path=/;SameSite=Lax';
            bar.parentNode&&bar.parentNode.removeChild(bar);
            document.dispatchEvent(new CustomEvent('zc-cookie-consent',{detail:{accepted:!!v}}));
        };
    })();
    </script>
    <?php
}, 20);
