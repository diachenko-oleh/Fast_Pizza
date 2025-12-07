<?php
if ($payment_method === 'card') {

    require 'vendor/autoload.php';

    \Stripe\Stripe::setApiKey('Ключ в тг');

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

