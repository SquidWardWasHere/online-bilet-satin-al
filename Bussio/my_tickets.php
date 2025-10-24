<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

Security::requireRole(ROLE_USER);

$db = Database::getInstance()->getConnection();

// Get user tickets
$stmt = $db->prepare("
    SELECT 
        tk.*,
        t.departure_city,
        t.destination_city,
        t.departure_time,
        t.arrival_time,
        bc.name as company_name,
        GROUP_CONCAT(bs.seat_number, ', ') as seats
    FROM Tickets tk
    INNER JOIN Trips t ON tk.trip_id = t.id
    INNER JOIN Bus_Company bc ON t.company_id = bc.id
    LEFT JOIN Booked_Seats bs ON tk.id = bs.ticket_id
    WHERE tk.user_id = ?
    GROUP BY tk.id
    ORDER BY tk.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim - <?php echo SITE_NAME; ?></title>
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

            <div class="card">
                <div class="card-header">
                    <h2>🎫 Biletlerim</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <div class="empty-state">
                            <h3>Henüz biletiniz yok</h3>
                            <p>Bilet satın almak için seferleri inceleyin.</p>
                            <a href="index.php" class="btn btn-primary">Sefer Ara</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Güzergah</th>
                                        <th>Firma</th>
                                        <th>Koltuklar</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket):
                                        // Update status if expired
                                        if ($ticket['status'] === 'active' && isPastDate($ticket['departure_time'])) {
                                            $updateStmt = $db->prepare("UPDATE Tickets SET status = 'expired' WHERE id = ?");
                                            $updateStmt->execute([$ticket['id']]);
                                            $ticket['status'] = 'expired';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo formatDate($ticket['departure_time']); ?></td>
                                            <td>
                                                <strong><?php echo $ticket['departure_city']; ?></strong> →
                                                <strong><?php echo $ticket['destination_city']; ?></strong>
                                            </td>
                                            <td><?php echo $ticket['company_name']; ?></td>
                                            <td><?php echo $ticket['seats']; ?></td>
                                            <td><?php echo formatPrice($ticket['total_price']); ?></td>
                                            <td><?php echo getTicketStatusBadge($ticket['status']); ?></td>
                                            <td>
                                                <?php if ($ticket['status'] === 'active'): ?>
                                                    <a href="download_ticket.php?id=<?php echo $ticket['id']; ?>"
                                                        class="btn btn-primary" style="margin-right: 0.5rem;">
                                                        PDF İndir
                                                    </a>
                                                    <?php if (canCancelTicket($ticket['departure_time'])): ?>
                                                        <a href="cancel_ticket.php?id=<?php echo $ticket['id']; ?>"
                                                            class="btn btn-danger"
                                                            onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?');">
                                                            İptal Et
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">İptal Edilemez</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>