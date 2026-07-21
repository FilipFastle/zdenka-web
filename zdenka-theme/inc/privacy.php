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

function zc_privacy_default_content() {
    $rok = date('Y');
    return <<<HTML
<!-- wp:paragraph -->
<p><em>Tento dokument obsahuje zástupné texty v zátvorkách ako {{MENO}}, {{ICO}}, {{SIDLO}}, {{EMAIL}}, {{TELEFON}} a {{WEB}}. Otvorte editor stránky, stlačte Ctrl+F (Nájsť a nahradiť) a nahraďte ich svojimi údajmi. Potom tento odsek zmažte.</em></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>1. Prevádzkovateľ osobných údajov</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Prevádzkovateľom, ktorý spracúva vaše osobné údaje, je {{MENO}}, so sídlom (miestom podnikania) {{SIDLO}}, IČO: {{ICO}}, e-mail: {{EMAIL}}, telefón: {{TELEFON}} (ďalej len „prevádzkovateľ"). Prevádzkovateľ je fyzická osoba – podnikateľ (živnostník) pôsobiaci v oblasti sprostredkovania predaja, kúpy a prenájmu nehnuteľností.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>2. Aké osobné údaje spracúvame</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li>identifikačné a kontaktné údaje: meno a priezvisko, e-mailová adresa, telefónne číslo;</li>
<li>údaje z kontaktného formulára, formulára na odhad nehnuteľnosti a dopytu k ponuke: obsah vašej správy, údaje o nehnuteľnosti, ktoré nám poskytnete;</li>
<li>údaje pri odbere noviniek (newsletter): e-mailová adresa, prípadne meno;</li>
<li>technické údaje: IP adresa, typ prehliadača a údaje z cookies (pozri bod 6).</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>3. Účely a právny základ spracúvania</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li><strong>Vybavenie dopytu a komunikácia</strong> – na základe vykonania opatrení pred uzatvorením zmluvy a nášho oprávneného záujmu odpovedať na vaše otázky (čl. 6 ods. 1 písm. b) a f) GDPR).</li>
<li><strong>Sprostredkovanie predaja/kúpy/prenájmu nehnuteľnosti</strong> – na základe zmluvy, resp. predzmluvných vzťahov (čl. 6 ods. 1 písm. b) GDPR).</li>
<li><strong>Zasielanie noviniek a ponúk (newsletter)</strong> – na základe vášho súhlasu (čl. 6 ods. 1 písm. a) GDPR), ktorý môžete kedykoľvek odvolať.</li>
<li><strong>Plnenie zákonných povinností</strong> – najmä účtovných a daňových (čl. 6 ods. 1 písm. c) GDPR).</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>4. Doba uchovávania</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Osobné údaje uchovávame len po dobu nevyhnutnú na dosiahnutie účelu: údaje z dopytov po dobu vybavenia a následne max. 12 mesiacov; údaje na newsletter do odvolania súhlasu; údaje potrebné pre zmluvné a zákonné povinnosti po dobu stanovenú príslušnými právnymi predpismi (napr. účtovné doklady 10 rokov).</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>5. Príjemcovia a sprostredkovatelia</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Vaše údaje môžu byť poskytnuté spoľahlivým sprostredkovateľom, ktorí pre nás zabezpečujú služby: poskytovateľ webhostingu, poskytovateľ e-mailových služieb, účtovník, prípadne realitné portály pri inzercii nehnuteľnosti. Všetci sú viazaní mlčanlivosťou a zmluvou o spracúvaní osobných údajov. Údaje neposkytujeme do tretích krajín mimo EÚ.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>6. Cookies</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Web {{WEB}} používa nevyhnutné cookies pre svoje fungovanie a môže používať analytické cookies na meranie návštevnosti. Používanie cookies môžete kedykoľvek obmedziť v nastaveniach svojho prehliadača.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>7. Vaše práva</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ako dotknutá osoba máte právo na prístup k svojim údajom, ich opravu, vymazanie, obmedzenie spracúvania, právo namietať proti spracúvaniu, právo na prenosnosť údajov a právo kedykoľvek odvolať súhlas. Svoje práva si môžete uplatniť e-mailom na {{EMAIL}} alebo telefonicky na {{TELEFON}}.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>8. Odber noviniek (newsletter)</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ak ste sa prihlásili na odber noviniek, spracúvame vašu e-mailovú adresu na základe súhlasu. Z odberu sa môžete kedykoľvek odhlásiť kliknutím na odkaz „Odhlásiť sa" v pätičke každého e-mailu alebo napísaním na {{EMAIL}}.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>9. Dozorný orgán</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ak sa domnievate, že spracúvanie vašich osobných údajov je v rozpore s právnymi predpismi, máte právo podať sťažnosť dozornému orgánu, ktorým je Úrad na ochranu osobných údajov Slovenskej republiky, Hraničná 12, 820 07 Bratislava, www.dataprotection.gov.sk.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><em>Tieto zásady sú účinné od {$rok} a prevádzkovateľ si vyhradzuje právo ich aktualizovať.</em></p>
<!-- /wp:paragraph -->
HTML;
}

// Vytvorenie stránky, ak ešte neexistuje (obsah je editovateľný v administrácii)
add_action('admin_init', function() {
    if (get_page_by_path('ochrana-osobnych-udajov')) return;
    wp_insert_post([
        'post_title'   => 'Ochrana osobných údajov',
        'post_name'    => 'ochrana-osobnych-udajov',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => zc_privacy_default_content(),
    ]);
});

// Šablóna – jednoduchý čitateľný rámec okolo obsahu
add_filter('template_include', function($template) {
    if (is_page('ochrana-osobnych-udajov')) {
        $custom = get_stylesheet_directory() . '/templates/page-ochrana.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
}, 998);
