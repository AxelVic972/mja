<?php

namespace App\Models;

use App\Support\Token;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Adhesion extends Model
{
    protected $fillable = [
        'user_id', 'premiere_adhesion', 'civilite', 'nom', 'prenom', 'date_naissance',
        'profession', 'telephone', 'email', 'reseaux_sociaux', 'adresse_postale',
        'taille_tshirt', 'permis', 'problemes_sante', 'urgence_contact', 'message', 'photo',
        'moyen_paiement', 'droit_image', 'rgpd_consentement', 'statut', 'lu',
        'commentaire',
        'source_id', 'period_id', 'account_token', 'account_token_expires_at',
        'renouvellement_token', 'renouvellement_token_expires_at', 'renouvelle_adhesion_id', 'promo_code_id',
    ];

    protected $casts = [
        'droit_image'                     => 'boolean',
        'rgpd_consentement'               => 'boolean',
        'lu'                              => 'boolean',
        'reseaux_sociaux'                 => 'array',
        'account_token_expires_at'        => 'datetime',
        'renouvellement_token_expires_at' => 'datetime',
    ];

    /**
     * Réseaux sociaux proposés (facultatifs) dans le formulaire d'adhésion.
     * clé => [libellé, icône Font Awesome, préfixe affiché, exemple].
     */
    public const RESEAUX = [
        'instagram' => ['Instagram', 'fab fa-instagram', '@', 'ton_pseudo'],
        'facebook'  => ['Facebook',  'fab fa-facebook',  '',  'Prénom Nom ou lien du profil'],
        'tiktok'    => ['TikTok',    'fab fa-tiktok',    '@', 'ton_pseudo'],
        'snapchat'  => ['Snapchat',  'fab fa-snapchat',  '@', 'ton_pseudo'],
        'linkedin'  => ['LinkedIn',  'fab fa-linkedin',  '',  'lien de ton profil'],
        'x'         => ['X',         'fab fa-x-twitter', '@', 'ton_pseudo'],
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AdhesionPeriod::class, 'period_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /** Adhésion de la saison précédente dont celle-ci est le renouvellement. */
    public function precedente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renouvelle_adhesion_id');
    }

    public function relances(): HasMany
    {
        return $this->hasMany(AdhesionRelance::class)->orderByDesc('envoyee_le');
    }

    // ─── Compte adhérent ──────────────────────────────────────────────────────

    /**
     * Génère (si nécessaire) un jeton de création de compte et renvoie
     * l'URL correspondante. Renvoie null si un compte existe déjà.
     */
    public function ensureAccountToken(): ?string
    {
        if ($this->user_id !== null) {
            return null;
        }

        $expired = $this->account_token_expires_at && $this->account_token_expires_at->isPast();

        if (! $this->account_token || $expired) {
            $this->account_token = Token::uniqueFor(self::class, 'account_token');
            $this->account_token_expires_at = now()->addDays(30);
            $this->save();
        }

        return $this->accountCreationUrl();
    }

    public function accountCreationUrl(): ?string
    {
        return $this->account_token ? route('member.account.create', $this->account_token) : null;
    }

    // ─── Renouvellement ───────────────────────────────────────────────────────

    /**
     * Jeton de renouvellement : ouvre le formulaire pré-rempli sans connexion.
     * Utilisé dans les emails de relance, y compris pour les adhérents qui
     * n'ont jamais créé de compte.
     */
    public function ensureRenouvellementToken(): string
    {
        $expire = $this->renouvellement_token_expires_at
            && $this->renouvellement_token_expires_at->isPast();

        if (! $this->renouvellement_token || $expire) {
            $this->renouvellement_token = Token::uniqueFor(self::class, 'renouvellement_token');
            $this->renouvellement_token_expires_at = now()->addDays(90);
            $this->save();
        }

        return route('adhesion.renouveler', $this->renouvellement_token);
    }

    /** Champs repris tels quels d'une saison à l'autre. */
    public function donneesReprises(): array
    {
        return [
            'civilite'        => $this->civilite,
            'nom'             => $this->nom,
            'prenom'          => $this->prenom,
            'date_naissance'  => $this->date_naissance,
            'profession'      => $this->profession,
            'telephone'       => $this->telephone,
            'email'           => $this->email,
            'adresse_postale' => $this->adresse_postale,
            'reseaux_sociaux' => $this->reseaux_sociaux,
            'taille_tshirt'   => $this->taille_tshirt,
            'permis'          => $this->permis,
            'problemes_sante' => $this->problemes_sante,
            'urgence_contact' => $this->urgence_contact,
            'photo'           => $this->photo,
        ];
    }

    // ─── Libellés ─────────────────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return $this->civilite . ' ' . $this->prenom . ' ' . $this->nom;
    }

    public function getLabelPremiereAdhesionAttribute(): string
    {
        return match ($this->premiere_adhesion) {
            'premiere'    => 'Première adhésion',
            'readhesion'  => 'Réadhésion',
            'information' => 'Prise d\'informations',
            default       => $this->premiere_adhesion,
        };
    }

    public function getLabelStatutAttribute(): string
    {
        return match ($this->statut) {
            'nouvelle'             => 'Nouvelle',
            'prise_infos'          => "Prise d'infos",
            'en_attente_paiement'  => 'En attente de paiement',
            'payee'                => 'Payée (adhérent)',
            'refusee'              => 'Refusée',
            'desistement'          => 'Désistement',
            // Anciens statuts (compatibilité)
            'traitee'              => 'Traitée',
            'acceptee'             => 'Acceptée',
            default                => $this->statut,
        };
    }

    public function getLabelMoyenPaiementAttribute(): string
    {
        return match ($this->moyen_paiement) {
            'cheque'   => 'Chèque',
            'espece'   => 'Espèces',
            'virement' => 'Virement bancaire',
            'en_ligne' => 'Paiement en ligne (CB)',
            'code_promo' => 'Code promotionnel',
            default    => '—',
        };
    }

    /** L'adhérent est confirmé une fois la cotisation payée. */
    public function isAdherent(): bool
    {
        return $this->statut === 'payee';
    }

    /** Cotisation due mais pas encore encaissée (cible des relances de paiement). */
    public function attendPaiement(): bool
    {
        return in_array($this->statut, ['nouvelle', 'en_attente_paiement'], true)
            && in_array($this->moyen_paiement, ['cheque', 'espece', 'virement'], true);
    }

    /** Statuts sélectionnables dans le back-office. */
    public const STATUTS = [
        'nouvelle'            => 'Nouvelle',
        'prise_infos'         => "Prise d'infos",
        'en_attente_paiement' => 'En attente de paiement',
        'payee'               => 'Payée (adhérent)',
        'refusee'             => 'Refusée',
        'desistement'         => 'Désistement',
    ];
}
