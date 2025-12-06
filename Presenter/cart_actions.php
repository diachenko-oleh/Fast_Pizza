<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
require __DIR__ . '/../Model/db.php'; // подключение PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Кошик порожній!');</script>";
        exit;
    }

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $payment = $_POST['payment'];
    $delivery_method = $_POST['delivery_method'];
    $delivery_time = $_POST['delivery_time'];
    $comments = $_POST['comments'] ?? '';

    // ---------------------------------------
    // 1. Если клиент авторизован — берем ID
    // ---------------------------------------
    $client = get_current_user_client();
    if (!$client) {
        echo "<script>alert('Авторизуйтесь перед замовленням');</script>";
        exit;
    }
    $client_id = $client['id'];

    // ---------------------------------------
    // 2. Адреса (самовывоз / доставка)
    // ---------------------------------------
    $address_id = null;

    if ($delivery_method === "self") {
        // адреса самовывоза — обычная строка
        $addr = explode(",", $_POST['address']);
        $street = trim($addr[0]);
        $house = trim($addr[1] ?? '');
        $city = "Черкаси";

        $q = $pdo->prepare("INSERT INTO addresses(street, house_number, city) VALUES (?,?,?) RETURNING id");
        $q->execute([$street, $house, $city]);
        $address_id = $q->fetchColumn();

    } else {
        // доставка — JSON
        $json = json_decode($_POST['address'], true);

        $q = $pdo->prepare("INSERT INTO addresses(street, house_number, city) VALUES (?,?,?) RETURNING id");
        $q->execute([$json['street'], $json['house_number'], $json['city']]);
        $address_id = $q->fetchColumn();
    }

    // ---------------------------------------
    // 3. Создаем новый чек (receipt)
    // ---------------------------------------
    // курьера пока ставим 1
    $courier_id = 1;

    $q = $pdo->prepare("
        INSERT INTO receipt (client_id, address_id, date_time, courier_id)
        VALUES (?, ?, NOW(), ?)
        RETURNING id
    ");
    $q->execute([$client_id, $address_id, $courier_id]);
    $receipt_id = $q->fetchColumn();

    // ---------------------------------------
    // 4. Создаем записи orders
    // ---------------------------------------
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['id'];  // ОБЯЗАТЕЛЬНО ДОБАВЬ id продукта в корзину!
        $qty = (int)$item['qty'];

        $q = $pdo->prepare("
            INSERT INTO orders (receipt_id, product_id, quantity)
            VALUES (?, ?, ?)
        ");
        $q->execute([$receipt_id, $product_id, $qty]);
    }

    // ---------------------------------------
    // 5. Очищаем корзину
    // ---------------------------------------
    $_SESSION['cart'] = [];

    echo "<script>alert('Замовлення успішно оформлено!'); window.location='menu_page.php';</script>";
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