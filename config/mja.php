<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Affichage du programme SNS / Fwi Ti Dèj
    |--------------------------------------------------------------------------
    |
    | Mettre à true pour ré-afficher la page « Santé · Nutrition · Sport »,
    | ainsi que les références à « Fwi Ti Dèj » dans la navigation, la page
    | d'accueil et le pied de page. La page et les vues restent en place :
    | ce drapeau ne fait que masquer/afficher les points d'entrée.
    |
    */
    'show_sns' => env('MJA_SHOW_SNS', false),

    // Temps minimal entre l'affichage et l'envoi du formulaire d'adhésion.
    'adhesion_min_fill_seconds' => env('MJA_ADHESION_MIN_FILL_SECONDS', 3),

    // Même protection temporelle pour les messages envoyés via la page contact.
    'contact_min_fill_seconds' => env('MJA_CONTACT_MIN_FILL_SECONDS', 3),

    /*
    |--------------------------------------------------------------------------
    | Adresse de contact publique
    |--------------------------------------------------------------------------
    |
    | Adresse affichée sur le site et dans les emails (page contact, pied de
    | page, mentions légales, confidentialité, kit de communication). Centralisée
    | ici pour n'avoir qu'un seul endroit à modifier le jour où elle change.
    |
    | À ne pas confondre avec les destinataires des notifications
    | d'administration, réglables en back-office (Paramètres › Emails de
    | notification), ni avec l'expéditeur technique (MAIL_FROM_ADDRESS).
    |
    */
    'contact_email' => env('MJA_CONTACT_EMAIL', 'madinjeunesambition@gmail.com'),

];
