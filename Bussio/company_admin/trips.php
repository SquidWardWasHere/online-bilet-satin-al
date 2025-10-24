<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

Security::requireRole(ROLE_COMPANY_ADMIN);

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
            $departureCity = Security::sanitizeInput($_POST['departure_city']);
            $destinationCity = Security::sanitizeInput($_POST['destination_city']);
            $departureTime = $_POST['departure_time'];
            $arrivalTime = $_POST['arrival_time'];
            $price = intval($_POST['price']);
            $capacity = intval($_POST['capacity']);

            if (
                empty($departureCity) || empty($destinationCity) || empty($departureTime) ||
                empty($arrivalTime) || $price <= 0 || $capacity <= 0
            ) {
                $error = 'Lütfen tüm alanları doğru şekilde doldurun.';
            } elseif ($departureCity === $destinationCity) {
                $error = 'Kalkış ve varış şehirleri aynı olamaz.';
            } elseif (strtotime($arrivalTime) <= strtotime($departureTime)) {
                $error = 'Varış zamanı, kalkış zamanından sonra olmalıdır.';
            } else {
                $tripId = Security::generateUUID();
                $stmt = $db->prepare("
                    INSERT INTO Trips (id, company_id, departure_city, destination_city, 
                                      departure_time, arrival_time, price, capacity) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([
                    $tripId,
                    $_SESSION['company_id'],
                    $departureCity,
                    $destinationCity,
                    $departureTime,
                    $arrivalTime,
                    $price,
                    $capacity
                ])) {
                    $success = 'Sefer başarıyla eklendi.';
                } else {
                    $error = 'Sefer eklenirken hata oluştu.';
                }
            }
        } elseif ($action === 'edit') {
            $tripId = $_POST['trip_id'] ?? '';
            $departureCity = Security::sanitizeInput($_POST['departure_city']);
            $destinationCity = Security::sanitizeInput($_POST['destination_city']);
            $departureTime = $_POST['departure_time'];
            $arrivalTime = $_POST['arrival_time'];
            $price = intval($_POST['price']);
            $capacity = intval($_POST['capacity']);

            if (
                empty($tripId) || empty($departureCity) || empty($destinationCity) ||
                empty($departureTime) || empty($arrivalTime) || $price <= 0 || $capacity <= 0
            ) {
                $error = 'Geçersiz veri.';
            } else {
                $stmt = $db->prepare("
                    UPDATE Trips 
                    SET departure_city = ?, destination_city = ?, departure_time = ?, 
                        arrival_time = ?, price = ?, capacity = ?
                    WHERE id = ? AND company_id = ?
                ");

                if ($stmt->execute([
                    $departureCity,
                    $destinationCity,
                    $departureTime,
                    $arrivalTime,
                    $price,
                    $capacity,
                    $tripId,
                    $_SESSION['company_id']
                ])) {
                    $success = 'Sefer başarıyla güncellendi.';
                } else {
                    $error = 'Sefer güncellenirken hata oluştu.';
                }
            }
        } elseif ($action === 'delete') {
            $tripId = $_POST['trip_id'] ?? '';

            if (!empty($tripId)) {
                $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");

                if ($stmt->execute([$tripId, $_SESSION['company_id']])) {
                    $success = 'Sefer başarıyla silindi.';
                } else {
                    $error = 'Sefer silinemedi.';
                }
            }
        }
    }
}

// Get all trips for this company
$stmt = $db->prepare("
    SELECT t.*,
    (SELECT COUNT(*) FROM Booked_Seats bs 
     INNER JOIN Tickets tk ON bs.ticket_id = tk.id 
     WHERE tk.trip_id = t.id AND tk.status = 'active') as booked_seats
    FROM Trips t
    WHERE t.company_id = ?
    ORDER BY t.departure_time DESC
");
$stmt->execute([$_SESSION['company_id']]);
$trips = $stmt->fetchAll();

$cities = getCityList();
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Yönetimi - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <div class="header-content">
            <a href="/company_admin/index.php" class="logo">🚌 Firma Panel</a>
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
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Trip Form -->
            <div class="card">
                <div class="card-header">
                    <h2>🚌 Yeni Sefer Ekle</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="departure_city">Kalkış Şehri</label>
                                <select name="departure_city" id="departure_city" class="form-control" required>
                                    <option value="">Şehir Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="destination_city">Varış Şehri</label>
                                <select name="destination_city" id="destination_city" class="form-control" required>
                                    <option value="">Şehir Seçin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="departure_time">Kalkış Zamanı</label>
                                <input type="datetime-local" name="departure_time" id="departure_time"
                                    class="form-control" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="arrival_time">Varış Zamanı</label>
                                <input type="datetime-local" name="arrival_time" id="arrival_time"
                                    class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Bilet Fiyatı (₺)</label>
                                <input type="number" name="price" id="price" class="form-control"
                                    min="1" step="1" required>
                            </div>

                            <div class="form-group">
                                <label for="capacity">Koltuk Kapasitesi</label>
                                <input type="number" name="capacity" id="capacity" class="form-control"
                                    min="1" max="60" value="45" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Sefer Ekle</button>
                    </form>
                </div>
            </div>

            <!-- Trips List -->
            <div class="card">
                <div class="card-header">
                    <h2>Kayıtlı Seferler</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($trips)): ?>
                        <div class="empty-state">
                            <p>Henüz sefer eklenmemiş.</p>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Güzergah</th>
                                    <th>Kalkış</th>
                                    <th>Varış</th>
                                    <th>Fiyat</th>
                                    <th>Doluluk</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trips as $trip):
                                    $availableSeats = $trip['capacity'] - $trip['booked_seats'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $trip['departure_city']; ?></strong> →
                                            <strong><?php echo $trip['destination_city']; ?></strong>
                                        </td>
                                        <td><?php echo formatDate($trip['departure_time']); ?></td>
                                        <td><?php echo formatDate($trip['arrival_time']); ?></td>
                                        <td><?php echo formatPrice($trip['price']); ?></td>
                                        <td>
                                            <?php echo $trip['booked_seats']; ?> / <?php echo $trip['capacity']; ?>
                                            <span class="badge badge-<?php echo $availableSeats > 10 ? 'success' : 'warning'; ?>">
                                                <?php echo $availableSeats; ?> boş
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick='editTrip(<?php echo json_encode($trip); ?>)'
                                                class="btn btn-warning" style="margin-right: 0.5rem;">
                                                Düzenle
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
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
                <h2>Sefer Düzenle</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="trip_id" id="edit_trip_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_departure_city">Kalkış Şehri</label>
                        <select name="departure_city" id="edit_departure_city" class="form-control" required>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_destination_city">Varış Şehri</label>
                        <select name="destination_city" id="edit_destination_city" class="form-control" required>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_departure_time">Kalkış Zamanı</label>
                        <input type="datetime-local" name="departure_time" id="edit_departure_time"
                            class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_arrival_time">Varış Zamanı</label>
                        <input type="datetime-local" name="arrival_time" id="edit_arrival_time"
                            class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_price">Bilet Fiyatı (₺)</label>
                        <input type="number" name="price" id="edit_price" class="form-control"
                            min="1" step="1" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_capacity">Koltuk Kapasitesi</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control"
                            min="1" max="60" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Güncelle</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function editTrip(trip) {
            document.getElementById('edit_trip_id').value = trip.id;
            document.getElementById('edit_departure_city').value = trip.departure_city;
            document.getElementById('edit_destination_city').value = trip.destination_city;
            document.getElementById('edit_departure_time').value = formatDateTimeLocal(trip.departure_time);
            document.getElementById('edit_arrival_time').value = formatDateTimeLocal(trip.arrival_time);
            document.getElementById('edit_price').value = trip.price;
            document.getElementById('edit_capacity').value = trip.capacity;
            document.getElementById('editModal').style.display = 'block';
        }

        function formatDateTimeLocal(dateString) {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
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