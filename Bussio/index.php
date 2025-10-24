<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

// Get search parameters
$departureCity = isset($_GET['departure']) ? Security::sanitizeInput($_GET['departure']) : '';
$destinationCity = isset($_GET['destination']) ? Security::sanitizeInput($_GET['destination']) : '';
$departureDate = isset($_GET['date']) ? Security::sanitizeInput($_GET['date']) : '';

// Build query
$query = "
    SELECT t.*, bc.name as company_name, bc.logo_path,
    (SELECT COUNT(*) FROM Booked_Seats bs 
     INNER JOIN Tickets tk ON bs.ticket_id = tk.id 
     WHERE tk.trip_id = t.id AND tk.status = 'active') as booked_seats
    FROM Trips t
    INNER JOIN Bus_Company bc ON t.company_id = bc.id
    WHERE 1=1
";

$params = [];

if (!empty($departureCity)) {
    $query .= " AND t.departure_city = ?";
    $params[] = $departureCity;
}

if (!empty($destinationCity)) {
    $query .= " AND t.destination_city = ?";
    $params[] = $destinationCity;
}

if (!empty($departureDate)) {
    $query .= " AND DATE(t.departure_time) = ?";
    $params[] = $departureDate;
}

$query .= " ORDER BY t.departure_time ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll();

$cities = getCityList();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

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

            <!-- Search Form -->
            <div class="card">
                <div class="card-header">
                    <h2>🚌 Otobüs Bileti Ara</h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="departure">Nereden</label>
                                <select name="departure" id="departure" class="form-control" required>
                                    <option value="">Kalkış Şehri Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city; ?>" <?php echo $departureCity === $city ? 'selected' : ''; ?>>
                                            <?php echo $city; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="destination">Nereye</label>
                                <select name="destination" id="destination" class="form-control" required>
                                    <option value="">Varış Şehri Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city; ?>" <?php echo $destinationCity === $city ? 'selected' : ''; ?>>
                                            <?php echo $city; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="date">Tarih</label>
                                <input type="date" name="date" id="date" class="form-control"
                                    value="<?php echo $departureDate; ?>"
                                    min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Sefer Ara
                        </button>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <?php if (!empty($departureCity) || !empty($destinationCity) || !empty($departureDate)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Sefer Sonuçları</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trips)): ?>
                            <div class="empty-state">
                                <h3>Sefer Bulunamadı</h3>
                                <p>Aradığınız kriterlere uygun sefer bulunamamıştır.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($trips as $trip):
                                $availableSeats = $trip['capacity'] - $trip['booked_seats'];
                            ?>
                                <div class="trip-card">
                                    <div class="trip-info">
                                        <div class="trip-route">
                                            <span class="trip-city"><?php echo $trip['departure_city']; ?></span>
                                            <span class="trip-arrow">→</span>
                                            <span class="trip-city"><?php echo $trip['destination_city']; ?></span>
                                        </div>
                                        <div class="trip-details">
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
                                                    <?php echo $availableSeats; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="trip-price">
                                        <div class="company"><?php echo $trip['company_name']; ?></div>
                                        <div class="price"><?php echo formatPrice($trip['price']); ?></div>
                                        <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-primary">
                                            Detaylar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>