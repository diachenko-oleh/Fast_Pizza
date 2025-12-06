<?php
require_once __DIR__ . '/../Model/auth.php';
require_once __DIR__ . '/../Model/db.php';

$page_title = 'Історія замовлень';
require __DIR__ . '/header.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: auth.php');
    exit;
}

$client = get_current_user_client();
if (!$client) {
    header('Location: auth.php');
    exit;
}

$client_id = $client['id'];

// Отримання всіх замовлень поточного клієнта
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
WHERE r.client_id = :client_id
ORDER BY r.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':client_id' => $client_id]);
$receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Отримання товарів для замовлень клієнта
$ordersSql = "
SELECT 
    o.receipt_id,
    p.name AS product_name,
    p.price,
    o.quantity
FROM orders o
JOIN products p ON o.product_id = p.id
WHERE o.receipt_id IN (SELECT id FROM receipt WHERE client_id = :client_id)
ORDER BY o.receipt_id DESC
";

$stmtOrders = $pdo->prepare($ordersSql);
$stmtOrders->execute([':client_id' => $client_id]);
$ordersResult = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Історія замовлень</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Історія замовлень</h2>
        <a href="profile.php" class="btn btn-secondary">← Назад до профілю</a>
    </div>

    <?php if (empty($receipts)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> У вас ще немає замовлень
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
                        <div class="col-md-4">
                            <strong>Замовлення №<?= $receiptId ?></strong>
                        </div>
                        <div class="col-md-4">
                            <strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($receipt['date_time'])) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Телефон:</strong> <?= htmlspecialchars($receipt['client_phone']) ?>
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
                        <div class="comment-label">Ваш коментар</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require __DIR__ . '/footer.php'; ?>