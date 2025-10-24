<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

Security::requireRole(ROLE_COMPANY_ADMIN);

$db = Database::getInstance()->getConnection();

// Get statistics for the company
$stmt = $db->prepare("SELECT COUNT(*) FROM Trips WHERE company_id = ?");
$stmt->execute([$_SESSION['company_id']]);
$totalTrips = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM Tickets tk
    INNER JOIN Trips t ON tk.trip_id = t.id
    WHERE t.company_id = ? AND tk.status = 'active'
");
$stmt->execute([$_SESSION['company_id']]);
$activeTickets = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT SUM(tk.total_price) FROM Tickets tk
    INNER JOIN Trips t ON tk.trip_id = t.id
    WHERE t.company_id = ? AND tk.status = 'active'
");
$stmt->execute([$_SESSION['company_id']]);
$totalRevenue = $stmt->fetchColumn() ?? 0;

// Get company name
$stmt = $db->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$stmt->execute([$_SESSION['company_id']]);
$companyName = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <div class="header-content">
            <a href="/company_admin/index.php" class="logo">🚌 <?php echo htmlspecialchars($companyName); ?></a>
            <nav>
                <ul>
                    <li><a href="/company_admin/index.php">Dashboard</a></li>
                    <li><a href="/company_admin/trips.php">Seferler</a></li>
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
                    <h2>📊 Firma Yönetim Paneli</h2>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>🚌 Toplam Sefer</h3>
                    <div class="value"><?php echo $totalTrips; ?></div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <h3>🎫 Aktif Biletler</h3>
                    <div class="value"><?php echo $activeTickets; ?></div>
                </div>

                <div class="dashboard-card" style="background: linear-gradient(135deg, #f39c12, #d68910);">
                    <h3>💰 Toplam Gelir</h3>
                    <div class="value"><?php echo formatPrice($totalRevenue); ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Hızlı Erişim</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <a href="trips.php" class="btn btn-primary btn-block">
                            🚌 Sefer Yönetimi
                        </a>
                        <a href="trips.php?action=add" class="btn btn-success btn-block">
                            ➕ Yeni Sefer Ekle
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>