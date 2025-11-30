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
            echo json_encode(['success' => false, 'message' => 'Немає id піци']);
            exit;
        }

        $id = intval($_POST['id']);
        $name = $_POST['name'] ?? ('Піца #' . $id);
        $price = floatval($_POST['price'] ?? 0);
        $img = $_POST['img'] ?? '';

        $key = "pizza_" . $id;

        if (!isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key] = [
                'type' => 'pizza',
                'id'   => $id,
                'name' => $name,
                'price'=> $price,
                'img'  => $img,
                'qty'  => 0
            ];
        }

        $_SESSION['cart'][$key]['qty']++;

        echo json_encode(['success' => true, 'message' => "$name додана до кошика"]);
        exit;

    case 'add_drink':
        if (empty($_POST['drink_key'])) {
            echo json_encode(['success' => false, 'message' => 'Немає ключа напою']);
            exit;
        }

        $dkey = $_POST['drink_key'];
        $name = $_POST['name'] ?? ('Напій ' . $dkey);
        $price = floatval($_POST['price'] ?? 0);

        $key = "drink_" . $dkey;

        if (!isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key] = [
                'type' => 'drink',
                'key'  => $dkey,
                'name' => $name,
                'price'=> $price,
                'qty'  => 0
            ];
        }

        $_SESSION['cart'][$key]['qty']++;

        echo json_encode(['success' => true, 'message' => "$name доданий до кошика"]);
        exit;


    default:
        echo json_encode(['success' => false, 'message' => 'Невідома дія']);
        exit;
}
?>