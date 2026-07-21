<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('zdenka-fonts','https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,400;1,700&display=swap',[],null);
    wp_enqueue_style('zdenka-main', get_stylesheet_directory_uri().'/assets/css/main.css',['zdenka-fonts'],'2.8');
    wp_enqueue_script('zdenka-js', get_stylesheet_directory_uri().'/assets/js/main.js',[],'2.8',true);
    wp_localize_script('zdenka-js','zcData',['ajaxurl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('zc_nonce'),'logoUrl'=>get_stylesheet_directory_uri().'/assets/images/zc-logo.svg']);
});

add_action('after_setup_theme', function() {
    register_nav_menus(['primary'=>'Hlavné menu','footer'=>'Footer menu']);
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo',['height'=>60,'width'=>200,'flex-width'=>true]);
    add_image_size('property-thumb',640,480,true);
    add_image_size('property-hero',1280,720,true);
});

add_filter('body_class',function($c){$c[]='zdenka-theme';return $c;});

add_filter('theme_page_templates', function($t) {
    $t['templates/page-home.php']         = 'Homepage';
    $t['templates/page-o-mne.php']        = 'O mne';
    $t['templates/page-ako-pracujem.php'] = 'Ako pracujem';
    $t['templates/page-ponuky.php']       = 'Ponuky';
    $t['templates/page-kontakt.php']      = 'Kontakt';
    $t['templates/page-odhad.php']         = 'Odhad nehnuteľnosti';
    return $t;
});

// AJAX form
add_action('wp_ajax_zc_contact','zc_handle_contact');
add_action('wp_ajax_nopriv_zc_contact','zc_handle_contact');
function zc_handle_contact() {
    check_ajax_referer('zc_nonce','nonce');
    // Honeypot – skryté polia vyplní iba bot (formulár na kontakte ich obsahuje)
    if (!empty($_POST['website']) || !empty($_POST['phone_confirm'])) {
        wp_send_json_error(['message'=>'Správu sa nepodarilo odoslať.']);
    }
    $name  = sanitize_text_field($_POST['name']??'');
    $email = sanitize_email($_POST['email']??'');
    $phone = sanitize_text_field($_POST['phone']??'');
    $msg   = sanitize_textarea_field($_POST['message']??'');
    if (!$name||!$email) { wp_send_json_error(['message'=>'Vyplňte meno a email.']); }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $to   = get_theme_mod('zc_email_main', '') ?: get_option('admin_email');
    $bcc  = get_theme_mod('zc_email_bcc', '');
    $from = get_theme_mod('zc_email_from', '') ?: get_option('admin_email');
    $site = zc_agent('name', 'Mgr. Zdenka Cibuľová');
    $headers = [
        "Content-Type: text/html; charset=UTF-8",
        "From: {$site} <{$from}>",
        "Reply-To: =?UTF-8?B?".base64_encode($name)."?= <{$email}>",
    ];
    if ($bcc) $headers[] = "Bcc: {$bcc}";
    $html = zc_email_template([
        'name'=>$name,'email'=>$email,'phone'=>$phone,
        'message'=>$msg,'subject'=>"Správa z webu: $name",
        'ip'=>$ip,'type'=>'contact',
    ]);
    wp_mail($to, "Správa z webu: $name", $html, $headers);

    // Newsletter opt-in
    if (!empty($_POST['newsletter']) && $email && function_exists('zcn_subscribe_forced')) {
        zcn_subscribe_forced($email, $name, 'contact-form');
    }

    wp_send_json_success(['message'=>'Správa odoslaná! Ozvem sa vám čoskoro.']);
}

// Customizer – kontaktné údaje
add_action('customize_register',function($wpc) {
    $wpc->add_section('zc_agent',['title'=>'Maklérka – Kontakt','priority'=>30]);
    $wpc->add_section('zc_emails',['title'=>'Email adresy','priority'=>31]);
    foreach([
        'zc_email_main'  =>['Email pre kontaktný formulár (prázdne = admin e-mail webu)',''],
        'zc_email_odhad' =>['Email pre odhad nehnuteľnosti (prázdne = admin e-mail webu)',''],
        'zc_email_bcc'   =>['BCC email (skrytá kópia, voliteľné)',''],
        'zc_email_from'  =>['From email (odosielateľ, ideálne na doméne webu)',''],
    ] as $id=>[$lbl,$def]) {
        $wpc->add_setting($id,['default'=>$def,'sanitize_callback'=>'sanitize_email']);
        $wpc->add_control($id,['label'=>$lbl,'section'=>'zc_emails','type'=>'email']);
    }
    foreach(['zc_agent_name'=>['Meno','Mgr. Zdenka Cibuľová'],'zc_agent_title'=>['Titul','Realitná maklérka'],'zc_agent_phone'=>['Telefón','+421 907 579 742'],'zc_agent_wa'=>['WhatsApp','421907579742'],'zc_agent_email'=>['Email','']] as $id=>[$lbl,$def]) {
        $wpc->add_setting($id,['default'=>$def,'sanitize_callback'=>'sanitize_text_field']);
        $wpc->add_control($id,['label'=>$lbl,'section'=>'zc_agent','type'=>'text']);
    }

    // Fotky maklérky – hero (široká) + portrét (vertikálna tvár)
    $wpc->add_section('zc_photos',['title'=>'Fotky maklérky','priority'=>32,
        'description'=>'Hero fotka sa zobrazí na úvodnej stránke, portrét v sekcii O mne a vo všetkých kruhoch s menom.']);
    foreach([
        'zc_photo_hero'     => 'Hero fotka (široká / horizontálna)',
        'zc_photo_portrait' => 'Portrét (vertikálna, hlavne tvár)',
    ] as $id=>$lbl) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wpc->add_control(new WP_Customize_Image_Control($wpc,$id,['label'=>$lbl,'section'=>'zc_photos']));
    }

    // Ako pracujem – fotky a video (šablóna ich už používa, sekcia chýbala)
    $wpc->add_section('zc_ap',['title'=>'Ako pracujem – médiá','priority'=>33]);
    foreach([
        'zc_apfoto1' => 'Fotka – Profesionálne fotografie',
        'zc_apfoto2' => 'Fotka – Moderný marketing',
    ] as $id=>$lbl) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wpc->add_control(new WP_Customize_Image_Control($wpc,$id,['label'=>$lbl,'section'=>'zc_ap']));
    }
    foreach([
        'zc_apvideo'  => 'Video 1 URL (YouTube, Shorts alebo Vimeo)',
        'zc_apvideo2' => 'Video 2 URL (voliteľné)',
        'zc_apvideo3' => 'Video 3 URL (voliteľné)',
    ] as $id=>$lbl) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wpc->add_control($id,['label'=>$lbl,'section'=>'zc_ap','type'=>'url']);
    }
    // Štartovacia hlasitosť videí — aby po spustení „nehúkalo"
    $wpc->add_setting('zc_video_volume',['default'=>50,'sanitize_callback'=>'absint']);
    $wpc->add_control('zc_video_volume',[
        'label'=>'Štartovacia hlasitosť videí (%)','section'=>'zc_ap','type'=>'number',
        'input_attrs'=>['min'=>0,'max'=>100,'step'=>5],
        'description'=>'Platí pre videá v „Ako pracujem" aj na detaile ponuky.',
    ]);
});

// Fotka maklérky: Customizer má prednosť, inak súbor v téme (assets/images/hero.* / portrait.*)
function zc_photo($which, $fallback = '') {
    $url = get_theme_mod('zc_photo_' . $which, '');
    if ($url) return $url;
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $rel = '/assets/images/' . $which . '.' . $ext;
        if (file_exists(get_stylesheet_directory() . $rel)) {
            return get_stylesheet_directory_uri() . $rel;
        }
    }
    return $fallback;
}

function zc_agent($k,$f='') { return get_theme_mod('zc_agent_'.$k,$f)?:$f; }

// Iniciálky z mena (fallback namiesto emoji avatara), napr. „Mgr. Zdenka Cibuľová" → „ZC"
function zc_initials($name) {
    $name = trim(preg_replace('/\b(Mgr|Ing|Bc|PhDr|JUDr|MUDr|Dr)\.?\s*/iu', '', (string)$name));
    $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '';
    $ini = mb_substr($parts[0], 0, 1);
    if (count($parts) > 1) $ini .= mb_substr(end($parts), 0, 1);
    return mb_strtoupper($ini);
}

// Univerzálny video embed – YouTube (watch, youtu.be, shorts, embed, live) aj Vimeo.
// Vracia ['url'=>iframe_src, 'vertical'=>bool] alebo [] pri neplatnom vstupe.
// enablejsapi/api umožňuje nastaviť štartovaciu hlasitosť cez JS (aby nehúkalo).
function zc_video_embed($url) {
    $url = trim((string)$url);
    if (!$url) return [];
    // YouTube – ID má vždy 11 znakov
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|live/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        $vertical = (stripos($url, '/shorts/') !== false);
        $origin   = rawurlencode(home_url());
        return [
            'url'      => 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1&enablejsapi=1&origin=' . $origin,
            'vertical' => $vertical,
        ];
    }
    // Vimeo
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return ['url' => 'https://player.vimeo.com/video/' . $m[1], 'vertical' => false];
    }
    return [];
}

// Štartovacia hlasitosť videí (0–100 %), nastaviteľná v Customizeri.
function zc_video_volume() {
    $v = (int) get_theme_mod('zc_video_volume', 50);
    return max(0, min(100, $v));
}

// One-time self-healing migration: ensure the saved agent name always
// includes the "Mgr." title, even if it was previously saved without it.
add_action('after_setup_theme', function() {
    $current = get_theme_mod('zc_agent_name', '');
    if ($current && strpos($current, 'Mgr.') === false) {
        set_theme_mod('zc_agent_name', 'Mgr. Zdenka Cibuľová');
    }
});

// Auto-create pages – runs on admin_init if not done yet
add_action('admin_init', function() {
    // Always check – don't skip based on old option
    $pages = [
        ['O mne','o-mne','templates/page-o-mne.php'],
        ['Ako pracujem','ako-pracujem','templates/page-ako-pracujem.php'],
        ['Ponuky','ponuky','templates/page-ponuky.php'],
        ['Kontakt',              'kontakt','templates/page-kontakt.php'],
        ['Odhad nehnuteľnosti', 'odhad',  'templates/page-odhad.php'],
    ];
    foreach ($pages as [$title, $slug, $tpl]) {
        if (!get_page_by_path($slug)) {
            $id = wp_insert_post(['post_title'=>$title,'post_name'=>$slug,'post_status'=>'publish','post_type'=>'page']);
            update_post_meta($id, '_wp_page_template', $tpl);
        }
    }
    // Front page
    $home = get_page_by_path('domov');
    if (!$home) {
        $home_id = wp_insert_post(['post_title'=>'Domov','post_name'=>'domov','post_status'=>'publish','post_type'=>'page']);
        update_post_meta($home_id, '_wp_page_template', 'templates/page-home.php');
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }
    update_option('zdenka_pages_created', true);
});

// Admin notice with status
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    if (get_option('zdenka_pages_created')) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Zdenka téma:</strong> Všetky stránky sú vytvorené! <a href="' . home_url() . '" target="_blank">Pozrieť web →</a></p></div>';
    }
});

// After switch theme fallback
add_action('after_switch_theme', function() {
    delete_option('zdenka_pages_created'); // reset so admin_init recreates
});

// ── Slug-based template routing (reliable, no meta needed) ──
add_filter('template_include', function($template) {
    $dir = get_stylesheet_directory();
    // Match by slug OR page ID
    if (is_page('o-mne'))        return $dir . '/templates/page-o-mne.php';
    if (is_page('ako-pracujem')) return $dir . '/templates/page-ako-pracujem.php';
    if (is_page('kontakt'))      return $dir . '/templates/page-kontakt.php';
    if (is_page('odhad'))        return $dir . '/templates/page-odhad.php';
    if (is_page('oblubene'))     return $dir . '/templates/page-oblubene.php';
    // Ponuky – match by slug, title, or page template meta
    if (is_page('ponuky') || (is_page() && get_page_template_slug() === 'templates/page-ponuky.php')) {
        return $dir . '/templates/page-ponuky.php';
    }
    return $template;
}, 999);

// ── Admin page to create pages manually ──
add_action('admin_menu', function() {
    add_menu_page('Zdenka Setup', 'Zdenka Setup', 'manage_options', 'zdenka-setup', 'zdenka_setup_page', 'dashicons-admin-home', 3);
});

function zdenka_setup_page() {
    // Handle create action
    if (isset($_POST['zdenka_create']) && check_admin_referer('zdenka_setup')) {
        $pages = [
            ['Domov',                'domov',        'templates/page-home.php'],
            ['O mne',                'o-mne',        'templates/page-o-mne.php'],
            ['Ako pracujem',         'ako-pracujem', 'templates/page-ako-pracujem.php'],
            ['Ponuky',               'ponuky',       'templates/page-ponuky.php'],
            ['Kontakt',              'kontakt',      'templates/page-kontakt.php'],
            ['Odhad nehnuteľnosti',  'odhad',        'templates/page-odhad.php'],
        ];
        $created = 0;
        $existing = 0;
        $home_id = null;

        foreach ($pages as [$title, $slug, $tpl]) {
            $page = get_page_by_path($slug);
            if (!$page) {
                $id = wp_insert_post([
                    'post_title'  => $title,
                    'post_name'   => $slug,
                    'post_status' => 'publish',
                    'post_type'   => 'page',
                ]);
                update_post_meta($id, '_wp_page_template', $tpl);
                if ($slug === 'domov') $home_id = $id;
                $created++;
            } else {
                // Update template even if exists
                update_post_meta($page->ID, '_wp_page_template', $tpl);
                if ($slug === 'domov') $home_id = $page->ID;
                $existing++;
            }
        }

        // Set front page
        if ($home_id) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $home_id);
        }

        echo '<div class="notice notice-success"><p>Hotovo! Vytvorené: <strong>' . $created . '</strong>, aktualizované: <strong>' . $existing . '</strong>. <a href="' . home_url() . '" target="_blank">Pozrieť web →</a></p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Zdenka Téma – Setup</h1>
        <div style="background:#fff;padding:28px;border-radius:8px;max-width:600px;margin-top:20px;border:1px solid #e2e8f0">
            <h2 style="margin-bottom:12px;font-size:18px">Vytvorenie stránok</h2>
            <p style="color:#666;margin-bottom:20px">Klikni na tlačidlo a automaticky sa vytvoria všetky stránky so správnymi templatemi.</p>
            <table style="width:100%;margin-bottom:24px;border-collapse:collapse">
                <tr style="background:#f8f9fa"><th style="padding:10px;text-align:left;border:1px solid #dee2e6">Stránka</th><th style="padding:10px;text-align:left;border:1px solid #dee2e6">Slug</th><th style="padding:10px;text-align:left;border:1px solid #dee2e6">Stav</th></tr>
                <?php foreach([['Domov','domov'],['O mne','o-mne'],['Ako pracujem','ako-pracujem'],['Ponuky','ponuky'],['Kontakt','kontakt'],['Odhad nehnuteľnosti','odhad']] as [$t,$s]):
                    $exists = get_page_by_path($s); ?>
                <tr>
                    <td style="padding:10px;border:1px solid #dee2e6"><strong><?php echo $t ?></strong></td>
                    <td style="padding:10px;border:1px solid #dee2e6"><code>/<?php echo $s ?>/</code></td>
                    <td style="padding:10px;border:1px solid #dee2e6"><?php echo $exists ? '<span style="color:#16a34a">Existuje</span>' : '<span style="color:#dc2626">Chýba</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <form method="post">
                <?php wp_nonce_field('zdenka_setup') ?>
                <input type="submit" name="zdenka_create" value="Vytvoriť / Opraviť všetky stránky" class="button button-primary button-large">
            </form>
        </div>
        <div style="background:#fff;padding:28px;border-radius:8px;max-width:600px;margin-top:20px;border:1px solid #e2e8f0">
            <h2 style="margin-bottom:12px;font-size:18px">Kontaktné údaje maklérky</h2>
            <p style="color:#666;margin-bottom:12px">Nastav tu: <strong>Appearance → Customize → Maklérka – Kontakt</strong></p>
            <a href="<?php echo admin_url('customize.php?autofocus[section]=zc_agent') ?>" class="button">Otvoriť Customizer →</a>
        </div>

        <!-- MAINTENANCE MODE -->
        <?php
        if (isset($_POST['zc_maint_save']) && check_admin_referer('zc_maint')) {
            update_option('zc_maintenance_on',  intval($_POST['zc_maint_on'] ?? 0));
            update_option('zc_maintenance_msg', sanitize_textarea_field($_POST['zc_maint_msg'] ?? ''));
            $roles = array_filter((array)($_POST['zc_maint_roles'] ?? []));
            update_option('zc_maintenance_roles', $roles);
            $users_raw = sanitize_text_field($_POST['zc_maint_users'] ?? '');
            $users = array_filter(array_map('intval', explode(',', $users_raw)));
            update_option('zc_maintenance_users', $users);
            update_option('zc_webp_quality',  intval($_POST['zc_webp_quality'] ?? 82));
            update_option('zc_webp_serve',    intval($_POST['zc_webp_serve'] ?? 0));
        // Bump cache version to force SW update
        if (!empty($_POST['zc_bump_cache'])) {
            update_option('zc_cache_version', 'v' . time());
            flush_rewrite_rules();
        }
            echo '<div class="notice notice-success"><p>Nastavenia uložené!</p></div>';
        }
        $maint_on    = get_option('zc_maintenance_on', 0);
        $maint_msg   = get_option('zc_maintenance_msg', 'Web sa momentálne aktualizuje. Ozvite sa mi priamo.');
        $maint_roles = get_option('zc_maintenance_roles', []);
        $maint_users = get_option('zc_maintenance_users', []);
        $webp_q      = get_option('zc_webp_quality', 82);
        $webp_serve  = get_option('zc_webp_serve', 0);
        $all_roles   = wp_roles()->get_names();
        $all_users   = get_users(['number'=>50,'fields'=>['ID','display_name']]);
        ?>
        <form method="post" style="max-width:600px">
        <?php wp_nonce_field('zc_maint') ?>

        <div style="background:#fff;padding:28px;border-radius:8px;margin-top:20px;border:1px solid #e2e8f0">
            <h2 style="font-size:18px;margin-bottom:16px">Maintenance Mode</h2>
            <table class="form-table">
                <tr>
                    <th>Zapnúť maintenance</th>
                    <td>
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                            <input type="hidden" name="zc_maint_on" value="0">
                            <input type="checkbox" name="zc_maint_on" value="1" <?php checked($maint_on,1) ?> style="width:18px;height:18px;accent-color:#B8A47A">
                            <span style="font-weight:600;color:<?php echo $maint_on?'#dc2626':'#666'?>"><?php echo $maint_on?'Zapnuté':'Vypnuté'?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Správa na stránke</th>
                    <td><textarea name="zc_maint_msg" rows="3" class="regular-text" style="width:100%"><?php echo esc_textarea($maint_msg) ?></textarea></td>
                </tr>
                <tr>
                    <th>Kto vidí web (role)</th>
                    <td>
                        <?php foreach($all_roles as $role_slug => $role_name): ?>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer">
                            <input type="checkbox" name="zc_maint_roles[]" value="<?php echo esc_attr($role_slug) ?>"
                                <?php echo in_array($role_slug,(array)$maint_roles)?'checked':'' ?>
                                style="accent-color:#B8A47A">
                            <?php echo esc_html($role_name) ?>
                        </label>
                        <?php endforeach; ?>
                        <p class="description">Admini vždy vidia web. Ostatní vidia maintenance stránku.</p>
                    </td>
                </tr>
                <tr>
                    <th>Konkrétni používatelia (ID)</th>
                    <td>
                        <?php foreach($all_users as $u): ?>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:5px;cursor:pointer">
                            <input type="checkbox" name="zc_maint_users_cb[]" value="<?php echo $u->ID ?>"
                                <?php echo in_array($u->ID,(array)$maint_users)?'checked':'' ?>
                                onchange="zcUpdateUsers()"
                                style="accent-color:#B8A47A">
                            <?php echo esc_html($u->display_name) ?> <small style="color:#999">(ID: <?php echo $u->ID ?>)</small>
                        </label>
                        <?php endforeach; ?>
                        <input type="hidden" name="zc_maint_users" id="zcMaintUsers"
                            value="<?php echo esc_attr(implode(',',$maint_users)) ?>">
                        <script>
                        function zcUpdateUsers(){
                            var ids=[];
                            document.querySelectorAll('[name="zc_maint_users_cb[]"]:checked').forEach(function(cb){ids.push(cb.value)});
                            document.getElementById('zcMaintUsers').value=ids.join(',');
                        }
                        </script>
                    </td>
                </tr>
            </table>
            <a href="<?php echo home_url('/?preview_maintenance=1') ?>" target="_blank" class="button" style="margin-top:8px">Náhľad maintenance stránky</a>
        </div>

        <div style="background:#fff;padding:28px;border-radius:8px;margin-top:20px;border:1px solid #e2e8f0">
            <h2 style="font-size:18px;margin-bottom:16px">WebP Optimalizátor</h2>
            <table class="form-table">
                <tr>
                    <th>Kvalita WebP</th>
                    <td>
                        <input type="range" name="zc_webp_quality" min="50" max="100" value="<?php echo $webp_q ?>"
                            oninput="this.nextElementSibling.textContent=this.value+'%'"
                            style="width:200px;accent-color:#B8A47A">
                        <span style="font-weight:700;color:#B8A47A;margin-left:8px"><?php echo $webp_q ?>%</span>
                        <p class="description">Nové upload fotky sa automaticky konvertujú na WebP.</p>
                    </td>
                </tr>
                <tr>
                    <th>Servovať WebP automaticky</th>
                    <td>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="zc_webp_serve" value="1" <?php checked($webp_serve,1) ?> style="accent-color:#B8A47A">
                            Nahradiť JPEG/PNG odkazmi na WebP verziu
                        </label>
                    </td>
                </tr>
            </table>
            <p style="margin-top:16px">
                <button type="button" class="button button-secondary" onclick="zcBulkWebp(this)">
                    Konvertovať všetky existujúce fotky na WebP
                </button>
            </p>
            <p style="margin-top:12px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
                    <input type="checkbox" name="zc_bump_cache" value="1" style="accent-color:#B8A47A">
                    Pri uložení vymazať cache PWA (odporúča sa pri každej aktualizácii webu)
                </label>
                <small style="color:#666;margin-top:4px;display:block">Aktuálna verzia: <code><?php echo esc_html(get_option('zc_cache_version','v1')); ?></code></small>
                <span id="zcWebpResult" style="margin-left:12px;font-size:13px;color:#666"></span>
            </p>
            <script>
            function zcBulkWebp(btn){
                btn.disabled=true;btn.textContent='Konvertujem...';
                var data=new FormData();
                data.append('action','zc_bulk_webp');
                data.append('_wpnonce','<?php echo wp_create_nonce("zc_bulk_webp") ?>');
                fetch('<?php echo admin_url("admin-ajax.php") ?>',{method:'POST',body:data})
                .then(function(r){return r.json()})
                .then(function(res){
                    btn.disabled=false;btn.textContent='Konvertovať všetky existujúce fotky na WebP';
                    if(res.success){
                        document.getElementById('zcWebpResult').innerHTML=
                            'Hotovo! Skonvertované: <strong>'+res.data.converted+'</strong> / '+res.data.total+
                            (res.data.failed?' | Chyby: '+res.data.failed:'');
                    }
                });
            }
            </script>
        </div>

        <p style="margin-top:20px">
            <input type="submit" name="zc_maint_save" value="Uložiť nastavenia" class="button button-primary button-large">
        </p>
        </form>
    </div>
    <?php
}
require_once get_stylesheet_directory() . '/inc/elementor-check.php';
require_once get_stylesheet_directory() . '/inc/transition.php';
require_once get_stylesheet_directory() . '/inc/spam-protection.php';
require_once get_stylesheet_directory() . '/inc/pwa.php';
require_once get_stylesheet_directory() . '/inc/favorites.php';
require_once get_stylesheet_directory() . '/inc/webp-optimizer.php';
require_once get_stylesheet_directory() . '/inc/maintenance.php';
require_once get_stylesheet_directory() . '/inc/email-template.php';
require_once get_stylesheet_directory() . '/inc/privacy.php';
require_once get_stylesheet_directory() . '/inc/seo.php';


// ═══ SECURITY HARDENING ═══════════════════════════════════════
// Hide WordPress version (harder to target known exploits)
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// Disable XML-RPC (common brute-force vector)
add_filter('xmlrpc_enabled', '__return_false');

// Remove RSD + wlwmanifest links (not needed)
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// Disable file editing in WP Admin (if hacked, can't edit theme files)
if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true);

// Block user enumeration (?author=1 reveals usernames)
add_action('init', function() {
    if (isset($_GET['author']) && !is_admin()) {
        wp_redirect(home_url(), 301); exit;
    }
});

// Remove REST API user listing for non-admins (username leak)
add_filter('rest_endpoints', function($endpoints) {
    if (!current_user_can('list_users')) {
        unset($endpoints['/wp/v2/users']);
        unset($endpoints['/wp/v2/users/(?P<id>[\\d]+)']);
    }
    return $endpoints;
});

// Login error messages - don't reveal if username exists
add_filter('login_errors', function() {
    return 'Nesprávne prihlasovacie údaje.';
});

// Add security headers
add_action('send_headers', function() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
});

// Rate limit login attempts (simple transient-based)
add_filter('authenticate', function($user, $username) {
    if (empty($username)) return $user;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = 'zc_login_' . md5($ip);
    $attempts = (int)get_transient($key);
    if ($attempts >= 5) {
        return new WP_Error('too_many_attempts',
            'Príliš veľa pokusov o prihlásenie. Skúste znova o 15 minút.');
    }
    return $user;
}, 30, 2);

add_action('wp_login_failed', function() {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = 'zc_login_' . md5($ip);
    $attempts = (int)get_transient($key);
    set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
});

add_action('wp_login', function() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    delete_transient('zc_login_' . md5($ip));
});
