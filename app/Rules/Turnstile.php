<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Vérifie côté serveur le jeton émis par Cloudflare Turnstile. */
class Turnstile implements ValidationRule
{
    public function __construct(private readonly ?string $ip)
    {
    }

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('La vérification anti-robot est obligatoire.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => config('services.turnstile.secret_key'),
                    'response' => $value,
                    'remoteip' => $this->ip,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Vérification Turnstile indisponible.', ['exception' => $e->getMessage()]);
            $fail('La vérification anti-robot est momentanément indisponible. Réessayez dans quelques instants.');

            return;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            Log::notice('Vérification Turnstile refusée.', ['codes' => $response->json('error-codes', [])]);
            $fail('La vérification anti-robot a échoué. Rechargez la page puis réessayez.');
        }
    }
}
