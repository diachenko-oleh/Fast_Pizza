<?php
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_51SbfgPPMUwJQfY2hNdCohPSg3kmj7KYApHVTlHRPN3pEVPe6HqSrzlLtrmwhDLHa0mTQa2ucDRJD5nZi5zub8L7q007iSiV5yn');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = trim($_POST['customer_id']);
    $amount = intval($_POST['amount']) * 100;

    try {
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'uah',
            'customer' => $customerId,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $secret = $paymentIntent->client_secret;

        header("Location: checkout_existing.php?client_secret=$secret");
        exit;

    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "Помилка: " . $e->getMessage();
    }
}
