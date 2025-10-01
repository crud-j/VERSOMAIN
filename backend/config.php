<?php
// DB Connection
function getDbConnection() {
    $conn = new mysqli('localhost', 'root', '@l03e1t3', 'versogym');  // Update credentials
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    return $conn;
}

// Google Constants (add your real values)
define('GOOGLE_CLIENT_ID', '1007094319099-0k29ipdh5q797sbl3aa4q7b2l3360on0.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/WebProj/google_callback.php');
// Other constants like FACEBOOK_APP_ID if needed...
?>