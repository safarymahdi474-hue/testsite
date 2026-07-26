<?php
// index.php - صفحه ورود
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

// اگر کاربر قبلاً وارد شده باشد
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید';
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت - PowerAdmin</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">P</div>
            <h2>خوش آمدید</h2>
            <p>وارد پنل مدیریت PowerAdmin شوید</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">نام کاربری</label>
                <div class="input-group">
                    <i class="bi bi-person"></i>
                    <input type="text" id="username" name="username"
                           placeholder="نام کاربری خود را وارد کنید" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">رمز عبور</label>
                <div class="input-group">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="رمز عبور خود را وارد کنید" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>مرا به خاطر بسپار</span>
                </label>
                <a href="#" class="forgot-link">فراموشی رمز عبور؟</a>
            </div>

            <button type="submit" class="login-btn">
                <i class="bi bi-box-arrow-in-right"></i>
                ورود به پنل
            </button>
        </form>

        <div class="login-footer">
            <p>نسخه 1.0.0 | PowerAdmin © 2026</p>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
<script src="assets/js/login.js"></script>
</body>
</html>