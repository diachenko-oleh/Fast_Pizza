<?php
if ($payment_method === 'card') {

    require 'vendor/autoload.php';

    \Stripe\Stripe::setApiKey('sk_test_51SbfgPPMUwJQfY2hNdCohPSg3kmj7KYApHVTlHRPN3pEVPe6HqSrzlLtrmwhDLHa0mTQa2ucDRJD5nZi5zub8L7q007iSiV5yn');

    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'uah',
                'product_data' => [
                    'name' => "Замовлення №$receipt_id"
                ],
                'unit_amount' => $stripeAmount
            ],
            'quantity' => 1
        ]],
        'success_url' => "http://localhost/success.php?order=$receipt_id",
        'cancel_url'   => "http://localhost/cancel.php?order=$receipt_id"
    ]);

    // очищаємо кошик
    $_SESSION['cart'] = [];

    header("Location: " . $session->url);
    exit;
}

