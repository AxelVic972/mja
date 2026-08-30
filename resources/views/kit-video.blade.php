@php
    /**
     * Monteur vidéo MJA — assemblage de rushes en réel prêt à publier.
     *
     * Tout se passe dans le navigateur : les fichiers ne quittent jamais le
     * poste. Les rushes déposés dans public/videos/kit sont proposés d'office ;
     * l'utilisateur peut en déposer d'autres à la volée.
     */
    /**
     * Fichiers d'un dossier, sans doublon.
     *
     * Un même rush redéposé sous un autre nom — ce qui arrive dès qu'on
     * réexporte une conversation — apparaissait deux fois dans la
     * bibliothèque. On compare le contenu, pas le nom, et on garde
     * l'exemplaire déjà nommé lisiblement.
     */
    $lister = function (string $dossier, string $motif) {
        $chemin = public_path($dossier);
        $fichiers = is_dir($chemin) ? (glob($chemin . '/' . $motif, GLOB_BRACE) ?: []) : [];

        // Les noms déjà classés (« 04-collecte… ») passent devant leur copie.
        usort($fichiers, function ($a, $b) {
            $classeA = (int) (bool) preg_match('/^\d{2}-/', basename($a));
            $classeB = (int) (bool) preg_match('/^\d{2}-/', basename($b));

            return $classeB <=> $classeA ?: strcmp(basename($a), basename($b));
        });

        $vus = [];
        $sortie = [];

        foreach ($fichiers as $f) {
            $empreinte = filesize($f) . '-' . md5_file($f);

            if (isset($vus[$empreinte])) {
                continue;
            }

            $vus[$empreinte] = true;
            $sortie[] = [
                'url'  => asset($dossier . '/' . rawurlencode(basename($f))),
                'nom'  => basename($f),
                'poids' => round(filesize($f) / 1048576, 1),
            ];
        }

        usort($sortie, fn ($a, $b) => strcmp($a['nom'], $b['nom']));

        return $sortie;
    };

    $videos = $lister('videos/kit', '*.{mp4,MP4,webm,WEBM,mov,MOV,m4v,M4V}');

    // Les photos du carrousel et du kit servent aussi de plans fixes. Celles
    // déposées à côté des rushes comptent au même titre : le dossier accueille
    // les deux, comme l'annonce son LISEZ-MOI.
    $photos = array_merge(
        $lister('videos/kit', '*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'),
        $lister('images/carrousel', '*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'),
        $lister('images/kit', '*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}'),
    );

    /**
     * Fiche des rushes et montages tout prêts, décrits dans
     * public/videos/kit/montage.json. Le fichier est facultatif : sans lui, la
     * bibliothèque affiche simplement les noms de fichiers.
     */
    $fiche = [];
    $ebauches = [];
    $tendances = [];
    $cheminFiche = public_path('videos/kit/montage.json');

    if (is_file($cheminFiche)) {
        $lu = json_decode((string) file_get_contents($cheminFiche), true);

        if (is_array($lu)) {
            $fiche = $lu['plans'] ?? [];
            // On ne garde que les ébauches dont tous les plans sont là :
            // proposer un montage qui s'ouvrirait à trous serait pire que rien.
            $ebauches = array_values(array_filter($lu['ebauches'] ?? [], function ($e) use ($fiche) {
                foreach ($e['plans'] ?? [] as $plan) {
                    // Un plan se cite par son nom, ou par un objet qui
                    // resserre l'extrait — les montages à coupes rapides
                    // en ont besoin.
                    $nom = is_array($plan) ? ($plan['fichier'] ?? '') : $plan;

                    if (! isset($fiche[$nom]) || ! is_file(public_path('videos/kit/' . $nom))) {
                        return false;
                    }
                }

                return ! empty($e['plans']);
            }));

            $tendances = $lu['tendances'] ?? [];
        }
    }

    // Musiques déposées par l'association. Aucune n'est livrée avec le site :
    // les titres du commerce sont protégés, et sur Instagram ou TikTok la
    // musique s'ajoute de toute façon dans l'application.
    $musiques = $lister('videos/musiques', '*.{mp3,MP3,m4a,M4A,aac,AAC,wav,WAV,ogg,OGG}');

    // Licences des pistes livrées : plusieurs demandent de citer l'auteur.
    $creditsMusique = [];
    $cheminCredits = public_path('videos/musiques/credits.json');

    if (is_file($cheminCredits)) {
        $lu = json_decode((string) file_get_contents($cheminCredits), true);
        $creditsMusique = is_array($lu) ? array_column($lu, null, 'fichier') : [];
    }

    // Montages déjà rendus par la commande mja:montages.
    $rendus = array_map(function ($v) {
        $affiche = preg_replace('/\.mp4$/', '.jpg', $v['nom']);

        return $v + [
            'id' => pathinfo($v['nom'], PATHINFO_FILENAME),
            'affiche' => is_file(public_path('videos/montages/' . $affiche))
                ? asset('videos/montages/' . rawurlencode($affiche))
                : null,
        ];
    }, $lister('videos/montages', '*.mp4'));

    // Nom lisible d'un montage rendu, repris de l'ébauche correspondante.
    $nomEbauche = array_column($ebauches, 'nom', 'id');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Monteur vidéo — Madin'Jeunes Ambition</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo.jpg') }}">
<link rel="stylesheet" href="{{ asset('css/gill-sans.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
<style>
:root{
  --navy:#1A3D8A; --dark:#2048A4; --blue:#3DAEF5; --bluedark:#1E93D6;
  --yellow:#F5A623; --red:#D0021B; --ink:#0B1E45; --gris:#6C7A91;
  --bord:#E4EAF4; --fond:#F5F8FD;
}
*{box-sizing:border-box}
body{margin:0;background:var(--fond);color:#333;font-family:'Gill Sans','Open Sans',sans-serif;font-size:15px;line-height:1.5}
.wrap{max-width:1400px;margin:0 auto;padding:0 20px}

header{background:linear-gradient(135deg,#1A3D8A 0%,#2048A4 48%,#3262CC 100%);color:#fff;padding:26px 0 24px;position:relative;overflow:hidden}
header .bar{position:absolute;left:0;right:0;top:0;height:6px;display:flex}
header .bar i{flex:1}
.b1{background:var(--blue)}.b2{background:var(--yellow)}.b3{background:var(--red)}
.idt{display:flex;align-items:center;gap:14px;margin-bottom:14px}
.idt img{height:52px;width:52px;object-fit:contain;background:#fff;border-radius:12px;padding:4px}
.idt .n{font-weight:800;font-size:17px;letter-spacing:.7px}
.idt .s{font-size:12.5px;color:#BDD4F5;font-style:italic}
h1{margin:0 0 4px;font-size:26px;font-weight:800}
header p{margin:0;color:#C9DBFA;font-size:14px;max-width:760px}

.grille{display:grid;grid-template-columns:300px minmax(0,1fr) 330px;gap:18px;margin:20px 0 22px;align-items:start}
@media (max-width:1180px){.grille{grid-template-columns:1fr}}

/* Les colonnes latérales défilent pour elles-mêmes : sans cela, une longue
   bibliothèque repoussait la timeline hors du premier écran. */
@media (min-width:1181px){
  .grille > .bloc:first-child,
  .grille > .bloc:last-child{position:sticky;top:14px;max-height:calc(100vh - 28px);
                             overflow-y:auto;overscroll-behavior:contain}
}

/* Panneaux repliables : la flèche remplace le titre cliquable. */
.repli{border-bottom:1px solid var(--bord)}
.bloc.repli{border:1px solid var(--bord);border-radius:16px;margin-bottom:20px;background:#fff}
.repli > summary{list-style:none;cursor:pointer;padding:13px 16px;font-size:14px;font-weight:800;
                 color:var(--navy);background:#FAFCFF;display:flex;align-items:center;gap:8px;
                 user-select:none}
.repli > summary::-webkit-details-marker{display:none}
.repli > summary::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;
                        margin-left:auto;font-size:11px;color:var(--gris);transition:transform .15s}
.repli[open] > summary::after{transform:rotate(180deg)}
.repli > summary .cpt{margin-left:auto;font-weight:600;color:var(--gris);font-size:12px}
.repli > summary .cpt + *{margin-left:0}
.repli[open] > summary{border-bottom:1px solid var(--bord)}

.bloc{background:#fff;border:1px solid var(--bord);border-radius:16px;overflow:hidden}
.bloc > h2{margin:0;padding:13px 16px;font-size:14px;font-weight:800;color:var(--navy);
           border-bottom:1px solid var(--bord);background:#FAFCFF;display:flex;align-items:center;gap:8px}
.bloc > h2 .cpt{margin-left:auto;font-weight:600;color:var(--gris);font-size:12px}
.bloc .corps{padding:14px 16px}

/* ── Bibliothèque ─────────────────────────────────────────────── */
.depot{border:2px dashed #C9D8EE;border-radius:12px;padding:16px;text-align:center;color:var(--gris);
       font-size:13px;cursor:pointer;transition:.15s;margin-bottom:12px}
.depot:hover,.depot.survol{border-color:var(--blue);background:#F2F8FF;color:var(--dark)}
.media{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.vignette{position:relative;border:1px solid var(--bord);border-radius:9px;overflow:hidden;cursor:pointer;
          aspect-ratio:1;background:#0B1E45;transition:.15s}
.vignette:hover{border-color:var(--blue);transform:translateY(-2px)}
.vignette .lg{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(transparent,rgba(11,30,69,.92));
              color:#fff;font-size:9.5px;line-height:1.25;padding:12px 4px 4px;text-align:left;
              overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}

/* Montages tout prêts, en tête de la colonne bibliothèque. */
.ebauches{display:flex;flex-direction:column;gap:8px;padding-bottom:14px}
.ebauche{display:block;width:100%;text-align:left;border:1px solid var(--bord);border-radius:11px;
         background:#fff;padding:10px 12px;cursor:pointer;font:inherit;transition:.15s}
.ebauche:hover{border-color:var(--blue);background:#F7FBFF;transform:translateY(-1px)}
.ebauche .n{display:inline-block;font-weight:800;color:var(--navy);font-size:13.5px}
.ebauche .d{float:right;font-size:11px;color:var(--gris);background:#EEF3FB;border-radius:20px;padding:1px 8px}
.ebauche .r{display:block;clear:both;font-size:11.5px;color:var(--gris);margin-top:3px;line-height:1.35}
.vignette img,.vignette video{width:100%;height:100%;object-fit:cover;display:block}
.vignette .t{position:absolute;left:4px;top:4px;background:rgba(11,30,69,.82);color:#fff;font-size:9px;
             font-weight:800;letter-spacing:.5px;border-radius:5px;padding:2px 5px}
.vignette .plus{position:absolute;right:4px;bottom:4px;width:20px;height:20px;border-radius:50%;
                background:var(--yellow);color:var(--navy);font-size:11px;display:flex;align-items:center;justify-content:center}

/* Montages déjà rendus par la commande mja:montages. */
.rendus{display:flex;flex-direction:column;gap:12px;padding-bottom:14px}
.rendu{border:1px solid var(--bord);border-radius:12px;overflow:hidden;background:#fff}
.rendu video{width:100%;display:block;background:#0B1E45;max-height:280px;object-fit:contain}
.rendu .meta{display:flex;align-items:center;gap:8px;padding:8px 10px;font-size:12px;color:var(--gris)}
.rendu .meta b{color:var(--navy);font-size:13px;flex:1;min-width:0;
               white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rendu .meta a{color:var(--bluedark);text-decoration:none;font-weight:700;white-space:nowrap}
.rendu .meta a:hover{text-decoration:underline}

/* Tendances : musiques et formats de montage. */
.tendances{display:grid;grid-template-columns:1fr 1fr;gap:26px}
@media (max-width:900px){.tendances{grid-template-columns:1fr}}
.tendances h3{margin:0 0 10px;font-size:14px;font-weight:800;color:var(--navy)}
.famille{border:1px solid var(--bord);border-radius:12px;padding:11px 13px;margin-bottom:9px;background:#FCFDFF}
.famille > b{color:var(--ink);font-size:13.5px}
.famille p{margin:4px 0 7px;font-size:12.5px;color:var(--gris);line-height:1.45}
.famille p.ou{margin:6px 0 0;font-size:11.5px;color:var(--bluedark)}
.jetons{display:flex;flex-wrap:wrap;gap:5px}
.jetons span{background:#EEF3FB;color:var(--navy);border-radius:20px;padding:2px 9px;font-size:11.5px;font-weight:600}
.etapes-son{margin:6px 0 0;padding-left:18px}
.etapes-son li{font-size:12.5px;color:var(--gris);line-height:1.45;margin-bottom:4px}
.duree-format{float:right;font-size:11px;color:var(--gris);background:#F2F5FA;border-radius:20px;padding:1px 9px}
.btn.mini{padding:6px 11px;font-size:12px}

/* ── Montage ──────────────────────────────────────────────────── */
.clips{max-height:520px;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:9px}
.clip{border:1px solid var(--bord);border-radius:12px;padding:9px 11px;background:#fff;display:flex;gap:10px;
      align-items:center;cursor:grab}
.clip.tire{opacity:.4}
.clip.cible{border-color:var(--blue);box-shadow:0 0 0 3px rgba(61,174,245,.18)}
.clip .apercu{width:52px;height:52px;border-radius:8px;overflow:hidden;flex:none;background:var(--navy);
              display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px}
.clip .apercu img,.clip .apercu video{width:100%;height:100%;object-fit:cover}
.clip .info{flex:1;min-width:0}
.clip .info b{display:block;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.clip .reglages{display:flex;flex-wrap:wrap;gap:5px;margin-top:5px}
.clip .legende-champ{width:100%;margin-top:5px;border:1px solid var(--bord);border-radius:8px;
                     padding:5px 8px;font:inherit;font-size:12px;color:var(--ink);outline:none}
.clip .legende-champ:focus{border-color:var(--blue)}
.clip .legende-champ::placeholder{color:#A9B6C8}
.clip select,.clip input[type=number]{font:inherit;font-size:11.5px;border:1px solid var(--bord);border-radius:7px;
                                      padding:2px 5px;color:var(--gris);background:#fff}
.clip input[type=number]{width:52px}
.clip .actions{display:flex;flex-direction:column;gap:3px}
.clip .actions button{border:0;background:#EEF3FB;color:var(--dark);border-radius:6px;width:24px;height:20px;
                      cursor:pointer;font-size:10px}
.clip .actions button:hover{background:var(--blue);color:#fff}
.clip .actions .sup:hover{background:var(--red)}
.clip.fixe{background:#FFFBEF;border-color:#F6E2B4;cursor:default}
.clip.fixe .apercu{background:var(--yellow);color:var(--navy)}

.vide{padding:38px 16px;text-align:center;color:var(--gris);font-size:13.5px}

/* ── Réglages ─────────────────────────────────────────────────── */
.champ{margin-bottom:13px}
.champ label{display:block;font-size:12px;font-weight:800;color:var(--navy);margin-bottom:5px;
             text-transform:uppercase;letter-spacing:.6px}
.champ select,.champ input[type=text]{width:100%;font:inherit;font-size:13.5px;border:1px solid var(--bord);
                                       border-radius:9px;padding:7px 10px;background:#fff}
.champ .aide{font-size:11.5px;color:var(--gris);margin-top:4px}
.bascule{display:flex;align-items:center;gap:9px;font-size:13.5px;color:#4A5A73;margin-bottom:9px;cursor:pointer}

/* ── Aperçu ───────────────────────────────────────────────────── */
.scene{background:#0B1E45;border-radius:14px;padding:14px;display:flex;justify-content:center}
#toile{max-width:100%;max-height:56vh;border-radius:8px;background:#000;box-shadow:0 10px 30px rgba(0,0,0,.35)}
.transport{display:flex;flex-wrap:wrap;gap:9px;align-items:center;padding:12px 16px;border-top:1px solid var(--bord)}
.duree{font-size:12.5px;color:var(--gris);margin-left:auto}
.btn{border:0;border-radius:10px;padding:9px 15px;font:inherit;font-size:13.5px;font-weight:700;cursor:pointer;
     display:inline-flex;align-items:center;gap:7px;transition:.15s}
.btn.p{background:var(--bluedark);color:#fff}.btn.p:hover{background:var(--dark)}
.btn.g{background:#EEF3FB;color:var(--dark)}.btn.g:hover{background:#E2EBF8}
.btn.j{background:var(--yellow);color:var(--navy)}.btn.j:hover{background:#e0941a}
.btn:disabled{opacity:.5;cursor:not-allowed}
.etat{padding:10px 16px;font-size:12.5px;color:var(--gris);border-top:1px solid var(--bord);min-height:38px}
.etat.actif{color:var(--dark);font-weight:600}

.note{background:#FFFBEB;border:1px solid #FDE9B8;border-radius:14px;padding:16px 18px;margin-bottom:22px;
      font-size:13.5px;color:#78591C}
.note b{color:#92400E}
</style>
</head>
<body>

<header>
  <div class="bar"><i class="b1"></i><i class="b2"></i><i class="b3"></i></div>
  <div class="wrap">
    <div class="idt">
      <img src="{{ asset('images/logo.jpg') }}" alt="Madin'Jeunes Ambition">
      <div>
        <div class="n">MADIN' JEUNES AMBITION</div>
        <div class="s">Relève tous les défis !</div>
      </div>
    </div>
    <h1>Monteur vidéo</h1>
    <p>Assemble tes rushes en réel : une intro, des plans vidéo et photo, une outro. Réordonne, ajuste les durées,
       choisis les effets et les transitions, puis exporte. Tout se passe sur ton ordinateur — aucun fichier n'est envoyé.</p>
  </div>
</header>

<div class="wrap">

  @if(empty($videos))
  <div class="note" style="margin-top:20px">
    <b>Aucune vidéo dans la bibliothèque.</b>
    Dépose tes fichiers dans <code>public/videos/kit/</code> pour qu'ils apparaissent ici d'office,
    ou glisse-les directement dans la zone de dépôt ci-dessous — dans ce cas ils ne sont chargés que pour cette session.
    Les photos du site sont déjà disponibles comme plans fixes.
  </div>
  @endif

  <div class="grille">

    {{-- ── Bibliothèque ───────────────────────────────────────── --}}
    <div class="bloc">
      @if($ebauches)
      <details class="repli" open>
        <summary><i class="fas fa-wand-magic-sparkles"></i> Ébauches <span class="cpt">{{ count($ebauches) }}</span></summary>
        <div class="corps ebauches">
        <p class="aide" style="margin:0 0 10px">
          Des montages déjà assemblés à partir des rushes du dossier : plans choisis,
          extraits calés, intro et outro posées. Un clic charge le tout dans la timeline,
          où rien n'est figé — ordre, durées, effets, tout reste modifiable.
        </p>
        @foreach($ebauches as $e)
        <button type="button" class="ebauche" data-ebauche="{{ $e['id'] }}">
          <span class="n">{{ $e['nom'] }}</span>
          <span class="d">{{ count($e['plans']) }} plans</span>
          <span class="r">{{ $e['resume'] }}</span>
        </button>
        @endforeach
        </div>
      </details>
      @endif

      @if($rendus)
      <details class="repli">
        <summary><i class="fas fa-circle-check"></i> Déjà prêts <span class="cpt">{{ count($rendus) }}</span></summary>
        <div class="corps rendus">
        <p class="aide" style="margin:0 0 10px">
          Montages déjà exportés en 1080 × 1920, muets, prêts à publier.
          Regarde, télécharge, ou recharge l'ébauche correspondante pour la retoucher.
        </p>
        @foreach($rendus as $r)
        <div class="rendu">
          <video src="{{ $r['url'] }}" @if($r['affiche']) poster="{{ $r['affiche'] }}" @endif
                 controls preload="none" playsinline></video>
          <div class="meta">
            <b>{{ $nomEbauche[$r['id']] ?? $r['nom'] }}</b>
            <span>{{ $r['poids'] }} Mo</span>
            <a href="{{ $r['url'] }}" download><i class="fas fa-download"></i> Télécharger</a>
          </div>
        </div>
        @endforeach
        </div>
      </details>
      @endif

      <h2><i class="fas fa-photo-film"></i> Bibliothèque
        <span class="cpt">{{ count($videos) }} vidéo(s) · {{ count($photos) }} photo(s)</span>
      </h2>
      <div class="corps">
        <div class="depot" id="depot">
          <i class="fas fa-cloud-arrow-up" style="font-size:20px;display:block;margin-bottom:6px"></i>
          Glisse tes vidéos et photos ici<br>
          <span style="font-size:11.5px">ou clique pour les choisir</span>
          <input type="file" id="fichiers" accept="video/*,image/*" multiple hidden>
        </div>
        <button type="button" class="btn g" id="btn-carton" style="width:100%;justify-content:center;margin-bottom:10px">
          <i class="fas fa-quote-left"></i> Ajouter un carton texte
        </button>
        <div class="media" id="media"></div>
      </div>
    </div>

    {{-- ── Montage ────────────────────────────────────────────── --}}
    <div class="bloc">
      <h2><i class="fas fa-timeline"></i> Montage <span class="cpt" id="cpt-clips"></span></h2>
      <div class="scene"><canvas id="toile" width="1080" height="1920"></canvas></div>
      <div class="transport">
        <button class="btn p" id="btn-lire"><i class="fas fa-play"></i> Lire</button>
        <button class="btn g" id="btn-stop"><i class="fas fa-stop"></i> Arrêter</button>
        <button class="btn j" id="btn-export"><i class="fas fa-download"></i> Exporter la vidéo</button>
        <span class="duree" id="duree">0,0 s</span>
      </div>
      <div class="etat" id="etat">Ajoute des plans depuis la bibliothèque pour composer ton réel.</div>
      <div class="clips" id="clips"></div>
    </div>

    {{-- ── Réglages ───────────────────────────────────────────── --}}
    <div class="bloc">
      <h2><i class="fas fa-sliders"></i> Réglages</h2>
      <div class="corps">
        <div class="champ">
          <label for="opt-qualite">Qualité de l'export</label>
          <select id="opt-qualite">
            <option value="normale">Standard — fichier léger</option>
            <option value="haute" selected>Haute — recommandée</option>
            <option value="maximale">Maximale — fichier lourd</option>
          </select>
          <div class="aide">
            Les rushes filmés au téléphone puis passés par une messagerie sont
            souvent en 360 × 640 : agrandis en 1080 × 1920, ils resteront doux
            quel que soit le réglage. Les fichiers d'origine, non compressés
            par l'application, changent tout.
          </div>
        </div>

        <div class="champ">
          <label for="opt-format">Format</label>
          <select id="opt-format">
            <option value="1080x1920">Reel / Story — 9:16 (1080 × 1920)</option>
            <option value="1080x1350">Post vertical — 4:5 (1080 × 1350)</option>
            <option value="1080x1080">Post carré — 1:1 (1080 × 1080)</option>
            <option value="1920x1080">Paysage — 16:9 (1920 × 1080)</option>
            <option value="1280x720">Paysage léger — 16:9 (1280 × 720)</option>
          </select>
          <div class="aide">Les plans sont recadrés au centre, jamais déformés.</div>
        </div>

        <div class="champ">
          <label for="opt-intro">Intro</label>
          <select id="opt-intro"></select>
          <div class="aide" id="aide-intro"></div>
        </div>

        <div class="champ">
          <label for="opt-outro">Outro</label>
          <select id="opt-outro"></select>
          <div class="aide" id="aide-outro"></div>
        </div>

        <div class="champ">
          <label for="opt-accroche">Texte de l'intro</label>
          <input type="text" id="opt-accroche" value="MJ'ADHÉSION" maxlength="28">
          <div class="aide">Ligne principale du carton d'ouverture.</div>
        </div>

        <div class="champ">
          <label for="opt-sous">Sous-titre de l'intro</label>
          <input type="text" id="opt-sous" value="SAISON 2026-2027" maxlength="34">
        </div>

        <div class="champ">
          <label for="opt-transition">Transition par défaut</label>
          <select id="opt-transition">
            <option value="fondu">Fondu</option>
            <option value="coupe">Coupe franche</option>
            <option value="glisse">Glissé</option>
            <option value="fondublanc">Fondu au blanc</option>
          </select>
          <div class="aide">Modifiable plan par plan dans le montage.</div>
        </div>

        <div class="champ">
          <label for="opt-duree">Durée d'un plan photo</label>
          <select id="opt-duree">
            <option value="1.5">1,5 s — rythme rapide</option>
            <option value="2" selected>2 s — équilibré</option>
            <option value="3">3 s — posé</option>
          </select>
        </div>

        <label class="bascule"><input type="checkbox" id="opt-logo" checked> Filigrane du logo</label>
        <label class="bascule"><input type="checkbox" id="opt-barre" checked> Filet tricolore</label>
        <label class="bascule"><input type="checkbox" id="opt-son"> Garder le son des vidéos</label>

        <div class="champ">
          <label for="opt-musique">Musique de fond</label>
          <select id="opt-musique">
            <option value="">Aucune — export muet (recommandé pour Instagram)</option>
            @foreach($musiques as $m)
            @php $c = $creditsMusique[$m['nom']] ?? null; @endphp
            <option value="{{ $m['url'] }}"
                    data-credit="{{ $c ? $c['titre'] . ' — ' . $c['artiste'] . ' (' . $c['licence'] . ')' : '' }}">
              {{ $c['titre'] ?? $m['nom'] }} — {{ $m['poids'] }} Mo
            </option>
            @endforeach
          </select>
          <div class="aide">
            @if($musiques)
              Mixée à l'export. Pour un réel Instagram, laisse « Aucune » : la musique
              s'ajoute dans l'application, où elle est sous licence.
            @else
              Aucun fichier dans <code>public/videos/musiques/</code>. Pour Instagram,
              c'est normal et souhaitable : la musique s'ajoute dans l'application.
              Pour le site ou une projection, dépose-y une piste libre de droits.
            @endif
          </div>
        </div>

        <div class="champ" id="bloc-volume" style="display:none">
          <label for="opt-depart-musique">
            Début du morceau <span id="etiquette-depart">0:00</span>
          </label>
          <input type="range" id="opt-depart-musique" min="0" max="0" step="1" value="0" style="width:100%">
          <button type="button" class="btn g mini" id="btn-ecouter" style="margin:6px 0 10px">
            <i class="fas fa-headphones"></i> Écouter 5 s à partir d'ici
          </button>

          <label for="opt-volume">Volume de la musique</label>
          <input type="range" id="opt-volume" min="0" max="100" value="70" style="width:100%">
          <div class="aide">
            Le refrain arrive rarement au début : place le curseur sur la montée,
            écoute, puis lis le montage pour vérifier le calage.
          </div>
          <div class="aide" id="credit-musique"></div>
        </div>

        <button class="btn g" id="btn-vider" style="width:100%;justify-content:center;margin-top:6px">
          <i class="fas fa-rotate-left"></i> Vider le montage
        </button>
      </div>
    </div>
  </div>

  <div class="note">
    <b>Récupérer tes vidéos Instagram.</b> Instagram ne permet pas à un outil tiers de télécharger des publications —
    l'accès demande une authentification et leurs conditions l'interdisent. En revanche, tu peux exporter les tiennes
    depuis ton compte : <em>Paramètres → Votre activité → Télécharger vos informations</em>, en choisissant
    « Contenu multimédia » et la qualité haute. Instagram envoie une archive par email. Mieux encore : les fichiers
    d'origine restés dans la galerie des téléphones sont de bien meilleure qualité que ceux réexportés par Instagram.
    Dépose le tout dans <code>public/videos/kit/</code>.
  </div>

  {{-- ── Tendances, sous la grille : on les consulte, on n'y travaille pas --}}
    @if($tendances)
    <details class="bloc repli">
      <summary><i class="fas fa-fire"></i> Tendances — musiques, sons Instagram et formats de montage</summary>
      <div class="corps tendances">

        <div>
          <h3>Quelle musique ?</h3>
          <div class="note" style="margin:0 0 12px">
            <b>Sur Instagram, TikTok et Facebook, ajoute la musique dans l'application</b>,
            pas dans le fichier : leur bibliothèque est sous licence, et un son tendance est
            mieux poussé par l'algorithme qu'une piste importée. Exporte donc muet.
            Pour le site, une projection ou WhatsApp, il faut une musique libre de droits —
            voir <code>public/videos/musiques/LISEZ-MOI.txt</code>.
          </div>
          @if(!empty($tendances['musique_gratuite']))
          @php $gratuite = $tendances['musique_gratuite']; @endphp
          <div class="famille">
            <b>{{ $gratuite['titre'] }}</b>
            <p>{{ implode(' ', $gratuite['preciser']) }}</p>
            @foreach($gratuite['sources'] as $src)
            <div style="margin:8px 0 0;padding-left:12px;border-left:3px solid var(--blue)">
              <b style="font-size:12.5px;color:var(--navy)">{{ $src['nom'] }}</b>
              <span style="font-size:11.5px;color:var(--bluedark)"> — {{ $src['adresse'] }}</span>
              <p style="margin:2px 0 0">{{ $src['detail'] }}</p>
            </div>
            @endforeach
            <p class="ou"><i class="fas fa-folder-open"></i> {{ implode(' ', $gratuite['ensuite']) }}</p>
            @if(!empty($gratuite['votre_dossier']))
            <p class="ou" style="color:var(--gris)"><i class="fas fa-shield-halved"></i> {{ implode(' ', $gratuite['votre_dossier']) }}</p>
            @endif
          </div>
          @endif

          @foreach($tendances['musiques'] ?? [] as $m)
          <div class="famille">
            <b>{{ $m['famille'] }}</b>
            <p>{{ $m['pourquoi'] }}</p>
            <div class="jetons">
              @foreach($m['reperes'] as $r)<span>{{ $r }}</span>@endforeach
            </div>
            <p class="ou"><i class="fas fa-magnifying-glass"></i> {{ $m['chercher'] }}</p>
          </div>
          @endforeach
        </div>

        <div>
          @if(!empty($tendances['ajouter_un_son']))
          @php $son = $tendances['ajouter_un_son']; @endphp
          <h3>{{ $son['titre'] }}</h3>
          <div class="famille">
            <p>{{ implode(' ', $son['pourquoi']) }}</p>
            <ol class="etapes-son">
              @foreach($son['etapes'] as $etape)<li>{{ $etape }}</li>@endforeach
            </ol>
          </div>
          @foreach($son['recherches'] as $r)
          <div class="famille">
            <b>{{ $r['quoi'] }}</b>
            <p>{{ $r['note'] }}</p>
            <p class="ou"><i class="fas fa-magnifying-glass"></i> {{ $r['ou'] }}</p>
          </div>
          @endforeach
          <div class="famille">
            <b>Repérer une tendance</b>
            <p>{{ implode(' ', $son['reperer_une_tendance']) }}</p>
          </div>
          @endif

          <h3 style="margin-top:18px">Quel format de montage ?</h3>
          <p class="aide" style="margin:0 0 12px">
            Des recettes qui marchent en réel. Celles qui portent un bouton ont une ébauche
            toute prête dans la colonne de gauche.
          </p>
          @foreach($tendances['formats'] ?? [] as $f)
          <div class="famille">
            <b>{{ $f['nom'] }}</b>
            <span class="duree-format">{{ $f['duree'] }}</span>
            <p>{{ $f['recette'] }}</p>
            @if(!empty($f['ebauche']))
            <button type="button" class="btn g mini" data-ebauche="{{ $f['ebauche'] }}">
              <i class="fas fa-wand-magic-sparkles"></i> Charger cette ébauche
            </button>
            @endif
          </div>
          @endforeach
        </div>

      </div>
    </details>
    @endif


</div>

<script>
(function () {
'use strict';

/* =====================================================================
   1. Données de départ
   ===================================================================== */
var C = { navy:'#1A3D8A', dark:'#2048A4', blue:'#3DAEF5', yellow:'#F5A623',
          red:'#D0021B', ink:'#0B1E45', blanc:'#FFFFFF' };
var FAM = "'Gill Sans','Montserrat',sans-serif";

var LOGO_URL = @json(asset('images/logomjat.png'));
var ORG      = "MADIN' JEUNES AMBITION";
var SLOGAN   = "VIENS RELEVER TOUS LES DÉFIS !";
var SITE     = "mja-martinique.com";
var INSTA    = "@madin_jeunes_ambition";
var TIKTOK   = "@madin_jeunes";
var FACEBOOK = "MadinJeunesAmbition";

/* Les trois comptes, dans l'ordre où ils se lisent en fin de vidéo. Le
   pictogramme est tracé par `pictoReseau` : il porte les couleurs de la
   marque, seules à faire reconnaître l'application avant qu'on lise le
   pseudo. */
var RESEAUX = [
  { id:'instagram', nom:'Instagram', pseudo:INSTA },
  { id:'tiktok',    nom:'TikTok',    pseudo:TIKTOK },
  { id:'facebook',  nom:'Facebook',  pseudo:FACEBOOK }
];

/* Bibliothèque servie par le site. Les fichiers déposés par l'utilisateur
   s'y ajoutent avec une URL locale (blob:). */
var BIBLIO = [];
@foreach($videos as $v)
BIBLIO.push({ type:'video', url:@json($v['url']), nom:@json($v['nom']), poids:@json($v['poids']) });
@endforeach
@foreach($photos as $p)
BIBLIO.push({ type:'photo', url:@json($p['url']), nom:@json($p['nom']), poids:@json($p['poids']) });
@endforeach

/* Fiche des rushes : titre lisible, extrait retenu, remarque de tournage.
   Chaque média la reçoit s'il en a une (repérage par nom de fichier). */
var FICHES = @json($fiche);
var EBAUCHES = @json($ebauches);

BIBLIO.forEach(function (m) {
  var f = FICHES[m.nom];
  if (!f) return;
  m.titre = f.titre;
  m.lieu = f.lieu;
  m.action = f.action;
  m.note = f.note;
  m.depart = f.depart || 0;
  m.dureeConseillee = f.duree;
});

/* Cartons d'ouverture et de fermeture. `dessin` reçoit l'avancement t ∈ [0,1]. */
var INTROS = [
  { id:'aucune', nom:'Aucune',                 duree:0,   aide:"Le montage démarre directement sur le premier plan." },
  { id:'logo',   nom:'Logo qui éclot',         duree:3.2, aide:"Le logo apparaît en zoom, le nom se pose dessous." },
  { id:'titre',  nom:'Titre plein écran',      duree:3.4, aide:"Fond navy, titre en gros, filet tricolore qui balaie." },
  { id:'flash',  nom:'Flash tricolore',        duree:1.6, aide:"Trois bandes de couleur balaient l'écran puis le titre tombe." },
  { id:'compte', nom:'Décompte 3-2-1',         duree:2.4, aide:"Décompte rythmé, utile pour accrocher dès la première seconde." }
];
var OUTROS = [
  { id:'aucune', nom:'Aucune',                 duree:0,   aide:"Le montage s'arrête sur le dernier plan." },
  { id:'appel',  nom:"Appel à l'action",       duree:5.8, aide:"« J'ADHÈRE », le slogan, le site, puis les trois comptes avec leur pictogramme." },
  { id:'reseaux',nom:'Réseaux sociaux',        duree:4.6, aide:"« SUIS-NOUS », les trois comptes en grand — Instagram, TikTok, Facebook — puis le slogan." },
  { id:'logo',   nom:'Logo et slogan',         duree:3.0, aide:"Retour au logo, slogan en dessous, pictogrammes des trois réseaux." },
  { id:'contact',nom:'Coordonnées',            duree:4.0, aide:"Le slogan, les trois réseaux avec leur pictogramme, le site et le téléphone." }
];

/* Bits par pixel et par image. 0,10 est le réglage courant d'une plateforme
   vidéo ; au-delà de 0,24 le fichier grossit sans gain visible. */
var QUALITES = { normale:0.10, haute:0.17, maximale:0.24 };

var EFFETS = { aucun:'Aucun', zoom:'Zoom lent', dezoom:'Dézoom', gauche:'Panoramique →',
               nb:'Noir et blanc', chaud:'Teinte chaude' };
var TRANSITIONS = { fondu:'Fondu', coupe:'Coupe', glisse:'Glissé', fondublanc:'Fondu blanc' };

/* =====================================================================
   2. État
   ===================================================================== */
var MONTAGE = [];              /* [{ media, duree, effet, transition }] */
var OPT = {
  largeur:1080, hauteur:1920, intro:'logo', outro:'appel',
  accroche:"MJ'ADHÉSION", sous:'SAISON 2026-2027',
  transition:'fondu', dureePhoto:3, logo:true, barre:true, son:false,
  musique:'', volume:0.7, musiqueDepart:0, qualite:'haute'
};

/* Piste de fond, partagée entre l'aperçu et l'export. */
var piste = null;

/* Durée du fondu de sortie du son, en secondes. Une coupure nette en fin de
   réel s'entend et fait bâclé ; le fondu referme la vidéo proprement. */
var FONDU_SON = 1.2;
var fermeturePiste = null;

var toile = document.getElementById('toile');
var ctx = toile.getContext('2d');

/* La plupart des rushes sont en 360 x 640 : agrandis en 1080 x 1920, ils
   passent par le rééchantillonnage du navigateur, réglé par défaut sur le
   mode le plus rapide. Le mode « high » coûte quelques millisecondes par
   image et évite l'aspect pâteux. */
ctx.imageSmoothingEnabled = true;
ctx.imageSmoothingQuality = 'high';
var logoImg = new Image();
logoImg.src = LOGO_URL;

var lecture = null;            /* état de la lecture en cours */

/* =====================================================================
   3. Utilitaires de dessin
   ===================================================================== */
function W(){ return toile.width; }
function H(){ return toile.height; }
function U(){ return Math.min(W(), H()); }

function fond(couleur){
  ctx.fillStyle = couleur || C.navy;
  ctx.fillRect(0, 0, W(), H());
}

/** Dessine une source en « couvrant » le cadre, avec zoom et décalage. */
function couvrir(src, sw, sh, zoom, dx, dy){
  if (!sw || !sh) return;
  var k = Math.max(W() / sw, H() / sh) * (zoom || 1);
  var w = sw * k, h = sh * k;
  ctx.drawImage(src, (W() - w) / 2 + (dx || 0), (H() - h) / 2 + (dy || 0), w, h);
}

/** Texte centré, réduit jusqu'à tenir dans `maxW`. */
function texte(str, y, taille, couleur, gras, maxW){
  maxW = maxW || W() * 0.86;
  var t = taille;
  ctx.font = (gras ? '800 ' : '600 ') + t + 'px ' + FAM;
  while (ctx.measureText(str).width > maxW && t > 8) {
    t -= 2;
    ctx.font = (gras ? '800 ' : '600 ') + t + 'px ' + FAM;
  }
  ctx.fillStyle = couleur;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(str, W() / 2, y);
  return t;
}

function filetTricolore(y, hauteur, avancement){
  var h = hauteur, l = W() * (avancement === undefined ? 1 : Math.max(0, Math.min(1, avancement)));
  var couleurs = [C.blue, C.yellow, C.red];
  for (var i = 0; i < 3; i++) {
    ctx.fillStyle = couleurs[i];
    ctx.fillRect(i * l / 3, y, l / 3 + 1, h);
  }
}

function filigrane(){
  if (!OPT.logo || !logoImg.complete || !logoImg.naturalWidth) return;
  var t = U() * 0.10, m = U() * 0.045;
  ctx.save();
  ctx.globalAlpha = 0.88;
  ctx.drawImage(logoImg, W() - m - t, m, t, t);
  ctx.restore();
}

function arrondi(x, y, w, h, r){
  r = Math.min(r, w / 2, h / 2);
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
}

function attenue(x){ x = Math.max(0, Math.min(1, x)); return 1 - Math.pow(1 - x, 3); }

/* =====================================================================
   3 bis. Pictogrammes des réseaux
   Tracés à la main dans la toile. Une police d'icônes ne serait pas
   garantie à l'export — la vidéo sortirait avec des carrés vides — alors
   qu'un tracé rend toujours la même image. Chaque pictogramme est dessiné
   dans un carré de 24 × 24 centré sur l'origine, puis mis à l'échelle.
   ===================================================================== */

/** Pastille blanche ronde : le fond commun des trois pictogrammes. */
function pastille(cx, cy, r){
  ctx.fillStyle = '#fff';
  ctx.beginPath();
  ctx.arc(cx, cy, r, 0, Math.PI * 2);
  ctx.fill();
}

/** Note de musique de TikTok, dans le repère 24 × 24 déjà mis à l'échelle. */
function noteTikTok(couleur){
  ctx.strokeStyle = couleur;
  ctx.fillStyle = couleur;
  ctx.lineWidth = 2.8;
  ctx.beginPath();
  ctx.moveTo(1.6, 5.4);
  ctx.lineTo(1.6, -8.4);
  ctx.bezierCurveTo(4.9, -7.6, 7.8, -5.9, 8.8, -2.4);
  ctx.stroke();
  ctx.beginPath();
  ctx.arc(-2.4, 5.2, 3.9, 0, Math.PI * 2);
  ctx.fill();
}

/** Pictogramme du réseau `id`, centré en (cx, cy), dans un carré de côté `d`. */
function pictoReseau(id, cx, cy, d){
  var k = d / 24;
  ctx.save();
  ctx.translate(cx, cy);
  ctx.scale(k, k);
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';

  if (id === 'instagram') {
    ctx.strokeStyle = '#E1306C';
    ctx.fillStyle = '#E1306C';
    ctx.lineWidth = 2.3;
    arrondi(-9.7, -9.7, 19.4, 19.4, 5.8); ctx.stroke();
    ctx.beginPath(); ctx.arc(0, 0, 4.7, 0, Math.PI * 2); ctx.stroke();
    ctx.beginPath(); ctx.arc(5.7, -5.7, 1.35, 0, Math.PI * 2); ctx.fill();

  } else if (id === 'tiktok') {
    /* Le décalage cyan puis rose est la signature du logo : sans lui, une
       note de musique ne dit pas de quelle application il s'agit. */
    ctx.save(); ctx.translate(-1.1, -1.1); noteTikTok('#25F4EE'); ctx.restore();
    ctx.save(); ctx.translate( 1.1,  1.1); noteTikTok('#FE2C55'); ctx.restore();
    noteTikTok('#12141A');

  } else if (id === 'facebook') {
    ctx.fillStyle = '#1877F2';
    ctx.font = '800 28px ' + FAM;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.fillText('f', 0, 9.8);
  } else if (id === 'web') {
    ctx.strokeStyle = '#1E93D6';
    ctx.lineWidth = 2.2;
    ctx.beginPath(); ctx.arc(0, 0, 9, 0, Math.PI * 2); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(-9, 0); ctx.lineTo(9, 0); ctx.moveTo(0, -9); ctx.lineTo(0, 9); ctx.stroke();
    ctx.beginPath(); ctx.ellipse(0, 0, 4, 9, 0, 0, Math.PI * 2); ctx.stroke();
  }
  ctx.restore();
}

/**
 * Ligne « pastille + pseudo », le groupe centré dans l'image. Les pseudos
 * sont longs : empilés l'un sous l'autre ils restent lisibles là où une
 * rangée horizontale les tasserait.
 */
function ligneReseau(reseau, y, taille){
  var maxW = W() * 0.86, d, ecart, large;

  /* Comme `texte`, la ligne se réduit jusqu'à tenir : un format étroit ou
     une police de secours plus large ne doit pas la faire déborder. */
  do {
    d = taille * 1.55; ecart = taille * 0.55;
    ctx.font = '700 ' + taille + 'px ' + FAM;
    large = d + ecart + ctx.measureText(reseau.pseudo).width;
    if (large <= maxW) break;
    taille -= 2;
  } while (taille > 8);

  var x = (W() - large) / 2, cy = y - taille * 0.35;
  pastille(x + d / 2, cy, d / 2);
  pictoReseau(reseau.id, x + d / 2, cy, d * 0.62);
  ctx.fillStyle = '#fff';
  ctx.textAlign = 'left';
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(reseau.pseudo, x + d + ecart, y);
  ctx.textAlign = 'center';
}

/** Ligne « pictogramme web + nom du site », centrée comme les réseaux. */
function ligneSite(y, taille, couleur){
  var d = taille * 1.55, ecart = taille * 0.55;
  ctx.font = '700 ' + taille + 'px ' + FAM;
  var large = d + ecart + ctx.measureText(SITE).width;
  var x = (W() - large) / 2, cy = y - taille * 0.35;
  pastille(x + d / 2, cy, d / 2);
  pictoReseau('web', x + d / 2, cy, d * 0.62);
  ctx.fillStyle = couleur || '#fff';
  ctx.textAlign = 'left';
  ctx.textBaseline = 'alphabetic';
  ctx.fillText(SITE, x + d + ecart, y);
  ctx.textAlign = 'center';
}

/**
 * Les trois pastilles côte à côte, sans pseudo, pour les cartons déjà
 * chargés. `sec` est le temps écoulé depuis le début de leur arrivée, en
 * secondes : elles se posent l'une après l'autre.
 */
function rangeeReseaux(y, taille, sec){
  var pas = taille * 1.45, x0 = W() / 2 - pas * (RESEAUX.length - 1) / 2;
  for (var i = 0; i < RESEAUX.length; i++) {
    ctx.save();
    ctx.globalAlpha = attenue((sec - i * 0.22) / 0.35);
    pastille(x0 + i * pas, y, taille / 2);
    pictoReseau(RESEAUX[i].id, x0 + i * pas, y, taille * 0.62);
    ctx.restore();
  }
}

/* =====================================================================
   4. Cartons d'intro et d'outro
   ===================================================================== */
function dessinerIntro(id, t){
  var u = U();
  if (id === 'logo') {
    fond(C.navy);
    var a = attenue(t / 0.5), k = 0.75 + 0.25 * a;
    if (logoImg.complete && logoImg.naturalWidth) {
      var s = u * 0.34 * k;
      ctx.save(); ctx.globalAlpha = a;
      ctx.drawImage(logoImg, W() / 2 - s / 2, H() * 0.32 - s / 2, s, s);
      ctx.restore();
    }
    var b = attenue((t - 0.35) / 0.45);
    ctx.save(); ctx.globalAlpha = b;
    texte(ORG, H() * 0.32 + u * 0.28, u * 0.058, '#fff', true);
    texte(SLOGAN, H() * 0.32 + u * 0.36, u * 0.040, C.yellow, true);
    ctx.restore();
    filetTricolore(H() - u * 0.022, u * 0.022, attenue(t / 0.6));

  } else if (id === 'titre') {
    fond(C.navy);
    filetTricolore(0, u * 0.026, attenue(t / 0.4));
    var c = attenue((t - 0.1) / 0.5);
    ctx.save(); ctx.globalAlpha = c;
    ctx.translate(0, (1 - c) * u * 0.06);
    texte(OPT.accroche, H() * 0.48, u * 0.15, '#fff', true);
    texte(OPT.sous, H() * 0.48 + u * 0.11, u * 0.055, C.yellow, true);
    ctx.restore();
    filetTricolore(H() - u * 0.026, u * 0.026, attenue((t - 0.2) / 0.5));

  } else if (id === 'flash') {
    fond('#fff');
    var couleurs = [C.blue, C.yellow, C.red];
    for (var i = 0; i < 3; i++) {
      var d = Math.max(0, Math.min(1, (t - i * 0.08) / 0.34));
      ctx.fillStyle = couleurs[i];
      ctx.fillRect(0, H() * (i / 3), W() * attenue(d), H() / 3 + 1);
    }
    if (t > 0.45) {
      var e = attenue((t - 0.45) / 0.5);
      ctx.save(); ctx.globalAlpha = e;
      fond('rgba(26,61,138,' + e.toFixed(3) + ')');
      texte(OPT.accroche, H() * 0.52, u * 0.14, '#fff', true);
      ctx.restore();
    }

  } else if (id === 'compte') {
    fond(C.navy);
    var n = 3 - Math.floor(t * 3);
    n = Math.max(1, Math.min(3, n));
    var phase = (t * 3) % 1;
    ctx.save();
    ctx.globalAlpha = 1 - Math.pow(phase, 3);
    texte(String(n), H() * 0.5 + u * 0.09, u * 0.34 * (1.25 - 0.25 * phase),
          [C.red, C.yellow, C.blue][n - 1], true);
    ctx.restore();
    texte(OPT.accroche, H() * 0.74, u * 0.055, '#fff', true);
    filetTricolore(H() - u * 0.022, u * 0.022, 1);
  }
}

function dessinerOutro(id, t){
  var u = U(), i;

  /* Les apparitions se calent en SECONDES, pas en fraction du carton :
     rallonger un outro doit ajouter du temps de lecture, pas ralentir
     l'animation. `arrive(debut)` vaut 0 avant `debut`, 1 une fois posé. */
  var duree = (trouver(OUTROS, id) || {}).duree || 1, sec = t * duree;
  function arrive(debut, rampe){ return attenue((sec - debut) / (rampe || 0.35)); }
  var a = arrive(0, 0.5);

  if (id === 'appel') {
    fond(C.navy);
    filetTricolore(0, u * 0.026, 1);
    ctx.save(); ctx.globalAlpha = a;
    var pulsation = 1 + 0.04 * Math.sin(sec * Math.PI * 1.6);
    ctx.translate(W() / 2, H() * 0.31); ctx.scale(pulsation, pulsation); ctx.translate(-W() / 2, -H() * 0.31);
    texte("J'ADHÈRE", H() * 0.33, u * 0.14, C.yellow, true);
    ctx.restore();
    ctx.save(); ctx.globalAlpha = arrive(0.55, 0.4);
    texte(SLOGAN, H() * 0.41, u * 0.046, '#fff', true);
    ctx.restore();
    ctx.save(); ctx.globalAlpha = arrive(0.8, 0.4);
    ligneSite(H() * 0.49, u * 0.052, '#BDD4F5');
    ctx.restore();
    /* Les trois comptes arrivent l'un après l'autre sous l'appel : le
       regard est déjà là, il n'a qu'à descendre. */
    for (i = 0; i < RESEAUX.length; i++) {
      ctx.save();
      ctx.globalAlpha = arrive(1.05 + i * 0.32);
      ligneReseau(RESEAUX[i], H() * (0.65 + i * 0.085), u * 0.040);
      ctx.restore();
    }
    filetTricolore(H() - u * 0.026, u * 0.026, 1);

  } else if (id === 'reseaux') {
    fond(C.navy);
    filetTricolore(0, u * 0.026, 1);
    ctx.save(); ctx.globalAlpha = a;
    texte('SUIS-NOUS', H() * 0.24, u * 0.11, C.yellow, true);
    texte('sur nos réseaux', H() * 0.305, u * 0.044, '#BDD4F5', true);
    ctx.restore();
    for (i = 0; i < RESEAUX.length; i++) {
      ctx.save();
      ctx.globalAlpha = arrive(0.75 + i * 0.42);
      /* Le nom de l'application au-dessus du pseudo : le pictogramme dit
         déjà lequel, le nom lève le doute sur un petit écran. */
      texte(RESEAUX[i].nom.toUpperCase(), H() * (0.45 + i * 0.14) - u * 0.056, u * 0.030, '#8FB2E8', true);
      ligneReseau(RESEAUX[i], H() * (0.45 + i * 0.14), u * 0.046);
      ctx.restore();
    }
    ctx.save(); ctx.globalAlpha = arrive(2.5, 0.4);
    texte(SLOGAN, H() * 0.855, u * 0.046, C.yellow, true);
    ligneSite(H() * 0.915, u * 0.038, '#fff');
    ctx.restore();
    filetTricolore(H() - u * 0.026, u * 0.026, 1);

  } else if (id === 'logo') {
    fond(C.navy);
    if (logoImg.complete && logoImg.naturalWidth) {
      var s = u * 0.40;
      ctx.save(); ctx.globalAlpha = a;
      ctx.drawImage(logoImg, W() / 2 - s / 2, H() * 0.40 - s / 2, s, s);
      ctx.restore();
    }
    ctx.save(); ctx.globalAlpha = arrive(0.6, 0.45);
    texte(SLOGAN, H() * 0.58, u * 0.055, C.yellow, true);
    ligneSite(H() * 0.66, u * 0.042, '#fff');
    ctx.restore();
    /* Carton sobre : les pictogrammes seuls, sans les pseudos, disent où
       nous retrouver sans surcharger la fin du plan. */
    rangeeReseaux(H() * 0.80, u * 0.095, sec - 1.1);
    filetTricolore(H() - u * 0.022, u * 0.022, 1);

  } else if (id === 'contact') {
    fond(C.navy);
    filetTricolore(0, u * 0.022, 1);
    texte('NOUS REJOINDRE', H() * 0.22, u * 0.062, C.yellow, true);
    texte(SLOGAN, H() * 0.285, u * 0.038, '#BDD4F5', true);
    for (i = 0; i < RESEAUX.length; i++) {
      var b = arrive(0.5 + i * 0.35);
      ctx.save(); ctx.globalAlpha = b;
      ctx.translate(0, (1 - b) * u * 0.03);
      ligneReseau(RESEAUX[i], H() * (0.42 + i * 0.09), u * 0.042);
      ctx.restore();
    }
    ctx.save(); ctx.globalAlpha = arrive(1.9, 0.4);
    texte('Site', H() * 0.71, u * 0.030, '#8FB2E8', true);
    ligneSite(H() * 0.765, u * 0.046, '#fff');
    texte('Téléphone', H() * 0.83, u * 0.030, '#8FB2E8', true);
    texte('0696 43 88 21', H() * 0.885, u * 0.050, '#fff', true);
    ctx.restore();
    filetTricolore(H() - u * 0.022, u * 0.022, 1);
  }
}

/* =====================================================================
   5. Dessin d'un plan
   ===================================================================== */
/* Fonds alternés des cartons : deux aplats identiques qui se suivent donnent
   l'impression d'une vidéo figée, l'alternance marque chaque respiration. */
var NUANCES = {
  navy:  { nom:'Bleu nuit',   haut:'#14306E', bas:'#2A55B4', encre:'#FFFFFF' },
  bleu:  { nom:'Bleu ciel',   haut:'#1A3D8A', bas:'#3DAEF5', encre:'#FFFFFF' },
  encre: { nom:'Encre',       haut:'#0B1E45', bas:'#2048A4', encre:'#FFFFFF' },
  jaune: { nom:'Jaune',       haut:'#F5A623', bas:'#FFCD78', encre:'#0B1E45' },
  rouge: { nom:'Rouge',       haut:'#B0061E', bas:'#D0021B', encre:'#FFFFFF' },
  blanc: { nom:'Blanc',       haut:'#FFFFFF', bas:'#E8F0FC', encre:'#0B1E45' }
};

/* Sans couleur choisie, les cartons alternent pour éviter deux aplats
   identiques à la suite. */
var ALTERNANCE = ['navy', 'bleu', 'encre'];

/** Carton de texte plein écran, intercalé entre deux plans. */
function dessinerCarton(clip){
  var n = NUANCES[clip.couleur] || NUANCES[ALTERNANCE[(clip.rang || 0) % ALTERNANCE.length]];
  var degrade = ctx.createLinearGradient(0, 0, 0, H());
  degrade.addColorStop(0, n.haut);
  degrade.addColorStop(1, n.bas);
  ctx.fillStyle = degrade;
  ctx.fillRect(0, 0, W(), H());

  filetsToile();

  var marge = U() * 0.09;
  var taille = U() * 0.082;
  var lignes = decouperToile(clip.texte || '', taille, W() - 2 * marge);

  while (lignes.length > 4 && taille > U() * 0.044) {
    taille -= U() * 0.004;
    lignes = decouperToile(clip.texte || '', taille, W() - 2 * marge);
  }

  var interligne = taille * 1.28;
  var y = (H() - lignes.length * interligne) / 2 + taille;

  // Sur un fond clair, le filet jaune et le texte blanc disparaissent.
  ctx.fillStyle = n.encre === '#FFFFFF' ? C.jaune : n.encre;
  ctx.fillRect(W() / 2 - U() * 0.055, y - taille - U() * 0.062, U() * 0.11, U() * 0.009);

  ctx.save();
  ctx.textAlign = 'center';
  ctx.font = '800 ' + taille + 'px ' + FAM;
  ctx.fillStyle = n.encre;
  lignes.forEach(function (ligne) {
    ctx.fillText(ligne, W() / 2, y);
    y += interligne;
  });
  ctx.restore();

  if (clip.emoji === 'cool') smileyToile(W() / 2, y + U() * 0.045, U() * 0.072);
}

/**
 * Smiley à lunettes, dessiné au trait.
 *
 * Les émojis en couleur ne se dessinent pas de la même façon d'un appareil à
 * l'autre : au rendu comme à l'aperçu, on trace la forme nous-mêmes.
 */
function smileyToile(cx, cy, rayon){
  ctx.save();

  ctx.fillStyle = '#FFCC4D';
  ctx.beginPath();
  ctx.arc(cx, cy, rayon, 0, Math.PI * 2);
  ctx.fill();

  ctx.fillStyle = '#14161C';
  var l = rayon * 0.62, h = rayon * 0.40, y = cy - rayon * 0.24, r = h * 0.42;

  [-1, 1].forEach(function (cote) {
    var x = cx + cote * rayon * 0.34 - l / 2;
    ctx.beginPath();
    if (ctx.roundRect) ctx.roundRect(x, y - h / 2, l, h, r);
    else ctx.rect(x, y - h / 2, l, h);
    ctx.fill();
  });

  ctx.fillRect(cx - rayon * 0.12, y - h * 0.16, rayon * 0.24, h * 0.26);

  ctx.strokeStyle = '#14161C';
  ctx.lineWidth = Math.max(2, rayon * 0.11);
  ctx.lineCap = 'round';
  ctx.beginPath();
  ctx.arc(cx, cy + rayon * 0.08, rayon * 0.52, Math.PI * 0.16, Math.PI * 0.84);
  ctx.stroke();

  ctx.restore();
}

/** Filets tricolores, en haut et en bas de l'image. */
function filetsToile(){
  var h = Math.max(4, U() * 0.0055), t = W() / 3;
  [C.blue, C.jaune, C.rouge].forEach(function (couleur, i) {
    ctx.fillStyle = couleur;
    ctx.fillRect(i * t, 0, t + 1, h);
    ctx.fillRect(i * t, H() - h, t + 1, h);
  });
}

function dessinerPlan(clip, t){
  if (clip.carton) { dessinerCarton(clip); return; }

  var m = clip.media, src = m.element;
  if (!src) { fond('#000'); return; }

  var sw = m.type === 'video' ? src.videoWidth : src.naturalWidth;
  var sh = m.type === 'video' ? src.videoHeight : src.naturalHeight;

  var zoom = 1, dx = 0, dy = 0;
  if (clip.effet === 'zoom')   zoom = 1 + 0.14 * t;
  if (clip.effet === 'dezoom') zoom = 1.14 - 0.14 * t;
  if (clip.effet === 'gauche') { zoom = 1.12; dx = (0.5 - t) * W() * 0.10; }

  fond('#000');
  ctx.save();
  if (clip.effet === 'nb')    ctx.filter = 'grayscale(1) contrast(1.05)';
  if (clip.effet === 'chaud') ctx.filter = 'saturate(1.18) sepia(.18)';
  couvrir(src, sw, sh, zoom, dx, dy);
  ctx.restore();

  legende(clip.texte);
}

/**
 * Légende incrustée en bas de l'image.
 *
 * Un voile sombre derrière le texte : sans lui, une phrase blanche posée sur
 * un ciel ou un tee-shirt clair devient illisible, et c'est justement là que
 * se trouvent la plupart des plans.
 */
function legende(texte){
  if (!texte) return;

  var marge = U() * 0.085;
  var taille = U() * 0.052;
  var lignes = decouperToile(texte, taille, W() - 2 * marge);

  while (lignes.length > 3 && taille > U() * 0.032) {
    taille -= U() * 0.004;
    lignes = decouperToile(texte, taille, W() - 2 * marge);
  }

  var interligne = taille * 1.32;
  var hauteur = lignes.length * interligne + taille * 1.6;
  var haut = H() - hauteur - H() * 0.075;

  /* Voile dégradé : opaque derrière le texte, effacé sur les bords. */
  var voile = ctx.createLinearGradient(0, haut, 0, haut + hauteur);
  voile.addColorStop(0,   'rgba(11,30,69,0)');
  voile.addColorStop(0.35, 'rgba(11,30,69,.72)');
  voile.addColorStop(0.65, 'rgba(11,30,69,.72)');
  voile.addColorStop(1,   'rgba(11,30,69,0)');
  ctx.fillStyle = voile;
  ctx.fillRect(0, haut, W(), hauteur);

  ctx.save();
  ctx.textAlign = 'center';
  ctx.shadowColor = 'rgba(0,0,0,.55)';
  ctx.shadowBlur = taille * 0.18;
  ctx.shadowOffsetY = taille * 0.06;
  ctx.font = '800 ' + taille + 'px ' + FAM;
  ctx.fillStyle = '#FFFFFF';

  var y = haut + taille * 1.5;
  lignes.forEach(function (ligne) {
    ctx.fillText(ligne, W() / 2, y);
    y += interligne;
  });
  ctx.restore();

  /* Petit trait jaune de la charte, sous la dernière ligne. */
  ctx.fillStyle = C.jaune;
  ctx.fillRect(W() / 2 - U() * 0.045, haut + hauteur - taille * 0.42, U() * 0.09, U() * 0.007);
}

/** Découpe une phrase en lignes qui tiennent dans `maxW`. */
function decouperToile(texte, taille, maxW){
  ctx.font = '800 ' + taille + 'px ' + FAM;
  var mots = String(texte).split(/\s+/), lignes = [], cur = '';

  for (var i = 0; i < mots.length; i++) {
    var essai = cur ? cur + ' ' + mots[i] : mots[i];
    if (ctx.measureText(essai).width > maxW && cur) { lignes.push(cur); cur = mots[i]; }
    else { cur = essai; }
  }
  if (cur) lignes.push(cur);

  return lignes;
}

/* =====================================================================
   6. Composition d'une image à l'instant global `temps`
   ===================================================================== */
function sequence(){
  var seq = [], t = 0;
  var intro = trouver(INTROS, OPT.intro);
  if (intro && intro.duree > 0) { seq.push({ genre:'intro', id:intro.id, debut:t, duree:intro.duree }); t += intro.duree; }

  MONTAGE.forEach(function (clip) {
    seq.push({ genre:'plan', clip:clip, debut:t, duree:clip.duree });
    t += clip.duree;
  });

  var outro = trouver(OUTROS, OPT.outro);
  if (outro && outro.duree > 0) { seq.push({ genre:'outro', id:outro.id, debut:t, duree:outro.duree }); t += outro.duree; }

  return { items:seq, total:t };
}

function trouver(liste, id){
  for (var i = 0; i < liste.length; i++) if (liste[i].id === id) return liste[i];
  return null;
}

var DUREE_TRANSITION = 0.45;

function composer(temps, seq){
  var items = seq.items;
  fond('#000');

  for (var i = 0; i < items.length; i++) {
    var it = items[i];
    if (temps < it.debut || temps >= it.debut + it.duree) continue;
    var local = (temps - it.debut) / it.duree;

    if (it.genre === 'intro')      dessinerIntro(it.id, local);
    else if (it.genre === 'outro') dessinerOutro(it.id, local);
    else                           dessinerPlan(it.clip, local);

    /* Transition avec l'élément suivant, jouée sur la fin de celui-ci. */
    var suivant = items[i + 1];
    if (suivant) {
      var reste = it.debut + it.duree - temps;
      var type = suivant.genre === 'plan' ? suivant.clip.transition : 'fondu';
      if (type !== 'coupe' && reste < DUREE_TRANSITION) {
        var p = 1 - reste / DUREE_TRANSITION;
        if (type === 'fondu') {
          ctx.save(); ctx.globalAlpha = p; ctx.fillStyle = '#000';
          ctx.fillRect(0, 0, W(), H()); ctx.restore();
        } else if (type === 'fondublanc') {
          ctx.save(); ctx.globalAlpha = p; ctx.fillStyle = '#fff';
          ctx.fillRect(0, 0, W(), H()); ctx.restore();
        } else if (type === 'glisse') {
          ctx.save(); ctx.fillStyle = C.navy;
          ctx.fillRect(W() * (1 - attenue(p)), 0, W(), H()); ctx.restore();
        }
      }
    }
    break;
  }

  if (OPT.barre) filetTricolore(H() - U() * 0.014, U() * 0.014, 1);
  filigrane();
}

/* =====================================================================
   7. Lecture
   ===================================================================== */
/**
 * Prépare la piste de fond et la remet au début.
 *
 * L'élément est conservé d'une lecture à l'autre : une fois branché sur le
 * graphe audio de l'export, un même élément ne peut plus l'être une seconde
 * fois, et le recréer perdrait ce branchement.
 */
function preparerPiste(){
  if (!OPT.musique) { if (piste) { try { piste.pause(); } catch (e) {} } return null; }

  if (!piste || piste.dataset.url !== OPT.musique) {
    if (piste) { try { piste.pause(); } catch (e) {} }
    piste = new Audio(OPT.musique);
    piste.dataset.url = OPT.musique;
    piste.crossOrigin = 'anonymous';
    piste.loop = true;
    piste.source = null;
  }

  piste.volume = OPT.volume;
  // La position n'est réglable qu'une fois la durée connue ; sur un fichier
  // encore en cours de chargement, on repasse dès que les données arrivent.
  var poser = function () {
    try { piste.currentTime = Math.min(OPT.musiqueDepart, Math.max(0, (piste.duration || 0) - 1)); } catch (e) {}
  };

  if (piste.readyState >= 1) poser();
  else piste.addEventListener('loadedmetadata', poser, { once:true });

  return piste;
}

function arreter(){
  if (fermeturePiste) { clearInterval(fermeturePiste); fermeturePiste = null; }
  if (piste) { try { piste.pause(); piste.volume = OPT.volume; } catch (e) {} }
  if (lecture) {
    cancelAnimationFrame(lecture.trame);
    lecture.videos.forEach(function (v) { try { v.pause(); } catch (e) {} });
    lecture = null;
  }
  document.getElementById('btn-lire').innerHTML = '<i class="fas fa-play"></i> Lire';
}

/**
 * Amène chaque vidéo sur son extrait et attend que l'image soit décodée.
 *
 * Sans cette avance, la première image d'un plan est celle du début du
 * fichier : le déplacement n'a pas eu le temps d'aboutir avant le dessin.
 */
function armerVideos(seq){
  var attentes = [];

  seq.items.forEach(function (it) {
    if (it.genre !== 'plan' || !it.clip.media || it.clip.media.type !== 'video') return;

    var el = it.clip.media.element;
    var cible = it.clip.depart || 0;
    try { el.pause(); } catch (e) {}

    if (el.readyState >= 2 && Math.abs(el.currentTime - cible) < 0.05) return;

    attentes.push(new Promise(function (resolve) {
      var fait = false;
      var fini = function () {
        if (fait) return;
        fait = true;
        el.removeEventListener('seeked', fini);
        resolve();
      };
      el.addEventListener('seeked', fini);
      // Filet : un déplacement qui n'aboutit pas ne doit pas bloquer la lecture.
      setTimeout(fini, 900);
      try { el.currentTime = cible; } catch (e) { fini(); }
    }));
  });

  return Promise.all(attentes);
}

function lire(surFin, surDemarrage){
  arreter();
  var seq = sequence();
  if (!seq.total) { message("Ajoute au moins un plan avant de lire."); return null; }

  // Les vidéos sont calées avant que l'horloge ne démarre : la lecture
  // commence donc sur la bonne image, sans saut ni clignotement. Le calage
  // prend un instant, d'où le rendez-vous donné à l'appelant.
  armerVideos(seq).then(function () {
    if (surDemarrage) surDemarrage();
    demarrer(seq, surFin);
  });

  return seq;
}

function demarrer(seq, surFin){

  var videos = [];
  MONTAGE.forEach(function (c) {
    if (c.media && c.media.type === 'video' && c.media.element) {
      c.media.element.muted = !OPT.son;
      videos.push(c.media.element);
    }
  });

  var fond = preparerPiste();
  if (fond) {
    fond.play().catch(function () {});

    // Aperçu : pas de graphe audio, on baisse le volume à la main sur la
    // dernière seconde pour entendre exactement ce que donnera l'export.
    var depart = Date.now();
    var total = seq.total;
    fermeturePiste = setInterval(function () {
      var reste = total - (Date.now() - depart) / 1000;
      if (reste <= FONDU_SON) {
        fond.volume = Math.max(0, OPT.volume * Math.max(0, reste) / FONDU_SON);
      }
      if (reste <= 0) { clearInterval(fermeturePiste); fermeturePiste = null; }
    }, 60);
  }

  var t0 = performance.now();
  lecture = { videos:videos, trame:0, seq:seq };
  document.getElementById('btn-lire').innerHTML = '<i class="fas fa-pause"></i> En lecture';

  /* Une vidéo n'est jouée que pendant sa fenêtre : on la positionne et on la
     lance au bon moment, sinon toutes joueraient en même temps. */
  var joue = [];

  function trame(){
    var temps = (performance.now() - t0) / 1000;

    seq.items.forEach(function (it, i) {
      if (it.genre !== 'plan' || !it.clip.media || it.clip.media.type !== 'video') return;
      var el = it.clip.media.element;
      var cible = it.clip.depart || 0;
      var dedans = temps >= it.debut && temps < it.debut + it.duree;

      if (dedans && !joue[i]) {
        joue[i] = true;
        // Le plan a normalement été calé à l'avance : on ne redéplace que si
        // ce n'est pas le cas, car un déplacement tardif fait clignoter.
        if (Math.abs(el.currentTime - cible) > 0.4) {
          try { el.currentTime = cible; } catch (e) {}
        }
        try { el.play(); } catch (e) {}
      } else if (!dedans && joue[i]) {
        joue[i] = false;
        try { el.pause(); } catch (e) {}
      } else if (!dedans && !joue[i] && temps > it.debut) {
        // Plan déjà passé : on le remet à son point de départ pour une
        // éventuelle relecture, tant qu'il n'est plus à l'écran.
        if (Math.abs(el.currentTime - cible) > 0.4) {
          try { el.currentTime = cible; } catch (e) {}
        }
      } else if (!dedans && temps >= it.debut - 0.5 && temps < it.debut) {
        // Dernière demi-seconde avant l'entrée : on le cale pendant que le
        // plan précédent occupe encore l'écran.
        if (Math.abs(el.currentTime - cible) > 0.05) {
          try { el.currentTime = cible; } catch (e) {}
        }
      }
    });

    composer(temps, seq);

    if (temps >= seq.total) {
      arreter();
      if (surFin) surFin();
      return;
    }
    lecture.trame = requestAnimationFrame(trame);
  }

  lecture.trame = requestAnimationFrame(trame);
}

/* =====================================================================
   8. Export
   ===================================================================== */
function formatVideo(){
  /* « avc1.42E01E » désigne le profil H.264 Baseline niveau 3.0, plafonné à
     environ 720 x 576. Le navigateur acceptait le type puis réduisait l'image
     pour rentrer dans ce plafond : d'où une vidéo presque paysage, le montage
     vertical étant recollé au centre entre deux bandes noires.
     On laisse désormais le navigateur choisir son profil, et le VP9 passe
     avant le MP4 : il encode n'importe quelle taille sans rien rogner. */
  var candidats = ['video/webm;codecs=vp9', 'video/mp4',
                   'video/webm;codecs=vp8', 'video/webm'];
  if (!window.MediaRecorder) return null;
  for (var i = 0; i < candidats.length; i++) {
    if (MediaRecorder.isTypeSupported(candidats[i])) return candidats[i];
  }
  return null;
}

function exporter(){
  var seq = sequence();
  if (!seq.total) { message("Ajoute au moins un plan avant d'exporter."); return; }

  var mime = formatVideo();
  if (!mime) { message("Ce navigateur ne sait pas enregistrer de vidéo. Essaie avec Chrome ou Edge."); return; }

  /* La toile est remise à la taille voulue juste avant la capture : le flux
     hérite de la taille du fond de toile au moment de captureStream(). */
  if (toile.width !== OPT.largeur || toile.height !== OPT.hauteur) {
    toile.width = OPT.largeur;
    toile.height = OPT.hauteur;
    // Redimensionner une toile remet ses réglages à zéro, celui-ci compris.
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    apercu();
  }

  var flux = toile.captureStream(30);

  /* Contrôle : si le navigateur n'enregistre pas à la taille demandée, mieux
     vaut le dire tout de suite que de livrer un fichier au mauvais format. */
  var pisteVideo = flux.getVideoTracks()[0];
  var reglages = (pisteVideo && pisteVideo.getSettings) ? pisteVideo.getSettings() : {};
  var largeurReelle = reglages.width || OPT.largeur;
  var hauteurReelle = reglages.height || OPT.hauteur;

  if (largeurReelle !== OPT.largeur || hauteurReelle !== OPT.hauteur) {
    message('Attention : le navigateur enregistre en ' + largeurReelle + ' × ' + hauteurReelle
            + ' au lieu de ' + OPT.largeur + ' × ' + OPT.hauteur + '.');
  }

  /* Le son des vidéos, s'il est demandé, est mixé dans un même flux. */
  var contexte = null;
  if (OPT.son || OPT.musique) {
    try {
      contexte = new (window.AudioContext || window.webkitAudioContext)();
      var sortie = contexte.createMediaStreamDestination();

      if (OPT.son) {
        MONTAGE.forEach(function (c) {
          if (!c.media || c.media.type !== 'video' || !c.media.element) return;
          if (!c.media.source) c.media.source = contexte.createMediaElementSource(c.media.element);
          c.media.source.connect(sortie);
        });
      }

      /* La musique passe par un gain : le curseur de volume agit sur le
         mixage, pas seulement sur ce qu'on entend dans l'aperçu. */
      var fondExport = preparerPiste();
      if (fondExport) {
        if (!fondExport.source) fondExport.source = contexte.createMediaElementSource(fondExport);
        if (!fondExport.gain) fondExport.gain = contexte.createGain();
        var horloge = contexte.currentTime;
        var fin = Math.max(0, seq.total - FONDU_SON);
        fondExport.gain.gain.setValueAtTime(OPT.volume, horloge);
        fondExport.gain.gain.setValueAtTime(OPT.volume, horloge + fin);
        fondExport.gain.gain.linearRampToValueAtTime(0.0001, horloge + seq.total);
        fondExport.source.connect(fondExport.gain);
        fondExport.gain.connect(sortie);
        fondExport.gain.connect(contexte.destination);
        fondExport.play().catch(function () {});
      }

      sortie.stream.getAudioTracks().forEach(function (p) { flux.addTrack(p); });
    } catch (e) {
      message("Le son n'a pas pu être capté ; la vidéo sera muette.");
    }
  }

  var morceaux = [];
  /* Un débit fixe de 6 Mbit/s suffisait à un plan calme, pas à une foule
     ou à un nuage de poudre colorée : l'encodeur rendait alors une image
     pâteuse. On le calcule sur la définition réelle, en bits par pixel et
     par image, ce qui suit automatiquement le format choisi. */
  var debit = Math.round(largeurReelle * hauteurReelle * 30 * QUALITES[OPT.qualite]);

  var enregistreur = new MediaRecorder(flux, {
    mimeType: mime,
    videoBitsPerSecond: debit,
    audioBitsPerSecond: 160000
  });
  enregistreur.ondataavailable = function (e) { if (e.data && e.data.size) morceaux.push(e.data); };

  enregistreur.onstop = function () {
    var ext = mime.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
    var blob = new Blob(morceaux, { type:mime });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'mja-reel-' + largeurReelle + 'x' + hauteurReelle + '.' + ext;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 5000);
    if (contexte) { try { contexte.close(); } catch (e) {} }
    message('Vidéo exportée — ' + largeurReelle + ' × ' + hauteurReelle + ', '
            + ext.toUpperCase() + ', ' + seq.total.toFixed(1).replace('.', ',') + ' s, '
            + Math.round(debit / 1000000) + ' Mbit/s, ' + Math.round(blob.size / 1048576) + ' Mo.');
    boutons(false);
  };

  boutons(true);
  message('Préparation des plans…');

  /* L'enregistreur ne démarre qu'au premier instant réellement joué : lancé
     avant le calage des vidéos, il capturait une toile encore vide. */
  lire(
    function () { setTimeout(function () { enregistreur.stop(); }, 200); },
    function () {
      enregistreur.start();
      message('Enregistrement en cours — laisse cet onglet au premier plan pendant '
              + seq.total.toFixed(1).replace('.', ',') + ' s…');
    }
  );
}

function boutons(occupe){
  ['btn-lire', 'btn-stop', 'btn-export'].forEach(function (id) {
    document.getElementById(id).disabled = occupe;
  });
}

function message(txt){
  var e = document.getElementById('etat');
  e.textContent = txt;
  e.classList.add('actif');
}

/* =====================================================================
   9. Interface
   ===================================================================== */
function chargerMedia(m){
  if (m.element) return Promise.resolve(m);
  return new Promise(function (resolve) {
    if (m.type === 'video') {
      var v = document.createElement('video');
      v.src = m.url; v.preload = 'auto'; v.muted = true; v.playsInline = true; v.crossOrigin = 'anonymous';
      v.addEventListener('loadedmetadata', function () {
        m.element = v;
        m.duree = isFinite(v.duration) ? v.duration : 3;
        resolve(m);
      });
      v.addEventListener('error', function () { m.element = null; resolve(m); });
    } else {
      var i = new Image();
      i.crossOrigin = 'anonymous';
      i.onload = function () { m.element = i; resolve(m); };
      i.onerror = function () { m.element = null; resolve(m); };
      i.src = m.url;
    }
  });
}

/** Texte sûr dans une chaîne HTML construite à la main. */
function echapper(t){
  return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Charge un montage tout prêt.
 *
 * L'ébauche ne fait que poser un point de départ : elle remplace la timeline,
 * pose l'intro, l'outro et les accroches, puis rend la main. Tout reste
 * modifiable ensuite, plan par plan.
 */
function appliquerEbauche(id){
  var e = null;
  for (var i = 0; i < EBAUCHES.length; i++) if (EBAUCHES[i].id === id) e = EBAUCHES[i];
  if (!e) return;

  if (MONTAGE.length && !confirm('Remplacer le montage en cours par « ' + e.nom + ' » ?')) return;

  /* Un plan se cite par son nom, ou par un objet qui resserre l'extrait.
     Un objet sans fichier est un carton : une phrase sur aplat. */
  var choix = e.plans.map(function (entree) {
    if (typeof entree === 'object' && entree.carton) {
      return { media: null, reglage: entree };
    }
    var nom = typeof entree === 'string' ? entree : entree.fichier;
    for (var i = 0; i < BIBLIO.length; i++) {
      if (BIBLIO[i].nom === nom) {
        return { media: BIBLIO[i], reglage: typeof entree === 'string' ? {} : entree };
      }
    }
    return null;
  }).filter(Boolean);

  var medias = choix.map(function (c) { return c.media; }).filter(Boolean);

  arreter();
  message('Préparation de « ' + e.nom + ' »…');

  Promise.all(medias.map(chargerMedia)).then(function () {
    var rang = 0;

    MONTAGE = choix.filter(function (c) {
      return c.reglage.carton || (c.media && c.media.element);
    }).map(function (c) {
      if (c.reglage.carton) {
        return {
          media: null,
          carton: true,
          rang: rang++,
          emoji: c.reglage.emoji || null,
          couleur: c.reglage.couleur || '',
          texte: c.reglage.carton,
          duree: c.reglage.duree || 1.5,
          effet: 'aucun',
          transition: e.transition || OPT.transition,
          depart: 0
        };
      }

      var m = c.media;
      var depart = c.reglage.depart !== undefined ? c.reglage.depart : (m.depart || 0);

      return {
        media: m,
        duree: c.reglage.duree || m.dureeConseillee || (m.type === 'photo' ? OPT.dureePhoto : 3),
        effet: m.type === 'photo' ? 'zoom' : 'aucun',
        transition: e.transition || OPT.transition,
        depart: Math.min(depart, Math.max(0, (m.duree || 0) - 0.4)),
        texte: c.reglage.texte || ''
      };
    });

    var perdus = choix.length - MONTAGE.length;

    OPT.intro = e.intro || OPT.intro;
    OPT.outro = e.outro || OPT.outro;
    OPT.transition = e.transition || OPT.transition;
    if (e.accroche) OPT.accroche = e.accroche;
    if (e.sous) OPT.sous = e.sous;
    refletterOptions();

    dessinerMontage();
    apercu();
    message('« ' + e.nom + ' » chargé : ' + MONTAGE.length + ' plans'
            + (perdus ? ', ' + perdus + ' illisible(s)' : '')
            + '. Réordonne, ajuste les durées, puis exporte.');
  });
}

/** Recopie OPT dans les champs du panneau de réglages. */
function refletterOptions(){
  // On simule une saisie plutôt que d'écrire la valeur en douce : les
  // gestionnaires déjà branchés remettent OPT et les aides à jour tout seuls.
  [['opt-intro', 'intro', 'change'], ['opt-outro', 'outro', 'change'],
   ['opt-transition', 'transition', 'change'], ['opt-accroche', 'accroche', 'input'],
   ['opt-sous', 'sous', 'input']].forEach(function (paire) {
    var champ = document.getElementById(paire[0]);
    if (! champ || champ.value === OPT[paire[1]]) return;
    champ.value = OPT[paire[1]];
    champ.dispatchEvent(new Event(paire[2]));
  });
}

function dessinerBibliotheque(){
  var zone = document.getElementById('media');
  zone.innerHTML = '';
  BIBLIO.forEach(function (m, i) {
    var d = document.createElement('div');
    d.className = 'vignette';
    d.title = [m.titre || m.nom, m.lieu, m.note, m.poids ? m.poids + ' Mo' : '']
                .filter(Boolean).join(' — ');
    /* Le repere de depart est plus parlant que la premiere image, souvent
       floue ou prise pendant que la camera se leve. */
    var vignette = m.type === 'video'
        ? '<video src="' + m.url + '#t=' + (m.depart || 0.5).toFixed(1) + '" muted preload="metadata"></video>'
        : '<img src="' + m.url + '" alt="">';
    d.innerHTML = vignette
      + '<span class="t">' + (m.type === 'video' ? 'VIDÉO' : 'PHOTO') + '</span>'
      + '<span class="plus"><i class="fas fa-plus"></i></span>'
      + (m.titre ? '<span class="lg">' + echapper(m.titre) + '</span>' : '');
    d.addEventListener('click', function () { ajouter(i); });
    zone.appendChild(d);
  });
}

function ajouter(index){
  var m = BIBLIO[index];
  chargerMedia(m).then(function () {
    if (!m.element) { message('Ce fichier n\'a pas pu être lu : ' + m.nom); return; }
    MONTAGE.push({
      media: m,
      /* L'extrait de la fiche prime : il vise le moment qui porte le plan. */
      duree: m.dureeConseillee
             || (m.type === 'video' ? Math.min(m.duree || 3, 4) : OPT.dureePhoto),
      effet: m.type === 'photo' ? 'zoom' : 'aucun',
      transition: OPT.transition,
      depart: Math.min(m.depart || 0, Math.max(0, (m.duree || 0) - 0.4)),
      texte: ''
    });
    dessinerMontage();
    apercu();
  });
}

function dessinerMontage(){
  var zone = document.getElementById('clips');
  zone.innerHTML = '';

  if (!MONTAGE.length) {
    zone.innerHTML = '<div class="vide">Aucun plan pour l\'instant.<br>Clique sur une vignette de la bibliothèque pour l\'ajouter.</div>';
    majDuree();
    return;
  }

  MONTAGE.forEach(function (clip, i) {
    var d = document.createElement('div');
    d.className = 'clip';
    d.draggable = true;
    d.dataset.index = i;

    var apercuHtml = clip.carton
      ? '<i class="fas fa-quote-left"></i>'
      : (clip.media.type === 'video'
          ? '<video src="' + clip.media.url + '#t=' + (clip.depart || 0.5).toFixed(1) + '" muted preload="metadata"></video>'
          : '<img src="' + clip.media.url + '" alt="">');

    var optEffets = Object.keys(EFFETS).map(function (k) {
      return '<option value="' + k + '"' + (clip.effet === k ? ' selected' : '') + '>' + EFFETS[k] + '</option>';
    }).join('');
    var optTrans = Object.keys(TRANSITIONS).map(function (k) {
      return '<option value="' + k + '"' + (clip.transition === k ? ' selected' : '') + '>' + TRANSITIONS[k] + '</option>';
    }).join('');

    d.innerHTML =
      '<div class="apercu">' + apercuHtml + '</div>' +
      '<div class="info"><b>' + (i + 1) + '. '
        + (clip.carton ? 'Carton texte' : clip.media.nom) + '</b>' +
        '<div class="reglages">' +
          '<input type="number" step="0.5" min="0.5" max="15" value="' + clip.duree + '" data-champ="duree" title="Durée en secondes">' +
          (clip.carton
            ? '<select data-champ="couleur" title="Couleur du carton">'
              + '<option value="">Couleur : alternée</option>'
              + Object.keys(NUANCES).map(function (k) {
                  return '<option value="' + k + '"' + (clip.couleur === k ? ' selected' : '') + '>'
                       + NUANCES[k].nom + '</option>';
                }).join('')
              + '</select>'
            : '<select data-champ="effet">' + optEffets + '</select>') +
          '<select data-champ="transition">' + optTrans + '</select>' +
        '</div>' +
        '<input class="legende-champ" type="text" maxlength="90" data-champ="texte" ' +
          'placeholder="' + (clip.carton ? 'Phrase du carton' : 'Texte à l\'écran (facultatif)') + '" '
          + 'value="' + echapper(clip.texte || '') + '">' +
      '</div>' +
      '<div class="actions">' +
        '<button data-act="haut" title="Monter"><i class="fas fa-chevron-up"></i></button>' +
        '<button data-act="bas" title="Descendre"><i class="fas fa-chevron-down"></i></button>' +
        '<button class="sup" data-act="sup" title="Retirer"><i class="fas fa-xmark"></i></button>' +
      '</div>';

    d.querySelectorAll('[data-champ]').forEach(function (ch) {
      var appliquer = function () {
        var v = ch.dataset.champ === 'duree' ? Math.max(0.5, parseFloat(ch.value) || 1) : ch.value;
        MONTAGE[i][ch.dataset.champ] = v;
        majDuree(); apercu();
      };
      ch.addEventListener('change', appliquer);
      // Le texte se voit à la frappe : c'est ainsi qu'on le cale.
      if (ch.dataset.champ === 'texte') ch.addEventListener('input', appliquer);
      ch.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    d.querySelectorAll('[data-act]').forEach(function (b) {
      b.addEventListener('click', function (e) {
        e.stopPropagation();
        var a = b.dataset.act;
        if (a === 'sup') MONTAGE.splice(i, 1);
        else if (a === 'haut' && i > 0) MONTAGE.splice(i - 1, 0, MONTAGE.splice(i, 1)[0]);
        else if (a === 'bas' && i < MONTAGE.length - 1) MONTAGE.splice(i + 1, 0, MONTAGE.splice(i, 1)[0]);
        dessinerMontage(); apercu();
      });
    });

    /* Réordonnancement par glisser-déposer. */
    d.addEventListener('dragstart', function (e) {
      e.dataTransfer.setData('text/plain', String(i));
      d.classList.add('tire');
    });
    d.addEventListener('dragend', function () { d.classList.remove('tire'); });
    d.addEventListener('dragover', function (e) { e.preventDefault(); d.classList.add('cible'); });
    d.addEventListener('dragleave', function () { d.classList.remove('cible'); });
    d.addEventListener('drop', function (e) {
      e.preventDefault();
      d.classList.remove('cible');
      var de = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (isNaN(de) || de === i) return;
      MONTAGE.splice(i, 0, MONTAGE.splice(de, 1)[0]);
      dessinerMontage(); apercu();
    });

    zone.appendChild(d);
  });

  majDuree();
}

function majDuree(){
  var seq = sequence();
  document.getElementById('duree').textContent = seq.total.toFixed(1).replace('.', ',') + ' s';
  document.getElementById('cpt-clips').textContent =
    MONTAGE.length + ' plan' + (MONTAGE.length > 1 ? 's' : '');
}

/** Image fixe de repère : première image de la séquence. */
function apercu(){
  if (lecture) return;
  var seq = sequence();
  composer(seq.total ? 0.02 : 0, seq);
}

/* ── Réglages ───────────────────────────────────────────────────── */
function remplirListe(id, liste, valeur, aideId){
  var s = document.getElementById(id);
  s.innerHTML = liste.map(function (o) {
    return '<option value="' + o.id + '"' + (o.id === valeur ? ' selected' : '') + '>'
         + o.nom + (o.duree ? ' — ' + String(o.duree).replace('.', ',') + ' s' : '') + '</option>';
  }).join('');
  var maj = function () {
    var o = trouver(liste, s.value);
    document.getElementById(aideId).textContent = o ? o.aide : '';
  };
  s.addEventListener('change', maj);
  maj();
}

function brancher(){
  var champQualite = document.getElementById('opt-qualite');
  if (champQualite) {
    champQualite.addEventListener('change', function () {
      OPT.qualite = this.value;
      var debit = Math.round(OPT.largeur * OPT.hauteur * 30 * QUALITES[OPT.qualite] / 1000000);
      message('Export à ' + debit + ' Mbit/s environ.');
    });
  }

  document.getElementById('opt-format').addEventListener('change', function () {
    var d = this.value.split('x');
    OPT.largeur = +d[0]; OPT.hauteur = +d[1];
    toile.width = OPT.largeur; toile.height = OPT.hauteur;
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    apercu();
    message(OPT.hauteur > OPT.largeur
      ? 'Format vertical ' + OPT.largeur + ' × ' + OPT.hauteur + ' — bon pour un réel.'
      : 'Attention : format paysage ' + OPT.largeur + ' × ' + OPT.hauteur
        + '. Un réel Instagram se publie en 1080 × 1920.');
  });

  ['intro', 'outro'].forEach(function (cle) {
    document.getElementById('opt-' + cle).addEventListener('change', function () {
      OPT[cle] = this.value; majDuree(); apercu();
    });
  });

  var champMusique = document.getElementById('opt-musique');
  var champVolume = document.getElementById('opt-volume');
  var blocVolume = document.getElementById('bloc-volume');

  if (champMusique) {
    champMusique.addEventListener('change', function () {
      OPT.musique = this.value;
      blocVolume.style.display = this.value ? '' : 'none';

      /* Plusieurs licences Creative Commons imposent de citer l'auteur :
         le crédit est affiché pour être recopié dans la publication. */
      var credit = this.options[this.selectedIndex].dataset.credit || '';
      document.getElementById('credit-musique').textContent =
        credit ? 'À créditer dans la publication : ' + credit : '';

      OPT.musiqueDepart = 0;
      if (window.majBorneDepart) window.majBorneDepart();
      preparerPiste();
      message(this.value
        ? 'Musique choisie : elle sera mixée à l\'export.'
        : 'Export muet — le bon choix pour un réel Instagram.');
    });
  }

  if (champVolume) {
    champVolume.addEventListener('input', function () {
      OPT.volume = this.value / 100;
      if (piste) piste.volume = OPT.volume;
    });
  }

  var champDepart = document.getElementById('opt-depart-musique');
  var etiquette = document.getElementById('etiquette-depart');
  var ecouter = document.getElementById('btn-ecouter');
  var arretEcoute = null;

  function minutes(secondes){
    var m = Math.floor(secondes / 60);
    var s = Math.round(secondes % 60);
    return m + ':' + (s < 10 ? '0' : '') + s;
  }

  if (champDepart) {
    champDepart.addEventListener('input', function () {
      OPT.musiqueDepart = +this.value;
      etiquette.textContent = minutes(OPT.musiqueDepart);
      if (piste) { try { piste.currentTime = OPT.musiqueDepart; } catch (e) {} }
    });
  }

  if (ecouter) {
    ecouter.addEventListener('click', function () {
      var p = preparerPiste();
      if (!p) return;
      if (arretEcoute) clearTimeout(arretEcoute);
      p.play().catch(function () {});
      arretEcoute = setTimeout(function () { try { p.pause(); } catch (e) {} }, 5000);
    });
  }

  /* La borne du curseur suit la durée du morceau choisi. */
  window.majBorneDepart = function () {
    if (!champDepart) return;
    var p = preparerPiste();
    if (!p) { champDepart.max = 0; return; }
    var regler = function () {
      champDepart.max = Math.max(0, Math.floor((p.duration || 0) - 5));
      if (OPT.musiqueDepart > champDepart.max) {
        OPT.musiqueDepart = 0;
        champDepart.value = 0;
        etiquette.textContent = '0:00';
      }
    };
    if (p.readyState >= 1) regler();
    else p.addEventListener('loadedmetadata', regler, { once:true });
  };

  document.getElementById('opt-accroche').addEventListener('input', function () {
    OPT.accroche = this.value.toUpperCase(); apercu();
  });
  document.getElementById('opt-sous').addEventListener('input', function () {
    OPT.sous = this.value.toUpperCase(); apercu();
  });
  document.getElementById('opt-transition').addEventListener('change', function () {
    OPT.transition = this.value;
    MONTAGE.forEach(function (c) { c.transition = OPT.transition; });
    dessinerMontage(); apercu();
  });
  document.getElementById('opt-duree').addEventListener('change', function () {
    OPT.dureePhoto = parseFloat(this.value);
    MONTAGE.forEach(function (c) { if (c.media && c.media.type === 'photo') c.duree = OPT.dureePhoto; });
    dessinerMontage(); apercu();
  });
  ['logo', 'barre', 'son'].forEach(function (cle) {
    document.getElementById('opt-' + cle).addEventListener('change', function () {
      OPT[cle] = this.checked; apercu();
    });
  });

  document.getElementById('btn-lire').addEventListener('click', function () {
    if (lecture) { arreter(); apercu(); } else { lire(); }
  });
  document.getElementById('btn-stop').addEventListener('click', function () { arreter(); apercu(); });
  document.getElementById('btn-export').addEventListener('click', exporter);
  document.getElementById('btn-vider').addEventListener('click', function () {
    if (MONTAGE.length && !confirm('Vider le montage ?')) return;
    MONTAGE = []; arreter(); dessinerMontage(); apercu();
    message('Montage vidé.');
  });

  /* Dépôt de fichiers */
  var depot = document.getElementById('depot'), champ = document.getElementById('fichiers');
  depot.addEventListener('click', function () { champ.click(); });
  champ.addEventListener('change', function () { recevoir(this.files); this.value = ''; });
  ['dragenter', 'dragover'].forEach(function (e) {
    depot.addEventListener(e, function (ev) { ev.preventDefault(); depot.classList.add('survol'); });
  });
  ['dragleave', 'drop'].forEach(function (e) {
    depot.addEventListener(e, function (ev) { ev.preventDefault(); depot.classList.remove('survol'); });
  });
  depot.addEventListener('drop', function (ev) { recevoir(ev.dataTransfer.files); });
}

function recevoir(fichiers){
  var n = 0;
  Array.prototype.forEach.call(fichiers, function (f) {
    var estVideo = f.type.indexOf('video') === 0;
    if (!estVideo && f.type.indexOf('image') !== 0) return;
    BIBLIO.push({
      type: estVideo ? 'video' : 'photo',
      url: URL.createObjectURL(f),
      nom: f.name,
      poids: Math.round(f.size / 1048576 * 10) / 10
    });
    n++;
  });
  if (n) { dessinerBibliotheque(); message(n + ' fichier(s) ajouté(s) à la bibliothèque.'); }
  else message('Aucun fichier vidéo ou image reconnu.');
}

/* ── Démarrage ──────────────────────────────────────────────────── */
remplirListe('opt-intro', INTROS, OPT.intro, 'aide-intro');
remplirListe('opt-outro', OUTROS, OPT.outro, 'aide-outro');
brancher();

var boutonCarton = document.getElementById('btn-carton');
if (boutonCarton) {
  boutonCarton.addEventListener('click', function () {
    var rangs = MONTAGE.filter(function (c) { return c.carton; }).length;
    MONTAGE.push({
      media: null, carton: true, rang: rangs, couleur: '',
      texte: 'Une phrase courte', duree: 1.5,
      effet: 'aucun', transition: OPT.transition, depart: 0
    });
    dessinerMontage();
    apercu();
    message('Carton ajouté — écris ta phrase dans le champ du plan.');
  });
}

Array.prototype.forEach.call(document.querySelectorAll('[data-ebauche]'), function (bouton) {
  bouton.addEventListener('click', function () { appliquerEbauche(bouton.dataset.ebauche); });
});
dessinerBibliotheque();
dessinerMontage();
logoImg.onload = apercu;
apercu();

})();
</script>
</body>
</html>
