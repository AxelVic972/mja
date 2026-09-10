{{--
    Champs de protection anti-spam, à placer dans tout formulaire public
    protégé par le middleware `antispam` :

        <form method="POST" ...>
            @csrf
            <x-form-guard />

    Le champ piège est retiré du flux et du parcours clavier : invisible pour
    un visiteur, présent dans le HTML que lit un robot. Le jeton porte l'heure
    d'affichage de la page, chiffrée. Le widget Turnstile n'apparaît que si les
    clés sont renseignées dans .env.
--}}
<div aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;overflow:hidden" tabindex="-1">
    <label>Ne pas remplir
        <input type="text" name="{{ \App\Http\Middleware\AntiSpam::CHAMP_PIEGE }}" tabindex="-1" autocomplete="off" value="">
    </label>
</div>

<input type="hidden" name="{{ \App\Http\Middleware\AntiSpam::CHAMP_JETON }}" value="{{ \App\Http\Middleware\AntiSpam::jeton() }}">

@if (filled(config('antispam.turnstile.cle_site')))
    <div class="cf-turnstile" data-sitekey="{{ config('antispam.turnstile.cle_site') }}" data-language="fr"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
