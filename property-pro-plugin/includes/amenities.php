<?php
defined('ABSPATH') || exit;
function get_property_amenities() {
    return [
        'interior' => [
            'label' => '🛋️ Interiér a dispozícia',
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
            ]
        ],
        'exterior' => [
            'label' => '🌳 Exteriér a príslušenstvo',
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
            ]
        ],
        'building' => [
            'label' => '🏢 Budova a spoločné priestory',
            'items' => [
                'vytah' => 'Výťah osobný',
                'vytah_nakladny' => 'Výťah nákladný',
                'bezbarierovy' => 'Bezbariérový prístup',
                'kocikaren' => 'Kočikáreň / Bicykláreň',
                'zateplenie' => 'Zateplený bytový dom',
                'kotolna' => 'Vlastná kotolňa',
                'uzavrety_dvor' => 'Uzavretý dvor / Vnútroblok',
                'spolovna' => 'Spoločná terasa / strecha',
            ]
        ],
        'parking' => [
            'label' => '🚗 Parkovanie',
            'items' => [
                'garaza' => 'Samostatná garáž',
                'garazove_statie' => 'Garážové státie (podzemné)',
                'parkovanie_vonkajsie' => 'Vyhradené vonkajšie parkovanie',
                'hosťovske_parkovanie' => 'Hosťovské parkovanie',
                'wallbox' => 'Nabíjanie elektromobilov (Wallbox)',
                'dvojgaraz' => 'Dvojgaráž',
            ]
        ],
        'tech' => [
            'label' => '🔌 Technológie a bezpečnosť',
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
            ]
        ],
        'location' => [
            'label' => '📍 Lokalita a okolie',
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
            ]
        ],
    ];
}
