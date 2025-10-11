<?php
// backend/booking.php

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php#booking');
    exit();
}

// Start session to check for logged-in user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Get form data
$name = clean_input($_POST['name'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
$trainer_id = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;
$service = clean_input($_POST['service'] ?? '');
$date = clean_input($_POST['date'] ?? '');
$time = clean_input($_POST['time'] ?? '');
$notes = clean_input($_POST['notes'] ?? '');

// Basic validation
if (empty($name) || empty($email) || empty($service) || empty($date) || empty($time) || empty($trainer_id)) {
    header('Location: ../index.php?booking_error=Please fill all required fields.#booking');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?booking_error=Invalid email address.#booking');
    exit();
}

$user_id = null;
// If user is logged in, use their ID. Otherwise, try to find a user with the same email.
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
} 

$title = $service;
if (!empty($notes)) {
    $title .= ' (' . $notes . ')';
}

try {
    $conn = getDbConnection();

    // If user is not logged in, check if email exists
    if (!$user_id) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_id = $result->fetch_assoc()['id'];
        }
        $stmt->close();
    }

    // If still no user_id, redirect to register (with pending booking)
    if (!$user_id) {
        $_SESSION['pending_booking'] = $_POST;
        header('Location: ../register.php?source=booking');
        exit();
    }

    // Insert into workouts table with trainer_id
    $stmt = $conn->prepare("INSERT INTO workouts (user_id, trainer_id, title, date, time, type, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param('iissss', $user_id, $trainer_id, $title, $date, $time, $service);
    
    if ($stmt->execute()) {
        // Notify admin of new booking
        $admin_id = 1; // Assuming admin user_id=1; adjust if needed
        $notif_msg = "New trainer session booking from user {$user_id}: {$service} on {$date} at {$time} with trainer ID {$trainer_id}.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, icon, category, is_read) VALUES (?, 'booking', ?, 'bell', 'Admin', 0)");
        $notif_stmt->bind_param('is', $admin_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        $stmt->close();
        $conn->close();
        header('Location: ../index.php?booking_success=1#booking');
        exit();
    } else {
        throw new Exception('Failed to save booking.');
    }

} catch (Exception $e) {
    error_log('Booking Error: ' . $e->getMessage());
    header('Location: ../index.php?booking_error=' . urlencode($e->getMessage()) . '#booking');
    exit();
}