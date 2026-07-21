<?php
defined('ABSPATH') || exit;
// ── Cookie lišta (GDPR/ePrivacy) — súhlas uložený v localStorage ────────
add_action('wp_footer', function() {
    if (is_admin()) return;
    $priv = home_url('/ochrana-osobnych-udajov/');
    ?>
    <div id="zcCookie" class="zc-cookie" role="dialog" aria-label="Súhlas s cookies" hidden>
        <div class="zc-cookie-txt">
            Používame cookies na správne fungovanie webu a meranie návštevnosti. Kliknutím na „Prijať" súhlasíte s ich používaním.
            <a href="<?php echo esc_url($priv); ?>">Viac info</a>
        </div>
        <div class="zc-cookie-btns">
            <button type="button" class="zc-cookie-btn zc-cookie-decline" onclick="zcCookieSet(0)">Odmietnuť</button>
            <button type="button" class="zc-cookie-btn zc-cookie-accept" onclick="zcCookieSet(1)">Prijať</button>
        </div>
    </div>
    <style>
    .zc-cookie{position:fixed;left:16px;right:16px;bottom:16px;z-index:9995;max-width:560px;margin:0 auto;
        background:var(--white,#fff);border:1px solid var(--border,#E0D8CE);border-radius:14px;
        box-shadow:0 12px 40px rgba(40,32,20,.18);padding:18px 20px;display:flex;gap:16px;
        align-items:center;flex-wrap:wrap;animation:zcCookieIn .5s var(--ease,ease) both}
    @keyframes zcCookieIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
    .zc-cookie-txt{flex:1;min-width:220px;font-size:13px;line-height:1.6;color:var(--text,#2C2825)}
    .zc-cookie-txt a{color:var(--accent-txt,#7C5E33);text-decoration:underline}
    .zc-cookie-btns{display:flex;gap:8px}
    .zc-cookie-btn{padding:9px 18px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;
        font-family:var(--sans,sans-serif);border:1.5px solid var(--border,#E0D8CE);transition:all .2s}
    .zc-cookie-decline{background:transparent;color:var(--muted,#7A7068)}
    .zc-cookie-decline:hover{border-color:#bbb;color:var(--dark,#1C1A18)}
    .zc-cookie-accept{background:var(--accent,#B8A47A);color:var(--dark,#1C1A18);border-color:var(--accent,#B8A47A)}
    .zc-cookie-accept:hover{background:var(--accent-dk,#9A8660)}
    @media(max-width:520px){.zc-cookie{flex-direction:column;align-items:stretch}.zc-cookie-btns{justify-content:stretch}.zc-cookie-btn{flex:1}}
    </style>
    <script>
    (function(){
        var bar=document.getElementById('zcCookie');
        if(!bar)return;
        var KEY='zc_cookie_consent';
        function hasConsent(){
            // Cookie má prednosť (prežije aj vymazanie localStorage), potom localStorage
            if(document.cookie.indexOf(KEY+'=')!==-1)return true;
            try{ if(localStorage.getItem(KEY))return true; }catch(e){}
            return false;
        }
        if(hasConsent())return;               // už rozhodnuté → lištu vôbec nezobraz
        bar.hidden=false;
        window.zcCookieSet=function(v){
            var val=v?'accepted':'declined';
            try{localStorage.setItem(KEY,val);}catch(e){}
            // cookie na 180 dní
            var d=new Date();d.setTime(d.getTime()+180*24*60*60*1000);
            document.cookie=KEY+'='+val+';expires='+d.toUTCString()+';path=/;SameSite=Lax';
            bar.style.display='none';
            document.dispatchEvent(new CustomEvent('zc-cookie-consent',{detail:{accepted:!!v}}));
        };
    })();
    </script>
    <?php
}, 20);
