<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FSRQuiz — Play. Answer. Win Real Rewards.</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Source+Sans+3:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background-color: #0d0620;
            color: #e8ddff;
            font-family: 'Source Sans 3', sans-serif;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(216, 180, 254, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(216, 180, 254, 0.05) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none;
            z-index: 0;
        }

        .font-display {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
        }

        /* ===== Ambient glow field ===== */
        .glow-field {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .glow-orb {
            position: absolute;
            border-radius: 999px;
            filter: blur(90px);
            mix-blend-mode: screen;
        }

        .glow-orb.o1 {
            width: 50vmax;
            height: 50vmax;
            top: -18vmax;
            left: -14vmax;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.32) 0%, transparent 68%);
            animation: driftA 14s ease-in-out infinite;
        }

        .glow-orb.o2 {
            width: 44vmax;
            height: 44vmax;
            top: 10vh;
            right: -16vmax;
            background: radial-gradient(circle, rgba(217, 70, 239, 0.26) 0%, transparent 68%);
            animation: driftB 17s ease-in-out infinite;
        }

        .glow-orb.o3 {
            width: 36vmax;
            height: 36vmax;
            bottom: -10vmax;
            left: 20vw;
            background: radial-gradient(circle, rgba(244, 191, 50, 0.16) 0%, transparent 70%);
            animation: driftC 12s ease-in-out infinite;
        }

        @keyframes driftA {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(10vw, 8vh) scale(1.15);
            }
        }

        @keyframes driftB {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-8vw, 10vh) scale(1.1);
            }
        }

        @keyframes driftC {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(6vw, -8vh) scale(1.12);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .glow-orb,
            [class*="anim-"] {
                animation: none !important;
            }
        }

        .glass-card {
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.02));
            backdrop-filter: blur(14px);
            border: 1px solid rgba(196, 158, 255, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
            border-radius: 20px;
        }

        .hex-shape {
            position: relative;
            clip-path: polygon(25% 3%, 75% 3%, 100% 50%, 75% 97%, 25% 97%, 0% 50%);
        }

        .grad-text {
            background: linear-gradient(90deg, #f0abfc, #e879f9 35%, #a78bfa 65%, #f4bf32);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffe9b0 0%, #f4bf32 55%, #b8860b 100%);
            color: #2b1b06;
            box-shadow: 0 10px 28px rgba(244, 191, 50, 0.28);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(244, 191, 50, 0.4);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: #e8ddff;
            transition: all .15s ease;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(244, 191, 50, 0.4);
        }

        /* ===== Signature: rotating light border (matches product UI) ===== */
        @keyframes borderTravel {
            0% {
                background-position: 0% 0%;
            }

            100% {
                background-position: 300% 300%;
            }
        }

        .signature-border {
            position: relative;
            border-radius: 28px;
        }

        .signature-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 28px;
            padding: 2px;
            z-index: 0;
            background: linear-gradient(115deg, transparent 0%, transparent 8%, #f0abfc 14%, #e879f9 20%, #a78bfa 26%, #f4bf32 32%, transparent 42%, transparent 100%);
            background-size: 320% 320%;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            animation: borderTravel 4.5s linear infinite;
        }

        @keyframes floatY {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-el {
            animation: floatY 4.5s ease-in-out infinite;
        }

        @keyframes coinFloat {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: .6;
            }

            90% {
                opacity: .35;
            }

            100% {
                transform: translateY(-320px) rotate(360deg);
                opacity: 0;
            }
        }

        .coin-field {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .coin {
            position: absolute;
            bottom: -20px;
            animation: coinFloat 8s linear infinite;
        }

        /* ===== Step banners (brand signature from product) ===== */
        .step-banner {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            padding: 28px 24px;
        }

        .step-banner.s1 {
            background: linear-gradient(115deg, #ffe9b0 0%, #f4bf32 100%);
        }

        .step-banner.s2 {
            background: linear-gradient(115deg, #f4bf32 0%, #b8860b 100%);
        }

        .step-banner.s3 {
            background: linear-gradient(115deg, #d4a017 0%, #7a4a00 100%);
        }

        @keyframes shineSweep {
            0% {
                background-position: -120% 0;
            }

            100% {
                background-position: 220% 0;
            }
        }

        .step-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background: linear-gradient(100deg, transparent 30%, rgba(255, 255, 255, 0.28) 48%, transparent 66%);
            background-size: 250% 100%;
            animation: shineSweep 3.4s ease-in-out infinite;
        }

        .step-num {
            width: 48px;
            height: 48px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 20px;
            background: rgba(13, 6, 32, 0.28);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.4);
            position: relative;
            z-index: 1;
        }

        .stat-num {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }

        .cat-chip {
            transition: transform .18s ease, border-color .18s ease;
        }

        .cat-chip:hover {
            transform: translateY(-4px);
            border-color: rgba(244, 191, 50, 0.4);
        }

        .nav-blur {
            background: rgba(13, 6, 32, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glass);
        }

        ::selection {
            background: rgba(244, 191, 50, 0.3);
        }
    </style>
</head>

<body>

    <div class="glow-field">
        <div class="glow-orb o1"></div>
        <div class="glow-orb o2"></div>
        <div class="glow-orb o3"></div>
    </div>

    <!-- ===================== NAV ===================== -->
    <nav class="nav-blur fixed top-0 w-full z-50">
        <div class="max-w-6xl mx-auto flex items-center justify-between px-6 py-4">
            <a href="{{ route('welcome') }}" class="flex items-center gap-2.5">
                <div class="w-10 h-10 flex-shrink-0">
                    <svg viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="navRing" x1="10%" y1="0%" x2="90%" y2="100%">
                                <stop offset="0%" stop-color="#f0abfc" />
                                <stop offset="35%" stop-color="#e879f9" />
                                <stop offset="70%" stop-color="#a78bfa" />
                                <stop offset="100%" stop-color="#7c3aed" />
                            </linearGradient>
                            <linearGradient id="navBubble" x1="20%" y1="0%" x2="90%" y2="100%">
                                <stop offset="0%" stop-color="#c084fc" />
                                <stop offset="100%" stop-color="#6d28d9" />
                            </linearGradient>
                            <linearGradient id="navStar" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#ffe9b0" />
                                <stop offset="100%" stop-color="#f4bf32" />
                            </linearGradient>
                        </defs>
                        <circle cx="300" cy="300" r="290" fill="#1a0b3d" />
                        <circle cx="300" cy="300" r="255" fill="none" stroke="url(#navRing)"
                            stroke-width="16" />
                        <path
                            d="M300 205c-62 0-112 44-112 100c0 35 20 66 51 84l-13 46l51-27c7 1 15 2 23 2c62 0 112-44 112-100c0-56-50-104-112-104z"
                            fill="url(#navBubble)" />
                        <text x="300" y="335" text-anchor="middle" font-family="Plus Jakarta Sans, Arial, sans-serif"
                            font-weight="800" font-size="96" fill="#ffffff">?</text>
                        <path
                            d="M420 400 l8.5 19 20.8 2.8 -15.1 14.2 3.8 20.8 -18 -10.4 -18 10.4 3.8 -20.8 -15.1 -14.2 20.8 -2.8 z"
                            fill="url(#navStar)" />
                    </svg>
                </div>
                <span class="font-display font-extrabold text-lg tracking-tight">FSRQuiz</span>
            </a>
            @php
                // ✅ Sirf active status wala latest APK fetch karo
                $latestApk = \App\Models\ApkUpdate::where('tenant_id', 1)
                                                ->where('status', 'active')
                                                ->latest()
                                                ->first();
            @endphp
            <div class="hidden md:flex items-center gap-8 text-[14px] font-semibold"
                style="color:rgba(232,221,255,0.7);">
                <a href="{{ route('welcome') }}#how" class="hover:text-white transition">How it works</a>
                <a href="{{ route('welcome') }}#features" class="hover:text-white transition">Features</a>
                <a href="{{ route('welcome') }}#leaderboard" class="hover:text-white transition">Leaderboard</a>
                <a href="{{ route('welcome') }}#categories" class="hover:text-white transition">Categories</a>
                <a href="{{ route('welcome') }}#reviews" class="hover:text-white transition">Reviews</a>
            
                <!-- ✅ NEW: Direct APK Download Button (Sirf tab dikhega jab status 'active' ho) -->
                @if($latestApk && $latestApk->status === 'active')
                    <a href="{{ $latestApk->apk_url }}" download 
                    class="bg-[#7c3aed] hover:bg-[#6d28d9] text-white px-5 py-2 rounded-full transition flex items-center gap-2 shadow-lg shadow-[#7c3aed]/30 hover:scale-105 active:scale-95">
                        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">download</span>
                        Download App
                    </a>
                @endif
            </div>
            <a href="{{ route('fsrapp.login') }}"
                class="btn-primary px-5 py-2.5 rounded-full font-display font-bold text-[13.5px]">Play Now</a>
        </div>
    </nav>
