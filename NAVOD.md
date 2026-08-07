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

### Vrátenie zmeny — do 7 dní
Pri každej zmene sa uloží aj to, **ako obsah vyzeral predtým**. V poslednom stĺpci denníka
je preto tlačidlo **Vrátiť**, ktoré zmenu vezme späť:

- **natrvalo zmazaná ponuka** sa obnoví aj s pôvodným ID, všetkými parametrami a fotkami,
- **prepísaná ponuka** sa vráti do stavu pred úpravou (aj metadáta, ktoré medzitým pribudli, sa odstránia),
- **zmazaná alebo skrytá recenzia** sa vráti,
- **zmenené nastavenie** sa vráti na pôvodnú hodnotu,
- **zmenená rola používateľa** sa vráti späť.

Lehota je **7 dní** a dá sa zmeniť dole v nastavení. Po jej uplynutí sa uložené stavy zahodia
(záznam v denníku ostane, len sa už nedá vrátiť) — databáza tak zbytočne nerastie.
Zaškrtávatko **Len vrátiteľné** vyfiltruje zmeny, ktoré sa ešte dajú vziať späť.
Vrátenie sa samo zapíše do denníka, takže je vidieť aj to, kto čo vrátil.

> Vytvorenie nového obsahu sa nevracia — na to slúži bežné zmazanie.

## Rýchlosť webu
Po väčšej optimalizácii je vo **Web Zdenky → Nástroje** pár vecí, o ktorých je dobré vedieť.

**Po každej inštalácii novej témy spusti „Prepočítať veľkosti fotiek."**
Bez toho nové (menšie a ostrejšie) verzie fotiek neexistujú a web siahne po origináli.
Beží po dávkach, klikaj kým nenapíše „hotovo".

| Tlačidlo | Čo robí |
|----------|---------|
| Prepočítať veľkosti fotiek | dorobí k starším fotkám WebP a veľkosti 1440/2048 px |
| Zapnúť/vypnúť WebP zmenšeniny | WebP je asi o tretinu menší než JPEG pri rovnakej kvalite |
| Vypnúť zmenšené štýly | keby po aktualizácii čokoľvek vyzeralo divne, web hneď beží na pôvodnom CSS |

Čo najviac ovplyvňuje rýchlosť na mobile, v poradí:
1. **veľkosť hero fotky** – je cez celú obrazovku, takže je to najväčší súbor na stránke,
2. cudzie skripty (CookieYes, Správca značiek) – tie web nevie ovplyvniť,
3. všetko ostatné je už zoptimalizované.

> Ak nahrávaš hero fotku, netreba 6000 px z fotoaparátu. Úplne stačí **2500 px na dlhšej
> strane** – web si z nej aj tak spraví menšie verzie a originál len zaberá miesto.

## Priečinky v Médiách
WordPress priečinky nepozná — všetko hádže do jednej kopy podľa dátumu. Preto sme ich dorobili.

**Fotky ponúk sa triedia samy.** Pri **úprave existujúcej ponuky** dostane fotka priečinok
`Ponuky → Názov ponuky` **hneď pri nahratí** — nemusíš čakať na uloženie. Pri **novej ponuke**
sa fotky zaradia vo chvíli, keď ju prvýkrát uložíš (dovtedy ponuka ešte nemá ani názov).
Platí to rovnako pre panel aj wp-admin.

Keď ponuku premenuješ, premenuje sa aj jej priečinok — nevznikne druhý s tými istými fotkami.

Kde to použiješ:
- **Médiá → Priečinky** — vytváranie a premenovanie, aj vnorené priečinky.
- **Médiá → Knižnica** — nový rozbaľovací zoznam *Všetky priečinky* na filtrovanie
  a stĺpec *Priečinky*. Cez hromadné akcie presunieš naraz viac fotiek.
- **V okne na výber fotky** (v paneli aj v editore ponuky) je v hornom rozbaľovacom
  zozname vidieť priečinky s ikonou 📁 — pri desiatkach ponúk to šetrí najviac času.

> Fotka môže byť vo viacerých priečinkoch naraz a priečinky nič nepresúvajú na disku —
> ide o označenie, takže sa nemôže stať, že sa niekde stratí odkaz na súbor.

## Newsletter — kategórie a pridávanie kontaktov

### Kategórie záujmu (dá sa vybrať aj viac naraz)
| Skupina | Možnosti |
|---|---|
| Nehnuteľnosti | 1-izbový byt · 2-izbový byt · 3+-izbový byt · Dom · Pozemok |
| Ostatné | Ebook a materiály zdarma · Realitné tipy a novinky |

**Nič nevybrané = dostáva všetko.** Kontakt môže mať naraz aj viac kategórií
(napr. *Dom + Pozemok + Realitné tipy*) — v databáze sú uložené vedľa seba
a dajú sa kedykoľvek zmeniť priamo v riadku zoznamu.

Kto si nevyberie žiadnu nehnuteľnosť (len ebook alebo tipy), **ponuky mu chodiť
nebudú** — tlačidlo *Poslať odberateľom* pri ponuke ho automaticky vynechá.

### Výber kategórií
**Pravidlo, ktoré platí všade:** nič neoznačené = **všetko**, všetko označené = **tiež
všetko**, čokoľvek medzi = presne to, čo je označené. Preto nemôže vzniknúť stav
„mám vybraté všetky kategórie, a predsa mi to nič nenašlo".

Všade, kde sa kategórie vyberajú (panel aj wp-admin), je **rozbaľovacie okienko
so zaškrtávacími políčkami** — žiadne Ctrl+klik. V tlačidle vidíš, čo je vybraté,
a v okienku sú tlačidlá *Označiť všetko* / *Zrušiť výber*.

Pri odosielaní newslettera si najprv zvolíš rozsah:

| Možnosť | Komu odíde |
|---|---|
| Všetci aktívni odberatelia | všetkým |
| Všetci so záujmom o ponuky | vynechá tých, čo ponuky nechcú |
| Vybrané kategórie… | otvorí sa výber kategórií (viac naraz) |
| **Vybraní ľudia…** | **zoznam odberateľov s vyhľadávaním – zaškrtneš konkrétne mená** |

Počet príjemcov sa priebežne prepočítava. Pri *Vybraní ľudia* môžeš hľadať podľa mena
aj e-mailu a tlačidlom *Označiť zobrazených* naraz označiť všetkých, čo prešli hľadaním.
Rovnaký výber je aj v okne pri tlačidle *Poslať odberateľom newslettera* pri ponuke.

### Vlastné kategórie
**Web Zdenky → Newsletter → Kategórie.** Pridáš, premenuješ alebo zmažeš čokoľvek.

- **Názov** — čo uvidí návštevník.
- **Skupina** — nadpis, pod ktorý sa možnosť zaradí (napr. *Nehnuteľnosti*, *Ostatné*).
- **Ponuka** — zaškrtni pri kategóriách nehnuteľností. Kontaktu, ktorý nemá ani jednu
  takú, sa ponuky neposielajú.
- **Kontaktov** — koľko ľudí danú kategóriu má; uvidíš, čoho sa zmena dotkne.

Kategóriu zmažeš tak, že vymažeš jej názov a uložíš. Kontaktom, ktorí ju mali,
ostane v databáze — len sa už nikde neponúka. *Vrátiť predvolené* obnoví pôvodných sedem.

Zmena sa hneď premietne všade: formulár na stránke Newsletter, e-mail s výberom tém,
filtre v paneli aj vo wp-admine, aj okno pri odosielaní ponuky.

### Stránka /newsletter/
- Kategórie sú **zaškrtávacie políčka** — dá sa označiť viac naraz, nič neoznačené = všetko.
- Pod formulárom je **zvýraznený pás** *„Už u nás odoberáte novinky?"* s tlačidlom
  **Zmeniť si témy**. Otvorí okno priamo na stránke: najprv e-mail, potom výber tém.
  V okne je aj *Odhlásiť sa zo všetkého*.
  Ak adresu v odbere nemáme, napíše sa to a odkážeme na formulár vyššie.
  Skúšanie adries je obmedzené na 12 pokusov za 10 minút z jednej IP.
- Newsletter je aj v hlavnom menu (na PC aj v mobile).

### Ako si kategórie vyberá návštevník
Vo formulári sa naň nepýtame, aby nezdržiaval. Postup je takýto:

1. Zaškrtne si newsletter a odošle formulár.
2. Príde mu e-mail **„Žiadosť o prihlásenie na odber"** — v bodoch je v ňom
   napísané, čo mu budeme posielať.
3. Klikne na **Prihlásiť sa a vybrať si témy** → otvorí sa stránka, kde si
   zaškrtne, čo ho zaujíma (aj viac naraz). Nič nezaškrtnuté = posielame všetko.

Kto si stiahne **ebook**, zaradí sa automaticky do *Všetko*.

### Komu poslať konkrétnu ponuku
Pri tlačidle **„Poslať odberateľom newslettera"** sa otvorí okno s výberom:

- **Vybrané skupiny** — zaškrtneš kategórie (napr. len *Dom* a *Pozemok*),
- **Vybraní ľudia** — zoznam odberateľov s vyhľadávaním, zaškrtneš konkrétne mená,
- **Všetci so záujmom o ponuky**.

Kontakty, ktoré ponuky nechcú, sa vynechajú v každom prípade.

### Pridávanie kontaktov
Rovnaké možnosti sú v **realitnom paneli** aj vo **wp-admine**
(*Web Zdenky → Newsletter → Pridať kontakty*):

- **Po jednom** — meno, e-mail, kategórie, spôsob pridania.
- **Viac naraz** — jeden človek na riadok vo formáte `Meno;Priezvisko;e-mail`
  (funguje aj CSV s čiarkou/tabulátorom, aj samotný e-mail). Naraz max. 500 riadkov.

Pri oboch si vyberáš spôsob:
- **Pridať priamo, bez potvrdenia** (predvolené) — kontakt je hneď aktívny
  a **nedostane žiadny e-mail**,
- **Poslať potvrdzovací e-mail** — človek si prihlásenie potvrdí sám a rovno si
  vyberie témy.

Rovnaká adresa sa nikdy nevytvorí druhýkrát — existujúci záznam sa aktualizuje
a zdroje sa zlúčia, takže je vždy vidieť, odkiaľ kontakt prišiel.

### Vizitka v e-maile
Pri e-maile s ponukou bola vizitka dvakrát — raz z tela e-mailu a raz z pätičky.
Teraz platí: keď má šablóna zapnuté *„Pridať na koniec kontakt na makléra"*,
pätičková vizitka sa vynechá. Prepínač je v *Newsletter → Šablóna novej ponuky*.

## Texty stránok — meniť sa dajú sám
**Web Zdenky → Texty stránok.** Nadpisy, popisy a tlačidlá na stránkach
*Úvod*, *O mne* a *Ako pracujem* sú tu ako obyčajné polia.

- **Prázdne pole = ostáva pôvodný text.** Keď políčko vymažeš, vráti sa presne to,
  čo tam bolo — pokaziť sa nedá nič.
- Zmenené polia sú označené štítkom *zmenené*, takže hneď vidíš, čoho si sa dotkol.
- Tlačidlo **Vrátiť všetko na pôvodné** obnoví celý web naraz.
- Povolené sú jednoduché značky `<br>`, `<strong>`, `<em>` a odkaz `<a href="">`.
  Zvyšok sa odstráni, aby sa nedal rozbiť vzhľad.

### A čo Breakdance?
Tieto stránky majú dizajn napísaný v kóde, takže ich Breakdance neotvorí ako hotovú
skladačku — zobrazil by prázdne plátno. Preto ti fungovali len novo vytvorené stránky.

Ak niektorú stránku chceš mať **celú v Breakdance**, otvor ju v ňom, postav ju a ulož.
Téma sa vtedy sama odsunie a zobrazí tvoju verziu (platí aj pre existujúce stránky).
Rátaj ale s tým, že pôvodný vzhľad tej stránky tým nahradíš. Realitný panel a detail
ponuky si Breakdance nikdy neprevezme — tie musia ostať funkčné.

Na bežnú zmenu textu stačí obrazovka *Texty stránok*.

## Formuláre v paneli — hľadanie
Vyhľadávanie prejde **meno, e-mail, telefón, správu aj internú poznámku**.
Predtým hľadalo len v názve záznamu, takže podľa e-mailu ani telefónu nič nenašlo —
tie sú uložené v doplnkových poliach, kam sa bežné hľadanie WordPressu nepozerá.

## Site Kit pre maklérku
Rola *Realitný maklér* má vo wp-admine sprístupnenú **jedinú položku — Google
Site Kit**, a to len na čítanie. Ostatné obrazovky wp-adminu ju presmerujú späť.
Nastavovanie Site Kitu, pripájanie účtov ani správa modulov v tom nie sú.

> **Musíš to ešte raz povoliť v samotnom Site Kite.** Ten si prístup stráži sám
> a inej role dáta neukáže, kým to nezapneš:
> **Site Kit → Settings → Dashboard sharing** → pri každom module zaškrtni rolu
> *Realitný maklér*. Bez toho jej Site Kit ukáže prázdno alebo hlášku o prístupe —
> a to už neovplyvní žiadne nastavenie z našej strany.

## Aktualizácia nahratím .zip (a hláška „Priečinok už existuje")
Od témy **3.36.3** stačí nahrať `.zip` cez **Pluginy → Pridať nový → Nahrať
plugin** a dať *Nainštalovať*. Starú verziu prepíše, netreba nič mazať vopred.

Prečo to predtým nešlo: WordPress odmieta prepísať priečinok, ktorý už existuje.
Obrazovku *„Nahradiť aktuálnu verziu nahranou"* vie ponúknuť len vtedy, keď ten
priečinok rozpozná ako nainštalovaný plugin. Keď v ňom ostalo torzo po predošlej
inštalácii (napríklad po balíku s iným názvom priečinka), WordPress ho nepozná
a skončí hláškou **„Priečinok už existuje"**, hoci ide o ten istý plugin.

Téma preto inštalátoru pri **našich** balíkoch (`zdenka-theme`,
`property-pro-plugin`, `zc-newsletter`, `zc-reviews`, `zc-ebook`, `zc-2fa`,
`zc-installer`) povie, nech starý priečinok pred rozbalením zmaže — presne to
isté, čo robí WordPress sám po kliknutí na *Nahradiť*. Cudzích pluginov
a aktualizácií z wordpress.org sa to netýka.

> **Odberatelia, recenzie, ponuky ani dopyty sa tým nestratia.** Žiadny z týchto
> pluginov nemá odinštalačný skript — dáta žijú v databáze webu a výmena
> priečinka sa ich nedotkne.

**Keby to predsa len ešte raz vypísalo hlášku** (napr. kým nemáš novú tému):
**Pluginy** → pri danom plugine *Deaktivovať* → *Zmazať* → nahrať `.zip` znova.
Alebo cez FTP zmazať priečinok v `wp-content/plugins/`.

## DÔLEŽITÉ pri inštalácii newslettera
Balík newslettera musí byť v priečinku **`zc-newsletter`**. Keby ho niekedy dostaneš
pod iným názvom (napr. `zc-newsletter-plugin`), WordPress ho nainštaluje ako
**druhý, samostatný plugin** vedľa pôvodného. Potom buď bežia dva naraz (a web
spadne na dvakrát definovanej funkcii), alebo ostane aktívna stará verzia a
Newsletter v paneli prestane fungovať.

Ako to skontrolovať: **Pluginy** → v zozname smie byť *ZC Newsletter* len raz.
Ak sú tam dva, deaktivuj a zmaž ten starý.

Rovnako to platí pre všetky ostatné balíky — priečinky sú `zdenka-theme`,
`property-pro-plugin`, `zc-newsletter`, `zc-reviews`, `zc-ebook`, `zc-2fa`.

> Pluginy patria k sebe. Keď aktualizuješ jeden, nahraj radšej všetky —
> panel používa funkcie z newslettera aj z recenzií a naopak.
> Keby predsa len ostal starý newsletter, panel to už nezhodí:
> namiesto bielej stránky napíše, že treba plugin aktualizovať.

## Kontakt na ponuke — Short vedľa formulára
Keď do ponuky vložíš **YouTube Short** (adresa s `/shorts/`), zobrazí sa **vpravo vedľa
kontaktného formulára**, nie pod ním. Kontakt je vľavo, Short vpravo, medzi nimi tenká linka.

- **Bežné širokouhlé video (16:9)** ostáva **pod kontaktom** cez celú šírku ako doteraz —
  na šírku by vedľa formulára aj tak nevyzeralo dobre.
- **Žiadne video** → formulár je cez celú šírku, presne ako predtým.
- **Na mobile (do 720 px)** ide Short pod formulár, aby ostal dosť veľký.

Video sa vkladá tam, kde vždy: *panel → záložka Médiá → YouTube / Vimeo URL*,
vo wp-admin box *Fotky & Video*.

### Vlastný doplnok (shortcode) vedľa kontaktu
Do toho istého pravého stĺpca sa dá dať aj vlastný obsah — pole
**Kontakt – doplnok vedľa formulára**:

- v paneli: *Upraviť ponuku → záložka Detaily*, úplne dole,
- vo wp-admin: box *Kontakt – doplnok vedľa formulára* (pod Vybavením).

Vložíš doň shortcode (napr. `[property_carousel limit="3"]`) alebo obyčajný text.
Keď je vyplnené aj video aj doplnok, zobrazia sa v pravom stĺpci pod sebou.
Prázdne pole nič nemení.

Obe nastavenia sú pri každej ponuke zvlášť, prenesú sa pri *Duplikovať*
a sú súčasťou denných záloh.

## Prihlásenie na newsletter je okamžité

Potvrdzovací krok (double opt-in) je zrušený. Kto vyplní formulár, je **hneď
aktívny odberateľ a nastavený na všetko**. Ak si vo formulári niečo označí,
dostane presne to.

Uvítací e-mail už nič nepotvrdzuje. Namiesto toho hovorí, čo bude chodiť, a dáva
dve možnosti:

1. tlačidlo **Zmeniť si témy** — otvorí výber kategórií,
2. pod ním odkaz **odhláste sa jedným klikom** — ide na medzikrok, ktorý najprv
   ponúkne úpravu tém a až potom odhlásenie.

Platí to pre nové adresy, pre staré nepotvrdené (`pending`) aj pre tých, čo sa
kedysi odhlásili — všetci sa prihlásia rovno. Token sa pritom vždy obnoví, aby
starý odkaz z e-mailu nikoho nevedel prihlásiť späť bez jeho vedomia.

**Čo tým strácame — vedz o tom:** bez potvrdenia môže ktokoľvek zadať cudziu
adresu a tá začne dostávať e-maily. Podľa GDPR je to slabší doklad o súhlase
a Gmail či Seznam za to vedia zhoršiť doručovanie. Preto je odhlásenie v e-maile
hneď pod tlačidlom, viditeľné, s vetou „Neprihlasovali ste sa vy?".

Vo wp-admine a v realitnom paneli ostáva pri ručnom pridávaní kontaktu voľba
**Poslať potvrdzovací e-mail** — tá funguje ďalej, keď ju maklérka zámerne zvolí.

**Čo aktualizovať:** `zc-newsletter.zip` (1.21.0).

## Číslice v serifovom písme — vyriešené natrvalo

Playfair Display má **predvolene textové (staroštýlové) číslice**: „1" siaha len
po výšku malého „x", „3" a „4" idú pod účiaru. Preto boli číslice sádzané
bezpätkovým DM Sans — a to sa maklérke nepáčilo.

Zistil som skutočnú príčinu. Font vie aj vysoké (lining) číslice cez OpenType
funkciu `lnum`, ale **náš skrátený súbor písma ju mal odstrihnutú** — obsahoval
len `calt`, `kern` a `liga`. Preto `font-variant-numeric: lining-nums` v CSS
nerobilo nič a jediná cesta bola náhradné písmo.

Teraz sú v téme dva nové drobné súbory, `PlayfairDisplay-num.woff2` (2,6 kB)
a kurzívna verzia (2,9 kB). Obsahujú **výlučne číslice 0–9, už v lining podobe**.
Cez `unicode-range` ich prehliadač použije len na číslice, zvyšok textu berie
z pôvodného súboru.

| | predtým | teraz |
|---|---|---|
| Písmo číslic | DM Sans (bezpätkové) | Playfair Display (pätkové) |
| Výška číslic | rôzna, „1" po x-výšku | všetky po výšku veľkých písmen |
| Ako to fungovalo | JavaScript prechádzal celý DOM a každé číslo obaľoval do `<span>` | čisto písmo, žiadny zásah do stránky |
| Navyše dáta | 0 kB | 5,5 kB (raz, potom z cache) |

Zmizol tým aj celý `TreeWalker` z `main.js` — stránka sa už po načítaní
neprepisuje, čo je rýchlejšie a nerobí to preblikávanie.

## Opravy hlásené 7. 8.

**Ikonky sietí sa na hover strácali.** Pravidlá pre priehľadnú hlavičku a hover
mali vyššiu špecificitu než brand farby, takže Instagram bol hore sivý (farbu
dostal až po odrolovaní) a pri prejdení myšou sa symbol stratil — biela ikona
na bielom pozadí. Farba siete je teraz v premennej `--soc` a všetky stavy z nej
čerpajú. Hover farbu nemení, len ikonu rozžiari a pridá biely prstenec.

**Mobilné menu sa nedalo hneď znova otvoriť.** Prekryv po zatvorení ešte asi
1,1 sekundy neviditeľne stál nad hamburgerom a kliky do neho nešli. Doplnil som
`pointer-events:none` pre neotvorený stav a zrušenie dobiehajúceho časovača.
Otestované na piatich rôznych oneskoreniach (0 – 560 ms) — otvorí sa vždy.

**Newsletter formulár na mobile.** Pravidlo `width:100%` platilo aj pre
checkboxy, takže z políčka kategórie bol obdĺžnik cez celý formulár a text
vedľa neho vytekal von. Checkboxy sú z pravidla vyňaté a text v štítkoch sa
zalamuje.

**Okno po prihlásení na newsletter.** Najprv je potvrdenie (zelená fajka,
„Odber máte zapnutý“) a až pod ním nepovinný výber tém. Tlačidlá sú v pevnej
päte, takže na nižšom telefóne roluje len zoznam možností — predtým bolo
tlačidlo pod okrajom.

**Medzikrok pri odhlásení sa dal zbytočne rolovať.** `100vh` na mobile počíta
so skrytou lištou prehliadača. Doplnené `100dvh`, ktoré meria skutočne
viditeľnú výšku.

**Carousel referencií.** Šípky mali od karty 3 px, teraz 19 px. Karta sa pri
prejdení myšou nadvihne o 3 px a `overflow:hidden` jej odrezával horný rámik
aj tieň — obal má teraz zvislé odsadenie 12 px.

**Čo aktualizovať:** `zdenka-theme.zip` (3.41.0), `zc-newsletter.zip` (1.20.0),
`zc-reviews.zip` (1.6.3).

## Menu, fotky a odhlásenie — najnovšie zmeny

**Menu (PC aj mobil, rovnaké poradie)**

`Domov · Ponuky · O mne · Ako pracujem · Odhad ZDARMA · Referencie · Newsletter · Ebook · Kontakt`

„Odhad" je teraz všade „Odhad ZDARMA". Ebook sa ako vždy zobrazí len keď je zapnutý.

Pribudla jedna dôležitá vec: menu má 9 položiek a k tomu ikony sietí.
Odmeral som, že celé sa to zmestí až od **1280 px** — pod tým sa meno v logu
lámalo do troch riadkov a ikonky padali pod seba. Hranica hamburgeru sa preto
posunula z 1180 na **1280 px**. Na bežnom notebooku (1366 px) je PC menu, na
menšom okne hamburger.

**Farebné ikonky sietí — len v navbare**

Facebook modrá, Instagram gradient, YouTube červená, LinkedIn a Google modrá,
symbol biely. V **pätičke a v kontaktnej vizitke ostávajú zlaté** — tam by to
bolo príliš pestré.

**Sekcia „Čo ma riadi pri práci"**

Je vypnutá, takže po hero fotke idú **rovno nehnuteľnosti**. Zapnúť sa dá späť
v `Vzhľad → Prispôsobiť → Úvodná stránka – sekcie`.

**Fotka v O mne je samostatná**

Doteraz brala rovnaký portrét ako hero na mobile — zmena jednej menila druhú.
Teraz má v `Prispôsobiť → Fotky maklérky` **vlastné pole „Fotka do sekcie O mne"**.
Keď ho necháš prázdne, použije sa portrét ako doteraz. Novú fotku si teda vieš
nahrať sama a hero sa nezmení.

**Referencie = čistý carousel**

Podstránka `/referencie` už nie je stránkovaná mriežka, ale carousel:
3 karty na PC, 2 na tablete, 1 na mobile, so šípkami a bodkami.

**Odhlásenie z newslettera cez medzikrok**

Kliknutie na „Odhlásiť sa" v e-maile už neodhlási hneď. Najprv sa otvorí
stránka *„Škoda, že odchádzate"* s výberom tém a tlačidlom **Uložiť výber
a zostať**. Až pod tým je **Nie, ďakujem – odhláste ma úplne**.

Jednoklikové odhlásenie z Gmailu či Apple Mail (tlačidlo priamo v poštovom
klientovi) funguje ďalej okamžite — vyžaduje to štandard RFC 8058 a nesmie sa
naň nič pýtať.

Pás „Zmeniť si témy" pod formulárom na `/newsletter` je preč — zmena tém ide
odteraz cez odkaz v e-maile.

**Čo aktualizovať:** `zdenka-theme.zip` (3.40.0), `zc-newsletter.zip` (1.19.0),
`zc-reviews.zip` (1.6.2).

## Mobil a priblíženie — opravené tvary a menu

Téma dávala na mobile **každému** tlačidlu `min-height: 44px` (dotykový cieľ).
Tým sa ale rozbili prvky, ktoré majú vlastný pevný rozmer:

| Prvok | Ako to vyzeralo | Teraz |
|---|---|---|
| Srdiečko na kartách ponúk | 38 × 44 px → **ovál** | kruh 38 × 38 px |
| Kolieska pri referenciách | fotka stlačená do oválu | kruh, fotka orezaná `object-fit: cover` |
| Bodky pod carouselom recenzií | 7 × 44 px → **paličky** | bodky 7 × 7 px |
| „Zobraziť celé" pod recenziou | 44 px vysoká diera | normálny odkaz |
| Ikona hamburgeru | prilepená hore, mimo stredu | presne v strede hlavičky |

Tlačidlá s vlastným rozmerom sú teraz z pravidla vyňaté, dotykový cieľ si nesú samy.

**Priblíženie na PC (100 % – 300 %)**

Pri 175 % má bežný monitor len ~617 px výšky a menu vtedy schovávalo logo „ZC".
Namiesto skrývania sa teraz logo aj odkazy **plynulo zmenšujú**:

- do 700 px výšky — logo 58 px
- do 560 px výšky — logo 42 px, menšie písmo odkazov, menší krížik
- pod 380 px výšky — logo a čiarka zmiznú, ostanú len odkazy

Menu má navyše poistku: ak by sa aj tak nezmestilo, dá sa v ňom posúvať —
nikdy sa nestane, že by položka bola neviditeľná a nedostupná.

Hlavička sa na úzkom okne (aj 300 % priblíženie, ~640 px šírky) zmenšuje tiež:
logo 38 → 32 → 28 px, meno 15 → 13,5 → 12,5 px. Nikdy sa nezalomí ani nezmizne.

**Čo aktualizovať:** `zdenka-theme.zip` (3.39.0) a `zc-reviews.zip` (1.6.2).

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
