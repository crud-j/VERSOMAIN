
<!DOCTYPE html>
<html lang="en" class="scroll-smooth" >
<head>
<meta charset="utf-8" />
<meta content="width=device-width, initial-scale=1.0" name="viewport" />
<title>Verso Gym Customer Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&amp;display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<script src="https://cdn.tailwindcss.com?plugins=forms,typography,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: "#EF4444",
          "background-light": "#F9FAFB",
          "background-dark": "#111827",
          "card-light": "#FFFFFF",
          "card-dark": "#1F2937",
          "text-light": "#1F2937",
          "text-dark": "#F9FAFB",
          "subtext-light": "#6B7280",
          "subtext-dark": "#9CA3AF",
          "accent-gold": "#FFC107",
          "success": "#10B981",
          "warning": "#F59E0B"
        },
        fontFamily: {
          display: ["Roboto", "sans-serif"],
        },
        borderRadius: {
          DEFAULT: "0.5rem",
        },
      },
    },
  };
</script>
<style>
  .material-icons {
    font-size: 20px;
  }
  /* Scrollbar for tables with overflow */
  .overflow-y-auto::-webkit-scrollbar {
    width: 8px;
  }
  .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(239, 68, 68, 0.5);
    border-radius: 4px;
  }
  /* Toggle Switch */
  .switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 20px;
  }
  .switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 20px;
  }
  .slider:before {
    position: absolute;
    content: "";
    height: 12px;
    width: 12px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
  }
  input:checked + .slider {
    background-color: #EF4444;
  }
  input:checked + .slider:before {
    transform: translateX(14px);
  }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-light dark:text-text-dark transition-colors duration-300">
<div class="flex h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-card-light dark:bg-card-dark p-6 flex flex-col justify-between rounded-r-lg shadow-lg">
    <div>
      <div class="flex items-center gap-3 mb-10">
        <div class="w-12 h-12  flex items-center justify-center">
          <img src="img/logo.png" class="material-icons text-white text-3xl">
        </div>
        <h1 class="text-2xl font-bold">Verso Gym</h1>
      </div>
      <nav class="space-y-2">
        <button id="nav-dashboard" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg bg-primary text-white" type="button">
          <span class="material-icons">dashboard</span>
          <span>Dashboard</span>
        </button>
        <button id="nav-class-schedule" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">schedule</span>
          <span>Class Schedule</span>
        </button>
        <button id="nav-workout-history" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">history</span>
          <span>Workout History</span>
        </button>
        <button id="nav-billing" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">receipt_long</span>
          <span>Billing</span>
        </button>
        <button id="nav-profile" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">account_circle</span>
          <span>Profile</span>
        </button>
      </nav>
    </div>
    <div>
      <a class="flex items-center gap-3 px-4 py-2 rounded-lg text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20" href="#">
        <span class="material-icons">logout</span>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">

    <!-- Dashboard Section -->
    <section id="dashboard-section" class="">
      <header class="flex justify-between items-center mb-8">
        <div>
          <h2 class="text-3xl font-bold">Welcome, Sarah!</h2>
          <p class="text-subtext-light dark:text-subtext-dark">Here's an overview of your membership.</p>
        </div>
        <div class="flex items-center gap-4">
          <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" title="Notifications">
            <span class="material-icons">notifications</span>
          </button>
          <button id="dark-mode-toggle-dashboard" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" aria-label="Toggle dark mode">
            <span class="material-icons" id="theme-icon-dashboard">dark_mode</span>
          </button>
          <img alt="Sarah's profile picture" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCNh0Tu_dkJ_hitEMg1C6s-PZT6WEfd-2mhnWsiGI-rHrfH1TZKAT3rMvQ6j7ZUYxby1U3J29aQE7odDWbahvgVZ457k417JgVcgbvN8DdFIf9nsi_OaHLoSDTpiCn11XNZfDRO5Z55ViSnf8bRpdnt0OIcutOTxuNqdJjbqG6syCZtse_PGjLbB6GOl-s6MtHPGuqWpMDEMseubJ2dNi6D6LeiqaOdlz3zIwVSJYP2iNtcMAJ7o-XoCSEfC8gkiF4e61cBElAU4JR5"/>
        </div>
      </header>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="xl:col-span-2">
          <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-md relative overflow-hidden">
            <div class="absolute -top-16 -right-16 w-48 h-48 bg-primary/10 dark:bg-primary/20 rounded-full"></div>
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-xl font-bold text-primary">Premium Membership</h3>
                <span class="px-3 py-1 text-xs font-semibold text-white bg-accent-gold rounded-full">Active</span>
              </div>
              <p class="text-subtext-light dark:text-subtext-dark mb-6">Your access to all our premium facilities and classes.</p>
              <div class="flex items-end justify-between">
                <div>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Membership ID</p>
                  <p class="font-mono text-lg">VG-PM12345678</p>
                </div>
                <div>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark text-right">Expiration Date</p>
                  <p class="text-lg font-semibold">December 31, 2024</p>
                </div>
              </div>
              <div class="mt-6">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                  <div class="bg-primary h-2.5 rounded-full" style="width: 75%"></div>
                </div>
                <p class="text-sm text-right mt-2 text-subtext-light dark:text-subtext-dark">275 days remaining</p>
              </div>
              <div class="mt-6 text-right">
                <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                  Renew Membership
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="row-span-1 xl:row-span-2 bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold mb-4">Membership Benefits</h3>
          <ul class="space-y-4">
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">check_circle</span>
              <div>
                <p class="font-medium">Unlimited Gym Access</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">24/7 entry to all gym locations.</p>
              </div>
            </li>
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">group</span>
              <div>
                <p class="font-medium">All Group Classes</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">Yoga, Zumba, HIIT, and more.</p>
              </div>
            </li>
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">spa</span>
              <div>
                <p class="font-medium">Sauna &amp; Spa Access</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">Relax and recover after your workouts.</p>
              </div>
            </li>
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">sports_gymnastics</span>
              <div>
                <p class="font-medium">Personal Trainer Discount</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">15% off on all personal training packages.</p>
              </div>
            </li>
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">person_add</span>
              <div>
                <p class="font-medium">Guest Passes</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">2 free guest passes per month.</p>
              </div>
            </li>
            <li class="flex items-start gap-3">
              <span class="material-icons text-primary mt-1">shopping_bag</span>
              <div>
                <p class="font-medium">Merchandise Discount</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">10% off on all Verso Gym merchandise.</p>
              </div>
            </li>
          </ul>
        </div>
        <div class="xl:col-span-2">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
              <a class="p-4 bg-background-light dark:bg-background-dark rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" href="#">
                <span class="material-icons text-primary text-3xl">event</span>
                <p class="mt-2 text-sm font-medium">Book a Class</p>
              </a>
              <a class="p-4 bg-background-light dark:bg-background-dark rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" href="#">
                <span class="material-icons text-primary text-3xl">qr_code_scanner</span>
                <p class="mt-2 text-sm font-medium">Scan QR</p>
              </a>
              <a class="p-4 bg-background-light dark:bg-background-dark rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" href="#">
                <span class="material-icons text-primary text-3xl">refresh</span>
                <p class="mt-2 text-sm font-medium">Freeze Plan</p>
              </a>
              <a class="p-4 bg-background-light dark:bg-background-dark rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" href="#">
                <span class="material-icons text-primary text-3xl">upgrade</span>
                <p class="mt-2 text-sm font-medium">Upgrade Plan</p>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Class Schedule Section -->
    <section id="class-schedule-section" class="hidden">
      <header class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold">Class Schedule</h2>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <img alt="Alex Morgan" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCwtmCS7yS0nUbMbSKYUtlTqI9g0cd-BZPMgGAQfkNS2cB0gcn0uNl3Fu852_K1tscJXvgd3mVpCPAne9l669F1B-AaXoMvlnVIRnFps4rHAiBbODnTzKD4_mKQTCsZCZVbT6cjuHc5HcCwabZxuxWNiFyLvHqjx6Ij7VwE87H_NCfDJJ6I1b3H5VFUNo_KAGl0vacgthJ8yqNkvmFKGxfspOiP8Qx4m1edpYhBz2upiVgyzGgg-hBNuSOHgqf3Or2P2p0LGpSxdVEM"/>
            <div>
              <p class="font-semibold">Alex Morgan</p>
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Member</p>
            </div>
          </div>
          <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" title="Notifications">
            <span class="material-icons">notifications</span>
          </button>
          <button id="dark-mode-toggle-schedule" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" aria-label="Toggle dark mode">
            <span class="material-icons dark-icon">dark_mode</span>
            <span class="material-icons light-icon" style="display:none;">light_mode</span>
          </button>
        </div>
      </header>

      <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
          <div class="flex items-center gap-4">
            <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" id="prev-month-btn" title="Previous Month">
              <span class="material-icons">chevron_left</span>
            </button>
            <h3 class="text-xl font-semibold" id="current-month-year">October 2023</h3>
            <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" id="next-month-btn" title="Next Month">
              <span class="material-icons">chevron_right</span>
            </button>
          </div>
          <div class="flex items-center gap-2">
            <button id="today-btn" class="px-4 py-2 text-sm font-medium rounded-lg bg-primary text-white">Today</button>
            <input id="date-picker" class="w-40 bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" type="date" />
          </div>
        </div>

        <div class="grid grid-cols-7 gap-px text-center bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Sun</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Mon</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Tue</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Wed</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Thu</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Fri</div>
          <div class="py-2 font-semibold text-subtext-light dark:text-subtext-dark bg-card-light dark:bg-card-dark">Sat</div>
        </div>

        <div id="calendar-grid" class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 rounded-b-lg">
          <!-- Days will be rendered here by JS -->
        </div>
      </div>

      <div class="mt-8">
        <h3 class="text-2xl font-bold mb-4">Upcoming Booked Classes</h3>
        <div class="space-y-4" id="upcoming-classes-list">
          <!-- Upcoming classes rendered here by JS -->
        </div>
      </div>
    </section>

    <!-- Workout History Section -->
    <section id="workout-history-section" class="hidden">
      <header class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold">Workout History</h2>
        <div class="flex items-center gap-4">
          <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" title="Notifications">
            <span class="material-icons">notifications</span>
          </button>
          <img alt="Sarah's profile picture" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCNh0Tu_dkJ_hitEMg1C6s-PZT6WEfd-2mhnWsiGI-rHrfH1TZKAT3rMvQ6j7ZUYxby1U3J29aQE7odDWbahvgVZ457k417JgVcgbvN8DdFIf9nsi_OaHLoSDTpiCn11XNZfDRO5Z55ViSnf8bRpdnt0OIcutOTxuNqdJjbqG6syCZtse_PGjLbB6GOl-s6MtHPGuqWpMDEMseubJ2dNi6D6LeiqaOdlz3zIwVSJYP2iNtcMAJ7o-XoCSEfC8gkiF4e61cBElAU4JR5"/>
        </div>
      </header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-4">Weekly Progress</h3>
            <div class="h-80">
              <canvas id="progressChart"></canvas>
            </div>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-semibold">Workout History</h3>
              <a class="text-primary font-medium hover:underline" href="#">View All</a>
            </div>
            <div class="space-y-4" id="workout-history-list">
              <!-- Workout history items rendered here by JS -->
            </div>
          </div>
        </div>
        <div class="space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md text-center">
            <h3 class="text-xl font-semibold mb-4">Today's Goal</h3>
            <div class="relative w-40 h-40 mx-auto">
              <svg class="w-full h-full" viewBox="0 0 36 36">
                <path class="text-gray-200 dark:text-gray-700" d="M18 2.0845
                                      a 15.9155 15.9155 0 0 1 0 31.831
                                      a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3"></path>
                <path class="text-primary" d="M18 2.0845
                                      a 15.9155 15.9155 0 0 1 0 31.831
                                      a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-dasharray="75, 100" stroke-linecap="round" stroke-width="3"></path>
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold">75%</span>
                <span class="text-subtext-light dark:text-subtext-dark">Complete</span>
              </div>
            </div>
            <p class="mt-4 text-subtext-light dark:text-subtext-dark">You've burned <span class="font-bold text-text-light dark:text-text-dark">450</span> out of <span class="font-bold text-text-light dark:text-text-dark">600</span> calories.</p>
            <button class="mt-4 w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              Log a Workout
            </button>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-4">Key Metrics</h3>
            <div class="space-y-4">
              <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
                  <span class="material-icons text-primary">local_fire_department</span>
                </div>
                <div class="ml-4">
                  <p class="font-semibold">Calories Burned</p>
                  <p class="text-lg font-bold">12,540 <span class="text-sm font-normal text-subtext-light dark:text-subtext-dark">kcal</span></p>
                </div>
              </div>
              <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                  <span class="material-icons text-blue-500">directions_walk</span>
                </div>
                <div class="ml-4">
                  <p class="font-semibold">Distance</p>
                  <p class="text-lg font-bold">88.5 <span class="text-sm font-normal text-subtext-light dark:text-subtext-dark">km</span></p>
                </div>
              </div>
              <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                  <span class="material-icons text-green-500">timer</span>
                </div>
                <div class="ml-4">
                  <p class="font-semibold">Duration</p>
                  <p class="text-lg font-bold">25 <span class="text-sm font-normal text-subtext-light dark:text-subtext-dark">hours</span></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Billing Section -->
    <section id="billing-section" class="hidden">
      <header class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold">Billing Information</h2>
        <div class="flex items-center gap-4">
          <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" title="Notifications">
            <span class="material-icons">notifications</span>
          </button>
          <img alt="User Avatar" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/a-/ALV-UjX8bB0x-hcatEx3tW2iXj2oX_i9-g_8aY_4m2bY8F4lJQ=s96-c"/>
        </div>
      </header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">Payment Methods</h3>
              <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-red-600 text-sm font-medium flex items-center gap-2">
                <span class="material-icons" style="font-size: 18px;">add</span> Add New Method
              </button>
            </div>
            <div class="space-y-4">
              <div class="flex justify-between items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex items-center gap-4">
                  <img alt="Gcash" class="w-12" src="https://upload.wikimedia.org/wikipedia/commons/6/6f/Gcash_Logo.png"/>
                  <div>
                    <p class="font-semibold">Gcash</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Primary</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-semibold bg-success text-success/20 dark:bg-green-900/50 dark:text-green-300 px-2 py-1 rounded-full">Primary</span>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-text-light dark:hover:text-text-dark" title="Edit">
                    <span class="material-icons">edit</span>
                  </button>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-red-500" title="Delete">
                    <span class="material-icons">delete</span>
                  </button>
                </div>
              </div>
              <div class="flex justify-between items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex items-center gap-4">
                  <img alt="Paymaya" class="w-12" src="https://upload.wikimedia.org/wikipedia/commons/e/e7/PayMaya_Logo.png"/>
                  <div>
                    <p class="font-semibold">Paymaya</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Expires 08/24</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button class="text-primary text-sm font-medium hover:underline">Set as Primary</button>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-text-light dark:hover:text-text-dark" title="Edit">
                    <span class="material-icons">edit</span>
                  </button>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-red-500" title="Delete">
                    <span class="material-icons">delete</span>
                  </button>
                </div>
              </div>
              <div class="flex justify-between items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex items-center gap-4">
                  <img alt="Card" class="w-12" src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png"/>
                  <div>
                    <p class="font-semibold">Card **** 5678</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">Expires 12/25</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button class="text-primary text-sm font-medium hover:underline">Set as Primary</button>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-text-light dark:hover:text-text-dark" title="Edit">
                    <span class="material-icons">edit</span>
                  </button>
                  <button class="text-subtext-light dark:text-subtext-dark hover:text-red-500" title="Delete">
                    <span class="material-icons">delete</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Past Invoices</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="border-b dark:border-gray-700">
                    <th class="py-3 px-4 font-semibold text-sm">Date</th>
                    <th class="py-3 px-4 font-semibold text-sm">Amount</th>
                    <th class="py-3 px-4 font-semibold text-sm">Status</th>
                    <th class="py-3 px-4 font-semibold text-sm"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="border-b dark:border-gray-700">
                    <td class="py-3 px-4">May 1, 2024</td>
                    <td class="py-3 px-4">₱2,600.00</td>
                    <td class="py-3 px-4"><span class="text-xs font-semibold bg-success/20 text-success px-2 py-1 rounded-full">Paid</span></td>
                  </tr>
                  <tr class="border-b dark:border-gray-700">
                    <td class="py-3 px-4">April 1, 2024</td>
                    <td class="py-3 px-4">₱2,600.00</td>
                    <td class="py-3 px-4"><span class="text-xs font-semibold bg-success/20 text-success px-2 py-1 rounded-full">Paid</span></td>
                  </tr>
                  <tr>
                    <td class="py-3 px-4">March 1, 2024</td>
                    <td class="py-3 px-4">₱2,600.00</td>
                    <td class="py-3 px-4"><span class="text-xs font-semibold bg-success/20 text-success px-2 py-1 rounded-full">Paid</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Subscription</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">Your Plan</p>
                <p class="font-semibold text-xl">Premium</p>
              </div>
              <div>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">Next Payment</p>
                <p class="font-semibold text-xl">₱2,600.00</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">on June 1, 2024</p>
              </div>
            </div>
            <button class="mt-6 w-full bg-primary text-white py-2 rounded-lg hover:bg-red-600">Upgrade Plan</button>
            <button class="mt-2 w-full text-subtext-light dark:text-subtext-dark hover:text-primary py-2 rounded-lg">Cancel Subscription</button>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Upcoming Payments</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Premium Membership</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Due June 1, 2024</p>
                </div>
                <p class="font-semibold">₱2,600.00</p>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Personal Training Session</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Due May 15, 2024</p>
                </div>
                <p class="font-semibold">₱3,900.00</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Profile Section -->
    <section id="profile-section" class="hidden">
      <header class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold">Profile Settings</h2>
        <div class="flex items-center gap-4">
          <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" title="Notifications">
            <span class="material-icons text-subtext-light dark:text-subtext-dark">notifications</span>
          </button>
          <button id="dark-mode-toggle-profile" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700" aria-label="Toggle dark mode">
            <span class="material-icons text-subtext-light dark:text-subtext-dark">dark_mode</span>
          </button>
          <img alt="Jessica Miller" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAjSdxuZp5753kQ-lPlmfI2KcjqpjeACy8AtYgmy7PoxZ5_WrbgTil-jn9cciaTmw8JKNVYQE8DcTctkdvOkQ8R2Z74RdcCXDLsjhAE5wVSlrjKJu7kGwMY9vx6FkVOU0RWW7KKbVm9ycbPnsrX4v4foea5S1dok1hju2Zid5hqVtU3l84SrXszjroSNCD9ktodjrV3iBiZarJCIXm-iR5h3nBqEwfcU6C7QuaC3A0F7gtleW6d4gG6vamuCbYE1btPy0poLarxeoSR"/>
        </div>
      </header>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex flex-col items-center text-center shadow-md">
            <div class="relative">
              <img alt="Jessica Miller" class="w-32 h-32 rounded-full mb-4 ring-4 ring-offset-2 ring-offset-card-light dark:ring-offset-card-dark ring-primary" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBHtQ9lHF5AuwCSuCScOGv2_1cYOSyiJIZbVGYOUJ45x9oWqDyz5rRc9YcqzrJ0HDA5sHBCtyr0RPmADQc-CfeX2p304co6BTopkdenK0v2POvMs-196YYub_I1IYj8XiMRzRqA2cnRWBTg3wEMDS7JYqkUgv85IFlcygpkboIrOcCTqS9G39wRWcOznfueQ0YTr9wKS6nfJctMhBYnswgtP-elfx1REmseSgYoDDQL-7D39uPQUgn1jS6Q3xOuAxI5T2lyBCoGr06O"/>
              <button class="absolute bottom-2 right-2 bg-primary text-white p-2 rounded-full hover:bg-red-600" title="Change Picture">
                <span class="material-icons" style="font-size: 20px;">edit</span>
              </button>
            </div>
            <h3 class="text-2xl font-semibold mt-4">Jessica Miller</h3>
            <p class="text-subtext-light dark:text-subtext-dark">Fitness Enthusiast</p>
          </div>
        </div>
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-6">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="full-name">Full Name</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="full-name" type="text" value="Jessica Miller"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="email">Email Address</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="email" type="email" value="jessica.m@email.com"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="phone">Phone Number</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="phone" type="tel" value="+1 234 567 890"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="dob">Date of Birth</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="dob" type="text" value="August 15, 1990"/>
              </div>
            </div>
            <div class="mt-8 text-right">
              <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card-dark focus:ring-primary">
                Save Changes
              </button>
            </div>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-6">Change Password</h3>
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="current-password">Current Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="current-password" placeholder="••••••••" type="password"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="new-password">New Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="new-password" placeholder="••••••••" type="password"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="confirm-password">Confirm New Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="confirm-password" placeholder="••••••••" type="password"/>
              </div>
            </div>
            <div class="mt-8 text-right">
              <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card-dark focus:ring-primary">
                Update Password
              </button>
            </div>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-8 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-6">Privacy Settings</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Show my profile to other members</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Your profile will be visible in the community directory.</p>
                </div>
                <label class="switch">
                  <input checked type="checkbox" />
                  <span class="slider"></span>
                </label>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Show my workout activity</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Allow others to see your recent workouts and progress.</p>
                </div>
                <label class="switch">
                  <input type="checkbox" />
                  <span class="slider"></span>
                </label>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Receive promotional emails</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Get updates on new classes, offers, and events.</p>
                </div>
                <label class="switch">
                  <input checked type="checkbox" />
                  <span class="slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>
</div>

<script>
  // Manage dark mode toggle icons and state for each section toggle button
  function setupDarkModeToggle(buttonId, iconIdOrIcons) {
    const toggleBtn = document.getElementById(buttonId);
    if (!toggleBtn) return;
    if (typeof iconIdOrIcons === 'string') {
      const icon = document.getElementById(iconIdOrIcons);
      toggleBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        if (document.documentElement.classList.contains('dark')) {
          icon.textContent = 'light_mode';
        } else {
          icon.textContent = 'dark_mode';
        }
      });
      // Init icon on load
      if (document.documentElement.classList.contains('dark')) {
        icon.textContent = 'light_mode';
      } else {
        icon.textContent = 'dark_mode';
      }
    } else if (typeof iconIdOrIcons === 'object') {
      const darkIcon = toggleBtn.querySelector('.dark-icon');
      const lightIcon = toggleBtn.querySelector('.light-icon');
      toggleBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        darkIcon.style.display = isDark ? 'none' : 'block';
        lightIcon.style.display = isDark ? 'block' : 'none';
      });
      // Init icons on load
      const isDark = document.documentElement.classList.contains('dark');
      darkIcon.style.display = isDark ? 'none' : 'block';
      lightIcon.style.display = isDark ? 'block' : 'none';
    }
  }

  setupDarkModeToggle('dark-mode-toggle-dashboard', 'theme-icon-dashboard');
  setupDarkModeToggle('dark-mode-toggle-schedule', {darkIcon: '.dark-icon', lightIcon: '.light-icon'});
  setupDarkModeToggle('dark-mode-toggle-profile', 'dark-mode-toggle-profile');

  // Navigation buttons and sections
  const sections = {
    dashboard: document.getElementById('dashboard-section'),
    classSchedule: document.getElementById('class-schedule-section'),
    workoutHistory: document.getElementById('workout-history-section'),
    billing: document.getElementById('billing-section'),
    profile: document.getElementById('profile-section'),
  };

  const navButtons = {
    dashboard: document.getElementById('nav-dashboard'),
    classSchedule: document.getElementById('nav-class-schedule'),
    workoutHistory: document.getElementById('nav-workout-history'),
    billing: document.getElementById('nav-billing'),
    profile: document.getElementById('nav-profile'),
  };

  function setActiveNav(activeKey) {
    Object.entries(navButtons).forEach(([key, btn]) => {
      if (!btn) return;
      if (key === activeKey) {
        btn.classList.add('bg-primary', 'text-white');
        btn.classList.remove('hover:bg-gray-200', 'dark:hover:bg-gray-700');
      } else {
        btn.classList.remove('bg-primary', 'text-white');
        btn.classList.add('hover:bg-gray-200', 'dark:hover:bg-gray-700');
      }
    });
  }

  function showSection(key) {
    Object.entries(sections).forEach(([k, section]) => {
      if (!section) return;
      if (k === key) {
        section.classList.remove('hidden');
      } else {
        section.classList.add('hidden');
      }
    });
  }

  Object.entries(navButtons).forEach(([key, btn]) => {
    if (!btn) return;
    btn.addEventListener('click', () => {
      showSection(key);
      setActiveNav(key);
    });
  });

  // Initialize with Dashboard visible
  showSection('dashboard');
  setActiveNav('dashboard');

  // Calendar rendering and navigation for Class Schedule
  const calendarGrid = document.getElementById('calendar-grid');
  const currentMonthYear = document.getElementById('current-month-year');
  const datePicker = document.getElementById('date-picker');
  const prevMonthBtn = document.getElementById('prev-month-btn');
  const nextMonthBtn = document.getElementById('next-month-btn');
  const todayBtn = document.getElementById('today-btn');
  const upcomingClassesList = document.getElementById('upcoming-classes-list');

  let currentDate = new Date();

  // Sample booked classes data for calendar (date, events)
  const bookedClasses = {
    "2023-10-03": [{ type: 'Yoga', time: '9am', colorClass: 'bg-green-200 text-green-800' }],
    "2023-10-09": [{ type: 'HIIT', time: '6pm', colorClass: 'bg-blue-200 text-blue-800' }],
    "2023-10-18": [{ type: 'Yoga', time: '9am', colorClass: 'bg-green-200 text-green-800' }, { type: 'Spin', time: '7am', colorClass: 'bg-yellow-200 text-yellow-800' }],
  };

  const upcomingClassesData = [
    { name: "Yoga Flow", instructor: "Jessica Miles", datetime: new Date(2023, 9, 18, 9, 0) },
    { name: "HIIT Blast", instructor: "Mark Johnson", datetime: new Date(2023, 9, 20, 18, 0) },
    { name: "Spin Revolution", instructor: "Sarah Chen", datetime: new Date(2023, 9, 23, 7, 0) },
  ];

  function renderCalendar(date) {
    calendarGrid.innerHTML = "";
    const year = date.getFullYear();
    const month = date.getMonth();

    currentMonthYear.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const startDayOfWeek = firstDay.getDay(); // Sunday=0
    const totalDays = lastDay.getDate();

    // Previous month's last days to fill before first day
    const prevMonthLastDay = new Date(year, month, 0).getDate();

    // Fill days from previous month if startDayOfWeek>0
    for(let i = startDayOfWeek - 1; i >= 0; i--) {
      const dayNum = prevMonthLastDay - i;
      const div = document.createElement('div');
      div.className = "p-2 h-32 bg-card-light dark:bg-card-dark text-subtext-light dark:text-subtext-dark select-none";
      div.textContent = dayNum;
      calendarGrid.appendChild(div);
    }

    // Fill current month days
    for(let day = 1; day <= totalDays; day++) {
      const div = document.createElement('div');
      div.className = "p-2 h-32 bg-card-light dark:bg-card-dark relative";
      const dateStr = `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
      div.innerHTML = `<span class="font-bold">${day}</span>`;

      if (bookedClasses[dateStr]) {
        bookedClasses[dateStr].forEach(event => {
          const eventDiv = document.createElement('div');
          eventDiv.className = `absolute bottom-2 left-2 right-2 text-xs p-1 rounded ${event.colorClass}`;
          eventDiv.textContent = `${event.type} @ ${event.time}`;
          div.appendChild(eventDiv);
        });
      }
      calendarGrid.appendChild(div);
    }

    // Fill days after month end for complete week
    const totalGridItems = calendarGrid.childElementCount;
    const remainder = totalGridItems % 7;
    if (remainder !== 0) {
      const fillDays = 7 - remainder;
      for(let i = 1; i <= fillDays; i++) {
        const div = document.createElement('div');
        div.className = "p-2 h-32 bg-card-light dark:bg-card-dark text-subtext-light dark:text-subtext-dark select-none";
        div.textContent = i;
        calendarGrid.appendChild(div);
      }
    }
  }

  // Render upcoming classes list
  function renderUpcomingClasses() {
    upcomingClassesList.innerHTML = "";
    upcomingClassesData.forEach(c => {
      const dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
      const timeOptions = { hour: 'numeric', minute: 'numeric', hour12: true };
      const dateStr = c.datetime.toLocaleDateString(undefined, dateOptions);
      const timeStr = c.datetime.toLocaleTimeString(undefined, timeOptions);
      const div = document.createElement('div');
      div.className = "bg-card-light dark:bg-card-dark p-4 rounded-lg flex items-center justify-between shadow-sm";
      div.innerHTML = `
        <div>
          <p class="font-semibold text-lg">${c.name}</p>
          <p class="text-sm text-subtext-light dark:text-subtext-dark">with ${c.instructor}</p>
          <p class="text-sm text-subtext-light dark:text-subtext-dark">${dateStr} @ ${timeStr}</p>
        </div>
        <button class="px-4 py-2 text-sm font-medium rounded-lg border border-red-500 text-red-500 hover:bg-red-500 hover:text-white transition-colors cancel-class-btn">Cancel</button>
      `;
      upcomingClassesList.appendChild(div);
    });
  }

  prevMonthBtn.addEventListener('click', () => {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
    renderCalendar(currentDate);
  });

  nextMonthBtn.addEventListener('click', () => {
    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
    renderCalendar(currentDate);
  });

  todayBtn.addEventListener('click', () => {
    currentDate = new Date();
    renderCalendar(currentDate);
    datePicker.value = '';
  });

  datePicker.addEventListener('change', (e) => {
    if (e.target.value) {
      currentDate = new Date(e.target.value);
      renderCalendar(currentDate);
    }
  });

  // Cancel class buttons in upcoming classes list
  upcomingClassesList.addEventListener('click', e => {
    if (e.target.classList.contains('cancel-class-btn')) {
      const classDiv = e.target.closest('div.bg-card-light');
      if (classDiv) {
        classDiv.remove();
      }
    }
  });

  renderCalendar(currentDate);
  renderUpcomingClasses();

  // Workout History Data
  const workoutHistoryList = document.getElementById('workout-history-list');
  const workouts = [
    { title: "Treadmill Run", datetime: "Today, 8:15 AM", icon: "directions_run", iconColor: "text-primary", bgColor: "bg-red-100 dark:bg-red-900/30", distance: "5.2 km", calories: "450 kcal" },
    { title: "Full Body Strength", datetime: "Yesterday, 6:30 PM", icon: "fitness_center", iconColor: "text-blue-500", bgColor: "bg-blue-100 dark:bg-blue-900/30", duration: "60 min", calories: "380 kcal" },
    { title: "Yoga Session", datetime: "June 12, 7:00 AM", icon: "self_improvement", iconColor: "text-green-500", bgColor: "bg-green-100 dark:bg-green-900/30", duration: "45 min", calories: "150 kcal" },
  ];

  function renderWorkoutHistory() {
    workoutHistoryList.innerHTML = "";
    workouts.forEach(w => {
      const div = document.createElement('div');
      div.className = "flex items-center justify-between p-4 bg-background-light dark:bg-background-dark rounded-lg";
      div.innerHTML = `
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full ${w.bgColor} flex items-center justify-center">
            <span class="material-icons ${w.iconColor}">${w.icon}</span>
          </div>
          <div>
            <p class="font-semibold">${w.title}</p>
            <p class="text-sm text-subtext-light dark:text-subtext-dark">${w.datetime}</p>
          </div>
        </div>
        <div class="text-right">
          <p class="font-semibold">${w.distance ? w.distance : w.duration}</p>
          <p class="text-sm text-subtext-light dark:text-subtext-dark">${w.calories}</p>
        </div>
      `;
      workoutHistoryList.appendChild(div);
    });
  }

  renderWorkoutHistory();

  // Chart.js for Weekly Progress
  const ctx = document.getElementById('progressChart').getContext('2d');
  const progressChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      datasets: [{
        label: 'Calories Burned',
        data: [500, 650, 480, 720, 600, 800, 450],
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        borderColor: '#EF4444',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#EF4444',
        pointBorderColor: '#FFFFFF',
        pointHoverBackgroundColor: '#FFFFFF',
        pointHoverBorderColor: '#EF4444',
        pointRadius: 5,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(107, 114, 128, 0.1)'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: '#1F2937',
          titleFont: { size: 14},
          bodyFont: { size: 12 },
          padding: 12,
          cornerRadius: 8,
          displayColors: false
        }
      }
    }
  });
</script>
</body>
</html>