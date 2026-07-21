<?php
/**
 * CRM-lite: evidencia dopytov (leadov) z formulárov na nehnuteľnostiach.
 * Ukladá kontakt, prepája ho s ponukou a umožňuje sledovať stav obchodu.
 */
defined('ABSPATH') || exit;

// ── Privátny post type na leady ────────────────────────────────────────────
add_action('init', function () {
    register_post_type('pp_lead', [
        'label'    => 'Dopyty',
        'public'   => false,
        'show_ui'  => false,          // spravujú sa cez realitný panel, nie cez wp-admin
        'supports' => ['title'],
        'capability_type' => 'post',
    ]);
});

// Stavy obchodu
function pp_lead_states() {
    return [
        'novy'      => ['label' => 'Nový',       'bg' => '#EEF2FF', 'fg' => '#4338CA'],
        'rokovanie' => ['label' => 'V rokovaní', 'bg' => '#FEF3C7', 'fg' => '#92400E'],
        'uzavrete'  => ['label' => 'Uzavreté',   'bg' => '#DCFCE7', 'fg' => '#15803D'],
        'stratene'  => ['label' => 'Stratené',   'bg' => '#F3F4F6', 'fg' => '#6B7280'],
    ];
}

/**
 * Zaznamená nový dopyt. Vracia ID leadu alebo 0.
 * @param array $a name,email,phone,message,property_id,source
 */
function pp_capture_lead($a) {
    $name  = sanitize_text_field($a['name'] ?? '');
    $email = sanitize_email($a['email'] ?? '');
    $phone = sanitize_text_field($a['phone'] ?? '');
    if ($name === '' && $email === '' && $phone === '') return 0;

    $pid = intval($a['property_id'] ?? 0);
    $title = ($name !== '' ? $name : ($email !== '' ? $email : $phone));
    if ($pid) $title .= ' — ' . get_the_title($pid);

    $lead_id = wp_insert_post([
        'post_type'   => 'pp_lead',
        'post_status' => 'publish',
        'post_title'  => wp_strip_all_tags($title),
    ], true);
    if (is_wp_error($lead_id) || !$lead_id) return 0;

    update_post_meta($lead_id, '_lead_name',    $name);
    update_post_meta($lead_id, '_lead_email',   $email);
    update_post_meta($lead_id, '_lead_phone',   $phone);
    update_post_meta($lead_id, '_lead_message', sanitize_textarea_field($a['message'] ?? ''));
    update_post_meta($lead_id, '_lead_property', $pid);
    update_post_meta($lead_id, '_lead_source',  sanitize_text_field($a['source'] ?? 'web'));
    update_post_meta($lead_id, '_lead_status',  'novy');

    if (function_exists('pp_log')) pp_log('lead_new', $pid, $name ?: $email);
    return $lead_id;
}

// ── AJAX: zmena stavu / poznámka / vymazanie leadu ─────────────────────────
add_action('wp_ajax_pp_lead_update', function () {
    if (!current_user_can('edit_posts')) wp_send_json_error('perm');
    check_ajax_referer('pp_lead', 'nonce');

    $lead_id = intval($_POST['lead_id'] ?? 0);
    if (!$lead_id || get_post_type($lead_id) !== 'pp_lead') wp_send_json_error('id');

    $op = sanitize_text_field($_POST['op'] ?? '');
    if ($op === 'status') {
        $st = sanitize_text_field($_POST['status'] ?? '');
        if (!isset(pp_lead_states()[$st])) wp_send_json_error('status');
        update_post_meta($lead_id, '_lead_status', $st);
    } elseif ($op === 'note') {
        update_post_meta($lead_id, '_lead_note', sanitize_textarea_field($_POST['note'] ?? ''));
    } elseif ($op === 'delete') {
        wp_delete_post($lead_id, true);
    } else {
        wp_send_json_error('op');
    }
    wp_send_json_success();
});

// ── Panel: zoznam leadov ───────────────────────────────────────────────────
function panel_leads() {
    $states = pp_lead_states();
    $filter = sanitize_text_field($_GET['stav'] ?? '');

    $meta = [];
    if ($filter && isset($states[$filter])) {
        $meta[] = ['key' => '_lead_status', 'value' => $filter];
    }
    $q = new WP_Query([
        'post_type'      => 'pp_lead',
        'posts_per_page' => 200,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => $meta,
    ]);

    // Počty pre filtre
    $counts = ['' => 0];
    foreach ($states as $k => $v) $counts[$k] = 0;
    $all = new WP_Query(['post_type' => 'pp_lead', 'posts_per_page' => -1, 'fields' => 'ids']);
    foreach ($all->posts as $lid) {
        $s = get_post_meta($lid, '_lead_status', true) ?: 'novy';
        $counts[''] = ($counts[''] ?? 0) + 1;
        if (isset($counts[$s])) $counts[$s]++;
    }
    wp_reset_postdata();

    ob_start(); ?>
    <div class="pnl-leads">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
            <h2 style="font-family:var(--serif);font-size:22px;color:var(--dark)">Dopyty <span style="color:var(--muted);font-size:15px">(<?php echo intval($counts['']) ?>)</span></h2>
            <div class="lead-filters" style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="?action=leads" class="lead-fbtn<?php echo $filter===''?' active':'' ?>">Všetky (<?php echo intval($counts['']) ?>)</a>
                <?php foreach ($states as $k => $v): ?>
                <a href="?action=leads&stav=<?php echo $k ?>" class="lead-fbtn<?php echo $filter===$k?' active':'' ?>"><?php echo esc_html($v['label']) ?> (<?php echo intval($counts[$k]) ?>)</a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$q->have_posts()): ?>
        <div class="empty"><div class="empty-icon"><?php echo pp_svg('email', 48) ?></div><p>Zatiaľ žiadne dopyty.</p></div>
        <?php else: ?>
        <div class="lead-list">
            <?php while ($q->have_posts()): $q->the_post();
                $lid   = get_the_ID();
                $lname = get_post_meta($lid, '_lead_name', true);
                $lmail = get_post_meta($lid, '_lead_email', true);
                $lphon = get_post_meta($lid, '_lead_phone', true);
                $lmsg  = get_post_meta($lid, '_lead_message', true);
                $lnote = get_post_meta($lid, '_lead_note', true);
                $lprop = intval(get_post_meta($lid, '_lead_property', true));
                $lstat = get_post_meta($lid, '_lead_status', true) ?: 'novy';
            ?>
            <div class="lead-card" data-id="<?php echo $lid ?>">
                <div class="lead-main">
                    <div class="lead-head">
                        <strong><?php echo esc_html($lname ?: '—') ?></strong>
                        <span class="lead-date"><?php echo esc_html(get_the_date('j.n.Y H:i')) ?></span>
                    </div>
                    <div class="lead-contact">
                        <?php if ($lphon): ?><a href="tel:<?php echo esc_attr($lphon) ?>"><?php echo pp_svg('phone',13) ?> <?php echo esc_html($lphon) ?></a><?php endif; ?>
                        <?php if ($lmail): ?><a href="mailto:<?php echo esc_attr($lmail) ?>"><?php echo pp_svg('email',13) ?> <?php echo esc_html($lmail) ?></a><?php endif; ?>
                        <?php if ($lprop): ?><a href="<?php echo esc_url(get_permalink($lprop)) ?>" target="_blank"><?php echo pp_svg('home',13) ?> <?php echo esc_html(get_the_title($lprop)) ?></a><?php endif; ?>
                    </div>
                    <?php if ($lmsg): ?><div class="lead-msg"><?php echo esc_html($lmsg) ?></div><?php endif; ?>
                    <textarea class="lead-note" placeholder="Interná poznámka (dohodnutá obhliadka, ponúknutá cena…)" onblur="ppLeadNote(<?php echo $lid ?>,this)"><?php echo esc_textarea($lnote) ?></textarea>
                </div>
                <div class="lead-side">
                    <select class="lead-status lead-status--<?php echo esc_attr($lstat) ?>" onchange="ppLeadStatus(<?php echo $lid ?>,this)">
                        <?php foreach ($states as $k => $v): ?>
                        <option value="<?php echo $k ?>" <?php selected($lstat, $k) ?>><?php echo esc_html($v['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-danger" onclick="ppLeadDel(<?php echo $lid ?>,this)">Zmazať</button>
                </div>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .lead-fbtn{padding:7px 13px;border-radius:var(--r-sm);font-size:12px;font-weight:600;text-decoration:none;color:var(--muted);background:var(--section);border:1px solid var(--border);transition:all .2s}
    .lead-fbtn:hover{color:var(--dark)}
    .lead-fbtn.active{background:var(--accent);color:var(--dark);border-color:transparent}
    .lead-list{display:flex;flex-direction:column;gap:12px}
    .lead-card{display:flex;gap:18px;background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px 18px;box-shadow:var(--sh)}
    .lead-main{flex:1;min-width:0}
    .lead-head{display:flex;align-items:baseline;gap:12px;margin-bottom:6px}
    .lead-head strong{font-size:15px;color:var(--dark)}
    .lead-date{font-size:11px;color:var(--muted)}
    .lead-contact{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:8px}
    .lead-contact a{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--accent-txt);text-decoration:none}
    .lead-contact a:hover{text-decoration:underline}
    .lead-msg{font-size:13px;color:var(--text);background:var(--section);border-radius:var(--r-sm);padding:10px 12px;margin-bottom:8px;line-height:1.5;white-space:pre-line}
    .lead-note{width:100%;font-size:13px;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);resize:vertical;min-height:40px;color:var(--text)}
    .lead-note:focus{outline:none;border-color:var(--accent)}
    .lead-side{display:flex;flex-direction:column;gap:8px;width:150px;flex-shrink:0}
    .lead-status{padding:8px 10px;border-radius:var(--r-sm);font-size:12px;font-weight:700;border:1.5px solid var(--border);cursor:pointer;font-family:var(--sans)}
    .lead-status--novy{background:#EEF2FF;color:#4338CA}
    .lead-status--rokovanie{background:#FEF3C7;color:#92400E}
    .lead-status--uzavrete{background:#DCFCE7;color:#15803D}
    .lead-status--stratene{background:#F3F4F6;color:#6B7280}
    @media(max-width:640px){.lead-card{flex-direction:column;gap:12px}.lead-side{width:100%;flex-direction:row}.lead-status{flex:1}}
    </style>
    <script>
    var ppLeadNonce='<?php echo wp_create_nonce('pp_lead') ?>';
    function ppLeadReq(d){d.append('nonce',ppLeadNonce);return fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json();});}
    function ppLeadStatus(id,sel){var d=new FormData();d.append('action','pp_lead_update');d.append('op','status');d.append('lead_id',id);d.append('status',sel.value);sel.className='lead-status lead-status--'+sel.value;ppLeadReq(d).then(function(){toast('Stav aktualizovaný',1);});}
    function ppLeadNote(id,ta){var d=new FormData();d.append('action','pp_lead_update');d.append('op','note');d.append('lead_id',id);d.append('note',ta.value);ppLeadReq(d).then(function(){toast('Poznámka uložená',1);});}
    function ppLeadDel(id,btn){if(!confirm('Naozaj zmazať tento dopyt?'))return;var d=new FormData();d.append('action','pp_lead_update');d.append('op','delete');d.append('lead_id',id);ppLeadReq(d).then(function(){var c=btn.closest('.lead-card');c.style.opacity='0';setTimeout(function(){c.remove();},200);toast('Dopyt zmazaný',1);});}
    </script>
    <?php
    return ob_get_clean();
}
