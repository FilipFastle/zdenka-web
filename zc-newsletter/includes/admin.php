<?php
defined('ABSPATH') || exit;
// Mobile CSS for WP Admin newsletter
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'zc-newsletter') === false) return;
    echo '<style>
    @media (max-width:782px) {
        .wrap > div[style*="grid-template-columns:repeat(4"] {
            grid-template-columns: repeat(2,1fr) !important;
        }
        .wrap > div[style*="max-width:700px"],
        .wrap > div[style*="max-width:720px"],
        .wrap > div[style*="max-width:760px"] {
            max-width:100% !important;
        }
        #zcnPreviewModal { padding:8px !important; }
        #zcnPreviewModal > div { border-radius:10px !important; }
        #zcnPreviewFrame { height:480px !important; }
        .wp-list-table th, .wp-list-table td { padding:8px 10px !important; font-size:12px !important; }
        input[type="text"], input[type="email"], textarea { font-size:16px !important; }
        /* Hide less important columns on mobile */
        .wp-list-table .column-source,
        .wp-list-table th:nth-child(3),
        .wp-list-table td:nth-child(3) { display:none !important; }
    }
    </style>';
});

add_action('admin_menu', function() {
    if (function_exists('zc_hub_slug')) {
        add_submenu_page(zc_hub_slug(), 'Newsletter', 'Newsletter', 'manage_options',
            'zc-newsletter', 'zcn_admin_page');
    } else {
        add_menu_page('ZC Newsletter', 'Newsletter ', 'manage_options',
            'zc-newsletter', 'zcn_admin_page', 'dashicons-email-alt', 26);
    }
});

function zcn_admin_page() {
    global $wpdb;
    $table = zcn_table();
    $tab   = sanitize_text_field($_GET['tab'] ?? 'subscribers');

    if (isset($_POST['zcn_delete']) && check_admin_referer('zcn_admin')) {
        $wpdb->delete($table, ['id' => intval($_POST['zcn_delete'])]);
        echo '<div class="notice notice-success is-dismissible"><p>Odberateľ vymazaný.</p></div>';
    }
    if (isset($_POST['zcn_unsub']) && check_admin_referer('zcn_admin')) {
        $wpdb->update($table, ['status' => 'unsubscribed'], ['id' => intval($_POST['zcn_unsub'])]);
        echo '<div class="notice notice-success is-dismissible"><p>Odberateľ odhlásený.</p></div>';
    }
    if (isset($_POST['zcn_interest_save']) && check_admin_referer('zcn_admin')) {
        $wpdb->update(
            $table,
            ['interest' => zcn_sanitize_interests($_POST['interest'] ?? '')],
            ['id' => intval($_POST['zcn_interest_save'])]
        );
        echo '<div class="notice notice-success is-dismissible"><p>Kategória kontaktu uložená.</p></div>';
    }
    // Pridanie jedného kontaktu – rovnaká logika ako v realitnom paneli
    if (isset($_POST['zcn_add']) && check_admin_referer('zcn_admin')) {
        [$msg, $ok] = zcn_add_contact(
            wp_unslash($_POST['zcn_add_email'] ?? ''),
            sanitize_text_field(wp_unslash($_POST['zcn_add_name'] ?? '')),
            zcn_sanitize_interests($_POST['zcn_add_interest'] ?? ''),
            sanitize_key($_POST['zcn_add_mode'] ?? 'active'),
            'manual_admin'
        );
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            $ok ? 'success' : 'error', esc_html($msg));
    }

    // Hromadné pridanie – „Meno;Priezvisko;email" na riadok
    if (isset($_POST['zcn_import']) && check_admin_referer('zcn_admin')) {
        $counts = zcn_add_contacts_bulk(
            wp_unslash($_POST['zcn_import_emails'] ?? ''),
            zcn_sanitize_interests($_POST['zcn_import_interest'] ?? ''),
            'manual_admin',
            sanitize_key($_POST['zcn_import_mode'] ?? 'active')
        );
        [$msg, $ok] = zcn_bulk_notice($counts);
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            $ok ? 'success' : 'warning', esc_html($msg));
    }
    // Správa kategórií
    if (isset($_POST['zcn_cats_save']) && check_admin_referer('zcn_admin')) {
        $in   = (array) ($_POST['cat'] ?? []);
        $cats = [];
        foreach ($in as $row) {
            $label = trim(sanitize_text_field(wp_unslash($row['label'] ?? '')));
            if ($label === '') continue;                       // prázdny riadok = zmazané
            $key = sanitize_key($row['key'] ?? '');
            if ($key === '' || $key === 'ziadne') {
                $key = sanitize_key(remove_accents($label));
                if ($key === '') $key = 'kat_' . substr(md5($label), 0, 6);
            }
            $group = trim(sanitize_text_field(wp_unslash($row['group'] ?? ''))) ?: 'Ostatné';
            $cats[$key] = ['label' => $label, 'group' => $group, 'offer' => !empty($row['offer']) ? 1 : 0];
        }
        // Nová kategória z posledného riadku
        $new_label = trim(sanitize_text_field(wp_unslash($_POST['new_label'] ?? '')));
        if ($new_label !== '') {
            $key = sanitize_key(remove_accents($new_label)) ?: 'kat_' . substr(md5($new_label), 0, 6);
            $cats[$key] = [
                'label' => $new_label,
                'group' => trim(sanitize_text_field(wp_unslash($_POST['new_group'] ?? ''))) ?: 'Ostatné',
                'offer' => !empty($_POST['new_offer']) ? 1 : 0,
            ];
        }
        if ($cats) {
            zcn_save_categories($cats);
            echo '<div class="notice notice-success is-dismissible"><p>Kategórie uložené.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Aspoň jedna kategória musí ostať.</p></div>';
        }
    }
    if (isset($_POST['zcn_cats_reset']) && check_admin_referer('zcn_admin')) {
        delete_option('zcn_categories');
        wp_cache_delete('zcn_categories', 'options');
        echo '<div class="notice notice-success is-dismissible"><p>Kategórie vrátené na predvolené.</p></div>';
    }

    if (isset($_GET['zcn_export']) && current_user_can('manage_options')) {
        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status='active' ORDER BY confirmed_at DESC");
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Meno','Email','Kategória','Status','Dátum prihlásenia','Dátum potvrdenia','Zdroj']);
        foreach ($rows as $r) {
            // Prefix riskantných znakov – ochrana pred CSV/formula injection v Exceli
            $name = preg_match('/^[=+\-@]/', (string)$r->name) ? "'" . $r->name : $r->name;
            fputcsv($out, [$r->id, $name, $r->email, zcn_interest_label($r->interest ?? ''), $r->status,
                $r->subscribed_at, $r->confirmed_at ?? '', $r->source]);
        }
        fclose($out);
        exit;
    }

    $stats = [
        'active'       => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active'"),
        'pending'      => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='pending'"),
        'unsubscribed' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='unsubscribed'"),
        'this_month'   => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active' AND subscribed_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
    ];
    $interest_counts = [];
    foreach (zcn_interests() as $value => $label) {
        $interest_counts[$value] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status='active' AND FIND_IN_SET(%s, interest)",
            $value
        ));
    }
    ?>
    <div class="wrap">
    <h1 style="display:flex;align-items:center;gap:10px;font-size:22px">ZC Newsletter
        <a href="?page=zc-newsletter&zcn_export=1" class="button" style="font-size:12px">Export CSV</a>
    </h1>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;max-width:700px;margin:16px 0 24px">
    <?php foreach([
        ['Aktívni', $stats['active'], '#22c55e', '#f0fdf4'],
        ['Tento mesiac', $stats['this_month'], '#3b82f6', '#eff6ff'],
        ['Čakajú', $stats['pending'], '#f59e0b', '#fffbeb'],
        ['Odhlásení', $stats['unsubscribed'], '#94a3b8', '#f8fafc'],
    ] as [$lbl,$n,$c,$bg]): ?>
    <div style="background:<?php echo $bg ?>;border:1px solid <?php echo $c ?>44;border-radius:10px;padding:14px 16px;text-align:center">
        <div style="font-size:26px;font-weight:800;color:<?php echo $c ?>"><?php echo $n ?></div>
        <div style="font-size:11px;color:#666;margin-top:2px"><?php echo $lbl ?></div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:2px;border-bottom:2px solid #e5e7eb;margin-bottom:20px">
    <?php foreach(['subscribers'=>'Odberatelia','send'=>'Odoslať','log'=>'História','import'=>'Pridať kontakty','cats'=>'Kategórie'] as $t=>$l): ?>
    <a href="?page=zc-newsletter&tab=<?php echo $t ?>"
       style="padding:9px 16px;text-decoration:none;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;margin-bottom:-2px;
              border:1px solid <?php echo $tab===$t?'#e5e7eb':'transparent' ?>;
              border-bottom:<?php echo $tab===$t?'2px solid #fff':'none' ?>;
              background:<?php echo $tab===$t?'#fff':'transparent' ?>;
              color:<?php echo $tab===$t?'#1C1A18':'#666' ?>">
        <?php echo $l ?>
    </a>
    <?php endforeach; ?>
    </div>

    <?php if ($tab === 'subscribers'):
        // SECURITY: whitelist status (enum), prepared statement for search
        $requested_status = sanitize_key($_GET['status'] ?? 'active');
        $status_filter = in_array($requested_status, ['active','pending','unsubscribed'], true)
            ? $requested_status : 'active';
        $search = sanitize_text_field($_GET['s'] ?? '');
        $interest_filter = zcn_sanitize_interest($_GET['interest'] ?? '');
        $where_interest = $interest_filter ? ' AND FIND_IN_SET(%s, interest)' : '';
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $sql = "SELECT * FROM {$table} WHERE status=%s{$where_interest} AND (email LIKE %s OR name LIKE %s) ORDER BY subscribed_at DESC LIMIT 500";
            $args = $interest_filter
                ? [$status_filter, $interest_filter, $like, $like]
                : [$status_filter, $like, $like];
            $rows = $wpdb->get_results($wpdb->prepare($sql, $args));
        } else {
            $sql = "SELECT * FROM {$table} WHERE status=%s{$where_interest} ORDER BY subscribed_at DESC LIMIT 500";
            $args = $interest_filter ? [$status_filter, $interest_filter] : [$status_filter];
            $rows = $wpdb->get_results($wpdb->prepare($sql, $args));
        }
    ?>
    <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
        <div style="display:flex;gap:4px">
        <?php foreach(['active'=>'Aktívni','pending'=>'Čakajú','unsubscribed'=>'Odhlásení'] as $s=>$l): ?>
        <a href="?page=zc-newsletter&tab=subscribers&status=<?php echo $s ?>"
           style="padding:5px 14px;border-radius:50px;font-size:12px;font-weight:700;text-decoration:none;
                  background:<?php echo $status_filter===$s?'#1C1A18':'#f1f5f9' ?>;
                  color:<?php echo $status_filter===$s?'#fff':'#666' ?>">
            <?php echo $l ?> (<?php echo $stats[$s] ?>)
        </a>
        <?php endforeach; ?>
        </div>
        <form method="get" style="display:flex;gap:6px;margin-left:auto">
            <input type="hidden" name="page" value="zc-newsletter">
            <input type="hidden" name="tab" value="subscribers">
            <input type="hidden" name="status" value="<?php echo $status_filter ?>">
            <select name="interest" style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
                <option value="">Všetky kategórie</option>
                <?php foreach (zcn_interests() as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($interest_filter, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="s" value="<?php echo esc_attr($search) ?>" placeholder="Hľadať..."
                style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
            <button type="submit" class="button"></button>
        </form>
    </div>
    <table class="wp-list-table widefat fixed striped" style="border-radius:10px;overflow:hidden">
        <thead><tr><th style="width:180px">E-mail</th><th>Meno</th><th style="width:170px">Kategória</th><th style="width:100px">Zdroj</th><th style="width:110px">Prihlásený</th><th style="width:220px">Akcie</th></tr></thead>
        <tbody>
        <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
            <td><a href="mailto:<?php echo esc_attr($r->email) ?>"><?php echo esc_html($r->email) ?></a></td>
            <td><?php echo esc_html($r->name ?: '–') ?></td>
            <td>
                <form method="post" style="display:flex;gap:4px">
                    <?php wp_nonce_field('zcn_admin') ?>
                    <input type="hidden" name="zcn_interest_save" value="<?php echo (int) $r->id ?>">
                    <div style="min-width:165px"><?php zcn_multiselect('interest', $r->interest ?? '', ['empty' => 'Všetko', 'compact' => true]); ?></div>
                    <button class="button button-small" title="Uložiť kategóriu">✓</button>
                </form>
            </td>
            <td><span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:11px"><?php echo esc_html($r->source) ?></span></td>
            <td style="font-size:12px"><?php echo date('d.m.Y', strtotime($r->subscribed_at)) ?></td>
            <td>
                <?php if ($r->status === 'active'): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Odhlásiť?')">
                    <?php wp_nonce_field('zcn_admin') ?><input type="hidden" name="zcn_unsub" value="<?php echo $r->id ?>">
                    <button class="button button-small">Odhlásiť</button>
                </form>
                <?php endif; ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Natrvalo vymazať?')">
                    <?php wp_nonce_field('zcn_admin') ?><input type="hidden" name="zcn_delete" value="<?php echo $r->id ?>">
                    <button class="button button-small" style="color:#dc2626;border-color:#dc2626">Vymazať</button>
                </form>
            </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:32px;color:#aaa">Žiadni odberatelia v tejto kategórii.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php elseif ($tab === 'send'): ?>
    <div style="max-width:760px">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px">
        <h3 style="margin:0 0 20px">Odoslať newsletter</h3>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Príjemcovia</label>
            <select id="zcnScope" onchange="zcnScopeChange()" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px;margin-bottom:8px">
                <option value="all">Všetci aktívni odberatelia</option>
                <option value="offers">Všetci so záujmom o ponuky</option>
                <option value="cats">Vybrané kategórie…</option>
                <option value="people">Vybraní ľudia…</option>
            </select>
            <div id="zcnCatsWrap" style="display:none"><?php zcn_multiselect('zcn_send_interest', '', ['empty' => 'Vyber kategórie']); ?></div>
            <div id="zcnPeopleWrap" style="display:none"><?php zcn_people_picker('zcnPeople'); ?></div>
        </div>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Predmet *</label>
            <input type="text" id="zcnSubject" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px" placeholder="Nová ponuka – 3-izbový byt Banská Bystrica">
        </div>
        <?php zcn_render_tpl_toolbar('zcnBody', 'zcnSubject'); ?>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Obsah *</label>
            <?php
            if (function_exists('zc_render_editor_tools')) {
                zc_render_editor_tools('zcnBody', ['template'=>'newsletter','label'=>'Obsah newslettera']);
            }
            $editor_settings = [
                'textarea_name' => 'zcn_body',
                'textarea_rows' => 14,
                'media_buttons' => true,
                'teeny'         => false,
                'quicktags'     => true,
                'tinymce'       => [
                    'toolbar1' => 'undo,redo,formatselect,|,bold,italic,underline,strikethrough,forecolor,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,outdent,indent,|,link,unlink,|,removeformat',
                    'toolbar2' => '',
                    'block_formats' => 'Odsek=p;Nadpis 1=h1;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Nadpis 5=h5;Nadpis 6=h6;Predformátované=pre',
                    'content_style' => 'body{font-family:DM Sans,sans-serif;font-size:16px;line-height:1.8;color:#2C2825;padding:12px}',
                    'browser_spellcheck' => true,
                    'resize' => true,
                ],
            ];
            wp_editor('', 'zcnBody', $editor_settings);
            ?>
        </div>
        <div style="background:#f8fafc;border-radius:8px;padding:14px;margin-bottom:16px;border:1px solid #e5e7eb;display:flex;gap:8px;align-items:flex-end">
            <div style="flex:1">
                <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Testovací e-mail</label>
                <input type="email" id="zcnTestEmail" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px" placeholder="vas@email.sk">
            </div>
            <button onclick="zcnSend(true)" class="button" style="white-space:nowrap;padding:8px 14px">Odoslať test</button>
        </div>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;align-items:end;flex-wrap:wrap">
            <div>
                <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Naplánovať odoslanie (voliteľné)</label>
                <input type="datetime-local" id="zcnScheduleAt" style="padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px">
            </div>
            <button onclick="zcnSend(false,true)" class="button">Naplánovať</button>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <span style="font-size:13px;color:#666">Odošle sa <strong id="zcnRecipientCount"><?php echo $stats['active'] ?></strong> aktívnym odberateľom</span>
            <div style="display:flex;gap:8px">
                <button onclick="zcnPreview()" class="button">Náhľad e-mailu</button>
                <button onclick="zcnSend(false)" class="button button-primary">Odoslať všetkým →</button>
            </div>
        </div>
        <div id="zcnSendMsg" style="display:none;margin-top:14px;padding:12px 16px;border-radius:8px;font-size:14px"></div>
    </div>
    <!-- Preview modal -->
    <div id="zcnPreviewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;overflow:auto;padding:24px">
        <div style="max-width:640px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden">
            <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
                <strong>Náhľad e-mailu</strong>
                <button onclick="document.getElementById('zcnPreviewModal').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:18px">✕</button>
            </div>
            <iframe id="zcnPreviewFrame" style="width:100%;height:600px;border:none"></iframe>
        </div>
    </div>
    </div>
    <script>
    function zcnMsg(msg, ok) {
        var el = document.getElementById('zcnSendMsg');
        el.style.display = 'block';
        el.style.background = ok ? '#f0fdf4' : '#fef2f2';
        el.style.color = ok ? '#15803d' : '#dc2626';
        el.style.border = '1px solid ' + (ok ? '#bbf7d0' : '#fecaca');
        el.textContent = msg;
    }
    var zcnCounts = <?php echo wp_json_encode($interest_counts); ?>;
    function zcnPickedCats(){
        return [].map.call(document.querySelectorAll('#zcnCatsWrap input:checked'),function(c){return c.value});
    }
    function zcnScopeChange(){
        var scope=document.getElementById('zcnScope').value;
        document.getElementById('zcnCatsWrap').style.display=(scope==='cats')?'block':'none';
        var pw=document.getElementById('zcnPeopleWrap');
        if(pw)pw.style.display=(scope==='people')?'block':'none';
        zcnRecalc();
    }
    function zcnRecalc(){
        var scope=document.getElementById('zcnScope').value, out=document.getElementById('zcnRecipientCount');
        var total='<?php echo (int) $stats['active'] ?>';
        if(scope==='people'){
            var picked=window.zcPeoplePicked?zcPeoplePicked('zcnPeople'):[];
            out.textContent=picked.length; return;
        }
        if(scope!=='cats'){ out.textContent=total; return; }
        var boxes=document.querySelectorAll('#zcnCatsWrap .zc-ms-opt input');
        var cats=zcnPickedCats();
        if(!cats.length||cats.length===boxes.length){ out.textContent=total; return; }
        var n=0; cats.forEach(function(c){ n+=Number(zcnCounts[c]||0) });
        out.textContent='max. '+n;
    }
    document.addEventListener('change',function(e){
        if(!e.target.closest)return;
        if(e.target.closest('#zcnCatsWrap')||e.target.closest('#zcnPeopleWrap')) zcnRecalc();
    });
    document.addEventListener('click',function(e){
        if(e.target.hasAttribute&&(e.target.hasAttribute('data-zc-people-all')||e.target.hasAttribute('data-zc-people-none')))
            setTimeout(zcnRecalc,10);
    });
    function zcnGetBody() {
        if (window.tinymce && tinymce.get('zcnBody') && !tinymce.get('zcnBody').isHidden()) {
            return tinymce.get('zcnBody').getContent();
        }
        var ta = document.getElementById('zcnBody');
        return ta ? ta.value : '';
    }
    function zcnSend(isTest, isSchedule) {
        var s = document.getElementById('zcnSubject').value.trim();
        var b = zcnGetBody().trim();
        var t = document.getElementById('zcnTestEmail').value.trim();
        var sch = document.getElementById('zcnScheduleAt') ? document.getElementById('zcnScheduleAt').value : '';
        if (!s||!b) { alert('Vyplňte predmet aj obsah.'); return; }
        var scope = document.getElementById('zcnScope').value;
        var interest = '';
        var pickedEmails = '';
        if (scope === 'people') {
            var picked = window.zcPeoplePicked ? zcPeoplePicked('zcnPeople') : [];
            if (!picked.length) { alert('Vyber aspoň jedného príjemcu.'); return; }
            pickedEmails = picked.join(',');
        } else if (scope === 'cats') {
            var cats = zcnPickedCats();
            var boxes = document.querySelectorAll('#zcnCatsWrap .zc-ms-opt input');
            if (!cats.length) { alert('Vyber aspoň jednu kategóriu.'); return; }
            // Všetko označené = všetci, filter neposielame
            if (cats.length < boxes.length) interest = cats.join(',');
        } else if (scope === 'offers') { interest = 'offers'; }
        if (isSchedule && !sch) { alert('Zvoľte dátum a čas odoslania.'); return; }
        if (isSchedule && !confirm('Naplánovať newsletter na ' + sch + '?')) return;
        var recipientCount=document.getElementById('zcnRecipientCount').textContent;
        if (!isTest && !isSchedule && !confirm('Odoslať newsletter ' + recipientCount + ' odberateľom?')) return;
        var data = new FormData();
        data.append('action','zcn_send_newsletter');
        data.append('nonce','<?php echo wp_create_nonce("zcn_send_nonce") ?>');
        data.append('subject',s); data.append('body',b); data.append('is_html','1');
        data.append('interest',interest);
        if (pickedEmails) data.append('emails', pickedEmails);
        if (isTest && t) data.append('test_email',t);
        if (isSchedule && sch) data.append('schedule_at',sch);
        fetch(ajaxurl,{method:'POST',body:data})
        .then(r=>r.json()).then(res=>{ zcnMsg(res.data.message, res.success); });
    }
    function zcnPreview() {
        var s = document.getElementById('zcnSubject').value.trim() || 'Náhľad';
        var b = zcnGetBody().trim();
        var data = new FormData();
        data.append('action','zcn_send_newsletter');
        data.append('nonce','<?php echo wp_create_nonce("zcn_send_nonce") ?>');
        data.append('subject',s); data.append('body',b); data.append('is_html','1'); data.append('preview','1');
        fetch(ajaxurl,{method:'POST',body:data}).then(r=>r.json()).then(res=>{
            if (res.success) {
                document.getElementById('zcnPreviewModal').style.display='block';
                // Build full email preview
                var frame=document.getElementById('zcnPreviewFrame');
                frame.srcdoc='<style>body{font-family:DM Sans,sans-serif;padding:20px;max-width:600px;margin:0 auto}</style>' + res.data.html;
            }
        });
    }
    </script>

    <?php elseif ($tab === 'log'):
        $log = get_option('zcn_send_log', []);
    ?>
    <?php if ($log): ?>
    <table class="wp-list-table widefat fixed" style="border-radius:10px;overflow:hidden;max-width:760px">
        <thead><tr><th>Dátum</th><th>Predmet</th><th style="width:100px">Odoslané</th><th style="width:100px">Zlyhalo</th></tr></thead>
        <tbody>
        <?php foreach ($log as $l): ?>
        <tr>
            <td><?php echo esc_html(date('d.m.Y H:i', strtotime($l['date']))) ?></td>
            <td><?php echo esc_html($l['subject']) ?></td>
            <td style="color:#15803d;font-weight:700"><?php echo $l['sent'] ?></td>
            <td style="color:<?php echo $l['failed']>0?'#dc2626':'#94a3b8' ?>"><?php echo $l['failed'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p style="color:#aaa;padding:32px;text-align:center">Zatiaľ nebol odoslaný žiadny newsletter.</p><?php endif; ?>

    <?php elseif ($tab === 'import'): ?>
    <?php // Rovnaké možnosti ako v realitnom paneli – po jednom aj hromadne ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;max-width:960px">

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px">
            <h3 style="margin:0 0 6px">Pridať jeden kontakt</h3>
            <p style="color:#666;font-size:13px;margin:0 0 16px">
                Kontakt sa uloží rovno ako aktívny a <strong>nedostane žiadny e-mail</strong>.
            </p>
            <form method="post">
                <?php wp_nonce_field('zcn_admin') ?>
                <input type="hidden" name="zcn_add" value="1">
                <p style="margin:0 0 10px">
                    <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px">Meno</label>
                    <input type="text" name="zcn_add_name" class="regular-text" style="width:100%" placeholder="Jana Nováková">
                </p>
                <p style="margin:0 0 10px">
                    <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px">E-mail *</label>
                    <input type="email" name="zcn_add_email" required class="regular-text" style="width:100%" placeholder="jana@example.sk">
                </p>
                <p style="margin:0 0 10px">
                    <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px">Kategória</label>
                    <?php zcn_multiselect('zcn_add_interest', '', ['empty' => 'Všetko']); ?>
                    <span style="font-size:11px;color:#999">Nič nevybrané = pošleme všetko.</span>
                </p>
                <p style="margin:0 0 14px">
                    <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px">Spôsob pridania</label>
                    <select name="zcn_add_mode" style="width:100%">
                        <option value="active">Aktívny – pridať priamo, bez e-mailu</option>
                        <option value="pending">Poslať potvrdzovací e-mail</option>
                    </select>
                </p>
                <button type="submit" class="button button-primary">Pridať kontakt</button>
            </form>
        </div>

        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px">
            <h3 style="margin:0 0 6px">Pridať viac naraz</h3>
            <p style="color:#666;font-size:13px;margin:0 0 14px">
                Jeden človek na riadok vo formáte <strong>Meno;Priezvisko;e-mail</strong>
                (funguje aj CSV s čiarkou alebo tabulátorom, aj samotný e-mail).
            </p>
            <form method="post">
                <?php wp_nonce_field('zcn_admin') ?>
                <textarea name="zcn_import_emails" rows="9" required spellcheck="false"
                    style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-family:ui-monospace,Consolas,monospace;font-size:13px;line-height:1.55"
                    placeholder="Jana;Nováková;jana@example.sk&#10;Peter;Kováč;peter@example.sk&#10;maria@email.sk"></textarea>
                <div style="margin-top:10px"><?php zcn_multiselect('zcn_import_interest', '', ['empty' => 'Spoločná kategória: všetko']); ?></div>
                <span style="display:block;font-size:11px;color:#999;margin-top:4px">Nič nevybrané = pošleme všetko.</span>
                <select name="zcn_import_mode" style="width:100%;margin-top:10px">
                    <option value="active">Pridať priamo, bez potvrdzovacieho e-mailu</option>
                    <option value="pending">Poslať každému potvrdzovací e-mail</option>
                </select>
                <button type="submit" name="zcn_import" value="1" class="button button-primary" style="margin-top:12px">Pridať celú dávku</button>
            </form>
            <p style="color:#777;font-size:12px;margin:14px 0 0;line-height:1.6">
                Kontakty sa pridajú priamo ako aktívne, <strong>bez potvrdzovacieho aj bez uvítacieho e-mailu</strong>.
                Rovnaký e-mail sa nevytvorí druhýkrát – existujúci záznam sa bezpečne aktualizuje.
                Naraz sa spracuje najviac 500 riadkov.
            </p>
        </div>

    </div>
    <p style="color:#999;font-size:12px;max-width:960px;margin-top:16px">
        Aktívny kontakt pridávaj len vtedy, keď ti preukázateľne udelil súhlas.
        Zdroj sa uloží ako „Ručne vo wp-admine“, takže je vždy dohľadateľné, odkiaľ prišiel.
    </p>
    
    <?php elseif ($tab === 'cats'): ?>
    <?php // Vlastné kategórie – premietnu sa všade: formulár, e-mail, panel aj filtre ?>
    <div style="max-width:820px">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px">
            <h3 style="margin:0 0 6px">Kategórie záujmu</h3>
            <p style="color:#666;font-size:13px;margin:0 0 18px;line-height:1.6">
                Tieto možnosti si vyberá návštevník pri prihlásení aj neskôr pri úprave tém.
                <strong>Skupina</strong> je len nadpis, pod ktorý sa možnosť zaradí.
                <strong>Ponuka</strong> označuje kategórie nehnuteľností — kontaktu, ktorý nemá
                žiadnu z nich, sa ponuky neposielajú.<br>
                Kategóriu zmažeš tak, že vymažeš jej názov a uložíš.
            </p>
            <form method="post">
                <?php wp_nonce_field('zcn_admin') ?>
                <table class="wp-list-table widefat striped" style="margin-bottom:16px">
                    <thead><tr>
                        <th style="width:42%">Názov</th>
                        <th style="width:32%">Skupina</th>
                        <th style="width:12%">Ponuka</th>
                        <th style="width:14%">Kontaktov</th>
                    </tr></thead>
                    <tbody>
                    <?php $i = 0; foreach (zcn_categories() as $key => $cat):
                        $used = (int) $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$table} WHERE FIND_IN_SET(%s, interest)", $key)); ?>
                    <tr>
                        <td>
                            <input type="hidden" name="cat[<?php echo $i ?>][key]" value="<?php echo esc_attr($key) ?>">
                            <input type="text" name="cat[<?php echo $i ?>][label]" value="<?php echo esc_attr($cat['label']) ?>" style="width:100%">
                        </td>
                        <td><input type="text" name="cat[<?php echo $i ?>][group]" value="<?php echo esc_attr($cat['group']) ?>" style="width:100%" list="zcnGroups"></td>
                        <td style="text-align:center"><input type="checkbox" name="cat[<?php echo $i ?>][offer]" value="1" <?php checked(!empty($cat['offer'])) ?>></td>
                        <td style="text-align:center;color:#666"><?php echo $used ?></td>
                    </tr>
                    <?php $i++; endforeach; ?>
                    <tr style="background:#fbfaf7">
                        <td><input type="text" name="new_label" placeholder="Nová kategória…" style="width:100%"></td>
                        <td><input type="text" name="new_group" placeholder="Skupina" style="width:100%" list="zcnGroups"></td>
                        <td style="text-align:center"><input type="checkbox" name="new_offer" value="1"></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
                <datalist id="zcnGroups">
                    <?php foreach (array_unique(wp_list_pluck(zcn_categories(), 'group')) as $g): ?>
                    <option value="<?php echo esc_attr($g) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <button class="button button-primary" name="zcn_cats_save" value="1">Uložiť kategórie</button>
                <button class="button" name="zcn_cats_reset" value="1"
                    onclick="return confirm('Vrátiť predvolené kategórie? Priradenia kontaktov ostanú.')">Vrátiť predvolené</button>
            </form>
        </div>
        <p style="color:#999;font-size:12px;margin-top:14px">
            Keď kategóriu zmažeš, kontaktom, ktorí ju mali priradenú, ostane v databáze —
            len sa už nikde neponúka. Stĺpec <em>Kontaktov</em> ukazuje, koľkých sa to týka.
        </p>
    </div>
    <?php endif; ?>

    </div>
    <?php
}
