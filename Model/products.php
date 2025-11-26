<?php
require __DIR__ . '/db.php';

$sql = "SELECT id, name, price, isPizza FROM products";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
$drinks   = [];

foreach ($data as $item) {
    if (!empty($item["ispizza"])) {
        $products[] = $item;
    } else {
        $drinks[] = $item;
    }
}