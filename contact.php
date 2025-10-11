// contact.php (full update)
<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit(); }

// Sanitize
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?contact_error=Invalid_input#contact');
    exit();
}

try {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $email, $message);
    $stmt->execute();
    
    // Notify admin
    $admin_id = 1;
    $notif_msg = "New contact message from {$name} ({$email}): " . substr($message, 0, 50) . "...";
    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, category, is_read) VALUES (?, 'contact', ?, 'Admin', 0)");
    $notif_stmt->bind_param('is', $admin_id, $notif_msg);
    $notif_stmt->execute();
    
    $stmt->close();
    $notif_stmt->close();
    $conn->close();
    header('Location: ../index.php?contact_success=1#contact');
    exit();
} catch (Exception $e) {
    header('Location: ../index.php?contact_error=Database_error#contact');
    exit();
}
?>