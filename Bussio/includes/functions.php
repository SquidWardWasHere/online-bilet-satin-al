<?php

/**
 * Format price in Turkish Lira
 */
function formatPrice($price)
{
    return number_format($price, 2, ',', '.') . ' ₺';
}

/**
 * Format date in Turkish format
 */
function formatDate($date)
{
    return date('d.m.Y H:i', strtotime($date));
}

/**
 * Format date for display
 */
function formatDateOnly($date)
{
    return date('d.m.Y', strtotime($date));
}

/**
 * Format time for display
 */
function formatTime($date)
{
    return date('H:i', strtotime($date));
}

/**
 * Get Turkish city list
 */
function getCityList()
{
    return [
        'İstanbul',
        'Ankara',
        'İzmir',
        'Antalya',
        'Bursa',
        'Adana',
        'Gaziantep',
        'Konya',
        'Mersin',
        'Diyarbakır',
        'Kayseri',
        'Eskişehir',
        'Samsun',
        'Denizli',
        'Şanlıurfa',
        'Trabzon'
    ];
}

/**
 * Calculate trip duration
 */
function calculateDuration($departure, $arrival)
{
    $diff = strtotime($arrival) - strtotime($departure);
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    return sprintf('%d saat %d dakika', $hours, $minutes);
}

/**
 * Check if ticket can be cancelled
 */
function canCancelTicket($departureTime)
{
    $now = time();
    $departure = strtotime($departureTime);
    $hoursDiff = ($departure - $now) / 3600;

    return $hoursDiff >= TICKET_CANCEL_TIME_LIMIT;
}

/**
 * Get ticket status badge
 */
function getTicketStatusBadge($status)
{
    $badges = [
        'active' => '<span class="badge badge-success">Aktif</span>',
        'cancelled' => '<span class="badge badge-danger">İptal Edildi</span>',
        'expired' => '<span class="badge badge-secondary">Süresi Doldu</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">Bilinmiyor</span>';
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info')
{
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if date is in the past
 */
function isPastDate($date)
{
    return strtotime($date) < time();
}

/**
 * Validate Turkish phone number
 */
function validatePhoneNumber($phone)
{
    $pattern = '/^0?5\d{9}$/';
    return preg_match($pattern, $phone);
}

/**
 * Get available seat numbers
 */
function getAvailableSeats($capacity, $bookedSeats = [])
{
    $allSeats = range(1, $capacity);
    return array_diff($allSeats, $bookedSeats);
}

/**
 * Generate seat layout HTML
 */
function generateSeatLayout($capacity, $bookedSeats, $selectedSeats = [])
{
    $html = '<div class="seat-layout">';

    for ($i = 1; $i <= $capacity; $i++) {
        $isBooked = in_array($i, $bookedSeats);
        $isSelected = in_array($i, $selectedSeats);

        $class = 'seat';
        if ($isBooked) {
            $class .= ' seat-booked';
            $disabled = 'disabled';
        } else {
            $class .= ' seat-available';
            $disabled = '';
        }

        if ($isSelected) {
            $class .= ' seat-selected';
        }

        if ($i % 4 == 0) {
            $html .= '<div class="seat-row">';
        }

        $html .= sprintf(
            '<button type="button" class="%s" data-seat="%d" %s>%d</button>',
            $class,
            $i,
            $disabled,
            $i
        );

        if ($i % 4 == 0) {
            $html .= '</div>';
        }
    }

    $html .= '</div>';
    return $html;
}
