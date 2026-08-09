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
        '  visibility:hidden;pointer-events:none;',
        /* Delay visibility:hidden until close animation ends (.56s) */
        '  transition:visibility 0s .56s;}',

        /* Open – instant visibility */
        '#zcOv.open{visibility:visible;pointer-events:auto;transition:visibility 0s 0s;}',

        /* Keep visible while closing animation plays */
        '#zcOv.closing{visibility:visible;pointer-events:none;transition:none;}',

        /* Curtain */
        '.zco-bg{position:absolute;inset:0;background:linear-gradient(135deg,#F5EEDF,#EBDCC0);transform:scaleY(0);transform-origin:top;',
        '  transition:transform .55s cubic-bezier(.86,0,.07,1);}',
        '#zcOv.open .zco-bg{transform:scaleY(1);}',

        /* Logo */
        /* Rozmery viaže výška okna – menu sa musí vojsť aj na zoomnutom PC
           či na nízkom okne, bez toho aby sa muselo scrollovať. */
        '#zcOv{padding:min(4vh,28px) 16px;overflow:hidden;}',
        '.zco-logo{position:relative;z-index:2;width:min(80px,9vh);height:min(80px,9vh);filter:brightness(0);',
        '  opacity:0;transform:scale(.6) rotate(-14deg);',
        '  transition:opacity .4s ease .28s,transform .52s cubic-bezier(.34,1.56,.64,1) .28s;}',
        '#zcOv.open .zco-logo{opacity:1;transform:scale(1) rotate(0);}',
        /* Closing: logo fades/shrinks quickly */
        '#zcOv.closing .zco-logo{opacity:0;transform:scale(.7) rotate(8deg);',
        '  transition:opacity .18s ease,transform .18s ease;}',

        /* Divider */
        '.zco-div{position:relative;z-index:2;width:32px;height:1px;background:rgba(154,134,96,.5);',
        '  margin:min(12px,1.4vh) 0;opacity:0;transition:opacity .3s .5s;}',
        '#zcOv.open .zco-div{opacity:1;}',
        '#zcOv.closing .zco-div{opacity:0;transition:opacity .1s;}',

        /* Nav links */
        '.zco-nav{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;}',
        '.zco-nav{width:100%;max-height:100%;overflow-y:auto;overscroll-behavior:contain;',
        '  -webkit-overflow-scrolling:touch;scrollbar-width:none;}',
        '.zco-nav::-webkit-scrollbar{width:0;height:0;}',
        '.zco-nav a{font-family:"Playfair Display",Georgia,serif;',
        /* menšie z dvoch: podľa šírky aj podľa výšky – rozhoduje ten tesnejší */
        '  font-size:clamp(15px,min(6.6vw,4.4vh),42px);',
        '  font-weight:700;color:#1C1A18;text-decoration:none;line-height:1.24;padding:min(3px,.4vh) 0;',
        '  opacity:0;transform:translateY(20px);',
        '  transition:opacity .32s,transform .36s,color .18s;display:block;}',
        '.zco-nav a:hover{color:#7C5E33;}',
        '#zcOv.open .zco-nav a:nth-child(1){opacity:1;transform:none;transition-delay:.36s;}',
        '#zcOv.open .zco-nav a:nth-child(2){opacity:1;transform:none;transition-delay:.43s;}',
        '#zcOv.open .zco-nav a:nth-child(3){opacity:1;transform:none;transition-delay:.50s;}',
        '#zcOv.open .zco-nav a:nth-child(4){opacity:1;transform:none;transition-delay:.57s;}',
        '#zcOv.open .zco-nav a:nth-child(5){opacity:1;transform:none;transition-delay:.64s;}',
        '#zcOv.open .zco-nav a:nth-child(6){opacity:1;transform:none;transition-delay:.71s;}',
        '#zcOv.open .zco-nav a:nth-child(7){opacity:1;transform:none;transition-delay:.78s;}',
        '#zcOv.open .zco-nav a:nth-child(8){opacity:1;transform:none;transition-delay:.85s;}',
        '#zcOv.open .zco-nav a:nth-child(9){opacity:1;transform:none;transition-delay:.92s;}',
        '#zcOv.open .zco-nav a:nth-child(10){opacity:1;transform:none;transition-delay:.99s;}',
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

        '@media(max-height:700px){.zco-logo{width:min(58px,7.6vh);height:min(58px,7.6vh);}}',
        '@media(max-height:560px){.zco-logo{width:min(42px,6.6vh);height:min(42px,6.6vh);}',
        '  .zco-div{margin:min(7px,.9vh) 0;}',
        '  .zco-nav a{font-size:clamp(13px,min(6vw,5vh),26px);line-height:1.2;padding:min(2px,.3vh) 0;}',
        '  .zco-close{top:10px;right:10px;width:38px;height:38px;}}',
        '@media(max-height:380px){.zco-logo,.zco-div{display:none;}}',
        '@media(min-width:1280px){#zcOv{display:none!important}}',
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
    // Poradie musí sedieť s PC menu v header.php. Kontakt sa pridáva až
    // za prípadný EBOOK, aby bol vždy posledný.
    [['/', 'Domov'], ['/ponuky/', 'Ponuky'], ['/o-mne/', 'O mne'],
     ['/ako-pracujem/', 'Ako pracujem'], ['/odhad/', 'Odhad ZDARMA'],
     ['/referencie/', 'Referencie'], ['/newsletter/', 'Newsletter']
    ].forEach(function (item) {
        var a = document.createElement('a');
        a.href = location.origin + item[0];
        a.textContent = item[1];
        nav.appendChild(a);
    });
    // Ebook položka sa na mobile smie zobraziť iba pri výslovnom serverovom
    // stave „aktívny“. DOM fallback vedel po vypnutí zachytiť starý/cachovaný
    // odkaz a EBOOK potom zostal v mobilnom menu.
    var ebookOn = Boolean(window.zcData && Number(zcData.ebookOn) === 1);
    if (ebookOn) {
        var eb = document.createElement('a');
        eb.href = '#';
        eb.textContent = 'EBOOK';
        eb.setAttribute('data-zc-ebook-open', '');
        eb.style.color = '#B8A47A';
        eb.addEventListener('click', function (e) {
            e.preventDefault();
            ov.classList.remove('open', 'closing');
            if (hdr) hdr.classList.remove('nav-open');
            document.body.style.overflow = '';
            isClosing = false;
            if (window.zcEbookOpen) window.zcEbookOpen();
        });
        nav.appendChild(eb);
    }

    var kontakt = document.createElement('a');
    kontakt.href = location.origin + '/kontakt/';
    kontakt.textContent = 'Kontakt';
    nav.appendChild(kontakt);
}

var ov = document.getElementById('zcOv');
var isClosing = false;

var closeTimer = null;

function openMenu() {
    // Dobiehajúce zatváranie zrušíme, inak by jeho časovač o chvíľu
    // zhodil triedy a práve otvorené menu by sa samo zavrelo.
    if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
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
    closeTimer = setTimeout(function () {
        closeTimer = null;
        ov.classList.remove('closing');
        if (hdr) hdr.classList.remove('nav-open');
        isClosing = false;
    }, 580);
}

// Hamburger prepína – druhý klik menu zavrie, nezasekne sa v otvorenom stave
function toggleMenu() {
    if (ov.classList.contains('open')) closeMenu(); else openMenu();
}

// Re-bind hamburger (clone to remove old listeners)
if (ham) {
    var h2 = ham.cloneNode(true);
    ham.parentNode.replaceChild(h2, ham);
    ham = h2;
    ham.addEventListener('click', toggleMenu);
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
        // Priehľadný len na úplnom vrchu; hneď pri prvom scrollnutí → liquid glass
        hdr.classList.toggle('transparent', window.scrollY < 24);
    };
    window.addEventListener('scroll', window._zcTrans, { passive: true });
    window._zcTrans();
} else if (hdr) {
    hdr.classList.remove('transparent');
}

/* ══ VÝBER TYPU PONÚK PO PRIHLÁSENÍ NA NEWSLETTER ════════════
   Vo formulári sa na to nepýtame, aby nezdržiaval. Kto si zaškrtne
   newsletter, dostane hneď po odoslaní malé okno s možnosťami.
   Kto ho zavrie alebo zvolí „Žiadne", dostáva len novinky a ebook. */
function zcInterestModal(token) {
    var opts = (window.zcData || {}).interests || [];
    if (!opts.length || document.getElementById('zcIntModal')) return;

    var wrap = document.createElement('div');
    wrap.id = 'zcIntModal';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'O aké ponuky máte záujem?');
    wrap.style.cssText = 'position:fixed;inset:0;z-index:100000;display:flex;align-items:center;' +
        'justify-content:center;padding:20px;background:rgba(20,17,14,.55);opacity:0;transition:opacity .25s';

    var boxes = opts.map(function (g) {
        var items = (g.items || []).map(function (o) {
            return '<label class="zci-opt"><input type="checkbox" value="' + o.value + '"><span>' +
                o.label + '</span></label>';
        }).join('');
        return '<div class="zci-grp">' + (g.group || '') + '</div>' + items;
    }).join('');

    wrap.innerHTML =
        '<style>' +
        '#zcIntModal .zci-card{background:#fff;border-radius:16px;max-width:440px;width:100%;' +
        'box-shadow:0 24px 64px rgba(0,0,0,.28);font-family:inherit;max-height:90dvh;max-height:90vh;' +
        'display:flex;flex-direction:column;overflow:hidden}' +
        /* Roluje sa len zoznam možností – tlačidlá musia byť vidieť vždy,
           inak ich na nižšom telefóne používateľ vôbec nenájde. */
        '#zcIntModal .zci-body{overflow-y:auto;padding:26px 26px 4px;flex:1 1 auto;min-height:0}' +
        '#zcIntModal .zci-foot{flex:0 0 auto;padding:14px 26px 20px;border-top:1px solid #EDE6DA;background:#fff}' +
        '#zcIntModal .zci-grp{font:800 10px/1.4 inherit;letter-spacing:1.2px;text-transform:uppercase;color:#7C5E33;margin:16px 0 8px}' +
        '#zcIntModal .zci-opt{display:flex;align-items:center;gap:10px;padding:10px 13px;border:1.5px solid #E2DACE;' +
        'border-radius:10px;margin-bottom:6px;cursor:pointer;font:600 14px/1.3 inherit;color:#2C2825}' +
        '#zcIntModal .zci-opt:hover{border-color:#B8A47A;background:#FBF8F2}' +
        '#zcIntModal .zci-opt input{width:17px;height:17px;accent-color:#B8A47A;flex:0 0 auto}' +
        '#zcIntModal .zci-head{text-align:center}' +
        '#zcIntModal .zci-check{width:40px;height:40px;margin:0 auto 10px;border-radius:50%;' +
        'display:flex;align-items:center;justify-content:center;background:#E8F5EC;color:#15803d}' +
        '#zcIntModal h3{margin:0 0 7px;font-family:"Playfair Display",Georgia,serif;' +
        'font-size:21px;font-weight:700;color:#1C1A18}' +
        '#zcIntModal .zci-lead{margin:0;font:400 13.5px/1.65 inherit;color:#6B6560}' +
        '#zcIntModal .zci-sep{height:1px;background:#EDE6DA;margin:17px 0 14px}' +
        '#zcIntModal .zci-ask{margin:0 0 2px;font:700 13.5px/1.5 inherit;color:#2C2825}' +
        '#zcIntModal .zci-note{margin:11px 0 16px;font:400 12px/1.6 inherit;color:#7C5E33}' +
        '#zcIntModal .zci-btn{display:block;width:100%;padding:13px;border:none;border-radius:9px;' +
        'background:#B8A47A;color:#1C1A18;font:700 14px/1.2 inherit;cursor:pointer;transition:background .2s}' +
        '#zcIntModal .zci-btn:hover{background:#9A8660}' +
        '#zcIntModal .zci-skip{display:block;width:100%;margin-top:7px;padding:10px;border:none;' +
        'background:none;color:#7C5E33;font:600 12.5px/1.3 inherit;cursor:pointer;text-decoration:underline;' +
        'text-underline-offset:3px}' +
        '#zcIntModal .zci-skip:hover{color:#7C5E33}' +
        '</style>' +
        '<div class="zci-card">' +
        '<div class="zci-body">' +
        '<div class="zci-head">' +
        '<div class="zci-check" aria-hidden="true">' +
        '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' +
        '</div>' +
        '<h3>Odber máte zapnutý</h3>' +
        '<p class="zci-lead">Vaša adresa je v zozname odberateľov. Píšeme, len keď ' +
        'pribudne nová ponuka alebo niečo naozaj užitočné.</p>' +
        '</div>' +
        '<div class="zci-sep"></div>' +
        '<p class="zci-ask">Chcete dostávať len časť ponúk? Označte, čo vás zaujíma.</p>' +
        boxes +
        '<p class="zci-note">Ak nič neoznačíte, posielame všetko. Zmeniť sa to dá kedykoľvek ' +
        'cez odkaz v ktoromkoľvek e-maile.</p>' +
        '</div>' +
        '<div class="zci-foot">' +
        '<div id="zcIntMsg" style="display:none;margin-bottom:10px;font-size:13px;color:#15803d"></div>' +
        '<button type="button" data-save class="zci-btn">Uložiť výber</button>' +
        '<button type="button" data-close class="zci-skip">Nechať všetky ponuky</button>' +
        '</div>' +
        '</div>';

    document.body.appendChild(wrap);
    requestAnimationFrame(function () { wrap.style.opacity = '1'; });

    function close() {
        wrap.style.opacity = '0';
        setTimeout(function () { if (wrap.parentNode) wrap.parentNode.removeChild(wrap); }, 260);
        document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') close(); }
    document.addEventListener('keydown', onKey);

    wrap.addEventListener('click', function (e) {
        if (e.target === wrap || e.target.hasAttribute('data-close')) { close(); return; }
        if (!e.target.hasAttribute('data-save')) return;

        var picks = [].map.call(wrap.querySelectorAll('input:checked'), function (c) { return c.value; });
        e.target.disabled = true;
        e.target.textContent = 'Ukladám…';

        var d = new FormData();
        d.append('action', 'zcn_set_interest');
        d.append('nonce', (window.zcData || {}).nlNonce || '');
        d.append('token', token);
        d.append('interest', picks.join(','));
        fetch((window.zcData || {}).ajaxurl || '/wp-admin/admin-ajax.php', { method: 'POST', body: d })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var m = wrap.querySelector('#zcIntMsg');
            if (m) {
                m.style.display = 'block';
                m.style.color = res.success ? '#15803d' : '#dc2626';
                m.textContent = (res.data && res.data.message) || '';
            }
            setTimeout(close, 1400);
        })
        .catch(close);
    });
}

/* ══ CONTACT FORM ════════════════════════════════════════════ */
document.querySelectorAll('#zcContactForm,.zc-contact-form').forEach(function (originalForm) {
    var form = originalForm.cloneNode(true);
    originalForm.parentNode.replaceChild(form, originalForm);
    var fmsg = form.querySelector('.zc-form-msg,#zcFormMsg');
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
            if (res.success) {
                form.reset();
                // Pre Správcu značiek Google – z tejto udalosti si spravíš konverziu
                document.dispatchEvent(new CustomEvent('zc:form-sent', {
                    detail: { form_name: form.getAttribute('data-zc-form') || 'kontakt' }
                }));
                // Prihlásil sa na newsletter → opýtame sa ho na typ ponúk
                if (res.data && res.data.interest_token) zcInterestModal(res.data.interest_token);
            }
            btn.textContent = orig; btn.disabled = false;
        })
        .catch(function () {
            if (fmsg) {
                fmsg.style.cssText = 'display:block;padding:14px 18px;border-radius:10px;margin-bottom:14px;font-size:14px;border:1px solid;';
                fmsg.style.background = 'rgba(239,68,68,.1)';
                fmsg.style.color = '#dc2626';
                fmsg.style.borderColor = 'rgba(239,68,68,.25)';
                fmsg.textContent = 'Správu sa nepodarilo odoslať. Obnovte stránku (Ctrl+F5) a skúste znova.';
            }
            btn.textContent = orig; btn.disabled = false;
        });
    });
});

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
                // Po dobehnutí revealu zmažeme inline transform/transition,
                // aby CSS :hover efekty (napr. zdvih karty ponuky) opäť fungovali
                var el = e.target;
                setTimeout(function () { el.style.transform = ''; el.style.transition = ''; }, 650);
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


/* ══ Objavenie obrázkov ponúk pri scrolle (fade + jemné priblíženie) ══════ */
(function(){
    var reveal = function(img){ img.classList.add('zc-loaded'); };
    var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function(entries){
        entries.forEach(function(e){ if(e.isIntersecting){ reveal(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' }) : null;

    window.zcRevealImages = function(root){
        (root || document).querySelectorAll('.zc-prop-img img:not(.zc-loaded),.pp-gal-item img:not(.zc-loaded)').forEach(function(img){
            if(img.dataset.zcObserved) return;
            img.dataset.zcObserved = '1';
            if(io){
                io.observe(img);
                // poistka: keby observer nezabral do 2 s, aj tak odhaliť
                setTimeout(function(){ reveal(img); }, 2000);
            } else {
                reveal(img);
            }
        });
    };
    window.zcRevealImages();
})();

/* ══ VIDEO — štartovacia hlasitosť (aby po spustení nehúkalo) ══════════ */
(function () {
    var vids = document.querySelectorAll('iframe[data-zc-vol]');
    if (!vids.length) return;
    var needYT = false, needVimeo = false;
    vids.forEach(function (f) {
        var s = f.src || '';
        if (s.indexOf('youtube.com') !== -1) needYT = true;
        else if (s.indexOf('vimeo.com') !== -1) needVimeo = true;
    });

    function volOf(f) { var v = parseInt(f.getAttribute('data-zc-vol'), 10); return isNaN(v) ? 50 : Math.max(0, Math.min(100, v)); }

    if (needYT) {
        var prevReady = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
            if (typeof prevReady === 'function') prevReady();
            document.querySelectorAll('iframe[data-zc-vol]').forEach(function (f) {
                if ((f.src || '').indexOf('youtube.com') === -1) return;
                if (!f.id) f.id = 'zcyt_' + Math.random().toString(36).slice(2, 8);
                try {
                    new YT.Player(f.id, { events: { onReady: function (e) { e.target.setVolume(volOf(f)); } } });
                } catch (err) {}
            });
        };
        if (!window.YT || !window.YT.Player) {
            var tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        } else {
            window.onYouTubeIframeAPIReady();
        }
    }

    if (needVimeo) {
        var vtag = document.createElement('script');
        vtag.src = 'https://player.vimeo.com/api/player.js';
        vtag.onload = function () {
            document.querySelectorAll('iframe[data-zc-vol]').forEach(function (f) {
                if ((f.src || '').indexOf('vimeo.com') === -1) return;
                try { new Vimeo.Player(f).setVolume(volOf(f) / 100); } catch (err) {}
            });
        };
        document.head.appendChild(vtag);
    }
})();


});

/* Číslice v serifových textoch rieši samostatný súbor písma
   (PlayfairDisplay-num.woff2) cez unicode-range – žiadny zásah do DOM
   už netreba. Predtým tu bol TreeWalker, ktorý každé číslo obaľoval
   do <span> s bezpätkovým písmom. */
