<?php
defined('ABSPATH') || exit;
require_once PROPERTY_PRO_PATH . 'includes/amenities.php';

// Create panel page on activation
register_activation_hook(PROPERTY_PRO_PATH . 'property-manager-pro.php', 'zcpp_ensure_panel_page');

// Also ensure page exists on every admin load (handles file-only updates)
add_action('admin_init', 'zcpp_ensure_panel_page');

function zcpp_ensure_panel_page() {
    // Check if page exists (published or draft)
    $existing = get_posts([
        'post_type'   => 'page',
        'post_name'   => 'realitny-panel',
        'post_status' => ['publish', 'draft', 'private'],
        'numberposts' => 1,
    ]);

    if (empty($existing)) {
        wp_insert_post([
            'post_type'    => 'page',
            'post_title'   => 'Realitný Panel',
            'post_name'    => 'realitny-panel',
            'post_content' => '[realitny_panel]',
            'post_status'  => 'publish',
        ]);
        // Flush rewrite rules so URL works immediately
        flush_rewrite_rules(false);
    }
}

add_shortcode('realitny_panel', function() {
    if (!is_user_logged_in()) return panel_login_page();
    if (!current_user_can('edit_posts')) return '<p style="text-align:center;padding:40px;color:#e74c3c">Nemáš prístup.</p>';
    wp_enqueue_media();
    wp_enqueue_editor(); // native TinyMCE for panel forms
    return panel_dashboard();
});

function panel_login_page() {
    $url = wp_login_url(get_permalink(get_page_by_path('realitny-panel')));
    return '
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F5F1EA;padding:20px;font-family:-apple-system,BlinkMacSystemFont,\'DM Sans\',sans-serif">
        <div style="background:#fff;padding:48px 40px;border-radius:20px;max-width:400px;width:100%;box-shadow:0 12px 48px rgba(60,50,30,.12);border:1px solid #E2DACE;text-align:center">
            <div style="width:64px;height:64px;background:#F5F1EA;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 24px">🏠</div>
            <h1 style="font-family:\'Playfair Display\',Georgia,serif;font-size:22px;margin-bottom:8px;color:#1C1A18">Realitný Panel</h1>
            <p style="color:#6B6560;font-size:14px;margin-bottom:32px">Správa nehnuteľností</p>
            <a href="'.esc_url($url).'" style="display:block;padding:14px;background:#B8A47A;color:#1C1A18;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px;letter-spacing:.8px;text-transform:uppercase;transition:all .2s">Prihlásiť sa</a>
        </div>
    </div>';
}

function panel_dashboard() {
    $action = sanitize_text_field($_GET['action'] ?? 'list');
    $pid    = intval($_GET['id'] ?? 0);
    $user   = wp_get_current_user();
    $logout = wp_logout_url(home_url());
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
    --bg:#FCFBF8; --section:#F5F1EA; --accent:#B8A47A; --accent-dk:#9A8660; --accent-txt:#7C5E33;
    --text:#2C2C2C; --muted:#6B6560; --white:#fff; --border:#E2DACE; --dark:#1C1A18;
    --serif:'Playfair Display',Georgia,serif; --sans:'DM Sans',system-ui,sans-serif;
    --r:12px; --r-sm:8px; --sh:0 2px 12px rgba(60,50,30,.08);
}
body{font-family:var(--sans);background:var(--bg);color:var(--text);min-height:100vh}

/* HEADER */
.ph{background:var(--white);border-bottom:1px solid var(--border);padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 12px rgba(60,50,30,.06)}
.ph-logo{font-family:var(--serif);font-size:17px;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:10px}
.ph-logo-icon{width:36px;height:36px;background:var(--section);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px}
.ph-logo small{font-family:var(--sans);font-size:10px;font-weight:400;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent-txt);display:block}
.ph-nav{display:flex;gap:6px}
.ph-nav a{padding:8px 16px;border-radius:var(--r-sm);font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;text-decoration:none;color:var(--muted);transition:all .2s}
.ph-nav a:hover{background:var(--section);color:var(--dark)}
.ph-nav a.active{background:var(--accent);color:var(--dark)}
.ph-right{display:flex;align-items:center;gap:12px}
.ph-user{font-size:13px;color:var(--muted)}
.ph-logout{padding:8px 16px;background:var(--section);border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:12px;font-weight:600;cursor:pointer;color:var(--text);font-family:var(--sans);transition:all .2s}
.ph-logout:hover{background:#e8e0d5;border-color:#ccc}

/* CONTENT */
.pc{max-width:1200px;margin:0 auto;padding:32px 24px}

/* PROPERTY LIST */
.prop-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.prop-item{background:var(--white);border-radius:var(--r);overflow:hidden;border:1px solid var(--border);box-shadow:var(--sh);transition:all .2s}
.prop-item:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(60,50,30,.1);border-color:transparent}
.prop-item-img{height:170px;background:var(--section);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:28px;overflow:hidden;position:relative}
.prop-item-img img{width:100%;height:100%;object-fit:cover}
.prop-item-badge{position:absolute;bottom:10px;left:10px;background:var(--accent);color:var(--dark);font-size:9px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:4px 10px;border-radius:50px}
.prop-item-body{padding:14px 16px}
.prop-item-title{font-family:var(--serif);font-size:14px;font-weight:700;color:var(--dark);margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prop-item-price{font-size:16px;font-weight:800;color:var(--accent-txt);font-family:var(--serif);margin-bottom:10px}
.prop-item-actions{display:flex;gap:6px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 14px;border-radius:var(--r-sm);font-size:11px;font-weight:700;border:none;cursor:pointer;text-decoration:none;font-family:var(--sans);letter-spacing:.3px;transition:all .2s}
.btn-primary{background:var(--accent);color:var(--dark)}
.btn-primary:hover{background:var(--accent-dk)}
.btn-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.btn-danger:hover{background:#fee2e2}
.btn-ghost{background:var(--section);color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{background:#e8e0d5;color:var(--dark)}
.btn-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.btn-success:hover{background:#dcfce7}

/* EMPTY STATE */
.empty{text-align:center;padding:80px 20px;color:var(--muted)}
.empty-icon{font-size:52px;margin-bottom:16px;opacity:.4}

/* FORM */
.pf{background:var(--white);border-radius:var(--r);border:1px solid var(--border);box-shadow:var(--sh);overflow:hidden;max-width:900px}
.pf-head{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--section)}
.pf-head h2{font-family:var(--serif);font-size:18px;color:var(--dark)}
.pf-tabs{display:flex;background:var(--white);border-bottom:1px solid var(--border)}
.pf-tab{padding:12px 20px;border:none;background:none;font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);cursor:pointer;position:relative;font-family:var(--sans);transition:color .2s}
.pf-tab.active{color:var(--accent-txt)}
.pf-tab.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:var(--accent)}
.pf-body{padding:28px}
.pf-panel{display:none}.pf-panel.active{display:block}
.ff{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.ff label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.ff input,.ff select,.ff textarea{padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;color:var(--text);background:var(--white);transition:border .2s;width:100%}
.ff input:focus,.ff select:focus,.ff textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(184,164,122,.12)}
.ff textarea{min-height:90px;resize:vertical}
.ff-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ff-row-3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.pf-sep{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);padding:10px 0 8px;border-bottom:1px solid var(--border);margin:8px 0 16px}
.pf-footer{padding:18px 28px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;background:var(--section)}

/* GALLERY PREVIEW */
.gp{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;margin-bottom:10px}
.gp-thumb{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;background:var(--section)}
.gp-thumb img{width:100%;height:100%;object-fit:cover}
.gp-rm{position:absolute;top:2px;right:2px;width:20px;height:20px;background:#dc2626;color:#fff;border:none;border-radius:50%;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1}

/* AMENITIES */
.am-cat{margin-bottom:20px}
.am-cat-hd{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:10px;padding:6px 10px;background:var(--section);border-radius:6px;display:inline-block}
.am-items{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:6px}
.am-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:7px;cursor:pointer;font-size:13px;color:var(--text);transition:background .15s}
.am-item:hover{background:var(--section)}
.am-item input{width:15px;height:15px;accent-color:var(--accent);cursor:pointer;flex-shrink:0}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;background:var(--dark);color:#fff;padding:14px 20px;border-radius:var(--r);font-size:13px;font-weight:600;z-index:9999;transform:translateY(60px);opacity:0;transition:all .3s;box-shadow:0 4px 20px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px}
.toast.show{transform:translateY(0);opacity:1}

.pnl-rev-grid{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
@media(max-width:900px){
    .pnl-rev-grid{grid-template-columns:1fr !important}
    .pnl-rev-grid > div:last-child{position:static !important}
}
@media(max-width:600px){
    .pnl-nl-stats-grid{grid-template-columns:1fr !important}
    .ff-row-3{grid-template-columns:1fr !important}
}
/* TinyMCE */
.wp-editor-wrap{border-radius:var(--r-sm);overflow:hidden;max-width:100%}
.mce-toolbar .mce-btn button{padding:6px 8px !important}
.mce-tinymce{max-width:100% !important}

.pnl-rev-item{display:flex;gap:14px;align-items:flex-start}

/* ═══ MOBILE (≤768px) ═══════════════════════════════════════ */
@media(max-width:768px){

    /* --- TinyMCE --- */
    .wp-editor-wrap .mce-toolbar-grp{overflow-x:auto !important;-webkit-overflow-scrolling:touch}
    .mce-toolbar .mce-btn button{padding:8px 10px !important}
    .mce-toolbar-grp .mce-flow-layout{flex-wrap:nowrap !important}
    .wp-editor-tabs{display:flex}
    .wp-switch-editor{font-size:12px !important;padding:4px 10px !important}
    textarea.wp-editor-area{font-size:16px !important} /* prevents iOS zoom-in on focus */

    /* --- Header — wraps to 2 rows: [logo][right] then [nav] --- */
    .ph{
        flex-wrap:wrap; height:auto; min-height:56px;
        padding:10px 14px; row-gap:8px;
    }
    .ph-logo{font-size:14px;gap:8px}
    .ph-logo-icon{width:30px;height:30px;font-size:15px}
    .ph-logo small{font-size:9px}
    .ph-right{gap:8px}
    .ph-user{display:none}
    .ph-logout{padding:7px 12px;font-size:11px}
    .ph-nav{
        order:3; flex-basis:100%; width:100%;
        overflow-x:auto; -webkit-overflow-scrolling:touch;
        gap:4px; scrollbar-width:none;
        padding-top:8px; border-top:1px solid var(--border);
    }
    .ph-nav::-webkit-scrollbar{display:none}
    .ph-nav a{white-space:nowrap;flex-shrink:0;padding:7px 12px;font-size:11px}

    /* --- Content spacing --- */
    .pc{padding:16px 12px}

    /* --- Property list --- */
    .prop-list{grid-template-columns:1fr}
    .prop-item{display:flex;gap:12px}
    .prop-item-img{width:100px;height:80px !important;flex-shrink:0;border-radius:var(--r-sm) 0 0 var(--r-sm) !important}
    .prop-item-body{padding:10px 12px;min-width:0}
    .prop-item-actions{flex-wrap:wrap}

    /* --- Property form --- */
    .pf-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap !important;scrollbar-width:none}
    .pf-tabs::-webkit-scrollbar{display:none}
    .pf-tab{white-space:nowrap;flex-shrink:0;padding:11px 14px;font-size:11px}
    .pf-head{padding:16px;flex-wrap:wrap;gap:10px}
    .pf-body{padding:16px}
    .pf-panel{padding:0 !important}
    .pf-footer{padding:14px 16px;flex-direction:column;gap:8px}
    .pf-footer .btn{width:100%;justify-content:center;text-align:center}
    .ff-row,.ff-row-3{grid-template-columns:1fr}

    /* --- Gallery preview --- */
    .gp{grid-template-columns:repeat(3,1fr) !important}

    /* --- Amenities --- */
    .am-items{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}

    /* --- Newsletter / Reviews grids --- */
    .pnl-nl-stats-grid{grid-template-columns:1fr !important}
    .pnl-nl-subtabs{overflow-x:auto;-webkit-overflow-scrolling:touch;flex-wrap:nowrap !important;scrollbar-width:none}
    .pnl-nl-subtabs::-webkit-scrollbar{display:none}
    .pnl-nl-subtabs a{white-space:nowrap;flex-shrink:0}
    .pnl-rev-item{flex-direction:column}
    .pnl-rev-item > div:last-child{flex-direction:row;align-items:center;width:100%;flex-wrap:wrap}

    /* --- Forms & inputs (16px prevents iOS auto-zoom) --- */
    input,textarea,select{font-size:16px !important}

    /* --- Toast --- */
    .toast{left:16px;right:16px;bottom:16px;text-align:center;justify-content:center}
}

@media(max-width:420px){
    .ph-logo small{display:none}
    .am-items{grid-template-columns:1fr}
    .gp{grid-template-columns:repeat(2,1fr) !important}
}
</style>

<div>
<div class="ph">
    <div class="ph-logo">
        <div class="ph-logo-icon">🏠</div>
        <div>Realitný Panel<small>Správa nehnuteľností</small></div>
    </div>
    <nav class="ph-nav">
        <a href="?action=list" class="<?php echo $action==='list'?'active':'' ?>">Ponuky</a>
        <a href="?action=add" class="<?php echo ($action==='add'||$action==='edit')?'active':'' ?>">+ Nová ponuka</a>
        <?php if (function_exists('zcn_table')): ?>
        <a href="?action=newsletter" class="<?php echo $action==='newsletter'?'active':'' ?>">📧 Newsletter</a>
        <?php endif; ?>
        <?php if (function_exists('zcr_table')): ?>
        <a href="?action=reviews" class="<?php echo $action==='reviews'?'active':'' ?>">⭐ Recenzie</a>
        <?php endif; ?>
    </nav>
    <div class="ph-right">
        <span class="ph-user">👤 <?php echo esc_html($user->display_name) ?></span>
        <button class="ph-logout" onclick="window.location='<?php echo esc_url($logout) ?>'">Odhlásiť</button>
    </div>
</div>

<div class="pc">
    <?php if(isset($_GET['saved'])): $saved_pid = intval($_GET['pid'] ?? 0); ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:12px 16px;border-radius:var(--r-sm);margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <span>✅ Nehnuteľnosť uložená!</span>
        <?php if ($saved_pid && function_exists('zcn_handle_property_blast')): ?>
        <button class="btn btn-primary" onclick="pnlBlast(<?php echo $saved_pid ?>, this)">📧 Poslať odberateľom newslettera</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php
    if ($action==='add'||$action==='edit') panel_form($pid);
    elseif ($action==='newsletter' && function_exists('zcn_table')) panel_newsletter();
    elseif ($action==='reviews'    && function_exists('zcr_table'))  panel_reviews();
    else panel_list();
    ?>
</div>
<div class="toast" id="toast"></div>
</div>

<script>
function showTab(name,btn){
    document.querySelectorAll('.pf-panel').forEach(function(p){p.classList.remove('active')});
    document.querySelectorAll('.pf-tab').forEach(function(t){t.classList.remove('active')});
    document.getElementById('pftab_'+name).classList.add('active');
    btn.classList.add('active');
}
function toast(msg,ok){
    var t=document.getElementById('toast');
    t.innerHTML=(ok?'✅ ':'⚠️ ')+msg;
    t.classList.add('show');
    setTimeout(function(){t.classList.remove('show')},3000);
}
function selCover(){
    var f=wp.media({title:'Cover foto',button:{text:'Nastav'},multiple:false});
    f.on('select',function(){
        var a=f.state().get('selection').first().toJSON();
        document.getElementById('cv').value=a.id;
        var t=a.sizes.thumbnail||a.sizes.full;
        document.getElementById('cv-pre').innerHTML='<div class="gp-thumb"><img src="'+t.url+'"><button type="button" class="gp-rm" onclick="rmCv()">✕</button></div>';
    });f.open();
}
function rmCv(){document.getElementById('cv').value='';document.getElementById('cv-pre').innerHTML=''}
function selGal(){
    var f=wp.media({title:'Galéria',button:{text:'Pridaj'},multiple:true});
    f.on('select',function(){
        var g=JSON.parse(document.getElementById('gal').value||'[]');
        var p=document.getElementById('gal-pre');
        f.state().get('selection').forEach(function(a){
            a=a.toJSON();
            if(!g.includes(a.id)){
                g.push(a.id);
                var t=a.sizes.thumbnail||a.sizes.full;
                p.innerHTML+='<div class="gp-thumb" data-id="'+a.id+'"><img src="'+t.url+'"><button type="button" class="gp-rm" onclick="rmGal(this)">✕</button></div>';
            }
        });
        document.getElementById('gal').value=JSON.stringify(g);
    });f.open();
}
function rmGal(btn){
    var id=btn.parentElement.dataset.id;
    var g=JSON.parse(document.getElementById('gal').value);
    g=g.filter(function(x){return x!=id});
    document.getElementById('gal').value=JSON.stringify(g);
    btn.parentElement.remove();
}
function delProp(id, nonce){
    if(confirm('Naozaj vymazať túto nehnuteľnosť?'))window.location='?action=delete&id='+id+'&_wpnonce='+nonce;
}
<?php if (function_exists('zcn_handle_property_blast')): ?>
function pnlBlast(id, btn){
    if(!confirm('Poslať túto ponuku e-mailom všetkým odberateľom newslettera?'))return;
    btn.disabled=true;var orig=btn.innerHTML;btn.innerHTML='Odosielam…';
    var data=new FormData();
    data.append('action','zcn_send_property');
    data.append('nonce','<?php echo wp_create_nonce('zcn_send_nonce') ?>');
    data.append('property_id',id);
    fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
    .then(function(r){return r.json()}).then(function(res){
        btn.disabled=false;btn.innerHTML=res.success?'📧 ✓':orig;
        toast((res.data&&res.data.message)||(res.success?'Odoslané':'Chyba'),res.success);
    }).catch(function(){btn.disabled=false;btn.innerHTML=orig;toast('Chyba pripojenia',false)});
}
<?php endif; ?>
</script>
<?php
    return ob_get_clean();
}

function panel_list() {
    $props = get_posts(['post_type'=>'property','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']);
    $typ_labels = ['predaj'=>'Na predaj','prenajom'=>'Na prenájom','pozemok'=>'Pozemok'];
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:14px">
        <div>
            <h1 style="font-family:var(--serif);font-size:22px;color:var(--dark)">Nehnuteľnosti</h1>
            <p style="font-size:13px;color:var(--muted);margin-top:3px"><?php echo count($props) ?> ponúk celkovo</p>
        </div>
        <a href="?action=add" class="btn btn-primary" style="padding:10px 22px;font-size:12px">+ Nová ponuka</a>
    </div>
    <?php if (empty($props)): ?>
    <div class="empty">
        <div class="empty-icon">🏡</div>
        <p style="font-size:16px;font-weight:600;margin-bottom:8px;color:var(--dark)">Žiadne ponuky</p>
        <p style="font-size:14px;margin-bottom:24px">Pridajte prvú nehnuteľnosť</p>
        <a href="?action=add" class="btn btn-primary" style="padding:12px 28px">Pridať ponuku</a>
    </div>
    <?php else: ?>
    <div class="prop-list">
    <?php foreach ($props as $p):
        $typ  = get_post_meta($p->ID,'_property_typ',true);
        $cena = get_post_meta($p->ID,'_property_cena',true);
        $cid  = get_post_meta($p->ID,'_property_cover_id',true);
        if ($cena && strpos($cena,'€')===false) $cena .= ' €';
        ?>
        <div class="prop-item">
            <div class="prop-item-img">
                <?php if($cid) echo wp_get_attachment_image($cid,'medium'); else echo '🏠'; ?>
                <?php if($typ): ?><span class="prop-item-badge"><?php echo $typ_labels[$typ]??$typ ?></span><?php endif; ?>
            </div>
            <div class="prop-item-body">
                <div class="prop-item-title"><?php echo esc_html($p->post_title) ?></div>
                <div class="prop-item-price"><?php echo esc_html($cena) ?></div>
                <div class="prop-item-actions">
                    <a href="?action=edit&id=<?php echo $p->ID ?>" class="btn btn-primary">Upraviť</a>
                    <a href="<?php echo get_permalink($p->ID) ?>" target="_blank" class="btn btn-ghost">Zobraziť</a>
                    <?php if (function_exists('zcn_handle_property_blast')):
                        $blast_sent = get_post_meta($p->ID, '_zcn_blast_sent', true); ?>
                    <button class="btn btn-success" onclick="pnlBlast(<?php echo $p->ID ?>, this)"
                        title="<?php echo $blast_sent ? 'Odoslané '.esc_attr(date('d.m.Y H:i', strtotime($blast_sent))).' — kliknutím pošleš znova' : 'Poslať ponuku odberateľom newslettera' ?>">
                        📧<?php echo $blast_sent ? ' ✓' : '' ?>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-danger" onclick="delProp(<?php echo $p->ID ?>, '<?php echo wp_create_nonce('panel_delete_'.$p->ID) ?>')">Zmazať</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif;
}

function panel_form($pid) {
    $f = function($k) use ($pid) { return $pid ? get_post_meta($pid,'_property_'.$k,true) : ''; };
    $post = $pid ? get_post($pid) : null;
    $ams_sel = $pid ? (get_post_meta($pid,'_property_amenities',true)?:[]) : [];
    $cover_id = $f('cover_id');
    $gallery_ids = $pid ? (get_post_meta($pid,'_property_gallery_ids',true)?:[]) : [];
    $all_am = get_property_amenities();
    ?>
    <div class="pf">
        <div class="pf-head">
            <h2><?php echo $pid?'Upraviť nehnuteľnosť':'Nová nehnuteľnosť' ?></h2>
            <a href="?action=list" class="btn btn-ghost">← Späť</a>
        </div>
        <div class="pf-tabs">
            <button class="pf-tab active" onclick="showTab('basic',this)">Základné</button>
            <button class="pf-tab" onclick="showTab('photos',this)">Fotky & Video</button>
            <button class="pf-tab" onclick="showTab('details',this)">Detaily</button>
            <button class="pf-tab" onclick="showTab('amenities',this)">Vybavenie</button>
        </div>
        <form method="post">
        <?php wp_nonce_field('panel_save') ?>
        <div class="pf-body">

        <div id="pftab_basic" class="pf-panel active">
            <div class="ff"><label>Názov nehnuteľnosti *</label><input type="text" name="title" value="<?php echo esc_attr($post ? $post->post_title : '') ?>" required></div>
            <div class="ff-row">
                <div class="ff"><label>Typ ponuky *</label>
                    <select name="typ" required>
                        <option value="">Vyber...</option>
                        <option value="predaj" <?php selected($f('typ'),'predaj') ?>>Na predaj</option>
                        <option value="prenajom" <?php selected($f('typ'),'prenajom') ?>>Na prenájom</option>
                        <option value="pozemok" <?php selected($f('typ'),'pozemok') ?>>Pozemok</option>
                    </select>
                </div>
                <div class="ff"><label>Cena (bez €, doplní sa auto)</label><input type="text" name="cena" value="<?php echo esc_attr(str_replace(' €','',$f('cena'))) ?>" placeholder="184 900"></div>
            </div>
            <div class="ff-row-3">
                <div class="ff"><label>Lokalita / Ulica</label><input type="text" name="lokalita" value="<?php echo esc_attr($f('lokalita')) ?>" placeholder="Sokolská, Zvolen"></div>
                <div class="ff"><label>Mesto</label><input type="text" name="mesto" value="<?php echo esc_attr($f('mesto')) ?>" placeholder="Zvolen"></div>
                <div class="ff"><label>Okres</label><input type="text" name="okres" value="<?php echo esc_attr($f('okres')) ?>" placeholder="Zvolen"></div>
            </div>
            <div class="ff"><label>Krátky popis (na karte)</label><textarea name="popis_kratky" rows="3" placeholder="Stručný popis..." style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;resize:vertical"><?php echo esc_textarea($f('popis_kratky')) ?></textarea></div>
            <div class="ff"><label>Detailný popis</label>
            <?php wp_editor($post ? $post->post_content : '', 'zcpp_content', [
                'textarea_name' => 'content',
                'textarea_rows' => 10,
                'media_buttons' => false,
                'teeny'         => false,
                'quicktags'     => true,
                'tinymce'       => [
                    'toolbar1' => 'undo,redo,formatselect,|,bold,italic,underline,strikethrough,forecolor,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,outdent,indent,|,link,unlink,table,|,removeformat',
                    'toolbar2' => '',
                    'block_formats' => 'Odsek=p;Nadpis 1=h1;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Nadpis 5=h5;Nadpis 6=h6;Predformátované=pre',
                    'content_style' => 'body{font-family:DM Sans,sans-serif;font-size:15px;line-height:1.8;color:#2C2825;padding:12px}',
                ],
            ]); ?>
            </div>
        </div>

        <div id="pftab_photos" class="pf-panel">
            <div class="pf-sep">Cover foto</div>
            <div id="cv-pre" class="gp">
                <?php if($cover_id&&$img=wp_get_attachment_image_src($cover_id,'thumbnail')): ?>
                <div class="gp-thumb"><img src="<?php echo $img[0] ?>"><button type="button" class="gp-rm" onclick="rmCv()">✕</button></div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-primary" onclick="selCover()" style="margin-bottom:24px">Vyber cover foto</button>
            <input type="hidden" name="cover_id" id="cv" value="<?php echo esc_attr($cover_id) ?>">

            <div class="pf-sep">Galéria fotos</div>
            <div id="gal-pre" class="gp">
                <?php foreach($gallery_ids as $gid): if($img=wp_get_attachment_image_src($gid,'thumbnail')): ?>
                <div class="gp-thumb" data-id="<?php echo $gid ?>"><img src="<?php echo $img[0] ?>"><button type="button" class="gp-rm" onclick="rmGal(this)">✕</button></div>
                <?php endif; endforeach; ?>
            </div>
            <button type="button" class="btn btn-success" onclick="selGal()" style="margin-bottom:24px">Pridaj fotky</button>
            <input type="hidden" name="gallery_ids" id="gal" value="<?php echo esc_attr(json_encode($gallery_ids)) ?>">

            <div class="pf-sep">Video</div>
            <div class="ff"><label>YouTube / Vimeo URL</label><input type="text" name="video_url" value="<?php echo esc_attr($f('video_url')) ?>" placeholder="https://www.youtube.com/watch?v=..."></div>
        </div>

        <div id="pftab_details" class="pf-panel">
            <div class="ff-row-3">
                <?php foreach(['plocha'=>'Plocha (m²)','pozemok'=>'Pozemok (m²)','spalne'=>'Počet izieb','kupelne'=>'Kúpeľne','wc'=>'WC','poschodie'=>'Poschodie','rocnik'=>'Ročník'] as $k=>$l): ?>
                <div class="ff"><label><?php echo $l ?></label><input type="text" name="<?php echo $k ?>" value="<?php echo esc_attr($f($k)) ?>"></div>
                <?php endforeach; ?>
            </div>
            <div class="ff-row">
                <div class="ff"><label>Stav</label>
                    <select name="stav">
                        <option value="">Vyber</option>
                        <?php foreach(['Novostavba','Veľmi dobrý','Dobrý','Vyhovujúci','Rekonštrukcia potrebná'] as $s): ?>
                        <option <?php selected($f('stav'),$s) ?>><?php echo $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ff"><label>Vlastníctvo</label>
                    <select name="vlastnictvo">
                        <option value="">Vyber</option>
                        <?php foreach(['Osobné','Družstevné','Štátne'] as $v): ?>
                        <option <?php selected($f('vlastnictvo'),$v) ?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div id="pftab_amenities" class="pf-panel">
            <?php foreach ($all_am as $catkey => $cat): ?>
            <div class="am-cat">
                <div class="am-cat-hd"><?php echo $cat['label'] ?></div>
                <div class="am-items" <?php echo $catkey==='custom' ? 'id="ppCustomItems"' : '' ?>>
                    <?php foreach ($cat['items'] as $k => $lbl): ?>
                    <label class="am-item" <?php echo $catkey==='custom' ? 'data-key="'.esc_attr($k).'"' : '' ?>>
                        <input type="checkbox" name="amenities[]" value="<?php echo esc_attr($k) ?>" <?php checked(in_array($k,$ams_sel)) ?>>
                        <?php echo esc_html($lbl) ?>
                        <?php if ($catkey==='custom'): ?>
                        <button type="button" class="pp-am-del" onclick="ppDelAm('<?php echo esc_js($k) ?>',this)" title="Zmazať položku"
                            style="margin-left:auto;width:20px;height:20px;border:none;border-radius:50%;background:#fef2f2;color:#dc2626;font-size:11px;cursor:pointer;line-height:1;flex-shrink:0">✕</button>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Správa vlastného vybavenia -->
            <div class="am-cat" style="background:var(--section);border-radius:var(--r-sm);padding:16px">
                <div class="am-cat-hd" style="background:var(--white)">⭐ Pridať vlastné vybavenie</div>
                <?php if (empty($all_am['custom'])): ?>
                <div class="am-items" id="ppCustomItems" style="margin-bottom:10px"></div>
                <?php endif; ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px">
                    <input type="text" id="ppNewAm" placeholder="Napr. Vínna pivnica" maxlength="60"
                        style="flex:1;min-width:200px;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();ppAddAm();}">
                    <button type="button" class="btn btn-primary" onclick="ppAddAm()">+ Pridať</button>
                </div>
                <p style="font-size:11px;color:var(--muted);margin:8px 0 0">Vlastné položky sa uložia natrvalo a budú dostupné pri všetkých nehnuteľnostiach. Mazať ich môžeš krížikom pri položke.</p>
            </div>

            <script>
            var ppAmNonce = '<?php echo wp_create_nonce('pp_amenity') ?>';
            function ppAddAm(){
                var inp = document.getElementById('ppNewAm');
                var label = inp.value.trim();
                if (!label) { toast('Zadaj názov položky', false); return; }
                var data = new FormData();
                data.append('action','pp_amenity_add');
                data.append('nonce', ppAmNonce);
                data.append('label', label);
                fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
                .then(function(r){return r.json()}).then(function(res){
                    if (!res.success) { toast((res.data&&res.data.message)||'Chyba', false); return; }
                    var wrap = document.getElementById('ppCustomItems');
                    var l = document.createElement('label');
                    l.className = 'am-item';
                    l.dataset.key = res.data.key;
                    l.innerHTML = '<input type="checkbox" name="amenities[]" value="' + res.data.key + '" checked> ' +
                        res.data.label.replace(/</g,'&lt;') +
                        '<button type="button" class="pp-am-del" onclick="ppDelAm(\'' + res.data.key + '\',this)" title="Zmazať položku" style="margin-left:auto;width:20px;height:20px;border:none;border-radius:50%;background:#fef2f2;color:#dc2626;font-size:11px;cursor:pointer;line-height:1;flex-shrink:0">✕</button>';
                    wrap.appendChild(l);
                    inp.value = '';
                    toast('Položka pridaná (označená pre túto ponuku)', true);
                });
            }
            function ppDelAm(key, btn){
                if (!confirm('Natrvalo zmazať túto položku vybavenia? Zmizne zo zoznamu pre všetky ponuky.')) return;
                var data = new FormData();
                data.append('action','pp_amenity_del');
                data.append('nonce', ppAmNonce);
                data.append('key', key);
                fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
                .then(function(r){return r.json()}).then(function(res){
                    if (!res.success) { toast((res.data&&res.data.message)||'Chyba', false); return; }
                    var item = btn.closest('.am-item');
                    if (item) item.remove();
                    toast('Položka zmazaná', true);
                });
            }
            </script>
        </div>

        </div>
        <div class="pf-footer">
            <a href="?action=list" class="btn btn-ghost">Zrušiť</a>
            <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:13px"><?php echo $pid?'💾 Uložiť zmeny':'✅ Vytvoriť ponuku' ?></button>
        </div>
        </form>
    </div>
    <?php
}

// SAVE
add_action('template_redirect', function() {
    if (!is_page('realitny-panel')||!is_user_logged_in()) return;
    // SECURITY: capability check for ALL panel write operations
    if (!current_user_can('edit_posts')) return;

    if (($_GET['action']??'')==='delete'&&isset($_GET['id'])) {
        // SECURITY: nonce required for delete + only property post type + must own or be editor
        if (!wp_verify_nonce($_GET['_wpnonce']??'', 'panel_delete_'.intval($_GET['id']))) {
            wp_die('Neplatný bezpečnostný token.');
        }
        $del_id = intval($_GET['id']);
        $post   = get_post($del_id);
        if (!$post || $post->post_type !== 'property') {
            wp_die('Neplatná požiadavka.');
        }
        if (!current_user_can('delete_post', $del_id)) {
            wp_die('Nemáte oprávnenie vymazať túto ponuku.');
        }
        wp_delete_post($del_id, true);
        wp_redirect(get_permalink(get_page_by_path('realitny-panel')).'?action=list');exit;
    }
    if (!isset($_POST['title'])||!wp_verify_nonce($_POST['_wpnonce']??'','panel_save')) return;
    $pid = intval($_GET['id']??0);
    $data = ['post_title'=>sanitize_text_field($_POST['title']),'post_content'=>wp_kses_post($_POST['content']??''),'post_type'=>'property','post_status'=>'publish'];
    if ($pid){$data['ID']=$pid;wp_update_post($data);}else{$pid=wp_insert_post($data);}
    foreach(['typ','cena','lokalita','mesto','okres','popis_kratky','plocha','pozemok','spalne','kupelne','wc','poschodie','rocnik','stav','vlastnictvo'] as $f) {
        if (isset($_POST[$f])) {
            $val = sanitize_text_field($_POST[$f]);
            if ($f==='cena' && $val && strpos($val,'€')===false) $val = $val.' €';
            update_post_meta($pid,'_property_'.$f,$val);
        }
    }
    update_post_meta($pid,'_property_cover_id',intval($_POST['cover_id']??0));
    $gids=json_decode(sanitize_text_field($_POST['gallery_ids']??'[]'),true);
    update_post_meta($pid,'_property_gallery_ids',is_array($gids)?array_map('intval',$gids):[]);
    update_post_meta($pid,'_property_video_url',esc_url_raw($_POST['video_url']??''));
    $ams=isset($_POST['amenities'])?array_map('sanitize_text_field',$_POST['amenities']):[];
    update_post_meta($pid,'_property_amenities',$ams);
    wp_redirect(get_permalink(get_page_by_path('realitny-panel')).'?action=list&saved=1&pid='.$pid);exit;
});

// ── Newsletter Panel ───────────────────────────────────────────────────────
function panel_newsletter() {
    if (!function_exists('zcn_table')) {
        echo '<div style="padding:40px;text-align:center;color:#e74c3c">Plugin ZC Newsletter nie je nainštalovaný.</div>';
        return;
    }
    global $wpdb;
    $table = zcn_table();
    $subtab = sanitize_text_field($_GET['sub'] ?? 'send');

    // Handle delete/unsub from panel
    if (isset($_POST['pnl_nsub']) && wp_verify_nonce($_POST['_pnlnonce'],'pnl_nl')) {
        $wpdb->update($table, ['status'=>'unsubscribed'], ['id'=>intval($_POST['pnl_nsub'])]);
    }
    if (isset($_POST['pnl_ndel']) && wp_verify_nonce($_POST['_pnlnonce'],'pnl_nl')) {
        $wpdb->delete($table, ['id'=>intval($_POST['pnl_ndel'])]);
    }

    $stats = [
        'active'  => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active'"),
        'pending' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='pending'"),
        'month'   => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status='active' AND subscribed_at>=DATE_FORMAT(NOW(),'%Y-%m-01')"),
    ];
    ?>

    <!-- Stats -->
    <div class="pnl-nl-stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">
        <?php foreach([
            ['📧 Aktívni odberatelia', $stats['active'],  '#B8A47A'],
            ['🆕 Tento mesiac',        $stats['month'],   '#22c55e'],
            ['⏳ Čakajú na potvrd.',   $stats['pending'], '#f59e0b'],
        ] as [$l,$n,$c]): ?>
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px;text-align:center">
            <div style="font-size:26px;font-weight:800;color:<?php echo $c ?>;font-family:var(--serif)"><?php echo $n ?></div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px"><?php echo $l ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Sub-tabs -->
    <div class="pnl-nl-subtabs" style="display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:20px">
    <?php foreach(['send'=>'📤 Odoslať','subscribers'=>'👥 Odberatelia','log'=>'📋 História'] as $st=>$sl): ?>
    <a href="?action=newsletter&sub=<?php echo $st ?>"
       style="padding:9px 16px;text-decoration:none;font-size:12px;font-weight:700;letter-spacing:.4px;
              text-transform:uppercase;border-radius:8px 8px 0 0;margin-bottom:-2px;
              border:1px solid <?php echo $subtab===$st?'var(--border)':'transparent' ?>;
              border-bottom:<?php echo $subtab===$st?'2px solid var(--white)':'none' ?>;
              background:<?php echo $subtab===$st?'var(--white)':'transparent' ?>;
              color:<?php echo $subtab===$st?'var(--accent-txt)':'var(--muted)' ?>">
        <?php echo $sl ?>
    </a>
    <?php endforeach; ?>
    </div>

    <?php if ($subtab === 'send'): ?>
    <!-- SEND -->
    <div style="max-width:720px">
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:28px">
            <div id="pnlNlMsg" style="display:none;margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:14px"></div>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Predmet *</label>
                <input type="text" id="pnlSubject"
                    style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;color:var(--text);outline:none;transition:border .2s"
                    placeholder="Nová ponuka — 3-izbový byt Banská Bystrica">
            </div>
            <?php if (function_exists('zcn_render_tpl_toolbar')) zcn_render_tpl_toolbar('pnlBody', 'pnlSubject'); ?>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Obsah *</label>
                <?php wp_editor('', 'pnlBody', [
                    'textarea_name' => 'pnl_body',
                    'textarea_rows' => 12,
                    'media_buttons' => false,
                    'quicktags'     => true,
                    'tinymce'       => [
                        'toolbar1' => 'undo,redo,formatselect,|,bold,italic,underline,strikethrough,forecolor,|,alignleft,aligncenter,alignright,|,bullist,numlist,|,outdent,indent,|,link,unlink,image,table,|,removeformat',
                        'toolbar2' => '',
                        'block_formats' => 'Odsek=p;Nadpis 1=h1;Nadpis 2=h2;Nadpis 3=h3;Nadpis 4=h4;Nadpis 5=h5;Nadpis 6=h6;Predformátované=pre',
                        'content_style' => 'body{font-family:DM Sans,sans-serif;font-size:15px;line-height:1.8;color:#2C2825;padding:12px}',
                    ],
                ]); ?>
            </div>
            <div style="background:var(--section);border-radius:var(--r-sm);padding:14px;margin-bottom:16px;display:flex;gap:8px;align-items:flex-end">
                <div style="flex:1">
                    <label style="display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Testovací e-mail</label>
                    <input type="email" id="pnlTestEmail"
                        style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px"
                        placeholder="vas@email.sk">
                </div>
                <button onclick="pnlNlSend(true)"
                    style="padding:9px 16px;background:var(--section);border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .2s">
                    📨 Test
                </button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                <div>
                    <span style="font-size:13px;color:var(--muted)">Odošle sa <strong style="color:var(--dark)"><?php echo $stats['active'] ?></strong> odberateľom</span>
                    <a href="?action=newsletter&sub=subscribers" style="font-size:12px;color:var(--accent-txt);margin-left:12px;text-decoration:none">zobraziť →</a>
                </div>
                <div style="display:flex;gap:8px">
                    <button onclick="pnlNlPreview()"
                        style="padding:11px 18px;background:var(--section);border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:12px;font-weight:700;cursor:pointer">
                        👁 Náhľad
                    </button>
                    <button onclick="pnlNlSend(false)" class="btn btn-primary" style="padding:11px 22px;font-size:12px">
                        📧 Odoslať všetkým →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview modal -->
    <div id="pnlNlModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;overflow:auto;padding:20px">
        <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.3)">
            <div style="padding:12px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#1C1A18">
                <strong style="color:#fff;font-size:14px">Náhľad e-mailu</strong>
                <button onclick="document.getElementById('pnlNlModal').style.display='none'"
                    style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;font-size:14px">✕</button>
            </div>
            <iframe id="pnlNlFrame" style="width:100%;height:580px;border:none"></iframe>
        </div>
    </div>

    <script>
    function pnlNlMsg(msg, ok) {
        var el = document.getElementById('pnlNlMsg');
        el.style.display = 'block';
        el.style.background = ok ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
        el.style.color = ok ? '#15803d' : '#dc2626';
        el.style.border = '1px solid ' + (ok ? 'rgba(34,197,94,.25)' : 'rgba(239,68,68,.25)');
        el.textContent = msg;
        el.scrollIntoView({behavior:'smooth',block:'nearest'});
    }
    function pnlNlGetBody() {
        // TinyMCE (visual mode) or plain textarea (text mode)
        if (window.tinymce && tinymce.get('pnlBody') && !tinymce.get('pnlBody').isHidden()) {
            return tinymce.get('pnlBody').getContent();
        }
        var ta = document.getElementById('pnlBody');
        return ta ? ta.value : '';
    }
    function pnlNlApi(extra) {
        var s = document.getElementById('pnlSubject').value.trim();
        var b = pnlNlGetBody().trim();
        if (!s||!b) { alert('Vyplňte predmet aj obsah.'); return null; }
        var data = new FormData();
        data.append('action','zcn_send_newsletter');
        data.append('nonce','<?php echo wp_create_nonce('zcn_send_nonce') ?>');
        data.append('subject',s); data.append('body',b);
        data.append('is_html','1');
        Object.keys(extra||{}).forEach(function(k){ data.append(k,extra[k]); });
        return data;
    }
    function pnlNlSend(isTest) {
        var data = pnlNlApi({});
        if (!data) return;
        var t = document.getElementById('pnlTestEmail').value.trim();
        if (isTest && !t) { alert('Zadajte testovací e-mail.'); return; }
        if (!isTest && !confirm('Odoslať newsletter <?php echo $stats['active'] ?> odberateľom?')) return;
        if (isTest && t) data.append('test_email', t);
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(r=>r.json()).then(res=>{ pnlNlMsg(res.data.message, res.success); });
    }
    function pnlNlPreview() {
        var data = pnlNlApi({preview:'1'});
        if (!data) return;
        fetch('<?php echo admin_url('admin-ajax.php') ?>',{method:'POST',body:data})
        .then(r=>r.json()).then(res=>{
            if (res.success) {
                document.getElementById('pnlNlModal').style.display='block';
                document.getElementById('pnlNlFrame').srcdoc = res.data.html;
            }
        });
    }
    </script>

    <?php elseif ($subtab === 'subscribers'):
        $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE status IN ('active','pending') ORDER BY subscribed_at DESC LIMIT 300");
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <span style="font-size:13px;color:var(--muted)"><strong style="color:var(--dark)"><?php echo $stats['active'] ?></strong> aktívnych · <strong style="color:var(--dark)"><?php echo $stats['pending'] ?></strong> čaká</span>
        <a href="<?php echo admin_url('admin.php?page=zc-newsletter&zcn_export=1') ?>" class="btn btn-ghost" style="font-size:12px;padding:8px 14px">⬇ Export CSV</a>
    </div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
    <?php if ($rows): ?>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--section);border-bottom:1px solid var(--border)">
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">E-mail</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Meno</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Status</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Dátum</th>
                <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Akcie</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $badge = $r->status === 'active'
                ? '<span style="background:#f0fdf4;color:#15803d;padding:2px 10px;border-radius:50px;font-size:10px;font-weight:700">Aktívny</span>'
                : '<span style="background:#fffbeb;color:#92400e;padding:2px 10px;border-radius:50px;font-size:10px;font-weight:700">Čaká</span>';
        ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:11px 16px;color:var(--dark)"><?php echo esc_html($r->email) ?></td>
            <td style="padding:11px 16px;color:var(--muted)"><?php echo esc_html($r->name ?: '—') ?></td>
            <td style="padding:11px 16px"><?php echo $badge ?></td>
            <td style="padding:11px 16px;color:var(--muted);font-size:12px"><?php echo date('d.m.Y', strtotime($r->subscribed_at)) ?></td>
            <td style="padding:11px 16px">
                <form method="post" style="display:inline" onsubmit="return confirm('Odhlásiť odberateľa?')">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_nsub" value="<?php echo $r->id ?>">
                    <button type="submit" style="padding:5px 10px;background:var(--section);border:1px solid var(--border);border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">Odhlásiť</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Natrvalo vymazať?')">
                    <?php wp_nonce_field('pnl_nl','_pnlnonce') ?>
                    <input type="hidden" name="pnl_ndel" value="<?php echo $r->id ?>">
                    <button type="submit" style="padding:5px 10px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">Vymazať</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:48px;text-align:center;color:var(--muted)">Zatiaľ žiadni odberatelia.</div>
    <?php endif; ?>
    </div>

    <?php elseif ($subtab === 'log'):
        $log = get_option('zcn_send_log', []);
    ?>
    <?php if ($log): ?>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:var(--section);border-bottom:1px solid var(--border)">
            <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Dátum</th>
            <th style="padding:10px 16px;text-align:left;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Predmet</th>
            <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Odoslané</th>
            <th style="padding:10px 16px;text-align:right;font-size:10px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted)">Zlyhalo</th>
        </tr></thead>
        <tbody>
        <?php foreach ($log as $l): ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:11px 16px;color:var(--muted);font-size:12px"><?php echo date('d.m.Y H:i', strtotime($l['date'])) ?></td>
            <td style="padding:11px 16px;color:var(--dark);font-weight:500"><?php echo esc_html($l['subject']) ?></td>
            <td style="padding:11px 16px;text-align:right;color:#15803d;font-weight:700"><?php echo $l['sent'] ?></td>
            <td style="padding:11px 16px;text-align:right;color:<?php echo $l['failed']>0?'#dc2626':'#94a3b8' ?>"><?php echo $l['failed'] ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div style="padding:48px;text-align:center;color:var(--muted)">Zatiaľ nebol odoslaný žiadny newsletter.</div>
    <?php endif; ?>
    <?php endif; ?>
    <?php
}

// ── Reviews Panel ─────────────────────────────────────────────────────────
function panel_reviews() {
    if (!function_exists('zcr_table')) {
        echo '<div style="padding:40px;text-align:center;color:#e74c3c">Plugin ZC Recenzie nie je nainštalovaný.</div>';
        return;
    }
    global $wpdb;
    $t = zcr_table();

    // Handle save
    if (isset($_POST['zcr_panel_save']) && wp_verify_nonce($_POST['_zcrnonce'],'zcr_panel')) {
        $id   = intval($_POST['zcr_id'] ?? 0);
        $data = [
            'author_name' => sanitize_text_field($_POST['author_name'] ?? ''),
            'author_role' => sanitize_text_field($_POST['author_role'] ?? ''),
            'body'        => sanitize_textarea_field($_POST['body'] ?? ''),
            'rating'      => max(1,min(5,intval($_POST['rating']??5))),
            'avatar_url'  => esc_url_raw($_POST['avatar_url'] ?? ''),
            'published'   => intval($_POST['published'] ?? 1),
        ];
        if ($id) {
            $wpdb->update($t, $data, ['id'=>$id]);
            $msg = '✅ Recenzia uložená!';
        } else {
            $data['sort_order'] = (int)$wpdb->get_var("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$t}");
            $wpdb->insert($t, $data);
            $msg = '✅ Recenzia pridaná!';
        }
    }
    if (isset($_POST['zcr_panel_delete']) && wp_verify_nonce($_POST['_zcrnonce'],'zcr_panel')) {
        $wpdb->delete($t, ['id'=>intval($_POST['zcr_id'])]);
        $msg = 'Recenzia vymazaná.';
    }

    $edit_id = intval($_GET['edit_review'] ?? 0);
    $edit_r  = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $edit_id)) : null;
    $rows    = $wpdb->get_results("SELECT * FROM {$t} ORDER BY sort_order ASC, id ASC");
    ?>

    <?php if (!empty($msg)): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px"><?php echo esc_html($msg) ?></div>
    <?php endif; ?>

    <div class="pnl-rev-grid" style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

        <!-- LIST -->
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h3 style="font-family:var(--serif);font-size:18px;margin:0">Recenzie (<?php echo count($rows) ?>)</h3>
                <a href="?action=reviews&edit_review=0" class="btn btn-primary" style="font-size:11px;padding:8px 16px">+ Nová recenzia</a>
            </div>
            <?php if ($rows): ?>
            <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach ($rows as $r): ?>
            <div class="pnl-rev-item" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:16px 18px">
                <div style="flex:1;min-width:0">
                    <div style="font-family:var(--serif);font-weight:700;font-size:15px;color:var(--dark);margin-bottom:2px"><?php echo esc_html($r->author_name) ?></div>
                    <?php if ($r->author_role): ?><div style="font-size:11px;color:var(--accent-txt);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px"><?php echo esc_html($r->author_role) ?></div><?php endif; ?>
                    <div style="font-size:13px;color:var(--muted);line-height:1.6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?php echo esc_html($r->body) ?></div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0">
                    <div style="color:#B8A47A;letter-spacing:2px;font-size:14px"><?php echo str_repeat('★',intval($r->rating)) ?></div>
                    <?php echo $r->published
                        ? '<span style="background:#f0fdf4;color:#15803d;padding:2px 8px;border-radius:50px;font-size:10px;font-weight:700">Aktívna</span>'
                        : '<span style="background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:50px;font-size:10px;font-weight:700">Skrytá</span>'
                    ?>
                    <div style="display:flex;gap:6px">
                        <a href="?action=reviews&edit_review=<?php echo $r->id ?>" style="padding:5px 10px;background:var(--section);border:1px solid var(--border);border-radius:5px;font-size:11px;font-weight:700;color:var(--dark);text-decoration:none">✏️ Upraviť</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Vymazať?')">
                            <?php wp_nonce_field('zcr_panel','_zcrnonce') ?>
                            <input type="hidden" name="zcr_id" value="<?php echo $r->id ?>">
                            <button name="zcr_panel_delete" value="1" style="padding:5px 8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:5px;font-size:11px;cursor:pointer;font-family:var(--sans)">🗑</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="padding:48px;text-align:center;color:var(--muted);background:var(--white);border-radius:var(--r);border:1px solid var(--border)">
                Zatiaľ žiadne recenzie. Pridajte prvú!
            </div>
            <?php endif; ?>
            <p style="font-size:11px;color:var(--muted);margin-top:12px">Shortcode: <code style="background:var(--section);padding:2px 6px;border-radius:4px">[zc_reviews]</code> alebo <code style="background:var(--section);padding:2px 6px;border-radius:4px">[zc_reviews limit="3" cols="3"]</code></p>
        </div>

        <!-- FORM -->
        <div style="position:sticky;top:calc(var(--hh,72px) + 20px)">
            <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r);padding:24px">
                <h3 style="font-family:var(--serif);font-size:17px;margin:0 0 18px"><?php echo $edit_r ? 'Upraviť recenziu' : 'Nová recenzia' ?></h3>
                <form method="post">
                    <?php wp_nonce_field('zcr_panel','_zcrnonce') ?>
                    <input type="hidden" name="zcr_id" value="<?php echo $edit_r ? $edit_r->id : 0 ?>">
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Meno klienta *</label>
                        <input type="text" name="author_name" required value="<?php echo esc_attr($edit_r->author_name??'') ?>"
                            style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px" placeholder="Jana Nováková">
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Rola / Typ</label>
                        <input type="text" name="author_role" value="<?php echo esc_attr($edit_r->author_role??'') ?>"
                            style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px" placeholder="Predaj bytu">
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Text recenzie *</label>
                        <textarea name="body" rows="4" required style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:14px;resize:vertical" placeholder="Zdenka nám pomohla..."><?php echo esc_textarea($edit_r->body??'') ?></textarea>
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Hodnotenie</label>
                        <?php $rating = intval($edit_r->rating ?? 5); ?>
                        <div style="display:flex;gap:4px" id="pnlStars">
                        <?php for($i=1;$i<=5;$i++): ?>
                        <span onclick="pnlSetRating(<?php echo $i ?>)"
                            style="font-size:26px;cursor:pointer;color:<?php echo $i<=$rating?'#B8A47A':'#E0D8CE' ?>;transition:color .15s"
                            data-v="<?php echo $i ?>">★</span>
                        <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="pnlRating" value="<?php echo $rating ?>">
                    </div>
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px">Foto klienta (URL)</label>
                        <input type="text" name="avatar_url" value="<?php echo esc_attr($edit_r->avatar_url??'') ?>"
                            style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-family:var(--sans);font-size:13px" placeholder="https://...">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="checkbox" name="published" value="1" <?php echo ($edit_r->published??1)?'checked':'' ?> style="accent-color:#B8A47A;width:16px;height:16px">
                            <span style="font-size:13px;color:var(--dark)">Zobrazená na webe</span>
                        </label>
                    </div>
                    <button type="submit" name="zcr_panel_save" value="1" class="btn btn-primary" style="width:100%;justify-content:center">
                        💾 Uložiť recenziu
                    </button>
                    <?php if ($edit_r): ?>
                    <a href="?action=reviews" style="display:block;text-align:center;margin-top:10px;font-size:12px;color:var(--muted)">Zrušiť úpravu</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script>
    function pnlSetRating(v) {
        document.getElementById('pnlRating').value = v;
        document.querySelectorAll('#pnlStars span').forEach(function(s) {
            s.style.color = parseInt(s.dataset.v) <= v ? '#B8A47A' : '#E0D8CE';
        });
    }
    </script>
    <?php
}
