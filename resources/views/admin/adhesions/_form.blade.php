@php
    $edition = $adhesion->exists;
    $valeur = fn (string $champ, mixed $defaut = '') => old($champ, $adhesion->{$champ} ?? $defaut);
@endphp

<form method="POST" action="{{ $edition ? route('admin.adhesions.update', $adhesion) : route('admin.adhesions.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if($edition) @method('PUT') @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-display font-bold text-lg text-mja-gray mb-5">Identité</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="block text-sm font-semibold text-gray-600">Type de demande
                <select name="premiere_adhesion" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                    <option value="premiere" @selected($valeur('premiere_adhesion', 'premiere') === 'premiere')>Première adhésion</option>
                    <option value="readhesion" @selected($valeur('premiere_adhesion') === 'readhesion')>Réadhésion</option>
                    <option value="information" @selected($valeur('premiere_adhesion') === 'information')>Prise d'informations</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-gray-600">Civilité
                <select name="civilite" required class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                    <option value="Madame" @selected($valeur('civilite') === 'Madame')>Madame</option>
                    <option value="Monsieur" @selected($valeur('civilite', 'Monsieur') === 'Monsieur')>Monsieur</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-gray-600">Prénom
                <input name="prenom" required value="{{ $valeur('prenom') }}" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
            </label>
            <label class="block text-sm font-semibold text-gray-600">Nom
                <input name="nom" required value="{{ $valeur('nom') }}" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
            </label>
            <label class="block text-sm font-semibold text-gray-600">Date de naissance
                <input name="date_naissance" value="{{ $valeur('date_naissance') }}" placeholder="JJ/MM/AAAA" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
            </label>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-display font-bold text-lg text-mja-gray mb-5">Coordonnées</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="block text-sm font-semibold text-gray-600">Téléphone
                <input name="telephone" required value="{{ $valeur('telephone') }}" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
            </label>
            <label class="block text-sm font-semibold text-gray-600">Adresse email
                <input type="email" name="email" required value="{{ $valeur('email') }}" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
            </label>
            <label class="block text-sm font-semibold text-gray-600 sm:col-span-2">Adresse postale
                <textarea name="adresse_postale" rows="3" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">{{ $valeur('adresse_postale') }}</textarea>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-display font-bold text-lg text-mja-gray mb-5">Suivi administratif</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="block text-sm font-semibold text-gray-600">Saison
                <select name="period_id" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                    <option value="">— Aucune saison —</option>
                    @foreach($periods as $periode)
                    <option value="{{ $periode->id }}" @selected((string) $valeur('period_id') === (string) $periode->id)>{{ $periode->label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-gray-600">Statut
                <select name="statut" required class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                    @foreach(\App\Models\Adhesion::STATUTS as $statut => $libelle)
                    <option value="{{ $statut }}" @selected($valeur('statut', 'en_attente_paiement') === $statut)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-gray-600">Moyen de paiement
                <select name="moyen_paiement" class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                    <option value="">Non renseigné</option>
                    @foreach(['cheque' => 'Chèque', 'espece' => 'Espèces', 'virement' => 'Virement bancaire', 'en_ligne' => 'Paiement en ligne (CB)', 'code_promo' => 'Code promotionnel'] as $moyen => $libelle)
                    <option value="{{ $moyen }}" @selected($valeur('moyen_paiement') === $moyen)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-semibold text-gray-600 sm:col-span-2">Commentaire interne
                <textarea name="commentaire" rows="4" maxlength="2000" placeholder="Information utile pour le suivi, non visible sur le site public." class="mt-1.5 w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">{{ $valeur('commentaire') }}</textarea>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-display font-bold text-lg text-mja-gray mb-5">Photo et droit à l'image</h2>
        <div class="flex flex-wrap items-start gap-5">
            @if($adhesion->photo)
            <a href="{{ Storage::url($adhesion->photo) }}" target="_blank" rel="noopener">
                <img src="{{ Storage::url($adhesion->photo) }}" alt="Photo actuelle" class="w-24 h-24 object-cover rounded-2xl border border-gray-200">
            </a>
            @endif
            <div class="flex-1 min-w-60 space-y-3">
                <label class="block text-sm font-semibold text-gray-600">{{ $adhesion->photo ? 'Remplacer la photo' : 'Ajouter une photo' }}
                    <input type="file" name="photo" accept="image/*" class="mt-1.5 block w-full text-sm text-gray-500">
                    <span class="mt-1 text-xs font-normal text-gray-400">JPG, PNG ou WebP, 5 Mo maximum.</span>
                </label>
                @if($adhesion->photo)
                <label class="inline-flex items-center gap-2 text-sm text-red-600">
                    <input type="checkbox" name="remove_photo" value="1"> Supprimer la photo actuelle
                </label>
                @endif
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="hidden" name="droit_image" value="0">
                    <input type="checkbox" name="droit_image" value="1" @checked(in_array($valeur('droit_image'), [true, 1, '1'], true))> Droit à l'image accordé
                </label>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <a href="{{ $edition ? route('admin.adhesions.show', $adhesion) : route('admin.adhesions.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50">Annuler</a>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-mja-blue hover:bg-mja-bluedark text-white font-display font-bold text-sm">
            <i class="fas fa-save mr-1"></i> {{ $edition ? 'Enregistrer les modifications' : "Ajouter l'adhésion" }}
        </button>
    </div>
</form>
