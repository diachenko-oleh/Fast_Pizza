<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => 'qty',
        'key' => $key,
        'cart_keys' => array_keys($_SESSION['cart'])
    ]);
    exit;
}

if (!empty($_GET['remove'])) {
    $rem = $_GET['remove'];
    if (isset($_SESSION['cart'][$rem])) {
        unset($_SESSION['cart'][$rem]);
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => 'remove',
        'removed' => $rem,
        'cart_keys' => array_keys($_SESSION['cart'])
    ]);
    exit;
}

if (!empty($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => 'clear',
        'cart_keys' => array_keys($_SESSION['cart'])
    ]);
    exit;
}