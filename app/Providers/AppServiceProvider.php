<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Les limites génériques par minute ne suffisent pas contre un robot
         * qui espace ses requêtes ou change d'adresse IP. Cette limite combine
         * une fenêtre courte (protection immédiate), une fenêtre journalière
         * par IP et une fenêtre journalière par e-mail. L'e-mail est haché
         * pour ne jamais apparaître en clair dans la clé de cache.
         */
        foreach (['adhesion', 'contact'] as $formulaire) {
            RateLimiter::for($formulaire, static function (Request $request) use ($formulaire): array {
                $ip = $request->ip() ?? 'inconnue';
                $email = Str::lower(trim((string) $request->input('email')));

                $limites = [
                    Limit::perMinutes(10, 3)->by("{$formulaire}:ip:10min:{$ip}"),
                    Limit::perDay(12)->by("{$formulaire}:ip:jour:{$ip}"),
                ];

                if ($email !== '') {
                    $limites[] = Limit::perDay(2)->by("{$formulaire}:email:jour:" . hash('sha256', $email));
                }

                return $limites;
            });
        }
    }
}
