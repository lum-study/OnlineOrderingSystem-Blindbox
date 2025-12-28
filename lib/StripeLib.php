<?php
$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    // Throw exception so CheckoutController can handle it
    throw new Exception('Stripe Library is not available. Please update it.');
}

require_once $autoload;

class StripeLib
{
    private $stripe;

    public function __construct()
    {
        $apiKey = require __DIR__ . '/../config/stripe.php';

        \Stripe\Stripe::setApiKey($apiKey['secret_key']);

        $this->stripe = new \Stripe\StripeClient($apiKey['secret_key']);
    }

    public function createPaymentIntent($amount)
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'myr',
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
