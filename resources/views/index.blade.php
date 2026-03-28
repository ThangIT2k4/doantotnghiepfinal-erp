<!doctype html>
<html lang="vi" class="scroll-smooth">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ZoroRMS – Nền tảng Quản lý Phòng Trọ Chuyên Nghiệp</title>
  <meta name="description" content="Nền tảng SaaS toàn diện cho môi giới và quản lý phòng trọ: quản lý phòng, hợp đồng, thu/chi, thông báo, báo cáo, eKYC, đa chi nhánh." />
  <link rel="icon" type="image/svg+xml"  sizes="any"  href="{{ asset('assets/image/logo2.svg') }}">
  <script>
    // Suppress Tailwind CDN production warning BEFORE loading Tailwind
    (function() {
      const originalWarn = console.warn;
      console.warn = function(...args) {
        // Suppress Tailwind CDN warning and video-related warnings
        if (args.length > 0 && typeof args[0] === 'string') {
          const message = args[0];
          if (message.includes('cdn.tailwindcss.com') || 
              message.includes('should not be used in production') ||
              message.includes('Tailwind CSS') ||
              message.includes('Video element not found')) {
            return; // Suppress warning
          }
        }
        // Call original warn for other warnings
        originalWarn.apply(console, args);
      };
    })();
  </script>
  <script src="https://cdn.tailwindcss.com" onload="window.tailwindLoaded = true;"></script>
  <script>
    // Configure Tailwind after it loads
    (function() {
      const tailwindConfig = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#f0f4ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                400: '#818cf8', 500: '#667eea', 600: '#5568d3', 700: '#4c51bf',
                800: '#434190', 900: '#3c366b'
              },
              accent: {
                50: '#faf5ff', 100: '#f3e8ff', 200: '#e9d5ff', 300: '#d8b4fe',
                400: '#c084fc', 500: '#a855f7', 600: '#9333ea', 700: '#7e22ce',
                800: '#6b21a8', 900: '#581c87'
              }
            },
            boxShadow: { 
              soft: '0 10px 40px -12px rgba(102, 126, 234, 0.15)',
              glow: '0 0 30px rgba(102, 126, 234, 0.4)',
              'glow-lg': '0 0 50px rgba(102, 126, 234, 0.5)',
            },
            animation: {
              'fade-in': 'fadeIn 0.5s ease-in',
              'slide-up': 'slideUp 0.6s ease-out',
              'slide-down': 'slideDown 0.6s ease-out',
              'float': 'float 3s ease-in-out infinite',
              'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
              'bounce-slow': 'bounce 2s infinite',
              'spin-slow': 'spin 3s linear infinite',
              'wiggle': 'wiggle 1s ease-in-out infinite',
            },
            keyframes: {
              fadeIn: {
                '0%': { opacity: '0' },
                '100%': { opacity: '1' }
              },
              slideUp: {
                '0%': { transform: 'translateY(30px)', opacity: '0' },
                '100%': { transform: 'translateY(0)', opacity: '1' }
              },
              slideDown: {
                '0%': { transform: 'translateY(-30px)', opacity: '0' },
                '100%': { transform: 'translateY(0)', opacity: '1' }
              },
              float: {
                '0%, 100%': { transform: 'translateY(0px)' },
                '50%': { transform: 'translateY(-20px)' }
              },
              wiggle: {
                '0%, 100%': { transform: 'rotate(-3deg)' },
                '50%': { transform: 'rotate(3deg)' }
              }
            }
          }
        }
      };
      
      function configureTailwind() {
        if (typeof tailwind !== 'undefined') {
          try {
            tailwind.config = tailwindConfig;
          } catch (e) {
            // Tailwind might not be ready yet, try again
            setTimeout(configureTailwind, 50);
          }
        } else {
          // Tailwind not loaded yet, try again
          setTimeout(configureTailwind, 50);
        }
      }
      
      // Wait for Tailwind to load
      if (window.tailwindLoaded) {
        // Tailwind already loaded
        configureTailwind();
      } else {
        // Wait for onload event
        window.addEventListener('load', configureTailwind);
        // Also try periodically in case onload already fired
        setTimeout(configureTailwind, 100);
        setTimeout(configureTailwind, 300);
      }
    })();
  </script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap');

    :root {
      --glass-bg: rgba(255, 255, 255, 0.42);
      --glass-bg-strong: rgba(255, 255, 255, 0.58);
      --glass-border: rgba(255, 255, 255, 0.66);
      --glass-border-soft: rgba(145, 185, 255, 0.42);
      --glass-shadow: 0 28px 58px -34px rgba(29, 62, 152, 0.58), inset 0 1px 0 rgba(255, 255, 255, 0.86);
    }

    body { 
      background:
        radial-gradient(circle at 16% 10%, rgba(100, 158, 255, 0.36) 0%, rgba(100, 158, 255, 0) 34%),
        radial-gradient(circle at 86% 2%, rgba(38, 213, 179, 0.32) 0%, rgba(38, 213, 179, 0) 32%),
        linear-gradient(145deg, #e7f2ff 0%, #f8fbff 48%, #ebf7ff 100%);
      font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
      color: #1e2a4f;
    }
    body::before,
    body::after {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
    }
    body::before {
      background: repeating-linear-gradient(
        140deg,
        rgba(255, 255, 255, 0.16) 0px,
        rgba(255, 255, 255, 0.16) 1px,
        transparent 1px,
        transparent 28px
      );
      opacity: 0.32;
    }
    body::after {
      background: radial-gradient(circle at 70% 74%, rgba(120, 161, 255, 0.22), transparent 52%);
    }
    .glass-theme > * {
      position: relative;
      z-index: 1;
    }
    .glass { 
      backdrop-filter: saturate(170%) blur(18px);
      -webkit-backdrop-filter: saturate(170%) blur(18px);
      background: linear-gradient(140deg, var(--glass-bg-strong), var(--glass-bg));
      border: 1px solid var(--glass-border);
      box-shadow: var(--glass-shadow);
    }
    .gradient-text {
      background: linear-gradient(135deg, #2468ff 0%, #14c9ab 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .gradient-bg {
      background: linear-gradient(135deg, #2966ff 0%, #11bfa1 100%);
    }
    .gradient-bg-alt {
      background: linear-gradient(135deg, #0ea5e9 0%, #14b8a6 100%);
    }
    .hover-lift {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .hover-lift:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 50px -12px rgba(102, 126, 234, 0.25);
    }
    .section-fade {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .section-fade.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .card-shine {
      position: relative;
      overflow: hidden;
    }
    .card-shine::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
      transform: rotate(45deg);
      animation: shine 3s infinite;
    }

    #header {
      background: linear-gradient(128deg, rgba(255, 255, 255, 0.68), rgba(255, 255, 255, 0.38)) !important;
      border-bottom: 1px solid var(--glass-border-soft) !important;
      backdrop-filter: blur(18px) saturate(165%);
      -webkit-backdrop-filter: blur(18px) saturate(165%);
      box-shadow: 0 12px 34px -20px rgba(31, 56, 131, 0.56), inset 0 1px 0 rgba(255, 255, 255, 0.88);
    }

    #mobile-menu {
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.66), rgba(255, 255, 255, 0.45)) !important;
      border-top: 1px solid rgba(155, 189, 255, 0.38) !important;
      backdrop-filter: blur(16px) saturate(155%);
      -webkit-backdrop-filter: blur(16px) saturate(155%);
    }

    #features,
    #faq,
    #contact {
      background: transparent !important;
    }

    section.py-16.bg-gradient-to-r {
      background: linear-gradient(130deg, rgba(255, 255, 255, 0.48), rgba(224, 241, 255, 0.32)) !important;
      border-color: rgba(137, 166, 239, 0.35) !important;
      backdrop-filter: blur(12px) saturate(145%);
      -webkit-backdrop-filter: blur(12px) saturate(145%);
    }

    #features .section-fade.card-shine,
    .glass-card {
      background: linear-gradient(145deg, var(--glass-bg-strong), var(--glass-bg)) !important;
      border: 1px solid var(--glass-border) !important;
      backdrop-filter: blur(18px) saturate(165%);
      -webkit-backdrop-filter: blur(18px) saturate(165%);
      box-shadow: var(--glass-shadow);
    }

    #features .section-fade.card-shine:hover,
    .glass-card:hover {
      border-color: rgba(87, 137, 255, 0.58) !important;
      box-shadow: 0 32px 62px -34px rgba(35, 76, 175, 0.52), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    #pricing .glass-card.border-brand-500 {
      border-color: rgba(58, 118, 255, 0.76) !important;
      box-shadow: 0 34px 66px -34px rgba(34, 86, 214, 0.66), inset 0 1px 0 rgba(255, 255, 255, 0.92);
    }

    #pricing .space-y-2 > div {
      background-image: none !important;
      background-color: rgba(255, 255, 255, 0.52) !important;
      border-color: rgba(159, 184, 232, 0.42) !important;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }

    #faq .space-y-4 > .section-fade button:hover {
      background: linear-gradient(90deg, rgba(223, 238, 255, 0.56), rgba(255, 255, 255, 0.2)) !important;
    }

    #contact-form input,
    #contact-form select,
    #contact-form textarea {
      background: rgba(255, 255, 255, 0.6) !important;
      border-color: rgba(143, 173, 236, 0.48) !important;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    #contact-form input:focus,
    #contact-form select:focus,
    #contact-form textarea:focus {
      border-color: rgba(54, 129, 255, 0.75) !important;
      box-shadow: 0 0 0 4px rgba(41, 102, 255, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    footer {
      background: linear-gradient(145deg, rgba(13, 23, 46, 0.86), rgba(19, 42, 78, 0.8)) !important;
      border-top-color: rgba(181, 215, 255, 0.22) !important;
      backdrop-filter: blur(15px) saturate(140%);
      -webkit-backdrop-filter: blur(15px) saturate(140%);
    }

    footer .bg-gray-800 {
      background: rgba(255, 255, 255, 0.08) !important;
      border: 1px solid rgba(191, 224, 255, 0.26);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }

    #scrollTop {
      border: 1px solid rgba(226, 243, 255, 0.45);
      backdrop-filter: blur(12px) saturate(145%);
      -webkit-backdrop-filter: blur(12px) saturate(145%);
      box-shadow: 0 20px 42px -22px rgba(41, 95, 208, 0.6);
    }

    @keyframes shine {
      0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
      100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    .text-shadow {
      text-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    }
    /* Infinity symbol for pricing cards */
    .infinity-pricing {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: infinity-pulse 2s ease-in-out infinite;
      position: relative;
      filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.2));
    }
    .infinity-pricing.blue {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.2));
    }
    .infinity-pricing.amber {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.2));
    }
    .infinity-pricing.green {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 2px 4px rgba(16, 185, 129, 0.2));
    }
    .infinity-pricing.purple {
      background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      filter: drop-shadow(0 2px 4px rgba(168, 85, 247, 0.2));
    }
    @keyframes infinity-pulse {
      0%, 100% { 
        transform: scale(1);
        opacity: 1;
      }
      50% { 
        transform: scale(1.1);
        opacity: 0.9;
      }
    }
    .unlimited-text {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      color: #667eea;
      position: relative;
    }
    .unlimited-text .text-switch {
      display: inline-block;
      animation: text-switch 4s ease-in-out infinite;
    }
    @keyframes text-switch {
      0%, 25% { opacity: 1; transform: translateY(0); }
      30%, 35% { opacity: 0; transform: translateY(-10px); }
      40%, 65% { opacity: 1; transform: translateY(0); }
      70%, 75% { opacity: 0; transform: translateY(-10px); }
      80%, 100% { opacity: 1; transform: translateY(0); }
    }
    .feature-illustration {
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .feature-illustration::before {
      content: '';
      position: absolute;
      width: 150%;
      height: 150%;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
      animation: pulse-illustration 4s ease-in-out infinite;
    }
    @keyframes pulse-illustration {
      0%, 100% { transform: scale(1); opacity: 0.5; }
      50% { transform: scale(1.2); opacity: 0.8; }
    }
    /* FAQ Animation Improvements */
    .faq-item {
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .faq-content {
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .animate-fade-in {
      animation: fadeInUp 0.4s ease-out;
    }
    
    @media (prefers-reduced-motion: reduce) {
      .section-fade, .hover-lift, .card-shine::before, .infinity-symbol, .unlimited-text .text-switch, .faq-item, .faq-content, .animate-fade-in {
        transition: none;
        animation: none;
      }
    }

    @media (max-width: 768px) {
      #header,
      #mobile-menu,
      #features .section-fade.card-shine,
      .glass-card {
        backdrop-filter: blur(14px) saturate(140%);
        -webkit-backdrop-filter: blur(14px) saturate(140%);
      }
    }
  </style>
</head>
<body class="glass-theme bg-white text-gray-800">
  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all duration-300" id="header">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3 font-bold text-xl hover:opacity-90 transition-all group">
        <img src="{{ asset('assets/image/logo2.svg') }}" alt="ZoroRMS" class="h-12 w-12 rounded-xl shadow-glow transform group-hover:rotate-6 group-hover:scale-110 transition-all duration-300" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <span class="inline-flex h-12 w-12 rounded-xl gradient-bg items-center justify-center text-white shadow-glow transform group-hover:rotate-6 group-hover:scale-110 transition-all duration-300 animate-pulse-slow" style="display: none;">
          <i class="fas fa-home text-xl"></i>
        </span>
        <span class="gradient-text text-2xl font-extrabold tracking-tight">ZoroRMS</span>
      </a>
      <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
        <a href="#features" class="hover:text-brand-600 transition-colors relative group">
          Tính năng
          <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="#pricing" class="hover:text-brand-600 transition-colors relative group">
          Gói đăng ký
          <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="#faq" class="hover:text-brand-600 transition-colors relative group">
          FAQ
          <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-600 group-hover:w-full transition-all duration-300"></span>
        </a>
       
        <a href="#contact" class="hover:text-brand-600 transition-colors relative group">
          Liên hệ
          <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        {{-- <a href="{{ route('docs.section', ['section' => 'staff']) }}" class="hover:text-brand-600 transition-colors relative group">
          Hướng dẫn
          <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-brand-600 group-hover:w-full transition-all duration-300"></span>
        </a> --}}
      </nav>
      <div class="flex items-center gap-3">
        @auth
        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex px-5 py-2.5 rounded-xl text-brand-600 hover:bg-brand-50 font-semibold transition-all duration-300 border-2 border-transparent hover:border-brand-200">Vào hệ thống</a>
        <a href="{{ route('logout.get') }}" class="hidden sm:inline-flex px-5 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 font-semibold transition-all duration-300 border-2 border-gray-200 hover:border-gray-300">Đăng xuất</a>
        @else
        <a href="{{ route('login') }}" class="hidden sm:inline-flex px-5 py-2.5 rounded-xl text-brand-600 hover:bg-brand-50 font-semibold transition-all duration-300 border-2 border-transparent hover:border-brand-200">Đăng nhập</a>
        @endauth
        <a href="#pricing" class="hidden sm:inline-flex items-center gap-2 px-6 py-3 rounded-xl gradient-bg hover:shadow-glow text-white shadow-lg transform hover:-translate-y-1 hover:scale-105 transition-all duration-300 font-bold">
          <i class="fas fa-rocket"></i>
          <span>Dùng thử miễn phí</span>
        </a>
        <button class="md:hidden p-2 text-gray-600 hover:text-brand-600" id="mobile-menu-btn">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
    <!-- Mobile Menu -->
    <div class="hidden md:hidden border-t border-gray-100 bg-white" id="mobile-menu">
      <nav class="px-4 py-4 space-y-2">
        <a href="#features" class="block px-4 py-2 rounded-lg hover:bg-brand-50 text-gray-700">Tính năng</a>
        <a href="#pricing" class="block px-4 py-2 rounded-lg hover:bg-brand-50 text-gray-700">Gói đăng ký</a>
        <a href="#faq" class="block px-4 py-2 rounded-lg hover:bg-brand-50 text-gray-700">FAQ</a>
        {{-- <a href="{{ route('docs.section', ['section' => 'staff']) }}" class="block px-4 py-2 rounded-lg hover:bg-brand-50 text-gray-700">
          <i class="fas fa-book mr-2"></i>Hướng dẫn
        </a> --}}
        <a href="#contact" class="block px-4 py-2 rounded-lg hover:bg-brand-50 text-gray-700">Liên hệ</a>
        @auth
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded-lg bg-brand-50 text-brand-600 font-medium">Vào hệ thống</a>
        <a href="{{ route('logout.get') }}" class="block px-4 py-2 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">Đăng xuất</a>
        @else
        <a href="{{ route('login') }}" class="block px-4 py-2 rounded-lg bg-brand-50 text-brand-600 font-medium">Đăng nhập</a>
        @endauth
      </nav>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative overflow-hidden max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32">
    <!-- Background decoration -->
    <div class="absolute inset-0 -z-10">
      <div class="absolute top-0 right-0 w-96 h-96 bg-brand-200 rounded-full blur-3xl opacity-30 animate-pulse-slow"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent-200 rounded-full blur-3xl opacity-30 animate-pulse-slow" style="animation-delay: 1s;"></div>
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-r from-brand-100 to-accent-100 rounded-full blur-3xl opacity-20"></div>
    </div>
    
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div class="section-fade">
      
        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6">
          Nền tảng 
          <span class="gradient-text block mt-2">Quản lý Phòng Trọ</span>
          <span class="text-gray-800 block mt-2">Chuyên Nghiệp</span>
        </h1>
        <p class="text-xl text-gray-600 max-w-2xl mb-8 leading-relaxed">
          Hệ thống SaaS toàn diện cho môi giới và chủ trọ: quản lý bất động sản, hợp đồng thuê, thu/chi tự động, thông báo thông minh, báo cáo chi tiết — <strong class="text-brand-600">tất cả trong một nền tảng</strong>.
        </p>
        <div class="flex flex-wrap gap-4 mb-8">
          <a href="#pricing" class="group px-8 py-4 rounded-xl gradient-bg hover:shadow-glow-lg text-white shadow-lg transform hover:-translate-y-1 hover:scale-105 transition-all duration-300 font-bold text-lg flex items-center gap-2">
            <i class="fas fa-rocket group-hover:rotate-12 transition-transform inline-block"></i>
            <span>Bắt đầu dùng thử</span>
          </a>
          <a href="#features" class="px-8 py-4 rounded-xl border-2 border-brand-300 hover:border-brand-500 bg-white hover:bg-brand-50 text-brand-700 hover:text-brand-600 font-bold text-lg transition-all duration-300 flex items-center gap-2 shadow-md hover:shadow-lg">
            <i class="fas fa-play-circle"></i>
            <span>Xem demo</span>
          </a>
        </div>
        <div class="flex items-center gap-6 text-sm text-gray-600">
          <div class="flex items-center gap-2">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>Miễn phí dùng thử</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>Không cần thẻ</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>Hỗ trợ 24/7</span>
          </div>
        </div>
      </div>
      <div class="section-fade">
        <div class="relative animate-float">
          <div class="glass rounded-3xl p-8 md:p-10 shadow-2xl border border-brand-100 hover-lift bg-white/90 backdrop-blur-sm card-shine overflow-hidden" style="position: relative;">
            <div class="flex flex-col items-center justify-center gap-5 text-center">
              <img src="{{ asset('assets/image/logo2.svg') }}" alt="ZORORMS" class="w-44 h-auto md:w-52 rounded-2xl shadow-lg ring-1 ring-brand-100/80">
              <p class="text-3xl md:text-4xl font-extrabold tracking-tight gradient-text" style="font-family: 'Space Grotesk', system-ui, sans-serif;">ZORORMS</p>
            </div>
          </div>
          <!-- Decorative image placeholder -->
          <div class="absolute -z-10 -right-8 -bottom-8 w-64 h-64 bg-gradient-to-br from-brand-200/30 to-amber-200/30 rounded-full blur-3xl"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="py-16 bg-gradient-to-r from-brand-50 to-amber-50 border-y border-gray-100">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div class="section-fade">
          <div class="text-4xl md:text-5xl font-bold gradient-text mb-2">1000+</div>
          <div class="text-sm text-gray-600 font-medium">Khách hàng tin dùng</div>
        </div>
        <div class="section-fade">
          <div class="text-4xl md:text-5xl font-bold gradient-text mb-2">50K+</div>
          <div class="text-sm text-gray-600 font-medium">Phòng trọ quản lý</div>
        </div>
        <div class="section-fade">
          <div class="text-4xl md:text-5xl font-bold gradient-text mb-2">99.9%</div>
          <div class="text-sm text-gray-600 font-medium">Uptime hệ thống</div>
        </div>
        <div class="section-fade">
          <div class="text-4xl md:text-5xl font-bold gradient-text mb-2">24/7</div>
          <div class="text-sm text-gray-600 font-medium">Hỗ trợ khách hàng</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="py-24 bg-white">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 section-fade">
        <p class="text-brand-600 font-semibold text-sm uppercase tracking-wide mb-4">Tính năng</p>
        <h2 class="text-4xl md:text-5xl font-bold mb-4">Mọi thứ bạn cần để quản lý hiệu quả</h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Nền tảng toàn diện với đầy đủ công cụ quản lý bất động sản cho thuê chuyên nghiệp</p>
      </div>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-brand-50 border border-gray-100 hover:border-brand-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M50 50 L150 50 L150 150 L50 150 Z" fill="url(#grad1)" opacity="0.3" class="animate-pulse-slow"/>
              <path d="M60 60 L90 60 L90 90 L60 90 Z" fill="#667eea" class="group-hover:fill-[#764ba2] transition-colors"/>
              <path d="M110 60 L140 60 L140 90 L110 90 Z" fill="#667eea" class="group-hover:fill-[#764ba2] transition-colors"/>
              <path d="M85 100 L115 100 L115 130 L85 130 Z" fill="#764ba2" class="group-hover:fill-[#667eea] transition-colors"/>
              <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl gradient-bg flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-building text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-brand-600 transition-colors text-center">Quản lý Bất động sản</h3>
          <p class="text-gray-600 leading-relaxed text-center">Quản lý toà nhà, căn hộ, phòng trọ với thông tin chi tiết, hình ảnh, tiện ích và tình trạng trống/đang thuê.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-amber-50 border border-gray-100 hover:border-amber-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="40" y="60" width="120" height="80" rx="5" fill="url(#grad2)" opacity="0.3" class="animate-pulse-slow"/>
              <path d="M50 80 L150 80" stroke="#f59e0b" stroke-width="3" class="group-hover:stroke-[#d97706] transition-colors"/>
              <path d="M50 100 L120 100" stroke="#f59e0b" stroke-width="2" class="group-hover:stroke-[#d97706] transition-colors"/>
              <circle cx="140" cy="100" r="8" fill="#f59e0b" class="group-hover:fill-[#d97706] transition-colors animate-pulse-slow"/>
              <defs>
                <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#d97706;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-file-contract text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-amber-600 transition-colors text-center">Hợp đồng Thuê</h3>
          <p class="text-gray-600 leading-relaxed text-center">Quản lý hợp đồng thuê, đặt cọc, hoàn tiền cọc, phụ lục, nhắc hạn thanh toán tự động.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-blue-50 border border-gray-100 hover:border-blue-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="50" y="70" width="100" height="60" rx="3" fill="url(#grad3)" opacity="0.2" class="animate-pulse-slow"/>
              <path d="M60 90 L140 90" stroke="#3b82f6" stroke-width="2" class="group-hover:stroke-[#2563eb] transition-colors"/>
              <path d="M60 110 L100 110" stroke="#3b82f6" stroke-width="2" class="group-hover:stroke-[#2563eb] transition-colors"/>
              <circle cx="130" cy="110" r="12" fill="#3b82f6" class="group-hover:fill-[#2563eb] transition-colors">
                <animate attributeName="r" values="12;14;12" dur="2s" repeatCount="indefinite"/>
              </circle>
              <defs>
                <linearGradient id="grad3" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-500 to-cyan-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-receipt text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-blue-600 transition-colors text-center">Hóa đơn & Thanh toán</h3>
          <p class="text-gray-600 leading-relaxed text-center">Tạo hóa đơn tự động, quản lý thanh toán, công nợ, tích hợp gateway thanh toán trực tuyến.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-purple-50 border border-gray-100 hover:border-purple-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="70" cy="80" r="25" fill="url(#grad4)" opacity="0.3" class="animate-pulse-slow"/>
              <circle cx="130" cy="80" r="25" fill="url(#grad4)" opacity="0.3" class="animate-pulse-slow" style="animation-delay: 0.5s;"/>
              <circle cx="100" cy="130" r="30" fill="url(#grad4)" opacity="0.3" class="animate-pulse-slow" style="animation-delay: 1s;"/>
              <circle cx="70" cy="80" r="15" fill="#a855f7" class="group-hover:fill-[#9333ea] transition-colors"/>
              <circle cx="130" cy="80" r="15" fill="#a855f7" class="group-hover:fill-[#9333ea] transition-colors"/>
              <circle cx="100" cy="130" r="18" fill="#9333ea" class="group-hover:fill-[#a855f7] transition-colors"/>
              <defs>
                <linearGradient id="grad4" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#a855f7;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#9333ea;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-500 to-pink-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-users-cog text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-purple-600 transition-colors text-center">Quản lý Nhân sự</h3>
          <p class="text-gray-600 leading-relaxed text-center">Phân quyền chi tiết, quản lý nhân viên, môi giới, tính hoa hồng và lương tự động.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-green-50 border border-gray-100 hover:border-green-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="60" y="70" width="80" height="60" rx="5" fill="url(#grad5)" opacity="0.2" class="animate-pulse-slow"/>
              <path d="M70 90 L130 90" stroke="#10b981" stroke-width="2" class="group-hover:stroke-[#059669] transition-colors"/>
              <path d="M70 110 L110 110" stroke="#10b981" stroke-width="2" class="group-hover:stroke-[#059669] transition-colors"/>
              <circle cx="130" cy="110" r="6" fill="#10b981" class="group-hover:fill-[#059669] transition-colors">
                <animate attributeName="opacity" values="0.5;1;0.5" dur="1.5s" repeatCount="indefinite"/>
              </circle>
              <defs>
                <linearGradient id="grad5" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-green-500 to-emerald-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-calendar-check text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-green-600 transition-colors text-center">Lịch xem phòng</h3>
          <p class="text-gray-600 leading-relaxed text-center">Quản lý lịch xem phòng, đặt lịch hẹn, thông báo tự động cho khách hàng và nhân viên.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-red-50 border border-gray-100 hover:border-red-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="70" y="80" width="60" height="40" rx="3" fill="url(#grad6)" opacity="0.2" class="animate-pulse-slow"/>
              <circle cx="90" cy="100" r="8" fill="#ef4444" class="group-hover:fill-[#dc2626] transition-colors">
                <animate attributeName="r" values="8;10;8" dur="2s" repeatCount="indefinite"/>
              </circle>
              <circle cx="130" cy="100" r="8" fill="#ef4444" class="group-hover:fill-[#dc2626] transition-colors">
                <animate attributeName="r" values="8;10;8" dur="2s" repeatCount="indefinite" begin="1s"/>
              </circle>
              <path d="M75 120 L125 120" stroke="#ef4444" stroke-width="3" class="group-hover:stroke-[#dc2626] transition-colors"/>
              <defs>
                <linearGradient id="grad6" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#ef4444;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#dc2626;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-red-500 to-pink-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-tools text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-red-600 transition-colors text-center">Bảo trì & Công tơ</h3>
          <p class="text-gray-600 leading-relaxed text-center">Quản lý ticket bảo trì, công tơ đo điện/nước, đọc chỉ số và tính toán tự động.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-indigo-50 border border-gray-100 hover:border-indigo-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="50" y="140" width="20" height="30" fill="#6366f1" class="group-hover:fill-[#4f46e5] transition-colors">
                <animate attributeName="height" values="30;50;30" dur="2s" repeatCount="indefinite"/>
              </rect>
              <rect x="80" y="120" width="20" height="50" fill="#6366f1" class="group-hover:fill-[#4f46e5] transition-colors">
                <animate attributeName="height" values="50;70;50" dur="2s" repeatCount="indefinite" begin="0.3s"/>
              </rect>
              <rect x="110" y="100" width="20" height="70" fill="#6366f1" class="group-hover:fill-[#4f46e5] transition-colors">
                <animate attributeName="height" values="70;90;70" dur="2s" repeatCount="indefinite" begin="0.6s"/>
              </rect>
              <rect x="140" y="110" width="20" height="60" fill="#6366f1" class="group-hover:fill-[#4f46e5] transition-colors">
                <animate attributeName="height" values="60;80;60" dur="2s" repeatCount="indefinite" begin="0.9s"/>
              </rect>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-blue-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-chart-bar text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-indigo-600 transition-colors text-center">Báo cáo & Thống kê</h3>
          <p class="text-gray-600 leading-relaxed text-center">Báo cáo tài chính, doanh thu, công nợ, hiệu suất hoạt động với biểu đồ trực quan.</p>
        </div>
        <div class="p-6 rounded-3xl bg-gradient-to-br from-white to-teal-50 border border-gray-100 hover:border-teal-300 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 group section-fade card-shine">
          <div class="feature-illustration mb-6">
            <svg class="w-32 h-32" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="100" cy="100" r="40" fill="url(#grad7)" opacity="0.2" class="animate-pulse-slow"/>
              <path d="M100 70 L100 100 L115 115" stroke="#14b8a6" stroke-width="4" stroke-linecap="round" class="group-hover:stroke-[#0d9488] transition-colors"/>
              <circle cx="100" cy="100" r="30" fill="none" stroke="#14b8a6" stroke-width="3" class="group-hover:stroke-[#0d9488] transition-colors"/>
              <circle cx="100" cy="100" r="5" fill="#14b8a6" class="group-hover:fill-[#0d9488] transition-colors">
                <animate attributeName="r" values="5;7;5" dur="1.5s" repeatCount="indefinite"/>
              </circle>
              <defs>
                <linearGradient id="grad7" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" style="stop-color:#14b8a6;stop-opacity:1" />
                  <stop offset="100%" style="stop-color:#0d9488;stop-opacity:1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-teal-500 to-cyan-400 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all shadow-md mx-auto">
            <i class="fas fa-bell text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-xl mb-3 group-hover:text-teal-600 transition-colors text-center">Thông báo</h3>
          <p class="text-gray-600 leading-relaxed text-center">Thông báo email và in-app, nhắc nhở thanh toán, hạn hợp đồng, sự kiện quan trọng.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section id="pricing" class="py-24 bg-gradient-to-b from-brand-50/30 via-white to-accent-50/30 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 -z-10">
      <div class="absolute top-1/4 right-0 w-96 h-96 bg-brand-200 rounded-full blur-3xl opacity-20 animate-pulse-slow"></div>
      <div class="absolute bottom-1/4 left-0 w-96 h-96 bg-accent-200 rounded-full blur-3xl opacity-20 animate-pulse-slow" style="animation-delay: 1s;"></div>
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-brand-100 to-accent-100 rounded-full blur-3xl opacity-10"></div>
    </div>
    
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 section-fade">
        <p class="text-brand-600 font-semibold text-sm uppercase tracking-wide mb-4">Gói đăng ký</p>
        <h2 class="text-4xl md:text-5xl font-bold mb-4">Chọn gói phù hợp với bạn</h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Linh hoạt, minh bạch, không ẩn phí. Dùng thử miễn phí 14 ngày cho mọi gói.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($subscriptionPlans as $index => $plan)
          @php
            // Phân loại features
            $limitFeatures = $plan['features']->filter(function($feature) {
                return $feature['type'] === 'limit';
            })->sortBy(function($feature) {
                // Sắp xếp theo thứ tự: properties, units, users, leases
                $order = ['max_properties' => 1, 'max_units' => 2, 'max_users' => 3, 'max_leases' => 4];
                return $order[$feature['key']] ?? 99;
            });
            
            $booleanFeatures = $plan['features']->filter(function($feature) {
                // Boolean features có value = null khi enabled = true (theo IndexController)
                // Các feature disabled đã bị filter ra trong IndexController
                return $feature['type'] === 'boolean';
            })->sortBy('name');
            
            $isPopular = $index === 1 && count($subscriptionPlans) >= 3;
          @endphp
          <div class="section-fade">
            <div class="relative p-6 rounded-3xl bg-white border-2 {{ $isPopular ? 'border-brand-500 shadow-xl' : 'border-gray-200 shadow-lg' }} hover-lift transition-all duration-300 h-full flex flex-col glass-card">
              @if($isPopular)
                <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
                  <span class="px-3 py-1 bg-gradient-to-r from-brand-600 to-brand-700 text-white text-xs font-bold rounded-full shadow-lg">
                    <i class="fas fa-star mr-1"></i>
                    Phổ biến
                  </span>
                </div>
              @endif
              
              <div class="text-center mb-5">
                <h3 class="font-bold text-xl {{ $isPopular ? 'text-brand-600' : 'text-gray-800' }} mb-2">{{ $plan['name'] }}</h3>
                <div class="mt-3">
                  @if($plan['price_monthly'] > 0)
                    <div class="flex items-baseline justify-center gap-2">
                      <span class="text-3xl font-extrabold {{ $isPopular ? 'text-brand-600' : 'text-gray-800' }}">
                        {{ number_format($plan['price_monthly'], 0, ',', '.') }}
                      </span>
                      <span class="text-gray-500 text-sm">{{ $plan['currency'] === 'VND' ? 'đ' : '$' }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">/ tháng</p>
                  @else
                    <div class="text-2xl font-bold text-gray-800">Theo yêu cầu</div>
                    <p class="text-xs text-gray-500 mt-1">Liên hệ để báo giá</p>
                  @endif
                </div>
              </div>
              
              @if($plan['description'])
                <p class="text-center text-gray-600 text-sm mb-5 pb-5 border-b border-gray-200">{{ $plan['description'] }}</p>
              @endif
              
              <!-- Giới hạn -->
              @if($limitFeatures->count() > 0)
              <div class="mb-4">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                  <i class="fas fa-chart-line text-brand-600"></i>
                  Giới hạn
                </h4>
                <div class="space-y-2">
                  @foreach($limitFeatures as $feature)
                    @php
                      $value = $feature['value'];
                      $key = $feature['key'];
                      // Map icon và màu sắc cho từng feature
                      $iconMap = [
                        'max_properties' => ['icon' => 'fa-building', 'color' => 'amber', 'bg' => 'from-amber-50 to-amber-100/50', 'border' => 'border-amber-200', 'text' => 'text-amber-600', 'badge' => 'bg-amber-500'],
                        'max_units' => ['icon' => 'fa-home', 'color' => 'green', 'bg' => 'from-green-50 to-green-100/50', 'border' => 'border-green-200', 'text' => 'text-green-600', 'badge' => 'bg-green-500'],
                        'max_users' => ['icon' => 'fa-users', 'color' => 'purple', 'bg' => 'from-purple-50 to-purple-100/50', 'border' => 'border-purple-200', 'text' => 'text-purple-600', 'badge' => 'bg-purple-500'],
                        'max_leases' => ['icon' => 'fa-file-contract', 'color' => 'blue', 'bg' => 'from-blue-50 to-blue-100/50', 'border' => 'border-blue-200', 'text' => 'text-blue-600', 'badge' => 'bg-blue-500'],
                      ];
                      $style = $iconMap[$key] ?? ['icon' => 'fa-circle', 'color' => 'gray', 'bg' => 'from-gray-50 to-gray-100/50', 'border' => 'border-gray-200', 'text' => 'text-gray-600', 'badge' => 'bg-gray-500'];
                    @endphp
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-gradient-to-r {{ $style['bg'] }} border {{ $style['border'] }}">
                      <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg {{ $style['badge'] }} flex items-center justify-center flex-shrink-0">
                          <i class="fas {{ $style['icon'] }} text-white text-xs"></i>
                        </div>
                        <span class="text-gray-700 font-medium text-xs">{{ $feature['name'] }}</span>
                      </div>
                      <div class="text-right">
                        @if($value == -1)
                          <span class="infinity-pricing {{ $style['color'] }}" style="font-size: 1.25rem;">∞</span>
                        @elseif($value > 0)
                          <span class="text-base font-bold {{ $style['text'] }}">
                            {{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}
                          </span>
                        @else
                          <span class="text-sm font-semibold text-gray-400">0</span>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              @endif
              
              <!-- Tính năng nâng cao -->
              @if($booleanFeatures->count() > 0)
              <div class="mb-4">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                  <i class="fas fa-star text-warning-500"></i>
                  Tính năng nâng cao
                </h4>
                <div class="space-y-2">
                  @foreach($booleanFeatures as $feature)
                    @php
                      $key = $feature['key'];
                      // Map icon cho từng feature
                      $featureIconMap = [
                        'enable_reports' => 'fa-chart-bar',
                        'enable_webhooks' => 'fa-plug',
                        'enable_advanced_permissions' => 'fa-user-shield',
                        'enable_data_export' => 'fa-file-excel',
                        'enable_chat' => 'fa-robot',
                        'enable_priority_support' => 'fa-headset',
                      ];
                      $icon = $featureIconMap[$key] ?? 'fa-check-circle';
                    @endphp
                    <div class="flex items-center gap-2 p-2.5 rounded-lg bg-gradient-to-r from-indigo-50 to-indigo-100/50 border border-indigo-200">
                      <div class="w-7 h-7 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $icon }} text-white text-xs"></i>
                      </div>
                      <span class="text-gray-700 font-medium text-xs">{{ $feature['name'] }}</span>
                      <i class="fas fa-check-circle text-green-500 text-xs ml-auto"></i>
                    </div>
                  @endforeach
                </div>
              </div>
              @endif
              
              <div class="mt-auto">
                @if($plan['price_monthly'] > 0)
                  <a href="#contact-form" class="block w-full text-center px-4 py-3 rounded-xl {{ $isPopular ? 'bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white shadow-lg' : 'bg-gray-100 hover:bg-gray-200 text-gray-800 border-2 border-gray-200' }} font-semibold text-sm transition-all duration-300 hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-phone mr-2"></i>
                    Liên hệ dùng thử
                  </a>
                  @if($plan['trial_days'] > 0)
                    <p class="text-center text-xs text-brand-600 font-semibold mt-2">
                      <i class="fas fa-gift mr-1"></i>
                      Dùng thử miễn phí {{ $plan['trial_days'] }} ngày
                    </p>
                  @endif
                @else
                  <a href="/register" class="block w-full text-center px-4 py-3 rounded-xl {{ $isPopular ? 'bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white shadow-lg' : 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-lg' }} font-semibold text-sm transition-all duration-300 hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-user-plus mr-2"></i>
                    Đăng ký ngay
                  </a>
                  <p class="text-center text-xs text-green-600 font-semibold mt-2">
                    <i class="fas fa-check-circle mr-1"></i>
                    Gói miễn phí - Không cần thanh toán
                  </p>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="col-span-full text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
              <i class="fas fa-box-open text-gray-400 text-3xl"></i>
            </div>
            <p class="text-gray-500 text-lg">Chưa có gói đăng ký nào. Vui lòng quay lại sau.</p>
          </div>
        @endforelse
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="py-24 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 section-fade">
        <p class="text-brand-600 font-semibold text-sm uppercase tracking-wide mb-4">FAQ</p>
        <h2 class="text-4xl md:text-5xl font-bold mb-4">Câu hỏi thường gặp</h2>
        <p class="text-xl text-gray-600">Tìm câu trả lời cho các thắc mắc phổ biến</p>
      </div>
      <div class="space-y-4" x-data="{ openFaq: null }">
        <div class="section-fade glass-card bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl hover:border-brand-300 transition-all duration-500 ease-out overflow-hidden transform hover:scale-[1.01]" 
             x-data="{ open: false }"
             style="transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
          <button @click="open = !open" 
                  class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gradient-to-r hover:from-brand-50 hover:to-transparent transition-all duration-300 group"
                  :class="open ? 'bg-gradient-to-r from-brand-50 to-transparent' : ''">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-3 group-hover:text-brand-600 transition-colors duration-300">
              <i class="fas fa-question-circle text-brand-600 transform transition-transform duration-300" :class="open ? 'rotate-12 scale-110' : 'group-hover:rotate-6'"></i>
              Có miễn phí dùng thử không?
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-all duration-500 ease-in-out transform" 
               :class="open ? 'rotate-180 text-brand-600 scale-110' : 'group-hover:text-brand-500'"
               style="transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;"></i>
          </button>
          <div x-show="open" 
               x-transition:enter="transition-all ease-out duration-500"
               x-transition:enter-start="opacity-0 -translate-y-4 max-h-0"
               x-transition:enter-end="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave="transition-all ease-in duration-400"
               x-transition:leave-start="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave-end="opacity-0 -translate-y-4 max-h-0"
               style="transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);"
               class="px-6 pb-5 text-gray-600 leading-relaxed overflow-hidden">
            <div class="pt-2 animate-fade-in">
              <p>Có, bạn được dùng thử miễn phí <strong class="text-brand-600">14 ngày</strong> cho tất cả các gói. Không cần thẻ tín dụng, không ràng buộc. Bạn có thể hủy bất cứ lúc nào.</p>
            </div>
          </div>
        </div>
        
        <div class="section-fade glass-card bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl hover:border-brand-300 transition-all duration-500 ease-out overflow-hidden transform hover:scale-[1.01]" 
             x-data="{ open: false }"
             style="transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
          <button @click="open = !open" 
                  class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gradient-to-r hover:from-brand-50 hover:to-transparent transition-all duration-300 group"
                  :class="open ? 'bg-gradient-to-r from-brand-50 to-transparent' : ''">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-3 group-hover:text-brand-600 transition-colors duration-300">
              <i class="fas fa-question-circle text-brand-600 transform transition-transform duration-300" :class="open ? 'rotate-12 scale-110' : 'group-hover:rotate-6'"></i>
              Có hỗ trợ nhập dữ liệu cũ không?
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-all duration-500 ease-in-out transform" 
               :class="open ? 'rotate-180 text-brand-600 scale-110' : 'group-hover:text-brand-500'"
               style="transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;"></i>
          </button>
          <div x-show="open" 
               x-transition:enter="transition-all ease-out duration-500"
               x-transition:enter-start="opacity-0 -translate-y-4 max-h-0"
               x-transition:enter-end="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave="transition-all ease-in duration-400"
               x-transition:leave-start="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave-end="opacity-0 -translate-y-4 max-h-0"
               class="px-6 pb-5 text-gray-600 leading-relaxed overflow-hidden">
            <div class="pt-2 animate-fade-in">
              <p>Hệ thống hỗ trợ <strong class="text-brand-600">thêm dữ liệu với người dùng trả phí</strong>. Chúng tôi cũng cung cấp dịch vụ hỗ trợ chuyển đổi dữ liệu miễn phí trong lần đầu đăng ký.</p>
            </div>
          </div>
        </div>
        
        <div class="section-fade glass-card bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl hover:border-brand-300 transition-all duration-500 ease-out overflow-hidden transform hover:scale-[1.01]" 
             x-data="{ open: false }"
             style="transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
          <button @click="open = !open" 
                  class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gradient-to-r hover:from-brand-50 hover:to-transparent transition-all duration-300 group"
                  :class="open ? 'bg-gradient-to-r from-brand-50 to-transparent' : ''">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-3 group-hover:text-brand-600 transition-colors duration-300">
              <i class="fas fa-question-circle text-brand-600 transform transition-transform duration-300" :class="open ? 'rotate-12 scale-110' : 'group-hover:rotate-6'"></i>
              Có thể nâng cấp hoặc hạ cấp gói không?
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-all duration-500 ease-in-out transform" 
               :class="open ? 'rotate-180 text-brand-600 scale-110' : 'group-hover:text-brand-500'"
               style="transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;"></i>
          </button>
          <div x-show="open" 
               x-transition:enter="transition-all ease-out duration-500"
               x-transition:enter-start="opacity-0 -translate-y-4 max-h-0"
               x-transition:enter-end="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave="transition-all ease-in duration-400"
               x-transition:leave-start="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave-end="opacity-0 -translate-y-4 max-h-0"
               class="px-6 pb-5 text-gray-600 leading-relaxed overflow-hidden">
            <div class="pt-2 animate-fade-in">
              <p>Có, bạn có thể <strong class="text-brand-600">nâng cấp hoặc hạ cấp gói</strong> bất cứ lúc nào. Thay đổi sẽ có hiệu lực ngay lập tức.</p>
            </div>
          </div>
        </div>
        
        <div class="section-fade glass-card bg-white border-2 border-gray-200 rounded-2xl shadow-lg hover:shadow-xl hover:border-brand-300 transition-all duration-500 ease-out overflow-hidden transform hover:scale-[1.01]" 
             x-data="{ open: false }"
             style="transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
          <button @click="open = !open" 
                  class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gradient-to-r hover:from-brand-50 hover:to-transparent transition-all duration-300 group"
                  :class="open ? 'bg-gradient-to-r from-brand-50 to-transparent' : ''">
            <h3 class="font-bold text-lg text-gray-800 flex items-center gap-3 group-hover:text-brand-600 transition-colors duration-300">
              <i class="fas fa-question-circle text-brand-600 transform transition-transform duration-300" :class="open ? 'rotate-12 scale-110' : 'group-hover:rotate-6'"></i>
              Dữ liệu có an toàn và bảo mật không?
            </h3>
            <i class="fas fa-chevron-down text-gray-400 transition-all duration-500 ease-in-out transform" 
               :class="open ? 'rotate-180 text-brand-600 scale-110' : 'group-hover:text-brand-500'"
               style="transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;"></i>
          </button>
          <div x-show="open" 
               x-transition:enter="transition-all ease-out duration-500"
               x-transition:enter-start="opacity-0 -translate-y-4 max-h-0"
               x-transition:enter-end="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave="transition-all ease-in duration-400"
               x-transition:leave-start="opacity-100 translate-y-0 max-h-[500px]"
               x-transition:leave-end="opacity-0 -translate-y-4 max-h-0"
               class="px-6 pb-5 text-gray-600 leading-relaxed overflow-hidden">
            <div class="pt-2 animate-fade-in">
              <p>Dữ liệu được mã hóa trong quá trình truyền tải, phân quyền truy cập theo vai trò và sao lưu định kỳ để đảm bảo an toàn tối đa cho hệ thống của bạn.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-20 gradient-bg text-white relative overflow-hidden">
    <div class="absolute inset-0">
      <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse-slow"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-white/5 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 section-fade">
      <h2 class="text-4xl md:text-5xl font-bold mb-4">Sẵn sàng bắt đầu?</h2>
      <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">Tham gia cùng hàng nghìn khách hàng đang sử dụng ZoroRMS để quản lý phòng trọ hiệu quả hơn.</p>
      <div class="flex flex-wrap justify-center gap-4">
        <a href="#pricing" class="group px-8 py-4 rounded-xl bg-white text-brand-600 hover:bg-gray-50 font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-105 transition-all duration-300 flex items-center gap-2">
          <i class="fas fa-rocket group-hover:rotate-12 transition-transform"></i>
          <span>Dùng thử miễn phí</span>
        </a>
        <a href="#contact" class="group px-8 py-4 rounded-xl border-2 border-white/80 hover:border-white text-white hover:bg-white/10 font-bold text-lg transition-all duration-300 flex items-center gap-2 backdrop-blur-sm">
          <i class="fas fa-phone group-hover:scale-110 transition-transform"></i>
          <span>Liên hệ tư vấn</span>
        </a>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section id="contact" class="py-24 bg-white">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 section-fade">
        <p class="text-brand-600 font-semibold text-sm uppercase tracking-wide mb-4">Liên hệ</p>
        <h2 class="text-4xl md:text-5xl font-bold mb-4">Liên hệ & Dùng thử</h2>
        <p class="text-xl text-gray-600">Nhận tư vấn và demo miễn phí từ đội ngũ chuyên nghiệp</p>
      </div>
      
      <!-- Contact Form -->
      <div id="contact-form" class="max-w-2xl mx-auto mb-16 section-fade">
        <div class="bg-white rounded-3xl shadow-xl border-2 border-gray-100 p-8 glass-card">
          <h3 class="text-2xl font-bold text-center mb-6 gradient-text">Đăng ký dùng thử miễn phí</h3>
          <p class="text-center text-gray-600 mb-8">Điền thông tin để chúng tôi liên hệ và hỗ trợ bạn dùng thử gói phù hợp</p>
          
          <form id="trialContactForm" method="POST" action="{{ route('trial.contact.submit') }}">
            @csrf
            <div class="grid md:grid-cols-2 gap-6 mb-6">
              <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                  <i class="fas fa-user mr-2 text-brand-600"></i>Họ và tên <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" required
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 transition-all outline-none"
                       placeholder="Nhập họ và tên">
              </div>
              <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                  <i class="fas fa-phone mr-2 text-brand-600"></i>Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="phone" name="phone" required
                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 transition-all outline-none"
                       placeholder="Nhập số điện thoại">
              </div>
            </div>
            
            <div class="mb-6">
              <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-envelope mr-2 text-brand-600"></i>Email <span class="text-red-500">*</span>
              </label>
              <input type="email" id="email" name="email" required
                     class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 transition-all outline-none"
                     placeholder="Nhập email">
            </div>
            
            <div class="mb-6">
              <label for="plan_interest" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-box mr-2 text-brand-600"></i>Gói quan tâm
              </label>
              <select id="plan_interest" name="plan_interest"
                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 transition-all outline-none">
                <option value="">Chọn gói quan tâm (tùy chọn)</option>
                @foreach($subscriptionPlans as $plan)
                  <option value="{{ $plan['name'] }}">{{ $plan['name'] }}</option>
                @endforeach
              </select>
            </div>
            
            <div class="mb-6">
              <label for="note" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-comment-alt mr-2 text-brand-600"></i>Ghi chú
              </label>
              <textarea id="note" name="note" rows="4"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 transition-all outline-none resize-none"
                        placeholder="Nhập thông tin bổ sung hoặc câu hỏi của bạn..."></textarea>
            </div>
            
            <div class="text-center">
              <button type="submit" class="px-8 py-4 rounded-xl gradient-bg hover:shadow-glow-lg text-white shadow-lg transform hover:-translate-y-1 hover:scale-105 transition-all duration-300 font-bold text-lg flex items-center gap-2 mx-auto">
                <i class="fas fa-paper-plane"></i>
                <span>Gửi yêu cầu</span>
              </button>
              <p class="text-xs text-gray-500 mt-4">
                <i class="fas fa-shield-alt mr-1"></i>
                Thông tin của bạn được bảo mật và chỉ dùng để liên hệ hỗ trợ
              </p>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Contact Methods -->
      <div class="grid md:grid-cols-3 gap-6">
        <a class="group p-8 rounded-3xl border-2 border-gray-200 hover:border-brand-500 bg-white hover:bg-brand-50 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 text-center glass-card" href="mailto:ZoroRMS.qqt@gmail.com">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-500 to-amber-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform">
            <i class="fas fa-envelope text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-lg mb-2 group-hover:text-brand-600">Email</h3>
          <p class="text-gray-600 text-sm">ZoroRMS.qqt@gmail.com</p>
        </a>
        <a class="group p-8 rounded-3xl border-2 border-gray-200 hover:border-brand-500 bg-white hover:bg-brand-50 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 text-center glass-card" href="tel:0988470962">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-500 to-cyan-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform">
            <i class="fas fa-phone text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-lg mb-2 group-hover:text-brand-600">Hotline</h3>
          <p class="text-gray-600 text-sm">0988470962</p>
        </a>
        <a class="group p-8 rounded-3xl border-2 border-gray-200 hover:border-brand-500 bg-white hover:bg-brand-50 shadow-lg hover:shadow-xl hover-lift transition-all duration-300 text-center glass-card" href="#">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-green-500 to-emerald-400 flex items-center justify-center mx-auto mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform">
            <i class="fas fa-comments text-white text-2xl"></i>
          </div>
          <h3 class="font-bold text-lg mb-2 group-hover:text-brand-600">Zalo</h3>
          <p class="text-gray-600 text-sm">0988470962</p>
        </a>
      </div>
    </div>
  </section>

  <footer class="py-12 bg-gray-900 text-gray-300 border-t border-gray-800">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8 mb-8">
        <div>
          <div class="flex items-center gap-2 mb-4">
            <span class="inline-flex h-10 w-10 rounded-xl bg-gradient-to-tr from-brand-500 to-amber-400 items-center justify-center text-white">
              <i class="fas fa-home"></i>
            </span>
            <span class="font-bold text-white text-lg">ZoroRMS</span>
          </div>
          <p class="text-sm text-gray-400">Nền tảng quản lý phòng trọ chuyên nghiệp, hiện đại và dễ sử dụng.</p>
        </div>
        <div>
          <h4 class="font-semibold text-white mb-4">Sản phẩm</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#features" class="hover:text-brand-400 transition-colors">Tính năng</a></li>
            <li><a href="#pricing" class="hover:text-brand-400 transition-colors">Gói đăng ký</a></li>
            <li><a href="#faq" class="hover:text-brand-400 transition-colors">FAQ</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold text-white mb-4">Hỗ trợ</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#contact" class="hover:text-brand-400 transition-colors">Liên hệ</a></li>
            {{-- <li><a href="{{ route('docs.section', ['section' => 'staff']) }}" class="hover:text-brand-400 transition-colors">Tài liệu</a></li>
            <li><a href="{{ route('docs.section', ['section' => 'staff']) }}" class="hover:text-brand-400 transition-colors">Hướng dẫn</a></li> --}}
          </ul>
        </div>
        <div>
          <h4 class="font-semibold text-white mb-4">Kết nối</h4>
          <div class="flex gap-4">
            <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-brand-600 flex items-center justify-center transition-colors">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-brand-600 flex items-center justify-center transition-colors">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="w-10 h-10 rounded-lg bg-gray-800 hover:bg-brand-600 flex items-center justify-center transition-colors">
              <i class="fab fa-linkedin-in"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="pt-8 border-t border-gray-800 text-center text-sm text-gray-400">
        <p>© 2026 ZoroRMS — Xây dựng bởi Thắng (Đồ án tốt nghiệp). All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to top button -->
  <button id="scrollTop" class="fixed bottom-8 right-8 w-12 h-12 rounded-full bg-gradient-to-r from-brand-600 to-brand-700 text-white shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-300 opacity-0 pointer-events-none z-40">
    <i class="fas fa-arrow-up"></i>
  </button>

  <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('hidden');
    });

    // Scroll animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, observerOptions);

    document.querySelectorAll('.section-fade').forEach(el => observer.observe(el));

    // Scroll to top button
    const scrollTopBtn = document.getElementById('scrollTop');
    window.addEventListener('scroll', function() {
      if (window.scrollY > 300) {
        scrollTopBtn.classList.remove('opacity-0', 'pointer-events-none');
        scrollTopBtn.classList.add('opacity-100');
      } else {
        scrollTopBtn.classList.add('opacity-0', 'pointer-events-none');
        scrollTopBtn.classList.remove('opacity-100');
      }
    });

    scrollTopBtn.addEventListener('click', function() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
          e.preventDefault();
          const target = document.querySelector(href);
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Close mobile menu if open
            document.getElementById('mobile-menu')?.classList.add('hidden');
          }
        }
      });
    });

    // Header scroll effect
    let lastScroll = 0;
    const header = document.getElementById('header');
    window.addEventListener('scroll', function() {
      const currentScroll = window.pageYOffset;
      if (currentScroll > 100) {
        header.classList.add('shadow-lg');
      } else {
        header.classList.remove('shadow-lg');
      }
      lastScroll = currentScroll;
    });

    // Trial Contact Form Handler
    const trialContactForm = document.getElementById('trialContactForm');
    if (trialContactForm) {
      trialContactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        // Disable button and show loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
        
        try {
          const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-xl shadow-lg z-50 flex items-center gap-3 animate-slide-down';
            successMessage.innerHTML = `
              <i class="fas fa-check-circle text-2xl"></i>
              <div>
                <strong>Thành công!</strong>
                <p class="text-sm">${data.message}</p>
              </div>
            `;
            document.body.appendChild(successMessage);
            
            // Reset form
            this.reset();
            
            // Remove message after 5 seconds
            setTimeout(() => {
              successMessage.remove();
            }, 5000);
            
            // Scroll to top of form
            document.getElementById('contact-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
          } else {
            // Show error message
            const errorMessage = document.createElement('div');
            errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-xl shadow-lg z-50 flex items-center gap-3 animate-slide-down';
            errorMessage.innerHTML = `
              <i class="fas fa-exclamation-circle text-2xl"></i>
              <div>
                <strong>Lỗi!</strong>
                <p class="text-sm">${data.message || 'Có lỗi xảy ra. Vui lòng thử lại.'}</p>
              </div>
            `;
            document.body.appendChild(errorMessage);
            
            // Remove message after 5 seconds
            setTimeout(() => {
              errorMessage.remove();
            }, 5000);
          }
        } catch (error) {
          console.error('Error:', error);
          const errorMessage = document.createElement('div');
          errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-xl shadow-lg z-50 flex items-center gap-3 animate-slide-down';
          errorMessage.innerHTML = `
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <div>
              <strong>Lỗi!</strong>
              <p class="text-sm">Có lỗi xảy ra. Vui lòng thử lại sau.</p>
            </div>
          `;
          document.body.appendChild(errorMessage);
          
          setTimeout(() => {
            errorMessage.remove();
          }, 5000);
        } finally {
          // Re-enable button
          submitButton.disabled = false;
          submitButton.innerHTML = originalButtonText;
        }
      });
    }
  </script>
</body>
</html>
