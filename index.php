<?php
/* Touring with Mina — public site entry point. */
require __DIR__ . '/app/bootstrap.php';
// Pages always reflect the latest content saved in the admin (no browser or server cache).
header('Cache-Control: no-cache, no-store, must-revalidate'); header('Pragma: no-cache'); header('X-LiteSpeed-Cache-Control: no-cache');
if (!installed()) { header('Location: install.php'); exit; }
migrate();
require APP . '/front.php';

$lang = ($_GET['l'] ?? '') === 'en' ? 'en' : 'fr';
$p = (string)($_GET['p'] ?? '');

// English first: the home page opens in English unless the visitor has chosen or is browsing the French version.
if ($lang === 'fr' && !empty(settings()['en_default'])) {
    $home = in_array(preg_replace('/\.html$/', '', strtolower($p)), ['', 'index'], true);
    $choseFr = isset($_GET['fr']) || !empty($_COOKIE['tw_fr']);
    if ($home && !$choseFr) { header('Location: ' . base_url() . '/en/', true, 302); exit; }
    if (empty($_COOKIE['tw_fr']) && !in_array($p, ['sitemap', 'robots'], true)) setcookie('tw_fr', '1', ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

if ($p === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    echo sitemap();
    exit;
}
if ($p === 'robots') {
    header('Content-Type: text/plain; charset=utf-8');
    $dom = rtrim((string)(settings()['domain'] ?? ''), '/');
    echo empty(settings()['indexable'])
        ? "User-agent: *\nDisallow: /\n"
        : "User-agent: *\nDisallow: /admin/\nDisallow: /api.php\n\nSitemap: $dom/sitemap.xml\n";
    exit;
}
header('Content-Type: text/html; charset=utf-8');
echo render_route($lang, $p);
