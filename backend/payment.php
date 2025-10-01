<?php
// /backend/payment.php

session_start();

// Your payment processing code here...

$payment_success = true; // Assume payment succeeded

if ($payment_success) {
    // Use PRG pattern: redirect to dashboard or success page
    header("Location: /customerdash.php"); // or any success page
    exit();
} else {
    // On error, you can redirect back to payment with error message or show error
    $_SESSION['error_message'] = "Payment failed. Please try again.";
    header("Location: /payment.php");
    exit();
}