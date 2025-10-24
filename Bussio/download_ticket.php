<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

Security::requireRole(ROLE_USER);

$ticketId = $_GET['id'] ?? '';

if (empty($ticketId)) {
    redirect('my_tickets.php', 'Geçersiz bilet.', 'danger');
}

$db = Database::getInstance()->getConnection();

// Get ticket details
$stmt = $db->prepare("
    SELECT 
        tk.*,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.arrival_time,
        bc.name as company_name,
        u.full_name,
        u.email,
        GROUP_CONCAT(bs.seat_number, ', ') as seats
    FROM Tickets tk
    INNER JOIN Trips t ON tk.trip_id = t.id
    INNER JOIN Bus_Company bc ON t.company_id = bc.id
    INNER JOIN User u ON tk.user_id = u.id
    LEFT JOIN Booked_Seats bs ON tk.id = bs.ticket_id
    WHERE tk.id = ? AND tk.user_id = ?
    GROUP BY tk.id
");
$stmt->execute([$ticketId, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('my_tickets.php', 'Bilet bulunamadı.', 'danger');
}

// Set headers for PDF download
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet - <?php echo $ticket['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .ticket-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .ticket-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .ticket-body {
            padding: 40px;
        }

        .route-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #ecf0f1;
            border-radius: 10px;
        }

        .city {
            text-align: center;
        }

        .city-name {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .arrow {
            font-size: 3rem;
            color: #3498db;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 5px;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .barcode {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 30px;
        }

        .barcode-text {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            letter-spacing: 3px;
            margin-top: 10px;
        }

        .ticket-footer {
            text-align: center;
            padding: 20px;
            background: #ecf0f1;
            color: #7f8c8d;
        }

        .print-button {
            display: inline-block;
            padding: 15px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .print-button:hover {
            background: #2980b9;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-button,
            .back-button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>🚌 BUSSIO</h1>
            <p>Otobüs Bileti</p>
            <p style="margin-top: 10px; opacity: 0.9;">Bilet No: <?php echo strtoupper(substr($ticket['id'], 0, 8)); ?></p>
        </div>

        <div class="ticket-body">
            <div class="route-section">
                <div class="city">
                    <div class="city-name"><?php echo $ticket['departure_city']; ?></div>
                    <div style="color: #7f8c8d; margin-top: 5px;">
                        <?php echo formatTime($ticket['departure_time']); ?>
                    </div>
                </div>

                <div class="arrow">→</div>

                <div class="city">
                    <div class="city-name"><?php echo $ticket['destination_city']; ?></div>
                    <div style="color: #7f8c8d; margin-top: 5px;">
                        <?php echo formatTime($ticket['arrival_time']); ?>
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Yolcu Adı</div>
                    <div class="detail-value"><?php echo $ticket['full_name']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Otobüs Firması</div>
                    <div class="detail-value"><?php echo $ticket['company_name']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Kalkış Tarihi</div>
                    <div class="detail-value"><?php echo formatDate($ticket['departure_time']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Koltuk No</div>
                    <div class="detail-value"><?php echo $ticket['seats']; ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Toplam Ücret</div>
                    <div class="detail-value"><?php echo formatPrice($ticket['total_price']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Durum</div>
                    <div class="detail-value" style="color: <?php echo $ticket['status'] === 'active' ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo $ticket['status'] === 'active' ? 'AKTİF' : strtoupper($ticket['status']); ?>
                    </div>
                </div>
            </div>

            <div class="barcode">
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 10px;">PNR Kodu</div>
                <div class="barcode-text"><?php echo strtoupper($ticket['id']); ?></div>
            </div>
        </div>

        <div class="ticket-footer">
            <p>Bu bilet Bussio platformu tarafından elektronik olarak oluşturulmuştur.</p>
            <p style="margin-top: 5px;">Yolculuğunuz esnasında yanınızda bulundurmanız gerekmektedir.</p>
            <p style="margin-top: 10px; font-size: 0.9rem;">
                Oluşturma Tarihi: <?php echo formatDate($ticket['created_at']); ?>
            </p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print();" class="print-button">🖨️ Yazdır / PDF Olarak Kaydet</button>
        <a href="my_tickets.php" class="print-button back-button" style="background: #95a5a6;">← Geri Dön</a>
    </div>
</body>

</html>