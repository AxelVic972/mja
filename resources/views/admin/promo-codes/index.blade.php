@extends('layouts.admin')
@section('title', 'Codes promotionnels')
@section('page-title', 'Codes promotionnels')
@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-4 mt-4 flex items-center gap-2 text-sm font-semibold"><i class="fas fa-check-circle text-green-500"></i>{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
        <h2 class="font-display font-bold text-gray-800 mb-1"><i class="fas fa-ticket text-mja-blue mr-1"></i> Nouveau code</h2>
        <p class="text-xs text-gray-400 mb-5">Pour un incident de paiement, créez un code à 100 %, utilisable une seule fois.</p>
        <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="space-y-4">
            @csrf
            <div><label class="block text-xs font-bold text-gray-600 mb-1">Code <span class="text-gray-400 font-normal">(généré si vide)</span></label><input name="code" value="{{ old('code') }}" placeholder="MJA-PAIEMENT-001" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm uppercase @error('code') border-red-400 @enderror"><p class="text-xs text-gray-400 mt-1">Lettres, chiffres, tirets et _ uniquement.</p>@error('code')<p class="text-mja-red text-xs mt-1">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-bold text-gray-600 mb-1">Remise</label><div class="relative"><input type="number" name="discount_percent" value="100" readonly class="w-full border border-gray-200 bg-gray-50 rounded-xl px-3 py-2.5 text-sm"><span class="absolute right-3 top-2.5 text-gray-400">%</span></div></div><div><label class="block text-xs font-bold text-gray-600 mb-1">Utilisations</label><input type="number" name="max_uses" min="1" value="{{ old('max_uses', 1) }}" required class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm"></div></div>
            <div><label class="block text-xs font-bold text-gray-600 mb-1">Expiration <span class="text-gray-400 font-normal">(facultatif)</span></label><input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm"></div>
            <div><label class="block text-xs font-bold text-gray-600 mb-1">Note interne</label><textarea name="note" rows="3" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm" placeholder="Ex. paiement débité mais inscription non finalisée">{{ old('note') }}</textarea></div>
            <button class="w-full btn-blue font-display font-bold py-3 rounded-xl"><i class="fas fa-plus mr-1"></i> Créer le code</button>
        </form>
    </div>
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100"><h2 class="font-display font-bold text-gray-800">Codes créés</h2></div>
        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="text-left px-5 py-3">Code</th><th class="text-left px-5 py-3">Remise</th><th class="text-left px-5 py-3">Usage</th><th class="text-left px-5 py-3">Validité</th><th class="text-right px-5 py-3">Action</th></tr></thead><tbody class="divide-y divide-gray-100">
        @forelse($codes as $code)
            @php $usable = $code->isUsable(); @endphp
            <tr><td class="px-5 py-4"><code class="font-bold text-mja-blue">{{ $code->code }}</code>@if($code->note)<p class="text-xs text-gray-400 mt-1">{{ $code->note }}</p>@endif</td><td class="px-5 py-4 font-semibold">{{ $code->discount_percent }} %</td><td class="px-5 py-4">{{ $code->uses_count }} / {{ $code->max_uses }}</td><td class="px-5 py-4 text-xs {{ $usable ? 'text-green-600' : 'text-gray-400' }}">{{ $usable ? 'Disponible' : 'Indisponible' }}@if($code->expires_at)<span class="block text-gray-400">jusqu’au {{ $code->expires_at->format('d/m/Y H:i') }}</span>@endif</td><td class="px-5 py-4 text-right"><form method="POST" action="{{ route('admin.promo-codes.update', $code) }}">@csrf @method('PATCH')<input type="hidden" name="active" value="{{ $code->active ? 0 : 1 }}"><button class="text-xs font-bold px-3 py-2 rounded-lg {{ $code->active ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">{{ $code->active ? 'Désactiver' : 'Activer' }}</button></form></td></tr>
        @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">Aucun code promotionnel.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
