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
    SELECT tk.*, t.departure_time
    FROM Tickets tk
    INNER JOIN Trips t ON tk.trip_id = t.id
    WHERE tk.id = ? AND tk.user_id = ? AND tk.status = 'active'
");
$stmt->execute([$ticketId, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('my_tickets.php', 'Bilet bulunamadı veya iptal edilemez.', 'danger');
}

// Check if ticket can be cancelled
if (!canCancelTicket($ticket['departure_time'])) {
    redirect('my_tickets.php', 'Bu bilet artık iptal edilemez. Kalkışa 1 saatten az süre kaldı.', 'danger');
}

try {
    $db->beginTransaction();

    // Update ticket status
    $stmt = $db->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$ticketId]);

    // Refund to user balance
    $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $_SESSION['user_id']]);

    $db->commit();

    redirect('my_tickets.php', 'Biletiniz başarıyla iptal edildi ve ücret iade edildi.', 'success');
} catch (Exception $e) {
    $db->rollBack();
    error_log("Ticket cancellation error: " . $e->getMessage());
    redirect('my_tickets.php', 'Bilet iptal edilirken bir hata oluştu.', 'danger');
}
