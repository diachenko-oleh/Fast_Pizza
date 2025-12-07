<?php
require_once __DIR__ . '/../Model/db.php';

// Отримання всіх чеків з інформацією про клієнтів та адреси
$sql = "
SELECT 
    r.id AS receipt_id,
    r.date_time,
    r.comment,
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

// Отримання всіх товарів для чеків
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

// Групування товарів по чеках
$ordersByReceipt = [];
foreach ($ordersResult as $order) {
    $ordersByReceipt[$order['receipt_id']][] = $order;
}
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Замовлення — Адмін панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Усі замовлення</h2>
        <a href="admin_page.php" class="btn btn-secondary">← Назад до панелі</a>
    </div>

    <?php if (empty($receipts)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Замовлень поки немає
        </div>
    <?php else: ?>
        <?php foreach ($receipts as $receipt): ?>
            <?php 
                $receiptId = $receipt['receipt_id'];
                $products = $ordersByReceipt[$receiptId] ?? [];
                $totalSum = 0;
            ?>
            
            <div class="receipt-card">
                <div class="receipt-header">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Чек №<?= $receiptId ?></strong>
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
                            <strong>Адреса доставки:</strong> 
                            <?= htmlspecialchars($receipt['street']) ?>, 
                            <?= htmlspecialchars($receipt['house_number']) ?>, 
                            <?= htmlspecialchars($receipt['city']) ?>
                        </div>
                    </div>
                    <?php if (!empty($receipt['comment'])): ?>
                    <div class="comment-block">
                        <div class="comment-label">Коментар клієнта</div>
                        <div class="comment-text"><?= htmlspecialchars($receipt['comment']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="receipt-body">
                    <?php if (empty($products)): ?>
                        <p class="text-muted">Немає товарів у замовленні</p>
                    <?php else: ?>
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th class="text-center" style="width: 120px;">Кількість</th>
                                    <th class="text-end" style="width: 120px;">Ціна</th>
                                    <th class="text-end" style="width: 120px;">Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                    $subtotal = $product['price'] * $product['quantity'];
                                    $totalSum += $subtotal;
                                ?>
                                    <tr class="product-row">
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td class="text-center"><?= $product['quantity'] ?></td>
                                        <td class="text-end"><?= number_format($product['price'], 2) ?> грн</td>
                                        <td class="text-end"><strong><?= number_format($subtotal, 2) ?> грн</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="total-sum">
                    Загальна сума: <?= number_format($totalSum, 2) ?> грн
                </div>
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>
<style>
        .receipt-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 24px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .receipt-header {
            background: #f8f9fa;
            padding: 16px;
            border-bottom: 2px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        .receipt-body {
            padding: 16px;
        }
        .total-sum {
            background: #e7f1ff;
            padding: 12px 16px;
            border-radius: 0 0 8px 8px;
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            text-align: right;
        }
        .product-row:hover {
            background-color: #f8f9fa;
        }
        .comment-block {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 12px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
        }
        .comment-label {
            font-weight: 600;
            color: #ffffff;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .comment-label::before {
            content: "💬";
            font-size: 16px;
        }
        .comment-text {
            color: #ffffff;
            margin: 0;
            line-height: 1.6;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid rgba(255, 255, 255, 0.5);
        }
    </style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>