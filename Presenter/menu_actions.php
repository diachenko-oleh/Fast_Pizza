<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Невірний запит']);
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'add_pizza':
        if (empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'Немає ID піци']);
            exit;
        }

        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? ('Піца #' . $id);
        $price = floatval($_POST['price'] ?? 0);
        $img = $_POST['img'] ?? '';

        $key = "pizza_" . $id;

        if (!isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key] = [
                'id'    => $id,
                'name'  => $name,
                'price' => $price,
                'img'   => $img,
                'qty'   => 0,
                'type'  => 'pizza'
            ];
        }

        $_SESSION['cart'][$key]['qty']++;

        echo json_encode([
            'success' => true, 
            'message' => "$name додана до кошика",
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'qty'))
        ]);
        exit;

    case 'add_drink':
        if (empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'Немає ID напою']);
            exit;
        }

        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? ('Напій #' . $id);
        $price = floatval($_POST['price'] ?? 0);

        $key = "drink_" . $id;

        if (!isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key] = [
                'id'    => $id,
                'name'  => $name,
                'price' => $price,
                'qty'   => 0,
                'type'  => 'drink'
            ];
        }

        $_SESSION['cart'][$key]['qty']++;

        echo json_encode([
            'success' => true, 
            'message' => "$name доданий до кошика",
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'qty'))
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Невідома дія']);
        exit;
}
?>