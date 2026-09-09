<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactAntispamTest extends TestCase
{
    use RefreshDatabase;

    private function formulaire(): array
    {
        return [
            'nom'       => 'Marie Dupont',
            'email'     => 'marie@exemple.com',
            'indicatif' => '+596',
            'telephone' => '0696000000',
            'sujet'     => 'Demande d’information',
            'message'   => 'Bonjour, je souhaiterais avoir plus d’informations.',
        ];
    }

    public function test_un_envoi_direct_sans_affichage_du_formulaire_est_refuse(): void
    {
        Mail::fake();

        $this->post('/contact', $this->formulaire())
            ->assertSessionHasErrors('formulaire');

        $this->assertSame(0, Contact::count());
    }

    public function test_l_affichage_du_formulaire_demarre_la_protection_temporelle(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSessionHas('contact_form_started_at');
    }

    public function test_un_message_valide_est_enregistre_apres_le_delai_minimum(): void
    {
        Mail::fake();

        $this->withSession(['contact_form_started_at' => now()->subSeconds(4)->getTimestamp()])
            ->post('/contact', $this->formulaire())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', ['email' => 'marie@exemple.com']);
    }
}
