<?php
defined('ABSPATH') || exit;
add_action('admin_menu', function() {
    add_menu_page('ZC Recenzie','Recenzie ⭐','edit_posts',
        'zc-reviews','zcr_admin_page','dashicons-star-filled',27);
});

function zcr_admin_page() {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM ".zcr_table()." ORDER BY sort_order ASC, id ASC");
    ?>
    <div class="wrap">
    <h1>⭐ Recenzie a referencie
        <button onclick="zcrOpenModal(0)" class="page-title-action">+ Pridať recenziu</button>
    </h1>

    <table class="wp-list-table widefat fixed striped" style="border-radius:10px;overflow:hidden;margin-top:16px">
        <thead><tr>
            <th style="width:40px">#</th>
            <th>Klient</th><th>Rola / Typ</th><th>Text</th>
            <th style="width:80px">Hodnotenie</th>
            <th style="width:80px">Status</th>
            <th style="width:120px">Akcie</th>
        </tr></thead>
        <tbody id="zcrList">
        <?php if ($rows): foreach ($rows as $r): ?>
        <tr data-id="<?php echo $r->id ?>">
            <td style="color:#aaa;font-size:12px"><?php echo $r->id ?></td>
            <td style="font-weight:600"><?php echo esc_html($r->author_name) ?></td>
            <td style="color:#666;font-size:13px"><?php echo esc_html($r->author_role ?: '—') ?></td>
            <td style="font-size:13px;color:#444;max-width:280px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?php echo esc_html($r->body) ?></td>
            <td style="color:#B8A47A;letter-spacing:2px"><?php echo str_repeat('★',intval($r->rating)) ?></td>
            <td><?php echo $r->published
                ? '<span style="background:#dcfce7;color:#15803d;padding:2px 10px;border-radius:50px;font-size:11px;font-weight:700">Aktívna</span>'
                : '<span style="background:#f1f5f9;color:#94a3b8;padding:2px 10px;border-radius:50px;font-size:11px;font-weight:700">Skrytá</span>'
            ?></td>
            <td>
                <button onclick="zcrOpenModal(<?php echo $r->id ?>)" class="button button-small">✏️ Upraviť</button>
                <button onclick="zcrDelete(<?php echo $r->id ?>)" class="button button-small" style="color:#dc2626">🗑</button>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:#aaa">Zatiaľ žiadne recenzie. Pridajte prvú!</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p style="color:#666;font-size:12px;margin-top:12px">Shortcode: <code>[zc_reviews]</code> alebo <code>[zc_reviews limit="3" cols="3"]</code></p>
    </div>

    <?php zcr_modal(); ?>
    <?php zcr_admin_scripts(); ?>
    <?php
}

function zcr_modal() {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM ".zcr_table()." ORDER BY sort_order ASC");
    ?>
    <div id="zcrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;overflow:auto;padding:24px">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.25)">
        <div style="padding:16px 22px;background:#1C1A18;display:flex;justify-content:space-between;align-items:center">
            <strong style="color:#fff;font-size:15px" id="zcrModalTitle">Pridať recenziu</strong>
            <button onclick="document.getElementById('zcrModal').style.display='none'"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;font-size:15px;line-height:1">✕</button>
        </div>
        <div style="padding:24px">
            <input type="hidden" id="zcrEditId" value="0">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Meno klienta *</label>
                    <input type="text" id="zcrName" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:14px" placeholder="Jana Nováková">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Rola / Typ</label>
                    <input type="text" id="zcrRole" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:14px" placeholder="Predaj bytu, Prenájom...">
                </div>
            </div>
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Text recenzie *</label>
                <textarea id="zcrBody" rows="4" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:14px;resize:vertical" placeholder="Zdenka nám pomohla predať nehnuteľnosť za najlepšiu cenu..."></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Hodnotenie</label>
                    <div id="zcrStars" style="display:flex;gap:4px;font-size:28px;cursor:pointer">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <span data-v="<?php echo $i ?>" onclick="zcrSetRating(<?php echo $i ?>)" style="color:#E0D8CE;transition:color .15s">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="zcrRating" value="5">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Zobraziť</label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:8px">
                        <input type="checkbox" id="zcrPublished" checked style="accent-color:#B8A47A;width:16px;height:16px">
                        <span style="font-size:13px">Zobrazená na webe</span>
                    </label>
                </div>
            </div>
            <div style="margin-bottom:18px">
                <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Fotografia klienta (URL, nepovinné)</label>
                <input type="text" id="zcrAvatar" style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:14px" placeholder="https://...">
            </div>
            <div id="zcrModalMsg" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:8px;font-size:13px"></div>
            <div style="display:flex;justify-content:flex-end;gap:8px">
                <button onclick="document.getElementById('zcrModal').style.display='none'" class="button">Zrušiť</button>
                <button onclick="zcrSave()" class="button button-primary">💾 Uložiť recenziu</button>
            </div>
        </div>
    </div>
    </div>
    <?php
}

function zcr_admin_scripts() {
    $nonce = wp_create_nonce('zcr_nonce');
    $ajax  = admin_url('admin-ajax.php');
    ?>
    <script>
    var ZCR_NONCE = '<?php echo $nonce ?>';
    var ZCR_AJAX  = '<?php echo $ajax ?>';

    // Load all reviews data for editing
    var ZCR_DATA = <?php
        global $wpdb;
        echo json_encode($wpdb->get_results("SELECT * FROM ".zcr_table()." ORDER BY id ASC") ?: []);
    ?>;

    function zcrSetRating(v) {
        document.getElementById('zcrRating').value = v;
        document.querySelectorAll('#zcrStars span').forEach(function(s) {
            s.style.color = parseInt(s.dataset.v) <= v ? '#B8A47A' : '#E0D8CE';
        });
    }

    function zcrOpenModal(id) {
        var modal = document.getElementById('zcrModal');
        var title = document.getElementById('zcrModalTitle');
        document.getElementById('zcrEditId').value = id;
        document.getElementById('zcrModalMsg').style.display = 'none';

        if (id) {
            var r = ZCR_DATA.find(function(x){return x.id==id});
            if (!r) return;
            title.textContent = 'Upraviť recenziu';
            document.getElementById('zcrName').value    = r.author_name;
            document.getElementById('zcrRole').value    = r.author_role;
            document.getElementById('zcrBody').value    = r.body;
            document.getElementById('zcrAvatar').value  = r.avatar_url;
            document.getElementById('zcrPublished').checked = r.published == 1;
            zcrSetRating(parseInt(r.rating)||5);
        } else {
            title.textContent = 'Pridať recenziu';
            document.getElementById('zcrName').value    = '';
            document.getElementById('zcrRole').value    = '';
            document.getElementById('zcrBody').value    = '';
            document.getElementById('zcrAvatar').value  = '';
            document.getElementById('zcrPublished').checked = true;
            zcrSetRating(5);
        }
        modal.style.display = 'block';
    }

    function zcrSave() {
        var data = new FormData();
        data.append('action',      'zcr_save');
        data.append('nonce',       ZCR_NONCE);
        data.append('id',          document.getElementById('zcrEditId').value);
        data.append('author_name', document.getElementById('zcrName').value);
        data.append('author_role', document.getElementById('zcrRole').value);
        data.append('body',        document.getElementById('zcrBody').value);
        data.append('rating',      document.getElementById('zcrRating').value);
        data.append('avatar_url',  document.getElementById('zcrAvatar').value);
        data.append('published',   document.getElementById('zcrPublished').checked ? 1 : 0);

        fetch(ZCR_AJAX, {method:'POST',body:data}).then(r=>r.json()).then(res=>{
            var msg = document.getElementById('zcrModalMsg');
            msg.style.display = 'block';
            msg.style.background = res.success ? '#f0fdf4' : '#fef2f2';
            msg.style.color      = res.success ? '#15803d' : '#dc2626';
            msg.style.border     = '1px solid ' + (res.success ? '#bbf7d0' : '#fecaca');
            msg.textContent = res.data.message;
            if (res.success) setTimeout(function(){ location.reload(); }, 800);
        });
    }

    function zcrDelete(id) {
        if (!confirm('Naozaj vymazať túto recenziu?')) return;
        var data = new FormData();
        data.append('action','zcr_delete'); data.append('nonce',ZCR_NONCE); data.append('id',id);
        fetch(ZCR_AJAX,{method:'POST',body:data}).then(function(){ location.reload(); });
    }
    </script>
    <?php
}
