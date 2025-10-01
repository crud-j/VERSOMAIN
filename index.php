<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>VersoGym — Home</title>
    <!-- CSS (relative paths for localhost) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      AOS.init({
        duration: 1000,
        once: true
      });
    </script>

    <!-- Tailwind CSS for About Us, Services, and Membership -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

    <!-- Inline Styles and Scripts -->
    <style>
        :root {
            --glow-color: #ff3300;
        }
        .parallax {
            background-image: url('https://images.unsplash.com/photo-1549060279-7e168fcee0c2?q=80&w=2670&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .service-card:hover {
            box-shadow: 0 0 40px 10px var(--glow-color);
        }
        .card-content-bg {
            background: linear-gradient(90deg, rgba(26,26,26,0.6) 0%, rgba(42,42,42,1) 50%, rgba(26,26,26,0.6) 100%);
        }
        .service-card-inner {
            background: linear-gradient(to right, rgba(26, 26, 26, 0.2), rgba(42, 42, 42, 0.9), rgba(26, 26, 26, 0.2));
            background-color: #1a1a1a99;
            backdrop-filter: blur(5px);
        }
        .trainer-card {
            background-size: cover;
            background-position: center;
        }
        .backdrop-blur-sm {
            backdrop-filter: blur(4px);
        }
        .group:hover .group-hover\:backdrop-blur-md {
            backdrop-filter: blur(16px);
        }
        .group:hover .group-hover\:scale-105 {
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        @keyframes blob {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-20px) scale(1.1); }
        }
        @keyframes blob-bounce {
            0%, 100% { transform: translateY(-5%) translateX(5%) scale(1); }
            50% { transform: translateY(5%) translateX(-5%) scale(1.1); }
        }
        .blob-1 {
            animation: blob-bounce 10s infinite ease-in-out;
        }
        .blob-2 {
            animation: blob-bounce 12s infinite ease-in-out;
            animation-delay: -3s;
        }
        .blob-3 {
            animation: blob-bounce 14s infinite ease-in-out;
            animation-delay: -6s;
        }
        .membership-parallax {
            background-image: url('https://images.unsplash.com/photo-1593079831268-3381b0db4a77?q=80&w=2670&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
      html { scroll-behavior: smooth; }
      .tooltip-glow {
        box-shadow: 0 0 15px 3px var(--glow-color);
      }

/* === Glassmorphism Styles from Dashboard === */
.glassy {
    position: relative;
    background: rgba(255, 255, 255, 0.05);
    -webkit-backdrop-filter: blur(10px);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.glassy::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at top left, rgba(255, 69, 0, 0.15), transparent 60%),
                radial-gradient(circle at bottom right, rgba(255, 99, 71, 0.15), transparent 60%);
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
    pointer-events: none;
    z-index: 0;
}
.glassy:hover::before {
    opacity: 1;
}
.glassy:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}
.btn-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(255, 69, 0, 0.3);
}


/* === Glassmorphism Enhancements === */
.glassy {
    border: 1px solid transparent;
    background-clip: padding-box;
}
.glassy::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 12px;
    padding: 1px;
    background: linear-gradient(135deg, #FF4500, #FF6347);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
}
.glassy:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}
/* Button glow */
.btn-hover:hover {
    box-shadow: 0 0 15px rgba(255, 69, 0, 0.6), 
                0 0 30px rgba(255, 99, 71, 0.4);
}
/* Section title underline */
.section-title {
    display: inline-block;
    position: relative;
    font-weight: 800;
}
.section-title::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -6px;
    width: 100%;
    height: 3px;
    border-radius: 2px;
    background: linear-gradient(90deg, #FF4500, #FF6347, #FF4500);
    background-size: 200% 100%;
    animation: gradient-move 3s linear infinite;
}
@keyframes gradient-move {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}

#coaching {
  box-shadow: 0 0 100px rgba(0,0,0,0.5);
}



#pricing, #booking, #contact {
    background: #000;
    padding: 8rem 1rem;
    border-radius: 1px;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

/* Back to Top button with gradient pulse */
#backToTop {
    background: linear-gradient(135deg, #FF4500, #FF6347);
    animation: pulseGradient 2s infinite;
}
@keyframes pulseGradient {
    0% { box-shadow: 0 0 10px rgba(255, 69, 0, 0.4); }
    50% { box-shadow: 0 0 25px rgba(255, 99, 71, 0.7); }
    100% { box-shadow: 0 0 10px rgba(255, 69, 0, 0.4); }
}

/* Nav Link Underline */
.nav-link-underline {
    position: relative;
    transition: color 0.3s, letter-spacing 0.3s;
}
.nav-link-underline::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -0.5rem;
    height: 3px;
    width: 0;
    transform: translateX(-50%);
    border-radius: 0.375rem;
    background-image: linear-gradient(to right, #ef3b1b, #ff6b3a);
    opacity: 0;
    transition: all 0.3s;
}
.nav-link-underline:hover::after, .nav-link.active::after {
    width: 100%;
    opacity: 1;
}
.nav-link-underline:hover {
    letter-spacing: 0.05em;
}
.nav-link.active {
    color: #ff4500; /* text-orange-400 */
    font-weight: 700; /* font-bold */
}

/* FAQ Accordion */
.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease-out, padding 0.4s ease-out;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}
.faq-answer.open {
    max-height: 10rem; /* Adjust as needed */
    padding-top: 0.5rem;
    padding-bottom: 1.5rem;
    transition: max-height 0.5s ease-in, padding 0.5s ease-in;
}
.faq-toggle .material-icons {
    transition: transform 0.3s ease-in-out;
}
.faq-toggle.active .material-icons {
    transform: rotate(45deg);
}

.day-selected {
  background-color: #ff4500 !important;
  color: white !important;
}

@keyframes pulse-slow {
  0%, 100% {
    box-shadow: 0 0 25px rgba(249, 115, 22, 0.4), 0 0 50px rgba(249, 115, 22, 0.2);
  }
  50% {
    box-shadow: 0 0 40px rgba(249, 115, 22, 0.7), 0 0 80px rgba(249, 115, 22, 0.4);
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}


</style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ff4500",
                        "primary-light": "#ff6a33",
                        "background-light": "#f1f5f9",
                        "background-dark": "#0f172a",
                        "brand-orange": "#ff4500",
                        "brand-dark": "#1A1A1A",
                        "brand-light-gray": "#D3D3D3",
                        
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "Lexend", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "full": "9999px"
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'fade-in': 'fadeIn 0.8s ease-out forwards'
                    },
                    boxShadow: {
                        'glass': '0 4px 30px rgba(0, 0, 0, 0.1)',
                        'glass-dark': '0 4px 30px rgba(0, 0, 0, 0.2)'
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                    },
                },
            },
        }
    </script>

</head>
<body class="font-display text-gray-300 antialiased relative" style="background-color: #000; background-image: radial-gradient(at 20% 20%, hsla(0, 0%, 0%, 0.10) 0px, transparent 50%), radial-gradient(at 80% 20%, hsla(0, 0%, 0%, 0.10) 0px, transparent 50%), radial-gradient(at 80% 80%, hsla(0, 0%, 0%, 0.10) 0px, transparent 50%), radial-gradient(at 20% 80%, hsla(0, 0%, 0%, 0.10) 0px, transparent 50%);">
  <!-- ---------- HEADER ---------- -->
  <header id="site-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 py-4 px-6 bg-transparent">
    <div class="max-w-[1200px] mx-auto flex items-center justify-between gap-6">
      <!-- Brand -->
      <a href="#" class="flex items-center gap-2 text-white font-bold text-base">
        <img src="img/logo.png" alt="VersoGym logo" class="w-11 h-auto" />
        <span class="hidden sm:inline font-bold text-lg">Verso<span class="text-[#ef3b1b]">Gym</span></span>
      </a>

      <!-- Navigation -->
      <nav class="hidden md:block">
        <ul class="flex items-center gap-8">
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#home">Home</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#about">About Us</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#services">Services</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#coaching">Trainer</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#membership">Membership</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#booking">Booking</a></li>
          <li><a class="nav-link nav-link-underline text-white/85 hover:text-white text-sm font-medium" href="#contact">Contact</a></li>
        </ul>
      </nav>

      <!-- Auth Buttons -->
      <div class="hidden md:flex items-center gap-3">
        <a href="login.php" class="px-5 py-2 rounded-full border border-white/40 text-white text-sm font-semibold uppercase hover:text-black hover:bg-white transition">Login</a>
        <a href="register.php" class="px-5 py-2 rounded-full bg-gradient-to-r from-[#ef3b1b] to-[#ff6b3a] text-white text-sm font-semibold uppercase shadow-lg hover:scale-105 transition">Sign Up</a>
      </div>
    </div>
  </header>

  <!-- ---------- HERO ---------- -->
  <section id="home" class="relative min-h-screen flex items-center overflow-hidden bg-[url('img/hero-1.png')] bg-cover bg-center brightness-90 pt-24">
    <!-- overlay glow effects -->
    <div class="absolute -left-40 top-1/2 -translate-y-1/2 w-[420px] h-[420px] rounded-full blur-[160px] bg-[radial-gradient(circle,rgba(239,59,27,0.35),transparent_70%)]"></div>
    <div class="absolute -right-40 top-1/2 -translate-y-1/2 w-[420px] h-[420px] rounded-full blur-[160px] bg-[radial-gradient(circle,rgba(101,17,0,0.25),transparent_70%)]"></div>

    <!-- hero content -->
    <div class="relative z-10 max-w-[1100px] px-5 md:pl-16 space-y-6" data-aos="fade-right">
      <!-- Pills -->
      <div class="flex items-center gap-4">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/70 text-sm font-medium text-white">
          Pro Trainers <span class="text-[#ef3b1b] text-base">•</span> Modern Programs
        </span>
      </div>

      <!-- Title -->
      <h1 class="font-[Riking] text-white text-[clamp(44px,8vw,70px)] font-medium leading-[0.92] uppercase tracking-wide drop-shadow-lg">
        ACHIEVE YOUR <span class="text-[#ef3b1b]">FITNESS<br>GOALS</span> WITH <span class="text-[#ef3b1b]">VERSO GYM</span>
      </h1>

      <!-- Subtitle -->
      <p class="text-white/90 text-lg max-w-2xl">
        Join the community transforming their bodies with expert training, real coaching, 
        and tools that make progress inevitable.
      </p>

      <!-- CTA Buttons -->
      <div class="flex items-center gap-4">
        <a href="#membership" class="nav-link px-8 py-4 rounded-lg text-lg font-semibold bg-gradient-to-r from-[#ef3b1b] to-[#ff6b3a] text-white shadow-lg hover:scale-105 transition relative overflow-hidden">
          Start Your Journey »
        </a>
        <a href="#services" class="nav-link px-8 py-4 rounded-lg text-lg font-semibold text-white border-2 border-[#ef3b1b]/90 bg-white/5 backdrop-blur-md hover:scale-105 transition">
          Explore Programs
        </a>
      </div>

      <!-- Stats -->
      <div class="flex flex-wrap gap-4 mt-8">
        <article class="min-w-[190px] bg-[#141414] rounded-xl p-5 border border-black shadow-[0_6px_20px_#000,0_10px_8px_-5px_rgba(239,59,27,0.9)] hover:scale-[1.03] hover:-translate-y-1 transition">
          <div class="stat-value text-2xl font-bold text-white" data-target="96" data-suffix="%">0%</div>
          <div class="text-white/85 text-sm">Client Satisfaction</div>
        </article>
        <article class="min-w-[190px] bg-[#141414] rounded-xl p-5 border border-black shadow-[0_6px_20px_#000,0_10px_8px_-5px_rgba(239,59,27,0.9)] hover:scale-[1.03] hover:-translate-y-1 transition">
          <div class="stat-value text-2xl font-bold text-white" data-target="1">0</div>
          <div class="text-white/85 text-sm">Year Experience</div>
        </article>
        <article class="min-w-[190px] bg-[#141414] rounded-xl p-5 border border-black shadow-[0_6px_20px_#000,0_10px_8px_-5px_rgba(239,59,27,0.9)] hover:scale-[1.03] hover:-translate-y-1 transition">
          <div class="stat-value text-2xl font-bold text-white" data-target="200" data-prefix="+">0</div>
          <div class="text-white/85 text-sm">Active Members</div>
        </article>
      </div>
    </div>
  </section>



<body class="bg-black text-white">

<!-- About Us -->
<section id="about" class="pt-32 pb-24 relative overflow-hidden">
  <!-- Radial Background Pattern (Orange) -->
  <div class="absolute inset-0">
    <div class="absolute inset-0 -z-20 h-full w-full 
                [background:radial-gradient(125%_125%_at_50%_10%,#000_40%,#fb923c_100%)]">
    </div>
  </div>

  <!-- Blobs -->
  <div class="absolute inset-0 -z-10">
    <div class="absolute top-0 left-0 w-96 h-96 bg-gradient-to-r from-orange-500/40 to-red-600/40 rounded-full filter blur-3xl opacity-70 animate-blob"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-r from-red-600/40 to-orange-500/40 rounded-full filter blur-3xl opacity-60 animate-blob animation-delay-2000"></div>
  </div>

  <div class="container mx-auto px-6 relative z-10">
    <!-- Title -->
    <div class="text-center mb-16" data-aos="fade-up">
      <h2 class="text-5xl sm:text-6xl font-extrabold text-white mb-4">
        About Us
      </h2>
      <p class="text-xl bg-gradient-to-r from-orange-400 to-red-500 bg-clip-text text-transparent font-semibold">
        Your journey starts here
      </p>
    </div>

    <!-- Content -->
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center gap-12">
      <!-- Left Text -->
      <div class="md:w-1/2" data-aos="fade-right" data-aos-delay="200">
        <p class="text-lg leading-relaxed text-gray-300 mb-6">
          At <span class="text-orange-400 font-semibold">Verso Gym</span>, we're more than just a fitness center; we're a community dedicated to helping you achieve your health and wellness goals. 
          <span class="text-red-400 font-semibold">Our mission</span> is to provide a supportive and motivating environment where individuals of all fitness levels can thrive. 
          With state-of-the-art equipment, expert trainers, and a variety of classes, we're committed to empowering you on your fitness journey.
        </p>
        <div class="flex items-center text-gray-300 mb-8">
          <span class="material-symbols-outlined text-orange-400 text-3xl mr-3">schedule</span>
          <p class="text-lg">Our gym is open from 8AM to 9PM every Monday to Saturday, giving you plenty of time to crush your fitness goals.</p>
        </div>
        <a href="#membership" class="nav-link bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold py-3 px-8 rounded-full shadow-lg hover:opacity-90 transition-all duration-300 text-lg transform hover:scale-105">
          Join Now
        </a>
      </div>

      <!-- Right Image -->
      <div class="md:w-1/2 relative" data-aos="fade-left" data-aos-delay="400">
        <div class="relative p-6 rounded-xl bg-gradient-to-br from-orange-500/10 to-red-500/10 shadow-inner">
          <img src="img/gallery-1.jpg" alt="Gym interior with modern equipment" class="rounded-lg shadow-2xl w-full h-auto object-cover transition-transform duration-500 hover:scale-105"/>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Gallery -->
<section class="py-24 relative overflow-hidden">
  <!-- Background with Grid + Orange Gradient Glow -->
  <div class="absolute inset-0 -z-10">
    <!-- Grid Pattern -->
    <div class="absolute inset-0 h-full w-full 
                bg-[#111111] 
                bg-[linear-gradient(to_right,rgba(251,146,60,0.15)_1px,transparent_1px),
                    linear-gradient(to_bottom,rgba(251,146,60,0.15)_1px,transparent_1px)] 
                bg-[size:18px_28px]">
    </div>
    <!-- Orange Spotlight Gradient -->
    <div class="absolute inset-0 h-full w-full 
                [background:radial-gradient(60%_40%_at_50%_5%,rgba(251,146,60,0.35)_0%,transparent_90%)]">
    </div>
  </div>

  <div class="container mx-auto px-6 relative z-10">
    <!-- Title -->
    <div class="text-center mb-16" data-aos="fade-up">
      <h3 class="text-4xl sm:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-300 mb-4">
        Our Gym in Action
      </h3>
      <p class="text-lg text-gray-400 max-w-2xl mx-auto">
        Step inside and see the energy, dedication, and community that drives our members every day.
      </p>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Card -->
      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="100">
        <img src="img/gallery-1.jpg" alt="Strength Training" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>

      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="200">
        <img src="img/gallery-2.jpg" alt="Yoga Session" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>

      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="300">
        <img src="img/gallery-3.jpg" alt="Cardio Zone" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>

      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="400">
        <img src="img/gallery-4.jpg" alt="Group Class" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>

      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="500">
        <img src="img/gallery-5.jpg" alt="Personal Training" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>

      <div class="group relative overflow-hidden rounded-2xl shadow-xl border border-orange-500/20 
                  transform transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-orange-500/20" 
           data-aos="fade-up" data-aos-delay="600">
        <img src="img/gallery-6.jpg" alt="Community Vibes" class="w-full h-72 object-cover transition-transform duration-700 group-hover:scale-110"/>
      </div>
    </div>
  </div>
</section>


<style>
/* Floating blobs animation */
@keyframes blob {
  0%   { transform: translate(0px, 0px) scale(1); }
  33%  { transform: translate(30px, -50px) scale(1.1); }
  66%  { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 18s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>

</body>
</html>


  <!-- Services -->
  <section id="services" class="w-full bg-brand-dark parallax">
    <div class="w-full bg-black/60">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="text-center" data-aos="fade-up">
          <h1 class="text-4xl sm:text-5xl font-bold text-white relative inline-block pb-2 section-title">Our Services
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2/1 h-1 bg-brand-orange"></span>
          </h1>
          <p class="mt-4 text-lg text-brand-orange">Transform your body and mind</p>
        </div>
        <div class="mt-20 grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3">
          <div class="service-card rounded-xl overflow-hidden shadow-lg shadow-[var(--glow-color)]/20 transition-all duration-300 hover:scale-105 hover:shadow-[var(--glow-color)]/60 group" data-aos="fade-up" data-aos-delay="100">
            <div class="relative">
              <img alt="Strength Training" class="w-full h-56 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDvOA0mVG507fmXsgI0JAjBhiDS740fYaa0nJ3rt525VURofbNjARJmcEEyFXdMXygAlUtj4rqpzS60adFnV7xGQvgxrpCp-SoirNtWKBtWL53xX5u_-IQhG5Vy7NfAzgY9mPrMIY3k7HjtArM15rmWqr8AV2YZtzlD2NnGJ5MgVYoKxZmC06urcjR6VL46M8xU6gLcmDYOFxsyFo_J56onIlXgTkGbVoWUCWOaRhU6AmQtEjV9gNeSEYw4bbKfMOyDsX7u7tODEw95"/>
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
            <div class="p-6 service-card-inner">
              <h3 class="text-xl font-semibold text-white">Strength Training</h3>
              <p class="mt-2 text-base text-gray-400">Build muscle and increase your power with our comprehensive strength training programs.</p>
            </div>
          </div>
          <div class="service-card rounded-xl overflow-hidden shadow-lg shadow-[var(--glow-color)]/20 transition-all duration-300 hover:scale-105 hover:shadow-[var(--glow-color)]/60 group" data-aos="fade-up" data-aos-delay="200">
            <div class="relative">
              <img alt="Cardio Fitness" class="w-full h-56 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCtM5RiPNkRbdY1R-JfTcT9uSC6dMjHh-I_NTHYYhm0aXBDyNHxqrwlpA617y5IWhsImE9iGCdynC_4hfrs05o07okgeUQHbg9L1s-HEYPMmSzjSWI4fVVdCRkk9CZHEQUG_Ju-E9AnRJoev7qvkOkoOn9D-vX65aSAW6L9tW5yW1gUPGuTlBYYa6-ZllaBTQFZHHJn-bu9u1Y1x6DvTBMqQ9SODIYXUqkGUw_HOJSmEw2eUJj0LaTjFWQz0pd4rif3-udnH0ildGrz"/>
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
            <div class="p-6 service-card-inner">
              <h3 class="text-xl font-semibold text-white">Cardio Fitness</h3>
              <p class="mt-2 text-base text-gray-400">Improve your cardiovascular health and endurance with our varied cardio workouts.</p>
            </div>
          </div>
          <div class="service-card rounded-xl overflow-hidden shadow-lg shadow-[var(--glow-color)]/20 transition-all duration-300 hover:scale-105 hover:shadow-[var(--glow-color)]/60 group" data-aos="fade-up" data-aos-delay="300">
            <div class="relative">
              <img alt="Personal Training" class="w-full h-56 object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDtu1y0PTebVC-UWVCoyDdooNvs6RzBmxnYURz_ly21R3KANa9dnCZVwvpqH-U2nXyAcUEWoUFgh-NHQaOzKn1KJz6lZBnta5Nx41WEH3J-bmqrOVM3Nb5yiUudo_4Lug4W9llnUVVePrrXNpa5dnfm3pgIPalbsRgp1weKL_S43ZfNAHIaMK111WdVPpxZVR5VdJafzJKtzk7mYs4Ji4YL5gKR4xVpawKQ_FidpWJP0Jpo7N12Jeoj7CKn65GAa8JQx8wSVt7p2Ib9"/>
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            </div>
            <div class="p-6 service-card-inner">
              <h3 class="text-xl font-semibold text-white">Personal Training</h3>
              <p class="mt-2 text-base text-gray-400">Achieve your fitness goals faster with personalized training plans tailored to your needs.</p>
            </div>
          </div>
          </div>
        <div class="mt-24 text-center" data-aos="fade-up">
          <h2 class="text-3xl sm:text-4xl font-bold text-white relative inline-block pb-2 section-title">Facilities &amp; Equipment
            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-2/1 h-1 bg-brand-orange"></span>
          </h2>
          <p class="mt-6 max-w-2xl mx-auto text-lg text-gray-400">
            Our state-of-the-art facilities are equipped with the latest fitness technology to help you achieve your goals. We offer a wide range of equipment and amenities, including:
          </p>
        </div>
        <div class="mt-12 max-w-lg mx-auto" data-aos="fade-up">
          <ul class="space-y-4">
            <li class="flex items-center">
              <span class="material-symbols-outlined text-brand-orange text-2xl">check_circle</span>
              <span class="ml-3 text-base text-gray-400">Free Weights Area</span>
            </li>
            <li class="flex items-center">
              <span class="material-symbols-outlined text-brand-orange text-2xl">check_circle</span>
              <span class="ml-3 text-base text-gray-400">Cardio Machines</span>
            </li>
            <li class="flex items-center">
              <span class="material-symbols-outlined text-brand-orange text-2xl">check_circle</span>
              <span class="ml-3 text-base text-gray-400">Changing Rooms &amp; Showers</span>
            </li>
          </ul>
        </div>
        <div class="mt-16 text-center" data-aos="fade-up">
          <a class="nav-link inline-block bg-brand-orange text-white font-bold text-lg px-8 py-4 rounded-full hover:bg-orange-600 transition-colors duration-300" href="#membership">
            Join Now
          </a>
        </div>
      </div>
    </div>
  </section>

<!-- Trainers -->
<section id="coaching" 
  class="py-24 md:py-32 relative overflow-hidden bg-black">

  <!-- Grain overlay -->
  <div class="grain-overlay"></div>

  <div class="container mx-auto px-6 relative z-10">
    <!-- Section Heading -->
    <div class="text-center mb-20" data-aos="fade-up">
      <h2 class="text-5xl md:text-6xl font-extrabold leading-tight bg-clip-text text-transparent bg-gradient-to-r from-red-500 via-orange-400 to-orange-500 animate-text-gradient">
        Meet Our Trainers
      </h2>
      <p class="mt-4 text-lg text-gray-300 max-w-2xl mx-auto">Expert coaches committed to your transformation journey.</p>
    </div>

    <!-- Trainer Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
      <!-- Trainer 1 -->
      <div class="group relative rounded-3xl overflow-hidden shadow-lg shadow-orange-600/10 hover:shadow-orange-600/40 transform hover:-translate-y-4 hover:rotate-1 hover:scale-[1.02] transition-all duration-700 ease-[cubic-bezier(.4,0,.2,1)] glass-dark" data-aos="fade-right">
        <img src="img/trainer-1.jpg" alt="Trainer Allynah Mendoza" loading="lazy"
             class="absolute inset-0 w-full h-full object-cover opacity-70 transition-transform duration-1000 ease-out group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/60 to-transparent group-hover:from-orange-600/30 group-hover:via-black/70 group-hover:to-transparent transition-all duration-700"></div>
        <div class="relative p-10 flex flex-col justify-end h-[520px] z-20">
          <h3 class="text-3xl font-bold text-white mb-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700">
            Allynah Mendoza
          </h3>
          <p class="text-orange-400 font-semibold uppercase tracking-wide text-sm mb-4 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700 delay-100">
            Strength Training
          </p>
          <p class="text-zinc-300 font-light opacity-0 translate-y-6 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700 delay-200">
          A certified trainer with 2 years' experience, specializes in strength training, promoting balance and mindfulness.
          </p>
          <a href="#booking" class="mt-8 w-fit bg-gradient-to-r from-orange-600 to-red-500 text-white font-bold py-3 px-8 rounded-full shadow-lg shadow-orange-600/30 hover:scale-105 transition-all duration-500 opacity-0 translate-y-6 group-hover:opacity-100 group-hover:translate-y-0 delay-300">
            Book a Session
          </a>
        </div>
      </div>

      <!-- Trainer 2 -->
      <div class="group relative rounded-3xl overflow-hidden shadow-lg shadow-red-600/10 hover:shadow-red-600/40 transform hover:-translate-y-4 hover:-rotate-1 hover:scale-[1.02] transition-all duration-700 ease-[cubic-bezier(.4,0,.2,1)] glass-dark" data-aos="fade-left">
        <img src="img/trainer-2.jpg" alt="Trainer Shin Jiro Tenebro" loading="lazy"
             class="absolute inset-0 w-full h-full object-cover opacity-70 transition-transform duration-1000 ease-out group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/60 to-transparent group-hover:from-red-600/30 group-hover:via-black/70 group-hover:to-transparent transition-all duration-700"></div>
        <div class="relative p-10 flex flex-col justify-end h-[520px] z-20">
          <h3 class="text-3xl font-bold text-white mb-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700">
            Shin Jiro Tenebro
          </h3>
          <p class="text-red-400 font-semibold uppercase tracking-wide text-sm mb-4 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700 delay-100">
           Rugby Athletic Training
          </p>
          <p class="text-zinc-300 font-light opacity-0 translate-y-6 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-700 delay-200">
            With 4 years of experience, an active rugby athlete, a certified Lv1 rugby coach, and have guided athletes in various Lv1 training.
          </p>
          <a href="#booking" class="mt-8 w-fit bg-gradient-to-r from-red-600 to-orange-500 text-white font-bold py-3 px-8 rounded-full shadow-lg shadow-red-600/30 hover:scale-105 transition-all duration-500 opacity-0 translate-y-6 group-hover:opacity-100 group-hover:translate-y-0 delay-300">
            Book a Session
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

 <!-- Membership & Pricing -->
<section id="membership" class="py-20 relative overflow-hidden bg-black">

  <div class="text-center mb-12 relative z-10">
    <h1 class="text-4xl font-bold text-white mb-2">Verso Gym Membership Plans</h1>
    <p class="text-lg text-gray-400">Affordable rates for gym access, coaching, and packages tailored to your fitness goals.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto w-full relative z-10">
    
    <!-- Coaching Fees -->
    <div class="border border-white/10 bg-black/20 backdrop-blur-2xl p-8 rounded-2xl flex flex-col transition-all duration-300 hover:border-orange-500/30 hover:shadow-2xl hover:shadow-orange-900/40 hover:-translate-y-2 group">
      <h2 class="text-2xl font-semibold text-white mb-2">Coaching Fees</h2>
      <p class="text-gray-400 mb-6">Structured coaching packages available.</p>
      <div class="flex items-baseline mb-6">
        <span class="text-5xl font-bold text-white">₱150</span>
        <span class="text-sm text-gray-400 ml-2">/ session</span>
      </div>
     <a href="#booking" ><button class="bg-white/5 border border-white/10 text-white font-semibold py-3 px-6 rounded-lg hover:bg-white/10 transition-all duration-300 mb-6 group-hover:bg-orange-600 group-hover:border-orange-500 group-hover:text-white">
        Book Coaching
      </button></a>
      <ul class="space-y-4 text-gray-300 flex-grow">
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>1 Week: ₱600</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>2 Weeks: ₱1,200</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>1 Month: ₱2,500</li>
      </ul>
    </div>

    <!-- Membership (Most Popular) -->
    <div class="bg-gradient-to-br from-orange-700 to-orange-70 backdrop-blur-2xl p-10 rounded-2xl flex flex-col relative transform md:scale-105 shadow-2xl shadow-orange-700/30 transition-all duration-300 hover:md:scale-110 border border-orange-500/50 group">
      <div class="absolute top-0 right-0 bg-white text-orange-700 text-xs font-bold px-4 py-1.5 rounded-bl-lg rounded-tr-lg">MOST POPULAR</div>
      <h2 class="text-2xl font-semibold text-white mb-2 pt-6">Membership</h2>
      <p class="text-orange-100 mb-6">Exclusive gym membership perks.</p>
      <div class="flex items-baseline mb-6">
        <span class="text-5xl font-bold text-white">₱850</span>
        <span class="text-sm text-orange-100 ml-2">/ year</span>
      </div>
      <a class="bg-white text-orange-700 font-semibold py-3 px-6 rounded-lg hover:bg-orange-50 transition-all duration-300 mb-6" href="register.php"><button>
        Join Now
  </button></a>
      <ul class="space-y-4 text-orange-50 flex-grow">
        <li class="flex items-center"><span class="material-symbols-outlined mr-3">check_circle</span>Free treadmill use</li>
        <li class="flex items-center"><span class="material-symbols-outlined mr-3">check_circle</span>Access to member-only promos</li>
        <li class="flex items-center"><span class="material-symbols-outlined mr-3">check_circle</span>Free Verso Gym T-shirt</li>
      </ul>
    </div>

    <!-- Gym Fees -->
    <div class="border border-white/10 bg-black/20 backdrop-blur-2xl p-8 rounded-2xl flex flex-col transition-all duration-300 hover:border-orange-500/30 hover:shadow-2xl hover:shadow-orange-900/40 hover:-translate-y-2 group">
      <h2 class="text-2xl font-semibold text-white mb-2">Gym Fees</h2>
      <p class="text-gray-400 mb-6">Flexible short-term gym access.</p>
      <div class="flex items-baseline mb-6">
        <span class="text-5xl font-bold text-white">₱60</span>
        <span class="text-sm text-gray-400 ml-2">/ walk-in</span>
      </div>
      <a href="register.php"><button class="bg-white/5 border border-white/10 text-white font-semibold py-3 px-6 rounded-lg hover:bg-white/10 transition-all duration-300 mb-6 group-hover:bg-orange-600 group-hover:border-orange-500 group-hover:text-white">
        Get Started
      </a></button>
      <ul class="space-y-4 text-gray-300 flex-grow">
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>1 Week: ₱250</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>2 Weeks: ₱500 (+1 free visit)</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>1 Month: ₱1,000 (+4 free visits)</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>Treadmill only: ₱30</li>
        <li class="flex items-center"><span class="material-symbols-outlined text-orange-400 mr-3">check_circle</span>Gym + treadmill: ₱80</li>
      </ul>
    </div>

  </div>
</section>

<script>
</script>


<!-- Gym with Coaching -->
<div class="mt-20 max-w-5xl mx-auto px-6" data-aos="fade-up">
  <!-- Heading -->
  <h2 class="text-3xl sm:text-4xl font-bold text-white text-center mb-12">
    Gym with <span class="text-brand-orange">Coaching</span> Package
  </h2>

  <!-- Package Grid -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    
    <!-- Package Item -->
    <div class="glassy rounded-2xl p-8 text-center border border-white/10 shadow-lg hover:scale-105 hover:shadow-[0_0_30px_rgba(249,115,22,0.6)] transition duration-300 ease-in-out flex flex-col justify-between"
         data-aos="fade-up" data-aos-delay="100">
      <div>
        <p class="font-semibold text-xl text-white mb-2">1 Week</p>
        <p class="text-3xl font-bold text-brand-orange">₱850</p>
        <p class="mt-4 text-sm text-gray-400">Includes personalized coaching sessions and gym access.</p>
      </div>
      <a href="auth/register.html">
        <button class="mt-6 w-full bg-brand-orange text-white py-3 rounded-lg font-semibold hover:opacity-90 transition">
          Choose Plan
        </button>
      </a>
    </div>

    <!-- Package Item (Best Value) -->
    <div class="glassy rounded-2xl p-8 text-center border-2 border-brand-orange shadow-[0_0_40px_rgba(249,115,22,0.7)] transform scale-105 relative overflow-hidden flex flex-col justify-between"
         data-aos="fade-up" data-aos-delay="200">
      <!-- Best Value Ribbon -->
      <span class="absolute top-4 right-[-28px] bg-gradient-to-r from-amber-500 to-pink-500 text-white text-xs font-bold py-1 px-10 rotate-45 shadow-md">
        Best Value
      </span>
      <div>
        <p class="font-semibold text-xl text-white mb-2">2 Weeks</p>
        <p class="text-3xl font-bold text-brand-orange">₱1,700</p>
        <p class="mt-4 text-sm text-gray-400">Twice the time, twice the results — full coaching support.</p>
      </div>
      <a href="auth/register.html">
        <button class="mt-6 w-full bg-white text-gray-900 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
          Choose Plan
        </button>
      </a>
    </div>

    <!-- Package Item -->
    <div class="glassy rounded-2xl p-8 text-center border border-white/10 shadow-lg hover:scale-105 hover:shadow-[0_0_30px_rgba(249,115,22,0.6)] transition duration-300 ease-in-out flex flex-col justify-between"
         data-aos="fade-up" data-aos-delay="300">
      <div>
        <p class="font-semibold text-xl text-white mb-2">1 Month</p>
        <p class="text-3xl font-bold text-brand-orange">₱3,500</p>
        <p class="mt-4 text-sm text-gray-400">A complete month of coaching guidance and gym training.</p>
      </div>
      <a href="#booking" class="scroll-to-booking">
        <button class="mt-6 w-full bg-brand-orange text-white py-3 rounded-lg font-semibold hover:opacity-90 transition">
          Choose Plan
        </button>
      </a>
    </div>

  </div>
</div>

<!-- Smooth Scroll Script -->
<script>
  document.querySelectorAll('a.scroll-to-booking').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelector('#booking').scrollIntoView({
        behavior: 'smooth'
      });
    });
  });
</script>


<!-- FAQs -->
<div class="mt-24 max-w-4xl mx-auto" data-aos="fade-up">
  <h2 class="text-3xl sm:text-4xl font-bold text-white text-center mb-12">Frequently Asked Questions</h2>
  <div class="space-y-4" id="faq-container">
    
    <!-- FAQ Item -->
    <div class="faq-item glassy rounded-lg overflow-hidden transition-all duration-500">
      <button class="faq-toggle w-full flex justify-between items-center p-6 focus:outline-none" aria-expanded="false">
        <span class="font-semibold text-lg text-white">Can I change my plan later?</span>
        <svg class="faq-icon w-6 h-6 text-orange-400 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
      </button>
      <div class="faq-answer max-h-0 opacity-0 transform translate-y-2 overflow-hidden transition-all duration-500 ease-in-out px-6 text-gray-400">
        <p class="py-4">Yes! You can switch from weekly to monthly packages or add coaching sessions anytime. Just message our page for adjustments.</p>
      </div>
    </div>

    <!-- FAQ Item -->
    <div class="faq-item glassy rounded-lg overflow-hidden transition-all duration-500">
      <button class="faq-toggle w-full flex justify-between items-center p-6 focus:outline-none" aria-expanded="false">
        <span class="font-semibold text-lg text-white">Is there a free trial?</span>
        <svg class="faq-icon w-6 h-6 text-orange-400 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
      </button>
      <div class="faq-answer max-h-0 opacity-0 transform translate-y-2 overflow-hidden transition-all duration-500 ease-in-out px-6 text-gray-400">
        <p class="py-4">We don't offer free trials, but walk-in rates are available so you can try the gym before committing to a plan.</p>
      </div>
    </div>

    <!-- FAQ Item -->
    <div class="faq-item glassy rounded-lg overflow-hidden transition-all duration-500">
      <button class="faq-toggle w-full flex justify-between items-center p-6 focus:outline-none" aria-expanded="false">
        <span class="font-semibold text-lg text-white">What payment methods do you accept?</span>
        <svg class="faq-icon w-6 h-6 text-orange-400 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
      </button>
      <div class="faq-answer max-h-0 opacity-0 transform translate-y-2 overflow-hidden transition-all duration-500 ease-in-out px-6 text-gray-400">
        <p class="py-4">We accept GCash and PayMaya for secure and convenient payments.</p>
      </div>
    </div>

  </div>
</div>

<!-- Glow Animation -->
<style>
  @keyframes glow {
    0%, 100% { box-shadow: 0 0 10px rgba(251, 146, 60, 0.5); }
    50% { box-shadow: 0 0 20px rgba(251, 146, 60, 0.9); }
  }
  .glow-active {
    animation: glow 1.5s ease-in-out infinite;
  }
</style>

<!-- FAQ Script -->
<script>
  document.querySelectorAll('.faq-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const answer = button.nextElementSibling;
      const icon = button.querySelector('.faq-icon');
      const parent = button.closest(".faq-item");
      const isOpen = button.getAttribute("aria-expanded") === "true";

      // Close all
      document.querySelectorAll('.faq-answer').forEach(a => {
        a.style.maxHeight = null;
        a.style.opacity = 0;
        a.style.transform = "translateY(0.5rem)";
      });
      document.querySelectorAll('.faq-toggle').forEach(b => b.setAttribute("aria-expanded", "false"));
      document.querySelectorAll('.faq-icon').forEach(i => i.classList.remove("rotate-45"));
      document.querySelectorAll('.faq-item').forEach(item => item.classList.remove("ring-2", "ring-orange-400/80", "bg-orange-500/10", "glow-active"));

      // Open clicked if not already open
      if (!isOpen) {
        button.setAttribute("aria-expanded", "true");
        answer.style.maxHeight = answer.scrollHeight + "px";
        answer.style.opacity = 1;
        answer.style.transform = "translateY(0)";
        icon.classList.add("rotate-45");
        parent.classList.add("ring-2", "ring-orange-400/80", "bg-orange-500/10", "glow-active");
      }
    });
  });
</script>



<!-- Booking and Contact -->
<section id="booking" class="relative bg-brand-dark font-display text-gray-300 antialiased">
  <div id="booking-spotlight1" class="pointer-events-none absolute w-[500px] h-[500px] rounded-full bg-orange-600/20 blur-[160px] opacity-0 transform -translate-x-1/2 -translate-y-1/2"></div>
  <div id="booking-spotlight2" class="pointer-events-none absolute w-[250px] h-[250px] rounded-full bg-orange-400/30 blur-[100px] opacity-0 transform -translate-x-1/2 -translate-y-1/2"></div>
  <div class="gradient-blur" style="bottom: -250px; right: -250px;"></div>
  <div class="container mx-auto px-4 py-20 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-y-16 lg:grid-cols-5 lg:gap-x-12">
      <div class="lg:col-span-3 space-y-12">
        <div class="space-y-8 glass-effect p-8 rounded-xl" data-aos="fade-up" id="booking-form-container">
          <div class="space-y-4">
            <h2 class="text-4xl font-bold text-white"><span class="section-title">Booking</span></h2>
            <p class="text-lg text-gray-400">Schedule your personalized fitness session with our expert coaches. Fill out the form below to get started on your fitness journey.</p>
          </div>
          <form class="space-y-6 text-lg" id="booking-form-element" action="backend/booking.php" method="POST">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" id="name" name="name" placeholder="Full Name" required="" type="text" value=""/>
              <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" id="email" name="email" placeholder="Email Address" required="" type="email" value=""/>
            </div>
            <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Phone number" name="phone" type="tel"/>
            <select class="form-select w-full rounded-lg border-black-700 bg-black/50 p-4 text-gray-400 focus:ring-2 focus:ring-primary focus:border-transparent" id="service" name="service" required="">
              <option disabled="" value="">Select Service</option>
              <option selected="" value="Personal Training">Personal Training</option>
              <option value="Group Class">Group Class</option>
              <option value="Nutrition Coaching">Nutrition Coaching</option>
            </select>
            <div class="bg-black/50 rounded-lg border border-gray-700 p-4">
              <div class="flex items-center justify-between mb-4">
                <button class="p-2 rounded-full hover:bg-gray-700 transition-colors btn-hover" type="button" id="prev-month">
                  <span class="material-symbols-outlined text-white">chevron_left</span>
                </button>
                <div class="text-lg font-bold text-white" id="month-year"></div>
                <button class="p-2 rounded-full hover:bg-gray-700 transition-colors btn-hover" type="button" id="next-month">
                  <span class="material-symbols-outlined text-white">chevron_right</span>
                </button>
              </div>
              <div class="grid grid-cols-7 gap-1 text-center text-gray-400 text-sm font-medium mb-2">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
              </div>
              <div class="grid grid-cols-7 gap-2 text-center" id="calendar-days"></div>
              <input type="hidden" name="date" id="selected-date-input">
            </div>
            <select class="form-select w-full rounded-lg border-gray-700 bg-black/50 p-4 text-gray-400 focus:ring-2 focus:ring-primary focus:border-transparent glass-dark" id="time" name="time" required="">
              <option disabled="" value="">Select Time</option>
            </select>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">  </span>
              <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 pl-12 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Input coach name" name="coach" type="text"/>
            </div>
            <textarea class="form-textarea w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Additional Notes (e.g., fitness goals, preferred time)" name="notes" rows="4"></textarea>
            <button class="w-full rounded-lg bg-primary px-6 py-4 text-lg text-white font-bold tracking-wide transition-all duration-300 hover:bg-opacity-90 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background-dark focus:ring-primary btn-hover" type="submit">Book Now</button>
          </form>
        </div>
        <div class="hidden space-y-8 glass-effect p-8 rounded-xl" data-aos="fade-up" id="confirmation-container">
          <div class="text-center space-y-6">
            <div class="flex justify-center">
              <div class="bg-green-500/20 p-4 rounded-full">
                <span class="material-symbols-outlined text-green-400 text-5xl">check_circle</span>
              </div>
            </div>
            <h2 class="text-4xl font-bold text-white">Booking Confirmed!</h2>
            <p class="text-lg text-gray-400">Thank you, <span class="font-semibold text-white" id="conf-name"></span>! Your session has been successfully booked. A confirmation email has been sent to <span class="font-semibold text-white" id="conf-email"></span>.</p>
          </div>
          <div class="border-t border-gray-700 my-6"></div>
          <div class="space-y-6">
            <h3 class="text-2xl font-bold text-white"><i class="fa-solid fa-calendar-check mr-2"></i>Booking Summary</h3>
            <div class="space-y-4 text-lg">
              <div class="flex justify-between">
                <span class="text-gray-400">Service:</span>
                <span class="font-medium text-white" id="conf-service"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Date:</span>
                <span class="font-medium text-white" id="conf-date"></span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Time:</span>
                <span class="font-medium text-white">To be confirmed</span>
              </div>
            </div>
          </div>
          <div class="pt-6">
            <button class="w-full rounded-lg bg-primary px-1 py-1 text-lg text-white font-bold tracking-wide transition-all duration-300 hover:bg-opacity-90 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background-dark focus:ring-primary btn-hover" id="new-booking-btn">Make Another Booking</button>
          </div>
        </div>
        <div id="contact" class="space-y-8 glass-effect p-8 rounded-xl relative" data-aos="fade-up" data-aos-delay="100">
          <div id="contact-spotlight1" class="pointer-events-none absolute w-[500px] h-[500px] rounded-full bg-orange-600/20 blur-[160px] opacity-0 transform -translate-x-1/2 -translate-y-1/2"></div>
          <div id="contact-spotlight2" class="pointer-events-none absolute w-[250px] h-[250px] rounded-full bg-orange-400/30 blur-[100px] opacity-0 transform -translate-x-1/2 -translate-y-1/2"></div>
          <div class="space-y-4">
            <h2 class="text-4xl font-bold text-white">Contact Us</h2>
            <p class="text-lg text-gray-400">Have questions or need assistance? Reach out to us using the form below, and we'll get back to you as soon as possible.</p>
          </div>
          <form class="space-y-6 text-lg">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Your Name" required="" type="text"/>
              <input class="form-input w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Your Email" required="" type="email"/>
            </div>
            <textarea class="form-textarea w-full rounded-lg border-gray-700 bg-black/50 p-4 placeholder-gray-500 text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Your Message" required="" rows="5"></textarea>
            <button class="w-full rounded-lg bg-primary px-6 py-4 text-lg text-white font-bold tracking-wide transition-all duration-300 hover:bg-opacity-90 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background-dark focus:ring-primary btn-hover" type="submit">Send Message</button>
          </form>
        </div>
      </div>
      <div class="lg:col-span-2 lg:sticky lg:top-20 lg:self-start space-y-8" data-aos="fade-up" data-aos-delay="200">
        <div class="glass-effect p-8 rounded-xl space-y-6">
          <div class="space-y-4">
            <h2 class="text-4xl font-bold text-white">Our Location</h2>
            <p class="text-lg text-gray-400">Find us at the address below. We're excited to welcome you to our fitness community.</p>
            <p class="text-lg text-gray-300 font-medium">Verso Gym, Blk 14 Lot 1 Villa Zaragosa, Turo, Bocaue, Philippines</p>
          </div>
          <div class="space-y-4 pt-4">
            <h3 class="text-2xl font-bold text-white"><i class="fa-solid fa-phone mr-2"></i>Contact Details</h3>
            <div class="space-y-4 text-lg">
              <a class="flex items-center gap-4 text-gray-300 hover:text-primary transition-colors" href="mailto:villaversogym@gmail.com">
                <span class="material-symbols-outlined text-2xl text-primary"> email </span>
                <span>villaversogym@gmail.com</span>
              </a>
              <a class="flex items-center gap-4 text-gray-300 hover:text-primary transition-colors" href="tel:+1234567890">
                <span class="material-symbols-outlined text-2xl text-primary"> call </span>
                <span>+63 928-438-2365</span>
              </a>
              <a class="flex items-center gap-4 text-gray-300 hover:text-primary transition-colors" href="https://www.facebook.com/profile.php?id=61558188146814" rel="noopener noreferrer" target="_blank">
                <svg class="w-7 h-7 text-primary" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v7.045C18.343 21.128 22 16.991 22 12z"></path>
                </svg>
                <span>facebook</span>
              </a>
            </div>
          </div>
        </div>
       <div class="h-96 w-full rounded-xl overflow-hidden shadow-lg border glass-effect" id="map"></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script>
  var map = L.map('map').setView([14.7957, 120.9253], 15); // Zoom 15 for detail
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
  }).addTo(map);
  L.marker([14.7957, 120.9253]).addTo(map).bindPopup('Blk 14 Lot 1 Villa Zaragosa').openPopup();
</script>
      </div>
    </div>
  </div>
</section>

<!-- Gym Motivation Carousel -->
<div class="relative w-full overflow-hidden bg-black py-14">
  <!-- Gradient Fade Edges -->
  <div class="pointer-events-none absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-black via-black/80 to-transparent z-10"></div>
  <div class="pointer-events-none absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-black via-black/80 to-transparent z-10"></div>

  <!-- Scrolling Wrapper -->
  <div class="flex w-max animate-marquee">
    <!-- Quotes Set 1 -->
    <div class="flex space-x-16 px-20 text-xl md:text-3xl font-extrabold uppercase tracking-wide 
                text-transparent bg-gradient-to-r from-orange-400 via-red-500 to-orange-400 
                bg-clip-text drop-shadow-[0_0_12px_rgba(255,100,0,0.5)]">
      <span>NO PAIN NO GAIN</span>
      <span>PUSH YOUR LIMITS</span>
      <span>TRAIN HARD STAY STRONG</span>
      <span>SWEAT IS FAT CRYING</span>
      <span>STRONGER EVERY DAY</span>
      <span>WORK HARD DREAM BIG</span>
    </div>

    <!-- Quotes Set 2 (Duplicate for seamless loop) -->
    <div class="flex space-x-16 px-20 text-xl md:text-3xl font-extrabold uppercase tracking-wide 
                text-transparent bg-gradient-to-r from-orange-400 via-red-500 to-orange-400 
                bg-clip-text drop-shadow-[0_0_12px_rgba(255,100,0,0.5)]">
      <span>NO PAIN NO GAIN</span>
      <span>PUSH YOUR LIMITS</span>
      <span>TRAIN HARD STAY STRONG</span>
      <span>SWEAT IS FAT CRYING</span>
      <span>STRONGER EVERY DAY</span>
      <span>WORK HARD DREAM BIG</span>
    </div>
  </div>
</div>

<!-- Tailwind Custom Animations -->
<style>
@keyframes marquee {
  0%   { transform: translateX(0); }
  50%  { transform: translateX(-50%); }
  100% { transform: translateX(0); }
}
.animate-marquee {
  animation: marquee 25s linear infinite alternate;
}
</style>


  <!-- Footer -->
  <footer class="relative bg-gradient-to-b from-black via-zinc-9 to-black text-gray-400 pt-24 pb-1 overflow-hidden">
    <!-- Gradient blur background -->
    <div class="absolute inset-0 -z-1">
      <div class="absolute -top-20 -left-20 w-[28rem] h-[28rem] rounded-full bg-gradient-to-r from-red-600 to-orange-500 opacity-30 blur-[140px]"></div>
      <div class="absolute bottom-0 right-0 w-[22rem] h-[22rem] rounded-full bg-gradient-to-r from-orange-600 to-red-500 opacity-20 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-4 gap-12" data-aos="fade-up">
      <!-- Brand -->
      <div data-aos="fade-up" data-aos-delay="100">
        <h3 class="text-white text-2xl font-bold mb-4">Verso<span class="text-[#ef3b1b]">Gym</span></h3>
        <p class="text-gray-400 mb-6">Achieve your fitness goals with expert coaching, modern facilities, and a supportive community.</p>
        <div class="flex gap-4">
          <a href="https://www.facebook.com/profile.php?id=61558188146814" class="hover:text-white transition transform hover:scale-110 social-glow"><i class="fab fa-facebook text-2xl"></i></a>
        </div>
      </div>
      <!-- Quick Links -->
      <div data-aos="fade-up" data-aos-delay="200">
        <h4 class="text-white font-semibold text-lg mb-4">Quick Links</h4>
        <ul class="space-y-3">
          <li><a href="#home" class="hover:text-[#ef3b1b] transition-colors footer-link">Home</a></li>
          <li><a href="#about" class="hover:text-[#ef3b1b] transition-colors footer-link">About Us</a></li>
          <li><a href="#services" class="hover:text-[#ef3b1b] transition-colors footer-link">Services</a></li>
          <li><a href="#membership" class="hover:text-[#ef3b1b] transition-colors footer-link">Pricing</a></li>
          <li><a href="#booking" class="hover:text-[#ef3b1b] transition-colors footer-link">Booking</a></li>
          <li><a href="#contact" class="hover:text-[#ef3b1b] transition-colors footer-link">Contact</a></li>
        </ul>
      </div>
      <!-- Support -->
      <div data-aos="fade-up" data-aos-delay="300">
        <h4 class="text-white font-semibold text-lg mb-4">Support</h4>
        <ul class="space-y-3">
          <li><a href="#faq-container" class="hover:text-[#ef3b1b] transition-colors footer-link">FAQs</a></li>
          <li><a href="#" class="hover:text-[#ef3b1b] transition-colors">Privacy Policy</a></li>
          <li><a href="#" class="hover:text-[#ef3b1b] transition-colors">Terms of Service</a></li>
        </ul>
      </div>
      <!-- Contact -->
      <div data-aos="fade-up" data-aos-delay="400">
        <h4 class="text-white font-semibold text-lg mb-4">Get in Touch</h4>
        <p class="mb-2 flex items-center"><i class="fas fa-map-marker-alt mr-2 text-[#ef3b1b]"></i>Blk 14 Lot 1 Villa Zaragosa, Turo, Bocaue, Philippines</p>
        <p class="mb-2 flex items-center"><i class="fas fa-phone mr-2 text-[#ef3b1b]"></i>+63 912 345 6789</p>
        <p class="flex items-center"><i class="fas fa-envelope mr-2 text-[#ef3b1b]"></i>villaversogym@gmail.com</p>
      </div>
    </div>
    <div class="border-t border-gray-700 mt-12 pt-6 text-center text-sm text-gray-500" data-aos="fade-up">
      © 2024 VersoGym. All rights reserved.
    </div>
  </footer>

  <!-- Back to Top Button -->
  <div class="group fixed bottom-6 right-6 z-50">
    <button id="backToTop" class="opacity-0 pointer-events-none w-12 h-12 flex items-center justify-center rounded-full bg-gradient-to-r from-[#ef3b1b] to-[#ff6b3a] text-white shadow-lg hover:scale-110 transition-all duration-500 animate-pulse-glow relative btn-hover">
      <i class="fas fa-arrow-up"></i>
      <span class="absolute right-14 top-1/2 -translate-y-1/2 opacity-0 translate-x-2 group-hover:translate-x-0 group-hover:opacity-100 bg-gradient-to-r from-[#ef3b1b] to-[#ff6b3a] text-white text-xs rounded px-2 py-1 whitespace-nowrap transition-all duration-300 shadow-lg tooltip-glow">Back to Top</span>
    </button>
  </div>

  <script>
    // Consolidated Scripts
    document.addEventListener("DOMContentLoaded", () => {
      // Page load animation
      document.body.classList.remove('opacity-0');

      // AOS Init
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          once: true,
          offset: 100,
        });
      }

      // Navbar blur effect
      const header = document.getElementById("site-header");
      window.addEventListener("scroll", () => {
        if (window.scrollY > 10) {
          header.classList.add("backdrop-blur-md", "bg-black/40");
          header.classList.remove("bg-transparent");
        } else {
          header.classList.remove("backdrop-blur-md", "bg-black/40");
          header.classList.add("bg-transparent");
        }
      });

      // Stats count-up
      const statValues = document.querySelectorAll(".stat-value");
      statValues.forEach(el => {
        const target = parseInt(el.getAttribute("data-target"));
        const suffix = el.getAttribute("data-suffix") || "";
        const prefix = el.getAttribute("data-prefix") || "";
        let current = 0;
        const increment = Math.ceil(target / 80);

        const update = () => {
          current += increment;
          if (current > target) current = target;
          el.textContent = `${prefix}${current}${suffix}`;
          if (current < target) requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
      });

      // Smooth scroll for navlinks
      const navLinks = document.querySelectorAll('.nav-link');
      navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          if (targetId.startsWith('#')) {
            const targetElement = document.getElementById(targetId.substring(1));
            if (targetElement) {
              const headerHeight = document.getElementById('site-header').offsetHeight;
              const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
              window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
              });
            }
          } else {
            window.location.href = targetId;
          }
        });
      });

      // Active Navlink Highlighting
      const sections = document.querySelectorAll("section[id]");
      const navLinkElements = document.querySelectorAll("nav ul li a");
      function activateNavLink() {
        let scrollY = window.pageYOffset;
        let currentSectionId = "";

        sections.forEach((section) => {
          const sectionHeight = section.offsetHeight;
          const sectionTop = section.offsetTop - 120;
          if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            currentSectionId = section.getAttribute("id");
          }
        });

        navLinkElements.forEach((link) => {
          link.classList.remove("active");
          if (link.getAttribute("href") === "#" + currentSectionId) {
            link.classList.add("active");
          }
        });
      }
      window.addEventListener("scroll", activateNavLink);
      activateNavLink();

      // FAQ Accordion
      const faqToggles = document.querySelectorAll(".faq-toggle");
      faqToggles.forEach((toggle) => {
          toggle.addEventListener("click", () => {
              const isActive = toggle.classList.contains("active");
              // Close all accordions
              faqToggles.forEach(t => {
                  t.classList.remove("active");
                  t.nextElementSibling.classList.remove('open');
              });
              // If the clicked one wasn't active, open it
              if (!isActive) {
                  toggle.classList.add("active");
                  toggle.nextElementSibling.classList.add('open');
              }
          });
      });

      // Booking Form
      const bookingForm = document.getElementById('booking-form-element');
      const bookingFormContainer = document.getElementById('booking-form-container');
      const confirmationContainer = document.getElementById('confirmation-container');
      const newBookingBtn = document.getElementById('new-booking-btn');
      if(bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const service = document.getElementById('service').value;
            const date = `October ${document.getElementById('selected-date').textContent}, 2024`;
            document.getElementById('conf-name').textContent = name;
            document.getElementById('conf-email').textContent = email;
            document.getElementById('conf-service').textContent = service;
            document.getElementById('conf-date').textContent = date;
            bookingFormContainer.classList.add('hidden');
            confirmationContainer.classList.remove('hidden');
            AOS.refresh();
            window.scrollTo({ top: confirmationContainer.offsetTop - 100, behavior: 'smooth' });
        });
      }
      if(newBookingBtn) {
        newBookingBtn.addEventListener('click', function() {
            confirmationContainer.classList.add('hidden');
            bookingFormContainer.classList.remove('hidden');
            if(bookingForm) bookingForm.reset();
            document.getElementById('name').value = 'John Doe';
            document.getElementById('email').value = 'john.doe@example.com';
            document.getElementById('service').value = 'Personal Training';
            AOS.refresh();
            window.scrollTo({ top: bookingFormContainer.offsetTop - 100, behavior: 'smooth' });
        });
      }

      // Back to Top Button
      const backToTop = document.getElementById("backToTop");
      window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
          backToTop.classList.remove("opacity-0", "pointer-events-none");
          backToTop.classList.add("opacity-100");
        } else {
          backToTop.classList.add("opacity-0", "pointer-events-none");
          backToTop.classList.remove("opacity-100");
        }
      });
      backToTop.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });

      // Simplified Calendar
      const monthYear = document.getElementById('month-year');
      const calendarDays = document.getElementById('calendar-days');
      const prevMonthBtn = document.getElementById('prev-month');
      const nextMonthBtn = document.getElementById('next-month');
      const timeSelect = document.getElementById('time');

      let currentDate = new Date();

      const renderCalendar = () => {
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();

        monthYear.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        calendarDays.innerHTML = '';

        for (let i = 0; i < firstDayOfMonth; i++) {
          const emptyDiv = document.createElement('div');
          calendarDays.appendChild(emptyDiv);
        }

        for (let i = 1; i <= daysInMonth; i++) {
          const dayElement = document.createElement('div');
          dayElement.textContent = i;
          dayElement.classList.add('cursor-pointer', 'w-10', 'h-10', 'flex', 'items-center', 'justify-center', 'rounded-full', 'transition-all', 'duration-300', 'ease-in-out', 'transform', 'hover:scale-110', 'hover:bg-brand-orange', 'hover:text-white');
          if (i === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear()) {
            dayElement.classList.add('bg-primary', 'text-white');
          }
          dayElement.addEventListener('click', () => {
            const selected = document.querySelector('.day-selected');
            if (selected) {
              selected.classList.remove('day-selected', 'bg-primary', 'text-white');
            }
            dayElement.classList.add('day-selected', 'bg-primary', 'text-white');
            document.getElementById('conf-date').textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${i}, ${year}`;
          });
          calendarDays.appendChild(dayElement);
        }
      };

      const populateTime = () => {
        timeSelect.innerHTML = '<option disabled="" value="">Select Time</option>';
        for (let i = 8; i <= 22; i++) { // Changed to 22 for 10 PM
          const hour = i < 10 ? `0${i}` : i;
          const option = document.createElement('option');
          option.value = `${hour}:00`;
          option.textContent = `${hour}:00`;
          timeSelect.appendChild(option);
        }
      };

      prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
      });

      nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
      });

      timeSelect.addEventListener('change', () => {
        document.getElementById('conf-time').textContent = timeSelect.value;
      });

      renderCalendar();
      populateTime();
    });
  </script>
</body>
</html>