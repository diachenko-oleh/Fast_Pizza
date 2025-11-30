<?php
session_start();
require __DIR__ . '/../Model/auth.php';

// Єдині змінні для шляхів
$msg_auth_path = 'Location: ../View/auth.php';
$msg_profile_path = 'Location: ../View/profile.php';

// Нормалізація та валідація номера телефону
function normalize_and_validate_phone($phone) {
    $phone = trim($phone);
    $normalized = preg_replace('/[^0-9+]/', '', $phone);

    return preg_match('/^\+38\d{9,10}$/', $normalized) ? $normalized : false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header($msg_auth_path);
    exit;
}

$action = $_POST['action'] ?? '';

/* ===========================  REGISTER  =========================== */

if ($action === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($full_name) || empty($phone) || empty($password)) {
        header($msg_auth_path . '?tab=register&msg=' . urlencode('Заповніть обов\'язкові поля'));
        exit;
    }

    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        header($msg_auth_path . '?tab=register&msg=' . urlencode('Неправильний формат телефону'));
        exit;
    }
    $phone = $phone_normalized;

    if ($password !== $password_confirm) {
        header($msg_auth_path . '?tab=register&msg=' . urlencode('Паролі не збігаються'));
        exit;
    }

    if (strlen($password) < 6) {
        header($msg_auth_path . '?tab=register&msg=' . urlencode('Пароль повинен мати не менше 6 символів'));
        exit;
    }

    $result = register_client($full_name, $phone, $email, $password);

    if ($result['success']) {
        $client = get_client_by_phone($phone);
        if ($client) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['full_name'];
            header('Location: ../View/index.php?msg=' . urlencode('Реєстрація успішна'));
        } else {
            header($msg_auth_path . '?tab=login&msg=' . urlencode('Помилка входу після реєстрації'));
        }
    } else {
        header($msg_auth_path . '?tab=register&msg=' . urlencode($result['message']));
    }
    exit;
}

/* ===========================  LOGIN  =========================== */

if ($action === 'login') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
        header($msg_auth_path . '?tab=login&msg=' . urlencode('Заповніть телефон та пароль'));
        exit;
    }

    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        header($msg_auth_path . '?tab=login&msg=' . urlencode('Неправильний формат телефону'));
        exit;
    }

    $phone = $phone_normalized;

    $result = login_client_by_phone($phone, $password);

    if ($result['success']) {
        $_SESSION['client_id'] = $result['client']['id'];
        $_SESSION['client_name'] = $result['client']['full_name'];
        header('Location: ../View/index.php?msg=' . urlencode('Ви успішно увійшли'));
    } else {
        header($msg_auth_path . '?tab=login&msg=' . urlencode($result['message']));
    }
    exit;
}

/* ===========================  LOGOUT  =========================== */

if ($action === 'logout') {
    logout_client();
    header('Location: ../View/index.php?msg=' . urlencode('Ви вийшли'));
    exit;
}

/* ===========================  UPDATE PROFILE  =========================== */

if ($action === 'update_profile') {
    $client = get_current_user_client();
    if (!$client) {
        header($msg_auth_path);
        exit;
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $new_password_confirm = trim($_POST['new_password_confirm'] ?? '');
    $current_password = $_POST['current_password'] ?? '';

    if (empty($full_name) || empty($phone)) {
        $_SESSION['error'] = 'Ім\'я та телефон обов\'язкові';
        header($msg_profile_path);
        exit;
    }

    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        $_SESSION['error'] = 'Неправильний формат телефону';
        header($msg_profile_path);
        exit;
    }

    if (empty($current_password)) {
        $_SESSION['error'] = 'Поточний пароль обов\'язковий';
        header($msg_profile_path);
        exit;
    }

    if (!password_verify($current_password, $client['password_hash'])) {
        $_SESSION['error'] = 'Неправильний поточний пароль';
        header($msg_profile_path);
        exit;
    }

    if ($new_password && strlen($new_password) < 6) {
        $_SESSION['error'] = 'Новий пароль занадто короткий';
        header($msg_profile_path);
        exit;
    }

    if ($new_password && $new_password !== $new_password_confirm) {
        $_SESSION['error'] = 'Паролі не збігаються';
        header($msg_profile_path);
        exit;
    }

    require __DIR__ . '/../Model/db.php';

    try {
        $password_hash = $new_password ? password_hash($new_password, PASSWORD_BCRYPT) : $client['password_hash'];

        $sql = "UPDATE client 
                SET full_name = :full_name, phone = :phone, email = :email, password_hash = :password_hash 
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone_normalized,
            ':email' => empty($email) ? null : $email,
            ':password_hash' => $password_hash,
            ':id' => $client['id']
        ]);

        $_SESSION['client_name'] = $full_name;
        $_SESSION['success'] = 'Профіль оновлено';

    } catch (Exception $e) {
        $_SESSION['error'] = 'Помилка: ' . $e->getMessage();
    }

    header($msg_profile_path);
    exit;
}


header($msg_auth_path);
exit;
