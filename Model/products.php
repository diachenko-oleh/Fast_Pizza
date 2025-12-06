<?php
require_once __DIR__ . '/db.php';

// Отримуємо всі продукти з бази даних
$sql = "SELECT id, name, price, isPizza FROM products ORDER BY id";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Розділяємо на піци та напої
$products = [];
$drinks = [];

foreach ($data as $item) {
    if (!empty($item["ispizza"])) {
        $products[] = $item;
    } else {
        $drinks[] = $item;
    }
}
?>