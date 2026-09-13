<?php

namespace Tests\Feature;

use App\Http\Middleware\AntiSpam;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Le formulaire de contact a encaissé environ 200 soumissions automatisées :
 * le champ piège seul ne suffisait pas. Chaque couche ajoutée est vérifiée
 * ici, et surtout : un message légitime doit continuer à passer.
 */
class ContactAntispamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /** Formulaire tel que le remplit un visiteur réel. */
    private function formulaire(array $remplace = []): array
    {
        return array_merge([
            'nom' => 'Lionely Agesilas',
            'email' => 'lionely@exemple.com',
            'indicatif' => '+596',
            'telephone' => '0696416080',
            'sujet' => 'Adhésion',
            'message' => 'Bonjour, je souhaite rejoindre l\'association cette année.',
            AntiSpam::CHAMP_JETON => AntiSpam::jeton(),
        ], $remplace);
    }

    /**
     * Envoie le formulaire comme le ferait un humain : le jeton naît à
     * l'affichage de la page, puis l'horloge avance du temps de saisie avant
     * l'envoi. L'ordre compte — un jeton généré après avoir avancé l'horloge
     * aurait l'âge zéro au moment du POST, et serait rejeté comme trop rapide.
     */
    private function soumettre(array $remplace = [], ?int $secondesDeSaisie = null): TestResponse
    {
        $donnees = $this->formulaire($remplace);

        Carbon::setTestNow(now()->addSeconds(
            $secondesDeSaisie ?? config('antispam.delai_minimum') + 1
        ));

        return $this->post('/contact', $donnees);
    }

    public function test_la_page_depose_les_champs_de_protection(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        // Sans ces deux champs dans le HTML, toutes les soumissions seraient
        // refusées : c'est le contrat entre le composant et le middleware.
        $this->assertStringContainsString('name="'.AntiSpam::CHAMP_PIEGE.'"', $html);
        $this->assertStringContainsString('name="'.AntiSpam::CHAMP_JETON.'"', $html);

        // Le champ piège doit rester hors de vue et hors du parcours clavier.
        $this->assertStringContainsString('left:-9999px', $html);
    }

    public function test_un_message_legitime_est_enregistre(): void
    {
        $this->soumettre()
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_le_champ_piege_rempli_bloque_sans_rien_dire(): void
    {
        // Refus muet : ni succès, ni message d'erreur exploitable par le robot.
        $this->soumettre([AntiSpam::CHAMP_PIEGE => 'https://spam.example'])
            ->assertSessionMissing('success')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_une_soumission_instantanee_est_bloquee(): void
    {
        $this->soumettre([], 0)->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_jeton_absent_est_bloque(): void
    {
        $donnees = $this->formulaire();
        unset($donnees[AntiSpam::CHAMP_JETON]);

        $this->post('/contact', $donnees)->assertSessionHasErrors('antispam');
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_jeton_forge_de_toutes_pieces_est_bloque(): void
    {
        // Un robot qui devine le nom du champ ne devine pas la clé de chiffrement.
        $this->soumettre([AntiSpam::CHAMP_JETON => (string) now()->getTimestamp()])
            ->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_jeton_rejoue_trop_tard_est_bloque(): void
    {
        $this->soumettre([], config('antispam.duree_validite') + 60)
            ->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_message_bourre_de_liens_est_bloque(): void
    {
        $this->soumettre([
            'message' => 'Boostez votre visibilite : https://a.example et https://b.example puis www.c.example',
        ])->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_seul_lien_reste_accepte(): void
    {
        $this->soumettre([
            'message' => 'Bonjour, voici le site de notre collectif : https://collectif.example — pouvons-nous echanger ?',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_un_point_sans_espace_ne_passe_pas_pour_un_lien(): void
    {
        // « Merci.Info » n'est pas un domaine : la majuscule le distingue.
        $this->soumettre([
            'message' => 'Bonjour et merci.Info complementaire : je passe au local mardi.Site ferme lundi ?',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_un_lien_dans_le_nom_est_bloque(): void
    {
        $this->soumettre(['nom' => 'RobertDussy http://spam.example'])
            ->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_le_bbcode_est_bloque(): void
    {
        $this->soumettre([
            'message' => 'Offre du moment [url=spam]cliquez ici[/url] a ne pas manquer.',
        ])->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_alphabet_hors_public_est_bloque(): void
    {
        $this->soumettre([
            'message' => 'Здравствуйте, предлагаем продвижение вашего сайта.',
        ])->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_un_mot_de_spam_commercial_est_bloque(): void
    {
        $this->soumettre([
            'message' => 'We offer quality backlink packages to rank higher on Google.',
        ])->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_les_accents_ne_masquent_pas_un_mot_interdit(): void
    {
        $this->soumettre([
            'message' => 'Bonjour, nous proposons un sérvice de backlînk pour votre site.',
        ])->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_le_meme_message_renvoye_est_bloque(): void
    {
        $this->soumettre()->assertSessionHasNoErrors();

        // Jeton neuf, adresse IP identique ou non : c'est le contenu qui trahit.
        $this->soumettre()->assertSessionHasErrors('antispam');

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_une_correction_apres_erreur_de_saisie_reste_possible(): void
    {
        // Nom oublié : la validation refuse, donc l'empreinte n'est pas mémorisée.
        $this->soumettre(['nom' => ''])->assertSessionHasErrors('nom');

        // Le visiteur ajoute son nom et renvoie le même message : il doit passer.
        $this->soumettre()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_la_limite_par_minute_coupe_la_rafale(): void
    {
        // Messages tous différents, pour isoler le plafond de la détection de doublon.
        for ($i = 0; $i < 3; $i++) {
            $this->soumettre([
                'email' => "lionely{$i}@exemple.com",
                'message' => "Bonjour, je vous ecris au sujet du projet numero {$i} de l'association.",
            ])->assertSessionHasNoErrors();
        }

        $this->soumettre([
            'message' => 'Bonjour, une derniere question au sujet de vos activites.',
        ])->assertStatus(429);

        $this->assertSame(3, Contact::count());
    }

    public function test_turnstile_est_ignore_tant_que_les_cles_sont_absentes(): void
    {
        config(['antispam.turnstile.cle_site' => null, 'antispam.turnstile.cle_secrete' => null]);

        $this->soumettre()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
