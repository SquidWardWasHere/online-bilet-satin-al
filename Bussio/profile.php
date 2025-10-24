<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

Security::requireRole(ROLE_USER);

$db = Database::getInstance()->getConnection();

// Get user data
$stmt = $db->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>👤 Hesap Bilgilerim</h2>
                </div>
                <div class="card-body">
                    <div class="details-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                        <div class="detail-item" style="padding: 1.5rem; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 5px;">
                            <div class="detail-label" style="color: #7f8c8d; margin-bottom: 0.5rem;">Ad Soyad</div>
                            <div class="detail-value" style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </div>
                        </div>

                        <div class="detail-item" style="padding: 1.5rem; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 5px;">
                            <div class="detail-label" style="color: #7f8c8d; margin-bottom: 0.5rem;">E-posta</div>
                            <div class="detail-value" style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </div>

                        <div class="detail-item" style="padding: 1.5rem; background: #e8f5e9; border-left: 4px solid #27ae60; border-radius: 5px;">
                            <div class="detail-label" style="color: #7f8c8d; margin-bottom: 0.5rem;">Bakiye</div>
                            <div class="detail-value" style="font-size: 1.5rem; font-weight: bold; color: #27ae60;">
                                <?php echo formatPrice($user['balance']); ?>
                            </div>
                        </div>

                        <div class="detail-item" style="padding: 1.5rem; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 5px;">
                            <div class="detail-label" style="color: #7f8c8d; margin-bottom: 0.5rem;">Kayıt Tarihi</div>
                            <div class="detail-value" style="font-size: 1.2rem; font-weight: bold; color: #2c3e50;">
                                <?php echo formatDate($user['created_at']); ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; text-align: center;">
                        <a href="my_tickets.php" class="btn btn-primary">Biletlerime Git</a>
                        <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>