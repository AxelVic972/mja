<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- ── Titre dynamique ──────────────────────────────────────── --}}
    <title>@yield('title', "Madin'Jeunes Ambition")</title>

    {{-- ── Favicon ──────────────────────────────────────────────── --}}
    <link rel="icon" type="image/jpeg" sizes="any" href="{{ asset('images/logo.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">

    {{-- ── SEO ─────────────────────────────────────────────────── --}}
    <meta name="description" content="@yield('meta_description', "Madin'Jeunes Ambition — Association de jeunes bénévoles en Martinique et au-delà. Actions éducatives, culturelles, sociales, sportives et de santé.")">
    <meta name="keywords" content="MJA, Madin'Jeunes Ambition, Martinique, Guadeloupe, Guyane, jeunesse, bénévolat, association, engagement">
    <meta name="author" content="Madin'Jeunes Ambition">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- ── Aperçu de partage ────────────────────────────────────────
         Ce qui s'affiche quand le lien est collé sur WhatsApp, Facebook,
         LinkedIn ou dans un SMS. La vignette est résolue en adresse absolue
         et mesurée : un chemin relatif ne donne aucun aperçu, et des
         dimensions fausses font rogner l'image de travers.
         Une page peut fournir @section('og_image') et @section('og_title') ;
         sinon on retombe sur la vignette de l'association et le titre. --}}
    @php
        $partage = \App\Support\Partage::class;
        $partageTitre = $partage::texte($__env->yieldContent('og_title'))
            ?: $partage::texte($__env->yieldContent('title', "Madin'Jeunes Ambition"));
        $partageTexte = $partage::resume($partage::texte(
            $__env->yieldContent('meta_description', "Association de jeunes engagés en Martinique et au-delà. Actions éducatives, culturelles, sociales, sportives et de santé.")
        ));
        $partageImage = $partage::image($partage::texte($__env->yieldContent('og_image')));
        $partageTaille = $partage::taille($partageImage);
        $partageAlt = $partage::texte($__env->yieldContent('og_image_alt')) ?: $partageTitre;
    @endphp
    <meta property="og:type"        content="@yield('og_type', 'website')">
    <meta property="og:site_name"   content="Madin'Jeunes Ambition">
    <meta property="og:title"       content="{{ $partageTitre }}">
    <meta property="og:description" content="{{ $partageTexte }}">
    <meta property="og:image"       content="{{ $partageImage }}">
    <meta property="og:image:alt"   content="{{ $partageAlt }}">
    @if($partageTaille)
    <meta property="og:image:width"  content="{{ $partageTaille[0] }}">
    <meta property="og:image:height" content="{{ $partageTaille[1] }}">
    @endif
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta property="og:locale"      content="fr_FR">

    {{-- ── Twitter Card ─────────────────────────────────────────── --}}
    <meta name="twitter:card"        content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title"       content="{{ $partageTitre }}">
    <meta name="twitter:description" content="{{ $partageTexte }}">
    <meta name="twitter:image"       content="{{ $partageImage }}">
    <meta name="twitter:image:alt"   content="{{ $partageAlt }}">

    {{-- ── Préchargement des polices critiques (au-dessus de la ligne de flottaison) ──
         Gill Sans (corps + gras) et AllRound Gothic Bold (gros titres du hero,
         classe .font-display.font-black). Servies depuis notre domaine et
         préchargées, elles arrivent avant le premier rendu : plus aucun échange
         de police après peinture → supprime le CLS de 0.149 mesuré par Lighthouse.
         crossorigin est requis même en same-origin (les polices sont toujours
         récupérées en mode CORS). --}}
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Gill_Sans.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/Gill_Sans_Bold.woff2') }}" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/AllRoundGothic-Bold.woff2') }}" crossorigin>

    {{-- ── Tailwind CSS (compilé en local, statique) ──────────── --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    {{-- ── Polices Google Fonts (local → CDN fallback) ──────────── --}}
    <link rel="stylesheet" href="{{ asset('css/gill-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}"
          onerror="this.onerror=null;var l=document.createElement('link');l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap';document.head.appendChild(l)">

    {{-- ── Font Awesome (local → CDN fallback) ────────────────── --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}"
          onerror="this.onerror=null;var l=document.createElement('link');l.rel='stylesheet';l.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';document.head.appendChild(l)">

    {{-- ── Meta Pixel ──────────────────────────────────────────── --}}
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1060600316583859');
    fbq('track', 'PageView');
    </script>

    <style>
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Gill Sans', 'Open Sans', sans-serif; color: #333333; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family: 'Gill Sans', 'Montserrat', sans-serif; }
        .font-round { font-family: 'AllRound Gothic', 'Gill Sans', sans-serif; }
        /* Gros titres (graisse « black ») en AllRound Gothic ; le reste reste en Gill Sans */
        .font-display.font-black { font-family: 'AllRound Gothic', 'Gill Sans', sans-serif; }

        .hero-gradient {
            background: linear-gradient(135deg, #1A3D8A 0%, #2048A4 45%, #3262CC 100%);
        }
        .card-hover { transition: transform .2s ease, box-shadow .2s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(74,144,226,.15); }

        /* Accent bar top of cards */
        .card-accent-blue  { border-top: 3px solid #3DAEF5; }
        .card-accent-yellow{ border-top: 3px solid #F5A623; }
        .card-accent-red   { border-top: 3px solid #D0021B; }

        /* Ring motif watermark - uses pseudo-element to avoid overriding background-image */
        .ring-watermark { position: relative; overflow: hidden; }
        .ring-watermark::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='100' r='90' fill='none' stroke='%233DAEF5' stroke-width='1' opacity='0.12'/%3E%3Ccircle cx='100' cy='100' r='70' fill='none' stroke='%23F5A623' stroke-width='1' opacity='0.09'/%3E%3Ccircle cx='100' cy='100' r='50' fill='none' stroke='%23D0021B' stroke-width='1' opacity='0.07'/%3E%3C/svg%3E");
            background-size: 280px;
            pointer-events: none;
            z-index: 0;
        }
        .ring-watermark > * { position: relative; z-index: 1; }

        .nav-link { transition: color .15s; }
        .nav-link:hover { color: #1A7BB8; }
        .nav-link.active { color: #1A7BB8; }
        .btn-blue { background: #1A7BB8; color: white; }
        .btn-blue:hover { background: #15679B; }
        .btn-yellow { background: #F5A623; color: #14264D; }
        .btn-yellow:hover { background: #e0941a; }

        /* Contenus rédigés dans l'éditeur du back-office : Tailwind remet à zéro
           listes et titres, il faut donc les réhabiller ici. text-align posé sur
           les blocs par l'éditeur reste prioritaire (aucune règle ne l'écrase). */
        .prose-mja p { margin: 0 0 1em; }
        .prose-mja p:last-child { margin-bottom: 0; }
        .prose-mja ul, .prose-mja ol { margin: 0 0 1em; padding-left: 1.5rem; }
        .prose-mja ul { list-style: disc; }
        .prose-mja ol { list-style: decimal; }
        .prose-mja li { margin-bottom: .35em; }
        .prose-mja h2, .prose-mja h3, .prose-mja h4 {
            font-family: 'Gill Sans', 'Montserrat', sans-serif;
            font-weight: 700; color: #14264D; margin: 1.6em 0 .6em; line-height: 1.3;
        }
        .prose-mja h2 { font-size: 1.4em; }
        .prose-mja h3 { font-size: 1.2em; }
        .prose-mja h4 { font-size: 1.05em; }
        .prose-mja a { color: #1A7BB8; text-decoration: underline; }
        .prose-mja a:hover { color: #15679B; }
        .prose-mja blockquote {
            margin: 0 0 1em; padding: .25em 0 .25em 1rem;
            border-left: 3px solid #F5A623; color: #555; font-style: italic;
        }
        .prose-mja strong { font-weight: 700; color: #14264D; }

        /* Éditeur du back-office : la zone de saisie montre le même rendu que
           le site public, sans quoi l'alignement se juge à l'aveugle. */
        .er-zone p { margin: 0 0 .8em; }
        .er-zone ul, .er-zone ol { margin: 0 0 .8em; padding-left: 1.5rem; }
        .er-zone ul { list-style: disc; }
        .er-zone ol { list-style: decimal; }
        .er-zone h2, .er-zone h3, .er-zone h4 { font-weight: 700; color: #14264D; margin: 1em 0 .4em; }
        .er-zone h3 { font-size: 1.15em; }
        .er-zone a { color: #1A7BB8; text-decoration: underline; }

        /* Barre de navigation.
           Le menu compte désormais un onglet de plus (Administration) : à 768 px
           les libellés passaient à la ligne. Le menu complet n'apparaît donc
           qu'à partir de 1024 px, sans retour à la ligne possible, et les
           libellés se resserrent entre 1024 et 1280 px. Écrit en CSS et non en
           classes Tailwind : la feuille du site est précompilée. */
        .nav-desktop { display: none; }
        .nav-desktop > * { white-space: nowrap; }
        .nav-burger { display: inline-flex; }
        @media (min-width: 1024px) {
            .nav-desktop { display: flex; flex-wrap: nowrap; align-items: center; }
            .nav-burger { display: none; }
            .nav-mobile { display: none !important; }
        }
        @media (min-width: 1024px) and (max-width: 1279px) {
            .nav-desktop { gap: 0; }
            .nav-desktop a { font-size: .875rem; }
            .nav-desktop > a { padding-left: .5rem; padding-right: .5rem; margin-left: 0; }
        }

        /* Accessibilité : focus clavier visible */
        a:focus-visible, button:focus-visible, input:focus-visible,
        select:focus-visible, textarea:focus-visible, [tabindex]:focus-visible {
            outline: 3px solid #1E93D6;
            outline-offset: 2px;
            border-radius: 4px;
        }
        /* Respect de la préférence « animations réduites » */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; scroll-behavior: auto !important; }
        }
    </style>

    {{-- ── Données structurées (JSON-LD) ────────────────────────── --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "NGO",
        "name": "Madin'Jeunes Ambition",
        "alternateName": "MJA",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('images/logomjat.png') }}",
        "foundingDate": "2011",
        "description": "Association de jeunes bénévoles en Martinique et au-delà. Actions éducatives, culturelles, sociales, sportives et de santé.",
        "areaServed": {
            "@@type": "AdministrativeArea",
            "name": "Martinique et au-delà"
        },
        "email": "{{ config('mja.contact_email') }}",
        "sameAs": [
            "https://www.facebook.com/MadinJeunesAmbition/",
            "https://www.instagram.com/madin_jeunes_ambition/",
            "https://www.tiktok.com/@fwi_ti_dej",
            "https://www.youtube.com/channel/UCX6nyVEv_QyFuLREyVvOMLw"
        ]
    }
    </script>

    @stack('head')
</head>
<body class="bg-white">

    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=1060600316583859&ev=PageView&noscript=1"
        alt=""></noscript>

    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:top-2 focus:left-2 focus:bg-white focus:text-mja-dark focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:font-display focus:font-bold">Aller au contenu principal</a>

    <!-- Navbar + bandeau saisonnier dans un seul bloc sticky -->
    <div class="sticky top-0 z-50 shadow-sm">
    <nav class="bg-white border-b border-gray-100" aria-label="Navigation principale">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Logo (saisonnier : dépose le fichier dans public/images/logos/ pour l'activer) -->
                @php
                $mois = (int) date('n');
                $logoSaisonnier = match(true) {
                    $mois === 12                   => '/images/logos/noel.png',
                    $mois === 1                    => '/images/logos/nouvel-an.png',
                    $mois === 6                    => '/images/logos/bac-ti-dej.png',
                    in_array($mois, [7, 8])        => '/images/logos/ete.png',
                    $mois === 9                    => '/images/logos/rentree.png',
                    default                        => null,
                };
                $logoNav = ($logoSaisonnier && file_exists(public_path(ltrim($logoSaisonnier,'/'))))
                    ? $logoSaisonnier
                    : '/images/logomjat.png';

                // Liens de navigation (SNS masqué tant que config('mja.show_sns') = false)
                $navLinks = [
                    ['Accueil', 'home', ''],
                    ['À propos', 'about', ''],
                    ['Projets', 'projects.index', 'projects.*'],
                ];
                if (config('mja.show_sns')) {
                    $navLinks[] = ['SNS', 'sns', 'sns'];
                }
                $navLinks = array_merge($navLinks, [
                    ['Événements', 'events.index', 'events.*'],
                    ['Actualités', 'articles.index', 'articles.*'],
                    ['Ressources', 'resources.index', 'resources.*'],
                ]);
                @endphp
                <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                    <img src="{{ $logoNav }}" alt="MJA Logo"
                         class="h-10 w-auto object-contain">
                    <div class="hidden sm:block leading-tight">
                        <div class="font-round font-black text-base tracking-tight">
                            <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span>
                        </div>
                        <div class="text-gray-500 text-xs font-display font-semibold tracking-widest uppercase" style="font-size:9px;">Madin' Jeunes Ambition</div>
                    </div>
                </a>

                <!-- Desktop nav -->
                <div class="nav-desktop gap-1">
                    @foreach($navLinks as [$label, $route, $pattern])
                    <a href="{{ route($route) }}"
                       @if(request()->routeIs($pattern ?: $route)) aria-current="page" @endif
                       class="nav-link px-3 py-2 rounded-lg text-base font-semibold font-display transition-colors
                              {{ request()->routeIs($pattern ?: $route) ? 'active' : 'text-gray-600' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                    <a href="{{ route('search') }}" aria-label="Rechercher sur le site"
                       class="ml-1 flex items-center justify-center w-10 h-10 rounded-lg transition-colors {{ request()->routeIs('search') ? 'text-mja-blue' : 'text-gray-600 hover:text-mja-blue' }}">
                        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    </a>
                    <a href="{{ auth()->check() ? route('member.dashboard') : route('member.login') }}"
                       class="ml-1 flex items-center gap-1.5 text-base font-display font-semibold px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('member.*') ? 'text-mja-blue' : 'text-gray-600 hover:text-mja-blue' }}">
                        <i class="fas fa-circle-user"></i> {{ auth()->check() ? 'Mon espace' : 'Espace membre' }}
                    </a>
                    @if(auth()->user()?->canAccessBackOffice())
                    <a href="{{ route('admin.dashboard') }}"
                       class="ml-1 flex items-center gap-1.5 text-base font-display font-semibold px-3 py-2 rounded-lg text-mja-dark hover:text-mja-blue transition-colors">
                        <i class="fas fa-sliders"></i> Admin
                    </a>
                    @endif
                    <a href="{{ route('adhesion') }}"
                       class="ml-1 bg-mja-yellow hover:bg-yellow-400 text-[#14264D] font-display font-bold text-sm px-4 py-2 rounded-full transition-colors {{ request()->routeIs('adhesion') ? 'ring-2 ring-mja-yellow ring-offset-2 ring-offset-white' : '' }}">
                        Adhérer
                    </a>
                    <a href="{{ route('contact') }}"
                       class="ml-1 btn-blue font-display font-bold text-sm px-4 py-2 rounded-full transition-colors {{ request()->routeIs('contact') ? 'ring-2 ring-mja-blue ring-offset-2 ring-offset-white' : '' }}">
                        Contact
                    </a>
                </div>

                <!-- Mobile toggle -->
                <button id="menu-btn" type="button" class="nav-burger text-mja-dark hover:text-mja-blue p-2"
                        aria-label="Ouvrir le menu de navigation" aria-controls="mobile-menu" aria-expanded="false">
                    <i class="fas fa-bars text-xl" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden nav-mobile pb-4 border-t border-gray-100 mt-1 pt-3 space-y-1">
                @foreach($navLinks as [$label, $route, $pattern])
                <a href="{{ route($route) }}" class="block px-3 py-2 text-gray-700 hover:text-mja-blue text-sm font-semibold font-display transition-colors">{{ $label }}</a>
                @endforeach
                <a href="{{ route('search') }}" class="block px-3 py-2 text-gray-700 hover:text-mja-blue text-sm font-semibold font-display transition-colors"><i class="fas fa-magnifying-glass mr-1"></i> Rechercher</a>
                <a href="{{ route('contact') }}" class="block px-3 py-2 text-gray-700 hover:text-mja-blue text-sm font-semibold font-display transition-colors">Contact</a>
                <a href="{{ route('adhesion') }}" class="block px-3 py-2 text-mja-dark hover:text-mja-navy text-sm font-bold font-display transition-colors">Adhérer</a>
                <a href="{{ auth()->check() ? route('member.dashboard') : route('member.login') }}" class="block px-3 py-2 text-gray-700 hover:text-mja-blue text-sm font-semibold font-display transition-colors border-t border-gray-100 mt-1 pt-2">
                    <i class="fas fa-circle-user mr-1"></i> {{ auth()->check() ? 'Mon espace' : 'Espace membre' }}
                </a>
                @if(auth()->user()?->canAccessBackOffice())
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-mja-dark hover:text-mja-blue text-sm font-bold font-display transition-colors">
                    <i class="fas fa-sliders mr-1"></i> Administration
                </a>
                @endif
            </div>
        </div>
    </nav>@include('partials.seasonal-banner')</div>{{-- /sticky --}}

    <!-- Flash -->
    @if(is_string(session('success')))
    <div role="status" class="bg-green-50 border-l-4 border-green-500 text-green-800 px-6 py-3 text-sm flex items-center gap-2 font-display font-semibold">
        <i class="fas fa-check-circle text-green-500" aria-hidden="true"></i> {{ session('success') }}
    </div>
    @endif
    @if(is_string(session('error')))
    <div role="alert" class="bg-red-50 border-l-4 border-mja-red text-red-800 px-6 py-3 text-sm flex items-center gap-2 font-display font-semibold">
        <i class="fas fa-exclamation-circle text-mja-red" aria-hidden="true"></i> {{ session('error') }}
    </div>
    @endif

    <main id="contenu" tabindex="-1" class="outline-none">@yield('content')</main>

    <!-- Footer -->
    <footer class="bg-mja-navy text-gray-300 pt-16 pb-6 mt-20 ring-watermark">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">

                <!-- Brand -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-5">
                        <img src="{{ $logoNav ?? '/images/logomjat.png' }}" alt="MJA" class="h-14 w-auto object-contain">
                        <div>
                            <div class="font-display font-black text-2xl leading-none">
                                <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span>
                            </div>
                            <div class="text-gray-400 text-xs font-display tracking-widest uppercase mt-0.5">Madin' Jeunes Ambition</div>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-400 max-w-sm">
                        Créée en 2011, active en Martinique et au-delà. Nous rassemblons les jeunes autour d'actions éducatives, culturelles, sociales, sportives et de santé.
                    </p>
                    <div class="flex flex-wrap gap-3 mt-5">
                        <a href="https://www.facebook.com/MadinJeunesAmbition/" target="_blank" rel="noopener" title="Facebook" aria-label="MJA sur Facebook"
                           class="w-9 h-9 bg-white/5 hover:bg-[#1877F2] rounded-xl flex items-center justify-center transition-colors text-sm">
                            <i class="fab fa-facebook" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.instagram.com/madin_jeunes_ambition/" target="_blank" rel="noopener" title="Instagram" aria-label="MJA sur Instagram"
                           class="w-9 h-9 bg-white/5 hover:bg-[#E1306C] rounded-xl flex items-center justify-center transition-colors text-sm">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.tiktok.com/@fwi_ti_dej" target="_blank" rel="noopener" title="TikTok" aria-label="MJA sur TikTok"
                           class="w-9 h-9 bg-white/5 hover:bg-[#010101] rounded-xl flex items-center justify-center transition-colors text-sm">
                            <i class="fab fa-tiktok" aria-hidden="true"></i>
                        </a>
                        {{-- Snapchat masqué : le lien du compte MJA n'est pas connu,
                             et pointer vers snapchat.com n'aide personne. --}}
                        <a href="https://www.youtube.com/channel/UCX6nyVEv_QyFuLREyVvOMLw" target="_blank" rel="noopener" title="YouTube" aria-label="MJA sur YouTube"
                           class="w-9 h-9 bg-white/5 hover:bg-[#FF0000] rounded-xl flex items-center justify-center transition-colors text-sm">
                            <i class="fab fa-youtube" aria-hidden="true"></i>
                        </a>
                        <a href="mailto:{{ config('mja.contact_email') }}" title="Email" aria-label="Envoyer un email à MJA"
                           class="w-9 h-9 bg-white/5 hover:bg-mja-blue rounded-xl flex items-center justify-center transition-colors text-sm">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- Nav -->
                <div>
                    <h2 class="font-display font-bold text-white mb-4 text-sm uppercase tracking-wider">Navigation</h2>
                    <ul class="space-y-2 text-sm">
                        @php
                        $footerLinks = [['À propos','about'],['Nos projets','projects.index']];
                        if (config('mja.show_sns')) { $footerLinks[] = ['SNS','sns']; }
                        $footerLinks = array_merge($footerLinks, [['Événements','events.index'],['Actualités','articles.index'],['Ressources','resources.index']]);
                        @endphp
                        @foreach($footerLinks as [$l,$r])
                        <li><a href="{{ route($r) }}" class="hover:text-mja-yellow transition-colors">{{ $l }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h2 class="font-display font-bold text-white mb-4 text-sm uppercase tracking-wider">Contact</h2>
                    <ul class="space-y-3 text-sm">
                        <li class="flex gap-2 items-start">
                            <i class="fas fa-map-marker-alt text-mja-yellow mt-0.5 w-4 shrink-0"></i>
                            <span>Martinique et au-delà</span>
                        </li>
                        <li class="flex gap-2 items-center">
                            <i class="fas fa-envelope text-mja-yellow w-4 shrink-0"></i>
                            <a href="{{ route('contact') }}" class="hover:text-mja-yellow transition-colors">Nous contacter</a>
                        </li>
                        <li class="flex gap-2 items-center">
                            <i class="fas fa-user-plus text-mja-yellow w-4 shrink-0"></i>
                            <a href="{{ route('adhesion') }}" class="hover:text-mja-yellow transition-colors">Adhérer à MJA</a>
                        </li>
                        <li class="flex gap-2 items-center">
                            <i class="fas fa-heart text-mja-red w-4 shrink-0"></i>
                            <a href="{{ route('don') }}" class="hover:text-mja-yellow transition-colors">Faire un don</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Three-color accent bar -->
            <div class="flex h-0.5 mb-6 rounded-full overflow-hidden">
                <div class="flex-1 bg-mja-blue"></div>
                <div class="flex-1 bg-mja-yellow"></div>
                <div class="flex-1 bg-mja-red"></div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-500">
                <span>&copy; {{ date('Y') }} Madin'Jeunes Ambition — Tous droits réservés</span>
                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
                    <a href="{{ route('mentions-legales') }}" class="hover:text-mja-yellow transition-colors">Mentions légales</a>
                    <a href="{{ route('confidentialite') }}" class="hover:text-mja-yellow transition-colors">Confidentialité</a>
                    @if(auth()->user()?->canAccessBackOffice())
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-mja-yellow transition-colors font-semibold">
                        <i class="fas fa-cog mr-1" aria-hidden="true"></i>Administration
                    </a>
                    @else
                    <a href="{{ route('member.login') }}" class="hover:text-mja-yellow transition-colors">
                        <i class="fas fa-lock mr-1" aria-hidden="true"></i>Administration
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Afficher / masquer un champ mot de passe (bouton œil adjacent à l'input)
        window.mjaTogglePw = function (btn) {
            var input = btn.parentNode.querySelector('input');
            var icon = btn.querySelector('i');
            if (!input) return;
            if (input.type === 'password') { input.type = 'text'; icon && icon.classList.replace('fa-eye', 'fa-eye-slash'); }
            else { input.type = 'password'; icon && icon.classList.replace('fa-eye-slash', 'fa-eye'); }
        };

        document.getElementById('menu-btn').addEventListener('click', function () {
            const menu = document.getElementById('mobile-menu');
            const isOpen = menu.classList.toggle('hidden') === false;
            this.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            this.setAttribute('aria-label', isOpen ? 'Fermer le menu de navigation' : 'Ouvrir le menu de navigation');
        });
    </script>
    @stack('scripts')
</body>
</html>
