<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';

// Redirect if already logged in
if (Security::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
        $fullName = Security::sanitizeInput($_POST['full_name']);
        $email = Security::sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validation
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Lütfen tüm alanları doldurun.';
        } elseif (!Security::validateEmail($email)) {
            $error = 'Geçersiz e-posta adresi.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Şifreler eşleşmiyor.';
        } elseif (!Security::validatePassword($password)) {
            $error = 'Şifre en az 8 karakter olmalı ve en az 1 büyük harf, 1 küçük harf, 1 rakam ve 1 özel karakter içermelidir.';
        } else {
            $db = Database::getInstance()->getConnection();

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM User WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Bu e-posta adresi zaten kayıtlı.';
            } else {
                // Create new user
                $userId = Security::generateUUID();
                $hashedPassword = Security::hashPassword($password);

                $stmt = $db->prepare("
                    INSERT INTO User (id, full_name, email, role, password, balance) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([$userId, $fullName, $email, ROLE_USER, $hashedPassword, DEFAULT_USER_BALANCE])) {
                    $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                }
            }
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🚌 Bussio</h1>
                <p>Yeni hesap oluşturun</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-group">
                    <label for="full_name">Ad Soyad</label>
                    <input type="text" name="full_name" id="full_name" class="form-control"
                        placeholder="Adınız Soyadınız" required>
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control"
                        placeholder="ornek@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="********" required>
                    <small style="color: #7f8c8d;">
                        En az 8 karakter, 1 büyük harf, 1 küçük harf, 1 rakam ve 1 özel karakter (@$!%*?&)
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Şifre Tekrar</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                        placeholder="********" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Kayıt Ol
                </button>
            </form>

            <div class="auth-links">
                <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
                <p><a href="index.php">Ana Sayfaya Dön</a></p>
            </div>
        </div>
    </div>
</body>

</html>