<?php
require_once __DIR__ . '/backend/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT id, fullname, email, password FROM users WHERE email = ? AND oauth_provider = 'local' LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $fullname, $email_db, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user'] = [
                'id' => $id,
                'fullname' => $fullname,
                'email' => $email_db,
                'oauth_provider' => 'local',
            ];
            $stmt->close();
            $conn->close();
            header('Location: customerdash.php');
            exit();
        } else {
            $stmt->close();
            $conn->close();
            die('Invalid email or password.');
        }
    } else {
        $stmt->close();
        $conn->close();
        die('Invalid email or password.');
    }
} else {
    // Show login form or handle GET request
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

<body class="font-display bg-background-dark text-white">
  <div class="relative min-h-screen flex items-center justify-center p-4">
    <div class="gradient-blur gradient-blur-1"></div>
    <div class="gradient-blur gradient-blur-2"></div>

    <!-- Back Button -->
    <a class="absolute top-8 left-8 z-20 flex items-center gap-2 text-white hover:text-primary-light transition-colors duration-300"
      href="index.php">
      <span class="material-icons">arrow_back</span>
      <span>Back to Home</span>
    </a>

    <div class="relative w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

      <!-- Left Section -->
      <div class="hidden lg:flex flex-col items-start justify-center text-left h-full pr-10">
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

        <h2 class="text-4xl font-bold mb-4 text-white">Unleash Your Potential.</h2>
        <p class="text-gray-400 text-lg leading-relaxed">
          Join a community of champions. At Verso Gym, we provide state-of-the-art equipment and expert-led classes to help you achieve your fitness goals. Your journey to a stronger, healthier you starts here.
        </p>
      </div>

      <!-- Right Section Login Form -->
      <div class="flex items-center justify-center w-full">
        <div class="glassy p-10 md:p-12 w-full max-w-md z-10">
          <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-2">
              <img class="w-17 h-16" src="img/logo.png" alt="Verso Gym Logo">
            </div>
            <p class="text-gray-400">Welcome back, champion!</p>
          </div>

          <form action="customerdash.php" method="POST" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2" for="email">Email Address</label>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">email</span>
                <input
                  class="input-field block w-full rounded-lg py-3 pr-3 pl-10 text-white placeholder-gray-500 focus:outline-none"
                  id="email" name="email" placeholder="you@example.com" type="email" required="required"/>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-300" for="password">Password</label>
              </div>
              <div class="relative">
                <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                <input
                  class="input-field block w-full rounded-lg py-3 pr-10 pl-10 text-white placeholder-gray-500 focus:outline-none"
                  id="password" name="password" placeholder="••••••••" type="password" />
              </div>
              <div class="text-right mt-2">
                <a class="text-sm text-blue-400 hover:text-blue-500 transition-colors" href="forgot-password.html">Forgot Password?</a>
              </div>
            </div>
            <div>
              <button type="submit" class="w-full gradient-bg text-white font-bold py-3 px-5 rounded-lg btn-hover transition-all duration-300 flex items-center justify-center gap-2">
                <span>Login</span>
                <span class="material-icons">arrow_forward</span>
              </button>
            </div>
          </form>

          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-white/10"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="bg-[#1a1a1a] px-2 text-gray-400">Or continue with</span>
            </div>
          </div>

          <!-- Only Google button kept -->
          <div class="grid grid-cols-1 gap-4">
            <a href="google_login.php" class="social-btn w-full font-semibold py-3 px-4 rounded-lg flex items-center justify-center gap-2 bg-white text-black">
              <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-6 h-6" />
              Google
            </a>
          </div>

          <div class="mt-8 text-center">
            <p class="text-sm text-gray-400">
              Still don't have an account?
              <a class="font-medium text-primary-light hover:text-primary-dark transition-colors" href="register.php">Sign
                up</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
