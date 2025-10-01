<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/config.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

if (!isset($_GET['code'])) {
    die('Authorization code not found. <a href="login.php">Try again</a>');
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new Exception('Error fetching access token: ' . $token['error']);
    }

    $client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    $conn = getDbConnection();

    $oauth_provider = 'google';
    $oauth_uid = $userInfo->id;
    $fullname = $conn->real_escape_string($userInfo->name);
    $email = $conn->real_escape_string($userInfo->email);
    $picture = $conn->real_escape_string($userInfo->picture);
    $membership_status = 'none';  // Default: no membership

    $stmt = $conn->prepare("SELECT * FROM users WHERE oauth_provider = ? AND oauth_uid = ? LIMIT 1");
    $stmt->bind_param('ss', $oauth_provider, $oauth_uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // Signup: Insert new user
        $stmt = $conn->prepare("INSERT INTO users (oauth_provider, oauth_uid, fullname, email, picture, membership_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $oauth_provider, $oauth_uid, $fullname, $email, $picture, $membership_status);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $user = [
            'id' => $user_id,
            'oauth_provider' => $oauth_provider,
            'oauth_uid' => $oauth_uid,
            'fullname' => $fullname,
            'email' => $email,
            'picture' => $picture,
            'membership_status' => $membership_status,
        ];
        $stmt->close();
    }

    $conn->close();

    $_SESSION['user'] = $user;
    header('Location: customerdash.php');
    exit();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage() . ' <a href="login.php">Try again</a>');
}
?>