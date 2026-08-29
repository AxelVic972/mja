@extends('layouts.app')
@section('title', "Rejoindre MJA — Adhésion Madin'Jeunes Ambition")
@section('og_image', asset('images/partage/adhesion.jpg'))
@section('meta_description', "Rejoins Madin'Jeunes Ambition ! Adhère en ligne pour intégrer notre équipe de jeunes bénévoles engagés en Martinique. Gratuit et ouvert à tous.")

@if(!empty($stripeEnabled))
{{-- Chargé dans le <head> : Stripe.js doit être prêt avant le script du formulaire. --}}
@push('head')
<script src="https://js.stripe.com/v3/"></script>
@endpush
@endif

@section('content')

@php
    /**
     * Valeur affichée dans un champ : ce que le visiteur vient de saisir
     * (old) l'emporte, sinon la reprise de l'adhésion précédente, sinon vide.
     */
    $prefill = $prefill ?? [];
    $pre = fn ($cle, $defaut = null) => old($cle, data_get($prefill, $cle, $defaut));
@endphp

<section class="hero-gradient text-white py-16 relative overflow-hidden">
    <div class="absolute -right-16 -top-16 w-64 h-64 opacity-10">
        <svg viewBox="0 0 200 200" fill="none"><circle cx="100" cy="100" r="95" stroke="#3DAEF5" stroke-width="2"/><circle cx="100" cy="100" r="65" stroke="#F5A623" stroke-width="2"/></svg>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-sm text-gray-400 mb-3 font-display font-semibold">
            <a href="{{ route('home') }}" class="hover:text-mja-yellow transition-colors">Accueil</a>
            <span class="mx-2 text-gray-600">/</span> Adhésion
        </div>
        <h1 class="font-display font-black text-4xl sm:text-5xl mb-4">Rejoindre <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span></h1>
        <p class="text-gray-300 text-lg max-w-2xl">Tu as entre 16 et 35 ans et tu veux t'engager ? Remplis ce formulaire pour rejoindre <strong class="text-white">Madin' Jeunes Ambition</strong>.</p>
    </div>
</section>

<div class="flex h-1"><div class="flex-1 bg-mja-blue"></div><div class="flex-1 bg-mja-yellow"></div><div class="flex-1 bg-mja-red"></div></div>

<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <!-- Sidebar info -->
            <div class="space-y-6">
                <div class="bg-mja-dark rounded-2xl p-6 text-center ring-watermark">
                    <img src="/images/logo.jpg" alt="MJA" class="h-20 w-20 mx-auto mb-4 object-contain bg-white rounded-xl p-1">
                    <div class="font-display font-black text-2xl mb-1">
                        <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span>
                    </div>
                    <div class="text-gray-400 text-xs font-display font-semibold uppercase tracking-wider">Madin' Jeunes Ambition</div>
                </div>

                <div class="bg-mja-light rounded-2xl p-5 border-l-4 border-mja-yellow">
                    <h3 class="font-display font-bold text-mja-gray mb-3 flex items-center gap-2">
                        <i class="fas fa-info-circle text-mja-yellow"></i> Modalités d'inscription
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        {{-- Une directive Blade collée au mot précédent n'est pas
                             reconnue : garder l'espace avant @if. --}}
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-mja-blue mt-0.5 shrink-0"></i>
                            <span>
                                Cotisation de <strong>{{ \App\Support\Cotisation::formatee() }}</strong> pour finaliser l'inscription
                                @if(!empty($stripeEnabled) && \App\Support\Cotisation::fraisCarte() > 0)
                                <span class="text-gray-500">({{ \App\Support\Cotisation::carteFormatee() }} par carte, frais bancaires inclus)</span>
                                @endif
                            </span>
                        </li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-mja-blue mt-0.5 shrink-0"></i> Photo facultative — tu peux la déposer maintenant ou plus tard</li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-mja-blue mt-0.5 shrink-0"></i> Tu seras présenté(e) aux autres membres</li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <h3 class="font-display font-bold text-mja-gray mb-3 flex items-center gap-2">
                        <i class="fas fa-phone text-mja-blue"></i> Contacts
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs font-display font-bold text-gray-400 uppercase tracking-wider mb-0.5">Secrétaire</div>
                            <a href="tel:+596696438821" class="font-display font-bold text-mja-dark hover:text-mja-blue transition-colors text-sm flex items-center gap-2">
                                <i class="fas fa-mobile-alt text-mja-blue text-xs"></i> +596 696 43 88 21
                            </a>
                        </div>
                        <div class="border-t border-gray-50 pt-3">
                            <div class="text-xs font-display font-bold text-gray-400 uppercase tracking-wider mb-0.5">Secrétaire adjointe</div>
                            <a href="tel:+596696438838" class="font-display font-bold text-mja-dark hover:text-mja-blue transition-colors text-sm flex items-center gap-2">
                                <i class="fas fa-mobile-alt text-mja-blue text-xs"></i> +596 696 43 88 38
                            </a>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <h3 class="font-display font-bold text-mja-gray mb-3">Une question ?</h3>
                    <p class="text-gray-500 text-sm mb-3">Tu préfères nous écrire avant de t'engager ?</p>
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-mja-blue font-display font-bold text-sm hover:underline">
                        <i class="fas fa-envelope"></i> Nous contacter
                    </a>
                </div>
            </div>

            <!-- Formulaire -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex h-1.5">
                    <div class="flex-1 bg-mja-blue"></div>
                    <div class="flex-1 bg-mja-yellow"></div>
                    <div class="flex-1 bg-mja-red"></div>
                </div>
                <div class="p-8">
                    <h2 id="titre-formulaire" class="font-display font-bold text-xl text-mja-gray mb-6">
                        @if(!empty($dejaAJour) && !session('success'))
                            Ton adhésion est à jour
                        @elseif(!empty($precedente))
                            Renouveler mon adhésion
                        @else
                            Formulaire d'adhésion
                        @endif
                    </h2>

                    @if(!empty($precedente) && empty($dejaAJour) && !session('success'))
                    <div class="bg-mja-blue/5 border border-mja-blue/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
                        <i class="fas fa-rotate-right text-mja-blue mt-0.5 shrink-0"></i>
                        <div class="text-sm text-gray-600 leading-relaxed">
                            <strong class="text-mja-gray font-display">Bon retour, {{ $precedente->prenom }} !</strong>
                            Tes informations de l'an dernier sont déjà là — vérifie-les, corrige ce qui a changé,
                            puis règle ta cotisation{{ !empty($periode) ? ' pour la saison ' . $periode->label : '' }}.
                            @if($precedente->photo)
                            <span class="block mt-1 text-gray-500">Ta photo est conservée : ne dépose un nouveau fichier que si tu veux la remplacer.</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-2xl p-6 mb-6 text-center">
                        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-green-500 text-2xl"></i>
                        </div>
                        @if(session('paye'))
                        <h3 class="font-display font-bold text-lg mb-2">Paiement confirmé — bienvenue ! 🎉</h3>
                        <p class="text-sm leading-relaxed">Ta cotisation a bien été reçue : tu es désormais officiellement <strong>adhérent(e)</strong> de MJA. Un email de bienvenue vient de t'être envoyé.</p>
                        @else
                        <h3 class="font-display font-bold text-lg mb-2">{{ session('renouvellement') ? 'Renouvellement enregistré !' : 'Merci pour ta demande !' }}</h3>
                        <p class="text-sm leading-relaxed">Nous avons bien reçu ton formulaire. Les instructions pour régler ta cotisation t'ont été envoyées par email — dès réception du paiement, tu {{ session('renouvellement') ? 'seras à jour pour la nouvelle saison' : 'deviendras officiellement adhérent(e)' }}.</p>
                        @endif
                    </div>
                    @else

                    @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6 text-sm font-display font-semibold flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 shrink-0"></i>
                        <div>Certains champs nécessitent votre attention. Veuillez vérifier le formulaire ci-dessous.</div>
                    </div>
                    @endif

                    @if(!empty($dejaAJour) && !session('success'))
                    {{-- Adhésion déjà enregistrée pour la saison : on remercie,
                         et on ne propose surtout pas un second formulaire. --}}
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                            <i class="fas fa-heart text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="font-display font-black text-2xl text-mja-gray mb-3">
                            Tu es des nôtres{{ !empty($periode) ? ' pour la ' . $periode->label : '' }} !
                        </h3>
                        <p class="text-gray-600 leading-relaxed max-w-lg mx-auto">
                            {{ $dejaAJour->prenom }}, ton adhésion est déjà enregistrée
                            @if($dejaAJour->isAdherent())
                                et ta cotisation est réglée.
                            @else
                                — il ne reste que le règlement de la cotisation, l'équipe revient vers toi.
                            @endif
                            Merci de continuer l'aventure avec nous : c'est grâce à toi que les actions existent.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                            @auth
                            <a href="{{ route('member.dashboard') }}"
                               class="inline-flex items-center gap-2 bg-mja-blue hover:bg-mja-bluedark text-white font-display font-bold px-6 py-3 rounded-xl transition-colors">
                                <i class="fas fa-user"></i> Mon espace adhérent
                            </a>
                            @if($dejaAJour->isAdherent())
                            <a href="{{ route('member.card') }}"
                               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-display font-bold px-6 py-3 rounded-xl transition-colors">
                                <i class="fas fa-id-card"></i> Ma carte de membre
                            </a>
                            @endif
                            @else
                            <a href="{{ route('member.login') }}"
                               class="inline-flex items-center gap-2 bg-mja-blue hover:bg-mja-bluedark text-white font-display font-bold px-6 py-3 rounded-xl transition-colors">
                                <i class="fas fa-right-to-bracket"></i> Accéder à mon espace
                            </a>
                            @endauth
                            <a href="{{ route('events.index') }}"
                               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-display font-bold px-6 py-3 rounded-xl transition-colors">
                                <i class="fas fa-calendar-days"></i> Les prochains rendez-vous
                            </a>
                        </div>

                        <p class="text-xs text-gray-400 mt-6">
                            Une information à corriger ?
                            @auth
                                Modifie-la depuis <a href="{{ route('member.profile.edit') }}" class="text-mja-blue font-semibold hover:underline">ton profil</a>.
                            @else
                                <a href="{{ route('contact') }}" class="text-mja-blue font-semibold hover:underline">Écris-nous</a>, on s'en occupe.
                            @endauth
                        </p>
                    </div>
                    @else

                    <form method="POST" action="{{ route('adhesion.store') }}" class="space-y-8" enctype="multipart/form-data">
                        @csrf
                        <div aria-hidden="true" style="position:absolute;left:-9999px;height:0;overflow:hidden" tabindex="-1">
                            <label>Ne pas remplir<input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
                        </div>
                        @if(!empty($precedente?->renouvellement_token))
                        <input type="hidden" name="renouvellement_token" value="{{ $precedente->renouvellement_token }}">
                        @endif

                        {{-- Bloc 1 : Type d'adhésion --}}
                        <div>
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">1</span>
                                Type de démarche
                            </h3>
                            <label class="block text-sm font-display font-bold text-mja-gray mb-2">Avant tout, est-ce ta première adhésion à MJA ? <span class="text-mja-red">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach(['premiere' => 'Première adhésion', 'readhesion' => 'Réadhésion', 'information' => 'Prise d\'informations'] as $val => $label)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="premiere_adhesion" value="{{ $val }}"
                                        {{ $pre('premiere_adhesion') === $val ? 'checked' : '' }}
                                        class="peer sr-only">
                                    <div class="border-2 border-gray-100 peer-checked:border-mja-blue peer-checked:bg-mja-blue/5 rounded-xl p-3 text-center text-sm font-display font-bold text-gray-500 peer-checked:text-mja-blue transition-all hover:border-gray-200">
                                        {{ $label }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('premiere_adhesion')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror

                            @if(empty($precedente))
                            {{-- Réadhésion : inutile de tout retaper si le compte
                                 existe déjà. Le lien mène à l'écran pré-rempli
                                 (connexion demandée en chemin si nécessaire). --}}
                            <div id="bloc-readhesion" class="hidden mt-4 bg-mja-blue/5 border border-mja-blue/20 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                                <i class="fas fa-rotate-right text-mja-blue shrink-0"></i>
                                <p class="flex-1 text-sm text-gray-600 leading-relaxed">
                                    Tu as déjà un espace adhérent ? Renouvelle depuis ton compte :
                                    ton formulaire s'ouvre <strong>pré-rempli</strong>, tu n'as plus qu'à vérifier et payer.
                                </p>
                                <a href="{{ route('adhesion.renouveler.espace') }}"
                                   class="shrink-0 btn-blue font-display font-bold text-sm px-4 py-2 rounded-xl transition-colors text-center">
                                    Renouveler depuis mon espace
                                </a>
                            </div>
                            @endif
                        </div>

                        {{-- Bloc 2 : Identité --}}
                        <div>
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">2</span>
                                Identité
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-display font-bold text-mja-gray mb-2">Civilité <span class="text-mja-red">*</span></label>
                                    <div class="flex gap-3">
                                        @foreach(['Madame', 'Monsieur'] as $civ)
                                        <label class="relative cursor-pointer flex-1">
                                            <input type="radio" name="civilite" value="{{ $civ }}"
                                                {{ $pre('civilite') === $civ ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="border-2 border-gray-100 peer-checked:border-mja-blue peer-checked:bg-mja-blue/5 rounded-xl p-3 text-center text-sm font-display font-bold text-gray-500 peer-checked:text-mja-blue transition-all hover:border-gray-200">
                                                {{ $civ }}
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                    @error('civilite')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="a-nom" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Nom <span class="text-mja-red" aria-hidden="true">*</span></label>
                                        <input type="text" id="a-nom" name="nom" value="{{ $pre('nom') }}" required autocomplete="family-name"
                                            class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('nom') border-mja-red @enderror"
                                            placeholder="DUPONT">
                                        @error('nom')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="a-prenom" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Prénom <span class="text-mja-red" aria-hidden="true">*</span></label>
                                        <input type="text" id="a-prenom" name="prenom" value="{{ $pre('prenom') }}" required autocomplete="given-name"
                                            class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('prenom') border-mja-red @enderror"
                                            placeholder="Jean">
                                        @error('prenom')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-mode="adhesion">
                                    {{-- <div>
                                        <label for="a-naissance" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Date de naissance <span class="text-mja-red" aria-hidden="true">*</span></label>
                                        <input type="text" id="a-naissance" name="date_naissance" value="{{ $pre('date_naissance') }}" required
                                            class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('date_naissance') border-mja-red @enderror"
                                            placeholder="JJ/MM/AAAA" maxlength="10">
                                        @error('date_naissance')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div> --}}

                <div>
                    <label for="a-naissance"
                        class="block text-sm font-display font-bold text-mja-gray mb-1.5">
                        Date de naissance
                        <span class="text-mja-red" aria-hidden="true">*</span>
                    </label>

                    <input
                        type="text"
                        id="a-naissance"
                        name="date_naissance"
                        value="{{ $pre('date_naissance') }}"
                        required
                        inputmode="numeric"
                        autocomplete="bday"
                        placeholder="JJ/MM/AAAA"
                        maxlength="10"
                        class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('date_naissance') border-mja-red @enderror"
                    >

                    @error('date_naissance')
                        <p class="text-mja-red text-xs mt-1 font-display font-semibold">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const input = document.getElementById('a-naissance');

                    if (!input) return;

                    input.addEventListener('input', function (e) {
                        // On récupère uniquement les chiffres
                        let value = e.target.value.replace(/\D/g, '');

                        // Maximum : 8 chiffres (JJMMAAAA)
                        value = value.substring(0, 8);

                        // Formatage automatique
                        if (value.length > 4) {
                            value = value.substring(0, 2) + '/' +
                                    value.substring(2, 4) + '/' +
                                    value.substring(4);
                        } else if (value.length > 2) {
                            value = value.substring(0, 2) + '/' +
                                    value.substring(2);
                        }

                        e.target.value = value;
                    });

                    // Empêche certains caractères non numériques
                    input.addEventListener('keydown', function (e) {
                        const allowedKeys = [
                            'Backspace',
                            'Delete',
                            'Tab',
                            'ArrowLeft',
                            'ArrowRight',
                            'Home',
                            'End'
                        ];

                        if (
                            allowedKeys.includes(e.key) ||
                            e.ctrlKey ||
                            e.metaKey
                        ) {
                            return;
                        }

                        if (!/[0-9]/.test(e.key)) {
                            e.preventDefault();
                        }
                    });

                    // Nettoyage final avant envoi
                    input.form?.addEventListener('submit', function () {
                        input.value = input.value.replace(/\D/g, '').replace(
                            /^(\d{2})(\d{2})(\d{4})$/,
                            '$1/$2/$3'
                        );
                    });
                });
                </script>

                                    <div>
                                        <label for="a-profession" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Profession / Secteur d'activité <span class="text-mja-red" aria-hidden="true">*</span></label>
                                        <input type="text" id="a-profession" name="profession" value="{{ $pre('profession') }}" required
                                            class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('profession') border-mja-red @enderror"
                                            placeholder="Étudiant, Commerce...">
                                        @error('profession')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bloc 3 : Coordonnées --}}
                        <div>
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">3</span>
                                Coordonnées
                            </h3>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-display font-bold text-mja-gray mb-1.5">Numéro de téléphone <span class="text-mja-red">*</span></label>
                                        <x-phone-field :value="$pre('telephone')" :indicatif="$pre('indicatif', '+596')" :required="true" />
                                        @error('telephone')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                        @error('indicatif')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="a-email" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Adresse mail <span class="text-mja-red" aria-hidden="true">*</span></label>
                                        <input type="email" id="a-email" name="email" value="{{ $pre('email') }}" required autocomplete="email"
                                            class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('email') border-mja-red @enderror"
                                            placeholder="jean@exemple.com">
                                        @error('email')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                {{-- Adresse postale : demandée pour une adhésion, pas pour
                                     une simple prise d'informations. --}}
                                <div data-mode="adhesion">
                                    <label for="a-adresse" class="block text-sm font-display font-bold text-mja-gray mb-1.5">
                                        Adresse postale <span class="text-mja-red" aria-hidden="true">*</span>
                                    </label>
                                    <textarea id="a-adresse" name="adresse_postale" rows="2" required
                                        class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors resize-none @error('adresse_postale') border-mja-red @enderror"
                                        placeholder="12 rue des Flamboyants, 97200 Fort-de-France">{{ $pre('adresse_postale') }}</textarea>
                                    @error('adresse_postale')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>

                                {{-- Réseaux sociaux, facultatifs --}}
                                <div data-mode="adhesion">
                                    <div class="flex items-center justify-between gap-3 mb-2">
                                        <label class="block text-sm font-display font-bold text-mja-gray">
                                            Tes réseaux sociaux <span class="text-gray-500 font-normal">(facultatif)</span>
                                        </label>
                                        <button type="button" id="btn-reseaux"
                                            class="text-mja-blue font-display font-bold text-xs hover:underline">
                                            <i class="fas fa-plus-circle mr-1"></i> Ajouter mes réseaux
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mb-3">Uniquement si tu souhaites les partager avec l'équipe — ils apparaîtront sur ta fiche du trombinoscope.</p>
                                    <div id="bloc-reseaux" class="grid grid-cols-1 sm:grid-cols-2 gap-3 {{ collect($pre('reseaux_sociaux', []) ?: [])->filter()->isEmpty() ? 'hidden' : '' }}">
                                        @foreach(\App\Models\Adhesion::RESEAUX as $cle => [$label, $icone, $prefixe, $exemple])
                                        <div>
                                            <label for="a-res-{{ $cle }}" class="block text-xs font-display font-bold text-gray-500 mb-1">
                                                <i class="{{ $icone }} mr-1"></i> {{ $label }}
                                            </label>
                                            <div class="flex items-stretch border-2 border-gray-100 focus-within:border-mja-blue rounded-xl overflow-hidden transition-colors">
                                                @if($prefixe)<span class="pl-3 flex items-center text-sm text-gray-400 font-display font-bold">{{ $prefixe }}</span>@endif
                                                <input type="text" id="a-res-{{ $cle }}" name="reseaux_sociaux[{{ $cle }}]"
                                                    value="{{ $pre('reseaux_sociaux.'.$cle) }}" maxlength="150"
                                                    class="flex-1 bg-transparent border-0 px-3 py-2.5 text-sm outline-none min-w-0"
                                                    placeholder="{{ $exemple }}">
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bloc « votre question » : prise d'informations uniquement.
                             Une demande de renseignements n'a pas à livrer date de
                             naissance, taille de T-shirt ni contact d'urgence. --}}
                        <div data-mode="info" style="display:none">
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">4</span>
                                Votre demande
                            </h3>
                            <label for="a-message" class="block text-sm font-display font-bold text-mja-gray mb-1.5">
                                Que souhaites-tu savoir ? <span class="text-mja-red" aria-hidden="true">*</span>
                            </label>
                            <textarea id="a-message" name="message" rows="4"
                                class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors resize-y @error('message') border-mja-red @enderror"
                                placeholder="Les activités, le fonctionnement de l'association, comment devenir bénévole…">{{ $pre('message') }}</textarea>
                            @error('message')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                            <p class="text-xs text-gray-400 mt-1.5">Nous te répondons par email ou par téléphone. Aucune autre information ne t'est demandée à ce stade.</p>
                        </div>

                        {{-- Bloc 4 : Informations complémentaires --}}
                        <div data-mode="adhesion">
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">4</span>
                                Informations complémentaires
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-display font-bold text-mja-gray mb-2">Taille de T-Shirt <span class="text-mja-red">*</span></label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(['S', 'M', 'L', 'XL', '2XL', '3XL'] as $taille)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="taille_tshirt" value="{{ $taille }}"
                                                {{ $pre('taille_tshirt') === $taille ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="border-2 border-gray-100 peer-checked:border-mja-blue peer-checked:bg-mja-blue peer-checked:text-white rounded-xl px-4 py-2 text-sm font-display font-bold text-gray-500 transition-all hover:border-gray-200 min-w-[3rem] text-center">
                                                {{ $taille }}
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                    @error('taille_tshirt')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-display font-bold text-mja-gray mb-2">As-tu le permis de conduire ? <span class="text-mja-red">*</span></label>
                                    <div class="flex gap-3">
                                        @foreach(['Oui', 'Non'] as $opt)
                                        <label class="cursor-pointer flex-1 max-w-[8rem]">
                                            <input type="radio" name="permis" value="{{ $opt }}"
                                                {{ $pre('permis') === $opt ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="border-2 border-gray-100 peer-checked:border-mja-blue peer-checked:bg-mja-blue/5 rounded-xl p-3 text-center text-sm font-display font-bold text-gray-500 peer-checked:text-mja-blue transition-all hover:border-gray-200">
                                                {{ $opt }}
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                    @error('permis')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="a-sante" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Problèmes de santé, allergies ou intolérances <span class="text-gray-500 font-normal">(facultatif)</span></label>
                                    <textarea id="a-sante" name="problemes_sante" rows="2"
                                        class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors resize-none"
                                        placeholder="Précise ici si tu as des problèmes de santé, des allergies ou des intolérances...">{{ $pre('problemes_sante') }}</textarea>
                                </div>

                                <div>
                                    <label for="a-urgence" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Personne à contacter en cas d'urgence <span class="text-mja-red" aria-hidden="true">*</span></label>
                                    <input type="text" id="a-urgence" name="urgence_contact" value="{{ $pre('urgence_contact') }}" required
                                        class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('urgence_contact') border-mja-red @enderror"
                                        placeholder="Nom — Prénom — Numéro de téléphone">
                                    @error('urgence_contact')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Bloc 5 : Droit à l'image --}}
                        <div data-mode="adhesion">
                            <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs font-black">5</span>
                                Autorisation — Droit à l'image
                            </h3>
                            <div class="bg-gray-50 rounded-xl p-5 text-xs text-gray-500 leading-relaxed mb-4 border border-gray-100">
                                <p class="mb-2">Je déclare accepter de participer, à titre gracieux, aux photographies et vidéos effectuées dans le cadre des actions réalisées par <strong class="text-mja-gray">Madin' Jeunes Ambition</strong>. J'autorise, à titre gracieux, l'organisateur :</p>
                                <ul class="list-disc list-inside space-y-1 mb-2 ml-1">
                                    <li>à procéder à la diffusion des images fixes ou en mouvement me représentant ainsi que des éléments sonores associés,</li>
                                    <li>à exploiter ou autoriser l'exploitation des photographies, en tout ou partie, tant dans le secteur commercial que non commercial, public que privé, par tous modes et procédés analogique et/ou numérique, notamment par télédiffusion, réseaux numériques interactifs, reproduction sur tous supports connus ou inconnus à ce jour (vidéocassettes, DVD, CD-ROM, USB, etc.).</li>
                                </ul>
                                <p>Ceci, dans le cadre de leurs activités ou de toutes autres prestations, <strong>sans aucune limitation de durée et dans le monde entier</strong>.</p>
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="droit_image" value="1"
                                    {{ old('droit_image') ? 'checked' : '' }}
                                    class="mt-0.5 w-5 h-5 rounded border-2 border-gray-300 text-mja-blue focus:ring-mja-blue shrink-0 @error('droit_image') border-mja-red @enderror">
                                <span class="text-sm font-display font-bold text-mja-gray group-hover:text-mja-blue transition-colors">
                                    J'accepte l'autorisation du droit à l'image <span class="text-mja-red">*</span>
                                </span>
                            </label>
                            @error('droit_image')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror
                        </div>

                        {{-- Bloc : Cotisation & photo (adhésion / réadhésion uniquement) --}}
                        <div id="bloc-cotisation" class="space-y-6" style="display:none">
                            <div>
                                <h3 class="font-display font-bold text-mja-gray text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 bg-mja-blue text-white rounded-full flex items-center justify-center text-xs"><i class="fas fa-id-card"></i></span>
                                    Cotisation &amp; photo
                                </h3>

                                {{-- Photo --}}
                                <label class="block text-sm font-display font-bold text-mja-gray mb-1.5">Ta photo <span class="text-gray-500 font-normal">(facultatif)</span></label>
                                <div class="flex items-center gap-4">
                                    <div id="photo-preview" class="w-16 h-16 rounded-xl bg-gray-100 border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-300 shrink-0 overflow-hidden">
                                        <i class="fas fa-user text-xl"></i>
                                    </div>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="file" name="photo" id="photo-input" accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-display file:font-bold file:bg-mja-blue file:text-white hover:file:bg-mja-bluedark file:cursor-pointer border-2 border-gray-100 rounded-xl @error('photo') border-mja-red @enderror">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-400 mt-1.5">Format JPG ou PNG, 5 Mo max. Elle servira à te présenter aux autres membres. Tu peux aussi la déposer plus tard depuis ton espace adhérent.</p>
                                @error('photo')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror

                                {{-- Moyen de paiement --}}
                                @php
                                    $moyens = ['cheque' => ['Chèque','fa-money-check-alt'], 'espece' => ['Espèces','fa-money-bill-wave'], 'virement' => ['Virement','fa-university']];
                                    if (!empty($stripeEnabled)) { $moyens['en_ligne'] = ['Carte bancaire','fa-credit-card']; }
                                @endphp
                                <label class="block text-sm font-display font-bold text-mja-gray mt-6 mb-2">Comment souhaites-tu régler la cotisation de {{ \App\Support\Cotisation::formatee() }} ? <span class="text-mja-red">*</span></label>
                                <div class="grid grid-cols-2 {{ !empty($stripeEnabled) ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-3">
                                    @foreach($moyens as $val => [$label, $icon])
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="moyen_paiement" value="{{ $val }}" {{ $pre('moyen_paiement') === $val ? 'checked' : '' }} class="peer sr-only">
                                        <div class="border-2 border-gray-100 peer-checked:border-mja-blue peer-checked:bg-mja-blue/5 rounded-xl p-3 text-center text-sm font-display font-bold text-gray-500 peer-checked:text-mja-blue transition-all hover:border-gray-200 flex flex-col items-center gap-1.5">
                                            <i class="fas {{ $icon }} text-lg"></i> {{ $label }}
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                @if(!empty($stripeEnabled))
                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="fas fa-lock mr-1"></i> Paiement par carte 100 % sécurisé via Stripe.
                                    @if(\App\Support\Cotisation::fraisCarte() > 0)
                                    Le règlement par carte est majoré de <strong>{{ \App\Support\Cotisation::fraisFormates() }}</strong> de frais bancaires, soit <strong>{{ \App\Support\Cotisation::carteFormatee() }}</strong> au total, afin que l'association perçoive bien l'intégralité de la cotisation.
                                    @endif
                                    Les autres moyens : instructions envoyées par email.
                                </p>
                                @else
                                <p class="text-xs text-gray-400 mt-2"><i class="fas fa-circle-info mr-1"></i> Les instructions de règlement te seront envoyées par email.</p>
                                @endif
                                @error('moyen_paiement')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror

                                <div class="mt-5 bg-green-50 border border-green-100 rounded-xl p-4">
                                    <label for="promo-code" class="block text-sm font-display font-bold text-green-800 mb-1"><i class="fas fa-ticket mr-1"></i> Tu as un code promo ?</label>
                                    <p class="text-xs text-green-700 mb-2">Si l’équipe te l’a transmis après un problème de paiement, saisis-le ici.</p>
                                    <input type="text" name="promo_code" id="promo-code" value="{{ old('promo_code') }}" maxlength="40" autocomplete="off" placeholder="Ex. MJA-XXXXXXXX" class="w-full sm:w-72 uppercase border border-green-200 rounded-lg px-3 py-2 text-sm @error('promo_code') border-red-400 @enderror">
                                    <p id="promo-info" class="hidden text-xs text-green-700 font-semibold mt-2"></p>
                                    @error('promo_code')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                                </div>

                                @if(!empty($stripeEnabled))
                                {{-- Paiement par carte, intégré au formulaire --}}
                                <div id="bloc-cb" class="hidden mt-5 border-2 border-mja-blue/20 bg-mja-blue/5 rounded-2xl p-5">
                                    <div class="flex items-center justify-between gap-3 mb-4">
                                        <div class="font-display font-bold text-mja-gray text-sm">
                                            <i class="fas fa-credit-card text-mja-blue mr-1.5"></i> Régler {{ \App\Support\Cotisation::carteFormatee() }} par carte
                                            @if(\App\Support\Cotisation::fraisCarte() > 0)
                                            <span class="block font-normal text-xs text-gray-500 mt-0.5">
                                                {{ \App\Support\Cotisation::formatee() }} de cotisation + {{ \App\Support\Cotisation::fraisFormates() }} de frais de transaction
                                            </span>
                                            @endif
                                        </div>
                                        <span id="cb-badge" class="hidden items-center gap-1.5 bg-green-100 text-green-700 font-display font-bold text-xs px-3 py-1 rounded-full">
                                            <i class="fas fa-check-circle"></i> Paiement validé
                                        </span>
                                    </div>

                                    <div id="cb-zone">
                                        <div id="cb-element" class="bg-white rounded-xl p-3 border border-gray-200 min-h-[44px]"></div>

                                        <button type="button" id="cb-payer"
                                                class="mt-4 w-full bg-mja-blue hover:bg-mja-bluedark text-white font-display font-bold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                            <i class="fas fa-lock"></i> <span id="cb-payer-texte">Payer la cotisation</span>
                                        </button>
                                    </div>

                                    <p id="cb-erreur" class="hidden text-mja-red text-xs mt-3 font-display font-semibold"></p>
                                    <p id="cb-aide" class="text-xs text-gray-500 mt-3">
                                        <i class="fas fa-circle-info mr-1"></i> Réglez d'abord la cotisation ici, puis envoyez votre demande d'adhésion.
                                    </p>

                                    <input type="hidden" name="payment_intent_id" id="payment-intent-id" value="">
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Consentement RGPD -->
                        <div class="bg-mja-blue/5 border border-mja-blue/20 rounded-2xl p-5">
                            <div class="text-sm text-gray-600 leading-relaxed mb-4">
                                Les informations recueillies sont enregistrées par l'association <strong>Madin'Jeunes Ambition</strong> pour la gestion de votre adhésion et de la vie associative. Les données relatives à votre santé, que vous choisissez de renseigner, servent uniquement à assurer votre sécurité lors des activités et font l'objet d'un accès restreint. Vous disposez d'un droit d'accès, de rectification et de suppression de vos données ; pour en savoir plus, consultez notre
                                <a href="{{ route('confidentialite') }}" target="_blank" class="text-mja-blue font-semibold hover:underline">Politique de confidentialité</a>.
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" name="rgpd_consentement" value="1"
                                    {{ old('rgpd_consentement') ? 'checked' : '' }}
                                    class="mt-0.5 w-5 h-5 rounded border-2 border-gray-300 text-mja-blue focus:ring-mja-blue shrink-0 @error('rgpd_consentement') border-mja-red @enderror">
                                <span class="text-sm font-display font-bold text-mja-gray group-hover:text-mja-blue transition-colors">
                                    Je consens à la collecte et au traitement de mes données personnelles, y compris les données de santé renseignées, dans les conditions décrites ci-dessus <span class="text-mja-red">*</span>
                                </span>
                            </label>
                            @error('rgpd_consentement')<p class="text-mja-red text-xs mt-1.5 font-display font-semibold">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" id="btn-envoyer"
                            class="w-full btn-blue font-display font-bold py-4 rounded-xl transition-colors flex items-center justify-center gap-2 text-base disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-paper-plane"></i>
                            <span id="btn-envoyer-texte">{{ !empty($precedente) ? 'Envoyer mon renouvellement' : "Envoyer ma demande d'adhésion" }}</span>
                        </button>
                        <p id="btn-envoyer-aide" class="hidden text-center text-xs text-gray-500 -mt-2">
                            <i class="fas fa-lock mr-1"></i> Réglez la cotisation par carte ci-dessus pour activer l'envoi.
                        </p>
                    </form>
                    @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    var radios  = document.querySelectorAll('input[name="premiere_adhesion"]');
    var bloc    = document.getElementById('bloc-cotisation');
    var photo   = document.getElementById('photo-input');
    var moyens  = document.querySelectorAll('input[name="moyen_paiement"]');
    if (!bloc) return;

    var titre       = document.getElementById('titre-formulaire');
    var texteBouton = document.getElementById('btn-envoyer-texte');
    var blocInfo    = document.getElementById('bloc-readhesion');
    var champMessage = document.getElementById('a-message');

    /* Libellés par type de démarche. Sans cela, une prise d'informations
       proposait « Envoyer ma demande d'adhésion » — ce qui n'est pas ce que
       la personne est en train de faire. */
    var LIBELLES = {
        premiere:    ["Formulaire d'adhésion",    "Envoyer ma demande d'adhésion"],
        readhesion:  ['Formulaire de réadhésion', 'Envoyer ma réadhésion'],
        information: ["Demande d'informations",   "Envoyer ma demande d'informations"]
    };

    function refresh() {
        var val  = document.querySelector('input[name="premiere_adhesion"]:checked');
        var mode = val ? val.value : null;

        // Cotisation et photo : première adhésion et réadhésion uniquement.
        var adhesion = mode === 'premiere' || mode === 'readhesion';
        bloc.style.display = adhesion ? '' : 'none';
        // La photo n'est plus exigée : elle peut être déposée plus tard.
        moyens.forEach(function (m) { m.required = adhesion; });

        /* Une prise d'informations ne collecte que l'identité, les coordonnées
           et la question posée : tout le reste est masqué, et surtout
           dé-« required », sinon le navigateur bloquerait sur un champ caché. */
        document.querySelectorAll('[data-mode]').forEach(function (zone) {
            var visible = mode === null
                ? zone.dataset.mode === 'adhesion'
                : (zone.dataset.mode === 'info' ? !adhesion : adhesion);

            zone.style.display = visible ? '' : 'none';
            zone.querySelectorAll('input, select, textarea').forEach(function (champ) {
                if (champ.dataset.requis === undefined) {
                    champ.dataset.requis = champ.required ? '1' : '0';
                }
                champ.required = visible && champ.dataset.requis === '1';
            });
        });

        if (champMessage) { champMessage.required = mode === 'information'; }

        var libelles = LIBELLES[mode];
        if (libelles) {
            if (titre && !@json(!empty($precedente))) { titre.textContent = libelles[0]; }
            if (texteBouton) { texteBouton.textContent = libelles[1]; }
        }

        // Raccourci vers l'espace adhérent : en réadhésion, le formulaire
        // pré-rempli évite toute ressaisie.
        if (blocInfo) { blocInfo.classList.toggle('hidden', mode !== 'readhesion'); }
    }
    radios.forEach(function (r) { r.addEventListener('change', refresh); });
    refresh();

    // Réseaux sociaux : repliés par défaut, dépliés à la demande (ou si déjà saisis).
    var btnReseaux  = document.getElementById('btn-reseaux');
    var blocReseaux = document.getElementById('bloc-reseaux');
    if (btnReseaux && blocReseaux) {
        var maj = function () {
            var ouvert = !blocReseaux.classList.contains('hidden');
            btnReseaux.innerHTML = ouvert
                ? '<i class="fas fa-minus-circle mr-1"></i> Masquer'
                : '<i class="fas fa-plus-circle mr-1"></i> Ajouter mes réseaux';
        };
        btnReseaux.addEventListener('click', function () {
            blocReseaux.classList.toggle('hidden');
            maj();
        });
        maj();
    }

    // Aperçu de la photo
    var preview = document.getElementById('photo-preview');
    if (photo && preview) {
        photo.addEventListener('change', function () {
            var f = this.files && this.files[0];
            if (!f) return;
            var url = URL.createObjectURL(f);
            preview.innerHTML = '<img src="' + url + '" class="w-full h-full object-cover" alt="Aperçu">';
        });
    }
})();
</script>

@if(!empty($stripeEnabled))
<script>
(function () {
    var blocCb   = document.getElementById('bloc-cb');
    var moyens   = document.querySelectorAll('input[name="moyen_paiement"]');
    var btnEnvoi = document.getElementById('btn-envoyer');
    var aideEnvoi= document.getElementById('btn-envoyer-aide');
    if (!blocCb || !btnEnvoi) return;

    var zone      = document.getElementById('cb-zone');
    var badge     = document.getElementById('cb-badge');
    var btnPayer  = document.getElementById('cb-payer');
    var txtPayer  = document.getElementById('cb-payer-texte');
    var erreur    = document.getElementById('cb-erreur');
    var aideCb    = document.getElementById('cb-aide');
    var champIntent = document.getElementById('payment-intent-id');

    var stripe = null, elements = null, paymentElement = null, monte = false, paye = false, promoTimer = null;

    /**
     * Un blocage de Stripe.js a deux causes très différentes : une extension du
     * visiteur, ou la politique de sécurité (CSP) du site — auquel cas c'est à
     * nous de la corriger, pas au visiteur de désactiver quoi que ce soit.
     * Le navigateur émet « securitypolicyviolation » dans le second cas : on
     * l'écoute pour afficher le bon message plutôt que d'accuser à tort.
     */
    var bloqueParCsp = false;
    document.addEventListener('securitypolicyviolation', function (e) {
        if (String(e.blockedURI || '').indexOf('stripe.com') !== -1) {
            bloqueParCsp = true;
            console.error(
                'CSP : ' + e.blockedURI + ' bloqué par la directive « ' + e.violatedDirective + ' ». '
                + 'Autorisez js.stripe.com (script-src, frame-src), hooks.stripe.com (frame-src) '
                + 'et api.stripe.com (connect-src) dans public/.htaccess.'
            );
        }
    });

    function afficheErreur(msg) {
        erreur.textContent = msg;
        erreur.classList.remove('hidden');
    }

    function cacheErreur() {
        erreur.classList.add('hidden');
    }

    /**
     * Le bouton d'envoi n'est bloqué que si le bloc cotisation est affiché,
     * que « carte » est choisi, et que le règlement n'a pas encore abouti.
     * (En « prise d'informations », aucune cotisation n'est due.)
     */
    function majBoutonEnvoi() {
        var blocCotis = document.getElementById('bloc-cotisation');
        var cotisVisible = blocCotis && blocCotis.style.display !== 'none';
        var choix = document.querySelector('input[name="moyen_paiement"]:checked');
        var carte = choix && choix.value === 'en_ligne';
        var bloque = cotisVisible && carte && !paye;

        btnEnvoi.disabled = bloque;
        aideEnvoi.classList.toggle('hidden', !bloque);
    }

    /**
     * Garantit que Stripe.js est disponible. La balise du <head> peut avoir
     * échoué (réseau lent, cache de vues obsolète) : on retente ici, une fois.
     */
    function chargerStripeJs() {
        if (typeof Stripe !== 'undefined') { return Promise.resolve(); }

        return new Promise(function (resolve, reject) {
            var existante = document.querySelector('script[src^="https://js.stripe.com/v3"]');

            var fini = function () {
                typeof Stripe !== 'undefined' ? resolve() : reject(new Error('stripe-indisponible'));
            };

            if (existante) {
                existante.addEventListener('load', fini);
                existante.addEventListener('error', fini);
            } else {
                var s = document.createElement('script');
                s.src = 'https://js.stripe.com/v3/';
                s.addEventListener('load', fini);
                s.addEventListener('error', fini);
                document.head.appendChild(s);
            }

            // Filet de sécurité : script bloqué sans événement d'erreur.
            setTimeout(fini, 8000);
        });
    }

    function echecStripeJs() {
        zone.classList.add('hidden');
        aideCb.classList.add('hidden');

        if (bloqueParCsp) {
            // Rien à faire côté visiteur : le blocage vient de la configuration du site.
            afficheErreur("Le paiement par carte est momentanément indisponible sur ce site. "
                + "Choisissez un autre moyen de règlement (virement, chèque ou espèces) : "
                + "nous vous enverrons les instructions par email.");
        } else {
            afficheErreur("Le module de paiement par carte n'a pas pu se charger. "
                + "Vérifiez votre connexion, ou désactivez votre bloqueur de publicités puis rechargez la page. "
                + "Vous pouvez aussi choisir un autre moyen de règlement.");
        }
    }

    /** Charge Stripe Elements à la première sélection de « carte bancaire ». */
    function monterElements() {
        if (monte) return;
        monte = true;
        btnPayer.disabled = true;
        txtPayer.textContent = 'Chargement…';

        chargerStripeJs()
            .then(creerIntent)
            .catch(function () { monte = false; echecStripeJs(); });
    }

    function creerIntent() {
        var codePromo = document.getElementById('promo-code');
        return fetch(@json(route('adhesion.payment-intent')), {
            method: 'POST',
            headers: {
                // Le layout n'expose pas de meta csrf-token : on reprend celui du formulaire.
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ promo_code: codePromo ? codePromo.value : '' })
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            if (!res.ok) { throw new Error(res.data.error || 'Paiement indisponible.'); }

            var infoPromo = document.getElementById('promo-info');
            if (res.data.free) {
                paye = true;
                zone.classList.add('hidden');
                aideCb.classList.add('hidden');
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Code promo appliqué';
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
                if (infoPromo) { infoPromo.textContent = 'Remise de ' + res.data.discount_percent + ' % appliquée : aucun paiement à effectuer.'; infoPromo.classList.remove('hidden'); }
                majBoutonEnvoi();
                return;
            }

            if (infoPromo) {
                infoPromo.classList.toggle('hidden', !res.data.discount_percent);
                if (res.data.discount_percent) { infoPromo.textContent = 'Remise de ' + res.data.discount_percent + ' % appliquée. Montant à régler par CB : ' + res.data.total + '.'; }
            }
            stripe = Stripe(res.data.public_key);
            elements = stripe.elements({ clientSecret: res.data.client_secret, locale: 'fr' });
            paymentElement = elements.create('payment', { layout: 'tabs' });
            paymentElement.mount('#cb-element');

            btnPayer.disabled = false;
            txtPayer.textContent = 'Payer la cotisation';
        })
        .catch(function (e) {
            monte = false;
            btnPayer.disabled = true;
            txtPayer.textContent = 'Payer la cotisation';
            afficheErreur(e.message || 'Le paiement est momentanément indisponible.');
        });
    }

    btnPayer.addEventListener('click', function () {
        if (!stripe || !elements) return;

        cacheErreur();
        btnPayer.disabled = true;
        txtPayer.textContent = 'Paiement en cours…';

        // redirect: 'if_required' garde le visiteur sur la page quand la carte
        // ne demande pas d'authentification 3-D Secure.
        stripe.confirmPayment({ elements: elements, redirect: 'if_required' })
            .then(function (res) {
                if (res.error) {
                    btnPayer.disabled = false;
                    txtPayer.textContent = 'Payer la cotisation';
                    afficheErreur(res.error.message || 'Le paiement a échoué.');
                    return;
                }

                if (res.paymentIntent && res.paymentIntent.status === 'succeeded') {
                    paye = true;
                    champIntent.value = res.paymentIntent.id;
                    zone.classList.add('hidden');
                    aideCb.classList.add('hidden');
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                    majBoutonEnvoi();
                    return;
                }

                btnPayer.disabled = false;
                txtPayer.textContent = 'Payer la cotisation';
                afficheErreur('Paiement non confirmé. Réessayez ou choisissez un autre moyen.');
            });
    });

    moyens.forEach(function (m) {
        m.addEventListener('change', function () {
            var carte = this.checked && this.value === 'en_ligne';
            blocCb.classList.toggle('hidden', !carte);
            if (carte) { monterElements(); }
            majBoutonEnvoi();
        });
    });
    document.getElementById('promo-code')?.addEventListener('input', function () {
        majBoutonEnvoi();
        var carte = document.querySelector('input[name="moyen_paiement"]:checked')?.value === 'en_ligne';
        if (!carte || paye) return;
        clearTimeout(promoTimer);
        promoTimer = setTimeout(function () {
            if (paymentElement) { paymentElement.unmount(); paymentElement = null; }
            document.getElementById('cb-element').innerHTML = '';
            elements = null;
            monte = false;
            monterElements();
        }, 500);
    });

    // Le choix du type de demande peut masquer tout le bloc cotisation.
    document.querySelectorAll('input[name="premiere_adhesion"]').forEach(function (r) {
        r.addEventListener('change', majBoutonEnvoi);
    });

    majBoutonEnvoi();
})();
</script>
@endif
@endpush

@endsection
