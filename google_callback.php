<?php
// google_callback.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/config.php';

function ensure_session_started() {
    // Start session if not already active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

ensure_session_started();

if (!isset($_GET['code'])) {
    die('Authorization code not found. <a href="login.php">Try again</a>');
}

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception('Error fetching access token: ' . $token['error_description'] ?? $token['error']);
    }

    // set token array (works with the google client)
    $client->setAccessToken($token);

    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    // Persist user in DB if new and set session user id
    $conn = getDbConnection();

    $oauth_provider = 'google';
    $oauth_uid = (string)$userInfo->id;
    $fullname = $conn->real_escape_string((string)$userInfo->name);
    $email = $conn->real_escape_string((string)$userInfo->email);
    $picture = $conn->real_escape_string((string)($userInfo->picture ?? ''));

    // Try to find existing user
    $stmt = $conn->prepare("SELECT id, fullname, email, picture, membership_status, role FROM users WHERE oauth_provider = ? AND oauth_uid = ? LIMIT 1");
    $stmt->bind_param('ss', $oauth_provider, $oauth_uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_role = 'customer'; // Default role
    if ($email === 'villaversogym@gmail.com') {
        $user_role = 'admin';
    }

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = intval($user['id']);
        // If the user is the admin, ensure their role is updated in the DB
        if ($email === 'villaversogym@gmail.com' && $user['role'] !== 'admin') {
            $update_stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $update_stmt->bind_param('i', $user_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        $user_role = ($email === 'villaversogym@gmail.com') ? 'admin' : $user['role'];
    } else {
        // Insert new user
        $membership_status = 'none';
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO users (oauth_provider, oauth_uid, fullname, email, picture, membership_status, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssss', $oauth_provider, $oauth_uid, $fullname, $email, $picture, $membership_status, $user_role);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('Failed to insert Google user: ' . $stmt->error);
            throw new Exception('Failed to create user.');
        }
        $user_id = (int)$stmt->insert_id;
    }

    $stmt->close();
    $conn->close();

    // Set session (consistent key used across app)
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['oauth_provider'] = $oauth_provider;
    $_SESSION['role'] = $user_role; // Set user role in session
    $_SESSION['user_picture'] = $picture;
    $_SESSION['user_fullname'] = $fullname;

    if ($user_role === 'admin') {
        header('Location: admindash.php');
    } else {
        header('Location: customerdash.php');
    }
    exit();
} catch (Exception $e) {
    error_log('Google callback error: ' . $e->getMessage());
    die('Error: ' . htmlspecialchars($e->getMessage()) . ' <a href="login.php">Try again</a>');
}