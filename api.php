<?php
/* Public endpoint: stores booking requests, contact messages and reviews sent from the site. */
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $data): void { http_response_code($code); echo json_encode($data); exit; }
function str_in($v, int $max): string { return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$v)), 0, $max); }

if (!installed()) out(503, ['ok' => false]);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, ['ok' => false]);
// Same-site requests only.
$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) out(403, ['ok' => false]);

$in = json_decode((string)file_get_contents('php://input', false, null, 0, 65536), true);
if (!is_array($in)) $in = $_POST;
$action = (string)($in['action'] ?? '');
$lang = ($in['lang'] ?? '') === 'en' ? 'en' : 'fr';
$S = settings();

if ($action === 'booking' || $action === 'contact') {
    if (throttled('form', 10, 3600)) out(429, ['ok' => false]);
    throttle_hit('form');
    $fields = [];
    foreach ((array)($in['fields'] ?? []) as $f) {
        if (is_array($f) && count($f) === 2) $fields[] = [str_in($f[0], 120), str_in($f[1], 1000)];
        if (count($fields) >= 60) break;
    }
    $row = [
        'kind' => $action, 'lang' => $lang,
        'name' => str_in($in['name'] ?? '', 120), 'email' => str_in($in['email'] ?? '', 160), 'phone' => str_in($in['phone'] ?? '', 60),
        'trip' => str_in($in['trip'] ?? '', 120), 'summary' => str_in($in['summary'] ?? '', 6000),
    ];
    if ($row['summary'] === '' && !$fields) out(422, ['ok' => false]);
    db()->prepare('INSERT INTO bookings(kind, status, created, lang, name, email, phone, trip, summary, data, ip) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$row['kind'], 'new', now(), $lang, $row['name'], $row['email'], $row['phone'], $row['trip'], $row['summary'],
            json_encode($fields, JSON_UNESCAPED_UNICODE), client_ip()]);
    $to = $S['notify_email'] ?? '';
    if ($to) {
        $isB = $action === 'booking';
        $what = $isB ? 'Nouvelle demande de réservation' : 'Nouveau message';
        $who = $row['name'] ?: 'Un visiteur';
        $rows = [['Nom', $row['name']], ['E-mail', $row['email']], ['Téléphone', $row['phone']]];
        if ($row['trip'] !== '') { $ct = doc_by_slug('circuit', $row['trip'], false); $rows[] = ['Circuit', (string)($ct['data']['fr']['t'] ?? $row['trip'])]; }
        // The contact details are already shown at the top: do not repeat them.
        $seen = array_filter([$row['name'], $row['email'], $row['phone']]);
        foreach ($fields as [$k, $v]) if (!in_array($v, $seen, true)) $rows[] = [$k, $v];
        $btn = [];
        if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $btn[] = ['Répondre par e-mail', 'mailto:' . $row['email'] . '?subject=' . rawurlencode('Votre voyage à Djanet, Touring with Mina'), true];
        $tel = preg_replace('/\D/', '', $row['phone']);
        if (strlen($tel) >= 8) $btn[] = ['WhatsApp', 'https://wa.me/' . ltrim($tel, '0')];
        $btn[] = ['Voir dans l’admin', site_url() . '/admin/?r=bookings'];
        $html = mail_html($isB ? 'Réservation' : 'Contact', $what, $who . ($isB ? ' vient d’envoyer une demande de réservation depuis le site.' : ' vous a écrit depuis la page Contact.') . ' Langue : ' . ($lang === 'en' ? 'anglais' : 'français') . '.',
            $rows, $fields ? '' : $row['summary'], $btn);
        send_mail($to, "$what, " . ($row['name'] ?: 'site'), $row['summary'] . "\n\n— Voir dans l'administration du site.", $row['email'], $html);
    }
    out(200, ['ok' => true]);
}

if ($action === 'review') {
    if (!empty($in['website'])) out(200, ['ok' => true]);              // honeypot: pretend success
    if (time() - (int)($in['t0'] ?? 0) < 4) out(200, ['ok' => true]);  // sent too fast to be human
    if (throttled('review', 3, 3600)) out(429, ['ok' => false]);
    $name = str_in($in['name'] ?? '', 60);
    $text = str_in($in['text'] ?? '', 2000);
    $rating = max(1, min(5, (int)($in['rating'] ?? 5)));
    if ($name === '' || mb_strlen($text) < 10 || empty($in['consent'])) out(422, ['ok' => false]);
    $circuit = preg_replace('/[^a-z0-9-]/', '', (string)($in['circuit'] ?? ''));
    throttle_hit('review');
    doc_save(null, 'review', '', ['name' => $name, 'origin' => str_in($in['origin'] ?? '', 60), 'circuit' => $circuit, 'rating' => $rating,
        'date' => date('Y-m-d'), 'lang' => $lang, 'text' => $text, 'sample' => false, 'source' => 'site'], 0);
    if (!empty($S['notify_email'])) {
        $ct = $circuit !== '' ? doc_by_slug('circuit', $circuit, false) : null;
        $html = mail_html('Avis', 'Nouvel avis à valider', "$name vient de laisser un avis sur le site. Il n’est pas encore visible : relisez-le puis publiez-le.",
            [['Prénom', $name], ['Pays', str_in($in['origin'] ?? '', 60)], ['Circuit', (string)($ct['data']['fr']['t'] ?? '')], ['Note', str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . "  $rating/5"]],
            $text, [['Relire et publier', site_url() . '/admin/?r=list&type=review', true]]);
        send_mail($S['notify_email'], "Nouvel avis à valider, $name", "$rating/5\n\n$text\n\n— À valider dans l'administration, rubrique Avis.", '', $html);
    }
    out(200, ['ok' => true]);
}
out(400, ['ok' => false]);
