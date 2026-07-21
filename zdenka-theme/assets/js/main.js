/* ─────────────────────────────────────────────────────────────
   Page transition handled by inc/transition.php (in <head>)
   This file handles UI interactions only.
───────────────────────────────────────────────────────────────*/
document.addEventListener('DOMContentLoaded', function () {

var ham = document.getElementById('zcHam');
var hdr = document.getElementById('zcHeader');
var logoUrl = (window.zcData && window.zcData.logoUrl) ? window.zcData.logoUrl : '';

/* ══ REAL VIEWPORT HEIGHT (JS fallback for older browsers) ════ */
function setRealVh() {
    document.documentElement.style.setProperty('--real-vh', (window.innerHeight * 0.01) + 'px');
}
setRealVh();
window.addEventListener('resize', setRealVh, { passive: true });

/* ══ MOBILE MENU OVERLAY ══════════════════════════════════════ */
if (!document.getElementById('zcOvCSS')) {
    var menuCSS = document.createElement('style');
    menuCSS.id = 'zcOvCSS';
    menuCSS.textContent = [
        /* Base – always hidden */
        '#zcOv{position:fixed;inset:0;z-index:9998;display:flex;flex-direction:column;align-items:center;justify-content:center;',
        '  visibility:hidden;',
        /* Delay visibility:hidden until close animation ends (.56s) */
        '  transition:visibility 0s .56s;}',

        /* Open – instant visibility */
        '#zcOv.open{visibility:visible;transition:visibility 0s 0s;}',

        /* Keep visible while closing animation plays */
        '#zcOv.closing{visibility:visible;transition:none;}',

        /* Curtain */
        '.zco-bg{position:absolute;inset:0;background:linear-gradient(135deg,#F5EEDF,#EBDCC0);transform:scaleY(0);transform-origin:top;',
        '  transition:transform .55s cubic-bezier(.86,0,.07,1);}',
        '#zcOv.open .zco-bg{transform:scaleY(1);}',

        /* Logo */
        '.zco-logo{position:relative;z-index:2;width:80px;height:80px;filter:brightness(0);',
        '  opacity:0;transform:scale(.6) rotate(-14deg);',
        '  transition:opacity .4s ease .28s,transform .52s cubic-bezier(.34,1.56,.64,1) .28s;}',
        '#zcOv.open .zco-logo{opacity:1;transform:scale(1) rotate(0);}',
        /* Closing: logo fades/shrinks quickly */
        '#zcOv.closing .zco-logo{opacity:0;transform:scale(.7) rotate(8deg);',
        '  transition:opacity .18s ease,transform .18s ease;}',

        /* Divider */
        '.zco-div{position:relative;z-index:2;width:32px;height:1px;background:rgba(154,134,96,.5);',
        '  margin:12px 0;opacity:0;transition:opacity .3s .5s;}',
        '#zcOv.open .zco-div{opacity:1;}',
        '#zcOv.closing .zco-div{opacity:0;transition:opacity .1s;}',

        /* Nav links */
        '.zco-nav{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;}',
        '.zco-nav a{font-family:"Playfair Display",Georgia,serif;font-size:clamp(28px,7vw,42px);',
        '  font-weight:700;color:#1C1A18;text-decoration:none;line-height:1.3;padding:3px 0;',
        '  opacity:0;transform:translateY(20px);',
        '  transition:opacity .32s,transform .36s,color .18s;display:block;}',
        '.zco-nav a:hover{color:#9A8660;}',
        '#zcOv.open .zco-nav a:nth-child(1){opacity:1;transform:none;transition-delay:.36s;}',
        '#zcOv.open .zco-nav a:nth-child(2){opacity:1;transform:none;transition-delay:.43s;}',
        '#zcOv.open .zco-nav a:nth-child(3){opacity:1;transform:none;transition-delay:.50s;}',
        '#zcOv.open .zco-nav a:nth-child(4){opacity:1;transform:none;transition-delay:.57s;}',
        '#zcOv.open .zco-nav a:nth-child(5){opacity:1;transform:none;transition-delay:.64s;}',
        '#zcOv.open .zco-nav a:nth-child(6){opacity:1;transform:none;transition-delay:.71s;}',
        /* Closing: links snap out quickly */
        '#zcOv.closing .zco-nav a{opacity:0;transform:translateY(-8px);transition:opacity .15s,transform .15s;}',

        /* Close button */
        '.zco-close{position:absolute;top:18px;right:18px;z-index:10;width:46px;height:46px;',
        '  background:rgba(28,26,24,.06);border:1px solid rgba(28,26,24,.12);',
        '  border-radius:50%;display:flex;align-items:center;justify-content:center;',
        '  color:#1C1A18;cursor:pointer;opacity:0;transition:opacity .25s .2s,background .2s;}',
        '#zcOv.open .zco-close{opacity:1;}',
        '#zcOv.closing .zco-close{opacity:0;transition:opacity .1s;}',
        '.zco-close:hover{background:rgba(28,26,24,.12);}',

        /* Hamburger → X */
        '.zc-hamburger span{transition:transform .28s,opacity .22s;}',
        '.nav-open .zc-hamburger span{background:#1C1A18 !important;}',
        '.nav-open .zc-hamburger span:nth-child(1){transform:rotate(45deg) translate(4.5px,4.5px);}',
        '.nav-open .zc-hamburger span:nth-child(2){opacity:0;transform:translateX(-8px);}',
        '.nav-open .zc-hamburger span:nth-child(3){transform:rotate(-45deg) translate(4.5px,-4.5px);}',

        '@media(min-width:769px){#zcOv{display:none!important}}',
    ].join('');
    document.head.appendChild(menuCSS);
}

/* Build overlay (once) */
if (!document.getElementById('zcOv')) {
    var ov = document.createElement('div');
    ov.id = 'zcOv';
    ov.innerHTML = [
        '<div class="zco-bg"></div>',
        '<button class="zco-close" id="zcClose">',
        '  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">',
        '    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        '  </svg></button>',
        logoUrl ? '<img class="zco-logo" src="' + logoUrl + '" style="filter:brightness(0)" alt="">' : '',
        '<div class="zco-div"></div>',
        '<nav class="zco-nav" id="zcOvNav"></nav>',
    ].join('');
    document.body.appendChild(ov);

    var nav = document.getElementById('zcOvNav');
    [['/', 'Domov'], ['/o-mne/', 'O mne'], ['/ako-pracujem/', 'Ako pracujem'],
     ['/ponuky/', 'Ponuky'], ['/odhad/', 'Odhad ZDARMA'], ['/kontakt/', 'Kontakt']
    ].forEach(function (item) {
        var a = document.createElement('a');
        a.href = location.origin + item[0];
        a.textContent = item[1];
        nav.appendChild(a);
    });
}

var ov = document.getElementById('zcOv');
var isClosing = false;

function openMenu() {
    isClosing = false;
    ov.classList.remove('closing');
    ov.classList.add('open');
    if (hdr) hdr.classList.add('nav-open');
    document.body.style.overflow = 'hidden';
}

function closeMenu() {
    if (isClosing) return;
    isClosing = true;

    // Reset overflow IMMEDIATELY so hamburger gets touch events right away
    document.body.style.overflow = '';

    ov.classList.add('closing');
    ov.classList.remove('open');
    setTimeout(function () {
        ov.classList.remove('closing');
        if (hdr) hdr.classList.remove('nav-open');
        isClosing = false;
    }, 580);
}

// Re-bind hamburger (clone to remove old listeners)
if (ham) {
    var h2 = ham.cloneNode(true);
    ham.parentNode.replaceChild(h2, ham);
    ham = h2;
    ham.addEventListener('click', openMenu);
}
var cb = document.getElementById('zcClose');
if (cb) {
    var c2 = cb.cloneNode(true);
    cb.parentNode.replaceChild(c2, cb);
    c2.addEventListener('click', closeMenu);
}
if (ov) {
    ov.querySelectorAll('.zco-nav a').forEach(function (a) {
        a.addEventListener('click', function () {
            // Don't wait for close animation on navigation
            ov.classList.remove('open', 'closing');
            if (hdr) hdr.classList.remove('nav-open');
            document.body.style.overflow = '';
            isClosing = false;
        });
    });
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

/* ══ HEADER SCROLL ════════════════════════════════════════════ */
if (hdr) {
    window.removeEventListener('scroll', window._zcScroll);
    window._zcScroll = function () { hdr.classList.toggle('scrolled', scrollY > 20); };
    window.addEventListener('scroll', window._zcScroll, { passive: true });
    window._zcScroll();
}

/* ══ TRANSPARENT HEADER ══════════════════════════════════════ */
var hero = document.getElementById('zcHomeHero') || document.getElementById('ppHero');
if (hero && hdr) {
    window.removeEventListener('scroll', window._zcTrans);
    window._zcTrans = function () {
        // scrollY namiesto rect.bottom – sticky hero má bottom vždy = viewport
        hdr.classList.toggle('transparent', window.scrollY < hero.offsetHeight - 60);
    };
    window.addEventListener('scroll', window._zcTrans, { passive: true });
    window._zcTrans();
} else if (hdr) {
    hdr.classList.remove('transparent');
}

/* ══ CONTACT FORM ════════════════════════════════════════════ */
var form = document.getElementById('zcContactForm');
var fmsg = document.getElementById('zcFormMsg');
if (form) {
    var f2 = form.cloneNode(true);
    form.parentNode.replaceChild(f2, form);
    form = f2;
    fmsg = document.getElementById('zcFormMsg');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('[type=submit]'), orig = btn.textContent;
        btn.textContent = 'Odosielam…'; btn.disabled = true;
        var data = new FormData(form);
        data.append('action', 'zc_contact');
        data.append('nonce', (window.zcData || {}).nonce || '');
        fetch((window.zcData || {}).ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (fmsg) {
                fmsg.style.cssText = 'display:block;padding:14px 18px;border-radius:10px;margin-bottom:14px;font-size:14px;border:1px solid;';
                fmsg.style.background  = res.success ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
                fmsg.style.color       = res.success ? '#15803d' : '#dc2626';
                fmsg.style.borderColor = res.success ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)';
                fmsg.textContent = res.data.message;
            }
            if (res.success) form.reset();
            btn.textContent = orig; btn.disabled = false;
        });
    });
}

/* ══ SCROLL TO TOP ═══════════════════════════════════════════ */
var stb = document.getElementById('zcScrollTop');
if (stb) {
    window.removeEventListener('scroll', window._zcStt);
    window._zcStt = function () { stb.classList.toggle('visible', scrollY > 400); };
    window.addEventListener('scroll', window._zcStt, { passive: true });
    stb.onclick = function () { scrollTo({ top: 0, behavior: 'smooth' }); };
}

/* ══ SCROLL REVEAL ═══════════════════════════════════════════ */
if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.zc-card,.zc-testimonial,.zc-step,.zc-prop-card').forEach(function (el) {
        if (!el.style.opacity) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(22px)';
            el.style.transition = 'opacity .5s ease,transform .5s ease';
            obs.observe(el);
        }
    });
}


/* ══ FAVORITES (obľúbené) ════════════════════════════════════ */
(function() {
    var KEY = 'zc_favorites';

    function getFavs() {
        try { return JSON.parse(localStorage.getItem(KEY) || '[]'); }
        catch(e) { return []; }
    }
    function setFavs(arr) {
        try { localStorage.setItem(KEY, JSON.stringify(arr)); } catch(e) {}
    }

    // Global toggle function (called from onclick)
    window.zcToggleFav = function(btn, id) {
        id = String(id);
        var favs = getFavs();
        var idx = favs.indexOf(id);
        if (idx === -1) {
            favs.push(id);
            btn.classList.add('is-fav');
            btn.title = 'Odobrať z obľúbených';
        } else {
            favs.splice(idx, 1);
            btn.classList.remove('is-fav');
            btn.title = 'Pridať do obľúbených';
        }
        // Pop animation
        btn.classList.remove('pop');
        void btn.offsetWidth; // reflow to restart animation
        btn.classList.add('pop');
        setFavs(favs);
        updateFavBadge();
    };

    // Expose so AJAX-loaded grids can re-mark
    window.zcMarkFavs = function(){ markFavs(); };

    // Mark already-favorited buttons on page load
    function markFavs() {
        var favs = getFavs();
        document.querySelectorAll('.zc-fav-btn').forEach(function(btn) {
            var id = btn.dataset.id;
            if (favs.indexOf(String(id)) !== -1) {
                btn.classList.add('is-fav');
                btn.title = 'Odobrať z obľúbených';
            }
        });
    }

    // Floating badge with count
    function updateFavBadge() {
        var favs = getFavs();
        var badge = document.getElementById('zcFavBadge');
        if (favs.length === 0) {
            if (badge) badge.remove();
            return;
        }
        if (!badge) {
            badge = document.createElement('a');
            badge.id = 'zcFavBadge';
            badge.href = '/oblubene/';
            badge.className = 'zc-fav-badge';
            badge.style.cssText = 'position:fixed;bottom:80px;right:28px;background:#fff;color:#1C1A18;border:1.5px solid #B8A47A;padding:11px 18px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;z-index:90;box-shadow:0 4px 16px rgba(184,164,122,.35);display:flex;align-items:center;gap:8px;transition:transform .2s';
            badge.onmouseenter = function(){ this.style.transform='translateY(-3px)'; };
            badge.onmouseleave = function(){ this.style.transform='none'; };
            document.body.appendChild(badge);
        }
        badge.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="#e53e3e" stroke="#e53e3e" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Obľúbené (' + favs.length + ')';
    }

    // Expose for favorites page
    window.zcGetFavorites = getFavs;
    window.zcUpdateFavBadge = updateFavBadge;

    markFavs();
    updateFavBadge();
})();


});