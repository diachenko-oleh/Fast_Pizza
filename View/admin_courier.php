<?php
require_once __DIR__ . '/../Model/auth.php';
require_once __DIR__ . '/../Model/db.php';

// Получаем всех курьеров (через PDO)
try {
    $stmt = $pdo->query("SELECT id, full_name, phone, status FROM courier ORDER BY full_name");
    $couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Failed to fetch couriers: ' . $e->getMessage());
    $couriers = [];
}

// Получаем все чеки с информацией о клиентах и адресах
try {
    $receiptsQuery = "
        SELECT 
            r.id,
            r.client_id,
            r.courier_id,
            r.date_time,
            r.comment,
            c.full_name as client_name,
            c.phone as client_phone,
            a.street,
            a.house_number,
            a.city
        FROM receipt r
        LEFT JOIN client c ON r.client_id = c.id
        LEFT JOIN addresses a ON r.address_id = a.id
        WHERE r.is_complete = FALSE
        ORDER BY r.date_time DESC
    ";
    $stmt = $pdo->query($receiptsQuery);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Failed to fetch receipts: ' . $e->getMessage());
    $receipts = [];
}

// Обработка изменения статуса курьера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $courierId = intval($_POST['courier_id']);

        try {
            // Read current status and invert it
            $stmtCur = $pdo->prepare("SELECT status FROM courier WHERE id = :id");
            $stmtCur->execute([':id' => $courierId]);
            $row = $stmtCur->fetch(PDO::FETCH_ASSOC);
            $curStatus = $row['status'] ?? null;
            // Use helper to interpret NULL/'t' etc.
            $isFree = courier_is_free($curStatus);
            $newStatus = !$isFree;

            $update = $pdo->prepare("UPDATE courier SET status = :status::boolean WHERE id = :id");
            $statusParam = $newStatus ? 't' : 'f';
            $update->execute([':status' => $statusParam, ':id' => $courierId]);
        } catch (Exception $e) {
            error_log('Failed to toggle courier status: ' . $e->getMessage());
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if ($_POST['action'] === 'assign_courier') {
        $receiptId = intval($_POST['receipt_id']);
        $courierId = $_POST['courier_id'] ? intval($_POST['courier_id']) : null;

        try {
            // Fetch previous assigned courier for this receipt
            $stmtPrev = $pdo->prepare("SELECT courier_id FROM receipt WHERE id = :id");
            $stmtPrev->execute([':id' => $receiptId]);
            $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            $prevCourier = $prev['courier_id'] ?? null;

            if ($courierId) {
                // Assign new courier to receipt
                $update = $pdo->prepare("UPDATE receipt SET courier_id = :courier_id WHERE id = :id");
                $update->execute([':courier_id' => $courierId, ':id' => $receiptId]);

                // Mark assigned courier as busy (status = false)
                $updCourier = $pdo->prepare("UPDATE courier SET status = :status::boolean WHERE id = :id");
                $updCourier->execute([':status' => 'f', ':id' => $courierId]);

                // If there was a previously assigned different courier, check whether that courier
                // still has other receipts; if none, mark them free
                if ($prevCourier && $prevCourier != $courierId) {
                    // count only active (not complete) receipts for that courier
                    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM receipt WHERE courier_id = :cid AND is_complete = FALSE");
                    $check->execute([':cid' => $prevCourier]);
                    $cnt = (int)$check->fetchColumn();
                    if ($cnt === 0) {
                        $freePrev = $pdo->prepare("UPDATE courier SET status = :status::boolean WHERE id = :id");
                        $freePrev->execute([':status' => 't', ':id' => $prevCourier]);
                    }
                }
            } else {
                // Unassign courier from receipt
                $update = $pdo->prepare("UPDATE receipt SET courier_id = NULL WHERE id = :id");
                $update->execute([':id' => $receiptId]);

                // If there was a previous courier, check whether they have other receipts; if none, mark free
                if ($prevCourier) {
                    // count only other active receipts for that courier
                    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM receipt WHERE courier_id = :cid AND id != :rid AND is_complete = FALSE");
                    $check->execute([':cid' => $prevCourier, ':rid' => $receiptId]);
                    $cnt = (int)$check->fetchColumn();
                    if ($cnt === 0) {
                        $freePrev = $pdo->prepare("UPDATE courier SET status = :status::boolean WHERE id = :id");
                        $freePrev->execute([':status' => 't', ':id' => $prevCourier]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Failed to assign courier: ' . $e->getMessage());
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($_POST['action'] === 'toggle_complete') {
        $receiptId = intval($_POST['receipt_id']);
        $setComplete = isset($_POST['is_complete']) && ($_POST['is_complete'] === '1');

        try {
            // fetch previous courier
            $stmtPrev = $pdo->prepare("SELECT courier_id FROM receipt WHERE id = :id");
            $stmtPrev->execute([':id' => $receiptId]);
            $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            $prevCourier = $prev['courier_id'] ?? null;

            if ($setComplete) {
                // mark receipt complete and unassign courier
                $upd = $pdo->prepare("UPDATE receipt SET is_complete = TRUE, courier_id = NULL WHERE id = :id");
                $upd->execute([':id' => $receiptId]);

                if ($prevCourier) {
                    // if courier has no other active receipts, mark free
                    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM receipt WHERE courier_id = :cid AND is_complete = FALSE");
                    $check->execute([':cid' => $prevCourier]);
                    $cnt = (int)$check->fetchColumn();
                    if ($cnt === 0) {
                        $freePrev = $pdo->prepare("UPDATE courier SET status = :status::boolean WHERE id = :id");
                        $freePrev->execute([':status' => 't', ':id' => $prevCourier]);
                    }
                }
            } else {
                // mark not complete
                $upd = $pdo->prepare("UPDATE receipt SET is_complete = FALSE WHERE id = :id");
                $upd->execute([':id' => $receiptId]);
            }

        } catch (Exception $e) {
            error_log('Failed to toggle receipt complete: ' . $e->getMessage());
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Получаем заказы для каждого чека
function getOrdersForReceipt($pdo, $receiptId) {
    try {
        $stmt = $pdo->prepare("SELECT o.quantity, p.name, p.price FROM orders o JOIN products p ON o.product_id = p.id WHERE o.receipt_id = :rid");
        $stmt->execute([':rid' => $receiptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed to fetch orders for receipt ' . $receiptId . ': ' . $e->getMessage());
        return [];
    }
}

// Helper: normalize courier status; treat NULL as free (not busy)
function courier_is_free($status) {
    if (is_null($status)) return true;
    if ($status === true) return true;
    if ($status === 't' || $status === '1' || $status === 1) return true;
    return false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Керування кур'єрами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .courier-card {
            transition: all 0.3s ease;
        }
        .courier-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .receipt-item {
            border-left: 4px solid #dee2e6;
            transition: border-color 0.3s;
        }
        .receipt-item:hover {
            border-left-color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid d-flex align-items-center">
            <span class="navbar-brand mb-0 h1"> Керування кур'єрами </span>
            <a href="admin_page.php" class="btn btn-sm btn-outline-light ms-auto">Повернутись в адмінпанель</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Секция курьеров -->
            <div class="col-md-4 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-people-fill"></i> Список кур'єрів</h5>
                    </div>
                    <div class="card-body" style="max-height: 85vh; overflow-y: auto;">
                        <?php if ($couriers): ?>
                            <?php foreach ($couriers as $courier): ?>
                                <div class="card courier-card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($courier['full_name']) ?></h6>
                                        <p class="card-text mb-2">
                                            <i class="bi bi-phone"></i> <?= htmlspecialchars($courier['phone']) ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <?php $isFree = courier_is_free($courier['status']); ?>
                                            <?php if ($isFree): ?>
                                                <span class="badge bg-success status-badge">
                                                    <i class="bi bi-check-circle"></i> Вільний
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger status-badge">
                                                    <i class="bi bi-x-circle"></i> Зайнятий
                                                </span>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="courier_id" value="<?= $courier['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Нет курьеров в базе данных</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Секция заказов -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Замовлення (чеки)</h5>
                    </div>
                    <div class="card-body" style="max-height: 85vh; overflow-y: auto;">
                        <?php if ($receipts): ?>
                            <?php foreach ($receipts as $receipt): ?>
                                <?php 
                                    $orders = getOrdersForReceipt($pdo, $receipt['id']);
                                    $total = 0;
                                    if ($orders) {
                                        foreach ($orders as $order) {
                                            $total += $order['price'] * $order['quantity'];
                                        }
                                    }
                                ?>
                                <div class="card receipt-item mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="card-title">
                                                    Чек #<?= $receipt['id'] ?> 
                                                    <span class="badge bg-info"><?= date('d.m.Y H:i', strtotime($receipt['date_time'])) ?></span>
                                                </h6>
                                                
                                                <p class="mb-1">
                                                    <strong><i class="bi bi-person"></i> Клієнт:</strong> 
                                                    <?= htmlspecialchars($receipt['client_name']) ?> 
                                                    (<?= htmlspecialchars($receipt['client_phone']) ?>)
                                                </p>
                                                
                                                <?php if ($receipt['street']): ?>
                                                    <p class="mb-1">
                                                        <strong><i class="bi bi-geo-alt"></i> Адреса:</strong> 
                                                        <?= htmlspecialchars($receipt['city']) ?>, 
                                                        <?= htmlspecialchars($receipt['street']) ?>, 
                                                        <?= htmlspecialchars($receipt['house_number']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if ($orders): ?>
                                                    <div class="mt-2">
                                                        <strong>Товари:</strong>
                                                        <ul class="mb-1">
                                                            <?php foreach ($orders as $order): ?>
                                                                <li>
                                                                    <?= htmlspecialchars($order['name']) ?> 
                                                                    x<?= $order['quantity'] ?> 
                                                                    (<?= number_format($order['price'], 2) ?> грн)
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                        <strong>Сума: <?= number_format($total, 2) ?> грн</strong>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($receipt['comment']): ?>
                                                    <p class="mb-1 text-muted">
                                                        <i class="bi bi-chat-left-text"></i> <?= htmlspecialchars($receipt['comment']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <form method="POST" style="margin-bottom:8px;">
                                                    <input type="hidden" name="action" value="toggle_complete">
                                                    <input type="hidden" name="receipt_id" value="<?= $receipt['id'] ?>">
                                                    <input type="hidden" name="is_complete" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success w-100">Позначити виконаним</button>
                                                </form>

                                                <form method="POST">
                                                    <input type="hidden" name="action" value="assign_courier">
                                                    <input type="hidden" name="receipt_id" value="<?= $receipt['id'] ?>">
                                                    
                                                    <label class="form-label"><strong>Кур'єр:</strong></label>
                                                    <select name="courier_id" class="form-select form-select-sm mb-2">
                                                        <option value="">Не назначен</option>
                                                        <?php foreach ($couriers as $courier): ?>
                                                            <option 
                                                                value="<?= $courier['id'] ?>"
                                                                <?= $receipt['courier_id'] == $courier['id'] ? 'selected' : '' ?>
                                                            >
                                                                <?= htmlspecialchars($courier['full_name']) ?>
                                                                <?= courier_is_free($courier['status']) ? '✓' : '✗' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    
                                                    <button type="submit" class="btn btn-sm btn-primary w-100"> Назначити
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Поки нема замовленнь на доставку</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>