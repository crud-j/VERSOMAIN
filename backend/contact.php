<?php
// backend/contact.php

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php#contact');
    exit();
}

// Get form data
$name = clean_input($_POST['name'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$message = clean_input($_POST['message'] ?? '');

// Basic validation
if (empty($name) || empty($email) || empty($message)) {
    header('Location: ../index.php?contact_error=Please fill all fields.');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?contact_error=Invalid email address.');
    exit();
}

try {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $email, $message);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Location: ../index.php?contact_success=1#contact');
        exit();
    } else {
        throw new Exception('Failed to send message.');
    }

} catch (Exception $e) {
    error_log('Contact Form Error: ' . $e->getMessage());
    header('Location: ../index.php?contact_error=' . urlencode($e->getMessage()));
    exit();
}