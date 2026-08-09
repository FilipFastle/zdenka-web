<?php
/**
 * Texty stránok – editovateľné vo wp-admine.
 *
 * Stránky ako Úvod, O mne alebo Ako pracujem majú vlastný dizajn v PHP,
 * takže ich Breakdance upravovať nevie (vedel by len vtedy, keby si ich
 * v ňom postavil odznova a prišiel o hotový vzhľad). Aby sa dali meniť
 * texty bez zásahu do kódu, prechádzajú cez zc_t().
 *
 * Kým pole necháš prázdne, použije sa pôvodný text – nič sa nerozbije.
 */
defined('ABSPATH') || exit;

/**
 * Zoznam všetkých editovateľných textov.
 * kľúč => [popisok, pôvodný text, typ: text|area]
 */
function zc_text_map() {
    return [
        'home' => [
            'title'  => 'Úvodná stránka',
            'fields' => [
                'home.badge'      => ['Štítok nad nadpisom', 'Banská Bystrica · Zvolen', 'text'],
                'home.title'      => ['Hlavný nadpis', 'Váš partner', 'text'],
                'home.title_hl'   => ['Hlavný nadpis – zvýraznená časť', 'pri predaji domova.', 'text'],
                'home.sub'        => ['Podnadpis pod nadpisom', 'Prevediem vás celým procesom profesionálne, bezpečne – vždy s osobným prístupom.', 'area'],
                'home.btn1'       => ['Tlačidlo 1', 'Pozrieť ponuky', 'text'],
                'home.btn2'       => ['Tlačidlo 2', 'Bezplatná konzultácia', 'text'],
                'home.scroll'     => ['Text pri šípke nadol', 'Scrolluj', 'text'],
                'home.values_eye' => ['Hodnoty – štítok', 'Moje hodnoty', 'text'],
                'home.values_t'   => ['Hodnoty – nadpis', 'Čo ma riadi', 'text'],
                'home.values_hl'  => ['Hodnoty – zvýraznená časť', 'pri práci', 'text'],
                'home.props_eye'  => ['Ponuky – štítok', 'Aktuálne na trhu', 'text'],
                'home.props_t'    => ['Ponuky – nadpis', 'Vybrané', 'text'],
                'home.props_hl'   => ['Ponuky – zvýraznená časť', 'nehnuteľnosti', 'text'],
                'home.props_all'  => ['Ponuky – odkaz na všetky', 'Všetky ponuky →', 'text'],
                'home.refs_eye'   => ['Referencie – štítok', 'Referencie', 'text'],
                'home.refs_t'     => ['Referencie – nadpis', 'Čo hovoria', 'text'],
                'home.refs_hl'    => ['Referencie – zvýraznená časť', 'klienti', 'text'],
                'home.refs_all'   => ['Referencie – odkaz na všetky', 'Všetky referencie →', 'text'],
                'home.cta_eye'    => ['Záverečná výzva – štítok', 'Nezáväzná konzultácia', 'text'],
                'home.cta_t'      => ['Záverečná výzva – nadpis', 'Predávate alebo hľadáte', 'text'],
                'home.cta_hl'     => ['Záverečná výzva – zvýraznená časť', 'nový domov?', 'text'],
                'home.cta_sub'    => ['Záverečná výzva – text', 'Prvá konzultácia je bezplatná a nezáväzná.', 'area'],
                'home.form_t'     => ['Nadpis formulára', 'Napíšte mi správu', 'text'],
            ],
        ],
        'about' => [
            'title'  => 'O mne',
            'fields' => [
                'about.eyebrow' => ['Štítok nad nadpisom', 'Spoznajte ma', 'text'],
                'about.p1'      => ['Prvý odsek (za menom)', ', realitná maklérka pôsobiaca vo Zvolene, Banskej Bystrici a okolí. Práca s ľuďmi ma napĺňa a svet realít mi dáva možnosť pomáhať klientom pri jednom z najdôležitejších rozhodnutí v ich živote.', 'area'],
                'about.p2'      => ['Druhý odsek', 'Ku každému klientovi pristupujem individuálne, s profesionálnym a ľudským prístupom. Pri spolupráci kladiem veľký dôraz na dôveru, úprimnosť, otvorenú komunikáciu a férové jednanie.', 'area'],
                'about.p3'      => ['Tretí odsek', 'Svojich klientov sprevádzam celým procesom – od prvého stretnutia až po odovzdanie kľúčov, pričom venujem pozornosť každému detailu. Mojím cieľom je spokojný klient, ktorý vie, že sa na mňa môže s dôverou obrátiť aj v budúcnosti.', 'area'],
                'about.btn'     => ['Tlačidlo', 'Kontaktujte ma', 'text'],
                'about.refs_t'  => ['Referencie – nadpis', 'Čo hovoria', 'text'],
                'about.refs_hl' => ['Referencie – zvýraznená časť', 'klienti', 'text'],
            ],
        ],
        'refs' => [
            'title'  => 'Referencie (podstránka)',
            'fields' => [
                'refs.eyebrow' => ['Štítok nad nadpisom', 'Referencie', 'text'],
                'refs.title'   => ['Nadpis', 'Čo hovoria', 'text'],
                'refs.title_hl'=> ['Nadpis – zvýraznená časť', 'klienti', 'text'],
                'refs.sub'     => ['Text pod nadpisom', 'Najlepšie o mojej práci hovoria skúsenosti klientov.', 'area'],
            ],
        ],
        'work' => [
            'title'  => 'Ako pracujem',
            'fields' => [
                'work.eyebrow'  => ['Štítok nad nadpisom', 'Postup spolupráce', 'text'],
                'work.title_hl' => ['Nadpis – zvýraznená časť', 'pracujem', 'text'],
                'work.sub'      => ['Text pod nadpisom', 'Od úvodnej konzultácie cez prípravu a marketing až po bezpečný prevod nehnuteľnosti – v každej fáze viete, čo sa deje a čo nasleduje. Otvorená komunikácia a transparentnosť sú pre mňa samozrejmosťou.', 'area'],
                'work.serv_eye' => ['Služby – štítok', 'Čo pre vás zabezpečím', 'text'],
                'work.serv_t'   => ['Služby – nadpis', 'Kompletný servis', 'text'],
                'work.serv_hl'  => ['Služby – zvýraznená časť', 'v každom kroku', 'text'],
                'work.vid_eye'  => ['Video – štítok', 'Ukážka práce', 'text'],
                'work.vid_hl'   => ['Video – zvýraznená časť nadpisu', 'prehliadka', 'text'],
                'work.vid_sub'  => ['Video – text', 'Pozrite si, ako vyzerá naša video prezentácia nehnuteľnosti.', 'area'],
                'work.cta_eye'  => ['Výzva – štítok', 'Nezáväzná konzultácia', 'text'],
                'work.cta_t'    => ['Výzva – nadpis', 'Začnime', 'text'],
                'work.cta_hl'   => ['Výzva – zvýraznená časť', 'spolupracovať', 'text'],
                'work.cta_btn'  => ['Výzva – tlačidlo', 'Kontaktovať →', 'text'],
            ],
        ],
    ];
}

/**
 * Jednorazové vyčistenie po zmene pôvodných textov.
 *
 * Prepis sa ukladá len vtedy, keď sa líši od pôvodného textu. Keď však
 * niekto pole otvoril a uložil ešte za starého znenia, zostal mu uložený
 * starý text – a nové znenie by sa na webe neukázalo. Preto pri prvom
 * načítaní po aktualizácii zahodíme prepisy, ktoré sa presne rovnajú
 * niektorému z nahradených textov. Ručne napísaných textov sa to nedotkne.
 */
add_action('init', function () {
    if (get_option('zc_texts_cleanup') === '2') return;

    $nahradene = [
        'home.title'    => ['Predáme váš domov'],
        'home.title_hl' => ['za najlepšiu cenu'],
        'home.sub'      => ['Profesionálna realitná maklérka s bohatými skúsenosťami. Predaj, prenájom aj poradenstvo – vždy s osobným prístupom.'],
        'work.sub'      => ['Transparentný, overený proces – od prvého stretnutia po odovzdanie kľúčov. Každý krok robím osobne a vždy v záujme klienta.'],
        'work.serv_eye' => ['Čo robím pre vás'],
    ];

    $saved  = (array) get_option('zc_texts', []);
    $zmena  = false;
    foreach ($nahradene as $key => $stare) {
        if (!isset($saved[$key])) continue;
        if (in_array(trim((string) $saved[$key]), $stare, true)) {
            unset($saved[$key]);
            $zmena = true;
        }
    }
    if ($zmena) update_option('zc_texts', $saved);
    update_option('zc_texts_cleanup', '2', false);
}, 5);

/** Uložené prepisy textov. */
function zc_texts_saved() {
    static $saved = null;
    if ($saved === null) $saved = (array) get_option('zc_texts', []);
    return $saved;
}

/** Pôvodný (zabudovaný) text pre kľúč. */
function zc_text_default($key) {
    foreach (zc_text_map() as $group) {
        if (isset($group['fields'][$key])) return $group['fields'][$key][1];
    }
    return '';
}

/**
 * Text na výpis. Prázdne pole = použije sa pôvodný text.
 * Povolené sú len jednoduché značky, aby sa nedal rozbiť vzhľad stránky.
 */
function zc_t($key) {
    $saved = zc_texts_saved();
    $value = isset($saved[$key]) ? trim((string) $saved[$key]) : '';
    if ($value === '') $value = zc_text_default($key);

    return wp_kses($value, [
        'br'     => [],
        'strong' => [], 'b' => [],
        'em'     => [], 'i' => [],
        'span'   => ['class' => [], 'style' => []],
        'a'      => ['href' => [], 'title' => [], 'target' => [], 'rel' => []],
    ]);
}

/* ───────────────────────── Stránka v admine ───────────────────────── */

add_action('admin_menu', function () {
    add_submenu_page(zc_hub_slug(), 'Texty stránok', 'Texty stránok', 'edit_posts',
        'zc-texty', 'zc_texts_admin_page');
}, 51);

function zc_texts_admin_page() {
    if (!current_user_can('edit_posts')) wp_die('Nemáš oprávnenie.');
    zc_hub_styles();

    $map   = zc_text_map();
    $saved = (array) get_option('zc_texts', []);
    $msg   = '';

    if (isset($_POST['zc_texts_save']) && check_admin_referer('zc_texts')) {
        $in  = (array) ($_POST['zct'] ?? []);
        $out = [];
        foreach ($map as $group) {
            foreach ($group['fields'] as $key => $field) {
                $value = isset($in[$key]) ? trim((string) wp_unslash($in[$key])) : '';
                // Prázdne pole necháme nezapísané – vráti sa pôvodný text
                if ($value !== '' && $value !== $field[1]) $out[$key] = wp_kses_post($value);
            }
        }
        update_option('zc_texts', $out);
        $saved = $out;
        $msg   = 'Texty uložené. Pozri sa na web – zmena je vidieť hneď.';
    }

    if (isset($_POST['zc_texts_reset']) && check_admin_referer('zc_texts')) {
        update_option('zc_texts', []);
        $saved = [];
        $msg   = 'Všetky texty sú vrátené na pôvodné znenie.';
    }
    ?>
    <div class="wrap zch">
        <div class="zch-head"><h1>Texty stránok</h1>
            <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="button">Pozrieť web →</a>
        </div>

        <?php if ($msg): ?><div class="notice notice-success"><p><?php echo esc_html($msg); ?></p></div><?php endif; ?>

        <div class="zch-note" style="margin-bottom:20px">
            Meníš tu texty na stránkach, ktoré majú vlastný dizajn (Úvod, O mne, Ako pracujem).
            <strong>Prázdne pole = ostáva pôvodný text</strong>, takže sa nedá nič pokaziť —
            keď políčko vymažeš, vráti sa presne to, čo tam bolo.
            <br>Povolené sú jednoduché značky <code>&lt;br&gt;</code>, <code>&lt;strong&gt;</code>,
            <code>&lt;em&gt;</code> a odkaz <code>&lt;a href=""&gt;</code>.
        </div>

        <div class="zch-note" style="margin-bottom:22px">
            <strong>A čo Breakdance?</strong> Tieto stránky majú vlastný dizajn napísaný v kóde,
            takže ich Breakdance neotvorí ako hotovú skladačku – zobrazil by prázdne plátno.
            Ak niektorú stránku chceš mať naozaj celú v Breakdance, otvor ju v ňom, postav ju
            a ulož; téma sa vtedy sama odsunie a zobrazí tvoju verziu. Rátaj ale s tým, že
            pôvodný vzhľad tej stránky tým nahradíš. Na bežnú zmenu textu stačí táto obrazovka.
        </div>

        <form method="post">
            <?php wp_nonce_field('zc_texts'); ?>
            <?php foreach ($map as $gkey => $group): ?>
            <div class="zch-box">
                <h2 style="margin-top:0;font-size:16px"><?php echo esc_html($group['title']); ?></h2>
                <table class="zch-tbl">
                    <?php foreach ($group['fields'] as $key => [$label, $default, $type]):
                        $value = isset($saved[$key]) ? $saved[$key] : ''; ?>
                    <tr>
                        <td style="width:32%;vertical-align:top;padding-top:14px">
                            <?php echo esc_html($label); ?>
                            <?php if ($value !== ''): ?>
                            <br><span style="font-size:11px;color:#b45309;font-weight:700">zmenené</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($type === 'area'): ?>
                            <textarea name="zct[<?php echo esc_attr($key); ?>]" rows="3"
                                style="width:100%;padding:9px 11px;border:1.5px solid #E6DFD2;border-radius:8px;font-size:13.5px;line-height:1.6"
                                placeholder="<?php echo esc_attr($default); ?>"><?php echo esc_textarea($value); ?></textarea>
                            <?php else: ?>
                            <input type="text" name="zct[<?php echo esc_attr($key); ?>]"
                                value="<?php echo esc_attr($value); ?>"
                                placeholder="<?php echo esc_attr($default); ?>"
                                style="width:100%;padding:9px 11px;border:1.5px solid #E6DFD2;border-radius:8px;font-size:13.5px">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>

            <p style="display:flex;gap:10px;flex-wrap:wrap">
                <button class="button button-primary button-large" name="zc_texts_save" value="1">Uložiť texty</button>
                <button class="button" name="zc_texts_reset" value="1"
                    onclick="return confirm('Naozaj vrátiť všetky texty na pôvodné znenie?')">Vrátiť všetko na pôvodné</button>
            </p>
        </form>
    </div>
    <?php
}
