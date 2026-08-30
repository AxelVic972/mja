@php
    /**
     * Photothèque du kit.
     *
     * Les photos « en action » déposées dans public/images/carrousel passent en
     * premier : ce sont elles qui montrent l'association à l'œuvre. Le dossier
     * kit/ prend le relais, mais amputé de ses doublons — equipe-01 à 06,
     * IMG_3366 à 3369 et « Groupe Pic 2026 » sont la même soirée photographiée
     * en rafale, et equipe-02 est une copie de « MJA Beach Party 2 ». En garder
     * une seule évite des mosaïques où six tuiles montrent la même scène.
     */
    $doublonsRafale = [
        'equipe-01.jpg', 'equipe-02.jpg', 'equipe-03.jpg', 'equipe-04.jpg',
        'equipe-05.jpg', 'equipe-06.jpg',
        'IMG_3366.jpg', 'IMG_3367.jpg', 'IMG_3368.jpg', 'IMG_3369.jpg',
    ];

    $lister = function (string $dossier) {
        $chemin = public_path($dossier);
        $fichiers = is_dir($chemin)
            ? (glob($chemin . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: [])
            : [];
        sort($fichiers);

        return array_map(fn ($f) => $dossier . '/' . basename($f), $fichiers);
    };

    // Les visuels imprimés tirent d'abord sur les fichiers les plus définis :
    // une vignette de 350 px pixellise sur un kakémono de deux mètres.
    $parDefinition = function (array $chemins) {
        usort($chemins, function ($a, $b) {
            $ta = @getimagesize(public_path($a));
            $tb = @getimagesize(public_path($b));

            return (($tb[0] ?? 0) * ($tb[1] ?? 0)) <=> (($ta[0] ?? 0) * ($ta[1] ?? 0));
        });

        return $chemins;
    };

    /**
     * Photos « en action » du dossier kit/ : membres en intervention, en
     * tee-shirt de l'association (distributions Ti Dèj, Caravane de l'unité).
     * Le classement est fait à la main — un nom de fichier ne dit pas ce que
     * montre l'image, et c'est cette distinction qui décide quelle photo va
     * sur un flyer. Le dossier images/carrousel est prévu pour ça : tout ce
     * qu'on y dépose est considéré comme « en action », et les deux réserves
     * restent classées de la plus définie à la plus légère.
     */
    $actionDansKit = [
        'membres-07.jpg', 'membres-08.jpg', 'membres-09.jpg', 'membres-10.jpg',
    ];

    $kit = array_values(array_filter(
        $lister('images/kit'),
        fn ($p) => ! in_array(basename($p), $doublonsRafale, true),
    ));

    /**
     * Photo vedette : la vue aérienne de l'équipe sur la plage, avec « MJA »
     * tracé dans le sable. C'est la seule image du lot qui montre à la fois
     * le groupe, le lieu et le nom de l'association — elle tient la case
     * centrale des visuels à trois photos, où le regard se pose d'abord.
     * Elle est retirée des photos de groupe pour ne pas se retrouver deux
     * fois sur le même triptyque.
     */
    $nomVedette = 'WhatsApp Image 2026-08-20 at 19.17.20.jpeg';
    $vedette = "images/kit/{$nomVedette}";
    if (! is_file(public_path($vedette))) {
        $vedette = null;
    }

    $photosAction = $parDefinition(array_merge(
        $lister('images/carrousel'),
        array_values(array_filter($kit, fn ($p) => in_array(basename($p), $actionDansKit, true))),
    ));
    $photosGroupe = $parDefinition(array_values(array_filter(
        $kit,
        fn ($p) => ! in_array(basename($p), $actionDansKit, true)
                && basename($p) !== $nomVedette,
    )));

    // Résolution la plus faible du lot : sert à alerter avant impression.
    $definitionMini = null;
    foreach (array_merge($photosAction, $photosGroupe) as $p) {
        $t = @getimagesize(public_path($p));
        $largeur = $t[0] ?? 0;
        if ($largeur && ($definitionMini === null || $largeur < $definitionMini)) {
            $definitionMini = $largeur;
        }
    }

    // Alternance action / groupe : deux tuiles voisines ne se ressemblent pas.
    // Cette liste sert de repli général ; les gabarits qui savent ce qu'ils
    // veulent montrer piochent directement dans $photosAction ou $photosGroupe.
    $phototheque = [];
    for ($i = 0; $i < max(count($photosAction), count($photosGroupe)); $i++) {
        if (isset($photosAction[$i])) $phototheque[] = $photosAction[$i];
        if (isset($photosGroupe[$i])) $phototheque[] = $photosGroupe[$i];
    }
    if ($vedette) $phototheque[] = $vedette;

    // Tout ce qui n'est pas une photo d'action : c'est la suite du choix
    // proposé par le sélecteur de photo, une fois les quatre photos d'action
    // parcourues.
    $photosAutres = $parDefinition(array_merge(
        $photosGroupe,
        $vedette ? [$vedette] : [],
    ));

    // Définition de la plus petite photo « en action » : ce sont elles qui
    // partent sur les flyers, un avertissement séparé est donc utile.
    $definitionMiniAction = null;
    foreach ($photosAction as $p) {
        $l = (@getimagesize(public_path($p))[0] ?? 0);
        if ($l && ($definitionMiniAction === null || $l < $definitionMiniAction)) {
            $definitionMiniAction = $l;
        }
    }

    // Largeur réelle de chaque photo : le gabarit s'en sert pour ne pas
    // étirer une vignette de 356 px sur un fond de flyer pleine page.
    $largeurs = [];
    foreach ($phototheque as $p) {
        $largeurs[$p] = (@getimagesize(public_path($p))[0] ?? 0);
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MJ'Adhésion 2026-2027 — Kit de communication | Madin' Jeunes Ambition</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">

<!-- Polices de marque MJA (Gill Sans + AllRound Gothic) servies depuis /public/fonts -->
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Gill_Sans.woff2') }}" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Gill_Sans_Bold.woff2') }}" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/AllRoundGothic-Bold.woff2') }}" crossorigin>
<link rel="stylesheet" href="{{ asset('css/gill-sans.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

<style>
/* =====================================================================
   MJ'Adhésion — Kit de communication (page autonome, 100 % statique)
   Charte reprise du site : couleurs MJA, Gill Sans, AllRound Gothic.
   Aucune dépendance externe : tout est servi depuis /public.
   ===================================================================== */
:root{
  --blue:#3DAEF5; --bluedark:#1E93D6; --yellow:#F5A623; --red:#D0021B;
  --dark:#2048A4; --navy:#1A3D8A; --light:#EBF4FF; --gray:#333333;
  --line:#E9EEF6; --muted:#7A879B;
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  font-family:'Gill Sans','Open Sans',sans-serif; color:var(--gray);
  background:#fff; -webkit-font-smoothing:antialiased;
}
h1,h2,h3,h4{font-family:'Gill Sans','Montserrat',sans-serif;margin:0}
.font-round{font-family:'AllRound Gothic','Gill Sans',sans-serif}
a{color:inherit}
.wrap{max-width:1280px;margin:0 auto;padding:0 24px}
.tribar{display:flex;height:6px}
.tribar i{flex:1}
.tribar i:nth-child(1){background:var(--blue)}
.tribar i:nth-child(2){background:var(--yellow)}
.tribar i:nth-child(3){background:var(--red)}

/* ── En-tête ─────────────────────────────────────────────────────── */
header.hero{
  background:linear-gradient(135deg,#1A3D8A 0%,#2048A4 45%,#3262CC 100%);
  color:#fff;padding:44px 0 52px;position:relative;overflow:hidden
}
header.hero .ring{position:absolute;right:-70px;top:-70px;width:320px;height:320px;opacity:.13}
header.hero .brand{display:flex;align-items:center;gap:18px;margin-bottom:26px}
header.hero .brand img{height:64px;width:64px;object-fit:contain;background:#fff;border-radius:14px;padding:4px}
header.hero .brand b{font-size:15px;letter-spacing:.16em;text-transform:uppercase;display:block}
header.hero .brand span{font-size:13px;color:#B9CBEE}
header.hero h1{font-family:'AllRound Gothic','Gill Sans',sans-serif;font-weight:800;font-size:clamp(34px,5vw,58px);line-height:1.02;letter-spacing:-.01em}
header.hero h1 em{font-style:normal;color:var(--yellow)}
header.hero p.lead{max-width:680px;color:#CBD9F4;font-size:17px;line-height:1.6;margin:18px 0 0}
.badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:22px}
.badges span{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);padding:7px 14px;border-radius:999px;font-size:13px;font-weight:600}

/* ── Barre d'outils ─────────────────────────────────────────────── */
.toolbar{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.96);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.toolbar .inner{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px 0}
.chip{border:2px solid var(--line);background:#fff;color:#5A6B85;font-family:inherit;font-size:13px;font-weight:700;
  padding:8px 15px;border-radius:999px;cursor:pointer;transition:.15s}
.chip:hover{border-color:#CFDBEC}
.chip.on{background:var(--navy);border-color:var(--navy);color:#fff}
.chip.on.blue{background:var(--blue);border-color:var(--blue)}
.tsep{width:1px;height:26px;background:var(--line);margin:0 4px}
.tlabel{font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}

/* ── Panneau d'options ──────────────────────────────────────────── */
.options{background:var(--light);border-bottom:1px solid var(--line)}
.options .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;padding:22px 0}
.field label{display:block;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--dark);margin-bottom:7px}
.field input[type=text],.field select{width:100%;border:2px solid #D8E4F5;background:#fff;border-radius:12px;padding:10px 13px;font-family:inherit;font-size:14px;color:var(--gray)}
.field input[type=text]:focus,.field select:focus{outline:none;border-color:var(--blue)}
.field .hint{font-size:12px;color:var(--muted);margin-top:6px;line-height:1.45}
.filebtn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:2px dashed #B9CDE8;border-radius:12px;padding:10px 14px;
  font-size:13px;font-weight:700;color:var(--dark);cursor:pointer;width:100%}
.filebtn:hover{border-color:var(--blue);background:#F7FBFF}
.switch{display:inline-flex;align-items:center;gap:9px;font-size:14px;font-weight:600;color:var(--gray);cursor:pointer}
.switch input{width:18px;height:18px;accent-color:var(--dark)}

/* ── Sections & cartes ──────────────────────────────────────────── */
section.block{padding:52px 0 8px}
.sechead{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:26px}
.sechead .k{display:flex;align-items:center;gap:9px;margin-bottom:6px}
.sechead .k s{width:30px;height:2px;background:var(--blue);display:block}
.sechead .k em{font-style:normal;color:var(--blue);font-weight:700;font-size:12px;letter-spacing:.18em;text-transform:uppercase}
.sechead h2{font-family:'AllRound Gothic','Gill Sans',sans-serif;font-weight:800;font-size:clamp(24px,3vw,34px);color:var(--navy)}
.sechead p{color:var(--muted);font-size:14px;margin:8px 0 0;max-width:640px;line-height:1.55}

.gallery{display:grid;gap:26px}
.gallery.g-square{grid-template-columns:repeat(auto-fill,minmax(300px,1fr))}
.gallery.g-tall{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
.gallery.g-paper{grid-template-columns:repeat(auto-fill,minmax(290px,1fr))}
.gallery.g-wide{grid-template-columns:repeat(auto-fill,minmax(420px,1fr))}
.gallery.g-stand{grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}

.card{border:1px solid var(--line);border-radius:20px;overflow:hidden;background:#fff;transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column}
.card[hidden]{display:none}
section.block[hidden]{display:none}
.card:hover{transform:translateY(-3px);box-shadow:0 18px 44px rgba(32,72,164,.13)}
.card .art{background:#F4F7FC;padding:14px;display:flex;align-items:center;justify-content:center;cursor:zoom-in;min-height:120px}
.card .art svg{width:100%;height:auto;display:block;border-radius:6px;box-shadow:0 3px 14px rgba(20,40,90,.14)}
/* Réserve visuelle avant le rendu paresseux */
.card .art .ph{width:100%;aspect-ratio:1/1;border-radius:6px;background:
  linear-gradient(100deg,#EEF3FA 30%,#F8FAFD 50%,#EEF3FA 70%) #EEF3FA;
  background-size:220% 100%;animation:sk 1.4s ease-in-out infinite}
@keyframes sk{0%{background-position:120% 0}100%{background-position:-60% 0}}
.card .meta{padding:14px 16px 4px}
.card .meta h3{font-size:15px;font-weight:700;color:var(--navy);line-height:1.3}
.card .tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}
.card .tags b{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 9px;border-radius:6px;background:var(--light);color:var(--dark)}
.card .tags b.px{background:#F3F5F9;color:var(--muted)}
.card .dl{padding:12px 16px 16px;margin-top:auto}
.dlrow{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:9px}
.photorow .btn{min-width:30px;padding:5px 9px;font-size:15px;line-height:1}
.photorow .pnum{font-size:11px;color:var(--muted);font-variant-numeric:tabular-nums;min-width:52px;text-align:center}
.dlrow > u{font-style:normal;text-decoration:none;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);width:34px;flex-shrink:0}
.btn{border:none;font-family:inherit;font-size:12px;font-weight:700;padding:8px 12px;border-radius:9px;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:5px}
.btn.png{background:var(--light);color:var(--dark)}
.btn.png:hover{background:#DCE9FC}
.btn.pdf{background:var(--navy);color:#fff}
.btn.pdf:hover{background:var(--dark)}
.btn.ghost{background:#fff;border:2px solid var(--line);color:#5A6B85;padding:7px 12px}
.btn.ghost:hover{border-color:var(--blue);color:var(--dark)}
.btn:disabled{opacity:.5;cursor:progress}
.btn.big{font-size:14px;padding:12px 20px;border-radius:12px}
.btn.solid{background:var(--blue);color:#fff}
.btn.solid:hover{background:var(--bluedark)}

/* ── Aide / pied ────────────────────────────────────────────────── */
.note{background:#FFFBF2;border:1px solid #F6E3BE;border-left:4px solid var(--yellow);border-radius:14px;padding:16px 18px;font-size:14px;line-height:1.6;color:#6B5722;margin:34px 0 0}
.note b{color:#4A3B10}
footer.foot{margin-top:60px;background:var(--navy);color:#B9CBEE;padding:30px 0;font-size:13px}
footer.foot a{color:#fff;font-weight:600;text-decoration:none}

/* ── Visionneuse plein écran ────────────────────────────────────── */
.lightbox{position:fixed;inset:0;z-index:90;background:rgba(8,18,42,.88);display:none;align-items:center;justify-content:center;padding:28px}
.lightbox.on{display:flex}
.lightbox .box{max-width:min(94vw,900px);max-height:92vh;overflow:auto;background:#fff;border-radius:16px;padding:14px}
.lightbox .box svg{width:100%;height:auto;display:block}
.lightbox .close{position:absolute;top:18px;right:22px;background:#fff;border:none;width:42px;height:42px;border-radius:50%;font-size:18px;cursor:pointer;color:var(--navy)}

#hidden-qr{position:absolute;left:-9999px;top:0}
.toast{position:fixed;left:50%;bottom:26px;transform:translate(-50%,120%);background:var(--navy);color:#fff;
  padding:13px 22px;border-radius:999px;font-size:14px;font-weight:600;z-index:95;transition:transform .28s;box-shadow:0 10px 30px rgba(0,0,0,.25)}
.toast.on{transform:translate(-50%,0)}
</style>
</head>
<body>

<div class="tribar"><i></i><i></i><i></i></div>

<header class="hero">
  <svg class="ring" viewBox="0 0 200 200" fill="none" aria-hidden="true">
    <circle cx="100" cy="100" r="95" stroke="#3DAEF5" stroke-width="2"/>
    <circle cx="100" cy="100" r="68" stroke="#F5A623" stroke-width="2"/>
    <circle cx="100" cy="100" r="42" stroke="#D0021B" stroke-width="2"/>
  </svg>
  <div class="wrap">
    <div class="brand">
      <img src="{{ asset('images/logomjat.png') }}" alt="Logo Madin' Jeunes Ambition">
      <div>
        <b>Madin' Jeunes Ambition</b>
        <span>Association de jeunes bénévoles · Martinique et au-delà</span>
      </div>
    </div>
    <h1>Kit de communication<br><em>MJ'Adhésion</em> — Saison 2026-2027</h1>
    <p class="lead">Tous les visuels de la campagne d'adhésion et de réadhésion, prêts à publier et à imprimer :
       posts Instagram, stories, affiches, flyers, bannières et vidéos motion design. Sept directions graphiques —
       de l'esprit des visuels de l'an dernier (mosaïque de photos, « Relève le défi ! », QR bordeaux) à des
       propositions plus contemporaines. PNG jusqu'à 300&nbsp;dpi, PDF A5 / A4 / A3, vidéo MP4.</p>
    <div class="badges">
      <span><i class="fas fa-square"></i> Posts 1080×1080</span>
      <span><i class="fas fa-mobile-screen"></i> Stories 1080×1920</span>
      <span><i class="fas fa-file-pdf"></i> Affiches A5 / A4 / A3</span>
      <span><i class="fas fa-note-sticky"></i> Flyer recto-verso</span>
      <span><i class="fas fa-panorama"></i> Bannières web</span>
      <span><i class="fas fa-film"></i> Vidéos motion 5 s</span>
    </div>
  </div>
</header>

<div class="toolbar">
  <div class="wrap inner">
    <span class="tlabel">Format</span>
    <button class="chip on" data-filter="group" data-value="all">Tout</button>
    <button class="chip" data-filter="group" data-value="post">Posts</button>
    <button class="chip" data-filter="group" data-value="story">Stories</button>
    <button class="chip" data-filter="group" data-value="affiche">Affiches</button>
    <button class="chip" data-filter="group" data-value="flyer">Flyers</button>
    <button class="chip" data-filter="group" data-value="banner">Bannières</button>
    <button class="chip" data-filter="group" data-value="motion">Vidéos</button>
    <span class="tsep"></span>
    <span class="tlabel">Style</span>
    <button class="chip on blue" data-filter="style" data-value="all">Tous</button>
    <button class="chip" data-filter="style" data-value="blanc">Fond blanc</button>
    <button class="chip" data-filter="style" data-value="navy">Fond navy</button>
    <button class="chip" data-filter="style" data-value="pastel">Pastel</button>
    <button class="chip" data-filter="style" data-value="mosaic">Mosaïque</button>
    <button class="chip" data-filter="style" data-value="blocs">Blocs</button>
    <button class="chip" data-filter="style" data-value="typo">Typo XXL</button>
    <button class="chip" data-filter="style" data-value="photo">Photo</button>
    <span class="tsep"></span>
    <button class="btn ghost" id="btn-all-png"><i class="fas fa-download"></i> Tous les PNG affichés</button>
    <button class="btn ghost" id="btn-all-pdf"><i class="fas fa-file-pdf"></i> Tous les PDF A4</button>
  </div>
</div>

<div class="options">
  <div class="wrap">
    <div class="grid">
      <div class="field">
        <label for="opt-season">Mention de saison</label>
        <input type="text" id="opt-season" value="SAISON 2026-2027" maxlength="30">
        <div class="hint">Apparaît sur tous les visuels sous le titre.</div>
      </div>
      <div class="field">
        <label for="opt-url">Lien du QR code &amp; des visuels</label>
        <input type="text" id="opt-url" value="https://mja-martinique.com">
        <div class="hint">Utilise un lien tracké depuis l'admin (Sources) si tu veux mesurer les scans.</div>
      </div>
      <div class="field">
        <label>Photos (styles « Photo » et « Mosaïque »)</label>
        <label class="filebtn" for="opt-photo"><i class="fas fa-images"></i> <span id="photo-name">{{ count($phototheque) }} photo(s) pré-chargée(s)</span></label>
        <input type="file" id="opt-photo" accept="image/*" multiple style="display:none">
        <div class="hint">Classées de la plus définie à la plus légère : les grands formats piochent d'abord les meilleures.
          {{ count($photosAction) }} en action, {{ count($photosGroupe) }} de groupe — les flyers et affiches
          montrent d'abord une photo en action.
          @if($definitionMini && $definitionMini < 1200)
          <b style="color:#B45309">La plus petite ne fait que {{ $definitionMini }} px de large</b> — suffisant pour un post,
          juste pour un flyer A5, insuffisant pour une affiche A3 ou un kakémono.
          @endif
          @if($definitionMiniAction && $definitionMiniAction < 1200)
          <b style="color:#B45309">Les photos en action plafonnent à {{ $definitionMiniAction }} px de large</b> :
          dépose des photos de terrain en pleine résolution dans <code>public/images/carrousel/</code>,
          elles passeront automatiquement devant sur les flyers et les affiches.
          @endif
          Dépose tes propres fichiers pour les remplacer : indispensable pour l'impression, les photos issues d'Instagram étant compressées.</div>
      </div>
      <div class="field">
        <label for="opt-actions">Actions mises en avant</label>
        <textarea id="opt-actions" rows="4" spellcheck="false"
                  style="width:100%;font:inherit;font-size:13px;line-height:1.5;padding:9px 11px;border:1px solid #d9e2f0;border-radius:9px;resize:vertical"></textarea>
        <div class="hint">Une action par ligne, au format <b>Titre | Description</b>.
          Apparaissent sur le gabarit « Moderne » (flyers, affiches, kakémonos). Quatre lignes au maximum.</div>
      </div>
      <div class="field">
        <label for="opt-textes">Modifier librement les textes</label>
        <textarea id="opt-textes" rows="5" spellcheck="true" placeholder="Texte actuel | Nouveau texte&#10;J'ADHÈRE | JE REJOINS MJA"
                  style="width:100%;font:inherit;font-size:13px;line-height:1.5;padding:9px 11px;border:1px solid #d9e2f0;border-radius:9px;resize:vertical"></textarea>
        <div class="hint">Une modification par ligne, au format <b>Texte actuel | Nouveau texte</b>. Les remplacements sont appliqués à tous les visuels et exports.</div>
      </div>
      <div class="field">
        <label for="opt-qrcolor">Couleur du QR code</label>
        <select id="opt-qrcolor">
          <option value="#9B1C1E">Bordeaux (comme la saison 2025)</option>
          <option value="#1A3D8A">Navy MJA</option>
          <option value="#111111">Noir</option>
        </select>
        <div class="hint">Le bordeaux reprend l'anneau du logo et les visuels de l'an dernier.</div>
      </div>
      <div class="field">
        <label>Affichages</label>
        <label class="switch"><input type="checkbox" id="opt-price"> Afficher la cotisation (réservé aux supports internes)</label><br>
        <label class="switch" style="margin-top:8px"><input type="checkbox" id="opt-qr" checked> Afficher le QR code</label>
        <div class="hint">Décoche le QR pour les posts si tu préfères « lien en bio ».</div>
      </div>
    </div>
  </div>
</div>

<main class="wrap">

  <section class="block" data-group="post">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Instagram · Facebook</em></div>
        <h2>Posts carrés — 1080 × 1080</h2>
        <p>Pour le feed. Publie-les seuls ou enchaîne-les en carrousel : annonce, réadhésion, pourquoi adhérer, comment adhérer, chiffres clés.</p>
      </div>
    </div>
    <div class="gallery g-square" id="gal-post"></div>
  </section>

  <section class="block" data-group="story">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Stories · Reels · Statut WhatsApp</em></div>
        <h2>Stories verticales — 1080 × 1920</h2>
        <p>Zones hautes et basses laissées libres pour le nom du compte, les stickers et le lien.</p>
      </div>
    </div>
    <div class="gallery g-tall" id="gal-story"></div>
  </section>

  <section class="block" data-group="affiche">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Impression</em></div>
        <h2>Affiches — A5 / A4 / A3</h2>
        <p>Mêmes visuels, trois tailles : lycées, maisons de quartier, médiathèques, commerces partenaires. PDF pour l'imprimeur, PNG 300 dpi pour un tirage bureautique.</p>
      </div>
    </div>
    <div class="gallery g-paper" id="gal-affiche"></div>
  </section>

  <section class="block" data-group="flyer">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>À distribuer</em></div>
        <h2>Flyer A5 — recto / verso</h2>
        <p>Recto accrocheur, verso complet : les 3 étapes de l'adhésion, ce que MJA apporte, les infos pratiques et les contacts.</p>
      </div>
    </div>
    <div class="gallery g-paper" id="gal-flyer"></div>
  </section>

  <section class="block" data-group="stand">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Sur le terrain</em></div>
        <h2>Tenue de stand — kakémono, pancarte, chevalet</h2>
        <p>Les formats verticaux d'un stand : kakémono roll-up 85 × 200 cm lisible à dix mètres,
           pancarte A2 à brandir ou à poser, chevalet de comptoir A5 pour le QR code d'adhésion.
           Tous en PDF à la taille exacte pour l'imprimeur.</p>
      </div>
    </div>
    <div class="gallery g-tall" id="gal-stand"></div>
  </section>

  <section class="block" data-group="banner">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Web</em></div>
        <h2>Bannières &amp; couvertures</h2>
        <p>Couverture Facebook 1640 × 624 et image de partage 1200 × 630 (aperçu des liens sur WhatsApp, Facebook, LinkedIn).</p>
      </div>
    </div>
    <div class="gallery g-wide" id="gal-banner"></div>
  </section>

  <section class="block" data-group="motion">
    <div class="sechead">
      <div>
        <div class="k"><s></s><em>Motion design</em></div>
        <h2>Vidéos animées — 2,5 s à 12 s</h2>
        <p>Même charte, en mouvement : le logo apparaît, le titre monte ligne par ligne, le soulignement jaune se déploie,
           les pastilles éclosent puis le bouton pulse. Idéal en reel, en story ou sur un écran d'accueil.
           « Aperçu animé » joue l'animation dans la carte ; le téléchargement produit un vrai fichier vidéo
           (MP4 si le navigateur le permet, sinon WebM).</p>
        <p>Trois durées : la <b>bande-annonce de 2,5 s</b> se colle en intro ou en outro d'un autre montage pour
           annoncer que la campagne démarre ; le <b>format court de 5 s</b> sert de post ou de story ;
           la <b>présentation de 12 s</b> laisse le temps de dérouler les valeurs et les projets.
           Les reels verticaux existent en version photo et mosaïque.</p>
      </div>
    </div>
    <div class="gallery g-square" id="gal-motion"></div>
  </section>

  <div class="note">
    <b>Bon à savoir avant impression.</b> Les PDF sont générés à 300 dpi à la taille exacte de la page (A5 148×210, A4 210×297, A3 297×420 mm),
    sans fond perdu : les visuels gardent une marge intérieure de 17 mm, donc un léger rognage de l'imprimeur ne coupe aucun texte.
    Si ton imprimeur demande un fond perdu de 3 mm, envoie-lui le PNG 300 dpi en lui précisant « mise à l'échelle 103 % ».
  </div>
</main>

<footer class="foot">
  <div class="wrap">
    Madin' Jeunes Ambition — Relève tous les défis ! ·
    <a href="mailto:{{ config('mja.contact_email') }}">{{ config('mja.contact_email') }}</a> ·
    <a href="https://www.instagram.com/madin_jeunes_ambition/" target="_blank" rel="noopener">@madin_jeunes_ambition</a> ·
    <a href="{{ route('adhesion') }}">mja-martinique.com</a>
  </div>
</footer>

<div class="lightbox" id="lightbox">
  <button class="close" id="lb-close" aria-label="Fermer">✕</button>
  <div class="box" id="lb-box"></div>
</div>
<div id="hidden-qr"></div>
<div class="toast" id="toast"></div>

<script src="{{ asset('vendor/qrcode/qrcode.min.js') }}"></script>
<script>
/* Base des URL, fournie par Laravel : la page fonctionne quel que soit
   le sous-dossier de déploiement. */
var BASE_URL = @json(rtrim(asset('/'), '/') . '/');
function ASSET(path){ return BASE_URL + path; }

/* =====================================================================
   1. Constantes de marque
   ===================================================================== */
var C = {
  blue:'#3DAEF5', bluedark:'#1E93D6', yellow:'#F5A623', red:'#D0021B',
  dark:'#2048A4', navy:'#1A3D8A', mid:'#3262CC', light:'#EBF4FF',
  gray:'#333333', white:'#FFFFFF', ink:'#0B1E45',
  /* Bordeaux de l'anneau du logo — utilisé sur les visuels d'adhésion 2025
     pour le QR code et le « INSCRIS-TOI ! ». */
  bordeaux:'#9B1C1E'
};
var FAM_AG = "AllRound Gothic, Gill Sans, Montserrat, sans-serif";
var FAM_GS = "Gill Sans, Open Sans, sans-serif";

var ORG   = "MADIN' JEUNES AMBITION";
/* Le sous-titre dit qui est l'association ; le slogan, lui, prend la place
   forte qu'il mérite — la pastille ronde du gabarit « Trio ». Les deux se
   répétaient tant qu'ils partageaient le même bloc d'en-tête. */
var TAG    = "Association de jeunes engagés en Martinique et au-delà";
var CRI    = ['RELÈVE', 'TOUS LES', 'DÉFIS !'];
var SLOGAN = "Madin' Jeunes Ambition relève tous les défis !";
var INSTA = "@madin_jeunes_ambition";
var FACEBOOK = "MadinJeunesAmbition";
var SITE  = "mja-martinique.com";
var MAIL  = @json(config('mja.contact_email'));
var TEL   = "0696 43 88 21";
/* Corrections libres saisies dans le panneau : chaque clé est remplacée dans
   tous les gabarits, y compris lors des exports PNG/PDF/vidéo. */
var TEXTES_LIBRES = {};

function texteLibre(texte){
  var sortie = String(texte);
  Object.keys(TEXTES_LIBRES).forEach(function(avant){
    if (!avant) return;
    sortie = sortie.split(avant).join(TEXTES_LIBRES[avant]);
  });
  return sortie;
}

/* Options globales, pilotées par le panneau du haut */
var OPT = {
  season:"SAISON 2026-2027", url:"https://mja-martinique.com",
  photo:null,        /* 1re photo — styles « Photo » */
  photos:[],         /* toutes les photos — style « Mosaïque » */
  price:false, qr:true,
  qrColor:C.bordeaux /* bordeaux comme sur les visuels 2025 ; navy possible */
};

/* Ressources encodées en base64 (nécessaires pour l'export : un SVG
   rasterisé dans un <canvas> n'a accès à aucune ressource externe). */
var ASSETS = { logo:null, fonts:'', qr:null, photos:{} };

/* Photothèque par défaut : photos de membres extraites des publications
   Instagram de l'association (collage d'adhésion de novembre 2025 et visuels
   « Ti Déj » / « Journée de l'amitié »). Classées de la meilleure résolution
   à la plus faible. Elles servent tant que l'utilisateur n'a rien déposé. */
/* Ordre entrelacé : une photo « equipe-* » (haute définition, fournie par
   l'association) puis une photo « membres-* » (extraite d'Instagram, contexte
   différent : plage, bateau, Noël, T-shirts). Ainsi six tuiles consécutives de
   mosaïque mélangent toujours qualité et variété de situations. */
var DEFAULT_PHOTOS = @json(array_values($phototheque));

/* Les deux réserves, classées de la plus définie à la plus légère.
   ACTION = membres en intervention, GROUPE = photos d'équipe et de soirée.
   Les gabarits à une seule photo tirent dans ACTION : c'est elle qui montre
   ce que fait l'association. Les triptyques prennent une action puis deux
   groupes — l'action dit quoi, le groupe dit avec qui. */
var PHOTOS_ACTION = @json(array_values($photosAction));
var PHOTOS_GROUPE = @json(array_values($photosGroupe));

/* Largeur en pixels de chaque photo de la photothèque. */
var LARGEURS = @json($largeurs);

/* Réserve d'une seule image : la vue aérienne de la plage, pour la case
   centrale des visuels à trois photos. Une liste plutôt qu'une constante,
   pour passer par le même `choisirPhoto` que les autres réserves — et pour
   qu'un fichier absent (liste vide) fasse simplement retomber la tuile sur
   la rotation générale, sans trou dans le visuel. */
var PHOTOS_VEDETTE = @json($vedette ? [$vedette] : []);

/* Tout le reste de la photothèque, classé de la plus définie à la plus
   légère. */
var PHOTOS_AUTRES = @json(array_values($photosAutres));

/**
 * Réserve en deux blocs, pour les cases qui montrent UNE photo : les quatre
 * photos d'action d'abord — ce sont elles qui doivent sortir par défaut sur
 * un flyer ou une affiche — puis toute la photothèque.
 *
 * Deux blocs plutôt qu'une liste à plat, parce que la préférence de
 * définition ne doit jouer qu'à l'intérieur d'un bloc : à plat, la photo de
 * groupe en 3566 px remonterait devant les photos d'action et le tirage par
 * défaut ne montrerait plus l'association à l'œuvre. Ainsi le défaut reste
 * une action, et le sélecteur continue au-delà — utile en A3, où seules les
 * photos de groupe ont la définition nécessaire.
 */
var PHOTOS_LARGE = [PHOTOS_ACTION, PHOTOS_AUTRES];

/* Décalage dans la photothèque, propre au visuel en cours de rendu : c'est ce
   qui fait que deux mosaïques ne montrent pas les mêmes photos. */
var PIDX = 0;
var EMBED_PHOTOS = false;

/* Décalage de photo choisi à la main, visuel par visuel. Deux visuels voisins
   tirent déjà des photos différentes ; ce décalage sert à changer celle d'un
   visuel précis sans toucher aux autres. Il s'applique à l'aperçu comme à
   l'export, puisque tout part de PIDX. */
var DECALAGE = {};

/* Décalage du visuel en cours de composition. Séparé de PIDX : le rang de
   départ du gabarit doit rester dans le premier bloc de la réserve (une photo
   d'action sur un flyer), alors que le décalage choisi à la main a le droit
   d'en sortir pour parcourir toute la photothèque. */
var DECAL = 0;

/* Nombre de tuiles photo posées par le dernier visuel construit : le
   sélecteur n'a de sens que sur un visuel qui montre des photos, et on ne le
   sait qu'après l'avoir composé. RESERVE retient la plus grande réserve
   consultée : c'est de ce nombre de crans que le sélecteur dispose avant de
   retomber sur la même image. */
var TUILES = 0;
var TUILES_RESERVE = 0;

/** Source d'image à l'index `i` (bouclage). Renvoie une data-URI à l'export. */
function photoAt(i){
  if (OPT.photos.length) return OPT.photos[((i % OPT.photos.length) + OPT.photos.length) % OPT.photos.length];
  var n = DEFAULT_PHOTOS.length, path = DEFAULT_PHOTOS[((i % n) + n) % n];
  if (EMBED_PHOTOS && ASSETS.photos[path]) return 'data:image/jpeg;base64,' + ASSETS.photos[path];
  return ASSET(path.split('/').map(encodeURIComponent).join('/'));
}

/* =====================================================================
   2. Utilitaires
   ===================================================================== */
/**
 * Glyphe Instagram (contour + objectif + point) placé devant le pseudo.
 * Sans lui, « @madin_jeunes_ambition » ne dit pas de quel réseau il s'agit.
 */
function instaGlyph(x, y, size, fill){
  var r = size * 0.22, k = size / 24;
  return '<g transform="translate(' + (x).toFixed(2) + ',' + (y - size * 0.82).toFixed(2) + ') scale(' + k.toFixed(4) + ')" fill="none" stroke="' + fill + '" stroke-width="2.2">'
    + '<rect x="2.4" y="2.4" width="19.2" height="19.2" rx="5.6"/>'
    + '<circle cx="12" cy="12" r="4.8"/>'
    + '</g>'
    + '<circle cx="' + (x + size * 0.735).toFixed(2) + '" cy="' + (y - size * 0.545).toFixed(2) + '" r="' + (size * 0.072).toFixed(2) + '" fill="' + fill + '"/>';
}

/** Pictogramme Facebook, placé devant le nom du compte. */
function facebookGlyph(x, y, size, fill){
  var cx = x + size * 0.42, cy = y - size * 0.42, r = size * 0.42;
  return '<circle cx="' + cx.toFixed(2) + '" cy="' + cy.toFixed(2) + '" r="' + r.toFixed(2) + '" fill="' + fill + '"/>'
    + '<text x="' + (cx - size * 0.07).toFixed(2) + '" y="' + (cy + size * 0.25).toFixed(2) + '" fill="#FFFFFF" font-family="Arial,sans-serif" font-size="' + (size * 0.86).toFixed(2) + '" font-weight="700">f</text>';
}

/** Comptes Instagram et Facebook, chacun précédé de son pictogramme. */
function instaTag(x, y, size, fill, opts){
  var o = opts || {}, wght = o.w || 600, textSize = size * 0.78;
  var gap = size * 1.25, between = size * 0.58, fbSize = size * 0.86;
  var instaText = texteLibre(INSTA), facebookText = texteLibre(FACEBOOK);
  var instaW = measure(instaText, textSize, wght, FAM_GS), facebookW = measure(facebookText, textSize, wght, FAM_GS);
  var totalW = gap + instaW + between + fbSize + between * 0.72 + facebookW;
  var left = x;
  if (o.anchor === 'end') left = x - totalW;
  if (o.anchor === 'middle') left = x - totalW / 2;
  var fbX = left + gap + instaW + between;
  return instaGlyph(left, y, size, fill)
    + T(left + gap, y, INSTA, { size:textSize, fill:fill, w:wght })
    + facebookGlyph(fbX, y, fbSize, fill)
    + T(fbX + fbSize + between * 0.72, y, FACEBOOK, { size:textSize, fill:fill, w:wght });
}

/* =====================================================================
   2 bis. Pictogrammes
   Tracés vectoriels simples, dessinés dans une boîte de 24 × 24 puis mis à
   l'échelle. Ils reprennent le vocabulaire des maquettes : un pictogramme
   par bénéfice, par action et par moyen de contact.
   ===================================================================== */
var PICTOS = {
  personnes: '<circle cx="8.5" cy="8" r="3.4"/><circle cx="16.5" cy="9" r="2.6"/>'
           + '<path d="M2.5 20c0-3.6 2.7-6 6-6s6 2.4 6 6" fill="none" stroke-width="2.1"/>'
           + '<path d="M15 14.2c3 0 6 1.8 6 5.8" fill="none" stroke-width="2.1"/>',
  ampoule:   '<path d="M12 3a6 6 0 0 0-3.4 10.9c.6.4 1 1.1 1 1.9v.7h4.8v-.7c0-.8.4-1.5 1-1.9A6 6 0 0 0 12 3z" fill="none" stroke-width="2"/>'
           + '<rect x="9.6" y="18" width="4.8" height="1.9" rx=".9"/><rect x="10.4" y="20.8" width="3.2" height="1.6" rx=".8"/>'
           + '<path d="M12 .8v1.4M4.4 4.2l1 1M19.6 4.2l-1 1M1.6 11.4H3M21 11.4h1.4" fill="none" stroke-width="1.7" stroke-linecap="round"/>',
  trophee:   '<path d="M7 3h10v5.2a5 5 0 0 1-10 0z" fill="none" stroke-width="2"/>'
           + '<path d="M7 4.6H4.4v1.6A3.2 3.2 0 0 0 7.6 9.4M17 4.6h2.6v1.6a3.2 3.2 0 0 1-3.2 3.2" fill="none" stroke-width="1.8"/>'
           + '<rect x="10.8" y="13.4" width="2.4" height="3.6"/><rect x="7.6" y="17" width="8.8" height="2.4" rx="1"/>'
           + '<rect x="6" y="19.8" width="12" height="2.4" rx="1"/>',
  fusee:     '<path d="M12 2c3.2 2.2 5 5.6 5 9.4l-2.2 3.4H9.2L7 11.4C7 7.6 8.8 4.2 12 2z" fill="none" stroke-width="2"/>'
           + '<circle cx="12" cy="9.2" r="2"/>'
           + '<path d="M9.2 15.2 6.6 17l.8-3.6M14.8 15.2 17.4 17l-.8-3.6" fill="none" stroke-width="1.8"/>'
           + '<path d="M10.6 18.4 12 22l1.4-3.6" fill="none" stroke-width="1.8"/>',
  diplome:   '<path d="M12 3.4 22 8l-10 4.6L2 8z" fill="none" stroke-width="2"/>'
           + '<path d="M6 10.6v4.8c0 1.8 2.7 3.2 6 3.2s6-1.4 6-3.2v-4.8" fill="none" stroke-width="2"/>'
           + '<path d="M21 8.6v5" fill="none" stroke-width="1.8" stroke-linecap="round"/>',
  megaphone: '<path d="M4 10.4 16 5.2v13.6L4 13.6z" fill="none" stroke-width="2"/>'
           + '<path d="M16 8.6c2.2 0 3.6 1.4 3.6 3.4S18.2 15.4 16 15.4" fill="none" stroke-width="1.9"/>'
           + '<path d="M6.6 14.2v4a1.7 1.7 0 0 0 3.4 0v-2.6" fill="none" stroke-width="1.9"/>',
  coche:     '<circle cx="12" cy="12" r="9.4" fill="none" stroke-width="2"/>'
           + '<path d="m7.6 12.2 3 3 5.8-6" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
  telephone: '<path d="M6.4 3.6h3l1.6 4-2 1.4a11 11 0 0 0 5 5l1.4-2 4 1.6v3c0 1-.8 1.8-1.8 1.8A15.6 15.6 0 0 1 4.6 5.4c0-1 .8-1.8 1.8-1.8z" fill="none" stroke-width="2" stroke-linejoin="round"/>',
  enveloppe: '<rect x="2.6" y="5" width="18.8" height="14" rx="2.4" fill="none" stroke-width="2"/>'
           + '<path d="m3.4 7 8.6 6 8.6-6" fill="none" stroke-width="2" stroke-linecap="round"/>',
  insta:     '<rect x="2.6" y="2.6" width="18.8" height="18.8" rx="5.4" fill="none" stroke-width="2.1"/>'
           + '<circle cx="12" cy="12" r="4.6" fill="none" stroke-width="2.1"/><circle cx="17.4" cy="6.6" r="1.3"/>',
  facebook:  '<circle cx="12" cy="12" r="9.4"/><text x="10.2" y="17.4" fill="#FFFFFF" stroke="none" font-family="Arial,sans-serif" font-size="15" font-weight="700">f</text>',
  cible:     '<circle cx="12" cy="12" r="9" fill="none" stroke-width="2"/><circle cx="12" cy="12" r="4.6" fill="none" stroke-width="2"/><circle cx="12" cy="12" r="1.4"/>',
  coeur:     '<path d="M12 20.4S3.4 15.2 3.4 9.4a4.6 4.6 0 0 1 8.6-2.4 4.6 4.6 0 0 1 8.6 2.4c0 5.8-8.6 11-8.6 11z" fill="none" stroke-width="2" stroke-linejoin="round"/>',
  ecran:     '<rect x="2.6" y="4" width="18.8" height="12.4" rx="2.2" fill="none" stroke-width="2"/>'
           + '<path d="M8 20h8M12 16.6V20" fill="none" stroke-width="2" stroke-linecap="round"/>',
  fleche:    '<path d="M4 12h15M13.4 6.4 19.6 12l-6.2 5.6" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>'
};

/** Pictogramme `nom` dans une boîte de côté `taille`, coin haut-gauche (x,y). */
function picto(nom, x, y, taille, couleur){
  var d = PICTOS[nom];
  if (!d) return '';
  var k = taille / 24;
  return '<g transform="translate(' + x.toFixed(2) + ',' + y.toFixed(2) + ') scale(' + k.toFixed(4) + ')"'
       + ' fill="' + couleur + '" stroke="' + couleur + '" stroke-width="0">' + d + '</g>';
}

/** Pictogramme centré dans une pastille ronde — motif récurrent des maquettes. */
function pastillePicto(cx, cy, r, nom, fondPastille, couleurPicto){
  return '<circle cx="' + cx.toFixed(2) + '" cy="' + cy.toFixed(2) + '" r="' + r.toFixed(2) + '" fill="' + fondPastille + '"/>'
       + picto(nom, cx - r * 0.58, cy - r * 0.58, r * 1.16, couleurPicto);
}

function esc(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function toast(msg){
  var t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('on');
  clearTimeout(toast._t); toast._t = setTimeout(function(){ t.classList.remove('on'); }, 2600);
}
function bufToB64(buf){
  var bytes = new Uint8Array(buf), out = '', chunk = 0x8000;
  for (var i = 0; i < bytes.length; i += chunk) {
    out += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
  }
  return btoa(out);
}
async function fetchB64(url){
  var r = await fetch(url);
  if (!r.ok) throw new Error('Ressource introuvable : ' + url);
  return bufToB64(await r.arrayBuffer());
}

/* Mesure de texte réelle (les polices de marque sont chargées dans la page) :
   permet d'ajuster la largeur des pastilles/boutons et la taille des titres XXL. */
var _mc = document.createElement('canvas').getContext('2d');
function measure(text, size, weight, fam){
  _mc.font = (weight||700) + ' ' + size + 'px ' + (fam === FAM_AG ? '"AllRound Gothic","Gill Sans"' : '"Gill Sans"');
  return _mc.measureText(text).width;
}
/** Plus grande taille de police telle que `text` tienne dans `maxW`. */
function fitSize(text, maxW, maxSize, weight, fam){
  var w = measure(text, 100, weight, fam);
  if (!w) return maxSize;
  return Math.min(maxSize, Math.floor(100 * maxW / w));
}

/* =====================================================================
   3. Primitives SVG
   ===================================================================== */
function T(x, y, s, o){
  o = o || {};
  s = texteLibre(s);
  var fam = o.f === 'ag' ? FAM_AG : FAM_GS;
  return '<text x="' + x + '" y="' + y + '"'
    + ' font-family="' + fam + '"'
    + ' font-size="' + (o.size || 32) + '"'
    + ' font-weight="' + (o.w === undefined ? 700 : o.w) + '"'
    + ' fill="' + (o.fill || C.gray) + '"'
    + (o.anchor ? ' text-anchor="' + o.anchor + '"' : '')
    + (o.ls ? ' letter-spacing="' + o.ls + '"' : '')
    + (o.it ? ' font-style="italic"' : '')
    + (o.op !== undefined ? ' opacity="' + o.op + '"' : '')
    + '>' + esc(s) + '</text>';
}
/** Texte qui se réduit automatiquement pour tenir dans `maxW`.
    Indispensable pour les accroches longues (« RÉADHÉSION — ON COMPTE SUR TOI »)
    qui, selon le format, dépasseraient du cadre. */
function TFit(x, y, str, maxW, o){
  o = o || {};
  var c = {}; Object.keys(o).forEach(function(k){ c[k] = o[k]; });
  c.size = fitSizeLS(str, maxW, o.size || 32, o.w === undefined ? 700 : o.w,
                     o.f === 'ag' ? FAM_AG : FAM_GS, o.ls || 0, o.size || 32);
  if (o.ls) c.ls = o.ls * c.size / (o.size || 32);
  return T(x, y, str, c);
}
/**
 * Taille maximale tenant dans `maxW`, interlettrage COMPRIS.
 * L'oubli de l'interlettrage faisait déborder les accroches en majuscules
 * espacées (« RÉADHÉSION — ON COMPTE SUR TOI »), où il représente à lui seul
 * près d'un quart de la largeur.
 */
function fitSizeLS(str, maxW, maxSize, weight, fam, ls, lsRef){
  var glyphs = measure(str, 100, weight, fam);
  var espace = (ls || 0) * (100 / (lsRef || maxSize)) * Math.max(0, String(str).length - 1);
  var total = glyphs + espace;
  if (!total) return maxSize;
  return Math.min(maxSize, Math.floor(100 * maxW / total));
}
/** Idem, sur plusieurs lignes : une seule taille commune à toutes. */
function TLFit(x, y, lines, maxW, o){
  var size = o.size;
  var fam = o.f === 'ag' ? FAM_AG : FAM_GS;
  for (var i = 0; i < lines.length; i++) {
    size = Math.min(size, fitSizeLS(lines[i], maxW, size, o.w === undefined ? 700 : o.w, fam, o.ls || 0, o.size));
  }
  var c = {}; Object.keys(o).forEach(function(k){ c[k] = o[k]; });
  c.size = size; c.lh = (o.lh || o.size * 1.28) * size / o.size;
  return TL(x, y, lines, c);
}
/** Bloc de lignes empilées. */
function TL(x, y, lines, o){
  var out = '', lh = o.lh || (o.size * 1.28);
  for (var i = 0; i < lines.length; i++) out += T(x, y + i * lh, lines[i], o);
  return out;
}
/** Le sigle MJA tricolore. */
function wordmark(x, y, size, anchor){
  return '<text x="' + x + '" y="' + y + '" font-family="' + FAM_AG + '" font-size="' + size
    + '" font-weight="800"' + (anchor ? ' text-anchor="' + anchor + '"' : '') + '>'
    + '<tspan fill="' + C.blue + '">M</tspan><tspan fill="' + C.yellow + '">J</tspan><tspan fill="' + C.red + '">A</tspan>'
    + '</text>';
}
/** Filet tricolore MJA. */
function triBar(x, y, w, h){
  var t = w / 3;
  return '<rect x="' + x + '" y="' + y + '" width="' + (t + 1) + '" height="' + h + '" fill="' + C.blue + '"/>'
       + '<rect x="' + (x + t) + '" y="' + y + '" width="' + (t + 1) + '" height="' + h + '" fill="' + C.yellow + '"/>'
       + '<rect x="' + (x + 2 * t) + '" y="' + y + '" width="' + t + '" height="' + h + '" fill="' + C.red + '"/>';
}
/** Anneaux concentriques du logo, en filigrane. */
function rings(cx, cy, r, op){
  var sw = Math.max(2, r * 0.022);
  return '<g fill="none" stroke-width="' + sw + '" opacity="' + op + '">'
    + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" stroke="' + C.blue + '"/>'
    + '<circle cx="' + cx + '" cy="' + cy + '" r="' + (r * 0.73) + '" stroke="' + C.yellow + '"/>'
    + '<circle cx="' + cx + '" cy="' + cy + '" r="' + (r * 0.48) + '" stroke="' + C.red + '"/>'
    + '</g>';
}
function logoHref(embed){
  return embed && ASSETS.logo ? 'data:image/png;base64,' + ASSETS.logo : ASSET('images/logomjat.png');
}
/** Logo MJA PNG détouré : aucun fond ni plaque ajoutée par les gabarits. */
function logoImg(x, y, size, embed){
  var href = logoHref(embed);
  return '<image x="' + x + '" y="' + y + '" width="' + size + '" height="' + size
    + '" href="' + href + '" xlink:href="' + href + '" preserveAspectRatio="xMidYMid meet"/>';
}
/** Filigrane : le logo répété en très basse opacité — signature visuelle MJA
    reprise des visuels d'adhésion de la saison précédente. */
function watermark(W, H, uid, embed, op, tile){
  tile = tile || Math.min(W, H) * 0.26;
  var href = logoHref(embed);
  return '<defs><pattern id="wm' + uid + '" x="0" y="0" width="' + tile + '" height="' + tile
    + '" patternUnits="userSpaceOnUse">'
    + '<image x="' + (tile * 0.1) + '" y="' + (tile * 0.1) + '" width="' + (tile * 0.8) + '" height="' + (tile * 0.8)
    + '" href="' + href + '" xlink:href="' + href + '" preserveAspectRatio="xMidYMid meet"/>'
    + '</pattern></defs>'
    + '<rect width="' + W + '" height="' + H + '" fill="url(#wm' + uid + ')" opacity="' + (op || 0.07) + '"/>';
}
/** Soulignement jaune sous un mot clé (code graphique repris de « DEVENEZ BÉNÉVOLE ! »). */
function underline(x, y, text, size, fill){
  var w = measure(text, size, 800, FAM_AG);
  return '<rect x="' + x + '" y="' + (y + size * 0.14) + '" width="' + w + '" height="' + (size * 0.15)
    + '" rx="' + (size * 0.05) + '" fill="' + (fill || C.yellow) + '"/>';
}
/** Flèche courbe dessinée, pointant vers le QR (comme « INSCRIS-TOI ! ➜ »). */
function curvedArrow(x1, y1, x2, y2, sw, fill){
  /* Point de contrôle décalé à l'opposé de la cible : la courbe part vers la
     droite, redescend, puis revient pointer le QR — sans croiser le texte. */
  var cx = x1 + (x1 - x2) * 0.45, cy = y1 + (y2 - y1) * 0.62;
  var ang = Math.atan2(y2 - cy, x2 - cx), hl = sw * 3.4;
  var p1 = [x2 - hl * Math.cos(ang - 0.45), y2 - hl * Math.sin(ang - 0.45)];
  var p2 = [x2 - hl * Math.cos(ang + 0.45), y2 - hl * Math.sin(ang + 0.45)];
  return '<path d="M' + x1 + ' ' + y1 + ' Q' + cx + ' ' + cy + ' ' + x2 + ' ' + y2 + '" fill="none" stroke="'
    + fill + '" stroke-width="' + sw + '" stroke-linecap="round"/>'
    + '<path d="M' + x2 + ' ' + y2 + ' L' + p1[0] + ' ' + p1[1] + ' L' + p2[0] + ' ' + p2[1] + ' Z" fill="' + fill + '"/>';
}
/** Pastille dont la largeur s'adapte au texte mesuré. */
function pill(x, y, text, size, bg, fg, padX, h){
  padX = padX || size * 0.85; h = h || size * 2.3;
  var w = measure(text, size, 700, FAM_GS) + padX * 2;
  return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="' + (h / 2) + '" fill="' + bg + '"/>'
       + T(x + w / 2, y + h / 2 + size * 0.36, text, { size: size, fill: fg, anchor: 'middle' });
}
function pillWidth(text, size, padX){ return measure(text, size, 700, FAM_GS) + (padX || size * 0.85) * 2; }

/** Bouton d'appel à l'action. */
function cta(x, y, w, h, label, bg, fg, size){
  size = size || h * 0.36;
  return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="' + (h * 0.28) + '" fill="' + bg + '"/>'
    + T(x + w / 2, y + h / 2 + size * 0.35, label, { size: size, fill: fg, anchor: 'middle', f: 'ag', w: 800, ls: size * 0.03 });
}
/**
 * ─── Appels à l'action ─────────────────────────────────────────────────
 * Un rectangle arrondi avec un libellé centré, c'est un bouton d'interface :
 * sur un post ou un flyer, le lecteur croit pouvoir cliquer. On utilise donc
 * des codes d'imprimé, sans coin arrondi ni aspect « cliquable » :
 *   · bandeau  : aplat pleine largeur, bord à bord, comme une manchette ;
 *   · typo     : le libellé en très grand, avec un filet épais, aucun cadre ;
 *   · manuscrit: le libellé penché et un filet graphique.
 */
function arrowGlyph(x, y, len, sw, fill){
  var h = sw * 2.1;
  return '<path d="M' + x + ' ' + y + ' L' + (x + len) + ' ' + y + '" stroke="' + fill
    + '" stroke-width="' + sw + '" stroke-linecap="butt"/>'
    + '<path d="M' + (x + len - h) + ' ' + (y - h) + ' L' + (x + len) + ' ' + y
    + ' L' + (x + len - h) + ' ' + (y + h) + '" fill="none" stroke="' + fill
    + '" stroke-width="' + sw + '" stroke-linecap="round" stroke-linejoin="round"/>';
}

/** Bandeau pleine largeur. Renvoie {svg, h} — hauteur totale occupée. */
function calloutBand(W, y, label, url, bg, fg, u, urlFill){
  var h = u * 0.115, size = u * 0.042;
  var s = '<rect x="0" y="' + y + '" width="' + W + '" height="' + h + '" fill="' + bg + '"/>';
  var txtW = measure(label, size, 800, FAM_AG) + size * 0.06 * (label.length - 1);
  var x0 = (W - txtW) / 2;
  s += T(x0, y + h / 2 + size * 0.34, label, { size: size, f: 'ag', w: 800, fill: fg, ls: size * 0.06 });
  if (url) s += T(W / 2, y + h + u * 0.048, url, { size: u * 0.028, fill: urlFill || C.dark, anchor: 'middle', w: 700 });
  return { svg: s, h: h + (url ? u * 0.068 : 0) };
}

/** Libellé XXL + filet, sans cadre. Renvoie {svg, h}. */
function calloutType(x, y, maxW, label, url, accent, ink, u){
  var size = fitSizeLS(label, maxW * 0.88, u * 0.062, 800, FAM_AG, 0, u * 0.062);
  var txtW = measure(label, size, 800, FAM_AG);
  var s = T(x, y + size * 0.82, label, { size: size, f: 'ag', w: 800, fill: ink });
  s += '<rect x="' + x + '" y="' + (y + size * 1.12) + '" width="' + txtW
    + '" height="' + (u * 0.011) + '" fill="' + accent + '"/>';
  var h = size * 1.12 + u * 0.011;
  if (url) {
    s += T(x, y + h + u * 0.052, url, { size: u * 0.030, fill: ink, w: 700 });
    h += u * 0.070;
  }
  return { svg: s, h: h, w: txtW };
}

/** Une seule ligne d'informations, séparée par des points médians :
    remplace les pastilles arrondies, qui faisaient « étiquettes cliquables »
    et débordaient en story. */
function infoLine(x, y, items, maxW, size, fill, anchor){
  var str = items.filter(Boolean).join('   ·   ').toUpperCase();
  return TFit(x, y, str, maxW, { size: size, fill: fill, anchor: anchor, w: 700, ls: size * 0.10 });
}

/** Carte blanche + QR code, avec libellé. */
function qrCard(x, y, size, label, labelFill){
  if (!OPT.qr || !ASSETS.qr) return '';
  var pad = size * 0.11, box = size + pad * 2;
  var s = '<rect x="' + x + '" y="' + y + '" width="' + box + '" height="' + box + '" rx="' + (box * 0.13) + '" fill="#FFFFFF"/>'
    + '<image x="' + (x + pad) + '" y="' + (y + pad) + '" width="' + size + '" height="' + size
    + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
  if (label) s += T(x + box / 2, y + box + size * 0.2, label, { size: size * 0.13, fill: labelFill || C.navy, anchor: 'middle', ls: 1 });
  return s;
}
/** Puce « check » ronde. */
function checkRow(x, y, text, size, dotFill, textFill, dotIconFill){
  var r = size * 0.62;
  return '<circle cx="' + (x + r) + '" cy="' + (y - size * 0.32) + '" r="' + r + '" fill="' + dotFill + '"/>'
    + '<path d="M' + (x + r * 0.55) + ' ' + (y - size * 0.32) + ' l' + (r * 0.32) + ' ' + (r * 0.34) + ' l' + (r * 0.6) + ' -' + (r * 0.72) + '" '
    + 'stroke="' + (dotIconFill || '#FFFFFF') + '" stroke-width="' + (r * 0.22) + '" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
    + T(x + r * 2 + size * 0.5, y, text, { size: size, w: 500, fill: textFill });
}

/* =====================================================================
   4. Palettes de style
   ===================================================================== */
function pal(style){
  if (style === 'navy') return {
    fg:'#FFFFFF', fg2:'#C4D4F3', kicker:C.yellow, accent:C.yellow, alt:C.yellow,
    pillBg:'rgba(255,255,255,.14)', pillFg:'#FFFFFF', ctaBg:C.yellow, ctaFg:C.navy,
    rule:'rgba(255,255,255,.20)', foot:'#9FB3DF', card:'rgba(255,255,255,.09)', dot:C.blue, qrLabel:'#FFFFFF'
  };
  if (style === 'photo') return {
    fg:'#FFFFFF', fg2:'#DEE6F7', kicker:C.yellow, accent:C.yellow, alt:C.yellow,
    pillBg:'rgba(255,255,255,.20)', pillFg:'#FFFFFF', ctaBg:C.yellow, ctaFg:C.navy,
    rule:'rgba(255,255,255,.28)', foot:'#DDE6F7', card:'rgba(11,30,69,.55)', dot:C.blue, qrLabel:'#FFFFFF'
  };
  /* Pastel : dégradé arc-en-ciel très clair, texte navy — reprend le fond
     du visuel « DEVENEZ BÉNÉVOLE ! » de la saison précédente. */
  if (style === 'pastel') return {
    fg:C.navy, fg2:'#5A6B85', kicker:C.red, accent:C.dark, alt:C.red,
    pillBg:'rgba(255,255,255,.72)', pillFg:C.dark, ctaBg:C.navy, ctaFg:'#FFFFFF',
    rule:'rgba(26,61,138,.16)', foot:'#7A879B', card:'rgba(255,255,255,.66)', dot:C.blue, qrLabel:C.navy
  };
  /* blanc & typo : fond blanc */
  return {
    fg:C.navy, fg2:'#6C7A91', kicker:C.blue, accent:C.blue, alt:C.blue,
    pillBg:C.light, pillFg:C.dark, ctaBg:C.navy, ctaFg:'#FFFFFF',
    rule:'#E4EAF4', foot:'#98A5B8', card:'#F5F8FD', dot:C.blue, qrLabel:C.navy
  };
}

/* Fond selon le style. */
function bgMarkup(style, W, H, uid, embed){
  if (style === 'navy') {
    return '<rect width="' + W + '" height="' + H + '" fill="url(#gNavy' + uid + ')"/>'
      + rings(W * 0.93, H * 0.06, Math.min(W, H) * 0.30, 0.20)
      + rings(W * 0.06, H * 0.97, Math.min(W, H) * 0.22, 0.13);
  }
  if (style === 'photo') {
    var img, src = OPT.photo || photoAt(PIDX);
    if (src) {
      /* photoTile applique le cadrage particulier de certaines photos ;
         une photo déposée par l'utilisateur est dessinée telle quelle. */
      img = OPT.photo
        ? '<image x="0" y="0" width="' + W + '" height="' + H + '" href="' + src + '" xlink:href="' + src
          + '" preserveAspectRatio="xMidYMid slice"/>'
        : photoTile(0, 0, W, H, 0, PHOTOS_LARGE);
    } else {
      /* Aucune photo chargée : fond neutre + petit repère en haut à droite,
         volontairement hors des zones de texte. */
      var U = Math.min(W, H), bs = U * 0.030;
      var lab = 'Ajoute une photo (panneau Options)';
      var bwd = measure(lab, bs, 600, FAM_GS) + bs * 1.6;
      img = '<rect width="' + W + '" height="' + H + '" fill="#8FA6C9"/>'
        + '<rect x="' + (W - bwd - U * 0.05) + '" y="' + (U * 0.05) + '" width="' + bwd + '" height="' + (bs * 2.2)
        + '" rx="' + (bs * 1.1) + '" fill="#0B1E45" opacity=".45"/>'
        + T(W - bwd / 2 - U * 0.05, U * 0.05 + bs * 1.5, lab,
            { size: bs, fill: '#FFFFFF', anchor: 'middle', w: 600, op: .95 });
    }
    return img + '<rect width="' + W + '" height="' + H + '" fill="url(#gShade' + uid + ')"/>';
  }
  if (style === 'pastel') {
    return '<defs><linearGradient id="gPast' + uid + '" x1="0" y1="0" x2="1" y2="1">'
      + '<stop offset="0" stop-color="#FFE3E8"/><stop offset=".28" stop-color="#FFF2DC"/>'
      + '<stop offset=".55" stop-color="#DCF0FF"/><stop offset=".8" stop-color="#D8F3E4"/>'
      + '<stop offset="1" stop-color="#FBF4D2"/></linearGradient></defs>'
      + '<rect width="' + W + '" height="' + H + '" fill="url(#gPast' + uid + ')"/>'
      + watermark(W, H, uid, embed, 0.06)
      + rings(W * 0.93, H * 0.07, Math.min(W, H) * 0.26, 0.22);
  }
  /* blanc / typo */
  return '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>'
    + '<circle cx="' + (W * 1.02) + '" cy="' + (-H * 0.04) + '" r="' + (Math.min(W, H) * 0.42) + '" fill="' + C.light + '"/>'
    + watermark(W, H, uid, embed, 0.05)
    + rings(W * 0.93, H * 0.07, Math.min(W, H) * 0.26, 0.30);
}

/* Enveloppe SVG : dimensions, dégradés, polices embarquées à l'export. */
function svgWrap(W, H, uid, inner, embed){
  var fonts = embed && ASSETS.fonts ? '<style type="text/css"><![CDATA[' + ASSETS.fonts + ']]></style>' : '';
  return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
    + 'width="' + W + '" height="' + H + '" viewBox="0 0 ' + W + ' ' + H + '">'
    + '<defs>' + fonts
    + '<linearGradient id="gNavy' + uid + '" x1="0" y1="0" x2="1" y2="1">'
    + '<stop offset="0" stop-color="' + C.navy + '"/><stop offset=".45" stop-color="' + C.dark + '"/><stop offset="1" stop-color="' + C.mid + '"/></linearGradient>'
    + '<linearGradient id="gShade' + uid + '" x1="0" y1="0" x2="0" y2="1">'
    + '<stop offset="0" stop-color="' + C.ink + '" stop-opacity=".38"/>'
    + '<stop offset=".42" stop-color="' + C.ink + '" stop-opacity=".58"/>'
    + '<stop offset="1" stop-color="' + C.ink + '" stop-opacity=".93"/></linearGradient>'
    + '</defs>' + inner + '</svg>';
}

/* =====================================================================
   5. Contenus (variantes de message)
   ===================================================================== */
function price(txt, fallback){ return OPT.price ? txt : fallback; }

/* Actions réelles de l'association, reprises telles quelles dans le gabarit
   « moderne ». On ne liste que des projets qui existent. */
var ACTIONS_DEFAUT = [
  ['fusee',     "Des projets ambitieux",   "Des projets à fort impact local"],
  ['personnes', "Une communauté soudée",   "Rencontre des jeunes engagés et ambitieux"],
  ['coeur',     "S'amuser entre jeunes",   "Des sorties et activités chaque mois"]
];
/* Pictogrammes attribués par position : l'utilisateur saisit du texte, pas
   des icônes. Au-delà de quatre lignes on boucle sur la même série. */
var PICTOS_ACTIONS = ['diplome', 'coeur', 'personnes', 'fusee'];
var ACTIONS = ACTIONS_DEFAUT.slice();

/** Transforme la saisie « Titre | Description » en table d'actions. */
function lireActions(texte){
  var lignes = String(texte).split(String.fromCharCode(10)).map(function(l){ return l.trim(); })
                 .filter(function(l){ return l.length; }).slice(0, 4);
  if (!lignes.length) return ACTIONS_DEFAUT.slice();

  return lignes.map(function(ligne, i){
    var bout = ligne.split('|');
    return [PICTOS_ACTIONS[i % PICTOS_ACTIONS.length],
            (bout[0] || '').trim(),
            (bout.slice(1).join('|') || '').trim()];
  });
}

/** Texte pré-rempli du champ de saisie. */
function actionsEnTexte(liste){
  return liste.map(function(a){ return a[1] + ' | ' + a[2]; }).join(String.fromCharCode(10));
}

/* Badges de valeurs du pied de page des maquettes. */
var VALEURS_BADGES = [
  ['coeur',     'ENGAGEMENT'],
  ['personnes', 'SOLIDARITÉ'],
  ['cible',     'AMBITION'],
  ['trophee',   'IMPACT']
];

/* Les trois bénéfices, avec leur pictogramme et leur couleur. */
var BENEFICES = [
  ['personnes', C.blue,   'Construis-toi',      'en tant que personne'],
  ['ampoule',   C.yellow, 'Mène des actions',   "à fort impact social"],
  ['trophee',   C.red,    'Monte en compétences', 'avec nous']
];

function V(variant){
  var season = OPT.season || '';
  var D = {
    annonce: {
      slogan: "Relève tous les défis !",
      kicker: "CAMPAGNE D'ADHÉSION",
      encart: ['Prêt à', "T'ENGAGER ?"],
      garanties: ["Ouvert de 16 à 35 ans",
                  "Adhésion en ligne en cinq minutes",
                  "Une équipe qui t'accompagne"],
      t: ["MJ'ADHÉSION"],
      season: season,
      big: ["MJ'", "ADHÉ", "SION"],
      sub: ["Madin' Jeunes Ambition", "relève tous les défis !"],
      subShort: ["Madin' Jeunes Ambition", "relève tous les défis !"],
      pills: ["16 – 35 ans", "Un impact social", "Une équipe qui compte sur toi"],
      cta: "J'ADHÈRE EN LIGNE", ctaShort: "J'ADHÈRE",
      checks: ["Construis-toi en tant que personne",
               "Mène des actions à fort impact social",
               "Monte en compétences avec nous"]
    },
    readhesion: {
      slogan: "On compte sur toi !",
      kicker: "RÉADHÉSION — ON COMPTE SUR TOI",
      encart: ['On compte', 'SUR TOI'],
      garanties: ["Ton dossier est déjà pré-rempli",
                  "Tu vérifies, tu corriges, tu valides",
                  "Carte bancaire, virement ou espèces"],
      t: ["JE RENOUVELLE", "MON ADHÉSION"],
      season: season,
      big: ["RÉ", "ADHÉ", "SION"],
      sub: ["Réadhère et continue", "l'aventure avec nous."],
      subShort: ["Réadhère", "et continue."],
      pills: [price("Réadhésion 20 €", "Réadhésion"), "Possible en ligne"],
      cta: "JE RÉADHÈRE", ctaShort: "JE RÉADHÈRE",
      checks: ["Tout est déjà pré-rempli, rien à ressaisir",
               price("20 € pour toute la saison " + season.replace('SAISON ', ''), "Une cotisation annuelle symbolique"),
               "Carte bancaire, virement ou espèces"]
    },
    pourquoi: {
      kicker: "POURQUOI ADHÉRER ?",
      t: ["4 BONNES", "RAISONS"],
      season: season,
      big: ["POUR", "QUOI", "ADHÉRER"],
      items: [
        ["TE CONSTRUIRE", "Grandis, prends confiance, révèle ce que tu sais faire."],
        ["T'ENGAGER", "Mène des actions à fort impact social."],
        ["MONTER EN COMPÉTENCES", "Projets, événements, communication : tu apprends en faisant."],
        ["COMPTER", "Une voix à l'assemblée générale et dans nos décisions."]
      ],
      cta: "J'ADHÈRE EN LIGNE"
    },
    comment: {
      kicker: "COMMENT ADHÉRER ?",
      t: ["3 ÉTAPES", "ET C'EST FAIT"],
      season: season,
      big: ["3", "ÉTAPES"],
      steps: [
        ["1", "Remplis le formulaire", "En ligne, rubrique Adhésion — cinq minutes."],
        ["2", "Règle ta cotisation", price("20 € par carte bancaire, virement ou espèces.", "Par carte bancaire, virement ou espèces.")],
        ["3", "Bienvenue chez MJA", "Tu reçois ton mail de bienvenue et tu rejoins l'équipe."]
      ],
      cta: "JE COMMENCE"
    },
    chiffres: {
      kicker: "MADIN' JEUNES AMBITION",
      t: ["MJA EN", "CHIFFRES"],
      season: season,
      big: ["MJA", "EN", "CHIFFRES"],
      stats: [["2011", "année de création"], ["5", "pôles d'action"], ["15", "ans d'engagement"], ["20 000+", "petits-déj"]],
      cta: "JE REJOINS L'ÉQUIPE"
    },
    /* ── Variantes reprenant les slogans MJA de la saison précédente ── */
    defi: {
      kicker: "CAMPAGNE D'ADHÉSION",
      t: ["RELÈVE", "LE DÉFI !"],
      season: season,
      big: ["RELÈVE", "LE", "DÉFI !"],
      slogan: "Relève le défi !",
      encart: ['Prêt à', 'RELEVER LE DÉFI ?'],
      garanties: ["Ouvert de 16 à 35 ans",
                  "Tu choisis les actions que tu portes",
                  "Une équipe qui t'accompagne"],
      signature: ["Madin' Jeunes Ambition", "Relève tous les défis !"],
      para: ["Envie de t'investir, de grandir et d'apprendre ?",
             "Pour les jeunes, par des jeunes ?",
             "Viens découvrir l'expérience associative de",
             "Madin' Jeunes Ambition !"],
      sub: ["Envie de t'investir, de grandir et d'apprendre ?", "Pour les jeunes, par des jeunes ?"],
      subShort: ["Pour les jeunes,", "par des jeunes."],
      pills: ["16 – 35 ans", "Pour les jeunes, par des jeunes", "Des projets concrets"],
      cta: "INSCRIS-TOI !", ctaShort: "INSCRIS-TOI !",
      checks: ["Construis-toi en tant que personne",
               "Mène des actions à fort impact social",
               "Monte en compétences avec nous"]
    },
    benevole: {
      kicker: "REJOINS LA TEAM MJA",
      t: ["DEVENEZ", "BÉNÉVOLE !"],
      season: season,
      big: ["DEVENEZ", "BÉNÉ", "VOLE !"],
      slogan: "Venez comme vous êtes !",
      encart: ['Venez comme', 'VOUS ÊTES'],
      garanties: ["Aucune compétence exigée",
                  "Tu choisis les actions que tu portes",
                  "Une équipe qui t'accompagne"],
      signature: ["Madin' Jeunes Ambition", "Pour les jeunes, par des jeunes"],
      actions: ["Prévention",
                "Solidarité",
                "Engagement citoyen",
                "Loisirs et développement personnel"],
      sub: ["Nos actions : prévention, solidarité,", "engagement et développement personnel."],
      subShort: ["Venez comme", "vous êtes !"],
      pills: ["16 – 35 ans", "Toutes les envies", "Venez comme vous êtes"],
      cta: "JE DEVIENS BÉNÉVOLE", ctaShort: "JE M'ENGAGE",
      checks: ["Prévention, solidarité, engagement, loisirs",
               "Pour les jeunes, par des jeunes",
               "Venez comme vous êtes !"]
    },
    teaser: {
      kicker: "ÇA COMMENCE",
      t: ["LA SAISON", "D'ADHÉSION"],
      season: season,
      big: ["ÇA", "COM", "MENCE"],
      slogan: "Relève tous les défis !",
      signature: ["Madin' Jeunes Ambition", "Relève tous les défis !"],
      para: ["Quelque chose se prépare.", "La campagne d'adhésion est ouverte."],
      sub: ["Quelque chose se prépare.", "La campagne d'adhésion est ouverte."],
      subShort: ["Ça commence.", "Rejoins-nous."],
      pills: ["Nouvelle saison", "Nouveaux projets", "Nouveaux visages"],
      cta: "RESTE CONNECTÉ", ctaShort: "ÇA ARRIVE",
      checks: ["Une nouvelle saison démarre",
               "De nouveaux projets à mener",
               "Une place t'attend"]
    },
    valeurs: {
      kicker: "CE QUE TU VIENS CHERCHER",
      encart: ['Viens', 'GRANDIR'],
      garanties: ["Ouvert de 16 à 35 ans",
                  "Des projets à porter dès l'arrivée",
                  "Une équipe qui t'accompagne"],
      t: ["VIENS", "GRANDIR"],
      season: season,
      big: ["VIENS", "GRANDIR"],
      slogan: "Relève tous les défis !",
      signature: ["Madin' Jeunes Ambition", "Relève tous les défis !"],
      para: ["Construis-toi en tant que personne.",
             "Engage-toi pour des actions à fort impact social.",
             "Monte en compétences avec nous.",
             "Pour les jeunes, par des jeunes."],
      sub: ["Une équipe, des projets,", "et une place qui t'attend."],
      subShort: ["Une place", "t'attend."],
      pills: ["Se construire", "S'engager", "Progresser"],
      cta: "JE REJOINS MJA", ctaShort: "JE REJOINS",
      checks: ["Construis-toi en tant que personne",
               "Mène des actions à fort impact social",
               "Monte en compétences avec nous"],
      items: [
        ["SE CONSTRUIRE", "Gagne en confiance et en autonomie."],
        ["S'ENGAGER", "Des actions concrètes, utiles, visibles."],
        ["PROGRESSER", "Gestion de projet, prise de parole, communication."],
        ["APPARTENIR", "Une équipe qui compte sur toi, et sur qui compter."]
      ]
    },
    temoignage: {
      kicker: "PAROLE DE MEMBRE",
      t: ["ILS L'ONT", "FAIT"],
      season: season,
      big: ["REJOINS", "NOUS"],
      quote: ["« Chez MJA, j'ai trouvé", "une équipe qui transforme", "les idées en actions. »"],
      author: "— Un membre de Madin' Jeunes Ambition",
      cta: "J'ADHÈRE À MON TOUR"
    }
  };
  return D[variant];
}

/* =====================================================================
   6. Blocs d'en-tête / de pied communs
   ===================================================================== */
function headerBlock(M, W, style, p, embed, k){
  var logo = k.logo, name = k.name, tag = k.tag, ruleY = k.rule;
  var s = logoImg(M, k.y, logo, embed, style === 'navy' || style === 'photo');
  s += T(M + logo + k.gap, k.y + logo * 0.44, ORG, { size: name, fill: p.fg, ls: name * 0.05 });
  s += TFit(M + logo + k.gap, k.y + logo * 0.44 + tag * 1.65, TAG, W - (M + logo + k.gap) - M, { size: tag, w: 500, fill: p.fg2 });
  if (ruleY) s += '<rect x="' + M + '" y="' + ruleY + '" width="' + (W - 2 * M) + '" height="2" fill="' + p.rule + '"/>';
  return s;
}
function footerBlock(M, W, y, p, size){
  /* Pas de SITE ici : le bandeau d'appel à l'action le porte déjà, et le
     répéter à trois centimètres n'apporte rien. */
  return instaTag(M, y, size, p.foot)
    + T(M, y + size * 1.18, 'Tél. ' + TEL, { size: size * 0.78, fill: p.foot, w: 700 })
    + T(W - M, y, CRI.join(' '), { size: size, fill: p.accent, anchor: 'end', w: 700 });
}

/* =====================================================================
   7. Layout — POST 1080 × 1080
   ===================================================================== */
function renderPost(style, variant, uid, embed){
  var W = 1080, H = 1080, M = 76, p = pal(style), v = V(variant), u = W, avail = W - 2 * M;
  if (style === 'typo') return svgWrap(W, H, uid, typoBody(W, H, M, variant, uid, embed), embed);

  var s = bgMarkup(style, W, H, uid, embed);
  s += triBar(0, 0, W, 13);
  s += headerBlock(M, W, style, p, embed, { y: 44, logo: 148, gap: 26, name: 30, tag: 22, rule: 224 });

  var y = 292;
  s += TFit(M, y, v.kicker, avail, { size: 25, fill: p.kicker, ls: 4.6 });

  /* Appel à l'action : bandeau bord à bord sur fond sombre ou photo,
     libellé XXL souligné sinon. Jamais de bouton arrondi. */
  var bandeau = (style === 'navy' || style === 'photo');
  var lien = OPT.url.replace(/^https?:\/\//, '');
  function faireCallout(yy){
    return bandeau ? calloutBand(W, yy, v.ctaShort || v.cta, lien, p.ctaBg, p.ctaFg, u, p.fg)
                   : calloutType(M, yy, avail, v.ctaShort || v.cta, lien, p.accent, p.fg, u);
  }
  var co = faireCallout(0);
  var coTop = H - 78 - co.h;

  if (variant === 'annonce' || variant === 'readhesion' || variant === 'defi' || variant === 'benevole') {
    var lines = v.t, tsMax = lines.length > 1 ? 90 : 104, ts = tsMax;
    for (var i = 0; i < lines.length; i++) ts = Math.min(ts, fitSize(lines[i], avail, ts, 800, FAM_AG));
    var cur = y + 40 + ts * 0.82;
    if (variant === 'benevole') s += underline(M, cur + (lines.length - 1) * ts * 1.02, lines[lines.length - 1], ts);
    s += TL(M, cur, lines, { size: ts, f: 'ag', w: 800, fill: p.fg, lh: ts * 1.02 });
    cur += (lines.length - 1) * ts * 1.02;

    cur += 60;
    s += T(M, cur, v.season, { size: 34, fill: p.alt, f: 'ag', w: 800, ls: 4 });
    cur += 50;
    s += TLFit(M, cur, v.sub, avail, { size: 29, w: 500, fill: p.fg2, lh: 42 });
    cur += (v.sub.length - 1) * 42;

    cur += 44;
    s += infoLine(M, cur, v.pills, avail, 23, p.pillFg === '#FFFFFF' ? p.fg : p.pillFg);

    /* Bandeau photo : prend la place disponible entre l'info et l'action. */
    var bandBottom = coTop - 34, bandTop = cur + 34;
    var bandH = Math.min(bandBottom - bandTop, H * 0.20);
    if (bandH >= H * 0.095) {
      var bandY = bandBottom - bandH;
      s += photoStrip(0, bandY, W, bandH, 3, style === 'photo' ? 1 : 0);
      s += triBar(0, bandY, W, 6);
      s += triBar(0, bandY + bandH - 6, W, 6);
    }
  }

  if (variant === 'pourquoi') {
    var ts2 = fitSize(v.t[0], avail, 82, 800, FAM_AG);
    s += TL(M, y + 96, v.t, { size: ts2, f: 'ag', w: 800, fill: p.fg, lh: ts2 * 1.0 });
    var gy = y + 96 + ts2 + 54, gw = (avail - 24) / 2;
    var gh = Math.min(160, ((coTop - 34 - gy) - 18) / 2);
    for (var j = 0; j < 4; j++) {
      var cx = M + (j % 2) * (gw + 24), cy = gy + Math.floor(j / 2) * (gh + 18);
      s += '<rect x="' + cx + '" y="' + cy + '" width="' + gw + '" height="' + gh + '" fill="' + p.card + '"/>';
      s += '<rect x="' + cx + '" y="' + cy + '" width="' + gw + '" height="6" fill="' + [C.blue, C.yellow, C.red, C.dark][j] + '"/>';
      s += TFit(cx + 24, cy + 54, v.items[j][0], gw - 48, { size: 26, fill: p.fg, ls: 2 });
      s += wrapFit(v.items[j][1], cx + 24, cy + 88, gw - 48, 2, 20, 27, { w: 500, fill: p.fg2 });
    }
  }

  if (variant === 'comment') {
    var ts3 = fitSize(v.t[1], avail, 76, 800, FAM_AG);
    s += TL(M, y + 92, v.t, { size: ts3, f: 'ag', w: 800, fill: p.fg, lh: ts3 * 1.0 });
    var sy = y + 92 + ts3 + 58;
    var pas = Math.min(132, (coTop - 34 - sy) / 3);
    for (var k = 0; k < 3; k++) {
      var by = sy + k * pas;
      s += '<rect x="' + M + '" y="' + (by - 34) + '" width="66" height="66" fill="' + [C.blue, C.yellow, C.red][k] + '"/>';
      s += T(M + 33, by + 12, v.steps[k][0], { size: 40, fill: k === 1 ? C.navy : '#FFFFFF', anchor: 'middle', f: 'ag', w: 800 });
      s += TFit(M + 92, by, v.steps[k][1], avail - 92, { size: 30, fill: p.fg });
      s += wrapFit(v.steps[k][2], M + 92, by + 36, avail - 92, 2, 22, 29, { w: 500, fill: p.fg2 });
    }
  }

  if (variant === 'chiffres') {
    var ts4 = fitSize(v.t[1], avail, 84, 800, FAM_AG);
    s += TL(M, y + 96, v.t, { size: ts4, f: 'ag', w: 800, fill: p.fg, lh: ts4 * 1.0 });
    var qy = y + 96 + ts4 + 58, qw = (avail - 24) / 2;
    var qh = Math.min(150, ((coTop - 34 - qy) - 18) / 2);
    for (var m = 0; m < 4; m++) {
      var qx = M + (m % 2) * (qw + 24), qyy = qy + Math.floor(m / 2) * (qh + 18);
      s += '<rect x="' + qx + '" y="' + qyy + '" width="' + qw + '" height="' + qh + '" fill="' + p.card + '"/>';
      s += T(qx + 26, qyy + qh * 0.60, v.stats[m][0], { size: Math.min(64, qh * 0.46), fill: [C.blue, C.yellow, C.red, C.dark][m], f: 'ag', w: 800 });
      s += TFit(qx + 26, qyy + qh * 0.86, v.stats[m][1], qw - 52, { size: 22, w: 500, fill: p.fg2 });
    }
  }

  if (variant === 'temoignage') {
    /* Le guillemet ouvrant est un ornement : il se place SOUS l'accroche,
       jamais par-dessus (chevauchement relevé par l'audit). */
    var qs = 52;
    s += T(M, y + 128, '“', { size: 140, fill: p.accent, f: 'ag', w: 800, op: .55 });
    s += TL(M, y + 196, v.quote, { size: qs, f: 'ag', w: 800, fill: p.fg, lh: qs * 1.18 });
    s += T(M, y + 196 + v.quote.length * qs * 1.18 + 8, v.author, { size: 25, w: 500, fill: p.fg2 });
  }

  s += faireCallout(coTop).svg;
  if (OPT.qr && ASSETS.qr && !bandeau) s += qrCard(W - M - 132, coTop - 6, 116, null, p.qrLabel);

  s += footerBlock(M, W, H - 40, p, 22);
  s += triBar(0, H - 11, W, 11);
  return svgWrap(W, H, uid, s, embed);
}

/**
 * Texte replié sur au plus `maxLines` lignes : la taille est réduite jusqu'à ce
 * que ça tienne. Sans cette contrainte, une description trop longue débordait
 * de sa carte et se faisait recouvrir par l'aplat suivant.
 */
function wrapFit(text, x, y, maxW, maxLines, size, lh, o){
  o = o || {};
  var t = size;
  for (var essai = 0; essai < 8; essai++) {
    if (decoupe(text, maxW, t, o.w || 500).length <= maxLines) break;
    t *= 0.92;
  }
  return wrapText(text, x, y, maxW, t, lh * (t / size), o);
}
/** Découpe un texte en lignes tenant dans `maxW`. */
function decoupe(text, maxW, size, weight){
  var mots = String(text).split(' '), lignes = [], cur = '';
  for (var i = 0; i < mots.length; i++) {
    var essai = cur ? cur + ' ' + mots[i] : mots[i];
    if (measure(essai, size, weight, FAM_GS) > maxW && cur) { lignes.push(cur); cur = mots[i]; }
    else cur = essai;
  }
  if (cur) lignes.push(cur);
  return lignes;
}

/* Retour à la ligne automatique sur une largeur donnée (mesure réelle). */
function wrapText(text, x, y, maxW, size, lh, o){
  o = o || {};
  var words = String(text).split(' '), lines = [], cur = '';
  for (var i = 0; i < words.length; i++) {
    var test = cur ? cur + ' ' + words[i] : words[i];
    if (measure(test, size, o.w || 500, FAM_GS) > maxW && cur) { lines.push(cur); cur = words[i]; }
    else cur = test;
  }
  if (cur) lines.push(cur);
  var out = '';
  for (var j = 0; j < lines.length; j++) out += T(x, y + j * lh, lines[j], { size: size, w: o.w || 500, fill: o.fill || C.gray, anchor: o.anchor });
  return out;
}

/* =====================================================================
   8. Style « Typo XXL » — mise en page dédiée (fond blanc)
   ===================================================================== */
function typoBody(W, H, M, variant, uid, embed){
  var v = V(variant), lines = v.big, avail = W - 2 * M;
  var s = '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>';
  s += watermark(W, H, uid, embed, 0.055);
  s += rings(W * 0.94, H * 0.055, Math.min(W, H) * 0.23, 0.26);
  s += triBar(0, 0, W, H * 0.014);

  /* En-tête : identité */
  var lg = H * 0.115;
  s += logoImg(M, H * 0.045, lg, embed);
  s += T(M + lg + W * 0.024, H * 0.045 + lg * 0.44, ORG, { size: H * 0.026, fill: C.navy, ls: H * 0.0013 });
  s += TFit(M + lg + W * 0.024, H * 0.045 + lg * 0.44 + H * 0.032, TAG, W - (M + lg + W * 0.024) - M, { size: H * 0.020, w: 500, fill: '#6C7A91' });

  /* Bloc typographique : la plus grande taille qui tienne en largeur ET en hauteur. */
  var maxLineH = (H * 0.40) / lines.length, size = maxLineH / 0.86;
  for (var i = 0; i < lines.length; i++) size = Math.min(size, fitSize(lines[i], avail, size, 800, FAM_AG));
  var lh = size * 0.86, top = H * 0.26;
  var colors = [C.dark, C.blue, C.red, C.navy];

  /* Soulignement jaune sous le dernier mot (code repris de « DEVENEZ BÉNÉVOLE ! »). */
  s += underline(M, top + (lines.length - 1) * lh, lines[lines.length - 1], size);
  for (var j = 0; j < lines.length; j++) {
    s += T(M, top + j * lh, lines[j], { size: size, f: 'ag', w: 800, fill: colors[j % colors.length], ls: -size * 0.02 });
  }

  var yb = top + (lines.length - 1) * lh + size * 0.30;
  s += '<rect x="' + M + '" y="' + yb + '" width="' + avail + '" height="3" fill="' + C.navy + '"/>';
  s += TFit(M, yb + H * 0.048, v.kicker, avail, { size: H * 0.024, fill: C.blue, ls: H * 0.0042 });
  s += TLFit(M, yb + H * 0.098, (v.sub || v.quote || [TAG]), avail, { size: H * 0.028, w: 500, fill: '#5C6B82', lh: H * 0.039 });

  /* Pied : saison + lien à gauche, QR à droite */
  var qs = H * 0.135, hasQr = OPT.qr && ASSETS.qr;
  var footW = hasQr ? (W - 2 * M - qs - W * 0.05) : avail;
  s += TFit(M, H - H * 0.128, v.season || OPT.season, footW, { size: H * 0.030, fill: C.navy, f: 'ag', w: 800, ls: 2 });
  s += TFit(M, H - H * 0.082, OPT.url.replace(/^https?:\/\//, ''), footW, { size: H * 0.026, fill: C.dark, w: 700 });
  s += instaTag(M, H - H * 0.034, H * 0.019, '#98A5B8');
  if (hasQr) {
    s += '<image x="' + (W - M - qs) + '" y="' + (H - H * 0.185) + '" width="' + qs + '" height="' + qs
      + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
    s += T(W - M - qs / 2, H - H * 0.026, 'SCANNE-MOI', { size: H * 0.019, fill: C.navy, anchor: 'middle', ls: 1.5 });
  } else {
    s += wordmark(W - M, H - H * 0.075, H * 0.10, 'end');
  }
  s += triBar(0, H - H * 0.014, W, H * 0.014);
  return s;
}

/* =====================================================================
   9. Layout — STORY 1080 × 1920
   ===================================================================== */
function renderStory(style, variant, uid, embed){
  var W = 1080, H = 1920, M = 88, p = pal(style), v = V(variant);
  if (style === 'typo') return svgWrap(W, H, uid, typoBody(W, H, M, variant, uid, embed), embed);

  var s = bgMarkup(style, W, H, uid, embed);
  s += triBar(0, 0, W, 15);

  /* Zone haute libre (nom de compte, stickers) : on démarre à 250 px. */
  s += logoImg(W / 2 - 116, 250, 232, embed, style === 'navy' || style === 'photo');
  s += T(W / 2, 546, ORG, { size: 32, fill: p.fg, anchor: 'middle', ls: 5 });
  s += TFit(W / 2, 590, TAG, W * 0.86, { size: 23, w: 500, fill: p.fg2, anchor: 'middle' });

  s += TFit(W / 2, 660, v.kicker, W - 2 * M, { size: 27, fill: p.kicker, anchor: 'middle', ls: 5 });

  var bandeauStory = (style === 'navy' || style === 'photo');
  var lienStory = OPT.url.replace(/^https?:\/\//, '');

  if (variant === 'annonce' || variant === 'readhesion' || variant === 'defi' || variant === 'benevole') {
    var ts = 999;
    for (var i = 0; i < v.t.length; i++) ts = Math.min(ts, fitSize(v.t[i], W - 2 * M, 108, 800, FAM_AG));
    if (variant === 'benevole') s += underline(W / 2 - measure(v.t[v.t.length - 1], ts, 800, FAM_AG) / 2, 790 + (v.t.length - 1) * ts * 1.05, v.t[v.t.length - 1], ts);
    s += TL(W / 2, 790, v.t, { size: ts, f: 'ag', w: 800, fill: p.fg, anchor: 'middle', lh: ts * 1.05 });
    var aft = 790 + (v.t.length - 1) * ts * 1.05;
    s += T(W / 2, aft + 80, v.season, { size: 38, fill: p.alt, anchor: 'middle', f: 'ag', w: 800, ls: 4 });
    s += TL(W / 2, aft + 168, v.sub, { size: 32, w: 500, fill: p.fg2, anchor: 'middle', lh: 46 });

    s += infoLine(W / 2, aft + 246, v.pills, W - 2 * M, 26, p.fg2, 'middle');

    /* Bandeau photo : occupe l'espace laissé libre au-dessus de l'action. */
    var bandTop = aft + 300, bandBottom = 1290;
    if (bandBottom - bandTop >= 150) {
      var bandH = Math.min(bandBottom - bandTop, 330);
      var bandY = bandBottom - bandH;
      s += photoStrip(0, bandY, W, bandH, 2, style === 'photo' ? 1 : 0);
      s += triBar(0, bandY, W, 7);
      s += triBar(0, bandY + bandH - 7, W, 7);
    }

    var coS = bandeauStory
      ? calloutBand(W, 1330, v.ctaShort, lienStory, p.ctaBg, p.ctaFg, W, p.fg)
      : calloutType(M, 1330, W - 2 * M, v.ctaShort, lienStory, p.accent, p.fg, W);
    s += coS.svg;
    if (OPT.qr && ASSETS.qr) s += qrCard(W / 2 - 88, 1552, 156, null, p.qrLabel);
    else s += T(W / 2, 1620, 'Lien en bio ↑', { size: 30, fill: p.accent, anchor: 'middle' });
  }

  if (variant === 'comment') {
    s += TL(W / 2, 780, v.t, { size: 74, f: 'ag', w: 800, fill: p.fg, anchor: 'middle', lh: 78 });
    for (var k = 0; k < 3; k++) {
      var by = 980 + k * 190;
      s += '<rect x="' + M + '" y="' + by + '" width="' + (W - 2 * M) + '" height="160" fill="' + p.card + '"/>';
      s += '<rect x="' + (M + 28) + '" y="' + (by + 44) + '" width="76" height="76" fill="' + [C.blue, C.yellow, C.red][k] + '"/>';
      s += T(M + 66, by + 96, v.steps[k][0], { size: 42, fill: k === 1 ? C.navy : '#FFFFFF', anchor: 'middle', f: 'ag', w: 800 });
      s += T(M + 130, by + 66, v.steps[k][1], { size: 30, fill: p.fg });
      s += wrapText(v.steps[k][2], M + 130, by + 106, W - 2 * M - 160, 22, 30, { w: 500, fill: p.fg2 });
    }
    s += (bandeauStory ? calloutBand(W, 1560, v.cta, null, p.ctaBg, p.ctaFg, W)
                       : calloutType(M, 1560, W - 2 * M, v.cta, null, p.accent, p.fg, W)).svg;
  }

  /* Une seule mention en pied : la saison figure déjà sous le titre, et la
     zone basse doit rester libre pour le lien de la story. */
  s += instaTag(W / 2, H - 62, 26, p.foot, { anchor:'middle' });
  s += triBar(0, H - 15, W, 15);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   10. Layout — PAPIER (ratio A) 1240 × 1754  → A5 / A4 / A3
   ===================================================================== */
/**
 * Bandeau de photos jointives, plein bord. La première tuile est une photo
 * « en action », les suivantes des photos de groupe. Sur un bandeau de trois,
 * la case centrale revient à la photo vedette : action à gauche, l'équipe et
 * le nom dans le sable au milieu, groupe à droite.
 */
function reserveTuile(i, n){
  if (i === 0) return PHOTOS_LARGE;
  if (n === 3 && i === 1 && PHOTOS_VEDETTE.length) return PHOTOS_VEDETTE;
  return PHOTOS_GROUPE;
}

function photoStrip(x, y, w, h, n, offset){
  var tw = w / n, s = '';
  for (var i = 0; i < n; i++) {
    s += photoTile(x + i * tw, y, tw + 0.6, h, (offset || 0) + i, reserveTuile(i, n));
  }
  return s;
}
/** Puce carrée tricolore + texte : plus « imprimé » qu'une pastille cochée. */
function bulletRow(x, y, text, size, color, textFill, maxW){
  var b = size * 0.52;
  return '<rect x="' + x + '" y="' + (y - size * 0.62) + '" width="' + b + '" height="' + b + '" fill="' + color + '"/>'
    + TFit(x + b + size * 0.55, y, text, maxW - b - size * 0.55, { size: size, w: 500, fill: textFill });
}

function renderPaper(style, variant, uid, embed){
  var W = 1240, H = 1754, M = 106, p = pal(style), v = V(variant);
  if (style === 'typo') return svgWrap(W, H, uid, typoBody(W, H, M, variant, uid, embed), embed);

  var u = W, avail = W - 2 * M;
  var s = bgMarkup(style, W, H, uid, embed);
  var bar = 18;
  s += triBar(0, 0, W, bar);
  s += headerBlock(M, W, style, p, embed, { y: 58, logo: 170, gap: 26, name: 30, tag: 23, rule: 262 });

  /* ── Bloc haut : accroche, titre, saison, sous-titre ───────────────── */
  var cur = 336;
  s += TFit(M, cur, v.kicker, avail, { size: 27, fill: p.kicker, ls: 5.2 });

  var lines = v.t, tsMax = lines.length > 1 ? 108 : 126, ts = tsMax;
  for (var i = 0; i < lines.length; i++) ts = Math.min(ts, fitSize(lines[i], avail, ts, 800, FAM_AG));
  cur += 40 + ts * 0.82;
  s += TL(M, cur, lines, { size: ts, f: 'ag', w: 800, fill: p.fg, lh: ts * 1.02 });
  cur += (lines.length - 1) * ts * 1.02;

  cur += 72;
  s += T(M, cur, v.season, { size: 44, fill: p.alt, f: 'ag', w: 800, ls: 4 });
  cur += 58;
  s += TLFit(M, cur, v.sub, avail, { size: 33, w: 500, fill: p.fg2, lh: 47 });
  cur += (v.sub.length - 1) * 47;

  /* ── Bloc bas, ancré : arguments, appel à l'action, QR, contacts ───── */
  var checks = (v.checks || []).slice(0, 3), pasCheck = 58;
  var footTop = H - 150;
  var qrTop   = footTop - 190;
  var infoX   = M;

  /* Appel à l'action : bandeau bord à bord sur fond sombre, sinon typographie. */
  var bandeau = (style === 'navy' || style === 'photo');
  /* Sans l'adresse : elle est donnée juste en dessous, à côté du QR code. */
  var co = bandeau
    ? calloutBand(W, 0, v.ctaShort || v.cta, null, p.ctaBg, p.ctaFg, u, p.fg)
    : calloutType(M, 0, avail, v.ctaShort || v.cta, null, p.accent, p.fg, u);
  var coTop = qrTop - 38 - co.h;
  var checksTop = coTop - 34 - (checks.length - 1) * pasCheck;

  /* ── Bandeau photo : occupe l'espace restant, s'il est suffisant ───── */
  var bandTop = cur + 38;
  var bandH = checksTop - 30 - bandTop;
  if (bandH >= H * 0.085) {
    bandH = Math.min(bandH, H * 0.28);
    var bandY = checksTop - 34 - bandH;
    s += photoStrip(0, bandY, W, bandH, 3, 0);
    s += triBar(0, bandY, W, 7);
    s += triBar(0, bandY + bandH - 7, W, 7);
  }

  for (var j = 0; j < checks.length; j++) {
    s += bulletRow(M, checksTop + j * pasCheck, checks[j], 29, [C.blue, C.yellow, C.red][j % 3], p.fg2, avail);
  }

  /* Le bandeau est repositionné en y une fois sa hauteur connue. */
  s += bandeau
    ? calloutBand(W, coTop, v.ctaShort || v.cta, null, p.ctaBg, p.ctaFg, u, p.fg).svg
    : calloutType(M, coTop, avail, v.ctaShort || v.cta, null, p.accent, p.fg, u).svg;

  if (OPT.qr && ASSETS.qr) {
    s += qrCard(M, qrTop, 150, null, p.qrLabel);
    infoX = M + 150 + 150 * 0.22 + 34;
  }
  s += T(infoX, qrTop + 46, '', { size: 22, fill: p.fg2, ls: 3 });
  s += T(infoX, qrTop + 86, SITE, { size: 30, fill: p.fg, w: 700 });
  s += instaTag(infoX, qrTop + 132, 21, p.fg2, { w:500 })
     + T(infoX, qrTop + 168, MAIL, { size: 21, w: 500, fill: p.fg2 });

  s += '<rect x="' + M + '" y="' + footTop + '" width="' + avail + '" height="2" fill="' + p.rule + '"/>';
  /* Pas d'adresse postale : on ne reçoit pas les adhésions par courrier. */
  s += T(W - M, footTop + 52, 'Tél. ' + TEL, { size: 21, w: 600, fill: p.foot, anchor: 'end' });
  s += triBar(0, H - bar, W, bar);
  return svgWrap(W, H, uid, s, embed);
}

/**
 * Gabarit « bulle » — flyer / affiche / kakémono au style plus contemporain.
 *
 * La photo occupe une forme organique asymétrique ancrée sur un bord : un
 * demi-disque prolongé en goutte, découpé par un `clipPath`. Le texte occupe
 * la diagonale opposée. Contrairement au bandeau photo horizontal des autres
 * gabarits, la composition n'est jamais symétrique, ce qui la rend lisible
 * même sans image forte.
 */
function renderBulle(style, variant, uid, embed, W, H){
  var sombre = (style === 'navy');
  var p = pal(sombre ? 'navy' : 'blanc'), v = V(variant);
  var encre  = sombre ? '#FFFFFF' : C.ink;
  var doux   = sombre ? '#C6D8F5' : '#5C6B82';
  var accent = sombre ? C.yellow : C.red;
  var M = W * 0.085, u = W;
  var s = sombre ? bgMarkup('navy', W, H, uid, embed)
                 : '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>';

  /* Demi-bulle : disque débordant du bord droit, prolongé en goutte.
     Le rayon dépend de la plus petite dimension, sinon la forme avale toute
     la page sur un format paysage et déborde sur les textes. */
  var R = Math.min(W * 0.56, H * 0.34);
  var cxB = W - R * 0.42, cyB = Math.max(R * 0.92, H * 0.24);
  s += '<defs><clipPath id="bul' + uid + '">'
     + '<path d="M ' + (cxB - R) + ' ' + cyB
     + ' a ' + R + ' ' + R + ' 0 1 1 ' + (2 * R) + ' 0'
     + ' L ' + (cxB + R) + ' ' + (cyB + R * 0.70)
     + ' q 0 ' + (R * 0.48) + ' ' + (-R * 0.52) + ' ' + (R * 0.48)
     + ' q ' + (-R * 0.72) + ' 0 ' + (-R * 0.72) + ' ' + (-R * 0.58)
     + ' Z"/></clipPath></defs>';
  s += '<g clip-path="url(#bul' + uid + ')" data-bleed="1">'
     + photoTile(cxB - R, cyB - R, 2 * R, R * 2.2, 0, PHOTOS_LARGE)
     + '<rect x="' + (cxB - R) + '" y="' + (cyB - R) + '" width="' + (2 * R) + '" height="' + (R * 2.2) + '" fill="' + C.navy + '" opacity="' + (sombre ? 0.28 : 0.16) + '"/>'
     + '</g>';

  s += rings(W * 0.10, H * 0.84, Math.min(W * 0.32, H * 0.20), sombre ? 0.16 : 0.10);
  var bar = Math.max(6, Math.round(Math.min(W, H) * 0.010));
  s += triBar(0, 0, W, bar);

  /* ── En-tête ─────────────────────────────────────────────────────── */
  var lg = Math.min(W * 0.130, H * 0.075), hy = Math.max(bar + H * 0.020, H * 0.042);
  s += logoImg(M, hy, lg, embed, sombre);
  s += T(M + lg + W * 0.022, hy + lg * 0.44, ORG, { size: lg * 0.20, fill: encre, ls: lg * 0.010 });
  s += TFit(M + lg + W * 0.022, hy + lg * 0.44 + lg * 0.30, TAG, W - (M + lg + W * 0.022) - M, { size: lg * 0.163, w: 600, fill: accent });
  var hautBloc = hy + lg + Math.min(H * 0.035, W * 0.05);

  /* ── Pied, construit de bas en haut : c'est lui qui fixe la place
        restante pour le bloc de titre. ─────────────────────────────── */
  var footY = H - Math.max(H * 0.040, bar * 3);
  var footS = Math.min(W * 0.020, H * 0.016);
  var colW  = Math.min(W * 0.62, W - 2 * M);

  var qrTaille = OPT.qr && ASSETS.qr ? Math.min(W * 0.13, H * 0.075) : 0;
  var co = calloutType(M, 0, colW, v.ctaShort || v.cta, null, accent, encre, u);
  var ctaTop = footY - footS * 2.6 - co.h;

  var checks = (v.checks || []).slice(0, 3);
  var pasCheck = Math.min(H * 0.034, W * 0.048);
  var checksTop = ctaTop - Math.min(H * 0.028, W * 0.04) - (checks.length - 1) * pasCheck;

  /* ── Bloc de titre, dimensionné pour tenir dans l'espace disponible ── */
  var dispo = checksTop - hautBloc - Math.min(H * 0.03, W * 0.04);
  var sub = v.sub || [SLOGAN];

  function mesures(k){
    var m = { kick: W * 0.024 * k, seas: W * 0.032 * k, subS: W * 0.026 * k };
    m.ts = W * 0.110 * k;
    for (var i = 0; i < v.t.length; i++) m.ts = Math.min(m.ts, fitSize(v.t[i], colW, m.ts, 800, FAM_AG));
    m.lh = m.ts * 1.04;
    m.subLh = m.subS * 1.32;
    /* kicker + interligne + titre + saison + sous-titre */
    m.h = m.kick * 1.9 + m.ts + (v.t.length - 1) * m.lh
        + m.seas * 2.1 + m.subS * 1.5 + (sub.length - 1) * m.subLh;
    return m;
  }
  var m = mesures(1);
  for (var pass = 0; pass < 6 && m.h > dispo; pass++) m = mesures(dispo / m.h * 0.99);

  var cur = hautBloc + m.kick;
  s += TFit(M, cur, v.kicker, colW, { size: m.kick, fill: accent, ls: m.kick * 0.18 });

  cur += m.kick * 0.9 + m.ts * 0.82;
  s += TL(M, cur, v.t, { size: m.ts, f: 'ag', w: 800, fill: encre, lh: m.lh });
  cur += (v.t.length - 1) * m.lh;

  cur += m.seas * 1.5;
  s += T(M, cur, v.season, { size: m.seas, fill: accent, f: 'ag', w: 800, ls: m.seas * 0.09 });

  cur += m.subS * 1.5;
  s += TLFit(M, cur, sub, colW, { size: m.subS, w: 500, fill: doux, lh: m.subLh });

  /* ── Arguments, appel à l'action, QR, pied ───────────────────────── */
  for (var j = 0; j < checks.length; j++) {
    s += bulletRow(M, checksTop + j * pasCheck, checks[j], Math.min(W * 0.023, H * 0.018),
                   [C.blue, C.yellow, C.red][j % 3], doux, colW);
  }

  s += calloutType(M, ctaTop, colW, v.ctaShort || v.cta, null, accent, encre, u).svg;

  if (qrTaille) {
    s += qrCard(W - M - qrTaille, ctaTop + co.h - qrTaille, qrTaille, null, doux);
  }

  s += instaTag(M, footY, footS, doux);
  s += T(W - M, footY, SITE, { size: footS, fill: accent, anchor: 'end', w: 700 });
  s += triBar(0, H - bar, W, bar);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   10 quater. Layout « MODERNE »
   Reprise du langage graphique des maquettes de référence : bandeau navy
   arrondi pour le titre, pastille jaune pour la saison, photo dans un cadre
   très arrondi, bande crème de bénéfices à pictogrammes, cartes d'actions,
   bloc jaune d'appel à l'action, pied navy à colonnes de contact.
   Entièrement proportionnel : tient du post carré au kakémono.
   ===================================================================== */
function renderModerne(style, variant, uid, embed, W, H){
  var v = V(variant), M = W * 0.062, u = W;
  var s = '<rect width="' + W + '" height="' + H + '" fill="#F7FAFF"/>';
  var bar = Math.max(5, Math.round(Math.min(W, H) * 0.007));

  /* Arc jaune en haut à droite, signature des maquettes. */
  s += '<path d="M ' + (W * 0.52) + ' 0 Q ' + (W * 0.94) + ' ' + (H * 0.045) + ' ' + W + ' ' + (H * 0.16)
     + ' L ' + W + ' 0 Z" fill="' + C.yellow + '" opacity="0.22"/>';
  s += '<path d="M ' + (W * 0.62) + ' 0 Q ' + (W * 0.97) + ' ' + (H * 0.035) + ' ' + W + ' ' + (H * 0.11)
     + ' L ' + W + ' 0 Z" fill="' + C.navy + '" opacity="0.10"/>';

  /* En-tete */
  var lg = Math.min(W * 0.135, H * 0.085), hy = H * 0.030;
  s += logoImg(M, hy, lg, embed, false);
  var hx = M + lg + W * 0.030;
  s += T(hx, hy + lg * 0.44, ORG, { size: lg * 0.225, fill: C.navy, ls: lg * 0.012 });
  s += TFit(hx, hy + lg * 0.44 + lg * 0.32, TAG, W - hx - M, { size: lg * 0.175, w: 600, fill: C.dark, it: true });

  var y = hy + lg + H * 0.028;

  /* Bandeau navy : titre en reserve blanche */
  var titreCol = W * 0.545;
  var lignes = v.t, ts = Math.min(W * 0.088, H * 0.055);
  for (var i = 0; i < lignes.length; i++) ts = Math.min(ts, fitSize(lignes[i], titreCol - W * 0.075, ts, 800, FAM_AG));
  var bandeauH = ts * (1.06 * lignes.length) + H * 0.045;
  s += '<path d="M 0 ' + y + ' H ' + (titreCol - W * 0.05)
     + ' q ' + (W * 0.05) + ' 0 ' + (W * 0.05) + ' ' + (W * 0.05)
     + ' V ' + (y + bandeauH - W * 0.05)
     + ' q 0 ' + (W * 0.05) + ' ' + (-W * 0.05) + ' ' + (W * 0.05)
     + ' H 0 Z" fill="' + C.navy + '"/>';
  s += TL(M, y + H * 0.030 + ts * 0.80, lignes,
          { size: ts, f: 'ag', w: 800, fill: '#FFFFFF', lh: ts * 1.06 });

  /* Pastille jaune de saison */
  var sy = y + bandeauH + H * 0.012, ss = Math.min(W * 0.036, H * 0.024);
  /* Largeur = marge gauche + texte + marge droite, plus le rayon du coin
     arrondi : sinon la dernière année sortait de l'aplat jaune. */
  var sw = M + measure(v.season, ss, 800, FAM_AG) + W * 0.055 + W * 0.028;
  s += '<path d="M 0 ' + sy + ' H ' + (sw - W * 0.028)
     + ' q ' + (W * 0.028) + ' 0 ' + (W * 0.028) + ' ' + (ss * 0.9)
     + ' q 0 ' + (ss * 0.9) + ' ' + (-W * 0.028) + ' ' + (ss * 0.9)
     + ' H 0 Z" fill="' + C.yellow + '"/>';
  s += T(M, sy + ss * 1.28, v.season, { size: ss, f: 'ag', w: 800, fill: C.navy, ls: ss * 0.06 });

  /* Sous-titre */
  var subY = sy + ss * 2.5 + H * 0.020, subS = Math.min(W * 0.030, H * 0.020);
  var sub = v.sub || [SLOGAN];
  s += TLFit(M, subY, sub, titreCol - W * 0.03, { size: subS, w: 700, fill: C.navy, lh: subS * 1.35 });
  var basTexte = subY + (sub.length - 1) * subS * 1.35;
  s += underline(M, basTexte + subS * 0.55, sub[0], subS, C.yellow);

  /* Photo dans un cadre tres arrondi, a droite */
  var phX = titreCol + W * 0.020, phW = W - phX - M, phY = y - H * 0.010;
  var phH = Math.min(basTexte + subS - phY, H * 0.30);
  if (phW > W * 0.20 && phH > H * 0.10) {
    var r = Math.min(phW, phH) * 0.22;
    s += '<defs><clipPath id="ph' + uid + '"><rect x="' + phX + '" y="' + phY + '" width="' + phW + '" height="' + phH + '" rx="' + r + '"/></clipPath></defs>';
    s += '<g clip-path="url(#ph' + uid + ')">' + photoTile(phX, phY, phW, phH, 0, PHOTOS_LARGE) + '</g>';
    s += '<rect x="' + phX + '" y="' + phY + '" width="' + phW + '" height="' + phH + '" rx="' + r
       + '" fill="none" stroke="' + C.yellow + '" stroke-width="' + (bar * 1.4) + '"/>';
  }

  /* Bande creme des trois benefices */
  var bY = Math.max(basTexte + subS * 1.6, (phY + phH) + H * 0.022);
  var bH = Math.min(H * 0.085, W * 0.115);
  s += '<rect x="' + M + '" y="' + bY + '" width="' + (W - 2 * M) + '" height="' + bH + '" rx="' + (bH * 0.22) + '" fill="#FFF8E8"/>';
  var colB = (W - 2 * M) / 3, pr = bH * 0.30, bs = Math.min(bH * 0.20, W * 0.019);
  for (var b = 0; b < 3; b++) {
    var bx = M + b * colB;
    if (b) s += '<rect x="' + bx + '" y="' + (bY + bH * 0.20) + '" width="1.6" height="' + (bH * 0.60) + '" fill="#E2CFA0"/>';
    s += pastillePicto(bx + colB * 0.145, bY + bH / 2, pr, BENEFICES[b][0], 'rgba(255,255,255,0)', BENEFICES[b][1]);
    s += TFit(bx + colB * 0.145 + pr * 1.35, bY + bH * 0.44, BENEFICES[b][2], colB * 0.70, { size: bs, w: 800, fill: C.navy });
    s += TFit(bx + colB * 0.145 + pr * 1.35, bY + bH * 0.72, BENEFICES[b][3], colB * 0.70, { size: bs * 0.92, w: 500, fill: '#5C6B82' });
  }

  /* Colonne « Nos actions » + bloc jaune d'adhesion */
  var zY = bY + bH + H * 0.028;
  var footH = Math.min(H * 0.155, W * 0.21);
  var zH = H - footH - H * 0.020 - zY;
  var gauche = (W - 2 * M) * 0.52, droite = (W - 2 * M) - gauche - W * 0.022;
  var dX = M + gauche + W * 0.022;

  if (zH > H * 0.10) {
    var titA = Math.min(W * 0.032, H * 0.022);
    s += pill(M, zY, 'Nos actions', titA, C.navy, '#FFFFFF', titA * 0.9, titA * 2.0);
    var cardY = zY + titA * 2.5, nA = Math.min(ACTIONS.length, 4);
    var cardH = (zH - (cardY - zY) - nA * H * 0.008) / nA;
    for (var a = 0; a < nA; a++) {
      var ay = cardY + a * (cardH + H * 0.008);
      s += '<rect x="' + M + '" y="' + ay + '" width="' + gauche + '" height="' + cardH + '" rx="' + (cardH * 0.26) + '" fill="#EDF4FF"/>';
      var apr = cardH * 0.30;
      s += pastillePicto(M + cardH * 0.42, ay + cardH / 2, apr, ACTIONS[a][0], C.yellow, C.navy);
      var atx = M + cardH * 0.42 + apr * 1.5, atw = gauche - (atx - M) - cardH * 0.22;
      s += TFit(atx, ay + cardH * 0.44, ACTIONS[a][1], atw, { size: Math.min(cardH * 0.24, W * 0.021), w: 800, fill: C.navy });
      s += TFit(atx, ay + cardH * 0.74, ACTIONS[a][2], atw, { size: Math.min(cardH * 0.20, W * 0.017), w: 500, fill: '#6C7A91' });
    }

    /* Bloc jaune : megaphone, accroche, garanties, bouton. */
    s += '<rect x="' + dX + '" y="' + zY + '" width="' + droite + '" height="' + zH + '" rx="' + (droite * 0.07) + '" fill="' + C.yellow + '"/>';
    var mr = Math.min(droite * 0.11, zH * 0.10);
    s += picto('megaphone', dX + droite * 0.07, zY + zH * 0.055, mr * 2, C.navy);
    var dtx = dX + droite * 0.07 + mr * 2.4, dtw = droite - (dtx - dX) - droite * 0.07;
    var dts = Math.min(droite * 0.075, zH * 0.075);
    /* Le bandeau navy porte déjà le nom de la campagne : cet encart pose une
       question au lecteur au lieu de répéter le titre. */
    var enc = v.encart || ['Prêt à', "T'ENGAGER ?"];
    s += TFit(dtx, zY + zH * 0.075 + dts * 0.8, enc[0], dtw, { size: dts, w: 800, fill: C.navy });
    s += TFit(dtx, zY + zH * 0.075 + dts * 2.0, enc[1], dtw, { size: dts, f: 'ag', w: 800, fill: '#FFFFFF' });

    /* `garanties` et non `checks` : les valeurs figurent déjà dans la bande
       crème des bénéfices, quelques centimètres plus haut. Ce bloc répond à
       une autre question — « concrètement, ça m'engage à quoi ? ». */
    var chk = (v.garanties || v.checks || []).slice(0, 3);
    var boxY = zY + zH * 0.40, boxH = zH * 0.34;
    s += '<rect x="' + (dX + droite * 0.055) + '" y="' + boxY + '" width="' + (droite * 0.89) + '" height="' + boxH + '" rx="' + (boxH * 0.18) + '" fill="#FFFBEF"/>';
    var cs = Math.min(droite * 0.048, boxH * 0.20);
    for (var c = 0; c < chk.length; c++) {
      var cy2 = boxY + boxH * (0.28 + c * 0.26);
      s += pastillePicto(dX + droite * 0.115, cy2 - cs * 0.30, cs * 0.72, 'coche', C.navy, '#FFFFFF');
      s += TFit(dX + droite * 0.115 + cs * 1.3, cy2, chk[c], droite * 0.70, { size: cs, w: 600, fill: C.navy });
    }

    var btH = Math.min(zH * 0.14, droite * 0.16), btY = zY + zH - btH - zH * 0.055;
    s += '<rect x="' + (dX + droite * 0.075) + '" y="' + btY + '" width="' + (droite * 0.85) + '" height="' + btH + '" rx="' + (btH / 2) + '" fill="' + C.navy + '"/>';
    var bl = v.ctaShort || v.cta, bls = Math.min(btH * 0.42, droite * 0.085);
    bls = fitSize(bl, droite * 0.55, bls, 800, FAM_AG);
    s += T(dX + droite * 0.075 + droite * 0.425, btY + btH * 0.64, bl,
           { size: bls, f: 'ag', w: 800, fill: '#FFFFFF', anchor: 'middle' });
    /* Pas de lien sous le bouton : le pied de page l'affiche déjà, en jaune
       et accompagné du QR code. */
  }

  /* Pied navy : QR et colonnes de contact */
  var fY = H - footH;
  s += '<rect x="0" y="' + fY + '" width="' + W + '" height="' + footH + '" fill="' + C.navy + '"/>';
  var fx = M, qz = 0;
  if (OPT.qr && ASSETS.qr) {
    qz = Math.min(footH * 0.62, W * 0.13);
    s += '<rect x="' + M + '" y="' + (fY + (footH - qz) / 2) + '" width="' + qz + '" height="' + qz + '" rx="' + (qz * 0.10) + '" fill="#FFFFFF"/>';
    s += '<image x="' + (M + qz * 0.07) + '" y="' + (fY + (footH - qz) / 2 + qz * 0.07) + '" width="' + (qz * 0.86) + '" height="' + (qz * 0.86)
       + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
    fx = M + qz + W * 0.028;
  }
  var fs = Math.min(footH * 0.115, W * 0.017);
  s += T(fx, fY + footH * 0.34, 'SCANNE OU', { size: fs, fill: '#FFFFFF', w: 800, ls: fs * 0.10 });
  s += T(fx, fY + footH * 0.52, 'RENDS-TOI SUR', { size: fs, fill: '#FFFFFF', w: 800, ls: fs * 0.10 });
  var lw = measure(SITE, fs, 700, FAM_GS) + fs * 1.1;
  s += '<rect x="' + fx + '" y="' + (fY + footH * 0.62) + '" width="' + lw + '" height="' + (fs * 1.7) + '" rx="' + (fs * 0.5) + '" fill="' + C.yellow + '"/>';
  s += T(fx + fs * 0.55, fY + footH * 0.62 + fs * 1.22, SITE, { size: fs, fill: C.navy, w: 700 });

  var cX = fx + Math.max(lw, W * 0.20) + W * 0.035, cW = W - cX - M;
  if (cW > W * 0.18) {
    var contacts = [['telephone', 'Tel. ' + TEL], ['enveloppe', MAIL], ['insta', INSTA], ['facebook', FACEBOOK]];
    for (var q = 0; q < contacts.length; q++) {
      var qy2 = fY + footH * (0.22 + q * 0.185);
      s += pastillePicto(cX + fs * 0.9, qy2 - fs * 0.30, fs * 0.86, contacts[q][0], C.yellow, C.navy);
      s += TFit(cX + fs * 2.4, qy2, contacts[q][1], cW - fs * 2.6, { size: fs, w: 600, fill: '#FFFFFF' });
    }
  }
  s += triBar(0, H - bar, W, bar);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   10 quinquies. Layout « TRIO »
   Triptyque de photos, trois bénéfices en pastilles colorées, appel à
   l'action typographique et badges de valeurs — d'après les maquettes.
   ===================================================================== */
function renderTrio(style, variant, uid, embed, W, H){
  var v = V(variant), M = W * 0.065, u = W;
  var s = '<rect width="' + W + '" height="' + H + '" fill="#F7FAFF"/>';
  var bar = Math.max(5, Math.round(Math.min(W, H) * 0.008));
  s += triBar(0, 0, W, bar);
  s += rings(W * 0.90, H * 0.055, Math.min(W * 0.30, H * 0.16), 0.20);

  /* En-tete */
  var lg = Math.min(W * 0.130, H * 0.080), hy = H * 0.032;
  s += logoImg(M, hy, lg, embed, false);
  s += '<rect x="' + (M + lg + W * 0.024) + '" y="' + (hy + lg * 0.12) + '" width="2" height="' + (lg * 0.72) + '" fill="#D8E2F2"/>';
  var hx = M + lg + W * 0.045;
  s += T(hx, hy + lg * 0.44, ORG, { size: lg * 0.225, fill: C.navy, ls: lg * 0.012 });
  s += TFit(hx, hy + lg * 0.44 + lg * 0.32, TAG, W - hx - M - Math.min(W * 0.24, H * 0.16), { size: lg * 0.175, w: 600, fill: '#5C6B82', it: true });

  /* Titre */
  var y = hy + lg + H * 0.045, colW = W * 0.66;
  var ks = Math.min(W * 0.025, H * 0.017);
  s += TFit(M, y, v.kicker, colW, { size: ks, fill: C.bluedark, ls: ks * 0.20 });

  var lignes = v.t, ts = Math.min(W * 0.105, H * 0.068);
  for (var i = 0; i < lignes.length; i++) ts = Math.min(ts, fitSize(lignes[i], colW, ts, 800, FAM_AG));
  y += ks * 1.1 + ts * 0.82;
  s += TL(M, y, lignes, { size: ts, f: 'ag', w: 800, fill: C.navy, lh: ts * 1.04 });
  y += (lignes.length - 1) * ts * 1.04;

  var seas = Math.min(W * 0.042, H * 0.028);
  y += seas * 1.5;
  s += T(M, y, v.season, { size: seas, fill: C.bluedark, f: 'ag', w: 800, ls: seas * 0.05 });

  var subS = Math.min(W * 0.028, H * 0.019), sub = v.sub || [SLOGAN];
  y += subS * 1.7;
  s += TLFit(M, y, sub, colW, { size: subS, w: 700, fill: '#3A4A63', lh: subS * 1.42 });
  y += (sub.length - 1) * subS * 1.42;

  /* Pastille ronde : le slogan de l'association, à sa vraie place. */
  var pr = Math.min(W * 0.115, H * 0.075), pcx = W - M - pr, pcy = hy + lg + H * 0.10 + pr;
  if (W - colW - 2 * M > pr) {
    s += '<circle cx="' + pcx + '" cy="' + pcy + '" r="' + pr + '" fill="none" stroke="' + C.blue + '" stroke-width="' + (pr * 0.035) + '"/>';
    var mots = CRI, mc = [C.navy, C.yellow, C.red];
    var msz = Math.min(pr * 0.27, W * 0.024);
    for (var w2 = 0; w2 < 3; w2++) {
      s += T(pcx, pcy - pr * 0.22 + w2 * msz * 1.5, mots[w2],
             { size: msz, w: 800, fill: mc[w2], anchor: 'middle', it: true });
    }
  }

  /* Triptyque de photos */
  var tY = y + subS * 1.9, tH = Math.min(H * 0.22, W * 0.30);
  var gap = W * 0.012, tW = (W - 2 * M - 2 * gap) / 3;
  var teintes = [C.blue, C.yellow, C.red];
  for (var t2 = 0; t2 < 3; t2++) {
    var tx = M + t2 * (tW + gap), rr = tW * 0.06;
    s += '<defs><clipPath id="tp' + uid + t2 + '"><rect x="' + tx + '" y="' + tY + '" width="' + tW + '" height="' + tH + '" rx="' + rr + '"/></clipPath></defs>';
    s += '<g clip-path="url(#tp' + uid + t2 + ')">'
       + photoTile(tx, tY, tW, tH, t2, reserveTuile(t2, 3)) + '</g>';
    s += '<rect x="' + tx + '" y="' + tY + '" width="' + tW + '" height="' + (bar * 0.9) + '" fill="' + teintes[t2] + '"/>';
    s += '<rect x="' + tx + '" y="' + (tY + tH - bar * 0.9) + '" width="' + tW + '" height="' + (bar * 0.9) + '" fill="' + teintes[t2] + '"/>';
  }

  /* Benefices a gauche, appel a l'action a droite */
  var zY = tY + tH + H * 0.035;
  var footH = Math.min(H * 0.20, W * 0.26);
  var zH = H - footH - H * 0.018 - zY;
  var gW = (W - 2 * M) * 0.46, dX2 = M + gW + W * 0.045;

  if (zH > H * 0.08) {
    var br = Math.min(zH * 0.13, W * 0.030), bsz = Math.min(zH * 0.115, W * 0.024);
    for (var k2 = 0; k2 < 3; k2++) {
      var ky = zY + zH * (0.16 + k2 * 0.33);
      s += pastillePicto(M + br, ky, br, BENEFICES[k2][0], BENEFICES[k2][1], '#FFFFFF');
      s += TFit(M + br * 2.5, ky - bsz * 0.15, BENEFICES[k2][2], gW - br * 2.7, { size: bsz, w: 800, fill: BENEFICES[k2][1] });
      s += TFit(M + br * 2.5, ky + bsz * 1.05, BENEFICES[k2][3], gW - br * 2.7, { size: bsz * 0.92, w: 500, fill: '#5C6B82' });
    }

    var dW = W - dX2 - M;
    var cl = (v.ctaShort || v.cta), cls = fitSize(cl, dW * 0.92, Math.min(zH * 0.30, dW * 0.20), 800, FAM_AG);
    s += T(dX2, zY + zH * 0.36, cl, { size: cls, f: 'ag', w: 800, fill: C.navy, it: true });
    s += '<path d="M ' + dX2 + ' ' + (zY + zH * 0.46) + ' Q ' + (dX2 + dW * 0.5) + ' ' + (zY + zH * 0.40) + ' ' + (dX2 + dW * 0.94) + ' ' + (zY + zH * 0.45)
       + '" fill="none" stroke="' + C.blue + '" stroke-width="' + (cls * 0.10) + '" stroke-linecap="round"/>';
    var us = Math.min(zH * 0.14, dW * 0.075);
    var uw = measure(SITE, us, 700, FAM_GS) + us * 2.0;
    s += '<rect x="' + dX2 + '" y="' + (zY + zH * 0.62) + '" width="' + Math.min(uw, dW) + '" height="' + (us * 2.0) + '" rx="' + (us * 0.35) + '" fill="' + C.bluedark + '"/>';
    s += T(dX2 + Math.min(uw, dW) / 2, zY + zH * 0.62 + us * 1.36, SITE, { size: us, fill: '#FFFFFF', w: 700, anchor: 'middle' });
  }

  /* Pied : QR, contacts, badges de valeurs */
  var fY = H - footH;
  s += '<rect x="' + M + '" y="' + fY + '" width="' + (W - 2 * M) + '" height="' + (footH - H * 0.030) + '" rx="' + (W * 0.022) + '" fill="#FFFFFF" stroke="#E4EAF4" stroke-width="2"/>';
  var qz = Math.min((footH - H * 0.030) * 0.72, W * 0.115), qx = M + W * 0.022, qy3 = fY + ((footH - H * 0.030) - qz) / 2;
  if (OPT.qr && ASSETS.qr) {
    s += '<rect x="' + qx + '" y="' + qy3 + '" width="' + qz + '" height="' + qz + '" rx="' + (qz * 0.10) + '" fill="#FFFFFF" stroke="' + C.blue + '" stroke-width="2"/>';
    s += '<image x="' + (qx + qz * 0.08) + '" y="' + (qy3 + qz * 0.08) + '" width="' + (qz * 0.84) + '" height="' + (qz * 0.84)
       + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
  } else { qz = 0; }
  var ix = qx + (qz ? qz + W * 0.024 : 0), isz = Math.min(footH * 0.095, W * 0.016);
  s += T(ix, qy3 + isz * 1.3, 'SCANNE OU RENDS-TOI SUR', { size: isz, fill: '#6C7A91', ls: isz * 0.10, w: 700 });
  s += T(ix, qy3 + isz * 2.9, '16 – 35 ans', { size: isz * 1.15, fill: C.navy, w: 800 });
  s += T(ix, qy3 + isz * 4.3, 'Adhésion en ligne', { size: isz, fill: '#6C7A91', w: 600 });
  s += instaTag(ix, qy3 + isz * 5.7, isz, '#6C7A91');

  var bX = ix + W * 0.24, bW2 = W - bX - M - W * 0.022;
  if (bW2 > W * 0.20) {
    var nb = VALEURS_BADGES.length, cb = bW2 / nb, vr = Math.min(cb * 0.22, (footH - H * 0.030) * 0.16);
    for (var vB = 0; vB < nb; vB++) {
      var vx = bX + cb * (vB + 0.5);
      if (vB) s += '<rect x="' + (bX + cb * vB) + '" y="' + (qy3 + vr * 0.2) + '" width="1.6" height="' + (vr * 3.4) + '" fill="#E9EFF9"/>';
      s += pastillePicto(vx, qy3 + vr * 1.3, vr, VALEURS_BADGES[vB][0], 'rgba(255,255,255,0)', [C.blue, C.yellow, C.red, C.navy][vB]);
      s += TFit(vx, qy3 + vr * 3.0, VALEURS_BADGES[vB][1], cb * 0.94, { size: Math.min(vr * 0.58, W * 0.015), w: 800, fill: C.navy, anchor: 'middle' });
    }
  }

  s += T(W - M, H - H * 0.012, 'Tel. ' + TEL, { size: Math.min(W * 0.018, H * 0.013), w: 700, fill: C.navy, anchor: 'end' });
  s += triBar(0, H - bar, W, bar);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   11. Layout — FLYER A5 verso (fond blanc, informatif)
   ===================================================================== */
function renderVerso(style, variant, uid, embed){
  var W = 1240, H = 1754, M = 106, p = pal('blanc');
  var vc = V('comment'), vp = V('pourquoi');
  var s = '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>';
  s += rings(W * 0.95, H * 0.03, 240, 0.16);
  s += triBar(0, 0, W, 18);

  s += logoImg(M, 60, 120, embed);
  s += T(M + 146, 118, ORG, { size: 29, fill: C.navy, ls: 1.4 });
  s += TFit(M + 146, 156, TAG, W - (M + 146) - M - 180, { size: 21, w: 500, fill: '#6C7A91' });
  s += T(W - M, 132, OPT.season, { size: 26, fill: C.blue, anchor: 'end', f: 'ag', w: 800, ls: 3 });
  s += '<rect x="' + M + '" y="220" width="' + (W - 2 * M) + '" height="2" fill="#E4EAF4"/>';

  /* Les 3 étapes */
  s += T(M, 306, 'COMMENT ADHÉRER ?', { size: 26, fill: C.blue, ls: 5 });
  s += T(M, 380, '3 étapes, et c\'est fait', { size: 58, fill: C.navy, f: 'ag', w: 800 });
  for (var k = 0; k < 3; k++) {
    var by = 452 + k * 152;
    s += '<rect x="' + M + '" y="' + by + '" width="' + (W - 2 * M) + '" height="132" rx="22" fill="#F5F8FD"/>';
    s += '<circle cx="' + (M + 62) + '" cy="' + (by + 66) + '" r="38" fill="' + [C.blue, C.yellow, C.red][k] + '"/>';
    s += T(M + 62, by + 81, vc.steps[k][0], { size: 40, fill: k === 1 ? C.navy : '#FFFFFF', anchor: 'middle', f: 'ag', w: 800 });
    s += T(M + 126, by + 56, vc.steps[k][1], { size: 29, fill: C.navy });
    s += wrapText(vc.steps[k][2], M + 126, by + 96, W - 2 * M - 160, 22, 30, { w: 500, fill: '#6C7A91' });
  }

  /* Ce que MJA apporte */
  s += T(M, 966, 'CE QUE TU TROUVES CHEZ MJA', { size: 26, fill: C.blue, ls: 5 });
  var gw = (W - 2 * M - 22) / 2;
  for (var j = 0; j < 4; j++) {
    var gx = M + (j % 2) * (gw + 22), gy = 1006 + Math.floor(j / 2) * 118;
    s += '<rect x="' + gx + '" y="' + gy + '" width="' + gw + '" height="104" rx="18" fill="#FFFFFF" stroke="#E4EAF4" stroke-width="2"/>';
    s += '<rect x="' + gx + '" y="' + gy + '" width="' + gw + '" height="5" rx="2.5" fill="' + [C.blue, C.yellow, C.red, C.dark][j] + '"/>';
    s += T(gx + 22, gy + 46, vp.items[j][0], { size: 23, fill: C.navy, ls: 1.6 });
    s += wrapText(vp.items[j][1], gx + 22, gy + 76, gw - 44, 18, 24, { w: 500, fill: '#6C7A91' });
  }

  /* Infos pratiques */
  s += '<rect x="' + M + '" y="1266" width="' + (W - 2 * M) + '" height="300" rx="26" fill="' + C.navy + '"/>';
  s += triBar(M, 1266, W - 2 * M, 8);
  s += T(M + 40, 1338, 'INFOS PRATIQUES', { size: 24, fill: C.yellow, ls: 5 });
  var infos = [
    ['Qui ?', 'Tous les jeunes de 16 à 35 ans, en Martinique.'],
    ['Combien ?', OPT.price ? 'Cotisation annuelle de 20 € (première adhésion et réadhésion).' : 'Une cotisation annuelle symbolique.'],
    ['Comment payer ?', 'Carte bancaire en ligne, virement ou espèces.'],
    ['Où ?', "En ligne, depuis le site — scanne le QR code ci-dessous."],
    ['Contact', MAIL + '  ·  Tél. ' + TEL]
  ];
  for (var n = 0; n < infos.length; n++) {
    var iy = 1394 + n * 44;
    s += T(M + 40, iy, infos[n][0], { size: 23, fill: '#FFFFFF' });
    s += T(M + 40 + 210, iy, infos[n][1], { size: 23, w: 500, fill: '#C4D4F3' });
  }

  /* QR + pied */
  if (OPT.qr && ASSETS.qr) {
    s += '<image x="' + (W - M - 150) + '" y="1574" width="150" height="150" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
    s += T(W - M - 176, 1620, 'Scanne et adhère', { size: 24, fill: C.navy, anchor: 'end' });
    s += T(W - M - 176, 1656, SITE, { size: 22, fill: C.blue, anchor: 'end', w: 700 });
  }
  s += instaTag(M, 1660, 22, '#98A5B8');
  s += wordmark(M, 1716, 44);
  s += triBar(0, H - 18, W, 18);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   11 bis. Layout — MOSAÏQUE DE PHOTOS
   Hommage direct au visuel d'adhésion MJA de novembre 2025 : bandeaux de
   photos de membres en haut et en bas, bloc central blanc filigrané,
   slogan en italique bleu, « INSCRIS-TOI ! » bordeaux + flèche vers le QR,
   signature jaune et bordeaux.
   ===================================================================== */
/**
 * Cadrage particulier de certaines photos.
 *
 * `preserveAspectRatio="slice"` remplit toujours le cadre, mais il centre la
 * photo : celles dont le sujet est decentre laissent apparaitre leur fond
 * clair sur un bord. On les rapproche donc avec un zoom et un point de
 * visee, exprimes en fraction de l'image (0 = gauche/haut, 1 = droite/bas).
 */
var CADRAGE = {
  'images/kit/membres-06.jpg': { zoom: 1.45, cx: 0.50, cy: 0.56 },

  /* Vue aérienne de la plage : le sujet (l'équipe et « MJA » dans le sable)
     occupe la moitié basse. Sans recadrage, une tuile portrait garde surtout
     le ciel et les cocotiers, et le sujet finit minuscule en bas de case. */
  'images/kit/WhatsApp Image 2026-08-20 at 19.17.20.jpeg': { zoom: 1.35, cx: 0.50, cy: 0.62 }
};

var _uidTile = 0;

/** Adresse de la photo `chemin` : data-URI à l'export, URL du site sinon. */
function srcPhoto(chemin){
  if (EMBED_PHOTOS && ASSETS.photos[chemin]) return 'data:image/jpeg;base64,' + ASSETS.photos[chemin];
  return ASSET(chemin.split('/').map(encodeURIComponent).join('/'));
}

/**
 * Une tuile photo. `reserve` dit ce qu'on veut montrer (PHOTOS_ACTION ou
 * PHOTOS_GROUPE) ; sans elle on retombe sur la photothèque mélangée.
 * Dès que l'utilisateur dépose ses propres fichiers, plus de classement
 * possible : on garde la rotation simple.
 */
/**
 * Réserve réordonnée pour une tuile de `w` unités : d'abord les photos assez
 * définies pour la remplir, puis les autres.
 *
 * Réordonner et non filtrer, c'est le point important. Une vignette de
 * 356 px en fond de flyer pleine page est nette dans la galerie et bouillie à
 * l'impression — d'où la préférence. Mais écarter les autres réduisait
 * parfois la réserve à une seule photo : le visuel affichait alors toujours la
 * même image et son sélecteur de photo disparaissait, faute de second cran.
 * La liste garde donc toujours sa taille ; seul l'ordre change, si bien que
 * le tirage par défaut tombe sur la mieux définie et que le sélecteur peut
 * quand même parcourir tout le lot.
 */
function candidats(reserve, w){
  var bons = [], reste = [];
  for (var k = 0; k < reserve.length; k++) {
    if ((LARGEURS[reserve[k]] || 0) >= w) bons.push(reserve[k]);
    else reste.push(reserve[k]);
  }
  return bons.concat(reste);
}

/** Index ramené dans [0, n[, quel que soit le signe. */
function tourne(i, n){ return ((i % n) + n) % n; }

/**
 * Aplatit une réserve pour une tuile de `w` unités et rend la taille de son
 * premier bloc. Une réserve est soit une liste de photos, soit une liste de
 * blocs dont l'ordre est significatif (voir PHOTOS_LARGE) ; le classement par
 * définition s'applique bloc à bloc.
 */
function ordonner(reserve, w){
  if (typeof reserve[0] === 'string') {
    var plate = candidats(reserve, w);
    return { liste: plate, bloc: plate.length };
  }
  var out = [], premier = 0;
  for (var b = 0; b < reserve.length; b++) {
    var part = candidats(reserve[b], w);
    if (b === 0) premier = part.length;
    out = out.concat(part);
  }
  return { liste: out, bloc: premier || out.length };
}

function photoTile(x, y, w, h, idx, reserve){
  var src, chemin = null, puise;
  TUILES++;

  if (!OPT.photos.length && reserve && reserve.length) {
    var o = ordonner(reserve, w), n = o.liste.length;
    puise = n;

    /* Le rang de départ tourne dans le PREMIER bloc : c'est lui qui porte
       l'intention du gabarit — une photo d'action sur un flyer — et le
       compteur du gabarit, qui va jusqu'à la douzaine, en sortirait sinon.
       Le décalage choisi à la main parcourt, lui, toute la réserve. */
    chemin = o.liste[tourne(tourne(PIDX + idx, o.bloc) + DECAL, n)];
    src = srcPhoto(chemin);
  } else {
    var i = PIDX + idx + DECAL;
    puise = OPT.photos.length || DEFAULT_PHOTOS.length;
    src = photoAt(i);
    if (!OPT.photos.length && DEFAULT_PHOTOS.length) {
      chemin = DEFAULT_PHOTOS[tourne(i, DEFAULT_PHOTOS.length)];
    }
  }
  if (puise > TUILES_RESERVE) TUILES_RESERVE = puise;
  var cad = chemin ? CADRAGE[chemin] : null;

  if (!cad) {
    return '<image x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" href="' + src
      + '" xlink:href="' + src + '" preserveAspectRatio="xMidYMid slice"/>';
  }

  /* Image agrandie puis recadree sur le point de visee, le tout coupe aux
     dimensions exactes de la tuile. */
  var id = 'pt' + (++_uidTile);
  var zw = w * cad.zoom, zh = h * cad.zoom;
  var zx = x - (zw - w) * cad.cx, zy = y - (zh - h) * cad.cy;

  return '<defs><clipPath id="' + id + '"><rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '"/></clipPath></defs>'
    + '<g clip-path="url(#' + id + ')">'
    + '<image x="' + zx + '" y="' + zy + '" width="' + zw + '" height="' + zh + '" href="' + src
    + '" xlink:href="' + src + '" preserveAspectRatio="xMidYMid slice"/>'
    + '</g>';
}

function renderMosaic(style, variant, uid, embed, W, H){
  var v = V(variant), M = W * 0.075;
  var band = H * 0.165, cTop = band, cBot = H - band;
  var tw = W / 3;
  var s = '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>';

  /* Bandeaux de photos */
  for (var i = 0; i < 3; i++) {
    s += photoTile(i * tw, 0, tw + 1, band, i);
    s += photoTile(i * tw, cBot, tw + 1, band, i + 3);
  }
  /* Bloc central — le filigrane est confiné entre les deux bandeaux. */
  s += '<rect x="0" y="' + cTop + '" width="' + W + '" height="' + (cBot - cTop) + '" fill="#FFFFFF"/>';
  s += '<defs><clipPath id="mid' + uid + '"><rect x="0" y="' + cTop + '" width="' + W + '" height="' + (cBot - cTop) + '"/></clipPath></defs>';
  s += '<g clip-path="url(#mid' + uid + ')">' + watermark(W, H, uid, embed, 0.075, W * 0.30) + '</g>';
  s += triBar(0, cTop, W, H * 0.008);
  s += triBar(0, cBot - H * 0.008, W, H * 0.008);

  /* Logo */
  var lg = H * 0.125;
  s += logoImg(W / 2 - lg / 2, cTop + H * 0.025, lg, embed);

  /* Slogan en italique (Gill Sans Italic, comme le « Relève le défi ! » 2025) */
  var slogan = v.slogan || v.t.join(String.fromCharCode(10));
  var ss = fitSize(slogan, W * 0.80, H * 0.115, 700, FAM_GS);
  s += T(W / 2, cTop + H * 0.235, slogan, { size: ss, fill: C.blue, anchor: 'middle', it: true, w: 700 });

  /* « INSCRIS-TOI ! » + QR : le QR est autonome, sans flèche décorative. */
  var isz = H * 0.040, ibase = cTop + H * 0.325;
  s += T(M, ibase, 'INSCRIS-TOI !', { size: isz, fill: C.bordeaux, w: 800, ls: isz * 0.02 });
  var qs = H * 0.175, qy = cTop + H * 0.345, avecQr = OPT.qr && ASSETS.qr;
  if (avecQr) {
    s += '<image x="' + M + '" y="' + qy + '" width="' + qs + '" height="' + qs
      + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
  } else {
    s += T(M, qy + qs * 0.3, OPT.url.replace(/^https?:\/\//, ''), { size: H * 0.026, fill: C.dark, w: 700 });
  }

  /* Paragraphe d'accroche. TLFit et non TL : les phrases de valeur sont plus
     longues que l'ancien texte et débordaient hors de la page. */
  var para = v.para || v.sub || [SLOGAN];
  var paraX = avecQr ? M + qs + W * 0.05 : M;
  var paraY = avecQr ? cTop + H * 0.40 : qy + qs * 0.3 + H * 0.055;
  s += TLFit(paraX, paraY, para, W - paraX - M, { size: H * 0.024, w: 500, fill: '#2B2B2B', lh: H * 0.033 });

  /* Signature */
  var sig = v.signature || ["Madin' Jeunes Ambition", OPT.season];
  s += T(W / 2, cBot - H * 0.075, sig[0], { size: H * 0.048, fill: C.yellow, anchor: 'middle', it: true, w: 800 });
  s += T(W / 2, cBot - H * 0.022, sig[1], { size: H * 0.040, fill: C.bordeaux, anchor: 'middle', it: true, w: 800 });

  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   11 ter. Layout — BLOCS (composition éditoriale moderne)
   Aplats de couleur asymétriques dans lesquels le texte est réservé.
   Même palette MJA, écriture beaucoup plus contemporaine que 2025.
   ===================================================================== */
function renderBlocs(style, variant, uid, embed, W, H){
  var v = V(variant), M = W * 0.062, pad = W * 0.05;
  var s = '<rect width="' + W + '" height="' + H + '" fill="#FFFFFF"/>';

  /* Toutes les mesures dérivent de la LARGEUR : la composition tient donc
     aussi bien en carré qu'en story ou en A4. */
  var aw = W * 0.63, ah = W * 0.46, bw = W - aw, bh = ah / 2;

  /* Aplat principal : le titre, réservé en blanc */
  s += '<rect x="0" y="0" width="' + aw + '" height="' + ah + '" fill="' + C.navy + '"/>';
  var lines = v.t, tsz = (ah - pad * 2) / (lines.length * 1.06);
  for (var i = 0; i < lines.length; i++) tsz = Math.min(tsz, fitSize(lines[i], aw - pad * 2, tsz, 800, FAM_AG));
  var t0 = pad + tsz * 0.82 + (ah - pad * 2 - lines.length * tsz * 1.06) / 2;
  s += TL(pad, t0, lines, { size: tsz, f: 'ag', w: 800, fill: '#FFFFFF', lh: tsz * 1.06 });

  /* Aplat jaune : la saison — aplat bleu : le logo */
  s += '<rect x="' + aw + '" y="0" width="' + bw + '" height="' + bh + '" fill="' + C.yellow + '"/>';
  var sz = fitSize(OPT.season, bw - pad * 0.7, bh * 0.34, 800, FAM_AG);
  s += T(aw + bw / 2, bh / 2 + sz * 0.36, OPT.season, { size: sz, f: 'ag', w: 800, fill: C.navy, anchor: 'middle' });
  /* L'aplat inférieur droit accueille une photo : sans elle, la composition
     restait très « institutionnelle » (que des aplats et du texte). */
  s += photoStrip(aw, bh, bw, bh, 1, 0);
  var lg = Math.min(bw, bh) * 0.46;
  s += logoImg(aw + bw - lg - bw * 0.08, bh + bh - lg - bw * 0.08, lg, embed, true);

  /* Filet rouge de séparation */
  var rule = W * 0.012;
  s += '<rect x="0" y="' + ah + '" width="' + W + '" height="' + rule + '" fill="' + C.red + '"/>';

  /* ── Zone basse ────────────────────────────────────────────────
     Le bloc « lien + réseau » est ancré en bas, l'appel à l'action juste
     au-dessus, et le texte est centré dans l'espace qui reste.          */
  var qs = W * 0.155, gap = W * 0.045, textW = W - 2 * M - qs - gap;
  var kick = W * 0.024, subS = W * 0.030, chkS = W * 0.022;
  var ctaH = W * 0.115;   /* hauteur du bandeau calloutBand */

  var ctaTop = H - M - ctaH - W * 0.115;
  var urlY   = ctaTop + ctaH + W * 0.052;
  var instaY = urlY + W * 0.042;

  var subLines = v.sub || [TAG];
  var checks = (v.checks || v.pills || []).slice(0, 3);
  var subLH = subS * 1.42, chkStep = chkS * 2.0;
  var top = ah + rule;
  /* On retire des arguments tant que le bloc bas ne tient pas au-dessus du
     bandeau d'action : mieux vaut deux arguments lisibles que trois écrasés. */
  function hauteurGroupe(n){ return kick * 1.7 + subLines.length * subLH + W * 0.022 + n * chkStep; }
  while (checks.length > 0 && (ctaTop - top - hauteurGroupe(checks.length)) < W * 0.035) checks.pop();
  var groupH = hauteurGroupe(checks.length);
  var gy = top + Math.max(W * 0.05, (ctaTop - top - groupH) / 2);

  s += TFit(M, gy, v.kicker, W - 2 * M, { size: kick, fill: C.blue, ls: kick * 0.18 });
  var cur = gy + kick * 1.7;
  s += TL(M, cur, subLines, { size: subS, w: 500, fill: '#4A5A73', lh: subLH });
  cur += subLines.length * subLH + W * 0.022;

  var chkTop = cur;
  for (var j = 0; j < checks.length; j++) {
    s += checkRow(M, cur + j * chkStep, checks[j], chkS, C.blue, '#4A5A73');
  }
  /* QR aligné sur le bloc d'arguments */
  if (OPT.qr && ASSETS.qr) {
    var qy = chkTop + (checks.length * chkStep) / 2 - qs / 2 - chkS;
    s += '<image x="' + (W - M - qs) + '" y="' + qy + '" width="' + qs + '" height="' + qs
      + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
    /* Pas de libellé sous le QR : le bandeau d'action juste dessous le disait
       déjà, et il finissait recouvert. */
  }

  s += calloutBand(W, ctaTop, v.ctaShort || v.cta, null, C.navy, '#FFFFFF', W).svg;
  s += T(M, urlY, OPT.url.replace(/^https?:\/\//, ''), { size: W * 0.026, fill: C.dark, w: 700 });
  s += instaTag(W - M, instaY, W * 0.022, '#98A5B8', { anchor:'end' });
  s += T(M, instaY, ORG, { size: W * 0.022, fill: '#98A5B8', w: 600, ls: 1 });
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   12. Layout — BANNIÈRES horizontales
   ===================================================================== */
function renderWide(style, variant, uid, embed, W, H){
  var p = pal(style), v = V(variant), M = Math.round(H * 0.13);
  var bar = Math.max(6, H * 0.018);
  var s = bgMarkup(style, W, H, uid, embed);
  s += triBar(0, 0, W, bar);

  /* Bloc de droite : QR (ou, à défaut, le sigle) — définit la largeur du texte. */
  var qs = H * 0.42, pad = H * 0.03, box = qs + pad * 2;
  var rightX = W - M - box, qrY = H * 0.13;
  var hasQr = OPT.qr && ASSETS.qr;
  var colW = (hasQr ? rightX : W - M - H * 0.62) - M - M * 0.6;

  /* Bloc de gauche : identité */
  var lg = H * 0.25;
  s += logoImg(M, H * 0.09, lg, embed, style === 'navy' || style === 'photo');
  s += T(M + lg + H * 0.05, H * 0.09 + H * 0.105, ORG, { size: H * 0.046, fill: p.fg, ls: H * 0.004 });
  s += TFit(M + lg + H * 0.05, H * 0.09 + H * 0.16, TAG, colW, { size: H * 0.036, w: 500, fill: p.fg2 });

  /* Titre + saison */
  var title = (variant === 'readhesion') ? 'JE RENOUVELLE' : "MJ'ADHÉSION";
  var ts = fitSize(title, colW, H * 0.24, 800, FAM_AG);
  s += T(M, H * 0.55, title, { size: ts, f: 'ag', w: 800, fill: p.fg });
  s += T(M, H * 0.685, OPT.season, { size: H * 0.058, fill: p.alt, f: 'ag', w: 800, ls: 3 });

  /* Appel à l'action : libellé XXL + flèche + filet, jamais de bouton. */
  var label = v.ctaShort || v.cta;
  var cob = calloutType(M, H * 0.70, colW, label, null, p.accent, p.fg, H * 0.86);
  s += cob.svg;
  s += T(M + cob.w + H * 0.06, H * 0.70 + cob.h * 0.62, OPT.url.replace(/^https?:\/\//, ''),
         { size: H * 0.048, fill: p.accent, w: 700 });

  /* QR + mentions */
  if (hasQr) {
    s += '<rect x="' + rightX + '" y="' + qrY + '" width="' + box + '" height="' + box + '" rx="' + (box * 0.11) + '" fill="#FFFFFF"/>';
    s += '<image x="' + (rightX + pad) + '" y="' + (qrY + pad) + '" width="' + qs + '" height="' + qs + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>';
    s += T(rightX + box / 2, qrY + box + H * 0.10, 'SCANNE ET ADHÈRE', { size: H * 0.042, fill: p.qrLabel, anchor: 'middle', ls: 2 });
    s += instaTag(rightX + box / 2, qrY + box + H * 0.175, H * 0.040, p.foot, { anchor:'middle' });
  } else {
    s += wordmark(W - M, H * 0.42, H * 0.26, 'end');
    s += instaTag(W - M, H * 0.62, H * 0.042, p.foot, { anchor:'end' });
  }
  s += triBar(0, H - bar, W, bar);
  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   13. Catalogue des visuels
   ===================================================================== */
var POST = { w:1080, h:1080 }, STORY = { w:1080, h:1920 }, PAP = { w:1240, h:1754 };
/* Supports de stand, au ratio exact du format imprimé :
   kakémono roll-up 85 × 200 cm, pancarte A2 (42 × 59,4), chevalet A5 paysage. */
var KAKEMONO = { w:1020, h:2400 };   /* 85 × 200 cm, ratio 1:2,353 */
var PANCARTE = { w:1240, h:1754 };   /* A2 au même ratio que A4 */
var CHEVALET = { w:1754, h:1240 };   /* A5 paysage, posé sur comptoir */

var NAMES = {
  annonce:    "Annonce de campagne",
  readhesion: "Appel à la réadhésion",
  defi:       "Relève le défi !",
  benevole:   "Devenez bénévole !",
  pourquoi:   "4 bonnes raisons d'adhérer",
  comment:    "Les 3 étapes de l'adhésion",
  chiffres:   "MJA en chiffres",
  temoignage: "Parole de membre",
  valeurs:    "Viens grandir — les valeurs",
  teaser:     "Bande-annonce — ça commence",
  verso:      "Flyer verso — infos complètes"
};

var CARDS = [];
var _nMosaic = 0, _nPhoto = 0, _nBulle = 0, _nModerne = 0, _nTrio = 0;
/** Enregistre une série de visuels.
    Chaque visuel « Mosaïque » démarre 6 photos plus loin que le précédent
    (donc un jeu de photos différent) ; chaque visuel « Photo » prend la
    photo suivante de la bibliothèque. */
function add(group, dim, style, variants, extra){
  variants.forEach(function(variant){
    var c = { group:group, style:style, variant:variant, w:dim.w, h:dim.h,
              name: NAMES[variant] || variant, id:'c' + (CARDS.length + 1) };
    if (style === 'mosaic')      { c.pidx = _nMosaic * 6; _nMosaic++; }
    else if (style === 'photo')  { c.pidx = _nPhoto;      _nPhoto++;  }
    // Sans compteur propre, tous les visuels « bulle » afficheraient la même photo.
    else if (style === 'bulle')  { c.pidx = _nBulle;      _nBulle++;  }
    else if (style === 'moderne'){ c.pidx = _nModerne;    _nModerne++; }
    else if (style === 'trio')   { c.pidx = _nTrio * 3;   _nTrio++;    }
    if (extra) Object.keys(extra).forEach(function(k){ c[k] = extra[k]; });
    CARDS.push(c);
  });
}

/* ── Posts carrés ───────────────────────────────────────────────── */
add('post', POST, 'blanc',  ['annonce','readhesion','defi','pourquoi','comment','chiffres','temoignage']);
add('post', POST, 'navy',   ['annonce','readhesion','defi','pourquoi','comment','chiffres']);
add('post', POST, 'pastel', ['annonce','benevole','defi','pourquoi']);
add('post', POST, 'typo',   ['annonce','readhesion','defi']);
add('post', POST, 'blocs',  ['annonce','readhesion','defi']);
add('post', POST, 'mosaic', ['defi','annonce']);
add('post', POST, 'photo',  ['annonce','readhesion','temoignage']);
add('post', POST, 'bulle',  ['annonce','valeurs']);
add('post', POST, 'moderne',['annonce','valeurs']);
add('post', POST, 'trio',   ['annonce','defi']);
add('post', POST, 'blanc',  ['valeurs']);
add('post', POST, 'navy',   ['valeurs']);

/* ── Stories ────────────────────────────────────────────────────── */
add('story', STORY, 'navy',   ['annonce','readhesion','defi','comment']);
add('story', STORY, 'blanc',  ['annonce','readhesion','comment']);
add('story', STORY, 'pastel', ['annonce','benevole']);
add('story', STORY, 'typo',   ['annonce','readhesion','defi']);
add('story', STORY, 'blocs',  ['annonce','defi']);
add('story', STORY, 'photo',  ['annonce','readhesion']);
add('story', STORY, 'bulle',  ['annonce','valeurs']);
add('story', STORY, 'moderne',['annonce','valeurs']);
add('story', STORY, 'trio',   ['annonce','readhesion']);
add('story', STORY, 'navy',   ['valeurs']);

/* ── Affiches (ratio A → A5 / A4 / A3) ──────────────────────────── */
add('affiche', PAP, 'blanc',  ['annonce','readhesion','defi','benevole'], { paper:true });
add('affiche', PAP, 'navy',   ['annonce','readhesion','defi'],            { paper:true });
add('affiche', PAP, 'pastel', ['annonce','benevole'],                     { paper:true });
add('affiche', PAP, 'typo',   ['annonce','readhesion','defi'],            { paper:true });
add('affiche', PAP, 'blocs',  ['annonce','defi'],                         { paper:true });
add('affiche', PAP, 'mosaic', ['defi'],                                   { paper:true });
add('affiche', PAP, 'photo',  ['annonce','readhesion'],                   { paper:true });
add('affiche', PAP, 'bulle',  ['annonce','valeurs','defi'],               { paper:true });
add('affiche', PAP, 'moderne',['annonce','valeurs','defi'],               { paper:true });
add('affiche', PAP, 'trio',   ['annonce','valeurs','readhesion'],         { paper:true });
add('affiche', PAP, 'blanc',  ['valeurs'],                                { paper:true });

/* ── Flyers A5 ──────────────────────────────────────────────────── */
add('flyer', PAP, 'blanc',  ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'navy',   ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'pastel', ['benevole'],{ paper:true, suffix:'recto' });
add('flyer', PAP, 'mosaic', ['defi'],    { paper:true, suffix:'recto' });
add('flyer', PAP, 'blocs',  ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'photo',  ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'moderne',['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'moderne',['valeurs'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'trio',   ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'trio',   ['readhesion'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'bulle',  ['annonce'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'bulle',  ['valeurs'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'blanc',  ['valeurs'], { paper:true, suffix:'recto' });
add('flyer', PAP, 'blanc',  ['verso'],   { paper:true, verso:true });

/* ── Bannières ──────────────────────────────────────────────────── */
add('banner', { w:1640, h:624 }, 'navy',   ['annonce','defi'],  { wide:true, label:'Couverture Facebook' });
add('banner', { w:1640, h:624 }, 'blanc',  ['annonce'],         { wide:true, label:'Couverture Facebook' });
add('banner', { w:1640, h:624 }, 'pastel', ['benevole'],        { wide:true, label:'Couverture Facebook' });
add('banner', { w:1640, h:624 }, 'photo',  ['annonce'],         { wide:true, label:'Couverture Facebook' });
add('banner', { w:1640, h:624 }, 'trio',   ['annonce'],         { label:'Couverture Facebook' });
add('banner', { w:1200, h:630 }, 'navy',   ['annonce'],         { wide:true, label:'Image de partage' });
add('banner', { w:1200, h:630 }, 'blanc',  ['readhesion'],      { wide:true, label:'Image de partage' });

/* ── Tenue de stand ─────────────────────────────────────────────── */
/* stand:true → gabarit « bulle », entièrement proportionnel : c'est le seul
   qui tienne aussi bien un kakémono 1:2,35 qu'un chevalet paysage. */
add('stand', KAKEMONO, 'navy',   ['annonce','defi'],    { paper:true, stand:true, label:'Kakémono 85 × 200 cm' });
add('stand', KAKEMONO, 'bulle',  ['valeurs','annonce'], { paper:true, stand:true, label:'Kakémono 85 × 200 cm' });
add('stand', KAKEMONO, 'blanc',  ['benevole'],          { paper:true, stand:true, label:'Kakémono 85 × 200 cm' });
add('stand', PANCARTE, 'blanc',  ['annonce','valeurs'], { paper:true, stand:true, label:'Pancarte A2' });
add('stand', PANCARTE, 'navy',   ['defi'],              { paper:true, stand:true, label:'Pancarte A2' });
add('stand', KAKEMONO, 'moderne',['annonce','valeurs'],  { paper:true, label:'Kakémono 85 × 200 cm' });
add('stand', PANCARTE, 'trio',   ['annonce'],            { paper:true, label:'Pancarte A2' });
add('stand', CHEVALET, 'blanc',  ['annonce'],           { paper:true, stand:true, label:'Chevalet de comptoir A5' });
add('stand', CHEVALET, 'navy',   ['readhesion'],        { paper:true, stand:true, label:'Chevalet de comptoir A5' });

/* ── Vidéos motion design ───────────────────────────────────────── */
add('motion', POST,               'navy',   ['annonce','defi'],       { motion:true, label:'Vidéo carrée' });
add('motion', POST,               'blanc',  ['readhesion','valeurs'], { motion:true, label:'Vidéo carrée' });
add('motion', POST,               'pastel', ['benevole'],             { motion:true, label:'Vidéo carrée' });

/* Reels Instagram : format vertical, photos à l'appui. */
add('motion', STORY,              'navy',   ['annonce','defi'],       { motion:true, label:'Reel Instagram' });
add('motion', STORY,              'blanc',  ['readhesion'],           { motion:true, label:'Reel Instagram' });
add('motion', STORY,              'photo',  ['annonce','valeurs'],    { motion:true, label:'Reel Instagram — photos' });
add('motion', STORY,              'mosaic', ['defi'],                 { motion:true, label:'Reel Instagram — mosaïque' });

/* Bande-annonce : 2,5 s, à coller en intro ou en outro d'une autre vidéo. */
add('motion', STORY,              'navy',   ['teaser'],               { motion:true, teaser:true, label:'Bande-annonce (intro / outro)' });
add('motion', POST,               'navy',   ['teaser'],               { motion:true, teaser:true, label:'Bande-annonce (intro / outro)' });
add('motion', { w:1920, h:1080 }, 'navy',   ['teaser'],               { motion:true, teaser:true, label:'Bande-annonce 16:9' });

/* Format long : présentation posée de l'association et de ses valeurs. */
add('motion', STORY,              'navy',   ['valeurs'],              { motion:true, long:true, label:'Présentation longue (12 s)' });
add('motion', { w:1920, h:1080 }, 'navy',   ['annonce','valeurs'],    { motion:true, long:true, label:'Présentation longue 16:9 (12 s)' });
add('motion', { w:1920, h:1080 }, 'blanc',  ['pourquoi'],             { motion:true, long:true, label:'Présentation longue 16:9 (12 s)' });

var STYLE_LABEL = {
  blanc:'Fond blanc', navy:'Fond navy', typo:'Typo XXL', photo:'Photo',
  pastel:'Pastel', mosaic:'Mosaïque', blocs:'Blocs', bulle:'Bulle photo',
  moderne:'Moderne — bandeau & actions', trio:'Trio — triptyque photo'
};
var GROUP_LABEL = { post:'Post', story:'Story', affiche:'Affiche', flyer:'Flyer', banner:'Bannière', motion:'Vidéo', stand:'Stand' };

/** Compose le SVG d'un visuel. `embed` = ressources en base64 (export). */
function buildSvg(card, embed){
  var uid = '-' + card.id + (embed ? 'x' : '');
  PIDX = card.pidx || 0;
  DECAL = DECALAGE[card.id] || 0;
  EMBED_PHOTOS = !!embed;
  if (card.verso)  return renderVerso(card.style, card.variant, uid, embed);
  if (card.style === 'moderne') return renderModerne(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.style === 'trio')    return renderTrio(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.style === 'bulle' || card.stand) return renderBulle(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.style === 'mosaic') return renderMosaic(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.style === 'blocs' && !card.wide) return renderBlocs(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.wide)   return renderWide(card.style, card.variant, uid, embed, card.w, card.h);
  if (card.paper)  return renderPaper(card.style, card.variant, uid, embed);
  if (card.h === 1920) return renderStory(card.style, card.variant, uid, embed);
  return renderPost(card.style, card.variant, uid, embed);
}

/* =====================================================================
   14. Motion design — storyboard paramétrique
   Chaque élément a sa fenêtre d'apparition ; `motionFrame(card, t)` rend
   l'image à l'instant t ∈ [0,1]. Le même code sert à l'aperçu animé dans
   la page et à l'encodage de la vidéo, image par image.
   ===================================================================== */
var MOTION_DUR = 5.0;   /* secondes d'animation, par défaut */
/**
 * Durée d'une vidéo donnée. Une bande-annonce doit tenir en intro ou en outro
 * d'un autre montage : elle est courte. Une présentation, à l'inverse, doit
 * laisser le temps de lire.
 */
function motionDur(card){
  if (card && card.teaser) return 2.5;
  if (card && card.long)   return 12.0;
  return MOTION_DUR;
}
var MOTION_HOLD = 0.7;  /* maintien de la dernière image */
var MOTION_FPS = 25;

function ease(x){ x = x < 0 ? 0 : (x > 1 ? 1 : x); return 1 - Math.pow(1 - x, 3); }
/** Progression d'un segment [a,b] de la timeline. */
function seg(t, a, b){ return ease((t - a) / (b - a)); }
/** Rebond léger pour les apparitions « pop ». */
function pop(x){ return x >= 1 ? 1 : 1 + 0.14 * Math.sin(Math.PI * ease(x)) ; }

/** Enveloppe animée : opacité, translation, échelle autour d'un point. */
function anim(inner, o){
  var tr = '';
  if (o.dx || o.dy) tr += 'translate(' + (o.dx || 0).toFixed(2) + ',' + (o.dy || 0).toFixed(2) + ') ';
  if (o.k !== undefined && Math.abs(o.k - 1) > 0.0005) {
    tr += 'translate(' + o.cx.toFixed(2) + ',' + o.cy.toFixed(2) + ') scale(' + o.k.toFixed(4) + ') '
        + 'translate(' + (-o.cx).toFixed(2) + ',' + (-o.cy).toFixed(2) + ') ';
  }
  return '<g' + (tr ? ' transform="' + tr + '"' : '')
    + (o.op !== undefined ? ' opacity="' + Math.max(0, Math.min(1, o.op)).toFixed(3) + '"' : '') + '>'
    + inner + '</g>';
}

function motionFrame(card, t, embed){
  var W = card.w, H = card.h, uid = '-m' + card.id + (embed ? 'x' : '');
  var style = card.style, p = pal(style), v = V(card.variant);
  PIDX = card.pidx || 0;
  DECAL = DECALAGE[card.id] || 0;
  EMBED_PHOTOS = !!embed;
  var cx = W / 2, M = W * 0.07;
  var lien = OPT.url.replace(/^https?:\/\//, '');
  var s = bgMarkup(style, W, H, uid, embed);

  /**
   * Mesures dérivées d'une unité U. On les recalcule en réduisant U jusqu'à ce
   * que la pile tienne dans la hauteur : sans cette boucle, les formats les
   * plus courts (carré, 16:9) poussaient le lien et le compte hors du cadre.
   */
  function metrics(U){
    var m = { U:U, lg:U * 0.150, orgS:U * 0.030, tagS:U * 0.022, kickS:U * 0.026, seasS:U * 0.034 };
    m.tsz = U * 0.132;
    for (var i = 0; i < v.t.length; i++) m.tsz = Math.min(m.tsz, fitSize(v.t[i], W * 0.84, m.tsz, 800, FAM_AG));
    m.lh = m.tsz * 1.06;
    m.infoS = U * 0.023;
    m.ctaS  = U * 0.058;
    m.urlS  = U * 0.028;
    m.instaS = U * 0.024;
    m.co = calloutType(0, 0, W - 2 * M, v.ctaShort || v.cta, null, p.accent, p.fg, U);
    m.total = m.lg + U * 0.060 + m.tagS * 1.5 + U * 0.072
            + U * 0.108 + (v.t.length - 1) * m.lh + U * 0.082
            + U * 0.040 + U * 0.060 + m.co.h + U * 0.058 + U * 0.046;
    return m;
  }
  var m = metrics(Math.min(W, H));
  for (var pass = 0; pass < 5 && m.total > H * 0.90; pass++) m = metrics(m.U * (H * 0.90) / m.total);
  var U = m.U;
  var y = Math.max(H * 0.045, (H - m.total) * 0.45);

  /* Filet tricolore : essuyage depuis la gauche (haut) et la droite (bas). */
  var bar = U * 0.013, wipe = seg(t, 0, 0.32);
  s += '<defs><clipPath id="wt' + uid + '"><rect x="0" y="0" width="' + (W * wipe) + '" height="' + bar + '"/></clipPath>'
     + '<clipPath id="wb' + uid + '"><rect x="' + (W * (1 - wipe)) + '" y="' + (H - bar) + '" width="' + (W * wipe) + '" height="' + bar + '"/></clipPath></defs>';
  s += '<g clip-path="url(#wt' + uid + ')">' + triBar(0, 0, W, bar) + '</g>';
  s += '<g clip-path="url(#wb' + uid + ')">' + triBar(0, H - bar, W, bar) + '</g>';

  /* 1. Logo — zoom léger + fondu */
  var a1 = seg(t, 0.00, 0.20);
  s += anim(logoImg(cx - m.lg / 2, y, m.lg, embed, style === 'navy' || style === 'photo'),
            { op:a1, k:0.72 + 0.28 * pop(a1), cx:cx, cy:y + m.lg / 2 });
  y += m.lg;

  /* 2. Identité */
  var a2 = seg(t, 0.10, 0.28);
  y += U * 0.060;
  s += anim(TFit(cx, y, ORG, W * 0.86, { size:m.orgS, fill:p.fg, anchor:'middle', ls:m.orgS * 0.055 }), { op:a2, dy:(1 - a2) * U * 0.03 });
  y += m.tagS * 1.5;
  s += anim(TFit(cx, y, TAG, W * 0.86, { size:m.tagS, w:500, fill:p.fg2, anchor:'middle' }), { op:a2, dy:(1 - a2) * U * 0.03 });

  /* 3. Accroche */
  var a3 = seg(t, 0.20, 0.36);
  y += U * 0.072;
  s += anim(TFit(cx, y, v.kicker, W * 0.86, { size:m.kickS, fill:p.kicker, anchor:'middle', ls:m.kickS * 0.17 }),
            { op:a3, dy:(1 - a3) * U * 0.025 });

  /* 4. Titre en cascade + soulignement jaune qui se déploie */
  y += U * 0.108;
  var lastY = y + (v.t.length - 1) * m.lh;
  var uw = measure(v.t[v.t.length - 1], m.tsz, 800, FAM_AG), au = seg(t, 0.50, 0.66);
  s += '<rect x="' + (cx - uw / 2) + '" y="' + (lastY + m.tsz * 0.14) + '" width="' + (uw * au)
     + '" height="' + (m.tsz * 0.13) + '" fill="' + C.yellow + '"/>';
  for (var j = 0; j < v.t.length; j++) {
    var aj = seg(t, 0.28 + j * 0.08, 0.46 + j * 0.08);
    s += anim(T(cx, y + j * m.lh, v.t[j], { size:m.tsz, f:'ag', w:800, fill:p.fg, anchor:'middle' }),
              { op:aj, dy:(1 - aj) * U * 0.045 });
  }
  y = lastY;

  /* 5. Saison */
  var a5 = seg(t, 0.46, 0.60);
  y += U * 0.082;
  s += anim(T(cx, y, OPT.season, { size:m.seasS, fill:p.alt, anchor:'middle', f:'ag', w:800, ls:2 }),
            { op:a5, k:0.86 + 0.14 * pop(a5), cx:cx, cy:y });

  /* 6. Ligne d'informations (une seule ligne : plus d'étiquettes arrondies) */
  y += U * 0.040;
  s += anim(infoLine(cx, y, v.pills || [], W * 0.88, m.infoS, p.fg2, 'middle'), { op:seg(t, 0.56, 0.70) });

  /* 7. Appel à l'action typographique, centré, avec pulsation discrète */
  var a7 = seg(t, 0.68, 0.82), puls = t > 0.86 ? 1 + 0.014 * Math.sin((t - 0.86) * 26) : 1;
  y += U * 0.060;
  var co = calloutType(cx - m.co.w / 2, y, W - 2 * M, v.ctaShort || v.cta, null, p.accent, p.fg, U);
  s += anim(co.svg, { op:a7, k:(0.9 + 0.1 * pop(a7)) * puls, cx:cx, cy:y + co.h / 2 });

  /* 8. QR, aligné sur l'appel à l'action */
  if (OPT.qr && ASSETS.qr) {
    var qs = U * 0.115, qx = W - M - qs, qy = y + co.h / 2 - qs / 2, aq = seg(t, 0.84, 0.96);
    s += anim('<rect x="' + (qx - qs * 0.1) + '" y="' + (qy - qs * 0.1) + '" width="' + (qs * 1.2) + '" height="' + (qs * 1.2)
            + '" fill="#FFFFFF"/>'
            + '<image x="' + qx + '" y="' + qy + '" width="' + qs + '" height="' + qs
            + '" href="' + ASSETS.qr + '" xlink:href="' + ASSETS.qr + '"/>',
              { op:aq, k:0.7 + 0.3 * pop(aq), cx:qx + qs / 2, cy:qy + qs / 2 });
  }
  y += co.h;

  /* 9. Lien + réseau */
  y += U * 0.058;
  s += anim(TFit(cx, y, lien, W * 0.8, { size:m.urlS, fill:p.accent, anchor:'middle', w:700 }), { op:seg(t, 0.78, 0.92) });
  y += U * 0.046;
  s += anim(instaTag(cx, y, m.instaS, p.foot, { anchor:'middle' }), { op:seg(t, 0.88, 1.0) });

  return svgWrap(W, H, uid, s, embed);
}

/* =====================================================================
   15. Exports — PNG, PDF, vidéo
   ===================================================================== */
var MM_PT = 72 / 25.4;
/* A3 est à 220 dpi : à 300 dpi le canvas dépasse la limite de surface de
   certains navigateurs. 220 dpi reste largement suffisant pour une affiche. */
var PAPER = {
  A5: { mm:[148, 210], dpi:300 },
  A4: { mm:[210, 297], dpi:300 },
  A3: { mm:[297, 420], dpi:220 }
};
function paperPx(fmt){ var d = PAPER[fmt]; return [Math.round(d.mm[0] / 25.4 * d.dpi), Math.round(d.mm[1] / 25.4 * d.dpi)]; }
function paperPt(fmt){ var d = PAPER[fmt]; return [d.mm[0] * MM_PT, d.mm[1] * MM_PT]; }

/* Le navigateur rasterise un SVG chargé dans une <img> à sa taille
   intrinsèque : on réécrit width/height à la taille de sortie pour un rendu
   net, le viewBox conservant les coordonnées de mise en page. */
function sizeSvg(svg, w, h){
  return svg.replace(/width="[\d.]+" height="[\d.]+"/, 'width="' + w + '" height="' + h + '"');
}
/** Un SVG chargé dans une <img> est parsé en XML STRICT : la moindre erreur
    de syntaxe le fait échouer silencieusement. On rejoue donc le parsing avec
    DOMParser pour remonter la cause exacte au lieu d'un message opaque. */
function xmlProblem(svg){
  try {
    var doc = new DOMParser().parseFromString(svg, 'image/svg+xml');
    var err = doc.querySelector('parsererror');
    if (err) return err.textContent.replace(/\s+/g, ' ').slice(0, 300);
  } catch (e) { return e.message; }
  return null;
}
/** SVG → data-URI base64 (UTF-8).
    On n'utilise PAS de blob: URL : la CSP du site autorise `img-src 'self' data:`
    mais pas `blob:`, une <img src="blob:…"> serait donc bloquée. Le base64
    est aussi bien plus compact que l'encodage pour-cent sur du texte accentué. */
function svgDataUri(svg){
  return 'data:image/svg+xml;base64,' + bufToB64(new TextEncoder().encode(svg).buffer);
}
function loadSvgImage(svg){
  return new Promise(function(resolve, reject){
    var img = new Image();
    img.onload  = function(){ resolve({ img:img, url:null }); };
    img.onerror = function(){
      var why = xmlProblem(svg);
      reject(new Error(why ? 'SVG invalide — ' + why : "Le navigateur a refusé de charger le visuel."));
    };
    img.src = svgDataUri(svg);
  });
}
async function rasterize(svg, outW, outH, reuse){
  var r = await loadSvgImage(sizeSvg(svg, outW, outH));
  var cv = reuse || document.createElement('canvas');
  cv.width = outW; cv.height = outH;
  var ctx = cv.getContext('2d');
  ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0, 0, outW, outH);
  ctx.drawImage(r.img, 0, 0, outW, outH);
  return cv;
}
function saveBlob(blob, filename){
  var a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(function(){ URL.revokeObjectURL(a.href); }, 6000);
}
function slug(s){
  return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}
function fileBase(card){
  return 'mjadhesion-' + card.group + '-' + slug(card.variant) + '-' + card.style
       + (card.suffix ? '-' + card.suffix : '');
}

/** SVG d'un visuel : dernière image du storyboard pour les vidéos. */
function svgFor(card, embed){
  TUILES = 0;
  TUILES_RESERVE = 0;
  return card.motion ? motionFrame(card, 1, embed) : buildSvg(card, embed);
}


async function exportPng(card, outW, outH, suffix){
  var cv = await rasterize(svgFor(card, true), outW, outH);
  await new Promise(function(res){
    cv.toBlob(function(b){ saveBlob(b, fileBase(card) + (suffix || '') + '.png'); res(); }, 'image/png');
  });
}

/** PDF minimal (1 page, 1 image JPEG DCTDecode) — aucune librairie externe.
    `box` = {x, y, w, h} en points : position de l'image sur la page. */
function jpegToPdf(jpegDataUrl, pxW, pxH, ptW, ptH, box){
  box = box || { x:0, y:0, w:ptW, h:ptH };
  var bin = atob(jpegDataUrl.split(',')[1]);
  var objs = [];
  objs.push('<< /Type /Catalog /Pages 2 0 R >>');
  objs.push('<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
  objs.push('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + ptW.toFixed(2) + ' ' + ptH.toFixed(2) + ']'
    + ' /Resources << /XObject << /Im0 5 0 R >> >> /Contents 4 0 R >>');
  var cs = 'q ' + box.w.toFixed(2) + ' 0 0 ' + box.h.toFixed(2) + ' '
    + box.x.toFixed(2) + ' ' + box.y.toFixed(2) + ' cm /Im0 Do Q';
  objs.push('<< /Length ' + cs.length + ' >>\nstream\n' + cs + '\nendstream');
  objs.push('<< /Type /XObject /Subtype /Image /Width ' + pxW + ' /Height ' + pxH
    + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + bin.length + ' >>\nstream\n' + bin + '\nendstream');

  var out = '%PDF-1.4\n%\xE2\xE3\xCF\xD3\n', offsets = [];
  for (var i = 0; i < objs.length; i++) {
    offsets.push(out.length);
    out += (i + 1) + ' 0 obj\n' + objs[i] + '\nendobj\n';
  }
  var xref = out.length;
  out += 'xref\n0 ' + (objs.length + 1) + '\n0000000000 65535 f \n';
  for (var j = 0; j < offsets.length; j++) out += ('0000000000' + offsets[j]).slice(-10) + ' 00000 n \n';
  out += 'trailer\n<< /Size ' + (objs.length + 1) + ' /Root 1 0 R >>\nstartxref\n' + xref + '\n%%EOF';

  var bytes = new Uint8Array(out.length);
  for (var k = 0; k < out.length; k++) bytes[k] = out.charCodeAt(k) & 0xFF;
  return new Blob([bytes], { type:'application/pdf' });
}

async function exportPdf(card, fmt){
  var svg = svgFor(card, true), pt, px, box, cv;
  if (card.paper) {
    /* Format papier : le visuel occupe toute la page (même ratio A). */
    px = paperPx(fmt); pt = paperPt(fmt);
    cv = await rasterize(svg, px[0], px[1]);
    box = { x:0, y:0, w:pt[0], h:pt[1] };
  } else {
    /* Visuel web : page A4 dans l'orientation adaptée, visuel centré avec
       10 mm de marge — jamais déformé. */
    var a4 = paperPt('A4'), landscape = card.w > card.h;
    pt = landscape ? [a4[1], a4[0]] : [a4[0], a4[1]];
    var marg = 10 * MM_PT;
    var k = Math.min((pt[0] - 2 * marg) / card.w, (pt[1] - 2 * marg) / card.h);
    box = { w:card.w * k, h:card.h * k, x:0, y:0 };
    box.x = (pt[0] - box.w) / 2; box.y = (pt[1] - box.h) / 2;
    var scale = (box.w / 72 * 300) / card.w;   /* 300 dpi de la taille imprimée */
    cv = await rasterize(svg, Math.round(card.w * scale), Math.round(card.h * scale));
    px = [cv.width, cv.height];
  }
  saveBlob(jpegToPdf(cv.toDataURL('image/jpeg', 0.94), px[0], px[1], pt[0], pt[1], box),
           fileBase(card) + '-' + (card.paper ? fmt : 'A4') + '.pdf');
}

/** Format vidéo le mieux supporté par le navigateur. */
function pickVideoMime(){
  var cands = ['video/mp4;codecs=avc1.42E01E', 'video/mp4',
               'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm'];
  if (!window.MediaRecorder) return null;
  for (var i = 0; i < cands.length; i++) {
    if (MediaRecorder.isTypeSupported(cands[i])) return cands[i];
  }
  return null;
}
/**
 * Export vidéo en deux temps :
 *  1. rendu de chaque image du storyboard en JPEG (aussi lent qu'il faut) ;
 *  2. relecture à la cadence réelle dans un canvas enregistré par
 *     MediaRecorder — la durée de la vidéo est donc exacte.
 */
async function exportVideo(card, onProgress){
  var mime = pickVideoMime();
  if (!mime) throw new Error("Ce navigateur ne sait pas enregistrer de vidéo (essaie Chrome ou Edge).");

  var W = card.w, H = card.h, n = Math.round(MOTION_FPS * motionDur(card)), frames = [];
  var work = document.createElement('canvas');
  for (var i = 0; i < n; i++) {
    var cv = await rasterize(motionFrame(card, i / (n - 1), true), W, H, work);
    frames.push(await new Promise(function(r){ cv.toBlob(r, 'image/jpeg', 0.93); }));
    if (onProgress && i % 8 === 0) onProgress('Rendu ' + (i + 1) + '/' + n + ' images…');
  }

  var out = document.createElement('canvas');
  out.width = W; out.height = H;
  var ctx = out.getContext('2d');
  ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0, 0, W, H);

  var stream = out.captureStream(MOTION_FPS);
  var rec = new MediaRecorder(stream, { mimeType:mime, videoBitsPerSecond:Math.min(14e6, Math.round(W * H * 0.11)) });
  var chunks = [];
  rec.ondataavailable = function(e){ if (e.data && e.data.size) chunks.push(e.data); };
  var done = new Promise(function(r){ rec.onstop = r; });
  rec.start();

  if (onProgress) onProgress('Encodage de la vidéo…');
  var t0 = performance.now();
  for (var f = 0; f < n; f++) {
    var bm = await createImageBitmap(frames[f]);
    var wait = (t0 + f * 1000 / MOTION_FPS) - performance.now();
    if (wait > 0) await new Promise(function(r){ setTimeout(r, wait); });
    ctx.drawImage(bm, 0, 0);
    if (bm.close) bm.close();
  }
  await new Promise(function(r){ setTimeout(r, MOTION_HOLD * 1000); });
  rec.stop();
  await done;
  stream.getTracks().forEach(function(tr){ tr.stop(); });

  var ext = mime.indexOf('mp4') >= 0 ? '.mp4' : '.webm';
  saveBlob(new Blob(chunks, { type:mime }), fileBase(card) + ext);
  return ext;
}

/* =====================================================================
   16. Galerie
   ===================================================================== */
function cardMarkup(card){
  var tags = '<b>' + (card.label || GROUP_LABEL[card.group]) + '</b><b>' + STYLE_LABEL[card.style] + '</b>'
    + '<b class="px">' + card.w + ' × ' + card.h + '</b>';
  var rows = '';
  if (card.motion) {
    rows += '<div class="dlrow"><u>Vidéo</u>'
      + '<button class="btn ghost" data-act="play"><i class="fas fa-play"></i> Aperçu animé</button>'
      + '<button class="btn pdf" data-act="video"><i class="fas fa-film"></i> Télécharger la vidéo</button></div>';
    rows += '<div class="dlrow"><u>PNG</u>'
      + '<button class="btn png" data-act="png" data-fmt="x1">Image finale ' + card.w + ' px</button></div>';
  } else if (card.paper) {
    rows += '<div class="dlrow"><u>PNG</u>'
      + '<button class="btn png" data-act="png" data-fmt="A5">A5 ' + PAPER.A5.dpi + ' dpi</button>'
      + '<button class="btn png" data-act="png" data-fmt="A4">A4 ' + PAPER.A4.dpi + ' dpi</button>'
      + '<button class="btn png" data-act="png" data-fmt="A3">A3 ' + PAPER.A3.dpi + ' dpi</button></div>';
    rows += '<div class="dlrow"><u>PDF</u>'
      + '<button class="btn pdf" data-act="pdf" data-fmt="A5"><i class="fas fa-file-pdf"></i> A5</button>'
      + '<button class="btn pdf" data-act="pdf" data-fmt="A4"><i class="fas fa-file-pdf"></i> A4</button>'
      + '<button class="btn pdf" data-act="pdf" data-fmt="A3"><i class="fas fa-file-pdf"></i> A3</button></div>';
  } else {
    rows += '<div class="dlrow"><u>PNG</u>'
      + '<button class="btn png" data-act="png" data-fmt="x1">' + card.w + ' px</button>'
      + '<button class="btn png" data-act="png" data-fmt="x2">×2</button>'
      + '<button class="btn pdf" data-act="pdf" data-fmt="A4"><i class="fas fa-file-pdf"></i> PDF</button></div>';
  }
  /* Rangée masquée par défaut : on ne sait qu'après le rendu si ce visuel
     montre des photos (voir drawCard). */
  var photoRow = '<div class="dlrow photorow" hidden><u>Photo</u>'
    + '<button class="btn ghost" data-act="photo-prev" title="Photo précédente">&#8249;</button>'
    + '<span class="pnum"></span>'
    + '<button class="btn ghost" data-act="photo-next" title="Photo suivante">&#8250;</button></div>';

  return '<article class="card" data-id="' + card.id + '" data-group="' + card.group + '" data-style="' + card.style + '">'
    + '<div class="art" data-act="zoom"><div class="ph"></div></div>'
    + '<div class="meta"><h3>' + esc(card.name) + '</h3><div class="tags">' + tags + '</div></div>'
    + '<div class="dl">' + photoRow + rows + '</div>'
    + '</article>';
}

function renderGallery(){
  var byGroup = { post:'', story:'', affiche:'', flyer:'', banner:'', motion:'' };
  CARDS.forEach(function(c){ byGroup[c.group] += cardMarkup(c); });
  Object.keys(byGroup).forEach(function(g){
    var el = document.getElementById('gal-' + g);
    if (el) el.innerHTML = byGroup[g];
  });
}

/* Rendu paresseux : un aperçu n'est composé que lorsqu'il approche de
   l'écran — indispensable avec une soixantaine de visuels. */
var drawn = {}, io = null;
function drawCard(id){
  var card = cardById(id), racine = document.querySelector('.card[data-id="' + id + '"]');
  var el = racine && racine.querySelector('.art');
  if (!card || !el) return;
  el.innerHTML = svgFor(card, false);
  drawn[id] = true;

  var rang = racine.querySelector('.photorow');
  if (!rang) return;
  var total = TUILES_RESERVE;
  if (!TUILES || total < 2) { rang.hidden = true; return; }
  rang.hidden = false;
  /* Le cran affiché est celui du sélecteur, pas le rang interne du gabarit :
     à l'ouverture le visuel est sur son choix par défaut, donc « 1 / n ». */
  rang.querySelector('.pnum').textContent = (tourne(DECALAGE[id] || 0, total) + 1) + ' / ' + total
    + (TUILES > 1 ? ' (' + TUILES + ' tuiles)' : '');
}
function setupLazy(){
  if (io) io.disconnect();
  drawn = {};
  io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if (e.isIntersecting) { drawCard(e.target.dataset.id); io.unobserve(e.target); }
    });
  }, { rootMargin:'400px 0px' });
  document.querySelectorAll('.card').forEach(function(el){ io.observe(el); });
}
/** Redessine les aperçus déjà visibles (après changement d'option). */
function refreshArt(){
  Object.keys(drawn).forEach(function(id){ drawCard(id); });
}
function cardById(id){
  for (var i = 0; i < CARDS.length; i++) if (CARDS[i].id === id) return CARDS[i];
  return null;
}

/* =====================================================================
   17. Interactions
   ===================================================================== */
var filters = { group:'all', style:'all' };
function applyFilters(){
  document.querySelectorAll('.card').forEach(function(el){
    var okG = filters.group === 'all' || el.dataset.group === filters.group;
    var okS = filters.style === 'all' || el.dataset.style === filters.style;
    el.hidden = !(okG && okS);
  });
  document.querySelectorAll('section.block').forEach(function(sec){
    sec.hidden = sec.querySelectorAll('.card:not([hidden])').length === 0;
  });
}
function visibleCards(){
  return CARDS.filter(function(c){
    var el = document.querySelector('.card[data-id="' + c.id + '"]');
    return el && !el.hidden;
  });
}

document.querySelectorAll('.chip').forEach(function(btn){
  btn.addEventListener('click', function(){
    var f = btn.dataset.filter;
    document.querySelectorAll('.chip[data-filter="' + f + '"]').forEach(function(b){ b.classList.remove('on'); });
    btn.classList.add('on');
    filters[f] = btn.dataset.value;
    applyFilters();
  });
});

/* Aperçu animé : une seule carte à la fois, ~30 images/s. */
var playing = null;
function stopPreview(){
  if (!playing) return;
  cancelAnimationFrame(playing.raf);
  drawCard(playing.id);
  playing = null;
}
function playPreview(card, el){
  stopPreview();
  var t0 = performance.now(), last = -1;
  playing = { id:card.id, raf:0 };
  (function step(now){
    var t = Math.min(1, (now - t0) / (motionDur(card) * 1000));
    if (t - last > 0.028 || t === 1) { el.innerHTML = motionFrame(card, t, false); last = t; }
    if (t < 1) playing.raf = requestAnimationFrame(step);
    else playing = null;
  })(t0);
}

document.addEventListener('click', async function(e){
  var btn = e.target.closest('[data-act]');
  if (!btn) return;
  var cardEl = btn.closest('.card');
  if (!cardEl) return;
  var card = cardById(cardEl.dataset.id);
  if (!card) return;
  var act = btn.dataset.act;

  if (act === 'zoom') {
    if (!drawn[card.id]) drawCard(card.id);
    document.getElementById('lb-box').innerHTML = svgFor(card, false);
    document.getElementById('lightbox').classList.add('on');
    return;
  }
  if (act === 'play') { playPreview(card, cardEl.querySelector('.art')); return; }

  /* Changement de photo : un simple décalage, puis on recompose ce visuel
     seul — inutile de refaire la galerie entière. */
  if (act === 'photo-prev' || act === 'photo-next') {
    DECALAGE[card.id] = (DECALAGE[card.id] || 0) + (act === 'photo-next' ? 1 : -1);
    drawCard(card.id);
    return;
  }

  btn.disabled = true;
  try {
    if (act === 'png') {
      if (card.paper) {
        var px = paperPx(btn.dataset.fmt);
        await exportPng(card, px[0], px[1], '-' + btn.dataset.fmt + '-' + PAPER[btn.dataset.fmt].dpi + 'dpi');
      } else {
        var m = btn.dataset.fmt === 'x2' ? 2 : 1;
        await exportPng(card, card.w * m, card.h * m, m === 2 ? '-x2' : '');
      }
      toast('Téléchargement lancé.');
    } else if (act === 'pdf') {
      await exportPdf(card, btn.dataset.fmt);
      toast('Téléchargement lancé.');
    } else if (act === 'video') {
      var ext = await exportVideo(card, toast);
      toast('Vidéo ' + ext.replace('.', '').toUpperCase() + ' téléchargée.');
    }
  } catch (err) {
    console.error(err);
    toast('Erreur : ' + err.message);
  }
  btn.disabled = false;
});

document.getElementById('lb-close').addEventListener('click', function(){
  document.getElementById('lightbox').classList.remove('on');
});
document.getElementById('lightbox').addEventListener('click', function(e){
  if (e.target === this) this.classList.remove('on');
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') { document.getElementById('lightbox').classList.remove('on'); stopPreview(); }
});

/* Téléchargements groupés (séquentiels, pour ne pas saturer le navigateur) */
document.getElementById('btn-all-png').addEventListener('click', async function(){
  var list = visibleCards().filter(function(c){ return !c.motion; }), btn = this;
  if (!list.length) { toast('Aucun visuel dans la sélection.'); return; }
  btn.disabled = true;
  for (var i = 0; i < list.length; i++) {
    var c = list[i];
    toast('PNG ' + (i + 1) + '/' + list.length + '…');
    if (c.paper) { var px = paperPx('A4'); await exportPng(c, px[0], px[1], '-A4-300dpi'); }
    else await exportPng(c, c.w, c.h, '');
    await new Promise(function(r){ setTimeout(r, 320); });
  }
  btn.disabled = false;
  toast(list.length + ' visuels téléchargés.');
});
document.getElementById('btn-all-pdf').addEventListener('click', async function(){
  var list = visibleCards().filter(function(c){ return c.paper; }), btn = this;
  if (!list.length) { toast('Aucun format papier dans la sélection.'); return; }
  btn.disabled = true;
  for (var i = 0; i < list.length; i++) {
    toast('PDF ' + (i + 1) + '/' + list.length + '…');
    await exportPdf(list[i], 'A4');
    await new Promise(function(r){ setTimeout(r, 320); });
  }
  btn.disabled = false;
  toast(list.length + ' PDF téléchargés.');
});

/* Panneau d'options */
var deb;
function onOptChange(regenQr){
  clearTimeout(deb);
  deb = setTimeout(function(){ if (regenQr) makeQr(); refreshArt(); }, 240);
}
document.getElementById('opt-season').addEventListener('input', function(){ OPT.season = this.value.toUpperCase(); onOptChange(false); });
document.getElementById('opt-url').addEventListener('input', function(){ OPT.url = this.value.trim(); onOptChange(true); });
document.getElementById('opt-price').addEventListener('change', function(){ OPT.price = this.checked; refreshArt(); });
document.getElementById('opt-qr').addEventListener('change', function(){ OPT.qr = this.checked; refreshArt(); });
var champActions = document.getElementById('opt-actions');
if (champActions) {
  champActions.value = actionsEnTexte(ACTIONS_DEFAUT);
  champActions.addEventListener('input', function(){
    ACTIONS = lireActions(this.value);
    refreshArt();
  });
}
var champTextes = document.getElementById('opt-textes');
if (champTextes) {
  champTextes.addEventListener('input', function(){
    TEXTES_LIBRES = {};
    String(this.value).split(String.fromCharCode(10)).forEach(function(ligne){
      var morceaux = ligne.split('|');
      var avant = (morceaux.shift() || '').trim();
      var apres = morceaux.join('|').trim();
      if (avant && apres) TEXTES_LIBRES[avant] = apres;
    });
    refreshArt();
  });
}
document.getElementById('opt-qrcolor').addEventListener('change', function(){
  OPT.qrColor = this.value; makeQr(); refreshArt();
});
document.getElementById('opt-photo').addEventListener('change', function(){
  var files = Array.prototype.slice.call(this.files || []);
  if (!files.length) return;
  Promise.all(files.map(function(f){
    return new Promise(function(res){
      var fr = new FileReader();
      fr.onload = function(){ res(fr.result); };
      fr.readAsDataURL(f);
    });
  })).then(function(urls){
    OPT.photos = urls;
    OPT.photo = urls[0];
    document.getElementById('photo-name').textContent =
      urls.length === 1 ? files[0].name : urls.length + ' photos sélectionnées';
    refreshArt();
    toast(urls.length + ' photo(s) intégrée(s) aux visuels Photo et Mosaïque.');
  });
});

/* =====================================================================
   18. QR code (librairie locale) → data-URL réutilisable dans les SVG
   ===================================================================== */
function makeQr(){
  var host = document.getElementById('hidden-qr');
  host.innerHTML = '';
  if (!OPT.url) { ASSETS.qr = null; return; }
  try {
    new QRCode(host, {
      text: OPT.url, width: 560, height: 560,
      colorDark: OPT.qrColor, colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });
    var cv = host.querySelector('canvas'), im = host.querySelector('img');
    ASSETS.qr = cv ? cv.toDataURL('image/png') : (im ? im.src : null);
  } catch (e) {
    console.warn('QR indisponible', e);
    ASSETS.qr = null;
  }
}

/** ===================================================================
    AUDIT DE MISE EN PAGE
    Passe chaque visuel au crible pour détecter, sans œil humain :
      · les éléments qui sortent du cadre (donc coupés à l'export) ;
      · les textes qui se chevauchent.
    Les cercles décoratifs (anneaux du logo, halos) débordent volontairement :
    ils sont ignorés, comme tout ce qui vit dans un <defs>.
    Accessible via ?audit=1.
    =================================================================== */
function textBox(el){
  var size = parseFloat(el.getAttribute('font-size')) || 0;
  var str  = el.textContent || '';
  if (!size || !str.trim()) return null;
  var weight = parseFloat(el.getAttribute('font-weight')) || 400;
  var fam    = (el.getAttribute('font-family') || '').indexOf('AllRound') === 0 ? FAM_AG : FAM_GS;
  var ls     = parseFloat(el.getAttribute('letter-spacing')) || 0;
  var w = measure(str, size, weight, fam) + ls * Math.max(0, str.length - 1);
  var x = parseFloat(el.getAttribute('x')) || 0;
  var y = parseFloat(el.getAttribute('y')) || 0;
  var a = el.getAttribute('text-anchor');
  var x0 = a === 'middle' ? x - w / 2 : (a === 'end' ? x - w : x);
  /* Hauteur utile approchée : hampes hautes et basses de la police. */
  return { x0:x0, x1:x0 + w, y0:y - size * 0.78, y1:y + size * 0.24, s:str, size:size };
}

var _svgMesure = null;

function auditCard(card){
  var svg = svgFor(card, false);
  var doc = new DOMParser().parseFromString(svg, 'image/svg+xml');
  if (doc.querySelector('parsererror')) return ['SVG invalide'];
  var W = card.w, H = card.h, pb = [], texts = [];
  var TOL = 2;

  function inDefs(el){
    for (var n = el.parentNode; n && n.nodeType === 1; n = n.parentNode) {
      var t = (n.tagName || '').toLowerCase();
      if (t === 'defs' || t === 'pattern' || t === 'clippath') return true;
    }
    return false;
  }

  /**
   * Boîte réellement peinte : la géométrie de l'élément, rognée par les
   * clip-path de ses ancêtres. Une photo recadrée est volontairement plus
   * grande que sa tuile — c'est la tuile qui se voit. Sans ce rognage, chaque
   * tuile recadrée était signalée « hors cadre » et le rapport devenait
   * illisible, donc inutile ; en l'ignorant tout court, on aurait cessé de
   * voir les textes qu'une photo recouvre vraiment.
   */
  /* Emprise d'un clipPath. Les bulles sont decrites par un `path` : plutot
     que de re-implementer un parseur de tracé, on demande sa vraie boîte au
     navigateur (getBBox), en greffant une copie du tracé dans un SVG hors
     écran. Un clipPath illisible renvoie null et l'élément n'est alors pas
     rogné — mieux vaut un faux positif qu'un débordement passé sous silence. */
  function boiteClip(cp){
    if (!cp) return null;
    if (!_svgMesure) {
      _svgMesure = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      _svgMesure.setAttribute('style', 'position:absolute;left:-9999px;top:0;width:1px;height:1px');
      document.body.appendChild(_svgMesure);
    }
    var boite = null;
    Array.prototype.forEach.call(cp.children, function(f){
      var n = document.importNode(f, true), b2 = null;
      _svgMesure.appendChild(n);
      try {
        var bb = n.getBBox();
        if (bb.width || bb.height) b2 = { x0:bb.x, y0:bb.y, x1:bb.x + bb.width, y1:bb.y + bb.height };
      } catch (e) { /* tracé illisible : on ne rogne pas */ }
      _svgMesure.removeChild(n);
      if (!b2) return;
      boite = boite ? { x0:Math.min(boite.x0, b2.x0), y0:Math.min(boite.y0, b2.y0),
                        x1:Math.max(boite.x1, b2.x1), y1:Math.max(boite.y1, b2.y1) } : b2;
    });
    return boite;
  }

  function boitePeinte(el){
    var x = parseFloat(el.getAttribute('x')) || 0, y = parseFloat(el.getAttribute('y')) || 0;
    var w = parseFloat(el.getAttribute('width')) || 0, h = parseFloat(el.getAttribute('height')) || 0;
    if (!w || !h) return null;
    var b = { x0:x, y0:y, x1:x + w, y1:y + h };

    for (var n = el.parentNode; n && n.nodeType === 1; n = n.parentNode) {
      var ref = n.getAttribute && n.getAttribute('clip-path');
      if (!ref) continue;
      var id = (ref.match(/#([^)]+)\)/) || [])[1];
      var c = id && boiteClip(doc.querySelector('clipPath[id="' + id + '"]'));
      if (!c) continue;
      b.x0 = Math.max(b.x0, c.x0); b.y0 = Math.max(b.y0, c.y0);
      b.x1 = Math.min(b.x1, c.x1); b.y1 = Math.min(b.y1, c.y1);
    }
    return (b.x1 - b.x0 > 0.5 && b.y1 - b.y0 > 0.5) ? b : null;
  }

  Array.prototype.forEach.call(doc.querySelectorAll('text'), function(el){
    if (inDefs(el)) return;
    var b = textBox(el);
    if (!b) return;
    texts.push(b);
    var d = [];
    if (b.x0 < -TOL) d.push('sort à gauche de ' + Math.round(-b.x0) + 'px');
    if (b.x1 > W + TOL) d.push('dépasse à droite de ' + Math.round(b.x1 - W) + 'px');
    if (b.y0 < -TOL) d.push('sort en haut');
    if (b.y1 > H + TOL) d.push('dépasse en bas de ' + Math.round(b.y1 - H) + 'px');
    if (d.length) pb.push('TEXTE « ' + b.s.slice(0, 34) + ' » ' + d.join(', '));
  });

  /* Le débordement volontaire est déclaré dans le gabarit (`data-bleed`) :
     la demi-bulle photo sort du bord droit par construction. On ne signale
     donc que ce qui sort sans l'avoir annoncé. */
  function debordVoulu(el){
    for (var n = el; n && n.nodeType === 1; n = n.parentNode) {
      if (n.getAttribute && n.getAttribute('data-bleed')) return true;
    }
    return false;
  }

  /* Rectangles et images : un fond peut faire exactement W×H, pas plus. */
  Array.prototype.forEach.call(doc.querySelectorAll('rect,image'), function(el){
    if (inDefs(el) || debordVoulu(el)) return;
    var b = boitePeinte(el);
    if (!b) return;
    var d = [];
    if (b.x0 < -TOL) d.push('x=' + Math.round(b.x0));
    if (b.y0 < -TOL) d.push('y=' + Math.round(b.y0));
    if (b.x1 > W + TOL) d.push('droite +' + Math.round(b.x1 - W));
    if (b.y1 > H + TOL) d.push('bas +' + Math.round(b.y1 - H));
    if (d.length) pb.push(el.tagName.toUpperCase() + ' hors cadre (' + d.join(', ') + ')');
  });

  /* Masquage : un rectangle ou une image opaque peint APRÈS un texte le
     recouvre. Les fonds sont dessinés en premier, ils ne remontent donc pas. */
  var flux = doc.querySelectorAll('text,rect,image'), vus = [];
  Array.prototype.forEach.call(flux, function(el){
    if (inDefs(el)) return;
    var tag = el.tagName.toLowerCase();
    if (tag === 'text') {
      var bt = textBox(el);
      if (bt) vus.push(bt);
      return;
    }
    var op = parseFloat(el.getAttribute('opacity'));
    if (!isNaN(op) && op < 0.85) return;                 /* voile translucide : normal */
    var fill = (el.getAttribute('fill') || '').toLowerCase();
    if (fill === 'none' || fill.indexOf('rgba') === 0) return;
    var bp = boitePeinte(el);
    if (!bp) return;
    if (bp.x1 - bp.x0 >= W - 2 && bp.y1 - bp.y0 >= H - 2) return;   /* fond plein cadre */
    vus.forEach(function(b){
      var ox = Math.min(b.x1, bp.x1) - Math.max(b.x0, bp.x0);
      var oy = Math.min(b.y1, bp.y1) - Math.max(b.y0, bp.y0);
      if (ox <= 1 || oy <= 1) return;
      var aire = (b.x1 - b.x0) * (b.y1 - b.y0);
      if (aire > 0 && (ox * oy) / aire > 0.35) {
        pb.push('MASQUÉ : « ' + b.s.slice(0, 26) + ' » recouvert par un ' + tag.toUpperCase());
      }
    });
  });

  /* Chevauchements texte / texte : on ne signale qu'un recouvrement net. */
  for (var i = 0; i < texts.length; i++) {
    for (var j = i + 1; j < texts.length; j++) {
      var a = texts[i], b = texts[j];
      var ox = Math.min(a.x1, b.x1) - Math.max(a.x0, b.x0);
      var oy = Math.min(a.y1, b.y1) - Math.max(a.y0, b.y0);
      if (ox <= 1 || oy <= 1) continue;
      var aire = ox * oy;
      var mini = Math.min((a.x1 - a.x0) * (a.y1 - a.y0), (b.x1 - b.x0) * (b.y1 - b.y0));
      if (mini > 0 && aire / mini > 0.22) {
        pb.push('CHEVAUCHEMENT « ' + a.s.slice(0, 22) + ' » / « ' + b.s.slice(0, 22) + ' »');
      }
    }
  }
  return pb;
}

async function auditAll(){
  var box = document.createElement('pre');
  box.id = 'selftest';
  box.style.cssText = 'padding:16px;font-size:12px;white-space:pre-wrap;background:#111;color:#0f0;margin:0';
  box.textContent = 'AUDIT en cours…';
  document.body.insertBefore(box, document.body.firstChild);
  var NL = String.fromCharCode(10), lines = [], nb = 0;
  for (var i = 0; i < CARDS.length; i++) {
    var c = CARDS[i], pb = auditCard(c);
    if (pb.length) {
      nb += pb.length;
      lines.push('── ' + c.id + ' ' + c.group + '/' + c.style + '/' + c.variant);
      pb.forEach(function(m){ lines.push('   ' + m); });
    }
  }
  box.textContent = 'AUDIT ' + nb + ' probleme(s) sur ' + CARDS.length + ' visuels' + NL + lines.join(NL);
  return lines;
}

/** Auto-test : tente de rasteriser chaque visuel et rapporte les échecs.
    Accessible via ?selftest=1 — le résultat est écrit dans #selftest. */
/** Largeur brute de `txt` dans une famille donnee, a 100 px. */
function mesureFamille(txt, fam, style, weight){
  _mc.font = (style === 'italic' ? 'italic ' : '') + weight + ' 100px "' + fam + '"';
  return _mc.measureText(txt).width;
}

/**
 * Garde-fou aperçu / export.
 *
 * `measure()` mesure avec les polices de la PAGE, mais l'export compose avec
 * les @font-face embarqués dans le SVG. Si une graisse ne pointe pas sur le
 * même fichier des deux côtés, les largeurs calculées ne valent plus rien :
 * l'aperçu tombe juste et l'export déborde. On recharge donc les
 * déclarations d'export sous un nom de famille neutre et on compare les
 * largeurs, graisse par graisse.
 */
async function controlerPolices(){
  if (!ASSETS.fonts) return ["polices d'export non embarquées — contrôle impossible"];
  if (!window.FontFace) return [];

  var re = /@font-face\{font-family:'([^']+)';font-style:(\w+);font-weight:([^;]+);src:url\(([^)]+)\)/g;
  var m, chargements = [];
  while ((m = re.exec(ASSETS.fonts))) {
    var face = new FontFace('Controle ' + m[1], 'url(' + m[4] + ')', { style:m[2], weight:m[3] });
    chargements.push(face.load().then(function(f){ document.fonts.add(f); }));
  }
  if (!chargements.length) return ['aucune déclaration @font-face reconnue dans ASSETS.fonts'];
  await Promise.all(chargements);

  /* Toutes les graisses réellement demandées par les gabarits. */
  var DEMANDEES = [
    ['Gill Sans', 'normal', 500], ['Gill Sans', 'normal', 600], ['Gill Sans', 'normal', 700],
    ['Gill Sans', 'normal', 800], ['Gill Sans', 'italic', 700],
    ['AllRound Gothic', 'normal', 700], ['AllRound Gothic', 'normal', 800]
  ];
  var ECHANTILLON = "Adhésion 2026-2027 — RELÈVE TOUS LES DÉFIS ! agjqw";
  var ecarts = [];
  DEMANDEES.forEach(function(t){
    var page = mesureFamille(ECHANTILLON, t[0], t[1], t[2]);
    var expo = mesureFamille(ECHANTILLON, 'Controle ' + t[0], t[1], t[2]);
    if (Math.abs(page - expo) > 0.5) {
      ecarts.push('POLICE ' + t[0] + ' ' + t[1] + ' ' + t[2] + ' : page ' + page.toFixed(1)
                + ' px / export ' + expo.toFixed(1) + " px — l'export ne composera pas comme l'aperçu");
    }
  });
  return ecarts;
}

async function selfTest(){
  var out = [], box = document.createElement('pre');
  box.id = 'selftest';
  box.style.cssText = 'padding:16px;font-size:13px;white-space:pre-wrap;background:#111;color:#0f0;margin:0';
  box.textContent = 'SELFTEST en cours…';
  document.body.insertBefore(box, document.body.firstChild);

  (await controlerPolices()).forEach(function(m){ out.push(m); });

  for (var i = 0; i < CARDS.length; i++) {
    var c = CARDS[i];
    try {
      await rasterize(svgFor(c, true), Math.round(c.w / 6), Math.round(c.h / 6));
    } catch (e) {
      out.push(c.id + ' ' + c.group + '/' + c.style + '/' + c.variant + ' :: ' + e.message);
    }
  }
  var nl = String.fromCharCode(10);
  box.textContent = 'SELFTEST ' + (out.length ? 'ECHECS ' + out.length + nl + out.join(nl)
                                              : 'OK ' + CARDS.length + ' visuels');
  return out;
}

/* =====================================================================
   19. Démarrage
   ===================================================================== */
async function boot(){
  /* Les polices doivent être prêtes avant toute mesure de texte. */
  try {
    await Promise.all([
      document.fonts.load('400 32px "Gill Sans"'),
      document.fonts.load('500 32px "Gill Sans"'),
      document.fonts.load('600 32px "Gill Sans"'),
      document.fonts.load('700 32px "Gill Sans"'),
      document.fonts.load('italic 400 32px "Gill Sans"'),
      document.fonts.load('600 32px "AllRound Gothic"'),
      document.fonts.load('800 32px "AllRound Gothic"')
    ]);
    await document.fonts.ready;
  } catch (e) { /* on continue avec les métriques de repli */ }

  makeQr();
  renderGallery();

  /* Filtres pré-sélectionnés par l'URL : ?format=affiche&style=mosaic
     — pratique pour envoyer à quelqu'un un lien déjà filtré. */
  var qs = new URLSearchParams(location.search);
  ['group', 'style'].forEach(function(f){
    var val = qs.get(f === 'group' ? 'format' : 'style');
    if (!val) return;
    var chip = document.querySelector('.chip[data-filter="' + f + '"][data-value="' + val + '"]');
    if (!chip) return;
    document.querySelectorAll('.chip[data-filter="' + f + '"]').forEach(function(b){ b.classList.remove('on'); });
    chip.classList.add('on');
    filters[f] = val;
  });

  applyFilters();
  setupLazy();

  /* Ressources base64 pour l'export : un SVG rasterisé dans un canvas n'a
     accès à aucune ressource externe, il faut donc tout embarquer. */
  try {
    var res = await Promise.all([
      fetchB64(ASSET('images/logomjat.png')),
      fetchB64(ASSET('fonts/Gill_Sans.woff2')),
      fetchB64(ASSET('fonts/Gill_Sans_Bold.woff2')),
      fetchB64(ASSET('fonts/Gill_Sans_Italic.woff2')),
      fetchB64(ASSET('fonts/AllRoundGothic-Demi.woff2')),
      fetchB64(ASSET('fonts/AllRoundGothic-Bold.woff2'))
    ]);
    ASSETS.logo = res[0];

    /* ATTENTION — ces plages de graisse doivent rester le miroir exact de
       public/css/gill-sans.css. `measure()` mesure avec les polices de la
       PAGE ; si l'export associe une graisse à un autre fichier, il compose
       plus large ou plus étroit que ce qui a été mesuré : l'aperçu tombe
       juste et l'export déborde. C'est ce qui arrivait avec la graisse 500,
       qui prenait le Regular dans la page et le Bold à l'export. */
    ASSETS.fonts =
      "@font-face{font-family:'Gill Sans';font-style:normal;font-weight:400 500;src:url(data:font/woff2;base64," + res[1] + ") format('woff2');}"
    + "@font-face{font-family:'Gill Sans';font-style:normal;font-weight:600 900;src:url(data:font/woff2;base64," + res[2] + ") format('woff2');}"
    + "@font-face{font-family:'Gill Sans';font-style:italic;font-weight:400;src:url(data:font/woff2;base64," + res[3] + ") format('woff2');}"
    + "@font-face{font-family:'AllRound Gothic';font-style:normal;font-weight:600;src:url(data:font/woff2;base64," + res[4] + ") format('woff2');}"
    + "@font-face{font-family:'AllRound Gothic';font-style:normal;font-weight:700 900;src:url(data:font/woff2;base64," + res[5] + ") format('woff2');}"
    + "text{font-kerning:normal;}";

    /* Photothèque en base64 : sans ça, les visuels Photo et Mosaïque
       ressortiraient vides à l'export (le SVG rasterisé est isolé). */
    await Promise.all(DEFAULT_PHOTOS.map(async function(path){
      try { ASSETS.photos[path] = await fetchB64(ASSET(path)); } catch (e) {}
    }));
  } catch (e) {
    console.warn('Polices non embarquées : les exports utiliseront une police de repli.', e);
    toast("Attention : polices non embarquées, vérifie un export avant diffusion.");
  }

  /* Contrôles après embarquement : avant, ils testeraient des SVG sans
     polices ni photos, c'est-à-dire pas ce que l'export produit. */
  if (qs.get('selftest')) { window.__selftest = await selfTest(); }
  if (qs.get('audit')) { window.__audit = await auditAll(); }
}
boot();
</script>
</body>
</html>
