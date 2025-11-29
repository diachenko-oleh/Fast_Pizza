<?php
require __DIR__ . '/../Model/auth.php';
$page_title = 'Мій профіль';
require __DIR__ . '/header.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: auth.php');
    exit;
}

$client = get_current_user_client();
if (!$client) {
    header('Location: auth.php');
    exit;
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">Мій профіль</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="../Presenter/auth_actions.php">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Ім'я та прізвище</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($client['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email (необов'язково)</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" placeholder="your@email.com">
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Поточний пароль (для збереження змін)</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <h5 class="mt-4 mb-3">Зміна пароля</h5>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новий пароль (залишити порожнім, щоб не змінювати)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Мінімум 6 символів" minlength="6">
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirm" class="form-label">Підтвердіть новий пароль</label>
                            <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" 
                                   placeholder="Мінімум 6 символів" minlength="6">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Зберегти зміни</button>
                        </div>
                    </form>

                    <div class="mt-3">
                        <a href="index.php" class="text-decoration-none">← Повернутися на головну</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>