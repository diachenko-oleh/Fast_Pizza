<?php
session_start();
require __DIR__ . '/../Model/admin_products.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/adminpage.php');
    exit;
}

$action = $_POST['action'] ?? '';

if (!empty($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    if ($delId > 0) {
        $ok = delete_product($delId);
        $msg = $ok ? 'Товар видалено' : 'Помилка при видаленні';
    } else {
        $msg = 'Невірний id для видалення';
    }

    header('Location: ../View/adminpage.php?msg=' . urlencode($msg));
    exit;
}

if ($action === 'bulk_update') {
    $ids = $_POST['id'] ?? [];
    $names = $_POST['name'] ?? [];
    $prices = $_POST['price'] ?? [];
    $ispizza_flags = $_POST['isPizza'] ?? [];

    $items = [];
    $error = false;
    
    for ($i = 0; $i < count($ids); $i++) {
        $id = intval($ids[$i]);
        if ($id <= 0) continue;

        $price = floatval($prices[$i] ?? 0);
        if ($price <= 0) {
            $error = true;
            break;
        }

        $items[] = [
            'id' => $id,
            'name' => trim($names[$i] ?? ''),
            'price' => $price,
            'isPizza' => in_array($ids[$i], $ispizza_flags) ? 1 : 0
        ];
    }

    if ($error) {
        $msg = 'Ціна не може бути менша або рівна 0';
    } elseif (empty($items)) {
        $msg = 'Немає даних для збереження';
    } else {
        $ok = update_products_bulk($items);
        $msg = $ok ? 'Всі зміни збережені' : 'Помилка при збереженні';
    }

    header('Location: ../View/adminpage.php?msg=' . urlencode($msg));
    exit;
}

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $isPizza = isset($_POST['isPizza']) ? 1 : 0;

    if ($name === '') {
        $msg = 'Назва не може бути порожньою';
    } elseif ($price <= 0) {
        $msg = 'Ціна не може бути менша або рівна 0';
    } else {
        $newId = create_product($name, $price, $isPizza);
        if ($newId === false) {
            $msg = 'Помилка при створенні товару';
        } else {
            $msg = 'Товар додано: ' . htmlspecialchars($name);
        }
    }

    header('Location: ../View/adminpage.php?msg=' . urlencode($msg));
    exit;
}

header('Location: ../View/adminpage.php');
exit;