<?php
require_once __DIR__ . '/../Model/db.php';

// Отримання всіх чеків (код без змін)
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 24px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        /* Стилі для виконаного замовлення (додаються через JS) */
        .receipt-card.is-completed {
            border-color: #198754;
            background-color: #f0fff4; /* Світло-зелений фон */
            opacity: 0.7; /* Трохи прозорий */
        }
        
        .receipt-card.is-completed .receipt-header {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .receipt-header {
            background: #f8f9fa;
            padding: 16px;
            border-bottom: 2px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }
        
        .receipt-body { padding: 16px; }
        
        .total-sum {
            background: #e7f1ff;
            padding: 12px 16px;
            border-radius: 0 0 8px 8px;
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            text-align: right;
        }

        /* Кнопка перемикання */
        .toggle-btn {
            width: 160px;
        }

        /* Інші стилі */
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
            margin-bottom: 6px;
        }
        .comment-text {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 12px;
            border-radius: 6px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Усі замовлення</h2>
        <a href="admin_page.php" class="btn btn-secondary">← Назад до панелі</a>
    </div>

    <?php if (empty($receipts)): ?>
        <div class="alert alert-info">Замовлень поки немає</div>
    <?php else: ?>
        <?php foreach ($receipts as $receipt): ?>
            <?php 
                $receiptId = $receipt['receipt_id'];
                $products = $ordersByReceipt[$receiptId] ?? [];
                $totalSum = 0;
            ?>
            
            <div class="receipt-card" id="card-<?= $receiptId ?>" data-id="<?= $receiptId ?>">
                <div class="receipt-header">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <strong>Чек №<?= $receiptId ?></strong>
                            <span class="status-badge ms-2"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Клієнт:</strong> <?= htmlspecialchars($receipt['client_name']) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Телефон:</strong> <?= htmlspecialchars($receipt['client_phone']) ?>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-success btn-sm toggle-btn" onclick="toggleOrder(<?= $receiptId ?>)">
                                <i class="bi bi-check-lg"></i> Виконати
                            </button>
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
                            <div class="comment-label">💬 Коментар клієнта</div>
                            <div class="comment-text"><?= htmlspecialchars($receipt['comment']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="receipt-body">
                    <?php if (empty($products)): ?>
                        <p class="text-muted">Немає товарів</p>
                    <?php else: ?>
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Товар</th>
                                    <th class="text-center">К-сть</th>
                                    <th class="text-end">Ціна</th>
                                    <th class="text-end">Сума</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                    $subtotal = $product['price'] * $product['quantity'];
                                    $totalSum += $subtotal;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td class="text-center"><?= $product['quantity'] ?></td>
                                        <td class="text-end"><?= number_format($product['price'], 2) ?></td>
                                        <td class="text-end"><strong><?= number_format($subtotal, 2) ?></strong></td>
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

<script>
    // Назва ключа в сховищі браузера
    const STORAGE_KEY = 'completed_orders';

    // Функція запуску при завантаженні сторінки
    document.addEventListener('DOMContentLoaded', () => {
        loadStatus();
    });

    function toggleOrder(id) {
        const card = document.getElementById('card-' + id);
        const btn = card.querySelector('.toggle-btn');
        
        // Перемикаємо клас
        card.classList.toggle('is-completed');
        
        // Оновлюємо вигляд кнопки
        updateCardVisuals(card, btn);
        
        // Зберігаємо зміни в пам'ять браузера
        saveStatus();
    }

    function updateCardVisuals(card, btn) {
        const isCompleted = card.classList.contains('is-completed');
        
        if (isCompleted) {
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Повернути';
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-secondary');
        } else {
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Виконати';
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-outline-success');
        }
    }

    function saveStatus() {
        // Знаходимо всі картки з класом is-completed
        const completedCards = document.querySelectorAll('.receipt-card.is-completed');
        const ids = Array.from(completedCards).map(card => card.dataset.id);
        
        // Зберігаємо масив ID у LocalStorage
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    }

    function loadStatus() {
        // Отримуємо дані з пам'яті
        const savedData = localStorage.getItem(STORAGE_KEY);
        if (!savedData) return;

        const ids = JSON.parse(savedData);
        
        // Проходимо по збережених ID і відновлюємо статус
        ids.forEach(id => {
            const card = document.getElementById('card-' + id);
            if (card) {
                card.classList.add('is-completed');
                const btn = card.querySelector('.toggle-btn');
                updateCardVisuals(card, btn);
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>