<?php
session_start();

// Define default variables to avoid undefined errors
if (!isset($error_message)) $error_message = '';
if (!isset($csrf_token)) $csrf_token = bin2hex(random_bytes(32));
if (!isset($plan)) $plan = 'basic';
if (!isset($price)) $price = 999.99;
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Verso Checkout</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#10B981",
            secondary: "#6366F1",
          },
          fontFamily: {
            display: ["Space Grotesk", "sans-serif"],
          },
        },
      },
    };
  </script>

  <style>
    /* Grain overlay animation */
    @keyframes grain {
      0%, 100% { transform: translate(0, 0); }
      10% { transform: translate(-5%, -10%); }
      20% { transform: translate(-15%, 5%); }
      30% { transform: translate(7%, -25%); }
      40% { transform: translate(-5%, 25%); }
      50% { transform: translate(-15%, 10%); }
      60% { transform: translate(15%, 0%); }
      70% { transform: translate(0%, 15%); }
      80% { transform: translate(3%, 35%); }
      90% { transform: translate(-10%, 10%); }
    }
    .grainy::after {
      content: "";
      position: absolute;
      top: -100%;
      left: -100%;
      width: 300%;
      height: 300%;
      background-image: url('data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMjUwIDI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZmlsdGVyIGlkPSJub2lzZSI+PGZlVHVyYnVsZW5jZSBiYXNlRnJlcXVlbmN5PSIuOCIgdHlwZT0iZnJhY3RhbE5vaXNlIi8+PC9maWx0ZXI+PHJlY3QgZmlsdGVyPSJ1cmwoI25vaXNlKSIgaGVpZ2h0PSIxMDAlIiB3aWR0aD0iMTAwJSIvPjwvc3ZnPg==');
      opacity: 0.04;
      animation: grain 8s steps(10) infinite;
      pointer-events: none;
    }
    /* Glass card */
    inventor-bg-card {
      position: relative;
      overflow: hidden;
      background-color: rgba(255, 255, 255, 0.04);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      transition: all .36s ease;
    }
    .interactive-bg-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 40px rgba(16, 185, 129, 0.08);
    }
    /* Modal helper */
    .modal {
      display: none;
    }
    .modal.active {
      display: flex;
    }
    /* Modal centering and padding */
    #confirmationModal > div,
    #successMessageBanner {
      max-width: 420px;
      width: 90%;
      padding: 2rem;
      text-align: center;
      border-radius: 1rem;
      margin: 1rem auto;
    }
    /* Success message banner styling */
    #successMessageBanner {
      background-color: rgba(16, 185, 129, 0.8);
      color: white;
      font-weight: 600;
      display: none;
    }
    /* Checkmark animation */
    .checkmark {
      width: 84px;
      height: 84px;
      display: block;
      margin: 0 auto 1rem auto;
    }
    .checkmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 3;
      stroke: #10B981;
      fill: none;
      animation: drawCircle .6s cubic-bezier(.65, .0, .45, 1) forwards;
    }
    .checkmark__check {
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      stroke-width: 3;
      stroke: #10B981;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
      animation: drawCheck .4s cubic-bezier(.65, .0, .45, 1) .6s forwards;
    }
    @keyframes drawCircle {
      to {
        stroke-dashoffset: 0;
      }
    }
    @keyframes drawCheck {
      to {
        stroke-dashoffset: 0;
      }
    }
    /* Form input spacing */
    form#checkoutForm > div {
      margin-bottom: 1rem;
    }
    /* Submit button styling */
    form#checkoutForm button[type="submit"] {
      font-weight: 700;
      width: 100%;
      padding: 0.75rem;
      border-radius: 0.75rem;
    }
  </style>
</head>

<body class="bg-[#0b0c10] font-display text-white relative min-h-screen grainy">

  <div class="absolute inset-0 -z-10">
    <div class="absolute top-0 left-0 w-[600px] h-[600px] bg-primary/20 blur-[150px] rounded-full"></div>
    <div class="absolute bottom-0 right-0 w-[700px] h-[700px] bg-secondary/20 blur-[150px] rounded-full"></div>
    <div class="absolute top-[30%] left-[50%] w-[400px] h-[400px] bg-pink-500/10 blur-[120px] rounded-full"></div>
  </div>

  <!-- Success message banner -->
  <div id="successMessageBanner" role="alert" aria-live="assertive"></div>

  <!-- Header -->
  <header class="w-full p-6 flex items-center justify-between">
    <a href="index.php/#membership" class="inline-flex items-center gap-2 bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition" tabindex="0">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Back to Membership
    </a>
  </header>

  <!-- Main content -->
  <main class="flex flex-col lg:flex-row items-stretch justify-center w-full p-6 lg:p-12 min-h-[85vh]">

    <!-- Left: Form -->
    <section class="w-full lg:w-1/2 flex items-start justify-center">
      <div class="interactive-bg-card rounded-2xl p-8 w-full max-w-lg" role="main">

        <?php if (!empty($_SESSION['error_message'])): ?>
          <div class="bg-red-500/20 text-red-200 p-4 rounded-lg mb-6 text-center text-sm max-w-lg mx-auto" role="alert">
            <?php
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
          </div>
        <?php endif; ?>

        <h1 class="text-3xl font-bold mb-6">Payment Method</h1>
        <p class="text-white/60 mb-6 text-sm">Choose GCash as your payment option and provide your details below.</p>

        <form id="checkoutForm" class="space-y-4" action="/backend/payment.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="amount" value="<?php echo htmlspecialchars(number_format($price, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">

          <div>
            <label for="firstName" class="text-sm block mb-1">First Name</label>
            <input id="firstName" name="firstName" type="text" required
              class="w-full rounded-lg bg-white/5 p-3 focus:ring-2 focus:ring-primary/50" autocomplete="given-name" aria-required="true" />
          </div>

          <div>
            <label for="lastName" class="text-sm block mb-1">Last Name</label>
            <input id="lastName" name="lastName" type="text" required
              class="w-full rounded-lg bg-white/5 p-3 focus:ring-2 focus:ring-primary/50" autocomplete="family-name" aria-required="true" />
          </div>

          <div>
            <label for="email" class="text-sm block mb-1">Email</label>
            <input id="email" name="email" type="email" required
              class="w-full rounded-lg bg-white/5 p-3 focus:ring-2 focus:ring-primary/50" autocomplete="email" aria-required="true" />
          </div>

          <div>
            <label for="phone" class="text-sm block mb-1">Phone</label>
            <input id="phone" name="phone" placeholder="917 123 4567" required
              class="w-full rounded-lg bg-white/5 p-3 focus:ring-2 focus:ring-primary/50" autocomplete="tel" aria-required="true" />
          </div>

          <div>
            <button type="submit"
              class="w-full bg-primary py-3 rounded-lg font-bold hover:bg-primary/90 transition">Proceed to Payment</button>
          </div>
        </form>
      </div>
    </section>

    <!-- Right: Info Panel -->
    <div class="flex flex-col justify-center items-center lg:w-1/2 p-6 mt-10 lg:mt-0">
      <div class="text-center space-y-4 max-w-md">
        <div class="w-full h-64 bg-white/5 rounded-xl flex items-center justify-center">
          <img src="img/hero-1.png" alt="Plan Image" class="w-full h-64 object-cover rounded-xl" />
        </div>
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars(ucfirst($plan), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="text-white/70">Get unlimited access to all gym facilities, classes, and exclusive perks with our
          <?php echo htmlspecialchars(ucfirst($plan), ENT_QUOTES, 'UTF-8'); ?> Plan.</p>
        <div class="mt-4 text-lg font-semibold">₱<?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?> / month</div>
      </div>
    </div>
  </main>

  <!-- Confirmation Modal -->
  <div id="confirmationModal" class="modal fixed inset-0 flex items-center justify-center bg-black/50 hidden" aria-hidden="true"
    role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmDesc">
    <div class="bg-[#111214] p-8 rounded-xl w-full max-w-md mx-auto text-center">
      <h3 id="confirmTitle" class="text-xl font-bold mb-4">Confirm Your Payment</h3>
      <p id="confirmDesc" class="text-white/60 text-sm mb-6">Please review your details before confirming.</p>
      <div class="bg-white/5 p-4 rounded-lg mb-6 text-sm space-y-3 text-left">
        <div class="flex justify-between"><span>Name</span><span id="confName"></span></div>
        <div class="flex justify-between"><span>Email</span><span id="confEmail"></span></div>
        <div class="flex justify-between"><span>Phone</span><span id="confPhone"></span></div>
        <div class="flex justify-between"><span>Payment</span><span>GCash</span></div>
        <div class="flex justify-between"><span>Plan</span><span><?php echo htmlspecialchars(ucfirst($plan), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="flex justify-between"><span>Amount</span><span>₱<?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?></span></div>
      </div>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeConfirm()"
          class="px-5 py-2 bg-white/10 rounded hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-primary">Back</button>
        <button type="button" onclick="startPayMongoPayment()"
          class="px-5 py-2 bg-primary rounded font-bold hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary">Confirm &amp; Pay</button>
      </div>
    </div>
  </div>

  <script>
    // Show/hide modal helper
    function toggleModal(show) {
      const modal = document.getElementById('confirmationModal');
      if (show) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
      } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
      }
    }

    // Validate form fields strictly
    function validateForm() {
      const form = document.getElementById('checkoutForm');
      const firstName = form.firstName.value.trim();
      const lastName = form.lastName.value.trim();
      const email = form.email.value.trim();
      const phone = form.phone.value.trim();

      if (!firstName || !lastName || !email || !phone) return false;

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) return false;

      return true;
    }

    // Masking functions for display
    function maskName(full) {
      const parts = full.split(' ').filter(Boolean);
      return parts.map(p => p.charAt(0) + '*'.repeat(p.length - 1)).join(' ');
    }

    function maskEmail(email) {
      const at = email.indexOf('@');
      if (at === -1) return email;
      const user = email.slice(0, at);
      const domain = email.slice(at);
      return user.charAt(0) + '***' + domain;
    }

    function maskPhone(phone) {
      const digits = phone.replace(/\D/g, '');
      if (digits.length <= 4) return phone;
      return digits.slice(0, 3) + ' **** ' + digits.slice(-2);
    }

    // Fill confirmation modal content
    function fillConfirmationModal() {
      const form = document.getElementById('checkoutForm');
      document.getElementById('confName').textContent = maskName(form.firstName.value.trim() + ' ' + form.lastName.value.trim());
      document.getElementById('confEmail').textContent = maskEmail(form.email.value.trim());
      document.getElementById('confPhone').textContent = maskPhone(form.phone.value.trim());
    }

    // Form submit event handler
    document.getElementById('checkoutForm').addEventListener('submit', function (e) {
      e.preventDefault();

      if (!validateForm()) {
        alert('Please fill out all required fields with valid information.');
        return;
      }

      fillConfirmationModal();
      toggleModal(true);
    });

    // Close confirmation modal
    function closeConfirm() {
      toggleModal(false);
    }

    // Placeholder function to start PayMongo payment flow
    function startPayMongoPayment() {
      toggleModal(false);

      // Here you would implement your PayMongo payment integration,
      // e.g., sending payment details to your backend to create payment intent,
      // then handle the response (redirect or display payment UI).

      // For demonstration, we'll just submit the form to backend for server-side processing:
      document.getElementById('checkoutForm').submit();
    }

    // Close modal on Escape key
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        closeConfirm();
      }
    });
  </script>
</body>

</html>