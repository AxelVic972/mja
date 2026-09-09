<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AdhesionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SourceTrackController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Member\AccountController as MemberAccountController;
use App\Http\Controllers\Member\AuthController as MemberAuthController;
use App\Http\Controllers\Member\PasswordResetController as MemberPasswordResetController;
use App\Http\Controllers\Member\SpaceController as MemberSpaceController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/a-propos', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'contactStore'])->name('contact.store')->middleware(['honeypot', 'throttle:contact']);

Route::get('/actualites', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/actualites/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/projets', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projets/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
Route::get('/evenements/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/evenements/{event:slug}/ics', [EventController::class, 'ics'])->name('events.ics');

Route::get('/ressources', [ResourceController::class, 'index'])->name('resources.index');
Route::get('/sante-nutrition-sport', [HomeController::class, 'sns'])->name('sns');

Route::get('/adhesion', [AdhesionController::class, 'create'])->name('adhesion');
Route::post('/adhesion', [AdhesionController::class, 'store'])->name('adhesion.store')->middleware(['honeypot', 'throttle:adhesion']);
// Renouvellement : depuis l'espace adhérent, ou via le lien magique des emails
// de relance (pour ceux qui n'ont jamais créé de compte).
Route::get('/adhesion/renouveler/{token}', [AdhesionController::class, 'renouvelerParLien'])->name('adhesion.renouveler');
Route::get('/espace/renouveler', [AdhesionController::class, 'renouvelerDepuisEspace'])
    ->name('adhesion.renouveler.espace')->middleware('auth');
// Paiement carte intégré au formulaire : création du PaymentIntent en AJAX.
Route::post('/adhesion/paiement-intent', [AdhesionController::class, 'paymentIntent'])
    ->name('adhesion.payment-intent')->middleware('throttle:10,1');
Route::get('/adhesion/paiement/succes', [AdhesionController::class, 'paiementSucces'])->name('adhesion.paiement.succes');
// Notifications Stripe (serveur à serveur). Ni session ni CSRF : l'appel ne
// vient pas d'un navigateur, il est authentifié par sa signature.
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::get('/adhesion/paiement/annule', [AdhesionController::class, 'paiementAnnule'])->name('adhesion.paiement.annule');

// Kit de communication MJ'Adhésion : générateur de visuels (posts, stories,
// affiches, flyers, bannières, vidéos motion). Page outil, non indexée.
Route::view('/kit-adhesion', 'kit-adhesion')->name('kit.adhesion');

// Plan de communication de la campagne d'adhésion : document de travail
// interne. Réservé aux comptes du back-office — il cite des personnes et
// des notes de réunion qui n'ont pas à être publiques.
Route::view('/plan-comm', 'plan-comm')->name('plan.comm')->middleware(['auth', 'content']);

// Monteur vidéo : assemblage de rushes en réel (intro, plans, outro).
// Tout le traitement a lieu dans le navigateur, aucun fichier n'est téléversé.
Route::view('/kit-video', 'kit-video')->name('kit.video');

// Cahier du projet : documentation technique et fonctionnelle du site.
Route::view('/cahier-projet', 'cahier-projet')->name('cahier.projet')->middleware(['auth', 'content']);

Route::get('/don', [DonationController::class, 'create'])->name('don');
Route::post('/don', [DonationController::class, 'store'])->name('don.store')->middleware(['honeypot', 'throttle:10,1']);
Route::get('/don/merci', [DonationController::class, 'merci'])->name('don.merci');

Route::get('/recherche', [SearchController::class, 'index'])->name('search');

Route::get('/mentions-legales', [HomeController::class, 'mentionsLegales'])->name('mentions-legales');
Route::get('/politique-de-confidentialite', [HomeController::class, 'confidentialite'])->name('confidentialite');

// ─── Espace adhérent ──────────────────────────────────────────────────────────
// Depuis la fusion des comptes, l'espace adhérent et le back-office partagent
// la même identité (guard « web ») : un email, un mot de passe.
Route::prefix('espace')->name('member.')->group(function () {
    // Création de compte via lien reçu par email
    Route::get('creer/{token}', [MemberAccountController::class, 'showCreate'])->name('account.create');
    Route::post('creer/{token}', [MemberAccountController::class, 'store'])->name('account.store');

    // Restauration d'un compte supprimé (lien reçu par email, 30 jours)
    Route::get('restaurer/{token}', [MemberAccountController::class, 'restore'])->name('account.restore');

    // Connexion
    Route::get('connexion', [MemberAuthController::class, 'showLogin'])->name('login');
    Route::post('connexion', [MemberAuthController::class, 'login'])->name('login.post')->middleware('throttle:6,1');
    Route::post('deconnexion', [MemberAuthController::class, 'logout'])->name('logout');

    // Mot de passe oublié (adhérent)
    Route::get('mot-de-passe-oublie', [MemberPasswordResetController::class, 'showLinkRequest'])->name('password.request');
    Route::post('mot-de-passe-oublie', [MemberPasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:4,1');
    Route::get('reinitialiser/{token}', [MemberPasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('reinitialiser', [MemberPasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:6,1');

    // Espace protégé — les visiteurs non connectés sont renvoyés vers la
    // connexion adhérent, pas vers celle du back-office.
    Route::middleware('auth')->group(function () {
        Route::get('/', [MemberSpaceController::class, 'dashboard'])->name('dashboard');
        Route::get('trombinoscope', [MemberSpaceController::class, 'trombinoscope'])->name('trombinoscope');
        Route::get('carte', [MemberSpaceController::class, 'card'])->name('card');
        Route::get('profil', [MemberSpaceController::class, 'editProfile'])->name('profile.edit');
        Route::put('profil', [MemberSpaceController::class, 'updateProfile'])->name('profile.update');
        Route::delete('compte', [MemberSpaceController::class, 'destroy'])->name('account.destroy');
    });
});

// ─── Auth ─────────────────────────────────────────────────────────────────────
require __DIR__.'/auth.php';

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // ── Contenu ── gestionnaire de contenu, admin, super admin
    Route::middleware('content')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('articles', Admin\ArticleController::class)->except(['show']);
        Route::resource('projects', Admin\ProjectController::class)->except(['show']);
        Route::resource('events', Admin\EventController::class)->except(['show']);
        Route::resource('resources', Admin\ResourceController::class)->except(['show']);
        Route::resource('team', Admin\TeamController::class)->except(['show']);
        Route::resource('partenaires', Admin\PartenaireController::class)->except(['show']);
    });

    // ── Gestion (adhésions, messages) ── admin et super admin
    Route::middleware('admin')->group(function () {
        Route::get('contacts', [Admin\ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}', [Admin\ContactController::class, 'show'])->name('contacts.show');
        Route::delete('contacts/{contact}', [Admin\ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('adhesions', [Admin\AdhesionController::class, 'index'])->name('adhesions.index');
        Route::get('adhesions/export', [Admin\AdhesionController::class, 'export'])->name('adhesions.export');
        Route::get('adhesions/{adhesion}', [Admin\AdhesionController::class, 'show'])->name('adhesions.show');
        Route::patch('adhesions/{adhesion}/statut', [Admin\AdhesionController::class, 'updateStatut'])->name('adhesions.statut');
        Route::get('adhesions/{adhesion}/carte', [Admin\AdhesionController::class, 'carte'])->name('adhesions.carte');
        Route::patch('adhesions/{adhesion}/periode', [Admin\AdhesionController::class, 'updatePeriode'])->name('adhesions.periode');
        Route::post('adhesions/rattacher-periode', [Admin\AdhesionController::class, 'rattacherPeriode'])->name('adhesions.rattacher-periode');
        Route::delete('adhesions/{adhesion}', [Admin\AdhesionController::class, 'destroy'])->name('adhesions.destroy');

        Route::get('codes-promo', [Admin\PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::post('codes-promo', [Admin\PromoCodeController::class, 'store'])->name('promo-codes.store');
        Route::patch('codes-promo/{promoCode}', [Admin\PromoCodeController::class, 'update'])->name('promo-codes.update');

        // Relances automatiques (paiement en attente, renouvellement de saison)
        Route::get('relances', [Admin\RelanceController::class, 'index'])->name('relances.index');
        Route::put('relances', [Admin\RelanceController::class, 'update'])->name('relances.update');
        Route::post('relances/suspendre', [Admin\RelanceController::class, 'suspendre'])->name('relances.suspendre');
        Route::post('relances/executer', [Admin\RelanceController::class, 'executer'])->name('relances.executer');
        Route::delete('relances/historique', [Admin\RelanceController::class, 'viderHistorique'])->name('relances.historique.vider');
        Route::post('relances/adhesion/{adhesion}', [Admin\RelanceController::class, 'relancerUne'])->name('relances.une');

        // Sources d'acquisition & statistiques de tracking
        Route::get('sources', [Admin\SourceController::class, 'index'])->name('sources.index');
        Route::get('sources/export', [Admin\SourceController::class, 'export'])->name('sources.export');
        Route::post('sources', [Admin\SourceController::class, 'store'])->name('sources.store');
        Route::get('sources/{source}/edit', [Admin\SourceController::class, 'edit'])->name('sources.edit');
        Route::put('sources/{source}', [Admin\SourceController::class, 'update'])->name('sources.update');
        Route::delete('sources/{source}', [Admin\SourceController::class, 'destroy'])->name('sources.destroy');

        // Dons
        Route::get('dons', [Admin\DonationController::class, 'index'])->name('donations.index');
        Route::delete('dons/{donation}', [Admin\DonationController::class, 'destroy'])->name('donations.destroy');

        // Périodes d'adhésion (saisons)
        Route::get('periodes', [Admin\PeriodController::class, 'index'])->name('periods.index');
        Route::post('periodes', [Admin\PeriodController::class, 'store'])->name('periods.store');
        Route::get('periodes/{period}/edit', [Admin\PeriodController::class, 'edit'])->name('periods.edit');
        Route::put('periodes/{period}', [Admin\PeriodController::class, 'update'])->name('periods.update');
        Route::delete('periodes/{period}', [Admin\PeriodController::class, 'destroy'])->name('periods.destroy');

        // Comptes adhérents. Un admin voit la liste et peut agir ; seul le super
        // admin voit les mots de passe en clair (contrôlé dans le contrôleur).
        Route::get('comptes-adherents', [Admin\MemberAccountController::class, 'index'])->name('members.index');
        Route::get('comptes-adherents/export', [Admin\MemberAccountController::class, 'export'])->name('members.export');
        Route::post('comptes-adherents', [Admin\MemberAccountController::class, 'store'])->name('members.store');
        Route::patch('comptes-adherents/{user}/mot-de-passe', [Admin\MemberAccountController::class, 'resetPassword'])->name('members.reset-password');
        Route::patch('comptes-adherents/{user}/trombinoscope', [Admin\MemberAccountController::class, 'toggleDirectory'])->name('members.toggle-directory');
        Route::patch('comptes-adherents/{user}/role', [Admin\MemberAccountController::class, 'updateRole'])->name('members.role');
    });

    // ── Comptes ── admin (gestionnaires seulement) et super admin (tous).
    // La hiérarchie fine est vérifiée dans le contrôleur.
    Route::middleware('admin')->group(function () {
        Route::resource('users', Admin\UserController::class)->except(['show']);
        Route::patch('users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('users/{user}/toggle-active', [Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    // ── Paramètres ── admin et super admin.
    // Les secrets Stripe (clé secrète, webhook) restent réservés au super
    // admin : la restriction est appliquée dans le contrôleur, pas ici.
    Route::middleware('admin')->group(function () {
        Route::get('parametres', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('parametres', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});

// ─── Tracking des sources d'acquisition ─────────────────────────────────────────
// DOIT rester en dernier : n'intercepte qu'un segment unique non déjà routé,
// et 404 si le slug n'est pas une source enregistrée.
Route::get('/{source}', [SourceTrackController::class, 'handle'])
    ->where('source', '[A-Za-z0-9._-]+')
    ->name('source.track');
