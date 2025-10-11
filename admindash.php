<?php // admindash.php
// Admin dashboard (UI + API) — unified single file
// Requires backend/config.php which defines getDbConnection(), csrf_generate(), csrf_validate(), helpers.

require_once __DIR__ . '/backend/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

// Helper: JSON response
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Escape output helper for HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Handle simple API endpoints (GET/POST)
if (isset($_GET['action']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
    $method = $_SERVER['REQUEST_METHOD'];

    // GET endpoints
    if ($method === 'GET' && isset($_GET['action'])) {
        $action = $_GET['action'];

        // 1) Fetch dashboard data
        if ($action === 'get_dashboard_data' || $action === 'get_notifications' || $action === 'get_chat_data') {
            try {
                $conn = getDbConnection();

                // Basic Stats (always returned)
                $total_users_q = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
                $total_users_q->execute();
                $total_users = $total_users_q->get_result()->fetch_assoc()['count'] ?? 0;
                $total_users_q->close();

                $new_users_q = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
                $new_users_q->execute();
                $new_users_this_month = $new_users_q->get_result()->fetch_assoc()['count'] ?? 0;
                $new_users_q->close();

                $workouts_today_q = $conn->prepare("SELECT COUNT(*) as count FROM workouts WHERE date = CURDATE()");
                $workouts_today_q->execute();
                $total_workouts_today = $workouts_today_q->get_result()->fetch_assoc()['count'] ?? 0;
                $workouts_today_q->close();

                $response = [
                    'stats' => [
                        'total_users' => (int)$total_users,
                        'new_users_this_month' => (int)$new_users_this_month,
                        'total_workouts_today' => (int)$total_workouts_today
                    ],
                    'csrf_token' => csrf_generate()
                ];

                // If full dashboard requested, fetch detailed lists
                if ($action === 'get_dashboard_data') {
                    // Customers
                    $customers_q = $conn->prepare("SELECT id, fullname, email, picture, membership_status, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 500");
                    $customers_q->execute();
                    $response['customers'] = $customers_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $customers_q->close();

                    // Bookings (workouts) - include user picture/name and trainer if present
                    $bookings_q = $conn->prepare("SELECT w.id, w.user_id, IFNULL(u.fullname, 'Unknown') AS fullname, u.picture, w.title, w.date, w.time, COALESCE(w.status, 'Pending') AS status, IFNULL(t.name, 'No Trainer') AS trainer_name FROM workouts w LEFT JOIN users u ON w.user_id = u.id LEFT JOIN trainers t ON w.trainer_id = t.id ORDER BY w.date DESC, w.time DESC LIMIT 500");
                    $bookings_q->execute();
                    $response['bookings'] = $bookings_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $bookings_q->close();

                    // Contact Messages
                    $contacts_q = $conn->prepare("SELECT id, name, email, message, is_read, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 500");
                    $contacts_q->execute();
                    $response['contacts'] = $contacts_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $contacts_q->close();

                    // Payments
                    $payments_q = $conn->prepare("SELECT p.id, p.user_id, IFNULL(u.fullname,'Unknown') AS fullname, u.picture, p.plan, p.amount, p.payment_method, p.payment_status, p.created_at FROM payments p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 500");
                    $payments_q->execute();
                    $response['payments'] = $payments_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $payments_q->close();

                    // Login history (safe query)
                    $login_q = $conn->prepare("SELECT lh.user_id, u.fullname, u.picture, lh.login_time, lh.ip_address, lh.user_agent FROM login_history lh LEFT JOIN users u ON lh.user_id = u.id ORDER BY lh.login_time DESC LIMIT 500");
                    $login_q->execute();
                    $response['login_history'] = $login_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $login_q->close();
                }

                // Notifications (can be fetched independently)
                if ($action === 'get_dashboard_data' || $action === 'get_notifications') {
                    // Fetch notifications for admin (use current admin user id from session)
                    $admin_user_id = intval($_SESSION['user_id'] ?? 1);
                    $notif_q = $conn->prepare("SELECT id, type, message, icon, category, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
                    $notif_q->bind_param('i', $admin_user_id);
                    $notif_q->execute();
                    $response['notifications'] = $notif_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $notif_q->close();
                }

                // Chat data (can be fetched independently)
                if ($action === 'get_dashboard_data' || $action === 'get_chat_data') {
                    $admin_id = intval($_SESSION['user_id'] ?? 0);
                    $selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

                    // Conversation list
                    $users_q = $conn->prepare("
                        SELECT u.id, u.fullname, u.picture,
                               (SELECT message FROM chat_messages
                                WHERE (from_user_id = u.id AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = u.id)
                                ORDER BY created_at DESC LIMIT 1) as last_message,
                               (SELECT created_at FROM chat_messages
                                WHERE (from_user_id = u.id AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = u.id)
                                ORDER BY created_at DESC LIMIT 1) as last_message_time
                        FROM users u WHERE u.role = 'customer' ORDER BY last_message_time DESC
                    ");
                    $users_q->bind_param("iiii", $admin_id, $admin_id, $admin_id, $admin_id);
                    $users_q->execute();
                    $response['chat_users'] = $users_q->get_result()->fetch_all(MYSQLI_ASSOC);
                    $users_q->close();

                    // Selected conversation messages
                    if ($selected_user_id) {
                        $messages_q = $conn->prepare("SELECT * FROM chat_messages WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?) ORDER BY created_at ASC");
                        $messages_q->bind_param("iiii", $admin_id, $selected_user_id, $selected_user_id, $admin_id);
                        $messages_q->execute();
                        $response['chat_messages'] = $messages_q->get_result()->fetch_all(MYSQLI_ASSOC);
                        $messages_q->close();
                    } else {
                        $response['chat_messages'] = [];
                    }
                }

                $conn->close();
                json_response($response);
            } catch (Exception $e) {
                // Return a generic error message for the client
                json_response(['error' => 'Server error while fetching data.'], 500);
            }
        }

        // Logout
        if ($action === 'logout') {
            session_unset();
            session_destroy();
            header('Location: login.php');
            exit();
        }

        // Unknown GET action
        json_response(['error' => 'Unknown action.'], 400);
    }

    // POST endpoints
    if ($method === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // Validate CSRF token
        $token = $_POST['csrf'] ?? '';
        if (!csrf_validate($token)) {
            json_response(['error' => 'Invalid CSRF token.'], 403);
        }

        try {
            $conn = getDbConnection();

            // 1) Update booking/workout status (approve/decline)
            if ($action === 'update_booking_status') {
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                if ($id <= 0 || $status === '') {
                    $conn->close();
                    json_response(['error' => 'Invalid parameters.'], 400);
                }
                $stmt = $conn->prepare("UPDATE workouts SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $status, $id);
                $ok = $stmt->execute();
                $stmt->close();

                if ($ok) {
                    // Get user_id from workout
                    $user_q = $conn->prepare("SELECT user_id FROM workouts WHERE id = ?");
                    $user_q->bind_param('i', $id);
                    $user_q->execute();
                    $user_id = $user_q->get_result()->fetch_assoc()['user_id'] ?? 0;
                    $user_q->close();

                    if ($user_id > 0) {
                        $notif_msg = "Your booking status updated to '{$status}' for ID {$id}";
                        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, category, is_read) VALUES (?, 'booking_update', ?, 'Bookings', 0)");
                        $notif_stmt->bind_param('is', $user_id, $notif_msg);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }

                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 2) Update payment status
            if ($action === 'update_payment_status') {
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                if ($id <= 0 || $status === '') {
                    $conn->close();
                    json_response(['error' => 'Invalid parameters.'], 400);
                }
                $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
                $stmt->bind_param('si', $status, $id);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 2.5) Add/Edit/Delete Booking
            if ($action === 'add_booking' || $action === 'update_booking') {
                $user_id = intval($_POST['user_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $date = trim($_POST['date'] ?? '');
                $time = trim($_POST['time'] ?? '');
                $status = trim($_POST['status'] ?? 'Pending');
                $booking_id = intval($_POST['booking_id'] ?? 0); // For updates

                if ($user_id <= 0 || empty($title) || empty($date) || empty($time)) {
                    $conn->close();
                    json_response(['error' => 'Missing required fields.'], 400);
                }

                if ($action === 'add_booking') {
                    $stmt = $conn->prepare("INSERT INTO workouts (user_id, title, date, time, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('issss', $user_id, $title, $date, $time, $status);
                } else { // update_booking
                    if ($booking_id <= 0) {
                        $conn->close();
                        json_response(['error' => 'Invalid booking ID for update.'], 400);
                    }
                    $stmt = $conn->prepare("UPDATE workouts SET user_id = ?, title = ?, date = ?, time = ?, status = ? WHERE id = ?");
                    $stmt->bind_param('issssi', $user_id, $title, $date, $time, $status, $booking_id);
                }
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            if ($action === 'delete_booking') {
                $booking_id = intval($_POST['id'] ?? 0);
                if ($booking_id <= 0) {
                    $conn->close();
                    json_response(['error' => 'Invalid booking ID.'], 400);
                }
                $stmt = $conn->prepare("DELETE FROM workouts WHERE id = ?");
                $stmt->bind_param('i', $booking_id);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 3) Mark contact message as read/unread
            if ($action === 'mark_contact_read') {
                $id = intval($_POST['id'] ?? 0);
                $is_read = isset($_POST['is_read']) && intval($_POST['is_read']) === 1 ? 1 : 0;
                if ($id <= 0) {
                    $conn->close();
                    json_response(['error' => 'Invalid parameters.'], 400);
                }
                $stmt = $conn->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
                $stmt->bind_param('ii', $is_read, $id);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 4) Send chat message
            if ($action === 'send_message') {
                $to_user_id = intval($_POST['to_user_id'] ?? 0);
                $message = trim($_POST['message'] ?? '');
                $from_user_id = $_SESSION['user_id'];

                if ($to_user_id <= 0 || empty($message)) {
                    $conn->close();
                    json_response(['error' => 'Invalid parameters.'], 400);
                }

                $stmt = $conn->prepare("INSERT INTO chat_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
                $stmt->bind_param('iis', $from_user_id, $to_user_id, $message);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 5) Mark notification read/unread
            if ($action === 'mark_notification_read') {
                $notif_id = intval($_POST['id'] ?? 0);
                $is_read = isset($_POST['is_read']) && intval($_POST['is_read']) === 1 ? 1 : 0;
                if ($notif_id <= 0) {
                    $conn->close();
                    json_response(['error' => 'Invalid notification ID.'], 400);
                }
                $stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE id = ?");
                $stmt->bind_param('ii', $is_read, $notif_id);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            // 6) Clear all notifications for current user
            if ($action === 'clear_notifications') {
                $admin_user_id = intval($_SESSION['user_id'] ?? 0);
                if ($admin_user_id <= 0) {
                    $conn->close();
                    json_response(['error' => 'Invalid user.'], 400);
                }
                $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
                $stmt->bind_param('i', $admin_user_id);
                $ok = $stmt->execute();
                $stmt->close();
                $conn->close();
                json_response(['success' => (bool)$ok]);
            }

            $conn->close();
            json_response(['error' => 'Unknown action.'], 400);
        } catch (Exception $e) {
            json_response(['error' => 'Server error.'], 500);
        }
    }
}

// If not API, render the HTML UI below
$csrf = csrf_generate();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Verso Gym Admin — Unified Dashboard</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

  <!-- Tailwind (with forms & container queries) -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

  <style>
    /* styles omitted here for brevity, use the original styles from your admindash.php */
  </style>

  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#E67E22",
            "background-light": "#F0F2F5",
            "background-dark": "#0F172A",
            "card-light": "#FFFFFF",
            "card-dark": "#1E293B",
            "text-light": "#1F2937",
            "text-dark": "#E2E8F0",
            "subtext-light": "#64748B",
            "subtext-dark": "#94A3B8"
          },
          fontFamily: {
            display: ["Poppins", "sans-serif"],
          },
          borderRadius: {
            DEFAULT: "0.75rem",
            lg: "1rem",
            xl: "1.25rem",
          },
        },
      },
    };
  </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark font-display flex transition-colors duration-300">

 
  <!-- SIDEBAR -->
  <aside id="sidebar" class="fixed top-0 left-0 h-screen bg-white/80 dark:bg-slate-900/70 backdrop-blur-lg border-r border-gray-200/80 dark:border-gray-800/60 w-64 p-6 flex flex-col transition-all duration-300 z-50">
    <div class="flex items-center gap-3 mb-10">
      <img alt="Verso Gym logo" class="w-10 h-10" src="img/logo.png" />
      <h1 id="sidebar-title" class="text-2xl font-bold text-text-light dark:text-text-dark">Verso</h1>
    </div>

    <nav id="sidebar-nav" class="flex-grow space-y-2">
      <button data-section="dashboard" class="section-link flex items-center gap-4 w-full text-left px-4 py-3 rounded-lg text-white font-medium shadow-lg bg-gradient-to-r from-primary to-orange-400">
        <span class="material-icons">dashboard</span>
        <span class="sidebar-text">Dashboard</span>
      </button>

      <button data-section="customers" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">people</span>
        <span class="sidebar-text">Customers</span>
      </button>

      <button data-section="bookings" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">book_online</span>
        <span class="sidebar-text">Bookings</span>
      </button>

      <button data-section="analytics" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">analytics</span>
        <span class="sidebar-text">Analytics</span>
      </button>
      <button data-section="login-history" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">history</span>
        <span class="sidebar-text">Login History</span>
      </button>

      <button data-section="contact" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">email</span>
        <span class="sidebar-text">Contact</span>
      </button>

      <button data-section="billing" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">receipt_long</span>
        <span class="sidebar-text">Billing</span>
      </button>

      <button data-section="chat" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left">
        <span class="material-icons">chat</span>
        <span class="sidebar-text">Chat</span>
      </button>

      <button data-section="notifications" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all duration-200 w-full text-left relative">
        <span class="material-icons">notifications</span>
        <span class="sidebar-text">Notifications</span>
        <span id="sidebar-notif-badge" class="absolute top-2 right-2 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
      </button>
    </nav>

    <div class="mt-auto space-y-2">
      <a href="?action=logout" class="flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 w-full text-left">
        <span class="material-icons">logout</span>
        <span class="sidebar-text">Logout</span>
      </a>
      <button data-section="settings" class="section-link flex items-center gap-4 px-4 py-3 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-100 dark:hover:bg-gray-800/60 w-full text-left">
        <span class="material-icons">settings</span>
        <span class="sidebar-text">Settings</span>
      </button>
    </div>
  </aside>

  <!-- Header -->
  <main id="main-wrapper" class="flex-1 transition-all duration-300 ml-64 p-8">
    <header class="flex justify-between items-center mb-8">
      <div class="flex items-center gap-4">
        <button id="sidebar-toggle-close" class="text-subtext-light dark:text-subtext-dark hover:text-text-light"><span class="material-icons text-3xl">menu_open</span></button>
        <div>
          <h2 id="page-title" class="text-3xl font-bold text-text-light dark:text-text-dark">Welcome, Admin!</h2>
          <p id="page-sub" class="text-subtext-light dark:text-subtext-dark">Overview and quick stats</p>
        </div>
      </div>

      <div class="flex items-center gap-6">
        <div class="flex items-center gap-3">
          <span class="material-icons text-yellow-500">wb_sunny</span>
          <label class="relative inline-flex items-center cursor-pointer" for="theme-toggle">
            <input class="sr-only peer" id="theme-toggle" type="checkbox" />
            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
          <span class="material-icons text-indigo-400">nights_stay</span>
        </div>

        <button id="notif-button" class="relative text-subtext-light dark:text-subtext-dark hover:text-text-light">
          <span class="material-icons text-3xl">notifications</span>
          <span id="header-notif-badge" class="absolute top-0 right-0 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center border-2 border-background-light dark:border-background-dark hidden">0</span>
        </button>

        <div class="flex items-center gap-4">
          <img alt="Admin avatar" class="w-11 h-11 rounded-full" src="<?php echo e($_SESSION['user_picture'] ?? 'img/logo.png'); ?>" />
          <div>
            <p class="font-semibold text-text-light dark:text-text-dark"><?php echo e($_SESSION['user_fullname'] ?? 'Admin User'); ?></p>
            <p class="text-sm text-subtext-light dark:text-subtext-dark">Administrator</p>
          </div>
        </div>
      </div>
    </header>

    <!-- SECTIONS (All pages included as sections in the single file) -->
    <div id="sections-container" class="space-y-8">

      <!-- 1. Dashboard -->
      <section id="dashboard" class="page-section">
        <div class="p-8 rounded-xl glass-light dark:glass-dark mb-8">
          <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Gym Statistics</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg text-center shadow-md border border-gray-200/80 dark:border-gray-700/50">
              <span class="material-icons text-5xl text-primary mb-3">group</span>
              <p id="total-customers" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
              <p class="text-subtext-light dark:text-subtext-dark">Total Customers</p>
            </div>
            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg text-center shadow-md border border-gray-200/80 dark:border-gray-700/50">
              <span class="material-icons text-5xl text-green-500 mb-3">person_add</span>
              <p id="new-members" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
              <p class="text-subtext-light dark:text-subtext-dark">New Members This Month</p>
            </div>
            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg text-center shadow-md border border-gray-200/80 dark:border-gray-700/50">
              <span class="material-icons text-5xl text-yellow-500 mb-3">fitness_center</span>
              <p id="workouts-today" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
              <p class="text-subtext-light dark:text-subtext-dark">Workouts Logged Today</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mt-8">
          <div class="lg:col-span-3 p-8 rounded-xl glass-light dark:glass-dark">
            <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Booking Requests</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead class="border-b-2 border-gray-200/80 dark:border-gray-700/50">
                  <tr>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Customer</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Class</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Date</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark text-center">Action</th>
                  </tr>
                </thead>
                <tbody id="bookings-table-body">
                  <!-- populated by JS -->
                </tbody>
              </table>
            </div>
          </div>

          <div class="lg:col-span-2 p-8 rounded-xl glass-light dark:glass-dark">
            <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Booking Analytics</h3>
            <div class="space-y-6">
              <div>
                <div class="flex justify-between items-center mb-1.5">
                  <p class="font-medium text-text-light dark:text-text-dark">Yoga Class</p>
                  <p id="percent-yoga" class="text-sm font-medium text-subtext-light dark:text-subtext-dark">0%</p>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div id="bar-yoga" class="bg-gradient-to-r from-primary to-orange-400 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between items-center mb-1.5">
                  <p class="font-medium text-text-light dark:text-text-dark">HIIT</p>
                  <p id="percent-hiit" class="text-sm font-medium text-subtext-light dark:text-subtext-dark">0%</p>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div id="bar-hiit" class="bg-gradient-to-r from-primary to-orange-400 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between items-center mb-1.5">
                  <p class="font-medium text-text-light dark:text-text-dark">Pilates</p>
                  <p id="percent-pilates" class="text-sm font-medium text-subtext-light dark:text-subtext-dark">0%</p>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div id="bar-pilates" class="bg-gradient-to-r from-primary to-orange-400 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between items-center mb-1.5">
                  <p class="font-medium text-text-light dark:text-text-dark">Weight Training</p>
                  <p id="percent-weight" class="text-sm font-medium text-subtext-light dark:text-subtext-dark">0%</p>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div id="bar-weight" class="bg-gradient-to-r from-primary to-orange-400 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 2. Customers -->
      <section id="customers" class="page-section hidden">
        <div class="p-8 rounded-xl glass-light dark:glass-dark">
            <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Customers</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead class="border-b-2 border-gray-200/80 dark:border-gray-700/50">
                  <tr>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Name</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Email</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Membership</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Joined</th>
                    <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark text-center">Actions</th>
                  </tr>
                </thead>
                <tbody id="customers-table-body"></tbody>
              </table>
            </div>
        </div>
      </section>

      <!-- Contact Messages Section -->
      <section id="contact" class="page-section hidden">
        <div class="p-8 rounded-xl glass-light dark:glass-dark">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Contact Form Messages</h3>
            <div class="flex items-center gap-4">
              <div class="relative">
                <input class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0" placeholder="Search messages..." type="text" />
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="border-b border-gray-300/50 dark:border-gray-700/50">
                <tr>
                  <th class="py-3 pr-4 font-semibold text-subtext-light dark:text-subtext-dark">From</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Message</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Date</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Status</th>
                  <th class="py-3 pl-4 font-semibold text-subtext-light dark:text-subtext-dark text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="contact-messages-body">
                <!-- populated by JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- 3. Bookings (detailed) -->
      <section id="bookings" class="page-section hidden">
        <div class="p-8 rounded-xl glass-light dark:glass-dark">
          <div class="flex flex-wrap items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
                <input class="pl-10 pr-4 py-2 w-64 bg-background-light dark:bg-card-dark border border-gray-300/70 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" placeholder="Search customer..." type="text" />
              </div>
              <div class="relative">
                <select class="pl-4 pr-10 py-2 w-48 bg-background-light dark:bg-card-dark border border-gray-300/70 dark:border-gray-700 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-primary/50">
                  <option>All Services</option>
                  <option>Yoga Class</option>
                  <option>HIIT Blast</option>
                  <option>Personal Training</option>
                  <option>Facility Reservation</option>
                </select>
                <span class="material-icons absolute right-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark pointer-events-none">expand_more</span>
               



              </div>
              <div class="relative">
                <input class="pl-4 pr-4 py-2 w-48 bg-background-light dark:bg-card-dark border border-gray-300/70 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50" type="date" value="<?php echo date('Y-m-d'); ?>" />
              </div>
            </div>
            <button id="open-add-booking" class="px-5 py-2 bg-primary text-white rounded-lg shadow-md hover:bg-orange-600 transition-colors flex items-center gap-2">
              <span class="material-icons text-lg">add</span>
              Add New Booking
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="border-b border-gray-300/50 dark:border-gray-700/50">
                <tr>
                  <th class="py-3 pr-4 font-semibold text-subtext-light dark:text-subtext-dark">Customer</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Service</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Date & Time</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Status
                     <td>
    <button @click="openStatusModal(booking)" class="text-green-400 hover:text-green-300">Update Status</button>
    <button @click="openDeleteModal(booking)" class="text-red-400 hover:text-red-300 ml-2">Delete</button>
</td>
                  </th>
                  <th class="py-3 pl-4 font-semibold text-subtext-light dark:text-subtext-dark text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="bookings-detailed-body">
                <!-- populated by JS (same data as bookings-table-body) -->
              </tbody>
            </table>


            <div class="mt-6 flex justify-between items-center">
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Showing recent bookings</p>
              <div class="flex items-center gap-2">
                <button class="px-3 py-1 rounded-md bg-primary/20 text-primary">Refresh</button>
                
              </div>
            </div>
          </div>
        </div>
        
      </section>

      <!-- 4. Analytics (charts) -->
      <section id="analytics" class="page-section hidden">
        <div class="mb-8">
          <div class="p-6 rounded-xl glass-light dark:glass-dark">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Sign-up Statistics</h3>
              <div class="flex items-center gap-4">
                <div class="relative">
                  <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg">calendar_today</span>
                  <input class="bg-white/50 dark:bg-card-dark/80 border border-gray-200/90 dark:border-gray-700/60 rounded-lg pl-10 pr-4 py-2 w-64" placeholder="Select Date Range" type="text" />
                </div>
                <div class="relative">
                  <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg">category</span>
                  <select class="bg-white/50 dark:bg-card-dark/80 border border-gray-200/90 dark:border-gray-700/60 rounded-lg pl-10 pr-4 py-2 w-56 appearance-none">
                    <option>All Membership Types</option>
                    <option>Basic</option>
                    <option>Premium</option>
                    <option>VIP</option>
                  </select>
                  <span class="material-icons absolute right-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg pointer-events-none">expand_more</span>
                </div>
                <button id="export-signups" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg shadow-md hover:bg-orange-600 transition-colors">
                  <span class="material-icons">download</span>
                  Export
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Total Sign-ups</h4>
                  <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <span class="material-icons text-blue-500">person_add</span>
                  </div>
                </div>
                <p id="total-signups" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
                <p class="text-sm text-green-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_upward</span> 0% vs last month</p>
              </div>

              <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-subtext-light dark:text-subtext-dark font-medium">New Trials</h4>
                  <div class="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                    <span class="material-icons text-purple-500">star_outline</span>
                  </div>
                </div>
                <p id="new-trials" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
                <p class="text-sm text-red-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_downward</span> 0% vs last month</p>
              </div>

              <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Conversion Rate</h4>
                  <div class="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                    <span class="material-icons text-green-500">trending_up</span>
                  </div>
                </div>
                <p id="conversion-rate" class="text-3xl font-bold text-text-light dark:text-text-dark">0%</p>
                <p class="text-sm text-green-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_upward</span> 0% vs last month</p>
              </div>

              <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Avg. Age</h4>
                  <div class="p-2 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                    <span class="material-icons text-yellow-500">cake</span>
                  </div>
                </div>
                <p id="avg-age" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark mt-1">—</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
          <div class="lg:col-span-3 p-8 rounded-xl glass-light dark:glass-dark">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Sign-up Trend</h3>
              <div class="flex gap-2 rounded-lg p-1 bg-gray-200/80 dark:bg-card-dark/90">
                <button id="signups-monthly" class="px-3 py-1 text-sm font-medium rounded-md text-white bg-primary shadow">Monthly</button>
                <button id="signups-weekly" class="px-3 py-1 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark">Weekly</button>
                <button id="signups-daily" class="px-3 py-1 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark">Daily</button>
              </div>
            </div>
            <div class="h-80"><canvas id="signupsChart"></canvas></div>
          </div>

          <div class="lg:col-span-2 p-8 rounded-xl glass-light dark:glass-dark">
            <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Sign-ups by Plan</h3>
            <div class="h-80"><canvas id="signupsSourceChart"></canvas></div>
          </div>
        </div>
      </section>

      <!-- 6. Attendance -->
      <section id="attendance" class="page-section hidden">
        <div class="p-6 rounded-xl glass-light dark:glass-dark">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Attendance Statistics</h3>
            <div class="flex items-center gap-4">
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg">calendar_today</span>
                <input class="bg-white/50 dark:bg-card-dark/80 border border-gray-200/90 dark:border-gray-700/60 rounded-lg pl-10 pr-4 py-2 w-64" placeholder="Select Date Range" type="text" />
              </div>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg">fitness_center</span>
                <select class="bg-white/50 dark:bg-card-dark/80 border border-gray-200/90 dark:border-gray-700/60 rounded-lg pl-10 pr-4 py-2 w-56 appearance-none">
                  <option>All Class Types</option>
                  <option>Yoga</option>
                  <option>HIIT</option>
                  <option>Cycling</option>
                  <option>Zumba</option>
                </select>
                <span class="material-icons absolute right-3 top-1/2 -translate-y-1/2 text-subtext-dark text-lg pointer-events-none">expand_more</span>
              </div>
              <button id="export-attendance" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg shadow-md hover:bg-orange-600 transition-colors">
                <span class="material-icons text-lg">download</span>
                Export
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
              <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Total Attendance</h4>
              <p id="total-attendance" class="text-3xl font-bold text-text-light dark:text-text-dark">0</p>
              <p class="text-sm text-green-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_upward</span> 0% vs last month</p>
            </div>

            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
              <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Check-in Rate</h4>
              <p id="checkin-rate" class="text-3xl font-bold text-text-light dark:text-text-dark">0%</p>
              <p class="text-sm text-green-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_upward</span> 0% vs last month</p>
            </div>

            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
              <h4 class="text-subtext-light dark:text-subtext-dark font-medium">No-show Rate</h4>
              <p id="no_show_rate" class="text-3xl font-bold text-text-light dark:text-text-dark">0%</p>
              <p class="text-sm text-red-500 flex items-center mt-1"><span class="material-icons text-base mr-1">arrow_downward</span> 0% vs last month</p>
            </div>

            <div class="bg-card-light/60 dark:bg-card-dark/70 p-6 rounded-lg">
              <h4 class="text-subtext-light dark:text-subtext-dark font-medium">Peak Hour</h4>
              <p id="peak-hour" class="text-3xl font-bold text-text-light dark:text-text-dark">—</p>
              <p class="text-sm text-subtext-light dark:text-subtext-dark mt-1">Most popular time</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
          <div class="lg:col-span-3 p-8 rounded-xl glass-light dark:glass-dark">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Attendance Trend</h3>
              <div class="flex gap-2 rounded-lg p-1 bg-gray-200/80 dark:bg-card-dark/90">
                <button id="attendance-monthly" class="px-3 py-1 text-sm font-medium rounded-md text-white bg-primary shadow">Monthly</button>
                <button id="attendance-weekly" class="px-3 py-1 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark">Weekly</button>
                <button id="attendance-daily" class="px-3 py-1 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark">Daily</button>
              </div>
            </div>
            <div class="h-80"><canvas id="attendanceChart"></canvas></div>
          </div>

          <div class="lg:col-span-2 p-8 rounded-xl glass-light dark:glass-dark">
            <h3 class="text-xl font-semibold mb-6 text-text-light dark:text-text-dark">Attendance by Class</h3>
            <div class="h-80"><canvas id="attendanceSourceChart"></canvas></div>
          </div>
        </div>
      </section>

   

      <!-- 7. Billing -->
      <section id="billing" class="page-section hidden">
        <div class="p-8 rounded-xl glass-light dark:glass-dark">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Invoices</h3>
            <div class="flex items-center gap-4">
              <div class="relative">
                <input class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0" placeholder="Search invoices..." type="text" />
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
              </div>
              <button class="px-4 py-2 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark hover:bg-gray-200/70 dark:hover:bg-gray-700/50 flex items-center gap-2">
                <span class="material-icons text-lg">filter_list</span>
                Filter
              </button>
              <button id="create-invoice" class="px-4 py-2 text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 flex items-center gap-2">
                <span class="material-icons text-lg">add</span>
                Create Invoice
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="border-b border-gray-300/50 dark:border-gray-700/50">
                <tr>
                  <th class="py-3 pr-4 font-semibold text-subtext-light dark:text-subtext-dark">Customer</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Plans</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Amount</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Payment</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Status</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Date</th>
                  <th class="py-3 pl-4 font-semibold text-subtext-light dark:text-subtext-dark text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="billing-table-body">
                <!-- populated by JS -->
              </tbody>
            </table>

            <div class="mt-6 flex justify-between items-center">
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Showing recent invoices</p>
              <div class="flex items-center gap-2">
                <button class="px-3 py-1 rounded-md text-subtext-light dark:text-subtext-dark bg-gray-200/70 dark:bg-gray-700/50 hover:bg-gray-300/80 dark:hover:bg-gray-600/60">Next</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 8. Chat -->
      <section id="chat" class="page-section hidden">
        <div class="h-[calc(100vh-12rem)] flex gap-6">
          <div class="w-1/3 flex flex-col rounded-xl glass-light dark:glass-dark">
            <div class="p-4 border-b border-gray-200/50 dark:border-gray-800/60">
              <h3 class="text-lg font-semibold text-text-light dark:text-text-dark mb-3">Conversations</h3>
              <div class="relative" x-data>
                <input class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0" placeholder="Search chats..." type="text" />
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
              </div>
            </div>
            <div id="chat-users-list" class="flex-grow overflow-y-auto scrollbar-thin">
              <div class="p-2 space-y-1" id="conversations-list">
                <!-- conversations populated by JS or static -->
              </div>
            </div>
          </div>

          <div class="w-2/3 flex flex-col rounded-xl glass-light dark:glass-dark">
            <div class="p-4 flex items-center justify-between border-b border-gray-200/50 dark:border-gray-800/60">
              <div class="flex items-center gap-4">
                <img id="chat-header-avatar" alt="User Avatar" class="w-12 h-12 rounded-full" src="img/logo.png" />
                <div>
                  <h3 id="chat-header-name" class="text-lg font-semibold text-text-light dark:text-text-dark">Chat</h3>
                  <p id="chat-header-status" class="text-sm text-subtext-light dark:text-subtext-dark">Select a conversation</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/50"><span class="material-icons text-xl">phone</span></button>
                <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/50"><span class="material-icons text-xl">videocam</span></button>
                <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/50"><span class="material-icons text-xl">more_vert</span></button>
              </div>
            </div>

            <div class="flex-grow p-6 overflow-y-auto scrollbar-thin space-y-6" id="chat-messages-container">
              <!-- messages -->
            </div>

            <div class="p-4 border-t border-gray-200/50 dark:border-gray-800/60">
              <div class="flex items-center gap-4">
                <button class="text-subtext-light dark:text-subtext-dark hover:text-primary dark:hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/50"><span class="material-icons text-2xl">sentiment_satisfied_alt</span></button>
                <input id="chat-input" class="flex-grow w-full px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0 placeholder:text-subtext-light dark:placeholder:text-subtext-dark" placeholder="Type a message..." type="text" />
                <button id="chat-send" class="px-5 py-3 text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary/90 flex items-center gap-2">
                  <span class="material-icons text-xl">send</span>
                </button>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 9. Notifications -->
      <section id="notifications" class="page-section hidden">
        <div class="p-6 rounded-xl glass-light dark:glass-dark">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">All Notifications</h3>
            <div class="flex items-center gap-4">
              <div class="relative">
                <select id="notifications-filter" class="pl-4 pr-10 py-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent text-sm">
                  <option>All Types</option>
                  <option>New Sign-ups</option>
                  <option>Booking Changes</option>
                  <option>Billing Alerts</option>
                </select>
                <span class="material-icons absolute right-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark text-lg">filter_list</span>
              </div>
              <button id="mark-all-read" class="flex items-center gap-2 text-sm text-subtext-light dark:text-subtext-dark hover:text-primary transition-colors">
                <span class="material-icons text-lg">done_all</span>
                Mark all as read
              </button>
            </div>
          </div>

          <div class="space-y-4 h-[calc(100vh-19rem)] overflow-y-auto scrollbar-thin pr-2" id="notifications-list-container">
            <!-- notifications populated by JS -->
          </div>
        </div>
      </section>

      <!-- 10. Settings -->
      <section id="settings" class="page-section hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2 space-y-8">
            <div class="p-6 rounded-xl glass-light dark:glass-dark">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark mb-6">Notification Types</h3>
              <div class="space-y-6">
                <!-- Example notification types controls (UI only; persistence can be added) -->
                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-medium text-text-light dark:text-text-dark">New Customer Sign-ups</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Notify when a new customer registers.</p>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">smartphone</span>
                      <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">email</span>
                      <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
                    </div>
                  </div>
                </div>

                <div class="border-t border-gray-200/50 dark:border-gray-700/50"></div>

                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-medium text-text-light dark:text-text-dark">Booking Changes</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Notify for new, rescheduled, or canceled bookings.</p>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">smartphone</span>
                      <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">email</span>
                      <label class="switch"><input type="checkbox" /><span class="slider"></span></label>
                    </div>
                  </div>
                </div>

                <div class="border-t border-gray-200/50 dark:border-gray-700/50"></div>

                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-medium text-text-light dark:text-text-dark">Billing Alerts</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Get alerts for failed payments or expiring cards.</p>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">smartphone</span>
                      <label class="switch"><input type="checkbox" /><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">email</span>
                      <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
                    </div>
                  </div>
                </div>

                <div class="border-t border-gray-200/50 dark:border-gray-700/50"></div>

                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-medium text-text-light dark:text-text-dark">New Messages</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Notify when a new message is received in chat.</p>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">smartphone</span>
                      <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-lg text-subtext-dark">email</span>
                      <label class="switch"><input type="checkbox" /><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-6 rounded-xl glass-light dark:glass-dark">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark mb-4">Delivery Methods</h3>
              <div class="flex items-center justify-between py-2">
                <div class="flex items-center gap-3">
                  <span class="material-icons text-primary text-2xl">smartphone</span>
                  <p class="font-medium">In-App Notifications</p>
                </div>
                <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
              </div>
              <div class="border-t border-gray-200/50 dark:border-gray-700/50 my-2"></div>
              <div class="flex items-center justify-between py-2">
                <div class="flex items-center gap-3">
                  <span class="material-icons text-primary text-2xl">email</span>
                  <p class="font-medium">Email Notifications</p>
                </div>
                <label class="switch"><input checked type="checkbox" /><span class="slider"></span></label>
              </div>
            </div>

            <div class="p-6 rounded-xl glass-light dark:glass-dark">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark mb-4">Quiet Hours</h3>
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                  <span class="material-icons text-primary text-2xl">notifications_off</span>
                  <p class="font-medium">Mute notifications</p>
                </div>
                <label class="switch"><input id="quiet-hours-toggle" type="checkbox" /><span class="slider"></span></label>
              </div>
              <p class="text-sm text-subtext-light dark:text-subtext-dark mb-4">Set a schedule to pause notifications and avoid interruptions.</p>
              <div class="flex items-center gap-4">
                <div class="w-full">
                  <label class="text-xs text-subtext-light dark:text-subtext-dark" for="start-time">From</label>
                  <input class="w-full p-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0 text-sm disabled:opacity-50" id="start-time" type="time" value="22:00" disabled />
                </div>
                <div class="w-full">
                  <label class="text-xs text-subtext-light dark:text-subtext-dark" for="end-time">To</label>
                  <input class="w-full p-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0 text-sm disabled:opacity-50" id="end-time" type="time" value="08:00" disabled />
                </div>
              </div>
            </div>

            <div class="flex justify-end">
              <button id="save-settings" class="bg-primary text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:bg-orange-600 transition-all duration-200">Save Changes</button>
            </div>
          </div>

          <div class="space-y-8">
            <div class="p-6 rounded-xl glass-light dark:glass-dark">
              <h3 class="text-xl font-semibold text-text-light dark:text-text-dark mb-4">Account</h3>
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Manage account-level settings here.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- 11. Login History -->
      <section id="login-history" class="page-section hidden">
        <div class="p-8 rounded-xl glass-light dark:glass-dark">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-text-light dark:text-text-dark">Login Log</h3>
            <div class="flex items-center gap-4">
              <div class="relative">
                <input class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800/60 border border-transparent focus:border-primary focus:ring-0" placeholder="Search by user or IP..." type="text" />
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
              </div>
              <button class="px-4 py-2 text-sm font-medium rounded-md text-subtext-light dark:text-subtext-dark hover:bg-gray-200/70 dark:hover:bg-gray-700/50 flex items-center gap-2">
                <span class="material-icons text-lg">filter_list</span>
                Filter
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="border-b border-gray-300/50 dark:border-gray-700/50">
                <tr>
                  <th class="py-3 pr-4 font-semibold text-subtext-light dark:text-subtext-dark">User</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">Login Time</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">IP Address</th>
                  <th class="py-3 px-4 font-semibold text-subtext-light dark:text-subtext-dark">User Agent</th>
                </tr>
              </thead>
<tbody id="login-history-body">
  <!-- populated by JS -->
</tbody>

<!-- Add modal for login history details -->
<div id="loginModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black/70 p-8 max-w-lg mx-auto">
  <div class="bg-gray-900 p-6 rounded-lg max-w-full max-h-full overflow-auto">
    <h3 class="text-xl font-bold mb-4 text-white">Login Details</h3>
    <p><strong>User:</strong> <span id="login-user"></span></p>
    <p><strong>Time:</strong> <span id="login-time"></span></p>
    <p><strong>IP:</strong> <span id="login-ip"></span></p>
    <p><strong>User Agent:</strong> <span id="login-agent"></span></p>
    <button onclick="closeLoginModal()" class="mt-4 px-4 py-2 bg-primary text-white rounded">Close</button>
  </div>
</div>

 <script>
  // Add event delegation to open login modal on login history row click
  document.addEventListener('click', function(e) {
    const target = e.target.closest('tbody#login-history-body tr');
    if (target) {
      const index = Array.from(target.parentNode.children).indexOf(target);
      const log = appData.login_history[index];
      if (log) {
        document.getElementById('login-user').textContent = log.fullname || 'Unknown';
        document.getElementById('login-time').textContent = new Date(log.login_time).toLocaleString();
        document.getElementById('login-ip').textContent = log.ip_address || 'Unknown';
        document.getElementById('login-agent').textContent = log.user_agent || 'Unknown';
        document.getElementById('loginModal').style.display = 'flex';
      }
    }
  });

  function closeLoginModal() {
    document.getElementById('loginModal').style.display = 'none';
  }

  // Extend postAction for notification mark read
  async function markNotificationRead(id, isRead) {
    return await postAction('mark_notification_read', { id, is_read: isRead ? 1 : 0 });
  }

  // Extend postAction for clear all notifications
  async function clearAllNotifications() {
    return await postAction('clear_notifications');
  }
</script>


<script>
    // Store CSRF for POST calls
    const ADM_DASHBOARD_CSRF = <?php echo json_encode($csrf); ?>;
  </script>

  <!-- Alpine.js (deferred) -->
  <script defer src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>

  <!-- Unified JS: navigation, theme, data fetch, actions, charts, modal -->
  <script>
    (function () {
      // Elements
      const sectionLinks = document.querySelectorAll(".section-link");
      const sections = document.querySelectorAll(".page-section");
      const pageTitle = document.getElementById("page-title");
      const pageSub = document.getElementById("page-sub");
      const themeToggle = document.getElementById("theme-toggle");
      const html = document.documentElement;
      const sidebarToggleClose = document.getElementById("sidebar-toggle-close");
      const sidebar = document.getElementById("sidebar");

      // State
      let appData = {};
      let activeChatUserId = null;
      let editingBooking = null; // null for new, or booking object for edit

      // Default active section
      let activeSectionId = "dashboard";

      // Chart placeholders
      let signupsChartInstance = null;
      let signupsSourceChartInstance = null;
      let attendanceChartInstance = null;
      let attendanceSourceChartInstance = null;

      const sectionMeta = {
        dashboard: { title: "Welcome, Admin!", sub: "Overview and quick stats" },
        customers: { title: "Customers", sub: "View and manage all customers" },
        bookings: { title: "Bookings", sub: "Manage all customer bookings" },
        billing: { title: "Billing", sub: "View and manage all payments" },
        contact: { title: "Contact Messages", sub: "View messages from the website contact form" },
        notifications: { title: "Notifications", sub: "View all system alerts and messages" },
        analytics: { title: "Analytics", sub: "View site-wide analytics and sign-up trends" },
        attendance: { title: "Booking Attendance", sub: "Trends and statistics for class and gym bookings" },
        chat: { title: "Chat", sub: "Communicate with customers in real-time" },
        settings: { title: "Settings", sub: "Manage your notification preferences" },
        "login-history": { title: "Login History", sub: "Review all user login attempts to the system." }
      };

      function updateActiveNav(id) {
        const nav = document.getElementById('sidebar-nav');
        nav.querySelectorAll('.section-link').forEach(link => {
          if (link.dataset.section === id) {
            link.classList.add('text-white', 'font-medium', 'shadow-lg', 'bg-gradient-to-r', 'from-primary', 'to-orange-400');
            link.classList.remove('text-subtext-light', 'dark:text-subtext-dark', 'hover:bg-gray-100', 'dark:hover:bg-gray-800/60');
          } else {
            link.classList.remove('text-white', 'font-medium', 'shadow-lg', 'bg-gradient-to-r', 'from-primary', 'to-orange-400');
            link.classList.add('text-subtext-light', 'dark:text-subtext-dark', 'hover:bg-gray-100', 'dark:hover:bg-gray-800/60');
          }
        });
      }

      function showSection(id) {
        if (!sectionMeta[id]) return;

        sections.forEach(s => s.id === id ? s.classList.remove("hidden") : s.classList.add("hidden"));
        activeSectionId = id;

        // Update title and sub-title
        const meta = sectionMeta[id] || { title: "Verso", sub: "" };
        pageTitle.textContent = meta.title;
        pageSub.textContent = meta.sub;

        updateActiveNav(id);

        // Render charts conditionally
        if (id === "analytics") renderSignupsCharts();
        if (id === "attendance") renderAttendanceCharts();
        window.scrollTo({ top: 0, behavior: "smooth" });
      }

      sectionLinks.forEach(btn => {
        btn.addEventListener("click", () => {
          const target = btn.getAttribute("data-section");
          if (target) showSection(target);
        });
      });

      // Sidebar collapse toggle
      sidebarToggleClose.addEventListener("click", () => {
        const collapsed = sidebar.classList.contains("w-20");
        sidebar.classList.toggle("w-64", collapsed);
        sidebar.classList.toggle("w-20", !collapsed);
        const main = document.getElementById("main-wrapper");
        main.classList.toggle("ml-64", collapsed);
        main.classList.toggle("ml-20", !collapsed);
      });

      // Theme init
      function isDarkMode() {
        return localStorage.getItem("theme") === "dark" || (!("theme" in localStorage) && window.matchMedia("(prefers-color-scheme: dark)").matches);
      }
      if (isDarkMode()) {
        html.classList.add("dark");
        if (themeToggle) themeToggle.checked = true;
      } else {
        html.classList.remove("dark");
        if (themeToggle) themeToggle.checked = false;
      }
      if (themeToggle) {
        themeToggle.addEventListener("change", () => {
          html.classList.toggle("dark");
          localStorage.setItem("theme", html.classList.contains("dark") ? "dark" : "light");
          if (activeSectionId === "analytics") renderSignupsCharts();
          if (activeSectionId === "attendance") renderAttendanceCharts();
        });
      }

      // Utility: escape HTML
      function escapeHtml(s) {
        if (!s && s !== 0) return '';
        return String(s).replace(/[&<>"']/g, function (m) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
      }

      // Map payment status to badge class
      function statusClass(status) {
        if (!status) return 'text-subtext-light';
        status = status.toLowerCase();
        if (status.includes('paid') || status.includes('completed')) return 'text-green-800 bg-green-200 dark:bg-green-900/50 dark:text-green-300';
        if (status.includes('pending')) return 'text-yellow-800 bg-yellow-200 dark:bg-yellow-900/50 dark:text-yellow-300';
        if (status.includes('overdue') || status.includes('failed')) return 'text-red-800 bg-red-200 dark:bg-red-900/50 dark:text-red-300';
        return 'text-subtext-light';
      }

      // Compute simple booking analytics
      function computeBookingAnalytics(bookings) {
        const counts = { Yoga:0, HIIT:0, Pilates:0, Weight:0, Other:0 };
        bookings.forEach(b => {
          const t = (b.title || '').toLowerCase();
          if (t.includes('yoga')) counts.Yoga++;
          else if (t.includes('hiit')) counts.HIIT++;
          else if (t.includes('pilates')) counts.Pilates++;
          else if (t.includes('weight') || t.includes('training')) counts.Weight++;
          else counts.Other++;
        });
        const total = Math.max(1, bookings.length);
        document.getElementById('percent-yoga').textContent = Math.round((counts.Yoga / total) * 100) + '%';
        document.getElementById('bar-yoga').style.width = Math.round((counts.Yoga / total) * 100) + '%';
        document.getElementById('percent-hiit').textContent = Math.round((counts.HIIT / total) * 100) + '%';
        document.getElementById('bar-hiit').style.width = Math.round((counts.HIIT / total) * 100) + '%';
        document.getElementById('percent-pilates').textContent = Math.round((counts.Pilates / total) * 100) + '%';
        document.getElementById('bar-pilates').style.width = Math.round((counts.Pilates / total) * 100) + '%';
        document.getElementById('percent-weight').textContent = Math.round((counts.Weight / total) * 100) + '%';
        document.getElementById('bar-weight').style.width = Math.round((counts.Weight / total) * 100) + '%';
      }

      // Generic POST action with CSRF (used for admin actions)
      async function postAction(action, payload = {}) {
        const form = new FormData();
        form.append('action', action);
        form.append('csrf', ADM_DASHBOARD_CSRF);
        for (const k in payload) {
          if (payload.hasOwnProperty(k)) form.append(k, payload[k]);
        }
        try {
          const res = await fetch('', { method: 'POST', body: form, credentials: 'same-origin' });
          const data = await res.json();
          if (!data || data.error) {
            alert('Action failed: ' + (data && data.error ? data.error : 'Unknown error'));
            return null;
          }
          return data;
        } catch (err) {
          console.error('Action error', err);
          alert('Action failed (see console).');
          return null;
        }
      }

      // Fetch and render dashboard data (populates all tables/cards)
      async function fetchDashboardData() {
        try {
          const res = await fetch('?action=get_dashboard_data', { credentials: 'same-origin' });
          if (!res.ok) throw new Error('Failed to fetch');
          const data = await res.json();
          if (data.error) throw new Error(data.error);
          appData = data; // Store data globally

          // Stats
          document.getElementById('total-customers').textContent = data.stats.total_users;
          document.getElementById('new-members').textContent = data.stats.new_users_this_month;
          document.getElementById('workouts-today').textContent = data.stats.total_workouts_today;

          // Update notification badges
          const unreadCount = (data.notifications || []).filter(n => !n.is_read).length;
          updateNotificationBadge(unreadCount);

          renderAll();

          // Analytics summary placeholders
          document.getElementById('total-signups').textContent = data.stats.total_users;
          document.getElementById('new-trials').textContent = data.stats.new_users_this_month;
          document.getElementById('conversion-rate').textContent = '—';
          document.getElementById('avg-age').textContent = '—';
          document.getElementById('total-attendance').textContent = data.stats.total_workouts_today;

        } catch (err) {
          console.error('Failed to load dashboard data', err);
          // silent UI-friendly message
        }
      }

      function renderAll() {
        if (!appData) return;

          // Customers table
          const customersBody = document.getElementById('customers-table-body');
          if (customersBody) {
            customersBody.innerHTML = '';
            appData.customers.forEach(c => {
              const tr = document.createElement('tr');
              tr.className = 'border-b border-gray-200/80 dark:border-gray-800/60';
              tr.innerHTML = `
                <td class="py-4 px-4"><div class="flex items-center gap-3"><img src="${escapeHtml(c.picture || 'img/logo.png')}" class="w-8 h-8 rounded-full object-cover" /> ${escapeHtml(c.fullname)}</div></td>
                <td class="py-4 px-4">${escapeHtml(c.email)}</td>
                <td class="py-4 px-4"><span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass(c.membership_status)}">${escapeHtml(c.membership_status)}</span></td>
                <td class="py-4 px-4">${new Date(c.created_at).toLocaleDateString()}</td>
                <td class="py-4 px-4 text-center"><button class="text-subtext-light dark:text-subtext-dark hover:text-primary p-1 rounded-full"><span class="material-icons">more_horiz</span></button></td>
              `;
              customersBody.appendChild(tr);
            });
          }

          // Bookings (simple and detailed)
          const bookingsBody = document.getElementById('bookings-table-body');
          const bookingsDetailed = document.getElementById('bookings-detailed-body');
          if (bookingsBody) bookingsBody.innerHTML = '';
          if (bookingsDetailed) bookingsDetailed.innerHTML = '';
          (appData.bookings || []).forEach(b => {
            const formattedDate = b.date ? new Date(b.date).toLocaleDateString() : '';
            const time = b.time ? b.time.slice(0,5) : '';
            const status = escapeHtml(b.status || 'Pending');

            if (bookingsBody) {
              const tr = document.createElement('tr');
              tr.className = 'border-b border-gray-200/80 dark:border-gray-800/60 hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition-colors duration-200';
              tr.innerHTML = `
                <td class="py-4 px-4 flex items-center gap-3"><img src="${escapeHtml(b.picture || 'img/logo.png')}" class="w-8 h-8 rounded-full object-cover" /><div>${escapeHtml(b.fullname)}</div></td>
                <td class="py-4 px-4">${escapeHtml(b.title)}</td>
                <td class="py-4 px-4">${formattedDate} ${time ? '<div class="text-sm text-subtext-light">' + time + '</div>' : ''}</td>
                <td class="py-4 px-4 text-center">
                  <div class="flex gap-2 justify-center">
                    <button class="approve-booking p-2 rounded-full hover:bg-green-100 dark:hover:bg-green-900/50 text-green-500" data-id="${b.id}"><span class="material-icons">check</span></button>
                    <button class="decline-booking p-2 rounded-full hover:bg-red-100 dark:hover:bg-red-900/50 text-red-500" data-id="${b.id}"><span class="material-icons">close</span></button>
                  </div>
                  <div class="mt-2 text-xs">${status}</div>
                </td>
              `;
              bookingsBody.appendChild(tr);
            }

            if (bookingsDetailed) {
              const tr2 = document.createElement('tr');
              tr2.className = 'border-b border-gray-200/50 dark:border-gray-800/60 hover:bg-gray-100/50 dark:hover:bg-gray-800/30 transition-colors';
              tr2.innerHTML = `
                <td class="py-4 pr-4">
                  <div class="flex items-center gap-3"><img src="${escapeHtml(b.picture || 'img/logo.png')}" class="w-10 h-10 rounded-full object-cover" />
                    <div>
                      <p class="font-semibold">${escapeHtml(b.fullname)}</p>
                      <p class="text-sm text-subtext-light">${new Date(b.date).toLocaleDateString()} ${time}</p>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">${escapeHtml(b.title)}</td>
                <td class="py-4 px-4">${formattedDate}<div class="text-sm text-subtext-light">${time}</div></td>
                <td class="py-4 px-4"><span class="px-3 py-1 text-xs font-semibold ${statusClass(b.status)}">${escapeHtml(b.status)}</span></td>
                <td class="py-4 pl-4 text-right">
                  <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors edit-booking" data-id="${b.id}"><span class="material-icons text-subtext-light dark:text-subtext-dark">edit</span></button>
                  <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors delete-booking" data-id="${b.id}"><span class="material-icons text-subtext-light dark:text-subtext-dark">delete_outline</span></button>
                </td>
              `;
              bookingsDetailed.appendChild(tr2);
            }
          });

          // Billing / Payments
          const billingBody = document.getElementById('billing-table-body');
          if (billingBody) billingBody.innerHTML = '';
          (appData.payments || []).forEach(p => {
            if (!billingBody) return;
            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-200/50 dark:border-gray-800/60 hover:bg-gray-50/50 dark:hover:bg-gray-800/20';
            tr.innerHTML = `
              <td class="py-4 pr-4">
                <div class="flex items-center gap-3">
                  <img src="${escapeHtml(p.picture || 'img/logo.png')}" class="w-10 h-10 rounded-full object-cover" />
                  <div>
                    <p class="font-semibold">${escapeHtml(p.fullname)}</p>
                    <p class="text-sm text-subtext-light">${new Date(p.created_at).toLocaleString()}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 px-4">${escapeHtml(p.plan)}</td>
              <td class="py-4 px-4 font-semibold">₱${Number(p.amount).toFixed(2)}</td>
              <td class="py-4 px-4">${escapeHtml(p.payment_method)}</td>
              <td class="py-4 px-4"><span class="px-3 py-1 text-xs font-semibold ${statusClass(p.payment_status)}">${escapeHtml(p.payment_status)}</span></td>
              <td class="py-4 px-4">${new Date(p.created_at).toLocaleString()}</td>
              <td class="py-4 pl-4 text-right">
                <button class="mark-paid text-subtext-light hover:text-primary p-2 rounded-full" data-id="${p.id}"><span class="material-icons">receipt</span></button>
              </td>
            `;
            billingBody.appendChild(tr);
          });

          // Contact Messages
          const contactBody = document.getElementById('contact-messages-body');
          if (contactBody) contactBody.innerHTML = '';
          (appData.contacts || []).forEach(m => {
            if (!contactBody) return;
            const tr = document.createElement('tr');
            tr.className = 'border-b border-gray-200/50 dark:border-gray-800/60';
            tr.innerHTML = `
              <td class="py-4 pr-4">
                <p class="font-semibold">${escapeHtml(m.name)}</p>
                <p class="text-sm text-subtext-light">${escapeHtml(m.email)}</p>
              </td>
              <td class="py-4 px-4 text-sm text-subtext-light dark:text-subtext-dark">${escapeHtml(m.message.substring(0, 70))}${m.message.length > 70 ? '...' : ''}</td>
              <td class="py-4 px-4">${new Date(m.created_at).toLocaleString()}</td>
              <td class="py-4 px-4"><span class="px-3 py-1 text-xs font-semibold ${m.is_read ? statusClass('read') : statusClass('pending')}">${m.is_read ? 'Read' : 'Unread'}</span></td>
              <td class="py-4 pl-4 text-right">
                <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors toggle-contact-read" data-id="${m.id}" data-read="${m.is_read ? 1:0}"><span class="material-icons text-subtext-light dark:text-subtext-dark">${m.is_read ? 'drafts' : 'mark_email_read'}</span></button>
              </td>
            `;
            contactBody.appendChild(tr);
          });

          // Login History
          const loginHistoryBody = document.getElementById('login-history-body');
          if (loginHistoryBody) {
              loginHistoryBody.innerHTML = '';
              (appData.login_history || []).forEach(log => {
                  const tr = document.createElement('tr');
                  tr.className = 'border-b border-gray-200/50 dark:border-gray-800/60';
                  tr.innerHTML = `
                      <td class="py-4 pr-4">
                          <div class="flex items-center gap-3">
                              <img src="${escapeHtml(log.picture || 'img/logo.png')}" class="w-10 h-10 rounded-full object-cover" />
                              <div>
                                  <p class="font-semibold">${escapeHtml(log.fullname)}</p>
                                  <p class="text-sm text-subtext-light">User ID: ${log.user_id}</p>
                              </div>
                          </div>
                      </td>
                      <td class="py-4 px-4">${new Date(log.login_time).toLocaleString()}</td>
                      <td class="py-4 px-4">${escapeHtml(log.ip_address || 'N/A')}</td>
                      <td class="py-4 px-4 text-sm text-subtext-light dark:text-subtext-dark">${escapeHtml(log.user_agent.substring(0, 50))}${log.user_agent.length > 50 ? '...' : ''}</td>
                  `;
                  loginHistoryBody.appendChild(tr);
              });
          }

          // Notifications
          const notifContainer = document.getElementById('notifications-list-container');
          if (notifContainer) notifContainer.innerHTML = '';
          (appData.notifications || []).forEach(n => {
            const item = document.createElement('div');
            const isReadClass = n.is_read ? 'bg-card-light/30 dark:bg-card-dark/30' : 'bg-primary/10 dark:bg-primary/20 border-l-4 border-primary';
            item.className = `flex items-start gap-4 p-4 rounded-lg ${isReadClass}`;
            item.innerHTML = `
              <div class="w-10 h-10 rounded-full bg-primary/20 text-primary flex items-center justify-center shrink-0">
                <span class="material-icons">${escapeHtml(n.icon || 'notifications')}</span>
              </div>
              <div class="flex-grow">
                <p class="font-medium text-text-light dark:text-text-dark">${escapeHtml(n.type)}</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">${escapeHtml(n.message)}</p>
                <span class="text-xs text-subtext-light dark:text-subtext-dark mt-1">${new Date(n.created_at).toLocaleString()}</span>
              </div>
              <div class="flex items-center gap-2">
                <button class="p-2 rounded-full hover:bg-gray-500/10 text-subtext-light dark:text-subtext-dark toggle-notif-read" data-id="${n.id}" data-read="${n.is_read?1:0}"><span class="material-icons text-xl">${n.is_read ? 'mark_email_unread' : 'mark_email_read'}</span></button>
              </div>
            `;
            notifContainer.appendChild(item);
          });

          // Chat Users List
          const chatUsersContainer = document.getElementById('chat-users-list');
          if (chatUsersContainer) chatUsersContainer.innerHTML = '';
          (appData.chat_users || []).forEach(u => {
            const item = document.createElement('div');
            const isActive = u.id === activeChatUserId;
            const activeClass = isActive ? 'bg-primary/10 dark:bg-primary/20' : 'hover:bg-gray-100/50 dark:hover:bg-gray-800/50';
            item.className = `flex items-center gap-4 p-3 rounded-lg cursor-pointer transition-colors ${activeClass}`;
            item.dataset.userId = u.id;
            item.innerHTML = `
              <img src="${escapeHtml(u.picture || 'img/logo.png')}" class="w-12 h-12 rounded-full object-cover" />
              <div class="flex-grow overflow-hidden">
                <div class="flex justify-between items-center">
                  <p class="font-semibold truncate">${escapeHtml(u.fullname)}</p>
                  <p class="text-xs text-subtext-light dark:text-subtext-dark shrink-0">${u.last_message_time ? new Date(u.last_message_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : ''}</p>
                </div>
                <p class="text-sm text-subtext-light dark:text-subtext-dark truncate">${escapeHtml(u.last_message || 'No messages yet')}</p>
              </div>
            `;
            chatUsersContainer.appendChild(item);
          });

          // Chat Messages
          const chatMessagesContainer = document.getElementById('chat-messages-container');
          if (chatMessagesContainer) chatMessagesContainer.innerHTML = '';
          if (activeChatUserId && appData.chat_messages) {
            appData.chat_messages.forEach(m => {
              const isFromAdmin = m.from_user_id == <?php echo $_SESSION['user_id']; ?>;
              const messageDiv = document.createElement('div');
              if (isFromAdmin) {
                  messageDiv.className = 'flex items-start gap-4 flex-row-reverse';
                  messageDiv.innerHTML = `<img class="w-10 h-10 rounded-full" src="<?php echo e($_SESSION['user_picture'] ?? 'img/logo.png'); ?>" alt="admin" /><div class="flex flex-col items-end max-w-lg"><div class="bg-primary text-white p-3 rounded-lg rounded-br-none"><p class="text-sm">${escapeHtml(m.message)}</p></div><span class="text-xs text-subtext-light dark:text-subtext-dark mt-1">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></div>`;
              } else {
                  const user = appData.chat_users.find(u => u.id === m.from_user_id);
                  messageDiv.className = 'flex items-start gap-4';
                  messageDiv.innerHTML = `<img class="w-10 h-10 rounded-full" src="${escapeHtml(user?.picture || 'img/logo.png')}" alt="user" /><div class="flex flex-col items-start max-w-lg"><div class="bg-card-light dark:bg-card-dark p-3 rounded-lg rounded-bl-none"><p class="text-sm">${escapeHtml(m.message)}</p></div><span class="text-xs text-subtext-light dark:text-subtext-dark mt-1">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></div>`;
              }
              chatMessagesContainer.appendChild(messageDiv);
            });
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
          } else {
            chatMessagesContainer.innerHTML = '<div class="text-center text-subtext-light dark:text-subtext-dark p-8">Select a conversation to start chatting.</div>';
          }

          // Booking analytics
          computeBookingAnalytics(appData.bookings || []);
      }

      function updateNotificationBadge(count) {
        const headerBadge = document.getElementById('header-notif-badge');
        const sidebarBadge = document.getElementById('sidebar-notif-badge');
        [headerBadge, sidebarBadge].forEach(badge => {
          if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
          } else {
            badge.classList.add('hidden');
          }
        });
      }

     

      // Event delegation for actions (approve/decline booking, mark paid, mark contact read)
      document.addEventListener('click', async (e) => {
        // Approve booking
        if (e.target.closest('.approve-booking')) {
          const btn = e.target.closest('.approve-booking');
          const id = btn.getAttribute('data-id');
          if (!confirm('Approve this booking?')) return;
          await postAction('update_booking_status', { id, status: 'Confirmed' });
          await fetchDashboardData();
        }

        // Decline booking
        if (e.target.closest('.decline-booking')) {
          const btn = e.target.closest('.decline-booking');
          const id = btn.getAttribute('data-id');
          if (!confirm('Decline this booking?')) return;
          await postAction('update_booking_status', { id, status: 'Declined' });
          await fetchDashboardData();
        }

        // Mark payment as paid (toggle to Paid)
        if (e.target.closest('.mark-paid')) {
          const btn = e.target.closest('.mark-paid');
          const id = btn.getAttribute('data-id');
          if (!confirm('Mark this invoice as Paid?')) return;
          await postAction('update_payment_status', { id, status: 'Paid' });
          await fetchDashboardData();
        }

        // Mark contact read/unread toggle
        if (e.target.closest('.toggle-contact-read')) {
            const btn = e.target.closest('.toggle-contact-read');
            const id = btn.dataset.id;
            const newRead = parseInt(btn.dataset.read, 10) ? 0 : 1;
            await postAction('mark_contact_read', { id, is_read: newRead });
            await fetchDashboardData();
        }

        // Mark notification read/unread toggle
        if (e.target.closest('.toggle-notif-read')) {
          const btn = e.target.closest('.toggle-notif-read');
          const id = btn.getAttribute('data-id');
          const cur = parseInt(btn.getAttribute('data-read') || '0', 10);
          const newRead = cur ? 0 : 1;
          // This needs a backend endpoint. Assuming one exists at `mark_notification_read`
          // await postAction('mark_notification_read', { id, is_read: newRead });
          // For demo, we just update UI
          const notif = appData.notifications.find(n => n.id == id);
          if (notif) notif.is_read = newRead;
          renderAll();
        }

        // Select chat user
        if (e.target.closest('#chat-users-list > div')) {
            const userId = e.target.closest('#chat-users-list > div').dataset.userId;
            await selectChatUser(userId);
        }

        // Edit booking
        if (e.target.closest('.edit-booking')) {
            const btn = e.target.closest('.edit-booking');
            const id = btn.dataset.id;
            const booking = appData.bookings.find(b => b.id == id);
            if (booking) {
                openBookingModal(booking);
            }
        }

        // Delete booking
        if (e.target.closest('.delete-booking')) {
            const btn = e.target.closest('.delete-booking');
            const id = btn.dataset.id;
            if (confirm('Are you sure you want to delete this booking? This cannot be undone.')) {
                const result = await postAction('delete_booking', { id });
                if (result && result.success) {
                    alert('Booking deleted successfully.');
                    await fetchDashboardData();
                } else {
                    alert('Failed to delete booking.');
                }
            }
        }
      });

      // Chart rendering functions (from provided UI)
      function destroyIfExists(chart) {
        if (chart) {
          try { chart.destroy(); } catch (e) { /* ignore */ }
        }
      }

      function renderSignupsCharts() {
        const isDark = isDarkMode();
        const gridColor = isDark ? 'rgba(148,163,184,0.2)' : 'rgba(203,213,225,0.5)';
        const textColor = isDark ? '#E2E8F0' : '#1F2937';
        const tooltipBg = isDark ? '#1E293B' : '#FFFFFF';
        const tooltipColor = isDark ? '#E2E8F0' : '#1F2937';

        destroyIfExists(signupsChartInstance);
        destroyIfExists(signupsSourceChartInstance);

        const signupsCtx = document.getElementById('signupsChart');
        if (signupsCtx) {
          signupsChartInstance = new Chart(signupsCtx.getContext('2d'), {
            type: 'line',
            data: {
              labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
              datasets: [{
                label: 'Sign-ups',
                data: [80,95,110,105,120,135,130,145,150,160,155,170],
                backgroundColor: 'rgba(230,126,34,0.2)',
                borderColor: '#E67E22',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#E67E22',
                pointBorderColor: isDark ? '#0F172A' : '#F0F2F5',
                pointHoverBackgroundColor: '#FFF',
                pointHoverBorderColor: '#E67E22',
                pointRadius: 4,
                pointHoverRadius: 6,
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: tooltipBg,
                  titleColor: tooltipColor,
                  bodyColor: tooltipColor,
                  borderColor: gridColor,
                  borderWidth: 1,
                  padding: 10,
                  callbacks: {
                    label: function(context) { return `Sign-ups: ${context.parsed.y}`; }
                  }
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  grid: { color: gridColor },
                  ticks: { color: textColor }
                },
                x: {
                  grid: { display: false },
                  ticks: { color: textColor }
                }
              }
            }
          });
        }

        const signupsSourceCtx = document.getElementById('signupsSourceChart');
        if (signupsSourceCtx) {
          signupsSourceChartInstance = new Chart(signupsSourceCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
              labels: ['Basic','Premium','VIP'],
              datasets: [{
                data: [720,414,150],
                backgroundColor: ['#3B82F6','#F59E0B','#A855F7'],
                borderColor: isDark ? '#0F172A' : '#F0F2F5',
                borderWidth: 4,
                hoverOffset: 8
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              cutout: '70%',
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: { color: textColor, usePointStyle: true, boxWidth: 8, padding: 20 }
                },
                tooltip: {
                  backgroundColor: tooltipBg,
                  titleColor: tooltipColor,
                  bodyColor: tooltipColor,
                  borderColor: gridColor,
                  borderWidth: 1,
                  padding: 10,
                  callbacks: {
                    label: function(context) {
                      let label = context.label || '';
                      if (label) label += ': ';
                      if (context.parsed !== null) label += context.parsed + ' members';
                      return label;
                    }
                  }
                }
              }
            }
          });
        }
      }

      function renderAttendanceCharts() {
        const isDark = isDarkMode();
        const gridColor = isDark ? 'rgba(148,163,184,0.2)' : 'rgba(203,213,225,0.5)';
        const textColor = isDark ? '#E2E8F0' : '#1F2937';
        const tooltipBg = isDark ? '#1E293B' : '#FFFFFF';
        const tooltipColor = isDark ? '#E2E8F0' : '#1F2937';

        destroyIfExists(attendanceChartInstance);
        destroyIfExists(attendanceSourceChartInstance);

        const attendanceCtx = document.getElementById('attendanceChart');
        if (attendanceCtx) {
          attendanceChartInstance = new Chart(attendanceCtx.getContext('2d'), {
            type: 'bar',
            data: {
              labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
              datasets: [{
                label: 'Attendance',
                data: [350,410,450,420,500,550,530,580,600,650,620,680],
                backgroundColor: 'rgba(230,126,34,0.6)',
                borderColor: '#E67E22',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(230,126,34,0.8)'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: tooltipBg,
                  titleColor: tooltipColor,
                  bodyColor: tooltipColor,
                  borderColor: gridColor,
                  borderWidth: 1,
                  padding: 10,
                  callbacks: {
                    label: function(context) { return `Attendance: ${context.parsed.y}`; }
                  }
                }
              },
              scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                x: { grid: { display: false }, ticks: { color: textColor } }
              }
            }
          });
        }

        const attendanceSourceCtx = document.getElementById('attendanceSourceChart');
        if (attendanceSourceCtx) {
          attendanceSourceChartInstance = new Chart(attendanceSourceCtx.getContext('2d'), {
            type: 'polarArea',
            data: {
              labels: ['Yoga','HIIT','Cycling','Zumba','Gym'],
              datasets: [{
                data: [1200,1500,950,650,292],
                backgroundColor: [
                  'rgba(96,165,250,0.7)',
                  'rgba(245,158,11,0.7)',
                  'rgba(168,85,247,0.7)',
                  'rgba(239,68,68,0.7)',
                  'rgba(16,185,129,0.7)'
                ],
                borderColor: isDark ? '#0F172A' : '#F0F2F5',
                borderWidth: 2
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: { color: textColor, usePointStyle: true, boxWidth: 8, padding: 20 }
                },
                tooltip: {
                  backgroundColor: tooltipBg,
                  titleColor: tooltipColor,
                  bodyColor: tooltipColor,
                  borderColor: gridColor,
                  borderWidth: 1,
                  padding: 10,
                  callbacks: {
                    label: function(context) {
                      let label = context.label || '';
                      if (label) label += ': ';
                      if (context.parsed.r !== null) label += context.parsed.r + ' attendees';
                      return label;
                    }
                  }
                }
              },
              scales: {
                r: {
                  grid: { color: gridColor },
                  angleLines: { color: gridColor },
                  pointLabels: { color: textColor, font: { size: 12 } },
                  ticks: { backdropColor: 'transparent', color: textColor, z: 1 }
                }
              }
            }
          });
        }
      }

      // Quiet hours toggle enabling/disabling time inputs
      const quietToggle = document.getElementById('quiet-hours-toggle');
      const startTime = document.getElementById('start-time');
      const endTime = document.getElementById('end-time');
      if (quietToggle) {
        quietToggle.addEventListener('change', (e) => {
          const disabled = !e.target.checked;
          startTime.disabled = disabled;
          endTime.disabled = disabled;
        });
      }

      // Save Settings button feedback
      const saveBtn = document.getElementById('save-settings');
      if (saveBtn) {
        saveBtn.addEventListener('click', () => {
          saveBtn.textContent = 'Saved';
          setTimeout(() => { saveBtn.textContent = 'Save Changes'; }, 1500);
        });
      }

      // Initial load
      showSection(activeSectionId);
      fetchDashboardData();
      
      // --- CHAT LOGIC ---
      async function selectChatUser(userId) {
        activeChatUserId = parseInt(userId, 10);
        const user = appData.chat_users.find(u => u.id === activeChatUserId);
        if (user) {
            document.getElementById('chat-header-name').textContent = user.fullname;
            document.getElementById('chat-header-avatar').src = user.picture || 'img/logo.png';
            document.getElementById('chat-header-status').textContent = 'Online'; // Placeholder
        }
        await fetchChatMessages(userId);
        renderAll(); // Re-render to highlight active user
      }

      async function fetchChatMessages(userId) {
        try {
            const res = await fetch(`?action=get_chat_data&user_id=${userId}`);
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            appData.chat_messages = data.chat_messages;
            renderAll();
        } catch (err) {
            console.error('Failed to fetch chat messages', err);
        }
      }

      const chatSend = document.getElementById('chat-send');
      const chatInput = document.getElementById('chat-input');

      async function sendMessage() {
        if (!activeChatUserId || !chatInput.value.trim()) return;
        const message = chatInput.value.trim();
        chatInput.value = '';
        await postAction('send_message', { to_user_id: activeChatUserId, message });
        await fetchChatMessages(activeChatUserId);
      }

      if (chatSend) chatSend.addEventListener('click', sendMessage);
      if (chatInput) chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

      // --- POLLING FOR REAL-TIME UPDATES ---
      // Poll for notifications every 15 seconds
      setInterval(async () => {
        const res = await fetch('?action=get_notifications');
        const data = await res.json();
        appData.notifications = data.notifications;
        const unreadCount = (data.notifications || []).filter(n => !n.is_read).length;
        updateNotificationBadge(unreadCount);
        if (activeSectionId === 'notifications') renderAll();
      }, 15000);

      // Poll for chat messages if a chat is active
      setInterval(async () => {
        if (activeSectionId === 'chat' && activeChatUserId) {
            await fetchChatMessages(activeChatUserId);
        }
      }, 5000);

      // --- BOOKING MODAL LOGIC ---
      const bookingModal = document.getElementById('bookingModal');
      const bookingModalTitle = document.getElementById('booking-modal-title');
      const bookingForm = document.getElementById('bookingForm');
      const customerSearchInput = document.getElementById('customer-search');
      const customerSearchDropdown = document.getElementById('customer-search-dropdown');
      const customerHiddenIdInput = document.getElementById('customer-id');
      const bookingIdInput = document.getElementById('booking-id');

      function openBookingModal(booking = null) {
          editingBooking = booking;
          bookingForm.reset();
          customerSearchDropdown.innerHTML = '';
          customerSearchDropdown.classList.add('hidden');

          if (booking) {
              // Editing existing booking
              bookingModalTitle.textContent = 'Edit Booking';
              bookingIdInput.value = booking.id;
              customerHiddenIdInput.value = booking.user_id;
              customerSearchInput.value = booking.fullname;
              document.getElementById('booking-title').value = booking.title;
              document.getElementById('booking-date').value = booking.date;
              document.getElementById('booking-time').value = booking.time;
              document.getElementById('booking-status').value = booking.status || 'Pending';
          } else {
              // Adding new booking
              bookingModalTitle.textContent = 'Add New Booking';
              bookingIdInput.value = '';
              customerHiddenIdInput.value = '';
          }
          bookingModal.style.display = 'flex';
      }

      function closeBookingModal() {
          bookingModal.style.display = 'none';
          editingBooking = null;
      }

      // Wire up modal controls
      document.getElementById('open-add-booking').addEventListener('click', () => openBookingModal());
      document.getElementById('close-booking-modal').addEventListener('click', closeBookingModal);
      document.getElementById('cancel-booking-modal').addEventListener('click', closeBookingModal);

      // Customer search in modal
      customerSearchInput.addEventListener('input', () => {
          const query = customerSearchInput.value.toLowerCase();
          if (query.length < 2) {
              customerSearchDropdown.classList.add('hidden');
              return;
          }
          const filteredCustomers = appData.customers.filter(c => c.fullname.toLowerCase().includes(query) || c.email.toLowerCase().includes(query));
          customerSearchDropdown.innerHTML = '';
          if (filteredCustomers.length > 0) {
              filteredCustomers.slice(0, 5).forEach(c => {
                  const item = document.createElement('div');
                  item.className = 'p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                  item.textContent = `${c.fullname} (${c.email})`;
                  item.addEventListener('click', () => {
                      customerSearchInput.value = c.fullname;
                      customerHiddenIdInput.value = c.id;
                      customerSearchDropdown.classList.add('hidden');
                  });
                  customerSearchDropdown.appendChild(item);
              });
              customerSearchDropdown.classList.remove('hidden');
          } else {
              customerSearchDropdown.classList.add('hidden');
          }
      });

      // Booking form submission
      bookingForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const action = editingBooking ? 'update_booking' : 'add_booking';
          const payload = {
              user_id: customerHiddenIdInput.value,
              title: document.getElementById('booking-title').value,
              date: document.getElementById('booking-date').value,
              time: document.getElementById('booking-time').value,
              status: document.getElementById('booking-status').value,
              booking_id: bookingIdInput.value // will be empty for new bookings
          };

          const result = await postAction(action, payload);
          if (result && result.success) {
              alert(`Booking ${editingBooking ? 'updated' : 'added'} successfully!`);
              closeBookingModal();
              await fetchDashboardData();
          } else {
              alert(`Failed to ${editingBooking ? 'update' : 'add'} booking.`);
          }
      });

    })();

    function openLoginModal(login) {
    document.getElementById('login-user').textContent = login.fullname;
    document.getElementById('login-time').textContent = new Date(login.login_time).toLocaleString();
    document.getElementById('login-ip').textContent = login.ip_address;
    document.getElementById('login-agent').textContent = login.user_agent;
    document.getElementById('loginModal').style.display = 'flex';
}
function closeLoginModal() {
    document.getElementById('loginModal').style.display = 'none';
}

function openContactModal(contact) {
    document.getElementById('contact-name').textContent = contact.name;
    document.getElementById('contact-email').textContent = contact.email;
    document.getElementById('contact-message').textContent = contact.message;
    document.getElementById('contactModal').style.display = 'flex';
}
function closeContactModal() {
    document.getElementById('contactModal').style.display = 'none';
}

let selectedBooking;
function openStatusModal(booking) {
    selectedBooking = booking;
    document.getElementById('statusModal').style.display = 'flex';
}
function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}
async function updateStatus() {
    const newStatus = document.getElementById('new-status').value;
    const result = await postAction('update_booking_status', { id: selectedBooking.id, status: newStatus });
    if (result.success) {
        alert('Status updated!');
        fetchDashboardData();
    }
    closeStatusModal();
}

  </script>
</body>
</html>