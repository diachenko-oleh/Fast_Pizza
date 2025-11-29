<?php
require __DIR__ . '/db.php';

//Рeєстрація нового клієнта
function register_client($full_name, $phone, $email, $password) {
    global $pdo;

    // Перевірка: чи телефон вже існує
    $sql = "SELECT id FROM client WHERE phone = :phone LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phone' => $phone]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Телефон вже зареєстрований'];
    }

    // Якщо email вказаний, перевіримо його унікальність
    if (!empty($email)) {
        $sql = "SELECT id FROM client WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email вже зареєстрований'];
        }
    }

    // Хешування пароля
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Вставка нового користувача
    $sql = "INSERT INTO client (full_name, phone, email, password_hash) 
            VALUES (:full_name, :phone, :email, :password_hash)";
    $stmt = $pdo->prepare($sql);
    try {
        $ok = $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => $email ?: null,
            ':password_hash' => $password_hash
        ]);

        if ($ok) {
            return ['success' => true, 'message' => 'Реєстрація успішна! Тепер можете увійти.'];
        } else {
            return ['success' => false, 'message' => 'Помилка при реєстрації'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Помилка БД'];
    }
}

//Отримання клієнта за телефоном
function get_client_by_phone($phone) {
    global $pdo;
    $sql = "SELECT id, full_name, phone, password_hash FROM client WHERE phone = :phone LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phone' => $phone]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//Отримання клієнта за email
function get_client_by_email($email) {
    global $pdo;
    $sql = "SELECT id, full_name, email, password_hash FROM client WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//Вхід користувача через телефон (перевірка пароля)
function login_client_by_phone($phone, $password) {
    $client = get_client_by_phone($phone);
    if (!$client) {
        return ['success' => false, 'message' => 'Телефон не знайдено'];
    }

    if (!password_verify($password, $client['password_hash'])) {
        return ['success' => false, 'message' => 'Невірний пароль'];
    }

    return ['success' => true, 'client' => $client];
}

//Вхід користувача через email (перевірка пароля)
function login_client_by_email($email, $password) {
    $client = get_client_by_email($email);
    if (!$client) {
        return ['success' => false, 'message' => 'Email не знайдено'];
    }

    if (!password_verify($password, $client['password_hash'])) {
        return ['success' => false, 'message' => 'Невірний пароль'];
    }

    return ['success' => true, 'client' => $client];
}

//Отримання поточного користувача з сесії
function get_current_user_client() {
    if (isset($_SESSION['client_id'])) {
        global $pdo;
        $sql = "SELECT id, full_name, phone, email, password_hash FROM client WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $_SESSION['client_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function logout_client() {
    session_destroy();
}