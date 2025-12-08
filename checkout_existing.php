<?php
$clientSecret = $_GET['client_secret'] ?? null;
if (!$clientSecret) die("Client secret missing");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        #card-element {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

<h3>Введіть дані для оплати: [номер карти] [термін придатності] [CVC] [поштовий індекс]</h3>

<form id="payment-form">
    <div id="card-element"></div>
    <button type="submit">Оплатити</button>
</form>

<script>
const stripe = Stripe("pk_test_51SbfgPPMUwJQfY2hgaJs6UzWjG5vKmqIt2fujAlkOPmE1YIMto9zVXCxks6fhY5noq8dLK7lSZ7uvdJ1Lw3y9wtZ00Dr0c19ir"); // твой public key

const elements = stripe.elements();
const card = elements.create("card");
card.mount("#card-element");

const clientSecret = "<?= $clientSecret ?>";

document.getElementById("payment-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: { card: card }
    });

    if (error) {
        alert("Ошибка оплаты: " + error.message);
    } else {
        window.close();
    }
});
</script>

</body>
</html>
