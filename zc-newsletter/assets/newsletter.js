document.querySelectorAll('.zcn-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn  = form.querySelector('[type=submit]');
        var msg  = form.querySelector('.zcn-msg');
        var orig = btn.textContent;
        btn.textContent = '...'; btn.disabled = true;

        var data = new FormData();
        data.append('action',  'zcn_subscribe');
        data.append('nonce',   zcnData.nonce);
        data.append('email',   form.querySelector('[name=zcn_email]').value);
        data.append('name',    form.querySelector('[name=zcn_name]')?.value || '');
        data.append('interest',form.querySelector('[name=zcn_interest]')?.value || '');
        data.append('source',  form.dataset.source || 'newsletter');

        fetch(zcnData.ajaxurl, {method:'POST', body:data})
        .then(function(r){ return r.json(); })
        .then(function(res){
            msg.style.display  = 'block';
            msg.style.background   = res.success ? 'rgba(34,197,94,.1)'  : 'rgba(239,68,68,.1)';
            msg.style.color        = res.success ? '#15803d' : '#dc2626';
            msg.style.border       = '1px solid ' + (res.success ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)');
            msg.style.borderRadius = '8px';
            msg.style.padding      = '12px 14px';
            msg.textContent        = res.data.message;
            if (res.success) form.reset();
            btn.textContent = orig; btn.disabled = false;
        });
    });
});
