<?php
session_start();
require_once __DIR__ . '/backend/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Simulate payment success (integrate real gateway like Stripe/PayPal here)
$user_id = $_SESSION['user']['id'];
$membership_type = $_POST['membership_type'];  // e.g., 'basic' or 'premium' from form

$conn = getDbConnection();
$stmt = $conn->prepare("UPDATE users SET membership_status = ? WHERE id = ?");
$stmt->bind_param('si', $membership_type, $user_id);
$stmt->execute();
$stmt->close();$conn->close();

// Update session
$_SESSION['user']['membership_status'] = $membership_type;

header('Location: customerdash.php');
exit();
?>