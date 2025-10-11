<?php
session_start();
require_once __DIR__ . '/backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Simulate payment success (integrate real gateway like Stripe/PayPal here)
$user_id = $_SESSION['user_id'];
$membership_type = $_POST['membership_type'];  // e.g., 'basic' or 'premium' from form

$conn = getDbConnection();
$stmt = $conn->prepare("UPDATE users SET membership_status = ? WHERE id = ?");
$stmt->bind_param('si', $membership_type, $user_id);
$stmt->execute();
$stmt->close();$conn->close();

// Update user's status in the session if needed, though it's better to fetch fresh data on dashboard load.
// For this app, the dashboard re-fetches user data, so updating the session here isn't critical.

header('Location: customerdash.php');
exit();
?>