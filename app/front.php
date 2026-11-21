<?php
/* Public site renderer (animated version of the Touring with Mina design). */
declare(strict_types=1);

$GLOBALS['L'] = 'fr';

function L(): string { return $GLOBALS['L']; }
function U(string $k): string { static $u = []; $l = L(); if (!isset($u[$l])) $u[$l] = page('ui')[$l] ?? []; return (string)($u[$l][$k] ?? $k); }
function eu(string $k): string { return e(U($k)); }
function img(?string $p): string { return e(asset($p)); }
/** Language block of a page doc, with the French block as fallback for missing keys. */
function P(string $slug): array { $p = page($slug); return array_replace($p['fr'] ?? [], $p[L()] ?? []); }
function Pfr(string $slug): array { return page($slug)['fr'] ?? []; }
function T(array $doc): array { return array_replace($doc['data']['fr'] ?? [], $doc['data'][L()] ?? []); }
function wa(): string { return preg_replace('/\D/', '', (string)(settings()['wa'] ?? '')); }

function dur(array $c): string
{
    $d = (int)($c['data']['d'] ?? 0); $n = (int)($c['data']['n'] ?? 0);
    return $d ? "$d " . U('days') . ", $n " . U('nights') : U('custom');
}
function badge(): string
{
    return '<span class="illus" title="' . eu('illus') . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Zm11-13 2 2"/></svg>' . eu('illus') . '</span>';
}
/** "Illustrative content" badge, shown until the client turns it off in Settings. */
function illus(): string { return !empty(settings()['show_illus']) ? badge() : ''; }
function stars(int $r): string
{
    $r = max(1, min(5, $r));
    return '<span class="stars" role="img" aria-label="' . $r . '/5">' . str_repeat('<i class="on">★</i>', $r) . str_repeat('<i>★</i>', 5 - $r) . '</span>';
}

/* ---------- layout ---------- */
function shell(string $file, string $body, array $o = []): string
{
    $_ = fn($s) => $s; // lets expressions be interpolated in the heredoc below
    $S = settings(); $l = L(); $Sl = array_replace($S['fr'] ?? [], $S[$l] ?? []);
    $site = $S['site_name'] ?? 'Touring with Mina';
    $title = isset($o['title']) ? $o['title'] . ' | ' . $site : ($Sl['seo_title'] ?? $site);
    $desc = $o['desc'] ?? ($Sl['tagline'] ?? '');
    $dom = rtrim((string)($S['domain'] ?? ''), '/') ?: ((is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_url());
    $path = $file === 'index.html' ? '' : $file;
    $frUrl = "$dom/$path"; $enUrl = "$dom/en/$path";
    $robots = !empty($S['indexable']) ? 'index, follow' : 'noindex, nofollow';
    $ogImg = $o['og'] ?? ($S['og_img'] ?? '');
    $ogUrl = preg_match('#^https?://#', $ogImg) ? $ogImg : "$dom/" . ltrim($ogImg, '/');
    $nav = [['presentation.html', 'about'], ['circuits.html', 'trips'], ['destinations.html', 'dest'], ['infos-pratiques.html', 'infos'], ['galerie.html', 'gallery'], ['blog.html', 'blog']];
    $links = '';
    foreach ($nav as [$h, $k]) {
        $cur = $h === $file || ($h === 'circuits.html' && str_starts_with($file, 'circuit-')) || ($h === 'blog.html' && str_starts_with($file, 'blog-'));
        $links .= '<a href="' . $h . '"' . ($cur ? ' aria-current="page"' : '') . '>' . eu($k) . '</a>';
    }
    $other = $l === 'fr' ? 'en/' . $path : '../' . $path;
    $otherL = $l === 'fr' ? 'en' : 'fr';
    $logo = e($site);
    $logoCls = 'logo';
    if (!empty($S['logo'])) {
        $logo = '<img class="lg-d" src="' . img($S['logo']) . '" alt="' . e($site) . '">';
        if (!empty($S['logo_light'])) { $logo .= '<img class="lg-l" src="' . img($S['logo_light']) . '" alt="">'; $logoCls .= ' has-l'; }
        $logoCls .= ' has-img';
    }
    $w = wa();
    $waDisp = e($S['wa_display'] ?? '');
    $contact = '';
    if ($w) $contact .= "<a href=\"https://wa.me/$w\" target=\"_blank\" rel=\"noopener\">$waDisp</a>";
    if (!empty($S['phone'])) $contact .= '<a href="tel:' . e($S['phone']) . '">' . e($S['phone_display'] ?? $S['phone']) . '</a>';
    if (!empty($S['email'])) $contact .= '<a href="mailto:' . e($S['email']) . '">' . e($S['email']) . '</a>';
    if (!empty($S['address'])) $contact .= '<span>' . e($S['address']) . '</span>';
    $follow = '';
    if (!empty($S['instagram'])) $follow .= '<a href="' . e($S['instagram']) . '" target="_blank" rel="noopener">Instagram</a>';
    if (!empty($S['facebook'])) $follow .= '<a href="' . e($S['facebook']) . '" target="_blank" rel="noopener">Facebook</a>';
    $year = date('Y');
    $credit = e($Sl['credit'] ?? '');
    $api = e(asset('api.php'));
    $css = e(asset('assets/site.css')) . '?v=' . CMS_VERSION;
    $cms = e(asset('assets/cms.css')) . '?v=' . CMS_VERSION;
    $js = e(asset('assets/site.js')) . '?v=' . CMS_VERSION;
    $cjs = e(asset('assets/cms.js')) . '?v=' . CMS_VERSION;
    $font = e(asset('assets/fonts/antonio-latin.woff2'));
    $extra = $o['head'] ?? '';
    $cls = $o['cls'] ?? '';
    $canon = $l === 'fr' ? $frUrl : $enUrl;
    return <<<HTML
<!DOCTYPE html>
<html lang="$l">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$_(e($title))}</title>
<meta name="robots" content="$robots">
<meta name="description" content="{$_(e($desc))}">
<link rel="canonical" href="{$_(e($canon))}">
<link rel="alternate" hreflang="fr" href="{$_(e($frUrl))}">
<link rel="alternate" hreflang="en" href="{$_(e($enUrl))}">
<meta property="og:title" content="{$_(e($o['title'] ?? $site))}">
<meta property="og:description" content="{$_(e($desc))}">
<meta property="og:image" content="{$_(e($ogUrl))}">
<meta property="og:type" content="website">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="{$_(!empty($S['favicon']) ? img($S['favicon']) : 'data:,')}">
<link rel="preload" href="$font" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="$css">
<link rel="stylesheet" href="$cms">
$extra</head>
<body class="v-ani $cls" data-page="$file">
<div class="sprog" id="sprog" aria-hidden="true"></div>
<a class="skip" href="#main">{$_(eu('skip'))}</a>
<header class="nav" id="nav">
  <a class="$logoCls" href="./">$logo</a>
  <nav class="nav-links" id="navLinks" aria-label="Menu">$links<a class="nav-book" href="reservation.html">{$_(eu('book'))}</a></nav>
  <div class="nav-r">
    <a class="lang" href="$other" hreflang="$otherL">{$_(strtoupper($otherL))}</a>
    <a class="btn btn-nav" href="reservation.html">{$_(eu('book'))}</a>
    <button class="burger" id="burger" aria-expanded="false" aria-controls="navLinks"><span>{$_(eu('menu'))}</span></button>
  </div>
</header>
<main id="main">
$body
</main>
<footer class="foot">
  <div class="foot-top">
    <div><a class="logo" href="./">{$_(e($site))}</a><p>{$_(e($Sl['tagline'] ?? ''))}</p></div>
    <div class="foot-cols">
      <div><h4>{$_(eu('explore'))}</h4>$links</div>
      <div><h4>{$_(eu('plan'))}</h4><a href="reservation.html">{$_(eu('book'))}</a><a href="avis.html">{$_(eu('reviews'))}</a><a href="faq.html">{$_(eu('faq'))}</a><a href="contact.html">{$_(eu('contact_p'))}</a></div>
      <div><h4>{$_(eu('contact'))}</h4>$contact</div>
      <div><h4>{$_(eu('follow'))}</h4>$follow</div>
    </div>
  </div>
  <div class="foot-word" aria-hidden="true">{$_(e($site))}</div>
  <div class="foot-bot"><span>© $year {$_(e($site))}</span><span></span><a href="https://webminds.dz" target="_blank" rel="noopener">$credit</a></div>
</footer>
<a class="wa" href="https://wa.me/$w" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.4-.7-2.8-1.1-4.6-4-4.8-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.4Z"/></svg></a>
<div class="lb" id="lb" aria-hidden="true"><button class="lb-x" data-lb="x" aria-label="{$_(eu('close'))}">×</button><button class="lb-p" data-lb="p" aria-label="‹">‹</button><img id="lbImg" alt=""><button class="lb-n" data-lb="n" aria-label="›">›</button></div>
<script>window.TWAPI="$api";window.TWLANG="$l";</script>
<script src="$js"></script>
<script src="$cjs"></script>
</body>
</html>
HTML;
}

function phead(string $h, string $p = '', ?string $imgPath = null, string $extra = ''): string
{
    $bg = $imgPath ? '<div class="phead-bg" style="background-image:url(' . img($imgPath) . ')" data-par="0.18"></div>' : '';
    $pp = $p !== '' ? '<p class="rv">' . e($p) . '</p>' : '';
    return '<section class="phead' . ($imgPath ? ' has-img' : '') . '">' . $bg . '<div class="phead-in"><h1 class="rv">' . e($h) . '</h1>' . $pp . $extra . '</div></section>';
}
function sec_head(string $h, string $p = '', ?array $link = null): string
{
    $l = $link ? '<a class="link" href="' . e($link[0]) . '">' . e($link[1]) . '</a>' : '';
    return '<div class="sec-head"><h2 class="rv">' . e($h) . '</h2>' . ($p !== '' ? '<p class="rv">' . e($p) . '</p>' : '') . $l . '</div>';
}
function tcard(array $c): string
{
    $t = T($c);
    $price = !empty($c['data']['price']) ? '<span class="price">' . e($c['data']['price']) . '</span>' : '';
    return '<a class="tcard rv" href="circuit-' . e($c['slug']) . '.html"><figure><img src="' . img($c['data']['img'] ?? '') . '" alt="' . e($t['t'] ?? '') . '" loading="lazy"></figure><div class="tcard-b"><span class="meta">' . e(dur($c)) . '</span><h3>' . e($t['t'] ?? '') . '</h3><p>' . e($t['s'] ?? '') . '</p>' . $price . '<span class="more">' . eu('see_trip') . '</span></div></a>';
}
function pcard(array $p): string
{
    $t = T($p);
    return '<a class="pcard rv" href="blog-' . e($p['slug']) . '.html"><figure><img src="' . img($p['data']['img'] ?? '') . '" alt="" loading="lazy"></figure><span class="meta">' . fdate($p['data']['date'] ?? '', L()) . '</span><h3>' . e($t['t'] ?? '') . '</h3><span class="more">' . eu('read') . '</span></a>';
}
function cta(): string
{
    $w = wa();
    $img = img(page('home')['cta_img'] ?? '');
    return '<section class="cta"><div class="cta-bg" style="background-image:url(' . $img . ')" data-par="0.15"></div><div class="cta-in"><h2 class="rv">' . eu('cta_h') . '</h2><p class="rv">' . eu('cta_p') . '</p><div class="btns rv"><a class="btn btn-light" href="reservation.html">' . eu('plan') . '</a><a class="btn btn-ghost" href="https://wa.me/' . $w . '" target="_blank" rel="noopener">WhatsApp</a></div></div></section>';
}
function faq_items(array $items): string
{
    $o = '';
    foreach ($items as $f) { $t = T($f); $o .= '<details class="qa rv"><summary>' . e($t['q'] ?? '') . '</summary><div class="qa-a"><p>' . md_inline($t['a'] ?? '') . '</p></div></details>'; }
    return $o;
}
function circuit_title(string $slug): string { $c = doc_by_slug('circuit', $slug); return $c ? (string)(T($c)['t'] ?? '') : ''; }

function review_card(array $r): string
{
    $d = $r['data'];
    $trip = '';
    if (!empty($d['circuit']) && ($ct = circuit_title($d['circuit'])) !== '') $trip = '<a href="circuit-' . e($d['circuit']) . '.html">' . e($ct) . '</a>';
    $who = e($d['name'] ?? '') . (!empty($d['origin']) ? ' <span>' . e($d['origin']) . '</span>' : '');
    $date = !empty($d['date']) ? '<time>' . fdate($d['date'], L()) . '</time>' : '';
    return '<figure class="rev rv">' . stars((int)($d['rating'] ?? 5)) . '<blockquote>' . nl2br(e($d['text'] ?? '')) . '</blockquote><figcaption><b>' . $who . '</b>' . $trip . $date . '</figcaption></figure>';
}
function reviews_block(array $list, string $h, string $p, ?array $link): string
{
    if (!$list) return '';
    $sample = array_filter($list, fn($r) => !empty($r['data']['sample'])) ? '<div class="rev-badge">' . badge() . '</div>' : '';
    return '<section class="sec revs">' . sec_head($h, $p, $link) . $sample . '<div class="rev-row">' . implode('', array_map('review_card', $list)) . '</div></section>';
}

/* ---------- day in the desert ---------- */
function day_section(): string
{
    $D = P('day'); $Dfr = Pfr('day');
    $imgs = $steps = $ticks = '';
    foreach (($D['steps'] ?? []) as $i => $s) {
        $im = img($Dfr['steps'][$i]['img'] ?? ($s['img'] ?? ''));
        $on = $i === 0 ? ' on' : '';
        $imgs .= "<div class=\"di$on\" style=\"background-image:url($im)\"></div>";
        $steps .= "<article class=\"ds$on\"><figure><img src=\"$im\" alt=\"\" loading=\"lazy\"></figure><span class=\"ds-k\">" . e($s['k'] ?? '') . '</span><h3>' . e($s['t'] ?? '') . '</h3><p>' . e($s['p'] ?? '') . '</p></article>';
        $ticks .= '<li>' . e($s['k'] ?? '') . '</li>';
    }
    if ($steps === '') return '';
    return '<section class="day" id="day"><div class="day-stick"><div class="day-imgs" aria-hidden="true">' . $imgs . '</div><canvas class="day-stars" id="stars" aria-hidden="true"></canvas><div class="day-shade" aria-hidden="true"></div>'
        . '<div class="day-sky" aria-hidden="true"><svg viewBox="0 0 1000 400" preserveAspectRatio="none"><path d="M40 380 Q500 -180 960 380" /></svg><i class="day-sun" id="daySun"></i></div>'
        . '<header class="day-h"><h2>' . e($D['h'] ?? '') . '</h2><p>' . e($D['p'] ?? '') . '</p></header><div class="day-steps">' . $steps . '</div><ol class="day-ticks" aria-hidden="true">' . $ticks . '</ol></div></section>';
}

/* ---------- pages ---------- */
function page_home(): string
{
    $Hd = page('home'); $H = P('home'); $Hfr = Pfr('home');
    $circuits = docs('circuit');
    $lines = '';
    foreach (($H['lines'] ?? []) as $t) $lines .= '<span class="ln"><span>' . e($t) . '</span></span>';
    $opts = '';
    foreach ($circuits as $c) $opts .= '<option value="' . e($c['slug']) . '">' . e(T($c)['t'] ?? '') . '</option>';
    $B = P('booking');
    $mo = '';
    foreach (($B['months'] ?? []) as $i => $m) $mo .= '<option value="' . $i . '">' . e($m) . '</option>';
    $slides = '';
    foreach (($Hd['slides'] ?? []) as $i => $s) $slides .= '<div class="h2s' . ($i === 0 ? ' on' : '') . '" style="background-image:url(' . img($s) . ')"></div>';
    $n = max(1, count($Hd['slides'] ?? []));
    $mq = '';
    foreach (array_merge($Hd['marquee'] ?? [], $Hd['marquee'] ?? []) as $m) $mq .= '<span>' . e($m) . '</span>';
    $F = $B['f'] ?? [];
    $hero = '<section class="hero2" id="top"><div class="h2-slides">' . $slides . '</div><div class="h2-shade"></div>'
        . '<div class="h2-in"><p class="h2-k">' . e($H['kicker'] ?? '') . '</p><h1 class="h2-t" aria-label="' . e($H['title'] ?? '') . '">' . $lines . '</h1><p class="h2-sub">' . e($H['sub'] ?? '') . '</p></div>'
        . '<form class="qb" action="reservation.html" method="get">'
        . '<label><span>' . e($H['qb_trip'] ?? '') . '</span><select name="trip"><option value="">' . e($F['unsure'] ?? '') . '</option>' . $opts . '</select></label>'
        . '<label><span>' . e($H['qb_month'] ?? '') . '</span><select name="month"><option value="">' . e($F['flexible'] ?? '') . '</option>' . $mo . '</select></label>'
        . '<label><span>' . e($H['qb_people'] ?? '') . '</span><input type="number" name="adults" min="1" max="20" value="2"></label>'
        . '<button class="btn" type="submit">' . e($H['qb_go'] ?? '') . '</button></form>'
        . '<div class="h2-count" id="h2Count"><b>01</b><i></i><span>' . sprintf('%02d', $n) . '</span></div></section>'
        . ($mq ? '<div class="marquee" aria-hidden="true"><div class="mq">' . $mq . '</div></div>' : '');
    $ii = $Hd['intro_imgs'] ?? [];
    $intro = '<section class="intro"><div class="intro-t"><h2 class="rv">' . e($H['intro_h'] ?? '') . '</h2><p class="lead rv">' . e($H['intro_p'] ?? '') . '</p><p class="rv">' . e($H['intro_p2'] ?? '') . '</p><a class="link rv" href="presentation.html">' . e($H['intro_link'] ?? '') . '</a></div>'
        . '<div class="intro-m"><figure class="im1 rv-img"><img src="' . img($ii[0] ?? '') . '" alt="" loading="lazy" data-par="0.08"></figure><figure class="im2 rv-img"><img src="' . img($ii[1] ?? '') . '" alt="" loading="lazy" data-par="-0.06"></figure></div></section>';
    $facts = '';
    foreach (($H['facts'] ?? []) as $f) $facts .= '<div class="fact rv"><b data-count="' . e($f['n'] ?? '') . '">' . e($f['n'] ?? '') . '</b><span>' . e($f['t'] ?? '') . '</span></div>';
    $facts = $facts ? '<section class="facts">' . $facts . '<a class="facts-src" href="' . e($Hd['unesco_url'] ?? '#') . '" target="_blank" rel="noopener">' . e($H['facts_src'] ?? '') . '</a></section>' : '';
    $trips = '<section class="sec hz-sec" id="hz">' . sec_head($H['trips_h'] ?? '', $H['trips_p'] ?? '', ['circuits.html', U('all_trips')]) . '<div class="hz" id="hzTrack">' . implode('', array_map('tcard', $circuits)) . '</div></section>';
    $dc = '';
    foreach (docs('destination') as $d) { $t = T($d); $dc .= '<a class="dcard rv" href="destinations.html#' . e($d['slug']) . '"><figure><img src="' . img($d['data']['img'] ?? '') . '" alt="' . e($t['t'] ?? '') . '" loading="lazy"></figure><div><h3>' . e($t['t'] ?? '') . '</h3><p>' . e($t['s'] ?? '') . '</p></div></a>'; }
    $dests = $dc ? '<section class="sec">' . sec_head($H['dest_h'] ?? '', $H['dest_p'] ?? '', ['destinations.html', U('see_dest')]) . '<div class="dgrid">' . $dc . '</div></section>' : '';
    $inc = '';
    foreach (($H['incl'] ?? []) as $i => $x) $inc .= '<div class="inc rv"><figure><img src="' . img($Hfr['incl'][$i]['img'] ?? ($x['img'] ?? '')) . '" alt="" loading="lazy"></figure><h3>' . e($x['t'] ?? '') . '</h3><p>' . e($x['p'] ?? '') . '</p></div>';
    $incl = '<section class="sec">' . sec_head($H['incl_h'] ?? '', $H['incl_p'] ?? '') . '<div class="incl">' . $inc . '</div></section>';
    $quote = '<section class="quote"><figure class="rv-img"><img src="' . img($Hd['quote_img'] ?? '') . '" alt="" loading="lazy"></figure><blockquote class="rv">' . e($H['quote'] ?? '') . '<cite>' . e($H['quote_by'] ?? '') . '</cite></blockquote></section>';
    $revs = reviews_block(array_slice(docs('review'), 0, 6), $H['rev_h'] ?? U('reviews'), $H['rev_p'] ?? '', ['avis.html', U('reviews_all')]);
    $strip = '';
    foreach (($Hd['strip'] ?? []) as $s) $strip .= '<img src="' . img($s) . '" alt="" loading="lazy">';
    $gal = $strip ? '<section class="sec sec-gal">' . sec_head($H['gal_h'] ?? '', '', ['galerie.html', $H['gal_link'] ?? '']) . '<a class="strip" href="galerie.html" aria-label="' . e($H['gal_link'] ?? '') . '"><div class="strip-t">' . $strip . $strip . '</div></a></section>' : '';
    $home = array_values(array_filter(docs('faq'), fn($f) => !empty($f['data']['home'])));
    $faqs = $home ? '<section class="sec fhome">' . sec_head($H['faq_h'] ?? '', $H['faq_p'] ?? '', ['faq.html', U('all_faq')]) . '<div class="fhome-l">' . faq_items($home) . '</div></section>' : '';
    $posts = array_slice(docs('post'), 0, 3);
    $blog = $posts ? '<section class="sec">' . sec_head($H['blog_h'] ?? '', '', ['blog.html', U('all_posts')]) . '<div class="pgrid">' . implode('', array_map('pcard', $posts)) . '</div></section>' : '';
    $body = implode("\n", [$hero, $intro, $facts, $trips, $dests, day_section(), $incl, $quote, $revs, $gal, $faqs, $blog, cta()]);
    return shell('index.html', $body, ['desc' => $H['sub'] ?? null, 'cls' => 'is-home']);
}

function page_about(): string
{
    $Ad = page('about'); $A = P('about'); $Afr = Pfr('about');
    $rows = '';
    foreach (($A['blocks'] ?? []) as $i => $b) $rows .= '<div class="arow"><figure class="rv-img"><img src="' . img($Afr['blocks'][$i]['img'] ?? ($b['img'] ?? '')) . '" alt="" loading="lazy"></figure><div><h2 class="rv">' . e($b['t'] ?? '') . '</h2><p class="rv">' . e($b['p'] ?? '') . '</p></div></div>';
    $vals = '';
    foreach (($A['values'] ?? []) as $v) $vals .= '<div class="val rv"><h3>' . e($v['t'] ?? '') . '</h3><p>' . e($v['p'] ?? '') . '</p></div>';
    $proc = '';
    foreach (($A['proc'] ?? []) as $i => $s) $proc .= '<li class="rv"><i>' . ($i + 1) . '</i><h3>' . e($s['t'] ?? '') . '</h3><p>' . e($s['p'] ?? '') . '</p></li>';
    $body = phead($A['h'] ?? '', $A['lead'] ?? '', $Ad['hero_img'] ?? null)
        . '<section class="sec about">' . $rows . '</section>'
        . '<section class="sec">' . sec_head($A['values_h'] ?? '', $A['langs'] ?? '') . '<div class="vals">' . $vals . '</div></section>'
        . ($proc ? '<section class="sec proc-sec">' . sec_head($A['proc_h'] ?? '', $A['proc_p'] ?? '') . '<ol class="proc">' . $proc . '</ol><a class="btn rv" href="reservation.html">' . eu('plan') . '</a></section>' : '')
        . cta();
    return shell('presentation.html', $body, ['title' => $A['h'] ?? U('about'), 'desc' => $A['lead'] ?? null, 'og' => $Ad['hero_img'] ?? null]);
}

function page_trips(): string
{
    $Td = page('trips'); $T = P('trips');
    $body = phead($T['h'] ?? '', $T['p'] ?? '', $Td['hero_img'] ?? null, illus()) . '<section class="sec"><div class="tgrid tgrid-all">' . implode('', array_map('tcard', docs('circuit'))) . '</div></section>' . cta();
    return shell('circuits.html', $body, ['title' => U('trips'), 'desc' => $T['p'] ?? null, 'og' => $Td['hero_img'] ?? null]);
}

function page_trip(array $c): string
{
    $t = T($c); $d = $c['data'];
    $lab = !empty($d['d']) ? U('day') : U('step');
    $fx = [[U('duration'), dur($c)], [U('level'), $t['level'] ?? ''], [U('season'), $t['season'] ?? ''], [U('group'), $t['group'] ?? '']];
    if (!empty($d['price'])) $fx[] = [L() === 'fr' ? 'À partir de' : 'From', $d['price']];
    $facts = '';
    foreach ($fx as [$k, $v]) if ($v !== '') $facts .= '<div><span>' . e($k) . '</span><b>' . e($v) . '</b></div>';
    $days = '';
    foreach (($t['p'] ?? []) as $i => $s) $days .= '<li class="rv"><i>' . e($lab) . ' ' . ($i + 1) . '</i><div><h3>' . e($s['t'] ?? '') . '</h3><p>' . e($s['p'] ?? '') . '</p></div></li>';
    $Tp = P('trips');
    $inc = implode('', array_map(fn($x) => '<li>' . e($x) . '</li>', $Tp['included'] ?? []));
    $ninc = implode('', array_map(fn($x) => '<li>' . e($x) . '</li>', $Tp['not_included'] ?? []));
    $imgs = '';
    foreach (($d['imgs'] ?? []) as $p) if ($p) $imgs .= '<figure class="rv-img"><img src="' . img($p) . '" alt="" loading="lazy" data-zoom></figure>';
    $tags = implode('', array_map(fn($x) => '<span>' . e($x) . '</span>', $t['tags'] ?? []));
    $others = array_slice(array_values(array_filter(docs('circuit'), fn($o) => $o['id'] !== $c['id'])), 0, 3);
    $revs = array_values(array_filter(docs('review'), fn($r) => ($r['data']['circuit'] ?? '') === $c['slug']));
    $flights = L() === 'fr' ? 'Visa, vols, saison' : 'Visa, flights, season';
    $body = phead($t['t'] ?? '', $t['s'] ?? '', $d['img'] ?? null, '<div class="tags">' . $tags . '</div>' . illus())
        . '<section class="tfacts">' . $facts . '</section>'
        . '<section class="sec tdetail"><div><h2 class="rv">' . eu('program') . '</h2><ol class="days">' . $days . '</ol></div>'
        . '<aside><div class="box"><h3>' . eu('incl') . '</h3><ul class="yes">' . $inc . '</ul><h3>' . eu('notincl') . '</h3><ul class="no">' . $ninc . '</ul><a class="btn" href="reservation.html?trip=' . e($c['slug']) . '">' . eu('book_this') . '</a></div></aside></section>'
        . ($imgs ? '<section class="trio">' . $imgs . '</section>' : '')
        . reviews_block($revs, U('reviews'), '', ['avis.html', U('reviews_all')])
        . '<section class="sec know"><a class="know-c rv" href="infos-pratiques.html"><h3>' . eu('infos') . '</h3><span class="more">' . $flights . '</span></a><a class="know-c rv" href="faq.html"><h3>' . eu('faq') . '</h3><span class="more">' . eu('all_faq') . '</span></a><a class="know-c rv" href="destinations.html"><h3>' . eu('dest') . '</h3><span class="more">' . eu('see_dest') . '</span></a></section>'
        . ($others ? '<section class="sec">' . sec_head(U('other_trips'), '', ['circuits.html', U('all_trips')]) . '<div class="tgrid">' . implode('', array_map('tcard', $others)) . '</div></section>' : '')
        . cta();
    $ld = ['@context' => 'https://schema.org', '@type' => 'TouristTrip', 'name' => $t['t'] ?? '', 'description' => $t['s'] ?? '',
        'itinerary' => ['@type' => 'ItemList', 'itemListElement' => array_map(fn($i, $s) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $s['t'] ?? ''], array_keys($t['p'] ?? []), $t['p'] ?? [])]];
    return shell('circuit-' . $c['slug'] . '.html', $body, ['title' => $t['t'] ?? '', 'desc' => $t['s'] ?? null, 'og' => $d['img'] ?? null,
        'head' => '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n"]);
}

function page_destinations(): string
{
    $Dd = page('destinations'); $U = P('destinations');
    $list = docs('destination');
    $jump = $rows = '';
    foreach ($list as $i => $d) {
        $t = T($d); $x = $d['data'];
        $jump .= '<a href="#' . e($d['slug']) . '">' . e($t['t'] ?? '') . '</a>';
        $see = implode('', array_map(fn($v) => '<li>' . e($v) . '</li>', $t['see'] ?? []));
        $ps = implode('', array_map(fn($v) => '<p class="rv">' . e($v) . '</p>', $t['p'] ?? []));
        $rel = '';
        if (!empty($x['circuit']) && ($ct = circuit_title($x['circuit'])) !== '') $rel = '<h4>' . eu('related') . '</h4><a class="link" href="circuit-' . e($x['circuit']) . '.html">' . e($ct) . '</a>';
        $rows .= '<section class="drow" id="' . e($d['slug']) . '"><div class="dword" data-drift="' . ($i % 2 ? '-0.25' : '0.25') . '" aria-hidden="true">' . e($x['word'] ?? '') . '</div>'
            . '<div class="drow-m"><figure class="dm1 rv-img"><img src="' . img($x['img'] ?? '') . '" alt="' . e($t['t'] ?? '') . '" loading="lazy" data-zoom></figure>'
            . (!empty($x['img2']) ? '<figure class="dm2 rv-img"><img src="' . img($x['img2']) . '" alt="" loading="lazy" data-zoom></figure>' : '') . '</div>'
            . '<div class="drow-t"><p class="dk rv">' . e($t['s'] ?? '') . '</p><h2 class="rv">' . e($t['t'] ?? '') . '</h2>' . $ps
            . '<div class="dmeta rv"><div><h4>' . eu('to_see') . '</h4><ul>' . $see . '</ul></div><div><h4>' . eu('access') . '</h4><p>' . e($t['acc'] ?? '') . '</p>' . $rel . '</div></div></div></section>';
    }
    $body = phead($U['h'] ?? '', $U['p'] ?? '', $Dd['hero_img'] ?? null, '<nav class="jump" aria-label="' . eu('dest') . '">' . $jump . '</nav>') . '<div class="drows">' . $rows . '</div>' . cta();
    return shell('destinations.html', $body, ['title' => U('dest'), 'desc' => $U['p'] ?? null, 'og' => $Dd['hero_img'] ?? null]);
}

function page_infos(): string
{
    $Id = page('infos'); $I = P('infos');
    $toc = $out = '';
    foreach (($I['secs'] ?? []) as $s) {
        $id = e($s['id'] ?? slugify($s['t'] ?? ''));
        $toc .= '<a href="#' . $id . '">' . e($s['t'] ?? '') . '</a>';
        $extra = '';
        if (($s['id'] ?? '') === 'saison' && !empty($I['seasons'])) {
            $extra = '<div class="seasons">';
            foreach ($I['seasons'] as $k => $se) $extra .= '<div class="season s' . $k . '"><b>' . e($se['t'] ?? '') . '</b><span>' . e($se['p'] ?? '') . '</span></div>';
            $extra .= '</div>';
        }
        if (($s['id'] ?? '') === 'visa' && !empty($Id['visa_src'])) $extra = '<p class="src"><a href="' . e($Id['visa_src']) . '" target="_blank" rel="noopener">Source</a></p>';
        $li = array_filter($s['li'] ?? [], fn($v) => trim((string)$v) !== '');
        $lst = $li ? '<ul class="ticks">' . implode('', array_map(fn($v) => '<li>' . e($v) . '</li>', $li)) . '</ul>' : '';
        $ps = implode('', array_map(fn($v) => '<p>' . md_inline((string)$v) . '</p>', $s['ps'] ?? []));
        $out .= '<section class="isec rv" id="' . $id . '"><h2>' . e($s['t'] ?? '') . '</h2>' . $ps . $lst . $extra . '</section>';
    }
    $body = phead($I['h'] ?? '', $I['p'] ?? '', null, illus()) . '<section class="sec info-grid"><aside class="toc"><h4>' . eu('infos') . '</h4>' . $toc . '<a class="btn" href="reservation.html">' . eu('plan') . '</a></aside><div class="isecs">' . $out . (!empty($I['note']) ? '<p class="inote">' . e($I['note']) . '</p>' : '') . '</div></section>' . cta();
    return shell('infos-pratiques.html', $body, ['title' => U('infos'), 'desc' => $I['p'] ?? null]);
}

function page_faq(): string
{
    $F = P('faq');
    $groups = [];
    foreach (docs('faq') as $f) { $g = (string)(T($f)['g'] ?? ''); $groups[$g][] = $f; }
    $html = '';
    $ld = [];
    foreach ($groups as $g => $items) {
        $html .= '<div class="fgroup"><h2 class="rv">' . e($g) . '</h2><div>' . faq_items($items) . '</div></div>';
        foreach ($items as $f) { $t = T($f); $ld[] = ['@type' => 'Question', 'name' => $t['q'] ?? '', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags(md_inline($t['a'] ?? ''))]]; }
    }
    $w = wa();
    $more = '<section class="sec fmore"><h2 class="rv">' . e($F['more'] ?? '') . '</h2><p class="rv">' . e($F['more_p'] ?? '') . '</p><div class="btns rv"><a class="btn" href="contact.html">' . eu('contact_p') . '</a><a class="btn btn-line" href="https://wa.me/' . $w . '" target="_blank" rel="noopener">WhatsApp</a></div></section>';
    $body = phead($F['h'] ?? '', $F['p'] ?? '', null, illus()) . '<section class="sec fgroups">' . $html . '</section>' . $more . cta();
    $head = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
    return shell('faq.html', $body, ['title' => U('faq'), 'desc' => $F['p'] ?? null, 'head' => $head]);
}

function page_contact(): string
{
    $C = P('contact'); $S = settings(); $w = wa();
    $items = '';
    if ($w) $items .= '<a class="ct-i rv" href="https://wa.me/' . $w . '" target="_blank" rel="noopener"><span>' . e($C['wa'] ?? '') . '</span><b>' . e($S['wa_display'] ?? '') . '</b></a>';
    if (!empty($S['phone'])) $items .= '<a class="ct-i rv" href="tel:' . e($S['phone']) . '"><span>' . e($C['tel'] ?? '') . '</span><b>' . e($S['phone_display'] ?? $S['phone']) . '</b></a>';
    if (!empty($S['email'])) $items .= '<a class="ct-i rv" href="mailto:' . e($S['email']) . '"><span>' . e($C['mail'] ?? '') . '</span><b>' . e($S['email']) . '</b></a>';
    $items .= '<div class="ct-i rv"><span>' . e($C['where'] ?? '') . '</span><b>' . e($C['where_v'] ?? '') . '</b></div><div class="ct-i rv"><span>' . e($C['langs_l'] ?? '') . '</span><b>' . e($C['langs'] ?? '') . '</b></div>';
    $cfg = json_encode(['wa' => $w, 'mail' => $S['email'] ?? '', 'hello' => $C['hello'] ?? '', 'saved' => $C['saved'] ?? ''], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $body = phead($C['h'] ?? '', $C['p'] ?? '') . '<section class="sec ct"><div class="ct-l">' . $items . '</div>'
        . '<form class="ct-f box rv" id="ctf" novalidate><h3>' . e($C['form_h'] ?? '') . '</h3>'
        . '<label class="fld"><span class="fl">' . e($C['name'] ?? '') . '</span><input name="name" autocomplete="name"></label>'
        . '<label class="fld"><span class="fl">' . e($C['msg'] ?? '') . '</span><textarea name="msg" rows="5"></textarea></label>'
        . '<div class="btns"><button type="button" class="btn btn-wa" data-ct="wa">' . e($C['send_wa'] ?? '') . '</button><button type="button" class="btn" data-ct="mail">' . e($C['send_mail'] ?? '') . '</button></div>'
        . '<p class="ok" id="ctOk" role="status"></p>'
        . '<p class="ct-n">' . e($C['full'] ?? '') . ' <a href="reservation.html">' . e($C['full_l'] ?? '') . '</a>.</p></form></section>'
        . '<section class="ct-map rv"><iframe title="' . e($C['map'] ?? '') . '" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=9.28%2C24.42%2C9.68%2C24.68&amp;layer=mapnik&amp;marker=24.5542%2C9.4847"></iframe></section>'
        . '<script>window.TWC=' . $cfg . '</script>' . cta();
    return shell('contact.html', $body, ['title' => U('contact_p'), 'desc' => $C['p'] ?? null]);
}

function page_gallery(): string
{
    $G = P('gallery');
    $f = '';
    foreach (($G['filters'] ?? []) as $x) $f .= '<button data-f="' . e($x['k'] ?? '') . '"' . (($x['k'] ?? '') === 'all' ? ' class="on"' : '') . '>' . e($x['t'] ?? '') . '</button>';
    $items = '';
    foreach (docs('photo') as $p) $items .= '<figure class="gi" data-c="' . e($p['data']['cat'] ?? '') . '"><img src="' . img($p['data']['img'] ?? '') . '" alt="' . e($p['data']['alt'] ?? '') . '" loading="lazy" data-zoom></figure>';
    $body = phead($G['h'] ?? '', $G['p'] ?? '') . '<section class="sec gal"><div class="chips" id="galF">' . $f . '</div><div class="masonry" id="masonry">' . $items . '</div></section>' . cta();
    return shell('galerie.html', $body, ['title' => U('gallery'), 'desc' => $G['p'] ?? null]);
}

function page_blog(): string
{
    $B = P('blog');
    $body = phead($B['h'] ?? '', $B['p'] ?? '', null, illus()) . '<section class="sec"><div class="pgrid pgrid-all">' . implode('', array_map('pcard', docs('post'))) . '</div></section>' . cta();
    return shell('blog.html', $body, ['title' => U('blog'), 'desc' => $B['p'] ?? null]);
}

function page_post(array $p): string
{
    $t = T($p); $d = $p['data'];
    [$html, $toc] = md((string)($t['body'] ?? ''));
    $tocHtml = implode('', array_map(fn($x) => '<a href="#' . e($x[0]) . '">' . e($x[1]) . '</a>', $toc));
    $others = array_slice(array_values(array_filter(docs('post'), fn($o) => $o['id'] !== $p['id'])), 0, 3);
    $S = settings();
    $dom = rtrim((string)($S['domain'] ?? ''), '/');
    $ld = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $t['t'] ?? '', 'description' => $t['d'] ?? '', 'datePublished' => $d['date'] ?? '', 'inLanguage' => L(),
        'image' => $dom . '/' . ltrim((string)($d['img'] ?? ''), '/'), 'author' => ['@type' => 'Person', 'name' => $d['author'] ?? 'Mina'], 'publisher' => ['@type' => 'Organization', 'name' => $S['site_name'] ?? ''],
        'mainEntityOfPage' => $dom . '/' . (L() === 'en' ? 'en/' : '') . 'blog-' . $p['slug'] . '.html'];
    $by = e($d['author'] ?? '') !== '' ? (L() === 'fr' ? 'Par ' : 'By ') . e($d['author']) : eu('by');
    $body = '<article class="post"><header class="post-h"><a class="link back" href="blog.html">' . eu('blog') . '</a><h1 class="rv">' . e($t['t'] ?? '') . '</h1><p class="meta">' . $by . ', ' . fdate($d['date'] ?? '', L()) . ' ' . illus() . '</p></header>'
        . '<figure class="post-cover rv-img"><img src="' . img($d['img'] ?? '') . '" alt="' . e($t['t'] ?? '') . '"></figure>'
        . '<div class="post-grid"><aside class="toc"><h4>' . eu('toc') . '</h4>' . $tocHtml . '<a class="btn" href="reservation.html">' . eu('plan') . '</a></aside><div class="prose">' . $html . '</div></div></article>'
        . ($others ? '<section class="sec">' . sec_head(U('all_posts'), '', ['blog.html', U('blog')]) . '<div class="pgrid">' . implode('', array_map('pcard', $others)) . '</div></section>' : '')
        . cta();
    return shell('blog-' . $p['slug'] . '.html', $body, ['title' => $t['t'] ?? '', 'desc' => $t['d'] ?? null, 'og' => $d['img'] ?? null,
        'head' => '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n"]);
}

function page_booking(): string
{
    $B = P('booking'); $F = $B['f'] ?? []; $S = settings(); $w = wa();
    $chips = function (string $name, string $label, array $opts, bool $req = false): string {
        $o = '';
        foreach (array_values($opts) as $i => $v) $o .= '<label class="chip"><input type="radio" name="' . $name . '" value="' . e($v) . '" data-l="' . e($label) . '"' . ($req && $i === 0 ? ' required' : '') . '><span>' . e($v) . '</span></label>';
        return '<div class="fld"><span class="fl">' . e($label) . '</span><div class="chips">' . $o . '</div></div>';
    };
    $inp = fn(string $name, string $label, string $type = 'text', bool $req = false, string $attrs = '') =>
        '<label class="fld"><span class="fl">' . e($label) . '</span><input type="' . $type . '" name="' . $name . '" data-l="' . e($label) . '"' . ($req ? ' required' : '') . ' ' . $attrs . '></label>';
    $sel = fn(string $name, string $label, array $opts) =>
        '<label class="fld"><span class="fl">' . e($label) . '</span><select name="' . $name . '" data-l="' . e($label) . '">' . implode('', array_map(fn($o) => '<option>' . e($o) . '</option>', $opts)) . '</select></label>';
    $chk = fn(string $name, string $label, bool $req = false) =>
        '<label class="check"><input type="checkbox" name="' . $name . '" data-l="' . e($label) . '"' . ($req ? ' required' : '') . '><span>' . e($label) . '</span></label>';
    $tl = U('trips');
    $trip = '';
    foreach (docs('circuit') as $c) $trip .= '<label class="chip"><input type="radio" name="trip" value="' . e((T($c)['t'] ?? '') . ' (' . dur($c) . ')') . '" data-id="' . e($c['slug']) . '" data-l="' . e($tl) . '"><span>' . e(T($c)['t'] ?? '') . '</span></label>';
    $trip .= '<label class="chip"><input type="radio" name="trip" value="' . e($F['unsure'] ?? '') . '" data-id="" data-l="' . e($tl) . '" checked><span>' . e($F['unsure'] ?? '') . '</span></label>';
    $ml = L() === 'fr' ? 'Mois' : 'Month';
    $mo = '';
    foreach (($B['months'] ?? []) as $i => $m) $mo .= '<option data-i="' . $i . '">' . e($m) . '</option>';
    $month = '<label class="fld"><span class="fl">' . e($F['month'] ?? '') . '</span><select name="month" data-l="' . $ml . '"><option value=""></option>' . $mo . '</select></label>';
    $g = fn($k) => (string)($F[$k] ?? '');
    $a = fn($k) => (array)($F[$k] ?? []);
    $steps = [
        '<div class="fld"><span class="fl">' . e($g('trip')) . '</span><div class="chips">' . $trip . '</div></div><div class="row">' . $inp('arrival', $g('arrival'), 'date') . $month . '</div>' . $chk('flexible', $g('flexible')) . $sel('length', $g('length'), $a('lengths')),
        '<div class="row">' . $inp('adults', $g('adults'), 'number', true, 'min=1 max=30 value=2') . $inp('kids', $g('kids'), 'number', false, 'min=0 max=20 value=0') . '</div>' . $inp('kids_ages', $g('kids_ages')) . '<div class="row">' . $inp('nationality', $g('nationality'), 'text', true) . $inp('country', $g('country'), 'text', true) . '</div><div class="row">' . $inp('city', $g('city')) . $sel('group_type', $g('group_type'), $a('group_types')) . '</div>',
        $chips('visa', $g('visa'), $a('visas'), true) . $chk('passport', $g('passport')) . $chips('flight', $g('flight'), $a('flights')) . $chk('insurance', $g('insurance')),
        $chips('stay', $g('stay'), $a('stays')) . $chips('fitness', $g('fitness'), $a('fitnesses')) . $chips('guide', $g('guide'), $a('guides')) . $inp('diet', $g('diet')) . '<div class="row">' . $sel('occasion', $g('occasion'), $a('occasions')) . $sel('budget', $g('budget'), $a('budgets')) . '</div>',
        $inp('name', $g('name'), 'text', true, 'autocomplete=name') . '<div class="row">' . $inp('email', $g('email'), 'email', false, 'autocomplete=email') . $inp('phone', $g('phone'), 'tel', false, 'autocomplete=tel') . '</div>' . $chips('pref', $g('pref'), $a('prefs')) . '<label class="fld"><span class="fl">' . e($g('msg')) . '</span><textarea name="msg" rows="3" data-l="' . e($g('msg')) . '"></textarea></label>' . $chk('consent', $g('consent'), true),
    ];
    $names = array_values($B['steps'] ?? []);
    $fs = $prog = '';
    foreach ($steps as $i => $s) {
        $fs .= '<fieldset class="step' . ($i === 0 ? ' on' : '') . '" data-step="' . $i . '"><legend><i>' . ($i + 1) . '/5</i>' . e($names[$i] ?? '') . '</legend>' . $s . '</fieldset>';
        $prog .= '<li' . ($i === 0 ? ' class="on"' : '') . '><button type="button" data-go="' . $i . '"><i>' . ($i + 1) . '</i>' . e($names[$i] ?? '') . '</button></li>';
    }
    $cfg = json_encode(['wa' => $w, 'mail' => $S['email'] ?? '', 'hello' => $B['hello'] ?? '', 'subject' => $B['subject'] ?? '', 'yes' => $B['yes'] ?? '', 'empty' => $B['sum_empty'] ?? '', 'steps' => $names, 'saved' => $B['saved'] ?? ''], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $body = phead($B['h'] ?? '', $B['p'] ?? '') . '<section class="sec bk"><form id="bk" novalidate><ol class="prog" id="prog">' . $prog . '</ol>' . $fs
        . '<p class="err" id="bkErr" role="alert">' . e($B['err'] ?? '') . '</p><p class="ok" id="bkOk" role="status"></p>'
        . '<div class="bk-nav"><button type="button" class="btn btn-line" id="bkPrev" hidden>' . e($B['prev'] ?? '') . '</button><button type="button" class="btn" id="bkNext">' . e($B['next'] ?? '') . '</button>'
        . '<div class="bk-send" id="bkSend" hidden><button type="button" class="btn btn-wa" data-send="wa">' . e($B['wa'] ?? '') . '</button><button type="button" class="btn" data-send="mail">' . e($B['mail'] ?? '') . '</button></div></div></form>'
        . '<aside class="sum"><div class="box"><h3>' . e($B['sum_h'] ?? '') . '</h3><dl id="sum"></dl><p class="sum-d">' . e($B['direct'] ?? '') . '</p>'
        . ($w ? '<a href="https://wa.me/' . $w . '" target="_blank" rel="noopener">WhatsApp ' . e($S['wa_display'] ?? '') . '</a>' : '')
        . (!empty($S['email']) ? '<a href="mailto:' . e($S['email']) . '">' . e($S['email']) . '</a>' : '') . '</div></aside></section>'
        . '<script>window.TWM=' . $cfg . '</script>';
    return shell('reservation.html', $body, ['title' => U('book'), 'desc' => $B['p'] ?? null, 'cls' => 'is-book']);
}

function page_reviews(): string
{
    $R = P('reviews');
    $list = docs('review');
    $opts = '<option value=""></option>';
    foreach (docs('circuit') as $c) $opts .= '<option value="' . e($c['slug']) . '">' . e(T($c)['t'] ?? '') . '</option>';
    $rate = '';
    for ($i = 5; $i >= 1; $i--) $rate .= '<input type="radio" id="rt' . $i . '" name="rating" value="' . $i . '"' . ($i === 5 ? ' checked' : '') . '><label for="rt' . $i . '" title="' . $i . '/5">★</label>';
    $grid = $list ? '<div class="rev-grid">' . implode('', array_map('review_card', $list)) . '</div>' : '<p class="rev-empty">' . e($R['empty'] ?? '') . '</p>';
    $sample = array_filter($list, fn($r) => !empty($r['data']['sample'])) ? '<div class="rev-badge">' . badge() . '</div>' : '';
    $form = '<form class="box rev-form rv" id="revf" novalidate><h3>' . e($R['form_h'] ?? '') . '</h3>'
        . '<div class="row"><label class="fld"><span class="fl">' . e($R['name'] ?? '') . '</span><input name="name" maxlength="60" autocomplete="given-name"></label><label class="fld"><span class="fl">' . e($R['origin'] ?? '') . '</span><input name="origin" maxlength="60"></label></div>'
        . '<label class="fld"><span class="fl">' . e($R['trip'] ?? '') . '</span><select name="circuit">' . $opts . '</select></label>'
        . '<div class="fld"><span class="fl">' . e($R['rating'] ?? '') . '</span><div class="rate">' . $rate . '</div></div>'
        . '<label class="fld"><span class="fl">' . e($R['text'] ?? '') . '</span><textarea name="text" rows="5" maxlength="2000"></textarea></label>'
        . '<input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"><input type="hidden" name="t0" value="' . time() . '">'
        . '<p class="err" id="revErr" role="alert">' . e($R['error'] ?? '') . '</p><p class="ok" id="revOk" role="status">' . e($R['thanks'] ?? '') . '</p>'
        . '<button class="btn" type="submit">' . e($R['send'] ?? '') . '</button></form>';
    $body = phead($R['h'] ?? '', $R['p'] ?? '') . '<section class="sec revs-all">' . $sample . $grid . '</section><section class="sec rev-form-sec">' . $form . '</section>' . cta();
    return shell('avis.html', $body, ['title' => U('reviews'), 'desc' => $R['p'] ?? null]);
}

function page_404(): string
{
    http_response_code(404);
    $fr = L() === 'fr';
    $body = phead($fr ? 'Cette page n’existe pas' : 'This page does not exist', $fr ? 'Le lien est peut-être ancien. Repartez de l’accueil ou découvrez nos circuits.' : 'The link may be outdated. Go back home or discover our tours.',
        null, '<div class="btns rv"><a class="btn" href="./">' . ($fr ? 'Accueil' : 'Home') . '</a><a class="btn btn-line" href="circuits.html">' . eu('all_trips') . '</a></div>');
    return shell('index.html', $body, ['title' => '404']);
}

/* ---------- router ---------- */
function render_route(string $lang, string $p): string
{
    $GLOBALS['L'] = in_array($lang, LANGS, true) ? $lang : 'fr';
    $p = preg_replace('/\.html$/', '', strtolower($p));
    switch ($p) {
        case '': case 'index': return page_home();
        case 'presentation': return page_about();
        case 'circuits': return page_trips();
        case 'destinations': return page_destinations();
        case 'infos-pratiques': return page_infos();
        case 'faq': return page_faq();
        case 'contact': return page_contact();
        case 'galerie': return page_gallery();
        case 'blog': return page_blog();
        case 'reservation': return page_booking();
        case 'avis': return page_reviews();
    }
    if (str_starts_with($p, 'circuit-') && ($c = doc_by_slug('circuit', substr($p, 8)))) return page_trip($c);
    if (str_starts_with($p, 'blog-') && ($b = doc_by_slug('post', substr($p, 5)))) return page_post($b);
    return page_404();
}

function sitemap(): string
{
    $dom = rtrim((string)(settings()['domain'] ?? ''), '/');
    $pages = ['', 'presentation.html', 'circuits.html', 'destinations.html', 'infos-pratiques.html', 'faq.html', 'contact.html', 'galerie.html', 'blog.html', 'reservation.html', 'avis.html'];
    foreach (docs('circuit') as $c) $pages[] = 'circuit-' . $c['slug'] . '.html';
    foreach (docs('post') as $b) $pages[] = 'blog-' . $b['slug'] . '.html';
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    foreach ($pages as $p) foreach (LANGS as $l) {
        $u = $dom . '/' . ($l === 'en' ? 'en/' : '') . $p;
        $x .= '<url><loc>' . e($u) . '</loc><xhtml:link rel="alternate" hreflang="fr" href="' . e("$dom/$p") . '"/><xhtml:link rel="alternate" hreflang="en" href="' . e("$dom/en/$p") . '"/></url>';
    }
    return $x . '</urlset>';
}
