# Návod — nové funkcie (verzia 3.1)

Balíčky na inštaláciu (Nástenka → Vzhľad/Pluginy → Nahrať ZIP):

| Súbor | Kam |
|-------|-----|
| `zdenka-theme.zip` | Vzhľad → Témy → Pridať novú → Nahrať |
| `property-pro-plugin.zip` | Pluginy → Pridať nový → Nahrať |
| `zc-newsletter.zip` | Pluginy → Pridať nový → Nahrať |
| `zc-reviews.zip` | Pluginy → Pridať nový → Nahrať |

> Inštaluj **každý ZIP zvlášť** (nie jeden veľký). Ak plugin už existuje, WordPress ponúkne „Nahradiť existujúcim“ — potvrď.

---

## Dopyty (CRM) — nové
Formulár „Mám záujem o túto nehnuteľnosť“ teraz každý dopyt **uloží** do panela.

- **Realitný panel → Dopyty** — zoznam všetkých kontaktov.
- Pri každom nastav stav: **Nový → V rokovaní → Uzavreté / Stratené**.
- Interná poznámka (dohodnutá obhliadka, ponúknutá cena…) — uloží sa automaticky.
- Filtre podľa stavu, priame `tel:` a `mailto:` odkazy, prelink na ponuku.
- Formulár má nové pole **e-mail** + skrytú ochranu proti spamu (honeypot).
- Ak návštevník zaškrtne „chcem novinky“, automaticky sa pridá do newslettra.

## Porovnávač ponúk — nové
- Na kartách ponúk je ikona **porovnania** (vedľa srdiečka).
- Dole sa objaví lišta „X/3 na porovnanie“ → tlačidlo **Porovnať**.
- Stránka **/porovnanie/** sa vytvorí automaticky (tabuľka parametrov vedľa seba).

## Import / Export ponúk — nové
**Realitný panel → Import/Export**
- **Export** stiahne všetky ponuky do CSV (otvoríš v Exceli / Google Sheets).
- **Import** nahrá CSV — ponuka s rovnakým názvom sa aktualizuje, nová sa vytvorí.
- Odporúčanie: najprv sprav Export → dostaneš presné stĺpce → doplň → nahraj späť.
- Fotky cez CSV nejdú (tie sa pridávajú v editácii ponuky).

## Poradie fotiek myšou — nové
V editácii ponuky (galéria) teraz **potiahnutím** zmeníš poradie fotiek. Číslo v rohu ukazuje pozíciu.

## Newsletter — otvorenia a kliky — nové
Každé rozposlanie sa meria. **Realitný panel → Newsletter → História**:
- počet a % **otvorení** (neviditeľný pixel),
- počet a % **klikov** (odkazy sa merajú cez presmerovanie).
> Skutočné otvorenia bývajú vyššie — časť e-mailových klientov obrázky blokuje.

## Lokálne SEO stránky — nové
Vlož na ľubovoľnú stránku shortcode:
```
[reality_mesto mesto="Banská Bystrica"]
[reality_mesto mesto="Zvolen" nadpis="Reality vo Zvolene"]
```
Vytvorí H1 + úvodný text + ponuky filtrované podľa lokality (dobré pre Google).

---

# Čo je nové v tejto verzii

## wp-admin má vlastnú „bublinu“ — Web Zdenky
Všetko vlastné je teraz pod jednou položkou **Web Zdenky** hore v ľavom menu
(oddelenej čiarami od bežných vecí WordPressu):

- Nehnuteľnosti · Recenzie · Recenzie z Google · Newsletter · Ebook
- Notifikácie · Stránky a údržba · Náhľad pre klienta · Nástroje · Zabezpečenie (2FA)

**Web Zdenky → Nástroje** je nová stránka pre teba ako webmastera: stav systému
(PHP, HTTPS, trvalé odkazy, limity, WP-Cron, COOKIE_DOMAIN), zoznam vlastných
pluginov s verziami a rýchle akcie — obnoviť trvalé odkazy, vynulovať cache webu,
zmazať dočasné dáta, poslať testovací e-mail.

## Notifikácie z formulárov — už nechodia „všetkým“
**Web Zdenky → Notifikácie** (a to isté aj v Realitnom paneli → Nastavenia).

Doteraz išli správy na administrátorský e-mail webu — teda na každý WordPress účet,
ktorý mal rovnakú adresu. Teraz presne vyberáš:

1. **konkrétne e-mailové adresy** (najspoľahlivejšie, nezávisia od účtov),
2. **roly** — dostanú to všetci s označenou rolou,
3. **konkrétnych používateľov**.

Stránka rovno ukazuje, na aké adresy notifikácie práve chodia. Platí to pre kontaktný
formulár, odhad, ebook aj dopyt na konkrétnu ponuku.

> Odberateľom newslettera sa **nikdy** nič neposiela automaticky — newsletter aj ponuka
> odídu len vtedy, keď na to sám klikneš v paneli.

## Rola „Náhľad webu“
**Web Zdenky → Náhľad pre klienta** → vytvoríš prístup (meno + heslo sa zobrazí raz).

Účet nemá **žiadne** oprávnenia: nevie nič upraviť, nedostane sa do wp-adminu ani do
realitného panelu, nevidí hornú lištu. Vidí len web — aj keď je zapnutý režim údržby.
Ideálne, keď chceš niekomu ukázať rozpracovaný web.

## Podstránka Referencie
Nová stránka **/referencie/** — všetky referencie v mozaike na 3 stĺpce, bez obmedzenia
počtu a s celým textom (nie skráteným). Vytvorí sa sama; je aj v mobilnom menu a v pätičke.

Na úvodnej stránke sa v karuseli točia **všetky** recenzie (predtým len 6) a pod nimi je
tlačidlo *Všetky referencie →*.

Shortcody: `[zc_reviews]`, `[zc_reviews layout="mosaic" clamp="0"]`, `[zc_reviews carousel="1"]`.

## Recenzie v realitnom paneli — prerobené
Otravné okno je preč. Formulár sa otvorí priamo na stránke a **celá správa recenzií
funguje bez JavaScriptu** — takže ju nezhodí žiadny iný skript na stránke.

- Zoznam je v riadkoch cez celú šírku, nie v mriežke.
- Prepínač *Na webe / Skrytá* jedným klikom, šípky na poradie, počet a priemerné hodnotenie.
- Formuláre idú na `admin-post.php`, takže sa odoslané dáta nemôžu cestou stratiť.
- Rovnaké odoslanie sa nespracuje dvakrát — dvojklik ani F5 nepridá recenziu znova.
- Keď sa niečo pokazí, panel napíše presný dôvod. Pod zoznamom je aj rozbaľovacia
  **Diagnostika** (vidí ju len správca) so stavom stránky panela, tabuľky a posledného odoslania.

## Subdoména panela zrušená
Plugin **ZC Panel doména** sme odstránili. Ak máš v `wp-config.php` riadky
`define('COOKIE_DOMAIN', …);` alebo `define('COOKIEPATH', …);`, **zmaž ich** —
spôsobovali zacyklené prihlasovanie. Panel beží normálne na
`zdenkacibulova.sk/realitny-panel/`. Kontrolu nájdeš vo **Web Zdenky → Nástroje**.

## Bezpečnostný denník
**Web Zdenky → Denník** — kto sa kedy prihlásil, z akej IP a čo zmenil.

Zaznamenáva sa: prihlásenie, **neúspešné prihlásenie**, odhlásenie, vytvorenie a
úprava obsahu, presun do koša aj trvalé zmazanie, nahraté súbory, recenzie, zmeny
používateľov a rolí, zapnutie/vypnutie pluginov a zmeny nastavení (stará → nová hodnota).

Pri každom zázname vidíš čas, používateľa, jeho rolu, IP adresu a čoho sa to týkalo.
Dá sa filtrovať podľa udalosti, používateľa aj textu, a stiahnuť do CSV pre Excel.
Záznamy sa **automaticky mažú po 30 dňoch** (dá sa zmeniť v nastavení dole na stránke).

## Denné zálohovanie panela
**Web Zdenky → Zálohy** — každý deň o 3:20 sa uloží celý obsah panela:

- ponuky (vrátane všetkých parametrov a odkazov na fotky),
- formuláre a dopyty,
- recenzie,
- odberatelia newslettera, šablóny a kampane,
- nastavenia pluginov aj vzhľad z Prispôsobiť.

Drží sa **posledných 30 záloh**, staršie sa automaticky mažú (počet sa dá zmeniť).
Zálohy sú v `wp-content/uploads/zc-zalohy/` a sú chránené — stiahnuť sa dajú len
prihlásený cez wp-admin.

Pri obnove si vyberieš, čo presne sa má vrátiť. Ponuky a formuláre sa doplnia a prepíšu
podľa zálohy (nič navyše sa nemaže), recenzie a odberatelia sa nahradia obsahom zálohy.

> Fotky sa do zálohy nekopírujú — ostávajú v Médiách a záloha si na ne drží odkaz.
> Na zálohu celého webu vrátane súborov použi nástroj hostingu alebo UpdraftPlus.

---

## Čo ešte vieme pridať — potrebuje tvoj hosting / API kľúč
Tieto sa nedali dokončiť „na diaľku“, lebo vyžadujú prístup u teba. Keď budeš chcieť, spravíme:

- **Google recenzie** naživo — treba Google Places API kľúč z tvojho Google účtu.
- **AVIF/WebP kompresia fotiek** — závisí od podpory na hostingu (väčšina moderných ju má).
- **Automatická záloha databázy** — ideálne rieši plugin hostingu alebo UpdraftPlus.
- **Dynamický náhľadový obrázok pri zdieľaní** (cena vypálená do fotky) — treba GD/Imagick na serveri.
- **Kešovanie / kritické CSS** — odporúčam plugin od hostingu (napr. LiteSpeed Cache).

Napíš a dorobíme podľa toho, čo tvoj hosting umožňuje.
