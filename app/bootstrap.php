<?php
declare(strict_types=1);

define('APP', __DIR__);
define('ROOT', dirname(__DIR__));
define('DATA', ROOT . '/data');
define('UPLOADS', ROOT . '/uploads');
const LANGS = ['fr', 'en'];
const CMS_VERSION = '1.3';

mb_internal_encoding('UTF-8');
date_default_timezone_set('Africa/Algiers');
ini_set('display_errors', '0');

/* ---------- paths ---------- */
function base_url(): string
{
    static $b = null;
    if ($b === null) {
        $s = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        if (defined('IN_ADMIN')) $s = dirname($s);
        $b = rtrim($s, '/');
    }
    return $b;
}
function asset(?string $path): string
{
    $path = (string)$path;
    if ($path === '') return '';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    return base_url() . '/' . ltrim($path, '/');
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function now(): string { return date('Y-m-d H:i:s'); }

/* ---------- database ---------- */
/** Database file. Its name is random (stored in data/config.php) so it cannot be guessed and downloaded. */
function db_file(): string
{
    static $f = null;
    if ($f === null) {
        $c = DATA . '/config.php';
        if (!is_file($c) && !is_file(DATA . '/site.sqlite')) {
            if (!is_dir(DATA)) @mkdir(DATA, 0775, true);
            @file_put_contents($c, "<?php return ['db' => 'site-" . bin2hex(random_bytes(10)) . ".sqlite'];\n");
        }
        $cfg = is_file($c) ? (include $c) : [];
        $f = DATA . '/' . (is_array($cfg) && !empty($cfg['db']) ? basename($cfg['db']) : 'site.sqlite');
    }
    return $f;
}
function installed(): bool { return is_file(db_file()) && filesize(db_file()) > 0; }

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(DATA)) mkdir(DATA, 0775, true);
    $pdo = new PDO('sqlite:' . db_file(), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Classic journal (not WAL): on shared hosting, WAL can leave the public pages reading an older copy of the data.
    $pdo->exec('PRAGMA busy_timeout=6000; PRAGMA foreign_keys=ON;');
    try { $pdo->query('PRAGMA journal_mode=DELETE')->fetchAll(); } catch (Throwable $t) { /* keep the current mode if the base is busy */ }
    return $pdo;
}

function db_schema(): void
{
    db()->exec(<<<SQL
CREATE TABLE IF NOT EXISTS docs(
  id INTEGER PRIMARY KEY, type TEXT NOT NULL, slug TEXT NOT NULL DEFAULT '',
  position INTEGER NOT NULL DEFAULT 0, published INTEGER NOT NULL DEFAULT 1,
  data TEXT NOT NULL, created TEXT, updated TEXT);
CREATE INDEX IF NOT EXISTS docs_type ON docs(type, position);
CREATE TABLE IF NOT EXISTS bookings(
  id INTEGER PRIMARY KEY, kind TEXT NOT NULL DEFAULT 'booking', status TEXT NOT NULL DEFAULT 'new',
  created TEXT, lang TEXT, name TEXT, email TEXT, phone TEXT, trip TEXT, summary TEXT, data TEXT,
  note TEXT NOT NULL DEFAULT '', ip TEXT);
CREATE TABLE IF NOT EXISTS users(
  id INTEGER PRIMARY KEY, email TEXT NOT NULL UNIQUE, name TEXT, role TEXT NOT NULL DEFAULT 'editor',
  pass TEXT NOT NULL, created TEXT, last_login TEXT);
CREATE TABLE IF NOT EXISTS throttle(ip TEXT, kind TEXT, at INTEGER);
CREATE INDEX IF NOT EXISTS throttle_k ON throttle(kind, ip, at);
SQL);
}

function row_doc(array $r): array
{
    $r['data'] = json_decode($r['data'], true) ?: [];
    $r['id'] = (int)$r['id'];
    $r['published'] = (int)$r['published'];
    return $r;
}
/** All docs of a type, ordered. */
function docs(string $type, bool $onlyPublished = true): array
{
    static $cache = [];
    $k = $type . (int)$onlyPublished;
    if (isset($cache[$k])) return $cache[$k];
    $sql = 'SELECT * FROM docs WHERE type = ?' . ($onlyPublished ? ' AND published = 1' : '') . ' ORDER BY position, id';
    $st = db()->prepare($sql);
    $st->execute([$type]);
    return $cache[$k] = array_map('row_doc', $st->fetchAll());
}
function doc_by_slug(string $type, string $slug, bool $onlyPublished = true): ?array
{
    foreach (docs($type, $onlyPublished) as $d) if ($d['slug'] === $slug) return $d;
    return null;
}
function doc_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM docs WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? row_doc($r) : null;
}
/** Page texts / settings singleton (data array). */
function page(string $slug): array
{
    static $c = [];
    if (!isset($c[$slug])) {
        $d = doc_by_slug($slug === 'settings' ? 'settings' : 'page', $slug, false);
        $c[$slug] = $d['data'] ?? [];
    }
    return $c[$slug];
}
function settings(): array { return page('settings'); }

function doc_save(?int $id, string $type, string $slug, array $data, ?int $published = null, ?int $position = null): int
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($id) {
        $sets = ['slug = ?', 'data = ?', 'updated = ?'];
        $args = [$slug, $json, now()];
        if ($published !== null) { $sets[] = 'published = ?'; $args[] = $published; }
        if ($position !== null) { $sets[] = 'position = ?'; $args[] = $position; }
        $args[] = $id;
        db()->prepare('UPDATE docs SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($args);
        return $id;
    }
    if ($position === null) {
        $st = db()->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM docs WHERE type = ?');
        $st->execute([$type]);
        $position = (int)$st->fetchColumn();
    }
    db()->prepare('INSERT INTO docs(type, slug, position, published, data, created, updated) VALUES(?,?,?,?,?,?,?)')
        ->execute([$type, $slug, $position, $published ?? 1, $json, now(), now()]);
    return (int)db()->lastInsertId();
}

/* ---------- home page blocks ---------- */
const SECTION_BUILTIN = [
    'hero' => 'Diaporama et barre de réservation', 'film' => 'Film', 'intro' => 'Introduction', 'facts' => 'Chiffres clés', 'trips' => 'Circuits',
    'rockart' => 'Art rupestre', 'dests' => 'Destinations', 'why' => 'Pourquoi le Sahara', 'day' => 'Une journée dans le désert', 'culture' => 'Culture touarègue', 'family' => 'Le désert en famille',
    'incl' => 'Nos services', 'quote' => 'Citation', 'reviews' => 'Avis des voyageurs', 'gallery' => 'Bande photos', 'clips' => 'Capsules vidéo',
    'faq' => 'Questions fréquentes', 'blog' => 'Derniers articles', 'cta' => 'Bandeau final',
];
const SECTION_CUSTOM = ['texte_photo' => 'Texte + photo à droite', 'photo_texte' => 'Photo à gauche + texte', 'texte' => 'Texte seul', 'photos' => 'Mosaïque de photos', 'bandeau' => 'Bandeau photo avec bouton'];
/** Creates the default list of home blocks the first time (also on sites installed before blocks existed). */
function ensure_sections(): void
{
    static $done = false;
    if ($done) return; $done = true;
    if ((int)db()->query("SELECT COUNT(*) FROM docs WHERE type = 'section'")->fetchColumn() > 0) return;
    foreach (SECTION_BUILTIN as $k => $t) doc_save(null, 'section', '', ['kind' => $k, 'img' => '', 'imgs' => [], 'fr' => ['t' => $t, 'p' => '', 'btn' => '', 'url' => ''], 'en' => ['t' => $t, 'p' => '', 'btn' => '', 'url' => '']], 1);
}

/* ---------- text helpers ---------- */
function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = strtr($s, ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u','ÿ'=>'y','ñ'=>'n','œ'=>'oe','æ'=>'ae',"'"=>'-','’'=>'-']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim((string)$s, '-');
}

function safe_url(string $u): string
{
    $u = trim($u);
    if (preg_match('#^(https?://|mailto:|tel:|\#|[a-z0-9-]+\.html|\./|/)#i', $u)) return $u;
    return '#';
}
function md_inline(string $s): string
{
    $s = e($s);
    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
        $url = safe_url(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
        $ext = preg_match('#^https?://#', $url) ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . e($url) . '"' . $ext . '>' . $m[1] . '</a>';
    }, $s);
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
    return (string)$s;
}
/** Minimal markdown: ## / ### headings, "- " lists, paragraphs, [links](url), **bold**. Returns [html, toc]. */
function md(string $text, bool $withToc = false): array
{
    $html = '';
    $toc = [];
    $blocks = preg_split("/\n\s*\n/", str_replace("\r", '', trim($text)));
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b === '') continue;
        if (preg_match('/^(#{2,3})\s+(.+)$/', $b, $m)) {
            $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $id = slugify($m[2]);
            if ($tag === 'h2') $toc[] = [$id, $m[2]];
            $html .= "<$tag id=\"" . e($id) . "\">" . md_inline($m[2]) . "</$tag>";
        } elseif (preg_match('/^- /', $b)) {
            $items = array_map(fn($l) => '<li>' . md_inline(preg_replace('/^- /', '', trim($l))) . '</li>', explode("\n", $b));
            $html .= '<ul>' . implode('', $items) . '</ul>';
        } else {
            $html .= '<p>' . implode('<br>', array_map('md_inline', explode("\n", $b))) . '</p>';
        }
    }
    return [$html, $toc];
}

const MONTHS = ['fr' => ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'],
                'en' => ['January','February','March','April','May','June','July','August','September','October','November','December']];
function fdate(string $d, string $lang): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $d, $m)) return e($d);
    $mo = MONTHS[$lang][(int)$m[2] - 1] ?? '';
    return $lang === 'fr' ? ((int)$m[3] . " $mo {$m[1]}") : "$mo " . (int)$m[3] . ", {$m[1]}";
}

/* ---------- requests ---------- */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0'), 0, 64); }
/** True when the IP made at least $max attempts of $kind within $seconds. */
function throttled(string $kind, int $max, int $seconds): bool
{
    db()->prepare('DELETE FROM throttle WHERE at < ?')->execute([time() - 86400]);
    $st = db()->prepare('SELECT COUNT(*) FROM throttle WHERE kind = ? AND ip = ? AND at > ?');
    $st->execute([$kind, client_ip(), time() - $seconds]);
    return (int)$st->fetchColumn() >= $max;
}
function throttle_hit(string $kind): void
{
    db()->prepare('INSERT INTO throttle(ip, kind, at) VALUES(?,?,?)')->execute([client_ip(), $kind, time()]);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('twm_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => base_url() . '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => is_https()]);
    session_start();
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? '')); }

function send_mail(string $to, string $subject, string $body, string $replyTo = '', string $html = ''): bool
{
    // Several recipients can be given, separated by commas.
    $list = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\s]+/', $to) ?: []), fn($a) => (bool)filter_var($a, FILTER_VALIDATE_EMAIL))));
    if (!$list || !function_exists('mail')) return false;
    $to = implode(', ', $list);
    if (PHP_SAPI === 'cli-server' && ($dump = getenv('TWM_MAIL_DUMP'))) { file_put_contents($dump, $html !== '' ? $html : $body); return true; } // local preview only
    $host = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $h = "From: Touring with Mina <noreply@$host>\r\nMIME-Version: 1.0\r\n";
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $h .= "Reply-To: $replyTo\r\n";
    if ($html === '') { $h .= "Content-Type: text/plain; charset=UTF-8\r\n"; $msg = $body; }
    else {
        // Plain text first, then the designed version: mail apps show the best one they can.
        $b = 'twm' . bin2hex(random_bytes(8));
        $h .= "Content-Type: multipart/alternative; boundary=\"$b\"\r\n";
        $msg = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body))
            . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "--$b--\r\n";
    }
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $msg, $h);
}

/** Absolute address of the site (for links inside e-mails). */
function site_url(): string
{
    $d = rtrim((string)(settings()['domain'] ?? ''), '/');
    return $d !== '' ? $d : (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_url();
}

/**
 * The e-mail the agency receives for a booking, a message or a review.
 * $rows: [[label, value], ...]  $buttons: [[text, url, primary?], ...]
 */
function mail_html(string $kicker, string $title, string $intro, array $rows, string $text = '', array $buttons = []): string
{
    $ink = '#22150E'; $mute = '#6f5b4c'; $bg = '#FBF5EA'; $card = '#ffffff'; $line = '#eadfcc'; $acc = '#E4521B'; $dark = '#1B1436';
    $ff = "font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;"; $fd = "font-family:'Arial Narrow','Helvetica Neue',Arial,sans-serif;";
    $r = '';
    foreach ($rows as [$k, $v]) {
        if (trim((string)$v) === '') continue;
        $r .= '<tr><td style="padding:11px 0;border-bottom:1px solid ' . $line . ';' . $ff . 'font-size:12px;line-height:16px;color:' . $mute . ';text-transform:uppercase;letter-spacing:.6px;width:38%;vertical-align:top">' . e($k) . '</td>'
            . '<td style="padding:11px 0 11px 14px;border-bottom:1px solid ' . $line . ';' . $ff . 'font-size:15px;line-height:21px;color:' . $ink . ';font-weight:bold;vertical-align:top;word-break:break-word">' . nl2br(e($v)) . '</td></tr>';
    }
    $rowsHtml = $r !== '' ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid ' . $line . ';margin:0 0 6px">' . $r . '</table>' : '';
    $textHtml = trim($text) !== '' ? '<div style="margin:22px 0 4px;padding:16px 18px;background:' . $bg . ';border-left:3px solid ' . $acc . ';' . $ff . 'font-size:15px;line-height:23px;color:' . $ink . '">' . nl2br(e(trim($text))) . '</div>' : '';
    $b = '';
    foreach ($buttons as $x) {
        [$t, $u] = $x; $prim = !empty($x[2]);
        $b .= '<table role="presentation" cellpadding="0" cellspacing="0" style="display:inline-table;margin:0 8px 8px 0"><tr><td style="border-radius:4px;background:' . ($prim ? $acc : $card) . ';border:1.5px solid ' . ($prim ? $acc : $ink) . '">'
            . '<a href="' . e($u) . '" style="display:inline-block;padding:12px 20px;' . $fd . 'font-size:14px;line-height:16px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;text-decoration:none;color:' . ($prim ? '#ffffff' : $ink) . '">' . e($t) . '</a></td></tr></table>';
    }
    $btns = $b !== '' ? '<div style="margin:26px 0 0">' . $b . '</div>' : '';
    $site = e(preg_replace('#^https?://#', '', site_url()));
    return '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title>' . e($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:' . $bg . '"><div style="display:none;max-height:0;overflow:hidden;opacity:0">' . e($intro) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $bg . '"><tr><td align="center" style="padding:28px 14px">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">'
        . '<tr><td style="background:' . $dark . ';padding:26px 30px;border-radius:8px 8px 0 0"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="' . $fd . 'font-size:22px;line-height:24px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#FBF5EA">Touring with Mina</td>'
        . '<td align="right" style="' . $ff . 'font-size:12px;line-height:16px;color:#b9b0cf">Djanet · Tassili n’Ajjer</td></tr></table></td></tr>'
        . '<tr><td style="background:' . $acc . ';height:4px;line-height:4px;font-size:0">&nbsp;</td></tr>'
        . '<tr><td style="background:' . $card . ';padding:32px 30px 34px;border-radius:0 0 8px 8px">'
        . '<div style="' . $ff . 'font-size:12px;line-height:16px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:' . $acc . '">' . e($kicker) . '</div>'
        . '<h1 style="margin:8px 0 10px;' . $fd . 'font-size:30px;line-height:33px;font-weight:bold;text-transform:uppercase;color:' . $ink . '">' . e($title) . '</h1>'
        . '<p style="margin:0 0 22px;' . $ff . 'font-size:15px;line-height:23px;color:' . $mute . '">' . e($intro) . '</p>'
        . $rowsHtml . $textHtml . $btns . '</td></tr>'
        . '<tr><td style="padding:18px 30px 0;' . $ff . 'font-size:12px;line-height:18px;color:' . $mute . '">Message envoyé automatiquement par le site ' . $site . '. Pour répondre au voyageur, utilisez les boutons ci-dessus ou « Répondre ».</td></tr>'
        . '</table></td></tr></table></body></html>';
}

require __DIR__ . '/migrate.php';
