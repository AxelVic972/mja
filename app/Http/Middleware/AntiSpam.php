<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Défense en profondeur des formulaires publics.
 *
 * Aucune de ces couches n'est suffisante seule — le champ piège posé jusqu'ici
 * n'a pas arrêté la campagne de septembre 2026 — mais un robot doit toutes les
 * franchir. Elles sont ordonnées de la moins coûteuse (un test de chaîne) à la
 * plus coûteuse (un appel réseau à Cloudflare).
 *
 * Le compagnon côté vue est le composant <x-form-guard />, qui dépose les
 * champs attendus ici.
 */
class AntiSpam
{
    /** Champ piège, invisible pour les humains, rempli par les robots naïfs. */
    public const CHAMP_PIEGE = Honeypot::FIELD;

    /** Jeton chiffré portant l'horodatage d'affichage du formulaire. */
    public const CHAMP_JETON = 'form_token';

    /** Champ déposé par le widget Cloudflare Turnstile. */
    public const CHAMP_TURNSTILE = 'cf-turnstile-response';

    /** Champs analysés par la détection de contenu, hors message. */
    private const CHAMPS_IDENTITE = ['nom', 'prenom', 'sujet'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Réponse muette : informer le robot, c'est l'aider à s'adapter.
        if (filled($request->input(self::CHAMP_PIEGE))) {
            return $this->refuser($request, 'piege');
        }

        if ($motif = $this->jetonInvalide($request)) {
            return $this->refuser($request, $motif, match ($motif) {
                'jeton_absent', 'jeton_illisible' => 'Votre session a expiré. Merci de recharger la page et de renvoyer le message.',
                'jeton_expire' => 'Cette page est restée ouverte trop longtemps. Merci de la recharger avant d\'envoyer votre message.',
                default => 'Votre message a été envoyé trop vite pour être pris en compte. Merci de réessayer.',
            });
        }

        if ($motif = $this->contenuSuspect($request)) {
            return $this->refuser(
                $request,
                $motif,
                'Votre message a été identifié comme indésirable. Si c\'est une erreur, retirez les liens qu\'il contient ou écrivez-nous directement par email.'
            );
        }

        if ($this->dejaRecu($request)) {
            return $this->refuser(
                $request,
                'doublon',
                'Ce message vient déjà de nous être envoyé. Nous y répondrons dans les plus brefs délais.'
            );
        }

        if (! $this->turnstileValide($request)) {
            return $this->refuser(
                $request,
                'turnstile',
                'La vérification anti-robot a échoué. Merci de recharger la page et de réessayer.'
            );
        }

        return $next($request);
    }

    // ── Jeton horodaté ────────────────────────────────────────────────────────

    /**
     * Jeton à déposer dans le formulaire. Chiffré avec la clé de l'application :
     * un robot ne peut pas en forger un, seulement réutiliser celui qu'il a lu —
     * ce que la durée de validité limite.
     */
    public static function jeton(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /** @return string|null Motif du refus, ou null si le jeton est valable. */
    private function jetonInvalide(Request $request): ?string
    {
        $jeton = $request->input(self::CHAMP_JETON);

        if (! is_string($jeton) || $jeton === '') {
            return 'jeton_absent';
        }

        try {
            $affichage = (int) Crypt::decryptString($jeton);
        } catch (DecryptException) {
            return 'jeton_illisible';
        }

        $age = now()->getTimestamp() - $affichage;

        if ($age < (int) config('antispam.delai_minimum')) {
            return 'trop_rapide';
        }

        if ($age > (int) config('antispam.duree_validite')) {
            return 'jeton_expire';
        }

        return null;
    }

    // ── Analyse du contenu ────────────────────────────────────────────────────

    /** @return string|null Motif du refus, ou null si le contenu est acceptable. */
    private function contenuSuspect(Request $request): ?string
    {
        $message = (string) $request->input('message', '');

        $identite = collect(self::CHAMPS_IDENTITE)
            ->map(fn (string $champ) => (string) $request->input($champ, ''))
            ->implode(' ');

        $tout = $identite.' '.$message;

        if (config('antispam.liens_interdits_hors_message') && $this->compterLiensExplicites($identite) > 0) {
            return 'lien_dans_identite';
        }

        if ($this->compterLiens($message) > (int) config('antispam.liens_max')) {
            return 'trop_de_liens';
        }

        if (config('antispam.bbcode_interdit') && preg_match('~\[/?(?:url|link|img|b|i|quote)\b~i', $tout)) {
            return 'bbcode';
        }

        foreach ((array) config('antispam.alphabets_interdits') as $alphabet) {
            if (preg_match('~\p{'.$alphabet.'}~u', $tout)) {
                return 'alphabet_'.Str::lower($alphabet);
            }
        }

        $normalise = Str::lower(Str::ascii($tout));

        foreach ((array) config('antispam.mots_interdits') as $mot) {
            if (str_contains($normalise, Str::lower($mot))) {
                return 'mot_interdit';
            }
        }

        return null;
    }

    /**
     * Compte les liens : URL explicites, adresses en www. et noms de domaine
     * nus sur les extensions dont vit le spam.
     */
    private function compterLiens(string $texte): int
    {
        /*
         * Le motif des domaines nus est volontairement sensible à la casse :
         * un vrai domaine s'écrit en minuscules, alors qu'un point sans espace
         * en fin de phrase (« Merci.Info complémentaire ») porte une majuscule
         * et ne doit pas compter pour un lien.
         */
        return $this->compterLiensExplicites($texte)
            + preg_match_all(
                '~\b[a-z0-9][a-z0-9-]*\.(?:com|net|org|ru|su|cn|xyz|top|shop|online|site|info|biz|club|icu|live|link|click|store|space|website|pw)\b~',
                $texte
            );
    }

    /**
     * Liens écrits noir sur blanc. Dans un nom ou un sujet, un seul suffit à
     * trahir un robot, alors qu'un domaine nu y serait trop ambigu.
     */
    private function compterLiensExplicites(string $texte): int
    {
        $motifs = [
            '~https?://~i',
            '~\bwww\.[a-z0-9-]~i',
            '~\[url~i',
        ];

        $total = 0;

        foreach ($motifs as $motif) {
            $total += preg_match_all($motif, $texte);
        }

        return $total;
    }

    // ── Doublons ──────────────────────────────────────────────────────────────

    /**
     * Une campagne rejoue le même message depuis des adresses IP différentes :
     * l'empreinte du contenu la repère là où la limitation par IP échoue.
     */
    private function dejaRecu(Request $request): bool
    {
        $cle = self::cleDoublon(
            (string) $request->input('email', ''),
            (string) $request->input('message', '')
        );

        return $cle !== null && Cache::has($cle);
    }

    /**
     * À appeler depuis le contrôleur, une fois le message réellement
     * enregistré — et là seulement.
     *
     * Le faire ici, après $next(), ne marcherait pas : Laravel transforme
     * l'exception de validation en réponse à l'intérieur du pipeline, si bien
     * qu'une soumission refusée revient au middleware comme une réussite. Le
     * visiteur qui corrige une faute de saisie et renvoie son message serait
     * alors pris pour un robot.
     */
    public static function memoriser(string $email, string $message): void
    {
        if ($cle = self::cleDoublon($email, $message)) {
            Cache::put($cle, true, (int) config('antispam.fenetre_doublon'));
        }
    }

    /** @return string|null Null lorsque la détection de doublon est désactivée. */
    private static function cleDoublon(string $email, string $message): ?string
    {
        if ((int) config('antispam.fenetre_doublon') <= 0) {
            return null;
        }

        return 'antispam:doublon:'.md5(Str::lower(trim($email.'|'.$message)));
    }

    // ── Cloudflare Turnstile ──────────────────────────────────────────────────

    /** Ignoré tant que les clés ne sont pas renseignées dans .env. */
    private function turnstileValide(Request $request): bool
    {
        $secret = config('antispam.turnstile.cle_secrete');

        if (blank($secret) || blank(config('antispam.turnstile.cle_site'))) {
            return true;
        }

        try {
            $reponse = Http::asForm()
                ->timeout(5)
                ->post(config('antispam.turnstile.url_verification'), [
                    'secret' => $secret,
                    'response' => $request->input(self::CHAMP_TURNSTILE),
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            // Cloudflare injoignable : on laisse passer plutôt que de fermer le
            // formulaire à tout le monde. Les autres couches restent en place.
            Log::warning('Turnstile injoignable : '.$e->getMessage());

            return true;
        }

        return $reponse->successful() && $reponse->json('success') === true;
    }

    // ── Refus ─────────────────────────────────────────────────────────────────

    /**
     * Refuse la soumission. Sans message affiché (champ piège), le visiteur
     * revient simplement au formulaire : le robot n'apprend rien.
     */
    private function refuser(Request $request, string $motif, ?string $message = null): Response
    {
        Log::info('Soumission rejetée par AntiSpam', [
            'motif' => $motif,
            'route' => $request->path(),
            'ip' => $request->ip(),
        ]);

        $retour = back()->withInput($request->except([self::CHAMP_PIEGE, self::CHAMP_JETON, self::CHAMP_TURNSTILE]));

        return $message === null
            ? $retour
            : $retour->withErrors(['antispam' => $message]);
    }
}
