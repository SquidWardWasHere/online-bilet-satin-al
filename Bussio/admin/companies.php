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
            $name = Security::sanitizeInput($_POST['name']);

            if (empty($name)) {
                $error = 'Firma adı boş olamaz.';
            } else {
                // Check if company exists
                $stmt = $db->prepare("SELECT id FROM Bus_Company WHERE name = ?");
                $stmt->execute([$name]);

                if ($stmt->fetch()) {
                    $error = 'Bu firma zaten kayıtlı.';
                } else {
                    $companyId = Security::generateUUID();
                    $stmt = $db->prepare("INSERT INTO Bus_Company (id, name, logo_path) VALUES (?, ?, ?)");

                    if ($stmt->execute([$companyId, $name, 'assets/images/default-company.png'])) {
                        $success = 'Firma başarıyla eklendi.';
                    } else {
                        $error = 'Firma eklenirken hata oluştu.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $companyId = $_POST['company_id'] ?? '';
            $name = Security::sanitizeInput($_POST['name']);

            if (empty($name) || empty($companyId)) {
                $error = 'Geçersiz veri.';
            } else {
                $stmt = $db->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");

                if ($stmt->execute([$name, $companyId])) {
                    $success = 'Firma başarıyla güncellendi.';
                } else {
                    $error = 'Firma güncellenirken hata oluştu.';
                }
            }
        } elseif ($action === 'delete') {
            $companyId = $_POST['company_id'] ?? '';

            if (!empty($companyId)) {
                try {
                    $stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
                    $stmt->execute([$companyId]);
                    $success = 'Firma başarıyla silindi.';
                } catch (Exception $e) {
                    $error = 'Firma silinemedi. Bu firmaya ait kayıtlar olabilir.';
                }
            }
        }
    }
}

// Get all companies
$stmt = $db->query("SELECT * FROM Bus_Company ORDER BY name ASC");
$companies = $stmt->fetchAll();

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yönetimi - <?php echo SITE_NAME; ?></title>
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

            <!-- Add Company Form -->
            <div class="card">
                <div class="card-header">
                    <h2>🏢 Yeni Firma Ekle</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Firma Adı</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    placeholder="Örn: Metro Turizm" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Firma Ekle</button>
                    </form>
                </div>
            </div>

            <!-- Companies List -->
            <div class="card">
                <div class="card-header">
                    <h2>Kayıtlı Firmalar</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($companies)): ?>
                        <div class="empty-state">
                            <p>Henüz firma eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Firma Adı</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                                        <td><?php echo formatDate($company['created_at']); ?></td>
                                        <td>
                                            <button onclick="editCompany('<?php echo $company['id']; ?>', '<?php echo htmlspecialchars($company['name']); ?>')"
                                                class="btn btn-warning" style="margin-right: 0.5rem;">
                                                Düzenle
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
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
                <h2>Firma Düzenle</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="company_id" id="edit_company_id">

                <div class="form-group">
                    <label for="edit_name">Firma Adı</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Güncelle</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function editCompany(id, name) {
            document.getElementById('edit_company_id').value = id;
            document.getElementById('edit_name').value = name;
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