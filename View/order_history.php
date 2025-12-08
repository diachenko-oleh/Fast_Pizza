<?php
require_once __DIR__ . '/../Model/auth.php';
require_once __DIR__ . '/../Model/db.php';

// Перевірка чи користувач авторизований
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['client_id'])) {
    header('Location: auth_page.php');
    exit;
}

$client_id = $_SESSION['client_id'];

// Обработка сохранения отзыва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_review') {
    $receiptId = intval($_POST['receipt_id'] ?? 0);
    $rating = trim($_POST['rating'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // basic validation: require receipt id and either a numeric rating or a non-empty description
    if ($receiptId > 0 && (is_numeric($rating) || $description !== '')) {
        $ratingVal = is_numeric($rating) ? floatval($rating) : null;
        if (is_numeric($ratingVal)) {
            if ($ratingVal < 0) $ratingVal = 0;
            if ($ratingVal > 5) $ratingVal = 5;
        }

        try {
            // ensure receipt belongs to current client
            $chk = $pdo->prepare("SELECT id FROM receipt WHERE id = :rid AND client_id = :cid LIMIT 1");
            $chk->execute([':rid' => $receiptId, ':cid' => $client_id]);
            if ($chk->fetch()) {
                // Use explicit SELECT then INSERT or UPDATE to avoid requiring a unique constraint
                $sel = $pdo->prepare("SELECT id FROM review WHERE receipt_id = :rid AND client_id = :cid LIMIT 1");
                $sel->execute([':rid' => $receiptId, ':cid' => $client_id]);
                $exists = (bool) $sel->fetchColumn();

                if ($exists) {
                    // Do not allow editing existing reviews
                    $_SESSION['review_message'] = 'Відгук вже існує і не може бути змінений';
                } else {
                    $ins = $pdo->prepare("INSERT INTO review (client_id, receipt_id, rating, description) VALUES (:cid, :rid, :rating, :desc)");
                    $ins->execute([':cid' => $client_id, ':rid' => $receiptId, ':rating' => $ratingVal, ':desc' => $description]);
                    $_SESSION['review_message'] = 'Відгук збережено';
                }
            } else {
                $_SESSION['review_message'] = 'Чек не знайдено або недоступний';
            }
        } catch (Exception $e) {
            error_log('Failed to save review: ' . $e->getMessage());
            $_SESSION['review_message'] = 'Помилка при збереженні відгуку';
        }
    } else {
        $_SESSION['review_message'] = 'Потрібно вказати рейтинг або коментар';
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

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

// Load existing reviews for these receipts
$receiptIds = array_column($receipts, 'receipt_id');
$reviewsByReceipt = [];
if (!empty($receiptIds)) {
    // build placeholder list
    $placeholders = implode(',', array_fill(0, count($receiptIds), '?'));
    $sqlReviews = "SELECT receipt_id, rating, description FROM review WHERE receipt_id IN ($placeholders) AND client_id = ?";
    $stmtRev = $pdo->prepare($sqlReviews);
    $params = $receiptIds;
    $params[] = $client_id;
    $stmtRev->execute($params);
    $revRows = $stmtRev->fetchAll(PDO::FETCH_ASSOC);
    foreach ($revRows as $r) {
        $reviewsByReceipt[$r['receipt_id']] = $r;
    }
}

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
        <a href="profile_page.php" class="btn btn-secondary">← Назад до профілю</a>
    </div>

    <?php if (!empty($_SESSION['review_message'])): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['review_message']); ?></div>
        <?php unset($_SESSION['review_message']); ?>
    <?php endif; ?>

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

                <?php $existingReview = $reviewsByReceipt[$receiptId] ?? null; ?>
                <div class="review-section p-3 border-top">
                    <?php if ($existingReview): ?>
                        <div class="existing-review mb-3">
                            <div><strong>Ваш відгук: <?= htmlspecialchars($existingReview['rating']) ?>/5</strong></div>
                            <?php if (!empty($existingReview['description'])): ?>
                                <div class="mt-2">&quot;<?= nl2br(htmlspecialchars($existingReview['description'])) ?>&quot;</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <form method="post" class="row g-2 align-items-end">
                            <input type="hidden" name="action" value="save_review">
                            <input type="hidden" name="receipt_id" value="<?= $receiptId ?>">

                            <div class="col-auto">
                                <label class="form-label">Рейтинг</label>
                                <input type="number" name="rating" class="form-control" step="0.5" min="0" max="5" value="3.0">
                            </div>

                            <div class="col">
                                <label class="form-label">Коментар</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Зберегти відгук</button>
                            </div>
                        </form>
                    <?php endif; ?>
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