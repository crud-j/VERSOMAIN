<?php
require_once __DIR__ . '/backend/config.php';

$error_message = '';
$show_swal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
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
            $oauth_uid = ''; // local registration
            $picture = '';
            $membership_status = 'none';  // default no membership

            $stmt = $conn->prepare("INSERT INTO users (oauth_provider, oauth_uid, fullname, email, phone, password, picture, membership_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssss', $oauth_provider, $oauth_uid, $fullname, $email, $phone, $hashed_password, $picture, $membership_status);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                $show_swal = true; // Flag to trigger Swal
            } else {
                $error_message = 'Registration failed. Please try again.';
                $stmt->close();
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Verso Gym - Sign Up</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: { light: "#FF4500", dark: "#FF6347" },
            "background-light": "#F5F5F5",
            "background-dark": "#121212",
          },
          fontFamily: { display: ["Poppins", "sans-serif"] },
          borderRadius: { DEFAULT: "12px" },
        },
      },
    };
  </script>
  <style>
    body { background-color: #121212; }
    .glassy {
      position: relative;
      background: rgba(18, 18, 18, 0.5);
      backdrop-filter: blur(20px);
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .gradient-blur {
      position: absolute; width: 500px; height: 500px;
      background: linear-gradient(135deg, #FF4500, #FF6347);
      border-radius: 50%; filter: blur(180px); opacity: 0.3;
      pointer-events: none;
    }
    .gradient-blur-1 { top: -150px; left: -200px; animation: animate-blur-1 18s infinite alternate; }
    .gradient-blur-2 { bottom: -200px; right: -250px; animation: animate-blur-2 15s infinite alternate; }

    @keyframes animate-blur-1 {
      0% { transform: translate(0,0) scale(1); }
      50% { transform: translate(120px,60px) scale(1.3); }
      100% { transform: translate(-60px,-120px) scale(0.9); }
    }
    @keyframes animate-blur-2 {
      0% { transform: translate(0,0) scale(1); }
      50% { transform: translate(-100px,-50px) scale(0.8); }
      100% { transform: translate(50px,100px) scale(1.2); }
    }

    .gradient-bg {
      background: linear-gradient(135deg, #FF4500, #FF6347);
    }
    .btn-hover:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(255,69,0,0.3); }

    /* Fix inputs background & autofill */
    .input-field {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: #f0f0f0 !important;
      transition: all 0.3s;
      padding-left: 3rem;
      border-radius: 12px;
      height: 3rem;
      font-weight: 500;
    }
    .input-field:focus {
      background: rgba(255, 255, 255, 0.25) !important;
      border-color: #FF6347 !important;
      color: #fff !important;
      box-shadow: 0 0 0 3px rgba(255, 99, 71, 0.3);
    }
    /* Chrome autofill fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 1000px rgba(255, 255, 255, 0.15) inset !important;
      -webkit-text-fill-color: #f0f0f0 !important;
      transition: background-color 5000s ease-in-out 0s;
    }

    .password-toggle-icon {
      position: absolute; right: 1rem; top: 50%;
      transform: translateY(-50%);
      cursor: pointer; color: #9CA3AF;
      transition: color 0.3s;
    }
    .password-toggle-icon:hover { color: #FF6347; }
    .social-btn {
      display: flex; align-items: center; justify-content: center;
      gap: 0.75rem; border: 1px solid rgba(255,255,255,0.15);
      padding: 0.75rem; border-radius: 8px;
      font-weight: 500; transition: all 0.3s;
    }
    .social-btn:hover { background: rgba(255,255,255,0.08); transform: translateY(-1px); }
  </style>
</head>

<body class="font-display bg-background-dark text-white">
  <div class="relative min-h-screen flex items-center justify-center p-4">
    <div class="gradient-blur gradient-blur-1"></div>
    <div class="gradient-blur gradient-blur-2"></div>

    <a class="absolute top-8 left-8 flex items-center gap-2 text-white hover:text-primary-light transition-colors" href="index.php" aria-label="Back to Home">
      <span class="material-icons">arrow_back</span>
      <span>Back to Home</span>
    </a>

    <div class="relative w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <!-- Left -->
      <div class="hidden lg:flex flex-col items-start justify-center text-left h-full pr-10">
        <div class="w-full h-64 bg-gray-800/50 rounded-lg flex items-center justify-center border border-white/10 mb-8">
          <img alt="Gym" class="w-full h-full object-cover rounded-lg" src="img/hero-1.png" />
        </div>
        <h2 class="text-4xl font-bold mb-4">Start Your Journey.</h2>
        <p class="text-gray-400 text-lg leading-relaxed">
          Create your account to access personalized workout plans, track your progress, and connect with a community that inspires you.
        </p>
        <a class="mt-8 text-primary-light hover:text-primary-dark flex items-center gap-2" href="#about">
          <span>Learn More</span><span class="material-icons">arrow_forward</span>
        </a>
      </div>

      <!-- Right -->
      <div class="flex items-center justify-center w-full">
        <div class="glassy p-8 md:p-10 w-full max-w-md z-10">

          <!-- Error Message -->
          <?php if ($error_message): ?>
          <div class="mb-4 p-4 text-red-600 bg-red-200 rounded">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
          <?php endif; ?>

          <?php if (!$show_swal): ?>
          <div class="text-center mb-6">
            <div class="inline-flex items-center gap-3 mb-2">
              <img class="w-16 h-16" src="img/logo.png" alt="Verso Gym Logo">
              <h1 class="text-2xl font-bold text-white">Verso Gym</h1>
            </div>
            <p class="text-gray-400">Create your account</p>
          </div>

          <form action="" method="POST" class="space-y-4">

            <!-- Full Name -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="fullname">Full Name</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">person</span>
                <input class="input-field block w-full rounded-lg py-3 pr-3 pl-10 placeholder-gray-500 focus:outline-none" id="fullname" name="fullname" placeholder="Enter your full name" type="text" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>">
              </div>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="email">Email Address</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">email</span>
                <input class="input-field block w-full rounded-lg py-3 pr-3 pl-10 placeholder-gray-500 focus:outline-none" id="email" name="email" placeholder="you@example.com" type="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
              </div>
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="phone">Phone Number</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">phone</span>
                <input class="input-field block w-full rounded-lg py-3 pr-3 pl-10 placeholder-gray-500 focus:outline-none" id="phone" name="phone" placeholder="Enter your phone (optional)" type="tel" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="password">Password</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                <input class="input-field block w-full rounded-lg py-3 pr-12 pl-10 placeholder-gray-500 focus:outline-none" id="password" name="password" placeholder="Password" type="password" required>
                <span class="material-icons password-toggle-icon" onclick="togglePasswordVisibility('password', this)">visibility_off</span>
              </div>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="confirm_password">Confirm Password</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock_reset</span>
                <input class="input-field block w-full rounded-lg py-3 pr-12 pl-10 placeholder-gray-500 focus:outline-none" id="confirm_password" name="confirm_password" placeholder="••••••••" type="password" required>
                <span class="material-icons password-toggle-icon" onclick="togglePasswordVisibility('confirm_password', this)">visibility_off</span>
              </div>
            </div>

            <!-- Sign Up -->
            <div class="pt-2">
              <button class="w-full gradient-bg text-white font-bold py-3 px-5 rounded-lg btn-hover flex items-center justify-center gap-2" type="submit">
                <span>Sign Up</span><span class="material-icons">arrow_forward</span>
              </button>
            </div>
          </form>
          <?php endif; ?>

          <!-- Divider -->
          <div class="flex items-center my-6">
            <hr class="flex-grow border-gray-700">
            <span class="px-3 text-sm text-gray-400">or sign up with</span>
            <hr class="flex-grow border-gray-700">
          </div>

          <!-- Social Buttons -->
          <div>
            <a href="google_login.php" class="social-btn w-full font-semibold py-3 px-4 rounded-lg flex items-center justify-center gap-2 bg-white text-black">
              <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-6 h-6" />
              Google
            </a>
          </div>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-400">
              Already have an account?
              <a class="font-medium text-primary-light hover:text-primary-dark" href="login.php">Log in</a>
            </p>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePasswordVisibility(fieldId, icon) {
      const input = document.getElementById(fieldId);
      if (input.type === "password") {
        input.type = "text";
        icon.textContent = "visibility";
      } else {
        input.type = "password";
        icon.textContent = "visibility_off";
      }
    }

    <?php if ($show_swal): ?>
    // Show SweetAlert success after registration
    Swal.fire({
      title: 'Great!',
      text: 'You have successfully registered!',
      icon: 'success',
      confirmButtonText: 'Go to Login'
    }).then(() => {
      window.location.href = 'login.php';
    });
    
    <?php endif; ?>
  </script>
</body>
</html>