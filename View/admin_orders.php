<?php
require __DIR__ . '/../Model/db.php';

$sql = "
SELECT 
    r.id AS receipt_id,
    r.date_time,
    c.full_name AS client_name,
    c.phone AS client_phone,
    a.street, 
    a.house_number, 
    a.city
FROM receipt r
JOIN client c ON r.client_id = c.id
JOIN addresses a ON r.address_id = a.id
ORDER BY r.id DESC
";

$receipts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Отримуємо товари для кожного чеку
$ordersSql = "
SELECT 
    o.receipt_id,
    p.name AS product_name,
    p.price,
    o.quantity
FROM orders o
JOIN products p ON o.product_id = p.id
ORDER BY o.receipt_id DESC
";

$ordersResult = $pdo->query($ordersSql)->fetchAll(PDO::FETCH_ASSOC);

// Групуємо товари по чеках
$ordersByReceipt = [];
foreach ($ordersResult as $order) {
    $ordersByReceipt[$order['receipt_id']][] = $order;
}
?>
<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <title>Замовлення — Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .receipt-card {
      margin-bottom: 30px;
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
    }
    .receipt-header {
      background-color: #f8f9fa;
      padding: 15px;
      border-bottom: 2px solid #dee2e6;
    }
    .receipt-body {
      padding: 15px;
    }
    .product-row {
      padding: 10px;
      border-bottom: 1px solid #f0f0f0;
    }
    .product-row:last-child {
      border-bottom: none;
    }
    .total-sum {
      background-color: #e9ecef;
      padding: 15px;
      font-weight: bold;
      text-align: right;
      font-size: 1.2em;
    }
  </style>
</head>
<body class="p-4">

<div class="container">
  <h2 class="mb-4">Усі замовлення</h2>

  <?php if (empty($receipts)): ?>
    <div class="alert alert-info">Замовлень поки немає</div>
  <?php else: ?>
    <?php foreach ($receipts as $receipt): ?>
      <?php 
        $receiptId = $receipt['receipt_id'];
        $products = $ordersByReceipt[$receiptId] ?? [];
        $totalSum = 0;
      ?>
      
      <div class="receipt-card">
        <div class="receipt-header">
          <div class="row">
            <div class="col-md-3">
              <strong>Чек #<?= $receiptId ?></strong>
            </div>
            <div class="col-md-3">
              <strong>Клієнт:</strong> <?= htmlspecialchars($receipt['client_name']) ?>
            </div>
            <div class="col-md-3">
              <strong>Телефон:</strong> <?= htmlspecialchars($receipt['client_phone']) ?>
            </div>
            <div class="col-md-3">
              <strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($receipt['date_time'])) ?>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12">
              <strong>Адреса:</strong> 
              <?= htmlspecialchars($receipt['street']) ?>, 
              <?= htmlspecialchars($receipt['house_number']) ?>, 
              <?= htmlspecialchars($receipt['city']) ?>
            </div>
          </div>
        </div>

        <div class="receipt-body">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Товар</th>
                <th style="width: 100px; text-align: center;">Кількість</th>
                <th style="width: 120px; text-align: right;">Ціна</th>
                <th style="width: 120px; text-align: right;">Сума</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): 
                $subtotal = $product['price'] * $product['quantity'];
                $totalSum += $subtotal;
              ?>
                <tr class="product-row">
                  <td><?= htmlspecialchars($product['product_name']) ?></td>
                  <td style="text-align: center;"><?= $product['quantity'] ?></td>
                  <td style="text-align: right;"><?= number_format($product['price'], 2) ?> грн</td>
                  <td style="text-align: right;"><strong><?= number_format($subtotal, 2) ?> грн</strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="total-sum">
          Загальна сума: <?= number_format($totalSum, 2) ?> грн
        </div>
      </div>

    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>