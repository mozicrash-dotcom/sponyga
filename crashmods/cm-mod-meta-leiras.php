<?php
/**
 * Plugin Name: CrashMods — mod-oldalak Google-leírása + ** jelek javítása
 * Description: Tulaj 2026-10-01: a mod-oldalak meta leírása eddig így nézett ki:
 *   "Lada 2101 – 35 Configurations - Features: 35 configurations High-quality exterior…"
 *   (cím + összeragasztott Features-lista, félbevágva). Helyette a leírás első rendes
 *   bekezdéséből épül egy max. 155 karakteres, mondathatáron vágott angol leírás.
 *   A kézzel írt (nem automatikus) Yoast-leírásokhoz nem nyúl.
 *   Emellett a mod-leírásokban nyersen maradt **félkövér** jelölést <strong>-ra cseréli.
 *   Visszaállítás: a fájl törlése.
 */

if (!defined('ABSPATH')) exit;

const CM_METALEIRAS_MAX = 155;

/** A leírás HTML-jéből a rendes (nem lista, nem "Features:") bekezdések szövege. */
function cm_metaleiras_bekezdesek($html) {
  $html = preg_replace('#<(ul|ol|table|figure|script|style)\b.*?</\1>#is', ' ', $html);
  $kimenet = array();
  foreach (preg_split('#</p>|<br\s*/?>|</h[1-6]>|</div>#i', $html) as $resz) {
    $szoveg = html_entity_decode(wp_strip_all_tags($resz), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $szoveg = str_replace(array('**', '__', 'BeamNG. drive'), array('', '', 'BeamNG.drive'), $szoveg);
    $szoveg = trim(preg_replace('/\s+/u', ' ', $szoveg));
    if (mb_strlen($szoveg) < 60) continue;
    if (preg_match('/^(features?|requirements?|installation|changelog|credits?)\b/i', $szoveg)) continue;
    if (preg_match('/^[-–—•*+]/u', $szoveg) || preg_match('/:$/', $szoveg)) continue;
    $kimenet[] = $szoveg;
  }
  return $kimenet;
}

/** Szöveg rövidítése mondathatáron; ha az első mondat is túl hosszú, szóhatáron + "…". */
function cm_metaleiras_rovidit($szoveg, $max) {
  if (mb_strlen($szoveg) <= $max) return $szoveg;
  $mondatok = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9"“])/u', $szoveg);
  $eredmeny = '';
  foreach ($mondatok as $mondat) {
    $proba = $eredmeny === '' ? $mondat : $eredmeny . ' ' . $mondat;
    if (mb_strlen($proba) > $max) break;
    $eredmeny = $proba;
  }
  if ($eredmeny !== '') return $eredmeny;
  // Tagmondathatár (vessző, gondolatjel, pontosvessző) → kerek mondat ponttal.
  $resz = mb_substr($mondatok[0], 0, $max);
  if (preg_match('/^(.{50,}?)(?:,|;|\s[–—]\s|—)(?!.*(?:,|;|\s[–—]\s|—))/u', $resz, $t)) {
    return rtrim($t[1], ' ,;') . '.';
  }
  // Második esély: "… into the game with a strong focus on…" → "… into the game."
  if (preg_match('/^(.{50,})\s(?:with|featuring|that|which|thanks to|including)\s/u', $resz, $t)) {
    return rtrim($t[1], ' ,;') . '.';
  }
  $vagott = mb_substr($szoveg, 0, $max - 1);
  $vagott = preg_replace('/[\s,;:–-]+\S*$/u', '', $vagott);
  return rtrim($vagott, " ,;:–-") . '…';
}

/**
 * Az új leírás. Példa:
 * "The Lada 2101 arrives with an impressive 35 configurations, giving you plenty of variants
 *  to spawn straight from the vehicle selector. Free BeamNG.drive mod."
 */
function cm_metaleiras_epit($cim, $jatek, $html) {
  $cim = trim(html_entity_decode(wp_strip_all_tags($cim), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  $vege = $jatek !== '' ? ' Free mod for ' . $jatek . '.' : ' Free mod download.';
  $bekezdesek = cm_metaleiras_bekezdesek($html);

  if ($bekezdesek) {
    $elso = $bekezdesek[0];
    $alap = cm_metaleiras_rovidit($elso, CM_METALEIRAS_MAX - mb_strlen($vege));
    // Ha a teljes első mondat elfér záró mondat nélkül, inkább az (ne legyen csonka).
    $mondat = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9"“])/u', $elso)[0];
    if (mb_strlen($mondat) <= CM_METALEIRAS_MAX && mb_strlen($mondat) > mb_strlen($alap)) return $mondat;
    if (mb_substr($alap, -1) === '…') return $alap; // nincs hely a záró mondatnak
    return $alap . $vege;
  }

  // Nincs rendes bekezdés: cím + az első 3 lista-elem.
  preg_match_all('#<li\b[^>]*>(.*?)</li>#is', $html, $talalat);
  $elemek = array();
  foreach ($talalat[1] as $li) {
    $t = trim(html_entity_decode(wp_strip_all_tags($li), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $t = trim(str_replace('**', '', $t), " .;");
    if ($t === '' || preg_match('/^(for |made by|by |author|version|requires?)\b/i', $t)) continue;
    $elemek[] = $t;
    if (count($elemek) === 3) break;
  }
  $alap = $cim . ($jatek !== '' ? ', a free ' . $jatek . ' mod' : ', a free mod');
  if ($elemek) $alap .= ': ' . implode(', ', $elemek);
  return cm_metaleiras_rovidit($alap . '.', CM_METALEIRAS_MAX);
}

/** Automatikusan generált (csúnya) leírás-e — a kézzel írtakhoz nem nyúlunk. */
function cm_metaleiras_automatikus($leiras, $cim) {
  $leiras = trim((string) $leiras);
  if ($leiras === '') return true;
  $cim = trim(html_entity_decode(wp_strip_all_tags($cim), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  return strpos($leiras, $cim . ' - ') === 0
    || stripos($leiras, 'Features:') !== false
    || mb_substr($leiras, -1) === '…'
    || mb_substr($leiras, -3) === '...';
}

function cm_metaleiras_szurt($leiras) {
  if (!is_singular('mod')) return $leiras;
  $post = get_queried_object();
  if (!$post instanceof WP_Post || !cm_metaleiras_automatikus($leiras, $post->post_title)) return $leiras;

  static $cache = array();
  if (!isset($cache[$post->ID])) {
    $jatekok = get_the_terms($post->ID, 'game');
    $jatek = (is_array($jatekok) && $jatekok) ? $jatekok[0]->name : '';
    $html = wpautop(strip_shortcodes($post->post_content));
    $cache[$post->ID] = cm_metaleiras_epit($post->post_title, $jatek, $html);
  }
  return $cache[$post->ID];
}
add_filter('wpseo_metadesc', 'cm_metaleiras_szurt', 20);
add_filter('wpseo_opengraph_desc', 'cm_metaleiras_szurt', 20);
add_filter('wpseo_twitter_description', 'cm_metaleiras_szurt', 20);

// ** nyers markdown félkövér → <strong> a mod-leírásokban (HTML-tagekbe nem nyúl).
add_filter('the_content', function ($html) {
  if (get_post_type() !== 'mod' || strpos($html, '**') === false) return $html;
  $darabok = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
  foreach ($darabok as $i => $d) {
    if ($d === '' || $d[0] === '<') continue;
    $darabok[$i] = preg_replace('/\*\*(?=\S)(.{1,200}?)(?<=\S)\*\*/u', '<strong>$1</strong>', $d);
  }
  return implode('', $darabok);
}, 20);
