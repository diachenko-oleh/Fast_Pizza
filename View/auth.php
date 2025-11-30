<?php
$page_title = 'Увійти або зареєструватися';
require __DIR__ . '/header.php';

$msg = $_GET['msg'] ?? '';
$tab = $_GET['tab'] ?? 'login';

if (isset($_SESSION['error'])) {
    $msg = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">Увійти</h4>
                </div>
                <div class="card-body">
                    <?php if ($msg): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div id="loginBox">
                        <form method="POST" action="../Presenter/auth_actions.php">
                            <input type="hidden" name="action" value="login">

                            <div class="mb-3">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+38 (0XX) XXX-XX-XX" pattern="\+38[0-9]{9,10}" inputmode="tel" title="Формат: +38XXXXXXXXXX" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="toggleLoginPassword">Показати</button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Увійти</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <small>Немає аккаунта? <a href="#" id="showRegisterLink">Зареєструватися</a></small>
                        </div>
                    </div>

                    <div id="registerBox" style="display: none;">
                        <hr>
                        <h5>Реєстрація</h5>
                        <form method="POST" action="../Presenter/auth_actions.php" id="registerForm">
                            <input type="hidden" name="action" value="register">

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Ім'я та прізвище</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="reg_phone" class="form-label">Телефон</label>
                                <input type="tel" class="form-control" id="reg_phone" name="phone" placeholder="+38 (0XX) XXX-XX-XX" pattern="\+38[0-9]{9,10}" inputmode="tel" title="Формат: +38XXXXXXXXXX" required>
                            </div>

                            <div class="mb-3">
                                <label for="reg_email" class="form-label">Email (необов'язково)</label>
                                <input type="email" class="form-control" id="reg_email" name="email" placeholder="your@email.com">
                            </div>

                            <div class="mb-3">
                                <label for="reg_password" class="form-label">Пароль</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="reg_password" name="password" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary" id="toggleRegPassword">Показати</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reg_password_confirm" class="form-label">Підтвердіть пароль</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="reg_password_confirm" name="password_confirm" minlength="6" required>
                                    <button type="button" class="btn btn-outline-secondary" id="toggleRegPasswordConfirm">Показати</button>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Зареєструватися</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <small>Вже маєте аккаунт? <a href="#" id="showLoginLink">Увійти</a></small>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const loginBox = document.getElementById('loginBox');
    const registerBox = document.getElementById('registerBox');
    const showRegisterLink = document.getElementById('showRegisterLink');
    const showLoginLink = document.getElementById('showLoginLink');

    function showRegister() {
        loginBox.style.display = 'none';
        registerBox.style.display = 'block';
        document.querySelector('.card-header h4').textContent = 'Реєстрація';
    }
    function showLogin() {
        loginBox.style.display = 'block';
        registerBox.style.display = 'none';
        document.querySelector('.card-header h4').textContent = 'Увійти';
    }

    showRegisterLink.addEventListener('click', (e) => { e.preventDefault(); showRegister(); });
    showLoginLink.addEventListener('click', (e) => { e.preventDefault(); showLogin(); });

    const serverTab = <?php echo json_encode($tab); ?>;
    if (serverTab === 'register') {
        showRegister();
    } else {
        showLogin();
    }

    const registerForm = document.getElementById('registerForm');
    registerForm.addEventListener('submit', function(e) {
        const p1 = document.getElementById('reg_password').value;
        const p2 = document.getElementById('reg_password_confirm').value;
        const phone = document.getElementById('reg_phone').value.trim();
        const phoneRegex = /^\+38\d{9,10}$/;

        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            alert('Неправильний формат телефону. Використовуйте +38XXXXXXXXXX');
            return false;
        }

        if (p1 !== p2) {
            e.preventDefault();
            alert('Паролі не збігаються');
            return false;
        }
    });

    // Валідація для форми логіну
    const loginForm = document.querySelector('form[action="../Presenter/auth_actions.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                const phoneVal = phoneInput.value.trim();
                const phoneRegex2 = /^\+38\d{9,10}$/;
                if (!phoneRegex2.test(phoneVal)) {
                    e.preventDefault();
                    alert('Неправильний формат телефону. Використовуйте +38XXXXXXXXXX');
                    return false;
                }
            }
        });
    }

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

    togglePassword('toggleLoginPassword', 'password');
    togglePassword('toggleRegPassword', 'reg_password');
    togglePassword('toggleRegPasswordConfirm', 'reg_password_confirm');
</script>