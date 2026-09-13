@extends('layouts.app')
@section('title', "Contact — Madin'Jeunes Ambition")
@section('og_image', asset('images/partage/contact.jpg'))
@section('meta_description', "Contactez Madin'Jeunes Ambition — association de jeunes bénévoles en Martinique et au-delà. Pour toute question, partenariat ou information sur nos programmes.")

@section('content')

<section class="hero-gradient text-white py-16 relative overflow-hidden">
    <div class="absolute -right-16 -top-16 w-64 h-64 opacity-10">
        <svg viewBox="0 0 200 200" fill="none"><circle cx="100" cy="100" r="95" stroke="#3DAEF5" stroke-width="2"/><circle cx="100" cy="100" r="65" stroke="#F5A623" stroke-width="2"/></svg>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-sm text-gray-400 mb-3 font-display font-semibold">
            <a href="{{ route('home') }}" class="hover:text-mja-yellow transition-colors">Accueil</a>
            <span class="mx-2 text-gray-600">/</span> Contact
        </div>
        <h1 class="font-display font-black text-4xl sm:text-5xl mb-4">Contactez-nous</h1>
        <p class="text-gray-300 text-lg max-w-2xl">Une question, un projet, envie de rejoindre <span class="text-mja-blue font-bold">M</span><span class="text-mja-yellow font-bold">J</span><span class="text-mja-red font-bold">A</span> ?</p>
    </div>
</section>

<div class="flex h-1"><div class="flex-1 bg-mja-blue"></div><div class="flex-1 bg-mja-yellow"></div><div class="flex-1 bg-mja-red"></div></div>

<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <!-- Infos -->
            <div class="space-y-6">
                <div>
                    <h2 class="font-display font-bold text-xl text-mja-gray mb-5">Informations</h2>
                    <div class="space-y-4">
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 bg-mja-blue/10 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-map-marker-alt text-mja-blue"></i>
                            </div>
                            <div>
                                <div class="font-display font-bold text-mja-gray text-sm">Où nous agissons</div>
                                <div class="text-gray-500 text-sm mt-0.5">Martinique et au-delà</div>
                            </div>
                        </div>
                        <div class="flex gap-4 items-start">
                            <div class="w-10 h-10 bg-mja-blue/10 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-envelope text-mja-blue"></i>
                            </div>
                            <div>
                                <div class="font-display font-bold text-mja-gray text-sm">Email</div>
                                <a href="mailto:{{ config('mja.contact_email') }}" class="text-mja-blue hover:underline text-sm mt-0.5 block">{{ config('mja.contact_email') }}</a>
                            </div>
                        </div>
                    </div>

                    <!-- Réseaux sociaux -->
                    <div>
                        <h3 class="font-display font-bold text-mja-gray text-sm mb-3">Nos réseaux sociaux</h3>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([
                                ['fab fa-facebook','Facebook','MadinJeunesAmbition','https://www.facebook.com/MadinJeunesAmbition/','#1877F2'],
                                ['fab fa-instagram','Instagram','@madin_jeunes_ambition','https://www.instagram.com/madin_jeunes_ambition/','#E1306C'],
                                ['fab fa-tiktok','TikTok','@fwi_ti_dej','https://www.tiktok.com/@fwi_ti_dej','#010101'],
                                ['fab fa-youtube','YouTube','MJA Officiel','https://www.youtube.com/channel/UCX6nyVEv_QyFuLREyVvOMLw','#FF0000'],
                            ] as [$icon,$name,$handle,$url,$color])
                            <a href="{{ $url }}" target="_blank"
                               class="flex flex-col items-center gap-1.5 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-xl p-3 transition-colors text-center">
                                <i class="{{ $icon }} text-xl" style="color:{{ $color }}"></i>
                                <span class="text-xs font-display font-bold text-mja-gray">{{ $name }}</span>
                            </a>
                            @endforeach
                            <a href="mailto:{{ config('mja.contact_email') }}"
                               class="flex flex-col items-center gap-1.5 bg-mja-blue/10 hover:bg-mja-blue/20 border border-mja-blue/20 rounded-xl p-3 transition-colors text-center">
                                <i class="fas fa-envelope text-xl text-mja-blue"></i>
                                <span class="text-xs font-display font-bold text-mja-blue">Email</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Logo card -->
                <div class="bg-mja-dark rounded-2xl p-6 text-center ring-watermark">
                    <img src="/images/logo.jpg" alt="MJA" class="h-20 w-20 mx-auto mb-4 object-contain bg-white rounded-xl p-1">
                    <div class="font-display font-black text-2xl mb-1">
                        <span class="text-mja-blue">M</span><span class="text-mja-yellow">J</span><span class="text-mja-red">A</span>
                    </div>
                    <div class="text-gray-400 text-xs font-display font-semibold uppercase tracking-wider">Madin' Jeunes Ambition</div>
                </div>

                <a href="{{ route('adhesion') }}" class="group block bg-mja-yellow/10 hover:bg-mja-yellow/20 rounded-2xl p-5 border-l-4 border-mja-yellow transition-colors">
                    <h3 class="font-display font-bold text-mja-gray mb-2 group-hover:text-mja-blue transition-colors flex items-center gap-2">
                        <i class="fas fa-user-plus text-mja-yellow"></i> Rejoindre l'association
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-3">Tu as entre 16 et 35 ans et tu veux t'engager ? Remplis le formulaire d'adhésion en ligne.</p>
                    <span class="inline-flex items-center gap-2 text-mja-blue font-display font-bold text-sm">
                        Formulaire d'adhésion <i class="fas fa-arrow-right text-xs"></i>
                    </span>
                </a>

                <a href="{{ route('don') }}" class="group block bg-mja-red/5 hover:bg-mja-red/10 rounded-2xl p-5 border-l-4 border-mja-red transition-colors">
                    <h3 class="font-display font-bold text-mja-gray mb-2 group-hover:text-mja-red transition-colors flex items-center gap-2">
                        <i class="fas fa-heart text-mja-red"></i> Soutenir l'association
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-3">Un don, même modeste, nous aide à mener nos actions auprès des jeunes.</p>
                    <span class="inline-flex items-center gap-2 text-mja-red font-display font-bold text-sm">
                        Faire un don <i class="fas fa-arrow-right text-xs"></i>
                    </span>
                </a>
            </div>

            <!-- Formulaire -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex h-1.5">
                    <div class="flex-1 bg-mja-blue"></div>
                    <div class="flex-1 bg-mja-yellow"></div>
                    <div class="flex-1 bg-mja-red"></div>
                </div>
                <div class="p-8">
                    <h2 class="font-display font-bold text-xl text-mja-gray mb-6">Envoyer un message</h2>

                    @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-6 flex items-start gap-3 font-display font-semibold text-sm">
                        <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    @endif

                    @error('antispam')
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6 flex items-start gap-3 font-display font-semibold text-sm">
                        <i class="fas fa-triangle-exclamation text-mja-red mt-0.5"></i>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror

                    <form method="POST" action="{{ route('contact.store') }}" class="space-y-5">
                        @csrf
                        <x-form-guard />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="c-nom" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Nom complet <span class="text-mja-red" aria-hidden="true">*</span></label>
                                <input type="text" id="c-nom" name="nom" value="{{ old('nom') }}" required autocomplete="name"
                                    class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('nom') border-mja-red @enderror"
                                    placeholder="Jean Dupont">
                                @error('nom')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="c-email" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Email <span class="text-mja-red" aria-hidden="true">*</span></label>
                                <input type="email" id="c-email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                    class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('email') border-mja-red @enderror"
                                    placeholder="jean@exemple.com">
                                @error('email')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <span class="block text-sm font-display font-bold text-mja-gray mb-1.5">Téléphone</span>
                                <x-phone-field :value="old('telephone')" :indicatif="old('indicatif', '+596')" />
                            </div>
                            <div>
                                <label for="c-sujet" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Sujet <span class="text-mja-red" aria-hidden="true">*</span></label>
                                <input type="text" id="c-sujet" name="sujet" value="{{ old('sujet') }}" required
                                    class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors @error('sujet') border-mja-red @enderror"
                                    placeholder="Adhésion, partenariat...">
                                @error('sujet')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="c-message" class="block text-sm font-display font-bold text-mja-gray mb-1.5">Message <span class="text-mja-red" aria-hidden="true">*</span></label>
                            <textarea id="c-message" name="message" rows="5" required
                                class="w-full border-2 border-gray-100 focus:border-mja-blue rounded-xl px-4 py-3 text-sm outline-none transition-colors resize-none @error('message') border-mja-red @enderror"
                                placeholder="Votre message...">{{ old('message') }}</textarea>
                            @error('message')<p class="text-mja-red text-xs mt-1 font-display font-semibold">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit"
                            class="w-full btn-blue font-display font-bold py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i> Envoyer le message
                        </button>
                        <p class="text-xs text-gray-400 leading-relaxed">
                            Les données transmises via ce formulaire sont utilisées uniquement pour répondre à votre demande et ne sont jamais cédées à des tiers.
                            En savoir plus dans notre <a href="{{ route('confidentialite') }}" class="text-mja-blue hover:underline">Politique de confidentialité</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
