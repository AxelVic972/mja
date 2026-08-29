<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdhesionRequest;
use App\Mail\AdhesionConfirmation;
use App\Mail\AdhesionNotification;
use App\Mail\AdhesionStatusUpdate;
use App\Models\Adhesion;
use App\Models\AdhesionPeriod;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Services\StripeService;
use App\Support\Cotisation;
use App\Support\Telephone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AdhesionController extends Controller
{
    public function create()
    {
        // Un adhérent connecté n'a rien à ressaisir : on l'envoie directement
        // sur son écran de renouvellement pré-rempli.
        $user = Auth::user();

        if ($user?->isMember() && $user->adhesion) {
            return redirect()->route('adhesion.renouveler.espace');
        }

        return view('adhesion', $this->donneesVue());
    }

    /**
     * Renouvellement depuis l'espace adhérent : le formulaire est pré-rempli
     * avec la dernière adhésion, il n'y a plus qu'à vérifier et payer.
     */
    public function renouvelerDepuisEspace()
    {
        $user = Auth::user();
        $precedente = $user?->adhesion;

        // Compte sans adhésion rattachée (administrateur, ou adhérent dont la
        // fiche n'a pas encore été liée) : il n'y a rien à pré-remplir. On
        // l'envoie sur le formulaire vierge plutôt que sur une page 404.
        if (! $precedente) {
            return redirect()->route('adhesion')
                ->with('error', "Aucune adhésion n'est rattachée à votre compte : remplissez le formulaire ci-dessous.");
        }

        return view('adhesion', $this->donneesVue($precedente));
    }

    /**
     * Renouvellement par lien magique reçu par email : même écran, sans
     * connexion — pour les adhérents qui n'ont jamais créé de compte.
     */
    public function renouvelerParLien(string $token)
    {
        $precedente = Adhesion::where('renouvellement_token', $token)->first();

        if (! $precedente
            || ! $precedente->renouvellement_token_expires_at
            || $precedente->renouvellement_token_expires_at->isPast()) {
            return redirect()->route('adhesion')
                ->with('error', "Ce lien de renouvellement a expiré. Remplissez le formulaire ci-dessous, ou connectez-vous à votre espace adhérent.");
        }

        return view('adhesion', $this->donneesVue($precedente));
    }

    /**
     * Adhésion déjà enregistrée pour la saison en cours, s'il y en a une.
     *
     * Sans ce contrôle, quelqu'un qui vient de renouveler retrouvait
     * l'écran de renouvellement et son message « vérifie tes informations
     * puis règle ta cotisation » — au risque de payer deux fois.
     */
    private function adhesionDeLaSaison(?Adhesion $precedente): ?Adhesion
    {
        $periode = AdhesionPeriod::pourAdhesion();

        if (! $periode || ! $precedente) {
            return null;
        }

        // Déjà la bonne saison : la personne arrive par un lien de l'an
        // dernier qui a servi, ou revient sur la page par habitude.
        if ($precedente->period_id === $periode->id) {
            return $precedente;
        }

        return Adhesion::where('period_id', $periode->id)
            ->where(function ($q) use ($precedente) {
                $q->where('renouvelle_adhesion_id', $precedente->id);

                if ($precedente->user_id) {
                    $q->orWhere('user_id', $precedente->user_id);
                }

                $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $precedente->email))]);
            })
            ->latest('id')
            ->first();
    }

    /** Variables communes aux trois façons d'ouvrir le formulaire. */
    private function donneesVue(?Adhesion $precedente = null): array
    {
        $prefill = [];

        if ($precedente) {
            $prefill = $precedente->donneesReprises();
            [$prefill['indicatif'], $prefill['telephone']] = Telephone::separer($precedente->telephone);
            $prefill['premiere_adhesion'] = 'readhesion';
        }

        return [
            'stripeEnabled'  => StripeService::enabled(),
            'stripePublicKey' => StripeService::publicKey(),
            'precedente'     => $precedente,
            'prefill'        => $prefill,
            'periode'        => AdhesionPeriod::pourAdhesion(),
            'dejaAJour'      => $this->adhesionDeLaSaison($precedente),
        ];
    }

    /**
     * Crée un PaymentIntent pour le paiement carte intégré au formulaire.
     * Appelé en AJAX quand le visiteur choisit « Carte bancaire ».
     */
    public function paymentIntent(Request $request)
    {
        if (! StripeService::enabled()) {
            return response()->json(['error' => 'Le paiement en ligne est indisponible.'], 422);
        }

        $promoCode = $this->promoCodeValide($request->input('promo_code'));
        if ($request->filled('promo_code') && ! $promoCode) {
            return response()->json(['error' => 'Ce code promotionnel est invalide, expiré ou déjà utilisé.'], 422);
        }

        $amount = $this->montantApresRemise($promoCode);
        if ($amount === 0) {
            return response()->json([
                'free' => true,
                'discount_percent' => $promoCode->discount_percent,
                'total' => '0,00 €',
            ]);
        }

        $intent = StripeService::createPaymentIntent($amount, ['type' => 'adhesion', 'promo_code' => $promoCode?->code]);

        if (! $intent) {
            return response()->json(['error' => "Le paiement est momentanément indisponible."], 502);
        }

        return response()->json([
            'client_secret' => $intent['client_secret'],
            'public_key'    => StripeService::publicKey(),
            'amount'        => $amount,
            'cotisation'    => Cotisation::formatee(),
            'frais'         => Cotisation::fraisFormates(),
            'total'         => number_format($amount / 100, 2, ',', ' ') . ' €',
            'discount_percent' => $promoCode?->discount_percent,
        ]);
    }

    public function store(AdhesionRequest $request)
    {
        $donnees = $request->donneesAdhesion();

        $precedente = $this->adhesionPrecedente($request);

        $donnees['source_id'] = $request->session()->get('mja_source_id');
        // pourAdhesion() plutôt que current() : entre deux saisons, aucune ne
        // contient la date du jour, et l'adhésion partirait sans rattachement.
        $donnees['period_id'] = AdhesionPeriod::pourAdhesion()?->id;
        // Ordre de priorité : la personne connectée, puis le compte de
        // l'adhésion renouvelée, puis un compte déjà en base à cette adresse.
        $donnees['user_id']   = Auth::id()
            ?: $precedente?->user_id
            ?: $this->compteExistant($donnees['email'])?->id;

        if ($precedente) {
            $donnees['renouvelle_adhesion_id'] = $precedente->id;
            // À défaut de nouvelle photo, on reprend celle de l'an dernier.
            $donnees['photo'] = $precedente->photo;
        }

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('adhesions/photos', 'public');
        }

        // Un code à 100 % évite à une personne déjà débitée lors d'un incident
        // de paiement de régler une seconde fois. La consommation est atomique.
        $promoCode = null;
        $codeSaisi = strtoupper(trim((string) $request->input('promo_code')));
        if ($codeSaisi !== '' && $donnees['premiere_adhesion'] !== 'information') {
            $promoCode = $this->promoCodeValide($codeSaisi);

            if (! $promoCode) {
                return back()->withInput()->withErrors(['promo_code' => 'Ce code promotionnel est invalide, expiré ou déjà utilisé.']);
            }

            $donnees['promo_code_id'] = $promoCode->id;
            if ($promoCode->discount_percent === 100) {
                $donnees['moyen_paiement'] = 'code_promo';
            } elseif (($donnees['moyen_paiement'] ?? null) !== 'en_ligne') {
                return back()->withInput()->withErrors(['moyen_paiement' => 'Pour une remise partielle, choisissez le paiement par carte bancaire.']);
            }
        }

        // Paiement carte : le règlement a lieu dans le formulaire, avant l'envoi.
        // On revérifie systématiquement le PaymentIntent auprès de Stripe — le
        // navigateur n'est jamais une source de vérité sur un paiement.
        $cartePayee = false;

        if (($donnees['moyen_paiement'] ?? null) === 'en_ligne') {
            $cartePayee = StripeService::paiementValide($request->input('payment_intent_id'), $this->montantApresRemise($promoCode));

            if (! $cartePayee) {
                return back()->withInput()
                    ->withErrors(['moyen_paiement' => "Le paiement par carte n'a pas été validé. Réglez la cotisation dans le formulaire avant d'envoyer votre demande."]);
            }
        }

        $donnees['statut'] = match (true) {
            $donnees['premiere_adhesion'] === 'information' => 'prise_infos',
            $cartePayee || $promoCode !== null               => 'payee',
            default                                         => 'nouvelle',
        };

        // La création et la consommation du code se font ensemble : si une
        // écriture échoue, le code reste disponible.
        $adhesion = DB::transaction(function () use ($donnees, $promoCode) {
            if ($promoCode) {
                $code = PromoCode::whereKey($promoCode->id)->lockForUpdate()->first();

                if (! $code || ! $code->isUsable()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'promo_code' => 'Ce code promotionnel vient d’être utilisé ou n’est plus disponible.',
                    ]);
                }

                $code->increment('uses_count');
            }

            return Adhesion::create($donnees);
        });

        // Le paiement carte a été créé avant l'adhésion : on l'y rattache pour
        // qu'un évènement Stripe ultérieur sache de quelle demande il parle.
        if ($cartePayee) {
            StripeService::attacherAdhesion($request->input('payment_intent_id'), $adhesion->id);
        }

        // Le renouvellement devient l'adhésion courante du compte.
        if ($adhesion->user_id) {
            \App\Models\User::whereKey($adhesion->user_id)->update(['adhesion_id' => $adhesion->id]);
        }


        // Le lien de renouvellement précédent ne doit plus rouvrir un formulaire.
        $precedente?->forceFill([
            'renouvellement_token'            => null,
            'renouvellement_token_expires_at' => null,
        ])->save();

        // Notification à l'association (toujours) — liste configurable en back-office.
        try {
            Mail::to(Setting::notificationEmails())->send(new AdhesionNotification($adhesion));
        } catch (\Throwable $e) {
            Log::error('Mail notification adhésion échoué : ' . $e->getMessage());
        }

        // Email de confirmation (carte déjà réglée, moyens hors ligne, prise d'informations).
        try {
            Mail::to($adhesion->email)->send(new AdhesionConfirmation($adhesion));
        } catch (\Throwable $e) {
            Log::error('Mail confirmation adhésion échoué : ' . $e->getMessage());
        }

        // Cotisation déjà encaissée : ouvrir l'accès sans attendre qu'un
        // administrateur repasse sur la fiche. En dernier, pour que le message
        // portant le lien soit celui qui arrive en dernier.
        $this->preparerAcces($adhesion);

        return back()
            ->with('success', true)
            ->with('paye', $cartePayee || $promoCode !== null)
            ->with('renouvellement', $precedente !== null);
    }

    private function promoCodeValide(?string $code): ?PromoCode
    {
        $code = strtoupper(trim((string) $code));
        $promo = $code === '' ? null : PromoCode::where('code', $code)->first();

        return $promo?->isUsable() ? $promo : null;
    }

    private function montantApresRemise(?PromoCode $promoCode): int
    {
        $pourcentage = $promoCode?->discount_percent ?? 0;

        return (int) round(StripeService::amountCents() * (100 - $pourcentage) / 100);
    }

    /**
     * Compte déjà en base pour cette adresse email.
     *
     * Une personne dont le compte a été créé par l'association — sans qu'on
     * lui ait forcément communiqué ses accès — peut très bien remplir le
     * formulaire public sans être connectée. Sans ce rapprochement, son
     * adhésion partirait orpheline et le site tenterait de créer un second
     * compte sur une adresse déjà prise.
     *
     * Un compte supprimé est restauré : la personne revient d'elle-même, et
     * l'unicité de l'email interdit de toute façon d'en créer un autre.
     */
    private function compteExistant(?string $email): ?\App\Models\User
    {
        if (! $email) {
            return null;
        }

        $compte = \App\Models\User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
            ->first();

        if ($compte && $compte->trashed()) {
            $compte->restore();
            Log::info("Compte adhérent restauré lors d'une nouvelle adhésion : {$compte->email}");
        }

        return $compte;
    }

    /**
     * Ouvre l'accès à l'espace adhérent une fois la cotisation encaissée.
     *
     * Deux cas, jamais un mot de passe en clair dans un email :
     *  — compte déjà existant : un lien de réinitialisation, qui sert aussi
     *    de premier accès à qui n'a jamais reçu ses identifiants ;
     *  — personne inconnue : le jeton de création de compte habituel.
     */
    private function preparerAcces(Adhesion $adhesion): void
    {
        if (! $adhesion->isAdherent()) {
            return;
        }

        if (! $adhesion->user_id) {
            $adhesion->ensureAccountToken();

            try {
                Mail::to($adhesion->email)->send(new AdhesionStatusUpdate($adhesion));
            } catch (\Throwable $e) {
                Log::error('Mail de bienvenue adhérent échoué : ' . $e->getMessage());
            }

            return;
        }

        try {
            Password::broker('members')->sendResetLink(
                ['email' => \Illuminate\Support\Str::lower(trim($adhesion->email))],
                fn ($user, $token) => $user->notify(new \App\Notifications\MemberResetPassword($token)),
            );
        } catch (\Throwable $e) {
            Log::error("Lien d'accès espace adhérent échoué : " . $e->getMessage());
        }
    }

    /**
     * Adhésion que ce formulaire renouvelle, si le contexte le dit — jeton
     * magique posté avec le formulaire, ou adhérent connecté.
     */
    private function adhesionPrecedente(Request $request): ?Adhesion
    {
        $token = $request->input('renouvellement_token');

        if ($token) {
            $precedente = Adhesion::where('renouvellement_token', $token)
                ->whereNotNull('renouvellement_token_expires_at')
                ->where('renouvellement_token_expires_at', '>', now())
                ->first();

            if ($precedente) {
                return $precedente;
            }
        }

        $user = Auth::user();

        return $user?->adhesion;
    }

    /** Retour Stripe : paiement réussi → statut « payée » + email de bienvenue. */
    public function paiementSucces(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId || ! StripeService::enabled()) {
            return redirect()->route('adhesion');
        }

        $session = StripeService::retrieveSession($sessionId);

        if ($session && ($session['payment_status'] ?? null) === 'paid') {
            $id = $session['metadata']['adhesion_id'] ?? ($session['client_reference_id'] ?? null);
            $adhesion = $id ? Adhesion::find($id) : null;

            if ($adhesion && ! $adhesion->isAdherent()) {
                $adhesion->update(['statut' => 'payee']);
                $this->preparerAcces($adhesion);
            }

            return redirect()->route('adhesion')->with('success', true)->with('paye', true);
        }

        return redirect()->route('adhesion')->with('error', "Le paiement n'a pas pu être confirmé. Contactez-nous si vous avez été débité.");
    }

    /** Retour Stripe : paiement annulé. */
    public function paiementAnnule(Request $request)
    {
        return redirect()->route('adhesion')
            ->with('error', "Paiement annulé. Votre demande reste enregistrée, vous pourrez régler plus tard.");
    }
}
