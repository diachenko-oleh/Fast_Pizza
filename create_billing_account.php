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
        echo "<h3>Клієнт успішно створений!</h3>";
        echo "ID клієнта Stripe: " . $customer->id . "<br>";
        echo "<a href='add_customer.php'>Додати ще одного клієнта</a>";
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "Помилка при створенні клієнта: " . $e->getMessage();
    }
} else {
}
