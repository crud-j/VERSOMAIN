<?php
// customerdash.php
// Full functional customer dashboard (server endpoints + UI)
// Requires: backend/config.php which should define getDbConnection().
// Make sure your database has tables: users, workouts, memberships, payments, notifications, feed_posts, chat_messages.

require_once __DIR__ . '/backend/config.php';

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header_remove('X-Powered-By'); // reduce information leakage

// Authentication check
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Not logged in -> redirect to login (registration link available on login page)
    header('Location: login.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'get_notifications') {
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT id, type, message, icon, category, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $conn->close();
            json_response(['notifications' => $notifications]);
        } catch (Exception $e) {
            json_response(['error' => 'Server error.'], 500);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'mark_notification_read') {
        $notif_id = intval($_POST['id'] ?? 0);
        $is_read = isset($_POST['is_read']) && intval($_POST['is_read']) === 1 ? 1 : 0;
        if ($notif_id <= 0) {
            json_response(['error' => 'Invalid notification ID.'], 400);
        }
        try {
            $conn = getDbConnection();
            // Ensure notification belongs to user
            $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param('iii', $is_read, $notif_id, $user_id);
            $ok = $stmt->execute();
            $stmt->close();
            $conn->close();
            json_response(['success' => (bool)$ok]);
        } catch (Exception $e) {
            json_response(['error' => 'Server error.'], 500);
        }
    }
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function clean_input($data) {
    // sanitize a string for DB usage (we also use prepared statements)
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/*
  AJAX endpoints: initial_data (GET) and multiple POST actions.
  These endpoints return JSON so the front-end (Alpine.js) can consume them.
*/

// GET initial_data -> returns user, workouts, memberships, payments, notifications, feed_posts, chat_users, chat_messages
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'initial_data') {
    $conn = getDbConnection();

    // fetch user
    $stmt = $conn->prepare("SELECT id, fullname, email, picture, age, gender, fitness_goals, membership_status FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if (!$user) {
        session_unset();
        session_destroy();
        json_response(['error' => 'User not found'], 401);
    }

    // workouts
    $stmt = $conn->prepare("SELECT id, title, date, time, type, recurring FROM workouts WHERE user_id = ? ORDER BY date ASC, time ASC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $workouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // memberships
    $stmt = $conn->prepare("SELECT id, plan, start_date, end_date, status FROM memberships WHERE user_id = ? ORDER BY start_date DESC LIMIT 10");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $memberships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // payments
    $stmt = $conn->prepare("SELECT id, plan, amount, payment_method, payment_status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // notifications
    $stmt = $conn->prepare("SELECT id, type, message, icon, is_read, category, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $notifications_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // transform
    $notifications = array_map(function($n){
        return [
            'id' => (int)$n['id'],
            'type' => $n['type'],
            'message' => $n['message'],
            'icon' => $n['icon'] ?: 'notifications',
            'is_read' => (bool)$n['is_read'],
            'category' => $n['category'] ?? 'General',
            'created_at' => $n['created_at']
        ];
    }, $notifications_raw);

    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'notifications_stream') {
    // Ensure session + auth
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo "event: error\n";
        echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
        exit();
    }
    // Headers for SSE
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-transform');
    header('Connection: keep-alive');
    // Turn off output buffering/compression (best-effort)
    @ini_set('zlib.output_compression', 'Off');
    @apache_setenv('no-gzip', '1');
    // Long running
    set_time_limit(0);
    ignore_user_abort(true);

    $last_id = intval($_GET['last_id'] ?? 0);
    $start = time();
    $timeout_seconds = 25; // server will break stream after ~25s so client reconnects

    while (true) {
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT id, type, message, icon, is_read, category, created_at FROM notifications WHERE user_id = ? AND id > ? ORDER BY id ASC");
            $stmt->bind_param('ii', $user_id, $last_id);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $payload = json_encode([
                        'id' => (int)$r['id'],
                        'type' => $r['type'],
                        'message' => $r['message'],
                        'icon' => $r['icon'] ?: 'notifications',
                        'is_read' => (bool)$r['is_read'],
                        'category' => $r['category'] ?? 'General',
                        'created_at' => $r['created_at']
                    ], JSON_UNESCAPED_UNICODE);

                    // Send SSE event named "notification"
                    echo "event: notification\n";
                    echo "id: " . ((int)$r['id']) . "\n";
                    echo "data: " . $payload . "\n\n";

                    @ob_flush();
                    @flush();

                    // bump last_id so we don't resend
                    $last_id = max($last_id, (int)$r['id']);
                }
            } else {
                // send a comment to keep connection alive every loop so proxies don't kill it
                echo ": keep-alive\n\n";
                @ob_flush();
                @flush();
            }

            $conn->close();
        } catch (Throwable $e) {
            // send error event then break
            echo "event: error\n";
            echo "data: " . json_encode(['error' => 'Server error']) . "\n\n";
            @ob_flush();
            @flush();
            break;
        }

        // break connection periodically to allow PHP process recycling; client will reconnect
        if (time() - $start > $timeout_seconds) {
            // optional final keepalive then break
            echo ": closing\n\n";
            @ob_flush();
            @flush();
            break;
        }

        // Poll interval inside SSE loop (short)
        sleep(2);
    }

    exit();
}

    // feed_posts
    $stmt = $conn->prepare("SELECT f.id, f.content, f.image, f.created_at, u.id AS author_id, u.fullname AS author_name, u.picture AS author_avatar FROM feed_posts f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC LIMIT 50");
    $stmt->execute();
    $feed_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // chat users (exclude current user)
    $stmt = $conn->prepare("SELECT id, fullname, email, picture, fitness_goals FROM users WHERE id != ? ORDER BY fullname LIMIT 200");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $chat_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // chat messages (conversation history involving current user)
    $stmt = $conn->prepare("SELECT m.id, m.from_user_id, m.to_user_id, m.message, m.created_at, u.fullname AS from_name, u.picture AS from_avatar FROM chat_messages m JOIN users u ON m.from_user_id = u.id WHERE m.from_user_id = ? OR m.to_user_id = ? ORDER BY m.created_at ASC LIMIT 1000");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $chat_messages_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    

    $chat_messages = [];
    foreach ($chat_messages_raw as $m) {
        $other_id = ($m['from_user_id'] == $user_id) ? $m['to_user_id'] : $m['from_user_id'];
        if (!isset($chat_messages[$other_id])) $chat_messages[$other_id] = [];
        $chat_messages[$other_id][] = [
            'id' => (int)$m['id'],
            'from_user_id' => (int)$m['from_user_id'],
            'to_user_id' => (int)$m['to_user_id'],
            'message' => $m['message'],
            'created_at' => $m['created_at'],
            'from_name' => $m['from_name'],
            'from_avatar' => $m['from_avatar']
        ];
    }

    $conn->close();

    json_response([
        'user' => $user,
        'workouts' => $workouts,
        'memberships' => $memberships,
        'payments' => $payments,
        'notifications' => $notifications,
        'feed_posts' => $feed_posts,
        'chat_users' => $chat_users,
        'chat_messages' => $chat_messages,
    ]);
}

// Handle POST actions (mutating operations)
if ($method === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $conn = getDbConnection();

    if ($action === 'update_profile') {
        $fullName = clean_input($_POST['fullName'] ?? '');
        $age = intval($_POST['age'] ?? 0) ?: null;
        $gender = clean_input($_POST['gender'] ?? '');
        $fitnessGoals = clean_input($_POST['fitnessGoals'] ?? '');
        $picture = clean_input($_POST['picture'] ?? '');

        if (empty($fullName)) {
            json_response(['error' => 'Full name required.'], 400);
        }

        $stmt = $conn->prepare("UPDATE users SET fullname = ?, age = ?, gender = ?, fitness_goals = ?, picture = ? WHERE id = ?");
        $stmt->bind_param('sisssi', $fullName, $age, $gender, $fitnessGoals, $picture, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();

        if ($ok) json_response(['success' => true]);
        else json_response(['error' => 'Failed to update profile.'], 500);
    }

    if ($action === 'add_workout') {
        $title = clean_input($_POST['title'] ?? '');
        $date = clean_input($_POST['date'] ?? '');
        $time = clean_input($_POST['time'] ?? '');
        $type = clean_input($_POST['type'] ?? 'Other');
        $recurring = isset($_POST['recurring']) && $_POST['recurring'] == '1' ? 1 : 0;

        if (!$title || !$date || !$time) {
            json_response(['error' => 'Missing required fields for workout.'], 400);
        }

        $stmt = $conn->prepare("INSERT INTO workouts (user_id, title, date, time, type, recurring) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssi', $user_id, $title, $date, $time, $type, $recurring);
        $ok = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        if ($ok) json_response(['success' => true, 'id' => $insert_id, 'title'=>$title,'date'=>$date,'time'=>$time,'type'=>$type,'recurring'=>$recurring]);
        else json_response(['error' => 'Failed to add workout.'], 500);
    }

    if ($action === 'post_feed') {
        // Accept content (optional if image or link provided), optional uploaded file 'image_file', optional 'link'
        $content = trim($_POST['content'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $imagePath = null;

        // Handle image upload (image_file)
        if (isset($_FILES['image_file']) && isset($_FILES['image_file']['tmp_name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
            $file = $_FILES['image_file'];
            // basic validations
            if ($file['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $conn->close();
                    json_response(['error' => 'Unsupported image type.'], 400);
                }
                $ext = $allowed[$mime];
                $upload_dir = __DIR__ . '/uploads/feed_images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $filename = uniqid('feed_') . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // store web-accessible relative path
                    $imagePath = 'uploads/feed_images/' . $filename;
                } else {
                    $conn->close();
                    json_response(['error' => 'Failed to save uploaded image.'], 500);
                }
            } else {
                $conn->close();
                json_response(['error' => 'Image upload error.'], 400);
            }
        }

        // require at least some content, link or image
        if ($content === '' && $imagePath === null && $link === '') {
            $conn->close();
            json_response(['error' => 'Post content required.'], 400);
        }

        // If a link is provided, append it to the content (DB doesn't have a link column)
        if ($link !== '') {
            $content = $content . ($content !== '' ? "\n\n" : '') . $link;
        }

        $content_clean = clean_input($content);
        $image_clean = $imagePath ? clean_input($imagePath) : null;

        $stmt = $conn->prepare("INSERT INTO feed_posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $user_id, $content_clean, $image_clean);
        $ok = $stmt->execute();
        $insert_id = $stmt->insert_id;

        if ($ok) {
            $stmt2 = $conn->prepare("SELECT f.id, f.content, f.image, f.created_at, u.id AS author_id, u.fullname AS author_name, u.picture AS author_avatar FROM feed_posts f JOIN users u ON f.user_id = u.id WHERE f.id = ?");
            $stmt2->bind_param('i', $insert_id);
            $stmt2->execute();
            $post = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $conn->close();
            json_response(['success' => true, 'post' => $post]);
        } else {
            $conn->close();
            json_response(['error' => 'Failed to save post.'], 500);
        }
    }

    if ($action === 'send_message') {
        $to_user = intval($_POST['to_user'] ?? 0);
        $message = clean_input($_POST['message'] ?? '');

        if ($to_user <= 0 || !$message) json_response(['error' => 'Invalid message payload.'], 400);

        $stmt = $conn->prepare("INSERT INTO chat_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $user_id, $to_user, $message);
        $ok = $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();

        if ($ok) {
            $stmt2 = $conn->prepare("SELECT m.id, m.from_user_id, m.to_user_id, m.message, m.created_at, u.fullname AS from_name, u.picture AS from_avatar FROM chat_messages m JOIN users u ON m.from_user_id = u.id WHERE m.id = ?");
            $stmt2->bind_param('i', $insert_id);
            $stmt2->execute();
            $msg = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            $conn->close();
            json_response(['success' => true, 'message' => $msg]);
        } else {
            $conn->close();
            json_response(['error' => 'Failed to send message.'], 500);
        }
    }

    if ($action === 'mark_notification') {
        $nid = intval($_POST['notification_id'] ?? 0);
        $is_read = isset($_POST['is_read']) ? intval($_POST['is_read']) : 1;
        if ($nid <= 0) json_response(['error' => 'Invalid notification id.'], 400);

        $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('iii', $is_read, $nid, $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        json_response(['success' => (bool)$ok]);
    }

    if ($action === 'clear_notifications') {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        json_response(['success' => (bool)$ok]);
    }

    if ($action === 'purchase_membership') {
        // Allow server-side to accept first_name, last_name, email, plan, amount, payment_method
        $plan = clean_input($_POST['plan'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $first_name = clean_input($_POST['first_name'] ?? '');
        $last_name = clean_input($_POST['last_name'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $payment_method = clean_input($_POST['payment_method'] ?? 'GCash');

        if (!$plan || $amount <= 0 || !$first_name || !$email) json_response(['error' => 'Invalid payment data.'], 400);

        $stmt = $conn->prepare("INSERT INTO payments (user_id, plan, amount, first_name, last_name, email, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed')");
        $stmt->bind_param('isdssss', $user_id, $plan, $amount, $first_name, $last_name, $email, $payment_method);
        $ok1 = $stmt->execute();
        $stmt->close();

        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+1 year'));
        $status = 'active';
        $stmt2 = $conn->prepare("INSERT INTO memberships (user_id, plan, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param('issss', $user_id, $plan, $start, $end, $status);
        $ok2 = $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("UPDATE users SET membership_status = 'active' WHERE id = ?");
        $stmt3->bind_param('i', $user_id);
        $ok3 = $stmt3->execute();
        $stmt3->close();

        $conn->close();

        if ($ok1 && $ok2 && $ok3) json_response(['success' => true]);
        else json_response(['error' => 'Failed to process membership.'], 500);
    }

    // Unknown action
    $conn->close();
    json_response(['error' => 'Unknown action.'], 400);
}

// Logout endpoint (GET)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// If execution continues, render the HTML UI (front-end will call ?action=initial_data)
?>
<!DOCTYPE html>
<html lang="en" x-data="versoGymApp()" x-init="initTheme(); loadInitialData()">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Verso Gym - Customer Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Request permission for notifications
    function requestNotificationPermission() {
        if (!("Notification" in window)) {
            console.log("This browser does not support notifications.");
            return;
        }

        Notification.requestPermission().then((permission) => {
            if (permission === "granted") {
                console.log("Notification permission granted.");
                // You can now send notifications
            } else if (permission === "denied") {
                console.log("Notification permission denied.");
            } else if (permission === "default") {
                console.log("Notification permission dismissed.");
            }
        });
    }

    // Function to display a notification
    function showNotification(title, message, icon) {
        if (Notification.permission === "granted") {
            new Notification(title, {
                body: message,
                icon: icon || 'img/logo.png', // Default icon if none provided
            });
        }
    }
</script>

<style type="text/tailwindcss">
 :root {
        --background-dark: #0c0a09;
        --background-light: #f7f7f7;
        --text-dark-primary: #f2f2f2;
        --text-dark-secondary: #a3a3a3;
        --text-light-primary: #000000;
        --text-light-secondary: #000000;
        --glass-bg-dark: rgba(28, 25, 23, 0.6);
        --glass-bg-light: rgba(255, 255, 255, 0.7);
        --glass-border-dark: rgba(255, 255, 255, 0.1);
        --glass-border-light: rgba(0, 0, 0, 0.05);
        --card-bg-dark: rgba(41, 37, 36, 0.5);
        --card-bg-light: #ffffff;
        --notification-border-light: #e5e7eb;
    }
    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        transition: background-color 0.5s ease;
    }
    .dark body {
        background-color: var(--background-dark);
        color: var(--text-dark-primary);
    }
    body {
        background-color: var(--background-light);
        color: var(--text-light-primary);
    }
    .spotlight-bg::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        pointer-events: none;
        z-index: -1;
    }
    .dark .spotlight-bg::before {
        background: radial-gradient(circle at center, rgba(249, 115, 22, 0.1) 0%, rgba(12, 10, 9, 0) 60%);
        animation: pulse-bg 15s infinite alternate;
    }
    .light .spotlight-bg::before {
        background: radial-gradient(circle at center, rgba(249, 115, 22, 0.05) 0%, rgba(247, 247, 247, 0) 60%);
        animation: pulse-bg 15s infinite alternate;
    }
    @keyframes pulse-bg {
        0% { transform: scale(1); opacity: 0.8; }
        100% { transform: scale(1.1); opacity: 1; }
    }
    .glassmorphism {
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: background-color 0.5s ease, border-color 0.5s ease;
    }
    .dark .glassmorphism {
        background-color: var(--glass-bg-dark);
        border: 1px solid var(--glass-border-dark);
    }
    .light .glassmorphism {
        background-color: var(--glass-bg-light);
        border: 1px solid var(--glass-border-light);
    }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        transition: color 0.3s ease;
    }
    .orange-gradient-text {
        background: -webkit-linear-gradient(45deg, #f97316, #fb923c, #fdba74);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .progress-ring-circle {
        transition: stroke-dashoffset 0.5s ease-in-out;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }
    .orange-gradient-bg {
        background: linear-gradient(135deg, #f97316, #ea580c);
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;  
        scrollbar-width: none;  
    }
    .theme-switch-wrapper {
        display: flex;
        align-items: center;
    }
    .theme-switch {
        display: inline-block;
        height: 24px;
        position: relative;
        width: 48px;
    }
    .theme-switch input {
        display:none;
    }
    .slider {
        background-color: #a8a29e;
        bottom: 0;
        cursor: pointer;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        transition: .4s;
        border-radius: 24px;
    }
    .dark .slider {
        background-color: #4b5563;
    }
    .slider:before {
        background-color: #fff;
        bottom: 2px;
        content: "";
        height: 20px;
        left: 2px;
        position: absolute;
        transition: .4s;
        width: 20px;
        border-radius: 50%;
    }
    input:checked + .slider {
        background: linear-gradient(135deg, #f97316, #ea580c);
    }
    input:checked + .slider:before {
        transform: translateX(24px);
    }
    .toggle-icon {
        color: #a3a3a3;transition: color 0.3s ease;
    }
    .light .toggle-icon {
        color: #a8a29e;}
    .toggle-icon.active {
        color: #f97316;}
    .nav-link {
        color: var(--text-light-secondary);
    }
    .dark .nav-link {
        color: var(--text-dark-secondary);
    }
    .nav-link:hover {
        color: var(--text-light-primary);
        background-color: rgba(0, 0, 0, 0.05);
    }
    .dark .nav-link:hover {
        color: var(--text-dark-primary);
        background-color: rgba(255, 255, 255, 0.05);
    }
    .nav-link.active {
        color: #f97316;
        background-color: rgba(249, 115, 22, 0.1);
    }
    .dark .nav-link.active {
        color: #ffffff;
        background-color: rgba(249, 115, 22, 0.2);
    }
    .dark .nav-link.active .material-symbols-outlined {
        color: #fb923c;
    }
    .light .nav-link.active .material-symbols-outlined {
        color: #f97316;
    }
    .card-item {
        background-color: var(--card-bg-light);
        transition: background-color 0.5s ease;
    }
    .dark .card-item {
        background-color: var(--card-bg-dark);
    }
    .light .text-stone-900 { color: var(--text-light-primary); }
    .light .text-stone-800 { color: var(--text-light-primary); }
    .light .text-stone-500 { color: var(--text-light-secondary); }
    .light .text-stone-600 { color: var(--text-light-secondary); }
    .scrollbar-hide {
        -ms-overflow-style: none;  
        scrollbar-width: none;  
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    /* Additional styling for chat dropdowns */
    .user-item.selected {
        background-color: rgba(249, 115, 22, 0.1);
        border: 1px solid rgba(249, 115, 22, 0.5);
    }
    .group .dropdown {
        display: none;
    }
    .group:hover .dropdown, .group:focus-within .dropdown {
        display: block;
    }
    /* Membership modal styles */
    #payment-modal, #active-membership-view, #gym-fees-modal, #purchase-success-modal { display: none; }
    .tier-option:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    .tier-option.selected {
        border-color: #f97316;
        background-color: rgba(249, 115, 22, 0.1);
    }
    /* Notification specific styles */
    .notification-item {
        border-color: transparent;
        transition: border-color 0.5s ease;
    }
    .light .notification-item {
        border: 1px solid var(--notification-border-light);
    }
    .filter-btn {
        @apply px-4 py-2 rounded-full text-sm font-medium transition-colors duration-300;
    }
    .filter-btn-active {
        @apply bg-orange-500 text-white shadow-md;
    }
    .filter-btn-inactive {
        @apply bg-stone-100 dark:bg-stone-800/50 text-stone-600 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700/50;
    }
    /* Profile UI no 2 - edit form input styles */
    .edit-form-input {
        background-color: rgba(28, 25, 23, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #f2f2f2;
    }
    .edit-form-input:focus {
        border-color: #f97316;
        outline: none;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.5);
    }
</style>
</head>
<body class="font-sans spotlight-bg">
<div class="flex w-full h-full relative">

<?php
if (isset($_SESSION['booking_after_register']) && $_SESSION['booking_after_register']) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Registration & Booking Successful!', 'Your account is created and your booking has been submitted.', 'success');
        });
    </script>";
    // Unset the session variable so it doesn't show again on refresh
    unset($_SESSION['booking_after_register']);
}
?>

<!-- Sidebar -->
<aside class="w-64 flex-shrink-0 glassmorphism p-6 flex flex-col justify-between h-full sticky top-0">
    <div>
        <div class="flex items-center gap-3 mb-10">
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white"><img src="img/logo.png" alt="Verso Gym Logo" class="w-12 h-12 mr-2 inline-block">Verso Gym</h1>
        </div>
        <nav class="space-y-3">
            <a href="#" @click.prevent="setView('dashboard')" :class="{'active': currentView==='dashboard'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-semibold">Dashboard</span>
            </a>
            <a href="#" @click.prevent="setView('schedule')" :class="{'active': currentView==='schedule'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">event</span>
                <span>My Schedule</span>
            </a>
            <a href="#" @click.prevent="setView('membership')" :class="{'active': currentView==='membership'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">credit_card</span>
                <span>Membership</span>
            </a>
            <a href="#" @click.prevent="setView('chat')" :class="{'active': currentView==='chat'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">chat</span>
                <span>Chat</span>
            </a>
            <a href="#" @click.prevent="setView('notifications')" :class="{'active': currentView==='notifications'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">notifications</span>
                <span>Notifications</span>
            </a>
            <a href="#profile" @click.prevent="setView('profile')" :class="{'active': currentView==='profile'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">person</span>
                <span>Profile</span>
            </a>
            <a href="#" @click.prevent="setView('feeds')" :class="{'active': currentView==='feeds'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">dynamic_feed</span>
                <span>Feeds</span>
            </a>
            <a href="#" @click.prevent="setView('settings')" :class="{'active': currentView==='settings'}" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
                <span class="material-symbols-outlined">settings</span>
                <span>Settings</span>
            </a>
        </nav>
    </div>
<div class="space-y-4">
    <a href="index.php" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
        <span class="material-symbols-outlined">home</span>
        <span>Back to main</span>
    </a>
    <a :href="'?action=logout'" class="flex items-center gap-3 px-4 py-2 rounded-lg nav-link transition-colors duration-300">
        <span class="material-symbols-outlined">logout</span>
        <span>Logout</span>
    </a>
</div>
</aside>

<!-- Main content -->
<main class="flex-1 p-8 flex flex-col h-full overflow-y-auto">
<!-- Header -->
<header class="flex justify-between items-center mb-8 flex-shrink-0" x-show="!['membership','profile'].includes(currentView)">
    <h2 class="text-3xl font-bold text-stone-900 dark:text-white" x-text="headerTitle"></h2>
    <div class="flex items-center gap-4" x-show="!['membership','profile'].includes(currentView)">
 <div class="theme-switch-wrapper">
            <span class="material-symbols-outlined toggle-icon light_mode" :class="{active: !isDark}">light_mode</span>
            <label class="theme-switch mx-2" for="theme-checkbox">
                <input id="theme-checkbox" type="checkbox" @change="toggleTheme()" :checked="isDark"/>
                <div class="slider round"></div>
            </label>
            <span class="material-symbols-outlined toggle-icon dark_mode" :class="{active: isDark}">dark_mode</span>
        </div>
        <template x-if="currentView==='dashboard'">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-stone-500">search</span>
                <input class="pl-10 pr-4 py-2 w-64 rounded-full bg-stone-100 dark:bg-stone-800/50 text-stone-900 dark:text-white placeholder-stone-500 dark:placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-orange-500 ring-offset-2 ring-offset-background-light dark:ring-offset-background-dark transition-all duration-300" placeholder="Search..." type="text" x-model="dashboardSearch"/>
            </div>
        </template>
        <template x-if="currentView==='dashboard'">
            <div class="flex items-center gap-3">
                <img alt="User Avatar" class="w-10 h-10 rounded-full border-2 border-orange-500" :src="profile.avatar"/>
                <div>
                    <p class="font-semibold text-stone-900 dark:text-white" x-text="profile.fullName"></p>
                    <p class="text-xs text-stone-500 dark:text-gray-400">Member</p>
                </div>
            </div>
        </template>
            </div>
</header>

<!-- Dashboard -->
<section x-show="currentView==='dashboard'" x-transition class="flex-1 grid grid-cols-1 @container lg:grid-cols-3 gap-6 overflow-y-auto scrollbar-hide">
    <div class="lg:col-span-2 space-y-6">
        <section class="glassmorphism p-6 rounded-2xl">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-semibold text-stone-900 dark:text-white">Fitness Journey</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400">Your progress this month</p>
                </div>
                <div class="flex items-center gap-2 p-2 rounded-lg card-item">
                    <span class="material-symbols-outlined text-orange-500">local_fire_department</span>
                    <span class="font-semibold text-lg text-stone-800 dark:text-white">12</span>
                    <span class="text-sm text-stone-500 dark:text-stone-400">Day Streak</span>
                </div>
            </div>
            <div class="grid grid-cols-1 @lg:grid-cols-3 gap-4 text-center">
                <div class="card-item p-4 rounded-xl flex flex-col justify-center items-center">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle class="text-stone-200 dark:text-stone-800/70" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-width="8"></circle>
                            <circle class="progress-ring-circle text-orange-500" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-dasharray="282.6" stroke-dashoffset="56.52" stroke-linecap="round" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold orange-gradient-text">16/20</span>
                        </div>
                    </div>
                    <h4 class="mt-3 font-semibold text-stone-800 dark:text-white">Monthly Workouts</h4>
                    <p class="text-xs text-stone-500 dark:text-gray-400">4 workouts left</p>
                </div>
                <div class="card-item p-4 rounded-xl flex flex-col justify-center items-center">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle class="text-stone-200 dark:text-stone-800/70" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-width="8"></circle>
                            <circle class="progress-ring-circle text-blue-500" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-dasharray="282.6" stroke-dashoffset="113.04" stroke-linecap="round" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-blue-500 dark:text-blue-400">3/5</span>
                        </div>
                    </div>
                    <h4 class="mt-3 font-semibold text-stone-800 dark:text-white">Goals Achieved</h4>
                    <p class="text-xs text-stone-500 dark:text-gray-400">2 goals remaining</p>
                </div>
                <div class="card-item p-4 rounded-xl flex flex-col justify-center items-center">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle class="text-stone-200 dark:text-stone-800/70" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-width="8"></circle>
                            <circle class="progress-ring-circle text-green-500" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-dasharray="282.6" stroke-dashoffset="28.26" stroke-linecap="round" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-green-500 dark:text-green-400">90%</span>
                        </div>
                    </div>
                    <h4 class="mt-3 font-semibold text-stone-800 dark:text-white">Activity Level</h4>
                    <p class="text-xs text-stone-500 dark:text-gray-400">Above average</p>
                </div>
            </div>
        </section>
        <section class="glassmorphism p-6 rounded-2xl flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-stone-900 dark:text-white">My Schedule</h3>
                <a class="text-sm text-orange-500 dark:text-orange-400 hover:text-orange-600 dark:hover:text-orange-300 transition-colors" href="#" @click.prevent="setView('schedule')">View Schedule</a>
            </div>
            <div class="flex-grow space-y-4">
                <!-- use x-text instead of moustache for icons -->
                <template x-for="item in dashboardSchedule" :key="item.title">
                    <div class="flex items-center p-4 card-item rounded-lg">
                        <div :class="item.colorBg" class="p-3 rounded-lg mr-4 flex items-center justify-center">
                            <span class="material-symbols-outlined" :class="item.colorText" style="font-size: 24px;" x-text="item.icon"></span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-stone-800 dark:text-white" x-text="item.title"></p>
                            <p class="text-sm text-stone-500 dark:text-gray-400" x-text="item.datetime"></p>
                        </div>
                        <button class="p-2 rounded-full hover:bg-stone-200/50 dark:hover:bg-white/10 transition-colors">
                            <span class="material-symbols-outlined text-stone-600 dark:text-stone-300">more_vert</span>
                        </button>
                    </div>
                </template>
            </div>
        </section>
    </div>
    <div class="space-y-6">
        <section class="glassmorphism p-6 rounded-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-stone-900 dark:text-white">Upcoming Classes</h3>
                <a class="text-sm text-orange-500 dark:text-orange-400 hover:text-orange-600 dark:hover:text-orange-300 transition-colors" href="#">View All</a>
            </div>
            <div class="space-y-4">
                <template x-for="item in dashboardUpcomingClasses" :key="item.title">
                    <div class="flex items-center p-4 card-item rounded-lg">
                        <div :class="item.colorBg" class="p-3 rounded-lg mr-4 flex items-center justify-center">
                            <span class="material-symbols-outlined" :class="item.colorText" style="font-size: 28px;" x-text="item.icon"></span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-stone-800 dark:text-white" x-text="item.title"></p>
                            <p class="text-sm text-stone-500 dark:text-gray-400" x-text="item.datetime"></p>
                        </div>
                        <button class="p-2 rounded-full hover:bg-stone-200/50 dark:hover:bg-white/10 transition-colors">
                            <span class="material-symbols-outlined text-stone-600 dark:text-stone-300">more_vert</span>
                        </button>
                    </div>
                </template>
            </div>
            <button class="w-full mt-4 py-2.5 font-semibold bg-stone-200/50 hover:bg-stone-300/60 dark:bg-white/10 dark:hover:bg-white/20 text-stone-800 dark:text-white transition-colors rounded-lg flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">add</span> Book New Class
            </button>
        </section>
        <section class="glassmorphism p-6 rounded-2xl flex flex-col items-center text-center">
            <h3 class="text-xl font-semibold mb-4 text-stone-900 dark:text-white">Weekly Workout Goal</h3>
            <div class="relative w-40 h-40">
                <svg class="w-full h-full" viewBox="0 0 100 100">
                    <circle class="text-stone-200 dark:text-stone-800/70" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-width="10"></circle>
                    <circle class="progress-ring-circle text-orange-500" cx="50" cy="50" fill="transparent" r="45" stroke="currentColor" stroke-dasharray="282.6" stroke-dashoffset="56.52" stroke-linecap="round" stroke-width="10"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-bold orange-gradient-text">4/5</span>
                    <span class="text-sm text-stone-500 dark:text-gray-400">Workouts</span>
                </div>
            </div>
            <p class="mt-4 text-sm text-stone-600 dark:text-gray-300">Great job! Just one more workout to hit your weekly goal.</p>
        </section>
    </div>
</section>

<!-- My Schedule (unchanged aside from small structure fixes) -->
<section x-show="currentView==='schedule'" x-transition class="flex-1 flex flex-col h-full overflow-hidden">
    <div class="flex justify-between items-center mb-8 flex-shrink-0">
        <h2 class="text-3xl font-bold text-stone-900 dark:text-white">My Schedule</h2>
        <div>
            <button @click="openAddWorkoutModal()" class="orange-gradient-bg text-white px-4 py-2 rounded-full font-semibold flex items-center gap-2 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined">add</span>
                Add New Workout
            </button>
        </div>
    </div>
    <div class="flex-1 flex flex-col glassmorphism p-6 rounded-2xl overflow-hidden">
        <div class="flex justify-between items-center mb-6 flex-shrink-0">
            <div class="flex items-center gap-4">
                <button @click="navigateSchedulePrev()" class="p-2 rounded-full hover:bg-stone-200/10 dark:hover:bg-white/10 transition-colors">
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <h3 class="text-xl font-semibold text-stone-900 dark:text-white w-48 text-center" x-text="scheduleHeader"></h3>
                <button @click="navigateScheduleNext()" class="p-2 rounded-full hover:bg-stone-200/10 dark:hover:bg-white/10 transition-colors">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
            <div class="flex items-center gap-2 bg-stone-700/50 p-1 rounded-full">
                <button :class="{'orange-gradient-bg text-white': scheduleView === 'monthly', 'hover:bg-stone-200/10 dark:hover:bg-white/10 text-stone-400 dark:text-stone-300': scheduleView !== 'monthly'}" @click="scheduleView = 'monthly'" class="px-4 py-1.5 rounded-full text-sm font-semibold transition-colors">Monthly</button>
                <button :class="{'orange-gradient-bg text-white': scheduleView === 'weekly', 'hover:bg-stone-200/10 dark:hover:bg-white/10 text-stone-400 dark:text-stone-300': scheduleView !== 'weekly'}" @click="scheduleView = 'weekly'" class="px-4 py-1.5 rounded-full text-sm font-semibold transition-colors">Weekly</button>
                <button :class="{'orange-gradient-bg text-white': scheduleView === 'daily', 'hover:bg-stone-200/10 dark:hover:bg-white/10 text-stone-400 dark:text-stone-300': scheduleView !== 'daily'}" @click="scheduleView = 'daily'" class="px-4 py-1.5 rounded-full text-sm font-semibold transition-colors">Daily</button>
            </div>
        </div>

        <!-- Monthly View -->
        <div class="flex flex-col flex-1" x-show="scheduleView === 'monthly'" x-transition>
            <div class="grid grid-cols-7 gap-1 flex-shrink-0 mb-2">
                <template x-for="dayName in ['SUN','MON','TUE','WED','THU','FRI','SAT']" :key="dayName">
                    <div class="text-center font-semibold text-stone-500 dark:text-stone-400 py-2 text-sm" x-text="dayName"></div>
                </template>
            </div>
            <div class="grid grid-cols-7 grid-rows-6 gap-2 flex-1 overflow-y-auto scrollbar-hide">
                <template x-for="day in calendarDays" :key="day.date ? day.date : 'empty-'+Math.random()">
                    <div :class="{'dark:bg-[rgba(41,37,36,0.2)] bg-stone-50/50': !day.isCurrentMonth, 'border-orange-500': day.isToday, 'border border-transparent rounded-lg p-2 flex flex-col min-h-[100px] group cursor-pointer': true}" class="card-item" @click="day.isCurrentMonth && openAddWorkoutModal(day.dateISO)">
                        <span :class="day.isCurrentMonth ? 'text-stone-800 dark:text-white font-semibold' : 'text-stone-400 dark:text-stone-600' " x-text="day.date"></span>
                        <template x-if="getWorkout(day.date) && day.isCurrentMonth">
                            <div :class="getWorkoutColor(getWorkoutsForDay(day.date)[0].type)" class="mt-1 text-xs rounded px-1.5 py-1 text-left leading-tight truncate" x-text="getWorkout(day.date)"></div>
                        </template>
                        <button x-show="day.date !== null && day.isCurrentMonth" @click.stop="openAddWorkoutModal(day.dateISO)" class="mt-auto w-full flex items-center justify-center text-stone-400 dark:text-stone-600 hover:text-orange-500 dark:hover:text-orange-400 transition-colors opacity-0 group-hover:opacity-100">
                            <span class="material-symbols-outlined text-lg">add_circle</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Weekly View -->
        <div class="flex-1 overflow-y-auto scrollbar-hide" x-show="scheduleView === 'weekly'" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                <template x-for="day in weeklySchedule" :key="day.date.toISOString()">
                    <div class="glassmorphism border border-transparent rounded-lg p-4 flex flex-col">
                        <div class="flex justify-between items-center mb-4">
                            <span class="font-bold text-white" x-text="day.dayName"></span>
                            <span class="font-semibold text-stone-300" x-text="day.dayNum"></span>
                        </div>
                        <div class="space-y-3">
                            <template x-if="day.workouts.length > 0">
                                <template x-for="workout in day.workouts" :key="workout.title">
                                    <div :class="getWorkoutColor(workout.type)" class="p-3 rounded-lg">
                                        <p class="font-semibold text-sm truncate" x-text="workout.title"></p>
                                        <p class="text-xs text-white/70" x-text="workout.time"></p>
                                    </div>
                                </template>
                            </template>
                            <template x-if="day.workouts.length === 0">
                                <div class="text-center text-stone-500 text-sm py-4">No workouts</div>
                            </template>
                        </div>
                        <button @click="openAddWorkoutModal(day.date.toISOString().slice(0,10))" class="mt-auto pt-4 flex items-center justify-center text-stone-400 dark:text-orange-500 hover:text-orange-500 dark:hover:text-orange-400 transition-colors">
                            <span class="material-symbols-outlined text-xl">add</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Daily View -->
        <div class="flex-1 overflow-y-auto scrollbar-hide" x-show="scheduleView === 'daily'" x-transition>
            <div class="space-y-4">
                <template x-if="dailySchedule.workouts.length > 0">
                    <template x-for="workout in dailySchedule.workouts" :key="workout.title">
                        <div class="flex items-center gap-4 p-4 rounded-lg glassmorphism">
                            <div class="text-lg font-bold text-orange-400" x-text="workout.time"></div>
                            <div :class="getWorkoutColor(workout.type)" class="w-1 h-12 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-semibold text-white" x-text="workout.title"></p>
                                <p class="text-sm text-stone-400" x-text="workout.type"></p>
                            </div>
                            <button class="p-2 rounded-full hover:bg-white/10">
                                <span class="material-symbols-outlined">more_vert</span>
                            </button>
                        </div>
                    </template>
                </template>
                <template x-if="dailySchedule.workouts.length === 0">
                    <div class="text-center py-16 text-stone-500">
                        <span class="material-symbols-outlined text-6xl">relax</span>
                        <p class="mt-4 text-lg">No workouts scheduled for today.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</section>

<section x-show="currentView==='membership'" x-transition class="flex-1 p-8 flex flex-col h-full overflow-y-auto scrollbar-hide">
    <header class="flex justify-between items-center mb-8 flex-shrink-0">
        <h2 class="text-3xl font-bold text-white">Membership &amp; Plans</h2>
        <div class="flex items-center gap-4">
            <div>
                <img alt="User Avatar" class="w-10 h-10 rounded-full border-2 border-orange-500" :src="profile.avatar"/>
            </div>
        </div>
    </header>
    <div id="membership-plans" class="overflow-y-auto">
        <div class="mb-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="glassmorphism rounded-2xl p-6 flex flex-col h-full">
                    <h3 class="text-2xl font-bold text-white">Coaching Fees</h3>
                    <p class="text-stone-400 mt-1 mb-4">Structured coaching packages available.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">₱150</span>
                        <span class="text-stone-400">/ session</span>
                    </div>
                    <div class="space-y-3 text-stone-300 flex-grow">
                        <p><span class="font-semibold text-white">1 Week:</span> ₱600</p>
                        <p><span class="font-semibold text-white">2 Weeks:</span> ₱1,200</p>
                        <p><span class="font-semibold text-white">1 Month:</span> ₱2,500</p>
                    </div>
                    <button @click="alert('Book Coaching feature coming soon')" class="mt-8 w-full bg-stone-700/50 hover:bg-stone-600/50 text-white font-semibold py-3 rounded-lg transition-colors duration-300">Book Coaching</button>
                </div>
                <div class="glassmorphism rounded-2xl p-6 flex flex-col h-full border-2 border-orange-600 shadow-2xl shadow-orange-700/20 relative overflow-hidden">
                    <div class="absolute top-0 -right-16 transform rotate-45 bg-orange-600 text-center text-white font-semibold py-1 w-48">MOST POPULAR</div>
                    <h3 class="text-2xl font-bold text-white">Membership</h3>
                    <p class="text-stone-400 mt-1 mb-4">Exclusive gym membership perks.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">₱850</span>
                        <span class="text-stone-400">/ year</span>
                    </div>
                    <ul class="space-y-3 text-stone-300 flex-grow">
                        <li class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-orange-500">check_circle</span>
                            <span>Free treadmill use</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-orange-500">check_circle</span>
                            <span>Access to member-only promos</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-orange-500">check_circle</span>
                            <span>Free Verso Gym T-shirt</span>
                        </li>
                    </ul>
                    <button @click="openPaymentModal('Membership', '₱850')" class="mt-8 w-full orange-gradient-bg text-white font-semibold py-3 rounded-lg hover:opacity-90 transition-opacity">Join Now</button>
                </div>
                <div class="glassmorphism rounded-2xl p-6 flex flex-col h-full relative">
                    <h3 class="text-2xl font-bold text-white">Gym Fees</h3>
                    <p class="text-stone-400 mt-1 mb-4">Flexible short-term gym access.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">₱60</span>
                        <span class="text-stone-400">/ walk-in</span>
                    </div>
                    <div class="space-y-3 text-stone-300 flex-grow">
                        <p><span class="font-semibold text-white">1 Week:</span> ₱250</p>
                        <p><span class="font-semibold text-white">2 Weeks:</span> ₱500 (+1 free visit)</p>
                        <p><span class="font-semibold text-white">1 Month:</span> ₱1,000 (+4 free visits)</p>
                        <hr class="border-stone-700 my-3"/>
                        <p><span class="font-semibold text-white">Treadmill only:</span> ₱30</p>
                        <p><span class="font-semibold text-white">Gym + treadmill:</span> ₱80</p>
                    </div>
                    <button @click="openGymFeesModal()" class="mt-8 w-full orange-gradient-bg text-white font-semibold py-3 rounded-lg hover:opacity-90 transition-opacity">Get Started</button>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-center text-white mb-2">Elevate your training with a Gym &amp; Coaching Package</h2>
            <p class="text-stone-400 text-center mb-8">Get the best of both worlds with our combined packages.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="glassmorphism rounded-2xl p-8 flex flex-col text-center items-center">
                    <h4 class="text-xl font-semibold text-white">1 Week</h4>
                    <p class="text-4xl font-bold text-white my-4">₱850</p>
                    <p class="text-stone-400 h-16">Includes personalized coaching sessions and full gym access.</p>
                    <button @click="openPaymentModal('1 Week Package', '₱850')" class="mt-6 w-full bg-stone-700/50 hover:bg-stone-600/50 text-white font-semibold py-3 rounded-lg transition-colors duration-300">Choose Plan</button>
                </div>
                <div class="glassmorphism rounded-2xl p-8 flex flex-col text-center items-center border-2 border-orange-500 relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                        <span class="bg-orange-600 text-white text-sm font-semibold px-4 py-1 rounded-full uppercase tracking-wider">Best Value</span>
                    </div>
                    <h4 class="text-xl font-semibold text-white">2 Weeks</h4>
                    <p class="text-4xl font-bold text-white my-4">₱1,700</p>
                    <p class="text-stone-400 h-16">Twice the time, twice the results — full coaching support.</p>
                    <button @click="openPaymentModal('2 Weeks Package', '₱1,700')" class="mt-6 w-full orange-gradient-bg text-white font-semibold py-3 rounded-lg hover:opacity-90 transition-opacity shadow-lg shadow-orange-700/30">Choose Plan</button>
                </div>
                <div class="glassmorphism rounded-2xl p-8 flex flex-col text-center items-center">
                    <h4 class="text-xl font-semibold text-white">1 Month</h4>
                    <p class="text-4xl font-bold text-white my-4">₱3,500</p>
                    <p class="text-stone-400 h-16">A complete month of coaching guidance and gym training.</p>
                    <button @click="openPaymentModal('1 Month Package', '₱3,500')" class="mt-6 w-full bg-stone-700/50 hover:bg-stone-600/50 text-white font-semibold py-3 rounded-lg transition-colors duration-300">Choose Plan</button>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Payment Modals (NEW) -->
<!-- Gym Fees Modal -->
<div x-show="showGymFeesModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
  <div @click.away="showGymFeesModal = false" class="glassmorphism w-full max-w-3xl rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-bold text-white">Choose a Gym Fee Tier</h3>
      <button @click="showGymFeesModal = false" class="p-2 rounded-full hover:bg-white/10">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <template x-for="tier in gymFeeTiers" :key="tier.name">
        <div class="p-4 rounded-lg cursor-pointer border" :class="{'border-orange-500 bg-orange-500/5': selectedGymFeeTier === tier.name}" @click="selectTier(tier)">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-semibold text-white" x-text="tier.name"></h4>
              <p class="text-sm text-stone-400" x-text="tier.note"></p>
            </div>
            <div class="text-lg font-bold text-white" x-text="tier.price"></div>
          </div>
        </div>
      </template>
    </div>
    <div class="mt-6 flex justify-end gap-3">
      <button @click="showGymFeesModal = false" class="px-4 py-2 rounded bg-stone-700/50 text-white">Cancel</button>
      <button :disabled="!selectedGymFeeTier" @click="openPaymentModal(selectedGymFeeTier, selectedGymFeePrice)" class="px-4 py-2 rounded orange-gradient-bg text-white disabled:opacity-50">Proceed</button>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div x-show="showPaymentModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
  <div @click.away="closePaymentModal()" class="glassmorphism w-full max-w-3xl rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-bold text-white">Checkout — <span class="font-medium" x-text="selectedGymFeeTier"></span></h3>
      <button @click="closePaymentModal()" class="p-2 rounded-full hover:bg-white/10">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <form @submit.prevent="onProceedPayment" class="space-y-3">
          <div>
            <label class="text-sm text-stone-400">First Name</label>
            <input x-model="paymentForm.firstName" required class="w-full rounded-lg p-3 bg-stone-900/50 text-white"/>
          </div>
          <div>
            <label class="text-sm text-stone-400">Last Name</label>
            <input x-model="paymentForm.lastName" required class="w-full rounded-lg p-3 bg-stone-900/50 text-white"/>
          </div>
          <div>
            <label class="text-sm text-stone-400">Email</label>
            <input x-model="paymentForm.email" type="email" required class="w-full rounded-lg p-3 bg-stone-900/50 text-white"/>
          </div>
          <div>
            <label class="text-sm text-stone-400">Phone</label>
            <input x-model="paymentForm.phone" required class="w-full rounded-lg p-3 bg-stone-900/50 text-white"/>
          </div>

          <fieldset class="mt-3">
            <legend class="text-sm text-stone-400 mb-2">Payment Method</legend>
            <div class="flex gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="pm" value="GCash" x-model="paymentForm.pm" class="hidden"/>
                <div :class="{'ring-2 ring-orange-400': paymentForm.pm === 'GCash'}" class="p-3 rounded-lg bg-white/5">
                  <img src="img/Gcash.png" alt="GCash" class="h-8"/>
                </div>
                <div class="text-sm text-white ml-2">GCash</div>
              </label>

            </div>
          </fieldset>

          <!-- dynamic area placeholder (QR or card inputs) -->
          <div class="mt-4">
            <div x-show="paymentForm.pm === 'GCash'" class="text-center">
              <template x-if="paymentForm.pm === 'GCash'">
                <div>
                  <img src="img/gcash-qr.jpg" alt="GCash QR" class="h-40 mx-auto mb-2"/>
                  <p class="text-xs text-stone-400 mb-2">Scan QR or copy number</p>
                  <button type="button" @click="copyNumber('09171234567')" class="px-3 py-1 rounded bg-orange-500 text-white">Copy Number</button>
                </div>
              </template>

            </div>


          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="closePaymentModal()" class="px-4 py-2 rounded bg-stone-700/50 text-white">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded orange-gradient-bg text-white">Proceed to Confirm</button>
          </div>
        </form>
      </div>

      <div class="bg-stone-900/50 rounded-lg p-4">
        <div class="mb-4">
          <div class="text-sm text-stone-400">Plan</div>
          <div class="text-lg font-bold text-white" x-text="selectedGymFeeTier"></div>
        </div>
        <div class="mb-4">
          <div class="text-sm text-stone-400">Amount</div>
          <div class="text-lg font-bold text-white" x-text="selectedGymFeePrice"></div>
        </div>
        <div class="mb-4">
          <div class="text-sm text-stone-400">Member</div>
          <div class="text-white font-medium" x-text="profile.fullName"></div>
        </div>

        <div class="mt-6">
          <h4 class="text-sm text-stone-400 mb-2">Payment Instructions</h4>
          <ul class="text-xs text-stone-400 space-y-2">
            <li>- For GCash: scan QR and include reference when confirming.</li>

          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div x-show="showPaymentConfirmModal" x-transition class="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4">
  <div class="glassmorphism w-full max-w-md rounded-2xl p-6">
    <div class="text-center mb-4">
      <h3 class="text-xl font-bold text-white">Confirm Your Payment</h3>
      <p class="text-sm text-stone-400">Please review your details before confirming.</p>
    </div>
    <div class="bg-white/5 p-4 rounded mb-4 text-sm space-y-2 text-stone-200">
      <div class="flex justify-between"><span>Name</span><span x-text="maskName(paymentForm.firstName + ' ' + paymentForm.lastName)"></span></div>
      <div class="flex justify-between"><span>Email</span><span x-text="maskEmail(paymentForm.email)"></span></div>
      <div class="flex justify-between"><span>Phone</span><span x-text="maskPhone(paymentForm.phone)"></span></div>
      <div class="flex justify-between"><span>Payment</span><span x-text="paymentForm.pm"></span></div>
      <div class="flex justify-between"><span>Plan</span><span x-text="selectedGymFeeTier"></span></div>
      <div class="flex justify-between"><span>Amount</span><span x-text="selectedGymFeePrice"></span></div>
    </div>
    <div class="flex justify-end gap-3">
      <button @click="showPaymentConfirmModal = false" class="px-4 py-2 rounded bg-stone-700/50 text-white">Back</button>
      <button @click="submitMembershipPayment()" class="px-4 py-2 rounded orange-gradient-bg text-white">Confirm &amp; Pay</button>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div x-show="showPaymentSuccessModal" x-transition class="fixed inset-0 z-70 flex items-center justify-center bg-black/50 p-4">
  <div class="glassmorphism w-full max-w-sm rounded-2xl p-6 text-center">
    <svg class="mx-auto mb-3" width="84" height="84" viewBox="0 0 52 52"><circle cx="26" cy="26" r="25" stroke="#fb923c" stroke-width="3" fill="none"/><path d="M14 27l7 7 16-16" stroke="#fb923c" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <h3 class="text-2xl font-bold text-white">Payment Successful!</h3>
    <p class="text-sm text-stone-400 mt-2">Your membership has been activated. Redirecting...</p>
    <div class="mt-6">
      <button @click="backToDashboard()" class="px-4 py-2 rounded orange-gradient-bg text-white">Back to Dashboard</button>
    </div>
  </div>
</div>
<!-- Chat (unchanged) -->
<section x-show="currentView==='chat'" x-transition class="flex-1 p-8 flex gap-8 h-full overflow-hidden">
    <!-- Chat Sidebar -->
    <div class="w-96 flex-shrink-0 glassmorphism rounded-2xl p-6 flex flex-col h-full">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-white">New Chat</h3>
            <button @click="clearSelectedChat()" class="text-stone-400 hover:text-white transition-colors" :disabled="!selectedChatUser">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="relative mb-6">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-stone-500">search</span>
            <input type="text" placeholder="Search by name or username" class="w-full bg-stone-900/70 border border-stone-800/50 rounded-xl pl-12 pr-4 py-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-300" x-model="chatSearchQuery" @input="filterChatUsers"/>
        </div>
        <div class="flex-1 overflow-y-auto -mr-3 pr-3 scrollbar-hide">
            <div class="space-y-3">
                <h4 class="text-sm font-semibold text-stone-400 uppercase tracking-wider mb-3">Suggestions</h4>
                <template x-for="user in filteredChatUsers" :key="user.id">
                    <div 
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-stone-800/50 transition-colors duration-200 cursor-pointer user-item"
                        :class="{'selected': selectedChatUser && selectedChatUser.id === user.id}"
                        @click="selectChatUser(user)"
                    >
                        <div class="flex items-center gap-4">
                            <img :alt="user.name" :src="user.avatar" class="w-12 h-12 rounded-full"/>
                            <div>

                                <!-- Make sure the text is displayed properly -->

                                <h5 class="font-semibold text-white" x-text="user.name"></h5>
                                <p class="text-sm text-stone-400" x-text="'@' + user.username"></p>
                            </div>
                        </div>

                        <!-- Add the indicator to the HTML -->

                        <!-- Add the indicator to the HTML -->

                        <!--  badge to display the unread messages -->

                        <template x-if="unreadMessagesCount[user.id] > 0">
                            <span class="px-2 py-1 ml-2 text-xs font-bold text-orange-500 bg-orange-100 rounded-full" x-text="unreadMessagesCount[user.id]"></span>
                        </template>
                        
                        <div class="relative group">
                            <button class="text-stone-400 hover:text-orange-400 p-1 rounded-full" @click.stop="toggleUserDropdown(user.id)">
                                <span class="material-symbols-outlined">more_vert</span>
                            </button>
                            <div class="dropdown absolute right-0 mt-1 w-40 bg-stone-900 border border-stone-800/50 rounded-lg shadow-lg z-10 glassmorphism" x-show="userDropdownOpen === user.id" @click.away="userDropdownOpen = null" x-transition>
                                <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-stone-300 hover:bg-stone-800/50 rounded-t-lg transition-colors duration-200" @click.prevent="viewUserProfile(user)">
                                    <span class="material-symbols-outlined text-base">person</span>View Profile
                                </a>
                                <button class="w-full flex items-center gap-3 text-left px-3 py-2 rounded-lg text-red-400 hover:bg-red-500/10 transition-colors duration-200" @click.prevent="blockUser(user)">
                                    <span class="material-symbols-outlined text-base">block</span>Block User
                                </button>
                                <button class="w-full flex items-center gap-3 text-left px-3 py-2 rounded-lg text-yellow-400 hover:bg-yellow-500/10 transition-colors duration-200" @click.prevent="reportUser(user)">
                                    <span class="material-symbols-outlined text-base">report</span>Report User
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="filteredChatUsers.length === 0">
                    <div class="text-center text-stone-500 py-6">No users found.</div>
                </template>
            </div>
        </div>
        <div class="mt-auto pt-6">
            <button class="w-full orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity" :disabled="!selectedChatUser" @click="startConversation">
                <span class="material-symbols-outlined">chat</span>
                Start a Conversation
            </button>
        </div>
    </div>

    <!-- Chat Conversation Panel -->
    <main class="flex-1 flex flex-col h-full glassmorphism rounded-2xl overflow-hidden relative" x-show="selectedChatUser" x-transition>
        <div class="flex-shrink-0 p-4 glassmorphism border-b border-stone-800/50 flex items-center justify-between z-10">
            <div class="flex items-center gap-4">
                <img :alt="selectedChatUser.name" :src="selectedChatUser.avatar" class="w-12 h-12 rounded-full"/>
                <div>


                    <h3 class="text-xl font-bold text-white" x-text="selectedChatUser.name"></h3>
                    <p class="text-sm text-stone-400" x-text="'@' + selectedChatUser.username"></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button class="text-stone-400 hover:text-white p-2 rounded-full transition-colors duration-300" title="Call" @click="alert('Call feature not implemented')">
                    <span class="material-symbols-outlined">call</span>
                </button>
                <button class="text-stone-400 hover:text-white p-2 rounded-full transition-colors duration-300" title="Video Call" @click="alert('Video call feature not implemented')">
                    <span class="material-symbols-outlined">videocam</span>
                </button>
                <div class="relative group">
                    <button class="text-stone-400 hover:text-white p-2 rounded-full transition-colors duration-300" @click="chatMoreDropdownOpen = !chatMoreDropdownOpen" title="More options">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                    <div class="dropdown absolute right-0 mt-2 w-56 glassmorphism rounded-xl shadow-2xl z-20 border border-stone-800/50" x-show="chatMoreDropdownOpen" @click.away="chatMoreDropdownOpen = false" x-transition>
                        <div class="p-2">
                            <button class="w-full flex items-center gap-3 text-left px-3 py-2 rounded-lg text-stone-300 hover:bg-stone-800/50 transition-colors" @click="viewUserProfile(selectedChatUser)">
                                <span class="material-symbols-outlined text-base">person</span>
                                View Profile
                            </button>
                            <div class="border-t border-stone-800/50 my-2"></div>
                            <button class="w-full flex items-center gap-3 text-left px-3 py-2 rounded-lg text-red-400 hover:bg-red-500/10 transition-colors" @click="blockUser(selectedChatUser)">
                                <span class="material-symbols-outlined text-base">block</span>
                                Block User
                            </button>
                            <button class="w-full flex items-center gap-3 text-left px-3 py-2 rounded-lg text-yellow-400 hover:bg-yellow-500/10 transition-colors" @click="reportUser(selectedChatUser)">
                                <span class="material-symbols-outlined text-base">report</span>
                                Report User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-1 p-6 space-y-6 overflow-y-auto scrollbar-hide" x-ref="chatMessages">
        <div x-show="isCheckingChat" class="text-center text-stone-500 py-6">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-orange-500"></div>
                <p class="mt-2">Loading messages...</p>
            </div>




            <template x-for="(msg, index) in getMessagesForUser(selectedChatUser.id)" :key="index">
                <div :class="msg.isSentByCurrentUser ? 'flex gap-3 justify-end' : 'flex gap-3'">
                    <img :alt="msg.senderName" :src="msg.senderAvatar" class="w-10 h-10 rounded-full flex-shrink-0" />
                    <div :class="msg.isSentByCurrentUser ? 'bg-orange-600/80 p-3 rounded-lg rounded-br-none max-w-lg text-white' : 'bg-stone-800/60 p-3 rounded-lg rounded-tl-none max-w-lg text-stone-200'">
                        <p x-text="msg.text"></p>
                    </div>
                    <span class="text-xs text-stone-500 mt-1 self-end" x-text="msg.time"></span>
                </div>
            </template>
            <template x-if="getMessagesForUser(selectedChatUser.id).length === 0">
                <div class="text-center py-16 text-stone-500">
                    <span class="material-symbols-outlined text-6xl">forum</span>
                    <p class="mt-4 text-lg">No messages yet. Start the conversation!</p>
                </div>
            </template>
        </div>
        <div class="p-4 glassmorphism border-t border-stone-800/50 z-10">
            <div class="relative">
                <input type="text" placeholder="Type a message..." class="w-full bg-stone-900/70 border border-stone-800/50 rounded-xl pl-4 pr-12 py-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-300" x-model="newMessageText" @keydown.enter.prevent="sendMessage"/>
                <button class="absolute right-3 top-1/2 -translate-y-1/2 orange-gradient-bg text-white p-2 rounded-full hover:opacity-90 transition-opacity" @click="sendMessage" :disabled="!newMessageText.trim()">
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Placeholder when no user selected -->
    <main class="flex-1 flex flex-col h-full glassmorphism rounded-2xl overflow-hidden relative" x-show="!selectedChatUser" x-transition>
        <div class="flex-1 p-6 space-y-6 overflow-y-auto scrollbar-hide flex flex-col justify-center items-center text-center">
            <span class="material-symbols-outlined text-8xl text-stone-600 mb-4">forum</span>
            <h3 class="text-2xl font-bold text-white mb-2">Select a chat to start messaging</h3>
            <p class="text-stone-400 max-w-sm">Find your friends and workout buddies by using the search bar, or start a new chat to connect.</p>
            <button class="mt-6 orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity" :disabled="!selectedChatUser" @click="startConversation">
                <span class="material-symbols-outlined">add</span>
                Start a Conversation
            </button>
        </div>
    </main>
</section>

<!-- Notifications (unchanged) -->
<section x-show="currentView==='notifications'" x-transition class="flex-1 p-8 flex flex-col h-full overflow-hidden">
    <header class="flex justify-between items-center mb-8 flex-shrink-0">
        <div class="flex items-center gap-4">
            <div class="theme-switch-wrapper">
                <span class="material-symbols-outlined toggle-icon light_mode" :class="{active: !isDark}">light_mode</span>
                <label class="theme-switch mx-2" for="checkbox2">
                    <input id="checkbox2" type="checkbox" @change="toggleTheme()" :checked="isDark"/>
                    <div class="slider round"></div>
                </label>
                <span class="material-symbols-outlined toggle-icon dark_mode" :class="{active: isDark}">dark_mode</span>
            </div>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 dark:text-stone-500">search</span>
                <input class="pl-10 pr-4 py-2 w-64 rounded-full bg-stone-100 dark:bg-stone-800/50 text-stone-900 dark:text-white placeholder-stone-500 dark:placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-orange-500 ring-offset-2 ring-offset-background-light dark:ring-offset-background-dark transition-all duration-300" placeholder="Search notifications..." type="text" x-model="notificationSearch" @input="filterNotifications"/>
            </div>
            <div class="flex items-center gap-3">
                <img alt="User Avatar" class="w-10 h-10 rounded-full border-2 border-orange-500" :src="profile.avatar"/>
            </div>
        </div>
    </header>
    <div class="flex-1 flex flex-col bg-transparent">
        <div class="glassmorphism p-6 rounded-2xl flex-1 flex flex-col">
            <div class="flex justify-between items-center mb-6 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <button :class="{'filter-btn-active': activeNotificationFilter === 'All', 'filter-btn-inactive': activeNotificationFilter !== 'All'}" @click="activeNotificationFilter = 'All'" class="filter-btn">All</button>
                    <button :class="{'filter-btn-active': activeNotificationFilter === 'Unread', 'filter-btn-inactive': activeNotificationFilter !== 'Unread'}" @click="activeNotificationFilter = 'Unread'" class="filter-btn">Unread</button>
                    <button :class="{'filter-btn-active': activeNotificationFilter === 'Promotions', 'filter-btn-inactive': activeNotificationFilter !== 'Promotions'}" @click="activeNotificationFilter = 'Promotions'" class="filter-btn">Promotions</button>
                    <button :class="{'filter-btn-active': activeNotificationFilter === 'System Alerts', 'filter-btn-inactive': activeNotificationFilter !== 'System Alerts'}" @click="activeNotificationFilter = 'System Alerts'" class="filter-btn">System Alerts</button>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="markAllNotificationsRead()" class="text-sm font-semibold text-orange-500 dark:text-orange-400 hover:underline">Mark all as read</button>
                    <button @click="showConfirmClearModal = true" class="px-4 py-2 rounded-full text-sm font-medium transition-colors duration-300 bg-red-500/10 dark:bg-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-500/20 dark:hover:bg-red-500/30 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">delete_sweep</span>
                        Clear All
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto scrollbar-hide flex-grow">
                <ul class="space-y-4">
                    <template x-for="notification in filteredNotifications" :key="notification.id">
                        <li :class="{'opacity-50 dark:opacity-40': notification.read}" class="flex items-center p-4 card-item notification-item rounded-xl transition-all duration-300">
                            <div :class="notification.iconBg" class="p-3 rounded-lg mr-4">
                                <span :class="notification.iconColor" class="material-symbols-outlined" x-text="notification.icon"></span>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-stone-800 dark:text-white" x-text="notification.type"></p>
                                <p class="text-sm text-stone-500 dark:text-gray-400" x-text="notification.message"></p>
                            </div>
                            <div class="flex items-center gap-4 ml-4">
                                <p class="text-xs text-stone-500 dark:text-gray-400 whitespace-nowrap" x-text="notification.time"></p>
                                <button :title="notification.read ? 'Mark as unread' : 'Mark as read'" @click="toggleNotificationRead(notification)" class="p-2 rounded-full hover:bg-stone-200/50 dark:hover:bg-white/10 transition-colors">
                                    <span class="material-symbols-outlined text-stone-600 dark:text-stone-300" x-text="notification.read ? 'mark_chat_read' : 'mark_chat_unread'"></span>
                                </button>
                            </div>
                        </li>
                    </template>
                    <template x-if="filteredNotifications.length === 0">
                        <li class="flex flex-col items-center justify-center py-12 text-center text-stone-500 dark:text-stone-400">
                            <span class="material-symbols-outlined text-6xl mb-4">notifications_off</span>
                            <p class="text-lg font-medium">No Notifications</p>
                            <p class="text-sm">You're all caught up!</p>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Clear All Confirm Modal (unchanged) -->
<div x-show="showConfirmClearModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-transition>
    <div @click.away="showConfirmClearModal = false" class="glassmorphism rounded-2xl w-full max-w-md p-8 text-center" x-show="showConfirmClearModal" x-transition>
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-500/20 mb-4">
            <span class="material-symbols-outlined text-4xl text-orange-400">warning</span>
        </div>
        <h3 class="text-2xl font-semibold text-stone-900 dark:text-white">Clear All Notifications?</h3>
        <p class="mt-2 text-stone-600 dark:text-stone-300">
            This action cannot be undone. All your notifications will be permanently deleted.
        </p>
        <div class="mt-8 flex justify-center gap-4">
            <button @click="showConfirmClearModal = false" class="px-6 py-2.5 rounded-full text-sm font-semibold bg-stone-100 dark:bg-stone-700/50 text-stone-800 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-600/50 transition-colors">
                Cancel
            </button>
            <button @click="clearAllNotifications()" class="px-6 py-2.5 rounded-full text-sm font-semibold text-white orange-gradient-bg hover:opacity-90 transition-opacity">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Add Workout Modal (unchanged) -->
<div x-show="showAddWorkoutModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50" x-transition.opacity>
    <div @click.away="showAddWorkoutModal = false" class="glassmorphism w-full max-w-2xl p-8 rounded-2xl border border-[var(--glass-border-dark)]">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-white">Add New Workout</h3>
            <button @click="showAddWorkoutModal = false" class="p-2 rounded-full hover:bg-white/10">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="font-semibold text-stone-300 mb-2 block" for="workout-title-modal">Workout Title</label>
                    <input class="w-full bg-stone-800/80 border-stone-600 rounded-lg text-white font-semibold focus:ring-orange-500 focus:border-orange-500 p-3" id="workout-title-modal" placeholder="e.g., Morning Run" type="text" x-model="newWorkout.title"/>
                </div>
                <div>
                    <label class="font-semibold text-stone-300 mb-2 block" for="workout-type-modal">Workout Type</label>
                    <select class="w-full bg-stone-800/80 border-stone-600 rounded-lg font-semibold focus:ring-orange-500 focus:border-orange-500 text-white p-3" id="workout-type-modal" x-model="newWorkout.type">
                        <option>Cardio</option>
                        <option>Strength</option>
                        <option>Flexibility</option>
                        <option>Other</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="font-semibold text-stone-300 mb-2 block" for="workout-date-modal">Date</label>
                    <input class="w-full bg-stone-800/80 border-stone-600 rounded-lg font-semibold focus:ring-orange-500 focus:border-orange-500 text-white p-2.5" id="workout-date-modal" type="date" x-model="newWorkout.date"/>
                </div>
                <div>
                    <label class="font-semibold text-stone-300 mb-2 block" for="workout-time-modal">Time</label>
                    <input class="w-full bg-stone-800/80 border-stone-600 rounded-lg font-semibold focus:ring-orange-500 focus:border-orange-500 text-white p-2.5" id="workout-time-modal" type="time" x-model="newWorkout.time"/>
                </div>
            </div>
            <div>
                <label class="font-semibold text-stone-300 mb-2 block">Set Recurring Schedule</label>
                <div class="flex items-center gap-2 bg-stone-900/50 p-1 rounded-lg">
                    <button :class="{'orange-gradient-bg text-white shadow-md': recurrence.type === 'daily', 'hover:bg-stone-700/80': recurrence.type !== 'daily'}" @click="recurrence.type = 'daily'" class="flex-1 px-4 py-2 rounded-md font-semibold transition-all duration-300">Daily</button>
                    <button :class="{'orange-gradient-bg text-white shadow-md': recurrence.type === 'weekly', 'hover:bg-stone-700/80': recurrence.type !== 'weekly'}" @click="recurrence.type = 'weekly'" class="flex-1 px-4 py-2 rounded-md font-semibold transition-all duration-300">Weekly</button>
                    <button :class="{'orange-gradient-bg text-white shadow-md': recurrence.type === 'custom', 'hover:bg-stone-700/80': recurrence.type !== 'custom'}" @click="recurrence.type = 'custom'" class="flex-1 px-4 py-2 rounded-md font-semibold transition-all duration-300">Custom</button>
                </div>
            </div>
            <div class="space-y-3" x-show="recurrence.type === 'weekly'" x-transition>
                <label class="font-semibold text-stone-300">Repeat on</label>
                <div class="flex justify-between">
                    <template x-for="day in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="day">
                        <button :class="{'orange-gradient-bg text-white': recurrence.days.includes(day), 'bg-stone-700/50 hover:bg-stone-600/50': !recurrence.days.includes(day)}" @click="toggleDay(day)" class="w-10 h-10 rounded-full font-semibold transition-colors" x-text="day"></button>
                    </template>
                </div>
            </div>
            <div class="flex justify-end gap-4 pt-4">
                <button @click="showAddWorkoutModal = false" class="px-6 py-2.5 rounded-full font-semibold bg-stone-700/50 hover:bg-stone-600/50 text-white transition-colors">Cancel</button>
                <button @click="addWorkout()" class="px-6 py-2.5 rounded-full font-semibold orange-gradient-bg text-white hover:opacity-90 transition-opacity">Add Workout</button>
            </div>
        </div>
    </div>
</div>

<!-- View User Profile Modal -->
<div x-show="showUserProfileModal" x-transition.opacity class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex justify-center items-center p-4" style="display: none;">
    <div @click.away="showUserProfileModal = false" class="w-full max-w-md h-auto glassmorphism rounded-3xl p-8 flex flex-col items-center relative border-2 border-orange-500/30 shadow-2xl shadow-orange-500/10" x-show="showUserProfileModal" x-transition>
        <button @click="showUserProfileModal = false" class="absolute top-4 right-4 text-stone-400 hover:text-white transition-colors">
            <span class="material-symbols-outlined">close</span>
        </button>
        <img :alt="viewedUser.name" class="w-32 h-32 rounded-full border-4 border-orange-500 object-cover mb-4" :src="viewedUser.avatar"/>
        <h2 class="text-3xl font-bold text-white" x-text="viewedUser.name"></h2>
        <p class="text-lg text-stone-400 mb-6" x-text="'@' + viewedUser.username"></p>
        <p class="text-center text-stone-300 max-w-sm mb-8" x-text="viewedUser.bio || 'No bio available.'"></p>
        
        <div class="w-full space-y-4 mb-8" x-show="viewedUser.tags && viewedUser.tags.length > 0">
            <h4 class="text-sm font-semibold text-stone-400 uppercase tracking-wider text-center">Fitness Interests</h4>
            <div class="flex flex-wrap justify-center gap-2">
                <template x-for="tag in viewedUser.tags" :key="tag">
                    <span class="bg-stone-800/60 text-orange-400 text-xs font-semibold px-3 py-1 rounded-full" x-text="tag"></span>
                </template>
            </div>
        </div>

        <div class="w-full border-t border-stone-800/50 pt-6 flex flex-col space-y-3">
            <button @click="blockUser(viewedUser)" class="w-full flex items-center justify-center gap-2 text-sm text-red-400 hover:bg-red-500/10 py-2 px-4 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-base">block</span> Block User
            </button>
            <button @click="reportUser(viewedUser)" class="w-full flex items-center justify-center gap-2 text-sm text-yellow-400 hover:bg-yellow-500/10 py-2 px-4 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-base">report</span> Report User
            </button>
        </div>
    </div>
</div>


<!-- Profile (Combined UI no 1 and 2) -->
<section x-show="currentView==='profile'" x-transition class="flex-1 p-8 flex flex-col h-full overflow-y-auto scrollbar-hide">
    <!-- Display Profile Info -->
    <template x-if="!isEditingProfile">
        <div>
            <div class="flex-shrink-0 glassmorphism rounded-2xl p-6 md:p-8 flex flex-col sm:flex-row items-center gap-6 md:gap-8 mb-8">
                <div class="relative flex-shrink-0">
                    <img :src="profile.avatar" alt="User Avatar" class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-stone-800/50 object-cover"/>
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <h2 class="text-3xl md:text-4xl font-bold text-white" x-text="profile.fullName"></h2>
                    <p class="text-lg text-stone-400" x-text="'@' + profile.username"></p>
                    <p class="text-stone-300 mt-4 max-w-lg" x-text="profile.bio"></p>
                    <div class="flex items-center justify-center sm:justify-start gap-6 mt-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white" x-text="profile.buddiesCount"></p>
                            <p class="text-sm text-stone-400">Buddies</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-white" x-text="profile.postsCount"></p>
                            <p class="text-sm text-stone-400">Posts</p>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <button @click="startEditProfile()" class="orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                        <span class="material-symbols-outlined">edit</span>
                        Edit Profile
                    </button>
                </div>
            </div>
            <div class="glassmorphism rounded-2xl p-6 mb-8">
                <div class="flex items-start gap-4">
                    <img :src="profile.avatar" alt="User Avatar" class="w-12 h-12 rounded-full flex-shrink-0"/>
                    <div class="flex-1">
                        <p class="whitespace-pre-wrap text-white" x-text="profile.bio"></p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Edit Profile Form -->
    <template x-if="isEditingProfile">
        <div class="glassmorphism rounded-2xl p-6 md:p-8">
            <h2 class="text-3xl font-bold text-white mb-8">Edit Profile</h2>
            <form class="space-y-6" @submit.prevent="saveProfileChanges">
                <div class="flex items-center gap-6 mb-2">
                    <div class="relative">
                        <!-- Make profile picture uploadable here (previously missing) -->
                        <img :src="profile.avatar" alt="User Avatar" id="profile-picture" class="w-24 h-24 rounded-full border-4 border-stone-800/50 object-cover"/>
                        <label for="profile-picture-upload-main" class="absolute bottom-0 right-0 orange-gradient-bg p-2 rounded-full cursor-pointer hover:opacity-90 transition-opacity">
                            <span class="material-symbols-outlined text-white text-lg">edit</span>
                            <input id="profile-picture-upload-main" accept="image/*" type="file" class="hidden" @change="updateProfilePicture"/>
                        </label>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-lg font-semibold text-white" x-text="profile.fullName"></p>
                        <p class="text-sm text-stone-400" x-text="'@' + profile.username"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full-name" class="block text-sm font-medium text-stone-400 mb-2">Full Name</label>
                        <input id="full-name" type="text" class="w-full edit-form-input rounded-lg p-3 transition-all duration-300" x-model="profile.fullName" required/>
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-stone-400 mb-2">Username</label>
                        <input id="username" type="text" class="w-full edit-form-input rounded-lg p-3 transition-all duration-300" x-model="profile.username" required/>
                    </div>
                </div>
                <div>
                    <label for="bio" class="block text-sm font-medium text-stone-400 mb-2">Bio</label>
                    <textarea id="bio" placeholder="Tell us about yourself..." rows="3" class="w-full edit-form-input rounded-lg p-3 transition-all duration-300 resize-none" x-model="profile.bio"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="age" class="block text-sm font-medium text-stone-400 mb-2">Age</label>
                        <input id="age" type="number" class="w-full edit-form-input rounded-lg p-3 transition-all duration-300" x-model.number="profile.age" min="0" required/>
                    </div>
                    <div>
                        <label for="gender" class="block text-sm font-medium text-stone-400 mb-2">Gender</label>
                        <select id="gender" class="w-full edit-form-input rounded-lg p-3 transition-all duration-300 appearance-none bg-no-repeat bg-right" style="background-image: url('data:image/svg+xml,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 20 20%27%3e%3cpath stroke=%27%23a3a3a3%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%271.5%27 d=%27M6 8l4 4 4-4%27/%3e%3c/svg%3e'); background-position: right 0.75rem center; background-size: 1.5em 1.5em;" x-model="profile.gender" required>
                            <option value="">Select Gender</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Non-binary</option>
                            <option>Prefer not to say</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-400 mb-2">Fitness Interests/Goals</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(tag,index) in profile.tags" :key="index">
                            <span class="bg-orange-600/30 text-orange-200 text-sm font-medium px-3 py-1.5 rounded-full flex items-center gap-1">
                                <span x-text="tag"></span>
                                <button type="button" class="text-orange-300 hover:text-white" @click="removeTag(index)">×</button>
                            </span>
                        </template>
                        <input type="text" class="bg-transparent border-0 text-white placeholder-stone-500 focus:ring-0" placeholder="Add a tag..." x-model="newTag" @keydown.enter.prevent="addTag"/>
                    </div>
                </div>
                <div class="flex justify-end gap-4 pt-6">
                    <button type="button" @click="cancelEditProfile()" class="bg-stone-800/80 text-white font-semibold py-3 px-6 rounded-lg hover:bg-stone-700/80 transition-colors">Cancel</button>
                    <button type="submit" class="orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">Save Changes</button>
                </div>
            </form>
        </div>
    </template>
</section>

<!-- Feeds Section (unchanged) -->
<section x-show="currentView==='feeds'" x-transition class="flex-1 overflow-y-auto scrollbar-hide space-y-6">

  <div class="glassmorphism rounded-2xl p-6">
    <h2 class="text-3xl font-bold text-stone-900 dark:text-white mb-4">Community Feed</h2>
    <div class="flex items-start gap-4">
      <img alt="User Avatar" class="w-12 h-12 rounded-full border-2 border-stone-800/50 object-cover" :src="profile.avatar"/>
      <div class="flex-1">
        <textarea x-model="newFeedPost" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300 resize-none" placeholder="What's on your mind?" rows="3"></textarea>
        <div class="flex justify-between items-center mt-3">
          <div class="flex items-center gap-4">
            <input type="file" x-ref="image_file" class="hidden" @change="newFeedPostImage = $event.target.files[0]">
            <button type="button" class="text-stone-400 hover:text-orange-400 transition-colors" title="Add Image" @click.prevent="$refs.image_file.click()">
              <span class="material-symbols-outlined text-2xl">image</span>
            </button>
            <button type="button" class="text-stone-400 hover:text-orange-400 transition-colors" title="Add Link" @click.prevent="showFeedLinkInput = !showFeedLinkInput">
              <span class="material-symbols-outlined text-2xl">link</span>
            </button>
          </div>
          <button @click="postFeed()" :disabled="!newFeedPost.trim()" class="orange-gradient-bg text-white font-semibold py-2 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
            Post
          </button>
        </div>
      </div>
    </div>
  </div>

  <template x-for="(post,index) in feedPosts" :key="index">
    <div class="glassmorphism rounded-2xl p-6 space-y-4">
      <div class="flex items-start gap-4">
        <img :alt="post.author" class="w-12 h-12 rounded-full object-cover cursor-pointer" :src="post.avatar" @click="viewUserProfile(post.author_id)"/>
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-semibold text-white cursor-pointer hover:underline" x-text="post.author" @click="viewUserProfile(post.author_id)"></p>
              <p class="text-xs text-stone-400" x-text="post.timeAgo"></p>
            </div>
            <button class="text-stone-400 hover:text-white" @click.prevent="alert('More options not implemented')">
              <span class="material-symbols-outlined">more_horiz</span>
            </button>
          </div>
          <p class="mt-3 text-stone-300 whitespace-pre-line" x-text="post.content"></p>
          <template x-if="post.image">
            <img :src="post.image" alt="Post image" class="mt-4 rounded-xl w-full h-auto object-cover"/>
          </template>
          <div class="mt-4 pt-3 border-t border-stone-700/50 flex justify-around items-center text-stone-400">
            <button @click.prevent="toggleLike(post)" :class="{'text-orange-400 bg-orange-500/10': post.liked}" class="flex items-center gap-2 transition-colors duration-300 px-4 py-2 rounded-lg hover:text-orange-400 hover:bg-orange-500/10">
              <span class="material-symbols-outlined text-xl" :style="post.liked ? 'font-variation-settings: \\'FILL\\' 1;' : ''" x-text="post.liked ? 'favorite' : 'favorite_border'"></span>
              <span class="font-medium text-sm" x-text="post.liked ? 'Liked' : 'Like'"></span>
            </button>
            <button @click.prevent="toggleCommentSection(post)" class="flex items-center gap-2 hover:text-orange-400 transition-colors duration-300 px-4 py-2 rounded-lg hover:bg-orange-500/10">
              <span class="material-symbols-outlined text-xl">chat_bubble_outline</span>
              <span class="font-medium text-sm">Comment</span>
            </button>
            <button @click.prevent="alert('Share feature not implemented')" class="flex items-center gap-2 hover:text-orange-400 transition-colors duration-300 px-4 py-2 rounded-lg hover:bg-orange-500/10">
              <span class="material-symbols-outlined text-xl">share</span>
              <span class="font-medium text-sm">Share</span>
            </button>
          </div>
          <div x-show="post.showComments" class="mt-4 pt-4 border-t border-stone-700/50 space-y-4">
            <template x-for="(comment, commentIndex) in post.comments" :key="commentIndex">
              <div class="flex items-start gap-3">
                <img :alt="comment.author" :src="comment.avatar" class="w-10 h-10 rounded-full object-cover"/>
                <div class="flex-1">
                  <div class="bg-stone-900/70 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                      <p class="font-semibold text-white text-sm" x-text="comment.author"></p>
                      <p class="text-xs text-stone-400" x-text="comment.timeAgo"></p>
                    </div>
                    <p class="text-sm text-stone-300 mt-1" x-text="comment.content"></p>
                  </div>
                  <div class="mt-2 ml-1">
                    <button class="text-xs font-semibold text-orange-400 hover:text-orange-300 transition-colors flex items-center gap-1">
                      <span class="material-symbols-outlined !text-base">reply</span>
                      Reply
                    </button>
                  </div>
                </div>
              </div>
            </template>
            <div class="flex items-start gap-3 mt-4">
              <img alt="User Avatar" class="w-10 h-10 rounded-full border-2 border-stone-800/50 object-cover" :src="profile.avatar"/>
              <div class="flex-1 relative flex items-center bg-stone-900/50 border border-stone-700 rounded-full focus-within:ring-2 focus-within:ring-orange-500 focus-within:border-orange-500 transition-all duration-300">
                <input x-model="post.newComment" @keydown.enter.prevent="addComment(post)" class="w-full bg-transparent border-none py-2 pl-4 pr-24 text-white placeholder-stone-400 focus:ring-0" placeholder="Write a comment..." type="text"/>
                <div class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center">
                  <button class="text-stone-400 hover:text-orange-400 transition-colors p-2">
                    <span class="material-symbols-outlined text-xl">mood</span>
                  </button>
                  <button class="text-stone-400 hover:text-orange-400 transition-colors p-2">
                    <span class="material-symbols-outlined text-xl">attach_file</span>
                  </button>
                  <button @click.prevent="addComment(post)" class="orange-gradient-bg text-white rounded-full p-2 hover:opacity-90 transition-opacity ml-1">
                    <span class="material-symbols-outlined text-md">send</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

</section>

<!-- Settings Section -->
<section x-show="currentView==='settings'" x-transition class="flex-1 overflow-y-auto scrollbar-hide">
  <div class="glassmorphism rounded-2xl p-6">
    <h2 class="text-3xl font-bold text-stone-900 dark:text-white mb-4">Settings</h2>
    <div class="flex items-center gap-3 mb-6 flex-wrap">
      <button @click="settingsTab = 1" :class="{'bg-orange-500 text-white': settingsTab === 1, 'bg-stone-100 dark:bg-stone-800/50 text-stone-600 dark:text-stone-300': settingsTab !== 1}" class="px-4 py-2 rounded-full text-sm font-medium transition-colors">General</button>
      <button @click="settingsTab = 2" :class="{'bg-orange-500 text-white': settingsTab === 2, 'bg-stone-100 dark:bg-stone-800/50 text-stone-600 dark:text-stone-300': settingsTab !== 2}" class="px-4 py-2 rounded-full text-sm font-medium transition-colors">Profile</button>
      <button @click="settingsTab = 3" :class="{'bg-orange-500 text-white': settingsTab === 3, 'bg-stone-100 dark:bg-stone-800/50 text-stone-600 dark:text-stone-300': settingsTab !== 3}" class="px-4 py-2 rounded-full text-sm font-medium transition-colors">Forgot Email?</button>
      <button @click="settingsTab = 4" :class="{'bg-orange-500 text-white': settingsTab === 4, 'bg-stone-100 dark:bg-stone-800/50 text-stone-600 dark:text-stone-300': settingsTab !== 4}" class="px-4 py-2 rounded-full text-sm font-medium transition-colors">Forgot Password?</button>
    </div>

    <!-- Tab 1: General Settings -->
    <div x-show="settingsTab === 1" x-transition class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="space-y-8">
          <div class="glassmorphism rounded-2xl p-6">
            <h3 class="text-xl font-bold text-white mb-4">Account</h3>
            <div class="space-y-4">
              <a href="#" class="flex items-center justify-between p-4 rounded-lg bg-stone-900/50 hover:bg-stone-800/70 transition-colors duration-300 border border-stone-800" @click.prevent="settingsTab = 2">
                <div>
                  <h4 class="font-semibold text-white">Profile Settings</h4>
                  <p class="text-sm text-stone-400">Edit your personal information</p>
                </div>
                <span class="material-symbols-outlined text-stone-400">chevron_right</span>
              </a>
              <a href="#" class="flex items-center justify-between p-4 rounded-lg bg-stone-900/50 hover:bg-stone-800/70 transition-colors duration-300 border border-stone-800" @click.prevent="settingsTab = 3">
                <div>
                  <h4 class="font-semibold text-white">Forgot Email</h4>
                  <p class="text-sm text-stone-400">Start email recovery process</p>
                </div>
                <span class="material-symbols-outlined text-stone-400">chevron_right</span>
              </a>
              <a href="#" class="flex items-center justify-between p-4 rounded-lg bg-stone-900/50 hover:bg-stone-800/70 transition-colors duration-300 border border-stone-800" @click.prevent="settingsTab = 4">
                <div>
                  <h4 class="font-semibold text-white">Forgot Password</h4>
                  <p class="text-sm text-stone-400">Reset your account password</p>
                </div>
                <span class="material-symbols-outlined text-stone-400">chevron_right</span>
              </a>
            </div>
          </div>
          <div class="glassmorphism rounded-2xl p-6">
            <h3 class="text-xl font-bold text-white mb-4">Appearance</h3>
            <div class="space-y-4">
              <!-- Replace confusing dual checkboxes with single clear toggle -->
              <div class="flex items-center justify-between p-4 rounded-lg bg-stone-900/50 border border-stone-800">
                <div>
                  <h4 class="font-semibold text-white">Theme</h4>
                  <p class="text-sm text-stone-400">Toggle between Light and Dark mode</p>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm text-stone-400">Light</span>
                  <label class="theme-switch" for="appearance-theme-checkbox">
                    <input id="appearance-theme-checkbox" type="checkbox" @change="toggleTheme()" :checked="isDark"/>
                    <div class="slider"></div>
                  </label>
                  <span class="text-sm text-stone-400">Dark</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="glassmorphism rounded-2xl p-6">
          <h3 class="text-xl font-bold text-white mb-4">Profile Settings Preview</h3>
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-stone-400 mb-1" for="name">Name</label>
              <input id="name" type="text" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.fullName"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-400 mb-1" for="email">Email</label>
              <input id="email" type="email" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.email" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-400 mb-1" for="phone">Phone Number</label>
              <input id="phone" type="tel" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-400 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.phone"/>
            </div>
            <div class="flex justify-end pt-4 border-t border-stone-800/50">
              <button @click.prevent="alert('Changes saved!')" class="orange-gradient-bg text-white font-semibold py-2 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 2: Profile Settings -->
    <div x-show="settingsTab === 2" x-transition class="space-y-4">
      <div class="glassmorphism rounded-2xl p-6 lg:p-8">
        <div class="flex items-center gap-4 mb-8">
          <button @click="settingsTab = 1" class="text-stone-400 hover:text-white transition-colors" title="Back to General Settings">
            <span class="material-symbols-outlined">arrow_back</span>
          </button>
          <h3 class="text-2xl font-bold text-white">Profile Settings</h3>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-1 flex flex-col items-center">
            <div class="relative mb-4">
              <img :src="profile.avatar" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover border-2 border-orange-500"/>
              <label class="absolute bottom-0 right-0 orange-gradient-bg p-2 rounded-full cursor-pointer hover:opacity-90 transition-opacity" for="profile-picture-upload">
                <span class="material-symbols-outlined text-white text-lg">edit</span>
                <input id="profile-picture-upload" accept="image/*" type="file" class="hidden" @change="updateProfilePicture"/>
              </label>
            </div>
            <p class="text-xl font-semibold text-white" x-text="profile.fullName"></p>
            <p class="text-sm text-stone-400" x-text="profile.email"></p>
          </div>
          <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="first-name" class="block text-sm font-medium text-stone-400 mb-2">First Name</label>
                <input id="first-name" type="text" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.firstName"/>
              </div>
              <div>
                <label for="last-name" class="block text-sm font-medium text-stone-400 mb-2">Last Name</label>
                <input id="last-name" type="text" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.lastName"/>
              </div>
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-stone-400 mb-2">Email Address</label>
              <input id="email" type="email" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.email"/>
            </div>
            <div>
              <label for="password" class="block text-sm font-medium text-stone-400 mb-2">Password</label>
              <div class="relative">
                <input id="password" :type="passwordVisible ? 'text' : 'password'" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300" x-model="profile.password"/>
                <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-stone-400 hover:text-white" @click="togglePasswordVisibility">
                  <span class="material-symbols-outlined text-xl" x-text="passwordVisible ? 'visibility' : 'visibility_off'"></span>
                </button>
              </div>
            </div>
            <div>
              <label for="bio" class="block text-sm font-medium text-stone-400 mb-2">Bio</label>
              <textarea id="bio" placeholder="Tell us a bit about yourself..." rows="4" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300 resize-none" x-model="profile.bio"></textarea>
            </div>
            <div class="flex justify-end items-center gap-4 pt-4 border-t border-stone-800/50">
              <button @click.prevent="settingsTab = 1" class="text-stone-400 font-semibold py-2 px-6 rounded-lg hover:text-white transition-colors duration-300">
                Cancel
              </button>
              <button @click.prevent="saveProfileChanges" class="orange-gradient-bg text-white font-semibold py-2 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity">
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 3: Forgot Email -->
    <div x-show="settingsTab === 3" x-transition class="space-y-4">
      <div class="w-full max-w-md mx-auto">
        <div class="glassmorphism rounded-2xl p-8">
          <div class="text-center mb-6">
            <h2 class="text-3xl font-bold text-white">Forgot Your Email?</h2>
            <p class="text-stone-400 mt-2">No problem. Enter your phone number or username below and we'll help you find your account.</p>
          </div>
          <form @submit.prevent="submitForgotEmail" class="space-y-6">
            <div>
              <label for="recovery-info" class="block text-sm font-medium text-stone-400 mb-2">Phone Number or Username</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">person_search</span>
                <input id="recovery-info" type="text" x-model="forgotEmailQuery" placeholder="Enter your phone or username" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 pl-11 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300"/>
              </div>
            </div>
            <div class="pt-2">
              <button type="submit" :disabled="!forgotEmailQuery.trim()" class="w-full orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined">send</span>
                Submit
              </button>
            </div>
          </form>
          <div class="text-center mt-6">
            <button @click.prevent="settingsTab = 1" class="text-sm text-orange-500 hover:text-orange-400 transition-colors inline-flex items-center gap-1">
              <span class="material-symbols-outlined align-middle text-base">arrow_back</span>
              Back to Settings
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 4: Forgot Password -->
    <div x-show="settingsTab === 4" x-transition class="space-y-4">
      <div class="w-full max-w-md mx-auto">
        <div class="glassmorphism rounded-2xl p-8">
          <button @click.prevent="settingsTab = 1" class="flex items-center gap-2 text-sm text-stone-400 hover:text-white transition-colors mb-6">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Settings
          </button>
          <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-white mb-2">Forgot Password?</h2>
            <p class="text-stone-400">No worries, we'll send you reset instructions.</p>
          </div>
          <form @submit.prevent="submitForgotPassword" class="space-y-6">
            <div>
              <label for="email-username" class="block text-sm font-medium text-stone-400 mb-2">Email or Username</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">person</span>
                <input id="email-username" type="text" x-model="forgotPasswordQuery" placeholder="Enter your email or username" class="w-full bg-stone-900/50 border border-stone-700 rounded-lg p-3 pl-10 text-white placeholder-stone-500 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300"/>
              </div>
            </div>
            <button type="submit" :disabled="!forgotPasswordQuery.trim()" class="w-full orange-gradient-bg text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2 hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
              <span>Send Reset Link</span>
              <span class="material-symbols-outlined">arrow_forward</span>
            </button>
          </form>
          <div class="text-center mt-6">
            <p class="text-sm text-stone-500">If you don't receive an email, please check your spam folder or <a href="#" class="font-medium text-orange-500 hover:text-orange-400">contact support</a>.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

</main>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function versoGymApp() {
    return {
       // server provided current user id
        currentUserId: <?php echo json_encode($user_id); ?>,

        // Theme
        isDark: false,
        initTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {

                this.isDark = savedTheme === 'dark';
            } else {
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.isDark = prefersDark || true;
            }
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(this.isDark ? 'dark' : 'light');
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        },
        toggleTheme() {
            this.isDark = !this.isDark;
            const theme = this.isDark ? 'dark' : 'light';
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(theme);
            localStorage.setItem('theme', theme);
        },

        // Navigation & UI
        currentView: 'dashboard',
        headerTitle: 'Welcome!',
        // For the new profile modal
        showUserProfileModal: false,
        viewedUser: { id: null, name: '', username: '', avatar: '', bio: '', tags: [] },

        settingsTab: 1,
        setView(view) {
            this.currentView = view;
            if (view === 'dashboard') this.headerTitle = `Welcome, ${this.profile.fullName.split(' ')[0] || 'Member'}!`;
            else if (view === 'schedule') this.headerTitle = 'My Schedule';
            else if (view === 'membership') this.headerTitle = 'Membership & Plans';
            else if (view === 'chat') this.headerTitle = 'Chat';
            else if (view === 'notifications') this.headerTitle = 'All Notifications';
            else if (view === 'profile') this.headerTitle = 'Profile';
            else if (view === 'feeds') this.headerTitle = 'Feeds';
            else if (view === 'settings') this.headerTitle = 'Settings';
            // reset some UI states
            this.clearSelectedChat();
            if (view === 'profile') this.isEditingProfile = false;
        },

        /* ---------- Profile & data containers ---------- */
        isEditingProfile: false,
        profile: {
            avatar: 'https://via.placeholder.com/150?text=User',
            fullName: 'Member',
            username: '',
            bio: '',
            buddiesCount: 0,
            postsCount: 0,
            age: null,
            gender: '',
            tags: [],
            email: '',
            phone: '',
            firstName: '',
            lastName: ''
        },

         /* ---------- Data arrays ---------- */workouts: [],
        memberships: [],
        payments: [],
        notifications: [],
        feedPosts: [],
        chatUsers: [],
        chatMessages: {},

        // UI states for schedule, chat, feed, notifications
        dashboardSearch: '',
        dashboardSchedule: [],
        dashboardUpcomingClasses: [],
        scheduleView: 'monthly',
        currentDate: new Date(),
        showAddWorkoutModal: false,
        recurrence: { type: 'weekly', days: [] },
        newWorkout: { title: '', date: new Date().toISOString().slice(0,10), time: '09:00', type: 'Cardio', recurring: false },

        // feed UI additions
        newFeedPost: '',
        
         MAX_IMAGE_BYTES: 3 * 1024 * 1024, // 3MB limit for feed images
    isUploadingImage: false,
    selectedImageFile: null,       // File|Blob ready to be appended to FormData
    selectedImagePreview: '',  
    linkUrl: '',    // dataURL for preview

        // chat UI
        filteredChatUsers: [],
        chatSearchQuery: '',
        unreadMessagesCount: {},

        selectedChatUser: null,
        userDropdownOpen: null,
        chatMoreDropdownOpen: false,
        newMessageText: '',

        // notifications UI
        isCheckingNotifications: false,
        isCheckingChat: false,


        lastNotificationCheck: null,
        notificationInterval: null,
        notificationCheckIntervalMs: 10000, // Check for new notifications every 10 seconds

        notificationSearch: '',
        activeNotificationFilter: 'All',
        showConfirmClearModal: false,

        // membership/payment UI
        showGymFeesModal: false,
        showPaymentModal: false,
        showPaymentConfirmModal: false,
        showPaymentSuccessModal: false,
        selectedGymFeeTier: '',
        selectedGymFeePrice: '',
        paymentReference: '',
        paymentForm: {
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
            pm: 'GCash',
            cardNumber: '',
            expiry: '',
            cvc: ''
        },
        gymFeeTiers: [
            { name: 'Membership', price: '₱850', note: 'Annual membership' },
            { name: '1 Week Package', price: '₱850', note: '1 week training' },
            { name: '2 Weeks Package', price: '₱1,700', note: '2 week training' },
            { name: '1 Month Package', price: '₱3,500', note: '1 month training' }
        ],

        /* ---------- Misc ---------- */
        passwordVisible: false,

        /* ---------- Data loading ---------- */
        async loadInitialData() {
     try {
        const res = await fetch('?action=initial_data');
                if (!res.ok) throw new Error('Failed to fetch initial data');
                const data = await res.json();
                if (data.error) {
                    Swal.fire('Error', data.error || 'Failed to load', 'error').then(()=> window.location.href='login.php');
                    return;
                }
                 /* ----------Map user---------- */
                const u = data.user || {};
                this.profile.fullName = u.fullname || this.profile.fullName;
                this.profile.email = u.email || '';
                this.profile.avatar = u.picture && u.picture !== '' ? u.picture : ('https://ui-avatars.com/api/?name=' + encodeURIComponent(u.fullname || u.email || 'User') + '&background=f97316&color=fff');
                this.profile.age = u.age || null;
                this.profile.gender = u.gender || '';
                this.profile.tags = (u.fitness_goals && u.fitness_goals !== '') ? [u.fitness_goals] : [];
                this.profile.firstName = (u.fullname || '').split(' ')[0] || '';
                this.profile.lastName = (u.fullname || '').split(' ').slice(1).join(' ') || '';
                // Use a more robust username generation
                this.profile.username = ((u.email || '').split('@')[0] || (u.fullname || 'user')).toLowerCase().replace(/[^a-z0-9]/g, '');
                this.profile.phone = u.phone || this.profile.phone || '';

                /* ---------- Other list ---------- */
            this.notifications = (data.notifications || []).map(n => ({
            id: n.id,
            type: n.type,
            message: n.message,
            icon: n.icon || 'notifications',
            read: !!n.is_read,
            category: n.category || 'General',
            time: n.created_at
                 }));

                this.startNotificationCheck();
                this.workouts = data.workouts || [];
                this.memberships = data.memberships || [];
                this.payments = data.payments || [];
                this.notifications = (data.notifications || []).map(n => ({
                    id: n.id,
                    type: n.type,
                    message: n.message,
                    icon: n.icon || 'notifications',
                    read: !!n.is_read,
                    category: n.category || 'General',
                    time: n.created_at
                }));
                this.feedPosts = (data.feed_posts || []).map(fp => ({

                    id: fp.id,
                    author: fp.author_name || 'User',
                    avatar: fp.author_avatar || 'https://via.placeholder.com/40',
                    content: fp.content,
                    image: fp.image || null,
                    created_at: fp.created_at,
                    liked: false,
                    comments: [],
                    showComments: false,
                    newComment: ''
                }));
                this.chatUsers = (data.chat_users || []).map(u2 => ({
                    id: u2.id,

                    // Add bio and tags for the profile modal
                    bio: u2.fitness_goals || '',




                    name: u2.fullname,
                    username: u2.email ? u2.email.split('@')[0] : (u2.fullname || '').toLowerCase().replace(/\s+/g,'.'),
                    avatar: u2.picture || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(u2.fullname) + '&background=f97316&color=fff')
                
                }));
                this.chatMessages = data.chat_messages || {};

                // set filtered chat users
                this.filteredChatUsers = this.chatUsers;


                // small dashboard placeholders if none
                this.setupChatAutoRefresh();
                
                // Dynamically populate dashboard schedule from fetched workouts
                this.dashboardSchedule = (this.workouts || []).slice(0, 3).map(w => ({
                    title: w.title,
                    datetime: `On ${new Date(w.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} at ${w.time.slice(0,5)}`,
                    icon: 'fitness_center',
                    colorBg: 'bg-orange-100 dark:bg-orange-900/30',
                    colorText: 'text-orange-500 dark:text-orange-400'
                }));
                this.dashboardUpcomingClasses = this.dashboardSchedule;

                /* ---------- Prefill payment form ---------- */
                this.paymentForm.firstName = this.profile.firstName || this.paymentForm.firstName;
                this.paymentForm.lastName = this.profile.lastName || this.paymentForm.lastName;
                this.paymentForm.email = this.profile.email || this.paymentForm.email;
                this.paymentForm.phone = this.profile.phone || this.paymentForm.phone;

                // update header
                this.headerTitle = `Welcome, ${this.profile.fullName.split(' ')[0] || 'Member'}!`;
            } catch (err) {
                console.error(err);

                Swal.fire('Error', 'Could not load dashboard data. Please login again.', 'error').then(()=> window.location.href='login.php');
            }
        },

        /* ---------- Feed helpers: image & link ---------- */
         async handleImageChange(event) {
        const f = event.target && event.target.files ? event.target.files[0] : null;
        if (!f) return;
        if (!f.type.startsWith('image/')) {
            Swal.fire('Error', 'Please select an image file.', 'error');
            if (event.target) event.target.value = '';
            return;
        }

        // show spinner in preview
        this.isUploadingImage = true;
        this.selectedImagePreview = '';
        this.selectedImageFile = null;

        try {
            // Try to use the global resizeImageFile if available (defined later on the page).
            // Fallback to in-place canvas resize if not available.
            let finalDataURL;
            if (typeof resizeImageFile === 'function') {
                // preferred: external helper
                finalDataURL = await resizeImageFile(f, 1024, 0.85);
            } else {
                // fallback: inline resizing using canvas
                finalDataURL = await new Promise((resolve, reject) => {
                    const img = new Image();
                    const url = URL.createObjectURL(f);
                    img.onload = () => {
                        try {
                            const maxDim = 1024;
                            const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
                            const cw = Math.round(img.width * scale);
                            const ch = Math.round(img.height * scale);
                            const canvas = document.createElement('canvas');
                            canvas.width = cw;
                            canvas.height = ch;
                            const ctx = canvas.getContext('2d');
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';
                            ctx.drawImage(img, 0, 0, cw, ch);
                            const dataURL = canvas.toDataURL('image/jpeg', 0.85);
                            URL.revokeObjectURL(url);
                            resolve(dataURL);
                        } catch (err) {
                            URL.revokeObjectURL(url);
                            reject(err);
                        }
                    };
                    img.onerror = () => {
                        URL.revokeObjectURL(url);
                        reject(new Error('Failed to load image for resizing.'));
                    };
                    img.src = url;
                });
            }

            // Convert to blob (use global helper if present)
            let blob;
            if (typeof dataURLToBlob === 'function') {
                blob = dataURLToBlob(finalDataURL);
            } else {
                // simple decoder
                const parts = finalDataURL.split(',');
                const mime = parts[0].match(/:(.*?);/)[1];
                const bstr = atob(parts[1]);
                let n = bstr.length;
                const u8arr = new Uint8Array(n);
                while (n--) u8arr[n] = bstr.charCodeAt(n);
                blob = new Blob([u8arr], { type: mime });
            }

            // If still too large, try stronger compression/resizing
            if (blob.size > this.MAX_IMAGE_BYTES) {
                // attempt lower quality pass if possible
                if (typeof resizeImageFile === 'function') {
                    finalDataURL = await resizeImageFile(f, 1024, 0.7);
                    blob = dataURLToBlob(finalDataURL);
                } else {
                    // fallback attempt: try reducing canvas quality
                    // (attempt already done above, so just fail on fallback)
                }
            }

            if (blob.size > this.MAX_IMAGE_BYTES) {
                Swal.fire('Error', 'Image is too large even after resizing. Please choose a smaller image (max 3MB).', 'error');
                this.selectedImagePreview = '';
                this.selectedImageFile = null;
                if (event.target) event.target.value = '';
                return;
            }

            // create a File so FormData.append sends a filename
            const filename = 'feed_' + Date.now() + '.jpg';
            const fileForUpload = new File([blob], filename, { type: blob.type || 'image/jpeg' });

            // set for UI & upload
            this.selectedImageFile = fileForUpload;
            this.selectedImagePreview = finalDataURL;
        } catch (err) {
            console.error('Feed image processing error', err);
            Swal.fire('Error', 'Failed to process image. Please try another image.', 'error');
            this.selectedImageFile = null;
            this.selectedImagePreview = '';
        } finally {
            this.isUploadingImage = false;
            if (event.target) event.target.value = '';
        }
    },

    // remove image helper (used by Remove image button)
    removeSelectedImage() {
        this.selectedImageFile = null;
        this.selectedImagePreview = '';
    },

/* Note: the existing postFeed() implementation in this file already
   builds a FormData, appends 'image_file' when this.selectedImageFile exists,
   and sends the POST with action 'post_feed'. That logic will now work
   because selectedImageFile is a File created above.
*/
        toggleLinkInput() {
            this.showLinkInput = !this.showLinkInput;
            if (!this.showLinkInput) this.linkUrl = '';
        },
        formatPostContent(s) {
            if (!s) return '';
            // content from server is already escaped; convert URLs to links and newlines to <br/>
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            // replace URLs with anchors
            const withAnchors = s.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline text-orange-400">$1</a>');
            // preserve newlines
            return withAnchors.replace(/\n/g, '<br/>');
        },

        /* ---------- Chat Auto-refresh ---------- */

        setupChatAutoRefresh() {
            // Check if already running
            if (this.chatRefreshInterval) {
                clearInterval(this.chatRefreshInterval);
            }
            // Load messages immediately and then start polling

            this.loadChatMessages();
            this.chatRefreshInterval = setInterval(() => {
                this.loadChatMessages();
            }, 5000); // Refresh every 5 seconds (adjust as needed)
        },

        async loadChatMessages() {

            this.isCheckingChat = true;
             if (!this.selectedChatUser) return;
            try {
                const res = await fetch('?action=initial_data');
                if (!res.ok) throw new Error('Failed to fetch initial data');
                const data = await res.json();
                if (data.error) {
                    Swal.fire('Error', data.error || 'Failed to load chat messages', 'error');
                    return;
                }

                this.chatMessages = data.chat_messages || {};
            } catch (error) {
                console.error("Error loading chat messages:", error);
            }
             finally {
                this.isCheckingChat = false; // Hide loading indicator
            }
        },

        /* ---------- Notification Auto-refresh ---------- */


        startNotificationCheck() {
            // Load notifications immediately and then start polling

            this.checkAndLoadNotifications();


            this.notificationInterval = setInterval(() => {
                this.checkAndLoadNotifications();
            }, this.notificationCheckIntervalMs);
        },
        async checkAndLoadNotifications() {
             this.isCheckingNotifications = true; // Show loading indicator
            try {
                const res = await fetch('?action=initial_data');
                if (!res.ok) throw new Error('Failed to fetch initial data');
                const data = await res.json();
                if (data.error) {
                    Swal.fire('Error', data.error || 'Failed to load notifications', 'error');
                    return;
                }
                // Update notifications only if there are new ones


                 const newNotifications = data.notifications || [];
                if (this.lastNotificationCheck === null || newNotifications.length > this.notifications.length || newNotifications.some(n => n.created_at > this.lastNotificationCheck)) {
                this.notifications = (data.notifications || []).map(n => ({
                    id: n.id,



                    type: n.type,
                    message: n.message,
                    icon: n.icon || 'notifications',
                    read: !!n.is_read,
                    category: n.category || 'General',
                    time: n.created_at

                }));

                 this.lastNotificationCheck = new Date().toISOString();
                }

            } catch (error) {

                console.error("Error checking for new notifications:", error);
            } finally {
                this.isCheckingNotifications = false; // Hide loading indicator
            }
            // Trigger a notification if there are new notifications
            if (newNotifications.length > this.notifications.length) {
            const latestNotification = newNotifications[newNotifications.length - 1];
            showNotification("New Notification", latestNotification.message, latestNotification.icon);

            }
        },


        /* ---------- Payment / membership helpers ---------- */
        openGymFeesModal() {
            this.showGymFeesModal = true;
            this.selectedGymFeeTier = '';
            this.selectedGymFeePrice = '';
        },
        selectTier(tier) {
            this.selectedGymFeeTier = tier.name;
            this.selectedGymFeePrice = tier.price;
        },
        openPaymentModal(tierName = '', tierPrice = '') {
            if (tierName) this.selectedGymFeeTier = tierName;
            if (tierPrice) this.selectedGymFeePrice = tierPrice;
            // prefill name/email from profile if available
            this.paymentForm.firstName = this.profile.firstName || this.paymentForm.firstName;
            this.paymentForm.lastName = this.profile.lastName || this.paymentForm.lastName;
            this.paymentForm.email = this.profile.email || this.paymentForm.email;
            this.paymentForm.phone = this.profile.phone || this.paymentForm.phone;
            this.showGymFeesModal = false;
            this.showPaymentModal = true;
        },
        closePaymentModal() {
            this.showPaymentModal = false;
            // reset sensitive fields lightly
            this.paymentForm.cardNumber = '';
            this.paymentForm.expiry = '';
            this.paymentForm.cvc = '';
        },
        copyNumber(num) {
            navigator.clipboard.writeText(num).then(()=> {
                Swal.fire('Copied', `Copied: ${num}`, 'success');
            }).catch(()=> Swal.fire('Error', 'Copy failed', 'error'));
        },
        maskName(full) {
            const parts = (full || '').split(' ').filter(Boolean);
            if (parts.length === 0) return '';
            return parts.map(p => p.charAt(0) + p.slice(1).replace(/./g, '*')).join(' ');
        },
        maskEmail(email) {
            const at = (email || '').indexOf('@');
            if (at === -1) return email;
            const user = email.slice(0, at);
            const domain = email.slice(at + 1);
            return user.charAt(0) + '***@' + domain;
        },
        maskPhone(phone) {
            const digits = (phone || '').replace(/\D/g, '');
            if (digits.length <= 4) return phone;
            return digits.slice(0, 3) + ' **** ' + digits.slice(-2);
        },
        onProceedPayment() {
            // validate fields quickly
            if (!this.paymentForm.firstName.trim() || !this.paymentForm.lastName.trim() || !this.paymentForm.email.trim() || !this.paymentForm.phone.trim()) {
                Swal.fire('Error', 'Please fill all required personal details.', 'error');
                return;
            }
            this.submitMembershipPayment();
        },
        async submitMembershipPayment() {
            // prepare payload
            const form = new FormData();
            form.append('action', 'purchase_membership');
            form.append('plan', this.selectedGymFeeTier || 'Membership');
            // normalize price (strip non-digits)
            const number = parseFloat((this.selectedGymFeePrice || '').replace(/[^\d.]/g, '')) || 0;
            form.append('amount', number);
            form.append('first_name', this.paymentForm.firstName || '');
            form.append('last_name', this.paymentForm.lastName || '');
            form.append('email', this.paymentForm.email || '');
            // simple payment method string (includes reference if needed)
            let pm = this.paymentForm.pm || 'GCash';
            if (pm === 'Card') {
                pm = 'Card';
            }
            form.append('payment_method', pm);

            try {
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data && data.success) {
                    this.showPaymentConfirmModal = false;
                    this.showPaymentModal = false;
                    this.showPaymentSuccessModal = true;
                    // reload initial data to pick up membership/payment
                    await this.loadInitialData();
                } else {
                    Swal.fire('Error', data.error || 'Payment failed', 'error');
                }
            } catch (err) {

                console.error(err);
                Swal.fire('Error', 'Payment request failed', 'error');
            }
        },
        backToDashboard() {
            this.showPaymentSuccessModal = false;
            this.setView('dashboard');
        },

        /* ---------- Workouts, feed, chat, profile ---------- */
        async addWorkout() {
            if (!this.newWorkout.title || !this.newWorkout.date || !this.newWorkout.time) {
                Swal.fire('Error', 'Please fill all required workout fields.', 'error');
                return;
            }
            try {
                const form = new FormData();
                form.append('action', 'add_workout');
                form.append('title', this.newWorkout.title);
                form.append('date', this.newWorkout.date);
                form.append('time', this.newWorkout.time);
                form.append('type', this.newWorkout.type);
                form.append('recurring', this.newWorkout.recurring ? '1' : '0');

                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    // push into local workouts list for UI
                    this.workouts.push({
                        id: data.id,
                        title: this.newWorkout.title,
                        date: this.newWorkout.date,
                        time: this.newWorkout.time,
                        type: this.newWorkout.type,
                        recurring: this.newWorkout.recurring ? 1 : 0
                    });
                    this.showAddWorkoutModal = false;
                    this.newWorkout = { title: '', date: new Date().toISOString().slice(0,10), time: '09:00', type: 'Cardio', recurring: false };
                    this.recurrence = { type: 'weekly', days: [] };
                    Swal.fire('Success', 'Workout added.', 'success');
                } else {
                    Swal.fire('Error', data.error || 'Failed to add workout.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to add workout.', 'error');
            }
        },
        async postFeed() {
            const content = (this.newFeedPost || '').trim();
            // allow image-only or link-only posts
            if (!content && !this.selectedImageFile && !this.linkUrl.trim()) {
                Swal.fire('Error', 'Please add text, an image, or a link to post.', 'error');
                return;
            }
            try {
                const form = new FormData();
                form.append('action', 'post_feed');
                form.append('content', content);
                if (this.linkUrl && this.linkUrl.trim()) form.append('link', this.linkUrl.trim());
                if (this.selectedImageFile) form.append('image_file', this.selectedImageFile);

                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success && data.post) {
                    const p = data.post;
                    this.feedPosts.unshift({
                        id: p.id,
                        author: p.author_name,
                        avatar: p.author_avatar || this.profile.avatar,
                        content: p.content,
                        image: p.image || null,
                        created_at: p.created_at,
                        liked: false,
                        comments: [],
                        showComments: false,
                        newComment: ''
                    });
                    // reset composer state
                    this.newFeedPost = '';
                    this.selectedImageFile = null;
                    this.selectedImagePreview = '';
                    this.linkUrl = '';
                    this.showLinkInput = false;
                    Swal.fire('Posted', 'Your post has been shared.', 'success');
                } else {
                    Swal.fire('Error', data.error || 'Failed to post', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to post.', 'error');
            }
        },
        async sendMessage() {
            if (!this.newMessageText.trim() || !this.selectedChatUser) return;
            try {
                const form = new FormData();
                form.append('action', 'send_message');
                form.append('to_user', this.selectedChatUser.id);
                form.append('message', this.newMessageText.trim());
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success && data.message) {
                    const msg = data.message;
                    const otherId = (msg.from_user_id == this.currentUserId) ? msg.to_user_id : msg.from_user_id;
                    if (!this.chatMessages[otherId]) this.chatMessages[otherId] = [];
                    this.chatMessages[otherId].push({
                        id: msg.id,
                        from_user_id: msg.from_user_id,
                        to_user_id: msg.to_user_id,
                        message: msg.message,
                        created_at: msg.created_at,
                        from_name: msg.from_name,
                        from_avatar: msg.from_avatar
                    });
                    this.newMessageText = '';
                    this.$nextTick(() => {
                        const container = this.$refs.chatMessages;
                        if (container) container.scrollTop = container.scrollHeight;
                    });
                } else {
                    Swal.fire('Error', data.error || 'Failed to send message', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to send message.', 'error');
            }
        },
        async saveProfileChanges() {
            if (!this.profile.fullName.trim()) {
                Swal.fire('Error', 'Full name is required.', 'error');
                return;
            }
            try {
                const form = new FormData();
                form.append('action', 'update_profile');
                form.append('fullName', this.profile.fullName);
                form.append('age', this.profile.age || '');
                form.append('gender', this.profile.gender || '');
                form.append('fitnessGoals', (this.profile.tags && this.profile.tags.join(', ')) || '');
                // If avatar is a data URL created by file reader, send it; otherwise send existing URL
                form.append('picture', this.profile.avatar || '');
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    this.isEditingProfile = false;
                    Swal.fire('Saved', 'Profile updated successfully', 'success');
                    await this.loadInitialData();
                } else {
                    Swal.fire('Error', data.error || 'Failed to save profile', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to save profile', 'error');
            }
        },

        /* ---------- Notifications ---------- */
        async toggleNotificationRead(notification) {
            try {
                const form = new FormData();
                form.append('action','mark_notification');
                form.append('notification_id', notification.id);
                form.append('is_read', notification.read ? '0' : '1'); // toggle
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    notification.read = !notification.read;
                } else {
                    Swal.fire('Error', data.error || 'Failed to update notification', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to update notification', 'error');
            }
        },
        async clearAllNotifications() {
            try {
                const form = new FormData();
                form.append('action', 'clear_notifications');
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    this.notifications = [];
                    this.showConfirmClearModal = false;
                    Swal.fire('Cleared', 'All notifications removed.', 'success');
                } else {
                    Swal.fire('Error', data.error || 'Failed to clear notifications', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to clear notifications', 'error');
            }
        },

        /* Manual confirm flow for payments that use a reference (used by some flows) */
        async confirmPayment() {
            if (!this.paymentReference.trim()) {
                Swal.fire('Error', 'Please enter a reference number.', 'error');
                return;
            }
            try {
                const form = new FormData();
                form.append('action', 'purchase_membership');
                form.append('plan', this.selectedGymFeeTier || 'Membership');
                const amt = parseFloat((this.selectedGymFeePrice || '').replace(/[^\d.]/g, '')) || 0;
                form.append('amount', amt);
                form.append('first_name', (this.profile.fullName||'').split(' ')[0] || '');
                form.append('last_name', (this.profile.fullName||'').split(' ').slice(1).join(' ') || '');
                form.append('email', this.profile.email || '');
                form.append('payment_method', 'Manual:' + this.paymentReference);
                const res = await fetch('', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    this.showPaymentModal = false;
                    this.paymentReference = '';
                    Swal.fire('Success', 'Membership purchased.', 'success');
                    await this.loadInitialData();
                } else {
                    Swal.fire('Error', data.error || 'Payment failed', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Payment request failed', 'error');
            }
        },

        /* ---------- Chat & UI helpers ---------- */
        filterChatUsers() {
            const q = (this.chatSearchQuery || '').trim().toLowerCase();
            if (!q) this.filteredChatUsers = this.chatUsers;
            else this.filteredChatUsers = this.chatUsers.filter(u => (u.name||'').toLowerCase().includes(q) || (u.username||'').toLowerCase().includes(q));
        },
        selectChatUser(user) {
            this.selectedChatUser = user;
            this.userDropdownOpen = null;
            this.chatMoreDropdownOpen = false;
            this.newMessageText = '';
            this.$nextTick(() => {
                const container = this.$refs.chatMessages;
                if (container) container.scrollTop = container.scrollHeight;
            });
        },
        clearSelectedChat() {
            this.selectedChatUser = null;
            this.userDropdownOpen = null;
            this.chatMoreDropdownOpen = false;
            this.newMessageText = '';
        },

        toggleUserDropdown(id) {
            this.userDropdownOpen = this.userDropdownOpen === id ? null : id;
        },
        viewUserProfile(userId) {
            if (!userId) return;
            const user = this.chatUsers.find(u => u.id === userId);
            if (user) {
                this.viewedUser = {
                    id: user.id,
                    name: user.name,
                    username: user.username,
                    avatar: user.avatar,
                    bio: user.bio || 'This user has not set a bio yet.',
                    // Assuming bio/fitness_goals is a comma-separated string
                    tags: user.bio ? user.bio.split(',').map(t => t.trim()).filter(t => t) : []
                };
                this.showUserProfileModal = true;
            } else {
                console.warn(`User with ID ${userId} not found in chatUsers list.`);
                Swal.fire('Error', 'Could not load user profile.', 'error');
            }
        },
        blockUser(user) {
            Swal.fire('Blocked', `${user.name || 'User'} has been blocked (client-side only).`, 'success');
        },
        reportUser(user) {
            Swal.fire('Reported', `${user.name || 'User'} has been reported (client-side only).`, 'warning');
        },
        startConversation() {
            if (!this.selectedChatUser) return;
            // focus chat input
            this.newMessageText = '';
            this.$nextTick(() => {
                const container = this.$refs.chatMessages;
                if (container) container.scrollTop = container.scrollHeight;
            });
        },

        /* ---------- Notifications filtering ---------- */
        filterNotifications() {
            // Helper that will be used indirectly by getter filteredNotifications
            // We keep this as a placeholder so template's @input can call it.
        },

        /* ---------- Schedule helpers ---------- */
        navigateSchedulePrev() {
            const d = new Date(this.currentDate);
            d.setMonth(d.getMonth() - 1);
            this.currentDate = d;
        },
        navigateScheduleNext() {
            const d = new Date(this.currentDate);
            d.setMonth(d.getMonth() + 1);
            this.currentDate = d;
        },

        /* ---------- Small UI helpers ---------- */
        togglePasswordVisibility() {
            this.passwordVisible = !this.passwordVisible;
        },

        startEditProfile() { this.isEditingProfile = true; this._editProfileBackup = JSON.parse(JSON.stringify(this.profile)); },
        cancelEditProfile() { if (this._editProfileBackup) { this.profile = JSON.parse(JSON.stringify(this._editProfileBackup)); this._editProfileBackup = null; } this.isEditingProfile = false; },

        updateProfilePicture(event) {
            const file = event.target.files ? event.target.files[0] : null;
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                Swal.fire('Error', 'Please select an image file', 'error');
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                this.profile.avatar = reader.result; // base64 data URL
            };
            reader.readAsDataURL(file);
            if (event.target) event.target.value = '';
        },

        /* ---------- Computed / getters ---------- */
        get monthName() { return this.currentDate.toLocaleString('default', { month: 'long' }); },
        get year() { return this.currentDate.getFullYear(); },
        get daysInMonth() { return new Date(this.year, this.currentDate.getMonth() + 1, 0).getDate(); },
        get firstDayOfMonth() { return new Date(this.year, this.currentDate.getMonth(), 1).getDay(); },
        get calendarDays() {
            const days = [];
            for (let i = 0; i < this.firstDayOfMonth; i++) days.push({date:null,isCurrentMonth:false});
            for (let i = 1; i <= this.daysInMonth; i++) {
                const dateISO = new Date(this.year, this.currentDate.getMonth(), i).toISOString().slice(0,10);
                days.push({ date: i, isCurrentMonth: true, dateISO, isToday: i === new Date().getDate() && this.currentDate.getMonth() === new Date().getMonth() && this.year === new Date().getFullYear() });
            }
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) days.push({date:null,isCurrentMonth:false});
            return days;
        },
        get weeklySchedule() {
            const today = new Date(this.currentDate);
            const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
            let week = [];
            for (let i=0; i<7; i++) {
                const day = new Date(startOfWeek);
                day.setDate(day.getDate() + i);
                week.push({ date: day, dayName: day.toLocaleDateString('default',{ weekday: 'short' }).toUpperCase(), dayNum: day.getDate(), workouts: this.getWorkoutsForDay(day.getDate())});
            }
            return week;
        },
        get dailySchedule() {
            return { date: this.currentDate, workouts: this.getWorkoutsForDay(this.currentDate.getDate()) };
        },
        get scheduleHeader() {
            return `${this.monthName} ${this.year}`;
        },
        getWorkout(day) { return (this.workouts || []).find(w => w.date && parseInt(w.date.split('-')[2],10) === Number(day)) ? (this.workouts.find(w => w.date && parseInt(w.date.split('-')[2],10) === Number(day)).title) : null; },
        getWorkoutsForDay(day) { return (this.workouts || []).filter(w => w.date && parseInt(w.date.split('-')[2],10) === Number(day)); },
        getWorkoutColor(type) {
            if (!type) return '';
            if (type.includes('Strength')) return 'bg-orange-500/20 text-orange-400';
            if (type.includes('Flexibility')) return 'bg-blue-500/20 text-blue-400';
            if (type.includes('Cardio')) return 'bg-purple-500/20 text-purple-400';
            return 'bg-green-500/20 text-green-400';
        },

        get filteredNotifications() {
            const q = (this.notificationSearch || '').trim().toLowerCase();
            return (this.notifications || []).filter(n => {
                const matchesSearch = !q || (n.message || '').toLowerCase().includes(q) || (n.type || '').toLowerCase().includes(q) || (n.category || '').toLowerCase().includes(q);
                const matchesFilter = this.activeNotificationFilter === 'All' ||
                                      (this.activeNotificationFilter === 'Unread' && !n.read) ||
                                      (this.activeNotificationFilter === 'Promotions' && (n.category || '').toLowerCase() === 'promotions') ||
                                      (this.activeNotificationFilter === 'System Alerts' && (n.category || '').toLowerCase() === 'system alerts' || (n.category || '').toLowerCase() === 'system');
                return matchesSearch && matchesFilter;
            });
        },

        /* ---------- Messages mapping for UI ---------- */
        getMessagesForUser(userId) {
            const msgs = this.chatMessages && this.chatMessages[userId] ? this.chatMessages[userId] : [];
            return msgs.map(m => ({
                id: m.id,
                text: m.message || m.message || '',
                time: (m.created_at || '').slice(11,16) || '',
                senderName: m.from_name || (m.from_name || ''),
                senderAvatar: m.from_avatar || (this.chatUsers.find(u=>u.id == (m.from_user_id || m.from_user_id)) || {}).avatar || this.profile.avatar,
                isSentByCurrentUser: (m.from_user_id == this.currentUserId)
            }));
        },

        /* ---------- Small helpers ---------- */
        toggleLike(post) { post.liked = !post.liked; },
        toggleCommentSection(post) { post.showComments = !post.showComments; },
        addComment(post) {
            const text = (post.newComment || '').trim();
            if (!text) return;
            post.comments.push({ author: this.profile.fullName, avatar: this.profile.avatar, content: text, timeAgo: 'Just now' });
            post.newComment = '';
            post.showComments = true;
        },

        markAllNotificationsRead() {
            // Simple client-side loop to mark each notification read via API
            const unread = (this.notifications || []).filter(n => !n.read);
            unread.forEach(n => {
                // call toggleNotificationRead which will toggle server-side
                this.toggleNotificationRead(n);
            });
        }
    };
    // initial data load is already invoked via x-init in the HTML root element
}

window.onload = requestNotificationPermission;
</script>

<script>
     // Maximum allowed bytes for client-side upload
  const REGISTER_MAX_IMAGE_BYTES = 3 * 1024 * 1024; // 3MB

  // utility: dataURL -> Blob
  function dataURLToBlob(dataURL) {
    const parts = dataURL.split(',');
    const mime = parts[0].match(/:(.*?);/)[1];
    const bstr = atob(parts[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], { type: mime });
  }

  // resize File to dataURL with max dimension and quality (returns dataURL)
  function resizeImageFile(file, maxDim = 1024, quality = 0.85) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = () => {
        try {
          let { width, height } = img;
          const scale = Math.min(1, maxDim / Math.max(width, height));
          const cw = Math.round(width * scale);
          const ch = Math.round(height * scale);

          const canvas = document.createElement('canvas');
          canvas.width = cw;
          canvas.height = ch;
          const ctx = canvas.getContext('2d');
          ctx.imageSmoothingEnabled = true;
          ctx.imageSmoothingQuality = 'high';
          ctx.drawImage(img, 0, 0, cw, ch);

          const dataURL = canvas.toDataURL('image/jpeg', quality);
          URL.revokeObjectURL(url);
          resolve(dataURL);
        } catch (err) {
          URL.revokeObjectURL(url);
          reject(err);
        }
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('Failed to load image for resizing.'));
      };
      img.src = url;
    });
  }

  // Handle profile-upload preview with resizing + spinner
  const profileUpload = document.getElementById('profile-upload');
  const profilePicture = document.getElementById('profile-picture');
  const profileSpinner = document.getElementById('profile-upload-spinner');
  let uploadedPictureDataURL = null;

  profileUpload.addEventListener('change', async function (event) {
    const file = event.target.files ? event.target.files[0] : null;
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      Swal.fire('Error', 'Please select an image file', 'error');
      event.target.value = '';
      return;
    }

    profileSpinner.style.display = 'flex';
    try {
      // Attempt resize immediately
      let finalDataURL = await resizeImageFile(file, 1024, 0.85);
      let blob = dataURLToBlob(finalDataURL);

      // If still too big, attempt stronger compression
      if (blob.size > REGISTER_MAX_IMAGE_BYTES) {
        finalDataURL = await resizeImageFile(file, 1024, 0.7);
        blob = dataURLToBlob(finalDataURL);
      }

      if (blob.size > REGISTER_MAX_IMAGE_BYTES) {
        Swal.fire('Error', 'Image is too large even after resizing. Please choose a smaller image (max 3MB).', 'error');
        uploadedPictureDataURL = null;
        profilePicture.src = 'https://via.placeholder.com/80?text=Profile';
        profileUpload.value = '';
      } else {
        // set preview & data
        profilePicture.src = finalDataURL;
        uploadedPictureDataURL = finalDataURL;
      }
    } catch (err) {
      console.error(err);
      Swal.fire('Error', 'Failed to process image. Please try another image.', 'error');
      uploadedPictureDataURL = null;
      profilePicture.src = 'https://via.placeholder.com/80?text=Profile';
    } finally {
      profileSpinner.style.display = 'none';
    }
  });

  // Profile form submit over AJAX (profile setup)
  const profileForm = document.getElementById('profile-form');
  const successMessage = document.getElementById('success-message');

  profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const spinnerEl = profileSpinner;
    spinnerEl.style.display = 'flex';

    const formData = new FormData(profileForm);
    formData.append('action', 'profile_setup');

    // Attach picture: prefer resized preview dataURL, else fallback to img.src
    const pictureToSend = uploadedPictureDataURL || profilePicture.src || '';
    if (pictureToSend && pictureToSend.startsWith('data:')) {
      const blobCheck = dataURLToBlob(pictureToSend);
      if (blobCheck.size > REGISTER_MAX_IMAGE_BYTES) {
        spinnerEl.style.display = 'none';
        Swal.fire('Error', 'Image is too large (max 3MB). Please choose a smaller image.', 'error');
        return;
      }
      formData.set('picture', pictureToSend);
    } else {
      formData.set('picture', pictureToSend);
    }

    // quick front-end validation (existing)
    if (!formData.get('fullName') || !formData.get('age') || !formData.get('gender') || !formData.get('fitnessGoals')) {
      spinnerEl.style.display = 'none';
      Swal.fire('Error', 'Please fill all the required fields.', 'error');
      return;
    }

    try {
      const res = await fetch('', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        successMessage.classList.remove('hidden');
        profileForm.querySelectorAll('input, select, textarea, button').forEach(el => el.disabled = true);
        setTimeout(() => {
          showStep(2); // uses existing showStep defined in this page
          setTimeout(() => {
            window.location.href = 'customerdash.php';
          }, 1500);
        }, 800);
      } else {
        Swal.fire('Error', data.error || 'Failed to save profile. Please try again.', 'error');
      }
    } catch (err) {
      console.error(err);
      Swal.fire('Error', 'Failed to save profile. Please try again.', 'error');
    } finally {
      spinnerEl.style.display = 'none';
    }
  });
</script>

</body>

</html>