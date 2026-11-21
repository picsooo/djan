<?php
/* Local development only: php -S localhost:8080 app/dev-router.php (emulates .htaccess). */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = dirname(__DIR__);
if (preg_match('#^/(app|data)(/|$)#', $uri)) { http_response_code(403); exit('Forbidden'); }
if ($uri !== '/' && is_file($root . $uri)) {
    if (str_ends_with($uri, '.php')) { $_SERVER['SCRIPT_NAME'] = $uri; chdir(dirname($root . $uri)); require $root . $uri; return true; }
    return false;
}
if (is_dir($root . $uri) && is_file($root . rtrim($uri, '/') . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = rtrim($uri, '/') . '/index.php'; chdir($root . rtrim($uri, '/')); require $root . rtrim($uri, '/') . '/index.php'; return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
if ($uri === '/sitemap.xml') $_GET['p'] = 'sitemap';
elseif ($uri === '/robots.txt') $_GET['p'] = 'robots';
elseif (preg_match('#^/en/?$#', $uri)) $_GET['l'] = 'en';
elseif (preg_match('#^/en/([a-z0-9-]+)\.html$#', $uri, $m)) { $_GET['l'] = 'en'; $_GET['p'] = $m[1]; }
elseif (preg_match('#^/([a-z0-9-]+)\.html$#', $uri, $m)) $_GET['p'] = $m[1];
chdir($root);
require $root . '/index.php';
return true;
