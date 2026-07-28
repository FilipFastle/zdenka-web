# SEO, miestne vyhľadávanie a Google nástroje

Návod pre web **zdenkacibulova.sk**. Písané tak, aby sa dalo ísť zhora nadol a
odškrtávať. Na konci je mesačná rutina, ktorá zaberie 20 minút.

Realitná maklérka nesúťaží o slovo „reality" — súťaží o *„realitná maklérka Banská
Bystrica"*, *„predaj domu Zvolen"* a o to, aby ju Google ukázal v mapovom boxe, keď
niekto hľadá z okolia. Tomu je celý návod podriadený.

---

## 0. Čo už web robí sám

Toto nemusíš riešiť, je to v téme a pluginoch:

- `<title>` a popis pre každú stránku, kanonické adresy
- OG a Twitter značky (náhľad pri zdieľaní na Facebooku a v správach)
- štruktúrované dáta `RealEstateAgent` pre celý web
- štruktúrované dáta `Product` s cenou a dostupnosťou na detaile každej ponuky
- sitemapa od WordPressu na `/wp-sitemap.xml`
- rýchle načítanie: WebP, lazy-loading, self-hostované písma, predlaadenie hero fotky
- mobilná verzia a PWA

**Čo musíš doplniť ty:** *Vzhľad → Prispôsobiť → **Miestne SEO (Google)***
Adresa, PSČ, kraj, súradnice, otváracie hodiny, mestá kde pôsobíš a overovací kód
Search Console. Bez adresy a súradníc Google web ťažšie priradí k miestu.

> Súradnice získaš tak, že v Google Mapách klikneš pravým tlačidlom na miesto —
> prvý riadok v ponuke sú dve čísla. Prvé je šírka, druhé dĺžka.

---

## 1. Google Business Profile — najdôležitejšia vec

Pre miestne podnikanie je firemný profil **dôležitejší než samotný web**. Mapový box
je nad organickými výsledkami a klikne naň väčšina ľudí.

### Založenie
1. [business.google.com](https://business.google.com) → prihlás sa Google účtom, ktorý
   budeš dlhodobo používať (nie súkromný, ktorý raz zrušíš).
2. Názov firmy: **presne** ako v skutočnosti — `Mgr. Zdenka Cibuľová – realitná maklérka`.
   Nepchaj tam kľúčové slová navyše, Google za to vypína profily.
3. Kategória: **Realitný agent** (primárna). Sekundárne môžeš pridať *Realitná kancelária*.
4. Ak nemáš kanceláriu s pultom, zvoľ **oblasť služieb** namiesto adresy — nastav
   Banskú Bystricu, Zvolen, Brezno a okolité obce.
5. Overenie: pohľadnicou, telefónom alebo videom. Video je dnes najčastejšie —
   natočíš jeden neprerušený záber: okolie, vstup, dokumenty, teba pri práci.

### Vyplň úplne všetko
Profily so 100 % vyplnenosťou dostávajú výrazne viac zobrazení:

- popis (750 znakov) — píš pre človeka, nie pre robota
- telefón, web (`https://www.zdenkacibulova.sk`), rezervačný odkaz → daj `/kontakt/`
- otváracie hodiny **a sviatky**
- služby: *predaj nehnuteľnosti, prenájom, odhad ceny, hypotekárne poradenstvo, …*
- atribúty: *online konzultácie*, *vlastníčka je žena*
- **fotky**: logo, titulná, minimálne 10 fotiek — ty pri práci, obhliadky, odovzdanie
  kľúčov. Reálne fotky, nie stock. Pridávaj nové aspoň raz mesačne.

### NAP musí sedieť na znak
**N**ázov, **A**dresa, **P**hone. Rovnaký tvar na webe, v profile, na Facebooku aj
v katalógoch. `+421 907 579 742` všade, nie raz `0907 579 742` a inde s medzerami inak.
Nezhody Googlu kazia dôveru v údaje.

### Recenzie
Najsilnejší faktor miestneho poradia hneď po vzdialenosti.

- Po každom uzavretom obchode pošli klientovi odkaz na hodnotenie (v profile:
  *Požiadať o recenzie* → krátky odkaz `g.page/r/…`).
- **Odpovedz na každú recenziu**, aj na zlú. Odpoveď vidia ďalší.
- Recenzie z Google vieš ťahať aj na web — *Web Zdenky → Recenzie z Google*
  (treba Places API kľúč, viď časť 3).
- Nikdy si recenzie nekupuj. Google ich vie odhaliť a profil zablokuje.

### Príspevky
Raz týždenne krátky príspevok — nová ponuka, predané, tip pre predávajúcich.
Trvá to päť minút a drží profil „živý".

---

## 2. Google Search Console

Ukazuje, na aké slová ťa ľudia nachádzajú a čo je pokazené. **Bez nej si slepý.**

1. [search.google.com/search-console](https://search.google.com/search-console) →
   *Pridať zdroj* → **Predpona URL**: `https://www.zdenkacibulova.sk`
2. Overenie → **Značka HTML**. Skopíruj len hodnotu z `content="…"` a vlož ju do
   *Prispôsobiť → Miestne SEO → Overovací kód Search Console*. Ulož, potom v Google
   klikni *Overiť*.
3. *Sitemaps* → pridaj `wp-sitemap.xml` → *Odoslať*.
4. Ak máš doménu aj bez `www`, pridaj ju ako druhý zdroj a nechaj presmerovanie na `www`.

### Čo v nej sledovať
| Kde | Čo hľadáš |
|-----|-----------|
| **Výkonnosť** | dopyty s vysokým počtom zobrazení a nízkym CTR → prepíš title a popis |
| **Indexovanie stránok** | „Zistená – momentálne neindexovaná" → stránka je slabá alebo nová |
| **Používateľský dojem na stránke** | Core Web Vitals, malo by byť zelené |
| **Odkazy** | kto na teba odkazuje |

Novú ponuku vieš dať zaindexovať hneď: vlož jej adresu do horného vyhľadávacieho poľa
→ *Požiadať o indexovanie*. Zaberie to hodiny až dni namiesto týždňov.

---

## 3. Google Places API — recenzie na web

Aby sa recenzie z máp ukazovali priamo na webe:

1. [console.cloud.google.com](https://console.cloud.google.com) → nový projekt.
2. *APIs & Services → Library* → zapni **Places API**.
3. *Credentials* → *Create credentials* → **API key**.
4. Kľúč hneď obmedz: *Application restrictions* → **HTTP referrers** →
   `*.zdenkacibulova.sk/*`. *API restrictions* → len **Places API**.
   Bez obmedzenia ti ho niekto môže zneužiť a príde ti faktúra.
5. Vo *Web Zdenky → Recenzie z Google* vlož kľúč a vyhľadaj svoj podnik.
6. V Cloude nastav **rozpočtové upozornenie** na pár eur mesačne. Bezplatný limit
   na tento objem bohato stačí, ale nech si pokojný.

---

## 4. Google Analytics 4

Analytics **nenasadzuj priamo do témy** — musí ho spúšťať až súhlas s cookies.
Nastavíme ho cez CookieYes v časti 6.

1. [analytics.google.com](https://analytics.google.com) → *Admin* → *Vytvoriť* →
   účet + vlastníctvo (Slovensko, EUR).
2. Dátový tok → *Web* → `https://www.zdenkacibulova.sk`. Dostaneš **ID merania** `G-XXXXXXXXXX`.
3. V *Dátový tok → Vylepšené meranie* nechaj zapnuté kliky na odchádzajúce odkazy
   a sťahovanie súborov — automaticky ti bude merať stiahnutia ebooku.
4. **Prepoj so Search Console**: *Admin → Prepojenia produktov → Search Console*.
   Až potom uvidíš v Analytics aj vyhľadávacie dopyty.

### Čo si označiť ako konverziu
*Admin → Udalosti → označiť ako kľúčovú udalosť*:
- `generate_lead` (odoslaný kontaktný formulár)
- `file_download` (ebook)
- kliky na `tel:` odkazy

---

## 5. Ostatné, čo sa oplatí

- **Bing Webmaster Tools** — pri zakladaní naimportuješ všetko zo Search Console
  jedným klikom. Bing poháňa aj vyhľadávanie v ChatGPT a Copilote.
- **PageSpeed Insights** — otestuj úvodnú stránku a jednu ponuku. Cieľ: mobil nad 80.
- **Facebook / Instagram** — v profile daj odkaz na web. Sociálne siete sa
  automaticky pridávajú do štruktúrovaných dát ako `sameAs`.
- **Mapy.cz, Zoznam, Azet** — bezplatné zápisy, opäť s presne rovnakým NAP.

---

## 6. CookieYes a prepojenie so všetkým ostatným

Web **nemá vlastnú cookie lištu** — presne preto, aby ju mohol robiť CookieYes.

### Nastavenie
1. [cookieyes.com](https://www.cookieyes.com) → registrácia → pridaj doménu.
2. *Scan* prejde web a vypíše cookies. Prvý sken sprav až keď máš nasadený Analytics.
3. **Nastavenia lišty**: jazyk *slovenčina*, typ **GDPR**, tlačidlá
   *Prijať všetko / Odmietnuť všetko / Prispôsobiť*.
   „Odmietnuť" musí byť **rovnako viditeľné** ako „Prijať" — inak súhlas neplatí.
4. Nechaj zapnuté *Predchádzajúce blokovanie skriptov* (prior blocking) — bez toho
   sa Analytics spustí skôr, než človek klikne.
5. Skopíruj vložený kód a daj ho do **hlavičky webu**. Ak nechceš zasahovať do témy,
   použi malý plugin typu *WPCode* a vlož ho do `<head>`.
6. Zapni **Google Consent Mode v2** (v CookieYes jedno zaškrtnutie). Toto je dnes
   povinné, ak chceš mať v Analytics zmysluplné dáta z EÚ.

### Kam dať Analytics
V CookieYes vlož GA4 kód do kategórie **Analytické cookies**. Spustí sa až po súhlase,
predtým beží Consent Mode v „odmietnutom" režime — Google si tak vie dopočítať aspoň
odhad, a ty neporušuješ zákon.

Kategórie, ktoré na tomto webe potrebuješ:
| Kategória | Čo tam patrí |
|-----------|--------------|
| Nevyhnutné | prihlásenie do panela, obľúbené, porovnávač — bez súhlasu |
| Analytické | Google Analytics 4 |
| Marketingové | Facebook Pixel, ak ho niekedy nasadíš |
| Funkčné | vložené YouTube videá |

> Videá na webe už bežia cez `youtube-nocookie.com`, takže bez súhlasu neukladajú
> reklamné cookies. Aj tak ich v CookieYes zaraď do *Funkčné*.

### Prepojenie s Ochranou osobných údajov
Stránka `/ochrana-osobnych-udajov/` je na webe a je aktuálna. V CookieYes v nastavení
lišty naň nastav odkaz — aby sa z lišty dalo kliknúť na plné znenie.
Do textu doplň jednu vetu o tom, že **bezpečnostný denník ukladá IP adresy 30 dní**
kvôli ochrane účtu; právny základ je oprávnený záujem.

### Stačí bezplatný plán?
Na jednu doménu a bežnú návštevnosť áno. Platený plán potrebuješ, keď chceš
odstrániť logo CookieYes, viac jazykov alebo denné skenovanie.

---

## 7. Texty na webe — čo naozaj funguje

- **Jeden `<h1>` na stránku** a nech je v ňom to, čo ľudia hľadajú:
  *„Realitná maklérka v Banskej Bystrici"*, nie *„Vitajte"*.
- **Popisy ponúk píš vlastnými slovami.** Skopírovaný text z inzertného portálu
  Google ignoruje. Tri odseky vlastného textu porazia desať skopírovaných.
- **Alt texty fotiek**: *„3-izbový byt na Fončorde – obývačka"*, nie *„IMG_2941"*.
- **Miestne stránky**: shortcode `[reality_mesto mesto="Zvolen"]` vytvorí stránku
  s ponukami pre dané mesto. Sprav jednu pre každé mesto, kde reálne pôsobíš,
  a dopíš k nej pár viet o tamojšom trhu.
- **Referencie** na `/referencie/` sú textový obsah s menami a typmi obchodov —
  Google ich číta rád. Preto sa oplatí ich pridávať.
- **Interné odkazovanie**: z článku o predaji odkáž na `/odhad/`, z ponuky na
  podobné ponuky. Web to už čiastočne robí sám.

---

## 8. Čo naopak nerob

- Nekupuj spätné odkazy a nezapisuj sa do „katalógových" fariem.
- Neopakuj kľúčové slovo dokola — Google to vyhodnotí ako spam.
- Nemeň adresy stránok bez presmerovania. Ak musíš, sprav 301 presmerovanie.
- Nedávaj rovnaký `title` na viac stránok.
- Neprepínaj medzi `www` a bez `www`. Vyber jednu a drž sa jej.
- Nemaž staré predané ponuky — daj im stav *Predané*. Sú to referencie aj
  zaindexované stránky.

---

## 9. Poradie krokov

1. Vyplniť *Prispôsobiť → Miestne SEO* (adresa, súradnice, hodiny, mestá).
2. Založiť a **overiť** Google Business Profile, vyplniť ho na 100 %.
3. Search Console: overiť, odoslať sitemapu.
4. CookieYes: nasadiť lištu, zapnúť Consent Mode v2.
5. Analytics 4: vytvoriť, vložiť **cez CookieYes**, prepojiť so Search Console.
6. Places API kľúč → recenzie z Google na web.
7. Bing Webmaster Tools (import zo Search Console).
8. Požiadať prvých 5 klientov o recenziu.

---

## 10. Mesačná rutina (20 minút)

- [ ] Search Console → *Výkonnosť*: na čo ma nachádzajú, čo má zlé CTR
- [ ] Odpovedať na nové recenzie
- [ ] Pridať 3–5 nových fotiek do firemného profilu
- [ ] Napísať 2–4 príspevky do firemného profilu
- [ ] Skontrolovať, či nové ponuky sú zaindexované
- [ ] Poprosiť o recenziu klientov, ktorých obchod sa uzavrel
- [ ] Raz za pol roka: znova skenovať cookies v CookieYes

---

## Kde čo nájdeš vo wp-admine

| Čo | Kde |
|----|-----|
| Adresa, súradnice, hodiny, overenie Search Console | Vzhľad → Prispôsobiť → **Miestne SEO (Google)** |
| Odkaz na firemný profil (ikona v pätičke) | Prispôsobiť → **Sociálne siete & Google** |
| Recenzie z Google | Web Zdenky → **Recenzie z Google** |
| Rýchlosť, HTTPS, trvalé odkazy | Web Zdenky → **Nástroje** |
| Ochrana osobných údajov | Stránky → *Ochrana osobných údajov* |
