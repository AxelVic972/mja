<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Administration') — MJA</title>

    <link rel="icon" type="image/jpeg" sizes="any" href="{{ asset('images/logo.jpg') }}">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gill-sans.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}"
          onerror="this.onerror=null;var l=document.createElement('link');l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;500;600&display=swap';document.head.appendChild(l)">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}"
          onerror="this.onerror=null;var l=document.createElement('link');l.rel='stylesheet';l.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';document.head.appendChild(l)">
    <style>
        body { font-family: 'Gill Sans', 'Open Sans', sans-serif; }
        h1,h2,h3,h4,.font-display { font-family: 'Gill Sans', 'Montserrat', sans-serif; }
        .font-display.font-black { font-family: 'AllRound Gothic', 'Gill Sans', sans-serif; }
        a:focus-visible, button:focus-visible, input:focus-visible,
        select:focus-visible, textarea:focus-visible { outline: 3px solid #1E93D6; outline-offset: 2px; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 sm:w-64 bg-mja-navy h-screen flex flex-col fixed left-0 top-0 z-40
        -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl">
        <!-- Logo -->
        <div class="p-5 border-b border-white/10">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <img src="/images/logo.jpg" alt="MJA" class="h-10 w-10 object-contain bg-white rounded-lg p-0.5 shadow">
                <div>
                    <div class="font-display font-black text-lg leading-none">
                        <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span>
                    </div>
                    <div class="text-gray-400 text-xs font-display tracking-wider mt-0.5" style="font-size:9px;">ADMINISTRATION</div>
                </div>
            </a>
        </div>

        <!-- Three-color bar -->
        <div class="flex h-0.5">
            <div class="flex-1 bg-mja-blue"></div>
            <div class="flex-1 bg-mja-yellow"></div>
            <div class="flex-1 bg-mja-red"></div>
        </div>

        <nav class="flex-1 p-4 space-y-0.5 overflow-y-auto">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.dashboard') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-tachometer-alt w-4 text-center"></i> Tableau de bord
            </a>

            <div class="pt-4 pb-1 px-3 text-xs text-gray-500 uppercase tracking-wider font-display font-bold">Contenu</div>

            @php
            $navItems = [
                ['label'=>'Actualités', 'icon'=>'fa-newspaper', 'route'=>'admin.articles', 'count'=>\App\Models\Article::count(), 'color'=>'text-mja-blue'],
                ['label'=>'Projets',    'icon'=>'fa-project-diagram','route'=>'admin.projects','count'=>\App\Models\Project::count(), 'color'=>'text-mja-yellow'],
                ['label'=>'Événements','icon'=>'fa-calendar-alt','route'=>'admin.events',  'count'=>\App\Models\Event::count(),   'color'=>'text-mja-red'],
                ['label'=>'Ressources','icon'=>'fa-folder-open','route'=>'admin.resources','count'=>\App\Models\Resource::count(),'color'=>'text-mja-blue'],
            ];
            @endphp
            @foreach($navItems as $item)
            <a href="{{ route($item['route'].'.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs($item['route'].'.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas {{ $item['icon'] }} w-4 text-center {{ request()->routeIs($item['route'].'.*') ? '' : $item['color'] }}"></i>
                {{ $item['label'] }}
                <span class="ml-auto bg-white/10 text-xs px-2 py-0.5 rounded-full">{{ $item['count'] }}</span>
            </a>
            @endforeach

            <div class="pt-4 pb-1 px-3 text-xs text-gray-500 uppercase tracking-wider font-display font-bold">Association</div>

            <a href="{{ route('admin.team.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.team.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-users w-4 text-center text-mja-yellow"></i> Équipe
            </a>
            <a href="{{ route('admin.partenaires.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.partenaires.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-handshake w-4 text-center text-mja-yellow"></i> Partenaires
            </a>

            @if(auth()->user()->canAccessManagement())
            @php
                $nonLus = \App\Models\Contact::where('lu', false)->count();
                $nouvellesAdhesions = \App\Models\Adhesion::where('lu', false)->count();
            @endphp
            <div class="pt-4 pb-1 px-3 text-xs text-gray-500 uppercase tracking-wider font-display font-bold">Gestion</div>
            <a href="{{ route('admin.adhesions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.adhesions.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-user-plus w-4 text-center text-mja-yellow"></i> Adhésions
                @if($nouvellesAdhesions > 0)
                <span class="ml-auto bg-mja-yellow text-mja-dark text-xs font-black px-2 py-0.5 rounded-full">{{ $nouvellesAdhesions }}</span>
                @endif
            </a>
            <a href="{{ route('admin.relances.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.relances.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-paper-plane w-4 text-center text-mja-yellow"></i> Relances
            </a>
            <a href="{{ route('admin.contacts.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.contacts.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-envelope w-4 text-center text-mja-red"></i> Messages
                @if($nonLus > 0)
                <span class="ml-auto bg-mja-red text-white text-xs px-2 py-0.5 rounded-full">{{ $nonLus }}</span>
                @endif
            </a>
            <a href="{{ route('admin.donations.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.donations.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-heart w-4 text-center text-mja-red"></i> Dons
            </a>
            <a href="{{ route('admin.sources.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.sources.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-bullseye w-4 text-center text-mja-blue"></i> Sources
            </a>
            <a href="{{ route('admin.periods.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.periods.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-calendar-week w-4 text-center text-mja-yellow"></i> Périodes
            </a>
            <a href="{{ route('admin.promo-codes.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.promo-codes.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-ticket w-4 text-center text-mja-blue"></i> Codes promo
            </a>
            @endif

            @if(auth()->user()->canManageUsers())
            <div class="pt-4 pb-1 px-3 text-xs text-gray-500 uppercase tracking-wider font-display font-bold">Comptes</div>
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.users.*') ? 'bg-mja-yellow text-mja-dark' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-crown w-4 text-center text-mja-yellow"></i>
                {{ auth()->user()->isSuperAdmin() ? 'Comptes admin' : 'Gestionnaires' }}
            </a>
            <a href="{{ route('admin.members.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.members.*') ? 'bg-mja-yellow text-mja-dark' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-id-badge w-4 text-center text-mja-red"></i> Comptes adhérents
            </a>
            @endif

            @if(auth()->user()->canAccessManagement())
            <a href="{{ route('admin.settings.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-display font-semibold transition-colors
                      {{ request()->routeIs('admin.settings.*') ? 'bg-mja-blue text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fas fa-sliders-h w-4 text-center text-mja-blue"></i> Paramètres
            </a>
            @endif
        </nav>

        <!-- User / logout -->
        <div class="p-4 border-t border-white/10">
            <div class="text-xs text-gray-400 font-display font-semibold mb-2 px-1">{{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-display font-semibold text-red-400 hover:bg-red-900/20 hover:text-red-300 transition-colors">
                    <i class="fas fa-sign-out-alt w-4 text-center"></i> Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="lg:ml-64 flex-1 flex flex-col min-h-screen pb-16 lg:pb-0">

        <!-- Topbar -->
        <header class="bg-white border-b border-gray-100 px-4 sm:px-6 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center gap-3">
                @php $totalNotifs = ($nonLus ?? 0) + ($nouvellesAdhesions ?? 0); @endphp
                <button id="sidebar-toggle" class="lg:hidden relative w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-500 hover:text-mja-blue transition-colors">
                    <i class="fas fa-bars text-lg"></i>
                    @if($totalNotifs > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-mja-red text-white text-[10px] font-black rounded-full flex items-center justify-center">{{ $totalNotifs > 9 ? '9+' : $totalNotifs }}</span>
                    @endif
                </button>
                <!-- Three-dot accent (desktop) -->
                <div class="hidden sm:flex gap-1 items-center">
                    <span class="w-2 h-2 rounded-full bg-mja-blue"></span>
                    <span class="w-2 h-2 rounded-full bg-mja-yellow"></span>
                    <span class="w-2 h-2 rounded-full bg-mja-red"></span>
                </div>
                <h1 class="font-display font-bold text-mja-gray text-base sm:text-lg truncate max-w-[180px] sm:max-w-none">@yield('page-title', 'Administration')</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-4">
                @if($totalNotifs > 0)
                <span class="lg:hidden bg-red-50 text-mja-red text-xs font-bold px-2 py-1 rounded-lg">{{ $totalNotifs }} notif.</span>
                @endif
                <a href="{{ route('home') }}" target="_blank"
                   class="hidden sm:flex text-sm text-gray-400 hover:text-mja-blue items-center gap-1.5 font-display font-semibold transition-colors">
                    <i class="fas fa-external-link-alt text-xs"></i> Voir le site
                </a>
                <span class="hidden sm:block text-xs text-gray-400 font-display font-semibold truncate max-w-[100px]">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <!-- Validation errors (inline, contextuel) -->
        @if($errors->any())
        <div class="px-6 pt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm mb-4">
                <div class="flex items-center gap-2 mb-1 font-display font-bold">
                    <i class="fas fa-exclamation-triangle text-mja-red"></i> Erreurs de validation :
                </div>
                <ul class="list-disc list-inside space-y-1 ml-4">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
        @endif

        <main class="flex-1 px-6 pb-10">
            @yield('content')
        </main>
    </div>

    <!-- Mobile sidebar overlay -->
    <div id="sidebar-overlay"
         class="fixed inset-0 bg-black/60 z-30 lg:hidden opacity-0 invisible transition-all duration-300"
         onclick="closeSidebar()"></div>

    <!-- Mobile bottom navigation bar -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-100 shadow-lg">
        <div class="grid grid-cols-5 h-16">
            @php
                if (auth()->user()->canAccessManagement()) {
                    $mobileNav = [
                        ['icon' => 'fa-tachometer-alt', 'label' => 'Accueil', 'route' => 'admin.dashboard',   'pattern' => 'admin.dashboard',   'badge' => 0,                       'color' => 'text-mja-blue'],
                        ['icon' => 'fa-newspaper',       'label' => 'Contenu',  'route' => 'admin.articles.index', 'pattern' => 'admin.articles.*', 'badge' => 0,                       'color' => 'text-mja-blue'],
                        ['icon' => 'fa-user-plus',       'label' => 'Adhésions','route' => 'admin.adhesions.index','pattern' => 'admin.adhesions.*','badge' => $nouvellesAdhesions ?? 0, 'color' => 'text-mja-yellow'],
                        ['icon' => 'fa-envelope',        'label' => 'Messages', 'route' => 'admin.contacts.index', 'pattern' => 'admin.contacts.*', 'badge' => $nonLus ?? 0,             'color' => 'text-mja-red'],
                        ['icon' => 'fa-ellipsis-h',      'label' => 'Plus',     'route' => null,                   'pattern' => null,               'badge' => 0,                       'color' => 'text-gray-500'],
                    ];
                } else {
                    $mobileNav = [
                        ['icon' => 'fa-tachometer-alt', 'label' => 'Accueil',    'route' => 'admin.dashboard',      'pattern' => 'admin.dashboard',   'badge' => 0, 'color' => 'text-mja-blue'],
                        ['icon' => 'fa-newspaper',       'label' => 'Actualités', 'route' => 'admin.articles.index', 'pattern' => 'admin.articles.*', 'badge' => 0, 'color' => 'text-mja-blue'],
                        ['icon' => 'fa-project-diagram', 'label' => 'Projets',    'route' => 'admin.projects.index', 'pattern' => 'admin.projects.*', 'badge' => 0, 'color' => 'text-mja-yellow'],
                        ['icon' => 'fa-calendar-alt',    'label' => 'Événements', 'route' => 'admin.events.index',    'pattern' => 'admin.events.*',   'badge' => 0, 'color' => 'text-mja-red'],
                        ['icon' => 'fa-ellipsis-h',      'label' => 'Plus',       'route' => null,                   'pattern' => null,               'badge' => 0, 'color' => 'text-gray-500'],
                    ];
                }
            @endphp
            @foreach($mobileNav as $item)
                @if($item['route'])
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center justify-center gap-0.5 relative transition-colors
                          {{ request()->routeIs($item['pattern']) ? 'text-mja-blue' : 'text-gray-400' }}">
                    <i class="fas {{ $item['icon'] }} text-lg"></i>
                    <span class="text-[9px] font-display font-bold leading-none">{{ $item['label'] }}</span>
                    @if($item['badge'] > 0)
                    <span class="absolute top-2 right-1/2 translate-x-3 w-4 h-4 bg-mja-red text-white text-[9px] font-black rounded-full flex items-center justify-center">{{ $item['badge'] > 9 ? '9+' : $item['badge'] }}</span>
                    @endif
                    @if(request()->routeIs($item['pattern']))
                    <span class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-mja-blue rounded-b-full"></span>
                    @endif
                </a>
                @else
                <button onclick="toggleSidebar()"
                        class="flex flex-col items-center justify-center gap-0.5 relative text-gray-400 transition-colors">
                    <i class="fas {{ $item['icon'] }} text-lg"></i>
                    <span class="text-[9px] font-display font-bold leading-none">{{ $item['label'] }}</span>
                </button>
                @endif
            @endforeach
        </div>
    </nav>

    <script>
        function openSidebar() {
            const aside   = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            aside.classList.remove('-translate-x-full');
            overlay.classList.remove('opacity-0', 'invisible');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            const aside   = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            aside.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0', 'invisible');
            document.body.style.overflow = '';
        }
        function toggleSidebar() {
            const aside = document.getElementById('sidebar');
            if (aside.classList.contains('-translate-x-full')) openSidebar();
            else closeSidebar();
        }
        document.getElementById('sidebar-toggle')?.addEventListener('click', toggleSidebar);

        // Fermer la sidebar si on passe en desktop (resize)
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.body.style.overflow = '';
            }
        });

        // Swipe gauche pour fermer
        let touchStartX = 0;
        document.getElementById('sidebar')?.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        document.getElementById('sidebar')?.addEventListener('touchend', e => {
            if (touchStartX - e.changedTouches[0].clientX > 60) closeSidebar();
        }, { passive: true });
    </script>
    <!-- ═══ TOASTS SESSION ═══ -->
    @if(session('success') || session('error'))
    <div id="toast-container" class="fixed top-5 right-5 z-[60] flex flex-col gap-3 w-80 pointer-events-none">
        @if(session('success'))
        <div class="toast-item pointer-events-auto bg-white border border-green-200 shadow-xl rounded-2xl px-4 py-3.5 flex items-start gap-3 translate-x-0 opacity-100 transition-all duration-400">
            <span class="w-8 h-8 bg-green-100 rounded-xl flex items-center justify-center shrink-0">
                <i class="fas fa-check text-green-600 text-sm"></i>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-display font-semibold text-sm text-gray-800 leading-snug">{{ session('success') }}</p>
            </div>
            <button onclick="this.closest('.toast-item').remove()" class="text-gray-300 hover:text-gray-500 transition-colors mt-0.5 shrink-0">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        @endif
        @if(session('error'))
        <div class="toast-item pointer-events-auto bg-white border border-red-200 shadow-xl rounded-2xl px-4 py-3.5 flex items-start gap-3">
            <span class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                <i class="fas fa-exclamation text-mja-red text-sm"></i>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-display font-semibold text-sm text-gray-800 leading-snug">{{ session('error') }}</p>
            </div>
            <button onclick="this.closest('.toast-item').remove()" class="text-gray-300 hover:text-gray-500 transition-colors mt-0.5 shrink-0">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        @endif
    </div>
    <script>
    setTimeout(function() {
        document.querySelectorAll('.toast-item').forEach(function(el) {
            el.style.transition = 'opacity 0.5s, transform 0.5s';
            el.style.opacity = '0';
            el.style.transform = 'translateX(24px)';
            setTimeout(function() { if (el.parentNode) el.remove(); }, 500);
        });
    }, 5000);
    </script>
    @endif

    <!-- ═══ MODAL CONFIRMATION ═══ -->
    <div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(10,20,40,.65)">
        <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 shadow-2xl">
            <div class="w-14 h-14 bg-red-50 border border-red-100 rounded-2xl flex items-center justify-center mb-5 mx-auto">
                <i class="fas fa-trash-alt text-mja-red text-xl"></i>
            </div>
            <h3 class="font-display font-black text-xl text-center text-mja-gray mb-2">Confirmer la suppression</h3>
            <p id="confirm-msg" class="text-gray-400 text-sm text-center mb-7 leading-relaxed"></p>
            <div class="flex gap-3">
                <button id="confirm-cancel-btn"
                    class="flex-1 py-3 border-2 border-gray-200 hover:border-gray-300 rounded-xl font-display font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times mr-1 text-xs"></i> Annuler
                </button>
                <button id="confirm-ok-btn"
                    class="flex-1 py-3 bg-mja-red hover:bg-red-700 text-white rounded-xl font-display font-bold transition-colors shadow-sm">
                    <i class="fas fa-trash-alt mr-1 text-xs"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var modal  = document.getElementById('confirm-modal');
        var msgEl  = document.getElementById('confirm-msg');
        var _form  = null;

        function showModal(form) {
            _form = form;
            msgEl.textContent = form.dataset.confirm || 'Cette action est irréversible. Confirmer ?';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function hideModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            _form = null;
        }

        document.addEventListener('submit', function(e) {
            var f = e.target;
            if (f.hasAttribute('data-confirm')) {
                e.preventDefault();
                showModal(f);
            }
        });

        document.getElementById('confirm-ok-btn').addEventListener('click', function() {
            if (!_form) return;
            var f = _form;
            f.removeAttribute('data-confirm');
            hideModal();
            f.submit();
        });
        document.getElementById('confirm-cancel-btn').addEventListener('click', hideModal);
        modal.addEventListener('click', function(e) { if (e.target === modal) hideModal(); });
    })();
    </script>

    @stack('scripts')
</body>
</html>
