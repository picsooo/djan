<?php
/* One-time installer: checks the server, creates the database with the site content and the first admin account. */
require __DIR__ . '/app/bootstrap.php';
session_boot();
header('X-Frame-Options: DENY');

$checks = [
    'PHP 8.1 ou plus récent' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extension PDO SQLite' => extension_loaded('pdo_sqlite'),
    'Extension GD (images)' => extension_loaded('gd'),
    'Extension mbstring' => extension_loaded('mbstring'),
    'Dossier data/ inscriptible' => is_dir(DATA) ? is_writable(DATA) : is_writable(ROOT),
    'Dossier uploads/ inscriptible' => is_dir(UPLOADS) ? is_writable(UPLOADS) : is_writable(ROOT),
];
$ready = !in_array(false, $checks, true);
$done = false;
$err = '';

$hasUsers = false;
if (installed()) {
    try { $hasUsers = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0; } catch (Throwable $t) { $hasUsers = false; }
}

if (!$hasUsers && $ready && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['pass'] ?? '');
    if (!csrf_ok()) $err = 'La session a expiré, rechargez la page.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Indiquez un nom et une adresse e-mail valide.';
    elseif (mb_strlen($pass) < 10) $err = 'Le mot de passe doit faire au moins 10 caractères.';
    elseif ($pass !== (string)($_POST['pass2'] ?? '')) $err = 'Les deux mots de passe ne sont pas identiques.';
    else {
        if (!is_dir(UPLOADS)) mkdir(UPLOADS, 0775, true);
        db_schema();
        $pdo = db();
        $pdo->beginTransaction();
        if ((int)$pdo->query('SELECT COUNT(*) FROM docs')->fetchColumn() === 0) {
            $seed = json_decode((string)file_get_contents(APP . '/seed.json'), true) ?: [];
            $st = $pdo->prepare('INSERT INTO docs(type, slug, position, published, data, created, updated) VALUES(?,?,?,?,?,?,?)');
            foreach ($seed as $d) $st->execute([$d['type'], $d['slug'], $d['position'], $d['published'], json_encode($d['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now(), now()]);
        }
        $pdo->prepare('INSERT INTO users(email, name, role, pass, created) VALUES(?,?,?,?,?)')
            ->execute([$email, $name, 'admin', password_hash($pass, PASSWORD_DEFAULT), now()]);
        $pdo->commit();
        $done = true;
        $hasUsers = true;
    }
}
$base = e(base_url());
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Installation</title><link rel="stylesheet" href="<?= $base ?>/admin/admin.css"></head>
<body class="auth"><main class="auth-box">
<h1>Installation du site</h1>
<?php if ($done): ?>
  <p class="flash ok">Le site est installé. Vous pouvez supprimer le fichier <code>install.php</code> du serveur.</p>
  <p><a class="btn" href="<?= $base ?>/admin/">Ouvrir l'administration</a> <a class="btn btn-line" href="<?= $base ?>/">Voir le site</a></p>
<?php elseif ($hasUsers): ?>
  <p>Le site est déjà installé. Supprimez <code>install.php</code> du serveur si ce n'est pas déjà fait.</p>
  <p><a class="btn" href="<?= $base ?>/admin/">Ouvrir l'administration</a></p>
<?php else: ?>
  <ul class="checks"><?php foreach ($checks as $k => $ok): ?><li class="<?= $ok ? 'ok' : 'ko' ?>"><?= e($k) ?></li><?php endforeach; ?></ul>
  <?php if (!$ready): ?>
    <p class="flash err">Le serveur ne remplit pas toutes les conditions. Activez les extensions manquantes dans cPanel (Select PHP Version) et donnez les droits d'écriture aux dossiers data/ et uploads/.</p>
  <?php else: ?>
    <?php if ($err): ?><p class="flash err"><?= e($err) ?></p><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <p>Créez le compte administrateur. Les contenus de la maquette sont importés automatiquement.</p>
      <label>Nom<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label>E-mail de connexion<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Mot de passe (10 caractères minimum)<input type="password" name="pass" required minlength="10" autocomplete="new-password"></label>
      <label>Confirmer le mot de passe<input type="password" name="pass2" required minlength="10" autocomplete="new-password"></label>
      <button class="btn">Installer le site</button>
    </form>
  <?php endif; ?>
<?php endif; ?>
</main></body></html>
