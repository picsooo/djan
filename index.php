<?php
/* Touring with Mina — public site entry point. */
require __DIR__ . '/app/bootstrap.php';
if (!installed()) { header('Location: install.php'); exit; }
require APP . '/front.php';

$lang = ($_GET['l'] ?? '') === 'en' ? 'en' : 'fr';
$p = (string)($_GET['p'] ?? '');

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
