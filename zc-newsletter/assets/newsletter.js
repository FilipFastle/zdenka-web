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

/* ── Okno na úpravu tém priamo na stránke ─────────────────────────────── */
function zcnPrefsModal(token, selected, msgEl) {
    var groups = (window.zcnData || {}).groups || [];
    var old = document.getElementById('zcnPrefsModal');
    if (old) old.parentNode.removeChild(old);

    var boxes = groups.map(function (g) {
        var items = (g.items || []).map(function (o) {
            var on = selected.indexOf(o.value) > -1 ? ' checked' : '';
            return '<label class="zcp-opt"><input type="checkbox" value="' + o.value + '"' + on +
                   '><span>' + o.label + '</span></label>';
        }).join('');
        return '<div class="zcp-grp">' + (g.group || '') + '</div>' + items;
    }).join('');

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
        '#zcnPrefsModal .zcp-card{background:#fff;border-radius:16px;max-width:440px;width:100%;padding:28px 26px;' +
        'box-shadow:0 24px 64px rgba(0,0,0,.28);max-height:88vh;overflow:auto}' +
        '#zcnPrefsModal h3{margin:0 0 6px;font:700 19px/1.25 "Playfair Display",Georgia,serif;color:#1C1A18}' +
        '#zcnPrefsModal .zcp-sub{margin:0 0 6px;font-size:13.5px;line-height:1.6;color:#6B6560}' +
        '#zcnPrefsModal .zcp-grp{font:800 10px/1.4 inherit;letter-spacing:1.2px;text-transform:uppercase;' +
        'color:#9A8660;margin:16px 0 8px}' +
        '#zcnPrefsModal .zcp-opt{display:flex;align-items:center;gap:10px;padding:11px 14px;border:1.5px solid #E2DACE;' +
        'border-radius:10px;margin-bottom:7px;cursor:pointer;font:600 14px/1.3 inherit;color:#2C2825}' +
        '#zcnPrefsModal .zcp-opt:hover{border-color:#B8A47A;background:#FBF8F2}' +
        '#zcnPrefsModal .zcp-opt input{width:17px;height:17px;accent-color:#B8A47A;flex:0 0 auto;margin:0}' +
        '#zcnPrefsModal .zcp-note{margin:12px 0 14px;font-size:12px;color:#9A8660;line-height:1.6}' +
        '#zcnPrefsModal .zcp-save{display:block;width:100%;padding:13px;border:none;border-radius:9px;' +
        'background:#B8A47A;color:#1C1A18;font:700 14px/1.2 inherit;cursor:pointer}' +
        '#zcnPrefsModal .zcp-save:hover{background:#9A8660}' +
        '#zcnPrefsModal .zcp-link{display:block;width:100%;margin-top:8px;padding:9px;border:none;background:none;' +
        'font:600 12.5px/1.3 inherit;cursor:pointer;color:#9A8660}' +
        '#zcnPrefsModal .zcp-unsub{color:#B0A898;text-decoration:underline}' +
        '#zcnPrefsModal .zcp-unsub:hover{color:#dc2626}' +
        '#zcnPrefsModal .zcp-msg{display:none;margin-top:10px;font-size:13px;line-height:1.5}' +
        '</style>' +
        '<div class="zcp-card">' +
        '<h3>Moje témy</h3>' +
        '<p class="zcp-sub">Označte, čo vám máme posielať. Môžete vybrať aj viac možností.</p>' +
        boxes +
        '<p class="zcp-note">Nič neoznačené = pošleme vám všetko.</p>' +
        '<div class="zcp-msg"></div>' +
        '<button type="button" class="zcp-save">Uložiť zmeny</button>' +
        '<button type="button" class="zcp-link zcp-unsub">Odhlásiť sa zo všetkého</button>' +
        '<button type="button" class="zcp-link" data-zcp-close>Zavrieť</button>' +
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

    function say(text, ok) {
        var m = wrap.querySelector('.zcp-msg');
        m.style.display = 'block';
        m.style.color = ok ? '#15803d' : '#dc2626';
        m.textContent = text;
    }
    function send(action, extra, btn, working) {
        var orig = btn.textContent;
        btn.disabled = true; btn.textContent = working;
        var d = new FormData();
        d.append('action', action);
        d.append('nonce', (window.zcnData || {}).nonce || '');
        d.append('token', token);
        Object.keys(extra || {}).forEach(function (k) { d.append(k, extra[k]); });
        fetch((window.zcnData || {}).ajaxurl, { method: 'POST', body: d })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            btn.disabled = false; btn.textContent = orig;
            say((res.data && res.data.message) || '', res.success);
            if (res.success) {
                if (msgEl) zcnShowMsg(msgEl, (res.data && res.data.message) || '', true);
                setTimeout(close, 1500);
            }
        })
        .catch(function () { btn.disabled = false; btn.textContent = orig; say('Spojenie zlyhalo.', false); });
    }

    wrap.addEventListener('click', function (e) {
        if (e.target === wrap || e.target.hasAttribute('data-zcp-close')) { close(); return; }
        if (e.target.classList.contains('zcp-save')) {
            var picks = [].map.call(wrap.querySelectorAll('.zcp-opt input:checked'), function (c) { return c.value; });
            send('zcn_set_interest', { interest: picks.join(',') }, e.target, 'Ukladám…');
        }
        if (e.target.classList.contains('zcp-unsub')) {
            if (!window.confirm('Naozaj sa chcete odhlásiť zo všetkého?')) return;
            send('zcn_prefs_unsub', {}, e.target, 'Odhlasujem…');
        }
    });
}

/* „Už odoberáte? Upravte si témy" – otvorí okno priamo na stránke. */
document.querySelectorAll('[data-zcn-prefs]').forEach(function (link) {
    var label = link.textContent;
    link.addEventListener('click', function (e) {
        e.preventDefault();
        var form  = link.closest('.zcn-form') || document.querySelector('.zcn-form');
        var msg   = form ? form.querySelector('.zcn-msg') : null;
        var field = form ? form.querySelector('[name=zcn_email]') : null;
        var email = field ? field.value.trim() : '';

        if (!email) {
            email = (window.prompt('Zadajte e-mail, ktorým ste prihlásení:') || '').trim();
        }
        if (!email) return;

        link.textContent = 'Načítavam…';
        var data = new FormData();
        data.append('action', 'zcn_prefs_open');
        data.append('nonce',  zcnData.nonce);
        data.append('email',  email);

        fetch(zcnData.ajaxurl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            link.textContent = label;
            if (res.success) {
                if (msg) msg.style.display = 'none';
                zcnPrefsModal(res.data.token, res.data.interests || [], msg);
            } else {
                zcnShowMsg(msg, res.data.message, false);
            }
        })
        .catch(function () {
            zcnShowMsg(msg, 'Spojenie zlyhalo. Skúste to, prosím, znova.', false);
            link.textContent = label;
        });
    });
});
