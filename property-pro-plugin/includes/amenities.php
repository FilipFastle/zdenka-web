<?php
defined('ABSPATH') || exit;
function get_property_amenities() {
    $amenities = [
        'interior' => [
            'label' => 'Interiér a dispozícia', 'icon' => 'sofa',
            'items' => [
                'zariadeny_kompletne' => 'Kompletne zariadený',
                'zariadeny_ciastocne' => 'Čiastočne zariadený',
                'nezariadeny' => 'Nezariadený',
                'klimatizacia' => 'Klimatizácia',
                'podlahove_kurenie' => 'Podlahové kúrenie',
                'krb' => 'Krb / Kachle',
                'vstavane_skrine' => 'Vstavané skrine',
                'satnik' => 'Samostatný šatník',
                'komora' => 'Komora / Špajza',
                'bezp_dvere' => 'Bezpečnostné dvere',
                'plastove_okna' => 'Plastové okná',
                'hlinikove_okna' => 'Hliníkové okná',
                'euro_okna' => 'Drevené eurookná',
                'smart_home' => 'Inteligentná domácnosť',
                'rekuperacia' => 'Rekuperácia vzduchu',
                'murivo_tehla' => 'Murivo: tehla',
                'murivo_panel' => 'Murivo: panel',
                'nova_kuchyna' => 'Nová kuchynská linka',
                'spotrebice' => 'Spotrebiče v cene',
                'vysoke_stropy' => 'Vysoké stropy',
                'mezonet' => 'Mezonet',
                'pracovna' => 'Pracovňa / Home office',
                'po_rekonstrukcii' => 'Po kompletnej rekonštrukcii',
                'orientacia_juh' => 'Orientácia na juh',
            ]
        ],
        'exterior' => [
            'label' => 'Exteriér a príslušenstvo', 'icon' => 'tree',
            'items' => [
                'balkon' => 'Balkón',
                'lodzia' => 'Loggia',
                'terasa' => 'Terasa',
                'zimna_zahrada' => 'Zimná záhrada',
                'pivnica' => 'Pivnica / Kobka',
                'zahrada' => 'Záhrada / Predzáhradka',
                'bazen' => 'Bazén',
                'sauna' => 'Sauna / Vírivka',
                'altanok' => 'Altánok / Gril',
                'studna' => 'Vlastná studňa',
                'pergola' => 'Pergola',
                'jazierko' => 'Jazierko / Vodný prvok',
                'oploteny_pozemok' => 'Oplotený pozemok',
                'zavlaha' => 'Zavlažovací systém',
                'vonkajsie_sedenie' => 'Vonkajšie sedenie',
                'sklenik' => 'Skleník',
                'ovocne_stromy' => 'Ovocné stromy',
                'hospodarska_budova' => 'Hospodárska budova / Sklad',
            ]
        ],
        'building' => [
            'label' => 'Budova a spoločné priestory', 'icon' => 'building',
            'items' => [
                'vytah' => 'Výťah osobný',
                'vytah_nakladny' => 'Výťah nákladný',
                'bezbarierovy' => 'Bezbariérový prístup',
                'kocikaren' => 'Kočikáreň / Bicykláreň',
                'zateplenie' => 'Zateplený bytový dom',
                'kotolna' => 'Vlastná kotolňa',
                'uzavrety_dvor' => 'Uzavretý dvor / Vnútroblok',
                'spolovna' => 'Spoločná terasa / strecha',
                'nizky_pocet_bytov' => 'Nízky počet bytov na poschodí',
                'spravca' => 'Správcovská spoločnosť',
                'fond_oprav' => 'Zdravý fond opráv',
                'nova_strecha' => 'Nová strecha',
            ]
        ],
        'parking' => [
            'label' => 'Parkovanie', 'icon' => 'car',
            'items' => [
                'garaza' => 'Samostatná garáž',
                'garazove_statie' => 'Garážové státie (podzemné)',
                'parkovanie_vonkajsie' => 'Vyhradené vonkajšie parkovanie',
                'hosťovske_parkovanie' => 'Hosťovské parkovanie',
                'wallbox' => 'Nabíjanie elektromobilov (Wallbox)',
                'dvojgaraz' => 'Dvojgaráž',
                'kryte_statie' => 'Kryté státie / Prístrešok',
            ]
        ],
        'energy' => [
            'label' => 'Kúrenie a energie', 'icon' => 'flame',
            'items' => [
                'plyn_kotol' => 'Plynový kotol',
                'czt' => 'Centrálne zásobovanie teplom',
                'elektro_kurenie' => 'Elektrické kúrenie',
                'tuhe_palivo' => 'Kotol na tuhé palivo',
                'nizkoenerg' => 'Nízkoenergetická stavba',
                'cert_ab' => 'Energetický certifikát A/B',
                'vlastne_merace' => 'Vlastné merače energií',
            ]
        ],
        'tech' => [
            'label' => 'Technológie a bezpečnosť', 'icon' => 'plug',
            'items' => [
                'opticky_internet' => 'Optický internet',
                'kabelova_tv' => 'Káblová televízia',
                'alarm' => 'Alarm / Jablotron',
                'kamerovy_system' => 'Kamerový systém (CCTV)',
                'video_vratnik' => 'Video-vrátnik',
                'recepcja' => 'Recepcia / Strážna služba 24/7',
                'fotovoltika' => 'Solárne / Fotovoltické panely',
                'tepelne_cerpadlo' => 'Tepelné čerpadlo',
                'dobijanie_auto' => 'Dobíjacia stanica pre auto',
                'smart_zamok' => 'Smart zámok',
            ]
        ],
        'location' => [
            'label' => 'Lokalita a okolie', 'icon' => 'pin',
            'items' => [
                'ticha_lokalita' => 'Tichá lokalita',
                'vyhlad_mesto' => 'Výhľad na mesto',
                'vyhlad_priroda' => 'Výhľad do prírody',
                'mhd_blizko' => 'Zastávka MHD v blízkosti',
                'pesia_dostupnost' => 'Pešia dostupnosť do centra',
                'park_les' => 'Blízkosť parku / Lesa',
                'cyklotrasy' => 'Cyklotrasy v okolí',
                'obcianska_vybavenost' => 'Kompletná občianska vybavenosť',
                'detske_ihrisko' => 'Detské ihrisko pri dome',
                'skola_blizko' => 'Škola / Škôlka v blízkosti',
                'obchody_blizko' => 'Obchody v blízkosti',
                'restauracie' => 'Reštaurácie / Kaviarne',
                'nemocnica_blizko' => 'Nemocnica / Zdravotné stredisko',
                'centrum' => 'Priamo v centre',
                'novostavba_stvrt' => 'Nová rezidenčná štvrť',
            ]
        ],
    ];

    // Vlastné položky pridané v Realitnom paneli
    $custom = get_option('pp_custom_amenities', []);
    if (is_array($custom) && $custom) {
        $amenities['custom'] = ['label' => 'Vlastné vybavenie', 'icon' => 'star', 'items' => $custom];
    }

    return $amenities;
}

// ── Vlastné vybavenie – pridanie / mazanie (Realitný panel) ─────────────
add_action('wp_ajax_pp_amenity_add', function() {
    check_ajax_referer('pp_amenity', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $label = trim(sanitize_text_field($_POST['label'] ?? ''));
    if (!$label || mb_strlen($label) > 60) {
        wp_send_json_error(['message' => 'Zadajte názov (max. 60 znakov).']);
    }
    $key = 'custom_' . sanitize_title($label);
    if (!$key || $key === 'custom_') wp_send_json_error(['message' => 'Neplatný názov.']);
    $custom = get_option('pp_custom_amenities', []);
    if (isset($custom[$key])) wp_send_json_error(['message' => 'Táto položka už existuje.']);
    $custom[$key] = $label;
    update_option('pp_custom_amenities', $custom);
    wp_send_json_success(['key' => $key, 'label' => $label]);
});

add_action('wp_ajax_pp_amenity_del', function() {
    check_ajax_referer('pp_amenity', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Nedostatočné oprávnenie.']);
    $key = sanitize_key($_POST['key'] ?? '');
    $custom = get_option('pp_custom_amenities', []);
    if (!isset($custom[$key])) wp_send_json_error(['message' => 'Položka neexistuje.']);
    unset($custom[$key]);
    update_option('pp_custom_amenities', $custom);
    wp_send_json_success();
});
