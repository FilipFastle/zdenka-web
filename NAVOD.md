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
- Odkaz **„Už odoberáte? Upravte si témy alebo sa odhláste"** — človek zadá e-mail
  a pošleme mu odkaz na stránku, kde si témy zmení alebo sa odhlási.
  Kvôli súkromiu odpovedáme rovnako, aj keď adresu v databáze nemáme.
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
