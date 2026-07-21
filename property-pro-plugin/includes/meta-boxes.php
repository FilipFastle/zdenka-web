<?php
defined('ABSPATH') || exit;
require_once PROPERTY_PRO_PATH . 'includes/amenities.php';

add_action('add_meta_boxes', function() {
    add_meta_box('prop_main', 'Nehnuteľnosť', 'render_prop_main_meta', 'property', 'normal', 'high');
    add_meta_box('prop_gallery', 'Fotky & Video', 'render_gallery_meta', 'property', 'normal', 'high');
    add_meta_box('prop_amenities_box', 'Vybavenie a okolie', 'render_amenities_meta_box', 'property', 'normal', 'default');
    add_meta_box('prop_agent_box', 'Maklér/ka', 'render_agent_meta_box', 'property', 'side', 'default');
});

function render_prop_main_meta($post) {
    wp_nonce_field('prop_nonce', 'prop_nonce_field');
    
    $f = function($key) use ($post) { return get_post_meta($post->ID, '_property_' . $key, true); };
    ?>
    <style>
    .pm-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
    .pm-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px}
    .pm-field{display:flex;flex-direction:column;gap:6px}
    .pm-field label{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#666}
    .pm-field input,.pm-field select,.pm-field textarea{padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px;font-family:inherit;transition:border .2s}
    .pm-field input:focus,.pm-field select:focus,.pm-field textarea:focus{outline:none;border-color:#3498db}
    .pm-field textarea{resize:vertical;min-height:80px}
    .pm-sep{grid-column:1/-1;border:none;border-top:1px solid #f0f0f0;margin:4px 0}
    .pm-title{font-size:13px;font-weight:700;color:#333;margin:16px 0 10px;padding-bottom:6px;border-bottom:2px solid #f0f0f0}
    </style>
    
    <div class="pm-grid" style="grid-template-columns:1fr 1fr 1fr">
        <div class="pm-field" style="grid-column:1/-1">
            <label>Názov nehnuteľnosti</label>
        </div>
    </div>
    
    <div class="pm-grid">
        <div class="pm-field">
            <label>Typ ponuky</label>
            <select name="prop_typ">
                <option value="">Vyber...</option>
                <option value="predaj" <?php selected($f('typ'),'predaj') ?>>Predaj</option>
                <option value="prenajom" <?php selected($f('typ'),'prenajom') ?>>Prenájom</option>
                <option value="pozemok" <?php selected($f('typ'),'pozemok') ?>>Pozemok</option>
            </select>
        </div>
        <div class="pm-field">
            <label>Cena</label>
            <input type="text" name="prop_cena" value="<?php echo esc_attr($f('cena')) ?>" placeholder="450 000 €">
        </div>
    </div>
    
    <div class="pm-grid" style="grid-template-columns:1fr 1fr 1fr">
        <div class="pm-field">
            <label>Lokalita</label>
            <input type="text" name="prop_lokalita" value="<?php echo esc_attr($f('lokalita')) ?>" placeholder="Banská Bystrica">
        </div>
        <div class="pm-field">
            <label>Mesto/Obec</label>
            <input type="text" name="prop_mesto" value="<?php echo esc_attr($f('mesto')) ?>" placeholder="Zvolen">
        </div>
        <div class="pm-field">
            <label>Okres</label>
            <input type="text" name="prop_okres" value="<?php echo esc_attr($f('okres')) ?>" placeholder="Zvolen">
        </div>
    </div>
    
    <div class="pm-title">Parametre</div>
    <div class="pm-grid-3">
        <?php foreach ([
            'plocha' => 'Úžitková plocha (m²)',
            'pozemok' => 'Pozemok (m²)',
            'spalne' => 'Počet izieb',
            'kupelne' => 'Kúpeľne',
            'wc' => 'WC',
            'poschodie' => 'Poschodie',
        ] as $key => $label): ?>
        <div class="pm-field">
            <label><?php echo $label ?></label>
            <input type="text" name="prop_<?php echo $key ?>" value="<?php echo esc_attr($f($key)) ?>">
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="pm-grid">
        <div class="pm-field">
            <label>Ročník stavby</label>
            <input type="text" name="prop_rocnik" value="<?php echo esc_attr($f('rocnik')) ?>" placeholder="2020">
        </div>
        <div class="pm-field">
            <label>Stav</label>
            <select name="prop_stav">
                <option value="">Vyber</option>
                <?php foreach (['Novostavba','Veľmi dobrý','Dobrý','Vyhovujúci','Rekonštrukcia potrebná'] as $s): ?>
                <option value="<?php echo $s ?>" <?php selected($f('stav'),$s) ?>><?php echo $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pm-field">
            <label>Vlastníctvo</label>
            <select name="prop_vlastnictvo">
                <option value="">Vyber</option>
                <?php foreach (['Osobné','Družstevné','Štátne','V príprave prevodu'] as $v): ?>
                <option value="<?php echo $v ?>" <?php selected($f('vlastnictvo'),$v) ?>><?php echo $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="pm-title">Popis</div>
    <div class="pm-field">
        <label>Krátky popis (na kartu)</label>
        <textarea name="prop_popis_kratky" rows="2" placeholder="Krátky popis..."><?php echo esc_textarea($f('popis_kratky')) ?></textarea>
    </div>
    <?php
}

function render_gallery_meta($post) {
    wp_nonce_field('gallery_nonce', 'gallery_nonce_field');
    $cover_id = get_post_meta($post->ID, '_property_cover_id', true);
    $gallery_ids = get_post_meta($post->ID, '_property_gallery_ids', true) ?: [];
    $video_url = get_post_meta($post->ID, '_property_video_url', true);
    ?>
    <style>
    .gal-preview{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;margin-bottom:12px}
    .gal-thumb{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;background:#f0f0f0}
    .gal-thumb img{width:100%;height:100%;object-fit:cover}
    .gal-thumb .gal-rm{position:absolute;top:3px;right:3px;width:22px;height:22px;border-radius:50%;background:#e74c3c;color:#fff;border:none;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center}
    .gal-btn{padding:9px 18px;border-radius:8px;font-weight:600;font-size:13px;border:none;cursor:pointer;margin-right:8px}
    </style>
    
    <p style="font-weight:600;margin-bottom:8px;color:#333">Cover foto <small style="color:#999">(hlavná fotka na karte)</small></p>
    <div id="cover-preview" class="gal-preview">
        <?php if ($cover_id && $img = wp_get_attachment_image_src($cover_id,'thumbnail')): ?>
        <div class="gal-thumb"><img src="<?php echo $img[0] ?>"><button type="button" class="gal-rm" onclick="rmCover()">✕</button></div>
        <?php endif; ?>
    </div>
    <button type="button" class="gal-btn" style="background:#3498db;color:#fff" onclick="selCover()">Vyber cover</button>
    <input type="hidden" name="prop_cover_id" id="cover-id" value="<?php echo esc_attr($cover_id) ?>">
    
    <hr style="margin:20px 0;border:none;border-top:1px solid #f0f0f0">
    
    <p style="font-weight:600;margin-bottom:8px;color:#333">Galéria <small style="color:#999">(masonry galéria na detail stránke)</small></p>
    <div id="gallery-preview" class="gal-preview">
        <?php foreach ($gallery_ids as $gid): if ($img = wp_get_attachment_image_src($gid,'thumbnail')): ?>
        <div class="gal-thumb" data-id="<?php echo $gid ?>"><img src="<?php echo $img[0] ?>"><button type="button" class="gal-rm" onclick="rmGal(this)">✕</button></div>
        <?php endif; endforeach; ?>
    </div>
    <button type="button" class="gal-btn" style="background:#27ae60;color:#fff" onclick="selGal()">Pridaj fotky</button>
    <input type="hidden" name="prop_gallery_ids" id="gallery-ids" value="<?php echo esc_attr(json_encode($gallery_ids)) ?>">
    
    <hr style="margin:20px 0;border:none;border-top:1px solid #f0f0f0">
    
    <p style="font-weight:600;margin-bottom:8px;color:#333">Video URL (YouTube / Vimeo)</p>
    <input type="text" name="prop_video_url" value="<?php echo esc_attr($video_url) ?>" placeholder="https://www.youtube.com/watch?v=..." style="width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px">
    
    <script>
    function selCover(){var f=wp.media({title:'Cover foto',button:{text:'Nastav'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();document.getElementById('cover-id').value=a.id;var t=a.sizes.thumbnail||a.sizes.full;document.getElementById('cover-preview').innerHTML='<div class="gal-thumb"><img src="'+t.url+'"><button type="button" class="gal-rm" onclick="rmCover()">✕</button></div>';});f.open();}
    function rmCover(){document.getElementById('cover-id').value='';document.getElementById('cover-preview').innerHTML='';}
    function selGal(){var f=wp.media({title:'Galéria',button:{text:'Pridaj'},multiple:true});f.on('select',function(){var g=JSON.parse(document.getElementById('gallery-ids').value||'[]');var p=document.getElementById('gallery-preview');f.state().get('selection').forEach(function(a){a=a.toJSON();if(!g.includes(a.id)){g.push(a.id);var t=a.sizes.thumbnail||a.sizes.full;p.innerHTML+='<div class="gal-thumb" data-id="'+a.id+'"><img src="'+t.url+'"><button type="button" class="gal-rm" onclick="rmGal(this)">✕</button></div>';}});document.getElementById('gallery-ids').value=JSON.stringify(g);});f.open();}
    function rmGal(b){var id=b.parentElement.dataset.id;var g=JSON.parse(document.getElementById('gallery-ids').value);g=g.filter(x=>x!=id);document.getElementById('gallery-ids').value=JSON.stringify(g);b.parentElement.remove();}
    </script>
    <?php
}

function render_amenities_meta_box($post) {
    $selected = get_post_meta($post->ID, '_property_amenities', true) ?: [];
    $all = get_property_amenities();
    ?>
    <style>
    .am-cat{margin-bottom:20px}
    .am-cat-title{font-size:13px;font-weight:700;color:#444;margin-bottom:10px;padding:6px 10px;background:#f8f9fa;border-radius:6px}
    .am-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px}
    .am-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;cursor:pointer;transition:background .15s}
    .am-item:hover{background:#f0f7ff}
    .am-item input{width:16px;height:16px;cursor:pointer;accent-color:#3498db}
    .am-item span{font-size:13px;color:#444}
    </style>
    <?php foreach ($all as $cat): ?>
    <div class="am-cat">
        <div class="am-cat-title"><?php echo pp_svg($cat['icon'] ?? '', 15) ?><?php echo esc_html($cat['label']) ?></div>
        <div class="am-grid">
            <?php foreach ($cat['items'] as $key => $label): ?>
            <label class="am-item">
                <input type="checkbox" name="prop_amenities[]" value="<?php echo $key ?>" <?php checked(in_array($key, $selected)) ?>>
                <span><?php echo esc_html($label) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php
}

function render_agent_meta_box($post) {
    $agent_id = get_post_meta($post->ID, '_property_agent_id', true);
    $users = get_users(['role__in' => ['administrator','editor','author']]);
    ?>
    <div style="display:flex;flex-direction:column;gap:10px">
        <label style="font-size:12px;font-weight:600;color:#666;text-transform:uppercase">Priradená maklérka</label>
        <select name="prop_agent_id" style="padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px">
            <option value="">Autor príspevku</option>
            <?php foreach ($users as $u): ?>
            <option value="<?php echo $u->ID ?>" <?php selected($agent_id, $u->ID) ?>><?php echo esc_html($u->display_name) ?></option>
            <?php endforeach; ?>
        </select>
        <p style="font-size:12px;color:#999">Telefón, WhatsApp a email sa nastavujú v Profile používateľa.</p>
    </div>
    <?php
}

// SAVE
add_action('save_post_property', function($post_id) {
    if (!isset($_POST['prop_nonce_field']) || !wp_verify_nonce($_POST['prop_nonce_field'], 'prop_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    foreach (['typ','cena','lokalita','mesto','okres','popis_kratky','plocha','pozemok','spalne','kupelne','wc','poschodie','rocnik','stav','vlastnictvo','agent_id'] as $f) {
        if (isset($_POST['prop_'.$f])) update_post_meta($post_id, '_property_'.$f, sanitize_text_field($_POST['prop_'.$f]));
    }
    
    if (isset($_POST['gallery_nonce_field']) && wp_verify_nonce($_POST['gallery_nonce_field'], 'gallery_nonce')) {
        update_post_meta($post_id, '_property_cover_id', intval($_POST['prop_cover_id'] ?? 0));
        $gids = json_decode(sanitize_text_field($_POST['prop_gallery_ids'] ?? '[]'), true);
        update_post_meta($post_id, '_property_gallery_ids', is_array($gids) ? array_map('intval', $gids) : []);
        update_post_meta($post_id, '_property_video_url', esc_url_raw($_POST['prop_video_url'] ?? ''));
    }
    
    $amenities = isset($_POST['prop_amenities']) ? array_map('sanitize_text_field', $_POST['prop_amenities']) : [];
    update_post_meta($post_id, '_property_amenities', $amenities);
}, 10, 1);

// Médiá pre uploader profilovej fotky na stránke profilu
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'profile.php' || $hook === 'user-edit.php') wp_enqueue_media();
});

// User profile fields for agent
add_action('show_user_profile', 'render_agent_profile_fields');
add_action('edit_user_profile', 'render_agent_profile_fields');
function render_agent_profile_fields($user) {
    $photo_id  = get_user_meta($user->ID, 'property_photo_id', true);
    $photo_src = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
    ?>
    <h3>Realitný profil</h3>
    <table class="form-table">
        <tr>
            <th><label>Profilová fotka</label></th>
            <td>
                <div id="pp-photo-preview" style="margin-bottom:10px">
                    <?php if ($photo_src): ?>
                    <img src="<?php echo esc_url($photo_src) ?>" style="width:96px;height:96px;object-fit:cover;border-radius:50%;box-shadow:0 0 0 3px #fff,0 0 0 5px #B8A47A">
                    <?php endif; ?>
                </div>
                <input type="hidden" name="property_photo_id" id="pp-photo-id" value="<?php echo esc_attr($photo_id) ?>">
                <button type="button" class="button" id="pp-photo-pick">Vybrať / nahrať fotku</button>
                <button type="button" class="button" id="pp-photo-clear" <?php echo $photo_src ? '' : 'style="display:none"' ?>>Odstrániť</button>
                <p class="description">Táto fotka sa zobrazí pri ponukách priradených tejto maklérke. Ak nie je nastavená, použije sa Gravatar podľa e-mailu.</p>
                <script>
                (function(){
                    var frame, pick=document.getElementById('pp-photo-pick'), clr=document.getElementById('pp-photo-clear'),
                        idIn=document.getElementById('pp-photo-id'), prev=document.getElementById('pp-photo-preview');
                    if(!pick) return;
                    pick.addEventListener('click', function(e){
                        e.preventDefault();
                        if(frame){ frame.open(); return; }
                        frame = wp.media({title:'Profilová fotka', button:{text:'Použiť'}, multiple:false, library:{type:'image'}});
                        frame.on('select', function(){
                            var a = frame.state().get('selection').first().toJSON();
                            idIn.value = a.id;
                            var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
                            prev.innerHTML = '<img src="'+url+'" style="width:96px;height:96px;object-fit:cover;border-radius:50%;box-shadow:0 0 0 3px #fff,0 0 0 5px #B8A47A">';
                            clr.style.display='';
                        });
                        frame.open();
                    });
                    clr.addEventListener('click', function(e){ e.preventDefault(); idIn.value=''; prev.innerHTML=''; clr.style.display='none'; });
                })();
                </script>
            </td>
        </tr>
        <tr><th><label>Telefón</label></th><td><input type="text" name="property_phone" value="<?php echo esc_attr(get_user_meta($user->ID,'property_phone',true)) ?>" class="regular-text" placeholder="+421 907 579 742"></td></tr>
        <tr><th><label>WhatsApp číslo</label></th><td><input type="text" name="property_whatsapp" value="<?php echo esc_attr(get_user_meta($user->ID,'property_whatsapp',true)) ?>" class="regular-text" placeholder="421907579742"></td></tr>
        <tr><th><label>Kontaktný email</label></th><td><input type="email" name="property_email" value="<?php echo esc_attr(get_user_meta($user->ID,'property_email',true)) ?>" class="regular-text"></td></tr>
        <tr><th><label>Titul / Pozícia</label></th><td><input type="text" name="property_title" value="<?php echo esc_attr(get_user_meta($user->ID,'property_title',true)) ?>" class="regular-text" placeholder="Realitná maklérka"></td></tr>
    </table>
    <?php
}

add_action('personal_options_update', 'save_agent_profile_fields');
add_action('edit_user_profile_update', 'save_agent_profile_fields');
function save_agent_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;
    foreach (['property_phone','property_whatsapp','property_email','property_title'] as $f) {
        if (isset($_POST[$f])) update_user_meta($user_id, $f, sanitize_text_field($_POST[$f]));
    }
    if (isset($_POST['property_photo_id'])) {
        update_user_meta($user_id, 'property_photo_id', absint($_POST['property_photo_id']));
    }
}
