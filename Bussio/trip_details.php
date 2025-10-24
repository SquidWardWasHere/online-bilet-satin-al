<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

$tripId = $_GET['id'] ?? '';

if (empty($tripId)) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get trip details
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name, bc.logo_path,
    (SELECT COUNT(*) FROM Booked_Seats bs 
     INNER JOIN Tickets tk ON bs.ticket_id = tk.id 
     WHERE tk.trip_id = t.id AND tk.status = 'active') as booked_seats
    FROM Trips t
    INNER JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE t.id = ?
");
$stmt->execute([$tripId]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: index.php');
    exit;
}

// Get booked seats
$stmt = $db->prepare("
    SELECT bs.seat_number 
    FROM Booked_Seats bs
    INNER JOIN Tickets tk ON bs.ticket_id = tk.id
    WHERE tk.trip_id = ? AND tk.status = 'active'
");
$stmt->execute([$tripId]);
$bookedSeatsData = $stmt->fetchAll(PDO::FETCH_COLUMN);

$availableSeats = $trip['capacity'] - count($bookedSeatsData);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detayları - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>Sefer Detayları</h2>
                </div>
                <div class="card-body">
                    <div class="trip-card" style="margin-bottom: 2rem;">
                        <div class="trip-info">
                            <div class="trip-route">
                                <span class="trip-city"><?php echo $trip['departure_city']; ?></span>
                                <span class="trip-arrow">→</span>
                                <span class="trip-city"><?php echo $trip['destination_city']; ?></span>
                            </div>
                            <div class="trip-details">
                                <div class="trip-detail">
                                    <strong>Firma:</strong> <?php echo $trip['company_name']; ?>
                                </div>
                                <div class="trip-detail">
                                    <strong>Kalkış:</strong> <?php echo formatDate($trip['departure_time']); ?>
                                </div>
                                <div class="trip-detail">
                                    <strong>Varış:</strong> <?php echo formatDate($trip['arrival_time']); ?>
                                </div>
                                <div class="trip-detail">
                                    <strong>Süre:</strong> <?php echo calculateDuration($trip['departure_time'], $trip['arrival_time']); ?>
                                </div>
                                <div class="trip-detail">
                                    <strong>Boş Koltuk:</strong>
                                    <span class="badge badge-<?php echo $availableSeats > 10 ? 'success' : 'warning'; ?>">
                                        <?php echo $availableSeats; ?> / <?php echo $trip['capacity']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="trip-price">
                            <div class="price"><?php echo formatPrice($trip['price']); ?></div>
                        </div>
                    </div>

                    <?php if ($availableSeats > 0): ?>
                        <?php if (Security::isLoggedIn() && Security::hasRole(ROLE_USER)): ?>
                            <div style="text-align: center;">
                                <a href="book_ticket.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-success" style="font-size: 1.2rem; padding: 1rem 3rem;">
                                    Bilet Satın Al
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" style="text-align: center;">
                                Bilet satın almak için <a href="login.php">giriş yapmalısınız</a>.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger" style="text-align: center;">
                            Bu sefer için tüm koltuklar dolmuştur.
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 2rem; text-align: center;">
                        <a href="index.php" class="btn btn-secondary">Geri Dön</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>