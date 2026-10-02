# Átadás a felhős munkamenetből (2026-10-01/02)

A tulaj kérésére küldöm. Ezt a munkát egy felhős Claude-munkamenet végezte, amíg a gépen futó munkamenetben heti limit volt. A felhőből nincs SSH a szerverre, ezért itt csak a nyilvános oldalt és a nyilvános API-kat lehetett elérni (WP REST, sitemap, Modrinth, RDAP). Szerveren semmi nem változott.

Ebben az anyagban csak a CrashMods-hoz tartozó rész van, a beszélgetés többi része nem.

Minden fájl a `mozicrash-dotcom/sponyga` repó `claude/gracious-rubin-tcijje` ágán, a `crashmods/` mappában van. A tulajnak készült áttekintő oldal: https://claude.ai/artifact/3M8Hny6YtZjGcNrk5PixNQ

## 1. Amiből indultunk

A tulaj utolsó kérése a CrashMods munkamenetben (szept. 29.): a Google-ben „crashmods BeamNG” keresésre a `/mod-category/beamng-drive-mods__other/` jön be, ami egy csúnya kategória-archívum. Ehelyett a BeamNG játékoldalnak kellene bejönnie, bekapcsolt szűrővel.

Oka: a Yoast `mod_category-sitemap.xml` 121 kategóriaoldalt küld be, és mindegyiknek saját magára mutat a canonicalja. A cél (`/game/beamng.drive-mods/?category=beamng-drive-mods__other`) már működik, a szűrő szerveroldali.

A gépen futó munkamenet akkor éppen a mobilos „Kiemelt & népszerű” blokkot ellenőrizte (`zzzz-cm-featured-mobil.php`, már élesítve). Itt jött a limit, a 390 px-es ellenőrzés félbemaradt.

## 2. Elkészült fájlok (még nincsenek a szerveren)

| Fájl | Mit csinál |
|---|---|
| `cm-modkategoria-atiranyitas.php` | mu-plugin. 301 a `/mod-category/<játék>__<kat>/` címekről a szűrős játékoldalra (`?lang=` és egyéb paraméter megmarad). A `term_link` filterrel a breadcrumb és a belső linkek is egyből oda mutatnak. A `mod_category` kikerül a Yoast sitemapból. A 201 célcím élesben mind 200-at ad. Egyedül a `trucks` (1 mod) marad a helyén. |
| `cm-mod-meta-leiras.php` | mu-plugin. A mod-oldalak automatikus Yoast-leírása („Cím - Features: …”, félbevágva) helyett az első rendes bekezdésből épít max. 155 karakteres leírást, mondat- vagy tagmondathatáron vágva. A kézzel írtakhoz nem nyúl. A `the_content`-ben a nyers `**félkövér**` jelölést `<strong>`-ra cseréli. 200 modon tesztelve: 197 régi leírás volt automatikus, 195 új lett kerek mondat. |
| `articles/` | 3 angol cikkvázlat a Guides részre (HTML törzs + JSON a sluggal, SEO-címmel és meta-leírással). Mind a 44 belső link 200-at ad. |
| `mods/` | 8 Minecraft-mod feltöltésre előkészítve, licenccel együtt. Részletek: 5. pont. |
| `README.md` | Telepítési lépések mindegyikhez. |

## 3. SEO-elemzés (nyilvános oldalról, kb. 340 oldal)

Nyers adat: `data/seo-feltaras-oldalak.json`. Oldalanként: státusz, TTFB, méret, title, description, canonical, robots, hreflang, H1, schema típusok, alt nélküli képek, szószám.

**Rendben van:**
- Minden oldalnak van title-je, description-je, egy H1-e és canonicalja.
- A sitemap 3392 URL-je mind 200.
- Az üres játék- és kategóriaoldalak noindexek.
- A http→https és a www→nem-www átirányítás működik.
- A `?lang=` változatok noindexek, canonicaljuk az angolra mutat. Ez rendben van, mert csak a felület van fordítva.
- TTFB medián 0,40 s.

**Javítandó, fontossági sorrendben:**
1. Kategóriaoldalak a Google-ben: a fenti átirányítás javítja.
2. **Mobil sebesség.** Lab mérés, Pixel 7, lassú 4G, 4× CPU-lassítás. LCP: főoldal 6,2 s, BeamNG-oldal 4,8 s, mod-oldal 6,8 s, cikk 4,4 s.
   - A `<head>`-ben 3 szinkron (renderelést blokkoló) script van: `exchange.journeymv.com/usersync.min.js`, a `scripts.journeymv.com/.../wrapper.min.js` és a `cm-combine/inline-*.js`. Szöveges LCP-nél (H1) ez a fő késleltető.
   - DOM-méret: a BeamNG-oldalon 6269 elem.
   - 64–117 script tag oldalanként.
   - A Journey-scriptekhez csak óvatosan szabad nyúlni, mert a hirdetési bevételt érintik.
3. **Mod-oldal meta leírások:** a mintában 97% „…”-ra végződött, és 13% „Features: -” listával kezdődött. Ezt javítja a `cm-mod-meta-leiras.php`.
4. **Schema.** A mod-oldalak fő entitása `CreativeWork`, ezért nem kaphatnak software rich resultot, pedig van rajta `applicationCategory`, `offers` (price 0), `softwareVersion` és csillagos értékelés. `SoftwareApplication` + `aggregateRating` kellene. Lehet, hogy ezt korábban szándékosan állítottátok így, nézd meg. Emellett minden mod-oldalon van egy sablonos `FAQPage` is.
5. **Szerzői jog a schemában.** A mod-oldalakon `"copyrightNotice": "© 2026 <feltöltő álneve>"` szerepel (pl. PolarDrift, RevLimitR), miközben az oldalon a valódi készítő más (pl. „Creator: Pankaj”). Átvett modoknál ez téves. Az eredeti készítőt kellene feltüntetni.
6. **Apróságok:**
   - A mod-oldalak ~2,5%-án nyers `**` látszik a szövegben (a meta-plugin javítja).
   - A title-ök 18%-a hosszabb 60 karakternél.
   - A `post-sitemap2-4.xml` üres, mégis benne van az indexben.
   - A `http://www` kétlépéses átirányítás.
   - Van nem-ASCII slug (pl. `m³`).
   - Az alt nélküli képek nagy része dekoratív, `aria-hidden` linkben van, ez rendben van.

## 4. Kulcsszókutatás

Google autocomplete-ből, 15 játékra, 4154 kifejezés (`data/kulcsszavak-osszes.json`). A téma-összesítő és a javaslatok: `data/kulcsszo-temak.json`.

A legnagyobb hiányok, amikre a 3 cikk készült:
- Sims 4 telepítés: „how to install sims 4 mods”, „sims 4 cc folder”, „sims 4 mods not working”. Nincs útmutató, pedig 259 Sims-mod van fent.
- Minecraft QoL: „minecraft mods qol”, „minecraft mods that improve performance”. 845 mod van fent, cikk egy sem.
- FS25 épületek: „fs25 mods sheds”, „fs25 mods houses”. 198 placeable mod van fent, cikk nincs.

További ötletek:
- A BeamNG install-útmutató első bekezdésébe kerüljön be a mods mappa helye („beamng mods folder” erős kifejezés).
- „BeamNG Truck Mods” gyűjtőoldal a BMW/Audi mintájára.
- A `/news/best-free-beamng-maps-to-download/` címe „Jumping Maps”, ezt érdemes átírni „Best Free BeamNG Maps”-re.
- „FS25 mods you need”, GTA 5 telepítési útmutató (Legacy/Enhanced), City Car Driving telepítési útmutató, ETS2 verziók a játékoldalon.

## 5. Modok (`mods/mods.json`)

Nyolc Minecraft-mod, egyik sincs még fent (címkereséssel ellenőrizve):
- AppleSkin (Unlicense)
- Mouse Tweaks (BSD-3)
- Dynamic FPS (MIT)
- Falling Leaves (MIT)
- Lootr (MIT)
- Tom's Simple Storage (MIT)
- RightClickHarvest (MIT)
- Explosive Enhancement (MIT)

A licenceket a Modrinth-on és a GitHub-repó LICENSE fájljában is ellenőriztem. Mind engedi a továbbterjesztést, hirdetéses oldalon is.

Minden modhoz megvan: fájl URL, SHA-256, betöltő (Fabric/NeoForge), MC 26.3, függőségek és angol leírás saját megfogalmazásban.

A legtöbb jarban nincs LICENSE fájl. Ezért a mod-oldalra ki kell írni a copyright sort és a licenc teljes szövegét (`mods/licenses/<slug>.txt`). Mouse Tweaksnél (BSD-3) a szerző nevével nem szabad támogatást sugallni.

FS25-ös és Sims 4-es modokat szándékosan nem készítettem elő: ott a készítők feltételei általában tiltják az újrafeltöltést.

Feltölteni csak azt szabad, amit a tulaj jóváhagy.

## 6. Domainek (RDAP, 2026-10-01)

| Domain | Regisztráció | Lejárat | Regisztrátor |
|---|---|---|---|
| crashmods.com | 2026-04-09 | 2027-04-09 | Key-Systems GmbH |
| modsdrop.com | 2026-09-20 | 2027-09-20 | Key-Systems GmbH |

Ez a mostani regisztráció kezdete. A korábbi tulajdonosok az archive.org-on látszanának, de onnan nem értem el.

## 7. Nyitva maradt

- A mobilos „Kiemelt & népszerű” blokk ellenőrzése 390 px-en (szept. 27.).
- Egy korábbi munkamenet összefoglalója szerint még hátravolt: a Gmail alkalmazásjelszó visszavonása, az OAuth secret cseréje és a WP Mail SMTP hozzáférés visszavonása. Nézd meg, megtörtént-e.
- Telepítés a `README.md` szerint: a két mu-plugin, a cikkek piszkozatként, a modok csak jóváhagyás után.
