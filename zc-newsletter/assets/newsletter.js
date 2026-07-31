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

/* „Už odoberáte? Upravte si témy" – odkaz pošleme na e-mail. */
document.querySelectorAll('[data-zcn-prefs]').forEach(function (link) {
    var label = link.textContent;
    link.addEventListener('click', function (e) {
        e.preventDefault();
        var form  = link.closest('.zcn-form') || document.querySelector('.zcn-form');
        var msg   = form ? form.querySelector('.zcn-msg') : null;
        var field = form ? form.querySelector('[name=zcn_email]') : null;
        var email = field ? field.value.trim() : '';

        if (!email) {
            email = (window.prompt('Zadajte e-mail, na ktorý máme poslať odkaz na úpravu tém:') || '').trim();
        }
        if (!email) return;

        link.textContent = 'Posielam…';
        var data = new FormData();
        data.append('action', 'zcn_prefs_link');
        data.append('nonce',  zcnData.nonce);
        data.append('email',  email);

        fetch(zcnData.ajaxurl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            zcnShowMsg(msg, res.data.message, res.success);
            link.textContent = label;
        })
        .catch(function () {
            zcnShowMsg(msg, 'Spojenie zlyhalo. Skúste to, prosím, znova.', false);
            link.textContent = label;
        });
    });
});
