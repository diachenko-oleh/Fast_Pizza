<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/../config.php';


\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    try {
        $customer = \Stripe\Customer::create([
            'name' => $name,
            'email' => $email,
        ]);
        
        echo "ID клієнта Stripe: " . $customer->id . "<br>";
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "Помилка при створенні клієнта: " . $e->getMessage();
    }
} else {
}
