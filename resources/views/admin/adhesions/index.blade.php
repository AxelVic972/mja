@extends('layouts.admin')
@section('title', 'Demandes d\'adhésion')
@section('page-title', 'Demandes d\'adhésion')
@section('content')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-mja-blue/10 rounded-xl flex items-center justify-center shrink-0">
            <i class="fas fa-users text-mja-blue"></i>
        </div>
        <div>
            <div class="text-2xl font-display font-black text-mja-gray">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Total</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center shrink-0">
            <i class="fas fa-clock text-orange-400"></i>
        </div>
        <div>
            <div class="text-2xl font-display font-black text-mja-gray">{{ $stats['en_attente_paiement'] }}</div>
            <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">En attente de paiement</div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center shrink-0">
            <i class="fas fa-check-circle text-green-500"></i>
        </div>
        <div>
            <div class="text-2xl font-display font-black text-mja-gray">{{ $stats['adherents'] }}</div>
            <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Adhérents (payés)</div>
        </div>
    </div>
</div>

{{-- Adhésions orphelines : sans saison, elles n'apparaissent dans aucun filtre
     ni export par période et ne reçoivent pas de relance de renouvellement.
     Le rattachement en masse évite de les reprendre une par une. --}}
@if($sansPeriode > 0 && $periods->count())
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-2xl p-4 sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>
            <div>
                <div class="font-display font-bold text-amber-900 text-sm">
                    {{ $sansPeriode }} adhésion{{ $sansPeriode > 1 ? 's' : '' }} sans saison
                </div>
                <p class="text-xs text-amber-800 mt-0.5">
                    Invisibles dans les filtres et les exports par période, et jamais relancées pour le renouvellement.
                    <a href="{{ route('admin.adhesions.index', ['period' => 'aucune']) }}" class="font-semibold underline">Les afficher</a>
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.adhesions.rattacher-periode') }}"
              class="flex flex-wrap items-center gap-2"
              data-confirm="Rattacher les {{ $sansPeriode }} adhésions sans saison à la saison choisie ?">
            @csrf
            <select name="period_id" required class="border border-amber-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mja-blue">
                @foreach($periods as $p)
                <option value="{{ $p->id }}" @selected($p->actif ?? false)>{{ $p->label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-display font-bold text-sm px-4 py-2 rounded-xl transition-colors">
                Tout rattacher
            </button>
        </form>
    </div>
</div>
@endif

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    @if($periods->count())
    <form method="GET" class="flex items-center gap-2">
        <label class="text-sm text-gray-500 font-display font-semibold">Période :</label>
        <select name="period" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mja-blue">
            @if($periodeSelectionnee)
            <option value="" @selected(!request()->has('period'))>{{ $periodeSelectionnee->label }} (par défaut)</option>
            @endif
            <option value="toutes" @selected(request('period') === 'toutes')>Toutes les saisons</option>
            @foreach($periods as $p)
            <option value="{{ $p->id }}" @selected(request()->has('period') && request('period') !== 'aucune' && request('period') == $p->id)>{{ $p->label }}</option>
            @endforeach
            @if($sansPeriode > 0)
            <option value="aucune" @selected(request('period') === 'aucune')>Sans saison ({{ $sansPeriode }})</option>
            @endif
        </select>
        @if(request()->has('period'))<a href="{{ route('admin.adhesions.index') }}" class="text-xs text-mja-blue hover:underline">Revenir à la saison par défaut</a>@endif
    </form>
    @else
    <span></span>
    @endif
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.adhesions.create') }}"
           class="inline-flex items-center gap-2 bg-mja-blue hover:bg-mja-bluedark text-white font-display font-bold text-sm px-4 py-2 rounded-xl transition-colors">
            <i class="fas fa-plus"></i> Ajouter une adhésion
        </a>
        <a href="{{ route('admin.adhesions.export', array_filter(['period' => request('period')])) }}"
           class="inline-flex items-center gap-2 bg-mja-dark hover:bg-mja-navy text-white font-display font-bold text-sm px-4 py-2 rounded-xl transition-colors">
            <i class="fas fa-file-csv"></i> Exporter en CSV
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="w-full text-sm min-w-[1000px]">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-6 py-3 text-left font-semibold w-4"></th>
                <th class="px-4 py-3 text-left font-semibold">Candidat</th>
                <th class="px-4 py-3 text-left font-semibold">Type</th>
                <th class="px-4 py-3 text-left font-semibold">Statut</th>
                <th class="px-4 py-3 text-left font-semibold">Paiement</th>
                <th class="px-4 py-3 text-left font-semibold">Commentaire</th>
                <th class="px-4 py-3 text-left font-semibold">Période</th>
                <th class="px-4 py-3 text-left font-semibold">Date</th>
                <th class="px-4 py-3 text-center font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($adhesions as $adhesion)
            <tr class="hover:bg-gray-50 {{ !$adhesion->lu ? 'bg-blue-50/40' : '' }}">
                <td class="px-6 py-4">
                    @if(!$adhesion->lu)<span class="w-2 h-2 bg-blue-500 rounded-full inline-block"></span>@endif
                </td>
                <td class="px-4 py-4">
                    <div class="font-semibold {{ !$adhesion->lu ? 'text-gray-900' : 'text-gray-600' }}">
                        {{ $adhesion->civilite }} {{ $adhesion->prenom }} {{ $adhesion->nom }}
                    </div>
                    <div class="text-xs text-gray-400">{{ $adhesion->email }}</div>
                </td>
                <td class="px-4 py-4">
                    @php $typeColors = ['premiere' => 'bg-blue-100 text-blue-700', 'readhesion' => 'bg-purple-100 text-purple-700', 'information' => 'bg-gray-100 text-gray-600']; @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $typeColors[$adhesion->premiere_adhesion] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $adhesion->label_premiere_adhesion }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    @php $statutColors = ['nouvelle' => 'bg-orange-100 text-orange-700', 'prise_infos' => 'bg-sky-100 text-sky-700', 'en_attente_paiement' => 'bg-amber-100 text-amber-700', 'payee' => 'bg-green-100 text-green-700', 'refusee' => 'bg-red-100 text-red-700', 'desistement' => 'bg-gray-200 text-gray-600', 'traitee' => 'bg-blue-100 text-blue-700', 'acceptee' => 'bg-green-100 text-green-700']; @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statutColors[$adhesion->statut] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $adhesion->label_statut }}
                    </span>
                </td>
                <td class="px-4 py-4 text-xs text-gray-600 whitespace-nowrap">{{ $adhesion->label_moyen_paiement }}</td>
                <td class="px-4 py-4 text-xs text-gray-500 max-w-48">
                    @if($adhesion->commentaire)
                    <span title="{{ $adhesion->commentaire }}" class="block truncate">{{ $adhesion->commentaire }}</span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-4">
                    @if($adhesion->period)
                    <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">{{ $adhesion->period->label }}</span>
                    @else
                    <a href="{{ route('admin.adhesions.show', $adhesion) }}"
                       class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full hover:bg-amber-100">Sans saison</a>
                    @endif
                </td>
                <td class="px-4 py-4 text-gray-400 text-xs">{{ $adhesion->created_at->locale('fr')->isoFormat('D MMM Y, H[h]mm') }}</td>
                <td class="px-4 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.adhesions.show', $adhesion) }}" class="w-8 h-8 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                        <a href="{{ route('admin.adhesions.edit', $adhesion) }}" class="w-8 h-8 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center"><i class="fas fa-pen text-xs"></i></a>
                        <form method="POST" action="{{ route('admin.adhesions.destroy', $adhesion) }}" data-confirm="Supprimer cette demande ?">
                            @csrf @method('DELETE')
                            <button class="w-8 h-8 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="px-6 py-12 text-center text-gray-400">Aucune demande d'adhésion reçue pour cette saison.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($adhesions->hasPages())<div class="px-6 py-4 border-t border-gray-50">{{ $adhesions->links() }}</div>@endif
</div>

@endsection
