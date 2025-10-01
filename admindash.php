

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="utf-8" />
<meta content="width=device-width, initial-scale=1.0" name="viewport" />
<title>Verso Gym Admin Dashboard</title>
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
  <aside class="w-64 bg-card-light dark:bg-card-dark p-6 flex flex-col justify-between rounded-r-lg">
    <div>
      <div class="flex items-center gap-3 mb-10">
        <div class="w-10 h-10  flex items-center justify-center">
          <img src="img/logo.png" alt="verso logo" class="material-icons text-white"></img>
        </div>
        <h1 class="text-xl font-bold">Verso Gym</h1>
      </div>
      <nav class="space-y-2">
        <button id="nav-dashboard" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">dashboard</span>
          <span>Dashboard</span>
        </button>
        <button id="nav-analytics" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">analytics</span>
          <span>Analytics</span>
        </button>
        <button id="nav-login-history" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">history</span>
          <span>Login History</span>
        </button>
        <button id="nav-members" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">people</span>
          <span>Members</span>
        </button>
        <button id="nav-billing" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">receipt_long</span>
          <span>Billing</span>
        </button>
        <button id="nav-trainers" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">sports_gymnastics</span>
          <span>Trainers</span>
        </button>
        <button id="nav-notifications" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">notifications</span>
          <span>Notification</span>
        </button>
        <button id="nav-profile" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700" type="button">
          <span class="material-icons">account_circle</span>
          <span>Profile Settings</span>
        </button>
        <button id="nav-logout" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20" type="button">
          <span class="material-icons">logout</span>
          <span>Logout</span>
        </button>
      </nav>
    </div>
    <div>
      <a class="flex items-center gap-3 px-4 py-2 rounded-lg text-subtext-light dark:text-subtext-dark hover:bg-gray-200 dark:hover:bg-gray-700" href="#">
        <span class="material-icons">web</span>
        <span>Main Index</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8 overflow-y-auto">
    <!-- Header with dark mode toggle and branch select -->
    <header class="flex justify-between items-center mb-8">
      <h2 id="page-title" class="text-2xl font-bold">Dashboard</h2>
      <div class="flex items-center gap-4">
        <div class="relative">
        </div>
        <button id="toggle-theme-btn" class="p-2 rounded-full bg-card-light dark:bg-card-dark" aria-label="Toggle dark mode">
          <span class="material-icons" id="theme-icon">dark_mode</span>
        </button>
      </div>
    </header>

    <!-- Dashboard Section -->
    <section id="dashboard-section" class="space-y-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex items-center gap-4">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-full">
            <span class="material-icons text-blue-500">receipt</span>
          </div>
          <div>
            <p class="text-subtext-light dark:text-subtext-dark">Total Invoices</p>
            <p class="text-2xl font-bold">1,250</p>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex items-center gap-4">
          <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-full">
            <span class="material-icons text-green-500">check_circle</span>
          </div>
          <div>
            <p class="text-subtext-light dark:text-subtext-dark">Paid</p>
            <p class="text-2xl font-bold">1,100</p>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex items-center gap-4">
          <div class="p-3 bg-red-100 dark:bg-red-900/40 rounded-full">
            <span class="material-icons text-red-500">cancel</span>
          </div>
          <div>
            <p class="text-subtext-light dark:text-subtext-dark">Unpaid</p>
            <p class="text-2xl font-bold">150</p>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex items-center gap-4">
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/40 rounded-full">
            <span class="material-icons text-yellow-500">hourglass_empty</span>
          </div>
          <div>
            <p class="text-subtext-light dark:text-subtext-dark">Overdue</p>
            <p class="text-2xl font-bold">25</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Billing Section (new separate section) -->
    <section id="billing-section" class="hidden">
      <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Invoices</h3>
          <div class="flex items-center gap-4">
            <div class="relative">
              <input id="billing-search" class="pl-10 pr-4 py-2 w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Search..." type="text"/>
              <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
            </div>
            <select id="billing-status-filter" class="px-3 py-2 text-sm bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
              <option>All Status</option>
              <option>Paid</option>
              <option>Unpaid</option>
              <option>Overdue</option>
            </select>
            <button id="billing-export-btn" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-red-600">
              <span class="material-icons">download</span>
              <span>Export Data</span>
            </button>
          </div>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto rounded-md shadow-inner">
          <table class="w-full text-left">
            <thead class="border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-card-light dark:bg-card-dark">
              <tr>
                <th class="py-3 px-4 font-semibold text-sm">Invoice ID</th>
                <th class="py-3 px-4 font-semibold text-sm">Member</th>
                <th class="py-3 px-4 font-semibold text-sm">Date</th>
                <th class="py-3 px-4 font-semibold text-sm">Amount</th>
                <th class="py-3 px-4 font-semibold text-sm">Status</th>
                <th class="py-3 px-4 font-semibold text-sm text-center">Action</th>
              </tr>
            </thead>
            <tbody id="billing-tbody" class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-4">#INV001</td>
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Alex Johnson" class="w-8 h-8 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABMnteW7Kiuj3p9Ego2-hjRM-_T0tC1qRq0TRvCGMg_MKyqNkOZAnwsy-KtZd5T5oTrSx0sPWma99mXl5ysewR2BYKWBSMlPHEtBp0OKpYY9bsI5L4Q53EuTdxae6tyJ_1q1WDCQWWAvOhRb-9_hfgAHcuVUNFaXhMbhrfDYN4qu9mMlWQy83OGXWyf2HTrXTBomEQyqXFLuAr2NbC-F3CKk8-yghgVeucuSwgaHRmI2AGV3Hx8E4JtmcTsDKH9LpoqBdXVgoyloQc"/>
                  Alex Johnson
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2023-10-26</td>
                <td class="py-3 px-4">₠59.99</td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Paid</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button class="p-1 text-subtext-light dark:text-subtext-dark hover:text-primary" title="More Options">
                    <span class="material-icons">more_horiz</span>
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-4">#INV002</td>
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Jane Smith" class="w-8 h-8 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCSzGQDV8x3sgn8kPuBmLdLfyZ7eX8vUQZHDgdaZ1Q8LX2Iu46Z0cPs_eKlTbYwlt6iGoSxnP2Gy9TThX9-b7gnrF18j-wAPjR5er9hXwwV54k5T-n0IN5k3dAwj6MqHYHaBtZd0J9X6ekvyK_poDknAT6fJTjVs35Eh1wrkx-cGL98lVt9TpTMhJUS5YPAFUOoHT2GPW8J9cqZ_Cnpk1-YeCFb-3LFzDEbH7kEy-b55bd_Ngfo3MpdTDjvpQ83lVFq1n9U9VUocJWL"/>
                  Jane Smith
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2023-10-25</td>
                <td class="py-3 px-4">79.99</td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Unpaid</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button class="p-1 text-subtext-light dark:text-subtext-dark hover:text-primary" title="More Options">
                    <span class="material-icons">more_horiz</span>
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-4">#INV003</td>
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Mike Ross" class="w-8 h-8 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC3vis-j1POiu9WorycNAnzQ5eoupaI73JtveQU6EXHmWK0ySCUX6k83Qm0N1j2McUV4ShSle4kd0MPLYuE5OGB-Ji1uQPpzdFENoD1Cm53VzeX5RmNrb35blPYw0erWPq14rxl1J7W4V7w8LmODYuR6HsqyEKhwifDJ_IIGvknZ7RKj7mkDjYxYY-pJS2vbSmeyZg_GatMHF1FLoXxSvNjrYq2GEWTR9AWScFUvIYwTfyY39hdjFm64BN-NqAxazNlfhOoHPkrBoH1"/>
                  Mike Ross
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2023-09-15</td>
                <td class="py-3 px-4">₠59.99</td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full">Overdue</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button class="p-1 text-subtext-light dark:text-subtext-dark hover:text-primary" title="More Options">
                    <span class="material-icons">more_horiz</span>
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-4">#INV004</td>
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Sara Lee" class="w-8 h-8 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDDNrrd5gu2-GyWPt4xQap4WT4GUQogLB5xcDfbww6g8l-65ei60PLXxFgkElAgEgFJxbtItWqYJdqtLOwg0NSaeebzJ2i9k-k8cKmh8sWLRviEGUQzD1lFsbLXzlIZn5Zoax9eAnQuXsqijXlgetXELpjg7znjp3TA4KNr2VY9weulyr9hS4JaYKqJ7AW-pnSTfEziKnGz7803V4nC4gYXryYmNZv4rAx402l5mzJzCxLZgFUHMDexnCSy2APYvSV7OR3GepwCQwS9"/>
                  Sara Lee
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2023-10-22</td>
                <td class="py-3 px-4">₠99.99</td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Paid</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button class="p-1 text-subtext-light dark:text-subtext-dark hover:text-primary" title="More Options">
                    <span class="material-icons">more_horiz</span>
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-100 dark:hover:bg-gray-800">
                <td class="py-3 px-4">#INV005</td>
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Peter Pan" class="w-8 h-8 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUve3oqgqsnoBEKa4AvIGsdFDvuuO3HDMbe6TTOZnrlQT7D1_w6O-699bQtsx89IjskrpahCoRWb1JBvwMrm1GYdqiAdP9wK5FDPL20PGxmzJn_8ZCS0omwprBf2vvGYgKo5p2T-n5srOpbJgtpniWLh01qHtfymUcDEXm9wdso0s2mE6V265qEcM0t1PhHVMGjqu1qdb9Iyn7H5AKhDqQB8vlm3cbgJu4ft133CUKKscxDsHegzUdC6rNOoxaB-ejlKZKrQp1l69u"/>
                  Peter Pan
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2023-10-20</td>
                <td class="py-3 px-4">₠59.99</td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Paid</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button class="p-1 text-subtext-light dark:text-subtext-dark hover:text-primary" title="More Options">
                    <span class="material-icons">more_horiz</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Analytics Content -->
    <section id="analytics-section" class="hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Member Sign-ups</h3>
            <div class="flex items-center gap-2">
              <select id="memberSignupsFilter" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none">
                <option>Monthly</option>
                <option>Yearly</option>
              </select>
            </div>
          </div>
          <div class="h-64 relative">
            <canvas id="memberSignupsChart"></canvas>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Class Attendance</h3>
            <div class="flex items-center gap-2">
              <select id="classAttendanceFilter" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none">
                <option>Weekly</option>
                <option>Monthly</option>
              </select>
            </div>
          </div>
          <div class="h-64 relative">
            <canvas id="classAttendanceChart"></canvas>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg col-span-1 lg:col-span-2">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Revenue</h3>
            <div class="flex items-center gap-2">
              <select id="revenueFilter" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none">
                <option>Monthly</option>
                <option>Quarterly</option>
                <option>Yearly</option>
              </select>
            </div>
          </div>
          <div class="h-80 relative">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>
    </section>

    <!-- Login History Content -->
    <section id="login-history-section" class="hidden">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg lg:col-span-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Login History</h3>
            <div class="relative">
              <input id="login-history-search" class="pl-10 pr-4 py-2 w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Search user..." type="text" />
              <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
            </div>
          </div>
          <div class="overflow-x-auto max-h-80 overflow-y-auto rounded-md shadow-inner">
            <table class="w-full text-left">
              <thead class="border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-card-light dark:bg-card-dark">
                <tr>
                  <th class="py-3 px-4 font-semibold text-sm text-subtext-light dark:text-subtext-dark">USER</th>
                  <th class="py-3 px-4 font-semibold text-sm text-subtext-light dark:text-subtext-dark">TIMESTAMP</th>
                </tr>
              </thead>
              <tbody id="login-history-tbody">
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="Alex Johnson" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABMnteW7Kiuj3p9Ego2-hjRM-_T0tC1qRq0TRvCGMg_MKyqNkOZAnwsy-KtZd5T5oTrSx0sPWma99mXl5ysewR2BYKWBSMlPHEtBp0OKpYY9bsI5L4Q53EuTdxae6tyJ_1q1WDCQWWAvOhRb-9_hfgAHcuVUNFaXhMbhrfDYN4qu9mMlWQy83OGXWyf2HTrXTBomEQyqXFLuAr2NbC-F3CKk8-yghgVeucuSwgaHRmI2AGV3Hx8E4JtmcTsDKH9LpoqBdXVgoyloQc" />
                    <div>
                      <p class="font-medium">Alex Johnson</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">alex.j@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:30:15 AM</td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="John Doe" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBFnfPNWT2HRW4bgE_sjWbcI0A3X8dr8KO-8x_9lp3xxdhlCYs4XP_6qiQ5U2UAB92kq9swQi4Iq_kOHSbLvtxAhZ965HQq5Ld1xuXwP78dqEXSuy30PtTfKB0m6zKOge9kSeBYBNFRQpl8l9qfhuuMY2NJ_Zh1NNf_6f8zN3l0ZLcfRL8sxq6bFV5le6RLzVBbVQLAfjubPzgPn5nZhiH7u-CENJwQ7-SEc8NIwRxcMHwAW0V3-G6u727sdzXfMgxodSntyaizLa0B" />
                    <div>
                      <p class="font-medium">John Doe</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">john.d@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:25:42 AM</td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="Jane Smith" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCSzGQDV8x3sgn8kPuBmLdLfyZ7eX8vUQZHDgdaZ1Q8LX2Iu46Z0cPs_eKlTbYwlt6iGoSxnP2Gy9TThX9-b7gnrF18j-wAPjR5er9hXwwV54k5T-n0IN5k3dAwj6MqHYHaBtZd0J9X6ekvyK_poDknAT6fJTjVs35Eh1wrkx-cGL98lVt9TpTMhJUS5YPAFUOoHT2GPW8J9cqZ_Cnpk1-YeCFb-3LFzDEbH7kEy-b55bd_Ngfo3MpdTDjvpQ83lVFq1n9U9VUocJWL" />
                    <div>
                      <p class="font-medium">Jane Smith</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">jane.s@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:20:05 AM</td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="Sara Lee" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDDNrrd5gu2-GyWPt4xQap4WT4GUQogLB5xcDfbww6g8l-65ei60PLXxFgkElAgEgFJxbtItWqYJdqtLOwg0NSaeebzJ2i9k-k8cKmh8sWLRviEGUQzD1lFsbLXzlIZn5Zoax9eAnQuXsqijXlgetXELpjg7znjp3TA4KNr2VY9weulyr9hS4JaYKqJ7AW-pnSTfEziKnGz7803V4nC4gYXryYmNZv4rAx402l5mzJzCxLZgFUHMDexnCSy2APYvSV7OR3GepwCQwS9" />
                    <div>
                      <p class="font-medium">Sara Lee</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">sara.l@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:15:18 AM</td>
                </tr>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="Mike Ross" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC3vis-j1POiu9WorycNAnzQ5eoupaI73JtveQU6EXHmWK0ySCUX6k83Qm0N1j2McUV4ShSle4kd0MPLYuE5OGB-Ji1uQPpzdFENoD1Cm53VzeX5RmNrb35blPYw0erWPq14rxl1J7W4V7w8LmODYuR6HsqyEKhwifDJ_IIGvknZ7RKj7mkDjYxYY-pJS2vbSmeyZg_GatMHF1FLoXxSvNjrYq2GEWTR9AWScFUvIYwTfyY39hdjFm64BN-NqAxazNlfhOoHPkrBoH1" />
                    <div>
                      <p class="font-medium">Mike Ross</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">mike.r@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:10:33 AM</td>
                </tr>
                <tr>
                  <td class="py-4 px-4 flex items-center gap-3">
                    <img alt="Lara Croft" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBNgEzP_Lr7WnQKARAuwg1yohhMhAJbotdLLKCbZ2Xni2BhrXXEcAUfWL79J8dIGPXgwna-bGpLFcbmKtbdaXxJPXfHVBgsjDGanlf9bkQjHLcsgufNWgfTlK_5QSAg2tsC533voKum6P8cXE7hLA2RSQ0YiSWGrQuNZsKSGQsK3LsOhoWGIyjcKFBnHDnwC7UM_xlKKs5BW2rwfd94-OTqDf02P6lS-QY1P5Py76OkATffZX6r7L6_x2fjRn7_TxeMyswGGXn7ENrl" />
                    <div>
                      <p class="font-medium">Lara Croft</p>
                      <p class="text-sm text-subtext-light dark:text-subtext-dark">lara.c@example.com</p>
                    </div>
                  </td>
                  <td class="py-4 px-4">2023-10-27 10:05:51 AM</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
    </section>

    <!-- Members Content -->
    <section id="members-section" class="hidden">
      <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
          <h2 class="text-2xl font-bold">Members</h2>
          <div class="flex items-center gap-4 flex-wrap">
            <div class="relative">
              <span class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark">search</span>
              <input id="members-search" class="pl-10 pr-4 py-2 w-64 bg-card-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Search members..." type="text" />
            </div>
            <div class="relative">
              <select id="members-status-filter" class="pl-4 pr-10 py-2 bg-card-light dark:bg-card-dark border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary appearance-none">
                <option>All Status</option>
                <option>Active</option>
                <option>Expired</option>
                <option>Frozen</option>
              </select>
              <span class="material-icons absolute right-3 top-1/2 -translate-y-1/2 text-subtext-light dark:text-subtext-dark pointer-events-none">expand_more</span>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto max-h-[450px] overflow-y-auto rounded-md shadow-inner">
          <table class="w-full text-left">
            <thead class="border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-card-light dark:bg-card-dark">
              <tr>
                <th class="py-3 px-4 font-semibold text-sm">Name</th>
                <th class="py-3 px-4 font-semibold text-sm">Membership Status</th>
                <th class="py-3 px-4 font-semibold text-sm">Last Activity</th>
                <th class="py-3 px-4 font-semibold text-sm"></th>
              </tr>
            </thead>
            <tbody id="members-tbody">
              <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Alex Johnson" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuABMnteW7Kiuj3p9Ego2-hjRM-_T0tC1qRq0TRvCGMg_MKyqNkOZAnwsy-KtZd5T5oTrSx0sPWma99mXl5ysewR2BYKWBSMlPHEtBp0OKpYY9bsI5L4Q53EuTdxae6tyJ_1q1WDCQWWAvOhRb-9_hfgAHcuVUNFaXhMbhrfDYN4qu9mMlWQy83OGXWyf2HTrXTBomEQyqXFLuAr2NbC-F3CKk8-yghgVeucuSwgaHRmI2AGV3Hx8E4JtmcTsDKH9LpoqBdXVgoyloQc" />
                  <div>
                    <p class="font-medium">Alex Johnson</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">alex.j@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Active</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">2 hours ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Sara Lee" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDDNrrd5gu2-GyWPt4xQap4WT4GUQogLB5xcDfbww6g8l-65ei60PLXxFgkElAgEgFJxbtItWqYJdqtLOwg0NSaeebzJ2i9k-k8cKmh8sWLRviEGUQzD1lFsbLXzlIZn5Zoax9eAnQuXsqijXlgetXELpjg7znjp3TA4KNr2VY9weulyr9hS4JaYKqJ7AW-pnSTfEziKnGz7803V4nC4gYXryYmNZv4rAx402l5mzJzCxLZgFUHMDexnCSy2APYvSV7OR3GepwCQwS9" />
                  <div>
                    <p class="font-medium">Sara Lee</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">sara.l@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Expired</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">1 day ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Mike Ross" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC3vis-j1POiu9WorycNAnzQ5eoupaI73JtveQU6EXHmWK0ySCUX6k83Qm0N1j2McUV4ShSle4kd0MPLYuE5OGB-Ji1uQPpzdFENoD1Cm53VzeX5RmNrb35blPYw0erWPq14rxl1J7W4V7w8LmODYuR6HsqyEKhwifDJ_IIGvknZ7RKj7mkDjYxYY-pJS2vbSmeyZg_GatMHF1FLoXxSvNjrYq2GEWTR9AWScFUvIYwTfyY39hdjFm64BN-NqAxazNlfhOoHPkrBoH1" />
                  <div>
                    <p class="font-medium">Mike Ross</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">mike.r@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Active</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">3 hours ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Lara Croft" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBNgEzP_Lr7WnQKARAuwg1yohhMhAJbotdLLKCbZ2Xni2BhrXXEcAUfWL79J8dIGPXgwna-bGpLFcbmKtbdaXxJPXfHVBgsjDGanlf9bkQjHLcsgufNWgfTlK_5QSAg2tsC533voKum6P8cXE7hLA2RSQ0YiSWGrQuNZsKSGQsK3LsOhoWGIyjcKFBnHDnwC7UM_xlKKs5BW2rwfd94-OTqDf02P6lS-QY1P5Py76OkATffZX6r7L6_x2fjRn7_TxeMyswGGXn7ENrl" />
                  <div>
                    <p class="font-medium">Lara Croft</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">lara.c@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full">Frozen</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">5 days ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="Peter Pan" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAUve3oqgqsnoBEKa4AvIGsdFDvuuO3HDMbe6TTOZnrlQT7D1_w6O-699bQtsx89IjskrpahCoRWb1JBvwMrm1GYdqiAdP9wK5FDPL20PGxmzJn_8ZCS0omwprBf2vvGYgKo5p2T-n5srOpbJgtpniWLh01qHtfymUcDEXm9wdso0s2mE6V265qEcM0t1PhHVMGjqu1qdb9Iyn7H5AKhDqQB8vlm3cbgJu4ft133CUKKscxDsHegzUdC6rNOoxaB-ejlKZKrQp1l69u" />
                  <div>
                    <p class="font-medium">Peter Pan</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">peter.p@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Active</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">1 hour ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                <td class="py-3 px-4 flex items-center gap-3">
                  <img alt="John Doe" class="w-10 h-10 rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBFnfPNWT2HRW4bgE_sjWbcI0A3X8dr8KO-8x_9lp3xxdhlCYs4XP_6qiQ5U2UAB92kq9swQi4Iq_kOHSbLvtxAhZ965HQq5Ld1xuXwP78dqEXSuy30PtTfKB0m6zKOge9kSeBYBNFRQpl8l9qfhuuMY2NJ_Zh1NNf_6f8zN3l0ZLcfRL8sxq6bFV5le6RLzVBbVQLAfjubPzgPn5nZhiH7u-CENJwQ7-SEc8NIwRxcMHwAW0V3-G6u727sdzXfMgxodSntyaizLa0B" />
                  <div>
                    <p class="font-medium">John Doe</p>
                    <p class="text-sm text-subtext-light dark:text-subtext-dark">john.d@example.com</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-3 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Expired</span>
                </td>
                <td class="py-3 px-4 text-subtext-light dark:text-subtext-dark">1 week ago</td>
                <td class="py-3 px-4">
                  <button class="text-primary hover:underline">Details</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Profile Settings -->
    <section id="profile-section" class="hidden">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="first-name">First Name</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="first-name" type="text" value="Admin"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="last-name">Last Name</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="last-name" type="text" value="User"/>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="email">Email Address</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="email" type="email" value="admin.user@versogym.com"/>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="bio">Bio</label>
                <textarea class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="bio" rows="3">Gym Administrator with 5+ years of experience in facility management and member services.</textarea>
              </div>
            </div>
            <div class="mt-6 text-right">
              <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                Save Changes
              </button>
            </div>
          </div>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Change Password</h3>
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="current-password">Current Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="current-password" type="password"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="new-password">New Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="new-password" type="password"/>
              </div>
              <div>
                <label class="block text-sm font-medium text-subtext-light dark:text-subtext-dark mb-1" for="confirm-password">Confirm New Password</label>
                <input class="w-full bg-background-light dark:bg-background-dark border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" id="confirm-password" type="password"/>
              </div>
            </div>
            <div class="mt-6 text-right">
              <button class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                Update Password
              </button>
            </div>
          </div>
        </div>
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex flex-col items-center text-center">
          <img alt="Admin User" class="w-24 h-24 rounded-full mb-4" src="https://lh3.googleusercontent.com/a-/ALV-UjVw-xVslc_7m2mAeyVb83fX8SVOcsbBv6Fn2dM_7GA78g=s96-c"/>
          <h3 class="text-xl font-semibold">Admin User</h3>
          <p class="text-subtext-light dark:text-subtext-dark">Administrator</p>
          <button class="mt-4 w-full bg-gray-200 dark:bg-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600">
            Change Picture
          </button>
          <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg mt-8 w-full">
            <h3 class="text-lg font-semibold mb-4">Notification Settings</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Email Notifications</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Receive updates via email.</p>
                </div>
                <label class="switch">
                  <input checked type="checkbox" />
                  <span class="slider"></span>
                </label>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Push Notifications</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Get alerts on your device.</p>
                </div>
                <label class="switch">
                  <input type="checkbox" />
                  <span class="slider"></span>
                </label>
              </div>
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium">Monthly Reports</p>
                  <p class="text-sm text-subtext-light dark:text-subtext-dark">Receive monthly performance reports.</p>
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

    <!-- Notification Center -->
    <section id="notification-section" class="hidden">
      <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Today</h3>
          <a class="text-sm text-primary hover:underline cursor-pointer" id="mark-all-read-btn">Mark all as read</a>
        </div>
        <div class="space-y-4" id="notifications-list">
          <div class="flex items-start gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark">
            <div class="w-10 h-10 bg-yellow-500 rounded-full flex-shrink-0 flex items-center justify-center">
              <span class="material-icons text-white">warning</span>
            </div>
            <div class="flex-1">
              <p class="font-medium">System Alert: Maintenance</p>
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Scheduled maintenance tonight from 12 AM to 2 AM. The system may be unavailable.</p>
              <p class="text-xs text-subtext-light dark:text-subtext-dark mt-1">15 minutes ago</p>
            </div>
            <div class="flex items-center gap-2">
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 mark-read-btn" title="Mark as read">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">done</span>
              </button>
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 archive-btn" title="Archive">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">archive</span>
              </button>
            </div>
          </div>
          <div class="flex items-start gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex-shrink-0 flex items-center justify-center">
              <span class="material-icons text-white">chat_bubble</span>
            </div>
            <div class="flex-1">
              <p class="font-medium">New message from Alex Morgan</p>
              <p class="text-sm text-subtext-light dark:text-subtext-dark">"Hey, can you check on my membership renewal? I believe it's due soon."</p>
              <p class="text-xs text-subtext-light dark:text-subtext-dark mt-1">1 hour ago</p>
            </div>
            <div class="flex items-center gap-2">
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 mark-read-btn" title="Mark as read">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">done</span>
              </button>
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 archive-btn" title="Archive">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">archive</span>
              </button>
            </div>
          </div>
          <div class="flex items-start gap-4 p-4 rounded-lg bg-background-light dark:bg-background-dark">
            <div class="w-10 h-10 bg-green-500 rounded-full flex-shrink-0 flex items-center justify-center">
              <span class="material-icons text-white">receipt</span>
            </div>
            <div class="flex-1">
              <p class="font-medium">Payment Successful</p>
              <p class="text-sm text-subtext-light dark:text-subtext-dark">Payment of ₠150 from Samantha Bee for annual membership has been successfully processed.</p>
              <p class="text-xs text-subtext-light dark:text-subtext-dark mt-1">3 hours ago</p>
            </div>
            <div class="flex items-center gap-2">
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 mark-read-btn" title="Mark as read">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">done</span>
              </button>
              <button class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 archive-btn" title="Archive">
                <span class="material-icons text-subtext-light dark:text-subtext-dark">archive</span>
              </button>
            </div>
          </div>
        </div>
        <div class="mt-8">
          <h3 class="text-lg font-semibold mb-4">Yesterday</h3>
          <div class="space-y-4 opacity-70">
            <div class="flex items-start gap-4 p-4 rounded-lg bg-gray-100/50 dark:bg-gray-800/50">
              <div class="w-10 h-10 bg-red-500 rounded-full flex-shrink-0 flex items-center justify-center">
                <span class="material-icons text-white">error</span>
              </div>
              <div class="flex-1">
                <p class="font-medium">Billing Alert: Failed Payment</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">Payment from user John Doe failed. Please check the billing details.</p>
                <p class="text-xs text-subtext-light dark:text-subtext-dark mt-1">1 day ago</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="material-icons text-green-500">done</span>
                <span class="material-icons text-subtext-light dark:text-subtext-dark">archive</span>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 rounded-lg bg-gray-100/50 dark:bg-gray-800/50">
              <div class="w-10 h-10 bg-blue-500 rounded-full flex-shrink-0 flex items-center justify-center">
                <span class="material-icons text-white">chat_bubble</span>
              </div>
              <div class="flex-1">
                <p class="font-medium">New message from Sarah Connor</p>
                <p class="text-sm text-subtext-light dark:text-subtext-dark">"I'd like to book a session with a personal trainer for next Monday."</p>
                <p class="text-xs text-subtext-light dark:text-subtext-dark mt-1">1 day ago</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="material-icons text-green-500">done</span>
                <span class="material-icons text-subtext-light dark:text-subtext-dark">archive</span>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-6 text-center">
          <button class="bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark px-6 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            View All Notifications
          </button>
        </div>
      </div>
    </section>

    <!-- Trainers Management -->
    <section id="trainers-section" class="hidden">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold">Trainers Management</h2>
        <div class="flex items-center gap-4">
          <button class="bg-primary text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-red-600">
            <span class="material-icons">add</span>
            <span>Add Trainer</span>
          </button>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <!-- Trainer Cards -->
        <div class="bg-card-light dark:bg-card-dark p-6 rounded-lg flex flex-col items-center text-center shadow-sm hover:shadow-lg transition-shadow duration-300">
          <img alt="John Doe" class="w-24 h-24 rounded-full mb-4 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDhfUHGz63ljVfMppgQjOJ3008kJJzF33MZ5KoKT0IhrFKNGsqYHhlpCPoITvyJUFtl-1jymXNTqnW9W3uI8oo9Cm9fzPGtHeyYFCUIokWr-E7P6G_8wP8omsXqLQmkHkk4cOtNwM-6Ba8YDd--69S17iz_curLrLKDwj2geOrdzouu-Ix6bfR8mwui1MfRU4d9xM_Hec7LcKenr4_6MeurtNQnmpYztfyCyfpOYP6RDtod1EzfCAyaOp24oux7TsA3vNUdVtC56Y_v"/>
          <h3 class="text-xl font-semibold">John Doe</h3>
          <p class="text-subtext-light dark:text-subtext-dark mb-3">CrossFit Specialist</p>
          <div class="flex items-center gap-2 mb-4">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>
            <p class="text-sm text-green-500 font-medium">Available</p>
          </div>
          <div class="flex gap-2 w-full mt-auto">
            <button class="flex-1 bg-gray-200 dark:bg-gray-700 py-2 px-4 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 flex items-center justify-center gap-1 transition-colors">
              <span class="material-icons text-sm">edit</span> Edit
            </button>
            <button class="flex-1 bg-red-100 dark:bg-red-900/20 text-red-500 py-2 px-4 rounded-lg text-sm font-medium hover:bg-red-200 dark:hover:bg-red-900/40 flex items-center justify-center gap-1 transition-colors">
              <span class="material-icons text-sm">delete</span> Remove
            </button>
          </div>
        </div>
        <!-- Add more trainers here in similar format -->
      </div>
    </section>

    <!-- Logout Page -->
    <section id="logout-section" class="hidden flex flex-col justify-center items-center h-full">
      <div class="bg-card-light dark:bg-card-dark p-10 rounded-lg shadow-lg text-center">
        <span class="material-icons text-6xl text-primary mb-6">logout</span>
        <h2 class="text-3xl font-bold mb-4">You have been logged out</h2>
        <p class="mb-6 text-subtext-light dark:text-subtext-dark">Thank you for using Verso Gym Admin Dashboard.</p>
        <a href="#" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-red-600 transition">Login Again</a>
      </div>
    </section>
  </main>
</div>

<script>
  // Dark mode toggle button
  const toggleThemeBtn = document.getElementById('toggle-theme-btn');
  const themeIcon = document.getElementById('theme-icon');

  function updateThemeIcon() {
    if (document.documentElement.classList.contains('dark')) {
      themeIcon.textContent = 'light_mode';
    } else {
      themeIcon.textContent = 'dark_mode';
    }
  }

  toggleThemeBtn.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
    updateThemeIcon();
  });

  // Initialize theme icon on load
  updateThemeIcon();

  // Trainers dark mode toggle (separate)
  const trainersThemeToggle = document.getElementById('trainers-theme-toggle');
  const trainersSunIcon = document.getElementById('trainers-sun-icon');
  const trainersMoonIcon = document.getElementById('trainers-moon-icon');

  trainersThemeToggle?.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
    if (document.documentElement.classList.contains('dark')) {
      trainersSunIcon.style.display = 'none';
      trainersMoonIcon.style.display = 'block';
    } else {
      trainersSunIcon.style.display = 'block';
      trainersMoonIcon.style.display = 'none';
    }
    updateThemeIcon();
  });

  // Navigation buttons and sections
  const sections = {
    dashboard: document.getElementById('dashboard-section'),
    analytics: document.getElementById('analytics-section'),
    loginHistory: document.getElementById('login-history-section'),
    members: document.getElementById('members-section'),
    billing: document.getElementById('billing-section'),
    profile: document.getElementById('profile-section'),
    notification: document.getElementById('notification-section'),
    trainers: document.getElementById('trainers-section'),
    logout: document.getElementById('logout-section')
  };

  const navButtons = {
    dashboard: document.getElementById('nav-dashboard'),
    analytics: document.getElementById('nav-analytics'),
    loginHistory: document.getElementById('nav-login-history'),
    members: document.getElementById('nav-members'),
    billing: document.getElementById('nav-billing'),
    profile: document.getElementById('nav-profile'),
    notification: document.getElementById('nav-notifications'),
    trainers: document.getElementById('nav-trainers'),
    logout: document.getElementById('nav-logout')
  };

  const pageTitle = document.getElementById('page-title');

  function setActiveNav(activeKey) {
    Object.entries(navButtons).forEach(([key, btn]) => {
      if (!btn) return;
      btn.classList.remove('bg-primary', 'text-white');
      btn.classList.add('hover:bg-gray-200', 'dark:hover:bg-gray-700');
      if (key === activeKey) {
        btn.classList.add('bg-primary', 'text-white');
        btn.classList.remove('hover:bg-gray-200', 'dark:hover:bg-gray-700');
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
    pageTitle.textContent = {
      dashboard: "Dashboard",
      analytics: "Analytics",
      loginHistory: "Login History",
      members: "Members",
      billing: "Billing Management",
      profile: "Profile Settings",
      notification: "Notification Center",
      trainers: "Trainers Management",
      logout: "Logout"
    }[key] || "Verso Gym Admin Dashboard";
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

  // Billing search and filter
  const billingSearchInput = document.getElementById('billing-search');
  const billingStatusFilter = document.getElementById('billing-status-filter');
  const billingTbody = document.getElementById('billing-tbody');

  function filterBilling() {
    const searchText = billingSearchInput.value.toLowerCase();
    const status = billingStatusFilter.value.toLowerCase();

    Array.from(billingTbody.querySelectorAll('tr')).forEach(row => {
      const memberCell = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
      const statusCell = row.querySelector('td:nth-child(5) > span').textContent.toLowerCase();

      const matchesSearch = memberCell.includes(searchText);
      const matchesStatus = status === 'all status' || status === '' || statusCell === status;

      if (matchesSearch && matchesStatus) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  billingSearchInput.addEventListener('input', filterBilling);
  billingStatusFilter.addEventListener('change', filterBilling);

  // Billing export functionality
  const billingExportBtn = document.getElementById('billing-export-btn');
  billingExportBtn.addEventListener('click', () => {
    // Export visible rows as CSV
    const rows = Array.from(billingTbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
    if (!rows.length) {
      alert('No data to export.');
      return;
    }

    const headers = ['Invoice ID', 'Member', 'Date', 'Amount', 'Status'];
    const csvContent = [headers.join(',')];

    rows.forEach(row => {
      const cols = row.querySelectorAll('td');
      const rowData = [
        cols[0].textContent.trim(),
        cols[1].textContent.trim(),
        cols[2].textContent.trim(),
        cols[3].textContent.trim(),
        cols[4].textContent.trim()
      ];
      csvContent.push(rowData.map(item => `"${item.replace(/"/g, '""')}"`).join(','));
    });

    const csvString = csvContent.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'billing_data.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });

  // Notifications mark as read/archive
  document.getElementById('notifications-list').addEventListener('click', e => {
    if (e.target.closest('.mark-read-btn')) {
      const notification = e.target.closest('div.flex.items-start');
      if (notification) {
        notification.style.opacity = '0.5';
        notification.style.pointerEvents = 'none';
      }
    }
    if (e.target.closest('.archive-btn')) {
      const notification = e.target.closest('div.flex.items-start');
      if (notification) {
        notification.remove();
      }
    }
  });

  document.getElementById('mark-all-read-btn').addEventListener('click', () => {
    const notifications = document.querySelectorAll('#notifications-list > div.flex.items-start');
    notifications.forEach(n => {
      n.style.opacity = '0.5';
      n.style.pointerEvents = 'none';
    });
  });

  // Chart.js instances for Analytics section
  const memberSignupsCtx = document.getElementById('memberSignupsChart').getContext('2d');
  const classAttendanceCtx = document.getElementById('classAttendanceChart').getContext('2d');
  const revenueCtx = document.getElementById('revenueChart').getContext('2d');

  function getTextColor() {
    return document.documentElement.classList.contains('dark') ? '#F9FAFB' : '#1F2937';
  }
  function getGridColor() {
    return document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
  }

  let memberSignupsChart = null;
  let classAttendanceChart = null;
  let revenueChart = null;

  function renderCharts() {
    const textColor = getTextColor();
    const gridColor = getGridColor();

    if (memberSignupsChart) memberSignupsChart.destroy();
    if (classAttendanceChart) classAttendanceChart.destroy();
    if (revenueChart) revenueChart.destroy();

    memberSignupsChart = new Chart(memberSignupsCtx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [{
          label: 'New Members',
          data: [65, 59, 80, 81, 56, 55, 40],
          fill: false,
          borderColor: '#EF4444',
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: textColor },
            grid: { color: gridColor }
          },
          x: {
            ticks: { color: textColor },
            grid: { color: gridColor }
          }
        },
        plugins: {
          legend: {
            labels: { color: textColor }
          }
        }
      }
    });

    classAttendanceChart = new Chart(classAttendanceCtx, {
      type: 'bar',
      data: {
        labels: ['Yoga', 'HIIT', 'Cycling', 'Zumba', 'Strength'],
        datasets: [{
          label: 'Attendance',
          data: [85, 65, 90, 75, 50],
          backgroundColor: [
            'rgba(239, 68, 68, 0.6)',
            'rgba(59, 130, 246, 0.6)',
            'rgba(16, 185, 129, 0.6)',
            'rgba(249, 115, 22, 0.6)',
            'rgba(139, 92, 246, 0.6)',
          ],
          borderColor: [
            '#EF4444',
            '#3B82F6',
            '#10B981',
            '#F97316',
            '#8B5CF6',
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: textColor },
            grid: { color: gridColor }
          },
          x: {
            ticks: { color: textColor },
            grid: { color: gridColor }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });

    revenueChart = new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Revenue ($K)',
          data: [12, 19, 15, 22, 18, 25, 23, 28, 26, 30, 28, 35],
          fill: true,
          backgroundColor: 'rgba(239, 68, 68, 0.2)',
          borderColor: '#EF4444',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              color: textColor,
              callback: function(value) {
                return '$' + value + 'k';
              }
            },
            grid: { color: gridColor }
          },
          x: {
            ticks: { color: textColor },
            grid: { color: gridColor }
          }
        },
        plugins: {
          legend: {
            labels: { color: textColor }
          }
        }
      }
    });
  }

  // Render charts initially
  renderCharts();

  // Update charts colors on theme change
  const observer = new MutationObserver(() => {
    renderCharts();
    updateThemeIcon();
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  // Login history search filter (simple client-side filter)
  const loginHistorySearch = document.getElementById('login-history-search');
  const loginHistoryTbody = document.getElementById('login-history-tbody');

  loginHistorySearch.addEventListener('input', () => {
    const filter = loginHistorySearch.value.toLowerCase();
    Array.from(loginHistoryTbody.querySelectorAll('tr')).forEach(row => {
      const userCell = row.querySelector('td > div > p')?.textContent.toLowerCase() || '';
      if (userCell.includes(filter)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });

  // Members search filter and status filter
  const membersSearch = document.getElementById('members-search');
  const membersStatusFilter = document.getElementById('members-status-filter');
  const membersTbody = document.getElementById('members-tbody');

  function filterMembers() {
    const searchTerm = membersSearch.value.toLowerCase();
    const statusTerm = membersStatusFilter.value.toLowerCase();

    Array.from(membersTbody.querySelectorAll('tr')).forEach(row => {
      const nameCell = row.querySelector('td > div > p.font-medium')?.textContent.toLowerCase() || '';
      const statusSpan = row.querySelector('td:nth-child(2) > span')?.textContent.toLowerCase() || '';

      const matchesSearch = nameCell.includes(searchTerm);
      const matchesStatus = statusTerm === 'all status' || statusTerm === '' || statusSpan === statusTerm;

      if (matchesSearch && matchesStatus) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  membersSearch.addEventListener('input', filterMembers);
  membersStatusFilter.addEventListener('change', filterMembers);
</script>
</body>
</html>