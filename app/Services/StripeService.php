<?php

namespace App\Services;

use App\Models\Adhesion;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private const API = 'https://api.stripe.com/v1';

    /** Le paiement en ligne est-il utilisable (activé + clés présentes) ? */
    public static function enabled(): bool
    {
        return (bool) Setting::get('stripe_enabled')
            && Setting::has('stripe_secret_key')
            && Setting::has('stripe_public_key');
    }

    /**
     * Montant débité par carte, en centimes : cotisation + frais de transaction
     * répercutés sur le payeur (voir App\Support\Cotisation).
     */
    public static function amountCents(): int
    {
        return \App\Support\Cotisation::montantCarteCents();
    }

    private static function secret(): ?string
    {
        return Setting::get('stripe_secret_key');
    }

    /** Clé publique, à exposer au navigateur pour Stripe.js. */
    public static function publicKey(): ?string
    {
        return Setting::get('stripe_public_key');
    }

    /**
     * Crée un PaymentIntent pour le paiement par carte intégré au formulaire.
     *
     * @return array{id: string, client_secret: string}|null
     */
    public static function createPaymentIntent(int $amountCents, array $metadata = []): ?array
    {
        $params = [
            'amount'                        => $amountCents,
            'currency'                      => 'eur',
            'description'                   => "Cotisation adhésion — Madin'Jeunes Ambition",
            'automatic_payment_methods[enabled]' => 'true',
        ];

        foreach ($metadata as $cle => $valeur) {
            $params["metadata[$cle]"] = $valeur;
        }

        $resp = Http::asForm()->withToken(self::secret())->post(self::API . '/payment_intents', $params);

        if ($resp->failed()) {
            Log::error('Stripe payment intent failed: ' . $resp->body());

            return null;
        }

        return [
            'id'            => $resp->json('id'),
            'client_secret' => $resp->json('client_secret'),
        ];
    }

    /**
     * Inscrit l'identifiant de l'adhésion dans le PaymentIntent.
     *
     * Le paiement est créé avant l'adhésion — le formulaire n'est envoyé
     * qu'ensuite. Sans ce rattrapage, un évènement Stripe arrivant plus tard
     * (validation différée, litige) ne saurait pas à quelle demande le
     * rattacher.
     */
    public static function attacherAdhesion(?string $intentId, int $adhesionId): void
    {
        if (! $intentId || ! self::enabled()) {
            return;
        }

        $resp = Http::asForm()->withToken(self::secret())
            ->post(self::API . '/payment_intents/' . $intentId, ['metadata[adhesion_id]' => $adhesionId]);

        if ($resp->failed()) {
            Log::warning('Stripe : rattachement adhésion au paiement échoué : ' . $resp->body());
        }
    }

    /** Récupère un PaymentIntent (tableau) ou null. */
    public static function retrievePaymentIntent(string $intentId): ?array
    {
        $resp = Http::withToken(self::secret())->get(self::API . '/payment_intents/' . $intentId);

        if ($resp->failed()) {
            Log::error('Stripe retrieve payment intent failed: ' . $resp->body());

            return null;
        }

        return $resp->json();
    }

    /**
     * Vérifie côté serveur qu'un paiement carte a bien abouti pour le bon montant.
     * Ne jamais se fier au seul retour du navigateur.
     */
    public static function paiementValide(?string $intentId, ?int $amountCents = null): bool
    {
        if (! $intentId || ! self::enabled()) {
            return false;
        }

        $intent = self::retrievePaymentIntent($intentId);

        return $intent !== null
            && ($intent['status'] ?? null) === 'succeeded'
            && ($intent['currency'] ?? null) === 'eur'
            && (int) ($intent['amount_received'] ?? 0) >= ($amountCents ?? self::amountCents());
    }

    /** Crée une session Stripe Checkout et renvoie l'URL de paiement (ou null). */
    public static function createCheckoutSession(Adhesion $adhesion, string $successUrl, string $cancelUrl): ?string
    {
        $resp = Http::asForm()->withToken(self::secret())->post(self::API . '/checkout/sessions', [
            'mode'                                              => 'payment',
            'success_url'                                       => $successUrl,
            'cancel_url'                                        => $cancelUrl,
            'client_reference_id'                               => $adhesion->id,
            'customer_email'                                    => $adhesion->email,
            'metadata[adhesion_id]'                             => $adhesion->id,
            'line_items[0][quantity]'                           => 1,
            'line_items[0][price_data][currency]'               => 'eur',
            'line_items[0][price_data][unit_amount]'            => self::amountCents(),
            'line_items[0][price_data][product_data][name]'     => "Cotisation adhésion — Madin'Jeunes Ambition",
        ]);

        if ($resp->failed()) {
            Log::error('Stripe checkout session failed: ' . $resp->body());
            return null;
        }

        return $resp->json('url');
    }

    /** Crée une session Checkout pour un don ; renvoie l'URL de paiement (ou null). */
    public static function createDonationCheckout(\App\Models\Donation $don, string $successUrl, string $cancelUrl): ?string
    {
        $resp = Http::asForm()->withToken(self::secret())->post(self::API . '/checkout/sessions', [
            'mode'                                          => 'payment',
            'success_url'                                   => $successUrl,
            'cancel_url'                                    => $cancelUrl,
            'client_reference_id'                           => $don->id,
            'customer_email'                                => $don->email,
            'metadata[donation_id]'                         => $don->id,
            'line_items[0][quantity]'                       => 1,
            'line_items[0][price_data][currency]'           => 'eur',
            'line_items[0][price_data][unit_amount]'        => (int) round(((float) $don->montant) * 100),
            'line_items[0][price_data][product_data][name]' => "Don à Madin'Jeunes Ambition",
        ]);

        if ($resp->failed()) {
            Log::error('Stripe donation session failed: ' . $resp->body());
            return null;
        }

        return $resp->json('url');
    }

    /** Récupère une session Checkout (tableau) ou null. */
    public static function retrieveSession(string $sessionId): ?array
    {
        $resp = Http::withToken(self::secret())->get(self::API . '/checkout/sessions/' . $sessionId);

        if ($resp->failed()) {
            Log::error('Stripe retrieve session failed: ' . $resp->body());
            return null;
        }

        return $resp->json();
    }
}
