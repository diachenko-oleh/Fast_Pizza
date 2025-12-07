<?php
// Захист від повторного підключення
if (!function_exists('register_client')) {

require_once __DIR__ . '/db.php';

/**
 * Реєстрація нового клієнта
 */
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
            ':full_name' => trim($full_name),
            ':phone' => trim($phone),
            ':email' => !empty($email) ? trim($email) : null,
            ':password_hash' => $password_hash
        ]);

        if ($ok) {
            return ['success' => true, 'message' => 'Реєстрація успішна! Тепер можете увійти.'];
        } else {
            return ['success' => false, 'message' => 'Помилка при реєстрації'];
        }
    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Помилка БД: ' . $e->getMessage()];
    }
}

/**
 * Отримання клієнта за телефоном
 */
function get_client_by_phone($phone) {
    global $pdo;
    $sql = "SELECT id, full_name, phone, email, password_hash FROM client WHERE phone = :phone LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phone' => trim($phone)]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Отримання клієнта за email
 */
function get_client_by_email($email) {
    global $pdo;
    $sql = "SELECT id, full_name, phone, email, password_hash FROM client WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => trim($email)]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Вхід користувача через телефон (перевірка пароля)
 */
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

/**
 * Вхід користувача через email (перевірка пароля)
 */
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

/**
 * Отримання поточного користувача з сесії
 */
function get_current_user_client() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['client_id'])) {
        global $pdo;
        $sql = "SELECT id, full_name, phone, email, billing_id FROM client WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => (int)$_SESSION['client_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

/**
 * Перевірка чи користувач авторизований
 */
function is_client_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['client_id']);
}

/**
 * Вихід користувача
 */
function logout_client() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Очищаємо всі дані сесії
    $_SESSION = [];
    
    // Видаляємо cookie сесії
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Знищуємо сесію
    session_destroy();
}

/**
 * Встановлення сесії для клієнта
 */
function set_client_session($client_id, $client_data = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['client_id'] = (int)$client_id;
    
    if ($client_data) {
        $_SESSION['client_name'] = $client_data['full_name'] ?? '';
        $_SESSION['client_phone'] = $client_data['phone'] ?? '';
    }
}

/**
 * Update client's Stripe customer id (if the DB has such a column).
 */
function update_client_stripe_id($client_id, $stripe_customer_id) {
    global $pdo;
    try {
        $sql = "UPDATE client SET billing_id = :stripe WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':stripe' => $stripe_customer_id, ':id' => (int)$client_id]);
    } catch (Exception $e) {
        // If column doesn't exist or other DB error, ignore to avoid breaking registration
        error_log('Failed to save stripe id: ' . $e->getMessage());
        return false;
    }
}

} // end of if (!function_exists('register_client'))
?>