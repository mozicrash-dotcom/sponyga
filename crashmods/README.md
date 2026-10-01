# CrashMods work package (prepared 2026-10-01)

Prepared in a cloud session without server access. Everything here still has to be
deployed to crashmods.com. Review page for the owner: https://claude.ai/artifact/3M8Hny6YtZjGcNrk5PixNQ

## 1. Category redirect: `cm-modkategoria-atiranyitas.php`

- Copy to `/var/www/crashmods/wp-content/mu-plugins/`, run `php -l` on it first.
- 301-redirects `/mod-category/<game>__<cat>/` to `/game/<game>/?category=<cat>` (keeps `?lang=`),
  rewrites mod_category term links (breadcrumbs) to the same target, and removes
  `mod_category` from the Yoast sitemap.
- After deploy: clear the page cache, open
  `https://crashmods.com/mod-category/beamng-drive-mods__other/` and confirm it lands on the
  BeamNG page with the Other filter, check that `mod_category-sitemap.xml` is gone from
  `sitemap_index.xml` (Yoast may need its sitemap cache refreshed), then ask the owner to
  resubmit the sitemap in Search Console.
- Tested against the live term list: 201 targets all return 200. Only `trucks` (1 mod) has no
  game and stays as it is.

## 2. Mod meta descriptions: `cm-mod-meta-leiras.php`

- Copy to `mu-plugins/`, `php -l`, clear cache.
- Replaces auto-generated Yoast descriptions ("Title - Features: ...") on `mod` pages with the
  first prose paragraph cut at a sentence or clause boundary (max 155 chars). Hand-written
  descriptions are kept. Also turns literal `**bold**` in mod content into `<strong>`.
- Check 3 mod pages (view source, `<meta name="description">` and `og:description`), e.g.
  `/mods/lada-2101-35-configurations-with-detailed-interior/`.

## 3. Articles: `articles/`

Three English drafts. Each `.html` is the post body (the `<h1>` is the post title, drop it from
the body if the template prints the title). Each `.json` has the slug, Yoast SEO title and meta
description. Create them as **drafts** in the Guides section with a featured image, and let the
owner publish. All 44 internal links were checked (HTTP 200).

| File | Slug | Main keyword |
|---|---|---|
| 01-how-to-install-sims-4-mods | /guides/how-to-install-sims-4-mods/ | how to install sims 4 mods |
| 02-best-minecraft-qol-mods | /guides/best-minecraft-qol-mods/ | minecraft qol mods |
| 03-best-fs25-sheds-and-houses | /guides/best-fs25-sheds-and-houses/ | fs25 mods sheds |

## 4. Mods: `mods/mods.json`

Eight Minecraft mods, none of them on CrashMods yet (checked by title search). Upload only the
ones the owner approves on the review page.

- Licenses checked on Modrinth **and** in the GitHub repo: Unlicense, BSD-3-Clause or MIT. All
  allow redistribution, including on a site with ads.
- MIT and BSD require the copyright notice and license text to go with every copy. Most of these
  jars do **not** contain a LICENSE file (`license_in_jar` in the JSON), so each mod page needs a
  "License" section with the copyright line and the full text from `mods/licenses/<slug>.txt`,
  plus a link to the source repo.
- Credit the original author(s) in the Creator field. Do not show the uploader persona as the
  copyright holder: the current mod schema prints `"copyrightNotice": "© 2026 <uploader>"`,
  which is wrong for re-hosted mods. For these, it should name the original author.
- BSD-3-Clause (Mouse Tweaks) also forbids implying endorsement by the author.
- Download the files from the `url` in the JSON and compare `sha256` before uploading.
  Title, category, description (features + paragraphs) and credit are ready in English.
