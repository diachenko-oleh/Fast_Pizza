<?php
$orderId = $_GET['order'] ?? 0;

echo "<h1>Оплата успішна!</h1>";
echo "<p>Ваше замовлення №$orderId оплачено.</p>";
echo "<a href='menu_page.php'>Повернутися</a>";
