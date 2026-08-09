/* Formulár odberu: prihlásenie aj vypýtanie odkazu na úpravu tém. */
function zcnShowMsg(msg, text, ok) {
    if (!msg) return;
    msg.style.display      = 'block';
    msg.style.background   = ok ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
    msg.style.color        = ok ? '#15803d' : '#dc2626';
    msg.style.border       = '1px solid ' + (ok ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)');
    msg.style.borderRadius = '8px';
    msg.style.padding      = '12px 14px';
    msg.textContent        = text;
}

document.querySelectorAll('.zcn-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn  = form.querySelector('[type=submit]');
        var msg  = form.querySelector('.zcn-msg');
        var orig = btn.textContent;
        btn.textContent = '...'; btn.disabled = true;

        // Kategórií môže byť označených viac naraz
        var picks = [].map.call(
            form.querySelectorAll('[name="zcn_interest[]"]:checked'),
            function (c) { return c.value; }
        );
        var single = form.querySelector('[name=zcn_interest]');
        if (!picks.length && single && single.value) picks = [single.value];

        var data = new FormData();
        data.append('action',   'zcn_subscribe');
        data.append('nonce',    zcnData.nonce);
        data.append('email',    form.querySelector('[name=zcn_email]').value);
        data.append('name',     (form.querySelector('[name=zcn_name]') || {}).value || '');
        data.append('interest', picks.join(','));
        data.append('source',   form.dataset.source || 'newsletter');

        fetch(zcnData.ajaxurl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            zcnShowMsg(msg, res.data.message, res.success);
            if (res.success) form.reset();
            btn.textContent = orig; btn.disabled = false;
        })
        .catch(function () {
            zcnShowMsg(msg, 'Spojenie zlyhalo. Skúste to, prosím, znova.', false);
            btn.textContent = orig; btn.disabled = false;
        });
    });
});

/* ── Okno „Moje témy" ────────────────────────────────────────────────────
   Dva kroky v jednom okne: najprv e-mail, potom výber tém.
   Žiadny window.prompt – ten vyzerá ako systémová hláška prehliadača. */
function zcnPrefsOpen(prefillEmail) {
    var old = document.getElementById('zcnPrefsModal');
    if (old) old.parentNode.removeChild(old);

    var wrap = document.createElement('div');
    wrap.id = 'zcnPrefsModal';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'Moje témy');
    wrap.innerHTML =
        '<style>' +
        '#zcnPrefsModal{position:fixed;inset:0;z-index:100020;display:flex;align-items:center;justify-content:center;' +
        'padding:20px;background:rgba(20,17,14,.55);opacity:0;transition:opacity .25s;' +
        'font-family:"DM Sans",system-ui,sans-serif}' +
        '#zcnPrefsModal *{box-sizing:border-box}' +
        '#zcnPrefsModal .zcp-card{background:#fff;border-radius:16px;max-width:440px;width:100%;padding:30px 28px;' +
        'box-shadow:0 24px 64px rgba(0,0,0,.28);max-height:88vh;overflow:auto;transform:translateY(10px);' +
        'transition:transform .25s}' +
        '#zcnPrefsModal.is-in .zcp-card{transform:none}' +
        '#zcnPrefsModal h3{margin:0 0 7px;font:700 20px/1.25 "Playfair Display",Georgia,serif;color:#1C1A18}' +
        '#zcnPrefsModal .zcp-sub{margin:0 0 18px;font-size:13.5px;line-height:1.65;color:#6B6560}' +
        '#zcnPrefsModal .zcp-input{width:100%;padding:13px 15px;border:1.5px solid #E0D8CE;border-radius:9px;' +
        'font:500 15px/1.3 inherit;color:#2C2825;background:#fff;outline:none}' +
        '#zcnPrefsModal .zcp-input:focus{border-color:#B8A47A}' +
        '#zcnPrefsModal .zcp-grp{font:800 10px/1.4 inherit;letter-spacing:1.2px;text-transform:uppercase;' +
        'color:#7C5E33;margin:16px 0 8px}' +
        '#zcnPrefsModal .zcp-opt{display:flex;align-items:center;gap:10px;padding:11px 14px;border:1.5px solid #E2DACE;' +
        'border-radius:10px;margin-bottom:7px;cursor:pointer;font:600 14px/1.3 inherit;color:#2C2825}' +
        '#zcnPrefsModal .zcp-opt:hover{border-color:#B8A47A;background:#FBF8F2}' +
        '#zcnPrefsModal .zcp-opt input{width:17px;height:17px;accent-color:#B8A47A;flex:0 0 auto;margin:0}' +
        '#zcnPrefsModal .zcp-note{margin:12px 0 14px;font-size:12px;color:#7C5E33;line-height:1.6}' +
        '#zcnPrefsModal .zcp-btn{display:block;width:100%;margin-top:14px;padding:13px;border:none;border-radius:9px;' +
        'background:#B8A47A;color:#1C1A18;font:700 14px/1.2 inherit;cursor:pointer;transition:background .2s}' +
        '#zcnPrefsModal .zcp-btn:hover{background:#9A8660}' +
        '#zcnPrefsModal .zcp-btn[disabled]{opacity:.6;cursor:default}' +
        '#zcnPrefsModal .zcp-link{display:block;width:100%;margin-top:8px;padding:9px;border:none;background:none;' +
        'font:600 12.5px/1.3 inherit;cursor:pointer;color:#7C5E33}' +
        '#zcnPrefsModal .zcp-unsub{color:#6B6560;text-decoration:underline}' +
        '#zcnPrefsModal .zcp-unsub:hover{color:#dc2626}' +
        '#zcnPrefsModal .zcp-msg{display:none;margin-top:12px;padding:11px 13px;border-radius:8px;' +
        'font-size:13px;line-height:1.55}' +
        '</style>' +
        '<div class="zcp-card"><div class="zcp-body"></div></div>';

    document.body.appendChild(wrap);
    requestAnimationFrame(function () { wrap.style.opacity = '1'; wrap.classList.add('is-in'); });

    var body  = wrap.querySelector('.zcp-body');
    var token = '';

    function close() {
        wrap.style.opacity = '0';
        wrap.classList.remove('is-in');
        setTimeout(function () { if (wrap.parentNode) wrap.parentNode.removeChild(wrap); }, 260);
        document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') close(); }
    document.addEventListener('keydown', onKey);
    wrap.addEventListener('click', function (e) { if (e.target === wrap) close(); });

    function say(text, ok) {
        var m = body.querySelector('.zcp-msg');
        if (!m) return;
        m.style.display    = 'block';
        m.style.background = ok ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
        m.style.color      = ok ? '#15803d' : '#dc2626';
        m.textContent      = text;
    }
    function post(action, extra) {
        var d = new FormData();
        d.append('action', action);
        d.append('nonce', (window.zcnData || {}).nonce || '');
        Object.keys(extra || {}).forEach(function (k) { d.append(k, extra[k]); });
        return fetch((window.zcnData || {}).ajaxurl, { method: 'POST', body: d }).then(function (r) { return r.json(); });
    }

    /* Krok 1 – e-mail */
    function stepEmail(prefill) {
        body.innerHTML =
            '<h3>Moje témy</h3>' +
            '<p class="zcp-sub">Zadajte e-mail, ktorým ste prihlásení na odber. Ukážeme vám, ' +
            'čo máte nastavené, a môžete to zmeniť.</p>' +
            '<input type="email" class="zcp-input" placeholder="vas@email.sk" autocomplete="email">' +
            '<div class="zcp-msg"></div>' +
            '<button type="button" class="zcp-btn">Pokračovať</button>' +
            '<button type="button" class="zcp-link" data-close>Zavrieť</button>';

        var input = body.querySelector('.zcp-input');
        var btn   = body.querySelector('.zcp-btn');
        if (prefill) input.value = prefill;
        setTimeout(function () { input.focus(); }, 60);

        function go() {
            var email = input.value.trim();
            if (!email) { say('Zadajte e-mailovú adresu.', false); input.focus(); return; }
            btn.disabled = true; btn.textContent = 'Načítavam…';
            post('zcn_prefs_open', { email: email }).then(function (res) {
                btn.disabled = false; btn.textContent = 'Pokračovať';
                if (res.success) { token = res.data.token; stepTopics(res.data.interests || []); }
                else say((res.data && res.data.message) || 'Nepodarilo sa načítať.', false);
            }).catch(function () {
                btn.disabled = false; btn.textContent = 'Pokračovať';
                say('Spojenie zlyhalo. Skúste to, prosím, znova.', false);
            });
        }
        btn.addEventListener('click', go);
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
        body.querySelector('[data-close]').addEventListener('click', close);
    }

    /* Krok 2 – výber tém */
    function stepTopics(selected) {
        var groups = (window.zcnData || {}).groups || [];
        var boxes = groups.map(function (g) {
            var items = (g.items || []).map(function (o) {
                var on = selected.indexOf(o.value) > -1 ? ' checked' : '';
                return '<label class="zcp-opt"><input type="checkbox" value="' + o.value + '"' + on +
                       '><span>' + o.label + '</span></label>';
            }).join('');
            return '<div class="zcp-grp">' + (g.group || '') + '</div>' + items;
        }).join('');

        body.innerHTML =
            '<h3>Moje témy</h3>' +
            '<p class="zcp-sub">Označte, čo vám máme posielať. Môžete vybrať aj viac možností.</p>' +
            boxes +
            '<p class="zcp-note">Nič neoznačené = pošleme vám všetko.</p>' +
            '<div class="zcp-msg"></div>' +
            '<button type="button" class="zcp-btn" data-save>Uložiť zmeny</button>' +
            '<button type="button" class="zcp-link zcp-unsub">Odhlásiť sa zo všetkého</button>' +
            '<button type="button" class="zcp-link" data-close>Zavrieť</button>';

        body.querySelector('[data-close]').addEventListener('click', close);

        body.querySelector('[data-save]').addEventListener('click', function () {
            var btn = this, orig = btn.textContent;
            var picks = [].map.call(body.querySelectorAll('.zcp-opt input:checked'), function (c) { return c.value; });
            btn.disabled = true; btn.textContent = 'Ukladám…';
            post('zcn_set_interest', { token: token, interest: picks.join(',') }).then(function (res) {
                btn.disabled = false; btn.textContent = orig;
                say((res.data && res.data.message) || '', res.success);
                if (res.success) setTimeout(close, 1500);
            }).catch(function () {
                btn.disabled = false; btn.textContent = orig;
                say('Spojenie zlyhalo.', false);
            });
        });

        body.querySelector('.zcp-unsub').addEventListener('click', function () {
            if (!window.confirm('Naozaj sa chcete odhlásiť zo všetkého?')) return;
            var btn = this, orig = btn.textContent;
            btn.disabled = true; btn.textContent = 'Odhlasujem…';
            post('zcn_prefs_unsub', { token: token }).then(function (res) {
                btn.disabled = false; btn.textContent = orig;
                say((res.data && res.data.message) || '', res.success);
                if (res.success) setTimeout(close, 1600);
            }).catch(function () {
                btn.disabled = false; btn.textContent = orig;
                say('Spojenie zlyhalo.', false);
            });
        });
    }

    stepEmail(prefillEmail || '');
}

/* Tlačidlá „Zmeniť si témy" – v páse pod formulárom aj v texte. */
document.querySelectorAll('[data-zcn-prefs]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        var form  = el.closest('.zcn-form-wrap') || document.querySelector('.zcn-form-wrap');
        var field = form ? form.querySelector('[name=zcn_email]') : null;
        zcnPrefsOpen(field && field.value.trim() ? field.value.trim() : '');
    });
});
