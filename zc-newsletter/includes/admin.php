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
    if (isset($_POST['zcn_import']) && check_admin_referer('zcn_admin')) {
        $emails = array_filter(array_map('trim', explode("\n", $_POST['zcn_import_emails'] ?? '')));
        $imported = 0;
        foreach ($emails as $email) {
            if (!is_email($email)) continue;
            $token = zcn_generate_token();
            $ins = $wpdb->insert($table, ['email'=>$email,'status'=>'active','token'=>$token,'confirmed_at'=>current_time('mysql'),'source'=>'import']);
            if ($ins) $imported++;
        }
        echo '<div class="notice notice-success is-dismissible"><p>Importovaných: <strong>' . $imported . '</strong> e-mailov.</p></div>';
    }
    if (isset($_GET['zcn_export']) && current_user_can('manage_options')) {
        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status='active' ORDER BY confirmed_at DESC");
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="newsletter-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Meno','Email','Status','Dátum prihlásenia','Dátum potvrdenia','Zdroj']);
        foreach ($rows as $r) {
            // Prefix riskantných znakov – ochrana pred CSV/formula injection v Exceli
            $name = preg_match('/^[=+\-@]/', (string)$r->name) ? "'" . $r->name : $r->name;
            fputcsv($out, [$r->id, $name, $r->email, $r->status,
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
    <?php foreach(['subscribers'=>'Odberatelia','send'=>'Odoslať','log'=>'História','import'=>'Import'] as $t=>$l): ?>
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
        $status_filter = in_array($_GET['status'] ?? 'active', ['active','pending','unsubscribed'], true)
            ? $_GET['status'] : 'active';
        $search = sanitize_text_field($_GET['s'] ?? '');
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status=%s AND (email LIKE %s OR name LIKE %s) ORDER BY subscribed_at DESC LIMIT 500",
                $status_filter, $like, $like
            ));
        } else {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status=%s ORDER BY subscribed_at DESC LIMIT 500",
                $status_filter
            ));
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
            <input type="text" name="s" value="<?php echo esc_attr($search) ?>" placeholder="Hľadať..."
                style="padding:6px 12px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
            <button type="submit" class="button"></button>
        </form>
    </div>
    <table class="wp-list-table widefat fixed striped" style="border-radius:10px;overflow:hidden">
        <thead><tr><th style="width:180px">E-mail</th><th>Meno</th><th style="width:100px">Zdroj</th><th style="width:130px">Prihlásený</th><th style="width:110px">Potvrdený</th><th style="width:160px">Akcie</th></tr></thead>
        <tbody>
        <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
            <td><a href="mailto:<?php echo esc_attr($r->email) ?>"><?php echo esc_html($r->email) ?></a></td>
            <td><?php echo esc_html($r->name ?: '–') ?></td>
            <td><span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:11px"><?php echo esc_html($r->source) ?></span></td>
            <td style="font-size:12px"><?php echo date('d.m.Y', strtotime($r->subscribed_at)) ?></td>
            <td style="font-size:12px"><?php echo $r->confirmed_at ? date('d.m.Y', strtotime($r->confirmed_at)) : '<span style="color:#aaa">–</span>' ?></td>
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
            <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Predmet *</label>
            <input type="text" id="zcnSubject" style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:14px" placeholder="Nová ponuka – 3-izbový byt Banská Bystrica">
        </div>
        <?php zcn_render_tpl_toolbar('zcnBody', 'zcnSubject'); ?>
        <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;font-weight:700;color:#666;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Obsah *</label>
            <?php wp_editor('', 'zcnBody', [
                'textarea_name' => 'zcn_body',
                'textarea_rows' => 14,
                'media_buttons' => true,
                'quicktags'     => true,
                'tinymce'       => [
                    'toolbar1' => 'undo,redo,formatselect,|,bold,italic,underline,strikethrough,forecolor,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,outdent,indent,|,link,unlink,image,table,|,removeformat',
                    'toolbar2' => '',
                    'block_formats' => 'Odsek=p;Nadpis 1=h1;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Nadpis 5=h5;Nadpis 6=h6;Predformátované=pre',
                    'content_style' => 'body{font-family:DM Sans,sans-serif;font-size:15px;line-height:1.8;color:#2C2825;padding:12px}',
                ],
            ]); ?>
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
            <span style="font-size:13px;color:#666">Odošle sa <strong><?php echo $stats['active'] ?></strong> aktívnym odberateľom</span>
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
        if (isSchedule && !sch) { alert('Zvoľte dátum a čas odoslania.'); return; }
        if (isSchedule && !confirm('Naplánovať newsletter na ' + sch + '?')) return;
        if (!isTest && !isSchedule && !confirm('Odoslať newsletter <?php echo $stats['active'] ?> odberateľom?')) return;
        var data = new FormData();
        data.append('action','zcn_send_newsletter');
        data.append('nonce','<?php echo wp_create_nonce("zcn_send_nonce") ?>');
        data.append('subject',s); data.append('body',b); data.append('is_html','1');
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
    <div style="max-width:540px">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px">
        <h3 style="margin:0 0 8px">Importovať odberateľov</h3>
        <p style="color:#666;font-size:13px;margin:0 0 16px">Jeden e-mail na riadok. Importovaní budú priamo aktívni (bez potvrdenia).</p>
        <form method="post">
            <?php wp_nonce_field('zcn_admin') ?>
            <textarea name="zcn_import_emails" rows="10" style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-family:monospace;font-size:13px" placeholder="jan@email.sk&#10;maria@email.sk&#10;peter@email.sk"></textarea>
            <button type="submit" name="zcn_import" value="1" class="button button-primary" style="margin-top:10px">Importovať</button>
        </form>
    </div>
    </div>
    <?php endif; ?>

    </div>
    <?php
}
