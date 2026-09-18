<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdhesionStatusUpdate;
use App\Models\Adhesion;
use App\Models\AdhesionPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdhesionController extends Controller
{
    public function index(Request $request)
    {
        $periods = AdhesionPeriod::orderByDesc('date_debut')->get();
        $periodeSelectionnee = null;
        $query = $this->adhesionsPourPeriode($request, $periodeSelectionnee);
        $adhesions = $query->paginate(20)->withQueryString();

        $statistiques = clone $query;
        $stats = [
            'total'                => (clone $statistiques)->count(),
            'en_attente_paiement' => (clone $statistiques)->where('statut', 'en_attente_paiement')->count(),
            'adherents'            => (clone $statistiques)->where('statut', 'payee')->count(),
            'demandes_adhesion'   => (clone $statistiques)->where('premiere_adhesion', 'premiere')->count(),
            'adhesions_payees'    => (clone $statistiques)->where('premiere_adhesion', 'premiere')->where('statut', 'payee')->count(),
            'readhesions'         => (clone $statistiques)->where('premiere_adhesion', 'readhesion')->count(),
            'readhesions_payees'  => (clone $statistiques)->where('premiere_adhesion', 'readhesion')->where('statut', 'payee')->count(),
            'prises_infos'        => (clone $statistiques)->where('statut', 'prise_infos')->count(),
        ];

        // Adhésions rattachées à aucune saison : elles échappent aux filtres,
        // aux exports par période et aux relances de renouvellement.
        $sansPeriode = Adhesion::whereNull('period_id')->count();

        return view('admin.adhesions.index', compact('adhesions', 'stats', 'periods', 'sansPeriode', 'periodeSelectionnee'));
    }

    public function export(Request $request): StreamedResponse
    {
        $periodeSelectionnee = null;
        $adhesions = $this->adhesionsPourPeriode($request, $periodeSelectionnee)->get();

        return response()->streamDownload(function () use ($adhesions) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 (Excel)
            fputcsv($out, [
                'Reçue le', 'Statut', 'Type', 'Civilité', 'Nom', 'Prénom', 'Date naissance',
                'Profession', 'Téléphone', 'Email', 'Adresse postale', 'T-shirt', 'Permis',
                'Problèmes santé', 'Contact urgence', 'Moyen paiement', 'Commentaire', 'Période',
            ], ';');
            foreach ($adhesions as $a) {
                fputcsv($out, [
                    $a->created_at?->format('d/m/Y H:i'),
                    $a->label_statut,
                    $a->label_premiere_adhesion,
                    $a->civilite,
                    $a->nom,
                    $a->prenom,
                    $a->date_naissance,
                    $a->profession,
                    $a->telephone,
                    $a->email,
                    str_replace(["\r", "\n"], ' ', (string) $a->adresse_postale),
                    $a->taille_tshirt,
                    $a->permis,
                    str_replace(["\r", "\n"], ' ', (string) $a->problemes_sante),
                    $a->urgence_contact,
                    $a->label_moyen_paiement,
                    str_replace(["\r", "\n"], ' ', (string) $a->commentaire),
                    $a->period?->label,
                ], ';');
            }
            fclose($out);
        }, 'adhesions-mja-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Adhesion $adhesion)
    {
        $adhesion->update(['lu' => true]);
        $periods = AdhesionPeriod::orderByDesc('date_debut')->get();

        return view('admin.adhesions.show', compact('adhesion', 'periods'));
    }

    public function create()
    {
        $periods = AdhesionPeriod::orderByDesc('date_debut')->get();
        $adhesion = new Adhesion([
            'premiere_adhesion' => 'premiere',
            'statut' => 'en_attente_paiement',
            'period_id' => AdhesionPeriod::pourAdhesion()?->id,
        ]);

        return view('admin.adhesions.create', compact('adhesion', 'periods'));
    }

    public function store(Request $request)
    {
        $donnees = $this->donneesAdhesionAdmin($request);
        $donnees['lu'] = true;
        $donnees['rgpd_consentement'] = true;

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('adhesions/photos', 'public');
        }

        $adhesion = Adhesion::create($donnees);
        if ($adhesion->statut === 'payee') {
            $adhesion->ensureAccountToken();
        }

        return redirect()->route('admin.adhesions.show', $adhesion)
            ->with('success', 'Adhésion ajoutée manuellement.');
    }

    public function edit(Adhesion $adhesion)
    {
        $periods = AdhesionPeriod::orderByDesc('date_debut')->get();

        return view('admin.adhesions.edit', compact('adhesion', 'periods'));
    }

    public function update(Request $request, Adhesion $adhesion)
    {
        $donnees = $this->donneesAdhesionAdmin($request);

        if ($request->hasFile('photo')) {
            $nouvellePhoto = $request->file('photo')->store('adhesions/photos', 'public');
            if ($adhesion->photo) {
                Storage::disk('public')->delete($adhesion->photo);
            }
            $donnees['photo'] = $nouvellePhoto;
        } elseif ($request->boolean('remove_photo') && $adhesion->photo) {
            Storage::disk('public')->delete($adhesion->photo);
            $donnees['photo'] = null;
        }

        $adhesion->update($donnees);
        if ($adhesion->statut === 'payee') {
            $adhesion->ensureAccountToken();
        }

        return redirect()->route('admin.adhesions.show', $adhesion)
            ->with('success', 'Adhésion mise à jour.');
    }

    public function updateStatut(Request $request, Adhesion $adhesion)
    {
        $validated = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Adhesion::STATUTS))],
        ]);

        $ancien = $adhesion->statut;
        $adhesion->update(['statut' => $validated['statut']]);

        // Devient adhérent : préparer le lien de création de compte.
        if ($validated['statut'] === 'payee') {
            $adhesion->ensureAccountToken();
        }

        // Email personnalisé uniquement si le statut change vers un état « notifiable ».
        $notifiables = ['payee', 'en_attente_paiement', 'refusee'];
        if ($validated['statut'] !== $ancien && in_array($validated['statut'], $notifiables, true)) {
            try {
                Mail::to($adhesion->email)->send(new AdhesionStatusUpdate($adhesion));
            } catch (\Throwable $e) {
                Log::error('Mail statut adhésion échoué : ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Statut mis à jour.');
    }

    /**
     * Attestation d'adhésion et carte de membre, vues du back-office.
     *
     * C'est exactement l'écran que voit l'adhérent dans son espace, avec le
     * même téléchargement en PDF : l'équipe peut ainsi rééditer le document
     * pour quelqu'un qui n'a pas de compte, ou qui n'y arrive pas seul.
     */
    public function carte(Adhesion $adhesion)
    {
        // Une attestation certifie une adhésion à jour : l'éditer pour une
        // demande non réglée reviendrait à attester quelque chose de faux.
        abort_unless(
            $adhesion->isAdherent(),
            403,
            "L'attestation n'est éditable que pour une adhésion à jour de cotisation.",
        );

        $adhesion->loadMissing('period');

        return view('member.card', ['adhesion' => $adhesion, 'member' => $adhesion->user]);
    }

    /** Rattache une adhésion à une saison, ou l'en détache. */
    public function updatePeriode(Request $request, Adhesion $adhesion)
    {
        $validated = $request->validate(
            ['period_id' => 'nullable|integer|exists:adhesion_periods,id'],
            ['period_id.exists' => "Cette saison n'existe pas."]
        );

        $adhesion->update(['period_id' => $validated['period_id'] ?? null]);
        // La relation chargée par le route-model binding est périmée après
        // l'update : sans ça, un détachement afficherait l'ancienne saison.
        $adhesion->unsetRelation('period');

        return back()->with('success', $adhesion->period
            ? "Adhésion rattachée à la « {$adhesion->period->label} »."
            : 'Adhésion détachée de toute saison.');
    }

    /**
     * Rattache d'un coup toutes les adhésions sans saison.
     *
     * Utile après une reprise de données : sans période, une adhésion
     * n'apparaît dans aucun filtre et ne déclenche jamais de relance de
     * renouvellement. On ne touche qu'aux adhésions orphelines — celles déjà
     * rattachées gardent leur saison.
     */
    public function rattacherPeriode(Request $request)
    {
        $validated = $request->validate(
            ['period_id' => 'required|integer|exists:adhesion_periods,id'],
            [
                'period_id.required' => 'Choisissez la saison de rattachement.',
                'period_id.exists'   => "Cette saison n'existe pas.",
            ]
        );

        $periode = AdhesionPeriod::findOrFail($validated['period_id']);
        $nombre = Adhesion::whereNull('period_id')->update(['period_id' => $periode->id]);

        return back()->with('success', $nombre === 0
            ? 'Aucune adhésion orpheline à rattacher.'
            : "{$nombre} adhésion(s) rattachée(s) à la « {$periode->label} ».");
    }

    public function destroy(Adhesion $adhesion)
    {
        if ($adhesion->photo) {
            Storage::disk('public')->delete($adhesion->photo);
        }
        $adhesion->delete();
        return redirect()->route('admin.adhesions.index')->with('success', 'Demande supprimée.');
    }

    /** Applique les filtres de la liste dans la liste et dans l'export. */
    private function adhesionsPourPeriode(Request $request, ?AdhesionPeriod &$periodeSelectionnee)
    {
        $query = Adhesion::with('period')->orderByDesc('created_at');
        $filtre = $request->input('period');
        $filtre = is_string($filtre) ? $filtre : null;

        if ($filtre === 'aucune') {
            $query->whereNull('period_id');
        } elseif ($filtre !== null && $filtre !== '' && $filtre !== 'toutes') {
            $query->where('period_id', (int) $filtre);
        } elseif ($filtre === null || $filtre === '') {
            // La liste ne mélange pas les campagnes : elle ouvre d'abord la
            // saison à laquelle les nouvelles adhésions sont aujourd'hui rattachées.
            $periodeSelectionnee = AdhesionPeriod::pourAdhesion();

            if ($periodeSelectionnee) {
                $query->where('period_id', $periodeSelectionnee->id);
            }
        }

        $this->appliquerFiltresColonnes($query, $request);

        return $query;
    }

    /** Ajoute les filtres visibles sous les en-têtes du tableau. */
    private function appliquerFiltresColonnes($query, Request $request): void
    {
        $candidat = $request->input('candidat', '');
        $candidat = is_string($candidat) ? trim($candidat) : '';
        if ($candidat !== '') {
            $query->where(function ($sousRequete) use ($candidat) {
                $recherche = '%' . $candidat . '%';
                $sousRequete->where('nom', 'like', $recherche)
                    ->orWhere('prenom', 'like', $recherche)
                    ->orWhere('telephone', 'like', $recherche)
                    ->orWhere('email', 'like', $recherche);
            });
        }

        $type = $request->input('type');
        if (is_string($type) && array_key_exists($type, [
            'premiere' => true,
            'readhesion' => true,
            'information' => true,
        ])) {
            $query->where('premiere_adhesion', $type);
        }

        $statut = $request->input('statut');
        if (is_string($statut) && array_key_exists($statut, Adhesion::STATUTS)) {
            $query->where('statut', $statut);
        }

        $paiement = $request->input('paiement');
        if (is_string($paiement) && array_key_exists($paiement, [
            'cheque' => true,
            'espece' => true,
            'virement' => true,
            'en_ligne' => true,
            'code_promo' => true,
        ])) {
            $query->where('moyen_paiement', $paiement);
        }

        $commentaire = $request->input('commentaire', '');
        $commentaire = is_string($commentaire) ? trim($commentaire) : '';
        if ($commentaire !== '') {
            $query->where('commentaire', 'like', '%' . $commentaire . '%');
        }

        $date = $request->input('date', '');
        $date = is_string($date) ? $date : '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $query->whereDate('created_at', $date);
        }
    }

    /** Validation des données saisies depuis le back-office. */
    private function donneesAdhesionAdmin(Request $request): array
    {
        $donnees = $request->validate([
            'premiere_adhesion' => ['required', Rule::in(['premiere', 'readhesion', 'information'])],
            'civilite'          => ['required', Rule::in(['Madame', 'Monsieur'])],
            'nom'               => 'required|string|max:100',
            'prenom'            => 'required|string|max:100',
            'date_naissance'    => 'nullable|string|max:20',
            'telephone'         => 'required|string|max:30',
            'email'             => 'required|email|max:150',
            'adresse_postale'   => 'nullable|string|max:500',
            'moyen_paiement'    => ['nullable', Rule::in(['cheque', 'espece', 'virement', 'en_ligne', 'code_promo'])],
            'statut'            => ['required', Rule::in(array_keys(Adhesion::STATUTS))],
            'period_id'         => 'nullable|integer|exists:adhesion_periods,id',
            'commentaire'       => 'nullable|string|max:2000',
            'photo'             => 'nullable|image|max:5120',
            'droit_image'       => 'nullable|boolean',
            'remove_photo'      => 'nullable|boolean',
        ]);

        unset($donnees['photo'], $donnees['remove_photo']);
        $donnees['droit_image'] = $request->boolean('droit_image');

        return $donnees;
    }
}
