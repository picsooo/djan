<?php
/* Content updates shipped with the code: each step runs once on an installed site, then is recorded in the base.
   They only touch what they name (photo slots, new pages); texts already edited in the admin are left alone. */
declare(strict_types=1);

const MIGRATIONS = ['2026_11_new_media', '2026_11_english_first', '2026_11_notify_both'];

function migrate(): void
{
    static $done = false;
    if ($done) return; $done = true;
    $st = db()->prepare("SELECT id, data FROM docs WHERE type = 'meta' AND slug = 'migrations'");
    $st->execute();
    $row = $st->fetch();
    $ran = $row ? (json_decode($row['data'], true) ?: []) : [];
    $todo = array_values(array_diff(MIGRATIONS, $ran));
    if (!$todo) return;
    foreach ($todo as $m) {
        db()->beginTransaction();
        try { ('mig_' . $m)(); $ran[] = $m; doc_save($row ? (int)$row['id'] : null, 'meta', 'migrations', $ran, 0); db()->commit(); }
        catch (Throwable $t) { db()->rollBack(); error_log('migration ' . $m . ': ' . $t->getMessage()); return; }
        if (!$row) { $st->execute(); $row = $st->fetch(); }
    }
}

function mig_page(string $slug): array
{
    $st = db()->prepare("SELECT * FROM docs WHERE type = 'page' AND slug = ?");
    $st->execute([$slug]);
    $r = $st->fetch();
    return $r ? row_doc($r) : ['id' => null, 'data' => []];
}
function mig_save_page(string $slug, array $doc): void { doc_save($doc['id'] ?: null, 'page', $slug, $doc['data'], 1); }

/** The client's travellers are mostly foreigners: the home page opens in English (can be switched off in Réglages). */
function mig_2026_11_english_first(): void
{
    $st = db()->prepare("SELECT * FROM docs WHERE type = 'settings' LIMIT 1");
    $st->execute();
    if ($r = $st->fetch()) { $d = row_doc($r); $d['data']['en_default'] = true; doc_save($d['id'], 'settings', $d['slug'], $d['data']); }
}

/** Booking, contact and review alerts go to both of the agency's addresses. */
function mig_2026_11_notify_both(): void
{
    $st = db()->prepare("SELECT * FROM docs WHERE type = 'settings' LIMIT 1");
    $st->execute();
    if (!($r = $st->fetch())) return;
    $d = row_doc($r); $S = $d['data'];
    $all = array_merge(preg_split('/[,;\s]+/', (string)($S['notify_email'] ?? '')) ?: [], [$S['email'] ?? '', $S['email2'] ?? '']);
    $all = array_values(array_unique(array_filter(array_map('trim', $all), fn($a) => (bool)filter_var($a, FILTER_VALIDATE_EMAIL))));
    $d['data']['notify_email'] = implode(', ', $all);
    doc_save($d['id'], 'settings', $d['slug'], $d['data']);
}

/** New photos and videos sent by the client (November 2026): rock art, folklore, Sebiba page, testimonials, terms. */
function mig_2026_11_new_media(): void
{
    $i = fn(string $n) => 'assets/img/w-' . $n . '.jpg';

    // Rock art: new header, introduction and mosaic.
    $p = mig_page('rockart');
    if ($p['id']) {
        $p['data']['hero_img'] = $i('ra-hero');
        $p['data']['intro_img'] = $i('ra-intro');
        $p['data']['gal'] = array_map($i, ['ra-g1', 'ra-g2', 'ra-g3', 'ra-g4', 'ra-g5', 'ra-g6', 'ra-g7', 'ra-g8', 'ra-g9', 'ra-g10', 'ra-g11', 'ra-g12', 'ra-eleph']);
        foreach (($p['data']['fr']['where'] ?? []) as $k => $w) if (($w['circuit'] ?? '') === 'erg-admer-7-jours') $p['data']['fr']['where'][$k]['img'] = $i('ra-vache');
        mig_save_page('rockart', $p);
    }

    // Culture: folklore photos and videos, crafts.
    $p = mig_page('culture');
    if ($p['id']) {
        $p['data']['hero_img'] = $i('cu-hero');
        $p['data']['art_imgs'] = [$i('cu-art1'), $i('cu-art2')];
        $p['data']['folk_gal'] = array_map($i, ['cu-f1', 'cu-f2', 'cu-f3', 'cu-f4', 'cu-f5', 'cu-f6', 'cu-f7', 'cu-f8', 'cu-tahamat']);
        $vids = [1 => 'alagh', 2 => 'tindi'];
        foreach (($p['data']['fr']['folk'] ?? []) as $k => $f) {
            if ($k === 0) $p['data']['fr']['folk'][$k]['img'] = $i('cu-tahamat');
            $p['data']['fr']['folk'][$k]['vid'] = $vids[$k] ?? '';
        }
        $p['data']['fr'] += ['folk_gal_h' => 'Le folklore en images', 'folk_vid_h' => 'En vidéo'];
        $p['data']['en'] = ($p['data']['en'] ?? []) + ['folk_gal_h' => 'Folklore in pictures', 'folk_vid_h' => 'On video'];
        mig_save_page('culture', $p);
    }

    // Camel caravan tour: header photo and a short film.
    $st = db()->prepare("SELECT * FROM docs WHERE type = 'circuit' AND slug = 'caravane-dromadaires-7-jours'");
    $st->execute();
    if ($r = $st->fetch()) { $d = row_doc($r); $d['data']['img'] = $i('dr-caravane'); $d['data']['vid'] = 'droma'; doc_save($d['id'], 'circuit', $d['slug'], $d['data']); }

    // Gallery: a selection of group and landscape photos, placed first.
    $new = [['g-01', 'people'], ['g-02', 'land'], ['g-03', 'people'], ['g-04', 'land'], ['g-05', 'land'], ['g-06', 'people'], ['g-07', 'land'], ['g-08', 'land'], ['g-09', 'people'],
        ['g-10', 'camp'], ['g-11', 'land'], ['g-12', 'camp'], ['g-13', 'people'], ['ra-eleph', 'land'], ['ra-arch', 'land'], ['ra-g1', 'art'], ['ra-g4', 'art'], ['ra-g9', 'art']];
    db()->exec("UPDATE docs SET position = position + " . count($new) . " WHERE type = 'photo'");
    foreach ($new as $k => [$n, $cat]) doc_save(null, 'photo', '', ['img' => $i($n), 'cat' => $cat, 'alt' => ''], 1, $k);

    // Reviews: video testimonials and consent line.
    $p = mig_page('reviews');
    if ($p['id']) {
        $p['data']['videos'] = array_map(fn($n) => ['f' => 'tem' . $n, 'name' => ''], range(1, 7));
        $p['data']['fr'] += ['vid_h' => 'Ils racontent leur voyage', 'vid_p' => 'Quelques mots filmés sur place, à la fin du circuit.', 'consent' => 'J’accepte que mon avis et mon prénom soient publiés sur le site.', 'terms' => 'Conditions d’utilisation',
            'link_h' => 'Votre avis compte', 'link_p' => 'Vous avez voyagé avec Touring with Mina ? Racontez votre expérience en deux minutes. Votre avis sera publié après relecture.'];
        $p['data']['en'] = ($p['data']['en'] ?? []) + ['vid_h' => 'They tell their trip', 'vid_p' => 'A few words filmed on site, at the end of the tour.', 'consent' => 'I agree to my review and first name being published on the site.', 'terms' => 'Terms of use',
            'link_h' => 'Your review matters', 'link_p' => 'Did you travel with Touring with Mina? Tell us about it in two minutes. Your review is published after proofreading.'];
        mig_save_page('reviews', $p);
    }

    // Home: family block.
    $p = mig_page('home');
    if ($p['id']) {
        $p['data']['fam_imgs'] = [$i('fam1'), $i('fam2'), $i('fam3')];
        $p['data']['fr'] += ['fam_h' => 'Le désert en famille', 'fam_p' => 'Les enfants sont les bienvenus. Au bivouac, des ateliers les attendent : dessin, puzzles, jeux de société, pendant que les parents profitent du thé et du coucher de soleil.', 'fam_link' => 'Préparer un voyage en famille'];
        $p['data']['en'] = ($p['data']['en'] ?? []) + ['fam_h' => 'The desert with your family', 'fam_p' => 'Children are welcome. At camp, activities await them: drawing, puzzles and board games, while parents enjoy tea and the sunset.', 'fam_link' => 'Plan a family trip'];
        mig_save_page('home', $p);
    }
    if ((int)db()->query("SELECT COUNT(*) FROM docs WHERE type = 'section'")->fetchColumn() > 0
        && !(int)db()->query("SELECT COUNT(*) FROM docs WHERE type = 'section' AND data LIKE '%\"kind\":\"family\"%'")->fetchColumn()) {
        $after = db()->query("SELECT position FROM docs WHERE type = 'section' AND data LIKE '%\"kind\":\"culture\"%' LIMIT 1")->fetchColumn();
        $pos = $after === false ? null : (int)$after + 1;
        if ($pos !== null) db()->exec("UPDATE docs SET position = position + 1 WHERE type = 'section' AND position >= $pos");
        $t = SECTION_BUILTIN['family'];
        doc_save(null, 'section', '', ['kind' => 'family', 'img' => '', 'imgs' => [], 'fr' => ['t' => $t, 'p' => '', 'btn' => '', 'url' => ''], 'en' => ['t' => $t, 'p' => '', 'btn' => '', 'url' => '']], 1, $pos);
    }

    // Menu labels.
    $p = mig_page('ui');
    if ($p['id']) {
        $p['data']['fr'] += ['sebiba' => 'Sebiba', 'terms' => 'Conditions d’utilisation'];
        $p['data']['en'] = ($p['data']['en'] ?? []) + ['sebiba' => 'Sebiba', 'terms' => 'Terms of use'];
        mig_save_page('ui', $p);
    }

    // New pages.
    if (!mig_page('sebiba')['id']) mig_save_page('sebiba', ['id' => null, 'data' => require __DIR__ . '/content_sebiba.php']);
    if (!mig_page('terms')['id']) mig_save_page('terms', ['id' => null, 'data' => require __DIR__ . '/content_terms.php']);
}
