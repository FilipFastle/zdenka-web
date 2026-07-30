<?php
defined('ABSPATH') || exit;
// ── Stránka Ochrana osobných údajov (živnostník / GDPR) ─────────────────
//
// Obsah je bežný obsah WP stránky, takže sa dá upraviť v editore.
// Zástupné texty sú v dvojitých zložených zátvorkách, aby sa dali naraz
// nahradiť cez Nájsť a nahradiť (Ctrl+F / Ctrl+H):
//   {{MENO}}     – meno a priezvisko živnostníčky (napr. Mgr. Zdenka Cibuľová)
//   {{ICO}}      – IČO
//   {{SIDLO}}    – miesto podnikania / adresa
//   {{EMAIL}}    – kontaktný e-mail
//   {{TELEFON}}  – telefónne číslo
//   {{WEB}}      – doména webu (napr. www.zdenkacibulova.sk)

// Verzia predvoleného obsahu – zvýš, keď sa text aktualizuje.
if (!defined('ZC_PRIVACY_VERSION')) define('ZC_PRIVACY_VERSION', '2');

function zc_privacy_default_content() {
    $rok = date('Y');
    $datum = date('d.m.Y');
    return <<<HTML
<!-- wp:paragraph -->
<p><em>Tento dokument obsahuje zástupné texty v zátvorkách ako {{MENO}}, {{ICO}}, {{SIDLO}}, {{EMAIL}}, {{TELEFON}} a {{WEB}}. Otvorte editor stránky, stlačte Ctrl+F (Nájsť a nahradiť) a nahraďte ich svojimi údajmi. Potom tento odsek zmažte. Tento vzor má informatívny charakter; pri pochybnostiach odporúčame konzultáciu s odborníkom na ochranu osobných údajov.</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tieto zásady popisujú, ako prevádzkovateľ spracúva osobné údaje v súlade s Nariadením Európskeho parlamentu a Rady (EÚ) 2016/679 (GDPR) a so zákonom č. 18/2018 Z. z. o ochrane osobných údajov v znení neskorších predpisov.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>1. Prevádzkovateľ osobných údajov</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Prevádzkovateľom, ktorý spracúva vaše osobné údaje, je {{MENO}}, so sídlom (miestom podnikania) {{SIDLO}}, IČO: {{ICO}}, e-mail: {{EMAIL}}, telefón: {{TELEFON}} (ďalej len „prevádzkovateľ"). Prevádzkovateľ je fyzická osoba – podnikateľ (živnostník) pôsobiaci v oblasti sprostredkovania predaja, kúpy a prenájmu nehnuteľností. Prevádzkovateľ neurčil zodpovednú osobu (DPO), keďže mu to zákon neukladá.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>2. Aké osobné údaje spracúvame</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li><strong>Identifikačné a kontaktné údaje:</strong> meno a priezvisko, e-mailová adresa, telefónne číslo;</li>
<li><strong>Údaje z formulárov</strong> (kontaktný formulár, formulár na odhad nehnuteľnosti, dopyt k ponuke, formulár na stiahnutie e-booku): obsah vašej správy, údaje o nehnuteľnosti a odpovede na položené otázky, ktoré nám dobrovoľne poskytnete;</li>
<li><strong>Údaje pri odbere noviniek (newsletter):</strong> e-mailová adresa, prípadne meno a zvolená kategória nehnuteľností;</li>
<li><strong>Údaje o udelení súhlasu a technické údaje formulára:</strong> IP adresa a dátum a čas odoslania formulára – uchovávame ich ako doklad o udelení súhlasu a na ochranu pred zneužívaním formulárov (spam);</li>
<li><strong>Ostatné technické údaje:</strong> typ a nastavenia prehliadača a údaje z cookies (pozri bod 7).</li>
</ul>
<!-- /wp:list -->
<!-- wp:paragraph -->
<p>Osobitné kategórie osobných údajov (napr. o zdraví, náboženstve) nespracúvame. Poskytnutie údajov je dobrovoľné; bez uvedenia kontaktných údajov vás však nedokážeme kontaktovať ani vybaviť váš dopyt.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>3. Účely a právny základ spracúvania</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li><strong>Vybavenie dopytu a komunikácia</strong> – vykonanie opatrení pred uzatvorením zmluvy na vašu žiadosť, resp. oprávnený záujem odpovedať na vaše otázky (čl. 6 ods. 1 písm. b) a f) GDPR).</li>
<li><strong>Sprostredkovanie predaja, kúpy alebo prenájmu nehnuteľnosti</strong> – plnenie zmluvy, resp. predzmluvné vzťahy (čl. 6 ods. 1 písm. b) GDPR).</li>
<li><strong>Poskytnutie e-booku (PDF na stiahnutie)</strong> – zaslanie vyžiadaného materiálu na váš e-mail na základe vašej žiadosti a súhlasu udeleného pri odoslaní formulára (čl. 6 ods. 1 písm. a) a b) GDPR).</li>
<li><strong>Zasielanie noviniek a ponúk (newsletter)</strong> – na základe vášho súhlasu (čl. 6 ods. 1 písm. a) GDPR), ktorý môžete kedykoľvek odvolať. Súhlas so zasielaním noviniek udeľujete aj pri stiahnutí e-booku, o čom ste pred odoslaním formulára viditeľne informovaní.</li>
<li><strong>Ochrana webu a formulárov pred zneužitím (spam) a preukázanie udeleného súhlasu</strong> – na základe oprávneného záujmu prevádzkovateľa (čl. 6 ods. 1 písm. f) GDPR); na tento účel spracúvame IP adresu a čas odoslania formulára.</li>
<li><strong>Plnenie zákonných povinností</strong> – najmä účtovných a daňových (čl. 6 ods. 1 písm. c) GDPR).</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>4. Doba uchovávania</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li>údaje z dopytov a formulárov: po dobu vybavenia a následne najviac 12 mesiacov;</li>
<li>údaje na zasielanie noviniek: do odvolania súhlasu (odhlásenia sa z odberu);</li>
<li>IP adresa a čas odoslania formulára ako doklad o súhlase: po dobu trvania súhlasu, resp. najviac 12 mesiacov od vybavenia;</li>
<li>údaje potrebné pre zmluvné a zákonné povinnosti: po dobu stanovenú príslušnými predpismi (napr. účtovné doklady 10 rokov).</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>5. Príjemcovia a sprostredkovatelia</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vaše údaje môžu byť poskytnuté spoľahlivým sprostredkovateľom, ktorí pre nás zabezpečujú služby: poskytovateľ webhostingu a e-mailových služieb (prevádzka webu {{WEB}} a odosielanie e-mailov), účtovník, prípadne realitné portály pri inzercii nehnuteľnosti. Všetci sú viazaní mlčanlivosťou a spracúvajú údaje na základe zmluvy o spracúvaní osobných údajov. Osobné údaje nezverejňujeme a nepredávame tretím stranám na marketingové účely.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>6. Prenos do tretích krajín</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Osobné údaje spravidla nespracúvame mimo Európskeho hospodárskeho priestoru (EHP). Web môže na svoje zobrazenie využívať technické prostriedky tretích poskytovateľov (napr. webové písma či mapy), pri ktorých môže dôjsť k prenosu technických údajov (vrátane IP adresy) do tretej krajiny; takýto prenos prebieha na základe primeraných záruk podľa GDPR. Ak si neželáte tento prenos, môžete použitie takýchto prvkov obmedziť odmietnutím voliteľných cookies alebo v nastaveniach prehliadača.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>7. Cookies</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Web {{WEB}} používa <strong>nevyhnutné cookies</strong> potrebné na svoje fungovanie (tie nevyžadujú súhlas) a môže používať <strong>analytické alebo marketingové cookies</strong>, ktoré nastavíme len s vaším súhlasom prostredníctvom lišty o súhlase s cookies. Svoj súhlas môžete kedykoľvek zmeniť alebo odvolať a používanie cookies obmedziť aj v nastaveniach svojho prehliadača.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>8. Odber noviniek (newsletter) a stiahnutie e-booku</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ak ste sa prihlásili na odber noviniek – či už priamo, alebo v rámci stiahnutia e-booku – spracúvame vašu e-mailovú adresu (prípadne meno) na základe súhlasu. Pred odoslaním formulára ste o prihlásení na odber viditeľne informovaní. Z odberu sa môžete <strong>kedykoľvek bezplatne odhlásiť</strong> kliknutím na odkaz „Odhlásiť sa" v pätičke každého e-mailu alebo napísaním na {{EMAIL}}. Odvolanie súhlasu nemá vplyv na zákonnosť spracúvania pred jeho odvolaním.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>9. Automatizované rozhodovanie a profilovanie</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Prevádzkovateľ nevykonáva automatizované rozhodovanie ani profilovanie, ktoré by malo právne účinky alebo by vás podobne významne ovplyvňovalo.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>10. Vaše práva</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ako dotknutá osoba máte právo na prístup k svojim údajom, ich opravu, vymazanie („právo na zabudnutie"), obmedzenie spracúvania, právo namietať proti spracúvaniu založenému na oprávnenom záujme, právo na prenosnosť údajov a právo kedykoľvek odvolať udelený súhlas. Svoje práva si môžete uplatniť e-mailom na {{EMAIL}} alebo telefonicky na {{TELEFON}}. Vašu žiadosť vybavíme bez zbytočného odkladu, najneskôr do jedného mesiaca.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>11. Dozorný orgán</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ak sa domnievate, že spracúvanie vašich osobných údajov je v rozpore s právnymi predpismi, máte právo podať návrh na začatie konania, resp. sťažnosť dozornému orgánu, ktorým je <strong>Úrad na ochranu osobných údajov Slovenskej republiky</strong>, Hraničná 12, 820 07 Bratislava 27, www.dataprotection.gov.sk.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><em>Tieto zásady sú účinné od {$datum} a prevádzkovateľ si vyhradzuje právo ich aktualizovať. Aktuálne znenie je vždy dostupné na tejto stránke.</em></p>
<!-- /wp:paragraph -->
HTML;
}

// Vytvorenie stránky, ak ešte neexistuje (obsah je editovateľný v administrácii).
// Ak stránka existuje, ale ešte nebola upravená (obsahuje zástupné {{MENO}}),
// aktualizuje sa na novšie znenie – NEPREPISUJE ručne upravený obsah.
add_action('admin_init', function() {
    $page = get_page_by_path('ochrana-osobnych-udajov');
    if (!$page) {
        wp_insert_post([
            'post_title'   => 'Ochrana osobných údajov',
            'post_name'    => 'ochrana-osobnych-udajov',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => zc_privacy_default_content(),
        ]);
        update_option('zc_privacy_version', ZC_PRIVACY_VERSION);
        return;
    }
    // Bezpečná aktualizácia iba neupraveného vzoru (stále obsahuje placeholder)
    $stored = get_option('zc_privacy_version', '1');
    if (version_compare($stored, ZC_PRIVACY_VERSION, '<')
        && strpos((string) $page->post_content, '{{MENO}}') !== false) {
        wp_update_post([
            'ID'           => $page->ID,
            'post_content' => zc_privacy_default_content(),
        ]);
        update_option('zc_privacy_version', ZC_PRIVACY_VERSION);
    }
});

// Šablóna – jednoduchý čitateľný rámec okolo obsahu
add_filter('template_include', function($template) {
    if (is_page('ochrana-osobnych-udajov')) {
        $custom = get_stylesheet_directory() . '/templates/page-ochrana.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}, 998);
