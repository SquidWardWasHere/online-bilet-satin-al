<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

Security::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $fullName = Security::sanitizeInput($_POST['full_name']);
            $email = Security::sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $companyId = $_POST['company_id'] ?? '';

            if (empty($fullName) || empty($email) || empty($password) || empty($companyId)) {
                $error = 'Tüm alanları doldurun.';
            } elseif (!Security::validateEmail($email)) {
                $error = 'Geçersiz e-posta adresi.';
            } elseif (!Security::validatePassword($password)) {
                $error = 'Şifre güvenlik gereksinimlerini karşılamıyor.';
            } else {
                // Check if email exists
                $stmt = $db->prepare("SELECT id FROM User WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $error = 'Bu e-posta adresi zaten kayıtlı.';
                } else {
                    $userId = Security::generateUUID();
                    $hashedPassword = Security::hashPassword($password);

                    $stmt = $db->prepare("
                        INSERT INTO User (id, full_name, email, role, password, company_id, balance) 
                        VALUES (?, ?, ?, ?, ?, ?, 0)
                    ");

                    if ($stmt->execute([$userId, $fullName, $email, ROLE_COMPANY_ADMIN, $hashedPassword, $companyId])) {
                        $success = 'Firma Admin başarıyla eklendi.';
                    } else {
                        $error = 'Firma Admin eklenirken hata oluştu.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $userId = $_POST['user_id'] ?? '';

            if (!empty($userId)) {
                $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = ?");

                if ($stmt->execute([$userId, ROLE_COMPANY_ADMIN])) {
                    $success = 'Firma Admin başarıyla silindi.';
                } else {
                    $error = 'Firma Admin silinemedi.';
                }
            }
        }
    }
}

// Get all company admins
$stmt = $db->query("
    SELECT u.*, bc.name as company_name
    FROM User u
    LEFT JOIN Bus_Company bc ON u.company_id = bc.id
    WHERE u.role = '" . ROLE_COMPANY_ADMIN . "'
    ORDER BY u.created_at DESC
");
$companyAdmins = $stmt->fetchAll();

// Get companies for dropdown
$stmt = $db->query("SELECT * FROM Bus_Company ORDER BY name ASC");
$companies = $stmt->fetchAll();

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Yönetimi - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <div class="header-content">
            <a href="/admin/index.php" class="logo">🚌 Bussio Admin</a>
            <nav>
                <ul>
                    <li><a href="/admin/index.php">Dashboard</a></li>
                    <li><a href="/admin/companies.php">Firmalar</a></li>
                    <li><a href="/admin/company_admins.php">Firma Adminleri</a></li>
                    <li><a href="/admin/coupons.php">Kuponlar</a></li>
                    <li><a href="/index.php">Ana Sayfa</a></li>
                    <li><a href="/logout.php">Çıkış</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Company Admin Form -->
            <div class="card">
                <div class="card-header">
                    <h2>👤 Yeni Firma Admin Ekle</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Ad Soyad</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="email">E-posta</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Şifre</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                                <small style="color: #7f8c8d;">En az 8 karakter, 1 büyük, 1 küçük harf, 1 rakam ve 1 özel karakter</small>
                            </div>

                            <div class="form-group">
                                <label for="company_id">Firma</label>
                                <select name="company_id" id="company_id" class="form-control" required>
                                    <option value="">Firma Seçin</option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?php echo $company['id']; ?>">
                                            <?php echo htmlspecialchars($company['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Firma Admin Ekle</button>
                    </form>
                </div>
            </div>

            <!-- Company Admins List -->
            <div class="card">
                <div class="card-header">
                    <h2>Kayıtlı Firma Adminleri</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($companyAdmins)): ?>
                        <div class="empty-state">
                            <p>Henüz firma admin eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Firma</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companyAdmins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['company_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($admin['created_at']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Bu firma admini silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn btn-danger">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>