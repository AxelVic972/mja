@extends('layouts.app')
@section('title', "{$project->titre} — Madin'Jeunes Ambition")
@section('og_title', $project->titre)
@section('meta_description', $project->description ? \Illuminate\Support\Str::limit(strip_tags($project->description), 155) : "Projet de Madin'Jeunes Ambition — association de jeunes bénévoles en Martinique.")
@section('og_image', $project->imageUrl() ?? asset('images/partage/projets.jpg'))

@section('content')
<section class="hero-gradient text-white py-16 relative overflow-hidden">
    <div class="absolute -right-10 -top-10 w-64 h-64 opacity-10 pointer-events-none">
        <svg viewBox="0 0 200 200" fill="none"><circle cx="100" cy="100" r="90" stroke="#F5A623" stroke-width="2"/><circle cx="100" cy="100" r="65" stroke="#3DAEF5" stroke-width="2"/></svg>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-sm text-gray-400 mb-4 font-display font-semibold">
            <a href="{{ route('home') }}" class="hover:text-mja-yellow">Accueil</a>
            <span class="mx-2 text-gray-600">/</span>
            <a href="{{ route('projects.index') }}" class="hover:text-mja-yellow">Projets</a>
            <span class="mx-2 text-gray-600">/</span>
            {{ Str::limit($project->titre, 40) }}
        </div>
        <span class="inline-block text-xs font-display font-bold px-4 py-1.5 rounded-full mb-4
            {{ $project->statut === 'en_cours' ? 'bg-mja-blue/30 border border-mja-blue/40 text-blue-200' : ($project->statut === 'a_venir' ? 'bg-mja-yellow/30 border border-mja-yellow/40 text-yellow-200' : 'bg-white/10 border border-white/20 text-gray-300') }}">
            @if($project->statut === 'en_cours')<i class="fas fa-circle text-[8px] mr-1"></i> En cours
            @elseif($project->statut === 'a_venir')<i class="far fa-circle text-[8px] mr-1"></i> À venir
            @else<i class="fas fa-check text-[8px] mr-1"></i> Terminé@endif
        </span>
        <h1 class="font-display font-black text-3xl sm:text-4xl lg:text-5xl mb-4 leading-tight">{{ $project->titre }}</h1>
        @if($project->date_debut)
        <div class="text-gray-300 text-sm font-display font-semibold">
            <i class="fas fa-calendar mr-1 text-mja-yellow"></i>
            {{ $project->date_debut->locale('fr')->isoFormat('D MMMM Y') }}
            @if($project->date_fin) <span class="mx-1">→</span> {{ $project->date_fin->locale('fr')->isoFormat('D MMMM Y') }}@endif
        </div>
        @endif
    </div>
</section>
<div class="flex h-1"><div class="flex-1 bg-mja-blue"></div><div class="flex-1 bg-mja-yellow"></div><div class="flex-1 bg-mja-red"></div></div>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($project->image)
        <img src="{{ $project->imageUrl() }}" alt="{{ $project->titre }}"
             class="w-full rounded-2xl mb-10 object-cover max-h-96 shadow-md">
        @endif
        @if($project->description_courte)
        <p class="text-xl text-gray-600 font-display font-semibold leading-relaxed mb-8 border-l-4 border-mja-blue pl-5 py-1">
            {{ $project->description_courte }}
        </p>
        @endif
        <div class="prose-mja text-gray-700 leading-loose text-[15px]">
            {!! \App\Support\HtmlRiche::rendre($project->description) !!}
        </div>

        @php
            /* Les prochains rendez-vous d'abord, dans l'ordre où ils arrivent ;
               les éditions passées ensuite, de la plus récente à la plus ancienne. */
            $evenements = $project->events()->where('publie', true)->get();
            $aVenir = $evenements->filter(fn ($e) => $e->date_debut && $e->date_debut->isFuture())
                                 ->sortBy('date_debut')->values();
            $passes = $evenements->reject(fn ($e) => $e->date_debut && $e->date_debut->isFuture())
                                 ->sortByDesc('date_debut')->values();
        @endphp

        @if($aVenir->count() || $passes->count())
        <div class="mt-12">
            @foreach([
                ['À venir', $aVenir, 'fa-calendar-day', 'text-mja-red', true],
                ['Événements passés', $passes, 'fa-clock-rotate-left', 'text-gray-400', false],
            ] as [$titreGroupe, $groupe, $icone, $couleur, $enAvant])
                @if($groupe->count())
                <h2 class="font-display font-black text-xl text-mja-gray mb-5 mt-10 first:mt-0 flex items-center gap-2">
                    <i class="fas {{ $icone }} {{ $couleur }}"></i> {{ $titreGroupe }}
                    <span class="text-sm font-display font-semibold text-gray-400">({{ $groupe->count() }})</span>
                </h2>
                <div class="space-y-3">
                    @foreach($groupe as $ev)
                    <a href="{{ route('events.show', $ev->slug) }}"
                       class="flex items-center gap-4 border rounded-2xl px-5 py-3.5 transition-colors group
                              {{ $enAvant ? 'bg-mja-blue/5 border-mja-blue/20 hover:bg-mja-blue/10' : 'bg-gray-50 border-gray-100 hover:bg-white' }}">
                        <div class="rounded-xl p-2.5 text-center min-w-[52px] shrink-0 text-white
                                    {{ $enAvant ? 'bg-mja-blue' : 'bg-mja-dark' }}">
                            <div class="font-display font-black text-lg leading-none">{{ $ev->date_debut->format('d') }}</div>
                            <div class="text-[10px] uppercase text-mja-yellow font-display font-semibold">{{ $ev->date_debut->locale('fr')->isoFormat('MMM') }}</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-mja-gray group-hover:text-mja-blue transition-colors">{{ $ev->titre }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 flex flex-wrap gap-3">
                                <span><i class="fas fa-calendar mr-1"></i>{{ $ev->date_debut->locale('fr')->isoFormat('D MMMM Y') }}</span>
                                @if($ev->lieu)<span><i class="fas fa-map-marker-alt mr-1"></i>{{ $ev->lieu }}</span>@endif
                                @if($enAvant && $ev->gratuit)<span class="text-green-600 font-semibold"><i class="fas fa-tag mr-1"></i>Gratuit</span>@endif
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 text-xs shrink-0"></i>
                    </a>
                    @endforeach
                </div>
                @endif
            @endforeach
        </div>
        @endif

        <div class="mt-12 pt-6 border-t border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <a href="{{ route('projects.index') }}" class="text-mja-blue hover:text-mja-bluedark font-display font-bold flex items-center gap-2 transition-colors">
                <i class="fas fa-arrow-left"></i> Retour aux projets
            </a>
            <a href="{{ route('contact') }}" class="btn-yellow font-display font-bold px-6 py-3 rounded-full text-sm transition-colors inline-flex items-center gap-2">
                <i class="fas fa-handshake"></i> Participer à ce projet
            </a>
        </div>
    </div>
</section>
@endsection
