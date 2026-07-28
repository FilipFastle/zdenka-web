<?php
/**
 * Priečinky v Médiách.
 *
 * WordPress priečinky nepozná – všetko hádže do jednej kopy podľa dátumu.
 * Tu pridávame skutočné priečinky (aj vnorené) a hlavne: fotky každej ponuky
 * sa do svojho priečinka zaradia samy pri uložení ponuky.
 */
defined('ABSPATH') || exit;

define('ZC_FOLDER_TAX', 'zc_media_folder');

add_action('init', function () {
    register_taxonomy(ZC_FOLDER_TAX, 'attachment', [
        'labels' => [
            'name'          => 'Priečinky',
            'singular_name' => 'Priečinok',
            'menu_name'     => 'Priečinky',
            'all_items'     => 'Všetky priečinky',
            'edit_item'     => 'Upraviť priečinok',
            'add_new_item'  => 'Pridať priečinok',
            'search_items'  => 'Hľadať priečinok',
            'parent_item'   => 'Nadradený priečinok',
            'not_found'     => 'Zatiaľ žiadne priečinky.',
        ],
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_menu'      => true,
        'show_in_rest'      => true,
        'rewrite'           => false,
        'capabilities'      => [
            'manage_terms' => 'upload_files',
            'edit_terms'   => 'upload_files',
            'delete_terms' => 'upload_files',
            'assign_terms' => 'upload_files',
        ],
    ]);
});

/** Zoznam priečinkov pripravený na výber (s odsadením podľa vnorenia). */
function zc_folders_flat() {
    $terms = get_terms(['taxonomy' => ZC_FOLDER_TAX, 'hide_empty' => false]);
    if (is_wp_error($terms) || !$terms) return [];

    $by_parent = [];
    foreach ($terms as $t) $by_parent[$t->parent][] = $t;

    $out = [];
    $walk = function ($parent, $depth) use (&$walk, &$out, $by_parent) {
        if (empty($by_parent[$parent])) return;
        foreach ($by_parent[$parent] as $t) {
            $out[] = [
                'id'    => (int) $t->term_id,
                'name'  => $t->name,
                'pad'   => str_repeat('— ', $depth),
                'count' => (int) $t->count,
            ];
            $walk($t->term_id, $depth + 1);
        }
    };
    $walk(0, 0);
    return $out;
}

/** Nájde alebo vytvorí priečinok. */
function zc_folder_get_or_create($name, $parent = 0) {
    $name = trim(wp_strip_all_tags((string) $name));
    if ($name === '') return 0;

    $existing = get_terms([
        'taxonomy'   => ZC_FOLDER_TAX,
        'hide_empty' => false,
        'name'       => $name,
        'parent'     => (int) $parent,
    ]);
    if (!is_wp_error($existing) && $existing) return (int) $existing[0]->term_id;

    $new = wp_insert_term($name, ZC_FOLDER_TAX, ['parent' => (int) $parent]);
    if (is_wp_error($new)) {
        // Rovnaký názov už existuje inde v strome – použijeme ten
        $data = $new->get_error_data();
        return is_array($data) && !empty($data['term_id']) ? (int) $data['term_id'] : 0;
    }
    return (int) $new['term_id'];
}

/* ─────────────── Fotky ponuky sa zaradia samy ─────────────── */

/** Koreňový priečinok „Ponuky". */
function zc_folder_root_ponuky() {
    static $id = null;
    if ($id === null) $id = zc_folder_get_or_create('Ponuky');
    return $id;
}

/**
 * Zaradí titulnú fotku aj celú galériu ponuky do priečinka s jej názvom.
 * Volá sa pri každom uložení ponuky – z panela aj z wp-adminu.
 */
function zc_folder_sync_property($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'property') return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

    $title = trim($post->post_title) ?: ('Ponuka #' . $post_id);
    $folder = zc_folder_get_or_create($title, zc_folder_root_ponuky());
    if (!$folder) return;

    $ids = [];
    $cover = (int) get_post_meta($post_id, '_property_cover_id', true);
    if ($cover) $ids[] = $cover;
    foreach ((array) get_post_meta($post_id, '_property_gallery_ids', true) as $g) {
        $g = (int) $g;
        if ($g) $ids[] = $g;
    }
    $thumb = get_post_thumbnail_id($post_id);
    if ($thumb) $ids[] = (int) $thumb;

    foreach (array_unique($ids) as $att_id) {
        if (get_post_type($att_id) !== 'attachment') continue;
        // Priradíme, ale nič iné fotke nezoberieme – môže patriť aj inam
        wp_set_object_terms($att_id, [$folder], ZC_FOLDER_TAX, true);
    }

    update_post_meta($post_id, '_property_folder_id', $folder);
}
add_action('save_post_property', 'zc_folder_sync_property', 20);

/* ─────────────── Filtrovanie v zozname médií ─────────────── */

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'attachment') return;
    $folders = zc_folders_flat();
    if (!$folders) return;
    $sel = isset($_GET['zc_folder']) ? (int) $_GET['zc_folder'] : 0;
    echo '<select name="zc_folder"><option value="0">Všetky priečinky</option>';
    foreach ($folders as $f) {
        printf('<option value="%d"%s>%s%s (%d)</option>',
            $f['id'], selected($sel, $f['id'], false),
            esc_html($f['pad']), esc_html($f['name']), $f['count']);
    }
    echo '</select>';
});

add_action('pre_get_posts', function ($q) {
    if (!is_admin() || !$q->is_main_query()) return;
    if ($q->get('post_type') !== 'attachment') return;
    $f = isset($_GET['zc_folder']) ? (int) $_GET['zc_folder'] : 0;
    if ($f > 0) {
        $q->set('tax_query', [[
            'taxonomy' => ZC_FOLDER_TAX,
            'field'    => 'term_id',
            'terms'    => $f,
        ]]);
    }
});

/** Hromadné presunutie do priečinka. */
add_filter('bulk_actions-upload', function ($actions) {
    foreach (array_slice(zc_folders_flat(), 0, 30) as $f) {
        $actions['zcfolder_' . $f['id']] = 'Priečinok → ' . $f['pad'] . $f['name'];
    }
    if (zc_folders_flat()) $actions['zcfolder_none'] = 'Vybrať z priečinkov';
    return $actions;
});

add_filter('handle_bulk_actions-upload', function ($redirect, $action, $ids) {
    if (strpos($action, 'zcfolder_') !== 0) return $redirect;
    $ids = array_map('intval', (array) $ids);
    if (!$ids) return $redirect;

    if ($action === 'zcfolder_none') {
        foreach ($ids as $id) wp_set_object_terms($id, [], ZC_FOLDER_TAX, false);
        return add_query_arg('zc_folder_done', count($ids), $redirect);
    }

    $term = (int) substr($action, strlen('zcfolder_'));
    if (!$term) return $redirect;
    foreach ($ids as $id) wp_set_object_terms($id, [$term], ZC_FOLDER_TAX, true);
    return add_query_arg('zc_folder_done', count($ids), $redirect);
}, 10, 3);

add_action('admin_notices', function () {
    if (empty($_GET['zc_folder_done'])) return;
    printf('<div class="notice notice-success is-dismissible"><p>Priečinok upravený pri <strong>%d</strong> súboroch.</p></div>',
        (int) $_GET['zc_folder_done']);
});

/* ─────────────── Filter aj v okne na výber fotiek ─────────────── */

/**
 * Okno na výber médií (aj v realitnom paneli) dostane rovnaký rozbaľovací
 * zoznam priečinkov ako knižnica.
 */
function zc_folders_media_script() {
    if (!wp_script_is('media-views', 'registered')) return;
    $folders = zc_folders_flat();
    if (!$folders) return;

    wp_add_inline_script('media-views',
        'var zcMediaFolders = ' . wp_json_encode($folders) . ';', 'before');

    $js = <<<'JS'
(function(){
    if (!window.wp || !wp.media || !wp.media.view || !wp.media.view.AttachmentFilters) return;
    if (!window.zcMediaFolders || !zcMediaFolders.length) return;
    var All = wp.media.view.AttachmentFilters.All;
    if (!All || All.__zcFolders) return;

    var Extended = All.extend({
        createFilters: function () {
            All.prototype.createFilters.apply(this, arguments);
            var filters = this.filters;
            zcMediaFolders.forEach(function (f) {
                filters['zcfolder-' + f.id] = {
                    text: '\u{1F4C1} ' + f.pad + f.name + ' (' + f.count + ')',
                    props: { zc_folder: f.id, uploadedTo: null, orderby: 'date', order: 'DESC' },
                    priority: 60
                };
            });
            this.filters = filters;
        }
    });
    Extended.__zcFolders = true;
    wp.media.view.AttachmentFilters.All = Extended;
})();
JS;
    wp_add_inline_script('media-views', $js);
}
add_action('admin_enqueue_scripts', 'zc_folders_media_script', 20);
add_action('wp_enqueue_scripts',    'zc_folders_media_script', 20);

/** Výber priečinka z okna médií preložíme na dopyt do databázy. */
add_filter('ajax_query_attachments_args', function ($args) {
    $f = 0;
    if (isset($_REQUEST['query']['zc_folder']))  $f = (int) $_REQUEST['query']['zc_folder'];
    elseif (isset($_REQUEST['zc_folder']))       $f = (int) $_REQUEST['zc_folder'];
    if ($f > 0) {
        $args['tax_query'] = [[
            'taxonomy' => ZC_FOLDER_TAX,
            'field'    => 'term_id',
            'terms'    => $f,
        ]];
    }
    return $args;
});

/* ─────────────── Priečinok priamo pri fotke ─────────────── */

add_filter('attachment_fields_to_edit', function ($fields, $post) {
    if (!current_user_can('upload_files')) return $fields;
    $current = wp_get_object_terms($post->ID, ZC_FOLDER_TAX, ['fields' => 'names']);
    $fields['zc_folder_info'] = [
        'label' => 'Priečinok',
        'input' => 'html',
        'html'  => '<span style="line-height:24px">' .
                   ($current && !is_wp_error($current) ? esc_html(implode(', ', $current)) : '<em>žiadny</em>') .
                   '</span>',
        'helps' => 'Priečinky sa nastavujú v Médiá → Priečinky alebo hromadne v knižnici.',
    ];
    return $fields;
}, 10, 2);
