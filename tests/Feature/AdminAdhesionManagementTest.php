<?php

namespace Tests\Feature;

use App\Models\Adhesion;
use App\Models\AdhesionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdhesionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrateur(): User
    {
        return User::create([
            'name' => 'Administratrice MJA',
            'email' => 'admin@mja.test',
            'password' => 'secret',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    private function donneesAdhesion(array $remplace = []): array
    {
        return array_merge([
            'premiere_adhesion' => 'premiere',
            'civilite' => 'Madame',
            'nom' => 'DUPONT',
            'prenom' => 'Marie',
            'date_naissance' => '01/01/1990',
            'telephone' => '0696000000',
            'email' => 'marie.dupont@example.test',
            'adresse_postale' => '1 rue de la MJA',
            'moyen_paiement' => 'cheque',
            'statut' => 'en_attente_paiement',
            'droit_image' => '0',
        ], $remplace);
    }

    public function test_la_liste_affiche_par_defaut_la_saison_en_cours_et_ses_statistiques(): void
    {
        $saisonEnCours = AdhesionPeriod::create([
            'label' => 'Saison en cours',
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->addMonth(),
            'actif' => true,
        ]);
        $ancienneSaison = AdhesionPeriod::create([
            'label' => 'Ancienne saison',
            'date_debut' => now()->subYears(2),
            'date_fin' => now()->subYear(),
            'actif' => true,
        ]);
        Adhesion::create($this->donneesAdhesion(['period_id' => $saisonEnCours->id]));
        Adhesion::create($this->donneesAdhesion([
            'email' => 'ancienne@example.test',
            'period_id' => $ancienneSaison->id,
            'statut' => 'payee',
        ]));

        $this->actingAs($this->administrateur())
            ->get(route('admin.adhesions.index'))
            ->assertOk()
            ->assertSee('Saison en cours')
            ->assertDontSee('ancienne@example.test')
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 1
                && $stats['en_attente_paiement'] === 1
                && $stats['adherents'] === 0
                && $stats['demandes_adhesion'] === 1
                && $stats['adhesions_payees'] === 0
                && $stats['readhesions'] === 0
                && $stats['readhesions_payees'] === 0
                && $stats['prises_infos'] === 0);
    }

    public function test_un_admin_peut_ajouter_une_adhesion_manuellement_avec_commentaire(): void
    {
        $saison = AdhesionPeriod::create([
            'label' => 'Saison 2026-2027',
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addYear(),
            'actif' => true,
        ]);

        $this->actingAs($this->administrateur())
            ->post(route('admin.adhesions.store'), $this->donneesAdhesion([
                'period_id' => $saison->id,
                'commentaire' => 'Chèque remis au trésorier.',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('adhesions', [
            'period_id' => $saison->id,
            'statut' => 'en_attente_paiement',
            'moyen_paiement' => 'cheque',
            'commentaire' => 'Chèque remis au trésorier.',
            'lu' => true,
        ]);
    }

    public function test_les_statistiques_distinguent_adhesions_readhesions_et_prises_infos(): void
    {
        $saison = AdhesionPeriod::create([
            'label' => 'Saison 2026-2027',
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addYear(),
            'actif' => true,
        ]);

        Adhesion::create($this->donneesAdhesion(['period_id' => $saison->id]));
        Adhesion::create($this->donneesAdhesion([
            'email' => 'payee@example.test',
            'statut' => 'payee',
            'period_id' => $saison->id,
        ]));
        Adhesion::create($this->donneesAdhesion([
            'email' => 'readhesion@example.test',
            'premiere_adhesion' => 'readhesion',
            'statut' => 'payee',
            'period_id' => $saison->id,
        ]));
        Adhesion::create($this->donneesAdhesion([
            'email' => 'information@example.test',
            'premiere_adhesion' => 'information',
            'statut' => 'prise_infos',
            'moyen_paiement' => null,
            'period_id' => $saison->id,
        ]));

        $this->actingAs($this->administrateur())
            ->get(route('admin.adhesions.index', ['period' => $saison->id]))
            ->assertViewHas('stats', fn (array $stats) => $stats['demandes_adhesion'] === 2
                && $stats['adhesions_payees'] === 1
                && $stats['readhesions'] === 1
                && $stats['readhesions_payees'] === 1
                && $stats['prises_infos'] === 1);
    }

    public function test_un_admin_peut_modifier_les_coordonnees_et_le_commentaire(): void
    {
        $adhesion = Adhesion::create($this->donneesAdhesion([
            'commentaire' => 'Ancien commentaire',
        ]));

        $this->actingAs($this->administrateur())
            ->put(route('admin.adhesions.update', $adhesion), $this->donneesAdhesion([
                'telephone' => '0696111111',
                'email' => 'marie.modifiee@example.test',
                'adresse_postale' => '2 avenue des Adhérents',
                'commentaire' => 'Coordonnées vérifiées par téléphone.',
                'moyen_paiement' => 'virement',
            ]))
            ->assertRedirect(route('admin.adhesions.show', $adhesion));

        $this->assertDatabaseHas('adhesions', [
            'id' => $adhesion->id,
            'telephone' => '0696111111',
            'email' => 'marie.modifiee@example.test',
            'adresse_postale' => '2 avenue des Adhérents',
            'moyen_paiement' => 'virement',
            'commentaire' => 'Coordonnées vérifiées par téléphone.',
        ]);
    }

    public function test_la_liste_filtre_chaque_colonne_et_conserve_les_filtres_dans_la_pagination(): void
    {
        $saison = AdhesionPeriod::create([
            'label' => 'Saison 2026-2027',
            'date_debut' => now()->subDay(),
            'date_fin' => now()->addYear(),
            'actif' => true,
        ]);

        Adhesion::create($this->donneesAdhesion([
            'nom' => 'MARTIN',
            'prenom' => 'Alice',
            'email' => 'alice.martin@example.test',
            'premiere_adhesion' => 'readhesion',
            'statut' => 'payee',
            'moyen_paiement' => 'virement',
            'commentaire' => 'Dossier complet',
            'period_id' => $saison->id,
        ]));
        Adhesion::create($this->donneesAdhesion([
            'nom' => 'DURAND',
            'email' => 'durand@example.test',
            'period_id' => $saison->id,
        ]));

        $filtres = [
            'period' => $saison->id,
            'candidat' => 'alice',
            'type' => 'readhesion',
            'statut' => 'payee',
            'paiement' => 'virement',
            'commentaire' => 'complet',
            'date' => now()->toDateString(),
        ];

        $this->actingAs($this->administrateur())
            ->get(route('admin.adhesions.index', $filtres))
            ->assertOk()
            ->assertSee('Alice')
            ->assertDontSee('DURAND')
            ->assertViewHas('adhesions', fn ($adhesions) => $adhesions->total() === 1);
    }
}
