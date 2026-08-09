<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function() {
    // Fonty sú self-hostované v main.css (@font-face) – žiadne Google servery.
    // Používame zmenšenú verziu štýlov; pôvodný main.css ostáva v téme na úpravy.
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    // Keby zmenšené štýly niekedy robili problém, dajú sa jedným klikom
    // vypnúť vo Web Zdenky → Nástroje a web hneď beží na pôvodnom main.css.
    $use_min = get_option('zc_min_css', '1') === '1'
            && file_exists($dir . '/assets/css/main.min.css');
    $css = $use_min ? '/assets/css/main.min.css' : '/assets/css/main.css';
    wp_enqueue_style('zdenka-main', $uri . $css, [], '3.42.0');
    // Malé kritické úpravy musia platiť aj pri zapnutej staršej minifikovanej verzii.
    wp_add_inline_style('zdenka-main',
        '.zc-nav a{font-size:11px}.zc-prop-badge.is-sold{background:#DC2626!important;color:#fff!important;box-shadow:0 0 9px rgba(220,38,38,.85),0 0 20px rgba(220,38,38,.5)}'
    );
    wp_enqueue_script('zdenka-js', $uri . '/assets/js/main.js', [], '3.42.0', true);
    wp_localize_script('zdenka-js','zcData',['ajaxurl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('zc_nonce'),'logoUrl'=>get_stylesheet_directory_uri().'/assets/images/zc-logo.png','ebookOn'=>(function_exists('zc_ebook_enabled') && zc_ebook_enabled())?1:0,'interests'=>function_exists('zcn_interest_choices')?zcn_interest_choices():[],'nlNonce'=>wp_create_nonce('zcn_nonce')]);
});

add_action('after_setup_theme', function() {
    register_nav_menus(['primary'=>'Hlavné menu','footer'=>'Footer menu']);
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo',['height'=>60,'width'=>200,'flex-width'=>true]);
    add_image_size('property-thumb',640,480,true);
    add_image_size('property-hero',1280,720,true);
    // Medzikroky pre veľké monitory. Bez nich musí prehliadač skočiť
    // z 1024 px rovno na originál – buď mäkká fotka, alebo zbytočne
    // veľký súbor. Nie sú orezané, takže si ich prehliadač berie do srcset.
    add_image_size('zc-1440',1440,0,false);
    add_image_size('zc-2048',2048,0,false);
});

add_filter('body_class',function($c){$c[]='zdenka-theme';return $c;});

/**
 * Nepoužívaný JavaScript z WordPressu preč.
 * Emoji skript (~15 kB) prekresľuje emoji na obrázky – tento web ich nepoužíva.
 * wp-embed.js slúži na vkladanie iných WordPress stránok, tiež netreba.
 */
add_action('init', function () {
    if (is_admin()) return;
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');
    add_filter('tiny_mce_plugins', function ($p) {
        return is_array($p) ? array_diff($p, ['wpemoji']) : [];
    });
});

add_action('wp_footer', function () {
    if (is_admin()) return;
    wp_dequeue_script('wp-embed');
}, 1);


/** Existuje stránka Referencie? (v menu ju ukazujeme len ak áno) */
function zc_has_referencie() {
    static $has = null;
    if ($has === null) $has = (bool) get_page_by_path('referencie');
    return $has;
}

add_filter('theme_page_templates', function($t) {
    $t['templates/page-home.php']         = 'Homepage';
    $t['templates/page-o-mne.php']        = 'O mne';
    $t['templates/page-ako-pracujem.php'] = 'Ako pracujem';
    $t['templates/page-ponuky.php']       = 'Ponuky';
    $t['templates/page-kontakt.php']      = 'Kontakt';
    $t['templates/page-odhad.php']         = 'Odhad nehnuteľnosti';
    $t['templates/page-referencie.php']    = 'Referencie';
    $t['templates/page-breakdance.php']    = 'Breakdance – editovateľná stránka';
    return $t;
});

// AJAX form
add_action('wp_ajax_zc_contact','zc_handle_contact');
add_action('wp_ajax_nopriv_zc_contact','zc_handle_contact');
function zc_handle_contact() {
    check_ajax_referer('zc_nonce','nonce');
    // Honeypot – skryté polia vyplní iba bot (autofill-safe názvy)
    if (!empty($_POST['zc_hpf_a']) || !empty($_POST['zc_hpf_b'])) {
        wp_send_json_error(['message'=>'Správu sa nepodarilo odoslať.']);
    }
    $name  = sanitize_text_field($_POST['name']??'');
    $email = sanitize_email($_POST['email']??'');
    $phone = sanitize_text_field($_POST['phone']??'');
    $msg   = sanitize_textarea_field($_POST['message']??'');
    // Na typ nehnuteľnosti sa už vo formulári nepýtame – opýtame sa až
    // po odoslaní, a len toho, kto si zaškrtol newsletter.
    $interest = '';
    if (!$name||!$email) { wp_send_json_error(['message'=>'Vyplňte meno a email.']); }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Adresátov určuje nastavenie notifikácií (roly / používatelia / e-maily)
    $to   = function_exists('zc_notify_to') ? zc_notify_to('contact')
          : (get_theme_mod('zc_email_main', '') ?: get_option('admin_email'));
    $bcc  = get_theme_mod('zc_email_bcc', '');
    $from = zc_mail_from();
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
        'extra'=>$interest ? ['Záujem o ponuky' => function_exists('zcn_interest_label') ? zcn_interest_label($interest) : $interest] : [],
    ]);
    wp_mail($to, "Správa z webu: $name", $html, $headers);

    // Zapísať do databázy klientov (CRM), ak je plugin aktívny
    if (function_exists('pp_capture_lead')) {
        pp_capture_lead(['name'=>$name,'email'=>$email,'phone'=>$phone,'message'=>$msg,'source'=>'kontakt','interest'=>$interest]);
    }

    // Newsletter opt-in
    $newsletter_requested = !empty($_POST['newsletter']);
    $newsletter_added = null;
    if ($newsletter_requested && $email) {
        $newsletter_added = function_exists('zcn_subscribe_forced')
            ? zcn_subscribe_forced($email, $name, 'kontakt', $interest)
            : false;
    }

    $response_message = 'Správa odoslaná! Ozvem sa vám čoskoro.';
    $interest_token = '';
    if ($newsletter_requested && $newsletter_added) {
        $response_message .= ' E-mail bol pridaný aj do newslettera.';
        // Token pre okno „O aké ponuky máte záujem?" – platí 30 minút
        if (function_exists('zcn_interest_token')) $interest_token = zcn_interest_token($email);
    } elseif ($newsletter_requested && !$newsletter_added) {
        $response_message .= ' Prihlásenie do newslettera sa nepodarilo; skúste samostatný formulár Newsletter.';
    }
    wp_send_json_success([
        'message'         => $response_message,
        'newsletter_added'=> (bool) $newsletter_added,
        'interest_token'  => $interest_token,
    ]);
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

    // Kto je pri ponukách podpísaný, keď ju do systému zapíše niekto iný
    $zc_agents  = function_exists('pp_agent_candidates') ? pp_agent_candidates() : [];
    $zc_choices = [0 => 'Autor ponuky (kto ju zapísal)'];
    foreach ($zc_agents as $zc_u) $zc_choices[$zc_u->ID] = $zc_u->display_name;
    $wpc->add_setting('zc_agent_user',['default'=>0,'sanitize_callback'=>'absint']);
    $wpc->add_control('zc_agent_user',[
        'label'   => 'Predvolená maklérka pri ponukách',
        'section' => 'zc_agent',
        'type'    => 'select',
        'choices' => $zc_choices,
        'description' => 'Zobrazí sa pri každej ponuke, ktorá nemá priradenú vlastnú maklérku – aj keď ju do systému zapíše webmaster.',
    ]);

    // Fotky maklérky – hero (široká) + portrét (vertikálna tvár)
    $wpc->add_section('zc_photos',['title'=>'Fotky maklérky','priority'=>32,
        'description'=>'Každá fotka je samostatná – hero na úvodnej stránke, O mne na podstránke, vizitka do malých kruhov. Prázdne pole = použije sa portrét.']);
    foreach([
        'zc_photo_hero'     => 'Hero fotka (široká / horizontálna) – úvodná stránka',
        'zc_photo_portrait' => 'Portrét (vertikálna, hlavne tvár) – hero na mobile',
        'zc_photo_about'    => 'Fotka do sekcie O mne (ak prázdne, použije sa portrét)',
        'zc_photo_card'     => 'Vizitka – fotka do malých kruhov (ak prázdne, použije sa portrét)',
    ] as $id=>$lbl) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wpc->add_control(new WP_Customize_Image_Control($wpc,$id,['label'=>$lbl,'section'=>'zc_photos']));
    }

    // Úvodná stránka – čo sa má zobraziť
    $wpc->add_section('zc_home_sections',['title'=>'Úvodná stránka – sekcie','priority'=>32.5,
        'description'=>'Vypnutá sekcia sa na úvodnej stránke vôbec nevykreslí.']);
    $wpc->add_setting('zc_home_values',['default'=>'0','sanitize_callback'=>function($v){return $v === '1' ? '1' : '0';}]);
    $wpc->add_control('zc_home_values',[
        'label'   => 'Zobraziť sekciu „Čo ma riadi pri práci“',
        'section' => 'zc_home_sections',
        'type'    => 'checkbox',
        'description' => 'Vypnuté = po hero fotke idú rovno nehnuteľnosti.',
    ]);

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
    // Sociálne siete + Google recenzie
    $wpc->add_section('zc_social',['title'=>'Sociálne siete & Google','priority'=>33.5,
        'description'=>'Odkazy sa zobrazia ako ikony v pätičke. Prázdne pole = ikona sa neukáže.']);
    foreach([
        'zc_social_fb'       => 'Facebook URL',
        'zc_social_ig'       => 'Instagram URL',
        'zc_social_linkedin' => 'LinkedIn URL',
        'zc_social_youtube'  => 'YouTube URL',
        'zc_social_google'   => 'Google firma – odkaz na recenzie (Google Maps / profil)',
    ] as $id=>$lbl) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wpc->add_control($id,['label'=>$lbl,'section'=>'zc_social','type'=>'url']);
    }

    // ── Miestne SEO (Google) ────────────────────────────────────────────
    // Tieto údaje idú do štruktúrovaných dát pre Google. Musia sa presne
    // zhodovať s firemným profilom na Google – inak si ich Google „nespáruje".
    $wpc->add_section('zc_localseo',['title'=>'Miestne SEO (Google)','priority'=>33.7,
        'description'=>'Adresa, hodiny a poloha pre Google. Vyplň rovnako ako vo firemnom profile na Google.']);
    foreach([
        'zc_addr_street' => ['Ulica a číslo','text','Napr. Námestie SNP 1'],
        'zc_addr_city'   => ['Mesto','text','Banská Bystrica'],
        'zc_addr_zip'    => ['PSČ','text','974 01'],
        'zc_addr_region' => ['Kraj','text','Banskobystrický kraj'],
        'zc_geo_lat'     => ['Zemepisná šírka','text','Z Google Máp: klik pravým na miesto → prvý riadok, napr. 48.7359'],
        'zc_geo_lng'     => ['Zemepisná dĺžka','text','Druhé číslo, napr. 19.1462'],
        'zc_open_hours'  => ['Otváracie hodiny','text','Formát pre Google: Mo-Fr 09:00-17:00'],
        'zc_area_served' => ['Kde pôsobíš','text','Mestá oddelené čiarkou: Banská Bystrica, Zvolen, Brezno'],
        'zc_gsc_verify'  => ['Overovací kód Search Console','text','Len hodnota z content="…", nie celý riadok'],
        'zc_gtm_id'      => ['Správca značiek Google (GTM)','text','ID kontajnera v tvare GTM-XXXXXXX. Kód sa vloží sám do hlavičky aj za <body>.'],
    ] as $id=>[$lbl,$type,$desc]) {
        $wpc->add_setting($id,['default'=>'','sanitize_callback'=>'sanitize_text_field']);
        $wpc->add_control($id,['label'=>$lbl,'section'=>'zc_localseo','type'=>$type,'description'=>$desc]);
    }
    $wpc->add_setting('zc_gtm_skip_admins',['default'=>false,'sanitize_callback'=>function($v){return (bool)$v;}]);
    $wpc->add_control('zc_gtm_skip_admins',[
        'label'=>'Nemerať prihlásených redaktorov','section'=>'zc_localseo','type'=>'checkbox',
        'description'=>'Nechaj vypnuté, kým si v GTM ladíš značky – režim náhľadu inak nebude fungovať.',
    ]);

    // Štartovacia hlasitosť videí — aby po spustení „nehúkalo"
    $wpc->add_setting('zc_video_volume',['default'=>50,'sanitize_callback'=>'absint']);
    $wpc->add_control('zc_video_volume',[
        'label'=>'Štartovacia hlasitosť videí (%)','section'=>'zc_ap','type'=>'number',
        'input_attrs'=>['min'=>0,'max'=>100,'step'=>5],
        'description'=>'Platí pre videá v „Ako pracujem" aj na detaile ponuky.',
    ]);
});

// From adresa pre odchádzajúce e-maily — VŽDY na doméne webu (kvôli SPF/DKIM,
// aby e-maily nekončili ako [SPAM]). Ak je v Customizeri nastavená vlastná
// adresa na doméne, použije sa tá.
function zc_mail_from() {
    $set = get_theme_mod('zc_email_from', '');
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    $host = preg_replace('/^www\./', '', (string) $host);
    // Vlastnú adresu použijeme len ak je na tej istej doméne (inak SPF zlyhá)
    if ($set && is_email($set) && $host && stripos($set, '@' . $host) !== false) {
        return $set;
    }
    return 'noreply@' . ($host ?: 'localhost');
}

// Globálny fallback pre všetky wp_mail() (welcome e-mail, atď.)
add_filter('wp_mail_from', function ($from) {
    // Predvolenú „wordpress@..." adresu nahradíme peknou on-domain adresou
    if (!$from || strpos($from, 'wordpress@') === 0) return zc_mail_from();
    return $from;
});
add_filter('wp_mail_from_name', function ($name) {
    $n = zc_agent('name', '');
    return $n ?: $name;
});

// Sociálne siete – vráti pole [názov => [url, svg-ikona]] len pre vyplnené
function zc_social_links() {
    $icons = [
        'fb'       => '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg>',
        'ig'       => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>',
        'linkedin' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5A2.5 2.5 0 1 1 5 8.5a2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM9 9h3.8v1.7h.05c.53-1 1.83-2.05 3.76-2.05C20.5 8.65 21 11 21 14.1V21h-4v-6.1c0-1.45-.03-3.3-2-3.3-2 0-2.3 1.57-2.3 3.2V21H9z"/></svg>',
        'youtube'  => '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M23 12s0-3.2-.4-4.7a2.5 2.5 0 0 0-1.7-1.8C19.4 5 12 5 12 5s-7.4 0-8.9.5A2.5 2.5 0 0 0 1.4 7.3C1 8.8 1 12 1 12s0 3.2.4 4.7a2.5 2.5 0 0 0 1.7 1.8C4.6 19 12 19 12 19s7.4 0 8.9-.5a2.5 2.5 0 0 0 1.7-1.8C23 15.2 23 12 23 12zM9.8 15.3V8.7l5.7 3.3z"/></svg>',
        'google'   => '<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1H12v3.83h5.35c-.23 1.4-1.66 4.1-5.35 4.1a5.9 5.9 0 0 1 0-11.8c1.87 0 3.13.8 3.85 1.48l2.62-2.53C16.9 3.6 14.66 2.6 12 2.6A9.4 9.4 0 1 0 21.35 11.1z"/></svg>',
    ];
    $out = [];
    foreach ($icons as $key => $svg) {
        $url = get_theme_mod('zc_social_' . $key, '');
        if ($url) $out[$key] = ['url' => $url, 'icon' => $svg];
    }
    return $out;
}

// Vykreslí ikony sociálnych sietí (znovupoužiteľné – navbar, kontakt, formulár)
function zc_social_icons_html($extra_class = '') {
    $socials = zc_social_links();
    if (!$socials) return '';
    $titles = ['fb'=>'Facebook','ig'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube','google'=>'Google recenzie'];
    $h = '<div class="zc-socials ' . esc_attr($extra_class) . '">';
    foreach ($socials as $k => $s) {
        $lbl = $titles[$k] ?? $k;
        // Trieda podľa siete – v navbare z nej CSS spraví ikonu v brand farbe
        $h .= '<a href="' . esc_url($s['url']) . '" target="_blank" rel="noopener" class="zc-social-ic zc-soc-' . esc_attr($k) . '" aria-label="' . esc_attr($lbl) . '" title="' . esc_attr($lbl) . '">' . $s['icon'] . '</a>';
    }
    $h .= '</div>';
    return $h;
}

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

function zc_agent_name_with_title($name = '') {
    $name = trim((string) $name);
    $name = trim(preg_replace('/^(?:Mgr\.\s*)+/u', '', $name));
    return 'Mgr. ' . ($name ?: 'Zdenka Cibuľová');
}

function zc_agent($k,$f='') {
    $value = get_theme_mod('zc_agent_'.$k,$f) ?: $f;
    return $k === 'name' ? zc_agent_name_with_title($value) : $value;
}

/**
 * ID fotky z Prispôsobiť (aby sa dali použiť zmenšeniny).
 * Hľadanie podľa adresy je dopyt do databázy, preto si ho pamätáme.
 */
function zc_photo_id($which) {
    $url = get_theme_mod('zc_photo_' . $which, '');
    if (!$url) return 0;
    $key = 'zc_photoid_' . md5($url);
    $id  = get_transient($key);
    if ($id === false) {
        $id = (int) attachment_url_to_postid($url);
        set_transient($key, $id, WEEK_IN_SECONDS);
    }
    return (int) $id;
}

/**
 * Adresa fotky v rozumnej veľkosti.
 * Hero sa vkladá cez CSS background, kde srcset nefunguje – bez tohto by sa
 * na mobil sťahoval originál z fotoaparátu (aj niekoľko MB).
 */
function zc_photo_sized($which, $size = 'zc-1440') {
    $id = zc_photo_id($which);
    if (!$id) return zc_photo($which);

    // POZOR: keď žiadaná veľkosť neexistuje, WordPress ticho vráti ORIGINÁL.
    // Práve preto sa na mobile sťahovala fotka z fotoaparátu, hoci sme si
    // pýtali zmenšeninu. Štvrtá hodnota návratu hovorí, či ide naozaj
    // o zmenšeninu – ak nie, skúšame postupne menšie, ktoré existujú.
    foreach ([$size, 'zc-1440', 'large', 'medium_large', 'medium'] as $try) {
        $src = wp_get_attachment_image_src($id, $try);
        if ($src && !empty($src[0]) && !empty($src[3])) return $src[0];
    }

    // Žiadna zmenšenina neexistuje – originál je posledná možnosť
    $full = wp_get_attachment_image_src($id, 'full');
    return (!empty($full[0])) ? $full[0] : zc_photo($which);
}

/**
 * Dopočíta chýbajúce veľkosti pre fotky z Prispôsobiť.
 * Bez nich by hero siahol po origináli – a to je na mobile ten najväčší
 * súbor na stránke. Beží len vo wp-admine a pre každú fotku najviac raz.
 */
add_action('admin_init', function () {
    if (!current_user_can('upload_files')) return;

    foreach (['hero', 'portrait', 'card'] as $which) {
        $id = zc_photo_id($which);
        if (!$id) continue;

        $key = 'zc_photofix_' . $id;
        if (get_transient($key)) continue;

        $src = wp_get_attachment_image_src($id, 'zc-1440');
        if ($src && !empty($src[3])) { set_transient($key, 1, WEEK_IN_SECONDS); continue; }

        $file = get_attached_file($id);
        if ($file && file_exists($file)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $meta = wp_generate_attachment_metadata($id, $file);
            if ($meta && !is_wp_error($meta)) wp_update_attachment_metadata($id, $meta);
        }
        set_transient($key, 1, DAY_IN_SECONDS);
        return; // vždy len jedna fotka na načítanie, nech to nezdrží admin
    }
});

// Líniové SVG ikony pre tému (náhrada za emoji). Ak je aktívny plugin, použije jeho sadu.
function zc_svg($name, $size = 24) {
    if (function_exists('pp_svg')) { $s = pp_svg($name, $size); if ($s) return $s; }
    $p = [
        'heart'     => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'bulb'      => '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/>',
        'bolt'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
        'handshake' => '<path d="m11 17 2 2a1 1 0 0 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 0 0 3-3l-3.9-3.9a2 2 0 0 0-2.8 0l-.6.6a2 2 0 0 1-2.8 0l-1-1a2 2 0 0 1 0-2.8l1.5-1.5a4 4 0 0 1 3-1.1l3.4.2"/><path d="M3 12v-2a4 4 0 0 1 4-4h1"/>',
        'camera'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
        'video'     => '<rect x="2" y="5" width="15" height="14" rx="2"/><path d="m17 9 5-3v12l-5-3z"/>',
        'pen'       => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'megaphone' => '<path d="m3 11 18-5v12L3 13z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'scale'     => '<path d="M12 3v18M5 7l-3 6h6zM19 7l-3 6h6zM5 7l7-2 7 2M4 21h16"/>',
        'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9"/><path d="M9 21v-6h6v6"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $b = $p[$name] ?? '';
    if (!$b) return '';
    return '<svg class="pp-ic" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$b.'</svg>';
}

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
            // youtube-nocookie.com = režim „rozšírenej ochrany súkromia" –
            // YouTube nenastaví sledovacie cookies, kým návštevník video nespustí
            'url'      => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1&enablejsapi=1&origin=' . $origin,
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
    $current = get_theme_mod('zc_agent_name', 'Zdenka Cibuľová');
    $fixed   = zc_agent_name_with_title($current);
    if ($current !== $fixed) set_theme_mod('zc_agent_name', $fixed);
});

// Aj WordPress profil maklérky používa rovnaký titul. Beží iba raz po aktualizácii.
add_action('admin_init', function() {
    if (get_option('zc_mgr_profile_migrated') === '1') return;
    $ids = [];
    $default_id = (int) get_theme_mod('zc_agent_user', 0);
    if ($default_id) $ids[] = $default_id;
    $agents = get_users(['role' => 'realitny_makler']);
    foreach ($agents as $user) {
        if (count($agents) === 1
            || stripos($user->display_name, 'Zdenka') !== false
            || stripos($user->display_name, 'Cibu') !== false) {
            $ids[] = (int) $user->ID;
        }
    }
    foreach (array_unique(array_filter($ids)) as $uid) {
        $user = get_userdata($uid);
        if ($user) wp_update_user(['ID' => $uid, 'display_name' => zc_agent_name_with_title($user->display_name)]);
    }
    update_option('zc_mgr_profile_migrated', '1', false);
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
        ['Referencie',          'referencie', 'templates/page-referencie.php'],
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
    // Ak Breakdance už vybral vlastný renderer alebo je stránka vedome prepnutá
    // na Breakdance template, slugové routovanie nesmie jeho výstup prepísať.
    if (function_exists('zc_should_respect_breakdance_template')
        && zc_should_respect_breakdance_template($template)) {
        return $template;
    }
    // Match by slug OR page ID
    if (is_page('o-mne'))        return $dir . '/templates/page-o-mne.php';
    if (is_page('ako-pracujem')) return $dir . '/templates/page-ako-pracujem.php';
    if (is_page('kontakt'))      return $dir . '/templates/page-kontakt.php';
    if (is_page('odhad'))        return $dir . '/templates/page-odhad.php';
    if (is_page('oblubene'))     return $dir . '/templates/page-oblubene.php';
    if (is_page('referencie'))   return $dir . '/templates/page-referencie.php';
    // Ponuky – match by slug, title, or page template meta
    if (is_page('ponuky') || (is_page() && get_page_template_slug() === 'templates/page-ponuky.php')) {
        return $dir . '/templates/page-ponuky.php';
    }
    return $template;
}, 999);

// ── Stránka na vytvorenie stránok a údržbu (v menu je pod „Web Zdenky“) ──
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
            ['Referencie',           'referencie',   'templates/page-referencie.php'],
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
                <?php foreach([['Domov','domov'],['O mne','o-mne'],['Ako pracujem','ako-pracujem'],['Ponuky','ponuky'],['Kontakt','kontakt'],['Odhad nehnuteľnosti','odhad'],['Referencie','referencie']] as [$t,$s]):
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
require_once get_stylesheet_directory() . '/inc/notifications.php';
require_once get_stylesheet_directory() . '/inc/preview-role.php';
require_once get_stylesheet_directory() . '/inc/admin-hub.php';
require_once get_stylesheet_directory() . '/inc/privacy.php';
require_once get_stylesheet_directory() . '/inc/seo.php';
require_once get_stylesheet_directory() . '/inc/analytics.php';
require_once get_stylesheet_directory() . '/inc/typography.php';
require_once get_stylesheet_directory() . '/inc/indexing.php';
require_once get_stylesheet_directory() . '/inc/updates.php';
require_once get_stylesheet_directory() . '/inc/texts.php';
require_once get_stylesheet_directory() . '/inc/breakdance.php';
// Ebook je teraz samostatný plugin (zc-ebook). Ak je aktívny, poskytuje
// zc_ebook_* funkcie aj shortcode [zc_ebook]; téma ich používa cez function_exists.

// Preload hero fotky na úvode (rýchlejší LCP).
// Dôležité: preloadujeme presne tú adresu, ktorú potom použije CSS, a to
// zvlášť pre mobil a pre počítač – inak si prehliadač stiahne obe fotky.
add_action('wp_head', function() {
    if (!is_front_page() || !function_exists('zc_photo_sized')) return;

    $portrait = zc_photo_sized('portrait', 'large');
    $desktop  = zc_photo_sized('hero', 'zc-1440');

    if ($portrait) {
        printf('<link rel="preload" as="image" href="%s" media="(max-width:768px)" fetchpriority="high">%s',
            esc_url($portrait), "\n");
    }
    if ($desktop) {
        printf('<link rel="preload" as="image" href="%s" media="(min-width:769px)" fetchpriority="high">%s',
            esc_url($desktop), "\n");
    }
}, 1);


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
