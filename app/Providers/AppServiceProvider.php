<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->limiterLesFormulaires();
    }

    /**
     * Plafonds du formulaire de contact, cumulés sur trois échelles de temps.
     *
     * La limite à la minute arrête une rafale ; celles à l'heure et au jour
     * arrêtent le robot qui prend son temps pour passer sous le radar — c'est
     * exactement ce qu'a fait la campagne de septembre 2026. Trois messages en
     * une journée reste très au-dessus de ce qu'envoie un visiteur réel.
     */
    private function limiterLesFormulaires(): void
    {
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(8)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);
    }
}
