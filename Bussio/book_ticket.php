<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

Security::requireRole(ROLE_USER);

$tripId = $_GET['trip_id'] ?? '';
$error = '';
$success = '';

if (empty($tripId)) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get trip details
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name 
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

// Get user balance
$stmt = $db->prepare("SELECT balance FROM User WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get booked seats
$stmt = $db->prepare("
    SELECT bs.seat_number 
    FROM Booked_Seats bs
    INNER JOIN Tickets tk ON bs.ticket_id = tk.id
    WHERE tk.trip_id = ? AND tk.status = 'active'
");
$stmt->execute([$tripId]);
$bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Geçersiz istek.';
    } else {
        $selectedSeats = $_POST['seats'] ?? [];
        $couponCode = Security::sanitizeInput($_POST['coupon_code'] ?? '');

        if (empty($selectedSeats)) {
            $error = 'Lütfen en az bir koltuk seçin.';
        } else {
            // Check if seats are available
            $conflictSeats = array_intersect($selectedSeats, $bookedSeats);
            if (!empty($conflictSeats)) {
                $error = 'Seçtiğiniz koltuklar rezerve edilmiş.';
            } else {
                $totalPrice = $trip['price'] * count($selectedSeats);
                $discount = 0;

                // Apply coupon if provided
                if (!empty($couponCode)) {
                    $stmt = $db->prepare("
                        SELECT c.*, 
                        (SELECT COUNT(*) FROM User_Coupons WHERE coupon_id = c.id) as usage_count
                        FROM Coupons c
                        WHERE c.code = ? AND c.expire_date > datetime('now')
                    ");
                    $stmt->execute([$couponCode]);
                    $coupon = $stmt->fetch();

                    if ($coupon) {
                        // Check if user already used this coupon
                        $stmt = $db->prepare("SELECT id FROM User_Coupons WHERE coupon_id = ? AND user_id = ?");
                        $stmt->execute([$coupon['id'], $_SESSION['user_id']]);

                        if ($stmt->fetch()) {
                            $error = 'Bu kuponu daha önce kullandınız.';
                        } elseif ($coupon['usage_count'] >= $coupon['usage_limit']) {
                            $error = 'Bu kuponun kullanım limiti dolmuştur.';
                        } else {
                            $discount = ($totalPrice * $coupon['discount']) / 100;
                            $totalPrice -= $discount;
                        }
                    } else {
                        $error = 'Geçersiz veya süresi dolmuş kupon kodu.';
                    }
                }

                if (empty($error)) {
                    // Check balance
                    if ($user['balance'] < $totalPrice) {
                        $error = 'Yetersiz bakiye. Bakiyeniz: ' . formatPrice($user['balance']);
                    } else {
                        try {
                            $db->beginTransaction();

                            // Create ticket
                            $ticketId = Security::generateUUID();
                            $stmt = $db->prepare("
                                INSERT INTO Tickets (id, trip_id, user_id, status, total_price) 
                                VALUES (?, ?, ?, 'active', ?)
                            ");
                            $stmt->execute([$ticketId, $tripId, $_SESSION['user_id'], $totalPrice]);

                            // Book seats
                            foreach ($selectedSeats as $seatNumber) {
                                $seatId = Security::generateUUID();
                                $stmt = $db->prepare("
                                    INSERT INTO Booked_Seats (id, ticket_id, seat_number) 
                                    VALUES (?, ?, ?)
                                ");
                                $stmt->execute([$seatId, $ticketId, $seatNumber]);
                            }

                            // Deduct balance
                            $stmt = $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
                            $stmt->execute([$totalPrice, $_SESSION['user_id']]);

                            // Record coupon usage
                            if (isset($coupon)) {
                                $userCouponId = Security::generateUUID();
                                $stmt = $db->prepare("
                                    INSERT INTO User_Coupons (id, coupon_id, user_id) 
                                    VALUES (?, ?, ?)
                                ");
                                $stmt->execute([$userCouponId, $coupon['id'], $_SESSION['user_id']]);
                            }

                            $db->commit();
                            redirect('my_tickets.php', 'Biletiniz başarıyla satın alındı!', 'success');
                        } catch (Exception $e) {
                            $db->rollBack();
                            $error = 'Bilet satın alınırken bir hata oluştu.';
                            error_log("Ticket booking error: " . $e->getMessage());
                        }
                    }
                }
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
    <title>Bilet Satın Al - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .seat-selection {
            max-width: 800px;
            margin: 0 auto;
        }

        .seat-layout {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 2rem 0;
        }

        .seat {
            aspect-ratio: 1;
            border: 2px solid #bdc3c7;
            background: #e8f5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seat:hover:not(.seat-booked) {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .seat.seat-booked {
            background: #ffebee;
            border-color: #e74c3c;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .seat.seat-selected {
            background: #3498db;
            color: white;
            border-color: #2980b9;
        }

        .summary-box {
            background: #ecf0f1;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #bdc3c7;
        }

        .summary-item:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2>Bilet Satın Al</h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

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
                                    <strong>Tarih:</strong> <?php echo formatDate($trip['departure_time']); ?>
                                </div>
                                <div class="trip-detail">
                                    <strong>Fiyat:</strong> <?php echo formatPrice($trip['price']); ?> / koltuk
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="bookingForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <div class="seat-selection">
                            <h3 style="text-align: center; margin-bottom: 1rem;">Koltuk Seçimi</h3>

                            <div class="seat-legend" style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem;">
                                <div class="legend-item">
                                    <div class="legend-box" style="width: 30px; height: 30px; background: #e8f5e9; border: 2px solid #27ae60; border-radius: 5px;"></div>
                                    <span>Boş</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-box" style="width: 30px; height: 30px; background: #3498db; border: 2px solid #2980b9; border-radius: 5px;"></div>
                                    <span>Seçili</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-box" style="width: 30px; height: 30px; background: #ffebee; border: 2px solid #e74c3c; border-radius: 5px;"></div>
                                    <span>Dolu</span>
                                </div>
                            </div>

                            <div class="seat-layout" id="seatLayout">
                                <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                                    <?php $isBooked = in_array($i, $bookedSeats); ?>
                                    <div class="seat <?php echo $isBooked ? 'seat-booked' : ''; ?>"
                                        data-seat="<?php echo $i; ?>"
                                        <?php echo $isBooked ? '' : 'onclick="toggleSeat(this)"'; ?>>
                                        <?php echo $i; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="form-group">
                                <label for="coupon_code">İndirim Kuponu (Opsiyonel)</label>
                                <input type="text" name="coupon_code" id="coupon_code" class="form-control"
                                    placeholder="Kupon kodunu girin">
                            </div>

                            <div class="summary-box">
                                <div class="summary-item">
                                    <span>Seçilen Koltuklar:</span>
                                    <span id="selectedSeatsText">-</span>
                                </div>
                                <div class="summary-item">
                                    <span>Koltuk Sayısı:</span>
                                    <span id="seatCount">0</span>
                                </div>
                                <div class="summary-item">
                                    <span>Bilet Fiyatı:</span>
                                    <span><?php echo formatPrice($trip['price']); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span>Toplam Tutar:</span>
                                    <span id="totalPrice"><?php echo formatPrice(0); ?></span>
                                </div>
                                <div class="summary-item" style="border-top: 2px solid #2c3e50; color: #3498db;">
                                    <span>Mevcut Bakiye:</span>
                                    <span><?php echo formatPrice($user['balance']); ?></span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-block" id="submitBtn" disabled>
                                Ödemeyi Tamamla
                            </button>

                            <a href="trip_details.php?id=<?php echo $tripId; ?>" class="btn btn-secondary btn-block" style="margin-top: 1rem;">
                                İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const ticketPrice = <?php echo $trip['price']; ?>;
        let selectedSeats = [];

        function toggleSeat(element) {
            const seatNumber = element.dataset.seat;

            if (element.classList.contains('seat-selected')) {
                element.classList.remove('seat-selected');
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
            } else {
                element.classList.add('seat-selected');
                selectedSeats.push(seatNumber);
            }

            updateSummary();
        }

        function updateSummary() {
            const count = selectedSeats.length;
            const total = count * ticketPrice;

            document.getElementById('seatCount').textContent = count;
            document.getElementById('selectedSeatsText').textContent = count > 0 ? selectedSeats.join(', ') : '-';
            document.getElementById('totalPrice').textContent = total.toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ₺';

            document.getElementById('submitBtn').disabled = count === 0;

            // Update hidden inputs
            const form = document.getElementById('bookingForm');
            const existingInputs = form.querySelectorAll('input[name="seats[]"]');
            existingInputs.forEach(input => input.remove());

            selectedSeats.forEach(seat => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'seats[]';
                input.value = seat;
                form.appendChild(input);
            });
        }
    </script>
</body>

</html>