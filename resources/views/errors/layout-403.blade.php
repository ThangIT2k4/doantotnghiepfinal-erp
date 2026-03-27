<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>403 — Không có quyền truy cập</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <script src="https://cdn.tailwindcss.com" onload="window.tailwindLoaded = true;"></script>
    <script>
        (function () {
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
                            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        },
                    }
                }
            };
            function configureTailwind() {
                if (typeof tailwind !== 'undefined') {
                    try { tailwind.config = tailwindConfig; } catch (e) { setTimeout(configureTailwind, 50); }
                } else {
                    setTimeout(configureTailwind, 50);
                }
            }
            if (window.tailwindLoaded) configureTailwind();
            else {
                window.addEventListener('load', configureTailwind);
                setTimeout(configureTailwind, 100);
                setTimeout(configureTailwind, 300);
            }
        })();
    </script>
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
        body.error403-landing {
            background:
                radial-gradient(circle at 16% 10%, rgba(100, 158, 255, 0.36) 0%, rgba(100, 158, 255, 0) 34%),
                radial-gradient(circle at 86% 2%, rgba(38, 213, 179, 0.32) 0%, rgba(38, 213, 179, 0) 32%),
                linear-gradient(145deg, #e7f2ff 0%, #f8fbff 48%, #ebf7ff 100%);
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            color: #1e2a4f;
        }
        body.error403-landing::before,
        body.error403-landing::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        body.error403-landing::before {
            background: repeating-linear-gradient(140deg, rgba(255, 255, 255, 0.16) 0px, rgba(255, 255, 255, 0.16) 1px, transparent 1px, transparent 28px);
            opacity: 0.32;
        }
        body.error403-landing::after {
            background: radial-gradient(circle at 70% 74%, rgba(120, 161, 255, 0.22), transparent 52%);
        }
        .error403-shell { position: relative; z-index: 1; }
        .hover-lift { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(102, 126, 234, 0.25);
        }
        .glass-card {
            background: linear-gradient(145deg, var(--glass-bg-strong), var(--glass-bg)) !important;
            border: 1px solid var(--glass-border) !important;
            backdrop-filter: blur(18px) saturate(165%);
            -webkit-backdrop-filter: blur(18px) saturate(165%);
            box-shadow: var(--glass-shadow);
        }
        .glass-card:hover {
            border-color: rgba(87, 137, 255, 0.58) !important;
            box-shadow: 0 32px 62px -34px rgba(35, 76, 175, 0.52), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }
        #error403-pricing .glass-card.border-brand-500 {
            border-color: rgba(58, 118, 255, 0.76) !important;
            box-shadow: 0 34px 66px -34px rgba(34, 86, 214, 0.66), inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }
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
        }
        .infinity-pricing.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .infinity-pricing.amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .infinity-pricing.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .infinity-pricing.purple {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="error403-landing">
    <div class="error403-shell max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        @yield('content')
    </div>
</body>
</html>
