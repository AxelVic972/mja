<?php

namespace App\Http\Requests;

use App\Models\Adhesion;
use App\Rules\Turnstile;
use App\Support\Telephone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Règles communes au formulaire d'adhésion et à l'écran de renouvellement :
 * les deux enregistrent la même chose, seule la façon d'y arriver diffère.
 */
class AdhesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Une prise d'informations ne collecte que le strict nécessaire pour
     * répondre : identité, coordonnées, question. Tout le reste — date de
     * naissance, profession, santé, contact d'urgence, droit à l'image,
     * cotisation — ne concerne que les adhésions.
     */
    private function priseDInformations(): bool
    {
        return $this->input('premiere_adhesion') === 'information';
    }

    /**
     * Deux jeux de règles plutôt qu'un `required_unless` sur chaque ligne.
     *
     * C'est aussi la seule forme sûre : `accepted` est une règle implicite,
     * qui échoue sur un champ absent même accompagnée de `nullable`. Le droit
     * à l'image bloquait ainsi toute demande d'information.
     */
    public function rules(): array
    {
        $regles = [
            'premiere_adhesion' => 'required|in:premiere,readhesion,information',
            'civilite'          => 'required|in:Madame,Monsieur',
            'nom'               => 'required|string|max:100',
            'prenom'            => 'required|string|max:100',
            'indicatif'         => 'nullable|string|max:6',
            'telephone'         => 'required|string|max:30',
            'email'             => 'required|email|max:150',
            'message'           => 'required_if:premiere_adhesion,information|nullable|string|max:2000',
            'reseaux_sociaux'   => 'nullable|array',
            'reseaux_sociaux.*' => 'nullable|string|max:150',
            'problemes_sante'   => 'nullable|string',
            // La photo est facultative : elle peut être transmise plus tard
            // depuis l'espace adhérent.
            'photo'             => 'nullable|image|max:5120',
            'payment_intent_id' => 'nullable|string|max:255',
            'promo_code'        => 'nullable|string|max:40',
            'rgpd_consentement' => 'required|accepted',
        ];

        if (config('services.turnstile.enabled')) {
            $regles['cf-turnstile-response'] = ['bail', 'required', 'string', new Turnstile($this->ip())];
        }

        if ($this->priseDInformations()) {
            return $regles;
        }

        return $regles + [
            'date_naissance'  => ['required', 'string', 'max:20', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'profession'      => 'required|string|max:150',
            'adresse_postale' => 'required|string|max:500',
            'taille_tshirt'   => 'required|in:S,M,L,XL,2XL,3XL',
            'permis'          => 'required|in:Oui,Non',
            'urgence_contact' => 'required|string|max:300',
            'moyen_paiement'  => 'required|in:cheque,espece,virement,en_ligne',
            'droit_image'     => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'premiere_adhesion.required'     => 'Ce champ est obligatoire.',
            'premiere_adhesion.in'           => 'Valeur invalide.',
            'civilite.required'              => 'La civilité est obligatoire.',
            'nom.required'                   => 'Le nom est obligatoire.',
            'prenom.required'                => 'Le prénom est obligatoire.',
            'date_naissance.required'        => 'La date de naissance est obligatoire.',
            'date_naissance.regex'           => 'Le format doit être JJ/MM/AAAA.',
            'profession.required'            => 'La profession est obligatoire.',
            'telephone.required'             => 'Le numéro de téléphone est obligatoire.',
            'email.required'                 => "L'adresse email est obligatoire.",
            'email.email'                    => "L'adresse email n'est pas valide.",
            'message.required_if'            => 'Dites-nous ce que vous souhaitez savoir.',
            'adresse_postale.required_unless' => "L'adresse postale est obligatoire.",
            'adresse_postale.required'       => "L'adresse postale est obligatoire.",
            'date_naissance.required_unless' => 'La date de naissance est obligatoire.',
            'profession.required_unless'     => 'La profession est obligatoire.',
            'taille_tshirt.required_unless'  => 'La taille de T-shirt est obligatoire.',
            'permis.required_unless'         => 'Ce champ est obligatoire.',
            'urgence_contact.required_unless' => "La personne à contacter en cas d'urgence est obligatoire.",
            'droit_image.required_unless'    => "L'autorisation du droit à l'image est obligatoire.",
            'taille_tshirt.required'         => 'La taille de T-shirt est obligatoire.',
            'permis.required'                => 'Ce champ est obligatoire.',
            'urgence_contact.required'       => "La personne à contacter en cas d'urgence est obligatoire.",
            'photo.image'                    => 'Le fichier doit être une image (JPG, PNG…).',
            'photo.max'                      => 'La photo ne doit pas dépasser 5 Mo.',
            'moyen_paiement.required_unless' => 'Choisissez un moyen de paiement.',
            'moyen_paiement.in'              => 'Moyen de paiement invalide.',
            'droit_image.required'           => "L'autorisation du droit à l'image est obligatoire.",
            'droit_image.accepted'           => "Vous devez accepter le droit à l'image pour finaliser votre adhésion.",
            'rgpd_consentement.required'     => 'Le consentement au traitement de vos données est obligatoire.',
            'rgpd_consentement.accepted'     => 'Vous devez consentir au traitement de vos données pour finaliser votre adhésion.',
        ];
    }

    /**
     * Un robot qui publie directement sur l'URL ne possède pas l'horodatage
     * enregistré quand la page a été affichée. Trois secondes restent
     * imperceptibles pour une personne, mais arrêtent les envois immédiats.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $debut = $this->session()->get('adhesion_form_started_at');
            $delaiMinimum = (int) config('mja.adhesion_min_fill_seconds', 3);

            if (! is_numeric($debut) || now()->getTimestamp() - (int) $debut < $delaiMinimum) {
                $validator->errors()->add('formulaire', 'Veuillez prendre un instant pour vérifier le formulaire avant de l’envoyer.');
            }
        });
    }

    /** Le jeton temporel ne doit pas pouvoir servir à rejouer un envoi valide. */
    protected function passedValidation(): void
    {
        $this->session()->forget('adhesion_form_started_at');
    }

    /**
     * Données prêtes à être enregistrées : téléphone recomposé avec son
     * indicatif, réseaux sociaux nettoyés, consentements normalisés.
     */
    public function donneesAdhesion(): array
    {
        $donnees = $this->safe()->except(['indicatif', 'payment_intent_id', 'promo_code', 'photo']);

        $donnees['telephone']         = Telephone::complet($this->input('indicatif'), $this->input('telephone'));
        $donnees['reseaux_sociaux']   = $this->reseauxNettoyes();
        $donnees['rgpd_consentement'] = true;
        // Le droit à l'image n'est pas demandé lors d'une prise d'informations :
        // il ne doit donc pas être enregistré comme accordé.
        $donnees['droit_image']       = ! $this->priseDInformations();

        return $donnees;
    }

    /**
     * Ne conserve que les réseaux réellement renseignés, et seulement ceux que
     * le formulaire propose — un champ vide ne doit pas encombrer la fiche.
     *
     * @return array<string, string>|null
     */
    private function reseauxNettoyes(): ?array
    {
        $saisis = (array) $this->input('reseaux_sociaux', []);
        $connus = array_keys(Adhesion::RESEAUX);
        $propre = [];

        foreach ($saisis as $cle => $valeur) {
            $valeur = trim((string) $valeur);

            if ($valeur !== '' && in_array($cle, $connus, true)) {
                $propre[$cle] = ltrim($valeur, '@');
            }
        }

        return $propre ?: null;
    }
}
