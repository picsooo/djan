<?php
/* Admin helpers: content types, labels, the generic form editor, image uploads. */
declare(strict_types=1);

/* ---------- content types ---------- */
function types(): array
{
    $today = date('Y-m-d');
    $lang = fn(array $f) => ['fr' => $f, 'en' => $f];
    return [
        'circuit' => ['add' => 'Ajouter un circuit', 'new' => 'Nouveau circuit', 'label' => 'Circuits', 'one' => 'circuit', 'icon' => 'route', 'slug' => true, 'url' => 'circuit-%s.html',
            'help' => 'Vos voyages. L’ordre ici est l’ordre d’affichage sur le site.',
            'blank' => ['img' => '', 'imgs' => [], 'd' => 0, 'n' => 0, 'price' => ''] + $lang(['t' => '', 's' => '', 'level' => '', 'season' => '', 'group' => '', 'tags' => [], 'p' => [['t' => '', 'p' => '']]])],
        'destination' => ['add' => 'Ajouter une destination', 'new' => 'Nouvelle destination', 'label' => 'Destinations', 'one' => 'destination', 'icon' => 'pin', 'slug' => true, 'url' => 'destinations.html#%s',
            'help' => 'Les lieux présentés sur la page Destinations et sur l’accueil.',
            'blank' => ['img' => '', 'img2' => '', 'word' => '', 'circuit' => ''] + $lang(['t' => '', 's' => '', 'p' => [], 'see' => [], 'acc' => ''])],
        'post' => ['add' => 'Écrire un article', 'new' => 'Nouvel article', 'label' => 'Blog', 'one' => 'article', 'icon' => 'pen', 'slug' => true, 'url' => 'blog-%s.html',
            'help' => 'Vos articles. Ils aident Google à trouver votre site : un bon titre, une description et des liens vers vos circuits.',
            'blank' => ['date' => $today, 'img' => '', 'author' => 'Mina'] + $lang(['t' => '', 'd' => '', 'body' => ''])],
        'faq' => ['add' => 'Ajouter une question', 'new' => 'Nouvelle question', 'label' => 'FAQ', 'one' => 'question', 'icon' => 'help', 'slug' => false,
            'help' => 'Questions fréquentes. Cochez « Afficher sur l’accueil » pour les mettre en avant.',
            'blank' => ['home' => false] + $lang(['g' => '', 'q' => '', 'a' => ''])],
        'photo' => ['add' => 'Ajouter une photo', 'new' => 'Nouvelle photo', 'label' => 'Galerie', 'one' => 'photo', 'icon' => 'image', 'slug' => false,
            'help' => 'Les photos de la page Galerie.',
            'blank' => ['img' => '', 'cat' => 'land', 'alt' => '']],
        'review' => ['add' => 'Ajouter un avis', 'new' => 'Nouvel avis', 'label' => 'Avis', 'one' => 'avis', 'icon' => 'star', 'slug' => false,
            'help' => 'Les avis envoyés depuis le site arrivent ici « en attente ». Publiez-les après relecture, ou ajoutez vous-même les avis reçus par WhatsApp.',
            'blank' => ['name' => '', 'origin' => '', 'circuit' => '', 'rating' => 5, 'date' => $today, 'lang' => 'fr', 'text' => '', 'sample' => false]],
    ];
}
function page_list(): array
{
    return [
        'home' => ['Accueil', 'Titre, photos du diaporama, textes et sections de la page d’accueil'],
        'day' => ['Une journée dans le désert', 'Les cinq moments de la section animée de l’accueil'],
        'about' => ['Présentation', 'Votre histoire, vos valeurs, les étapes de préparation'],
        'trips' => ['Page Circuits', 'Introduction de la page et la liste « inclus / non inclus » des circuits'],
        'destinations' => ['Page Destinations', 'Titre et introduction de la page'],
        'infos' => ['Infos pratiques', 'Visa, vols, saisons, bagages…'],
        'faq' => ['Page FAQ', 'Titre et texte de la page (les questions sont dans FAQ)'],
        'contact' => ['Contact', 'Textes de la page Contact'],
        'gallery' => ['Page Galerie', 'Titre et noms des filtres'],
        'blog' => ['Page Blog', 'Titre et introduction'],
        'reviews' => ['Page Avis', 'Titre et textes du formulaire « Laisser un avis »'],
        'booking' => ['Formulaire de réservation', 'Toutes les questions et réponses proposées'],
        'ui' => ['Libellés du site', 'Menu, boutons, pied de page'],
    ];
}

/* ---------- labels ---------- */
const LBL = [
    't' => 'Titre', 's' => 'Résumé', 'level' => 'Niveau', 'season' => 'Saison', 'group' => 'Taille du groupe', 'd' => 'Description courte (Google)',
    'img' => 'Photo', 'img2' => 'Deuxième photo', 'imgs' => 'Photos', 'price' => 'Prix affiché', 'word' => 'Grand mot en fond', 'circuit' => 'Circuit lié',
    'acc' => 'Comment on y va', 'date' => 'Date', 'author' => 'Auteur', 'body' => 'Texte de l’article', 'home' => 'Afficher sur l’accueil',
    'g' => 'Rubrique', 'q' => 'Question', 'a' => 'Réponse', 'cat' => 'Catégorie', 'alt' => 'Description de la photo (accessibilité)',
    'name' => 'Nom', 'origin' => 'Pays', 'rating' => 'Note', 'lang' => 'Langue de l’avis', 'text' => 'Texte de l’avis', 'sample' => 'Avis d’exemple (affiche le badge « contenu illustratif »)',
    'k' => 'Moment', 'p' => 'Texte', 'n' => 'Chiffre', 'id' => 'Ancre (lettres sans espace)',
    'h' => 'Titre de la page', 'lead' => 'Introduction', 'hero_img' => 'Photo d’en-tête', 'quote_img' => 'Photo de la citation', 'cta_img' => 'Photo du bandeau final',
    'title' => 'Titre principal', 'sub' => 'Sous-titre', 'kicker' => 'Petite phrase au-dessus du titre', 'intro_h' => 'Titre de l’introduction', 'intro_p' => 'Introduction',
    'intro_p2' => 'Deuxième paragraphe', 'intro_link' => 'Texte du lien', 'trips_h' => 'Titre de la section Circuits', 'trips_p' => 'Texte de la section Circuits',
    'facts_src' => 'Texte du lien source', 'incl_h' => 'Titre de la section « Tout est compris »', 'incl_p' => 'Texte de la section « Tout est compris »',
    'quote' => 'Citation', 'quote_by' => 'Signature de la citation', 'gal_h' => 'Titre de la section Galerie', 'gal_link' => 'Lien vers la galerie', 'blog_h' => 'Titre de la section Blog',
    'qb_trip' => 'Barre de réservation : Circuit', 'qb_month' => 'Barre de réservation : Mois', 'qb_people' => 'Barre de réservation : Voyageurs', 'qb_go' => 'Barre de réservation : bouton',
    'dest_h' => 'Titre de la section Destinations', 'dest_p' => 'Texte de la section Destinations', 'faq_h' => 'Titre de la section FAQ', 'faq_p' => 'Texte de la section FAQ',
    'rev_h' => 'Titre de la section Avis', 'rev_p' => 'Texte de la section Avis', 'unesco_url' => 'Lien source des chiffres',
    'values_h' => 'Titre des valeurs', 'langs' => 'Langues parlées', 'proc_h' => 'Titre des étapes', 'proc_p' => 'Texte des étapes', 'note' => 'Note en bas de page',
    'visa_src' => 'Lien source (visa)', 'more' => 'Titre « autre question »', 'more_p' => 'Texte « autre question »',
    'site_name' => 'Nom du site', 'logo' => 'Logo (fond clair)', 'logo_light' => 'Logo blanc (sur photo, facultatif)', 'favicon' => 'Icône d’onglet (carré)',
    'wa' => 'Numéro WhatsApp (chiffres, avec indicatif)', 'wa_display' => 'WhatsApp affiché', 'phone' => 'Téléphone (format international)', 'phone_display' => 'Téléphone affiché',
    'email' => 'E-mail affiché sur le site', 'notify_email' => 'E-mail qui reçoit les alertes (réservations, avis)', 'address' => 'Adresse',
    'instagram' => 'Lien Instagram', 'facebook' => 'Lien Facebook', 'domain' => 'Adresse du site (https://…)', 'indexable' => 'Le site est visible sur Google',
    'show_illus' => 'Afficher le badge « Contenu illustratif » (à décocher quand vos vrais textes sont en place)', 'og_img' => 'Image de partage (Facebook, WhatsApp)',
    'tagline' => 'Phrase de présentation (pied de page, Google)', 'seo_title' => 'Titre de l’accueil sur Google', 'credit' => 'Crédit en bas de page',
    'form_h' => 'Titre du formulaire', 'send' => 'Bouton d’envoi', 'thanks' => 'Message de remerciement', 'error' => 'Message d’erreur', 'empty' => 'Texte quand il n’y a pas encore d’avis',
    'saved' => 'Message après envoi', 'hello' => 'Début du message envoyé', 'subject' => 'Objet de l’e-mail', 'err' => 'Message d’erreur', 'next' => 'Bouton Suivant', 'prev' => 'Bouton Retour',
    'sum_h' => 'Titre du récapitulatif', 'sum_empty' => 'Récapitulatif vide', 'direct' => 'Texte « contact direct »', 'yes' => 'Mot « Oui »', 'no' => 'Mot « Non »',
    'done_h' => 'Titre de confirmation', 'done_p' => 'Texte de confirmation', 'f' => 'Questions du formulaire', 'wa_btn' => 'Bouton WhatsApp', 'mail' => 'Bouton e-mail',
];
const LBL_LIST = [
    'p' => 'Programme jour par jour', 'incl' => 'Ce qui est compris', 'facts' => 'Chiffres clés', 'blocks' => 'Blocs de texte', 'values' => 'Valeurs', 'proc' => 'Étapes de préparation',
    'steps' => 'Les moments de la journée', 'secs' => 'Rubriques', 'seasons' => 'Saisons', 'filters' => 'Filtres',
];
const LBL_LINES = [
    'p' => 'Paragraphes (un par ligne)', 'see' => 'À voir (un par ligne)', 'tags' => 'Mots-clés (un par ligne)', 'lines' => 'Lignes du grand titre (une par ligne)',
    'marquee' => 'Noms qui défilent (un par ligne)', 'ps' => 'Paragraphes (un par ligne)', 'li' => 'Liste à cocher (un élément par ligne)', 'months' => 'Mois (un par ligne)',
    'steps' => 'Noms des étapes (un par ligne)', 'included' => 'Inclus dans tous les circuits (un par ligne)', 'not_included' => 'Non inclus (un par ligne)',
];
const IMG_KEYS = ['img', 'img2', 'logo', 'logo_light', 'favicon', 'hero_img', 'quote_img', 'cta_img', 'og_img'];
const IMGS_KEYS = ['imgs', 'slides', 'intro_imgs', 'strip'];
const BOOL_KEYS = ['home', 'indexable', 'show_illus', 'sample'];
const INT_KEYS = ['rating'];
const LONG_KEYS = ['s', 'p', 'lead', 'sub', 'text', 'quote', 'acc', 'intro_p', 'intro_p2', 'a', 'tagline', 'note', 'more_p', 'cta_p', 'foot', 'hello', 'direct', 'thanks', 'saved', 'done_p', 'trips_p', 'incl_p'];
const CATS = ['land' => 'Paysages', 'people' => 'Rencontres', 'camp' => 'Bivouac'];

function label_for(string $key, $val): string
{
    if (is_array($val) && array_is_list($val)) {
        $isDicts = $val && is_array($val[0]);
        if ($isDicts && isset(LBL_LIST[$key])) return LBL_LIST[$key];
        if (!$isDicts && isset(LBL_LINES[$key])) return LBL_LINES[$key];
    }
    if ($key === 'd' && is_int($val)) return 'Nombre de jours (0 = sur mesure)';
    if ($key === 'n' && is_int($val)) return 'Nombre de nuits';
    if (isset(LBL[$key])) return LBL[$key];
    return ucfirst(str_replace('_', ' ', $key));
}

/* ---------- generic editor ---------- */
function blankify($v)
{
    if (is_bool($v)) return false;
    if (is_int($v)) return 0;
    if (is_string($v)) return '';
    if (is_array($v)) {
        if (array_is_list($v)) return ($v && is_array($v[0])) ? [blankify($v[0])] : [];
        $o = [];
        foreach ($v as $k => $x) $o[$k] = blankify($x);
        return $o;
    }
    return $v;
}

/** Renders the form fields of a data tree. $name is the input-name prefix, $en hides image fields (they follow the French tab). */
function fields(array $data, string $name, bool $en = false, int $depth = 0): string
{
    $o = '';
    foreach ($data as $key => $val) {
        $key = (string)$key;
        if (in_array($key, LANGS, true) && $depth === 0) continue;
        if (in_array($key, ['sample_note', 'source'], true)) continue;
        $o .= field($key, $val, $name . '[' . $key . ']', $en, $depth);
    }
    return $o;
}

function field(string $key, $val, string $name, bool $en, int $depth): string
{
    $label = e(label_for($key, $val));
    $n = e($name);
    if (in_array($key, IMG_KEYS, true)) {
        if ($en) return '';
        return '<div class="f f-img"><span class="fl">' . $label . '</span>' . img_widget($name, (string)$val) . '</div>';
    }
    if (in_array($key, IMGS_KEYS, true)) {
        if ($en) return '';
        $items = '';
        foreach ((array)$val as $p) $items .= imgs_item($name, (string)$p);
        return '<div class="f f-imgs"><span class="fl">' . $label . '</span><div class="imgs" data-name="' . $n . '[]">' . $items . '<button type="button" class="imgs-add" data-pick-multi>+ Ajouter</button></div><input type="hidden" name="' . $n . '[__list]" value="1"></div>';
    }
    if (in_array($key, BOOL_KEYS, true)) {
        return '<label class="f f-check"><input type="hidden" name="' . $n . '" value="0"><input type="checkbox" name="' . $n . '" value="1"' . ($val ? ' checked' : '') . '><span>' . $label . '</span></label>';
    }
    if ($key === 'rating') {
        $opts = '';
        for ($i = 5; $i >= 1; $i--) $opts .= '<option value="' . $i . '"' . ((int)$val === $i ? ' selected' : '') . '>' . str_repeat('★', $i) . " ($i/5)</option>";
        return '<label class="f"><span class="fl">' . $label . '</span><select name="' . $n . '">' . $opts . '</select></label>';
    }
    if ($key === 'circuit') {
        $opts = '<option value="">—</option>';
        foreach (docs('circuit', false) as $c) $opts .= '<option value="' . e($c['slug']) . '"' . ($c['slug'] === $val ? ' selected' : '') . '>' . e($c['data']['fr']['t'] ?? $c['slug']) . '</option>';
        return '<label class="f"><span class="fl">' . $label . '</span><select name="' . $n . '">' . $opts . '</select></label>';
    }
    if ($key === 'cat') {
        $opts = '';
        foreach (CATS as $k => $t) $opts .= '<option value="' . $k . '"' . ($k === $val ? ' selected' : '') . '>' . $t . '</option>';
        return '<label class="f"><span class="fl">' . $label . '</span><select name="' . $n . '">' . $opts . '</select></label>';
    }
    if ($key === 'lang' && is_string($val)) {
        return '<label class="f"><span class="fl">' . $label . '</span><select name="' . $n . '"><option value="fr"' . ($val === 'fr' ? ' selected' : '') . '>Français</option><option value="en"' . ($val === 'en' ? ' selected' : '') . '>Anglais</option></select></label>';
    }
    if ($key === 'date') return '<label class="f f-s"><span class="fl">' . $label . '</span><input type="date" name="' . $n . '" value="' . e($val) . '"></label>';
    if (is_int($val)) return '<label class="f f-s"><span class="fl">' . $label . '</span><input type="number" min="0" name="' . $n . '[__int]" value="' . e((string)$val) . '"></label>';
    if ($key === 'body') {
        return '<div class="f"><span class="fl">' . $label . '</span><div class="md-bar"><button type="button" data-md="h2">Intertitre</button><button type="button" data-md="b">Gras</button><button type="button" data-md="a">Lien</button><button type="button" data-md="ul">Liste</button></div>'
            . '<textarea class="md" name="' . $n . '" rows="22">' . e($val) . '</textarea><p class="hint">Une ligne vide sépare les paragraphes. « ## » au début d’une ligne crée un intertitre. Un lien s’écrit [texte](adresse), par exemple [réserver](reservation.html) ou [Tadrart Rouge](circuit-tadrart-rouge.html).</p></div>';
    }
    if (is_array($val) && array_is_list($val) && (!$val || !is_array($val[0]))) {
        $rows = max(3, min(14, count($val) + 1));
        return '<label class="f"><span class="fl">' . $label . '</span><textarea name="' . $n . '[__lines]" rows="' . $rows . '">' . e(implode("\n", array_map('strval', $val))) . '</textarea></label>';
    }
    if (is_array($val) && array_is_list($val)) {
        $ph = '__i' . $depth . '__';
        $items = '';
        foreach ($val as $i => $item) $items .= rep_item($name, (string)$i, $item, $en, $depth);
        $tpl = rep_item($name, $ph, blankify($val[0]), $en, $depth);
        return '<div class="f f-rep"><span class="fl">' . $label . '</span><div class="rep" data-ph="' . $ph . '">' . $items . '</div><template>' . $tpl . '</template><button type="button" class="btn-sm" data-rep-add>+ Ajouter</button></div>';
    }
    if (is_array($val)) {
        return '<fieldset class="f f-group"><legend>' . $label . '</legend>' . fields($val, $name, $en, $depth + 1) . '</fieldset>';
    }
    $long = in_array($key, LONG_KEYS, true) || mb_strlen((string)$val) > 90;
    if ($long) return '<label class="f"><span class="fl">' . $label . '</span><textarea name="' . $n . '" rows="' . max(2, min(8, (int)ceil(mb_strlen((string)$val) / 90) + 1)) . '">' . e($val) . '</textarea></label>';
    $type = in_array($key, ['email', 'notify_email'], true) ? 'email' : (in_array($key, ['instagram', 'facebook', 'domain', 'unesco_url', 'visa_src'], true) ? 'url' : 'text');
    return '<label class="f"><span class="fl">' . $label . '</span><input type="' . $type . '" name="' . $n . '" value="' . e($val) . '"></label>';
}

function rep_item(string $name, string $i, $item, bool $en, int $depth): string
{
    $title = is_array($item) ? (string)($item['t'] ?? $item['k'] ?? $item['q'] ?? $item['n'] ?? '') : '';
    $body = is_array($item) ? fields($item, $name . '[' . $i . ']', $en, $depth + 1) : field('v', $item, $name . '[' . $i . ']', $en, $depth + 1);
    return '<div class="rep-i"><div class="rep-h"><b class="rep-n"></b><span class="rep-t">' . e($title) . '</span>'
        . '<span class="rep-a"><button type="button" data-rep="up" title="Monter">↑</button><button type="button" data-rep="down" title="Descendre">↓</button><button type="button" data-rep="del" title="Supprimer">×</button></span></div>'
        . '<div class="rep-b">' . $body . '</div></div>';
}

function img_widget(string $name, string $val): string
{
    $src = $val !== '' ? e(asset($val)) : '';
    return '<div class="imgw' . ($val === '' ? ' empty' : '') . '"><img src="' . $src . '" alt=""><input type="hidden" name="' . e($name) . '" value="' . e($val) . '">'
        . '<span class="imgw-a"><button type="button" class="btn-sm" data-pick>Choisir</button><button type="button" class="btn-sm btn-ghost" data-clear>Retirer</button></span></div>';
}
function imgs_item(string $name, string $val): string
{
    return '<span class="imgs-i"><img src="' . e(asset($val)) . '" alt=""><input type="hidden" name="' . e($name) . '[]" value="' . e($val) . '"><button type="button" data-imgs-del title="Retirer">×</button></span>';
}

/** Turns posted form arrays back into clean data. */
function normalize($v, string $key = '')
{
    if (!is_array($v)) {
        if (in_array($key, BOOL_KEYS, true)) return $v === '1';
        if (in_array($key, INT_KEYS, true)) return max(0, (int)$v);
        return trim(str_replace("\r", '', (string)$v));
    }
    if (array_key_exists('__int', $v)) return max(0, (int)$v['__int']);
    if (array_key_exists('__lines', $v)) {
        return array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", '', (string)$v['__lines']))), fn($s) => $s !== ''));
    }
    if (array_key_exists('__list', $v)) { unset($v['__list']); return array_values(array_filter(array_map('strval', $v), fn($s) => $s !== '')); }
    if (in_array($key, BOOL_KEYS, true)) return in_array('1', $v, true);
    $out = [];
    $numeric = true;
    foreach ($v as $k => $x) { if (!is_int($k) && !ctype_digit((string)$k)) $numeric = false; $out[$k] = normalize($x, (string)$k); }
    if ($numeric) {
        $out = array_values($out);
        // A list of single scalar items rendered through field('v', …)
        $out = array_map(fn($x) => (is_array($x) && array_keys($x) === ['v']) ? $x['v'] : $x, $out);
    }
    return $out;
}

/** English images always follow the French tab. */
function sync_images(array $fr, array $en): array
{
    foreach ($fr as $k => $v) {
        if (in_array((string)$k, IMG_KEYS, true) || in_array((string)$k, IMGS_KEYS, true)) { $en[$k] = $v; continue; }
        if (is_array($v) && isset($en[$k]) && is_array($en[$k])) $en[$k] = sync_images($v, $en[$k]);
    }
    return $en;
}

/** Merges posted data over the stored doc, keeping keys the form does not show. */
function merge_posted(array $old, array $new): array
{
    foreach ($new as $k => $v) {
        if (in_array($k, LANGS, true) && is_array($v)) $old[$k] = array_replace($old[$k] ?? [], $v);
        else $old[$k] = $v;
    }
    if (isset($old['fr'], $old['en']) && is_array($old['fr']) && is_array($old['en'])) $old['en'] = sync_images($old['fr'], $old['en']);
    return $old;
}

/* ---------- uploads ---------- */
/** Saves an uploaded image (resized), returns its site-relative path or throws. */
function save_upload(array $f, int $max = 2200): string
{
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK) throw new RuntimeException($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE ? 'Fichier trop lourd pour le serveur.' : 'Envoi interrompu, réessayez.');
    if ($f['size'] > 25 * 1024 * 1024) throw new RuntimeException('Fichier trop lourd (25 Mo maximum).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($map[$mime]) || !@getimagesize($f['tmp_name'])) throw new RuntimeException('Format non accepté : utilisez JPG, PNG ou WebP.');
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']),
        'image/png' => @imagecreatefrompng($f['tmp_name']),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false,
        'image/gif' => @imagecreatefromgif($f['tmp_name']),
    };
    if (!$src) throw new RuntimeException('Image illisible.');
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($f['tmp_name'])['Orientation'] ?? 1);
        if ($o === 3) $src = imagerotate($src, 180, 0); elseif ($o === 6) $src = imagerotate($src, -90, 0); elseif ($o === 8) $src = imagerotate($src, 90, 0);
    }
    $w = imagesx($src); $h = imagesy($src);
    $r = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $r)); $nh = max(1, (int)round($h * $r));
    $keepAlpha = $mime !== 'image/jpeg';
    $dst = imagecreatetruecolor($nw, $nh);
    if ($keepAlpha) { imagealphablending($dst, false); imagesavealpha($dst, true); imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127)); }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $base = slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME)) ?: 'image';
    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(ROOT . '/' . $dir)) mkdir(ROOT . '/' . $dir, 0775, true);
    $ext = $keepAlpha ? 'png' : 'jpg';
    $rel = $dir . '/' . substr($base, 0, 40) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $ok = $keepAlpha ? imagepng($dst, ROOT . '/' . $rel, 8) : imagejpeg($dst, ROOT . '/' . $rel, 84);
    if (!$ok) throw new RuntimeException('Impossible d’enregistrer l’image (droits du dossier uploads/).');
    return $rel;
}

/** All images available in the media library. */
function media_list(): array
{
    $out = [];
    if (is_dir(UPLOADS)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOADS, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) if (preg_match('/\.(jpe?g|png|webp|gif)$/i', $f->getFilename())) $out[] = ['p' => 'uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen(UPLOADS) + 1)), 't' => $f->getMTime(), 'own' => true];
    }
    usort($out, fn($a, $b) => $b['t'] <=> $a['t']);
    foreach (glob(ROOT . '/assets/img/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $f) $out[] = ['p' => 'assets/img/' . basename($f), 't' => 0, 'own' => false];
    return $out;
}
