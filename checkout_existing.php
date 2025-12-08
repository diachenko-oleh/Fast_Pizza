<?php
$clientSecret = $_GET['client_secret'] ?? null;
if (!$clientSecret) die("Client secret missing");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Оплата</title>
<script src="https://js.stripe.com/v3/"></script>
<style>
    body {
        font-family: Arial, sans-serif;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .payment-modal {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        position: relative;
        text-align: center;
    }

    .payment-modal h2 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 22px;
    }

    #card-element {
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    button {
        background-color: #6772e5;
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s;
    }

    button:hover {
        background-color: #5469d4;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
        color: #888;
    }

    .close-btn:hover {
        color: #333;
    }
</style>
</head>
<body>

<div class="payment-modal">
    <span class="close-btn" onclick="window.close()">&times;</span>
    <h2>Оплата карткою</h2>
    <form id="payment-form">
        <div id="card-element"></div>
        <button type="submit">Оплатити</button>
    </form>
</div>

<script>
const stripe = Stripe("pk_test_51SbfgPPMUwJQfY2hgaJs6UzWjG5vKmqIt2fujAlkOPmE1YIMto9zVXCxks6fhY5noq8dLK7lSZ7uvdJ1Lw3y9wtZ00Dr0c19ir");
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
        alert("Помилка оплати: " + error.message);
    } else {
        alert("Оплата успішна!");
        window.close();
    }
});
</script>

</body>
</html>
