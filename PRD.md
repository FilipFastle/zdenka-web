# PRD — zdenkacibulova.sk

**Produkt:** Osobný realitný web + kompletný prevádzkový systém pre realitnú maklérku
**Klient / používateľka:** Mgr. Zdenka Cibuľová, realitná maklérka, Zvolen (SK)
**Zadávateľ a vývojár:** Filip Fastle
**Doména:** zdenkacibulova.sk
**Repozitár:** `FilipFastle/zdenka-web`, vývojová vetva `claude/wordpress-realtor-photos-hero-22vh7p`
**Stav:** v produkcii, priebežne sa dopĺňa
**Jazyk produktu:** slovenčina — **všetky texty v UI aj komentáre v kóde sú po slovensky**

---

## 1. Kontext a zámer

Zdenka je samostatná realitná maklérka. Pred týmto projektom nemala vlastný web
ani systém na správu ponúk — komunikácia a evidencia prebiehali cez telefón,
e-mail a papier.

Cieľom nebolo „spraviť stránku", ale **postaviť celý prevádzkový systém jednej
realitnej kancelárie**, ktorý:

1. vyzerá ako web za ~10 000 € (výslovná ambícia zadávateľa — prémiový,
   nie šablónový dojem),
2. **Zdenka ho vie ovládať sama**, bez znalosti WordPressu a bez rizika, že
   niečo pokazí,
3. zbiera a eviduje dopyty tak, aby sa žiadny kontakt nestratil,
4. je nezávislý od platených pluginov tretích strán (žiadne predplatné,
   žiadne licencie) — všetko je vlastný kód.

### Prečo vlastný kód namiesto hotových pluginov
- Realitné pluginy (WP Residence, Houzez…) sú drahé, ťažké a ovládanie je pre
  netechnického človeka neprehľadné.
- Newsletter cez Mailchimp/Ecomail = ďalšie predplatné a ďalší účet navyše.
- Vlastný kód sa dá spraviť presne na mieru jednej používateľky a jednému trhu.

---

## 2. Ciele

### Produktové ciele
| Cieľ | Ako je naplnený |
|---|---|
| Prémiový vizuál | Vlastná téma, serif + sans dvojica písiem, zlato/béžová paleta, celoobrazovkový hero, jemné animácie |
| Samoobsluha maklérky | **Realitný panel** — vlastné rozhranie mimo wp-adminu |
| Žiadny stratený dopyt | Každý formulár na webe zapisuje kontakt do CRM (`pp_lead`) **a** posiela e-mail |
| Bez predplatného | Newsletter, recenzie, ebook, 2FA, zálohy — všetko vlastné pluginy |
| Bezpečnosť a dohľad | 2FA, bezpečnostný denník s IP, denné zálohy, vrátenie zmien |
| Viditeľnosť v Google | Miestne SEO, schema.org, GTM, sitemapy, diagnostika indexovania |

### Merateľné ukazovatele
- **Rýchlosť:** PC v PageSpeed ~1,5 s LCP (dosiahnuté). Mobil realisticky 3–4 s —
  strop je daný celoobrazovkovou fotografiou v hero, viď §12.
- **TBT** pod 200 ms (aktuálne ~10–50 ms).
- **Dopyty:** 100 % odoslaných formulárov je v *Formulároch* aj v e-maili.
- **Indexovanie:** všetky verejné stránky v Google Search Console bez chýb
  (aktuálne otvorený bod — viď §15).

### Čo produktom NIE JE
- Nie je to realitný portál s viacerými maklérmi (aj keď dátový model to znesie —
  agent sa priraďuje per ponuka).
- Nie je to e-shop.
- Nie je to viacjazyčný web.

---

## 3. Používatelia a role

| Rola | Kto | Kde pracuje | Čo smie |
|---|---|---|---|
| `administrator` | Filip (webmaster) | wp-admin | všetko |
| `realitny_makler` (`PP_AGENT_ROLE`) | Zdenka | **iba realitný panel** | ponuky, formuláre, recenzie, newsletter, nastavenia panela |
| `zc_nahlad` (`ZC_PREVIEW_ROLE`) | klient/tretia strana na ukážku | iba web | **nič** — má len `read`, do wp-adminu sa nedostane |
| neprihlásený | návštevník | web | prezeranie, formuláre, obľúbené, porovnanie |

**Kľúčové rozhodnutie:** Zdenka **nikdy nevidí wp-admin**. Pokus o vstup ju
presmeruje do panela (`roles.php`). Výnimka je allowlist koncových bodov, ktoré
panel potrebuje: `admin-ajax.php`, `admin-post.php`, `async-upload.php`,
`media-upload.php` — bez nich by sa neuložil formulár ani nenahrala fotka.
(Toto bola reálna chyba, ktorá spôsobila, že recenzie sa ticho neukladali.)

---

## 4. Architektúra

### Stack
- WordPress (klasický, bez FSE), PHP ≥ 7.4, MySQL
- **Žiadny build step**, žiadne node_modules, žiadny framework
- CSS a JS sú ručne písané; `main.min.css` sa generuje konzervatívnym
  minifikátorom (len komentáre a biele znaky, s overením znak po znaku)
- Klasický TinyMCE editor (nie Gutenberg) v paneli

### Repozitár
```
zdenka-web/
├── zdenka-theme/            # téma (standalone, bez parent theme)
├── property-pro-plugin/     # Property Manager Pro — jadro systému
├── zc-reviews/              # recenzie a referencie
├── zc-newsletter/           # newsletter
├── zc-ebook/                # PDF lead-magnet
├── zc-2fa/                  # 2FA + časovanie relácií
├── zc-installer/            # hromadná inštalácia (jednorazová pomôcka)
├── dist/                    # inštalovateľné .zip balíky
├── NAVOD.md                 # návod pre Zdenku/Filipa
├── SEO-GEO-NAVOD.md         # SEO/GEO/Google nástroje/CookieYes
├── FEATURES.md
└── PRD.md                   # tento dokument
```

Deploy = nahratie `.zip` z `dist/` cez wp-admin. Nie je CI/CD.

### Dátový model

**Typy obsahu**
- `property` — nehnuteľnosť (slug `/ponuka/…`), podporuje title, editor,
  thumbnail, excerpt
- `pp_lead` — dopyt/kontakt z ktoréhokoľvek formulára (CRM)

**Taxonómia**
- `zc_media_folder` na `attachment` — priečinky v Médiách

**Vlastné tabuľky**
- `{prefix}zc_reviews` — recenzie (author_name, author_role, body, rating,
  avatar_url, published, sort_order, created_at)
- `{prefix}zc_newsletter` — odberatelia (email, name, status
  pending/active/unsubscribed, token, source, subscribed_at, confirmed_at)
- `{prefix}zc_audit` — bezpečnostný denník + snímky na vrátenie zmien

**Dôležité meta kľúče `property`**
`_property_typ`, `_property_cena`, `_property_cena_povodna`, `_property_lokalita`,
`_property_mesto`, `_property_okres`, `_property_popis_kratky`, `_property_plocha`,
`_property_pozemok`, `_property_spalne`, `_property_kupelne`, `_property_wc`,
`_property_poschodie`, `_property_rocnik`, `_property_stav`, `_property_vlastnictvo`,
`_property_stav_predaja` (''/rezervovane/predane), `_property_energie`,
`_property_poznamka` (interná), `_property_cover_id`, `_property_gallery_ids`,
`_property_video_url`, `_property_amenities`, `_property_agent_id`,
`_property_views`, `_property_cta_extra`, `_property_folder_id`

---

## 5. Téma `zdenka-theme`

Standalone téma bez parent theme.

### Stránky
| Šablóna | Obsah |
|---|---|
| `front-page.php` | Úvod: celoobrazovkový hero carousel, ponuky, o mne, referencie, ebook pás, kontakt |
| `page-ponuky.php` | Zoznam ponúk + filtre |
| `page-o-mne.php` | Profil maklérky |
| `page-ako-pracujem.php` | Proces spolupráce |
| `page-referencie.php` | **Všetky** recenzie v 3-stĺpcovej mozaike, bez limitu |
| `page-odhad.php` | Formulár na odhad ceny nehnuteľnosti |
| `page-kontakt.php` | Kontakt + formulár |
| `page-oblubene.php` | Obľúbené ponuky (localStorage) |
| `page-ochrana.php` | Ochrana osobných údajov (živnostník) |

Stránky sa vytvárajú automaticky (`admin_init`), takže inštalácia nevyžaduje
ručné klikanie.

### Dizajnový systém
- Písma: **Playfair Display** (serif, nadpisy) + **DM Sans** (sans, text) —
  lokálne `.woff2`, podmnožina znakov (DMSans 67 kB, Playfair 38 kB)
- Paleta: zlatá `#B8A47A`, tmavá `#1C1A18`, béžová `#F5F1EA`, biela
- **Ceny a parametre ponúk sú zásadne bezpätkové** + `tabular-nums`
  (výslovná požiadavka — serif na číslach pôsobil lacno)
- Hero má vždy plnú výšku obrazovky: `100vh` s `@supports (height:100dvh)`,
  **bez `max-height` stropov** (tie sa spúšťali pri oddialení a lámali dojem)

### Customizer (Prispôsobiť) — sekcie
| Sekcia | Obsah |
|---|---|
| Maklérka – Kontakt | meno, titul, telefón, WhatsApp, e-mail |
| Email adresy | adresy pre notifikácie |
| Fotky maklérky | hero, portrét, karta |
| Ako pracujem – médiá | fotky/ikony procesu |
| Sociálne siete & Google | odkazy, sameAs |
| Miestne SEO (Google) | adresa, GPS, otváracie hodiny, **GTM ID**, „nemerať prihlásených" |
| Text – riadkovanie a medzery | line-height a medzery **zvlášť pre odsek, zoznamy a H1–H6** |
| Ostatné nastavenia | hlasitosť videí, kurz CZK… |

### Typografia (`inc/typography.php`) — dôležitý princíp
Riadkovanie a medzery sa nastavujú per štýl (odsek, zoznam, H1–H6) a **tie isté
pravidlá sa vložia aj do editora** — cez súbor `uploads/zc-editor.css`
registrovaný `add_editor_style()` plus `tiny_mce_before_init.content_style`.
Zdenka teda pri písaní vidí presne to, čo uvidí návštevník. Súbor sa prepisuje
na `customize_save_after`.

---

## 6. Property Manager Pro (`property-pro-plugin`) — jadro

Verzia 5.35. Obsahuje typ `property`, `pp_lead`, panel, denník, zálohy,
priečinky médií, role.

### Detail ponuky (`includes/single-property.php`)
- **Hero carousel** cez celú obrazovku, prvá fotka `fetchpriority=high`,
  ostatné lazy; `sizes` počítané s ohľadom na to, že `object-fit:cover`
  pri celoobrazovkovej výške potrebuje šírku podľa **pomeru strán**, nie podľa
  šírky viewportu
- Parametre (plocha, izby, poschodie…) — bezpätkovo, tabular-nums
- Cena, cena/m², znížená cena, stav predaja (Rezervované/Predané)
- Náklady na bývanie
- Vybavenie a okolie (kategorizované, vlastné položky)
- Popis z editora (riadkovanie podľa nastavení témy)
- **Kontaktný blok**:
  - **Short (zvislé YouTube video) → vpravo vedľa formulára**, kontakt vľavo
  - širokouhlé 16:9 video → pod kontaktom cez celú šírku (ako predtým)
  - voliteľný **doplnok** (`_property_cta_extra`) — shortcode alebo text v pravom stĺpci
  - bez videa aj bez doplnku → formulár cez celú šírku
  - do 720 px sa všetko skladá pod seba
- Galéria (masonry) + lightbox
- Bočný panel: cena, tlačidlá volať / WhatsApp / e-mail, karta maklérky
- Zdieľanie, QR, PDF exposé, porovnanie, obľúbené, počet zobrazení
- **Kliknutie na fotku otvorí ponuku** (zistené pri testovaní s dvomi ľuďmi —
  obaja klikli najprv na obrázok, až potom na tlačidlo)

### Shortcodes
`[property_grid]`, `[property_carousel]`, `[predane_nehnutelnosti]`,
`[reality_mesto]`, `[porovnanie]`, `[property_favorites]`, `[realitny_panel]`,
`[zc_reviews]`, `[zc_newsletter]`, `[zc_ebook]`

### Maklér — jeden zdroj pravdy (`includes/helpers.php`)
`pp_agent_data($post_id)` skladá meno, titul, telefón, WhatsApp, e-mail a fotku
**z profilu priradeného používateľa**, čo chýba doplní z Customizera.
Fotka: profilová → `zc_photo('card'/'portrait')` → Gravatar.

> Historická chyba: údaje sa brali z troch rôznych miest, takže ponuka ukazovala
> jej meno, cudzí telefón a žiadnu fotku. Výber makléra navyše filtroval len
> role `administrator/editor/author`, takže Zdenka v zozname vôbec nebola.
> Odvtedy platí: **jedna funkcia, jeden zdroj**.

---

## 7. Realitný panel — najdôležitejšia časť produktu

**Adresa:** `/realitny-panel/` · vlastná šablóna `templates/panel-standalone.php`
(bez hlavičky a pätičky témy) · vlastný dizajn, nie wp-admin.

### Sekcie
- **Ponuky** — zoznam, pridať/upraviť, duplikovať, prepínač stavu, hromadné akcie
- **Formuláre** — všetky dopyty vrátane telefónu, e-mailu a IP
- **Recenzie** — pridať/upraviť/zmazať/skryť
- **Newsletter** — odberatelia, šablóny, kampane, rozposlanie novej ponuky
- **Nastavenia** — otvoriť/zavrieť web, prístup podľa rolí a používateľov, notifikácie

### Technické princípy panela (tvrdo vydobyté)
1. **Detekcia stránky nezávislá od slugu.** `pp_panel_page_id()` hľadá stránku
   podľa uloženého ID → slugu → obsahu s `[realitny_panel]`.
   *Dôvod:* stránka v koši zabrala slug, skutočná stránka sa volala
   `realitny-panel-2`, panel sa vykreslil, ale **všetky POST-y sa ticho zahodili**,
   lebo handlery boli podmienené `is_page('realitny-panel')`.
2. **Formuláre idú cez `admin-post.php`**, nie na stránku panela.
3. **Post/Redirect/Get + jednorazový token** proti duplicitám.
4. **`pp_go()`** — keď `wp_safe_redirect()` zlyhá (hlavičky už odoslané),
   vypíše HTML `meta refresh` fallback. *Dôvod:* po pridaní recenzie sa točil
   loading a po F5 tam bola dvakrát.
5. **Recenzie sú kompletne bez JavaScriptu** — formulár priamo v stránke,
   hviezdičky cez CSS `input:checked ~ label`. *Dôvod:* modal sa zatváral pri
   označení textu myšou.
6. **Médiá viazané na upravovanú ponuku** — `wp_enqueue_media(['post' => $id])`,
   takže nahraté fotky dostanú priečinok okamžite.
7. Upozornenie, keď je vybraná fotka užšia ako 1600 px.

---

## 8. Ostatné pluginy

### ZC Recenzie (1.3.2)
Vlastná tabuľka, hodnotenie 1–5, poradie, skrytie, avatar.
Napojenie na **Google recenzie** cez Places API je pripravené
(`zcr_google_key`, `zcr_google_place`, min. hodnotenie) — **čaká na API kľúč**.
Na webe: carousel na úvode (bez limitu) + stránka **Referencie** (3-stĺpcová mozaika).

### ZC Newsletter (1.6.0)
- Vlastná tabuľka odberateľov, **double opt-in** (token, potvrdzovací e-mail)
- Šablóny (`zcn_templates`), kampane (`zcn_campaigns`), plánovanie (`zcn_scheduled`)
- **Rozposlanie novej ponuky** (`property-blast.php`)
- Meranie otvorení a klikov (`tracking.php`)
- Odhlasovací odkaz v každom e-maili
- Vizitka v pätičke e-mailu obsahuje **telefón, e-mail aj WhatsApp**
- **Bez automatického oslovenia** — „Dobrý deň [meno]" si píše maklérka sama
  (šablóna ho predtým vkladala a v e-maili bolo dvakrát)

> **Kritické pravidlo, ktoré musí platiť navždy:** odberatelia newslettera
> **nikdy** nedostávajú správy z kontaktných formulárov. Rozposiela sa výhradne
> explicitnou akciou v paneli.

### ZC Ebook (1.0.3)
PDF lead-magnet: pás na stránke + modal formulár → kontakt do *Formulárov*,
prihlásenie na newsletter, odoslanie PDF.
Súbor: `dist/ebook-ako-predat-nehnutelnost.pdf` (+ InDesign kit).

### ZC Zabezpečenie – 2FA (1.1.3)
TOTP (Google Authenticator), QR kód, záložné kódy, vynútenie pre rolu.
Automatické odhlásenie: **30 min bez 2FA, 2 h s 2FA**. Platí pre wp-admin aj panel.

### ZC Inštalátor (1.0.1)
Jednorazová pomôcka — nahratie viacerých `.zip` naraz. Po inštalácii sa zmaže.

---

## 9. Prierezové systémy

### Notifikácie (`inc/notifications.php`)
Adresáti sa nastavujú **explicitne** — konkrétne e-maily, role a používatelia
(`zc_notify_emails`, `zc_notify_roles`, `zc_notify_users`), zvlášť podľa typu
formulára. Nastavenie je vo wp-admine aj v paneli.

> **Historická chyba a poučenie:** predtým sa posielalo na `admin_email`, ktorý
> zdieľalo viac WP účtov — vyzeralo to, akoby správy chodili „všetkým".
> Kompletný audit `wp_mail()` potvrdil, že odberateľom newslettera nikdy nič
> nechodilo. Napriek tomu je odvtedy zoznam adresátov vždy explicitný.

### Bezpečnostný denník (`includes/audit-log.php`)
Tabuľka `{prefix}zc_audit`. Zaznamenáva prihlásenia (aj neúspešné), odhlásenia,
zmeny a mazanie príspevkov, nahratie médií, zmeny používateľov, pluginov, tém
a nastavení — vždy s **používateľom, IP a časom**. IP sa číta z CF-Connecting-IP /
X-Real-IP / X-Forwarded-For.
**Uchováva sa 1 mesiac.**

### Vrátenie zmien (undo) — do 7 dní
Denník si pri rizikových operáciách odkladá **snímku** (`snapshot`, `snap_type`).
Vrátiť sa dá zmazaná/zmenená ponuka (obnova cez `import_id`, teda s pôvodným ID),
recenzia, nastavenie aj role používateľa. Obnova je chránená proti rekurzii
(`$GLOBALS['zc_audit_restoring']`). Po 7 dňoch sa snímky nulujú, po 30 dňoch
sa mažú celé riadky.

### Denné zálohy (`includes/backup.php`)
Každý deň o **3:20** (cron `zc_backup_daily`) sa uloží gzip JSON s celým obsahom
panela: **ponuky, formuláre, recenzie, newsletter (odberatelia + šablóny +
kampane), nastavenia pluginov, vzhľad z Customizera**.
Uložisko `uploads/zc-zalohy/` chránené `.htaccess`. **Max 30 záloh**, staršie sa mažú.
Obnova je výberová (zaškrtneš, čo sa má vrátiť). Fotky sa nekopírujú — ostávajú
v Médiách, záloha si drží odkaz.

### Priečinky v Médiách (`includes/media-folders.php`)
Taxonómia `zc_media_folder`. **Fotky ponúk sa triedia samy** do
`Ponuky → Názov ponuky` — pri úprave existujúcej ponuky hneď pri nahratí,
pri novej po prvom uložení. Premenovanie ponuky premenuje priečinok.
Filter v knižnici, hromadné akcie, priečinky aj v okne výberu médií.

### Bublina „Web Zdenky" (`inc/admin-hub.php`)
Všetky vlastné pluginy sú v **jednej položke menu**, nie roztrúsené po wp-admine.
Podstránky: Prehľad, Nehnuteľnosti, Notifikácie, Náhľad pre klienta,
Stránky a údržba, Nástroje, **Indexovanie**.
Trik: `add_menu_page()` nastaví `$admin_page_hooks[$slug]`, takže opätovné
pridanie toho istého slugu cez `add_submenu_page()` zachová pôvodný hook aj callback.

**Nástroje pre webmastera** — stav systému (PHP, HTTPS, trvalé odkazy, cron,
COOKIE_DOMAIN, veľkosti hero fotiek, WebP, GTM, adresáti notifikácií, URL panela)
a rýchle akcie: obnoviť trvalé odkazy, vynulovať cache, zmazať dočasné dáta,
**prepočítať veľkosti fotiek** (dávkovo), prepnúť WebP, **prepnúť zmenšené štýly**,
test e-mailu.

### Režim údržby
Prepínač priamo na nástenke wp-adminu. Návštevník dostane 503 + oznam,
admin a povolené role vidia web normálne. Admin má náhľad
`?preview_maintenance=1`.

---

## 10. SEO, GEO a meranie

- **schema.org**: `RealEstateAgent` (adresa, GPS, otváracie hodiny, sameAs),
  `Product` + `Offer` na detaile ponuky
- **OG / Twitter** meta pre zdieľanie (fotka, názov, cena, lokalita)
- **Google Tag Manager**, kontajner **`GTM-TRR2FR88`** — ID sa vkladá
  v Customizeri, nie natvrdo. Skript sa neaktivuje v admine ani v paneli,
  voliteľne nemeria prihlásených redaktorov. Naviazaný v `requestIdleCallback`,
  poslucháče `passive`. dataLayer nesie premenné stránky aj ponuky
  (typ, cena, lokalita) a udalosť `generate_lead`.
- **Consent Mode v2** + **CookieYes** — postup v `SEO-GEO-NAVOD.md`
- **Miestne SEO stránky** pre okolité mestá
- `SEO-GEO-NAVOD.md` pokrýva: Google Business Profile, Search Console,
  Places API, GTM, GA4, CookieYes + kontrolné zoznamy

### Diagnostika indexovania (`inc/indexing.php`) — nové
Search Console hlásila **„Stránka nie je indexovaná: Chyba presmerovania"**.
Preto pribudlo:
1. **Poistka proti zacykleniu** — keď je pred webom proxy (Cloudflare, LB),
   doplní sa `$_SERVER['HTTPS']` podľa `X-Forwarded-Proto` / `CF-Visitor` /
   `X-Forwarded-SSL` / `X-Forwarded-Port`, aby `redirect_canonical()` neposielal
   robota http → https → http donekonečna. Nastaví sa len ak to proxy naozaj
   hlási **a** adresa webu je https.
2. **Stránka Web Zdenky → Indexovanie** — prejde reťaz presmerovaní pre všetky
   štyri varianty (http/https × www/bez www), vypíše každý skok a odhalí
   zacyklenie či pridlhú reťaz; skontroluje viditeľnosť pre vyhľadávače
   (`blog_public`), režim údržby, nesúlad `home` vs `siteurl`, robots.txt
   a dostupnosť mapy stránok.

Najčastejšie príčiny, ktoré nástroj pomenuje: Cloudflare v režime *Flexible SSL*
(treba *Full strict*), dvojité presmerovanie v `.htaccess` aj u hostingu,
a www ↔ bez www posielajúce sa navzájom.

---

## 11. GDPR a bezpečnosť

- Stránka *Ochrana osobných údajov* na mieru živnostníka
- Súhlas so spracovaním OÚ pri každom formulári, newsletter zvlášť (opt-in)
- Double opt-in do newslettera, odhlásenie jedným klikom
- YouTube cez `youtube-nocookie.com`
- Cookie lišta / CookieYes
- Honeypot + antispam vo formulároch, nonce a kontrola oprávnení všade
- Vypnuté XML-RPC, blokované vypisovanie používateľov (`?author=`),
  REST endpoint používateľov skrytý, `DISALLOW_FILE_EDIT`
- 2FA + časovanie relácií
- Vlastný HTML od účtu bez `unfiltered_html` prechádza cez `wp_kses_post()`

---

## 12. Výkon — čo sa spravilo a kde je strop

**Spravené**
- Podmnožiny písiem `.woff2`, logo PNG 9 kB namiesto SVG 193 kB
- Vlastné veľkosti obrázkov `zc-1440`, `zc-2048`; WebP zmenšeniny (prepínateľné)
- `srcset` / `sizes` počítané podľa reálneho použitia, `fetchpriority` na hero,
  `<link rel=preload>` obmedzený médiami
- Odstránené emoji skripty a `wp-embed`
- Minifikované CSS (46 → 32 kB) s **kill switchom** v Nástrojoch
- GTM v `requestIdleCallback`, passive listenery
- PWA / service worker s verziovanou cache

**Poctivý strop:** celoobrazovkový fotografický hero **nedosiahne zelené LCP
(< 2,5 s)** na mobilnom teste PageSpeed. Reálny cieľ je 3–4 s. Najväčší
zostávajúci vplyv má veľkosť hero fotky (nahrávať max ~2500 px na dlhšej strane)
a cudzie skripty (CookieYes, GTM), ktoré web neovplyvní.

### Dve zranenia, z ktorých plynú pravidlá
1. **Minifikácia CSS rozbila celý web.** Regex prepisoval čísla
   (`max-width:1000px` → `100px`) a odstraňoval medzery v `calc(18px + env(...))`.
   → Minifikátor odvtedy robí **len komentáre a biele znaky** a build zlyhá,
   ak sa obsah čo i len o znak líši. Plus kill switch v Nástrojoch.
2. **„Optimalizácia" fotiek časy zhoršila.** `wp_get_attachment_image_src()`
   pri neexistujúcej veľkosti **ticho vráti originál**. Nová `zc-1440` teda
   spôsobila, že telefón sťahoval originál *aj* preloadnutú `large`.
   → `zc_photo_sized()` kontroluje `$src[3]` (`is_intermediate`), má reťaz
   záloh, chýbajúce veľkosti sa dogenerujú a stav je vidno v Nástrojoch.

---

## 13. Zásady pre ďalší vývoj (dodržiavať)

1. **Všetko po slovensky** — UI texty aj komentáre v kóde. Komentár vysvetľuje
   *prečo*, nie *čo*.
2. **Zdenka nesmie skončiť vo wp-admine.** Každá nová funkcia pre ňu patrí do panela.
3. **Bez závislostí.** Žiadne npm, žiadne CDN, žiadne platené pluginy.
4. **Jeden zdroj pravdy.** Údaje maklérky = `pp_agent_data()`. Nekopírovať logiku.
5. **Formuláre:** nonce + kontrola oprávnení + Post/Redirect/Get + idempotencia.
6. **Nikdy nespoliehať na slug.** Stránky sa hľadajú podľa ID/obsahu.
7. **Nič nesmie ísť odberateľom newslettera automaticky.**
8. **Zmena vzhľadu = pozrieť sa aj na mobil aj na veľký monitor.** Zlom
   pre skladanie do stĺpca je 720 px v kontaktnom bloku, 768/480 px inde.
9. **Verzie sa dvíhajú a `.zip` sa prebalí** pri každej zmene, ktorá ide na web.
10. Vetva `claude/wordpress-realtor-photos-hero-22vh7p`, PR sa neotvára,
    kým si ho zadávateľ nevypýta.

---

## 14. História požiadaviek (chronologicky, skrátene)

Integrácia fotiek do Customizera · bezpečnostný audit · newsletter so šablónami
a rozposlaním ponuky · rozšírené vybavenie · univerzálny YouTube embed vrátane
Shorts · parametre bez prázdnych polí · maklérka z WP profilu · stránka Ochrana OÚ
· OG a schema.org · štítky, cena/m², znížená cena, zdieľanie, počet zobrazení,
podobné ponuky · panel: hromadné akcie, duplikovanie, poznámky · PDF exposé, QR,
mena, náklady na bývanie · cookie lišta, uvítací e-mail, PWA, ARIA · porovnávač,
drag-drop fotiek, CSV import, CRM, meranie otvorení, miestne SEO · prerobenie
panela · režim údržby na nástenke · panel na vlastnej šablóne · živý kurz mien ·
databáza klientov a zachytenie všetkých formulárov · ebook lead-magnet ·
nastavenie adresátov notifikácií · rola „Náhľad webu" · stránka Referencie ·
carousel bez limitu · jedna bublina v menu · zrušenie subdomény panela ·
oprava recenzií v paneli · bezpečnostný denník · denné zálohy · vrátenie zmien do
7 dní · kliknutie na fotku otvorí ponuku · návod na SEO/GEO/Google/CookieYes ·
Google Tag Manager · priečinky v Médiách automaticky po ponukách · kvalita fotiek
v hero pre veľké monitory aj malé telefóny · newsletter bez automatického
oslovenia + vizitka s TČ/e-mailom/WhatsApp · oprava chýbajúcej maklérky vo výbere
· odrážky z editora + nastaviteľné riadkovanie · optimalizácia podľa Site Kitu ·
oprava rozbitého webu po minifikácii · hero 100vh aj pri oddialení · ceny a
parametre bezpätkovo · riadkovanie po štýloch priamo v editore · **Short vedľa
kontaktu** · **diagnostika indexovania v Google**

---

## 15. Otvorené body / backlog

| Bod | Poznámka |
|---|---|
| **Chyba presmerovania v Search Console** | Nástroj na diagnostiku je hotový; samotnú príčinu treba potvrdiť na hostingu (Cloudflare SSL režim, `.htaccess`, www vs bez www) a potom dať *Vyžiadať indexovanie* |
| Google recenzie naživo | Treba Places API kľúč z účtu Zdenky |
| AVIF | Závisí od podpory hostingu |
| Záloha databázy celého webu | Rieši hosting alebo UpdraftPlus — vlastné zálohy pokrývajú len obsah panela |
| Mobilné LCP | Fyzický strop celoobrazovkového hera, viď §12 |

---

## 16. Verzie (k dátumu tohto dokumentu)

| Balík | Verzia |
|---|---|
| zdenka-theme | 3.27.0 |
| Property Manager Pro | 5.35 |
| ZC Recenzie | 1.3.2 |
| ZC Newsletter | 1.6.0 |
| ZC Ebook | 1.0.3 |
| ZC Zabezpečenie 2FA | 1.1.3 |
| ZC Inštalátor | 1.0.1 |

Inštalovateľné balíky sú v `dist/`. Návod na obsluhu je `NAVOD.md`,
SEO/GEO postupy `SEO-GEO-NAVOD.md`.
