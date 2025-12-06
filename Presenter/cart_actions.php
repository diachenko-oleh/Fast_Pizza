<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Model/auth.php';

// Обробка AJAX запитів
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    
    // Зміна кількості товару
    if (!empty($_GET['qty'])) {
        $key = $_GET['qty'];
        $action = $_GET['action'] ?? '';
        
        if (isset($_SESSION['cart'][$key])) {
            if ($action === 'inc') {
                $_SESSION['cart'][$key]['qty']++;
            } elseif ($action === 'dec' && $_SESSION['cart'][$key]['qty'] > 1) {
                $_SESSION['cart'][$key]['qty']--;
            }
            echo json_encode(['success' => true, 'action' => 'qty', 'key' => $key]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
        }
        exit;
    }
    
    // Видалення товару
    if (!empty($_GET['remove'])) {
        $key = $_GET['remove'];
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
            echo json_encode(['success' => true, 'action' => 'remove', 'removed' => $key]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Item not found']);
        }
        exit;
    }
    
    // Очищення кошика
    if (!empty($_GET['clear'])) {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'action' => 'clear']);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

// Обробка POST запиту (оформлення замовлення)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Кошик порожній!'); window.location='menu_page.php';</script>";
        exit;
    }
    
    // Перевірка авторизації
    $client = get_current_user_client();
    if (!$client) {
        echo "<script>alert('Будь ласка, авторизуйтесь перед оформленням замовлення'); window.location='login.php';</script>";
        exit;
    }
    
    $client_id = $client['id'];
    $delivery_method = $_POST['delivery_method'] ?? '';
    
    // Валідація обов'язкових полів
    if (empty($delivery_method) || empty($_POST['address'])) {
        echo "<script>alert('Заповніть всі обов\\'язкові поля'); window.location='cart_page.php';</script>";
        exit;
    }
    
    try {
        // Початок транзакції
        $pdo->beginTransaction();
        
        // 1. Створення адреси
        $street = '';
        $house = '';
        $city = 'Черкаси';
        
        if ($delivery_method === 'self') {
            // Самовивіз - парсимо звичайну строку
            $addressParts = array_map('trim', explode(',', $_POST['address']));
            $street = $addressParts[0] ?? '';
            $house = $addressParts[1] ?? '';
            $city = $addressParts[2] ?? 'Черкаси';
        } else {
            // Доставка - парсимо JSON
            $addressData = json_decode($_POST['address'], true);
            
            if (!$addressData || !isset($addressData['street']) || !isset($addressData['house_number'])) {
                throw new Exception('Невірний формат адреси доставки');
            }
            
            $street = trim($addressData['street']);
            $house = trim($addressData['house_number']);
            $city = trim($addressData['city']);
        }
        
        // Перевірка що адреса заповнена
        if (empty($street)) {
            throw new Exception('Не вказана вулиця');
        }
        
        // Вставка адреси
        $stmtAddress = $pdo->prepare("
            INSERT INTO addresses (street, house_number, city) 
            VALUES (:street, :house, :city) 
            RETURNING id
        ");
        
        $stmtAddress->execute([
            ':street' => $street,
            ':house' => $house,
            ':city' => $city
        ]);
        
        $addressResult = $stmtAddress->fetch(PDO::FETCH_ASSOC);
        $address_id = $addressResult['id'] ?? null;
        
        if (!$address_id) {
            throw new Exception('Помилка створення адреси');
        }
        
        // 2. Створення чека
        $courier_id = 1; // Тимчасовий курʼєр
        
        $stmtReceipt = $pdo->prepare("
            INSERT INTO receipt (client_id, address_id, date_time, courier_id)
            VALUES (:client_id, :address_id, NOW(), :courier_id)
            RETURNING id
        ");
        
        $stmtReceipt->execute([
            ':client_id' => $client_id,
            ':address_id' => $address_id,
            ':courier_id' => $courier_id
        ]);
        
        $receiptResult = $stmtReceipt->fetch(PDO::FETCH_ASSOC);
        $receipt_id = $receiptResult['id'] ?? null;
        
        if (!$receipt_id) {
            throw new Exception('Помилка створення чека');
        }
        
        // 3. Додавання товарів до замовлення
        $stmtOrder = $pdo->prepare("
            INSERT INTO orders (receipt_id, product_id, quantity)
            VALUES (:receipt_id, :product_id, :quantity)
        ");
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if (!isset($item['id']) || !isset($item['qty'])) {
                throw new Exception("Товар '$key' має невірний формат");
            }
            
            $product_id = (int)$item['id'];
            $quantity = (int)$item['qty'];
            
            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception("Невірні дані товару: ID=$product_id, qty=$quantity");
            }
            
            $stmtOrder->execute([
                ':receipt_id' => $receipt_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity
            ]);
        }
        
        // Підтверджуємо транзакцію
        $pdo->commit();
        
        // Очищення кошика
        $_SESSION['cart'] = [];
        
        echo "<script>
            alert('Замовлення №$receipt_id успішно оформлено!');
            localStorage.removeItem('cart');
            window.location='menu_page.php';
        </script>";
        
    } catch (PDOException $e) {
        // Відкат транзакції при помилці БД
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $errorMsg = $e->getMessage();
        error_log("Order error (PDO): $errorMsg");
        
        echo "<script>
            alert('Помилка бази даних. Спробуйте ще раз.');
            window.location='cart_page.php';
        </script>";
        
    } catch (Exception $e) {
        // Відкат транзакції при інших помилках
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $errorMsg = $e->getMessage();
        error_log("Order error: $errorMsg");
        
        echo "<script>
            alert('Помилка: " . addslashes($errorMsg) . "');
            window.location='cart_page.php';
        </script>";
    }
    
    exit;
}
?>