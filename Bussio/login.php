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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
        $email = Security::sanitizeInput($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = 'Lütfen tüm alanları doldurun.';
        } elseif (!Security::validateEmail($email)) {
            $error = 'Geçersiz e-posta adresi.';
        } else {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM User WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && Security::verifyPassword($password, $user['password'])) {
                // Regenerate session to prevent session fixation
                Security::regenerateSession();

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];

                // Redirect based on role
                if ($user['role'] === ROLE_ADMIN) {
                    header('Location: admin/index.php');
                } elseif ($user['role'] === ROLE_COMPANY_ADMIN) {
                    header('Location: company_admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'E-posta veya şifre hatalı.';
                // Log failed login attempt
                error_log("Failed login attempt for email: $email");
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
    <title>Giriş Yap - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🚌 Bussio</h1>
                <p>Hesabınıza giriş yapın</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control"
                        placeholder="ornek@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="********" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Giriş Yap
                </button>
            </form>

            <div class="auth-links">
                <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
                <p><a href="index.php">Ana Sayfaya Dön</a></p>
            </div>
        </div>
    </div>
</body>

</html>