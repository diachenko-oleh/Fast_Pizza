<?php
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Обработка изменения количества товара
if (!empty($_GET['qty'])) {
    $key = $_GET['qty'];
    $action = $_GET['action'] ?? '';
    
    if (isset($_SESSION['cart'][$key])) {
        if ($action === 'inc') {
            $_SESSION['cart'][$key]['qty']++;
        } elseif ($action === 'dec' && $_SESSION['cart'][$key]['qty'] > 1) {
            $_SESSION['cart'][$key]['qty']--;
        }
    }
    
    // Если это AJAX запрос, отправляем JSON и выходим
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    exit;
}

if (!empty($_GET['remove'])) {
    $rem = $_GET['remove'];
    if (isset($_SESSION['cart'][$rem])) {
        unset($_SESSION['cart'][$rem]);
    }
}

if (!empty($_GET['clear'])) {
    $_SESSION['cart'] = [];
}