<?php
defined('ABSPATH') || exit;
// ── Šablóny newsletteru + premenné {meno}, {email} ───────────────────────

function zcn_get_templates() {
    $tpls = get_option('zcn_templates', []);
    return is_array($tpls) ? $tpls : [];
}

// Nahradí {premenne} v texte – používa sa pri odoslaní pre každého odberateľa
function zcn_apply_vars($text, $vars) {
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }
    // Upratanie po prázdnom mene: "Dobrý deň ," → "Dobrý deň,"
    return str_replace([' ,', ' .'], [',', '.'], $text);
}

// AJAX – uloženie šablóny
add_action('wp_ajax_zcn_tpl_save', function() {
    check_ajax_referer('zcn_tpl', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $name    = trim(sanitize_text_field($_POST['name'] ?? ''));
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $body    = wp_kses_post($_POST['body'] ?? '');
    if (!$name || mb_strlen($name) > 60) wp_send_json_error(['message' => 'Zadajte názov šablóny (max. 60 znakov).']);
    if (!$subject && !$body) wp_send_json_error(['message' => 'Šablóna je prázdna.']);
    $id = sanitize_title($name);
    if (!$id) wp_send_json_error(['message' => 'Neplatný názov.']);
    $tpls = zcn_get_templates();
    $tpls[$id] = ['name' => $name, 'subject' => $subject, 'body' => $body];
    update_option('zcn_templates', $tpls);
    wp_send_json_success(['id' => $id, 'name' => $name]);
});

// AJAX – zmazanie šablóny
add_action('wp_ajax_zcn_tpl_delete', function() {
    check_ajax_referer('zcn_tpl', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $id   = sanitize_key($_POST['id'] ?? '');
    $tpls = zcn_get_templates();
    if (!isset($tpls[$id])) wp_send_json_error(['message' => 'Šablóna neexistuje.']);
    unset($tpls[$id]);
    update_option('zcn_templates', $tpls);
    wp_send_json_success();
});

// ── Panel šablón + premenných nad editorom (WP admin aj Realitný panel) ──
function zcn_render_tpl_toolbar($editor_id, $subject_id) {
    $tpls = zcn_get_templates();
    $uid  = preg_replace('/[^a-zA-Z0-9]/', '', $editor_id);
    $btn  = 'padding:7px 12px;border:1.5px solid #E0D8CE;background:#fff;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit';
    ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;padding:12px 14px;background:#F5F1EA;border:1px solid #E0D8CE;border-radius:8px">
        <select id="zcnTplSel_<?php echo $uid ?>" style="padding:7px 10px;border:1.5px solid #E0D8CE;border-radius:7px;font-size:13px;max-width:220px;background:#fff">
            <option value="">– Šablóna –</option>
            <?php foreach ($tpls as $id => $t): ?>
            <option value="<?php echo esc_attr($id) ?>"><?php echo esc_html($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" style="<?php echo $btn ?>" onclick="zcnTplLoad_<?php echo $uid ?>()">📂 Načítať</button>
        <button type="button" style="<?php echo $btn ?>" onclick="zcnTplSave_<?php echo $uid ?>()">💾 Uložiť ako šablónu</button>
        <button type="button" style="<?php echo $btn ?>;color:#dc2626" onclick="zcnTplDel_<?php echo $uid ?>()" title="Zmazať vybranú šablónu">🗑</button>
        <span style="flex-basis:100%;font-size:11px;color:#7A7068;line-height:2">Premenné (kliknutím vložíš, doplnia sa pri odoslaní pre každého odberateľa zvlášť):
            <?php foreach (['meno' => 'Meno odberateľa', 'email' => 'E-mail odberateľa'] as $v => $tip): ?>
            <code style="cursor:pointer;background:#fff;border:1px solid #E0D8CE;border-radius:4px;padding:1px 7px;margin-right:4px" title="<?php echo esc_attr($tip) ?>"
                onclick="zcnTplIns_<?php echo $uid ?>('{<?php echo $v ?>}')">{<?php echo $v ?>}</code>
            <?php endforeach; ?>
        </span>
    </div>
    <script>
    var zcnTpls_<?php echo $uid ?> = <?php echo wp_json_encode($tpls) ?>;
    function zcnTplEd_<?php echo $uid ?>() {
        if (window.tinymce && tinymce.get('<?php echo esc_js($editor_id) ?>') && !tinymce.get('<?php echo esc_js($editor_id) ?>').isHidden()) {
            return tinymce.get('<?php echo esc_js($editor_id) ?>');
        }
        return null;
    }
    function zcnTplIns_<?php echo $uid ?>(txt) {
        var ed = zcnTplEd_<?php echo $uid ?>();
        if (ed) { ed.execCommand('mceInsertContent', false, txt); ed.focus(); }
        else { var ta = document.getElementById('<?php echo esc_js($editor_id) ?>'); if (ta) { ta.value += txt; ta.focus(); } }
    }
    function zcnTplLoad_<?php echo $uid ?>() {
        var id = document.getElementById('zcnTplSel_<?php echo $uid ?>').value;
        if (!id || !zcnTpls_<?php echo $uid ?>[id]) { alert('Vyber šablónu.'); return; }
        var t = zcnTpls_<?php echo $uid ?>[id];
        var subj = document.getElementById('<?php echo esc_js($subject_id) ?>');
        if (subj && t.subject) subj.value = t.subject;
        var ed = zcnTplEd_<?php echo $uid ?>();
        if (ed) ed.setContent(t.body || '');
        else { var ta = document.getElementById('<?php echo esc_js($editor_id) ?>'); if (ta) ta.value = t.body || ''; }
    }
    function zcnTplSave_<?php echo $uid ?>() {
        var name = prompt('Názov šablóny:');
        if (!name) return;
        var subj = document.getElementById('<?php echo esc_js($subject_id) ?>');
        var ed = zcnTplEd_<?php echo $uid ?>();
        var body = ed ? ed.getContent() : ((document.getElementById('<?php echo esc_js($editor_id) ?>') || {}).value || '');
        var data = new FormData();
        data.append('action', 'zcn_tpl_save');
        data.append('nonce', '<?php echo wp_create_nonce('zcn_tpl') ?>');
        data.append('name', name);
        data.append('subject', subj ? subj.value : '');
        data.append('body', body);
        fetch('<?php echo admin_url('admin-ajax.php') ?>', {method: 'POST', body: data})
        .then(function(r){ return r.json(); }).then(function(res){
            if (!res.success) { alert((res.data && res.data.message) || 'Chyba'); return; }
            var sel = document.getElementById('zcnTplSel_<?php echo $uid ?>');
            if (!sel.querySelector('option[value="' + res.data.id + '"]')) {
                var o = document.createElement('option');
                o.value = res.data.id; o.textContent = res.data.name;
                sel.appendChild(o);
            }
            zcnTpls_<?php echo $uid ?>[res.data.id] = {name: res.data.name, subject: subj ? subj.value : '', body: body};
            sel.value = res.data.id;
            alert('✅ Šablóna uložená.');
        });
    }
    function zcnTplDel_<?php echo $uid ?>() {
        var sel = document.getElementById('zcnTplSel_<?php echo $uid ?>');
        var id = sel.value;
        if (!id) { alert('Vyber šablónu, ktorú chceš zmazať.'); return; }
        if (!confirm('Zmazať šablónu „' + (zcnTpls_<?php echo $uid ?>[id] ? zcnTpls_<?php echo $uid ?>[id].name : id) + '"?')) return;
        var data = new FormData();
        data.append('action', 'zcn_tpl_delete');
        data.append('nonce', '<?php echo wp_create_nonce('zcn_tpl') ?>');
        data.append('id', id);
        fetch('<?php echo admin_url('admin-ajax.php') ?>', {method: 'POST', body: data})
        .then(function(r){ return r.json(); }).then(function(res){
            if (!res.success) { alert((res.data && res.data.message) || 'Chyba'); return; }
            delete zcnTpls_<?php echo $uid ?>[id];
            sel.querySelector('option[value="' + id + '"]').remove();
            sel.value = '';
        });
    }
    </script>
    <?php
}
