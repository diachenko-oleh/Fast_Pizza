<?php
session_start();
require __DIR__ . '/../Model/auth.php';

// Нормалізація та валідація номера телефону: допускаємо тільки формат, що починається з +38
function normalize_and_validate_phone($phone) {
    $phone = trim($phone);
    // Видаляємо пробіли, дужки та дефіси, залишаємо цифри та знак +
    $normalized = preg_replace('/[^0-9+]/', '', $phone);
    if (!preg_match('/^\+38\d{9,10}$/', $normalized)) {
        return false;
    }
    return $normalized;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../View/auth.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Валідація
    if (empty($full_name) || empty($phone) || empty($password)) {
        $msg = 'Заповніть обов\'язкові поля (ім\'я, телефон, пароль)';
        header('Location: ../View/auth.php?tab=register&msg=' . urlencode($msg));
        exit;
    }

    // Перевірити формат телефону
    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        $msg = 'Неправильний формат телефону. Використовуйте +38XXXXXXXXXX';
        header('Location: ../View/auth.php?tab=register&msg=' . urlencode($msg));
        exit;
    }

    $phone = $phone_normalized;

    if ($password !== $password_confirm) {
        $msg = 'Паролі не збігаються';
        header('Location: ../View/auth.php?tab=register&msg=' . urlencode($msg));
        exit;
    }

    if (strlen($password) < 6) {
        $msg = 'Пароль повинен мати не менше 6 символів';
        header('Location: ../View/auth.php?tab=register&msg=' . urlencode($msg));
        exit;
    }

    $result = register_client($full_name, $phone, $email, $password);
    if ($result['success']) {
        $client = get_client_by_phone($phone);
        if ($client) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['full_name'];
            header('Location: ../View/index.php?msg=' . urlencode('Реєстрація пройшла успішно. Ви увійшли як ' . $client['full_name']));
        } else {
            header('Location: ../View/auth.php?tab=login&msg=' . urlencode($result['message']));
        }
    } else {
        header('Location: ../View/auth.php?tab=register&msg=' . urlencode($result['message']));
    }
    exit;
}

if ($action === 'login') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
        $msg = 'Заповніть телефон та пароль';
        header('Location: ../View/auth.php?tab=login&msg=' . urlencode($msg));
        exit;
    }

    // Перевірка формату телефону при логіні
    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        $msg = 'Неправильний формат телефону. Використовуйте +38XXXXXXXXXX';
        header('Location: ../View/auth.php?tab=login&msg=' . urlencode($msg));
        exit;
    }
    $phone = $phone_normalized;

    $result = login_client_by_phone($phone, $password);
    if ($result['success']) {
        $_SESSION['client_id'] = $result['client']['id'];
        $_SESSION['client_name'] = $result['client']['full_name'];
        header('Location: ../View/index.php?msg=Ви+успішно+увійшли');
    } else {
        header('Location: ../View/auth.php?tab=login&msg=' . urlencode($result['message']));
    }
    exit;
}

if ($action === 'logout') {
    logout_client();
    header('Location: ../View/index.php?msg=Ви+вийшли');
    exit;
}

if ($action === 'update_profile') {
    $client = get_current_user_client();
    if (!$client) {
        header('Location: ../View/auth.php');
        exit;
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $new_password_confirm = trim($_POST['new_password_confirm'] ?? '');
    $current_password = $_POST['current_password'] ?? '';

    // Валідація
    if (empty($full_name) || empty($phone)) {
        $_SESSION['error'] = 'Ім\'я та телефон обов\'язкові';
        header('Location: ../View/profile.php');
        exit;
    }

    // Перевірка формату телефону при оновленні профілю
    $phone_normalized = normalize_and_validate_phone($phone);
    if ($phone_normalized === false) {
        $_SESSION['error'] = 'Неправильний формат телефону. Використовуйте +38XXXXXXXXXX';
        header('Location: ../View/profile.php');
        exit;
    }
    $phone = $phone_normalized;

    if (empty($current_password)) {
        $_SESSION['error'] = 'Поточний пароль обов\'язковий';
        header('Location: ../View/profile.php');
        exit;
    }

    if (!password_verify($current_password, $client['password_hash'])) {
        $_SESSION['error'] = 'Неправильний поточний пароль';
        header('Location: ../View/profile.php');
        exit;
    }

    if ($new_password && strlen($new_password) < 6) {
        $_SESSION['error'] = 'Новий пароль повинен містити мінімум 6 символів';
        header('Location: ../View/profile.php');
        exit;
    }

    if ($new_password && $new_password !== $new_password_confirm) {
        $_SESSION['error'] = 'Паролі не збігаються';
        header('Location: ../View/profile.php');
        exit;
    }

    require __DIR__ . '/../Model/db.php';
    try {
        $password_hash = $new_password ? password_hash($new_password, PASSWORD_BCRYPT) : $client['password_hash'];
        
        $sql = "UPDATE client SET full_name = :full_name, phone = :phone, email = :email, password_hash = :password_hash WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => empty($email) ? null : $email,
            ':password_hash' => $password_hash,
            ':id' => $client['id']
        ]);
        
        $_SESSION['client_name'] = $full_name;
        $_SESSION['success'] = 'Профіль оновлено успішно';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Помилка при оновленні профілю: ' . $e->getMessage();
    }
    
    header('Location: ../View/profile.php');
    exit;
}

header('Location: ../View/auth.php');
exit;
