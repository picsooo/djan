<?php
/* Touring with Mina — administration. */
define('IN_ADMIN', true);
require dirname(__DIR__) . '/app/bootstrap.php';
header('Cache-Control: no-cache, no-store, must-revalidate'); header('Pragma: no-cache'); header('X-LiteSpeed-Cache-Control: no-cache');
require APP . '/admin_lib.php';

if (!installed()) { header('Location: ../install.php'); exit; }
migrate();
session_boot();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

const STATUSES = ['new' => 'Nouvelle', 'progress' => 'En cours', 'confirmed' => 'Confirmée', 'archived' => 'Archivée'];
const ROLES = ['admin' => 'Administrateur', 'editor' => 'Éditeur'];

/* ---------- helpers ---------- */
function url(string $r, array $q = []): string { return '?' . http_build_query(['r' => $r] + $q); }
function go(string $r, array $q = []): never { header('Location: ' . url($r, $q)); exit; }
function flash(string $msg, string $kind = 'ok'): void { $_SESSION['flash'][] = [$kind, $msg]; }
function json_out(array $d, int $code = 200): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function me(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        if (time() - (int)($_SESSION['seen'] ?? 0) > 12 * 3600) { $_SESSION = []; return null; }
        $st = db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
        if ($u) $_SESSION['seen'] = time();
    }
    return $u;
}
function is_admin(): bool { return (me()['role'] ?? '') === 'admin'; }
function counts(): array
{
    static $c = null;
    if ($c === null) $c = [
        'bookings' => (int)db()->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn(),
        'reviews' => (int)db()->query("SELECT COUNT(*) FROM docs WHERE type = 'review' AND published = 0")->fetchColumn(),
    ];
    return $c;
}
function icon(string $n): string
{
    $p = [
        'home' => 'M3 11 12 4l9 7v9h-6v-6H9v6H3z', 'route' => 'M6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm12-10a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM6 15V9a4 4 0 0 1 4-4h4m4 4v6a4 4 0 0 1-4 4h-4',
        'pin' => 'M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z', 'pen' => 'M4 20h4L19 9l-4-4L4 16v4Z',
        'help' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-2.5-11.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6m0 3v.1', 'image' => 'M4 5h16v14H4zM4 15l4-4 5 5 3-3 4 4M15 9.5a1 1 0 1 0 0-.1',
        'star' => 'm12 4 2.4 5 5.6.8-4 3.9 1 5.5-5-2.7-5 2.7 1-5.5-4-3.9 5.6-.8z', 'inbox' => 'M4 13h4l1.5 3h5L16 13h4M5 5h14l1 8v6H4v-6z',
        'text' => 'M5 6h14M5 12h14M5 18h9', 'gear' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.3 7.3 0 0 0-2-1.2L14.5 3h-4l-.4 2.6a7.3 7.3 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.3 7.3 0 0 0 2 1.2l.4 2.6h4l.4-2.6a7.3 7.3 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z',
        'users' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-6 9a6 6 0 0 1 12 0m2-9a3 3 0 1 0 0-6m2 15a5 5 0 0 0-3-4.6', 'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'eye' => 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z', 'out' => 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10',
        'media' => 'M3 7h4l2-2h6l2 2h4v12H3zM12 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z',
    ][$n] ?? '';
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . $p . '"/></svg>';
}
function doc_title(array $d): string
{
    $x = $d['data'];
    $t = $x['fr']['t'] ?? $x['fr']['q'] ?? $x['name'] ?? $x['alt'] ?? '';
    if ($t === '' && $d['type'] === 'photo') $t = basename((string)($x['img'] ?? 'Photo'));
    return (string)($t !== '' ? $t : 'Sans titre');
}
function unique_slug(string $type, string $slug, int $id): string
{
    $slug = slugify($slug) ?: $type;
    $base = $slug; $i = 2;
    $st = db()->prepare('SELECT COUNT(*) FROM docs WHERE type = ? AND slug = ? AND id != ?');
    while (true) { $st->execute([$type, $slug, $id]); if (!(int)$st->fetchColumn()) return $slug; $slug = $base . '-' . $i++; }
}
function wa_link(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);
    if (str_starts_with($d, '00')) $d = substr($d, 2);
    elseif (str_starts_with($d, '0') && strlen($d) === 10) $d = '213' . substr($d, 1);
    return $d ? 'https://wa.me/' . $d : '';
}

/* ---------- layout ---------- */
function layout(string $title, string $body, string $active = '', string $actions = ''): never
{
    $S = settings(); $me = me(); $c = counts();
    $site = e($S['site_name'] ?? 'Site');
    $brand = !empty($S['logo']) ? '<img src="' . e(asset($S['logo'])) . '" alt="' . $site . '">' : '<b>' . $site . '</b>';
    $T = types();
    $nav = [
        ['dashboard', 'Tableau de bord', 'home', ''],
        ['—', 'Contenus'],
        ['list:circuit', 'Circuits', 'route', ''], ['list:destination', 'Destinations', 'pin', ''], ['list:post', 'Blog', 'pen', ''],
        ['list:faq', 'FAQ', 'help', ''], ['list:photo', 'Galerie', 'image', ''], ['list:review', 'Avis', 'star', $c['reviews']],
        ['—', 'Demandes'],
        ['bookings', 'Réservations & messages', 'inbox', $c['bookings']],
        ['—', 'Site'],
        ['list:section', 'Blocs de l’accueil', 'text', ''], ['pages', 'Textes des pages', 'text', ''], ['media', 'Médiathèque', 'media', ''],
    ];
    if (is_admin()) { $nav[] = ['settings', 'Réglages', 'gear', '']; $nav[] = ['users', 'Utilisateurs', 'users', '']; }
    $nav[] = ['account', 'Mon compte', 'user', ''];
    $links = '';
    foreach ($nav as $n) {
        if ($n[0] === '—') { $links .= '<span class="nav-h">' . e($n[1]) . '</span>'; continue; }
        [$r, $q] = str_contains($n[0], ':') ? [explode(':', $n[0])[0], ['type' => explode(':', $n[0])[1]]] : [$n[0], []];
        $badge = $n[3] ? '<em>' . (int)$n[3] . '</em>' : '';
        $links .= '<a href="' . e(url($r, $q)) . '"' . ($active === $n[0] ? ' aria-current="page"' : '') . '>' . icon($n[2]) . '<span>' . e($n[1]) . '</span>' . $badge . '</a>';
    }
    $flash = '';
    foreach ($_SESSION['flash'] ?? [] as [$k, $m]) $flash .= '<p class="flash ' . e($k) . '" role="status">' . e($m) . '</p>';
    unset($_SESSION['flash']);
    $csrf = e(csrf_token());
    $logout = e(url('logout', ['t' => csrf_token()]));
    $siteUrl = e(base_url() . '/');
    $name = e($me['name'] ?? '');
    $assets = e(base_url() . '/admin');
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="$csrf"><title>{$title} · Administration</title>
<link rel="icon" href="data:,"><link rel="stylesheet" href="$assets/admin.css?v=3"></head>
<body class="adm">
<aside class="side" id="side"><div class="brand">$brand<small>Administration</small></div><nav>$links</nav>
<div class="side-f">Réalisé par <a href="https://webminds.dz" target="_blank" rel="noopener">Webminds</a></div></aside>
<div class="main">
<header class="top"><button class="menu-b" type="button" data-menu aria-label="Menu">☰</button><h1>{$title}</h1><div class="top-r">$actions<a class="btn btn-line" href="$siteUrl" target="_blank" rel="noopener">{$GLOBALS['ic_eye']}Voir le site</a><span class="who">$name</span><a class="out" href="$logout" title="Se déconnecter">{$GLOBALS['ic_out']}</a></div></header>
<div class="content">$flash$body</div>
</div>
<div class="modal" id="picker" hidden><div class="modal-b"><header><h2>Médiathèque</h2><label class="btn">Envoyer des images<input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-upload></label><button type="button" class="x" data-close>×</button></header><p class="hint up-msg" data-up-msg></p><div class="grid-m" data-grid></div></div></div>
<script src="$assets/admin.js?v=3"></script>
</body></html>
HTML;
    exit;
}
$GLOBALS['ic_eye'] = icon('eye');
$GLOBALS['ic_out'] = icon('out');

/* ---------- routing ---------- */
$r = (string)($_GET['r'] ?? 'dashboard');
$post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

if ($r === 'login') {
    $err = '';
    if ($post) {
        if (!csrf_ok()) $err = 'La page a expiré, réessayez.';
        elseif (throttled('login', 6, 900)) $err = 'Trop de tentatives. Réessayez dans 15 minutes.';
        else {
            $st = db()->prepare('SELECT * FROM users WHERE email = ?');
            $st->execute([mb_strtolower(trim((string)($_POST['email'] ?? '')))]);
            $u = $st->fetch();
            if ($u && password_verify((string)($_POST['pass'] ?? ''), $u['pass'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$u['id']; $_SESSION['seen'] = time(); unset($_SESSION['csrf']);
                if (password_needs_rehash($u['pass'], PASSWORD_DEFAULT)) db()->prepare('UPDATE users SET pass = ? WHERE id = ?')->execute([password_hash((string)$_POST['pass'], PASSWORD_DEFAULT), $u['id']]);
                db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([now(), $u['id']]);
                go('dashboard');
            }
            throttle_hit('login');
            $err = 'E-mail ou mot de passe incorrect.';
        }
    }
    $S = settings();
    $brand = !empty($S['logo']) ? '<img src="' . e(asset($S['logo'])) . '" alt="">' : '';
    $site = e($S['site_name'] ?? '');
    $assets = e(base_url() . '/admin');
    $msg = $err ? '<p class="flash err">' . e($err) . '</p>' : '';
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>Connexion · ' . $site . '</title><link rel="icon" href="data:,"><link rel="stylesheet" href="' . $assets . '/admin.css?v=3"></head>'
        . '<body class="auth"><main class="auth-box">' . $brand . '<h1>' . $site . '</h1><p class="hint">Administration du site</p>' . $msg
        . '<form method="post" class="form">' . csrf_field() . '<label>E-mail<input type="email" name="email" required autocomplete="username" autofocus value="' . e($_POST['email'] ?? '') . '"></label>'
        . '<label>Mot de passe<input type="password" name="pass" required autocomplete="current-password"></label><button class="btn">Se connecter</button></form>'
        . '<p class="hint small">Mot de passe oublié ? Demandez à Webminds de le réinitialiser.</p></main></body></html>';
    exit;
}

if (!me()) go('login');
if ($r === 'logout') {
    if (hash_equals(csrf_token(), (string)($_GET['t'] ?? ''))) { $_SESSION = []; session_destroy(); }
    header('Location: ' . url('login')); exit;
}
if ($post && !csrf_ok()) {
    if (str_starts_with($r, 'upload')) json_out(['ok' => false, 'error' => 'Session expirée, rechargez la page.'], 400);
    flash('La page a expiré, vos changements n’ont pas été enregistrés. Réessayez.', 'err');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('dashboard'))); exit;
}
$T = types();

/* ---------- dashboard ---------- */
if ($r === 'dashboard') {
    $c = counts();
    $n = fn(string $t) => (int)db()->query("SELECT COUNT(*) FROM docs WHERE type = '$t' AND published = 1")->fetchColumn();
    $cards = [
        ['bookings', [], $c['bookings'], 'nouvelle(s) demande(s)', $c['bookings'] ? 'hot' : ''],
        ['list', ['type' => 'review'], $c['reviews'], 'avis à valider', $c['reviews'] ? 'hot' : ''],
        ['list', ['type' => 'circuit'], $n('circuit'), 'circuits en ligne', ''],
        ['list', ['type' => 'post'], $n('post'), 'articles de blog', ''],
    ];
    $h = '<div class="cards">';
    foreach ($cards as [$rr, $q, $num, $lab, $cls]) $h .= '<a class="card stat ' . $cls . '" href="' . e(url($rr, $q)) . '"><b>' . $num . '</b><span>' . e($lab) . '</span></a>';
    $h .= '</div>';
    $rows = db()->query('SELECT * FROM bookings ORDER BY id DESC LIMIT 6')->fetchAll();
    $h .= '<div class="cols"><section class="card"><header class="card-h"><h2>Dernières demandes</h2><a href="' . e(url('bookings')) . '">Tout voir</a></header>';
    if (!$rows) $h .= '<p class="empty">Aucune demande pour l’instant. Les demandes envoyées depuis le formulaire de réservation et la page Contact apparaîtront ici.</p>';
    else {
        $h .= '<ul class="mini">';
        foreach ($rows as $b) $h .= '<li><a href="' . e(url('booking', ['id' => $b['id']])) . '"><b>' . e($b['name'] ?: 'Sans nom') . '</b><span>' . e($b['kind'] === 'contact' ? 'Message' : ($b['trip'] ? (circuit_name($b['trip']) ?: $b['trip']) : 'Réservation')) . '</span><i class="st st-' . e($b['status']) . '">' . e(STATUSES[$b['status']] ?? $b['status']) . '</i><time>' . e(date('d/m H:i', strtotime($b['created']))) . '</time></a></li>';
        $h .= '</ul>';
    }
    $h .= '</section><section class="card"><header class="card-h"><h2>Raccourcis</h2></header><div class="quick">'
        . '<a href="' . e(url('edit', ['type' => 'circuit'])) . '">+ Nouveau circuit</a><a href="' . e(url('edit', ['type' => 'post'])) . '">+ Nouvel article</a>'
        . '<a href="' . e(url('edit', ['type' => 'review'])) . '">+ Ajouter un avis</a><a href="' . e(url('list', ['type' => 'photo'])) . '">+ Photos dans la galerie</a>'
        . '<a href="' . e(url('page', ['slug' => 'home'])) . '">Modifier la page d’accueil</a>' . (is_admin() ? '<a href="' . e(url('settings')) . '">Logo et coordonnées</a>' : '') . '</div></section></div>';
    if (!empty(settings()['show_illus'])) $h .= '<p class="note">Le badge « Contenu illustratif » est encore affiché sur le site. Quand vos vrais textes et circuits sont en place, décochez-le dans Réglages.</p>';
    layout('Bonjour ' . e(explode(' ', (string)me()['name'])[0]), $h, 'dashboard');
}

/* ---------- content lists ---------- */
if ($r === 'list' && isset($T[$_GET['type'] ?? ''])) {
    $type = $_GET['type']; $t = $T[$type];
    if ($type === 'section') ensure_sections();
    $all = docs($type, false);
    if ($type === 'review') usort($all, fn($a, $b) => $a['published'] <=> $b['published'] ?: $a['position'] <=> $b['position']);
    $csrf = csrf_field();
    $h = '<p class="lead">' . e($t['help']) . '</p>';
    if ($type === 'photo') {
        $h .= '<div class="drop" data-photo-drop><p><b>Ajouter des photos</b> : glissez-les ici ou <label class="lnk">choisissez des fichiers<input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-photo-files></label>.</p>'
            . '<label class="inl">Catégorie <select data-photo-cat>' . implode('', array_map(fn($k, $v) => '<option value="' . $k . '">' . $v . '</option>', array_keys(CATS), CATS)) . '</select></label><p class="hint up-msg" data-up-msg></p></div><div class="pgrid">';
        foreach ($all as $d) {
            $h .= '<div class="pcell' . ($d['published'] ? '' : ' off') . '"><a href="' . e(url('edit', ['type' => $type, 'id' => $d['id']])) . '"><img src="' . e(asset($d['data']['img'] ?? '')) . '" alt="" loading="lazy"></a><span>' . e(CATS[$d['data']['cat'] ?? ''] ?? '') . '</span>'
                . '<form method="post" action="' . e(url('move')) . '">' . $csrf . '<input type="hidden" name="id" value="' . $d['id'] . '"><button name="dir" value="up" title="Avant">←</button><button name="dir" value="down" title="Après">→</button></form>'
                . '<form method="post" action="' . e(url('delete')) . '" data-confirm="Supprimer cette photo de la galerie ?">' . $csrf . '<input type="hidden" name="id" value="' . $d['id'] . '"><button class="del" title="Supprimer">×</button></form></div>';
        }
        layout('Galerie', $h . '</div>', 'list:photo');
    }
    if ($type === 'review') {
        $link = (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . base_url() . '/votre-avis.html';
        $msg = "Bonjour, merci d'avoir voyagé avec Touring with Mina ! Pouvez-vous nous laisser un petit avis ? Cela prend deux minutes : $link";
        $h .= '<div class="card share"><h3>Lien à envoyer à vos clients</h3><p>Envoyez ce lien après le voyage. L’avis arrive ici « À valider » : cliquez sur Publier pour l’afficher sur le site (page Avis et accueil).</p>'
            . '<div class="share-r"><input readonly value="' . e($link) . '" onfocus="this.select()"><button type="button" class="btn-sm" data-copy="' . e($link) . '">Copier le lien</button><button type="button" class="btn-sm btn-ghost" data-copy="' . e($msg) . '">Copier le message WhatsApp</button></div></div>';
    }
    $h .= '<div class="card"><table class="tbl"><thead><tr><th></th><th>' . ($type === 'review' ? 'Avis' : 'Titre') . '</th><th>Infos</th><th>État</th><th class="r">Actions</th></tr></thead><tbody>';
    if (!$all) $h .= '<tr><td colspan="5" class="empty">Rien pour l’instant.</td></tr>';
    foreach ($all as $d) {
        $x = $d['data'];
        $thumb = !empty($x['img']) ? '<img src="' . e(asset($x['img'])) . '" alt="" loading="lazy">' : '<span class="ph">' . icon($t['icon']) . '</span>';
        $meta = match ($type) {
            'circuit' => !empty($x['d']) ? $x['d'] . ' jours' . (!empty($x['price']) ? ' · ' . $x['price'] : '') : 'Sur mesure',
            'post' => fdate((string)($x['date'] ?? ''), 'fr') . (!empty($x['fr']['category']) ? ' · ' . $x['fr']['category'] : ''),
            'faq' => ($x['fr']['g'] ?? '') . (!empty($x['home']) ? ' · sur l’accueil' : ''),
            'review' => str_repeat('★', (int)($x['rating'] ?? 5)) . ' · ' . ($x['origin'] ?? '') . (!empty($x['circuit']) ? ' · ' . $x['circuit'] : '') . (!empty($x['sample']) ? ' · exemple' : ''),
            'destination' => $x['word'] ?? '',
            'section' => isset(SECTION_BUILTIN[$x['kind'] ?? '']) ? 'Section du site' : 'Bloc ajouté · ' . (SECTION_CUSTOM[$x['kind'] ?? ''] ?? ''),
            default => '',
        };
        $title = doc_title($d);
        if ($type === 'review') $title .= '<small>' . e(mb_strimwidth((string)($x['text'] ?? ''), 0, 120, '…')) . '</small>';
        else $title = e($title);
        $state = $d['published'] ? '<i class="st st-on">En ligne</i>' : '<i class="st st-off">' . ($type === 'review' ? 'À valider' : 'Brouillon') . '</i>';
        $view = (!empty($t['url']) && $d['published']) ? '<a class="btn-sm btn-ghost" href="' . e(base_url() . '/' . sprintf($t['url'], $d['slug'])) . '" target="_blank" rel="noopener">Voir</a>' : '';
        $pub = '<form method="post" action="' . e(url('toggle')) . '">' . $csrf . '<input type="hidden" name="id" value="' . $d['id'] . '"><button class="btn-sm' . ($d['published'] ? ' btn-ghost' : '') . '">' . ($d['published'] ? 'Dépublier' : 'Publier') . '</button></form>';
        $h .= '<tr><td class="th">' . $thumb . '</td><td><a class="tt" href="' . e(url('edit', ['type' => $type, 'id' => $d['id']])) . '">' . $title . '</a></td><td class="mt">' . e($meta) . '</td><td>' . $state . '</td>'
            . '<td class="r acts"><form method="post" action="' . e(url('move')) . '">' . $csrf . '<input type="hidden" name="id" value="' . $d['id'] . '"><button class="btn-ic" name="dir" value="up" title="Monter">↑</button><button class="btn-ic" name="dir" value="down" title="Descendre">↓</button></form>'
            . $pub . $view . '<a class="btn-sm" href="' . e(url('edit', ['type' => $type, 'id' => $d['id']])) . '">Modifier</a>'
            . '<form method="post" action="' . e(url('delete')) . '" data-confirm="Supprimer définitivement « ' . e(doc_title($d)) . ' » ?">' . $csrf . '<input type="hidden" name="id" value="' . $d['id'] . '"><button class="btn-ic del" title="Supprimer">×</button></form></td></tr>';
    }
    $h .= '</tbody></table></div>';
    layout(e($t['label']), $h, 'list:' . $type, '<a class="btn" href="' . e(url('edit', ['type' => $type])) . '">+ ' . e($t['add']) . '</a>');
}

/* ---------- list actions ---------- */
if (in_array($r, ['move', 'toggle', 'delete'], true) && $post) {
    $d = doc_by_id((int)($_POST['id'] ?? 0));
    if (!$d || in_array($d['type'], ['page', 'settings'], true)) go('dashboard');
    if ($r === 'toggle') {
        db()->prepare('UPDATE docs SET published = ?, updated = ? WHERE id = ?')->execute([$d['published'] ? 0 : 1, now(), $d['id']]);
        flash($d['published'] ? '« ' . doc_title($d) . ' » n’est plus affiché sur le site.' : '« ' . doc_title($d) . ' » est en ligne.');
    } elseif ($r === 'delete') {
        db()->prepare('DELETE FROM docs WHERE id = ?')->execute([$d['id']]);
        flash('« ' . doc_title($d) . ' » a été supprimé.');
    } else {
        $list = docs($d['type'], false);
        $ids = array_column($list, 'id');
        $i = array_search($d['id'], $ids, true);
        $j = ($_POST['dir'] ?? '') === 'up' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < count($ids)) { [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]]; }
        $st = db()->prepare('UPDATE docs SET position = ? WHERE id = ?');
        foreach ($ids as $p => $id) $st->execute([$p, $id]);
    }
    go('list', ['type' => $d['type']]);
}

/* ---------- editors ---------- */
function editor_form(array $data, string $extraTop = '', bool $langs = true): string
{
    $general = fields($data, 'd');
    $h = '<div class="ed">' . $extraTop;
    if (trim($general) !== '') $h .= '<section class="card"><h2 class="card-t">Général</h2><div class="grid-f">' . $general . '</div></section>';
    if ($langs && isset($data['fr'])) {
        $h .= '<section class="card"><div class="tabs" role="tablist"><button type="button" class="on" data-tab="fr">Français</button><button type="button" data-tab="en">English</button><span class="hint">Les photos se choisissent dans l’onglet Français, elles valent pour les deux langues.</span></div>'
            . '<div class="tab on" data-pane="fr"><div class="grid-f">' . fields($data['fr'], 'd[fr]', false, 1) . '</div></div>'
            . '<div class="tab" data-pane="en"><div class="grid-f">' . fields($data['en'] ?? blankify($data['fr']), 'd[en]', true, 1) . '</div></div></section>';
    }
    return $h . '</div>';
}

if ($r === 'edit' && isset($T[$_GET['type'] ?? ''])) {
    $type = $_GET['type']; $t = $T[$type];
    $id = (int)($_GET['id'] ?? 0);
    $doc = $id ? doc_by_id($id) : null;
    if ($id && (!$doc || $doc['type'] !== $type)) go('list', ['type' => $type]);
    $data = $doc ? array_replace_recursive($t['blank'], $doc['data']) : $t['blank'];
    if ($doc) foreach (['fr', 'en'] as $l) if (isset($doc['data'][$l])) $data[$l] = $doc['data'][$l] + ($t['blank'][$l] ?? []);
    if ($post) {
        $posted = normalize($_POST['d'] ?? []);
        $new = merge_posted($doc['data'] ?? $t['blank'], is_array($posted) ? $posted : []);
        $slug = '';
        if ($t['slug']) $slug = unique_slug($type, trim((string)($_POST['slug'] ?? '')) ?: (string)($new['fr']['t'] ?? $type), $id);
        if ($doc && $t['slug'] && $doc['slug'] !== '' && $doc['slug'] !== $slug) $new['_old'] = array_values(array_unique(array_merge((array)($doc['data']['_old'] ?? []), [$doc['slug']])));
        $published = isset($_POST['published']) ? 1 : 0;
        $nid = doc_save($id ?: null, $type, $slug, $new, $published);
        flash('Enregistré.' . ($published ? '' : ' (non publié : invisible sur le site)'));
        go('edit', ['type' => $type, 'id' => $nid]);
    }
    $published = $doc ? $doc['published'] : 1;
    $top = '<section class="card meta-bar">';
    if ($t['slug']) { [$pre, $suf] = explode('%s', $t['url']) + [1 => ''];
        $top .= '<label class="f f-slug"><span class="fl">Adresse de la page</span><span class="slug"><i>/' . e($pre) . '</i><input name="slug" value="' . e($doc['slug'] ?? '') . '" placeholder="créée à partir du titre"><i class="suf">' . e($suf) . '</i></span></label>'; }
    $top .= '<label class="f-check pub"><input type="checkbox" name="published" value="1"' . ($published ? ' checked' : '') . '><span>Publié sur le site</span></label>';
    if ($doc && !empty($t['url']) && $doc['published']) $top .= '<a class="btn-sm btn-ghost" href="' . e(base_url() . '/' . sprintf($t['url'], $doc['slug'])) . '" target="_blank" rel="noopener">Voir sur le site</a>';
    $top .= '</section>';
    if ($type === 'post') $top .= '<section class="card seo" data-seo><h2 class="card-t">Conseils pour Google</h2><p class="hint">La liste se met à jour pendant que vous écrivez (onglet Français).</p><ul class="seo-l">'
        . '<li data-k="title">Titre de 30 à 60 caractères, avec le lieu ou le sujet (ex. « Tadrart Rouge ») <i></i></li>'
        . '<li data-k="desc">Description courte de 120 à 160 caractères <i></i></li>'
        . '<li data-k="words">Texte d’au moins 600 mots <i></i></li>'
        . '<li data-k="h2">Au moins 2 intertitres (bouton « Intertitre ») <i></i></li>'
        . '<li data-k="links">Au moins un lien vers un circuit ou la réservation <i></i></li>'
        . '<li data-k="img">Une image à la une (champ « Photo »)</li>'
        . '<li data-k="cat">Une catégorie</li>'
        . '<li data-k="en">La version anglaise remplie (titre et texte)</li></ul></section>';
    $form = '<form method="post" class="edit" data-dirty>' . csrf_field() . editor_form($data, $top) . '<div class="savebar"><a class="btn btn-line" href="' . e(url('list', ['type' => $type])) . '">Retour à la liste</a><button class="btn">Enregistrer</button></div></form>';
    layout(($doc ? 'Modifier : ' . e(doc_title($doc)) : e($t['new'])), $form, 'list:' . $type);
}

if ($r === 'pages') {
    $h = '<p class="lead">Tous les textes fixes du site, page par page, en français et en anglais.</p><div class="cards cards-p">';
    foreach (page_list() as $slug => [$name, $desc]) $h .= '<a class="card pg" href="' . e(url('page', ['slug' => $slug])) . '"><b>' . e($name) . '</b><span>' . e($desc) . '</span></a>';
    layout('Textes des pages', $h . '</div>', 'pages');
}
if ($r === 'page' && isset(page_list()[$_GET['slug'] ?? ''])) {
    $slug = $_GET['slug'];
    $doc = doc_by_slug('page', $slug, false);
    if (!$doc) go('pages');
    if ($post) {
        $posted = normalize($_POST['d'] ?? []);
        doc_save($doc['id'], 'page', $slug, merge_posted($doc['data'], is_array($posted) ? $posted : []));
        flash('Textes enregistrés.');
        go('page', ['slug' => $slug]);
    }
    $view = ['home' => '', 'day' => '', 'about' => 'presentation.html', 'trips' => 'circuits.html', 'destinations' => 'destinations.html', 'infos' => 'infos-pratiques.html', 'faq' => 'faq.html',
        'contact' => 'contact.html', 'gallery' => 'galerie.html', 'blog' => 'blog.html', 'reviews' => 'avis.html', 'booking' => 'reservation.html', 'ui' => '', 'film' => '', 'why' => '', 'rockart' => 'art-rupestre.html', 'culture' => 'culture.html'][$slug] ?? '';
    $top = '<p class="lead">' . e(page_list()[$slug][1]) . '. <a href="' . e(base_url() . '/' . $view) . '" target="_blank" rel="noopener">Voir la page</a></p>';
    $form = '<form method="post" class="edit" data-dirty>' . csrf_field() . editor_form($doc['data'], $top) . '<div class="savebar"><a class="btn btn-line" href="' . e(url('pages')) . '">Toutes les pages</a><button class="btn">Enregistrer</button></div></form>';
    layout(e(page_list()[$slug][0]), $form, 'pages');
}

if ($r === 'settings' && is_admin()) {
    $doc = doc_by_slug('settings', 'settings', false);
    if ($post) {
        $posted = normalize($_POST['d'] ?? []);
        $new = merge_posted($doc['data'], is_array($posted) ? $posted : []);
        $new['wa'] = preg_replace('/\D/', '', (string)($new['wa'] ?? ''));
        doc_save($doc['id'], 'settings', 'settings', $new);
        flash('Réglages enregistrés.');
        go('settings');
    }
    $top = '<p class="lead">Logo, coordonnées, réseaux sociaux et visibilité sur Google.</p>';
    $form = '<form method="post" class="edit" data-dirty>' . csrf_field() . editor_form($doc['data'], $top) . '<div class="savebar"><button class="btn">Enregistrer</button></div></form>';
    layout('Réglages', $form, 'settings');
}

/* ---------- media ---------- */
if ($r === 'media_json') json_out(['items' => array_map(fn($m) => $m + ['u' => asset($m['p'])], media_list())]);
if ($r === 'upload' && $post) {
    try { $p = save_upload($_FILES['file'] ?? []); } catch (Throwable $e) { json_out(['ok' => false, 'error' => $e->getMessage()], 422); }
    if (($_POST['as'] ?? '') === 'photo') doc_save(null, 'photo', '', ['img' => $p, 'cat' => array_key_exists($_POST['cat'] ?? '', CATS) ? $_POST['cat'] : 'land', 'alt' => '']);
    json_out(['ok' => true, 'path' => $p, 'url' => asset($p)]);
}
if ($r === 'media_delete' && $post) {
    $p = (string)($_POST['p'] ?? '');
    $full = realpath(ROOT . '/' . $p);
    if ($full && str_starts_with($full, realpath(UPLOADS) . DIRECTORY_SEPARATOR) && is_file($full)) { unlink($full); flash('Image supprimée.'); }
    go('media');
}
if ($r === 'media') {
    $csrf = csrf_field();
    $used = (string)db()->query("SELECT group_concat(data, ' ') FROM docs")->fetchColumn();
    $h = '<p class="lead">Toutes vos images. Les images envoyées sont redimensionnées automatiquement. Les images de la maquette ne peuvent pas être supprimées.</p>'
        . '<div class="drop"><label class="btn">Envoyer des images<input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-media-files></label><p class="hint up-msg" data-up-msg></p></div><div class="pgrid">';
    foreach (media_list() as $m) {
        $inUse = str_contains($used, $m['p']) || str_contains($used, str_replace('/', '\/', $m['p']));
        $h .= '<div class="pcell"><img src="' . e(asset($m['p'])) . '" alt="" loading="lazy"><span title="' . e($m['p']) . '">' . e(basename($m['p'])) . '</span>'
            . ($m['own'] ? ($inUse ? '<i class="st st-on">Utilisée</i>' : '<form method="post" action="' . e(url('media_delete')) . '" data-confirm="Supprimer cette image ?">' . $csrf . '<input type="hidden" name="p" value="' . e($m['p']) . '"><button class="del" title="Supprimer">×</button></form>') : '') . '</div>';
    }
    layout('Médiathèque', $h . '</div>', 'media');
}

/* ---------- bookings ---------- */
if ($r === 'bookings_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="demandes-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Date', 'Type', 'Statut', 'Nom', 'E-mail', 'Téléphone', 'Circuit', 'Demande', 'Note interne'], ';');
    foreach (db()->query('SELECT * FROM bookings ORDER BY id DESC') as $b)
        fputcsv($o, [$b['created'], $b['kind'] === 'contact' ? 'Message' : 'Réservation', STATUSES[$b['status']] ?? $b['status'], $b['name'], $b['email'], $b['phone'], $b['trip'], $b['summary'], $b['note']], ';');
    exit;
}
if ($r === 'bookings') {
    $f = (string)($_GET['s'] ?? 'open');
    $where = match ($f) { 'all' => '1', 'open' => "status IN ('new','progress')", default => array_key_exists($f, STATUSES) ? 'status = ' . db()->quote($f) : '1' };
    $rows = db()->query("SELECT * FROM bookings WHERE $where ORDER BY id DESC LIMIT 500")->fetchAll();
    $tabs = '';
    foreach (['open' => 'À traiter', 'new' => 'Nouvelles', 'progress' => 'En cours', 'confirmed' => 'Confirmées', 'archived' => 'Archivées', 'all' => 'Tout'] as $k => $v) $tabs .= '<a href="' . e(url('bookings', ['s' => $k])) . '"' . ($k === $f ? ' class="on"' : '') . '>' . $v . '</a>';
    $h = '<p class="lead">Chaque demande envoyée depuis le site (formulaire de réservation ou page Contact) est enregistrée ici, même si le client a aussi écrit sur WhatsApp.</p><nav class="ftabs">' . $tabs . '</nav><div class="card"><table class="tbl"><thead><tr><th>Date</th><th>Client</th><th>Demande</th><th>Contact</th><th>Statut</th></tr></thead><tbody>';
    if (!$rows) $h .= '<tr><td colspan="5" class="empty">Aucune demande ici.</td></tr>';
    foreach ($rows as $b) {
        $u = e(url('booking', ['id' => $b['id']]));
        $what = $b['kind'] === 'contact' ? 'Message' : ($b['trip'] ? (circuit_name($b['trip']) ?: $b['trip']) : 'Réservation');
        $h .= '<tr class="' . ($b['status'] === 'new' ? 'is-new' : '') . '"><td class="mt">' . e(date('d/m/Y H:i', strtotime($b['created']))) . '</td><td><a class="tt" href="' . $u . '">' . e($b['name'] ?: 'Sans nom') . '</a></td><td>' . e($what) . '<small>' . e(mb_strimwidth(preg_replace('/\s+/', ' ', (string)$b['summary']), 0, 90, '…')) . '</small></td><td class="mt">' . e($b['phone']) . '<br>' . e($b['email']) . '</td><td><i class="st st-' . e($b['status']) . '">' . e(STATUSES[$b['status']] ?? '') . '</i></td></tr>';
    }
    $h .= '</tbody></table></div>';
    layout('Réservations & messages', $h, 'bookings', '<a class="btn btn-line" href="' . e(url('bookings_csv')) . '">Exporter (Excel)</a>');
}
function circuit_name(string $slug): string { $c = doc_by_slug('circuit', $slug, false); return $c ? (string)($c['data']['fr']['t'] ?? '') : ''; }
if ($r === 'booking') {
    $st = db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $st->execute([(int)($_GET['id'] ?? 0)]);
    $b = $st->fetch();
    if (!$b) go('bookings');
    if ($post) {
        if (isset($_POST['delete'])) { db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$b['id']]); flash('Demande supprimée.'); go('bookings'); }
        $s = array_key_exists($_POST['status'] ?? '', STATUSES) ? $_POST['status'] : $b['status'];
        db()->prepare('UPDATE bookings SET status = ?, note = ? WHERE id = ?')->execute([$s, mb_substr(trim((string)($_POST['note'] ?? '')), 0, 5000), $b['id']]);
        flash('Demande mise à jour.');
        go('booking', ['id' => $b['id']]);
    }
    if ($b['status'] === 'new') { db()->prepare("UPDATE bookings SET status = 'progress' WHERE id = ?")->execute([$b['id']]); $b['status'] = 'progress'; }
    $fields = json_decode((string)$b['data'], true) ?: [];
    $dl = '';
    foreach ($fields as [$k, $v]) $dl .= '<dt>' . e($k) . '</dt><dd>' . nl2br(e($v)) . '</dd>';
    $wa = wa_link((string)$b['phone']);
    $first = e(explode(' ', trim((string)$b['name']))[0] ?? '');
    $reply = rawurlencode(($b['lang'] === 'en' ? "Hello $first, thank you for your request. " : "Bonjour $first, merci pour votre demande. "));
    $acts = ($wa ? '<a class="btn btn-wa" href="' . e($wa) . '?text=' . $reply . '" target="_blank" rel="noopener">Répondre sur WhatsApp</a>' : '')
        . ($b['email'] ? '<a class="btn btn-line" href="mailto:' . e($b['email']) . '?subject=' . rawurlencode(($b['lang'] === 'en' ? 'Your trip to Djanet' : 'Votre voyage à Djanet')) . '">Répondre par e-mail</a>' : '');
    $opts = '';
    foreach (STATUSES as $k => $v) $opts .= '<option value="' . $k . '"' . ($k === $b['status'] ? ' selected' : '') . '>' . $v . '</option>';
    $h = '<div class="cols cols-b"><section class="card"><header class="card-h"><h2>' . e($b['kind'] === 'contact' ? 'Message' : 'Demande de réservation') . '</h2><span class="mt">Reçue le ' . e(date('d/m/Y à H:i', strtotime($b['created']))) . ' · ' . ($b['lang'] === 'en' ? 'site anglais' : 'site français') . '</span></header>'
        . '<div class="who-b"><b>' . e($b['name'] ?: 'Sans nom') . '</b>' . ($b['phone'] ? '<span>' . e($b['phone']) . '</span>' : '') . ($b['email'] ? '<span>' . e($b['email']) . '</span>' : '') . '</div><div class="btns">' . $acts . '</div>'
        . ($dl ? '<dl class="dl">' . $dl . '</dl>' : '<pre class="pre">' . e($b['summary']) . '</pre>') . '</section>'
        . '<aside class="card"><form method="post" class="form">' . csrf_field() . '<label>Statut<select name="status">' . $opts . '</select></label><label>Note interne (visible seulement ici)<textarea name="note" rows="7">' . e($b['note']) . '</textarea></label><button class="btn">Enregistrer</button></form>'
        . '<form method="post" data-confirm="Supprimer définitivement cette demande ?">' . csrf_field() . '<button class="btn-sm btn-ghost del-t" name="delete" value="1">Supprimer la demande</button></form></aside></div>';
    layout(e($b['name'] ?: 'Demande'), $h, 'bookings', '<a class="btn btn-line" href="' . e(url('bookings')) . '">Toutes les demandes</a>');
}

/* ---------- users & account ---------- */
if ($r === 'users' && is_admin()) {
    if ($post) {
        $a = $_POST['a'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        if ($a === 'add') {
            $email = mb_strtolower(trim((string)($_POST['email'] ?? ''))); $pass = (string)($_POST['pass'] ?? '');
            $role = array_key_exists($_POST['role'] ?? '', ROLES) ? $_POST['role'] : 'editor';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) flash('E-mail valide et mot de passe de 10 caractères minimum.', 'err');
            else {
                try { db()->prepare('INSERT INTO users(email, name, role, pass, created) VALUES(?,?,?,?,?)')->execute([$email, trim((string)($_POST['name'] ?? '')), $role, password_hash($pass, PASSWORD_DEFAULT), now()]); flash('Utilisateur ajouté.'); }
                catch (PDOException $e) { flash('Cette adresse e-mail a déjà un compte.', 'err'); }
            }
        } elseif ($a === 'del' && $id !== (int)me()['id']) {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); flash('Utilisateur supprimé.');
        } elseif ($a === 'pass') {
            $pass = (string)($_POST['pass'] ?? '');
            if (mb_strlen($pass) < 10) flash('10 caractères minimum.', 'err');
            else { db()->prepare('UPDATE users SET pass = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]); flash('Mot de passe changé.'); }
        }
        go('users');
    }
    $csrf = csrf_field();
    $h = '<div class="card"><table class="tbl"><thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Dernière connexion</th><th class="r">Actions</th></tr></thead><tbody>';
    foreach (db()->query('SELECT * FROM users ORDER BY id') as $u) {
        $h .= '<tr><td><b>' . e($u['name']) . '</b></td><td>' . e($u['email']) . '</td><td>' . e(ROLES[$u['role']] ?? $u['role']) . '</td><td class="mt">' . e($u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'jamais') . '</td><td class="r acts">'
            . '<form method="post" class="inl-f">' . $csrf . '<input type="hidden" name="a" value="pass"><input type="hidden" name="id" value="' . $u['id'] . '"><input type="password" name="pass" placeholder="Nouveau mot de passe" minlength="10" autocomplete="new-password"><button class="btn-sm">Changer</button></form>'
            . ((int)$u['id'] !== (int)me()['id'] ? '<form method="post" data-confirm="Supprimer ce compte ?">' . $csrf . '<input type="hidden" name="a" value="del"><input type="hidden" name="id" value="' . $u['id'] . '"><button class="btn-ic del">×</button></form>' : '') . '</td></tr>';
    }
    $h .= '</tbody></table></div><section class="card"><h2 class="card-t">Ajouter un utilisateur</h2><form method="post" class="form grid-f">' . $csrf . '<input type="hidden" name="a" value="add">'
        . '<label class="f">Nom<input name="name" required></label><label class="f">E-mail<input type="email" name="email" required></label>'
        . '<label class="f">Rôle<select name="role"><option value="editor">Éditeur : contenus et réservations</option><option value="admin">Administrateur : tout, y compris réglages et utilisateurs</option></select></label>'
        . '<label class="f">Mot de passe (10 caractères minimum)<input type="password" name="pass" minlength="10" required autocomplete="new-password"></label><div class="f"><button class="btn">Ajouter</button></div></form></section>';
    layout('Utilisateurs', $h, 'users');
}
if ($r === 'account') {
    $u = me();
    if ($post) {
        $name = trim((string)($_POST['name'] ?? ''));
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['cur'] ?? ''), $u['pass'])) flash('Mot de passe actuel incorrect.', 'err');
        elseif ($new !== '' && mb_strlen($new) < 10) flash('Le nouveau mot de passe doit faire au moins 10 caractères.', 'err');
        elseif ($new !== '' && $new !== (string)($_POST['new2'] ?? '')) flash('Les deux mots de passe ne sont pas identiques.', 'err');
        else {
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name ?: $u['name'], $u['id']]);
            if ($new !== '') { db()->prepare('UPDATE users SET pass = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]); session_regenerate_id(true); }
            flash('Compte mis à jour.');
        }
        go('account');
    }
    $h = '<section class="card narrow"><form method="post" class="form">' . csrf_field() . '<label>Nom<input name="name" value="' . e($u['name']) . '"></label><label>E-mail<input value="' . e($u['email']) . '" disabled></label>'
        . '<label>Nouveau mot de passe (laisser vide pour le garder)<input type="password" name="new" minlength="10" autocomplete="new-password"></label><label>Confirmer le nouveau mot de passe<input type="password" name="new2" autocomplete="new-password"></label>'
        . '<label>Mot de passe actuel (obligatoire)<input type="password" name="cur" required autocomplete="current-password"></label><button class="btn">Enregistrer</button></form></section>';
    layout('Mon compte', $h, 'account');
}

go('dashboard');
