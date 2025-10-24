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
            $code = strtoupper(Security::sanitizeInput($_POST['code']));
            $discount = floatval($_POST['discount']);
            $usageLimit = intval($_POST['usage_limit']);
            $expireDate = $_POST['expire_date'];

            if (empty($code) || $discount <= 0 || $discount > 100 || $usageLimit <= 0 || empty($expireDate)) {
                $error = 'Lütfen tüm alanları doğru şekilde doldurun.';
            } else {
                // Check if code exists
                $stmt = $db->prepare("SELECT id FROM Coupons WHERE code = ?");
                $stmt->execute([$code]);

                if ($stmt->fetch()) {
                    $error = 'Bu kupon kodu zaten mevcut.';
                } else {
                    $couponId = Security::generateUUID();
                    $stmt = $db->prepare("
                        INSERT INTO Coupons (id, code, discount, usage_limit, expire_date) 
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    if ($stmt->execute([$couponId, $code, $discount, $usageLimit, $expireDate])) {
                        $success = 'Kupon başarıyla eklendi.';
                    } else {
                        $error = 'Kupon eklenirken hata oluştu.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $couponId = $_POST['coupon_id'] ?? '';
            $discount = floatval($_POST['discount']);
            $usageLimit = intval($_POST['usage_limit']);
            $expireDate = $_POST['expire_date'];

            if (empty($couponId) || $discount <= 0 || $discount > 100 || $usageLimit <= 0 || empty($expireDate)) {
                $error = 'Geçersiz veri.';
            } else {
                $stmt = $db->prepare("
                    UPDATE Coupons 
                    SET discount = ?, usage_limit = ?, expire_date = ? 
                    WHERE id = ?
                ");

                if ($stmt->execute([$discount, $usageLimit, $expireDate, $couponId])) {
                    $success = 'Kupon başarıyla güncellendi.';
                } else {
                    $error = 'Kupon güncellenirken hata oluştu.';
                }
            }
        } elseif ($action === 'delete') {
            $couponId = $_POST['coupon_id'] ?? '';

            if (!empty($couponId)) {
                $stmt = $db->prepare("DELETE FROM Coupons WHERE id = ?");

                if ($stmt->execute([$couponId])) {
                    $success = 'Kupon başarıyla silindi.';
                } else {
                    $error = 'Kupon silinemedi.';
                }
            }
        }
    }
}

// Get all coupons
$stmt = $db->query("
    SELECT c.*,
    (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as usage_count
    FROM Coupons c
    ORDER BY c.created_at DESC
");
$coupons = $stmt->fetchAll();

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kupon Yönetimi - <?php echo SITE_NAME; ?></title>
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

            <!-- Add Coupon Form -->
            <div class="card">
                <div class="card-header">
                    <h2>🎟️ Yeni Kupon Ekle</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="code">Kupon Kodu</label>
                                <input type="text" name="code" id="code" class="form-control"
                                    placeholder="Örn: SUMMER2025" required>
                            </div>

                            <div class="form-group">
                                <label for="discount">İndirim Oranı (%)</label>
                                <input type="number" name="discount" id="discount" class="form-control"
                                    min="1" max="100" step="0.01" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="usage_limit">Kullanım Limiti</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control"
                                    min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="expire_date">Son Kullanma Tarihi</label>
                                <input type="datetime-local" name="expire_date" id="expire_date" class="form-control"
                                    min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Kupon Ekle</button>
                    </form>
                </div>
            </div>

            <!-- Coupons List -->
            <div class="card">
                <div class="card-header">
                    <h2>Kayıtlı Kuponlar</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($coupons)): ?>
                        <div class="empty-state">
                            <p>Henüz kupon eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kupon Kodu</th>
                                    <th>İndirim</th>
                                    <th>Kullanım</th>
                                    <th>Son Kullanma</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon):
                                    $isExpired = strtotime($coupon['expire_date']) < time();
                                    $isLimitReached = $coupon['usage_count'] >= $coupon['usage_limit'];
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                        <td><?php echo $coupon['discount']; ?>%</td>
                                        <td><?php echo $coupon['usage_count']; ?> / <?php echo $coupon['usage_limit']; ?></td>
                                        <td><?php echo formatDate($coupon['expire_date']); ?></td>
                                        <td>
                                            <?php if ($isExpired): ?>
                                                <span class="badge badge-danger">Süresi Doldu</span>
                                            <?php elseif ($isLimitReached): ?>
                                                <span class="badge badge-warning">Limit Doldu</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editCoupon('<?php echo $coupon['id']; ?>', 
                                                                        <?php echo $coupon['discount']; ?>, 
                                                                        <?php echo $coupon['usage_limit']; ?>, 
                                                                        '<?php echo date('Y-m-d\TH:i', strtotime($coupon['expire_date'])); ?>')"
                                                class="btn btn-warning" style="margin-right: 0.5rem;">
                                                Düzenle
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
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

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Kupon Düzenle</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="coupon_id" id="edit_coupon_id">

                <div class="form-group">
                    <label for="edit_discount">İndirim Oranı (%)</label>
                    <input type="number" name="discount" id="edit_discount" class="form-control"
                        min="1" max="100" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="edit_usage_limit">Kullanım Limiti</label>
                    <input type="number" name="usage_limit" id="edit_usage_limit" class="form-control"
                        min="1" required>
                </div>

                <div class="form-group">
                    <label for="edit_expire_date">Son Kullanma Tarihi</label>
                    <input type="datetime-local" name="expire_date" id="edit_expire_date" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Güncelle</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function editCoupon(id, discount, usageLimit, expireDate) {
            document.getElementById('edit_coupon_id').value = id;
            document.getElementById('edit_discount').value = discount;
            document.getElementById('edit_usage_limit').value = usageLimit;
            document.getElementById('edit_expire_date').value = expireDate;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>