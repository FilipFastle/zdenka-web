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

## Čo ešte vieme pridať — potrebuje tvoj hosting / API kľúč
Tieto sa nedali dokončiť „na diaľku“, lebo vyžadujú prístup u teba. Keď budeš chcieť, spravíme:

- **Google recenzie** naživo — treba Google Places API kľúč z tvojho Google účtu.
- **AVIF/WebP kompresia fotiek** — závisí od podpory na hostingu (väčšina moderných ju má).
- **Automatická záloha databázy** — ideálne rieši plugin hostingu alebo UpdraftPlus.
- **Dynamický náhľadový obrázok pri zdieľaní** (cena vypálená do fotky) — treba GD/Imagick na serveri.
- **Kešovanie / kritické CSS** — odporúčam plugin od hostingu (napr. LiteSpeed Cache).

Napíš a dorobíme podľa toho, čo tvoj hosting umožňuje.
