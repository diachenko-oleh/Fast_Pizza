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
                                value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>" pattern="\+38[0-9]{9,10}" inputmode="tel" title="Формат: +38XXXXXXXXXX" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email (необов'язково)</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($client['email'] ?? ''); ?>" placeholder="your@email.com">
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Поточний пароль (для збереження змін)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button type="button" class="btn btn-outline-secondary" id="toggleCurrentPassword">Показати</button>
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Зміна пароля</h5>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новий пароль (залишити порожнім, щоб не змінювати)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Мінімум 6 символів" minlength="6">
                                <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">Показати</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirm" class="form-label">Підтвердіть новий пароль</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" 
                                       placeholder="Мінімум 6 символів" minlength="6">
                                <button type="button" class="btn btn-outline-secondary" id="toggleNewPasswordConfirm">Показати</button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Зберегти зміни</button>
                        </div>
                    </form>

                    <div class="mt-3 d-flex gap-2 align-items-center">
                        <a href="menu_page.php" class="text-decoration-none">← Повернутися на головну</a>
                        <form method="POST" action="../Presenter/auth_actions.php" style="margin-left: auto; margin-bottom: 0;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Вийти з акаунту</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function(){
        function togglePassword(buttonId, inputId) {
            const btn = document.getElementById(buttonId);
            const input = document.getElementById(inputId);
            if (!btn || !input) return;
            btn.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = 'Сховати';
                } else {
                    input.type = 'password';
                    btn.textContent = 'Показати';
                }
            });
        }

        togglePassword('toggleCurrentPassword', 'current_password');
        togglePassword('toggleNewPassword', 'new_password');
        togglePassword('toggleNewPasswordConfirm', 'new_password_confirm');
    })();
</script>