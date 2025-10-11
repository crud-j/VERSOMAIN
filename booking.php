// booking.php (full update)
<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit(); }

// Sanitize inputs
$booking_data = array_map('trim', $_POST);
$required = ['service', 'date', 'time'];
foreach ($required as $key) {
    if (empty($booking_data[$key])) {
        header('Location: ../index.php?booking_error=Missing_' . $key . '#booking');
        exit();
    }
}

if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $user_id = (int)$_SESSION['user_id'];
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO workouts (user_id, title, date, time, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param('issss', $user_id, $booking_data['service'], $booking_data['date'], $booking_data['time'], $booking_data['notes']);
        $stmt->execute();
        
        // Notify admin
        $admin_id = 1; // As per admindash.php
        $notif_msg = "New booking from user {$user_id}: {$booking_data['service']} on {$booking_data['date']}";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, category, is_read) VALUES (?, 'booking', ?, 'Admin', 0)");
        $notif_stmt->bind_param('is', $admin_id, $notif_msg);
        $notif_stmt->execute();
        
        $stmt->close();
        $notif_stmt->close();
        $conn->close();
        header('Location: ../index.php?booking_success=1#booking');
        exit();
    } catch (Exception $e) {
        header('Location: ../index.php?booking_error=Database_error#booking');
        exit();
    }
} else {
    $_SESSION['pending_booking'] = $booking_data;
    header('Location: ../register.php?source=booking');
    exit();
}
?>