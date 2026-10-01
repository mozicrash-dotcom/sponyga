<?php
/**
 * Plugin Name: CrashMods — mod-kategória oldalak → játékoldal bekapcsolt szűrővel
 * Description: Tulaj 2026-09-29: a Google-ből a /mod-category/<játék>__<kategória>/ oldalra érkezők
 *   ne a csupasz kategória-archívumot lássák, hanem a játékoldalt a szűrővel
 *   (pl. /game/beamng.drive-mods/?category=beamng-drive-mods__other).
 *   1) 301-es átirányítás (a ?lang= és a többi paraméter megmarad),
 *   2) a belső linkek és a breadcrumb rögtön a szűrős játékoldalra mutatnak,
 *   3) a mod_category kikerül a Yoast sitemapból, így a Google nem kapja meg újra ezeket a címeket.
 *   Visszaállítás: a fájl törlése.
 */

if (!defined('ABSPATH')) exit;

/**
 * Melyik játékhoz tartozik a kategória, és milyen ?category= értékkel szűrjünk.
 * @return array{0: WP_Term, 1: string}|null  [játék term, szűrő slug ('' = szűrő nélkül)]
 */
function cm_modkat_jatek_es_szuro(WP_Term $kat) {
  static $cache = array();
  if (array_key_exists($kat->term_id, $cache)) return $cache[$kat->term_id];

  $jatek = null;
  $utotag = $kat->slug;
  if (strpos($kat->slug, '__') !== false) {
    list($elotag, $utotag) = explode('__', $kat->slug, 2);
    $jatek = get_term_by('slug', $elotag, 'game') ?: null;
  }

  // Régi / eltérő slugok (pl. assetto-corsa__cars, beamng-drive-trucks):
  // a kategória modjai közül a leggyakoribb játék.
  if (!$jatek) {
    $modok = get_posts(array(
      'post_type'      => 'mod',
      'post_status'    => 'publish',
      'posts_per_page' => 30,
      'fields'         => 'ids',
      'no_found_rows'  => true,
      'tax_query'      => array(array('taxonomy' => 'mod_category', 'terms' => $kat->term_id)),
    ));
    $db = array();
    foreach ($modok as $mod_id) {
      foreach ((array) get_the_terms($mod_id, 'game') as $j) {
        if ($j instanceof WP_Term) $db[$j->term_id] = ($db[$j->term_id] ?? 0) + 1;
      }
    }
    if ($db) {
      arsort($db);
      $jatek = get_term((int) key($db), 'game');
      if (!$jatek instanceof WP_Term) $jatek = null;
    }
  }

  if (!$jatek) return $cache[$kat->term_id] = null;

  // A játékoldal szűrője a <játék-slug>__<kategória> alakú termekre épül.
  $szuro = '';
  if (strpos($kat->slug, $jatek->slug . '__') === 0) {
    if ($kat->count > 0) $szuro = $kat->slug;
  } else {
    // pl. assetto-corsa__cars → assetto-corsa-mods__cars, beamng-drive-trucks → beamng-drive-mods__trucks
    $darabok = explode('-', $utotag);
    while ($darabok && $szuro === '') {
      $par = get_term_by('slug', $jatek->slug . '__' . implode('-', $darabok), 'mod_category');
      if ($par && $par->count > 0) $szuro = $par->slug;
      array_shift($darabok);
    }
  }

  return $cache[$kat->term_id] = array($jatek, $szuro);
}

function cm_modkat_cel_url(WP_Term $kat) {
  $par = cm_modkat_jatek_es_szuro($kat);
  if (!$par) return '';
  list($jatek, $szuro) = $par;
  $url = get_term_link($jatek);
  if (is_wp_error($url)) return '';
  return $szuro !== '' ? add_query_arg('category', rawurlencode($szuro), $url) : $url;
}

// 1) 301-es átirányítás a kategória-archívumról (a lapozott változatról is az első oldalra).
add_action('template_redirect', function () {
  // is_tax('game'): ha a játékoldal szűrője a lekérdezésbe mod_category-t is tesz, az ne legyen hurok.
  if (!is_tax('mod_category') || is_tax('game') || is_feed()) return;
  $kat = get_queried_object();
  if (!$kat instanceof WP_Term) return;

  $cel = cm_modkat_cel_url($kat);
  if ($cel === '') return; // nincs hozzá játék → marad a régi oldal

  $parameterek = wp_unslash($_GET);
  unset($parameterek['category'], $parameterek['paged'], $parameterek['mod_category']);
  if ($parameterek) $cel = add_query_arg(rawurlencode_deep($parameterek), $cel);

  wp_safe_redirect($cel, 301, 'CrashMods');
  exit;
}, 1);

// 2) Minden get_term_link() (breadcrumb, mod-oldal kategória link, menük) rögtön a szűrős játékoldalra mutat.
add_filter('term_link', function ($url, $term, $taxonomy) {
  if ($taxonomy !== 'mod_category' || !$term instanceof WP_Term) return $url;
  $cel = cm_modkat_cel_url($term);
  return $cel !== '' ? $cel : $url;
}, 20, 3);

// 3) A kategória-archívumok ne kerüljenek a Yoast sitemapba.
add_filter('wpseo_sitemap_exclude_taxonomy', function ($kizar, $taxonomy) {
  return $taxonomy === 'mod_category' ? true : $kizar;
}, 10, 2);
