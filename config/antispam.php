<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Piège temporel
    |--------------------------------------------------------------------------
    |
    | Un robot poste le formulaire dans la seconde qui suit le chargement de la
    | page. Un humain met au moins quelques secondes à le remplir. Le jeton
    | déposé dans le formulaire est chiffré : il ne peut pas être fabriqué de
    | toutes pièces, seulement rejoué — d'où la durée de validité maximale.
    |
    */

    'delai_minimum' => (int) env('ANTISPAM_DELAI_MINIMUM', 4),

    'duree_validite' => (int) env('ANTISPAM_DUREE_VALIDITE', 7200),

    /*
    |--------------------------------------------------------------------------
    | Analyse du contenu
    |--------------------------------------------------------------------------
    |
    | Le spam commercial vit de ses liens : c'est le signal le plus fiable.
    | Un lien reste toléré (« voici notre site »), au-delà on refuse.
    |
    */

    'liens_max' => (int) env('ANTISPAM_LIENS_MAX', 1),

    // Un lien dans le nom ou le sujet n'a aucune raison d'être légitime.
    'liens_interdits_hors_message' => true,

    // Alphabets qui ne correspondent à aucun public de l'association.
    'alphabets_interdits' => ['Cyrillic', 'Han', 'Hangul', 'Hiragana', 'Katakana'],

    // Recherchés en minuscules, sans accent, dans nom + sujet + message.
    'mots_interdits' => [
        'backlink',
        'seo service',
        'seo expert',
        'link building',
        'guest post',
        'xrumer',
        'casino',
        'viagra',
        'cialis',
        'porn',
        'escort',
        'binary option',
        'forex trading',
        'crypto investment',
        'bitcoin investment',
        'buy now',
        'cheap price',
        'make money online',
        'increase your traffic',
        'rank higher',
    ],

    // Le BBCode n'existe dans aucun de nos champs : c'est du spam de forum.
    'bbcode_interdit' => true,

    /*
    |--------------------------------------------------------------------------
    | Doublons
    |--------------------------------------------------------------------------
    |
    | Les campagnes de spam rejouent le même message depuis plusieurs adresses
    | IP : la limitation par IP ne les voit pas passer, l'empreinte du message
    | si. Durée en secondes.
    |
    */

    'fenetre_doublon' => (int) env('ANTISPAM_FENETRE_DOUBLON', 900),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Dernier rempart, à n'activer que si le spam persiste : renseigner les
    | deux clés dans .env suffit à afficher le widget et à vérifier la réponse.
    | Sans clés, la couche est simplement ignorée.
    |
    */

    'turnstile' => [
        'cle_site' => env('TURNSTILE_SITE_KEY'),
        'cle_secrete' => env('TURNSTILE_SECRET_KEY'),
        'url_verification' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

];
