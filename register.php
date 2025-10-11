<?php
// register.php
session_start();
require_once __DIR__ . '/backend/config.php';

// try to autoload Google Client only if composer vendor is present
$googleAvailable = file_exists(__DIR__ . '/vendor/autoload.php');
if ($googleAvailable) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$error_message = '';
$current_step = 0; // 0=signup form,1=profile setup,2=redirect/dashboard
$user_id = null;
$user_data = null;

// Handle Google OAuth start
if (isset($_GET['google_login']) && $googleAvailable) {
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope('email');
    $client->addScope('profile');
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
} elseif (isset($_GET['google_login']) && !$googleAvailable) {
    $error_message = 'Google login is not available on this server (composer/google-api-php-client missing).';
}

// Handle Google OAuth callback (code param)
if ($googleAvailable && isset($_GET['code'])) {
    try {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            $oauth2 = new Google_Service_Oauth2($client);
            $google_user_info = $oauth2->userinfo->get();

            $email = $google_user_info->email;
            $fullname = $google_user_info->name;
            $oauth_uid = $google_user_info->id;
            $picture = $google_user_info->picture ?? '';

            $conn = getDbConnection();
            // Check existing google user by oauth_uid
            $stmt = $conn->prepare("SELECT id, membership_status FROM users WHERE oauth_provider = 'google' AND oauth_uid = ? LIMIT 1");
            $stmt->bind_param('s', $oauth_uid);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($existing_user_id, $membership_status);
                $stmt->fetch();
                $_SESSION['user_id'] = (int)$existing_user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_fullname'] = $fullname;
                $_SESSION['user_picture'] = $picture;
                $stmt->close();
                $conn->close();

                // If profile complete, go to dashboard; else profile setup
                if ($membership_status === 'active') {
                    header('Location: customerdash.php');
                    exit();
                } else {
                    header('Location: register.php');
                    exit();
                }
            } else {
                $stmt->close();
                $membership_status = 'none';
                $role = 'customer';
                $stmt = $conn->prepare("INSERT INTO users (oauth_provider, oauth_uid, fullname, email, picture, membership_status, role) VALUES ('google', ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssss', $oauth_uid, $fullname, $email, $picture, $membership_status, $role);
                if ($stmt->execute()) {
                    $new_user_id = $stmt->insert_id;
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_fullname'] = $fullname;
                    $_SESSION['user_picture'] = $picture;
                    $stmt->close();
                    $conn->close();
                    // New user, go to profile setup
                    header('Location: register.php');
                    exit();
                } else {
                    $error_message = 'Google login failed. Please try again.';
                    $stmt->close();
                    $conn->close();
                }
            }
        } else {
            $error_message = 'Failed to obtain Google user information.';
        }
    } catch (Exception $e) {
        $error_message = 'Google OAuth error: ' . $e->getMessage();
    }
}

// Local signup POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $fullname = clean_input($_POST['fullname'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address.';
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error_message = 'Email already registered.';
            $stmt->close();
            $conn->close();
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $oauth_provider = 'local';
            $oauth_uid = null;
            $picture = null;
            $membership_status = 'none';
            $role = 'customer';

            $stmt = $conn->prepare("INSERT INTO users (oauth_provider, oauth_uid, fullname, email, password, picture, membership_status, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssss', $oauth_provider, $oauth_uid, $fullname, $email, $hashed_password, $picture, $membership_status, $role);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_fullname'] = $fullname;

                // Log the successful "first login" upon registration
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $insert_stmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $insert_stmt->bind_param('iss', $user_id, $ip, $user_agent);
                $insert_stmt->execute();
                $insert_stmt->close();

                $stmt->close();
                $conn->close();

                // Go directly to the dashboard, which will handle the new user state
                header('Location: customerdash.php');
                exit();
            } else {
                $error_message = 'Registration failed. Please try again.';
                $stmt->close();
                $conn->close();
            }
        }
    }
}

// AJAX profile setup handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile_setup') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $fullName = clean_input($_POST['fullName'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = clean_input($_POST['gender'] ?? '');
    $fitnessGoals = clean_input($_POST['fitnessGoals'] ?? '');
    $picture_input = $_POST['picture'] ?? '';
    $picture_path = '';

    if (empty($fullName) || $age <= 0 || empty($gender) || empty($fitnessGoals)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please fill all required fields with valid values.']);
        exit;
    }

    // Handle picture upload
    if (preg_match('/^data:image\/(\w+);base64,/', $picture_input, $type)) {
        $data = substr($picture_input, strpos($picture_input, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

        if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid image type.']);
            exit;
        }
        $data = base64_decode($data);
        if ($data === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Base64 decode failed.']);
            exit;
        }

        $upload_dir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = uniqid() . '.' . $type;
        $filepath = $upload_dir . $filename;

        if (file_put_contents($filepath, $data)) {
            $picture_path = 'uploads/avatars/' . $filename;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save image.']);
            exit;
        }
    } elseif (filter_var($picture_input, FILTER_VALIDATE_URL)) {
        $picture_path = $picture_input;
    } else {
        // Fallback for existing or placeholder images
        $picture_path = $picture_input;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE users SET fullname = ?, age = ?, gender = ?, fitness_goals = ?, picture = ?, membership_status = 'active' WHERE id = ?");
    $stmt->bind_param('sisssi', $fullName, $age, $gender, $fitnessGoals, $picture_path, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($success) {
        $_SESSION['user_fullname'] = $fullName;
        $_SESSION['user_picture'] = $picture_path;
        echo json_encode(['success' => true, 'picture_url' => $picture_path]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile in database.']);
    }
    exit;
}

// Check session to determine current step
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, fullname, email, picture, age, gender, fitness_goals, membership_status FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        if ($user_data['membership_status'] === 'active') {
            // Profile setup done, redirect to dashboard
            header('Location: customerdash.php');
            exit();
        } else {
            $current_step = 1; // profile setup
        }
    }
    $stmt->close();
    $conn->close();
} else {
    $current_step = 0; // signup form
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Verso Gym - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: {
              light: "#FF4500",
              dark: "#FF6347"
            },
            "background-light": "#F5F5F5",
            "background-dark": "#121212",
          },
          fontFamily: {
            display: ["Poppins", "sans-serif"],
          },
          borderRadius: {
            DEFAULT: "12px",
          },
        },
      },
    };
  </script>
  <style>
    body {
      background-color: #121212;
      overflow: hidden;
    }

    .glassy {
      position: relative;
      background: rgba(18, 18, 18, 0.5);
      -webkit-backdrop-filter: blur(20px);
      backdrop-filter: blur(20px);
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      overflow: hidden;
    }

    .gradient-blur {
      position: absolute;
      width: 500px;
      height: 500px;
      background: linear-gradient(135deg, #FF4500, #FF6347);
      border-radius: 50%;
      filter: blur(180px);
      opacity: 0.3;
      pointer-events: none;
    }

    .gradient-blur-1 {
      top: -150px;
      left: -200px;
      animation: animate-blur-1 18s infinite alternate;
    }

    .gradient-blur-2 {
      bottom: -200px;
      right: -250px;
      animation: animate-blur-2 15s infinite alternate;
    }

    @keyframes animate-blur-1 {
      0% {
        transform: translateX(0) translateY(0) scale(1);
      }

      50% {
        transform: translateX(120px) translateY(60px) scale(1.3);
      }

      100% {
        transform: translateX(-60px) translateY(-120px) scale(0.9);
      }
    }

    @keyframes animate-blur-2 {
      0% {
        transform: translateX(0) translateY(0) scale(1);
      }

      50% {
        transform: translateX(-100px) translateY(-50px) scale(0.8);
      }

      100% {
        transform: translateX(50px) translateY(100px) scale(1.2);
      }
    }

    .gradient-bg {
      background: linear-gradient(135deg, #FF4500, #FF6347);
    }

    .btn-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(255, 69, 0, 0.3);
    }

    .input-field {
  background: rgba(255, 255, 255, 0.15); /* slightly stronger background */
  border: 1px solid rgba(255, 255, 255, 0.2); /* stronger border */
  color: #f0f0f0; /* light gray text color */
  caret-color: #FF6347; /* accent caret color */
  font-weight: 500;
  padding-left: 2.75rem; /* adjusted for icon */
  border-radius: 12px;
  height: 3rem;
  width: 100%;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.input-field::placeholder {
  color: #cbd5e1; /* Tailwind slate-300, lighter placeholder */
  font-weight: 400;
}

.input-field:focus {
  background: rgba(255, 255, 255, 0.25);
  border-color: #FF6347;
  box-shadow: 0 0 0 3px rgba(255, 99, 71, 0.3);
  outline: none;
  color: #fff; /* pure white text on focus */
}
    .social-btn {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }

    .social-btn:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    .slider {
      display: flex;
      width: max-content;
      animation: scroll 20s linear infinite;
    }

    @keyframes scroll {
      0% {
        transform: translateX(0);
      }

      100% {
        transform: translateX(-50%);
      }
    }
  </style>
</head>
<body class="font-sans spotlight-bg text-white">
 <a class="absolute top-4 left-4 text-white bg-gray-800/50 hover:bg-gray-700/70 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center backdrop-blur-sm border border-gray-600 z-20"
    href="login.php">
    <span class="material-symbols-outlined mr-2">arrow_back</span>
    Back
  </a>

  <div class="flex flex-col items-center justify-start min-h-screen px-4 py-8 max-w-5xl mx-auto w-full">
    <div class="spotlight-border w-full mx-auto">
      <div
        class="relative flex flex-col m-0 space-y-8 bg-black/50 shadow-2xl rounded-[calc(1rem-1px)] overflow-visible glassmorphism md:flex-row md:space-y-0">

        <!-- Left Panel -->
        <div class="relative w-full md:w-[40%] p-8 text-white flex flex-col justify-between gradient-bg">
          <div class="relative z-10 text-center flex flex-col justify-start">
            <div class="flex items-center justify-center mb-4">
              <img src="img/logo.png" class="w-14 h-14" alt="Verso Logo">
              <h1 class="text-3xl font-bold ml-2">Verso Gym</h1>
            </div>
            <div class="overflow-hidden w-full rounded-lg border border-white/10 mb-8">
              <div class="slider">
                <div class="flex">
                  <img src="img/gallery-1.jpg" class="w-64 h-40 object-cover" />
                  <img src="img/gallery-2.jpg" class="w-64 h-40 object-cover" />
                  <img src="img/hero-1.png" class="w-64 h-40 object-cover" />
                  <img src="img/gallery-3.jpg" class="w-64 h-40 object-cover" />
                </div>
                <div class="flex">
                  <img src="img/gallery-4.jpg" class="w-64 h-40 object-cover" />
                  <img src="img/gallery-2.jpg" class="w-64 h-40 object-cover" />
                  <img src="img/hero-1.png" class="w-64 h-40 object-cover" />
                  <img src="img/gallery-3.jpg" class="w-64 h-40 object-cover" />
                </div>
              </div>
            </div>
          </div>
          <div class="relative z-10 text-center flex flex-col justify-end mt-12">
            <h2 class="text-2xl font-bold mb-2">Get started with us</h2>
            <p class="mb-6 text-sm">Complete these easy steps to register your account.</p>
            <div class="space-y-3 text-left mx-auto max-w-xs text-sm mt-4">
              <div id="step-1-indicator" class="flex items-center glassmorphism p-3 rounded-lg shadow border-transparent <?= $current_step === 0 ? '' : 'opacity-70' ?>">
                <div
                  class="bg-white/30 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold mr-3 text-xs">1</div>
                <span>Sign up your account</span>
              </div>
              <div id="step-2-indicator"
                class="flex items-center glassmorphism p-3 rounded-lg border-transparent <?= $current_step === 1 ? '' : 'opacity-70' ?>">
                <div
                  class="bg-white/20 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold mr-3 text-xs">2</div>
                <span>Set up your profile</span>
              </div>
              <div id="step-3-indicator"
                class="flex items-center glassmorphism p-3 rounded-lg border-transparent <?= $current_step === 2 ? '' : 'opacity-70' ?>">
                <div
                  class="bg-white/20 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold mr-3 text-xs">3</div>
                <span>Customer Dashboard</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Panel -->
        <div class="w-full md:w-[60%] p-8 md:p-12 bg-gray-900/80 backdrop-blur-sm">

          <!-- Step 1: Sign Up Form -->
          <section id="step-1" class="step <?= $current_step === 0 ? 'active' : '' ?>">
            <h2 class="text-3xl font-bold text-white mb-2">Sign Up Account</h2>
            <p class="text-gray-400 mb-8">Enter your personal data to create your account.</p>

            <button id="google-login-btn"
              class="w-full flex items-center justify-center py-3 mb-6 glassmorphism-input rounded-lg text-gray-300 hover:bg-gray-800/80 transition-colors">
              <svg class="mr-3" fill="none" height="24" viewBox="0 0 24 24" width="24"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25C22.56 11.45 22.49 10.68 22.36 9.92H12V14.4H18.17C17.89 15.83 17.07 17.06 15.82 17.86V20.25H19.33C21.43 18.36 22.56 15.55 22.56 12.25Z"
                  fill="#4285F4"></path>
                <path d="M12 23C15.02 23 17.57 21.99 19.33 20.25L15.82 17.86C14.78 18.57 13.51 19 12 19C9.21 19 6.8 17.29 5.86 14.9H2.25V17.38C3.99 20.73 7.7 23 12 23Z"
                  fill="#34A853"></path>
                <path d="M5.86 14.9C5.62 14.22 5.5 13.48 5.5 12.7C5.5 11.92 5.62 11.18 5.86 10.5H2.25V7.92C1.47 9.48 1 11.02 1 12.7C1 14.38 1.47 15.92 2.25 17.38L5.86 14.9Z"
                  fill="#FBBC05"></path>
                <path d="M12 5C13.68 5 15.11 5.57 16.22 6.6L19.41 3.58C17.57 1.95 15.02 1 12 1C7.7 1 3.99 3.27 2.25 6.62L5.86 9.1C6.8 6.71 9.21 5 12 5Z"
                  fill="#EA4335"></path>
              </svg>
              Sign up with Google
            </button>

            <div class="flex items-center mb-6">
              <div class="flex-grow border-t border-gray-700"></div>
              <span class="mx-4 text-gray-400">Or</span>
              <div class="flex-grow border-t border-gray-700"></div>
            </div>

            <form id="signup-form" method="POST" action="">
              <input type="hidden" name="action" value="signup" />
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="fullname">Full Name</label>
                <div class="spotlight-input">
                  <div>
                    <input id="fullname" name="fullname" placeholder="Enter your full name" type="text" required
                      value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>" />
                  </div>
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="email">Email Address</label>
                <div class="spotlight-input">
                  <div>
                    <input id="email" name="email" placeholder="youremail@domain.com" type="email" required
                      value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" />
                  </div>
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="password">Password</label>
                <div class="spotlight-input">
                  <div class="flex items-center">
                    <input class="w-full pr-10" id="password" name="password" minlength="8" placeholder="Enter your password"
                      type="password" required />
                    <button class="px-3" id="togglePasswordBtn" type="button" aria-label="Toggle password visibility">
                      <span class="material-symbols-outlined text-sm" id="password-toggle-icon">visibility_off</span>
                    </button>
                  </div>
                </div>
                <p class="mt-2 text-sm text-gray-400">Must be at least 8 characters.</p>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="confirm_password">Confirm Password</label>
                <div class="spotlight-input">
                  <div class="flex items-center">
                    <input class="w-full pr-10" id="confirm_password" name="confirm_password" minlength="8" placeholder="Confirm your password"
                      type="password" required />
                    <button class="px-3" id="toggleConfirmPasswordBtn" type="button" aria-label="Toggle password visibility">
                      <span class="material-symbols-outlined text-sm" id="confirm-password-toggle-icon">visibility_off</span>
                    </button>
                  </div>
                </div>
              </div>
              <button
                class="w-full bg-orange-600 text-white font-bold py-3 rounded-lg hover:bg-orange-700 transition-colors"
                type="submit">Sign Up</button>
            </form>
          </section>

          <!-- Step 2: Profile Setup Form -->
          <section id="step-2" class="step <?= $current_step === 1 ? 'active' : '' ?>">
            <h2 class="text-3xl font-bold text-white mb-2">Set up your profile</h2>
            <p class="text-gray-400 mb-8">Add your personal details to complete your profile.</p>
            <form id="profile-form" enctype="multipart/form-data" novalidate>
              <div class="flex items-center space-x-6 mb-6">
                <div class="shrink-0">
                  <img alt="Profile photo preview" class="h-20 w-20 object-cover rounded-full border-2 border-gray-600"
                    id="profile-picture"
                    src="<?= isset($user_data['picture']) && $user_data['picture'] !== '' ? htmlspecialchars($user_data['picture']) : 'https://via.placeholder.com/80?text=Profile' ?>" />
                </div>
                <label class="block w-full">
                  <span class="sr-only">Choose profile photo</span>
                  <input class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-600/20 file:text-orange-400 hover:file:bg-orange-600/30"
                    id="profile-upload" type="file" accept="image/*" />
                </label>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1" for="full-name">Full Name</label>
                  <div class="spotlight-input">
                    <div>
                      <input id="full-name" name="fullName" placeholder="Enter your full name" type="text" required
                        value="<?= isset($user_data['fullname']) ? htmlspecialchars($user_data['fullname']) : '' ?>" />
                    </div>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-300 mb-1" for="age">Age</label>
                  <div class="spotlight-input">
                    <div>
                      <input id="age" name="age" placeholder="Enter your age" type="number" min="1" max="120" required value="<?= isset($user_data['age']) && $user_data['age'] > 0 ? (int)$user_data['age'] : '' ?>" />
                    </div>
                  </div>
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="gender">Gender</label>
                <div class="spotlight-input">
                  <div>
                    <select class="text-gray-400" id="gender" name="gender" required>
                      <option value="" disabled <?= empty($user_data['gender']) ? 'selected' : '' ?>>Select your gender</option>
                      <option <?= (isset($user_data['gender']) && $user_data['gender'] === 'Male') ? 'selected' : '' ?>>Male</option>
                      <option <?= (isset($user_data['gender']) && $user_data['gender'] === 'Female') ? 'selected' : '' ?>>Female</option>
                      <option <?= (isset($user_data['gender']) && $user_data['gender'] === 'Non-binary') ? 'selected' : '' ?>>Non-binary</option>
                      <option <?= (isset($user_data['gender']) && $user_data['gender'] === 'Prefer not to say') ? 'selected' : '' ?>>Prefer not to say</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1" for="fitness-goals">Fitness Goals</label>
                <div class="spotlight-input">
                  <div>
                    <textarea id="fitness-goals" name="fitnessGoals" placeholder="e.g., Lose weight, build muscle, improve cardio" rows="3" required><?= isset($user_data['fitness_goals']) ? htmlspecialchars($user_data['fitness_goals']) : '' ?></textarea>
                  </div>
                </div>
              </div>
              <div class="hidden mt-4 p-4 mb-4 text-sm text-green-400 bg-green-900/30 rounded-lg border border-green-600/50"
                id="success-message" role="alert">
                <span class="font-medium">Profile setup successful! Redirecting to your dashboard...</span>
              </div>
              <button
                class="w-full bg-orange-600 text-white font-bold py-3 rounded-lg hover:bg-orange-700 transition-colors mt-8"
                type="submit">Save and Continue</button>
            </form>
          </section>

          <!-- Step 3: Redirecting -->
          <section id="step-3" class="step <?= $current_step === 2 ? 'active' : '' ?> text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Almost there...</h2>
            <p class="text-gray-400 mb-6">You are just a few steps away from your personalized fitness journey.</p>
            <div class="spinner mb-8"></div>
            <h2 class="text-3xl font-bold text-white mb-2">Directing You to Your Dashboard</h2>
            <p class="text-gray-400 mt-4 max-w-sm mx-auto">Your profile has been successfully set up. Please wait while we
              redirect you to your personalized dashboard.</p>
            <div class="w-full bg-gray-700/50 rounded-full h-2.5 mt-8 max-w-sm overflow-hidden mx-auto">
              <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-2.5 rounded-full loading-bar"></div>
            </div>
            <p class="text-sm text-gray-500 mt-4">This should only take a moment.</p>
          </section>

          <?php if ($error_message): ?>
            <div class="mt-4 p-4 text-red-500 bg-gray-800 rounded"><?= htmlspecialchars($error_message) ?></div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <script>
    // Password visibility toggles
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const passwordToggleIcon = document.getElementById('password-toggle-icon');
    togglePasswordBtn.addEventListener('click', () => {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggleIcon.textContent = 'visibility';
      } else {
        passwordInput.type = 'password';
        passwordToggleIcon.textContent = 'visibility_off';
      }
    });
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPasswordBtn');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const confirmPasswordToggleIcon = document.getElementById('confirm-password-toggle-icon');
    toggleConfirmPasswordBtn.addEventListener('click', () => {
      if (confirmPasswordInput.type === 'password') {
        confirmPasswordInput.type = 'text';
        confirmPasswordToggleIcon.textContent = 'visibility';
      } else {
        confirmPasswordInput.type = 'password';
        confirmPasswordToggleIcon.textContent = 'visibility_off';
      }
    });

    // Spotlight input mousemove effect
    function addSpotlightEffect() {
      document.querySelectorAll('.spotlight-input').forEach(item => {
        item.addEventListener('mousemove', e => {
          const rect = item.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          item.style.setProperty('--x', `${x}px`);
          item.style.setProperty('--y', `${y}px`);
        });
      });
    }
    addSpotlightEffect();

    // Spotlight border mousemove effect
    const spotlightBorder = document.querySelector('.spotlight-border');
    spotlightBorder.addEventListener('mousemove', e => {
      const rect = e.currentTarget.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      e.currentTarget.style.setProperty('--x', `${x}px`);
      e.currentTarget.style.setProperty('--y', `${y}px`);
    });

    // Navigation and step management
    const stepIndicators = [
      document.getElementById('step-1-indicator'),
      document.getElementById('step-2-indicator'),
      document.getElementById('step-3-indicator'),
    ];
    const steps = [
      document.getElementById('step-1'),
      document.getElementById('step-2'),
      document.getElementById('step-3'),
    ];

    function showStep(stepIndex) {
      steps.forEach((step, i) => {
        step.classList.toggle('active', i === stepIndex);
      });
      stepIndicators.forEach((indicator, i) => {
        if (i === stepIndex) {
          indicator.classList.remove('opacity-70');
          indicator.classList.add('shadow');
        } else {
          indicator.classList.add('opacity-70');
          indicator.classList.remove('shadow');
        }
      });
      // Scroll top for better UX on small screens
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Initial highlight for current step from PHP
    showStep(<?= $current_step ?>);

    // Back button behavior
    const backLink = document.querySelector('a.absolute.top-4.left-4');
    backLink.addEventListener('click', e => {
      e.preventDefault();
      let currentStepIndex = steps.findIndex(step => step.classList.contains('active'));
      if (currentStepIndex > 0) {
        showStep(currentStepIndex - 1);
      } else {
        window.location.href = 'login.php';
      }
    });

    // Google login button click
    const googleLoginBtn = document.getElementById('google-login-btn');
    googleLoginBtn.addEventListener('click', () => {
      window.location.href = '?google_login=1';
    });

    // Handle profile picture preview upload
    const profileUpload = document.getElementById('profile-upload');
    const profilePicture = document.getElementById('profile-picture');
    let uploadedPictureDataURL = null;
    profileUpload.addEventListener('change', function (event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          profilePicture.src = e.target.result;
          uploadedPictureDataURL = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });

    // Handle profile form submission with AJAX to update backend
    const profileForm = document.getElementById('profile-form');
    const successMessage = document.getElementById('success-message');

    profileForm.addEventListener('submit', e => {
      e.preventDefault();

      const formData = new FormData(profileForm);
      formData.append('action', 'profile_setup');

      if (uploadedPictureDataURL) {
        formData.set('picture', uploadedPictureDataURL);
      } else {
        formData.set('picture', profilePicture.src);
      }

      if (!formData.get('fullName') || !formData.get('age') || !formData.get('gender') || !formData.get('fitnessGoals')) {
        Swal.fire('Error', 'Please fill all the required fields.', 'error');
        return;
      }

      fetch('', {
        method: 'POST',
        body: formData,
      }).then(res => res.json())
        .then(data => {
          if (data.success) {
            successMessage.classList.remove('hidden');
            profileForm.querySelectorAll('input, select, textarea, button').forEach(el => el.disabled = true);

            setTimeout(() => {
              successMessage.classList.add('hidden');
              showStep(2);

              setTimeout(() => {
                window.location.href = 'customerdash.php';
              }, 2500);
            }, 1500);
          } else if (data.error) {
            Swal.fire('Error', data.error, 'error');
          }
        }).catch(() => {
          Swal.fire('Error', 'Failed to save profile. Please try again.', 'error');
        });
    });

    <?php if ($error_message): ?>
      Swal.fire('Error', '<?= addslashes($error_message) ?>', 'error');
    <?php endif; ?>

  </script>
</body>
</html>