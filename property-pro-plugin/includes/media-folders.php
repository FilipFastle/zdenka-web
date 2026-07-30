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
        // Pri prílohách je spoľahlivejší všeobecný počet vzťahov. Predvolený
        // WordPress callback vie pri médiách ponechať zastaraný počet.
        'update_count_callback' => '_update_generic_term_count',
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

    $branch_count = function ($term_id) use (&$branch_count, $by_parent) {
        $sum = 0;
        if (!empty($by_parent[$term_id])) {
            foreach ($by_parent[$term_id] as $child) {
                $sum += (int) $child->count + $branch_count((int) $child->term_id);
            }
        }
        return $sum;
    };

    $out = [];
    $walk = function ($parent, $depth) use (&$walk, &$out, $by_parent, $branch_count) {
        if (empty($by_parent[$parent])) return;
        foreach ($by_parent[$parent] as $t) {
            $out[] = [
                'id'    => (int) $t->term_id,
                'name'  => $t->name,
                'pad'   => str_repeat('— ', $depth),
                'count' => (int) $t->count + $branch_count((int) $t->term_id),
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

/** Počet médií, ktoré ešte nie sú zaradené do žiadneho priečinka. */
function zc_folders_unassigned_count() {
    $query = new WP_Query([
        'post_type'              => 'attachment',
        'post_status'            => 'inherit',
        'post_mime_type'         => 'image',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => [[
            'taxonomy' => ZC_FOLDER_TAX,
            'operator' => 'NOT EXISTS',
        ]],
    ]);
    return (int) $query->found_posts;
}

/**
 * Vráti priečinok konkrétnej ponuky. Voliteľne ho bezpečne vytvorí.
 *
 * Priečinok je iba taxonomické označenie prílohy. Fyzická cesta súboru,
 * URL ani väzby na iné priečinky sa nemenia.
 */
function zc_folder_for_property($post_id, $create = false) {
    $post_id = (int) $post_id;
    $post    = $post_id ? get_post($post_id) : null;
    if (!$post || $post->post_type !== 'property') return 0;

    $folder = (int) get_post_meta($post_id, '_property_folder_id', true);
    if ($folder && term_exists($folder, ZC_FOLDER_TAX)) return $folder;
    if (!$create) return 0;

    $folder = zc_folder_get_or_create(zc_folder_name_for_property($post), zc_folder_root_ponuky());
    if ($folder) update_post_meta($post_id, '_property_folder_id', $folder);
    return $folder;
}

/**
 * Zaradí titulnú fotku aj celú galériu ponuky do priečinka s jej názvom.
 * Volá sa pri každom uložení ponuky – z panela aj z wp-adminu.
 */
function zc_folder_sync_property($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'property') return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

    // Ak už priečinok pre túto ponuku poznáme, držíme sa ho – aj keď sa
    // ponuka medzitým premenovala. Inak by pribúdali priečinky navyše.
    $folder = zc_folder_for_property($post_id, true);
    if (!$folder) return;

    // Staršie priečinky mohli mať iba názov ponuky. Doplníme ID a správneho
    // rodiča, aby sa dve rovnako pomenované ponuky nikdy nezlúčili.
    $term = get_term($folder, ZC_FOLDER_TAX);
    $target_name   = zc_folder_name_for_property($post);
    $target_parent = zc_folder_root_ponuky();
    if ($term && !is_wp_error($term) &&
        ($term->name !== $target_name || (int) $term->parent !== $target_parent)) {
        wp_update_term($folder, ZC_FOLDER_TAX, [
            'name'   => $target_name,
            'slug'   => '',
            'parent' => $target_parent,
        ]);
    }

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

/** Názov priečinka pre ponuku. */
function zc_folder_name_for_property($post) {
    $title = is_object($post) ? trim((string) $post->post_title) : '';
    $id    = is_object($post) ? (int) $post->ID : 0;
    // ID zaručí samostatný priečinok aj pri dvoch ponukách s rovnakým názvom.
    return ($title !== '' ? $title : 'Ponuka') . ' — #' . $id;
}

/**
 * Zaradenie hneď pri nahratí súboru.
 *
 * Keď maklérka nahráva fotky priamo z úpravy ponuky, WordPress ich pripne
 * k tejto ponuke (post_parent). Priečinok jej teda vieme dať okamžite –
 * nemusí čakať na uloženie formulára.
 */
add_action('add_attachment', function ($att_id) {
    $parent = (int) get_post_field('post_parent', $att_id);
    if (!$parent) return;

    $post = get_post($parent);
    if (!$post || $post->post_type !== 'property') return;

    $folder = zc_folder_get_or_create(zc_folder_name_for_property($post), zc_folder_root_ponuky());
    if ($folder) wp_set_object_terms($att_id, [$folder], ZC_FOLDER_TAX, true);
});

/**
 * Keď sa ponuka premenuje, premenujeme aj jej priečinok –
 * inak by po každej zmene názvu vznikol druhý priečinok s tými istými fotkami.
 */
add_action('post_updated', function ($post_id, $after, $before) {
    if (!$after || $after->post_type !== 'property') return;
    if ($before->post_title === $after->post_title) return;

    $folder = (int) get_post_meta($post_id, '_property_folder_id', true);
    if (!$folder || !term_exists($folder, ZC_FOLDER_TAX)) return;

    $new_name = zc_folder_name_for_property($after);
    $current  = get_term($folder, ZC_FOLDER_TAX);
    if (!$current || is_wp_error($current) || $current->name === $new_name) return;

    wp_update_term($folder, ZC_FOLDER_TAX, ['name' => $new_name, 'slug' => '']);
}, 10, 3);

/* ─────────────── Bezpečné spätné zaradenie starších ponúk ─────────────── */

/**
 * Staršie ponuky vznikli skôr než priečinky. Po aktualizácii ich spracujeme
 * po dávkach, aby sa pri väčšom webe nepreťažil server. Menia sa iba termíny
 * taxonómie; fyzické súbory, URL a galérie zostávajú bez zmeny.
 */
function zc_folders_backfill_existing_properties() {
    if (!is_admin() || !current_user_can('upload_files')) return;
    if (get_option('zc_media_folders_backfill_version') === '5') return;

    $offset = max(0, (int) get_option('zc_media_folders_backfill_offset', 0));
    $ids = get_posts([
        'post_type'      => 'property',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
        'posts_per_page' => 50,
        'offset'         => $offset,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    foreach ($ids as $property_id) {
        zc_folder_sync_property((int) $property_id);
    }

    if (count($ids) < 50) {
        $term_ids = get_terms([
            'taxonomy'   => ZC_FOLDER_TAX,
            'hide_empty' => false,
            'fields'     => 'ids',
        ]);
        if (!is_wp_error($term_ids) && $term_ids) {
            wp_update_term_count_now(array_map('intval', $term_ids), ZC_FOLDER_TAX);
        }
        update_option('zc_media_folders_backfill_version', '5', false);
        delete_option('zc_media_folders_backfill_offset');
    } else {
        update_option('zc_media_folders_backfill_offset', $offset + count($ids), false);
    }
}
add_action('admin_init', 'zc_folders_backfill_existing_properties', 30);

/* ─────────────── Filtrovanie v zozname médií ─────────────── */

add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'attachment') return;
    $folders = zc_folders_flat();
    if (!$folders) return;
    $sel = isset($_GET['zc_folder']) ? (int) $_GET['zc_folder'] : 0;
    $unassigned = zc_folders_unassigned_count();
    echo '<select name="zc_folder"><option value="0">Všetky priečinky</option>';
    printf('<option value="-1"%s>Nezaradené (%d)</option>',
        selected($sel, -1, false), $unassigned);
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
    if ($f === -1) {
        $q->set('tax_query', [[
            'taxonomy' => ZC_FOLDER_TAX,
            'operator' => 'NOT EXISTS',
        ]]);
    } elseif ($f > 0) {
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

    $property_id = 0;
    if (function_exists('pp_is_panel_page') && pp_is_panel_page() && (($_GET['action'] ?? '') === 'edit')) {
        $candidate = intval($_GET['id'] ?? 0);
        if ($candidate && get_post_type($candidate) === 'property' && current_user_can('edit_post', $candidate)) {
            $property_id = $candidate;
        }
    } elseif (is_admin()) {
        $candidate = intval($_GET['post'] ?? $_POST['post_ID'] ?? 0);
        if ($candidate && get_post_type($candidate) === 'property' && current_user_can('edit_post', $candidate)) {
            $property_id = $candidate;
        }
    }
    $property_folder = $property_id ? zc_folder_for_property($property_id, true) : 0;
    $unassigned_count = zc_folders_unassigned_count();

    wp_add_inline_script('media-views',
        'var zcMediaFolders = ' . wp_json_encode($folders) . ';'
        . 'var zcCurrentPropertyFolder = ' . (int) $property_folder . ';'
        . 'var zcCurrentPropertyId = ' . (int) $property_id . ';'
        . 'var zcUnassignedMediaCount = ' . (int) $unassigned_count . ';', 'before');

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
            filters['zcfolder-unassigned'] = {
                text: '\u{1F5C2} Nezaradené (' + Number(window.zcUnassignedMediaCount || 0) + ')',
                props: { zc_folder: -1, uploadedTo: null, orderby: 'date', order: 'DESC' },
                priority: 55
            };
            zcMediaFolders.forEach(function (f) {
                filters['zcfolder-' + f.id] = {
                    text: (Number(f.id)===Number(window.zcCurrentPropertyFolder)?'\u2605 ':'\u{1F4C1} ') + f.pad + f.name + ' (' + f.count + ')',
                    props: { zc_folder: f.id, uploadedTo: null, orderby: 'date', order: 'DESC' },
                    priority: Number(f.id)===Number(window.zcCurrentPropertyFolder)?5:60
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
    if ($f === -1) {
        $args['tax_query'] = [[
            'taxonomy' => ZC_FOLDER_TAX,
            'operator' => 'NOT EXISTS',
        ]];
    } elseif ($f > 0) {
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
