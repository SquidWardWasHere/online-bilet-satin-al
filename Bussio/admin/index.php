<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

Security::requireRole(ROLE_ADMIN);

$db = Database::getInstance()->getConnection();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) FROM User WHERE role = 'user'");
$totalUsers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM Bus_Company");
$totalCompanies = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM Trips");
$totalTrips = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM Tickets WHERE status = 'active'");
$activeTickets = $stmt->fetchColumn();

$stmt = $db->query("SELECT SUM(total_price) FROM Tickets WHERE status = 'active'");
$totalRevenue = $stmt->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
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
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>📊 Yönetim Paneli</h2>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>👥 Toplam Kullanıcı</h3>
                    <div class="value"><?php echo $totalUsers; ?></div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <h3>🏢 Otobüs Firmaları</h3>
                    <div class="value"><?php echo $totalCompanies; ?></div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #f39c12, #d68910);">
                    <h3>🚌 Toplam Sefer</h3>
                    <div class="value"><?php echo $totalTrips; ?></div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <h3>🎫 Aktif Biletler</h3>
                    <div class="value"><?php echo $activeTickets; ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Hızlı Erişim</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <a href="companies.php" class="btn btn-primary btn-block">
                            🏢 Firma Yönetimi
                        </a>
                        <a href="company_admins.php" class="btn btn-success btn-block">
                            👤 Firma Admin Yönetimi
                        </a>
                        <a href="coupons.php" class="btn btn-warning btn-block">
                            🎟️ Kupon Yönetimi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>