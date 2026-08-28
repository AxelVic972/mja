<?php

namespace Tests\Feature;

use App\Models\Adhesion;
use App\Models\AdhesionPeriod;
use App\Models\PromoCode;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Une personne déjà en base qui adhère sans être connectée doit retrouver son
 * compte, pas en obtenir un second.
 */
class AdhesionRattachementTest extends TestCase
{
    use RefreshDatabase;

    private function formulaire(array $remplace = []): array
    {
        return array_merge([
            'premiere_adhesion' => 'premiere',
            'civilite'          => 'Monsieur',
            'nom'               => 'AGESILAS',
            'prenom'            => 'Lionely',
            'date_naissance'    => '13/11/1996',
            'profession'        => 'Étudiant',
            'indicatif'         => '+596',
            'telephone'         => '0696416080',
            'email'             => 'lionely@exemple.com',
            'adresse_postale'   => 'Quartier Wallon, 97229 Les Trois-Îlets',
            'taille_tshirt'     => 'XL',
            'permis'            => 'Oui',
            'urgence_contact'   => 'Eric 0696402497',
            'moyen_paiement'    => 'cheque',
            'droit_image'       => '1',
            'rgpd_consentement' => '1',
        ], $remplace);
    }

    public function test_adhesion_rattachee_au_compte_existant_par_email(): void
    {
        Mail::fake();

        $compte = User::create([
            'name' => 'Lionely AGESILAS', 'email' => 'lionely@exemple.com',
            'password' => bcrypt('secret-actuel'), 'role' => User::ROLE_MEMBER,
        ]);

        $this->post('/adhesion', $this->formulaire())->assertSessionHasNoErrors();

        $adhesion = Adhesion::firstOrFail();
        $this->assertSame($compte->id, $adhesion->user_id, "L'adhésion doit pointer sur le compte existant.");
        $this->assertSame(1, User::where('email', 'lionely@exemple.com')->count(), 'Aucun second compte ne doit être créé.');
    }

    public function test_casse_de_l_email_ignoree(): void
    {
        Mail::fake();

        $compte = User::create([
            'name' => 'Lionely AGESILAS', 'email' => 'lionely@exemple.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_MEMBER,
        ]);

        $this->post('/adhesion', $this->formulaire(['email' => '  Lionely@Exemple.COM ']));

        $this->assertSame($compte->id, Adhesion::firstOrFail()->user_id);
    }

    public function test_compte_supprime_restaure(): void
    {
        Mail::fake();

        $compte = User::create([
            'name' => 'Lionely AGESILAS', 'email' => 'lionely@exemple.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_MEMBER,
        ]);
        $compte->delete();

        $this->post('/adhesion', $this->formulaire());

        $this->assertSame($compte->id, Adhesion::firstOrFail()->user_id);
        $this->assertNull($compte->fresh()->deleted_at, 'Le compte doit être restauré.');
    }

    public function test_personne_inconnue_reste_sans_compte(): void
    {
        Mail::fake();

        $this->post('/adhesion', $this->formulaire(['email' => 'inconnue@exemple.com']));

        $this->assertNull(Adhesion::firstOrFail()->user_id);
        $this->assertSame(0, User::count());
    }

    public function test_lien_d_acces_envoye_quand_la_cotisation_est_encaissee(): void
    {
        Mail::fake();
        Notification::fake();

        $compte = User::create([
            'name' => 'Lionely AGESILAS', 'email' => 'lionely@exemple.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_MEMBER,
        ]);

        // Statut « payée » atteint par la voie administrative.
        $this->post('/adhesion', $this->formulaire());
        $adhesion = Adhesion::firstOrFail();
        $adhesion->update(['statut' => 'payee']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@exemple.com',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $this->actingAs($admin)->patch("/admin/adhesions/{$adhesion->id}/statut", ['statut' => 'payee']);

        // Le compte existant ne reçoit jamais de jeton de création de compte.
        $this->assertNull($adhesion->fresh()->account_token);
    }

    public function test_adresse_postale_obligatoire_pour_une_adhesion(): void
    {
        Mail::fake();

        $this->post('/adhesion', $this->formulaire(['adresse_postale' => '']))
            ->assertSessionHasErrors('adresse_postale');
    }

    public function test_adresse_postale_non_exigee_pour_une_prise_d_informations(): void
    {
        Mail::fake();

        $this->post('/adhesion', [
            'premiere_adhesion' => 'information',
            'civilite'          => 'Madame',
            'nom'               => 'DUPONT',
            'prenom'            => 'Marie',
            'indicatif'         => '+596',
            'telephone'         => '0696000000',
            'email'             => 'marie@exemple.com',
            'message'           => 'Comment adhérer ?',
            'rgpd_consentement' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('prise_infos', Adhesion::firstOrFail()->statut);
    }

    public function test_un_code_promo_a_100_pourcent_valide_l_adhesion_sans_paiement(): void
    {
        Mail::fake();
        $code = PromoCode::create([
            'code' => 'MJA-INCIDENT',
            'discount_percent' => 100,
            'max_uses' => 1,
            'active' => true,
        ]);

        $this->post('/adhesion', $this->formulaire(['promo_code' => 'mja-incident']))
            ->assertSessionHasNoErrors();

        $adhesion = Adhesion::firstOrFail();
        $this->assertSame('payee', $adhesion->statut);
        $this->assertSame('code_promo', $adhesion->moyen_paiement);
        $this->assertSame($code->id, $adhesion->promo_code_id);
        $this->assertSame(1, $code->fresh()->uses_count);
    }

    public function test_un_code_promo_deja_utilise_est_refuse(): void
    {
        Mail::fake();
        PromoCode::create([
            'code' => 'MJA-UNIQUE',
            'discount_percent' => 100,
            'max_uses' => 1,
            'uses_count' => 1,
            'active' => true,
        ]);

        $this->post('/adhesion', $this->formulaire(['promo_code' => 'MJA-UNIQUE']))
            ->assertSessionHasErrors('promo_code');

        $this->assertSame(0, Adhesion::count());
    }
}
